// Phase 12.2: one severity value. The header badge said "HIGH" (it showed the
// urgency) while the details said "Moderate" for the same scan. Renders the
// API response in shared-fixtures/severity-diagnosis.json (the Laravel suite
// checks the API returns exactly that) and fails if any two places disagree.
import React from 'react';
import fs from 'fs';
import path from 'path';
import { render, screen, act } from '@testing-library/react-native';
import { diagnoseAPI } from '../../../lib/api';
import DiagnosisDetailScreen from '../[id].jsx';

const fixture = JSON.parse(fs.readFileSync(path.join(__dirname, '../../../../shared-fixtures/severity-diagnosis.json'), 'utf8'));

jest.mock('../../../components/ListenPanel', () => () => null);
jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), push: jest.fn() }), useLocalSearchParams: () => ({ id: '1' }) }));
jest.mock('react-i18next', () => ({
  initReactI18next: { type: '3rdParty', init: () => {} },
  useTranslation: () => ({ t: (k) => require('../../../lib/i18n').resources.en.translation[k] ?? k, i18n: { language: 'en' } }),
}));
jest.mock('../../../lib/api', () => ({
  diagnoseAPI: { get: jest.fn(), feedback: jest.fn() },
  marketplaceAPI: { recommended: jest.fn(() => Promise.resolve({ data: [] })) },
  cartAPI: { add: jest.fn() },
}));
jest.mock('../../../context/LanguageContext', () => ({ useLanguage: () => ({ language: 'en', voiceEnabled: false }), LANGUAGES: [] }));
jest.mock('../../../services/VoiceService', () => ({ VoiceService: { speak: jest.fn(), stop: jest.fn() } }));

const flatText = (c) => (Array.isArray(c) ? c.map(flatText).join('') : (typeof c === 'string' || typeof c === 'number') ? String(c) : '');
const textOf = (id) => screen.getByTestId(id).findAll(n => n.props && 'children' in n.props).map(n => flatText(n.props.children)).join(' ');
const severityWord = (s) => (s.match(/\b(none|mild|moderate|severe|critical)\b/i) || [])[1]?.toLowerCase();

const show = async (diagnosis) => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis: { diagnosisId: 1, createdAt: '2026-10-18T10:00:00Z', ...diagnosis } });
  render(<DiagnosisDetailScreen />);
  await act(async () => {});
};

test('header badge and details show the same severity; urgency is separate and labelled', async () => {
  await show(fixture.api);
  const header = textOf('header-severity');
  const details = flatText(screen.getByTestId('details-severity').props.children);
  expect(severityWord(header)).toBe('moderate');
  expect(severityWord(details)).toBe('moderate');
  expect(header).toContain('Severity');
  expect(header).not.toMatch(/HIGH/);
  const urgency = textOf('header-urgency');
  expect(urgency).toContain('Urgency');
  expect(urgency).toContain('HIGH');
});

test('an older server (severity carried the urgency) still shows the real severity in the header', async () => {
  const { urgency, urgencyLevel, ...old } = fixture.api.aiResult;
  await show({ ...fixture.api, aiResult: { ...old, severity: 'high' } });
  expect(severityWord(textOf('header-severity'))).toBe('moderate');
  expect(textOf('header-urgency')).toContain('HIGH');
});

test('the buffer zone says Likely and asks to confirm', async () => {
  await show({ ...fixture.api, aiResult: { ...fixture.api.aiResult, confidence: 70 },
    assessment: { ...fixture.api.assessment, needsConfirmation: true } });
  expect(flatText(screen.getByTestId('diagnosis-name').props.children).trim()).toBe('Likely: Late blight');
  expect(textOf('likely-confirm-card')).toContain('Please confirm with a vet or extension officer before treating.');
});

test('no Likely wording on a confident result', async () => {
  await show(fixture.api);
  expect(flatText(screen.getByTestId('diagnosis-name').props.children).trim()).toBe('Late blight');
  expect(screen.queryByTestId('likely-confirm-card')).toBeNull();
});
