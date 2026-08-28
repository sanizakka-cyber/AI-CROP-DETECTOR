import React, { useState, useRef } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  ScrollView, KeyboardAvoidingView, Platform, ActivityIndicator, Animated, Easing, Alert, Linking,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import {
  useAudioRecorder, useAudioRecorderState, RecordingPresets,
  requestRecordingPermissionsAsync, setAudioModeAsync,
} from 'expo-audio';
import { aiAPI } from '../lib/api';
import { Colors, Spacing, Radius, Typography, Shadows } from '../constants/Theme';

/**
 * Renders one AI reply. The backend (AiWidgetController::chat(), via
 * AiResponseNormalizer) already strips Markdown and returns structured
 * `sections` — the same normalized payload the web chat widget renders —
 * so this only ever needs to lay out plain strings, never parse Markdown
 * itself. Falls back to the flat `reply` string for the initial greeting
 * and any message that predates structured sections.
 */
function BotMessage({ text, sections }) {
  if (sections && sections.length > 0) {
    return (
      <View style={[styles.bubble, styles.bubbleBot]}>
        {sections.map((s, i) => (
          <View key={i} style={i > 0 ? { marginTop: 8 } : null}>
            {s.title ? <Text style={styles.sectionTitle}>{s.title}</Text> : null}
            {s.content ? <Text style={styles.sectionText}>{s.content}</Text> : null}
            {s.items?.map((item, j) => (
              <Text key={j} style={styles.sectionItem}>{'•'} {item}</Text>
            ))}
          </View>
        ))}
      </View>
    );
  }
  return (
    <View style={[styles.bubble, styles.bubbleBot]}>
      <Text style={styles.sectionText}>{text}</Text>
    </View>
  );
}

export default function AssistantScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';
  const scrollRef = useRef(null);

  const [messages, setMessages] = useState([
    { role: 'bot', text: isHausa
        ? 'Sannu! Ni ne mataimakin gonar MSAS na AI. Tambaye ni komai game da amfanin gona, kwari, dabbobi, farashin kasuwa, ko yanayi.'
        : "Hello! I'm your MSAS AI farming assistant. Ask me anything about crops, pests, livestock, market prices, or weather." },
  ]);
  const [input, setInput] = useState('');
  const [sending, setSending] = useState(false);
  const historyRef = useRef('[]');

  // ── Voice input ──────────────────────────────────────────────────────
  // Records real audio and uploads it to the same server-side Whisper
  // transcription endpoint the web chat widget uses (AiWidgetController::
  // transcribe()) — deliberately not on-device speech recognition, since
  // that engine isn't reliably present on every Android environment
  // (BlueStacks in particular often lacks Google's speech services), and
  // this way both clients share one transcription implementation instead
  // of two different engines potentially disagreeing.
  const [voiceState, setVoiceState] = useState('idle'); // idle | recording | processing
  const recorder = useAudioRecorder(RecordingPresets.HIGH_QUALITY);
  const recorderState = useAudioRecorderState(recorder, 200);
  const pulseAnim = useRef(new Animated.Value(1)).current;

  React.useEffect(() => {
    if (voiceState !== 'recording') { pulseAnim.setValue(1); return; }
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, { toValue: 1.25, duration: 500, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
        Animated.timing(pulseAnim, { toValue: 1, duration: 500, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [voiceState]);

  const startRecording = async () => {
    const perm = await requestRecordingPermissionsAsync();
    if (!perm.granted) {
      Alert.alert(
        isHausa ? 'Ana Bukatar Izinin Makirofo' : 'Microphone Permission Required',
        isHausa
          ? "Ana bukatar izinin makirofo don amfani da shigar da murya. Da fatan ku bada izini a saitunan na'ku."
          : 'Microphone access is required to use voice input. Please allow microphone access in your device settings.',
        [
          { text: isHausa ? 'A\'a' : 'Cancel', style: 'cancel' },
          { text: isHausa ? 'Buɗe Saitunan' : 'Open Settings', onPress: () => Linking.openSettings() },
        ]
      );
      return;
    }
    try {
      await setAudioModeAsync({ allowsRecording: true, playsInSilentMode: true });
      await recorder.prepareToRecordAsync();
      recorder.record();
      setVoiceState('recording');
    } catch (e) {
      setMessages(prev => [...prev, { role: 'bot', text: "We couldn't start recording. Please try again." }]);
    }
  };

  const stopRecording = async () => {
    setVoiceState('processing');
    try {
      await recorder.stop();
      const uri = recorder.uri;
      if (!uri) throw new Error('no-recording');
      const data = await aiAPI.transcribe(uri, i18n.language);
      if (data.text) {
        setInput(prev => (prev ? prev.trim() + ' ' : '') + data.text);
      } else {
        setMessages(prev => [...prev, { role: 'bot', text: "We couldn't understand the recording. Please try again in a quieter environment." }]);
      }
    } catch (e) {
      setMessages(prev => [...prev, { role: 'bot', text: e?.message || "We couldn't understand the recording. Please try again in a quieter environment." }]);
    } finally {
      setVoiceState('idle');
    }
  };

  const send = async () => {
    const msg = input.trim();
    if (!msg || sending) return;
    setInput('');
    setMessages(prev => [...prev, { role: 'user', text: msg }]);
    setSending(true);

    try {
      const data = await aiAPI.chat(msg, historyRef.current, i18n.language);
      if (data.history) historyRef.current = typeof data.history === 'string' ? data.history : JSON.stringify(data.history);
      const hasSections = Array.isArray(data.sections) && data.sections.length > 0;
      // Mirror the web widget's guard: never render an empty/`undefined`
      // bubble if the backend returns an unexpected shape (spec §15).
      const reply = (typeof data.reply === 'string' && data.reply.trim())
        || data.response || data.message
        || (hasSections ? '' : "We couldn't process your request right now. Please try again.");
      setMessages(prev => [...prev, { role: 'bot', text: reply, sections: data.sections }]);
    } catch (e) {
      // Never surface a raw technical error to the farmer.
      const friendly = e?.kind === 'network'
        ? 'Connection unavailable. Please check your internet connection and try again.'
        : "We couldn't process your request right now. Please try again.";
      setMessages(prev => [...prev, { role: 'bot', text: friendly }]);
    } finally {
      setSending(false);
      setTimeout(() => scrollRef.current?.scrollToEnd({ animated: true }), 100);
    }
  };

  return (
    <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.back}>
          <MaterialCommunityIcons name="close" size={22} color={Colors.white} />
        </TouchableOpacity>
        <View style={styles.headerTitleRow}>
          <MaterialCommunityIcons name="robot-outline" size={20} color={Colors.white} />
          <Text style={styles.headerTitle}>{isHausa ? 'Mataimakin AI na MSAS' : 'MSAS AI Assistant'}</Text>
        </View>
      </View>

      <ScrollView
        ref={scrollRef}
        style={styles.root}
        contentContainerStyle={styles.content}
        onContentSizeChange={() => scrollRef.current?.scrollToEnd({ animated: true })}
      >
        {messages.map((m, i) => m.role === 'user' ? (
          <View key={i} style={[styles.bubble, styles.bubbleUser]}>
            <Text style={styles.userText}>{m.text}</Text>
          </View>
        ) : (
          <BotMessage key={i} text={m.text} sections={m.sections} />
        ))}
        {sending && (
          <View style={[styles.bubble, styles.bubbleBot, { flexDirection: 'row', alignItems: 'center', gap: 8 }]}>
            <ActivityIndicator size="small" color={Colors.primary} />
            <Text style={styles.sectionText}>{isHausa ? 'Ana tunani...' : 'Thinking...'}</Text>
          </View>
        )}
      </ScrollView>

      {voiceState === 'recording' ? (
        <View style={styles.recordingBar}>
          <Animated.View style={[styles.recDot, { transform: [{ scale: pulseAnim }] }]} />
          <Text style={styles.recordingText}>{isHausa ? 'Ana saurara...' : 'Listening...'}</Text>
          <TouchableOpacity style={styles.stopBtn} onPress={stopRecording}>
            <Text style={styles.stopBtnText}>{isHausa ? 'Tsaya' : 'Stop'}</Text>
          </TouchableOpacity>
        </View>
      ) : voiceState === 'processing' ? (
        <View style={styles.recordingBar}>
          <ActivityIndicator size="small" color={Colors.primary} />
          <Text style={styles.recordingText}>{isHausa ? 'Ana sarrafa murya...' : 'Processing voice...'}</Text>
        </View>
      ) : (
        <View style={styles.footer}>
          <TextInput
            style={styles.input}
            value={input}
            onChangeText={setInput}
            placeholder={isHausa ? 'Tambaya game da gonarka…' : 'Ask about your farm…'}
            placeholderTextColor={Colors.textMuted}
            onSubmitEditing={send}
            returnKeyType="send"
            multiline
          />
          <TouchableOpacity style={styles.micBtn} onPress={startRecording}>
            <MaterialCommunityIcons name="microphone" size={20} color={Colors.textSecondary} />
          </TouchableOpacity>
          <TouchableOpacity style={styles.sendBtn} onPress={send} disabled={sending || !input.trim()}>
            <MaterialCommunityIcons name="send" size={20} color={Colors.white} />
          </TouchableOpacity>
        </View>
      )}
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  header: {
    backgroundColor: Colors.primary, paddingTop: 50, paddingBottom: Spacing.md, paddingHorizontal: Spacing.md,
    flexDirection: 'row', alignItems: 'center', gap: Spacing.sm,
  },
  back: { padding: 4 },
  headerTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  headerTitle: { ...Typography.h3, color: Colors.white, fontWeight: '700' },

  root: { flex: 1, backgroundColor: Colors.background },
  content: { padding: Spacing.md, paddingBottom: Spacing.lg, gap: Spacing.sm },

  bubble: { maxWidth: '85%', borderRadius: Radius.lg, padding: Spacing.sm + 2, ...Shadows.sm },
  bubbleBot: { backgroundColor: Colors.white, alignSelf: 'flex-start', borderBottomLeftRadius: 4 },
  bubbleUser: { backgroundColor: Colors.primary, alignSelf: 'flex-end', borderBottomRightRadius: 4 },
  userText: { ...Typography.small, color: Colors.white, lineHeight: 20 },

  sectionTitle: { ...Typography.label, color: Colors.primary, fontWeight: '800', marginBottom: 2 },
  sectionText: { ...Typography.small, color: Colors.textPrimary, lineHeight: 20 },
  sectionItem: { ...Typography.small, color: Colors.textPrimary, lineHeight: 20, marginTop: 2 },

  footer: {
    flexDirection: 'row', alignItems: 'flex-end', gap: Spacing.sm,
    padding: Spacing.sm, backgroundColor: Colors.white,
    borderTopWidth: 1, borderTopColor: Colors.border,
  },
  input: {
    flex: 1, backgroundColor: Colors.background, borderRadius: Radius.lg,
    borderWidth: 1.5, borderColor: Colors.border, paddingHorizontal: Spacing.sm,
    paddingVertical: 8, maxHeight: 100, color: Colors.textPrimary, ...Typography.small,
  },
  sendBtn: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: Colors.primary,
    alignItems: 'center', justifyContent: 'center',
  },
  micBtn: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: Colors.background,
    borderWidth: 1.5, borderColor: Colors.border,
    alignItems: 'center', justifyContent: 'center',
  },

  recordingBar: {
    flexDirection: 'row', alignItems: 'center', gap: Spacing.sm,
    padding: Spacing.md, backgroundColor: '#FEF2F2',
    borderTopWidth: 1, borderTopColor: '#FECACA',
  },
  recDot: { width: 10, height: 10, borderRadius: 5, backgroundColor: Colors.danger },
  recordingText: { ...Typography.small, color: '#991B1B', fontWeight: '700', flex: 1 },
  stopBtn: { backgroundColor: Colors.danger, borderRadius: Radius.md, paddingHorizontal: Spacing.md, paddingVertical: 8 },
  stopBtnText: { color: Colors.white, fontWeight: '700', fontSize: 12 },
});
