// Add-animal screen (Phase 8): the Records "+ Add Animal" button pointed at
// a route that did not exist. Covers validation, the request body sent to
// AnimalApiController::store, failure handling and double-submit protection.
import React from 'react';
import { Alert } from 'react-native';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { animalsAPI } from '../../../lib/api';
import AddAnimalScreen from '../add.jsx';

const mockBack = jest.fn();
jest.mock('expo-router', () => ({ useRouter: () => ({ back: mockBack }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({ animalsAPI: { create: jest.fn() } }));

const flush = () => act(async () => {});

beforeEach(() => {
  jest.clearAllMocks();
  jest.spyOn(Alert, 'alert').mockImplementation(() => {});
});

test('requires a species before sending anything', async () => {
  render(<AddAnimalScreen />);
  fireEvent.press(screen.getByText('Register Animal'));
  await flush();
  expect(screen.getByText('Choose a species.')).toBeTruthy();
  expect(animalsAPI.create).not.toHaveBeenCalled();
});

test('sends the backend field names and returns to Records on success', async () => {
  animalsAPI.create.mockResolvedValue({ data: { id: 9 }, tag_id: 'CTL-KTS-2610-00009' });
  render(<AddAnimalScreen />);
  fireEvent.press(screen.getByTestId('species-Cattle'));
  fireEvent.changeText(screen.getByTestId('name'), ' Daisy ');
  fireEvent.press(screen.getByTestId('gender-Female'));
  fireEvent.press(screen.getByText('Register Animal'));
  await flush();
  expect(animalsAPI.create).toHaveBeenCalledWith({ species: 'Cattle', gender: 'Female', name: 'Daisy' });
  expect(Alert.alert).toHaveBeenCalledWith('Animal registered', 'Tag number: CTL-KTS-2610-00009');
  expect(mockBack).toHaveBeenCalled();
});

test('"Other" species requires and sends species_other', async () => {
  animalsAPI.create.mockResolvedValue({ data: { id: 10 } });
  render(<AddAnimalScreen />);
  fireEvent.press(screen.getByTestId('species-Other'));
  fireEvent.press(screen.getByText('Register Animal'));
  await flush();
  expect(screen.getByText('Type the species name.')).toBeTruthy();
  fireEvent.changeText(screen.getByTestId('species-other'), 'Ostrich');
  fireEvent.press(screen.getByText('Register Animal'));
  await flush();
  expect(animalsAPI.create).toHaveBeenCalledWith({ species: 'Other', species_other: 'Ostrich', gender: 'Unknown' });
});

test('a failed create shows the error, keeps the form and does not navigate', async () => {
  animalsAPI.create.mockRejectedValue(new Error('MSAS could not be reached.'));
  render(<AddAnimalScreen />);
  fireEvent.press(screen.getByTestId('species-Goat'));
  fireEvent.press(screen.getByText('Register Animal'));
  await flush();
  expect(screen.getByText('MSAS could not be reached.')).toBeTruthy();
  expect(mockBack).not.toHaveBeenCalled();
  expect(Alert.alert).not.toHaveBeenCalled();
});

test('double-tapping Register creates only one animal', async () => {
  let resolve;
  animalsAPI.create.mockReturnValue(new Promise(r => { resolve = r; }));
  render(<AddAnimalScreen />);
  fireEvent.press(screen.getByTestId('species-Sheep'));
  // Same element twice, as a fast double-tap lands before the re-render
  // that swaps the label for a spinner.
  const btn = screen.getByText('Register Animal');
  fireEvent.press(btn);
  fireEvent.press(btn);
  expect(animalsAPI.create).toHaveBeenCalledTimes(1);
  await act(async () => resolve({ data: { id: 11 } }));
});
