// "Use GPS" on the profile edit screen: the same rules as the scan location
// (lib/locationCapture.js) and the web profile form. The best reading over
// 30 s; State and LGA from the boundaries; the village suggested into the
// Village field (never over what the user typed, lib/locationSuggest.js);
// nothing filled from a reading worse than 1 km — the user moves the pin
// instead, which runs the lookups again.
import { useCallback, useEffect, useRef, useState } from 'react';
import * as Location from 'expo-location';
import { locationsAPI } from './api';
import { captureBest, rating } from './gpsQuality';
import { canAutoFill } from './locationSuggest';

/**
 * onNames(state, lga): the boundary lookup found the point's State and LGA.
 * onVillage(community|null): the village suggestion (null: none for this point).
 */
export function useProfileLocation({ onNames, onVillage }) {
  const [loading, setLoading] = useState(false);
  const [fix, setFix] = useState(null);           // { latitude, longitude, accuracy, rating }
  const [needsPin, setNeedsPin] = useState(false);
  const [note, setNote] = useState(null);         // { key, params?, attribution? }
  const current = useRef(null);
  const mounted = useRef(true);
  const captureRef = useRef(null);
  useEffect(() => () => { mounted.current = false; captureRef.current?.stop(); }, []);

  const lookup = useCallback(async (point, accuracy) => {
    current.current = point;
    setNote({ key: 'loc_looking_up' });
    try {
      const res = await locationsAPI.lookup(point.latitude, point.longitude, { accuracy });
      if (!mounted.current || current.current !== point) return;
      if (res?.low_accuracy) { setNote({ key: 'loc_low_accuracy' }); return; }
      if (!res?.found) { setNote({ key: 'loc_not_found' }); onVillage(null); return; }
      onNames(res.state, res.lga);
      setNote({ key: 'loc_filled_from', params: { place: `${res.lga}, ${res.state}` }, attribution: res.attribution || '' });
      const s = await locationsAPI.lookup(point.latitude, point.longitude, { accuracy, suggest: true }).catch(() => null);
      if (!mounted.current || current.current !== point) return;
      if (s?.suggestions?.ok) onVillage(s.suggestions.community || null);
    } catch {
      if (mounted.current && current.current === point) setNote({ key: 'loc_err_failed' });
    }
  }, [onNames, onVillage]);

  const capture = useCallback(async () => {
    captureRef.current?.stop();
    setLoading(true);
    setNote({ key: 'loc_requesting' });
    try {
      const perm = await Location.requestForegroundPermissionsAsync();
      if (!mounted.current) return;
      if (!perm.granted) { setNote({ key: 'loc_err_denied' }); return; }
      const c = captureBest(Location, {});
      captureRef.current = c;
      const { fix: best } = await c.promise;
      if (!mounted.current || captureRef.current !== c) return;
      captureRef.current = null;
      const point = { latitude: best.lat, longitude: best.lon, accuracy: best.accuracy, rating: rating(best.accuracy) };
      setFix(point);
      if (!canAutoFill(best.accuracy)) {
        current.current = point;
        setNeedsPin(true);
        setNote({ key: 'loc_low_accuracy' });
        return;
      }
      setNeedsPin(false);
      await lookup(point, best.accuracy);
    } catch {
      if (mounted.current) setNote({ key: 'loc_err_no_signal' });
    } finally {
      if (mounted.current) setLoading(false);
    }
  }, [lookup]);

  /** The user moved the pin: the lookups run again for that point (no device accuracy). */
  const movePin = useCallback((latitude, longitude) => {
    const point = { latitude, longitude, accuracy: fix?.accuracy ?? null, rating: fix?.rating ?? null };
    setNeedsPin(false);
    lookup(point, null);
  }, [fix, lookup]);

  return { loading, fix, needsPin, note, capture, movePin };
}
