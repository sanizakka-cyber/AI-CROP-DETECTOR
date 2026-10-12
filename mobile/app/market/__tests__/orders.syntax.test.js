// Syntax/import smoke-test (Phase 10): confirms orders.jsx still parses
// and resolves its imports after adding a distinct load-error state.
jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), replace: jest.fn() }) }));
jest.mock('../../../lib/api', () => ({ ordersAPI: { list: jest.fn(), cancel: jest.fn() } }));

test('orders.jsx imports without a syntax or module-resolution error', () => {
  expect(() => require('../orders.jsx')).not.toThrow();
});
