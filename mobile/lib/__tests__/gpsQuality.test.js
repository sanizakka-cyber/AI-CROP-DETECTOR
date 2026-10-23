// GPS capture rules, for the app (lib/gpsQuality.js) and the web form
// (msas-system/public/js/gps-quality.js), against the same cases so the two
// can never rate or pick a reading differently.
import * as app from '../gpsQuality';

const web = require('../../../msas-system/public/js/gps-quality.js');

const T0 = 1_000_000;
const reading = (accuracy, over = {}) => ({ lat: 11.523, lon: 7.312, accuracy, timestamp: T0 + 500, ...over });

describe.each([['app', app], ['web', web]])('%s', (_name, gps) => {
  test.each([
    [3, 'excellent'], [5, 'excellent'], [5.1, 'good'], [15, 'good'], [15.5, 'fair'], [50, 'fair'], [51, 'low'], [900, 'low'],
    [null, null], [NaN, null],
  ])('±%s m is rated %s', (m, expected) => {
    expect(gps.rating(m)).toBe(expected);
  });

  test('a retry is offered for Fair and Low only', () => {
    expect(['excellent', 'good', 'fair', 'low'].map(gps.needsRetry)).toEqual([false, false, true, true]);
  });

  test('keeps the best reading and stops once it is 5 m or better', () => {
    const t = gps.createTracker(T0);
    expect(t.offer(reading(18)).done).toBe(false);
    expect(t.offer(reading(25)).best.accuracy).toBe(18); // a worse one does not replace it
    expect(t.offer(reading(7)).best.accuracy).toBe(7);
    const last = t.offer(reading(4.6));
    expect(last.done).toBe(true);
    expect(last.best.accuracy).toBe(4.6);
    expect(last.first.accuracy).toBe(18);
  });

  test('ignores a cached position from before the capture began', () => {
    const t = gps.createTracker(T0);
    const r = t.offer(reading(3, { timestamp: T0 - 60000 }));
    expect(r.used).toBe(false);
    expect(t.best()).toBeNull();
  });

  test('ignores (0,0), impossible points and missing accuracy', () => {
    const t = gps.createTracker(T0);
    expect(t.offer(reading(4, { lat: 0, lon: 0 })).used).toBe(false);
    expect(t.offer(reading(4, { lat: 95 })).used).toBe(false);
    expect(t.offer(reading(undefined)).used).toBe(false);
    expect(t.best()).toBeNull();
  });

  test('keeps the mocked flag of the reading it picked', () => {
    const t = gps.createTracker(T0);
    t.offer(reading(8, { mocked: true }));
    expect(t.best().mocked).toBe(true);
  });
});

describe('web captureBest (navigator.geolocation)', () => {
  const fakeGeo = () => {
    const geo = { cleared: [], opts: null };
    geo.watchPosition = (ok, err, opts) => { geo.ok = ok; geo.err = err; geo.opts = opts; return 7; };
    geo.clearWatch = (id) => geo.cleared.push(id);
    return geo;
  };
  const pos = (accuracy, ts = Date.now()) => ({ coords: { latitude: 11.523, longitude: 7.312, accuracy }, timestamp: ts });

  test('asks for a fresh, high-accuracy position', () => {
    const geo = fakeGeo();
    web.captureBest(geo, {});
    expect(geo.opts).toEqual({ enableHighAccuracy: true, maximumAge: 0, timeout: 30000 });
  });

  test('reports progress and stops early at the target', async () => {
    const geo = fakeGeo();
    const seen = [];
    const c = web.captureBest(geo, { onProgress: (best, first) => seen.push([first.accuracy, best.accuracy]) });
    geo.ok(pos(18));
    geo.ok(pos(7));
    geo.ok(pos(4));
    await expect(c.promise).resolves.toMatchObject({ reason: 'target', fix: { accuracy: 4 } });
    expect(seen).toEqual([[18, 18], [18, 7], [18, 4]]);
    expect(geo.cleared).toEqual([7]);
  });

  test('after 30 s keeps the best reading found', async () => {
    jest.useFakeTimers();
    const geo = fakeGeo();
    const c = web.captureBest(geo, {});
    geo.ok(pos(40));
    geo.ok(pos(22));
    jest.advanceTimersByTime(30000);
    await expect(c.promise).resolves.toMatchObject({ reason: 'window', fix: { accuracy: 22 } });
    jest.useRealTimers();
  });

  test('a refused permission ends at once; no reading in 30 s is a timeout', async () => {
    const denied = fakeGeo();
    const c1 = web.captureBest(denied, {});
    denied.err({ code: 1 });
    await expect(c1.promise).rejects.toEqual({ code: 'denied' });

    jest.useFakeTimers();
    const silent = fakeGeo();
    const c2 = web.captureBest(silent, {});
    jest.advanceTimersByTime(30000);
    await expect(c2.promise).rejects.toEqual({ code: 'timeout' });
    jest.useRealTimers();
  });

  test('a laptop browser is told to use the app or a phone', () => {
    expect(web.isLikelyDesktop({ userAgent: 'Mozilla/5.0 (Windows NT 10.0) Chrome/130', maxTouchPoints: 0 }, {})).toBe(true);
    expect(web.isLikelyDesktop({ userAgent: 'Mozilla/5.0 (Linux; Android 14) Mobile', maxTouchPoints: 5 }, {})).toBe(false);
  });
});

describe('app captureBest (expo-location watchPositionAsync)', () => {
  const fakeLocation = () => {
    const L = { Accuracy: { BestForNavigation: 6 }, removed: 0 };
    L.watchPositionAsync = jest.fn((opts, cb) => { L.opts = opts; L.cb = cb; return Promise.resolve({ remove: () => { L.removed += 1; } }); });
    return L;
  };
  const pos = (accuracy, extra = {}) => ({ coords: { latitude: 11.523, longitude: 7.312, accuracy }, timestamp: Date.now(), ...extra });

  test('watches with the highest accuracy, stops at 5 m, keeps the mocked flag', async () => {
    const L = fakeLocation();
    const c = app.captureBest(L, {});
    await Promise.resolve();
    expect(L.opts).toMatchObject({ accuracy: 6, timeInterval: 1000, distanceInterval: 0 });
    L.cb(pos(12));
    L.cb(pos(5, { mocked: true }));
    await expect(c.promise).resolves.toMatchObject({ reason: 'target', fix: { accuracy: 5, mocked: true } });
    await Promise.resolve();
    expect(L.removed).toBe(1);
  });

  test('stop() takes the best reading so far', async () => {
    const L = fakeLocation();
    const c = app.captureBest(L, {});
    await Promise.resolve();
    L.cb(pos(30));
    L.cb(pos(11));
    c.stop();
    await expect(c.promise).resolves.toMatchObject({ reason: 'stopped', fix: { accuracy: 11 } });
  });

  test('no reading within 30 s is a timeout', async () => {
    jest.useFakeTimers();
    const L = fakeLocation();
    const c = app.captureBest(L, {});
    jest.advanceTimersByTime(30000);
    await expect(c.promise).rejects.toEqual({ code: 'timeout' });
    jest.useRealTimers();
  });

  test('accuracy is never shown better than reported', () => {
    expect(app.formatAccuracy(4.64)).toBe('4.6');
    expect(app.formatAccuracy(0.4)).toBe('0.4');
    expect(app.formatAccuracy(18.4)).toBe('18');
  });
});
