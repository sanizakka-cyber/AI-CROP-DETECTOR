import React, { useState, useEffect } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, Image,
  StyleSheet, ScrollView, KeyboardAvoidingView,
  Platform, Alert, StatusBar,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import '../../lib/i18n';
import { useAuth } from '../../context/AuthContext';
import { Button } from '../../components/UI';
import ServerStatus from '../../components/ServerStatus';

// ── Brand palette ─────────────────────────────────
const C = {
  navy:      '#0B2447',
  green:     '#0F6B3E',
  greenLt:   '#1FA84A',
  blue:      '#2D9CDB',
  gold:      '#F4A300',
  white:     '#FFFFFF',
  bg:        '#F1F5F9',
  textDark:  '#0F172A',
  textMid:   '#475569',
  textLight: '#94A3B8',
  border:    '#E2E8F0',
  cardBg:    '#F8FAFC',
};

const SAVED_ACCOUNTS_KEY = 'msas_saved_accounts';

export default function LoginScreen() {
  const { t, i18n } = useTranslation();
  const { login } = useAuth();
  const router = useRouter();
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [remember, setRemember] = useState(false);
  const [showPass, setShowPass] = useState(false);
  const [savedAccounts, setSavedAccounts] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const isHausa = i18n.language === 'ha';

  // Load saved accounts from AsyncStorage on mount
  useEffect(() => {
    AsyncStorage.getItem(SAVED_ACCOUNTS_KEY).then(raw => {
      try { if (raw) setSavedAccounts(JSON.parse(raw)); }
      catch {}
    });
  }, []);

  const persistAccount = async (identifier) => {
    if (!identifier) return;
    const updated = [identifier, ...savedAccounts.filter(a => a !== identifier)].slice(0, 5);
    setSavedAccounts(updated);
    await AsyncStorage.setItem(SAVED_ACCOUNTS_KEY, JSON.stringify(updated));
  };

  const clearSavedAccounts = async () => {
    setSavedAccounts([]);
    setShowSuggestions(false);
    await AsyncStorage.removeItem(SAVED_ACCOUNTS_KEY);
  };

  const handleLogin = async () => {
    if (!phone || !password)
      return Alert.alert('', isHausa ? 'Shigar da bayanai duka' : 'Please enter phone and password');
    setLoading(true);
    try {
      await login(phone, password, remember);
      if (remember) await persistAccount(phone.trim());
    } catch (e) {
      Alert.alert(isHausa ? 'Kuskure' : 'Login Failed', e.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: C.navy }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <StatusBar barStyle="light-content" backgroundColor={C.navy} />
      <ServerStatus />
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

        {/* ── HERO HEADER ─────────────────────────────── */}
        <View style={styles.hero}>

          {/* Decorative blobs */}
          <View style={[styles.blob, { width: 220, height: 220, backgroundColor: C.greenLt, top: -60, right: -60, opacity: 0.15 }]} />
          <View style={[styles.blob, { width: 140, height: 140, backgroundColor: C.blue, bottom: 20, left: -40, opacity: 0.12 }]} />
          <View style={[styles.blob, { width: 90, height: 90, backgroundColor: C.gold, top: 60, left: 20, opacity: 0.1 }]} />

          {/* Language switch */}
          <TouchableOpacity style={styles.langBtn} onPress={() => i18n.changeLanguage(isHausa ? 'en' : 'ha')}>
            <Text style={styles.langBtnText}>{isHausa ? '🇬🇧 EN' : '🇳🇬 HA'}</Text>
          </TouchableOpacity>

          {/* MSAS Logo image */}
          <View style={styles.logoWrap}>
            <Image
              source={require('../../assets/images/msas_logo.png')}
              style={styles.logoImg}
              resizeMode="cover"
            />
          </View>

          <Text style={styles.brandName}>MSAS FarmAI</Text>
          <Text style={styles.brandSub}>Livestock & Agro Services Platform</Text>

          {/* Secured badge only */}
          <View style={styles.securedPill}>
            <Text style={styles.securedText}>🔒 {isHausa ? 'Amintacce' : 'Secured'}</Text>
          </View>

          {/* Stats row */}
          <View style={styles.statsRow}>
            <View style={[styles.statPill, { borderColor: C.greenLt + '55', backgroundColor: C.greenLt + '18' }]}>
              <Text style={[styles.statNum, { color: C.greenLt }]}>14</Text>
              <Text style={styles.statLabel}>{isHausa ? 'Matsayi' : 'Roles'}</Text>
            </View>
            <View style={[styles.statPill, { borderColor: C.blue + '55', backgroundColor: C.blue + '18' }]}>
              <Text style={[styles.statNum, { color: C.blue }]}>AI</Text>
              <Text style={styles.statLabel}>{isHausa ? 'Na kishin gona' : 'Powered'}</Text>
            </View>
            <View style={[styles.statPill, { borderColor: C.gold + '55', backgroundColor: C.gold + '18' }]}>
              <Text style={[styles.statNum, { color: C.gold }]}>36+</Text>
              <Text style={styles.statLabel}>{isHausa ? 'Jihohi' : 'States'}</Text>
            </View>
          </View>
        </View>

        {/* ── FORM CARD ─────────────────────────────── */}
        <View style={styles.card}>

          {/* Card top accent strip */}
          <View style={styles.cardAccent} />

          <Text style={styles.cardTitle}>{isHausa ? 'Shiga Asusunka' : 'Welcome Back'}</Text>
          <Text style={styles.cardSubtitle}>{isHausa ? 'Shigar da bayananka don shiga' : 'Sign in to your MSAS portal account'}</Text>

          {/* Phone */}
          <View style={styles.fieldWrap}>
            <Text style={styles.fieldLabel}>{isHausa ? 'LAMBAR WAYA' : 'PHONE NUMBER'}</Text>
            <View style={styles.inputWrap}>
              <View style={styles.inputIcon}>
                <Text style={styles.inputIconText}>📞</Text>
              </View>
              <TextInput
                style={styles.input}
                value={phone}
                onChangeText={(v) => { setPhone(v); if (v) setShowSuggestions(false); }}
                onFocus={() => { if (savedAccounts.length > 0 && !phone) setShowSuggestions(true); }}
                keyboardType="phone-pad"
                placeholder=""
                placeholderTextColor={C.textLight}
                autoComplete="off"
                autoCorrect={false}
                autoCapitalize="none"
                textContentType="none"
              />
            </View>

            {/* Saved account suggestions */}
            {showSuggestions && savedAccounts.length > 0 && (
              <View style={styles.suggestBox}>
                <View style={styles.suggestHeader}>
                  <Text style={styles.suggestHeaderText}>
                    {isHausa ? 'Asusun da aka adana' : 'Saved accounts'}
                  </Text>
                  <TouchableOpacity onPress={clearSavedAccounts}>
                    <Text style={styles.suggestClear}>{isHausa ? 'Goge duka' : 'Clear all'}</Text>
                  </TouchableOpacity>
                </View>
                {savedAccounts.map((acc, i) => (
                  <TouchableOpacity
                    key={i}
                    style={[styles.suggestItem, i < savedAccounts.length - 1 && styles.suggestItemBorder]}
                    onPress={() => { setPhone(acc); setShowSuggestions(false); }}
                    activeOpacity={0.7}
                  >
                    <Text style={styles.suggestIcon}>👤</Text>
                    <Text style={styles.suggestText}>{acc}</Text>
                    <Text style={styles.suggestArrow}>›</Text>
                  </TouchableOpacity>
                ))}
              </View>
            )}
          </View>

          {/* Password */}
          <View style={styles.fieldWrap}>
            <Text style={styles.fieldLabel}>{isHausa ? 'KALMAR SIRRI' : 'PASSWORD'}</Text>
            <View style={styles.inputWrap}>
              <View style={styles.inputIcon}>
                <Text style={styles.inputIconText}>🔒</Text>
              </View>
              <TextInput
                style={[styles.input, { flex: 1 }]}
                value={password}
                onChangeText={setPassword}
                secureTextEntry={!showPass}
                placeholder=""
                placeholderTextColor={C.textLight}
                autoComplete="off"
                textContentType="none"
              />
              <TouchableOpacity onPress={() => setShowPass(v => !v)} style={styles.eyeBtn}>
                <Text style={styles.eyeText}>{showPass ? '👁' : '🙈'}</Text>
              </TouchableOpacity>
            </View>
          </View>

          {/* Remember me */}
          <TouchableOpacity style={styles.rememberRow} onPress={() => setRemember(r => !r)} activeOpacity={0.7}>
            <View style={[styles.checkbox, remember && styles.checkboxOn]}>
              {remember && <Text style={styles.checkmark}>✓</Text>}
            </View>
            <Text style={styles.rememberText}>{isHausa ? 'Kiyaye ni a shiga' : 'Keep me signed in'}</Text>
          </TouchableOpacity>

          {/* Submit */}
          <TouchableOpacity
            style={[styles.submitBtn, loading && { opacity: 0.7 }]}
            onPress={handleLogin}
            disabled={loading}
            activeOpacity={0.85}
          >
            <Text style={styles.submitText}>
              {loading ? (isHausa ? 'Ana shiga...' : 'Signing in…') : (isHausa ? 'Shiga' : 'Sign In Securely')}
            </Text>
          </TouchableOpacity>

          {/* Feature highlights */}
          <View style={styles.featureRow}>
            {[
              { icon: '🌾', label: isHausa ? 'AI na gona' : 'AI Crop Scan', color: C.greenLt },
              { icon: '🐄', label: isHausa ? 'Dabbobi' : 'Livestock', color: C.blue },
              { icon: '📊', label: isHausa ? 'Rahotanni' : 'Analytics', color: C.gold },
            ].map((f, i) => (
              <View key={i} style={[styles.featureCard, { borderColor: f.color + '40' }]}>
                <View style={[styles.featureIcon, { backgroundColor: f.color + '20' }]}>
                  <Text style={{ fontSize: 16 }}>{f.icon}</Text>
                </View>
                <Text style={[styles.featureLabel, { color: f.color }]}>{f.label}</Text>
              </View>
            ))}
          </View>

          {/* Sign up link */}
          <View style={styles.switchRow}>
            <Text style={styles.switchPrompt}>{isHausa ? 'Ba ku da asusun?' : "Don't have an account?"}</Text>
            <TouchableOpacity onPress={() => router.push('/(auth)/register')}>
              <Text style={styles.switchLink}>{isHausa ? ' Yi Rajista' : ' Create Account'}</Text>
            </TouchableOpacity>
          </View>

        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flexGrow: 1 },

  // ── Hero ─────────────────────────────────────────
  hero: {
    backgroundColor: C.navy,
    paddingTop: 56,
    paddingBottom: 32,
    paddingHorizontal: 24,
    alignItems: 'center',
    position: 'relative',
    overflow: 'hidden',
  },
  blob: { position: 'absolute', borderRadius: 999 },
  langBtn: {
    position: 'absolute', top: 16, right: 20,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 20, paddingHorizontal: 12, paddingVertical: 6,
    borderWidth: 1, borderColor: 'rgba(255,255,255,0.15)',
  },
  langBtnText: { color: C.gold, fontWeight: '700', fontSize: 13 },

  logoWrap: { marginBottom: 14, alignItems: 'center' },
  logoImg: {
    width: 100, height: 100, borderRadius: 18,
    shadowColor: '#000', shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.4, shadowRadius: 16, elevation: 12,
    borderWidth: 2, borderColor: 'rgba(255,255,255,0.15)',
  },

  brandName: { color: C.white, fontSize: 24, fontWeight: '800', letterSpacing: -0.5, marginBottom: 4 },
  brandSub: { color: 'rgba(255,255,255,0.55)', fontSize: 12, fontWeight: '500', marginBottom: 14 },

  securedPill: {
    backgroundColor: 'rgba(45,156,219,0.18)',
    borderRadius: 20, paddingHorizontal: 14, paddingVertical: 5,
    borderWidth: 1, borderColor: 'rgba(45,156,219,0.35)',
    marginBottom: 20,
  },
  securedText: { color: '#2D9CDB', fontSize: 11, fontWeight: '700' },

  statsRow: { flexDirection: 'row', gap: 10 },
  statPill: {
    flex: 1, alignItems: 'center', paddingVertical: 10, paddingHorizontal: 8,
    borderRadius: 12, borderWidth: 1,
  },
  statNum: { fontSize: 18, fontWeight: '800', lineHeight: 22 },
  statLabel: { color: 'rgba(255,255,255,0.55)', fontSize: 10, fontWeight: '600', marginTop: 2 },

  // ── Card ─────────────────────────────────────────
  card: {
    backgroundColor: C.white,
    borderTopLeftRadius: 28, borderTopRightRadius: 28,
    paddingHorizontal: 24, paddingBottom: 40,
    paddingTop: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.08, shadowRadius: 20, elevation: 10,
  },
  cardAccent: {
    height: 4, borderRadius: 2,
    backgroundColor: C.green,
    marginBottom: 24, marginTop: 8,
    // Rainbow effect via multiple strips — RN can't do CSS gradients, so we use a colored bar
    // Full gradient: applied as overlapping Views
  },

  cardTitle: { fontSize: 22, fontWeight: '800', color: C.textDark, marginBottom: 4, letterSpacing: -0.3 },
  cardSubtitle: { fontSize: 13, color: C.textMid, marginBottom: 24 },

  // ── Fields ───────────────────────────────────────
  fieldWrap: { marginBottom: 14 },
  fieldLabel: { fontSize: 10, fontWeight: '700', color: C.textMid, letterSpacing: 0.08, marginBottom: 6, textTransform: 'uppercase' },
  inputWrap: {
    flexDirection: 'row', alignItems: 'center',
    borderWidth: 1.5, borderColor: C.border,
    borderRadius: 10, backgroundColor: C.cardBg, overflow: 'hidden',
  },
  inputIcon: { paddingHorizontal: 11, paddingVertical: 12 },
  inputIconText: { fontSize: 16 },
  input: {
    flex: 1, paddingVertical: 12, paddingRight: 12,
    fontSize: 14, color: C.textDark,
  },
  eyeBtn: { paddingHorizontal: 12 },
  eyeText: { fontSize: 16 },

  // ── Remember me ──────────────────────────────────
  rememberRow: { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 20 },
  checkbox: {
    width: 20, height: 20, borderRadius: 5,
    borderWidth: 1.5, borderColor: C.border,
    backgroundColor: C.cardBg, alignItems: 'center', justifyContent: 'center',
  },
  checkboxOn: { backgroundColor: C.green, borderColor: C.green },
  checkmark: { color: C.white, fontSize: 12, fontWeight: '800' },
  rememberText: { fontSize: 13, color: C.textMid, fontWeight: '500' },

  // ── Submit ───────────────────────────────────────
  submitBtn: {
    backgroundColor: C.green,
    borderRadius: 12, paddingVertical: 15,
    alignItems: 'center', marginBottom: 20,
    shadowColor: C.green,
    shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.35, shadowRadius: 14, elevation: 8,
  },
  submitText: { color: C.white, fontSize: 15, fontWeight: '800', letterSpacing: 0.3 },

  // ── Feature row ──────────────────────────────────
  featureRow: { flexDirection: 'row', gap: 8, marginBottom: 24 },
  featureCard: {
    flex: 1, alignItems: 'center', paddingVertical: 12, paddingHorizontal: 6,
    borderRadius: 12, borderWidth: 1, backgroundColor: '#f8fafc',
  },
  featureIcon: { width: 32, height: 32, borderRadius: 8, alignItems: 'center', justifyContent: 'center', marginBottom: 6 },
  featureLabel: { fontSize: 10, fontWeight: '700', textAlign: 'center' },

  // ── Switch link ───────────────────────────────────
  switchRow: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center' },
  switchPrompt: { fontSize: 13, color: C.textLight },
  switchLink: { fontSize: 13, color: C.green, fontWeight: '800' },

  // ── Saved account suggestions ─────────────────────
  suggestBox: {
    marginTop: 6,
    backgroundColor: C.white,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: C.border,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.07,
    shadowRadius: 10,
    elevation: 4,
  },
  suggestHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
    backgroundColor: C.cardBg,
  },
  suggestHeaderText: { fontSize: 10, fontWeight: '700', color: C.textLight, textTransform: 'uppercase', letterSpacing: 0.5 },
  suggestClear: { fontSize: 11, fontWeight: '600', color: '#94a3b8' },
  suggestItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 11,
    backgroundColor: C.white,
  },
  suggestItemBorder: { borderBottomWidth: 1, borderBottomColor: '#f8fafc' },
  suggestIcon: { fontSize: 14, marginRight: 10 },
  suggestText: { flex: 1, fontSize: 13, fontWeight: '600', color: C.textDark },
  suggestArrow: { fontSize: 18, color: C.textLight, fontWeight: '300' },
});
