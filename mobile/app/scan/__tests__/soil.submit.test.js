// Phase 11: a retry after a failed submit reuses the same client_scan_id
// (so the backend can return the scan it already created), and a new photo
// starts a new id.
import React from 'react';
import { Alert } from 'react-native';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { diagnoseAPI } from '../../../lib/api';
import { acquireScanImages } from '../../../lib/scanImagePicker';
import SoilScreen from '../soil.jsx';

const mockReplace = jest.fn();
jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), replace: mockReplace, push: jest.fn() }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({ diagnoseAPI: { soil: jest.fn() } }));
jest.mock('../../../lib/locationCapture', () => ({ useLocationCapture: () => ({ toApiFields: () => ({}) }) }));
jest.mock('../../../components/LocationCaptureSection', () => () => null);
jest.mock('../../../lib/imageValidator', () => ({
  validateImageForScanning: jest.fn(async () => ({ valid: true, qualityScore: 90, warnings: [] })),
  qualityLabel: () => ({ label: 'Good', color: '#0a0' }),
}));
jest.mock('../../../lib/scanImagePicker', () => {
  let n = 0;
  return {
    acquireScanImages: jest.fn(),
    newClientScanId: jest.fn(() => `id-${++n}`),
  };
});

const flush = () => act(async () => {});

beforeEach(() => {
  jest.clearAllMocks();
  jest.spyOn(Alert, 'alert').mockImplementation(() => {});
  acquireScanImages.mockResolvedValue({ canceled: false, assets: [{ uri: 'file:///soil.jpg', width: 2000, height: 1500 }] });
});

const submit = async () => { fireEvent.press(screen.getByText('Analyse Soil Now')); await flush(); };

test('a retry after a network failure sends the same client scan id', async () => {
  diagnoseAPI.soil
    .mockRejectedValueOnce(new Error('MSAS could not be reached.'))
    .mockResolvedValueOnce({ diagnosisId: 9 });
  render(<SoilScreen />);
  fireEvent.press(screen.getByText('Browse Files'));
  await flush();
  expect(acquireScanImages).toHaveBeenCalledWith('files', expect.any(Object));

  await submit();
  await submit();

  const ids = diagnoseAPI.soil.mock.calls.map(c => c[0].clientScanId);
  expect(ids).toHaveLength(2);
  expect(ids[0]).toBeTruthy();
  expect(ids[1]).toBe(ids[0]);
  expect(mockReplace).toHaveBeenCalledWith('/diagnosis/9');
});

test('choosing a new photo starts a new scan id', async () => {
  diagnoseAPI.soil.mockRejectedValue(new Error('offline'));
  render(<SoilScreen />);
  fireEvent.press(screen.getByText('Take Picture'));
  await flush();
  await submit();
  fireEvent.press(screen.getByText('Select Picture'));
  await flush();
  await submit();

  const ids = diagnoseAPI.soil.mock.calls.map(c => c[0].clientScanId);
  expect(ids[0]).not.toBe(ids[1]);
});

test('a rejected file shows the reason and adds nothing', async () => {
  acquireScanImages.mockResolvedValueOnce({ canceled: false, assets: [], error: '"scan.pdf" is not a supported image.' });
  render(<SoilScreen />);
  fireEvent.press(screen.getByText('Browse Files'));
  await flush();
  expect(Alert.alert).toHaveBeenCalledWith('Photo not accepted', '"scan.pdf" is not a supported image.');
});
