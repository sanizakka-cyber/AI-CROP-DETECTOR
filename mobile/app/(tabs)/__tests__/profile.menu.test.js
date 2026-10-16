// Profile menu: order history is reachable from the menu (it was only
// reachable right after a checkout), and the expert line is a real number.
import React from 'react';
import { Alert, Linking } from 'react-native';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import ProfileScreen from '../profile.jsx';

const mockPush = jest.fn();
jest.mock('expo-router', () => ({ useRouter: () => ({ push: mockPush }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('expo-constants', () => ({ expoConfig: { version: '9.9.9' } }));
jest.mock('../../../context/AuthContext', () => ({ useAuth: () => ({ user: { name: 'Musa', role: 'farmer' }, logout: jest.fn() }) }));
jest.mock('../../../context/LanguageContext', () => ({
  useLanguage: () => ({
    currentLang: { code: 'en', flag: 'GB', name: 'English', nativeName: 'English' }, language: 'en',
    accessibilityMode: false, toggleAccessibility: jest.fn(), voiceEnabled: false, toggleVoice: jest.fn(),
    textSize: 'normal', changeTextSize: jest.fn(),
  }),
  LANGUAGES: [{ code: 'en', name: 'English' }],
}));
jest.mock('../../../components/LanguagePicker', () => () => null);
jest.mock('../../../lib/api', () => ({
  speechAPI: { languages: jest.fn(() => Promise.resolve({ credits: [
    { provider: 'yarngpt', label: 'YarnGPT2 (self-hosted)', license: 'Apache-2.0', credit: null },
    { provider: 'farmerline', label: 'FarmerlineML Hausa (self-hosted)', license: 'CC-BY-4.0', credit: 'Hausa voice: FarmerlineML' },
  ] })) },
}));
jest.mock('../../../services/VoiceService', () => ({ VoiceService: { speak: jest.fn(), stop: jest.fn() } }));

beforeEach(() => { jest.clearAllMocks(); jest.spyOn(Alert, 'alert').mockImplementation(() => {}); });

test('My Orders and My Cart open their screens', () => {
  render(<ProfileScreen />);
  fireEvent.press(screen.getByText('My Orders'));
  fireEvent.press(screen.getByText('My Cart'));
  expect(mockPush.mock.calls.map(c => c[0])).toEqual(['/market/orders', '/market/cart']);
});

test('the expert line offers a real number that dials', () => {
  jest.spyOn(Linking, 'openURL').mockResolvedValue(true);
  render(<ProfileScreen />);
  fireEvent.press(screen.getByText('Contact Expert Vet'));
  const [, message, buttons] = Alert.alert.mock.calls[0];
  expect(message).toBe('Call: 08129582957');
  buttons.find(b => b.text === 'Call now').onPress();
  expect(Linking.openURL).toHaveBeenCalledWith('tel:08129582957');
});

test('About shows the real app version', () => {
  render(<ProfileScreen />);
  fireEvent.press(screen.getByText('About FarmAI'));
  expect(Alert.alert.mock.calls[0][0]).toBe('MSAS FarmAI v9.9.9');
});

test('the voices used for spoken results are credited', async () => {
  render(<ProfileScreen />);
  await act(async () => {});
  const credits = screen.getByTestId('voice-credits');
  const text = [].concat(credits.props.children).join('');
  expect(text).toContain('Hausa voice: FarmerlineML');
  expect(text).toContain('YarnGPT2 (self-hosted) (Apache-2.0)');
});
