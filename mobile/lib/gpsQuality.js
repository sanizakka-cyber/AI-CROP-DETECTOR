// GPS capture for the app: a fresh position (never the last known one), the
// best reading over up to 30 seconds, stopping early once accuracy is 5 m or
// better, and the accuracy in plain words. The accuracy shown is always the
// one the device reported.
//
// Same rules as the web form (msas-system/public/js/gps-quality.js); the
// tests run both against the same cases. Bands match
// App\Models\CollectionLocation::ACCURACY_RATINGS.

export const BANDS = [['excellent', 5], ['good', 15], ['fair', 50]];
export const TARGET_METERS = 5;
export const WINDOW_MS = 30000;
// A reading timestamped this long before the capture began is a cached one.
const STALE_SLACK_MS = 1000;

export function rating(meters) {
  if (meters == null || !Number.isFinite(meters)) return null;
  for (const [name, max] of BANDS) {
    if (meters <= max) return name;
  }
  return 'low';
}

export const needsRetry = (r) => r === 'fair' || r === 'low';

function validPoint(lat, lon) {
  return Number.isFinite(lat) && Number.isFinite(lon) && Math.abs(lat) <= 90 && Math.abs(lon) <= 180
    && !(lat === 0 && lon === 0); // (0,0): a fix that never resolved, not a farm
}

/**
 * Keeps the best reading of a capture. offer() takes a reading
 * {lat, lon, accuracy, timestamp, mocked} and says whether it was used, the
 * best so far, and whether the target accuracy is reached.
 */
export function createTracker(startedAt, targetMeters = TARGET_METERS) {
  let best = null;
  let first = null;
  return {
    offer(r) {
      const fresh = r.timestamp == null || r.timestamp >= startedAt - STALE_SLACK_MS;
      const usable = fresh && validPoint(r.lat, r.lon) && Number.isFinite(r.accuracy) && r.accuracy > 0;
      if (usable && first === null) first = r;
      if (usable && (best === null || r.accuracy < best.accuracy)) best = r;
      return { used: usable, best, first, done: best !== null && best.accuracy <= targetMeters };
    },
    best: () => best,
    first: () => first,
  };
}

/**
 * Watches the position with expo-location and resolves { fix, reason } with
 * the best reading: reason "target" (5 m reached), "window" (30 s passed) or
 * "stopped" (the farmer took it early). Rejects { code: "timeout" |
 * "unavailable" } when there is no usable reading. onProgress(best, first)
 * after each used reading.
 */
export function captureBest(Location, { windowMs = WINDOW_MS, targetMeters, onProgress, now = Date.now } = {}) {
  const startedAt = now();
  const tracker = createTracker(startedAt, targetMeters);
  let subscription = null;
  let timer = null;
  let settled = false;
  let lastError = null;
  let finish;

  const promise = new Promise((resolve, reject) => {
    finish = (reason) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      subscription?.remove();
      const best = tracker.best();
      if (best) resolve({ fix: best, reason });
      else reject({ code: lastError || 'timeout' });
    };
    timer = setTimeout(() => finish('window'), windowMs);
    Location.watchPositionAsync(
      { accuracy: Location.Accuracy.BestForNavigation, timeInterval: 1000, distanceInterval: 0, mayShowUserSettingsDialog: true },
      (pos) => {
        const r = tracker.offer({
          lat: pos.coords.latitude, lon: pos.coords.longitude, accuracy: pos.coords.accuracy,
          timestamp: pos.timestamp, mocked: pos.mocked === true,
        });
        if (r.used && onProgress) onProgress(r.best, r.first);
        if (r.done) finish('target');
      },
      () => { lastError = 'unavailable'; },
    ).then((sub) => {
      subscription = sub;
      if (settled) sub.remove();
    }, () => {
      lastError = 'unavailable';
      finish('window');
    });
  });

  return { promise, stop: () => finish('stopped') };
}

/** Accuracy for display: one decimal below 10 m, never rounded down to zero. */
export function formatAccuracy(m) {
  return m < 10 ? String(Math.round(m * 10) / 10) : String(Math.round(m));
}
