// Phase 11: the result screen shows recoverable outcomes (retake photo,
// wrong subject, inconclusive) and never invents a 0% score or an
// "accepted" image badge.
import React from 'react';
import { render, screen, act } from '@testing-library/react-native';
import { diagnoseAPI } from '../../../lib/api';
import DiagnosisDetailScreen from '../[id].jsx';

jest.mock('expo-router', () => ({
  useRouter: () => ({ back: jest.fn(), push: jest.fn() }),
  useLocalSearchParams: () => ({ id: '7' }),
}));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({
  diagnoseAPI: { get: jest.fn(), feedback: jest.fn() },
  marketplaceAPI: { recommended: jest.fn(() => Promise.resolve({ data: [] })) },
  cartAPI: { add: jest.fn() },
}));
jest.mock('../../../context/LanguageContext', () => ({ useLanguage: () => ({ language: 'en', voiceEnabled: false }), LANGUAGES: [] }));
jest.mock('../../../services/VoiceService', () => ({ VoiceService: { speak: jest.fn(), stop: jest.fn() } }));

const base = {
  diagnosisId: 7, status: 'reviewed', type: 'crop', subjectName: null,
  aiResult: { primaryDiagnosis: 'Photo too unclear to assess', confidence: null, explanation: 'The image is blurred.' },
  treatmentPlan: { immediateActions: [], chemicalTreatments: [] },
  confidenceProvenance: {}, collectionLocation: null,
};
const show = async (diagnosis) => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis });
  render(<DiagnosisDetailScreen />);
  await act(async () => {});
};

beforeEach(() => jest.clearAllMocks());

test('poor image: retake screen with issues and tips, no treatment and no score', async () => {
  await show({ ...base, assessment: {
    outcome: 'poor_image', imageQuality: 'poor', imageIssues: ['blurred', 'too_far'],
    photoTips: ['Hold the camera steady'], alternativeFindings: [],
  } });
  expect(screen.getByTestId('outcome-title').props.children).toBe('Photo too unclear to assess');
  expect(screen.getByText('- The photo is blurred')).toBeTruthy();
  expect(screen.getByText('1. Hold the camera steady')).toBeTruthy();
  expect(screen.getByText('Retake photo')).toBeTruthy();
  expect(screen.queryByText(/% confidence/)).toBeNull();
  expect(screen.queryByText('treatment')).toBeNull();
});

test('wrong subject: says the photo does not show what the scan needs', async () => {
  await show({ ...base, assessment: { outcome: 'unsupported_subject', imageIssues: [], photoTips: ['Photograph the leaf'] } });
  expect(screen.getByTestId('outcome-title').props.children).toBe('This photo does not show what this scan needs');
  expect(screen.getByText('1. Photograph the leaf')).toBeTruthy();
});

test('inconclusive: shows the candidates and says no treatment until confirmed', async () => {
  await show({
    ...base,
    aiResult: { primaryDiagnosis: 'Possible late blight', confidence: 35, healthStatus: 'Uncertain' },
    assessment: {
      outcome: 'uncertain', imageQuality: 'acceptable', imageIssues: [], photoTips: ['Photograph the leaf underside'],
      alternativeFindings: [{ name: 'Early blight', why_considered: 'Similar brown lesions' }],
      whatCannotBeConfirmed: 'Spores are not visible',
    },
  });
  expect(screen.getByText('Inconclusive result')).toBeTruthy();
  expect(screen.getByText('Spores are not visible')).toBeTruthy();
  expect(screen.getByText('- Early blight: Similar brown lesions')).toBeTruthy();
  expect(screen.getByText('No treatment is recommended until the problem is confirmed.')).toBeTruthy();
  expect(screen.getByText('35% confidence')).toBeTruthy();
});

test('a missing score shows "No score", never 0%, and no invented "accepted" image badge', async () => {
  await show({ ...base, aiResult: { primaryDiagnosis: 'Late blight', confidence: null }, assessment: { outcome: null } });
  expect(screen.getByText('No score')).toBeTruthy();
  expect(screen.queryByText('0% confidence')).toBeNull();
  expect(screen.queryByText(/Image: accepted/)).toBeNull();
});

test('an empty treatment tab says nothing is recommended instead of rendering blank', async () => {
  await show({ ...base, aiResult: { primaryDiagnosis: 'Late blight', confidence: 78 }, assessment: { outcome: 'assessed', imageQuality: 'good' } });
  expect(screen.getByText('Nothing is recommended here for this result.')).toBeTruthy();
  expect(screen.getByText('Image: good')).toBeTruthy();
});
