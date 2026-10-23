// useLocationCapture(): GPS watched for up to 30 s for a fresh position (best
// reading kept, stops at 5 m), quality rating, mocked-location flag, pin
// adjustment, State/LGA from the boundary lookup, every failure with what to
// do next, and manual entry. expo-location is faked: the test feeds readings
// into watchPositionAsync's callback.
import { renderHook, act, waitFor } from '@testing-library/react-native';
import * as Location from 'expo-location';
import { Linking } from 'react-native';
import { useLocationCapture } from '../locationCapture';

jest.mock('expo-location', () => ({
  requestForegroundPermissionsAsync: jest.fn(),
  hasServicesEnabledAsync: jest.fn(),
  watchPositionAsync: jest.fn(),
  Accuracy: { High: 4, BestForNavigation: 6 },
}));

jest.mock('../api', () => ({
  locationsAPI: {
    list: jest.fn(() => Promise.resolve({ countries: ['Nigeria'], states: [] })),
    lookup: jest.fn(() => Promise.resolve({ found: false })),
  },
}));
// eslint-disable-next-line import/first
import { locationsAPI } from '../api';

jest.spyOn(Linking, 'openSettings').mockImplementation(() => {});

let emit;
let removed;
const reading = (accuracy, over = {}) => ({ coords: { latitude: 11.523, longitude: 7.312, accuracy }, timestamp: Date.now(), ...over });

beforeEach(() => {
  emit = null;
  removed = 0;
  Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: true });
  Location.hasServicesEnabledAsync.mockResolvedValue(true);
  Location.watchPositionAsync.mockImplementation((opts, cb) => { emit = cb; return Promise.resolve({ remove: () => { removed += 1; } }); });
});
afterEach(() => { jest.clearAllMocks(); jest.useRealTimers(); });

/** Starts a capture, feeds `readings`, and waits for it to finish. */
async function capture(result, readings, { after } = {}) {
  let done;
  await act(async () => { done = result.current.captureGps(); });
  await waitFor(() => expect(emit).not.toBeNull());
  await act(async () => { readings.forEach(r => emit(r)); });
  if (after) await act(async () => { after(); });
  await act(async () => { await done; });
}

describe('permissions and device location', () => {
  test('a plain denial says how to allow it, with Try again and Enter manually instead', async () => {
    Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: false, canAskAgain: true });
    const { result } = renderHook(() => useLocationCapture());
    await act(async () => { await result.current.captureGps(); });
    expect(result.current.gpsStatus).toMatchObject({ type: 'error', key: 'loc_err_denied', canRetry: true, canManual: true, help: ['loc_help_allow_prompt'] });
    expect(result.current.gpsStatus.canOpenSettings).toBeFalsy();
  });

  test('a permanent denial gives the Settings path (Apps → MSAS FarmAI → Location, Precise location)', async () => {
    Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: false, canAskAgain: false });
    const { result } = renderHook(() => useLocationCapture());
    await act(async () => { await result.current.captureGps(); });
    expect(result.current.gpsStatus).toMatchObject({ key: 'loc_err_denied_forever', canOpenSettings: true, help: ['loc_help_settings_app', 'loc_help_precise'] });
    act(() => { result.current.openLocationSettings(); });
    expect(Linking.openSettings).toHaveBeenCalled();
  });

  test('location services off', async () => {
    Location.hasServicesEnabledAsync.mockResolvedValue(false);
    const { result } = renderHook(() => useLocationCapture());
    await act(async () => { await result.current.captureGps(); });
    expect(result.current.gpsStatus).toMatchObject({ key: 'loc_err_services_off', canOpenSettings: true, canManual: true });
    expect(Location.watchPositionAsync).not.toHaveBeenCalled();
  });
});

describe('best reading over 30 seconds', () => {
  test('watches with the highest accuracy, shows progress, stops at 5 m and keeps the best', async () => {
    const { result } = renderHook(() => useLocationCapture());
    let done;
    await act(async () => { done = result.current.captureGps(); });
    await waitFor(() => expect(emit).not.toBeNull());
    expect(Location.watchPositionAsync.mock.calls[0][0]).toMatchObject({ accuracy: 6, timeInterval: 1000, distanceInterval: 0 });

    await act(async () => { emit(reading(18)); emit(reading(7)); });
    expect(result.current.gpsProgress).toEqual({ first: 18, best: 7 });

    await act(async () => { emit(reading(4.6)); });
    await act(async () => { await done; });
    expect(removed).toBe(1);
    expect(result.current.coords).toEqual({ latitude: 11.523, longitude: 7.312, accuracy: 4.6 });
    expect(result.current.quality).toBe('excellent');
    expect(result.current.gpsStatus).toMatchObject({ type: 'success', key: 'loc_captured', params: { acc: 4.6, rating: 'excellent' } });
    expect(result.current.toApiFields()).toMatchObject({ loc_accuracy_meters: '5', loc_capture_method: 'gps_device', loc_location_source: 'gps', loc_is_mocked: '0' });
  });

  test('a cached position from before the capture is never used', async () => {
    jest.useFakeTimers({ doNotFake: ['nextTick', 'setImmediate'] });
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(3, { timestamp: Date.now() - 120000 })], { after: () => jest.advanceTimersByTime(30000) });
    expect(result.current.coords).toBeNull();
    expect(result.current.gpsStatus).toMatchObject({ key: 'loc_err_no_signal', canRetry: true, canManual: true, help: ['loc_help_outside', 'loc_help_wait'] });
  });

  test('after 30 s a Fair reading is kept, with tips and Try again', async () => {
    jest.useFakeTimers({ doNotFake: ['nextTick', 'setImmediate'] });
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(40), reading(22)], { after: () => jest.advanceTimersByTime(30000) });
    expect(result.current.coords.accuracy).toBe(22);
    expect(result.current.quality).toBe('fair');
    expect(result.current.gpsStatus).toMatchObject({ type: 'warning', key: 'loc_captured', bestIn30: true, canRetry: true });
    expect(result.current.gpsStatus.help).toEqual(['loc_help_outside', 'loc_help_wait', 'loc_help_high_accuracy']);
  });

  test('"Use this reading now" takes the best so far', async () => {
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(30), reading(12)], { after: () => result.current.stopCapture() });
    expect(result.current.coords.accuracy).toBe(12);
    expect(result.current.quality).toBe('good');
  });

  test('a mocked location is flagged, warned about and sent as such', async () => {
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(4, { mocked: true })]);
    expect(result.current.isMocked).toBe(true);
    expect(result.current.gpsStatus).toMatchObject({ type: 'warning', key: 'loc_mocked', canRetry: true });
    expect(result.current.toApiFields().loc_is_mocked).toBe('1');
  });

  test('approximate location only (Precise location off) adds the tip to turn it on', async () => {
    Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: true, android: { accuracy: 'coarse' } });
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(4)]);
    expect(result.current.gpsStatus.help).toContain('loc_help_precise');
    expect(result.current.gpsStatus.type).toBe('warning');
  });

  test('a late reading after unmount does nothing', async () => {
    const { result, unmount } = renderHook(() => useLocationCapture());
    let done;
    await act(async () => { done = result.current.captureGps(); });
    await waitFor(() => expect(emit).not.toBeNull());
    unmount();
    await act(async () => { emit(reading(3)); await done; });
  });
});

describe('place names and the map pin', () => {
  test('GPS fills State and LGA from the lookup, sent as lookup names', async () => {
    locationsAPI.lookup.mockResolvedValueOnce({ found: true, state: 'Katsina', lga: 'Funtua', attribution: 'Boundaries: GRID3.' });
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(4)]);
    expect(locationsAPI.lookup).toHaveBeenCalledWith(11.523, 7.312);
    expect(result.current.state).toBe('Katsina');
    expect(result.current.namesNote).toMatchObject({ key: 'loc_filled_from', params: { place: 'Funtua, Katsina' }, attribution: 'Boundaries: GRID3.' });
    expect(result.current.toApiFields()).toMatchObject({ loc_location_source: 'gps', loc_place_names_source: 'lookup' });
  });

  test('changing a GPS-filled LGA marks the location as GPS, then changed', async () => {
    locationsAPI.lookup.mockResolvedValueOnce({ found: true, state: 'Katsina', lga: 'Funtua' });
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(4)]);
    act(() => { result.current.selectLga('Bakori'); });
    expect(result.current.toApiFields()).toMatchObject({ loc_lga: 'Bakori', loc_location_source: 'gps_edited', loc_place_names_source: 'user' });
  });

  test('without internet the coordinates stay and the names are left for the server', async () => {
    locationsAPI.lookup.mockRejectedValueOnce(new Error('Network request failed'));
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(4)]);
    expect(result.current.state).toBe('');
    expect(result.current.namesNote).toEqual({ key: 'loc_fill_on_submit' });
    expect(result.current.toApiFields().loc_latitude).toBe('11.523');
  });

  test('dragging the pin submits the farmer\'s point and keeps the device\'s fix', async () => {
    locationsAPI.lookup.mockResolvedValueOnce({ found: true, state: 'Katsina', lga: 'Funtua' })
      .mockResolvedValueOnce({ found: true, state: 'Katsina', lga: 'Bakori' });
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(4)]);
    await act(async () => { result.current.adjustPoint(11.53, 7.33); });
    expect(result.current.locationSource).toBe('gps_adjusted');
    expect(result.current.lga).toBe('Bakori'); // names the lookup filled follow the pin
    expect(result.current.toApiFields()).toMatchObject({
      loc_latitude: '11.53', loc_longitude: '7.33', loc_location_source: 'gps_adjusted',
      loc_gps_original_latitude: '11.523', loc_gps_original_longitude: '7.312',
    });
  });
});

describe('manual entry and scanning elsewhere', () => {
  test('switching to manual entry drops the fix and the names the lookup filled', async () => {
    locationsAPI.lookup.mockResolvedValueOnce({ found: true, state: 'Katsina', lga: 'Funtua' });
    const { result } = renderHook(() => useLocationCapture());
    await capture(result, [reading(4)]);
    act(() => { result.current.enterManually(); });
    expect(result.current.mode).toBe('manual');
    expect(result.current.coords).toBeNull();
    expect(result.current.state).toBe('');
    expect(result.current.toApiFields()).toBeNull();
  });

  test('picking a State first is manual entry', async () => {
    const { result } = renderHook(() => useLocationCapture());
    await act(async () => {}); // let the /locations fetch settle
    act(() => { result.current.selectState('Kano'); });
    expect(result.current.mode).toBe('manual');
    expect(result.current.toApiFields()).toMatchObject({ loc_state: 'Kano', loc_location_source: 'manual', loc_capture_method: 'administrative_selection' });
  });

  test('a fix taken while "scanning elsewhere" is ticked is the scan place', async () => {
    const { result } = renderHook(() => useLocationCapture());
    act(() => { result.current.setDiffersFromScan(true); });
    await capture(result, [reading(4)]);
    act(() => { result.current.selectState('Kano'); });
    expect(locationsAPI.lookup).not.toHaveBeenCalled();
    expect(result.current.coords).toBeNull();
    expect(result.current.toApiFields()).toMatchObject({ loc_scan_latitude: '11.523', loc_location_source: 'manual', loc_differs_from_scan: '1' });
  });
});
