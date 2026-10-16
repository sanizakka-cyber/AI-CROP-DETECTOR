import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { useAudioPlayer, useAudioPlayerStatus } from 'expo-audio';
import { Directory, File, Paths } from 'expo-file-system';
import { speechAPI } from '../lib/api';
import { VoiceService } from '../services/VoiceService';
import { Colors, Radius, Spacing } from '../constants/Theme';

/**
 * "Listen to your result": the result in the farmer's chosen language, read
 * aloud with the current sentence highlighted. Nothing is translated or
 * generated until the farmer picks a language or presses Listen (no
 * auto-play). If audio is not available the translated text stays on screen.
 */

const SPEEDS = [0.8, 1, 1.2];
const BUSY = ['pending', 'retrying', 'translating', 'none'];
const AUDIO_WAIT_MS = 4 * 60 * 1000;

// Section headings shown in the chosen language (part of native-speaker review).
const HEADINGS = {
  en: { observations: 'Observations', explanation: 'Explanation', recommendations: 'Recommendations', safety_note: 'Safety note' },
  ha: { observations: 'Abubuwan da aka gani', explanation: 'Bayani', recommendations: 'Shawarwari', safety_note: 'Gargaɗin tsaro' },
  yo: { observations: 'Àwọn àkíyèsí', explanation: 'Àlàyé', recommendations: 'Àwọn ìmọ̀ràn', safety_note: 'Ìkìlọ̀ ààbò' },
  ig: { observations: 'Ihe a hụrụ', explanation: 'Nkọwa', recommendations: 'Ndụmọdụ', safety_note: 'Ịdọ aka ná ntị maka nchekwa' },
};
const SECTIONS = ['observations', 'explanation', 'recommendations', 'safety_note'];

const L = (ha, en, uiHausa) => (uiHausa ? ha : en);

export function formatSize(bytes) {
  if (!bytes) return '';
  return bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

/** Index of the sentence being spoken at time t (seconds), or -1. */
export function sentenceAt(sentences, t) {
  let idx = -1;
  (sentences || []).forEach((s, i) => {
    if (s.start != null && t >= s.start - 0.05 && t < s.end + 0.35) idx = i;
  });
  return idx;
}

/** [{section, items: [[{text, i}]]}] from sentences, or from plain content. */
export function sectionsFrom(content, sentences) {
  if (sentences && sentences.length) {
    return SECTIONS.map((section) => {
      const items = {};
      sentences.forEach((s, i) => {
        if (s.section === section) (items[s.item] = items[s.item] || []).push({ text: s.text, i });
      });
      return { section, items: Object.values(items) };
    }).filter((s) => s.items.length);
  }
  if (!content) return [];
  return SECTIONS.map((section) => {
    const v = content[section];
    const list = Array.isArray(v) ? v : (v ? [v] : []);
    return { section, items: list.map((text) => [{ text, i: -1 }]) };
  }).filter((s) => s.items.length);
}

function offlineFiles(scanId, lang) {
  const dir = new Directory(Paths.document, 'speech');
  return { dir, audio: new File(dir, `result-${scanId}-${lang}.mp3`), meta: new File(dir, `result-${scanId}-${lang}.json`) };
}

const wait = (ms) => new Promise((r) => setTimeout(r, ms));

export default function ListenPanel({ scanId, uiHausa = false }) {
  const [languages, setLanguages] = useState([]);
  const [lang, setLang] = useState(null);
  const [remember, setRemember] = useState(false);
  const [content, setContent] = useState(null);       // text before audio is ready
  const [audio, setAudio] = useState(null);           // ready audio payload
  const [status, setStatus] = useState('idle');       // idle | loading | ready | text-only | error
  const [message, setMessage] = useState('');
  const [speed, setSpeed] = useState(1);
  const [savedOffline, setSavedOffline] = useState(false);
  const [saving, setSaving] = useState(false);
  const [feedback, setFeedback] = useState({ shown: false, clear: null, comment: '', sent: false });

  const player = useAudioPlayer(null);
  const playerStatus = useAudioPlayerStatus(player);
  const loadToken = useRef(0);
  const mounted = useRef(true);
  const playWhenReady = useRef(false);

  useEffect(() => {
    mounted.current = true;
    speechAPI.languages().then((res) => {
      if (!mounted.current) return;
      setLanguages(res.languages || []);
      setLang(res.default || 'en');
      setRemember(!!res.preferred);
    }).catch(() => {});
    return () => {
      mounted.current = false;
      loadToken.current += 1;
      VoiceService.stop();
    };
  }, []);

  useEffect(() => {
    if (playerStatus.didJustFinish) setFeedback((f) => ({ ...f, shown: true }));
  }, [playerStatus.didJustFinish]);

  const current = audio ? sentenceAt(audio.sentences, playerStatus.currentTime) : -1;
  const progress = playerStatus.duration ? Math.min(1, playerStatus.currentTime / playerStatus.duration) : 0;

  const startAudio = useCallback((payload, uri) => {
    player.replace({ uri });
    player.setPlaybackRate(speed);
    setAudio(payload);
    setStatus('ready');
    setMessage('');
    if (playWhenReady.current) player.play();
  }, [player, speed]);

  const load = useCallback(async (code, thenPlay) => {
    const token = ++loadToken.current;
    const alive = () => mounted.current && token === loadToken.current;
    playWhenReady.current = thenPlay;
    player.pause();
    setAudio(null);
    setContent(null);
    setSavedOffline(false);
    setFeedback({ shown: false, clear: null, comment: '', sent: false });
    setStatus('loading');
    setMessage(L('Ana shirya sakamakonka…', 'Preparing your result in this language…', uiHausa));

    const files = offlineFiles(scanId, code);
    try {
      let t = await speechAPI.translate(scanId, { lang: code, remember, audio: true });
      for (let i = 0; t.status === 'pending' && i < 60 && alive(); i++) {
        await wait(1500);
        if (!alive()) return; // left the screen or picked another language while waiting
        t = await speechAPI.translation(scanId, code);
      }
      if (!alive()) return;
      setContent(t.content || t.source?.content || null);
      if (t.status !== 'ready') {
        setStatus('text-only');
        setMessage(t.message || '');
        return;
      }

      const chosen = languages.find((l) => l.code === code);
      if (chosen && !chosen.hasVoice) {
        setStatus('text-only');
        setMessage(L('Babu sauti a wannan harshe tukuna.', 'Audio is not available for this language yet. Read the result below.', uiHausa));
        return;
      }

      setMessage(L('Ana shirya sauti…', 'Preparing audio…', uiHausa));
      let a = await speechAPI.requestAudio(scanId, { lang: code });
      const started = Date.now();
      while (alive() && BUSY.includes(a.status) && Date.now() - started < AUDIO_WAIT_MS) {
        if (a.status === 'retrying') setMessage(a.message);
        else if (Date.now() - started > 20000) setMessage(L('Yana ɗaukar lokaci. Karanta sakamakon da ke ƙasa.', 'This is taking longer than usual. Read the result below; the audio will be ready soon.', uiHausa));
        await wait(2000);
        if (!alive()) return;
        a = await speechAPI.audio(scanId, code);
      }
      if (!alive()) return;
      if (a.status !== 'ready') {
        setStatus('text-only');
        setMessage(a.message || L('Sauti bai samu ba a yanzu.', 'Audio is temporarily unavailable. Read the result below.', uiHausa));
        return;
      }
      setSavedOffline(files.audio.exists);
      startAudio(a, files.audio.exists ? files.audio.uri : a.url);
    } catch (e) {
      if (!alive()) return;
      // No connection: play a copy saved earlier, if there is one.
      if (files.audio.exists && files.meta.exists) {
        try {
          const saved = JSON.parse(files.meta.textSync());
          setSavedOffline(true);
          startAudio(saved, files.audio.uri);
          return;
        } catch { /* fall through */ }
      }
      setStatus('error');
      setMessage(e?.message || L('Ba a iya kaiwa MSAS ba.', 'Could not reach MSAS. Check your connection and try again.', uiHausa));
    }
  }, [scanId, remember, languages, player, startAudio, uiHausa]);

  const onListen = () => {
    if (status === 'ready') {
      if (playerStatus.playing) player.pause();
      else {
        if (playerStatus.didJustFinish || (playerStatus.duration && playerStatus.currentTime >= playerStatus.duration - 0.2)) player.seekTo(0);
        player.play();
      }
      return;
    }
    if (lang) load(lang, true);
  };

  const onPickLanguage = (code) => {
    setLang(code);
    load(code, false);
  };

  const onSpeed = (s) => {
    setSpeed(s);
    player.setPlaybackRate(s);
  };

  const onDownload = async () => {
    if (!audio?.downloadUrl || saving) return;
    setSaving(true);
    try {
      const files = offlineFiles(scanId, lang);
      files.dir.create({ intermediates: true, idempotent: true });
      if (files.audio.exists) files.audio.delete();
      await File.downloadFileAsync(audio.downloadUrl, files.audio);
      files.meta.write(JSON.stringify({ sentences: audio.sentences, sizeBytes: audio.sizeBytes, lang }));
      if (mounted.current) setSavedOffline(true);
    } catch {
      if (mounted.current) setMessage(L('Ba a iya ajiye sautin ba.', 'Could not save the audio. Try again.', uiHausa));
    } finally {
      if (mounted.current) setSaving(false);
    }
  };

  const onPhoneVoice = () => {
    const text = sectionsFrom(content, null).flatMap((s) => s.items.flat().map((x) => x.text)).join(' ');
    if (text) VoiceService.speak(text, lang, { rate: speed });
  };

  const sendFeedback = (clear, comment) => {
    setFeedback((f) => ({ ...f, clear, sent: comment !== undefined ? true : f.sent }));
    speechAPI.feedback({ scan_id: Number(scanId), lang, clear, comment: comment || null }).catch(() => {});
  };

  if (!languages.length) return null;

  const headings = HEADINGS[lang] || HEADINGS.en;
  const sections = sectionsFrom(audio ? null : content, audio?.sentences);
  const playing = playerStatus.playing;

  return (
    <View style={styles.panel} testID="listen-panel">
      <Text style={styles.title}>🔊 {L('Saurari sakamakonka', 'Listen to your result', uiHausa)}</Text>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.langRow}>
        {languages.map((l) => (
          <TouchableOpacity
            key={l.code}
            testID={`listen-lang-${l.code}`}
            accessibilityRole="button"
            accessibilityState={{ selected: lang === l.code }}
            style={[styles.langChip, lang === l.code && styles.langChipActive]}
            onPress={() => onPickLanguage(l.code)}
          >
            <Text style={[styles.langText, lang === l.code && styles.langTextActive]}>{l.name}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <View style={styles.rememberRow}>
        <Switch value={remember} onValueChange={setRemember} testID="listen-remember" />
        <Text style={styles.rememberText}>{L('Ka tuna zaɓina', 'Remember my choice', uiHausa)}</Text>
      </View>

      <View style={styles.controls}>
        <TouchableOpacity
          testID="listen-play"
          accessibilityRole="button"
          style={[styles.listenBtn, playing && styles.listenBtnActive]}
          onPress={onListen}
          disabled={status === 'loading'}
        >
          {status === 'loading'
            ? <ActivityIndicator color="#fff" />
            : <Text style={styles.listenText}>{playing ? `⏸  ${L('Dakata', 'Pause', uiHausa)}` : `▶  ${L('Saurara', 'Listen', uiHausa)}`}</Text>}
        </TouchableOpacity>
        {status === 'ready' && (
          <TouchableOpacity testID="listen-replay" style={styles.smallBtn} onPress={() => { player.seekTo(0); player.play(); }}>
            <Text style={styles.smallText}>↺ {L('Sake', 'Replay', uiHausa)}</Text>
          </TouchableOpacity>
        )}
      </View>

      <View style={styles.speedRow}>
        {SPEEDS.map((s) => (
          <TouchableOpacity key={s} testID={`listen-speed-${s}`} style={[styles.speedPill, speed === s && styles.speedPillActive]} onPress={() => onSpeed(s)}>
            <Text style={[styles.speedText, speed === s && styles.speedTextActive]}>{s}×</Text>
          </TouchableOpacity>
        ))}
      </View>

      {status === 'ready' && (
        <View style={styles.progressTrack} testID="listen-progress">
          <View style={[styles.progressFill, { width: `${Math.round(progress * 100)}%` }]} />
        </View>
      )}

      {!!message && <Text style={styles.message} testID="listen-message">{message}</Text>}

      {status === 'text-only' && !!content && (
        <TouchableOpacity testID="listen-phone-voice" style={styles.smallBtn} onPress={onPhoneVoice}>
          <Text style={styles.smallText}>{L('Karanta da muryar waya', 'Read with phone voice', uiHausa)}</Text>
        </TouchableOpacity>
      )}

      {sections.length > 0 && (
        <View style={styles.textBox} testID="listen-text">
          {sections.map(({ section, items }) => (
            <View key={section} style={{ marginBottom: Spacing.sm }}>
              <Text style={styles.heading}>{headings[section]}</Text>
              {items.map((sentencesInItem, k) => (
                <Text key={k} style={styles.item}>
                  {(section === 'observations' || section === 'recommendations') ? '• ' : ''}
                  {sentencesInItem.map(({ text, i }) => (
                    <Text key={`${i}-${text}`} style={i >= 0 && i === current ? styles.currentSentence : null} testID={i === current && i >= 0 ? 'listen-current' : undefined}>
                      {text}{' '}
                    </Text>
                  ))}
                </Text>
              ))}
            </View>
          ))}
        </View>
      )}

      {status === 'ready' && (
        <TouchableOpacity testID="listen-download" style={styles.smallBtn} onPress={onDownload} disabled={saving || savedOffline}>
          <Text style={styles.smallText}>
            {savedOffline
              ? `✓ ${L('An ajiye don sauraro ba tare da intanet ba', 'Saved for offline listening', uiHausa)}`
              : `⬇ ${L('Sauke sauti', 'Download audio', uiHausa)} (${formatSize(audio?.sizeBytes)})`}
          </Text>
        </TouchableOpacity>
      )}

      {feedback.shown && (
        <View style={styles.feedback} testID="listen-feedback">
          <Text style={styles.smallTextDark}>{L('Shin sautin ya fito sosai?', 'Was this audio clear?', uiHausa)}</Text>
          {feedback.sent ? (
            <Text style={styles.smallTextDark}>{L('Mun gode!', 'Thank you!', uiHausa)}</Text>
          ) : (
            <>
              <View style={{ flexDirection: 'row', gap: 8 }}>
                <TouchableOpacity testID="listen-fb-yes" style={[styles.fbBtn, feedback.clear === true && styles.fbBtnOn]} onPress={() => sendFeedback(true)}><Text>👍</Text></TouchableOpacity>
                <TouchableOpacity testID="listen-fb-no" style={[styles.fbBtn, feedback.clear === false && styles.fbBtnOn]} onPress={() => sendFeedback(false)}><Text>👎</Text></TouchableOpacity>
              </View>
              {feedback.clear !== null && (
                <View style={{ flexDirection: 'row', gap: 8, marginTop: 6 }}>
                  <TextInput
                    testID="listen-fb-comment"
                    style={styles.input}
                    maxLength={1000}
                    value={feedback.comment}
                    onChangeText={(c) => setFeedback((f) => ({ ...f, comment: c }))}
                    placeholder={L('Me bai fito ba? (ba dole ba)', 'What was unclear? (optional)', uiHausa)}
                  />
                  <TouchableOpacity testID="listen-fb-send" style={styles.smallBtn} onPress={() => sendFeedback(feedback.clear, feedback.comment)}>
                    <Text style={styles.smallText}>{L('Aika', 'Send', uiHausa)}</Text>
                  </TouchableOpacity>
                </View>
              )}
            </>
          )}
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  panel: { backgroundColor: '#1e293b', borderRadius: Radius.lg, padding: Spacing.md, marginHorizontal: Spacing.md, marginBottom: Spacing.md },
  title: { color: '#fff', fontSize: 16, fontWeight: '800', marginBottom: Spacing.sm },
  langRow: { gap: 8, paddingVertical: 4 },
  langChip: { paddingHorizontal: 14, paddingVertical: 10, borderRadius: 999, backgroundColor: '#334155' },
  langChipActive: { backgroundColor: Colors.primary },
  langText: { color: '#e2e8f0', fontSize: 15, fontWeight: '600' },
  langTextActive: { color: '#fff' },
  rememberRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 6 },
  rememberText: { color: '#cbd5e1', fontSize: 13 },
  controls: { flexDirection: 'row', alignItems: 'center', gap: 10, marginTop: Spacing.sm },
  listenBtn: { flex: 1, backgroundColor: '#10b981', borderRadius: Radius.lg, paddingVertical: 16, alignItems: 'center', minHeight: 56, justifyContent: 'center' },
  listenBtnActive: { backgroundColor: Colors.primary },
  listenText: { color: '#fff', fontSize: 20, fontWeight: '800' },
  smallBtn: { backgroundColor: '#475569', borderRadius: Radius.md, paddingHorizontal: 12, paddingVertical: 10, marginTop: 8, alignSelf: 'flex-start' },
  smallText: { color: '#fff', fontSize: 13, fontWeight: '600' },
  smallTextDark: { color: '#e2e8f0', fontSize: 13, marginBottom: 6 },
  speedRow: { flexDirection: 'row', gap: 8, marginTop: Spacing.sm },
  speedPill: { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 999, backgroundColor: '#334155' },
  speedPillActive: { backgroundColor: '#059669' },
  speedText: { color: '#cbd5e1', fontWeight: '700' },
  speedTextActive: { color: '#fff' },
  progressTrack: { height: 8, backgroundColor: '#334155', borderRadius: 4, overflow: 'hidden', marginTop: Spacing.sm },
  progressFill: { height: 8, backgroundColor: '#34d399' },
  message: { color: '#fde68a', fontSize: 13, marginTop: Spacing.sm },
  textBox: { backgroundColor: '#0f172a', borderRadius: Radius.md, padding: Spacing.md, marginTop: Spacing.sm },
  heading: { color: '#6ee7b7', fontSize: 12, fontWeight: '800', textTransform: 'uppercase', marginBottom: 4 },
  item: { color: '#e2e8f0', fontSize: 16, lineHeight: 24, marginBottom: 4 },
  currentSentence: { backgroundColor: '#065f46', color: '#fff' },
  feedback: { marginTop: Spacing.sm },
  fbBtn: { backgroundColor: '#334155', borderRadius: 8, paddingHorizontal: 14, paddingVertical: 8 },
  fbBtnOn: { backgroundColor: '#059669' },
  input: { flex: 1, backgroundColor: '#334155', color: '#fff', borderRadius: 8, paddingHorizontal: 10 },
});
