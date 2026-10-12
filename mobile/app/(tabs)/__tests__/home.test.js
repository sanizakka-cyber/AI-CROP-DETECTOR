// Home dashboard (Phase 8): a failed dashboard load must show an error,
// never zeros, "No active outbreaks", or an endless "Loading platform data".
import React from 'react';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { analyticsAPI, weatherAPI, notificationsAPI } from '../../../lib/api';
import HomeScreen from '../home.jsx';

let mockUser = { role: 'vet', name: 'Amina', state: 'Katsina' };
jest.mock('expo-router', () => ({ useRouter: () => ({ push: jest.fn() }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../context/AuthContext', () => ({ useAuth: () => ({ user: mockUser }) }));
jest.mock('../../../lib/api', () => ({
  analyticsAPI: { summary: jest.fn(), adminSummary: jest.fn(), outbreaks: jest.fn(), insurability: jest.fn() },
  weatherAPI: { current: jest.fn() },
  notificationsAPI: { list: jest.fn() },
}));

const flush = () => act(async () => {});

beforeEach(() => {
  jest.clearAllMocks();
  weatherAPI.current.mockResolvedValue({});
  notificationsAPI.list.mockResolvedValue({ unread_count: 0 });
});

test('vet: a failed outbreak load shows an error, not "No active outbreaks"', async () => {
  mockUser = { role: 'vet', name: 'Amina' };
  analyticsAPI.outbreaks.mockRejectedValueOnce(new Error('MSAS could not be reached.'));
  render(<HomeScreen />);
  await flush();
  expect(screen.getByText('Dashboard unavailable')).toBeTruthy();
  expect(screen.getByText('MSAS could not be reached.')).toBeTruthy();
  expect(screen.queryByText(/No active outbreaks/)).toBeNull();
});

test('vet: Retry reloads and shows the real (empty) result once it succeeds', async () => {
  mockUser = { role: 'vet', name: 'Amina' };
  analyticsAPI.outbreaks
    .mockRejectedValueOnce(new Error('offline'))
    .mockResolvedValueOnce({ outbreaks: [] });
  render(<HomeScreen />);
  await flush();
  fireEvent.press(screen.getByText('Retry'));
  await flush();
  expect(screen.queryByText('Dashboard unavailable')).toBeNull();
  expect(screen.getByText(/No active outbreaks/)).toBeTruthy();
});

test('admin: a failed load shows an error instead of loading forever', async () => {
  mockUser = { role: 'admin', name: 'Sani' };
  analyticsAPI.adminSummary.mockRejectedValueOnce(new Error('MSAS server error. Please try again later.'));
  render(<HomeScreen />);
  await flush();
  expect(screen.getByText('MSAS server error. Please try again later.')).toBeTruthy();
  expect(screen.queryByText(/Loading platform data/)).toBeNull();
});

test('weather/notification failure alone does not block the dashboard', async () => {
  mockUser = { role: 'vet', name: 'Amina' };
  weatherAPI.current.mockRejectedValueOnce(new Error('weather down'));
  analyticsAPI.outbreaks.mockResolvedValueOnce({ outbreaks: [] });
  render(<HomeScreen />);
  await flush();
  expect(screen.queryByText('Dashboard unavailable')).toBeNull();
});
