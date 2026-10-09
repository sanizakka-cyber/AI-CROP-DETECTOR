// Pure, framework-free serialization: a plain location-state object -> the
// exact loc_* field set the API expects (App\Http\Controllers\Api\
// DiagnoseApiController::locationRules(), Phase 5). Extracted out of
// useLocationCapture() (lib/locationCapture.js) specifically so this logic
// can be unit-tested without React or any Expo/React Native runtime —
// this project has no test runner configured yet (no Jest, no
// react-native-testing-library), so a function with zero framework
// dependencies is the only part of the new location-capture code that can
// be verified by running it directly, without adding new dependencies this
// session cannot safely install and verify blind.
//
// @param {object} locState
// @param {string} locState.country
// @param {string} locState.state
// @param {string} locState.lga
// @param {string} locState.community
// @param {string} locState.postalCode
// @param {string} locState.address
// @param {{latitude:number, longitude:number, accuracy?:number}|null} locState.coords
// @param {string|null} locState.captureMethod
// @param {boolean} locState.confirmed
// @param {boolean} locState.differsFromScan
// @returns {object|null} the loc_* fields, or null when nothing was entered at all
export function buildLocationApiFields(locState) {
  const {
    country = 'Nigeria', state = '', lga = '', community = '',
    postalCode = '', address = '', coords = null,
    captureMethod = null, confirmed = false, differsFromScan = false,
  } = locState || {};

  // No location fields submitted means no location row at all — mirrors
  // CollectionLocation::createFromRequest()'s own rule. Never fabricate a
  // location block just because toApiFields() was called.
  const hasAnything = Boolean(state || lga || community || address || coords);
  if (!hasAnything) return null;

  const fields = { loc_country: country };
  if (state)     fields.loc_state = state;
  if (lga)       fields.loc_lga = lga;
  if (community) fields.loc_community = community;
  if (postalCode) fields.loc_postal_code = postalCode;
  if (address)   fields.loc_address = address;
  if (coords) {
    fields.loc_latitude  = String(coords.latitude);
    fields.loc_longitude = String(coords.longitude);
    if (coords.accuracy != null) fields.loc_accuracy_meters = String(Math.round(coords.accuracy));
  }
  if (captureMethod)   fields.loc_capture_method = captureMethod;
  if (confirmed)        fields.loc_user_confirmed = '1';
  if (differsFromScan)  fields.loc_differs_from_scan = '1';

  return fields;
}
