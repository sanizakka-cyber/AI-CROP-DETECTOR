// Phase 8: if the states list fails to load, registration (which requires
// a state + LGA for Nigeria) must say so and offer a retry, not show an
// empty picker with no explanation.
import React from 'react';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { locationsAPI } from '../../../lib/api';
import RegisterScreen from '../register.jsx';

jest.mock('expo-router', () => ({ useRouter: () => ({ push: jest.fn(), replace: jest.fn(), back: jest.fn() }), Link: ({ children }) => children }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
const mockAuth = { register: jest.fn() };
jest.mock('../../../context/AuthContext', () => ({ useAuth: () => mockAuth }));
jest.mock('../../../lib/api', () => ({ locationsAPI: { list: jest.fn() } }));

const flush = () => act(async () => {});
// The registration form is large; its first render took ~6s cold locally.
jest.setTimeout(20000);
beforeEach(() => jest.clearAllMocks());

test('a failed states load shows the error with a working retry', async () => {
  locationsAPI.list
    .mockRejectedValueOnce(new Error('MSAS could not be reached.'))
    .mockResolvedValueOnce({ countries: ['Nigeria'], states: [{ name: 'Katsina', lgas: ['Funtua'] }] });
  render(<RegisterScreen />);
  await flush();
  const retry = screen.getByText(/MSAS could not be reached\. Tap to retry\./);
  fireEvent.press(retry);
  await flush();
  expect(locationsAPI.list).toHaveBeenCalledTimes(2);
  expect(screen.queryByText(/Tap to retry/)).toBeNull();
  expect(screen.getByText('Select State')).toBeTruthy();
});

test('a successful load shows no error', async () => {
  locationsAPI.list.mockResolvedValueOnce({ countries: ['Nigeria'], states: [] });
  render(<RegisterScreen />);
  await flush();
  expect(screen.queryByText(/Tap to retry/)).toBeNull();
});
