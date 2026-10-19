// Phase 12.1 web/APK parity for the low-confidence screen: renders the API
// response in shared-fixtures/low-confidence-diagnosis.json with the app's
// real English strings and checks every string the web history page and PDF
// report are tested to show (CanonicalDiagnosisParityTest.php). Also: the AI
// disclaimer is on every result screen.
import React from 'react';
import fs from 'fs';
import path from 'path';
import { render, screen, act } from '@testing-library/react-native';
import { diagnoseAPI } from '../../../lib/api';
import DiagnosisDetailScreen from '../[id].jsx';

const fixture = JSON.parse(fs.readFileSync(path.join(__dirname, '../../../../shared-fixtures/low-confidence-diagnosis.json'), 'utf8'));

jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), push: jest.fn() }), useLocalSearchParams: () => ({ id: '1' }) }));
// Real English strings from lib/i18n.js, so the test sees what a farmer sees.
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
const allText = () => screen.UNSAFE_root.findAll(n => n.props && 'children' in n.props)
  .map(n => flatText(n.props.children)).filter(Boolean).join(' | ');

const show = async (diagnosis) => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis: { diagnosisId: 1, createdAt: '2026-10-16T10:00:00Z', ...diagnosis } });
  render(<DiagnosisDetailScreen />);
  await act(async () => {});
};

test('the app shows the low-confidence result exactly as the web does', async () => {
  await show(fixture.api);
  const shown = allText();
  const missing = fixture.mustDisplay.filter(s => !shown.includes(s));
  expect(missing).toEqual([]);
  // Never presented as one diagnosis.
  expect(flatText(screen.getByTestId('diagnosis-name').props.children).trim()).toBe(fixture.headline);
  expect(screen.getAllByTestId('possible-condition')).toHaveLength(3);
  expect(screen.queryByText('Other possibilities considered')).toBeNull();
});

test('an older server without notCertain still gets the rule (confidence below 65)', async () => {
  const { notCertain, possibleConditions, minConfidence, ...oldAssessment } = fixture.api.assessment;
  await show({ ...fixture.api, assessment: oldAssessment });
  expect(flatText(screen.getByTestId('diagnosis-name').props.children).trim()).toBe('Not certain');
  expect(screen.getAllByTestId('possible-condition').map(n => flatText(n.props.children))).toEqual(
    ['• Ringworm (Trichophyton verrucosum)', '• Mange', '• Lumpy skin disease']);
});

test('at or above the threshold one diagnosis is shown', async () => {
  await show({ ...fixture.api, aiResult: { ...fixture.api.aiResult, confidence: 80 },
    assessment: { ...fixture.api.assessment, notCertain: false, possibleConditions: [] } });
  expect(flatText(screen.getByTestId('diagnosis-name').props.children).trim()).toBe('Ringworm (Trichophyton verrucosum)');
  expect(screen.queryByTestId('not-certain-card')).toBeNull();
});

const DISCLAIMER = 'AI guidance can be wrong; confirm with an expert.';
test.each([
  ['assessed', { aiResult: { ...fixture.api.aiResult, confidence: 80 }, assessment: { ...fixture.api.assessment, notCertain: false } }],
  ['not certain', {}],
  ['rejected photo', { assessment: { outcome: 'poor_image', imageIssues: ['blurred'], photoTips: ['Use daylight'] } }],
  ['wrong subject', { assessment: { outcome: 'unsupported_subject', imageIssues: [], photoTips: [] } }],
  ['inconclusive', { assessment: { outcome: 'uncertain', alternativeFindings: [], photoTips: [] } }],
  ['AI unavailable', { status: 'needs_review' }],
])('the AI disclaimer is on the %s screen', async (_name, over) => {
  await show({ ...fixture.api, ...over });
  expect(flatText(screen.getByTestId('ai-disclaimer').props.children)).toContain(DISCLAIMER);
});
