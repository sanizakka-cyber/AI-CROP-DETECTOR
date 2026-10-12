// Phase 9 §B9: starter auth coverage. AuthProvider's login()/register()/
// verifyOtp() each guard against persisting a malformed server response
// (missing token or user) as a real session -- without this, a broken
// response would silently log the user into an unusable session rather
// than failing loudly. Mocks AsyncStorage and lib/api.js's authAPI since
// neither the real native module nor a real network call exists under Jest.
import { renderHook, act, waitFor } from '@testing-library/react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { authAPI } from '../../lib/api';
import { AuthProvider, useAuth } from '../AuthContext';

jest.mock('@react-native-async-storage/async-storage', () => ({
  getItem: jest.fn(() => Promise.resolve(null)),
  setItem: jest.fn(() => Promise.resolve()),
  removeItem: jest.fn(() => Promise.resolve()),
}));

jest.mock('../../lib/api', () => ({
  authAPI: {
    login: jest.fn(),
    register: jest.fn(),
    verifyOtp: jest.fn(),
    me: jest.fn(),
  },
}));

function renderAuth() {
  return renderHook(() => useAuth(), { wrapper: AuthProvider });
}

describe('AuthContext — login()', () => {
  afterEach(() => jest.clearAllMocks());

  test('a response missing a token throws and never persists a session', async () => {
    authAPI.login.mockResolvedValue({ user: { id: 1, role: 'farmer' } }); // no token
    const { result } = renderAuth();
    await waitFor(() => expect(result.current.loading).toBe(false));

    await act(async () => {
      await expect(result.current.login('farmer@example.com', 'password')).rejects.toThrow(
        /missing a token or user/
      );
    });

    expect(AsyncStorage.setItem).not.toHaveBeenCalled();
    expect(result.current.authStatus).toBe('unauthenticated');
  });

  test('a response missing a user throws and never persists a session', async () => {
    authAPI.login.mockResolvedValue({ token: 'real-token' }); // no user
    const { result } = renderAuth();
    await waitFor(() => expect(result.current.loading).toBe(false));

    await act(async () => {
      await expect(result.current.login('farmer@example.com', 'password')).rejects.toThrow(
        /missing a token or user/
      );
    });

    expect(AsyncStorage.setItem).not.toHaveBeenCalled();
  });

  test('a well-formed response persists the token and sets the user', async () => {
    authAPI.login.mockResolvedValue({ token: 'real-token', user: { id: 1, role: 'farmer' } });
    const { result } = renderAuth();
    await waitFor(() => expect(result.current.loading).toBe(false));

    await act(async () => {
      await result.current.login('farmer@example.com', 'password');
    });

    expect(AsyncStorage.setItem).toHaveBeenCalledWith('token', 'real-token');
    expect(result.current.authStatus).toBe('authenticated');
    expect(result.current.user).toEqual({ id: 1, role: 'farmer' });
  });
});

describe('AuthContext — register()', () => {
  afterEach(() => jest.clearAllMocks());

  test('a non-farmer role pending approval returns needs_approval without a session', async () => {
    authAPI.register.mockResolvedValue({ message: 'Your application is under review.' });
    const { result } = renderAuth();
    await waitFor(() => expect(result.current.loading).toBe(false));

    let outcome;
    await act(async () => {
      outcome = await result.current.register({ role: 'vet' });
    });

    expect(outcome.status).toBe('needs_approval');
    expect(result.current.authStatus).toBe('unauthenticated');
  });

  test('an email signup needing OTP returns needs_verification with the identifier', async () => {
    authAPI.register.mockResolvedValue({ needs_verification: true, identifier: 'farmer@example.com', message: 'Code sent.' });
    const { result } = renderAuth();
    await waitFor(() => expect(result.current.loading).toBe(false));

    let outcome;
    await act(async () => {
      outcome = await result.current.register({ role: 'farmer', email: 'farmer@example.com' });
    });

    expect(outcome).toEqual({ status: 'needs_verification', identifier: 'farmer@example.com', message: 'Code sent.' });
  });
});

describe('AuthContext — logout()', () => {
  test('clears the stored token and resets state', async () => {
    authAPI.login.mockResolvedValue({ token: 'real-token', user: { id: 1, role: 'farmer' } });
    const { result } = renderAuth();
    await waitFor(() => expect(result.current.loading).toBe(false));
    await act(async () => { await result.current.login('farmer@example.com', 'password'); });

    await act(async () => { await result.current.logout(); });

    expect(AsyncStorage.removeItem).toHaveBeenCalledWith('token');
    expect(result.current.authStatus).toBe('unauthenticated');
    expect(result.current.user).toBeNull();
  });
});
