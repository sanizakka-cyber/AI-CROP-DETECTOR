// Unit tests for buildLocationApiFields() (lib/locationFields.js) — the one
// piece of the mobile location-capture feature with zero React/Expo
// dependency, and so the only part a plain function-level test can cover
// in this project today.
//
// NOTE FOR WHOEVER WIRES UP A TEST RUNNER: this project has no Jest/test
// infrastructure configured (no jest.config, no "test" script, no
// react-native-testing-library in package.json) as of 2026-10-09. This
// file uses standard Jest globals (describe/test/expect) so it runs
// unmodified once jest-expo is added — until then it cannot be executed
// via `npm test`. It WAS verified for real this session by extracting the
// same assertions into a plain Node script with a minimal expect() shim
// (no Jest required) — see the audit report for that run's actual output.
// Component-level tests (permission prompts, GPS timeout UI, network
// failure during the GET /locations fetch, offline queuing) need
// react-native-testing-library + mocked expo-location/expo-router and are
// NOT covered here — flagged as follow-up work, not silently skipped.
import { buildLocationApiFields } from '../locationFields';

describe('buildLocationApiFields', () => {
  test('returns null when nothing was entered at all', () => {
    expect(buildLocationApiFields({})).toBeNull();
    expect(buildLocationApiFields({ country: 'Nigeria' })).toBeNull();
  });

  test('never blocks a scan just because location is empty — null, not a fabricated block', () => {
    // Confirms the "optional, never required" contract item by item.
    const result = buildLocationApiFields({ country: 'Nigeria', state: '', lga: '', coords: null });
    expect(result).toBeNull();
  });

  test('manual state/LGA selection produces the expected administrative fields', () => {
    const result = buildLocationApiFields({
      country: 'Nigeria', state: 'Katsina', lga: 'Funtua',
      captureMethod: 'administrative_selection',
    });
    expect(result).toEqual({
      loc_country: 'Nigeria',
      loc_state: 'Katsina',
      loc_lga: 'Funtua',
      loc_capture_method: 'administrative_selection',
    });
  });

  test('GPS coordinates are serialized as strings with rounded accuracy', () => {
    const result = buildLocationApiFields({
      country: 'Nigeria',
      coords: { latitude: 11.1965, longitude: 7.3167, accuracy: 14.7 },
      captureMethod: 'gps_device',
      confirmed: true,
    });
    expect(result.loc_latitude).toBe('11.1965');
    expect(result.loc_longitude).toBe('7.3167');
    expect(result.loc_accuracy_meters).toBe('15');
    expect(result.loc_capture_method).toBe('gps_device');
    expect(result.loc_user_confirmed).toBe('1');
  });

  test('a GPS fix with no accuracy reading omits loc_accuracy_meters rather than sending "null"', () => {
    const result = buildLocationApiFields({
      country: 'Nigeria',
      coords: { latitude: 11.1965, longitude: 7.3167, accuracy: null },
    });
    expect(result.loc_accuracy_meters).toBeUndefined();
  });

  test('a denied/failed GPS attempt (coords stays null) still allows a manual-entry submission', () => {
    // Simulates the real sequence: captureGps() failed and left coords
    // null, but the user still typed a postal code / address afterward.
    const result = buildLocationApiFields({
      country: 'Nigeria', address: 'Near Funtua market', postalCode: '', coords: null, captureMethod: null,
    });
    expect(result).toEqual({ loc_country: 'Nigeria', loc_address: 'Near Funtua market' });
  });

  test('"differs from scan location" flag round-trips correctly', () => {
    const result = buildLocationApiFields({ country: 'Nigeria', state: 'Kano', differsFromScan: true });
    expect(result.loc_differs_from_scan).toBe('1');
  });

  test('an unchecked "differs from scan" flag is omitted, not sent as "0"', () => {
    const result = buildLocationApiFields({ country: 'Nigeria', state: 'Kano', differsFromScan: false });
    expect(result.loc_differs_from_scan).toBeUndefined();
  });

  test('a blank postal code is omitted rather than submitted as an empty string', () => {
    const result = buildLocationApiFields({ country: 'Nigeria', state: 'Kano', postalCode: '' });
    expect('loc_postal_code' in result).toBe(false);
  });
});

describe('buildLocationApiFields — the two ways of recording a location', () => {
  test('a GPS capture sends how it was entered, when, and that the names came from the lookup', () => {
    const result = buildLocationApiFields({
      state: 'Katsina', lga: 'Funtua', coords: { latitude: 11.523, longitude: 7.312, accuracy: 8 },
      captureMethod: 'gps_device', locationSource: 'gps', gpsCapturedAt: '2026-10-22T09:15:00.000Z', placeNamesFrom: 'lookup',
    });
    expect(result).toMatchObject({
      loc_location_source: 'gps', loc_gps_captured_at: '2026-10-22T09:15:00.000Z', loc_place_names_source: 'lookup',
    });
  });

  test('GPS with no names yet (offline) sends the coordinates only, for the server to name', () => {
    const result = buildLocationApiFields({
      coords: { latitude: 11.523, longitude: 7.312 }, captureMethod: 'gps_device', locationSource: 'gps', placeNamesFrom: 'lookup',
    });
    expect(result.loc_state).toBeUndefined();
    expect(result.loc_place_names_source).toBeUndefined();
    expect(result.loc_latitude).toBe('11.523');
  });

  test('a capture time without a fix is not sent', () => {
    const result = buildLocationApiFields({ state: 'Kano', locationSource: 'manual', gpsCapturedAt: '2026-10-22T09:15:00.000Z' });
    expect(result.loc_gps_captured_at).toBeUndefined();
    expect(result.loc_location_source).toBe('manual');
  });

  test('the scan place is sent apart from the collection place, only while "scanning elsewhere" is ticked', () => {
    const scanCoords = { latitude: 12.0022, longitude: 8.592, accuracy: 14.6 };
    const ticked = buildLocationApiFields({ state: 'Katsina', lga: 'Funtua', locationSource: 'manual', differsFromScan: true, scanCoords });
    expect(ticked).toMatchObject({ loc_scan_latitude: '12.0022', loc_scan_longitude: '8.592', loc_scan_accuracy_meters: '15' });
    expect(ticked.loc_latitude).toBeUndefined();

    const unticked = buildLocationApiFields({ state: 'Katsina', differsFromScan: false, scanCoords });
    expect(unticked.loc_scan_latitude).toBeUndefined();
  });
});
