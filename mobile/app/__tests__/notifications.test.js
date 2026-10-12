// Notifications behavior (Phase 8): delete and mark-all-read update the UI
// only after the server confirms, alert on failure, and ignore repeat taps.
import React from 'react';
import { Alert } from 'react-native';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { notificationsAPI } from '../../lib/api';
import NotificationsScreen from '../notifications.jsx';

jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), push: jest.fn() }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../lib/api', () => ({ notificationsAPI: { list: jest.fn(), markRead: jest.fn(), markAllRead: jest.fn(), delete: jest.fn() } }));

const N1 = { id: 1, title: 'Scan ready', body: 'Your maize scan is ready', type: 'diagnosis', created_at: '2026-10-01T10:00:00Z', read_at: null };
const N2 = { id: 2, title: 'Order shipped', body: 'Order #12 shipped', type: 'order', created_at: '2026-10-02T10:00:00Z', read_at: null };

const flush = () => act(async () => {});

// Long-press opens a confirm Alert; this presses its "Delete" button.
function confirmDelete() {
  const call = Alert.alert.mock.calls.find(c => c[0] === 'Delete Notification');
  const del = call[2].find(b => b.text === 'Delete');
  return act(async () => { del.onPress(); });
}

beforeEach(() => {
  jest.clearAllMocks();
  jest.spyOn(Alert, 'alert').mockImplementation(() => {});
  notificationsAPI.list.mockResolvedValue({ data: [N1, N2], unread_count: 2 });
});

test('deleting a notification removes it after the server confirms', async () => {
  notificationsAPI.delete.mockResolvedValue({});
  render(<NotificationsScreen />);
  await flush();
  fireEvent(screen.getByText('Scan ready'), 'longPress');
  await confirmDelete();
  expect(notificationsAPI.delete).toHaveBeenCalledWith(1);
  expect(screen.queryByText('Scan ready')).toBeNull();
  expect(screen.getByText(/1 new/)).toBeTruthy();
});

test('a failed delete alerts and keeps the notification on screen', async () => {
  notificationsAPI.delete.mockRejectedValue(new Error('MSAS could not be reached.'));
  render(<NotificationsScreen />);
  await flush();
  fireEvent(screen.getByText('Scan ready'), 'longPress');
  await confirmDelete();
  expect(Alert.alert).toHaveBeenCalledWith('Error', 'MSAS could not be reached.');
  expect(screen.getByText('Scan ready')).toBeTruthy();
  expect(screen.getByText(/2 new/)).toBeTruthy();
});

test('mark-all-read clears unread state after the server confirms', async () => {
  notificationsAPI.markAllRead.mockResolvedValue({});
  render(<NotificationsScreen />);
  await flush();
  fireEvent.press(screen.getByText('Mark all read'));
  await flush();
  expect(notificationsAPI.markAllRead).toHaveBeenCalledTimes(1);
  expect(screen.queryByText(/new/)).toBeNull();
  expect(screen.queryByText('Mark all read')).toBeNull();
});

test('a failed mark-all-read alerts and leaves everything unread', async () => {
  notificationsAPI.markAllRead.mockRejectedValue(new Error('MSAS server error. Please try again later.'));
  render(<NotificationsScreen />);
  await flush();
  fireEvent.press(screen.getByText('Mark all read'));
  await flush();
  expect(Alert.alert).toHaveBeenCalledWith('Error', 'MSAS server error. Please try again later.');
  expect(screen.getByText(/2 new/)).toBeTruthy();
  expect(screen.getByText('Mark all read')).toBeTruthy();
});

test('repeated mark-all-read taps send one request', async () => {
  let resolve;
  notificationsAPI.markAllRead.mockReturnValue(new Promise(r => { resolve = r; }));
  render(<NotificationsScreen />);
  await flush();
  const btn = screen.getByText('Mark all read');
  fireEvent.press(btn);
  fireEvent.press(btn);
  fireEvent.press(btn);
  expect(notificationsAPI.markAllRead).toHaveBeenCalledTimes(1);
  await act(async () => resolve({}));
});

test('confirming delete twice on the same item sends one request', async () => {
  let resolve;
  notificationsAPI.delete.mockReturnValue(new Promise(r => { resolve = r; }));
  render(<NotificationsScreen />);
  await flush();
  fireEvent(screen.getByText('Scan ready'), 'longPress');
  await confirmDelete();
  await confirmDelete();
  expect(notificationsAPI.delete).toHaveBeenCalledTimes(1);
  await act(async () => resolve({}));
  expect(screen.queryByText('Scan ready')).toBeNull();
  expect(screen.getByText(/1 new/)).toBeTruthy(); // decremented once, not twice
});

test('a failed load shows the error with Retry, not the empty state', async () => {
  notificationsAPI.list.mockRejectedValueOnce(new Error('offline')).mockResolvedValueOnce({ data: [], unread_count: 0 });
  render(<NotificationsScreen />);
  await flush();
  expect(screen.getByText('offline')).toBeTruthy();
  expect(screen.queryByText('No notifications yet')).toBeNull();
  fireEvent.press(screen.getByText('Retry'));
  await flush();
  expect(screen.getByText('No notifications yet')).toBeTruthy();
});
