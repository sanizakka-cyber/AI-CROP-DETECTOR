// Registration requires agreeing to the Terms/Privacy Policy and being 18+.
// An under-18 age is stopped on the phone, so nothing is sent to the server.
import React from 'react';
import { Alert } from 'react-native';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { locationsAPI } from '../../../lib/api';
import RegisterScreen from '../register.jsx';

jest.mock('expo-router', () => ({ useRouter: () => ({ push: jest.fn(), replace: jest.fn(), back: jest.fn() }), Link: ({ children }) => children }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en', changeLanguage: jest.fn() } }) }));
jest.mock('expo-constants', () => ({ expoConfig: { version: '1.4.11' } }));
const mockAuth = { register: jest.fn() };
jest.mock('../../../context/AuthContext', () => ({ useAuth: () => mockAuth }));
jest.mock('../../../lib/api', () => ({ locationsAPI: { list: jest.fn() } }));

const flush = () => act(async () => {});
jest.setTimeout(30000);

beforeEach(() => {
  jest.clearAllMocks();
  locationsAPI.list.mockResolvedValue({ countries: ['Nigeria'], states: [{ name: 'Katsina', lgas: ['Funtua'] }] });
  mockAuth.register.mockResolvedValue({ status: 'authenticated' });
  jest.spyOn(Alert, 'alert').mockImplementation(() => {});
});

async function fillForm({ age = '30', confirmAge = true } = {}) {
  render(<RegisterScreen />);
  await flush();
  const inputs = screen.UNSAFE_getAllByType(require('react-native').TextInput);
  // first, middle, last, identifier, age, ... password, confirm (order on screen)
  fireEvent.changeText(inputs[0], 'Amina');
  fireEvent.changeText(inputs[2], 'Bello');
  fireEvent.changeText(inputs[3], 'amina@example.com');
  fireEvent.changeText(screen.getByTestId('age-input'), age);
  if (confirmAge) fireEvent.press(screen.getByTestId('age-confirm'));

  fireEvent.press(screen.getByText('Select State'));
  fireEvent.press(await screen.findByText('Katsina'));
  await flush();
  fireEvent.press(screen.getByText('Select LGA'));
  fireEvent.press(await screen.findByText('Funtua'));
  await flush();

  const pw = inputs.filter((i) => i.props.secureTextEntry !== undefined);
  fireEvent.changeText(pw[0], 'Password123!');
  fireEvent.changeText(pw[1], 'Password123!');
}

test('the register button stays disabled until the terms box is ticked', async () => {
  await fillForm();
  const submit = screen.getByTestId('register-submit');
  expect(submit.props.accessibilityState.disabled).toBe(true);

  fireEvent.press(screen.getByTestId('terms-accept'));
  expect(screen.getByTestId('register-submit').props.accessibilityState.disabled).toBe(false);
});

test('an under-18 age is refused on the phone and nothing is sent', async () => {
  await fillForm({ age: '15' });
  fireEvent.press(screen.getByTestId('terms-accept'));
  fireEvent.press(screen.getByTestId('register-submit'));
  await flush();

  expect(mockAuth.register).not.toHaveBeenCalled();
  expect(Alert.alert).toHaveBeenCalledWith('Registration not allowed', expect.stringContaining('18 or older'));
});

test('the 18+ confirmation is required', async () => {
  await fillForm({ confirmAge: false });
  fireEvent.press(screen.getByTestId('terms-accept'));
  fireEvent.press(screen.getByTestId('register-submit'));
  await flush();

  expect(mockAuth.register).not.toHaveBeenCalled();
  expect(Alert.alert).toHaveBeenCalledWith('', 'Please confirm that you are 18 years or older.');
});

test('a valid registration sends age, agreement and app version', async () => {
  await fillForm({ age: '30' });
  fireEvent.press(screen.getByTestId('terms-accept'));
  fireEvent.press(screen.getByTestId('register-submit'));
  await flush();

  expect(mockAuth.register).toHaveBeenCalledWith(expect.objectContaining({
    age: 30, age_confirmed: true, terms_accepted: true, app_version: '1.4.11',
  }));
});
