// Web/APK parity: renders the canonical API response from
// shared-fixtures/canonical-diagnosis.json and checks every string the
// web history page and report are also tested to show
// (msas-system/tests/Feature/CanonicalDiagnosisParityTest.php).
import React from 'react';
import fs from 'fs';
import path from 'path';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { diagnoseAPI } from '../../../lib/api';
import DiagnosisDetailScreen from '../[id].jsx';

const fixture = JSON.parse(fs.readFileSync(path.join(__dirname, '../../../../shared-fixtures/canonical-diagnosis.json'), 'utf8'));

jest.mock('expo-router', () => ({ useRouter: () => ({ back: jest.fn(), push: jest.fn() }), useLocalSearchParams: () => ({ id: '1' }) }));
jest.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k) => k, i18n: { language: 'en' } }) }));
jest.mock('../../../lib/api', () => ({
  diagnoseAPI: { get: jest.fn(), feedback: jest.fn() },
  marketplaceAPI: { recommended: jest.fn(() => Promise.resolve({ data: [] })) },
  cartAPI: { add: jest.fn() },
}));
jest.mock('../../../context/LanguageContext', () => ({ useLanguage: () => ({ language: 'en', voiceEnabled: false }), LANGUAGES: [] }));
jest.mock('../../../services/VoiceService', () => ({ VoiceService: { speak: jest.fn(), stop: jest.fn() } }));

// Text nodes can hold several string pieces (e.g. "- {name}: {why}"), so join them.
const flatText = (c) => (Array.isArray(c) ? c.map(flatText).join('') : (typeof c === 'string' || typeof c === 'number') ? String(c) : '');
const allText = () => screen.UNSAFE_root.findAll(n => n.props && 'children' in n.props)
  .map(n => flatText(n.props.children)).filter(Boolean).join(' | ');

test('the app shows every canonical field the web shows, from the same API response', async () => {
  diagnoseAPI.get.mockResolvedValue({ diagnosis: { diagnosisId: 1, createdAt: '2026-10-13T10:00:00Z', ...fixture.api } });
  render(<DiagnosisDetailScreen />);
  await act(async () => {});

  // Organic options live on their own tab, as on the web they have their own card.
  const before = allText();
  fireEvent.press(screen.getByText('Organic'));
  const after = allText();
  const shown = `${before}\n${after}`;

  const missing = fixture.mustDisplay.filter(s => !shown.includes(s));
  expect(missing).toEqual([]);
  // No Latin names or jargon on the farmer's screen (staff see them under `technical`).
  const technical = (fixture.mustNotDisplay || []).filter(w => shown.toLowerCase().includes(w.toLowerCase()));
  expect(technical).toEqual([]);
});
