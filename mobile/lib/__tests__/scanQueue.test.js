// Offline scans (lib/scanQueue): saved on the phone with their photos and
// GPS location, sent when online with the same client_scan_id (no duplicate
// scan), "pending" → "synced"; a refusal is "failed" with its reason.
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as FileSystem from 'expo-file-system/legacy';
import { Alert } from 'react-native';
import { scanQueue, isOfflineError, saveScanOffline } from '../scanQueue';
import { diagnoseAPI } from '../api';

jest.mock('../api', () => ({ diagnoseAPI: { crop: jest.fn(), soil: jest.fn() } }));

const location = { loc_latitude: '11.523', loc_longitude: '7.312', loc_accuracy_meters: '5', loc_is_mocked: '0', loc_gps_captured_at: '2026-10-23T07:00:00.000Z' };
const payload = () => ({ clientScanId: 'scan-abc', onProgress: jest.fn(), cropType: 'maize', images: [{ uri: 'file:///cache/ImagePicker/x.jpg', fileName: 'x.jpg', mimeType: 'image/jpeg' }], location });

beforeEach(async () => { await AsyncStorage.clear(); jest.clearAllMocks(); });

test('only "could not reach MSAS" failures are saved for later', () => {
  expect(isOfflineError({ kind: 'network' })).toBe(true);
  expect(isOfflineError({ kind: 'timeout' })).toBe(true);
  expect(isOfflineError({ kind: 'server', status: 503 })).toBe(false);
  expect(isOfflineError({ kind: 'validation', status: 422 })).toBe(false);
});

test('a saved scan keeps its photo (copied into app storage), fields and GPS location, as pending', async () => {
  await scanQueue.enqueue('crop', payload());
  expect(FileSystem.copyAsync).toHaveBeenCalledWith({ from: 'file:///cache/ImagePicker/x.jpg', to: 'file:///docs/pending-scans/scan-abc/0-x.jpg' });
  const [item] = await scanQueue.list();
  expect(item).toMatchObject({ id: 'scan-abc', type: 'crop', status: 'pending', params: { cropType: 'maize', location } });
  expect(item.params.onProgress).toBeUndefined();
  expect(item.images[0].uri).toBe('file:///docs/pending-scans/scan-abc/0-x.jpg');
});

test('sync sends it with the same scan id, marks it synced and removes the photo copy', async () => {
  await scanQueue.enqueue('crop', payload());
  diagnoseAPI.crop.mockResolvedValue({ diagnosisId: 42 });
  expect(await scanQueue.flush()).toBe(1);
  expect(diagnoseAPI.crop).toHaveBeenCalledWith(expect.objectContaining({ clientScanId: 'scan-abc', cropType: 'maize', location }));
  const [item] = await scanQueue.list();
  expect(item).toMatchObject({ status: 'synced', diagnosisId: 42 });
  expect(FileSystem.deleteAsync).toHaveBeenCalledWith('file:///docs/pending-scans/scan-abc/', { idempotent: true });
});

test('still offline: it stays pending; refused by the server: failed with the reason', async () => {
  await scanQueue.enqueue('crop', payload());
  diagnoseAPI.crop.mockRejectedValueOnce({ kind: 'network' });
  expect(await scanQueue.flush()).toBe(0);
  expect((await scanQueue.list())[0].status).toBe('pending');

  diagnoseAPI.crop.mockRejectedValueOnce({ kind: 'validation', status: 422, message: 'This photo is too large.' });
  await scanQueue.flush();
  expect((await scanQueue.list())[0]).toMatchObject({ status: 'failed', error: 'This photo is too large.' });
});

test('a scan screen saves offline and tells the farmer "Saved on phone – not yet synced"', async () => {
  const alert = jest.spyOn(Alert, 'alert').mockImplementation(() => {});
  const router = { replace: jest.fn() };
  const t = (k) => k;
  expect(await saveScanOffline({ kind: 'network' }, 'soil', payload(), { t, router })).toBe(true);
  expect(alert).toHaveBeenCalledWith('scan_saved_offline_title', 'scan_saved_offline_body', expect.any(Array));
  expect((await scanQueue.list())[0].type).toBe('soil');
  expect(await saveScanOffline({ kind: 'server', status: 503 }, 'soil', payload(), { t, router })).toBe(false);
  alert.mockRestore();
});
