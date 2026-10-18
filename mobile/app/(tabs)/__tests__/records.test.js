// Records tab behavior (Phase 8). The two requests are independent: each
// list shows its own error/retry, an empty state only after its own request
// succeeded, and one failure never discards the other list's data.
import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react-native';
import { diagnoseAPI, animalsAPI } from '../../../lib/api';
import RecordsScreen from '../records.jsx';

const mockPush = jest.fn();
let mockParams = {};
jest.mock('expo-router', () => {
  const { useEffect } = require('react');
  return {
    useRouter: () => ({ push: mockPush }),
    useLocalSearchParams: () => mockParams,
    useFocusEffect: (cb) => useEffect(cb, [cb]),
  };
});
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({ diagnoseAPI: { history: jest.fn() }, animalsAPI: { list: jest.fn() } }));

const DIAG = { id: 7, status: 'reviewed', type: 'plant', subject_name: 'Maize leaf blight', created_at: '2026-10-01T10:00:00Z' };
const ANIMAL = { id: 3, name: 'Daisy', tag_number: 'CTL-KTS-2610-00001', species: 'Cattle', gender: 'Female', health_status: 'sick', breed: 'White Fulani' };

beforeEach(() => {
  jest.clearAllMocks();
  mockParams = {};
});

test('shows a loading indicator, not an empty state, before the requests settle', async () => {
  let resolve;
  diagnoseAPI.history.mockReturnValue(new Promise(r => { resolve = r; }));
  animalsAPI.list.mockResolvedValue({ data: [] });
  render(<RecordsScreen />);
  expect(screen.getByTestId('records-loading')).toBeTruthy();
  expect(screen.queryByText('noDiagnoses')).toBeNull();
  await act(async () => resolve({ data: [] }));
  expect(await screen.findByText('noDiagnoses')).toBeTruthy();
});

test('diagnoses request failure shows its error with Retry, not the empty state', async () => {
  diagnoseAPI.history.mockRejectedValue(new Error('MSAS could not be reached.'));
  animalsAPI.list.mockResolvedValue({ data: [] });
  render(<RecordsScreen />);
  expect(await screen.findByText('MSAS could not be reached.')).toBeTruthy();
  expect(screen.getByText('Retry')).toBeTruthy();
  expect(screen.queryByText('noDiagnoses')).toBeNull();
});

test('animals request failure shows its error on the animals tab', async () => {
  mockParams = { tab: 'animals' };
  diagnoseAPI.history.mockResolvedValue({ data: [DIAG] });
  animalsAPI.list.mockRejectedValue(new Error('MSAS server error. Please try again later.'));
  render(<RecordsScreen />);
  expect(await screen.findByText('MSAS server error. Please try again later.')).toBeTruthy();
  expect(screen.queryByText('No animals added yet')).toBeNull();
});

test('one failed request does not discard the other request\'s valid results', async () => {
  diagnoseAPI.history.mockResolvedValue({ data: [DIAG] });
  animalsAPI.list.mockRejectedValue(new Error('animals down'));
  render(<RecordsScreen />);
  // Diagnoses tab still renders its real data despite the animals failure.
  expect(await screen.findByText('Maize leaf blight')).toBeTruthy();
  fireEvent.press(screen.getByText(/myAnimals/));
  expect(screen.getByText('animals down')).toBeTruthy();
});

test('Retry repeats the failed request and renders the data when it succeeds', async () => {
  diagnoseAPI.history
    .mockRejectedValueOnce(new Error('offline'))
    .mockResolvedValueOnce({ data: [DIAG] });
  animalsAPI.list.mockResolvedValue({ data: [] });
  render(<RecordsScreen />);
  fireEvent.press(await screen.findByText('Retry'));
  expect(await screen.findByText('Maize leaf blight')).toBeTruthy();
  expect(screen.queryByText('offline')).toBeNull();
  expect(diagnoseAPI.history).toHaveBeenCalledTimes(2);
});

test('rapid repeated Retry taps issue only one request', async () => {
  diagnoseAPI.history.mockRejectedValueOnce(new Error('offline'));
  animalsAPI.list.mockResolvedValue({ data: [] });
  render(<RecordsScreen />);
  const retry = await screen.findByText('Retry');
  let resolve;
  diagnoseAPI.history.mockReturnValue(new Promise(r => { resolve = r; }));
  fireEvent.press(retry);
  fireEvent.press(retry);
  fireEvent.press(retry);
  expect(diagnoseAPI.history).toHaveBeenCalledTimes(2); // initial + one retry
  await act(async () => resolve({ data: [] }));
});

test('genuinely empty results show the empty states, not an error', async () => {
  diagnoseAPI.history.mockResolvedValue({ data: [] });
  animalsAPI.list.mockResolvedValue({ data: [] });
  render(<RecordsScreen />);
  expect(await screen.findByText('noDiagnoses')).toBeTruthy();
  fireEvent.press(screen.getByText(/myAnimals/));
  expect(screen.getByText('No animals added yet')).toBeTruthy();
  expect(screen.queryByText('Retry')).toBeNull();
});

test('animals render from the Laravel field names (name, species, gender, tag)', async () => {
  mockParams = { tab: 'animals' };
  diagnoseAPI.history.mockResolvedValue({ data: [] });
  animalsAPI.list.mockResolvedValue({ data: [ANIMAL, { ...ANIMAL, id: 4, name: null }] });
  render(<RecordsScreen />);
  expect(await screen.findByText('Daisy')).toBeTruthy();
  expect(screen.getByText('White Fulani · Female · CTL-KTS-2610-00001')).toBeTruthy();
  // An unnamed animal falls back to its tag number, never "Unnamed".
  expect(screen.getByText('CTL-KTS-2610-00001')).toBeTruthy();
  expect(screen.getAllByLabelText('sick')).toHaveLength(2);
});

test('the Add Animal button opens the add-animal route', async () => {
  mockParams = { tab: 'animals' };
  diagnoseAPI.history.mockResolvedValue({ data: [] });
  animalsAPI.list.mockResolvedValue({ data: [] });
  render(<RecordsScreen />);
  fireEvent.press(await screen.findByText('+ addAnimal'));
  expect(mockPush).toHaveBeenCalledWith('/animals/add');
});

// Phase 12.2: the list badge is the severity (severity_level), the same value
// as the result screen's header and details, never the urgency.
test('the records badge shows the severity, not the urgency', async () => {
  const row = JSON.parse(require('fs').readFileSync(require('path').join(__dirname, '../../../../shared-fixtures/severity-diagnosis.json'), 'utf8')).historyRow;
  diagnoseAPI.history.mockResolvedValue({ data: [row] });
  animalsAPI.list.mockResolvedValue({ data: [] });
  render(<RecordsScreen />);
  const badge = await screen.findByTestId('records-severity');
  const text = badge.findAll(n => n.props && typeof n.props.children !== 'undefined').map(n => [].concat(n.props.children).filter(c => typeof c === 'string').join('')).join(' ');
  expect(text).toContain('MODERATE');
  expect(text).not.toContain('HIGH');
});
