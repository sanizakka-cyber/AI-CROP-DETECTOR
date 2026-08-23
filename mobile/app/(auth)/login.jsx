import React, { useState, useEffect } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, Image,
  StyleSheet, ScrollView, KeyboardAvoidingView,
  Platform, Alert, StatusBar, ActivityIndicator,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import * as WebBrowser from 'expo-web-browser';
import '../../lib/i18n';
import { useAuth } from '../../context/AuthContext';
import ServerStatus from '../../components/ServerStatus';

// Same root as lib/api.js's BASE_URL, without the /api suffix — password
// reset is a full HTML flow (request link -> email -> set new password),
// not a JSON endpoint, so it's reused as-is in an in-app browser instead
// of being reimplemented as separate mobile-native business logic.
const WEB_ROOT = (process.env.EXPO_PUBLIC_API_URL || 'https://www.msasagro.com/api').replace(/\/api\/?$/, '');

// ── Brand palette ─────────────────────────────────
const C = {
  navy:      '#0B2447',
  green:     '#0F6B3E',
  greenLt:   '#1FA84A',
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
  const { login, sessionExpired } = useAuth();
  const router = useRouter();
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [remember, setRemember] = useState(false);
  const [showPass, setShowPass] = useState(false);
  const [savedAccounts, setSavedAccounts] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const isHausa = i18n.language === 'ha';

  useEffect(() => {
    AsyncStorage.getItem(SAVED_ACCOUNTS_KEY).then(raw => {
      try { if (raw) setSavedAccounts(JSON.parse(raw)); }
      catch {}
    });
  }, []);

  const persistAccount = async (id) => {
    if (!id) return;
    const updated = [id, ...savedAccounts.filter(a => a !== id)].slice(0, 5);
    setSavedAccounts(updated);
    await AsyncStorage.setItem(SAVED_ACCOUNTS_KEY, JSON.stringify(updated));
  };

  const clearSavedAccounts = async () => {
    setSavedAccounts([]);
    setShowSuggestions(false);
    await AsyncStorage.removeItem(SAVED_ACCOUNTS_KEY);
  };

  const handleLogin = async () => {
    if (!identifier || !password)
      return Alert.alert('', isHausa ? 'Shigar da bayanai duka' : 'Please enter your email/phone and password.');
    setLoading(true);
    try {
      // The session itself is always persisted regardless of this checkbox —
      // it only controls whether this identifier is offered as a quick-fill
      // suggestion next time (see persistAccount below).
      await login(identifier, password);
      if (remember) await persistAccount(identifier.trim());
    } catch (e) {
      // e.kind (from lib/api.js's ApiError) tells a bad network apart from
      // a real "wrong password" apart from an unexpected client-side bug —
      // showing "Connection Error" for all three is exactly what this
      // screen used to do, which hid the real cause.
      const titles = {
        network: 'Connection Error',
        timeout: 'Connection Timed Out',
        auth: isHausa ? 'Kuskure' : 'Login Failed',
        rate_limit: 'Too Many Attempts',
        server: 'Server Error',
        client: 'Something Went Wrong',
      };
      Alert.alert(titles[e.kind] || (isHausa ? 'Kuskure' : 'Login Failed'), e.message || 'Something went wrong. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleForgotPassword = () => {
    WebBrowser.openBrowserAsync(`${WEB_ROOT}/forgot-password`);
  };

  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: C.navy }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <StatusBar barStyle="light-content" backgroundColor={C.navy} />
      <ServerStatus />
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

        {/* ── HEADER ─────────────────────────────── */}
        <View style={styles.hero}>
          <TouchableOpacity style={styles.langBtn} onPress={() => i18n.changeLanguage(isHausa ? 'en' : 'ha')}>
            <Text style={styles.langBtnText}>{isHausa ? '🇬🇧 EN' : '🇳🇬 HA'}</Text>
          </TouchableOpacity>

          <View style={styles.logoWrap}>
            <Image source={require('../../assets/images/msas_logo.png')} style={styles.logoImg} resizeMode="cover" />
          </View>

          <Text style={styles.brandName}>MSAS FarmAI</Text>
          <Text style={styles.brandSub}>Livestock & Agro Services Platform</Text>
        </View>

        {/* ── FORM CARD ─────────────────────────────── */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>{isHausa ? 'Barka da Dawowa' : 'Welcome Back'}</Text>
          <Text style={styles.cardSubtitle}>{isHausa ? 'Shigar da bayananka don shiga' : 'Sign in to your MSAS account'}</Text>

          {sessionExpired && (
            <View style={styles.expiredBanner}>
              <Text style={styles.expiredBannerText}>
                {isHausa ? 'An fita daga asusun ka. Da fatan za a sake shiga.' : 'Your session ended. Please sign in again.'}
              </Text>
            </View>
          )}

          {/* Email or Phone */}
          <View style={styles.fieldWrap}>
            <Text style={styles.fieldLabel}>{isHausa ? 'IMEL KO LAMBAR WAYA' : 'EMAIL OR PHONE'}</Text>
            <View style={styles.inputWrap}>
              <TextInput
                style={styles.input}
                value={identifier}
                onChangeText={(v) => { setIdentifier(v); if (v) setShowSuggestions(false); }}
                onFocus={() => { if (savedAccounts.length > 0 && !identifier) setShowSuggestions(true); }}
                keyboardType="email-address"
                placeholderTextColor={C.textLight}
                autoComplete="off"
                autoCorrect={false}
                autoCapitalize="none"
                textContentType="none"
              />
            </View>

            {showSuggestions && savedAccounts.length > 0 && (
              <View style={styles.suggestBox}>
                <View style={styles.suggestHeader}>
                  <Text style={styles.suggestHeaderText}>{isHausa ? 'Asusun da aka adana' : 'Saved accounts'}</Text>
                  <TouchableOpacity onPress={clearSavedAccounts}>
                    <Text style={styles.suggestClear}>{isHausa ? 'Goge duka' : 'Clear all'}</Text>
                  </TouchableOpacity>
                </View>
                {savedAccounts.map((acc, i) => (
                  <TouchableOpacity
                    key={i}
                    style={[styles.suggestItem, i < savedAccounts.length - 1 && styles.suggestItemBorder]}
                    onPress={() => { setIdentifier(acc); setShowSuggestions(false); }}
                    activeOpacity={0.7}
                  >
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
              <TextInput
                style={[styles.input, { flex: 1 }]}
                value={password}
                onChangeText={setPassword}
                secureTextEntry={!showPass}
                placeholderTextColor={C.textLight}
                autoComplete="off"
                textContentType="none"
              />
              <TouchableOpacity onPress={() => setShowPass(v => !v)} style={styles.eyeBtn}>
                <Text style={styles.eyeText}>{showPass ? '👁' : '🙈'}</Text>
              </TouchableOpacity>
            </View>
          </View>

          {/* Remember me + Forgot password */}
          <View style={styles.optionsRow}>
            <TouchableOpacity style={styles.rememberRow} onPress={() => setRemember(r => !r)} activeOpacity={0.7}>
              <View style={[styles.checkbox, remember && styles.checkboxOn]}>
                {remember && <Text style={styles.checkmark}>✓</Text>}
              </View>
              <Text style={styles.rememberText}>{isHausa ? 'Tuna imel/lambar wayata' : 'Remember my email/phone'}</Text>
            </TouchableOpacity>
            <TouchableOpacity onPress={handleForgotPassword}>
              <Text style={styles.forgotText}>{isHausa ? 'Manta kalmar sirri?' : 'Forgot Password?'}</Text>
            </TouchableOpacity>
          </View>

          {/* Submit */}
          <TouchableOpacity
            style={[styles.submitBtn, loading && { opacity: 0.7 }]}
            onPress={handleLogin}
            disabled={loading}
            activeOpacity={0.85}
          >
            {loading ? <ActivityIndicator color={C.white} /> : (
              <Text style={styles.submitText}>{isHausa ? 'Shiga' : 'Sign In'}</Text>
            )}
          </TouchableOpacity>

          {/* Divider */}
          <View style={styles.dividerRow}>
            <View style={styles.dividerLine} />
            <Text style={styles.dividerText}>OR</Text>
            <View style={styles.dividerLine} />
          </View>

          {/* Sign up link */}
          <View style={styles.switchRow}>
            <Text style={styles.switchPrompt}>{isHausa ? 'Ba ku da asusun?' : "Don't have an account?"}</Text>
          </View>
          <TouchableOpacity style={styles.createBtn} onPress={() => router.push('/(auth)/register')} activeOpacity={0.8}>
            <Text style={styles.createBtnText}>{isHausa ? 'Ƙirƙiri Asusun' : 'Create Account'}</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flexGrow: 1 },

  // ── Header ─────────────────────────────────────────
  hero: {
    backgroundColor: C.navy,
    paddingTop: 64,
    paddingBottom: 32,
    paddingHorizontal: 24,
    alignItems: 'center',
  },
  langBtn: {
    position: 'absolute', top: 16, right: 20,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 20, paddingHorizontal: 12, paddingVertical: 6,
    borderWidth: 1, borderColor: 'rgba(255,255,255,0.15)',
  },
  langBtnText: { color: C.gold, fontWeight: '700', fontSize: 13 },

  logoWrap: { marginBottom: 14, alignItems: 'center' },
  logoImg: { width: 84, height: 84, borderRadius: 18, borderWidth: 1.5, borderColor: 'rgba(255,255,255,0.15)' },

  brandName: { color: C.white, fontSize: 22, fontWeight: '800', letterSpacing: -0.5, marginBottom: 4 },
  brandSub: { color: 'rgba(255,255,255,0.55)', fontSize: 12, fontWeight: '500' },

  // ── Card ─────────────────────────────────────────
  card: {
    backgroundColor: C.white,
    borderTopLeftRadius: 28, borderTopRightRadius: 28,
    paddingHorizontal: 24, paddingTop: 28, paddingBottom: 40,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.08, shadowRadius: 20, elevation: 10,
  },
  cardTitle: { fontSize: 22, fontWeight: '800', color: C.textDark, marginBottom: 4, letterSpacing: -0.3 },
  cardSubtitle: { fontSize: 13, color: C.textMid, marginBottom: 24 },
  expiredBanner: {
    backgroundColor: '#FEF3C7', borderWidth: 1, borderColor: '#FDE68A',
    borderRadius: 10, padding: 12, marginTop: -12, marginBottom: 20,
  },
  expiredBannerText: { fontSize: 12, color: '#92400E', fontWeight: '600' },

  // ── Fields ───────────────────────────────────────
  fieldWrap: { marginBottom: 16 },
  fieldLabel: { fontSize: 10, fontWeight: '700', color: C.textMid, letterSpacing: 0.08, marginBottom: 6, textTransform: 'uppercase' },
  inputWrap: {
    flexDirection: 'row', alignItems: 'center',
    borderWidth: 1.5, borderColor: C.border,
    borderRadius: 10, backgroundColor: C.cardBg, overflow: 'hidden',
  },
  input: { flex: 1, paddingVertical: 13, paddingHorizontal: 14, fontSize: 14, color: C.textDark },
  eyeBtn: { paddingHorizontal: 12 },
  eyeText: { fontSize: 16 },

  // ── Options row ──────────────────────────────────
  optionsRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 22 },
  rememberRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  checkbox: {
    width: 18, height: 18, borderRadius: 5,
    borderWidth: 1.5, borderColor: C.border,
    backgroundColor: C.cardBg, alignItems: 'center', justifyContent: 'center',
  },
  checkboxOn: { backgroundColor: C.green, borderColor: C.green },
  checkmark: { color: C.white, fontSize: 11, fontWeight: '800' },
  rememberText: { fontSize: 12, color: C.textMid, fontWeight: '500' },
  forgotText: { fontSize: 12, fontWeight: '700', color: C.green },

  // ── Submit ───────────────────────────────────────
  submitBtn: {
    backgroundColor: C.green,
    borderRadius: 12, paddingVertical: 15,
    alignItems: 'center', marginBottom: 22,
    shadowColor: C.green,
    shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.3, shadowRadius: 12, elevation: 6,
  },
  submitText: { color: C.white, fontSize: 15, fontWeight: '800', letterSpacing: 0.3 },

  // ── Divider ──────────────────────────────────────
  dividerRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 20, gap: 10 },
  dividerLine: { flex: 1, height: 1, backgroundColor: C.border },
  dividerText: { fontSize: 11, fontWeight: '700', color: C.textLight, letterSpacing: 0.5 },

  // ── Switch link ───────────────────────────────────
  switchRow: { alignItems: 'center', marginBottom: 12 },
  switchPrompt: { fontSize: 13, color: C.textLight },
  createBtn: {
    borderWidth: 1.5, borderColor: C.green, borderRadius: 12,
    paddingVertical: 13, alignItems: 'center',
  },
  createBtnText: { color: C.green, fontSize: 14, fontWeight: '800' },

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
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: 12, paddingVertical: 7,
    borderBottomWidth: 1, borderBottomColor: '#f1f5f9', backgroundColor: C.cardBg,
  },
  suggestHeaderText: { fontSize: 10, fontWeight: '700', color: C.textLight, textTransform: 'uppercase', letterSpacing: 0.5 },
  suggestClear: { fontSize: 11, fontWeight: '600', color: '#94a3b8' },
  suggestItem: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: 12, paddingVertical: 11, backgroundColor: C.white,
  },
  suggestItemBorder: { borderBottomWidth: 1, borderBottomColor: '#f8fafc' },
  suggestText: { flex: 1, fontSize: 13, fontWeight: '600', color: C.textDark },
  suggestArrow: { fontSize: 18, color: C.textLight, fontWeight: '300' },
});
