// Syntax/import smoke-test (Phase 10): confirms notifications.jsx still
// parses and resolves its imports after surfacing action failures.
jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), push: jest.fn() }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../lib/api', () => ({ notificationsAPI: { list: jest.fn(), markRead: jest.fn(), markAllRead: jest.fn(), delete: jest.fn() } }));

test('notifications.jsx imports without a syntax or module-resolution error', () => {
  expect(() => require('../notifications.jsx')).not.toThrow();
});
