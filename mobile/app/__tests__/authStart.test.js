// App start while the saved login is checked (/auth/me took ~12 s on a test
// device): a loading screen, never the sign-in form; then straight to Home,
// or to sign-in only when there is no valid login. Real AuthProvider; the
// API and storage are faked.
import React from 'react';
import { render, screen, act, waitFor } from '@testing-library/react-native';
import { authAPI } from '../../lib/api';
import { AuthProvider } from '../../context/AuthContext';
import Index from '../index';

const mockStore = {};
jest.mock('@react-native-async-storage/async-storage', () => ({
  getItem: jest.fn((k) => Promise.resolve(mockStore[k] ?? null)),
  setItem: jest.fn((k, v) => { mockStore[k] = v; return Promise.resolve(); }),
  removeItem: jest.fn((k) => { delete mockStore[k]; return Promise.resolve(); }),
}));
jest.mock('../../lib/api', () => ({ authAPI: { me: jest.fn() } }));
jest.mock('expo-router', () => {
  const { Text } = require('react-native');
  return { Redirect: ({ href }) => <Text testID="redirect">{href}</Text> };
});
jest.mock('react-i18next', () => ({
  initReactI18next: { type: '3rdParty', init: () => {} },
  useTranslation: () => ({ t: (k) => require('../../lib/i18n').resources.en.translation[k] ?? k }),
}));

const FARMER = { id: 1, role: 'farmer', name: 'Umar' };
const err = (kind, status) => Object.assign(new Error('x'), { kind, status });

beforeEach(() => {
  jest.clearAllMocks();
  for (const k of Object.keys(mockStore)) delete mockStore[k];
});

const start = () => render(<AuthProvider><Index /></AuthProvider>);
const destination = () => screen.getByTestId('redirect').props.children;

test('while the saved login is checked: the loading screen, not the sign-in form', async () => {
  mockStore.token = 'tok_123';
  let answer;
  authAPI.me.mockImplementation(() => new Promise((resolve) => { answer = resolve; }));
  start();

  await waitFor(() => expect(authAPI.me).toHaveBeenCalled());
  expect(screen.getByTestId('auth-splash')).toBeTruthy();
  expect(screen.getByText('Checking your account…')).toBeTruthy();
  expect(screen.queryByTestId('redirect')).toBeNull();

  await act(async () => { answer({ user: FARMER }); });
  expect(destination()).toBe('/(tabs)/home');
  expect(screen.queryByTestId('auth-splash')).toBeNull();
});

test('no saved login: straight to sign-in', async () => {
  start();
  await waitFor(() => expect(destination()).toBe('/(auth)/login'));
  expect(authAPI.me).not.toHaveBeenCalled();
});

test('a saved login the server rejects: sign-in', async () => {
  mockStore.token = 'tok_expired';
  authAPI.me.mockRejectedValue(err('auth', 401));
  start();
  await waitFor(() => expect(destination()).toBe('/(auth)/login'));
  expect(mockStore.token).toBeUndefined();
});

test('offline with a saved login and cached profile: Home, not sign-in', async () => {
  mockStore.token = 'tok_123';
  mockStore.auth_user_cache = JSON.stringify(FARMER);
  authAPI.me.mockRejectedValue(err('network', null));
  start();
  await waitFor(() => expect(destination()).toBe('/(tabs)/home'));
});
