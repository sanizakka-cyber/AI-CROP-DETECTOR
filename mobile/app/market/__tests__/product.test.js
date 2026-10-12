// Product detail (Phase 8): only a real 404 is "Product not found"; any
// other failure shows its message with a working Retry.
import React from 'react';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { marketplaceAPI } from '../../../lib/api';
import ProductScreen from '../product.jsx';

jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), push: jest.fn() }), useLocalSearchParams: () => ({ id: '5' }) }));
jest.mock('../../../lib/api', () => ({ marketplaceAPI: { product: jest.fn() }, cartAPI: { add: jest.fn() } }));

const flush = () => act(async () => {});
const notFound = Object.assign(new Error('That MSAS resource could not be found.'), { status: 404 });

beforeEach(() => jest.clearAllMocks());

test('a network failure is not reported as "Product not found"', async () => {
  marketplaceAPI.product.mockRejectedValueOnce(new Error('MSAS could not be reached.'));
  render(<ProductScreen />);
  await flush();
  expect(screen.getByText('MSAS could not be reached.')).toBeTruthy();
  expect(screen.queryByText('Product not found')).toBeNull();
});

test('a real 404 shows "Product not found"', async () => {
  marketplaceAPI.product.mockRejectedValueOnce(notFound);
  render(<ProductScreen />);
  await flush();
  expect(screen.getByText('Product not found')).toBeTruthy();
});

test('Retry refetches the product', async () => {
  marketplaceAPI.product
    .mockRejectedValueOnce(new Error('offline'))
    .mockResolvedValueOnce({ data: { id: 5, name: 'Mancozeb 80WP', price: 2500, stock_status: 'in_stock' } });
  render(<ProductScreen />);
  await flush();
  fireEvent.press(screen.getByText('Retry'));
  await flush();
  expect(marketplaceAPI.product).toHaveBeenCalledTimes(2);
  expect(screen.getAllByText('Mancozeb 80WP').length).toBeGreaterThan(0);
});
