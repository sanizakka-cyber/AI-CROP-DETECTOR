// Syntax/import smoke-test (Phase 10): confirms records.jsx still parses
// and resolves its imports after adding a distinct load-error state.
jest.mock('expo-router', () => ({ useRouter: () => ({ push: jest.fn() }), useLocalSearchParams: () => ({}) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({ diagnoseAPI: { history: jest.fn() }, animalsAPI: { list: jest.fn() } }));

test('records.jsx imports without a syntax or module-resolution error', () => {
  expect(() => require('../records.jsx')).not.toThrow();
});
