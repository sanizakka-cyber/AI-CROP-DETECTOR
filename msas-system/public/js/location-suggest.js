/*
 * Place-name suggestions from the farmer's location, for the web forms (scan
 * location, profile). The server (/diagnostics/location-lookup, /api/locations/lookup)
 * returns Community, Address/Landmark and Postal Code suggestions from
 * OpenStreetMap; this decides what goes into the fields:
 *
 * - A suggestion fills a field only if it is empty or still holds an earlier
 *   suggestion. Anything the farmer typed is never overwritten.
 * - A field still holding an earlier suggestion is cleared when the new point
 *   has none (the pin moved somewhere else).
 * - A reading worse than 1 km (a laptop estimating from Wi-Fi) fills nothing.
 *
 * Each field carries its source: 'gps' (a suggestion the farmer has not
 * changed), 'user' (typed or edited), or null (empty).
 *
 * Plain script (window.MsasSuggest) and CommonJS, so the app's Jest suite runs
 * it against the same cases as its own copy (mobile/lib/locationSuggest.js).
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) { module.exports = factory(); } else { root.MsasSuggest = factory(); }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    var FIELDS = ['community', 'landmark', 'postal_code'];
    var MAX_AUTO_FILL_ACCURACY_M = 1000;   // matches config('geocoding.max_auto_fill_accuracy_m')

    /** A device reading this accurate may fill fields; null means a point placed on the map. */
    function canAutoFill(accuracyMeters) {
        if (accuracyMeters === null || accuracyMeters === undefined || accuracyMeters === '') { return true; }
        var m = Number(accuracyMeters);
        return isFinite(m) && m <= MAX_AUTO_FILL_ACCURACY_M;
    }

    function blank(v) { return v === null || v === undefined || String(v).trim() === ''; }

    /**
     * fields: { community: {value, source}, landmark: {...}, postal_code: {...} }
     * suggestions: { community, landmark, postal_code } (strings or null), or null when the lookup failed.
     * Returns new field states; the input is not changed.
     */
    function apply(fields, suggestions) {
        var out = {};
        FIELDS.forEach(function (k) {
            var cur = fields[k] || { value: '', source: null };
            var typedByUser = !blank(cur.value) && cur.source !== 'gps';
            var s = suggestions ? suggestions[k] : null;
            if (typedByUser) {
                out[k] = { value: cur.value, source: 'user' };
            } else if (!blank(s)) {
                out[k] = { value: String(s), source: 'gps' };
            } else if (suggestions && cur.source === 'gps') {
                out[k] = { value: '', source: null };     // the old suggestion no longer fits the point
            } else {
                out[k] = { value: blank(cur.value) ? '' : cur.value, source: blank(cur.value) ? null : cur.source };
            }
        });
        return out;
    }

    /** The farmer typed in a field: it is theirs now (or empty). */
    function edited(value) {
        return { value: value, source: blank(value) ? null : 'user' };
    }

    return { FIELDS: FIELDS, MAX_AUTO_FILL_ACCURACY_M: MAX_AUTO_FILL_ACCURACY_M, canAutoFill: canAutoFill, apply: apply, edited: edited };
}));
