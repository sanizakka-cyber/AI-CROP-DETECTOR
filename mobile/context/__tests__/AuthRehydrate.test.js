// Phase 8: app-start session restore. Only an auth rejection may end the
// session; being offline (or a 5xx/timeout) must not log the farmer out.
import { renderHook, waitFor } from '@testing-library/react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { authAPI } from '../../lib/api';
import { AuthProvider, useAuth } from '../AuthContext';

const mockStore = {};
jest.mock('@react-native-async-storage/async-storage', () => ({
  getItem: jest.fn((k) => Promise.resolve(mockStore[k] ?? null)),
  setItem: jest.fn((k, v) => { mockStore[k] = v; return Promise.resolve(); }),
  removeItem: jest.fn((k) => { delete mockStore[k]; return Promise.resolve(); }),
}));
jest.mock('../../lib/api', () => ({ authAPI: { me: jest.fn() } }));

const FARMER = { id: 1, role: 'farmer', name: 'Musa' };
const err = (kind, status) => Object.assign(new Error('x'), { kind, status });

beforeEach(() => {
  jest.clearAllMocks();
  for (const k of Object.keys(mockStore)) delete mockStore[k];
  mockStore.token = 'tok_123';
});

async function start() {
  const { result } = renderHook(() => useAuth(), { wrapper: AuthProvider });
  await waitFor(() => expect(result.current.loading).toBe(false));
  return result;
}

test('online start: /auth/me user is used and cached', async () => {
  authAPI.me.mockResolvedValue({ user: FARMER });
  const result = await start();
  expect(result.current.user).toEqual(FARMER);
  expect(JSON.parse(mockStore.auth_user_cache)).toEqual(FARMER);
});

test.each([['network', null], ['timeout', null], ['server', 500]])(
  'offline-style failure (%s) keeps the token and restores the cached user',
  async (kind, status) => {
    mockStore.auth_user_cache = JSON.stringify(FARMER);
    authAPI.me.mockRejectedValue(err(kind, status));
    const result = await start();
    expect(mockStore.token).toBe('tok_123');
    expect(result.current.user).toEqual(FARMER);
    expect(result.current.authStatus).toBe('authenticated');
  },
);

test('auth rejection clears the token and the cached user', async () => {
  mockStore.auth_user_cache = JSON.stringify(FARMER);
  authAPI.me.mockRejectedValue(err('auth', 401));
  const result = await start();
  expect(mockStore.token).toBeUndefined();
  expect(mockStore.auth_user_cache).toBeUndefined();
  expect(result.current.user).toBeNull();
});

test('offline with no cached user keeps the token but stays unauthenticated', async () => {
  authAPI.me.mockRejectedValue(err('network', null));
  const result = await start();
  expect(mockStore.token).toBe('tok_123');
  expect(result.current.authStatus).toBe('unauthenticated');
});

test('logout clears the cached user', async () => {
  authAPI.me.mockResolvedValue({ user: FARMER });
  const result = await start();
  await result.current.logout();
  expect(mockStore.auth_user_cache).toBeUndefined();
  expect(mockStore.token).toBeUndefined();
});
