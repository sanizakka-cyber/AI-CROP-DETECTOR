import React, { useState, useRef } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  ScrollView, KeyboardAvoidingView, Platform, ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
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

  const send = async () => {
    const msg = input.trim();
    if (!msg || sending) return;
    setInput('');
    setMessages(prev => [...prev, { role: 'user', text: msg }]);
    setSending(true);

    try {
      const data = await aiAPI.chat(msg, historyRef.current, i18n.language);
      if (data.history) historyRef.current = typeof data.history === 'string' ? data.history : JSON.stringify(data.history);
      setMessages(prev => [...prev, { role: 'bot', text: data.reply, sections: data.sections }]);
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
        <TouchableOpacity style={styles.sendBtn} onPress={send} disabled={sending || !input.trim()}>
          <MaterialCommunityIcons name="send" size={20} color={Colors.white} />
        </TouchableOpacity>
      </View>
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
});
