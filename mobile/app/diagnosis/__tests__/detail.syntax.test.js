// Syntax/import smoke-test (Phase 10): confirms [id].jsx still parses and
// resolves its imports after adding a load-error state and feedback alert.
jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), push: jest.fn() }), useLocalSearchParams: () => ({ id: '1' }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({ diagnoseAPI: { get: jest.fn(), feedback: jest.fn() }, marketplaceAPI: { recommended: jest.fn() }, cartAPI: { add: jest.fn() } }));
jest.mock('../../../context/LanguageContext', () => ({ useLanguage: () => ({ language: 'en', voiceEnabled: false }), LANGUAGES: [] }));
jest.mock('../../../services/VoiceService', () => ({ VoiceService: { speak: jest.fn(), stop: jest.fn() } }));

test('diagnosis/[id].jsx imports without a syntax or module-resolution error', () => {
  expect(() => require('../[id].jsx')).not.toThrow();
});
