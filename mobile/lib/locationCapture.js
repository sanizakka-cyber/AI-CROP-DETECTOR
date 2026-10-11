// Sample-collection-location capture for the diagnostic scan screens (spec
// Section 5 / MSAS FarmAI Phase 6). One shared hook used by all four scan
// screens (crop/livestock/soil/pest) so they can never drift into four
// slightly-different location contracts — mirrors the backend's own single
// shared CollectionLocation::createFromRequest() for the same reason.
//
// Produces exactly the loc_* field set the API already accepts (added in
// Phase 5, see App\Http\Controllers\Api\DiagnoseApiController::
// locationRules()) — this is additive on top of an existing, already-tested
// contract, not a new one.
import { useCallback, useEffect, useRef, useState } from 'react';
import { Linking } from 'react-native';
import * as Location from 'expo-location';
import { locationsAPI } from './api';
import { buildLocationApiFields } from './locationFields';

// App-level ceiling on top of getCurrentPositionAsync's own behavior -- a
// native hang (not a rejection) previously left gpsLoading stuck true
// forever, with no escape for the user. Races the real GPS fetch against
// this timeout and always resolves one way or the other.
const GPS_TIMEOUT_MS = 20000;

function isValidCoordinate(latitude, longitude) {
  return Number.isFinite(latitude) && Number.isFinite(longitude)
    && Math.abs(latitude) <= 90 && Math.abs(longitude) <= 180
    && !(latitude === 0 && longitude === 0); // (0,0) is the ocean off West Africa -- a classic "GPS fix never actually resolved" sentinel, not a real farm location.
}

export function useLocationCapture() {
  // Nigeria-only for now — the location dataset (App\Data\NigeriaLocations)
  // and its state/LGA picker only cover Nigeria, matching the web scan
  // form and the mobile registration screen. No setter is exposed since
  // there's nothing to switch to yet.
  const [country] = useState('Nigeria');
  const [state, setState]           = useState('');
  const [lga, setLga]               = useState('');
  const [community, setCommunity]   = useState('');
  const [postalCode, setPostalCode] = useState('');
  const [address, setAddress]       = useState('');
  // { latitude, longitude, accuracy } | null
  const [coords, setCoords]         = useState(null);
  // 'gps_device' | 'manual_coordinates' | 'administrative_selection' | 'address_entry' | null
  const [captureMethod, setCaptureMethod] = useState(null);
  const [confirmed, setConfirmed]         = useState(false);
  const [differsFromScan, setDiffersFromScan] = useState(false);
  const [gpsStatus, setGpsStatus]   = useState(null); // { type: 'success'|'warning'|'error', message, canOpenSettings? }
  const [gpsLoading, setGpsLoading] = useState(false);

  // Guards every async setState below against firing after the screen that
  // owns this hook has unmounted (e.g. the user navigated away mid-capture).
  const isMounted = useRef(true);
  useEffect(() => () => { isMounted.current = false; }, []);

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

  const selectState = useCallback((name) => {
    // Changing state invalidates any previously selected LGA — never leave
    // an LGA from a different state selected (same rule the registration
    // screen already follows).
    setState(name);
    setLga('');
    // A manual administrative pick is its own provenance — but a GPS fix
    // already on file takes precedence and is never silently overwritten
    // by picking a state afterward (e.g. to add detail the GPS fix can't
    // provide, like ward/community).
    if (captureMethod !== 'gps_device') setCaptureMethod('administrative_selection');
  }, [captureMethod]);

  const selectLga = useCallback((name) => {
    setLga(name);
    if (captureMethod !== 'gps_device') setCaptureMethod('administrative_selection');
  }, [captureMethod]);

  const captureGps = useCallback(async () => {
    setGpsLoading(true);
    setGpsStatus(null);
    try {
      const perm = await Location.requestForegroundPermissionsAsync();
      if (!isMounted.current) return;

      if (!perm.granted) {
        // canAskAgain distinguishes a plain "denied, but the OS prompt can
        // still be shown again" from a permanently-denied/iOS-"restricted"
        // state, where the only way back is the system Settings app --
        // previously both collapsed into the same generic message with no
        // path forward for the permanent case.
        if (!perm.canAskAgain) {
          setGpsStatus({
            type: 'error',
            canOpenSettings: true,
            message: 'Location access is turned off for this app and can’t be requested again here. Open Settings to allow it, or select your state/LGA manually below.',
          });
        } else {
          setGpsStatus({ type: 'error', message: 'Location permission denied. You can still select your state/LGA manually below.' });
        }
        return;
      }

      const enabled = await Location.hasServicesEnabledAsync();
      if (!isMounted.current) return;
      if (!enabled) {
        setGpsStatus({ type: 'error', message: 'Location services are turned off on this device. You can still select your state/LGA manually below.' });
        return;
      }

      // Races the real GPS fetch against an app-level timeout so a native
      // hang (not a rejection) can never leave gpsLoading stuck true
      // forever -- getCurrentPositionAsync's own timeout option only
      // covers some failure modes, not an outright stall. The timer is
      // cleared as soon as either side settles so a fast GPS fix doesn't
      // leave a dangling 20s timer alive in the background.
      let timeoutId;
      const pos = await Promise.race([
        Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High }),
        new Promise((_, reject) => { timeoutId = setTimeout(() => reject({ code: 'E_LOCATION_TIMEOUT' }), GPS_TIMEOUT_MS); }),
      ]).finally(() => clearTimeout(timeoutId));
      if (!isMounted.current) return;

      if (!isValidCoordinate(pos.coords.latitude, pos.coords.longitude)) {
        setGpsStatus({ type: 'error', message: 'The device returned an invalid location reading. You can still select your state/LGA manually below.' });
        return;
      }

      const acc = Math.round(pos.coords.accuracy || 0);

      setCoords({ latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: pos.coords.accuracy });
      setCaptureMethod('gps_device');
      setGpsStatus({
        type: acc > 100 ? 'warning' : 'success',
        message: acc > 100
          ? `Location captured (±${acc}m). Accuracy is low — consider moving to open ground and trying again, or proceed with this estimate.`
          : `Location captured (±${acc}m). Please still confirm the state/LGA below.`,
      });
    } catch (e) {
      if (!isMounted.current) return;
      const timedOut = e?.code === 'E_LOCATION_TIMEOUT' || /timeout/i.test(e?.message || '');
      setGpsStatus({
        type: 'error',
        message: timedOut
          ? 'Location request timed out. You can still select your state/LGA manually below.'
          : 'Could not get your location. You can still select your state/LGA manually below.',
      });
    } finally {
      if (isMounted.current) setGpsLoading(false);
    }
  }, []);

  const openLocationSettings = useCallback(() => {
    Linking.openSettings();
  }, []);

  // Serializes to the exact loc_* field set the API expects — see
  // lib/locationFields.js (buildLocationApiFields) for the pure logic and
  // its unit tests.
  const toApiFields = useCallback(
    () => buildLocationApiFields({ country, state, lga, community, postalCode, address, coords, captureMethod, confirmed, differsFromScan }),
    [country, state, lga, community, postalCode, address, coords, captureMethod, confirmed, differsFromScan]
  );

  return {
    country, state, lga, community, postalCode, address, coords, captureMethod,
    confirmed, differsFromScan, gpsStatus, gpsLoading,
    locations, lgasForState,
    setCommunity, setPostalCode, setAddress, setConfirmed, setDiffersFromScan,
    selectState, selectLga, captureGps, toApiFields, openLocationSettings,
  };
}
