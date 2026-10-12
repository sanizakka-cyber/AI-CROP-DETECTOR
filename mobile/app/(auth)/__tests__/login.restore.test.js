// Phase 9: first offline launch with a saved session but no cached profile
// explains the situation and offers Retry, instead of a bare login form.
import React from 'react';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import LoginScreen from '../login.jsx';

jest.mock('expo-router', () => ({ useRouter: () => ({ push: jest.fn(), replace: jest.fn() }), Link: ({ children }) => children }));
jest.mock('react-i18next', () => ({
  useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }),
  initReactI18next: { type: '3rdParty', init: () => {} },
}));
jest.mock('@react-native-async-storage/async-storage', () => ({
  getItem: jest.fn(() => Promise.resolve(null)), setItem: jest.fn(() => Promise.resolve()), removeItem: jest.fn(() => Promise.resolve()),
}));
const mockAuth = { login: jest.fn(), sessionExpired: false, restoreError: null, retryRestore: jest.fn(() => Promise.resolve()) };
jest.mock('../../../context/AuthContext', () => ({ useAuth: () => mockAuth }));

jest.setTimeout(20000);
beforeEach(() => { jest.clearAllMocks(); mockAuth.restoreError = null; mockAuth.sessionExpired = false; });

test('shows the restore notice with a Retry that re-attempts the restore', async () => {
  mockAuth.restoreError = 'MSAS could not be reached.';
  render(<LoginScreen />);
  await act(async () => {});
  expect(screen.getByText(/still signed in, but MSAS couldn't be reached/)).toBeTruthy();
  fireEvent.press(screen.getByText('Retry'));
  fireEvent.press(screen.getByText(/Retry/));
  await act(async () => {});
  expect(mockAuth.retryRestore).toHaveBeenCalledTimes(1);
});

test('no notice on a normal signed-out start', async () => {
  render(<LoginScreen />);
  await act(async () => {});
  expect(screen.queryByText(/still signed in/)).toBeNull();
});
