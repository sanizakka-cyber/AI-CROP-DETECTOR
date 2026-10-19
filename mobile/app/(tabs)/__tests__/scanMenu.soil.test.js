// Phase 12.1: the soil scan card never promises pH from a photo, in any
// language the app ships; and every language has the low-confidence and
// disclaimer strings.
import React from 'react';
import { render, screen } from '@testing-library/react-native';
import { resources } from '../../../lib/i18n';
import ScanMenuScreen from '../scan.jsx';

let mockLang = 'en';
jest.mock('react-i18next', () => ({
  initReactI18next: { type: '3rdParty', init: () => {} },
  useTranslation: () => ({
    t: (k) => require('../../../lib/i18n').resources[mockLang].translation[k] ?? k,
    i18n: { language: mockLang },
  }),
}));
jest.mock('expo-router', () => ({ useRouter: () => ({ push: jest.fn(), back: jest.fn() }) }));

const LANGS = Object.keys(resources);
const NEW_KEYS = ['soilScanDesc', 'notCertain', 'notCertainBody', 'possibleConditions', 'confirmWithExpert', 'aiDisclaimer'];

test('the app ships the six expected languages', () => {
  expect(LANGS.sort()).toEqual(['en', 'ff', 'fr', 'ha', 'ig', 'yo']);
});

test.each(LANGS)('%s has every low-confidence, disclaimer and soil string', (lang) => {
  for (const key of NEW_KEYS) {
    expect(resources[lang].translation[key]).toEqual(expect.stringMatching(/\S/));
  }
});

test.each(LANGS)('the %s soil card shows its own text and never "pH"', (lang) => {
  mockLang = lang;
  render(<ScanMenuScreen />);
  const desc = screen.getByTestId('scan-desc-soil').props.children;
  expect(desc).toBe(resources[lang].translation.soilScanDesc);
  expect(desc).not.toMatch(/\bpH\b/);
});

test('no soil-card string in any language mentions pH', () => {
  for (const lang of LANGS) expect(resources[lang].translation.soilScanDesc).not.toMatch(/\bpH\b/i);
});
