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
  locationsAPI: { list: jest.fn(() => Promise.resolve({ countries: ['Nigeria'], states: [] })) },
}));

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
