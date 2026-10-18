// Phase 13: the voices are credited in the app. OpenBible (Hausa) is
// CC BY-SA 4.0, which requires attribution, so its credit is built in and
// shown even when the server cannot be reached.
import React from 'react';
import { Linking } from 'react-native';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import { speechAPI } from '../../../lib/api';
import CreditsScreen, { mergeCredits } from '../credits.jsx';

jest.mock('../../../lib/api', () => ({ speechAPI: { languages: jest.fn() } }));
jest.mock('../../../context/LanguageContext', () => ({ useLanguage: () => ({ language: 'en' }) }));

const flat = (c) => (Array.isArray(c) ? c.map(flat).join('') : (typeof c === 'string' || typeof c === 'number') ? String(c) : '');
const textOf = (id) => screen.getByTestId(id).findAll((n) => n.props && 'children' in n.props)
  .map((n) => flat(n.props.children)).filter(Boolean).join(' | ');

test('OpenBible CC BY-SA 4.0 and Kokoro Apache-2.0 are credited even offline', async () => {
  speechAPI.languages.mockRejectedValue(new Error('offline'));
  render(<CreditsScreen />);
  await act(async () => {});
  const ob = textOf('credit-openbible');
  expect(ob).toContain('OpenBibleTTS (McGill-NLP, Mila)');
  expect(ob).toContain('CC BY-SA 4.0');
  expect(ob).toContain('https://creativecommons.org/licenses/by-sa/4.0/');
  expect(textOf('credit-kokoro')).toContain('Apache-2.0');
});

test('other providers the server reports are added once, built-in ones are not duplicated', async () => {
  speechAPI.languages.mockResolvedValue({ credits: [
    { provider: 'openbible', label: 'OpenBible VITS (self-hosted)', license: 'CC-BY-SA-4.0' },
    { provider: 'elevenlabs', label: 'ElevenLabs (cloud)', license: 'Commercial API (paid)' },
  ] });
  render(<CreditsScreen />);
  await act(async () => {});
  expect(screen.getAllByTestId('credit-openbible')).toHaveLength(1);
  expect(textOf('credit-elevenlabs')).toContain('ElevenLabs');
  expect(mergeCredits([{ provider: 'kokoro' }]).filter((c) => c.provider === 'kokoro')).toHaveLength(1);
});

test('licence links open', async () => {
  speechAPI.languages.mockResolvedValue({ credits: [] });
  jest.spyOn(Linking, 'openURL').mockResolvedValue(true);
  render(<CreditsScreen />);
  await act(async () => {});
  fireEvent.press(screen.getByText('https://creativecommons.org/licenses/by-sa/4.0/'));
  expect(Linking.openURL).toHaveBeenCalledWith('https://creativecommons.org/licenses/by-sa/4.0/');
});
