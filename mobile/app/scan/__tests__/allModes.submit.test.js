// Phase 12: every scan mode (crop, livestock, soil, pest) offers the same
// three image inputs, submits the image to ITS OWN endpoint with a
// duplicate-safe client_scan_id, shows real upload progress, then opens
// the result.
import React from 'react';
import { Alert } from 'react-native';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { diagnoseAPI } from '../../../lib/api';
import { acquireScanImages } from '../../../lib/scanImagePicker';

const mockReplace = jest.fn();
jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), replace: mockReplace, push: jest.fn() }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => ({ takePhoto: 'Take Photo', fromGallery: 'From Gallery' }[k] || k), i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({ diagnoseAPI: { crop: jest.fn(), livestock: jest.fn(), soil: jest.fn(), pest: jest.fn() } }));
jest.mock('../../../lib/locationCapture', () => ({ useLocationCapture: () => ({ toApiFields: () => ({}) }) }));
jest.mock('../../../components/LocationCaptureSection', () => () => null);
jest.mock('../../../lib/imageValidator', () => ({
  validateImageForScanning: jest.fn(async () => ({ valid: true, qualityScore: 90, warnings: [] })),
  qualityLabel: () => ({ label: 'Good', color: '#0a0' }),
}));
jest.mock('../../../lib/scanImagePicker', () => ({
  acquireScanImages: jest.fn(),
  newClientScanId: jest.fn(() => 'scan-id-1'),
}));

jest.setTimeout(20000);
const flush = () => act(async () => {});

const MODES = [
  { mode: 'crop', screen: () => require('../crop.jsx').default, submit: 'Analyse Crop Now' },
  { mode: 'livestock', screen: () => require('../livestock.jsx').default, submit: 'Analyse Animal Health', before: 'Cattle / Shanu' },
  { mode: 'soil', screen: () => require('../soil.jsx').default, submit: 'Analyse Soil Now' },
  { mode: 'pest', screen: () => require('../pest.jsx').default, submit: 'Identify Pest Now' },
];

beforeEach(() => {
  jest.clearAllMocks();
  jest.spyOn(Alert, 'alert').mockImplementation(() => {});
  acquireScanImages.mockResolvedValue({ canceled: false, assets: [{ uri: 'file:///leaf.jpg', width: 2000, height: 1500, fileName: 'leaf.jpg', mimeType: 'image/jpeg' }] });
});

describe.each(MODES)('$mode scan', ({ mode, screen: Screen, submit, before }) => {
  test('offers Take Picture, Select Picture and Browse Files', () => {
    const Comp = Screen();
    render(<Comp />);
    expect(screen.getByText('Take Picture')).toBeTruthy();
    expect(screen.getByText('Select Picture')).toBeTruthy();
    expect(screen.getByText('Browse Files')).toBeTruthy();
  });

  test.each(['camera', 'gallery', 'files'])('the %s input feeds the image into the scan', async (source) => {
    const Comp = Screen();
    render(<Comp />);
    const label = { camera: 'Take Picture', gallery: 'Select Picture', files: 'Browse Files' }[source];
    fireEvent.press(screen.getByText(label));
    await flush();
    expect(acquireScanImages).toHaveBeenCalledWith(source, expect.any(Object));
  });

  test('submits to its own endpoint with the image, a scan id and upload progress, then opens the result', async () => {
    let finish;
    diagnoseAPI[mode].mockImplementation(({ onProgress }) => {
      onProgress(40);
      return new Promise((r) => { finish = r; });
    });
    const Comp = Screen();
    render(<Comp />);
    if (before) fireEvent.press(screen.getByText(before));
    fireEvent.press(screen.getByText('Browse Files'));
    await flush();
    fireEvent.press(screen.getByText(submit));
    await flush();

    expect(screen.getByText('Uploading photo… 40%')).toBeTruthy();
    const args = diagnoseAPI[mode].mock.calls[0][0];
    expect(args.images[0].uri).toBe('file:///leaf.jpg');
    expect(args.clientScanId).toBe('scan-id-1');
    for (const other of ['crop', 'livestock', 'soil', 'pest'].filter(m => m !== mode)) {
      expect(diagnoseAPI[other]).not.toHaveBeenCalled();
    }

    await act(async () => finish({ diagnosisId: 42 }));
    expect(mockReplace).toHaveBeenCalledWith('/diagnosis/42');
  });

  test('a failed submit shows the reason and leaves the photo in place for retry', async () => {
    diagnoseAPI[mode].mockRejectedValue(new Error('MSAS could not be reached.'));
    const Comp = Screen();
    render(<Comp />);
    if (before) fireEvent.press(screen.getByText(before));
    fireEvent.press(screen.getByText('Browse Files'));
    await flush();
    fireEvent.press(screen.getByText(submit));
    await flush();
    // showScanError: title, the message (no code from the server here), and the buttons.
    expect(Alert.alert).toHaveBeenCalledWith(expect.any(String), 'MSAS could not be reached.', expect.any(Array));
    expect(screen.getByText(submit)).toBeTruthy();
  });

  test('a coded failure shows the plain message and reference, and Try again resubmits the same scan', async () => {
    const e = Object.assign(new Error('raw provider text that must not be shown'), {
      code: 'AI_PROVIDER_NO_CREDIT', reference: 'SCN-7F3K2', action: 'retry', technical: null,
    });
    diagnoseAPI[mode].mockRejectedValueOnce(e).mockResolvedValueOnce({ diagnosisId: 7 });
    const Comp = Screen();
    render(<Comp />);
    if (before) fireEvent.press(screen.getByText(before));
    fireEvent.press(screen.getByText('Browse Files'));
    await flush();
    fireEvent.press(screen.getByText(submit));
    await flush();

    const [, body, buttons] = Alert.alert.mock.calls[0];
    expect(body).toContain('Your scan was NOT counted');
    expect(body).toContain('Reference: SCN-7F3K2');
    expect(body).not.toContain('raw provider text');
    expect(buttons.map((b) => b.text)).not.toContain('Technical details'); // farmers never see them
    await act(async () => buttons.find((b) => b.text === 'Try again').onPress());
    await flush();
    expect(diagnoseAPI[mode]).toHaveBeenCalledTimes(2);
    expect(diagnoseAPI[mode].mock.calls[1][0].clientScanId).toBe('scan-id-1');
    expect(mockReplace).toHaveBeenCalledWith('/diagnosis/7');
  });
});
