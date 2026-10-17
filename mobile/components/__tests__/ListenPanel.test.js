// "Listen to your result" on the phone: language picker, no auto-play,
// translate -> audio polling, sentence highlighting, speed, offline download,
// text-only fallback, feedback, and playing a saved copy without a connection.
import React from 'react';
import { render, screen, fireEvent, act } from '@testing-library/react-native';
import ListenPanel, { sentenceAt, sectionsFrom, formatSize } from '../ListenPanel';
import { speechAPI } from '../../lib/api';
import { VoiceService } from '../../services/VoiceService';
import { File } from 'expo-file-system';

jest.mock('../../lib/api', () => ({
  speechAPI: {
    languages: jest.fn(), translate: jest.fn(), translation: jest.fn(),
    requestAudio: jest.fn(), audio: jest.fn(), feedback: jest.fn(() => Promise.resolve({})), review: jest.fn(),
  },
}));
jest.mock('../../services/VoiceService', () => ({ VoiceService: { speak: jest.fn(), stop: jest.fn() } }));

const mockPlayer = { replace: jest.fn(), play: jest.fn(), pause: jest.fn(), seekTo: jest.fn(), setPlaybackRate: jest.fn() };
let mockStatus;
jest.mock('expo-audio', () => ({
  useAudioPlayer: () => mockPlayer,
  useAudioPlayerStatus: () => mockStatus,
}));

const mockFiles = {}; // uri -> { exists, text }
jest.mock('expo-file-system', () => {
  class Directory { constructor(...p) { this.uri = p.map((x) => x.uri || x).join('/'); } create() {} }
  class File {
    constructor(...p) { this.uri = p.map((x) => x.uri || x).join('/'); }
    get exists() { return !!mockFiles[this.uri]?.exists; }
    delete() { delete mockFiles[this.uri]; }
    write(t) { mockFiles[this.uri] = { exists: true, text: t }; }
    textSync() { return mockFiles[this.uri].text; }
    static downloadFileAsync = jest.fn(async (url, dest) => { mockFiles[dest.uri] = { exists: true, text: 'mp3' }; return dest; });
  }
  return { Directory, File, Paths: { document: 'file:///docs' } };
});

const LANGS = {
  languages: [
    { code: 'en', name: 'English', hasVoice: true },
    { code: 'ha', name: 'Hausa', hasVoice: true },
    { code: 'pcm', name: 'Naijá', hasVoice: false },
  ],
  default: 'ha', preferred: 'ha',
};
const CONTENT = {
  observations: ['Ganyen sun yi ruwan ƙasa.'],
  explanation: 'Wannan cutar ɓarna ce.',
  recommendations: ['Cire ganyen da suka kamu.'],
  safety_note: 'Wannan shawarar AI ce.',
};
const SENTENCES = [
  { text: 'Ganyen sun yi ruwan ƙasa.', section: 'observations', item: 0, start: 0, end: 1.5 },
  { text: 'Wannan cutar ɓarna ce.', section: 'explanation', item: 0, start: 2.2, end: 3.6 },
  { text: 'Cire ganyen da suka kamu.', section: 'recommendations', item: 0, start: 4.3, end: 5.9 },
  { text: 'Wannan shawarar AI ce.', section: 'safety_note', item: 0, start: 6.6, end: 7.8 },
];
const READY = { lang: 'ha', status: 'ready', url: 'https://x/a.mp3', downloadUrl: 'https://x/a.mp3?download=1', sizeBytes: 1200, sentences: SENTENCES };

const flush = () => act(async () => {});
const advance = async (ms) => { await act(async () => { jest.advanceTimersByTime(ms); }); await flush(); };

beforeEach(() => {
  jest.useFakeTimers();
  jest.clearAllMocks();
  for (const k of Object.keys(mockFiles)) delete mockFiles[k];
  mockStatus = { playing: false, currentTime: 0, duration: 8, didJustFinish: false };
  speechAPI.languages.mockResolvedValue(LANGS);
  speechAPI.translate.mockResolvedValue({ lang: 'ha', status: 'ready', content: CONTENT });
  speechAPI.requestAudio.mockResolvedValue(READY);
});
afterEach(() => jest.useRealTimers());

test('shows languages by their own names, defaults to the saved choice, and does nothing until asked', async () => {
  render(<ListenPanel scanId={7} />);
  await flush();
  expect(screen.getByText('Hausa')).toBeTruthy();
  expect(screen.getByText('Naijá')).toBeTruthy();
  expect(screen.getByTestId('listen-lang-ha').props.accessibilityState.selected).toBe(true);
  await advance(10000);
  expect(speechAPI.translate).not.toHaveBeenCalled();
  expect(mockPlayer.play).not.toHaveBeenCalled(); // no auto-play
});

test('Listen translates, waits for the audio, then plays it with the text on screen', async () => {
  speechAPI.translate.mockResolvedValue({ lang: 'ha', status: 'pending' });
  speechAPI.translation.mockResolvedValue({ lang: 'ha', status: 'ready', content: CONTENT });
  speechAPI.requestAudio.mockResolvedValue({ lang: 'ha', status: 'pending' });
  speechAPI.audio.mockResolvedValue(READY);

  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-play'));
  await flush();
  expect(speechAPI.translate).toHaveBeenCalledWith(7, { lang: 'ha', remember: true, audio: true });

  await advance(1500); // translation poll -> ready, audio requested
  expect(screen.getByText('Bayani')).toBeTruthy(); // section heading in Hausa
  await advance(2000); // audio poll -> ready
  expect(mockPlayer.replace).toHaveBeenCalledWith({ uri: READY.url });
  expect(mockPlayer.play).toHaveBeenCalled();
  expect(screen.getByText(/Download audio \(1 KB\)/)).toBeTruthy();
});

test('the sentence being spoken is highlighted and speed changes apply', async () => {
  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-play'));
  await flush(); await flush();

  mockStatus = { ...mockStatus, playing: true, currentTime: 2.5 };
  fireEvent.press(screen.getByTestId('listen-speed-0.8')); // re-render with the new time
  expect(mockPlayer.setPlaybackRate).toHaveBeenCalledWith(0.8);
  const current = screen.getByTestId('listen-current');
  expect([].concat(current.props.children).join('')).toContain('Wannan cutar ɓarna ce.');

  fireEvent.press(screen.getByTestId('listen-play')); // now playing -> pause
  expect(mockPlayer.pause).toHaveBeenCalled();
});

test('when audio is unavailable the translated text stays and the phone voice can read it', async () => {
  speechAPI.requestAudio.mockResolvedValue({ lang: 'ha', status: 'failed', message: 'Audio is temporarily unavailable. Read the result below and try again later.' });
  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-play'));
  await flush(); await flush();

  expect(screen.getByTestId('listen-message').props.children).toMatch(/temporarily unavailable/);
  expect(screen.getByText('Wannan cutar ɓarna ce.', { exact: false })).toBeTruthy();
  expect(mockPlayer.play).not.toHaveBeenCalled();
  fireEvent.press(screen.getByTestId('listen-phone-voice'));
  expect(VoiceService.speak).toHaveBeenCalledWith(expect.stringContaining('Wannan shawarar AI ce.'), 'ha', { rate: 1 });
});

test('a language without a voice shows the text only', async () => {
  speechAPI.translate.mockResolvedValue({ lang: 'pcm', status: 'ready', content: CONTENT });
  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-lang-pcm'));
  await flush(); await flush();
  expect(speechAPI.requestAudio).not.toHaveBeenCalled();
  expect(screen.getByTestId('listen-message').props.children).toMatch(/not available for this language/);
});

test('download saves the audio for offline listening, and it plays later without a connection', async () => {
  const first = render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-play'));
  await flush(); await flush();
  fireEvent.press(screen.getByTestId('listen-download'));
  await flush();
  expect(File.downloadFileAsync).toHaveBeenCalledWith(READY.downloadUrl, expect.objectContaining({ uri: 'file:///docs/speech/result-7-ha.mp3' }));
  expect(screen.getByText(/Saved for offline listening/)).toBeTruthy();
  first.unmount();

  // Later, offline: the saved copy plays, with its sentences.
  const { unmount } = render(<ListenPanel scanId={7} />);
  await flush();
  speechAPI.translate.mockRejectedValue(Object.assign(new Error('MSAS could not be reached.'), { kind: 'network' }));
  mockPlayer.replace.mockClear();
  fireEvent.press(screen.getByTestId('listen-play'));
  await flush(); await flush();
  expect(mockPlayer.replace).toHaveBeenCalledWith({ uri: 'file:///docs/speech/result-7-ha.mp3' });
  unmount();
});

test('after listening, the farmer is asked whether the audio was clear', async () => {
  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-play'));
  await flush(); await flush();

  mockStatus = { ...mockStatus, didJustFinish: true };
  fireEvent.press(screen.getByTestId('listen-speed-1.2')); // any state change re-renders with the new status
  await flush();
  fireEvent.press(screen.getByTestId('listen-fb-no'));
  expect(speechAPI.feedback).toHaveBeenCalledWith({ scan_id: 7, lang: 'ha', clear: false, comment: null });
  fireEvent.changeText(screen.getByTestId('listen-fb-comment'), 'Too fast');
  fireEvent.press(screen.getByTestId('listen-fb-send'));
  expect(speechAPI.feedback).toHaveBeenLastCalledWith({ scan_id: 7, lang: 'ha', clear: false, comment: 'Too fast' });
  expect(screen.getByText('Thank you!')).toBeTruthy();
});

test('leaving the screen stops polling', async () => {
  speechAPI.translate.mockResolvedValue({ lang: 'ha', status: 'pending' });
  speechAPI.translation.mockResolvedValue({ lang: 'ha', status: 'pending' });
  const { unmount } = render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-play'));
  await advance(1500);
  unmount();
  const calls = speechAPI.translation.mock.calls.length;
  await advance(30000);
  expect(speechAPI.translation).toHaveBeenCalledTimes(calls);
  expect(VoiceService.stop).toHaveBeenCalled();
});

test('helpers: sentence timing, sections and file size', () => {
  expect(sentenceAt(SENTENCES, 0.1)).toBe(0);
  expect(sentenceAt(SENTENCES, 1.7)).toBe(0); // short pause still shows the last sentence
  expect(sentenceAt(SENTENCES, 2.3)).toBe(1);
  expect(sentenceAt(SENTENCES, 20)).toBe(-1);
  expect(sectionsFrom(null, SENTENCES).map((s) => s.section)).toEqual(['observations', 'explanation', 'recommendations', 'safety_note']);
  expect(sectionsFrom(CONTENT, null)[0].items[0][0].text).toBe('Ganyen sun yi ruwan ƙasa.');
  expect(formatSize(1200)).toBe('1 KB');
  expect(formatSize(2.5 * 1048576)).toBe('2.5 MB');
});

// ── Sentence-by-sentence playback (Phase 13.1) ──────────────────────────────

const PENDING_SENTENCES = SENTENCES.map(({ start, end, ...s }) => ({ ...s, start: null, end: null }));
const chunk = (i) => ({ index: i, sentence: i, url: `https://x/c${i}.mp3`, durationSec: 2 });
const streaming = (n, extra = {}) => ({ lang: 'ha', status: 'pending', message: 'Audio is being prepared…', chunksTotal: 4,
  chunks: Array.from({ length: n }, (_, i) => chunk(i)), sentences: PENDING_SENTENCES, ...extra });
const finishCurrent = async () => {
  // A speed change is a state change, so the panel re-renders with the new player status.
  mockStatus = { ...mockStatus, didJustFinish: true };
  fireEvent.press(screen.getByTestId('listen-speed-0.8'));
  await flush();
  mockStatus = { ...mockStatus, didJustFinish: false };
  fireEvent.press(screen.getByTestId('listen-speed-1'));
  await flush();
};

test('the first sentence plays as soon as it exists, before the whole file is ready', async () => {
  speechAPI.requestAudio.mockResolvedValue(streaming(1));
  speechAPI.audio.mockResolvedValue(streaming(2));
  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-play'));
  await flush(); await flush();

  expect(mockPlayer.replace).toHaveBeenCalledWith({ uri: 'https://x/c0.mp3' });
  expect(mockPlayer.play).toHaveBeenCalledTimes(1);
  // The sentence being spoken is highlighted from the chunk, before timings exist.
  expect([].concat(screen.getByTestId('listen-current').props.children).join('')).toContain('Ganyen sun yi ruwan ƙasa.');
  expect(screen.queryByTestId('listen-download')).toBeNull(); // no whole file yet

  await advance(2000); // poll: sentence 2 is ready
  await finishCurrent();
  expect(mockPlayer.replace).toHaveBeenLastCalledWith({ uri: 'https://x/c1.mp3' });
});

test('when playback catches up it says audio is being prepared, then continues by itself', async () => {
  speechAPI.requestAudio.mockResolvedValue(streaming(1));
  speechAPI.audio.mockResolvedValueOnce(streaming(1)).mockResolvedValue(streaming(2));
  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-play'));
  await flush(); await flush();

  await finishCurrent(); // sentence 1 done, sentence 2 not generated yet
  expect(screen.getByTestId('listen-message').props.children).toBe('Audio is being prepared…');
  expect(mockPlayer.replace).toHaveBeenCalledTimes(1);

  await advance(2000); // still only 1
  await advance(2000); // now 2: it plays without another tap
  expect(mockPlayer.replace).toHaveBeenLastCalledWith({ uri: 'https://x/c1.mp3' });
});

test('after the last sentence the farmer is asked for feedback, and download appears once the whole file exists', async () => {
  const ready = { ...READY, chunksTotal: 2, chunks: [chunk(0), chunk(1)] };
  speechAPI.requestAudio.mockResolvedValue(streaming(1, { chunksTotal: 2 }));
  speechAPI.audio.mockResolvedValue(ready);
  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-play'));
  await flush(); await flush();
  await advance(2000); // whole file done

  await finishCurrent();
  expect(mockPlayer.replace).toHaveBeenLastCalledWith({ uri: 'https://x/c1.mp3' });
  await finishCurrent();
  expect(screen.getByTestId('listen-feedback')).toBeTruthy();
  expect(screen.getByText(/Download audio/)).toBeTruthy();

  // Replay uses the whole file now that it exists.
  fireEvent.press(screen.getByTestId('listen-replay'));
  expect(mockPlayer.replace).toHaveBeenLastCalledWith({ uri: READY.url });
});

test('while audio is being prepared the text is shown, and Listen plays it when ready (no auto-play otherwise)', async () => {
  speechAPI.requestAudio.mockResolvedValue({ lang: 'ha', status: 'pending', message: 'Audio is being prepared…' });
  speechAPI.audio.mockResolvedValueOnce({ lang: 'ha', status: 'pending' }).mockResolvedValue(streaming(1));
  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-lang-ha')); // picking a language prepares, never plays
  await flush(); await flush();
  expect(screen.getByTestId('listen-message').props.children).toBe('Audio is being prepared…');
  expect(screen.getByText('Wannan cutar ɓarna ce.', { exact: false })).toBeTruthy();

  fireEvent.press(screen.getByTestId('listen-play')); // "play when ready"
  expect(screen.getByTestId('listen-message').props.children).toMatch(/will play when ready/);
  expect(mockPlayer.play).not.toHaveBeenCalled();
  await advance(2000);
  await advance(2000);
  expect(mockPlayer.replace).toHaveBeenCalledWith({ uri: 'https://x/c0.mp3' });
  expect(mockPlayer.play).toHaveBeenCalled();
});

test('picking a language while sentences stream in does not start playback', async () => {
  speechAPI.requestAudio.mockResolvedValue(streaming(1));
  speechAPI.audio.mockResolvedValue(streaming(2));
  render(<ListenPanel scanId={7} />);
  await flush();
  fireEvent.press(screen.getByTestId('listen-lang-ha'));
  await flush(); await flush();
  await advance(2000);
  expect(mockPlayer.play).not.toHaveBeenCalled();
  fireEvent.press(screen.getByTestId('listen-play'));
  expect(mockPlayer.replace).toHaveBeenCalledWith({ uri: 'https://x/c0.mp3' });
});
