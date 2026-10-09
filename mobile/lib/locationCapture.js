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
import { useCallback, useEffect, useState } from 'react';
import * as Location from 'expo-location';
import { locationsAPI } from './api';
import { buildLocationApiFields } from './locationFields';

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
  const [gpsStatus, setGpsStatus]   = useState(null); // { type: 'success'|'warning'|'error', message }
  const [gpsLoading, setGpsLoading] = useState(false);

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
      if (!perm.granted) {
        setGpsStatus({ type: 'error', message: 'Location permission denied. You can still select your state/LGA manually below.' });
        return;
      }

      const enabled = await Location.hasServicesEnabledAsync();
      if (!enabled) {
        setGpsStatus({ type: 'error', message: 'Location services are turned off on this device. You can still select your state/LGA manually below.' });
        return;
      }

      const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
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
      const timedOut = e?.code === 'E_LOCATION_TIMEOUT' || /timeout/i.test(e?.message || '');
      setGpsStatus({
        type: 'error',
        message: timedOut
          ? 'Location request timed out. You can still select your state/LGA manually below.'
          : 'Could not get your location. You can still select your state/LGA manually below.',
      });
    } finally {
      setGpsLoading(false);
    }
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
    selectState, selectLga, captureGps, toApiFields,
  };
}
