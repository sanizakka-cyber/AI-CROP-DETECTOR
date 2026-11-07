// Sample-collection-location capture for the diagnostic scan screens (spec
// Section 5 / MSAS FarmAI Phase 6). One shared hook used by all four scan
// screens (crop/livestock/soil/pest) so they can never drift into four
// slightly-different location contracts — mirrors the backend's own single
// shared CollectionLocation::createFromRequest() for the same reason.
//
// Two ways to record the location: GPS (watched for up to 30 s for a fresh
// position, the best reading kept, State and LGA filled from the boundaries;
// lib/gpsQuality.js) or by hand. Every message is an i18n key (+ params) so
// LocationCaptureSection shows it in the farmer's language. GPS works with
// no internet: the names are then filled by the server when the scan syncs.
import { useCallback, useEffect, useRef, useState } from 'react';
import { Linking } from 'react-native';
import * as Location from 'expo-location';
import { locationsAPI } from './api';
import { buildLocationApiFields } from './locationFields';
import { captureBest, needsRetry, rating } from './gpsQuality';
import { apply as applySuggestions, canAutoFill, edited } from './locationSuggest';

const NO_SUGGESTIONS = { community: null, landmark: null, postal_code: null };
const EMPTY_FIELDS = { community: { value: '', source: null }, landmark: { value: '', source: null }, postal_code: { value: '', source: null } };

export function useLocationCapture() {
  // Nigeria-only for now — the location dataset (App\Data\NigeriaLocations)
  // and its state/LGA picker only cover Nigeria, matching the web scan
  // form and the mobile registration screen. No setter is exposed since
  // there's nothing to switch to yet.
  const [country] = useState('Nigeria');
  const [state, setState]           = useState('');
  const [lga, setLga]               = useState('');
  // Community, Address/Landmark and Postal Code, each with its source: 'gps'
  // (a suggestion from the location, unchanged), 'user' (typed or edited) or
  // null. Suggestions never overwrite what the farmer typed (lib/locationSuggest.js).
  const [suggestFields, setSuggestFields] = useState(EMPTY_FIELDS);
  const community  = suggestFields.community.value;
  const postalCode = suggestFields.postal_code.value;
  const address    = suggestFields.landmark.value;
  const setCommunity  = useCallback((v) => setSuggestFields(f => ({ ...f, community: edited(v) })), []);
  const setPostalCode = useCallback((v) => setSuggestFields(f => ({ ...f, postal_code: edited(v) })), []);
  const setAddress    = useCallback((v) => setSuggestFields(f => ({ ...f, landmark: edited(v) })), []);
  // A reading worse than 1 km (approximate location): shown on the map, nothing
  // filled or sent until the farmer moves the pin.
  const [needsPin, setNeedsPin] = useState(false);
  const needsPinRef = useRef(false);
  // { latitude, longitude, accuracy } | null — the point that is submitted
  const [coords, setCoords]         = useState(null);
  // 'gps_device' | 'manual_coordinates' | 'administrative_selection' | 'address_entry' | null
  const [captureMethod, setCaptureMethod] = useState(null);
  const [confirmed, setConfirmed]         = useState(false);
  const [differsFromScan, setDiffersFromScan] = useState(false);
  // { type: 'success'|'warning'|'error', key, params?, help?: [key], canOpenSettings?, canRetry?, canManual? }
  const [gpsStatus, setGpsStatus]   = useState(null);
  const [gpsLoading, setGpsLoading] = useState(false);
  // While watching: { first, best } accuracies in metres, for "±18 m → ±7 m".
  const [gpsProgress, setGpsProgress] = useState(null);

  // 'gps' | 'manual' | null until the farmer picks one.
  const [mode, setMode] = useState(null);
  // loc_location_source: gps | manual | gps_edited (names changed) | gps_adjusted (pin moved)
  const [locationSource, setLocationSource] = useState(null);
  const [gpsCapturedAt, setGpsCapturedAt]   = useState(null);
  const [placeNamesFrom, setPlaceNamesFrom] = useState(null); // 'lookup' | 'user' | null
  const [namesNote, setNamesNote]           = useState(null); // { key, params } under the pickers
  const [quality, setQuality]               = useState(null); // excellent | good | fair | low
  const [isMocked, setIsMocked]             = useState(null); // Android "mock location" flag of the fix
  // The device's own fix once the farmer has moved the pin.
  const [originalCoords, setOriginalCoords] = useState(null);
  // The phone's fix when the farmer is scanning away from where the sample was collected.
  const [scanCoords, setScanCoords]         = useState(null);
  const namesFilled = useRef(false); // State/LGA currently hold the lookup's answer
  const lookupFor = useRef(null);    // the fix whose lookup is in flight; a newer fix or manual entry supersedes it
  const fixRef = useRef(null);
  const originalRef = useRef(null);
  const differsRef = useRef(false);
  const captureRef = useRef(null);   // the running captureBest(), so "use this reading now" can stop it

  // Guards every async setState below against firing after the screen that
  // owns this hook has unmounted (e.g. the user navigated away mid-capture).
  const isMounted = useRef(true);
  useEffect(() => () => { isMounted.current = false; captureRef.current?.stop(); }, []);

  // Same backend dataset the web scan form and mobile registration screen
  // already use (App\Data\NigeriaLocations via GET /locations) — never a
  // second, possibly-drifting copy of the state/LGA list.
  const [locations, setLocations] = useState({ countries: ['Nigeria'], states: [] });
  useEffect(() => {
    locationsAPI.list()
      .then(res => setLocations({ countries: res.countries || ['Nigeria'], states: res.states || [] }))
      .catch(() => {}); // keep defaults — a failed fetch just means empty pickers, never blocks the scan
  }, []);

  const lgasForState = state
    ? (locations.states.find(s => s.name === state)?.lgas || [])
    : [];

  // A State/LGA picked by the farmer. Picking first means manual entry; picking
  // over names the GPS lookup filled means the farmer corrected them.
  const namesChangedByUser = useCallback(() => {
    setLocationSource(s => s || 'manual');
    setMode(m => m || 'manual');
    if (namesFilled.current) {
      namesFilled.current = false;
      // A moved pin stays "adjusted"; the names are recorded as the farmer's either way.
      if (!originalRef.current) setLocationSource('gps_edited');
      setPlaceNamesFrom('user');
      setNamesNote({ key: 'loc_names_changed' });
    }
  }, []);

  const selectState = useCallback((name) => {
    // Changing state invalidates any previously selected LGA — never leave
    // an LGA from a different state selected (same rule the registration
    // screen already follows).
    setState(name);
    setLga('');
    // A manual administrative pick is its own provenance — but a GPS fix
    // already on file takes precedence and is never silently overwritten.
    if (captureMethod !== 'gps_device') setCaptureMethod('administrative_selection');
    namesChangedByUser();
  }, [captureMethod, namesChangedByUser]);

  const selectLga = useCallback((name) => {
    setLga(name);
    if (captureMethod !== 'gps_device') setCaptureMethod('administrative_selection');
    namesChangedByUser();
  }, [captureMethod, namesChangedByUser]);

  // Drops the collection-place fix and any names the lookup filled from it.
  const clearCollectionFix = useCallback(() => {
    setCoords(null);
    fixRef.current = null;
    originalRef.current = null;
    setOriginalCoords(null);
    setGpsCapturedAt(null);
    setPlaceNamesFrom(null);
    setQuality(null);
    setIsMocked(null);
    if (namesFilled.current) {
      setState('');
      setLga('');
    }
    namesFilled.current = false;
    lookupFor.current = null;
    setNamesNote(null);
    // Suggestions from the old point go; anything typed stays.
    setSuggestFields(f => applySuggestions(f, NO_SUGGESTIONS));
    needsPinRef.current = false;
    setNeedsPin(false);
  }, []);

  const enterManually = useCallback(() => {
    captureRef.current?.stop();
    captureRef.current = null;
    const keepsNames = Boolean(state) && !namesFilled.current;
    clearCollectionFix();
    setGpsProgress(null);
    setMode('manual');
    setLocationSource('manual');
    setCaptureMethod(keepsNames ? 'administrative_selection' : null);
    setGpsStatus(null);
  }, [clearCollectionFix, state]);

  const setDiffersFromScanPlace = useCallback((on) => {
    setDiffersFromScan(on);
    differsRef.current = on;
    if (!on) {
      setScanCoords(null);
      return;
    }
    // The fix already taken is where the phone is: the scan place.
    if (fixRef.current) {
      setScanCoords(originalRef.current || fixRef.current);
      enterManually();
      setGpsStatus({ type: 'success', key: 'loc_scan_place_saved' });
    }
  }, [enterManually]);

  // accuracy: the device's reading in metres, or null for a point the farmer placed.
  const lookupNames = useCallback(async (fix, accuracy = null) => {
    lookupFor.current = fix;
    setNamesNote({ key: 'loc_looking_up' });
    try {
      const res = await locationsAPI.lookup(fix.latitude, fix.longitude, { accuracy });
      if (!isMounted.current || lookupFor.current !== fix) return;
      if (res?.low_accuracy) {
        setNamesNote({ key: 'loc_low_accuracy' });
        return;
      }
      if (!res?.found) {
        setNamesNote({ key: 'loc_not_found' });
        setSuggestFields(f => applySuggestions(f, NO_SUGGESTIONS));
        return;
      }
      setState(res.state);
      setLga(res.lga);
      namesFilled.current = true;
      setPlaceNamesFrom('lookup');
      setNamesNote({ key: 'loc_filled_from', params: { place: `${res.lga}, ${res.state}` }, attribution: res.attribution || '' });
      // Community, Address/Landmark and Postal Code take a few seconds (outside
      // services): asked for after State/LGA are shown. A failed lookup leaves
      // the fields for the farmer.
      locationsAPI.lookup(fix.latitude, fix.longitude, { accuracy, suggest: true })
        .then((s) => {
          if (!isMounted.current || lookupFor.current !== fix) return;
          setSuggestFields(f => applySuggestions(f, s?.suggestions?.ok ? s.suggestions : null));
        })
        .catch(() => {});
    } catch {
      // Offline or the server is unreachable: keep the coordinates; the
      // server fills State/LGA from them when the scan is sent.
      if (isMounted.current && lookupFor.current === fix) {
        setNamesNote({ key: 'loc_fill_on_submit' });
      }
    }
  }, []);

  const captureGps = useCallback(async () => {
    captureRef.current?.stop();
    setGpsLoading(true);
    setGpsStatus(null);
    setGpsProgress(null);
    setMode('gps');
    const fail = (status) => setGpsStatus({ type: 'error', canRetry: true, canManual: true, ...status });
    try {
      const perm = await Location.requestForegroundPermissionsAsync();
      if (!isMounted.current) return;

      if (!perm.granted) {
        // canAskAgain false: permanently denied (or iOS "restricted") — the
        // only way back is the system Settings app.
        fail(perm.canAskAgain === false
          ? { key: 'loc_err_denied_forever', help: ['loc_help_settings_app', 'loc_help_precise'], canOpenSettings: true }
          : { key: 'loc_err_denied', help: ['loc_help_allow_prompt'] });
        return;
      }

      const enabled = await Location.hasServicesEnabledAsync();
      if (!isMounted.current) return;
      if (!enabled) {
        fail({ key: 'loc_err_services_off', help: ['loc_help_turn_on_location'], canOpenSettings: true });
        return;
      }

      // Approximate location only (Android "Precise location" off / iOS reduced accuracy).
      const approximate = perm.android?.accuracy === 'coarse' || perm.ios?.accuracy === 'reduced';

      const capture = captureBest(Location, {
        onProgress: (best, first) => {
          if (isMounted.current) setGpsProgress({ first: first.accuracy, best: best.accuracy });
        },
      });
      captureRef.current = capture;
      let result;
      try {
        result = await capture.promise;
      } catch (e) {
        if (!isMounted.current || captureRef.current !== capture) return;
        fail(e?.code === 'unavailable'
          ? { key: 'loc_err_unavailable', help: ['loc_help_outside', 'loc_help_turn_on_location'] }
          : { key: 'loc_err_no_signal', help: ['loc_help_outside', 'loc_help_wait'] });
        return;
      }
      if (!isMounted.current || captureRef.current !== capture) return;
      captureRef.current = null;

      const { fix: best, reason } = result;
      const fix = { latitude: best.lat, longitude: best.lon, accuracy: best.accuracy };

      if (differsRef.current) {
        setScanCoords(fix);
        enterManually();
        setGpsStatus({ type: 'success', key: 'loc_scan_place_saved' });
        return;
      }

      clearCollectionFix();
      setCoords(fix);
      fixRef.current = fix;
      const r = rating(best.accuracy);
      setQuality(r);
      setIsMocked(best.mocked === true);
      setGpsCapturedAt(new Date(best.timestamp || Date.now()).toISOString());
      setCaptureMethod('gps_device');
      setLocationSource('gps');

      if (!canAutoFill(best.accuracy)) {
        // Worse than 1 km: fill nothing; the farmer drags the pin to the farm.
        needsPinRef.current = true;
        setNeedsPin(true);
        setGpsStatus({ type: 'warning', key: 'loc_low_accuracy', help: approximate ? ['loc_help_precise'] : [], canRetry: true, canManual: true });
        return;
      }

      const help = [];
      if (needsRetry(r)) help.push('loc_help_outside', 'loc_help_wait', 'loc_help_high_accuracy');
      if (approximate) help.push('loc_help_precise');
      setGpsStatus({
        type: best.mocked || needsRetry(r) || approximate ? 'warning' : 'success',
        key: best.mocked ? 'loc_mocked' : 'loc_captured',
        params: { acc: best.accuracy, rating: r },
        bestIn30: reason === 'window',
        help,
        canRetry: needsRetry(r) || best.mocked,
      });
      await lookupNames(fix, best.accuracy);
    } catch {
      if (isMounted.current) fail({ key: 'loc_err_failed' });
    } finally {
      if (isMounted.current) {
        setGpsLoading(false);
        setGpsProgress(null);
      }
    }
  }, [clearCollectionFix, lookupNames, enterManually]);

  /** "Use this reading now": end the 30-second watch with the best reading so far. */
  const stopCapture = useCallback(() => { captureRef.current?.stop(); }, []);

  /** The farmer dragged the pin: that point is submitted, the device's fix is kept beside it. */
  const adjustPoint = useCallback((latitude, longitude) => {
    if (!fixRef.current) return;
    if (!originalRef.current) {
      originalRef.current = fixRef.current;
      setOriginalCoords(fixRef.current);
    }
    const moved = { latitude, longitude, accuracy: originalRef.current.accuracy };
    fixRef.current = moved;
    setCoords(moved);
    setLocationSource('gps_adjusted');
    if (needsPinRef.current) {
      // The low-accuracy estimate has been placed by the farmer: it can be used now.
      needsPinRef.current = false;
      setNeedsPin(false);
      setGpsStatus(null);
    }
    // Names the lookup filled follow the pin (State, LGA and the suggestions);
    // names the farmer chose stay. A placed pin has no device accuracy.
    if (namesFilled.current || !state) lookupNames(moved, null);
  }, [lookupNames, state]);

  const openLocationSettings = useCallback(() => {
    Linking.openSettings();
  }, []);

  // Serializes to the exact loc_* field set the API expects — see
  // lib/locationFields.js (buildLocationApiFields) for the pure logic and
  // its unit tests.
  const toApiFields = useCallback(
    () => buildLocationApiFields({
      country, state, lga, community, postalCode, address,
      // An estimate worse than 1 km is never sent as the collection point.
      coords: needsPin ? null : coords,
      captureMethod: needsPin ? null : captureMethod,
      locationSource: needsPin ? null : locationSource,
      confirmed, differsFromScan, gpsCapturedAt, placeNamesFrom, scanCoords, isMocked, originalCoords,
      communitySource: suggestFields.community.source,
      postalSource: suggestFields.postal_code.source,
      addressSource: suggestFields.landmark.source,
    }),
    [country, state, lga, community, postalCode, address, coords, captureMethod, confirmed, differsFromScan,
      locationSource, gpsCapturedAt, placeNamesFrom, scanCoords, isMocked, originalCoords, needsPin, suggestFields]
  );

  // Which fields hold a suggestion from the location ("please check" + map credit).
  const suggested = {
    community: suggestFields.community.source === 'gps',
    postalCode: suggestFields.postal_code.source === 'gps',
    address: suggestFields.landmark.source === 'gps',
  };

  return {
    country, state, lga, community, postalCode, address, coords, captureMethod,
    confirmed, differsFromScan, gpsStatus, gpsLoading, gpsProgress, suggested, needsPin,
    mode, locationSource, namesNote, scanCoords, quality, isMocked, originalCoords, gpsCapturedAt,
    locations, lgasForState,
    setCommunity, setPostalCode, setAddress, setConfirmed, setDiffersFromScan: setDiffersFromScanPlace,
    selectState, selectLga, captureGps, stopCapture, adjustPoint, enterManually, toApiFields, openLocationSettings,
  };
}
