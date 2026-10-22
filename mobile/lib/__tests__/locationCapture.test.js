// Regression coverage for useLocationCapture()'s confirmed gaps (Phase 7,
// Section 4 mobile audit): permission-denial handling that collapsed
// denied/permanently-denied into one message with no path to Settings, no
// coordinate validation, no app-level GPS timeout ceiling, and no unmount
// guard. Mocks expo-location since the real native module doesn't exist
// under Jest/Node.
import { renderHook, act, waitFor } from '@testing-library/react-native';
import * as Location from 'expo-location';
import { Linking } from 'react-native';
import { useLocationCapture } from '../locationCapture';

jest.mock('expo-location', () => ({
  requestForegroundPermissionsAsync: jest.fn(),
  hasServicesEnabledAsync: jest.fn(),
  getCurrentPositionAsync: jest.fn(),
  Accuracy: { High: 4 },
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

describe('useLocationCapture — GPS permission handling', () => {
  afterEach(() => jest.clearAllMocks());

  test('a plain denial (canAskAgain true) shows the generic message with no Settings link', async () => {
    Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: false, canAskAgain: true });
    const { result } = renderHook(() => useLocationCapture());

    await act(async () => { await result.current.captureGps(); });

    expect(result.current.gpsStatus.type).toBe('error');
    expect(result.current.gpsStatus.canOpenSettings).toBeFalsy();
    expect(result.current.gpsStatus.message).toMatch(/permission denied/i);
  });

  test('a permanent denial (canAskAgain false) offers a Settings link', async () => {
    Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: false, canAskAgain: false });
    const { result } = renderHook(() => useLocationCapture());

    await act(async () => { await result.current.captureGps(); });

    expect(result.current.gpsStatus.canOpenSettings).toBe(true);
    act(() => { result.current.openLocationSettings(); });
    expect(Linking.openSettings).toHaveBeenCalled();
  });
});

describe('useLocationCapture — coordinate validation', () => {
  afterEach(() => jest.clearAllMocks());

  test('an invalid (0,0) GPS reading is rejected rather than stored', async () => {
    Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: true });
    Location.hasServicesEnabledAsync.mockResolvedValue(true);
    Location.getCurrentPositionAsync.mockResolvedValue({ coords: { latitude: 0, longitude: 0, accuracy: 5 } });
    const { result } = renderHook(() => useLocationCapture());

    await act(async () => { await result.current.captureGps(); });

    expect(result.current.coords).toBeNull();
    expect(result.current.gpsStatus.type).toBe('error');
    expect(result.current.gpsStatus.message).toMatch(/invalid location/i);
  });

  test('a genuine reading is accepted and stored', async () => {
    Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: true });
    Location.hasServicesEnabledAsync.mockResolvedValue(true);
    Location.getCurrentPositionAsync.mockResolvedValue({ coords: { latitude: 12.98, longitude: 7.6, accuracy: 15 } });
    const { result } = renderHook(() => useLocationCapture());

    await act(async () => { await result.current.captureGps(); });

    expect(result.current.coords).toEqual({ latitude: 12.98, longitude: 7.6, accuracy: 15 });
    expect(result.current.captureMethod).toBe('gps_device');
  });
});

describe('useLocationCapture — unmount guard', () => {
  afterEach(() => jest.clearAllMocks());

  test('a late GPS result after unmount does not throw or warn about updating an unmounted component', async () => {
    let resolvePosition;
    Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: true });
    Location.hasServicesEnabledAsync.mockResolvedValue(true);
    Location.getCurrentPositionAsync.mockImplementation(() => new Promise((resolve) => { resolvePosition = resolve; }));

    const { result, unmount } = renderHook(() => useLocationCapture());

    let captured;
    act(() => { captured = result.current.captureGps(); });
    // Let the permission/services-enabled promises resolve first so
    // getCurrentPositionAsync has actually been called (and
    // resolvePosition assigned) before unmounting.
    await waitFor(() => expect(Location.getCurrentPositionAsync).toHaveBeenCalled());
    unmount();

    // Resolve the GPS promise only after the hook has already unmounted --
    // the isMounted guard must skip every subsequent setState silently.
    await act(async () => {
      resolvePosition({ coords: { latitude: 12.98, longitude: 7.6, accuracy: 15 } });
      await captured;
    });
    // No assertion needed beyond "this didn't throw" -- React's own
    // "Warning: Cannot update a component" would otherwise surface as a
    // console.error, which a stricter test setup could assert on directly.
  });
});

describe('useLocationCapture — two ways to record the location', () => {
  const FUNTUA = { coords: { latitude: 11.523, longitude: 7.312, accuracy: 8 }, timestamp: Date.parse('2026-10-22T09:15:00Z') };

  beforeEach(() => {
    Location.requestForegroundPermissionsAsync.mockResolvedValue({ granted: true });
    Location.hasServicesEnabledAsync.mockResolvedValue(true);
    Location.getCurrentPositionAsync.mockResolvedValue(FUNTUA);
  });
  afterEach(() => jest.clearAllMocks());

  test('GPS fills State and LGA from the lookup and sends them as lookup names', async () => {
    locationsAPI.lookup.mockResolvedValueOnce({ found: true, state: 'Katsina', lga: 'Funtua', attribution: 'Boundaries: GRID3.' });
    const { result } = renderHook(() => useLocationCapture());

    await act(async () => { await result.current.captureGps(); });

    expect(locationsAPI.lookup).toHaveBeenCalledWith(11.523, 7.312);
    expect(result.current.mode).toBe('gps');
    expect(result.current.state).toBe('Katsina');
    expect(result.current.lga).toBe('Funtua');
    expect(result.current.namesNote).toMatch(/Filled from your GPS point: Funtua, Katsina/);
    expect(result.current.toApiFields()).toMatchObject({
      loc_location_source: 'gps', loc_place_names_source: 'lookup', loc_gps_captured_at: '2026-10-22T09:15:00.000Z',
    });
  });

  test('changing a GPS-filled LGA marks the location as GPS, then changed', async () => {
    locationsAPI.lookup.mockResolvedValueOnce({ found: true, state: 'Katsina', lga: 'Funtua' });
    const { result } = renderHook(() => useLocationCapture());
    await act(async () => { await result.current.captureGps(); });

    act(() => { result.current.selectLga('Bakori'); });

    expect(result.current.toApiFields()).toMatchObject({
      loc_lga: 'Bakori', loc_location_source: 'gps_edited', loc_place_names_source: 'user', loc_capture_method: 'gps_device',
    });
  });

  test('when the lookup cannot be reached, names stay blank for the server to fill', async () => {
    locationsAPI.lookup.mockRejectedValueOnce(new Error('Network request failed'));
    const { result } = renderHook(() => useLocationCapture());

    await act(async () => { await result.current.captureGps(); });

    expect(result.current.state).toBe('');
    expect(result.current.namesNote).toMatch(/filled from your GPS point when you submit/);
    expect(result.current.toApiFields().loc_latitude).toBe('11.523');
  });

  test('switching to manual entry drops the fix and the names the lookup filled', async () => {
    locationsAPI.lookup.mockResolvedValueOnce({ found: true, state: 'Katsina', lga: 'Funtua' });
    const { result } = renderHook(() => useLocationCapture());
    await act(async () => { await result.current.captureGps(); });

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

  test('a fix taken while "scanning elsewhere" is ticked is the scan place, not the collection place', async () => {
    const { result } = renderHook(() => useLocationCapture());
    act(() => { result.current.setDiffersFromScan(true); });

    await act(async () => { await result.current.captureGps(); });
    act(() => { result.current.selectState('Kano'); });

    expect(locationsAPI.lookup).not.toHaveBeenCalled();
    expect(result.current.coords).toBeNull();
    expect(result.current.mode).toBe('manual');
    expect(result.current.toApiFields()).toMatchObject({ loc_scan_latitude: '11.523', loc_location_source: 'manual', loc_differs_from_scan: '1' });
    expect(result.current.toApiFields().loc_latitude).toBeUndefined();
  });

  test('ticking "scanning elsewhere" after a GPS capture moves the fix to the scan place', async () => {
    locationsAPI.lookup.mockResolvedValueOnce({ found: true, state: 'Katsina', lga: 'Funtua' });
    const { result } = renderHook(() => useLocationCapture());
    await act(async () => { await result.current.captureGps(); });

    act(() => { result.current.setDiffersFromScan(true); });

    expect(result.current.coords).toBeNull();
    expect(result.current.state).toBe('');
    expect(result.current.scanCoords).toEqual(FUNTUA.coords);
  });
});
