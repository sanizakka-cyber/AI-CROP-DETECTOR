// Scans made without internet: the photos are copied into the app's own
// storage (a picker or camera URI does not survive), and the scan's fields,
// including its GPS location (coordinates, accuracy, time, mock flag), are
// kept with them in AsyncStorage. flush() sends them when the phone is back
// online, with the scan's client_scan_id, so a retry can never create a
// second scan. Place names the server fills from GPS are filled then.
// Status per scan: "pending" (saved on phone, not yet synced), "synced"
// (with its diagnosisId) or "failed" (the server refused it; the reason is
// kept and the farmer can remove it).
import { Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as FileSystem from 'expo-file-system/legacy';
import { diagnoseAPI } from './api';

const KEY = 'msas_pending_scans';
const DIR = `${FileSystem.documentDirectory}pending-scans/`;
// Synced scans stay listed (as "Synced") for a week, then are dropped from the list.
const KEEP_SYNCED_MS = 7 * 24 * 60 * 60 * 1000;
const TYPES = ['crop', 'livestock', 'soil', 'pest'];

const listeners = new Set();
let flushing = null;

/** A failure where the scan never reached MSAS (or no answer came back): save it and send later. */
export function isOfflineError(e) {
  return e?.kind === 'network' || e?.kind === 'timeout';
}

async function read() {
  try {
    const raw = await AsyncStorage.getItem(KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

async function write(items) {
  await AsyncStorage.setItem(KEY, JSON.stringify(items));
  listeners.forEach(fn => { try { fn(items); } catch { /* a listener's error is its own */ } });
}

async function removeFiles(id) {
  try { await FileSystem.deleteAsync(`${DIR}${id}/`, { idempotent: true }); } catch { /* already gone */ }
}

export const scanQueue = {
  /**
   * Saves a scan to send later. `payload` is what diagnoseAPI[type]() takes
   * (images, location, clientScanId, the scan's own fields); onProgress is dropped.
   */
  async enqueue(type, payload) {
    if (!TYPES.includes(type)) throw new Error(`Unknown scan type ${type}`);
    const { images = [], onProgress: _ignored, clientScanId, ...params } = payload;
    const id = clientScanId || `scan-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    await FileSystem.makeDirectoryAsync(`${DIR}${id}/`, { intermediates: true });
    const saved = [];
    for (let i = 0; i < images.length; i++) {
      const img = images[i];
      const name = img.fileName || `img_${i}.jpg`;
      const to = `${DIR}${id}/${i}-${name.replace(/[^\w.-]/g, '_')}`;
      await FileSystem.copyAsync({ from: img.uri, to });
      saved.push({ uri: to, fileName: name, mimeType: img.mimeType || 'image/jpeg' });
    }
    const items = (await read()).filter(it => it.id !== id);
    items.push({ id, type, params, images: saved, status: 'pending', createdAt: new Date().toISOString() });
    await write(items);
    return id;
  },

  /** Every saved scan, newest first; synced ones older than a week are dropped. */
  async list() {
    const items = await read();
    const now = Date.now();
    const kept = items.filter(it => !(it.status === 'synced' && now - Date.parse(it.syncedAt || it.createdAt) > KEEP_SYNCED_MS));
    if (kept.length !== items.length) await write(kept);
    return [...kept].sort((a, b) => Date.parse(b.createdAt) - Date.parse(a.createdAt));
  },

  /**
   * Sends the pending scans, oldest first. Stops at the first one that
   * still cannot reach MSAS (offline) or needs a new login; a scan the
   * server refuses is marked "failed" with its reason. One flush at a time.
   */
  flush() {
    if (flushing) return flushing;
    flushing = (async () => {
      let sent = 0;
      const pending = (await read()).filter(it => it.status === 'pending')
        .sort((a, b) => Date.parse(a.createdAt) - Date.parse(b.createdAt));
      for (const item of pending) {
        try {
          const res = await diagnoseAPI[item.type]({ ...item.params, images: item.images, clientScanId: item.id });
          await scanQueue.update(item.id, { status: 'synced', diagnosisId: res?.diagnosisId ?? null, syncedAt: new Date().toISOString() });
          await removeFiles(item.id);
          sent += 1;
        } catch (e) {
          if (isOfflineError(e) || e?.kind === 'auth' || e?.status === 401) break;
          await scanQueue.update(item.id, { status: 'failed', error: e?.message || String(e) });
        }
      }
      return sent;
    })().finally(() => { flushing = null; });
    return flushing;
  },

  async update(id, changes) {
    const items = await read();
    await write(items.map(it => (it.id === id ? { ...it, ...changes } : it)));
  },

  async remove(id) {
    await removeFiles(id);
    await write((await read()).filter(it => it.id !== id));
  },

  /** Called with the full list after every change; returns an unsubscribe function. */
  subscribe(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
  },
};

/**
 * For a scan screen's catch block: when `error` means no internet, saves the
 * scan on the phone, tells the farmer "Saved on phone – not yet synced" and
 * opens Records, and returns true. Otherwise (or if saving fails) returns
 * false and the screen shows its usual error.
 */
export async function saveScanOffline(error, type, payload, { t, router }) {
  if (!payload || !isOfflineError(error)) return false;
  try {
    await scanQueue.enqueue(type, payload);
  } catch {
    return false;
  }
  Alert.alert(t('scan_saved_offline_title'), t('scan_saved_offline_body'), [
    { text: 'OK', onPress: () => router.replace('/(tabs)/records') },
  ]);
  return true;
}
