/**
 * Beta Logger — structured in-app error/event log for test builds.
 * Stores up to MAX_ENTRIES recent entries in AsyncStorage.
 * In production this can be replaced by Sentry / Datadog / Expo error reporting.
 */

import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';

const LOG_KEY    = 'msas_beta_log';
const MAX_ENTRIES = 200;

const BUILD = {
  version:     Constants.expoConfig?.version || '1.1.0',
  buildDate:   new Date().toISOString().split('T')[0],
  env:         process.env.EXPO_PUBLIC_ENV || 'development',
  apiUrl:      process.env.EXPO_PUBLIC_API_URL || 'unknown',
};

async function readLog() {
  try {
    const raw = await AsyncStorage.getItem(LOG_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

async function append(entry) {
  try {
    const log = await readLog();
    log.unshift({ ...entry, build: BUILD, timestamp: new Date().toISOString() });
    if (log.length > MAX_ENTRIES) log.splice(MAX_ENTRIES);
    await AsyncStorage.setItem(LOG_KEY, JSON.stringify(log));
  } catch {}
}

export const betaLogger = {
  BUILD,

  /** Log an error with optional context object */
  async error(error, context = {}) {
    const entry = {
      level:   'error',
      message: error?.message || String(error),
      stack:   error?.stack?.substring(0, 500) || null,
      context,
    };
    await append(entry);
    if (__DEV__) console.error('[BetaLog ERROR]', entry);
  },

  /** Log a warning */
  async warn(message, context = {}) {
    const entry = { level: 'warn', message, context };
    await append(entry);
    if (__DEV__) console.warn('[BetaLog WARN]', entry);
  },

  /** Log an informational event (page views, scan starts, etc.) */
  async info(message, context = {}) {
    const entry = { level: 'info', message, context };
    await append(entry);
    if (__DEV__) console.log('[BetaLog INFO]', entry);
  },

  /** Log API errors (network, timeout, server errors) */
  async apiError(path, status, message) {
    await append({
      level:   'api_error',
      message: `${path} → ${status}: ${message}`,
      context: { path, status },
    });
  },

  /** Retrieve all log entries */
  async getLog() {
    return readLog();
  },

  /** Clear the log */
  async clear() {
    await AsyncStorage.removeItem(LOG_KEY);
  },

  /** Export log as a formatted string for sharing in bug reports */
  async exportText() {
    const log = await readLog();
    const lines = log.map(e =>
      `[${e.timestamp}] ${e.level.toUpperCase()}: ${e.message}${e.context && Object.keys(e.context).length ? '\n  Context: ' + JSON.stringify(e.context) : ''}`
    );
    return `MSAS FarmAI Beta Log\nVersion: ${BUILD.version} (${BUILD.buildDate})\nEnv: ${BUILD.env}\nAPI: ${BUILD.apiUrl}\n\n` + lines.join('\n\n');
  },
};

/**
 * Global error handler — catches unhandled JS exceptions.
 * Call once in app root (_layout.jsx).
 */
export function setupGlobalErrorHandler() {
  if (global.ErrorUtils) {
    const original = global.ErrorUtils.getGlobalHandler();
    global.ErrorUtils.setGlobalHandler(async (error, isFatal) => {
      await betaLogger.error(error, { isFatal });
      original(error, isFatal);
    });
  }
}
