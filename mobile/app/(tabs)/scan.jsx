import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ScrollView } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';

const SCAN_OPTIONS = [
  {
    id: 'crop',
    icon: 'sprout',
    titleEn: 'Plant / Crop',
    titleHa: 'Shuka / Amfanin Gona',
    descEn: 'Auto-detect species & disease',
    descHa: 'Gano nau\'in shuka da cuta kai tsaye',
    color: '#D1FAE5',
    accent: Colors.primary,
    to: '/scan/crop',
  },
  {
    id: 'livestock',
    icon: 'cow',
    titleEn: 'Livestock',
    titleHa: 'Dabbobi',
    descEn: 'Auto-detect breed & condition',
    descHa: 'Gano irin dabba da yanayin lafiya',
    color: '#FEF3C7',
    accent: '#D97706',
    to: '/scan/livestock',
  },
  {
    id: 'soil',
    icon: 'shovel',
    titleEn: 'Soil Sample',
    titleHa: 'Samfurin Ƙasa',
    descEn: 'Nutrients, pH & recommendations',
    descHa: 'Abinci, pH da shawarwari',
    color: '#E7E0D3',
    accent: '#92651A',
    to: '/scan/soil',
  },
  {
    id: 'pest',
    icon: 'bug',
    titleEn: 'Pest ID',
    titleHa: 'Gano Kwari',
    descEn: 'Insects, weeds & pathogens',
    descHa: 'Kwari, ciyayi da cututtuka',
    color: '#FEE2E2',
    accent: '#DC2626',
    to: '/scan/pest',
  },
];

export default function ScanMenuScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.content}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>{t('scan')}</Text>
        <Text style={styles.headerSub}>
          {isHausa ? 'Me kake son bincika?' : 'What are you scanning?'}
        </Text>
      </View>

      {SCAN_OPTIONS.map(opt => (
        <TouchableOpacity
          key={opt.id}
          style={[styles.card, { backgroundColor: opt.color }]}
          onPress={() => router.push(opt.to)}
          activeOpacity={0.85}
        >
          <View style={[styles.iconWrap, { backgroundColor: opt.accent }]}>
            <MaterialCommunityIcons name={opt.icon} size={28} color={Colors.white} />
          </View>
          <View style={styles.cardBody}>
            <Text style={[styles.cardTitle, { color: opt.accent }]}>{isHausa ? opt.titleHa : opt.titleEn}</Text>
            <Text style={styles.cardDesc}>{isHausa ? opt.descHa : opt.descEn}</Text>
          </View>
          <MaterialCommunityIcons name="chevron-right" size={26} color={Colors.textMuted} />
        </TouchableOpacity>
      ))}

      {/* How it works */}
      <View style={styles.howCard}>
        <View style={styles.howTitleRow}>
          <MaterialCommunityIcons name="camera-outline" size={18} color={Colors.primary} />
          <Text style={styles.howTitle}>{isHausa ? 'Yadda ake amfani' : 'How it works'}</Text>
        </View>
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
  iconWrap: {
    width: 52, height: 52, borderRadius: Radius.lg,
    alignItems: 'center', justifyContent: 'center',
    marginRight: Spacing.md,
  },
  cardBody:  { flex: 1 },
  cardTitle: { ...Typography.h3, fontWeight: '700' },
  cardDesc:  { ...Typography.small, color: Colors.textSecondary, marginTop: 4 },
  howCard: {
    backgroundColor: Colors.white,
    borderRadius: Radius.lg,
    padding: Spacing.lg,
    marginTop: Spacing.md,
    ...Shadows.sm,
  },
  howTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: Spacing.sm },
  howTitle: { ...Typography.h3, color: Colors.primary },
  howStep:  { ...Typography.body, color: Colors.textSecondary, marginBottom: Spacing.xs },
});
