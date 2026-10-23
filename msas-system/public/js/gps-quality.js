/*
 * GPS capture for the web scan form: a fresh position (never a cached one),
 * the best reading over up to 30 seconds, stopping early once accuracy is
 * 5 m or better, and the accuracy in plain words. The accuracy shown is
 * always the one the device reported.
 *
 * Plain script (window.MsasGps) and CommonJS, so the app's Jest suite runs
 * it against the same cases as its own copy (mobile/lib/gpsQuality.js).
 * Bands match App\Models\CollectionLocation::ACCURACY_RATINGS.
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) { module.exports = factory(); } else { root.MsasGps = factory(); }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    var BANDS = [['excellent', 5], ['good', 15], ['fair', 50]];
    var TARGET_METERS = 5;
    var WINDOW_MS = 30000;
    // A cached reading: timestamped 10 s to 30 min before the capture began.
    // Further back is a phone whose clock disagrees with GPS time (common on
    // phones with a wrong clock), not a cached fix, so it is not refused.
    var STALE_MIN_MS = 10000;
    var STALE_MAX_MS = 30 * 60 * 1000;

    function rating(meters) {
        if (meters == null || !isFinite(meters)) { return null; }
        for (var i = 0; i < BANDS.length; i++) {
            if (meters <= BANDS[i][1]) { return BANDS[i][0]; }
        }
        return 'low';
    }

    function needsRetry(r) { return r === 'fair' || r === 'low'; }

    function validPoint(lat, lon) {
        return isFinite(lat) && isFinite(lon) && Math.abs(lat) <= 90 && Math.abs(lon) <= 180 && !(lat === 0 && lon === 0);
    }

    /**
     * Keeps the best reading of a capture. offer() takes a reading
     * {lat, lon, accuracy, timestamp, mocked} and says whether it was used,
     * the best so far, and whether the target accuracy is reached.
     */
    function createTracker(startedAt, targetMeters) {
        var best = null;
        var first = null;
        var target = targetMeters == null ? TARGET_METERS : targetMeters;
        return {
            offer: function (r) {
                var age = r.timestamp == null ? 0 : startedAt - r.timestamp;
                var fresh = !(age > STALE_MIN_MS && age < STALE_MAX_MS);
                var usable = fresh && validPoint(r.lat, r.lon) && isFinite(r.accuracy) && r.accuracy > 0;
                if (usable && first === null) { first = r; }
                if (usable && (best === null || r.accuracy < best.accuracy)) { best = r; }
                return { used: usable, best: best, first: first, done: best !== null && best.accuracy <= target };
            },
            best: function () { return best; },
            first: function () { return first; },
        };
    }

    /**
     * Watches the position (navigator.geolocation-shaped `geo`) and resolves
     * { fix, reason } with the best reading: reason "target" (5 m reached),
     * "window" (30 s passed) or "stopped" (the farmer took it early).
     * Rejects { code: "denied" | "unavailable" | "timeout" | "unsupported" }
     * when there is no usable reading. onProgress(best, first) after each.
     */
    function captureBest(geo, opts) {
        opts = opts || {};
        var now = opts.now || function () { return Date.now(); };
        var setT = opts.setTimeout || setTimeout;
        var clearT = opts.clearTimeout || clearTimeout;
        var windowMs = opts.windowMs || WINDOW_MS;
        var startedAt = now();
        var tracker = createTracker(startedAt, opts.targetMeters);
        var watchId = null;
        var timer = null;
        var lastError = null;
        var settled = false;
        var finish;

        var promise = new Promise(function (resolve, reject) {
            finish = function (reason, error) {
                if (settled) { return; }
                settled = true;
                if (watchId !== null) { geo.clearWatch(watchId); }
                if (timer !== null) { clearT(timer); }
                var best = tracker.best();
                if (best && !error) { resolve({ fix: best, reason: reason }); return; }
                if (best && error && error !== 'denied') { resolve({ fix: best, reason: reason }); return; }
                reject({ code: error || lastError || 'timeout' });
            };
            if (!geo || typeof geo.watchPosition !== 'function') { finish('unsupported', 'unsupported'); return; }
            timer = setT(function () { finish('window'); }, windowMs);
            watchId = geo.watchPosition(function (pos) {
                var r = tracker.offer({
                    lat: pos.coords.latitude, lon: pos.coords.longitude, accuracy: pos.coords.accuracy,
                    timestamp: pos.timestamp, mocked: pos.mocked === true,
                });
                if (r.used && opts.onProgress) { opts.onProgress(r.best, r.first); }
                if (r.done) { finish('target'); }
            }, function (err) {
                lastError = err && err.code === 1 ? 'denied' : err && err.code === 3 ? 'timeout' : 'unavailable';
                // Permission refused: nothing more will come. Other errors: keep listening until the window ends.
                if (lastError === 'denied') { finish('denied', 'denied'); }
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: windowMs });
        });

        return { promise: promise, stop: function () { finish('stopped'); } };
    }

    /** A laptop or desktop browser: no GPS, Wi-Fi positioning only. */
    function isLikelyDesktop(nav, win) {
        var ua = (nav && nav.userAgent) || '';
        var mobileUa = /Android|iPhone|iPad|iPod|Mobile/i.test(ua);
        var touch = !!(win && ('ontouchstart' in win)) || ((nav && nav.maxTouchPoints) || 0) > 0;
        return !mobileUa && !touch;
    }

    return {
        BANDS: BANDS, TARGET_METERS: TARGET_METERS, WINDOW_MS: WINDOW_MS,
        rating: rating, needsRetry: needsRetry, createTracker: createTracker,
        captureBest: captureBest, isLikelyDesktop: isLikelyDesktop,
    };
}));
