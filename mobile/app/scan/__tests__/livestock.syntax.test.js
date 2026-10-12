// Syntax/import smoke-test (Phase 10): confirms livestock.jsx still
// parses and resolves its imports after wiring in
// validateImageForScanning(). Not a behavioral test -- real component
// rendering needs more native-module mocking than this check is for.
jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), replace: jest.fn() }) }));
jest.mock('expo-image-picker', () => ({
  requestCameraPermissionsAsync: jest.fn(),
  requestMediaLibraryPermissionsAsync: jest.fn(),
  launchCameraAsync: jest.fn(),
  launchImageLibraryAsync: jest.fn(),
}));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({ diagnoseAPI: { livestock: jest.fn() } }));
jest.mock('../../../lib/locationCapture', () => ({ useLocationCapture: () => ({ toApiFields: () => ({}) }) }));

test('livestock.jsx imports without a syntax or module-resolution error', () => {
  expect(() => require('../livestock.jsx')).not.toThrow();
});
