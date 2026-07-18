/**
 * Offline Queue — persists API requests to AsyncStorage and retries them
 * when connectivity is restored. Images cannot be queued (URIs are ephemeral).
 *
 * Usage:
 *   import { offlineQueue } from '../lib/offlineQueue';
 *   await offlineQueue.enqueue('farms', 'POST', body);
 *   await offlineQueue.flush();            // call on app foreground/reconnect
 */

import AsyncStorage from '@react-native-async-storage/async-storage';

const QUEUE_KEY = 'msas_offline_queue';
const MAX_RETRIES = 5;

let _baseUrl = null;
let _getToken = null;

export function initOfflineQueue(baseUrl, getTokenFn) {
  _baseUrl  = baseUrl;
  _getToken = getTokenFn;
}

async function readQueue() {
  try {
    const raw = await AsyncStorage.getItem(QUEUE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

async function writeQueue(items) {
  try {
    await AsyncStorage.setItem(QUEUE_KEY, JSON.stringify(items));
  } catch {}
}

/**
 * Enqueue a JSON-only request to be retried later.
 * @param {string} path  - e.g. '/farms'
 * @param {string} method - GET|POST|PATCH|PUT|DELETE
 * @param {object} body  - JSON-serialisable payload (no images)
 */
export const offlineQueue = {
  async enqueue(path, method, body = null) {
    const queue = await readQueue();
    const item = {
      id:        `oq-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
      path,
      method:    method.toUpperCase(),
      body,
      retries:   0,
      createdAt: new Date().toISOString(),
    };
    queue.push(item);
    await writeQueue(queue);
    return item.id;
  },

  async getPending() {
    return readQueue();
  },

  async count() {
    const q = await readQueue();
    return q.length;
  },

  /**
   * Attempt to send all queued items.
   * Items that succeed are removed; failed items have their retry count bumped
   * and are dropped after MAX_RETRIES.
   * @returns {{ sent: number, failed: number, remaining: number }}
   */
  async flush() {
    if (!_baseUrl || !_getToken) return { sent: 0, failed: 0, remaining: 0 };

    const queue = await readQueue();
    if (!queue.length) return { sent: 0, failed: 0, remaining: 0 };

    const token   = await _getToken();
    const headers = {
      'Content-Type': 'application/json',
      'Accept':       'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };

    let sent = 0, failed = 0;
    const remaining = [];

    for (const item of queue) {
      try {
        const res = await fetch(`${_baseUrl}${item.path}`, {
          method:  item.method,
          headers,
          body:    item.body ? JSON.stringify(item.body) : undefined,
          signal:  AbortSignal.timeout(12000),
        });
        if (res.ok) {
          sent++;
          continue; // don't keep in remaining
        }
        // 4xx errors (validation, auth) — no point retrying
        if (res.status >= 400 && res.status < 500) {
          failed++;
          continue;
        }
        throw new Error(`HTTP ${res.status}`);
      } catch {
        item.retries = (item.retries || 0) + 1;
        if (item.retries >= MAX_RETRIES) {
          failed++;
        } else {
          remaining.push(item);
        }
      }
    }

    await writeQueue(remaining);
    return { sent, failed, remaining: remaining.length };
  },

  async clear() {
    await AsyncStorage.removeItem(QUEUE_KEY);
  },
};
