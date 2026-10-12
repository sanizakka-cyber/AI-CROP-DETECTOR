// Syntax/import smoke-test (Phase 10): confirms cart.jsx still parses
// and resolves its imports after adding distinct loading/error/empty
// state handling. Not a behavioral test -- real component rendering
// needs more native-module mocking than this check is for.
jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), replace: jest.fn() }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({
  cartAPI: { get: jest.fn(), update: jest.fn(), remove: jest.fn(), clear: jest.fn() },
  ordersAPI: { checkout: jest.fn() },
}));

test('cart.jsx imports without a syntax or module-resolution error', () => {
  expect(() => require('../cart.jsx')).not.toThrow();
});
