import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ScrollView } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';

const SCAN_OPTIONS = [
  {
    id: 'crop',
    icon: '🌽',
    titleKey: 'scanCrop',
    descEn: 'Identify plant diseases, nutrient deficiencies, and pest damage',
    descHa: 'Gano cututtukan shuka, karancin abinci, da ƙwari',
    color: '#D1FAE5',
    accent: Colors.primary,
    to: '/scan/crop',
  },
  {
    id: 'livestock',
    icon: '🐄',
    titleKey: 'scanAnimal',
    descEn: 'Analyse stool samples or visible symptoms for livestock health',
    descHa: 'Bincika najasa ko alamomi masu ganuwa na lafiyar dabbobi',
    color: '#FEF3C7',
    accent: '#D97706',
    to: '/scan/livestock',
  },
];

export default function ScanMenuScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.content}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>🔬 {t('scan')}</Text>
        <Text style={styles.headerSub}>
          {isHausa ? 'Zaɓi nau\'in dubawa' : 'Choose what to scan'}
        </Text>
      </View>

      {SCAN_OPTIONS.map(opt => (
        <TouchableOpacity
          key={opt.id}
          style={[styles.card, { backgroundColor: opt.color }]}
          onPress={() => router.push(opt.to)}
          activeOpacity={0.85}
        >
          <Text style={styles.cardIcon}>{opt.icon}</Text>
          <View style={styles.cardBody}>
            <Text style={[styles.cardTitle, { color: opt.accent }]}>{t(opt.titleKey)}</Text>
            <Text style={styles.cardDesc}>{isHausa ? opt.descHa : opt.descEn}</Text>
          </View>
          <Text style={styles.arrow}>›</Text>
        </TouchableOpacity>
      ))}

      {/* How it works */}
      <View style={styles.howCard}>
        <Text style={styles.howTitle}>
          {isHausa ? '📸 Yadda ake amfani' : '📸 How it works'}
        </Text>
        {[
          isHausa ? '1. Zaɓi nau\'in dubawa' : '1. Choose scan type',
          isHausa ? '2. Ɗauki ko zaɓi hoto' : '2. Take or choose a photo',
          isHausa ? '3. AI yana bincikawa nan da nan' : '3. AI analyses instantly',
          isHausa ? '4. Sami magani da shawarwari' : '4. Get remedies & advice',
        ].map((step, i) => (
          <Text key={i} style={styles.howStep}>{step}</Text>
        ))}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root:    { flex: 1, backgroundColor: Colors.background },
  content: { padding: Spacing.md, paddingBottom: 80 },
  header:  { paddingTop: 50, paddingBottom: Spacing.lg },
  headerTitle: { ...Typography.h1, color: Colors.textPrimary },
  headerSub:   { ...Typography.body, color: Colors.textSecondary, marginTop: 4 },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: Radius.xl,
    padding: Spacing.lg,
    marginBottom: Spacing.md,
    ...Shadows.md,
  },
  cardIcon:  { fontSize: 52, marginRight: Spacing.md },
  cardBody:  { flex: 1 },
  cardTitle: { ...Typography.h3, fontWeight: '700' },
  cardDesc:  { ...Typography.small, color: Colors.textSecondary, marginTop: 4 },
  arrow:     { fontSize: 28, color: Colors.textMuted },
  howCard: {
    backgroundColor: Colors.white,
    borderRadius: Radius.lg,
    padding: Spacing.lg,
    marginTop: Spacing.md,
    ...Shadows.sm,
  },
  howTitle: { ...Typography.h3, color: Colors.primary, marginBottom: Spacing.sm },
  howStep:  { ...Typography.body, color: Colors.textSecondary, marginBottom: Spacing.xs },
});
