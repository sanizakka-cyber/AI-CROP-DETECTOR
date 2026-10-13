// Phase 9: marketplace next-page loading. A failed page shows a footer
// with Retry (instead of failing silently), repeated end-of-list events
// don't load the same page twice, and a reset starts paging from 1 again.
import React from 'react';
import { FlatList } from 'react-native';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { marketplaceAPI, cartAPI } from '../../../lib/api';
import MarketScreen from '../market.jsx';

jest.mock('expo-router', () => ({ useRouter: () => ({ push: jest.fn() }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({
  marketplaceAPI: { products: jest.fn() },
  cartAPI: { count: jest.fn(() => Promise.resolve(0)), add: jest.fn() },
}));

const page = (n, start = 0) => Array.from({ length: n }, (_, i) => ({ id: start + i + 1, name: `Product ${start + i + 1}`, price: 1000 }));
// First render with fake timers can exceed the 5s default on a loaded machine.
jest.setTimeout(20000);
const flush = () => act(async () => {});
// Every string inside a React element tree, in order.
const textOf = (el) => (el == null || typeof el === 'boolean') ? []
  : (typeof el === 'string' || typeof el === 'number') ? [String(el)]
  : Array.isArray(el) ? el.flatMap(textOf)
  : textOf(el.props?.children);
const endReached = () => act(async () => { screen.UNSAFE_getByType(FlatList).props.onEndReached(); });

beforeEach(() => {
  jest.useFakeTimers();
  jest.clearAllMocks();
});
afterEach(() => jest.useRealTimers());

async function renderFirstPage() {
  marketplaceAPI.products.mockResolvedValueOnce({ data: page(20) });
  render(<MarketScreen />);
  await act(async () => { jest.advanceTimersByTime(600); }); // debounced search effect
  await flush();
}

test('a failed next page shows an error footer and Retry loads that page', async () => {
  await renderFirstPage();
  marketplaceAPI.products.mockRejectedValueOnce(new Error('MSAS could not be reached.'));
  await endReached();
  // FlatList only draws its first 10 rows under test, so the footer (after
  // row 20) is read from the list's props rather than the rendered tree.
  const footer = () => screen.UNSAFE_getByType(FlatList).props.ListFooterComponent;
  expect(textOf(footer())).toEqual(['MSAS could not be reached.', 'Tap to retry']);
  // Auto-loading pauses while the error shows.
  const calls = marketplaceAPI.products.mock.calls.length;
  await endReached();
  expect(marketplaceAPI.products).toHaveBeenCalledTimes(calls);
  marketplaceAPI.products.mockResolvedValueOnce({ data: page(5, 20) });
  await act(async () => { footer().props.onPress(); });
  await flush();
  expect(marketplaceAPI.products.mock.calls.at(-1)[0].page).toBe(2);
  expect(footer()).toBeNull();
});

test('repeated end-of-list events while a page loads request it only once', async () => {
  await renderFirstPage();
  let resolve;
  marketplaceAPI.products.mockReturnValueOnce(new Promise(r => { resolve = r; }));
  const before = marketplaceAPI.products.mock.calls.length;
  await endReached();
  await endReached();
  await endReached();
  expect(marketplaceAPI.products.mock.calls.length - before).toBe(1);
  await act(async () => resolve({ data: page(3, 20) }));
});

test('after a reset, the next page requested is page 2 again', async () => {
  await renderFirstPage();
  marketplaceAPI.products.mockResolvedValueOnce({ data: page(20, 20) }); // page 2
  await endReached();
  marketplaceAPI.products.mockResolvedValueOnce({ data: page(20) }); // refresh -> page 1
  await act(async () => { screen.UNSAFE_getByType(FlatList).props.refreshControl.props.onRefresh(); });
  await flush();
  marketplaceAPI.products.mockResolvedValueOnce({ data: page(2, 20) });
  await endReached();
  expect(marketplaceAPI.products.mock.calls.at(-1)[0].page).toBe(2);
});

test('opening the marketplace loads page 1 exactly once', async () => {
  marketplaceAPI.products.mockResolvedValue({ data: page(3) });
  render(<MarketScreen />);
  await act(async () => { jest.advanceTimersByTime(2000); });
  await flush();
  expect(marketplaceAPI.products).toHaveBeenCalledTimes(1);
});

test('typing a search still reloads after the debounce', async () => {
  marketplaceAPI.products.mockResolvedValue({ data: page(3) });
  render(<MarketScreen />);
  await flush();
  fireEvent.changeText(screen.getByPlaceholderText(/search/i), 'maize');
  await act(async () => { jest.advanceTimersByTime(600); });
  await flush();
  expect(marketplaceAPI.products).toHaveBeenCalledTimes(2);
  expect(marketplaceAPI.products.mock.calls[1][0].search).toBe('maize');
});
