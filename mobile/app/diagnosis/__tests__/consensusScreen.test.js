// The consensus result screen, from a real banana scan where it contradicted
// itself. Renders the API responses in shared-fixtures/banana-consensus-diagnosis.json
// (the web side, ConsensusResultScreenTest.php, checks the same fixture) with
// the app's real English strings.
import React from 'react';
import fs from 'fs';
import path from 'path';
import { render, screen, act, fireEvent } from '@testing-library/react-native';
import { diagnoseAPI } from '../../../lib/api';
import DiagnosisDetailScreen, { InfoField } from '../[id].jsx';

const fixture = JSON.parse(fs.readFileSync(path.join(__dirname, '../../../../shared-fixtures/banana-consensus-diagnosis.json'), 'utf8'));
const mockPush = jest.fn();
let mockLang = 'en';

jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), push: mockPush }), useLocalSearchParams: () => ({ id: '1' }) }));
jest.mock('react-i18next', () => ({
  initReactI18next: { type: '3rdParty', init: () => {} },
  useTranslation: () => ({ t: (k) => require('../../../lib/i18n').resources[mockLang].translation[k] ?? k, i18n: { language: mockLang } }),
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

const BASE = { diagnosisId: 1, status: 'reviewed', type: 'crop', createdAt: '2026-10-19T10:00:00Z',
  treatmentPlan: { immediateActions: [], chemicalTreatments: [] }, confidenceProvenance: {}, collectionLocation: null };
const show = async (api) => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis: { ...BASE, ...api } });
  render(<DiagnosisDetailScreen />);
  await act(async () => {});
};

beforeEach(() => { jest.clearAllMocks(); mockLang = 'en'; });

describe('inconclusive (the scan as the old wording vote stored it)', () => {
  const { api } = fixture.inconclusive;
  const mustDisplay = [...fixture.inconclusive.mustDisplay, ...(fixture.inconclusive.mustDisplayOnScreen || [])];

  test('headline is never a candidate; no severity or urgency; health "Needs checking"', async () => {
    await show(api);
    expect(flatText(screen.getByTestId('diagnosis-name').props.children)).toBe(fixture.headline);
    expect(screen.queryByTestId('header-severity')).toBeNull();
    expect(screen.queryByTestId('header-urgency')).toBeNull();
    expect(flatText(screen.getByTestId('details-health').props.children)).toBe('Needs checking');
    expect(flatText(screen.getByTestId('details-severity').props.children)).toBe('Not determined');
  });

  test('at most three plain-language causes, 2-4 steps, retake button with tips; no jargon', async () => {
    await show(api);
    const causes = screen.getAllByTestId('possible-condition').map(n => flatText(n.props.children));
    expect(causes).toEqual(['1. Natural leaf sheath ageing', '2. Natural drying and ageing of old leaf sheaths', '3. Natural drying and shedding of old outer leaf sheaths']);
    const steps = screen.getAllByTestId('what-to-do-step');
    expect(steps.length).toBeGreaterThanOrEqual(2);
    expect(steps.length).toBeLessThanOrEqual(4);
    expect(flatText(steps[0].props.children)).toBe('1. Look closely for soft, rotting or bad-smelling parts.');
    fireEvent.press(screen.getByTestId('retake-button'));
    expect(mockPush).toHaveBeenCalledWith('/(tabs)/scan');

    const shown = allText();
    expect(mustDisplay.filter(s => !shown.includes(s))).toEqual([]);
    expect(fixture.jargon.filter(w => shown.toLowerCase().includes(w.toLowerCase()))).toEqual([]);
    expect(screen.queryByTestId('technical-details')).toBeNull();
  });

  test('steps, tips and headline are translated from their keys (Hausa)', async () => {
    mockLang = 'ha';
    await show(api);
    const ha = require('../../../lib/i18n').resources.ha.translation;
    expect(flatText(screen.getByTestId('diagnosis-name').props.children)).toBe(ha.inconclusiveHeadline);
    expect(flatText(screen.getAllByTestId('what-to-do-step')[0].props.children)).toBe(`1. ${ha.check_rot}`);
    expect(allText()).toContain(ha.tip_fill_frame);
  });

  test('staff see the technical names in their own card', async () => {
    await show({ ...api, scientificName: 'Musa spp.', technical: {
      technical_name: 'Natural leaf sheath senescence', scientific_name: 'Musa spp.', detected_part: 'stem (pseudostem)',
      pass_findings: fixture.inconclusive.record.ai_consensus.pass_findings, pass_labels: [], pass_confidences: [72, 68, 75] } });
    const card = allText();
    expect(screen.getByTestId('technical-details')).toBeTruthy();
    expect(card).toContain('Musa spp.');
    expect(card).toContain('stem (pseudostem)');
    expect(card).toContain('Natural leaf sheath senescence 72%');
  });
});

describe('normal ageing (the same passes under the condition-label vote)', () => {
  const { api, mustDisplay, mustNotDisplay } = fixture.normalAgeing;

  test('one finding, health never says unhealthy, severity None, no urgency, no retake', async () => {
    await show(api);
    expect(flatText(screen.getByTestId('diagnosis-name').props.children)).toBe('Old outer layers drying naturally');
    expect(flatText(screen.getByTestId('details-health').props.children)).toBe('Healthy (normal ageing)');
    expect(screen.getByTestId('header-severity')).toBeTruthy();
    expect(allText()).toContain('Severity: NONE');
    expect(screen.queryByTestId('header-urgency')).toBeNull();
    expect(screen.queryByTestId('inconclusive-card')).toBeNull();
    expect(screen.queryByTestId('retake-button')).toBeNull();
    const shown = allText();
    expect(mustDisplay.filter(s => !shown.includes(s))).toEqual([]);
    expect(mustNotDisplay.filter(w => shown.includes(w))).toEqual([]);
  });
});

// "DETECTED PART" ran into its value on a narrow screen. In the app the label sits
// above the value and both may shrink and wrap; the snapshot pins that layout.
describe('label/value layout', () => {
  test('InfoField stacks the label above a wrapping value', () => {
    const tree = render(<InfoField label="Plant/Body Part" value="stem (pseudostem) with a very long description that has to wrap" />).toJSON();
    expect(tree).toMatchSnapshot();
    expect(tree.props.style).toMatchObject({ flexDirection: 'column' });
    const [label, value] = tree.children;
    expect(label.props.style).toMatchObject({ flexShrink: 1 });
    expect(value.props.style).toMatchObject({ flexShrink: 1 });
  });

  test('the diagnostic details card of the banana scan', async () => {
    await show(fixture.inconclusive.api);
    const details = ['details-part', 'details-health', 'details-severity'].map(id => flatText(screen.getByTestId(id).props.children));
    expect(details).toEqual(['stem', 'Needs checking', 'Not determined']);
    const card = screen.getByTestId('inconclusive-card');
    expect(card.findAll(n => n.type === 'Text').map(n => flatText(n.props.children))).toMatchSnapshot();
  });
});

describe('disclaimer, ask an expert, advice', () => {
  test('the disclaimer is farmer-friendly and the button opens the request page with this scan', async () => {
    const { Linking } = require('react-native');
    const open = jest.spyOn(Linking, 'openURL').mockResolvedValue(true);
    await show({ ...fixture.inconclusive.api, askExpertUrl: 'https://www.msasagro.com/diagnostics/1/ask-expert' });
    // A crop scan: an agronomist or extension officer, never a vet.
    expect(allText()).toContain('This is AI advice and can be wrong. If unsure, ask an agronomist or extension officer.');
    expect(allText()).toContain('Request Agronomist / Ask an expert');
    expect(allText()).toContain('Please confirm with an agronomist or extension officer before treating.');
    expect(allText()).not.toMatch(/\bvet\b/i);
    fireEvent.press(screen.getByTestId('ask-expert'));
    expect(open).toHaveBeenCalledWith('https://www.msasagro.com/diagnostics/1/ask-expert');
    open.mockRestore();
  });

  test('a livestock scan names a vet', async () => {
    const api = fixture.inconclusive.api;
    await show({ ...api, type: 'livestock', askExpertUrl: 'https://www.msasagro.com/diagnostics/2/ask-expert',
      assessment: { ...api.assessment, result: { ...api.assessment.result, expert: { key: 'vet' } } } });
    expect(allText()).toContain('This is AI advice and can be wrong. If unsure, ask an extension officer or vet.');
    expect(allText()).toContain('Request Vet / Ask an expert');
    expect(allText()).toContain('Please confirm with a vet or extension officer before treating.');
  });

  test('the explanation of an inconclusive result is made from the listed causes', async () => {
    const api = fixture.inconclusive.api;
    const causes = api.assessment.result.possible_causes;
    await show({ ...api, aiResult: { ...api.aiResult, explanation: 'Algal leaf spot or anthracnose (Colletotrichum).' },
      assessment: { ...api.assessment, result: { ...api.assessment.result, explanation: { key: 'explain_causes', text: 'x', causes } } } });
    const explanation = flatText(screen.getByTestId('details-explanation').props.children);
    expect(explanation).toBe(`The signs in this photo could be one of these: ${causes.join(', ')}. The AI cannot tell which one from this photo.`);
    expect(allText()).not.toMatch(/anthracnose|algal/i);
  });

  test('no button when the server sends no request page (failed scan or older server)', async () => {
    await show(fixture.inconclusive.api);
    expect(screen.queryByTestId('ask-expert')).toBeNull();
  });

  test('"Likely Causes" only for a confident result, even if an older server sends it', async () => {
    const cause = ['Old outer sheaths drying'];
    await show({ ...fixture.inconclusive.api, aiResult: { ...fixture.inconclusive.api.aiResult, likelyCauses: cause } });
    expect(screen.queryByTestId('likely-causes')).toBeNull();
    screen.unmount();

    const likely = fixture.normalAgeing.api;
    await show({ ...likely, aiResult: { ...likely.aiResult, likelyCauses: cause }, assessment: { ...likely.assessment, result: { ...likely.assessment.result, kind: 'likely' } } });
    expect(screen.queryByTestId('likely-causes')).toBeNull();
    screen.unmount();

    await show({ ...likely, aiResult: { ...likely.aiResult, likelyCauses: cause } });
    expect(screen.getByTestId('likely-causes')).toBeTruthy();
  });

  test('a healthy result labels its advice "Advice", not "Recovery Period"', async () => {
    await show({ ...fixture.normalAgeing.api, treatmentPlan: { ...BASE.treatmentPlan, recoveryPeriod: 'No recovery needed.' } });
    expect(allText()).toContain('Advice');
    expect(allText()).not.toContain('Recovery Period');
  });

  test('inconclusive: the confidence says why it is low, never "No score"', async () => {
    await show(fixture.inconclusive.api);
    expect(flatText(screen.getByTestId('header-confidence').props.children)).toBe('Low – the 3 analyses did not agree');
    expect(flatText(screen.getByTestId('confidence-line').props.children)).toBe('Confidence: Low – the 3 analyses did not agree');
    expect(allText()).not.toContain('No score');
  });
});
