/**
 * Credits and licences for the voices used by "Listen to your result".
 *
 * OpenBible voices are CC BY-SA 4.0, which requires attribution wherever
 * they are used, so the launch credits are built into the app (shown even
 * offline) and merged with the list the server sends for the voices it
 * currently uses (GET /speech/languages -> credits).
 */
import React, { useEffect, useState } from 'react';
import { Linking, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { speechAPI } from '../../lib/api';
import { useLanguage } from '../../context/LanguageContext';
import { Colors, Spacing } from '../../constants/Theme';

// The voices MSAS FarmAI launches with (docs/listen-to-result.md, Decisions).
export const BUILT_IN_CREDITS = [
  {
    provider: 'openbible',
    label: 'Hausa voice: OpenBibleTTS',
    credit: 'Voices: OpenBibleTTS (McGill-NLP, Mila), trained from scratch on Open Bible recordings (CC BY-SA 4.0)',
    license: 'CC-BY-SA-4.0',
    licenseUrl: 'https://creativecommons.org/licenses/by-sa/4.0/',
    source: 'https://huggingface.co/multilingual-tts',
  },
  {
    provider: 'kokoro',
    label: 'English voice: Kokoro-82M',
    credit: 'English voice: Kokoro-82M by hexgrad (Apache License 2.0)',
    license: 'Apache-2.0',
    licenseUrl: 'https://www.apache.org/licenses/LICENSE-2.0',
    source: 'https://huggingface.co/hexgrad/Kokoro-82M',
  },
];

/** Built-in credits first, then any other provider the server reports. */
export function mergeCredits(server) {
  const seen = new Set(BUILT_IN_CREDITS.map((c) => c.provider));
  return [...BUILT_IN_CREDITS, ...(server || []).filter((c) => c && !seen.has(c.provider))];
}

export default function CreditsScreen() {
  const { language } = useLanguage();
  const isHa = language === 'ha';
  const [credits, setCredits] = useState(mergeCredits([]));

  useEffect(() => {
    let alive = true;
    speechAPI.languages()
      .then((res) => { if (alive) setCredits(mergeCredits(res.credits)); })
      .catch(() => {});
    return () => { alive = false; };
  }, []);

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.content} testID="credits-screen">
      <Text style={styles.title}>{isHa ? 'Yabo da lasisi' : 'Credits and licences'}</Text>
      <Text style={styles.intro}>
        {isHa
          ? 'Muryoyin da ke karanta sakamakonka sun fito daga waɗannan ayyukan buɗaɗɗen tushe:'
          : 'The voices that read your results aloud come from these open-source projects:'}
      </Text>
      {credits.map((c) => (
        <View key={c.provider} style={styles.card} testID={`credit-${c.provider}`}>
          <Text style={styles.label}>{c.label}</Text>
          {!!c.credit && <Text style={styles.body}>{c.credit}</Text>}
          <Text style={styles.body}>{isHa ? 'Lasisi' : 'Licence'}: {c.license}</Text>
          {!!c.licenseUrl && (
            <TouchableOpacity onPress={() => Linking.openURL(c.licenseUrl)}>
              <Text style={styles.link}>{c.licenseUrl}</Text>
            </TouchableOpacity>
          )}
          {!!c.source && (
            <TouchableOpacity onPress={() => Linking.openURL(c.source)}>
              <Text style={styles.link}>{c.source}</Text>
            </TouchableOpacity>
          )}
        </View>
      ))}
      <Text style={styles.note}>
        {isHa
          ? 'Ba a canza muryoyin ba. MSAS FarmAI ba ta da alaƙa da waɗannan ayyukan.'
          : 'The voice models are used unmodified. MSAS FarmAI is not affiliated with these projects.'}
      </Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root:    { flex: 1, backgroundColor: Colors.background },
  content: { padding: Spacing.md, paddingBottom: 60 },
  title:   { fontSize: 20, fontWeight: '800', color: Colors.textPrimary, marginBottom: Spacing.sm },
  intro:   { fontSize: 14, color: Colors.textSecondary, marginBottom: Spacing.md },
  card:    { backgroundColor: '#fff', borderRadius: 12, padding: Spacing.md, marginBottom: Spacing.sm, borderWidth: 1, borderColor: '#E5E7EB' },
  label:   { fontSize: 15, fontWeight: '700', color: Colors.textPrimary, marginBottom: 4 },
  body:    { fontSize: 13, color: Colors.textSecondary, marginBottom: 4 },
  link:    { fontSize: 13, color: Colors.primary, textDecorationLine: 'underline', marginBottom: 4 },
  note:    { fontSize: 12, color: Colors.textMuted, marginTop: Spacing.md },
});
