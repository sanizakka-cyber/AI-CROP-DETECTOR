// Phase 8: a subscription-status server error must not demote a paying
// farmer to "no subscription"; it falls back to the last cached status.
import React from 'react';
import { renderHook, waitFor } from '@testing-library/react-native';
import { SubscriptionProvider, useSubscription } from '../SubscriptionContext';

const mockStore = {};
jest.mock('@react-native-async-storage/async-storage', () => ({
  getItem: jest.fn((k) => Promise.resolve(mockStore[k] ?? null)),
  setItem: jest.fn((k, v) => { mockStore[k] = v; return Promise.resolve(); }),
  removeItem: jest.fn(() => Promise.resolve()),
}));
// Stable object: the real AuthProvider keeps the same user between renders,
// and fetchSubscription depends on it.
const mockAuth = { user: { id: 1, role: 'farmer' }, token: 'tok' };
jest.mock('../AuthContext', () => ({ useAuth: () => mockAuth }));

const ACTIVE = { plan: 'basic', status: 'active' };

beforeEach(() => {
  for (const k of Object.keys(mockStore)) delete mockStore[k];
  mockStore.msas_subscription = JSON.stringify(ACTIVE);
});
afterEach(() => { delete global.fetch; });

async function start() {
  const { result } = renderHook(() => useSubscription(), { wrapper: SubscriptionProvider });
  await waitFor(() => expect(global.fetch).toHaveBeenCalled());
  await waitFor(() => expect(result.current.loading).toBe(false));
  return result;
}

test('a 500 falls back to the cached active subscription', async () => {
  global.fetch = jest.fn(() => Promise.resolve({ ok: false, status: 500, json: () => Promise.resolve({}) }));
  const result = await start();
  await waitFor(() => expect(result.current.isActive).toBe(true));
  expect(result.current.currentPlan).toBe('basic');
});

test('a network failure falls back to the cached subscription', async () => {
  global.fetch = jest.fn(() => Promise.reject(new TypeError('Network request failed')));
  const result = await start();
  await waitFor(() => expect(result.current.isActive).toBe(true));
});

test('a successful response replaces the cache with the server value', async () => {
  global.fetch = jest.fn(() => Promise.resolve({
    ok: true, status: 200,
    json: () => Promise.resolve({ subscription: { plan: 'premium', status: 'active' }, usage: {} }),
  }));
  const result = await start();
  await waitFor(() => expect(result.current.currentPlan).toBe('premium'));
  expect(JSON.parse(mockStore.msas_subscription).plan).toBe('premium');
});
