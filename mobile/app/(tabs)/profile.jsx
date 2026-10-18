import React, { useEffect, useState } from 'react';
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, Alert, Switch, Image, Linking } from 'react-native';
import Constants from 'expo-constants';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { useLanguage } from '../../context/LanguageContext';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';
import { Card, Button } from '../../components/UI';
import LanguagePicker from '../../components/LanguagePicker';
import { VoiceService } from '../../services/VoiceService';
import { speechAPI } from '../../lib/api';

export default function ProfileScreen() {
  const { t } = useTranslation();
  const router = useRouter();
  const { user, logout } = useAuth();
  const {
    currentLang, language,
    accessibilityMode, toggleAccessibility,
    voiceEnabled, toggleVoice,
    textSize, changeTextSize,
  } = useLanguage();

  const [pickerVisible, setPickerVisible] = useState(false);
  // Credits for the voices used by "Listen to your result" (licence terms,
  // e.g. CC-BY "Hausa voice: FarmerlineML"), from the server's provider list.
  const [voiceCredits, setVoiceCredits] = useState([]);
  useEffect(() => {
    let alive = true;
    speechAPI.languages()
      .then((res) => { if (alive) setVoiceCredits(res.credits || []); })
      .catch(() => {});
    return () => { alive = false; };
  }, []);

  const isHa = language === 'ha';

  // Same expert line the diagnosis screen uses (app/diagnosis/[id].jsx).
  const EXPERT_PHONE = '08129582957';
  const appVersion = Constants.expoConfig?.version;

  const MENU = [
    {
      icon: '📦',
      labelEn: 'My Orders',
      labelHa: 'Odar da na yi',
      // Order history was only reachable from the screen shown right after
      // a successful checkout.
      action: () => router.push('/market/orders'),
    },
    {
      icon: '🛒',
      labelEn: 'My Cart',
      labelHa: 'Kwandon sayayya',
      action: () => router.push('/market/cart'),
    },
    {
      icon: '🐄',
      labelEn: 'My Animals',
      labelHa: 'Dabbobin na',
      action: () => router.push('/(tabs)/records?tab=animals'),
    },
    {
      icon: '🌽',
      labelEn: 'My Crops',
      labelHa: 'Amfanin Gona',
      action: () => router.push('/(tabs)/records'),
    },
    {
      icon: '📊',
      labelEn: 'Health Reports',
      labelHa: 'Rahotannin Lafiya',
      action: () => router.push('/(tabs)/records'),
    },
    {
      icon: '📞',
      labelEn: 'Contact Expert Vet',
      labelHa: 'Tuntubi Likita',
      // Was "+234 800 FARMAI VET", which is not a dialable number.
      action: () => Alert.alert(
        isHa ? 'Layin Likitan Dabbobi' : 'MSAS Expert Line',
        `${isHa ? 'Kira' : 'Call'}: ${EXPERT_PHONE}`,
        [
          { text: isHa ? 'Soke' : 'Cancel', style: 'cancel' },
          { text: isHa ? 'Kira yanzu' : 'Call now', onPress: () => Linking.openURL(`tel:${EXPERT_PHONE}`) },
        ],
      ),
    },
    {
      icon: 'ℹ️',
      labelEn: 'About FarmAI',
      labelHa: 'Acikin FarmAI',
      action: () =>
        Alert.alert(
          appVersion ? `MSAS FarmAI v${appVersion}` : 'MSAS FarmAI',
          'AI-Powered Agricultural Health Platform for Katsina State, Nigeria.\n\nBuilt for smallholder farmers.',
          [
            { text: isHa ? 'Yabo da lasisi' : 'Credits and licences', onPress: () => router.push('/profile/credits') },
            { text: 'OK' },
          ],
        ),
    },
    {
      icon: '📜',
      labelEn: 'Credits and licences',
      labelHa: 'Yabo da lasisi',
      // Voice credits: OpenBible's CC BY-SA 4.0 licence requires attribution.
      action: () => router.push('/profile/credits'),
    },
  ];

  const fs = (base) => {
    if (textSize === 'large') return base + 3;
    if (textSize === 'xlarge') return base + 6;
    return base;
  };

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.content}>
      {/* Profile Header */}
      <View style={styles.header}>
        <View style={styles.avatar}>
          {user?.avatar_url ? (
            <Image source={{ uri: user.avatar_url }} style={styles.avatarImage} />
          ) : (
            <Text style={[styles.avatarText, { fontSize: fs(36) }]}>
              {(user?.display_first_name || user?.name || '?')[0]?.toUpperCase() || '?'}
            </Text>
          )}
        </View>
        <Text style={[styles.userName, { fontSize: fs(20) }]}>{user?.name || user?.display_first_name || 'User'}</Text>
        <Text style={[styles.userRole, { fontSize: fs(13) }]}>{user?.role_label || user?.role_display || user?.role || 'Member'}</Text>
        <Text style={[styles.userPhone, { fontSize: fs(14) }]}>{user?.phone || ''}</Text>
        <Text style={[styles.userEmail, { fontSize: fs(12) }]}>{user?.email || ''}</Text>
        <View style={styles.tagRow}>
          <View style={[styles.tag, { backgroundColor: Colors.accent }]}>
            <Text style={styles.tagText}>{user?.state || 'Katsina'}</Text>
          </View>
          <View style={[styles.tag, { backgroundColor: user?.is_verified ? Colors.primary : Colors.textMuted }]}>
            <Text style={styles.tagText}>{user?.is_verified ? '✓ Verified' : 'Unverified'}</Text>
          </View>
        </View>
        <TouchableOpacity style={styles.editProfileBtn} onPress={() => router.push('/profile/edit')}>
          <Text style={[styles.editProfileBtnText, { fontSize: fs(13) }]}>{isHa ? 'Gyara Bayanai' : 'Edit Profile'}</Text>
        </TouchableOpacity>
      </View>

      {/* Language Selector */}
      <Card style={styles.section}>
        <Text style={[styles.sectionTitle, { fontSize: fs(13) }]}>
          {isHa ? 'Harshe' : 'Language / Harshe'}
        </Text>
        <TouchableOpacity
          style={styles.langRow}
          onPress={() => setPickerVisible(true)}
          activeOpacity={0.8}
        >
          <Text style={styles.flagBig}>{currentLang.flag}</Text>
          <View style={{ flex: 1 }}>
            <Text style={[styles.langName, { fontSize: fs(15) }]}>{currentLang.nativeName}</Text>
            <Text style={[styles.langSub, { fontSize: fs(11) }]}>{currentLang.name}</Text>
          </View>
          <View style={styles.changePill}>
            <Text style={[styles.changePillText, { fontSize: fs(12) }]}>
              {isHa ? 'Canza' : 'Change'}
            </Text>
          </View>
        </TouchableOpacity>
        {language === 'ff' && (
          <Text style={[styles.ttsNote, { fontSize: fs(11) }]}>
            Voice assistance for Fulfulde uses English speech engine.
          </Text>
        )}
      </Card>

      {/* Accessibility & Voice */}
      <Card style={styles.section}>
        <Text style={[styles.sectionTitle, { fontSize: fs(13) }]}>
          {isHa ? 'Yanayin Amfani' : 'Accessibility & Voice'}
        </Text>

        <View style={styles.toggleRow}>
          <View style={{ flex: 1 }}>
            <Text style={[styles.toggleLabel, { fontSize: fs(14) }]}>
              {isHa ? 'Yanayin Sauƙi' : 'Accessibility Mode'}
            </Text>
            <Text style={[styles.toggleSub, { fontSize: fs(11) }]}>
              {isHa
                ? 'Babban rubutu, manyan abubuwa, murya farko'
                : 'Large text, large icons, voice-first navigation'}
            </Text>
          </View>
          <Switch
            value={accessibilityMode}
            onValueChange={toggleAccessibility}
            trackColor={{ false: Colors.border, true: Colors.primary }}
            thumbColor={Colors.white}
          />
        </View>

        <View style={[styles.toggleRow, { marginTop: 12 }]}>
          <View style={{ flex: 1 }}>
            <Text style={[styles.toggleLabel, { fontSize: fs(14) }]}>
              {isHa ? 'Muryan Sakamakon Bincike' : 'Voice Diagnosis Readout'}
            </Text>
            <Text style={[styles.toggleSub, { fontSize: fs(11) }]}>
              {isHa
                ? 'Karanta sakamakon binciken ta murya'
                : 'Automatically read diagnosis results aloud'}
            </Text>
          </View>
          <Switch
            value={voiceEnabled}
            onValueChange={toggleVoice}
            trackColor={{ false: Colors.border, true: Colors.primary }}
            thumbColor={Colors.white}
          />
        </View>

        {/* Text Size */}
        <View style={styles.textSizeRow}>
          <Text style={[styles.toggleLabel, { fontSize: fs(14), marginBottom: 8 }]}>
            {isHa ? 'Girman Rubutu' : 'Text Size'}
          </Text>
          <View style={styles.sizePills}>
            {['normal', 'large', 'xlarge'].map((s) => (
              <TouchableOpacity
                key={s}
                style={[styles.sizePill, textSize === s && styles.sizePillActive]}
                onPress={() => changeTextSize(s)}
              >
                <Text
                  style={[
                    styles.sizePillText,
                    textSize === s && styles.sizePillTextActive,
                    { fontSize: s === 'xlarge' ? 14 : s === 'large' ? 12 : 11 },
                  ]}
                >
                  {s === 'normal' ? 'A' : s === 'large' ? 'A+' : 'A++'}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* TTS preview */}
        <TouchableOpacity
          style={styles.testVoiceBtn}
          onPress={() => {
            const msg = {
              en: 'Voice assistance is working. Your diagnosis results will be read aloud in English.',
              ha: 'Muryan taimako yana aiki. Sakamakon binciken ku za a karanta da Hausa.',
              yo: 'Iranlọwọ ohun n ṣiṣẹ. Awọn abajade àyẹ̀wò rẹ yoo ka ni Yoruba.',
              ig: 'Enyemaka olu na-arụ ọrụ. A ga-agụ ihe nchọpụta gị n\'Igbo.',
              ff: 'Voice assistance is working in English for Fulfulde users.',
            };
            VoiceService.speak(msg[language] || msg.en, language);
          }}
        >
          <Text style={[styles.testVoiceTxt, { fontSize: fs(13) }]}>
            {isHa ? '🔊 Gwada Murya' : '🔊 Test Voice'}
          </Text>
        </TouchableOpacity>
      </Card>

      {/* Premium Banner */}
      {!user?.isPremium && (
        <Card style={styles.premiumCard}>
          <Text style={[styles.premiumTitle, { fontSize: fs(16) }]}>
            {isHa ? 'Zama Premium' : 'Upgrade to Premium'}
          </Text>
          <Text style={[styles.premiumSub, { fontSize: fs(13) }]}>
            {isHa
              ? 'Sami dubawa maras iyaka, tattaunawa da kwararre, da ƙari'
              : 'Unlimited scans, expert vet consultation, disease alerts & more'}
          </Text>
          <TouchableOpacity style={styles.premiumBtn}>
            <Text style={[styles.premiumBtnText, { fontSize: fs(13) }]}>
              {isHa ? 'Farawa daga ₦1,500/wata' : 'From ₦1,500/month'}
            </Text>
          </TouchableOpacity>
        </Card>
      )}

      {/* Menu */}
      <View style={styles.menu}>
        {MENU.map((item, i) => (
          <TouchableOpacity key={i} style={styles.menuItem} onPress={item.action}>
            <Text style={[styles.menuIcon, { fontSize: accessibilityMode ? 28 : 22 }]}>
              {item.icon}
            </Text>
            <Text style={[styles.menuLabel, { fontSize: fs(15) }]}>
              {isHa ? item.labelHa : item.labelEn}
            </Text>
            <Text style={[styles.menuArrow, { fontSize: fs(20) }]}>›</Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Logout */}
      <Button
        title={t('logout')}
        variant="danger"
        onPress={() =>
          Alert.alert(
            isHa ? 'Fita?' : 'Logout?',
            isHa
              ? 'Kuna so ku fita daga asusun ku?'
              : 'Are you sure you want to log out?',
            [
              { text: isHa ? "A'a" : 'Cancel' },
              { text: isHa ? 'Eh' : 'Logout', style: 'destructive', onPress: logout },
            ]
          )
        }
        style={styles.logoutBtn}
      />

      <Text style={[styles.version, { fontSize: fs(11) }]}>
        FarmAI v{appVersion || '—'} · {currentLang.nativeName} · Made for Katsina Farmers
      </Text>
      {voiceCredits.length > 0 && (
        <Text style={[styles.version, { fontSize: fs(10) }]} testID="voice-credits">
          {isHa ? 'Muryoyi' : 'Voices'}: {voiceCredits.map((c) => c.credit || `${c.label} (${c.license})`).join(' · ')}
        </Text>
      )}

      <LanguagePicker visible={pickerVisible} onClose={() => setPickerVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root:    { flex: 1, backgroundColor: Colors.background },
  content: { paddingBottom: 80 },
  header: {
    backgroundColor: Colors.primary,
    alignItems: 'center',
    paddingTop: 60,
    paddingBottom: Spacing.xl,
  },
  avatar: {
    width: 80, height: 80, borderRadius: 40,
    backgroundColor: Colors.accent,
    justifyContent: 'center', alignItems: 'center',
    marginBottom: Spacing.sm,
  },
  avatarImage: { width: 80, height: 80, borderRadius: 40 },
  avatarText:  { fontWeight: '800', color: Colors.white },
  userName:    { fontWeight: '800', color: Colors.white },
  userRole:    { color: 'rgba(255,255,255,0.8)', marginTop: 2, fontWeight: '600' },
  userPhone:   { color: 'rgba(255,255,255,0.7)', marginTop: 2 },
  userEmail:   { color: 'rgba(255,255,255,0.6)', marginTop: 2 },
  tagRow:      { flexDirection: 'row', gap: Spacing.sm, marginTop: Spacing.sm },
  tag: { paddingHorizontal: Spacing.sm, paddingVertical: 2, borderRadius: Radius.full },
  tagText:     { fontSize: 11, color: Colors.white, fontWeight: '700' },
  editProfileBtn: {
    marginTop: Spacing.md, backgroundColor: 'rgba(255,255,255,0.18)',
    borderRadius: Radius.full, paddingHorizontal: Spacing.md, paddingVertical: 6,
    borderWidth: 1, borderColor: 'rgba(255,255,255,0.4)',
  },
  editProfileBtnText: { color: Colors.white, fontWeight: '700' },

  section: { margin: Spacing.md },
  sectionTitle: {
    fontWeight: '700',
    color: Colors.textMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    marginBottom: 10,
  },

  langRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8F9FA',
    borderRadius: 10,
    padding: 12,
    gap: 12,
  },
  flagBig:   { fontSize: 28 },
  langName:  { fontWeight: '700', color: '#1A1A2E' },
  langSub:   { color: '#888', marginTop: 2 },
  changePill: {
    backgroundColor: Colors.primary,
    borderRadius: 14,
    paddingHorizontal: 12,
    paddingVertical: 5,
  },
  changePillText: { color: '#fff', fontWeight: '700' },
  ttsNote: { color: '#856404', backgroundColor: '#FFF3CD', padding: 8, borderRadius: 6, marginTop: 8 },

  toggleRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  toggleLabel: { fontWeight: '600', color: Colors.textPrimary },
  toggleSub:   { color: Colors.textMuted, marginTop: 2 },

  textSizeRow:    { marginTop: 14 },
  sizePills:      { flexDirection: 'row', gap: 8 },
  sizePill: {
    flex: 1,
    paddingVertical: 9,
    borderRadius: 8,
    backgroundColor: '#F0F0F0',
    alignItems: 'center',
  },
  sizePillActive:      { backgroundColor: Colors.primary },
  sizePillText:        { color: '#555', fontWeight: '700' },
  sizePillTextActive:  { color: '#fff' },

  testVoiceBtn: {
    marginTop: 14,
    borderWidth: 1.5,
    borderColor: Colors.primary,
    borderRadius: 8,
    paddingVertical: 10,
    alignItems: 'center',
  },
  testVoiceTxt: { color: Colors.primary, fontWeight: '700' },

  premiumCard:    { margin: Spacing.md, marginTop: 0, backgroundColor: '#1a0a3e' },
  premiumTitle:   { color: '#F0C040', fontWeight: '800' },
  premiumSub:     { color: 'rgba(255,255,255,0.7)', marginTop: 4 },
  premiumBtn: {
    backgroundColor: Colors.accent,
    borderRadius: Radius.md,
    padding: Spacing.sm,
    marginTop: Spacing.sm,
    alignItems: 'center',
  },
  premiumBtnText: { color: Colors.white, fontWeight: '700' },

  menu: {
    backgroundColor: Colors.white,
    marginHorizontal: Spacing.md,
    borderRadius: Radius.lg,
    overflow: 'hidden',
    ...Shadows.sm,
  },
  menuItem: {
    flexDirection: 'row', alignItems: 'center',
    padding: Spacing.md,
    borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  menuIcon:  { marginRight: Spacing.md },
  menuLabel: { color: Colors.textPrimary, flex: 1 },
  menuArrow: { color: Colors.textMuted },

  logoutBtn: { margin: Spacing.md, marginTop: Spacing.lg },
  version:   { color: Colors.textMuted, textAlign: 'center', paddingBottom: Spacing.md },
});
