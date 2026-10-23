// The location section shows the hook's state in the farmer's language:
// live accuracy, errors with what to do (Try again / Settings / Enter
// manually instead), the captured point (rating, coordinates, time, map),
// and the mocked-location warning. Real app strings (lib/i18n.js).
import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react-native';
import LocationCaptureSection from '../LocationCaptureSection';

let mockLang = 'en';
jest.mock('react-i18next', () => ({
  initReactI18next: { type: '3rdParty', init: () => {} },
  useTranslation: () => ({ t: (k) => require('../../lib/i18n').resources[mockLang].translation[k] ?? k, i18n: { language: mockLang } }),
}));

const base = () => ({
  locations: { states: [] }, lgasForState: [], state: '', lga: '', community: '', postalCode: '', address: '',
  coords: null, originalCoords: null, mode: null, gpsStatus: null, gpsLoading: false, gpsProgress: null, namesNote: null,
  quality: null, isMocked: null, locationSource: null, gpsCapturedAt: null, differsFromScan: false, confirmed: false,
  captureGps: jest.fn(), stopCapture: jest.fn(), adjustPoint: jest.fn(), enterManually: jest.fn(), openLocationSettings: jest.fn(),
  selectState: jest.fn(), selectLga: jest.fn(), setCommunity: jest.fn(), setPostalCode: jest.fn(), setAddress: jest.fn(),
  setDiffersFromScan: jest.fn(), setConfirmed: jest.fn(),
});
const text = () => screen.UNSAFE_root.findAll(n => typeof n.props?.children === 'string' || Array.isArray(n.props?.children))
  .map(n => [].concat(n.props.children).filter(c => typeof c === 'string' || typeof c === 'number').join('')).join(' | ');

afterEach(() => { mockLang = 'en'; });

test('live accuracy while watching, with "Use this reading now"', () => {
  const loc = { ...base(), gpsLoading: true, gpsProgress: { first: 18, best: 7 } };
  render(<LocationCaptureSection loc={loc} />);
  expect(text()).toContain('Improving accuracy… ±18 m → ±7 m');
  fireEvent.press(screen.getByTestId('loc-use-now'));
  expect(loc.stopCapture).toHaveBeenCalled();
});

test('a permission error says how to allow it and offers Try again, Settings and manual entry', () => {
  const loc = { ...base(), mode: 'gps', gpsStatus: { type: 'error', key: 'loc_err_denied_forever', help: ['loc_help_settings_app', 'loc_help_precise'], canOpenSettings: true, canRetry: true, canManual: true } };
  render(<LocationCaptureSection loc={loc} />);
  const all = text();
  expect(all).toContain('Location access is turned off for MSAS FarmAI.');
  expect(all).toContain('Open phone Settings → Apps → MSAS FarmAI → Location → Allow.');
  expect(all).toContain('Turn on "Precise location" for MSAS FarmAI.');
  fireEvent.press(screen.getByTestId('loc-manual-instead'));
  expect(loc.enterManually).toHaveBeenCalled();
  fireEvent.press(screen.getByTestId('loc-open-settings'));
  expect(loc.openLocationSettings).toHaveBeenCalled();
});

test('the captured point: reported accuracy, rating, coordinates, time and the map', () => {
  const loc = { ...base(), mode: 'gps', quality: 'fair', coords: { latitude: 11.5230123, longitude: 7.3120456, accuracy: 22 },
    gpsCapturedAt: '2026-10-23T07:00:00.000Z',
    gpsStatus: { type: 'warning', key: 'loc_captured', params: { acc: 22, rating: 'fair' }, bestIn30: true, help: ['loc_help_outside'], canRetry: true } };
  render(<LocationCaptureSection loc={loc} />);
  const all = text();
  expect(all).toContain('Location captured: ±22 m (Fair). This is the best reading in 30 seconds.');
  expect(screen.getByTestId('loc-rating').props.children).toBe('Fair');
  expect(all).toContain('11.523012');
  expect(all).toContain('7.312046');
  expect(screen.getByTestId('loc-map')).toBeTruthy();
  fireEvent.press(screen.getByTestId('loc-try-again'));
  expect(loc.captureGps).toHaveBeenCalled();
});

test('a mocked location is shown as such', () => {
  render(<LocationCaptureSection loc={{ ...base(), isMocked: true, quality: 'excellent', coords: { latitude: 11.5, longitude: 7.3, accuracy: 4 } }} />);
  expect(screen.getByTestId('loc-mocked')).toBeTruthy();
});

test('Hausa', () => {
  mockLang = 'ha';
  render(<LocationCaptureSection loc={{ ...base(), gpsLoading: true, gpsProgress: { first: 18, best: 7 } }} />);
  expect(text()).toContain('Ana inganta daidaito… ±18 m → ±7 m');
  expect(text()).toContain('Shigar da hannu'); // "Enter by hand" (the GPS button shows a spinner while capturing)
});
