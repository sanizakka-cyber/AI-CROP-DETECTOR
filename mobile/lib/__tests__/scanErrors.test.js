// Scan error codes, app side: every code gets a plain message in the app's
// language, the reference, the right next step, and technical details only
// when the server sent them (staff roles). Never raw text for engine/provider codes.
import { Alert, Share } from 'react-native';

jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));
import { ApiError } from '../api';
import { formatScanError, showScanError, technicalText, SCAN_ERROR_TEXT } from '../scanErrors';

const CODES = {
  SCAN_QUOTA_EXCEEDED: 'upgrade', SESSION_EXPIRED: 'sign_in', UPLOAD_TOO_LARGE: 'choose_photo', UPLOAD_BAD_FORMAT: 'choose_photo',
  UPLOAD_INTERRUPTED: 'retry', VALIDATION_FAILED: 'fix_fields', TOO_MANY_SCANS: 'wait', ENGINE_UNREACHABLE: 'retry',
  ENGINE_AUTH_FAILED: 'retry', AI_PROVIDER_AUTH_FAILED: 'retry', AI_PROVIDER_NO_CREDIT: 'retry', AI_PROVIDER_RATE_LIMIT: 'retry',
  AI_PROVIDER_ERROR: 'retry', INTERNAL_ERROR: 'retry',
};
const SERVER_MESSAGE = 'Server-worded message for this code.';
const err = (code, extra = {}) => new ApiError(SERVER_MESSAGE, { code, reference: 'SCN-7F3K2', action: CODES[code], ...extra });

beforeEach(() => { jest.clearAllMocks(); jest.spyOn(Alert, 'alert').mockImplementation(() => {}); });

describe.each(Object.keys(CODES))('%s', (code) => {
  test('farmer sees a plain message, the reference and the next step, no technical details', () => {
    const handlers = { onRetry: jest.fn(), onSignIn: jest.fn(), onUpgrade: jest.fn(), onChoosePhoto: jest.fn() };
    showScanError(err(code), { lang: 'en', ...handlers });
    const [title, body, buttons] = Alert.alert.mock.calls[0];
    expect(title).toBe('Scan not completed');
    expect(body).toContain('Reference: SCN-7F3K2');
    const serverWorded = code === 'SCAN_QUOTA_EXCEEDED' || code === 'VALIDATION_FAILED';
    expect(body.startsWith(serverWorded ? SERVER_MESSAGE : SCAN_ERROR_TEXT.en.codes[code])).toBe(true);
    if (code.startsWith('ENGINE_') || code.startsWith('AI_PROVIDER_')) {
      expect(body).toContain('Your scan was NOT counted');
    }
    const labels = buttons.map((b) => b.text);
    expect(labels).not.toContain('Technical details');
    const expected = { retry: 'Try again', sign_in: 'Sign in', upgrade: 'Upgrade plan', choose_photo: 'Choose another photo' }[CODES[code]];
    if (expected) {
      expect(labels).toContain(expected);
      buttons.find((b) => b.text === expected).onPress();
      const called = { retry: handlers.onRetry, sign_in: handlers.onSignIn, upgrade: handlers.onUpgrade, choose_photo: handlers.onChoosePhoto }[CODES[code]];
      expect(called).toHaveBeenCalled();
    }
    expect(labels[labels.length - 1]).toBe('Close');
  });

  test('Hausa users get it in Hausa', () => {
    const { message, referenceLine } = formatScanError(err(code), 'ha');
    expect(referenceLine).toBe('Lambar shaida: SCN-7F3K2');
    if (code !== 'SCAN_QUOTA_EXCEEDED' && code !== 'VALIDATION_FAILED') {
      expect(message).toBe(SCAN_ERROR_TEXT.ha.codes[code]);
    }
  });
});

test('engine and provider codes never show the raw server text', () => {
  const raw = new ApiError('Claude API error: invalid x-api-key sk-ant-...', { code: 'AI_PROVIDER_AUTH_FAILED', reference: 'SCN-AAAAA', action: 'retry' });
  expect(formatScanError(raw, 'en').message).toBe(SCAN_ERROR_TEXT.en.codes.AI_PROVIDER_AUTH_FAILED);
});

test('staff get technical details they can copy or share', () => {
  jest.spyOn(Share, 'share').mockResolvedValue({});
  const technical = { code: 'AI_PROVIDER_NO_CREDIT', http_status: 503, service: 'claude_api', service_label: 'Claude API (Anthropic)',
    message: 'Claude API error (BadRequestError, HTTP 400): credit balance too low', timestamp: '2026-10-19T08:00:00Z', reference: 'SCN-7F3K2' };
  showScanError(err('AI_PROVIDER_NO_CREDIT', { technical }), { lang: 'en', onRetry: jest.fn() });
  const buttons = Alert.alert.mock.calls[0][2];
  buttons.find((b) => b.text === 'Technical details').onPress();
  const [title, details, inner] = Alert.alert.mock.calls[1];
  expect(title).toBe('Technical details');
  for (const part of ['SCN-7F3K2', 'AI_PROVIDER_NO_CREDIT', '503', 'Claude API (Anthropic)', 'credit balance too low', '2026-10-19T08:00:00Z']) {
    expect(details).toContain(part);
  }
  inner.find((b) => b.text === 'Copy / share details').onPress();
  expect(Share.share).toHaveBeenCalledWith({ message: technicalText(err('AI_PROVIDER_NO_CREDIT', { technical })) });
});

test('a lost connection (no server answer) is UPLOAD_INTERRUPTED with a retry and no reference', () => {
  const e = new ApiError('MSAS could not be reached.', { kind: 'network', code: 'UPLOAD_INTERRUPTED', action: 'retry' });
  const { message, referenceLine } = formatScanError(e, 'en');
  expect(message).toBe(SCAN_ERROR_TEXT.en.codes.UPLOAD_INTERRUPTED);
  expect(referenceLine).toBeNull();
});

test('every language has every message the app words itself', () => {
  for (const lang of ['en', 'ha', 'fr', 'yo', 'ig']) {
    for (const code of Object.keys(CODES)) {
      if (code === 'SCAN_QUOTA_EXCEEDED' || code === 'VALIDATION_FAILED') continue;
      expect(SCAN_ERROR_TEXT[lang].codes[code]).toBeTruthy();
    }
  }
  // Fulfulde falls back to English until a native translation exists.
  expect(formatScanError(err('ENGINE_UNREACHABLE'), 'ff').message).toBe(SCAN_ERROR_TEXT.en.codes.ENGINE_UNREACHABLE);
});
