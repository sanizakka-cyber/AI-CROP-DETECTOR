// Phase 11: shared image acquisition for all four scan modes.
import { Image } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { acquireScanImages, MAX_UPLOAD_BYTES, newClientScanId } from '../scanImagePicker';

jest.mock('expo-image-picker', () => ({
  requestCameraPermissionsAsync: jest.fn(), requestMediaLibraryPermissionsAsync: jest.fn(),
  launchCameraAsync: jest.fn(), launchImageLibraryAsync: jest.fn(),
}));
jest.mock('expo-document-picker', () => ({ getDocumentAsync: jest.fn() }));

beforeEach(() => {
  jest.clearAllMocks();
  jest.spyOn(Image, 'getSize').mockImplementation((uri, ok) => ok(1600, 1200));
});

test('Browse Files returns assets with dimensions, size and the real file name', async () => {
  DocumentPicker.getDocumentAsync.mockResolvedValue({
    canceled: false, assets: [{ uri: 'file:///leaf.png', name: 'leaf.png', mimeType: 'image/png', size: 900000 }],
  });
  const r = await acquireScanImages('files');
  expect(DocumentPicker.getDocumentAsync).toHaveBeenCalledWith(expect.objectContaining({
    type: ['image/jpeg', 'image/png', 'image/webp'], copyToCacheDirectory: true,
  }));
  expect(r.assets[0]).toEqual(expect.objectContaining({
    uri: 'file:///leaf.png', width: 1600, height: 1200, fileSize: 900000, fileName: 'leaf.png', mimeType: 'image/png', _source: 'files',
  }));
});

test('Browse Files rejects unsupported types and files over the backend limit with a reason', async () => {
  DocumentPicker.getDocumentAsync.mockResolvedValueOnce({ canceled: false, assets: [{ uri: 'f', name: 'scan.pdf', mimeType: 'application/pdf', size: 10 }] });
  expect((await acquireScanImages('files')).error).toMatch(/not a supported image/);

  DocumentPicker.getDocumentAsync.mockResolvedValueOnce({ canceled: false, assets: [{ uri: 'f', name: 'big.jpg', mimeType: 'image/jpeg', size: MAX_UPLOAD_BYTES + 1 }] });
  expect((await acquireScanImages('files')).error).toMatch(/larger than 10 MB/);
});

test('Browse Files never asks for camera or photo permission', async () => {
  DocumentPicker.getDocumentAsync.mockResolvedValue({ canceled: true, assets: [] });
  expect((await acquireScanImages('files')).canceled).toBe(true);
  expect(ImagePicker.requestCameraPermissionsAsync).not.toHaveBeenCalled();
  expect(ImagePicker.requestMediaLibraryPermissionsAsync).not.toHaveBeenCalled();
});

test('a denied camera permission returns a clear error and opens nothing', async () => {
  ImagePicker.requestCameraPermissionsAsync.mockResolvedValue({ granted: false });
  const r = await acquireScanImages('camera');
  expect(r.error).toMatch(/camera access/);
  expect(ImagePicker.launchCameraAsync).not.toHaveBeenCalled();
});

test('Select Picture uses the photo picker and tags the source', async () => {
  ImagePicker.requestMediaLibraryPermissionsAsync.mockResolvedValue({ granted: true });
  ImagePicker.launchImageLibraryAsync.mockResolvedValue({ canceled: false, assets: [{ uri: 'u', width: 2000, height: 1500, fileSize: 500000 }] });
  const r = await acquireScanImages('gallery', { multiple: true });
  expect(ImagePicker.launchImageLibraryAsync).toHaveBeenCalledWith(expect.objectContaining({ allowsMultipleSelection: true }));
  expect(r.assets[0]._source).toBe('gallery');
});

test('client scan ids are unique', () => {
  expect(newClientScanId()).not.toBe(newClientScanId());
});
