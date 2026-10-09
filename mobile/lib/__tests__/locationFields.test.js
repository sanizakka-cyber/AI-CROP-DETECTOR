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
