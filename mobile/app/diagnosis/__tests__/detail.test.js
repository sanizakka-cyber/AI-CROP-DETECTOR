// Diagnosis result screen behavior (Phase 8): load failure, retry, polling
// to completion, polling timeout, no overlapping/duplicate requests, timer
// cleanup on unmount, and feedback success/failure.
import React from 'react';
import { Alert } from 'react-native';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { diagnoseAPI } from '../../../lib/api';
import { VoiceService } from '../../../services/VoiceService';
import DiagnosisDetailScreen from '../[id].jsx';

const mockBack = jest.fn();
jest.mock('expo-router', () => ({
  useRouter: () => ({ back: mockBack, push: jest.fn() }),
  useLocalSearchParams: () => ({ id: '42' }),
}));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({
  diagnoseAPI: { get: jest.fn(), feedback: jest.fn() },
  marketplaceAPI: { recommended: jest.fn(() => Promise.resolve({ data: [] })) },
  cartAPI: { add: jest.fn() },
}));
let mockVoiceEnabled = false;
jest.mock('../../../context/LanguageContext', () => ({ useLanguage: () => ({ language: 'en', voiceEnabled: mockVoiceEnabled }), LANGUAGES: [] }));
jest.mock('../../../services/VoiceService', () => ({ VoiceService: {
  speak: jest.fn(), stop: jest.fn(), buildCropNarration: jest.fn(() => 'narration'),
} }));

const PENDING = { id: 42, status: 'pending' };
const DONE = {
  id: 42, status: 'reviewed', type: 'plant',
  aiResult: { primaryDiagnosis: 'Northern Leaf Blight', confidence: 0.9 },
  treatmentPlan: {},
};

// Flush resolved promises inside act so state updates land.
const flush = () => act(async () => {});
const advance = (ms) => act(async () => { jest.advanceTimersByTime(ms); });

beforeEach(() => {
  jest.useFakeTimers();
  jest.clearAllMocks();
  mockVoiceEnabled = false;
  jest.spyOn(Alert, 'alert').mockImplementation(() => {});
});
afterEach(() => {
  jest.useRealTimers();
});

test('initial fetch failure exits the spinner and shows the error with Retry', async () => {
  diagnoseAPI.get.mockRejectedValue(new Error('MSAS could not be reached.'));
  render(<DiagnosisDetailScreen />);
  await flush();
  expect(screen.getByText('Could not load result')).toBeTruthy();
  expect(screen.getByText('MSAS could not be reached.')).toBeTruthy();
  expect(screen.queryByText('AI is validating and analysing your scan...')).toBeNull();
  // Failure stops polling: no further requests are scheduled.
  await advance(30000);
  expect(diagnoseAPI.get).toHaveBeenCalledTimes(1);
});

test('Retry issues exactly one new request and resumes polling to completion', async () => {
  diagnoseAPI.get
    .mockRejectedValueOnce(new Error('offline'))
    .mockResolvedValueOnce({ diagnosis: PENDING })
    .mockResolvedValueOnce({ diagnosis: DONE });
  render(<DiagnosisDetailScreen />);
  await flush();
  fireEvent.press(screen.getByText('Retry'));
  await flush();
  expect(diagnoseAPI.get).toHaveBeenCalledTimes(2); // not 3: no duplicate fetch on retry
  expect(screen.getByText('AI is validating and analysing your scan...')).toBeTruthy();
  await advance(3000);
  expect(diagnoseAPI.get).toHaveBeenCalledTimes(3);
  expect(screen.queryByText('AI is validating and analysing your scan...')).toBeNull();
  // Terminal result: polling stops, no extra trailing fetch.
  await advance(30000);
  expect(diagnoseAPI.get).toHaveBeenCalledTimes(3);
});

test('polling waits for each response, so slow requests never overlap', async () => {
  let resolveFirst;
  diagnoseAPI.get
    .mockReturnValueOnce(new Promise(r => { resolveFirst = r; }))
    .mockResolvedValue({ diagnosis: DONE });
  render(<DiagnosisDetailScreen />);
  await advance(15000); // first request still hanging
  expect(diagnoseAPI.get).toHaveBeenCalledTimes(1);
  await act(async () => resolveFirst({ diagnosis: PENDING }));
  await advance(3000);
  expect(diagnoseAPI.get).toHaveBeenCalledTimes(2);
});

test('a scan stuck in pending times out instead of spinning forever', async () => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis: PENDING });
  render(<DiagnosisDetailScreen />);
  await flush();
  for (let i = 0; i < 70; i++) await advance(3000); // > 3 minutes
  expect(screen.getByText('Analysis is taking longer than expected')).toBeTruthy();
  const calls = diagnoseAPI.get.mock.calls.length;
  await advance(60000);
  expect(diagnoseAPI.get).toHaveBeenCalledTimes(calls); // stopped
  // "Check again" restarts a fresh polling window.
  diagnoseAPI.get.mockResolvedValue({ diagnosis: DONE });
  fireEvent.press(screen.getByText('Check again'));
  await flush();
  expect(diagnoseAPI.get).toHaveBeenCalledTimes(calls + 1);
  expect(screen.queryByText('Analysis is taking longer than expected')).toBeNull();
});

test('repeated retries never leave more than one polling loop running', async () => {
  diagnoseAPI.get.mockRejectedValueOnce(new Error('offline'));
  render(<DiagnosisDetailScreen />);
  await flush();
  diagnoseAPI.get.mockResolvedValue({ diagnosis: PENDING });
  fireEvent.press(screen.getByText('Retry'));
  await flush();
  const afterRetry = diagnoseAPI.get.mock.calls.length;
  // Step one interval at a time: each next request is scheduled only after
  // the previous response settles. Two live loops would make 10 calls here.
  for (let i = 0; i < 5; i++) await advance(3000);
  // One loop => exactly one request per interval.
  expect(diagnoseAPI.get.mock.calls.length - afterRetry).toBe(5);
});

test('leaving the screen stops polling and voice', async () => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis: PENDING });
  const { unmount } = render(<DiagnosisDetailScreen />);
  await flush();
  unmount();
  const calls = diagnoseAPI.get.mock.calls.length;
  await advance(30000);
  expect(diagnoseAPI.get).toHaveBeenCalledTimes(calls);
  expect(VoiceService.stop).toHaveBeenCalled();
});

test('auto-narration fires on a finished result when the user stays', async () => {
  mockVoiceEnabled = true;
  diagnoseAPI.get.mockResolvedValue({ diagnosis: DONE });
  render(<DiagnosisDetailScreen />);
  await flush();
  await advance(1000);
  expect(VoiceService.speak).toHaveBeenCalledTimes(1);
});

test('auto-narration scheduled before leaving never fires after leaving', async () => {
  mockVoiceEnabled = true;
  diagnoseAPI.get.mockResolvedValue({ diagnosis: DONE });
  const { unmount } = render(<DiagnosisDetailScreen />);
  await flush(); // result arrives; narration is scheduled 600ms out
  unmount();
  await advance(1000);
  expect(VoiceService.speak).not.toHaveBeenCalled();
});

test('feedback success shows the thank-you card', async () => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis: DONE });
  diagnoseAPI.feedback.mockResolvedValue({});
  render(<DiagnosisDetailScreen />);
  await flush();
  fireEvent.press(screen.getByText('Yes'));
  await flush();
  expect(diagnoseAPI.feedback).toHaveBeenCalledWith('42', { rating: 'thumbs_up' });
  expect(screen.getByText('Thank you for your feedback!')).toBeTruthy();
});

test('feedback failure alerts and keeps the buttons, without faking success', async () => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis: DONE });
  diagnoseAPI.feedback.mockRejectedValue(new Error('MSAS server error. Please try again later.'));
  render(<DiagnosisDetailScreen />);
  await flush();
  fireEvent.press(screen.getByText('No'));
  await flush();
  expect(Alert.alert).toHaveBeenCalledWith('Error', 'MSAS server error. Please try again later.');
  expect(screen.queryByText('Thank you for your feedback!')).toBeNull();
  expect(screen.getByText('Yes')).toBeTruthy();
});

test('double-tapping feedback sends a single request', async () => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis: DONE });
  let resolve;
  diagnoseAPI.feedback.mockReturnValue(new Promise(r => { resolve = r; }));
  render(<DiagnosisDetailScreen />);
  await flush();
  fireEvent.press(screen.getByText('Yes'));
  fireEvent.press(screen.getByText('Yes'));
  fireEvent.press(screen.getByText('No'));
  expect(diagnoseAPI.feedback).toHaveBeenCalledTimes(1);
  await act(async () => resolve({}));
});
