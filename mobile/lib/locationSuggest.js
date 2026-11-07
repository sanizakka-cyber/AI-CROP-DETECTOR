// Place-name suggestions from the farmer's location (Community, Address/Landmark,
// Postal Code). Same rules as the web forms (msas-system/public/js/location-suggest.js);
// lib/__tests__/locationSuggest.test.js runs both against the same cases.
//
// - A suggestion fills a field only if it is empty or still holds an earlier
//   suggestion. Anything the farmer typed is never overwritten.
// - A field still holding an earlier suggestion is cleared when the new point
//   has none (the pin moved somewhere else).
// - A reading worse than 1 km fills nothing.
//
// Each field carries its source: 'gps' (an unchanged suggestion), 'user'
// (typed or edited), or null (empty).

export const FIELDS = ['community', 'landmark', 'postal_code'];
export const MAX_AUTO_FILL_ACCURACY_M = 1000; // matches config('geocoding.max_auto_fill_accuracy_m')

const blank = (v) => v === null || v === undefined || String(v).trim() === '';

/** A device reading this accurate may fill fields; null means a point placed on the map. */
export function canAutoFill(accuracyMeters) {
  if (accuracyMeters === null || accuracyMeters === undefined || accuracyMeters === '') return true;
  const m = Number(accuracyMeters);
  return Number.isFinite(m) && m <= MAX_AUTO_FILL_ACCURACY_M;
}

/**
 * fields: { community: {value, source}, landmark: {...}, postal_code: {...} }
 * suggestions: { community, landmark, postal_code } or null when the lookup failed.
 */
export function apply(fields, suggestions) {
  const out = {};
  for (const k of FIELDS) {
    const cur = fields[k] || { value: '', source: null };
    const typedByUser = !blank(cur.value) && cur.source !== 'gps';
    const s = suggestions ? suggestions[k] : null;
    if (typedByUser) out[k] = { value: cur.value, source: 'user' };
    else if (!blank(s)) out[k] = { value: String(s), source: 'gps' };
    else if (suggestions && cur.source === 'gps') out[k] = { value: '', source: null };
    else out[k] = { value: blank(cur.value) ? '' : cur.value, source: blank(cur.value) ? null : cur.source };
  }
  return out;
}

/** The farmer typed in a field: it is theirs now (or empty). */
export function edited(value) {
  return { value, source: blank(value) ? null : 'user' };
}
