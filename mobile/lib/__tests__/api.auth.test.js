// Scan uploads (multipartRequest): expired-session handling (Phase 9),
// real upload progress and the 180 s timeout (Phase 12).
import { diagnoseAPI, ApiError } from '../api';

const mockStore = {};
jest.mock('@react-native-async-storage/async-storage', () => ({
  getItem: jest.fn((k) => Promise.resolve(mockStore[k] ?? null)),
  setItem: jest.fn((k, v) => { mockStore[k] = v; return Promise.resolve(); }),
  removeItem: jest.fn((k) => { delete mockStore[k]; return Promise.resolve(); }),
}));

// Minimal XMLHttpRequest stand-in. `respond` decides what each send() does.
let respond;
let last;
class FakeXHR {
  constructor() { this.headers = {}; this.upload = {}; last = this; }
  open(method, url) { this.method = method; this.url = url; }
  setRequestHeader(k, v) { this.headers[k] = v; }
  send(body) { this.body = body; respond(this); }
}

const reply = (status, body) => (x) => {
  x.upload.onprogress?.({ lengthComputable: true, loaded: 50, total: 100 });
  x.upload.onprogress?.({ lengthComputable: true, loaded: 100, total: 100 });
  x.status = status; x.responseText = JSON.stringify(body); x.onload();
};

beforeEach(() => {
  for (const k of Object.keys(mockStore)) delete mockStore[k];
  globalThis.__onAuthExpired = jest.fn();
  global.XMLHttpRequest = FakeXHR;
  jest.spyOn(console, 'log').mockImplementation(() => {});
});

const upload = (extra = {}) => diagnoseAPI.soil({ images: [{ uri: 'file:///a.jpg' }], location: {}, ...extra });

test('a 401 on an image upload clears the token and reports session expiry', async () => {
  mockStore.token = 'tok_old';
  respond = reply(401, { error: 'Unauthenticated.' });
  const err = await upload().catch(e => e);
  expect(err).toBeInstanceOf(ApiError);
  expect(err.kind).toBe('auth');
  expect(err.message).toBe('Session expired. Please log in again.');
  expect(mockStore.token).toBeUndefined();
  expect(globalThis.__onAuthExpired).toHaveBeenCalledTimes(1);
});

test('an upload with no token sends no Authorization header', async () => {
  respond = reply(201, { diagnosisId: 9 });
  await upload();
  expect(last.headers.Authorization).toBeUndefined();
  expect(last.method).toBe('POST');
  expect(last.url).toMatch(/\/diagnose\/soil$/);
});

test('a non-auth upload failure leaves the session alone', async () => {
  mockStore.token = 'tok_ok';
  respond = reply(500, { error: 'boom' });
  const err = await upload().catch(e => e);
  expect(err.kind).toBe('server');
  expect(mockStore.token).toBe('tok_ok');
  expect(globalThis.__onAuthExpired).not.toHaveBeenCalled();
});

test('reports real upload progress to the screen', async () => {
  respond = reply(201, { diagnosisId: 9 });
  const seen = [];
  const res = await upload({ onProgress: (p) => seen.push(p) });
  expect(seen).toEqual([50, 100]);
  expect(res.diagnosisId).toBe(9);
});

test('waits as long as the server does (180 s), and a timeout is a clear timeout error', async () => {
  respond = (x) => x.ontimeout();
  const err = await upload().catch(e => e);
  expect(last.timeout).toBe(180000);
  expect(err.kind).toBe('timeout');
});

test('a network failure is reported as such', async () => {
  respond = (x) => x.onerror();
  const err = await upload().catch(e => e);
  expect(err.kind).toBe('network');
});

test('server field errors are surfaced', async () => {
  respond = reply(422, { error: 'Validation failed.', details: { 'images.0': ['The images.0 field must be an image.'] } });
  const err = await upload().catch(e => e);
  expect(err.status).toBe(422);
  expect(err.message).toMatch(/must be an image/);
});
