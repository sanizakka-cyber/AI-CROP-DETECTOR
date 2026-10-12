// Phase 9: an expired session during a scan upload must end the session
// cleanly (token cleared, AuthContext notified, clear message), the same
// as every JSON request already did.
import AsyncStorage from '@react-native-async-storage/async-storage';
import { diagnoseAPI, ApiError } from '../api';

const mockStore = {};
jest.mock('@react-native-async-storage/async-storage', () => ({
  getItem: jest.fn((k) => Promise.resolve(mockStore[k] ?? null)),
  setItem: jest.fn((k, v) => { mockStore[k] = v; return Promise.resolve(); }),
  removeItem: jest.fn((k) => { delete mockStore[k]; return Promise.resolve(); }),
}));

const res = (status, body) => ({ ok: status >= 200 && status < 300, status, text: () => Promise.resolve(JSON.stringify(body)) });

beforeEach(() => {
  for (const k of Object.keys(mockStore)) delete mockStore[k];
  globalThis.__onAuthExpired = jest.fn();
  jest.spyOn(console, 'log').mockImplementation(() => {});
});
afterEach(() => { delete global.fetch; });

const upload = () => diagnoseAPI.soil({ images: [{ uri: 'file:///a.jpg' }], location: {} });

test('a 401 on an image upload clears the token and reports session expiry', async () => {
  mockStore.token = 'tok_old';
  global.fetch = jest.fn(() => Promise.resolve(res(401, { error: 'Unauthenticated.' })));
  const err = await upload().catch(e => e);
  expect(err).toBeInstanceOf(ApiError);
  expect(err.kind).toBe('auth');
  expect(err.message).toBe('Session expired. Please log in again.');
  expect(mockStore.token).toBeUndefined();
  expect(globalThis.__onAuthExpired).toHaveBeenCalledTimes(1);
});

test('an upload with no token sends no Authorization header', async () => {
  global.fetch = jest.fn(() => Promise.resolve(res(201, { diagnosisId: 9 })));
  await upload().catch(() => {});
  const headers = global.fetch.mock.calls[0][1].headers;
  expect(headers.Authorization).toBeUndefined();
});

test('a non-auth upload failure leaves the session alone', async () => {
  mockStore.token = 'tok_ok';
  global.fetch = jest.fn(() => Promise.resolve(res(500, { error: 'boom' })));
  const err = await upload().catch(e => e);
  expect(err.kind).toBe('server');
  expect(mockStore.token).toBe('tok_ok');
  expect(globalThis.__onAuthExpired).not.toHaveBeenCalled();
});
