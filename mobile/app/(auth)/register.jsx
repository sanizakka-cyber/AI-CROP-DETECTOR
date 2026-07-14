import React, { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, Image,
  StyleSheet, ScrollView, KeyboardAvoidingView,
  Platform, Alert, StatusBar,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { Button } from '../../components/UI';

// ── Brand palette ─────────────────────────────────
const C = {
  navy:      '#0B2447',
  green:     '#0F6B3E',
  greenLt:   '#1FA84A',
  blue:      '#2D9CDB',
  gold:      '#F4A300',
  purple:    '#818CF8',
  white:     '#FFFFFF',
  bg:        '#F1F5F9',
  textDark:  '#0F172A',
  textMid:   '#475569',
  textLight: '#94A3B8',
  border:    '#E2E8F0',
  cardBg:    '#F8FAFC',
  errorBg:   '#FEF2F2',
  errorBorder:'#FECACA',
  errorText: '#DC2626',
  successBg: '#F0FDF4',
  successBorder: '#BBF7D0',
};

export default function RegisterScreen() {
  const { t, i18n } = useTranslation();
  const { register } = useAuth();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  const [form, setForm] = useState({
    name: '', phone: '', passcode: '', confirmPasscode: '', state: '',
  });
  const [loading, setLoading] = useState(false);
  const set = (k) => (v) => setForm(f => ({ ...f, [k]: v }));

  const passcodeMatch = form.confirmPasscode.length === 6 && form.confirmPasscode === form.passcode;
  const passcodeMismatch = form.confirmPasscode.length === 6 && form.confirmPasscode !== form.passcode;

  const handleRegister = async () => {
    const { name, phone, passcode, confirmPasscode, state } = form;
    if (!name.trim())
      return Alert.alert('', isHausa ? 'Shigar da sunan ku' : 'Enter your full name');
    if (!phone.trim())
      return Alert.alert('', isHausa ? 'Shigar da lambar waya' : 'Enter your phone number');
    if (passcode.length !== 6)
      return Alert.alert('', isHausa ? 'Lamba ta sirri dole ta zama lamba 6' : 'Passcode must be exactly 6 digits');
    if (passcode !== confirmPasscode)
      return Alert.alert('', isHausa ? 'Lamba ta sirri ba ta dace ba' : 'Passcodes do not match');
    if (!state.trim())
      return Alert.alert('', isHausa ? 'Zaɓi jiha' : 'Enter your state');

    setLoading(true);
    try {
      await register({ name: name.trim(), phone: phone.trim(), password: passcode, state: state.trim(), language: i18n.language });
    } catch (e) {
      Alert.alert(isHausa ? 'Kuskure' : 'Registration Failed', e.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: C.navy }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <StatusBar barStyle="light-content" backgroundColor={C.navy} />
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

        {/* ── HERO HEADER ─────────────────────────────── */}
        <View style={styles.hero}>

          {/* Decorative orbs */}
          <View style={[styles.orb, { width: 200, height: 200, backgroundColor: C.greenLt, top: -50, right: -50, opacity: 0.14 }]} />
          <View style={[styles.orb, { width: 120, height: 120, backgroundColor: C.blue, bottom: 10, left: -30, opacity: 0.12 }]} />
          <View style={[styles.orb, { width: 80, height: 80, backgroundColor: C.gold, top: 40, left: 30, opacity: 0.1 }]} />

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
          <Text style={styles.brandSub}>{isHausa ? 'Dandalin Aikin Gona na Najeriya' : 'Livestock & Agro Services Platform'}</Text>

          {/* Features mini grid */}
          <View style={styles.featGrid}>
            {[
              { icon: '🌾', label: isHausa ? 'Gona ta AI' : 'AI Crop Scan', bg: C.greenLt + '20', border: C.greenLt + '50' },
              { icon: '🐄', label: isHausa ? 'Kiwo' : 'Livestock Mgmt', bg: C.blue + '20', border: C.blue + '50' },
              { icon: '📊', label: isHausa ? 'Bayanan' : 'Live Analytics', bg: C.gold + '20', border: C.gold + '50' },
              { icon: '🔒', label: isHausa ? 'Aminci' : 'Secure RBAC', bg: C.purple + '20', border: C.purple + '50' },
            ].map((f, i) => (
              <View key={i} style={[styles.featCard, { backgroundColor: f.bg, borderColor: f.border }]}>
                <Text style={{ fontSize: 18, marginBottom: 4 }}>{f.icon}</Text>
                <Text style={styles.featLabel}>{f.label}</Text>
              </View>
            ))}
          </View>
        </View>

        {/* ── FORM CARD ─────────────────────────────── */}
        <View style={styles.card}>

          {/* Rainbow accent bar */}
          <View style={{ flexDirection: 'row', height: 4, borderRadius: 2, marginBottom: 22, marginTop: 6, overflow: 'hidden' }}>
            <View style={{ flex: 1, backgroundColor: C.green }} />
            <View style={{ flex: 1, backgroundColor: C.greenLt }} />
            <View style={{ flex: 1, backgroundColor: C.blue }} />
            <View style={{ flex: 1, backgroundColor: C.gold }} />
          </View>

          <Text style={styles.cardTitle}>{isHausa ? 'Ƙirƙirar Asusun' : 'Create Account'}</Text>
          <Text style={styles.cardSubtitle}>{isHausa ? 'Shiga MSAS Livestock & Agro Services' : 'Join MSAS Livestock & Agro Services today'}</Text>

          {/* Full Name */}
          <Field
            label={isHausa ? 'CIKAKKEN SUNA' : 'FULL NAME'}
            icon="👤"
          >
            <TextInput
              style={styles.input}
              value={form.name}
              onChangeText={set('name')}
              placeholder=""
              keyboardType="default"
              autoComplete="name"
              textContentType="name"
              autoCorrect={false}
              autoCapitalize="words"
            />
          </Field>

          {/* Phone */}
          <Field
            label={isHausa ? 'LAMBAR WAYA' : 'PHONE NUMBER'}
            icon="📞"
          >
            <TextInput
              style={styles.input}
              value={form.phone}
              onChangeText={set('phone')}
              placeholder=""
              keyboardType="phone-pad"
              autoComplete="tel"
              textContentType="telephoneNumber"
              autoCorrect={false}
            />
          </Field>

          {/* Passcode */}
          <Field
            label={isHausa ? 'LAMBA TA SIRRI (LAMBA 6)' : 'PASSCODE (6 DIGITS)'}
            icon="🔢"
          >
            <TextInput
              style={styles.input}
              value={form.passcode}
              onChangeText={(v) => { if (/^\d{0,6}$/.test(v)) set('passcode')(v); }}
              placeholder=""
              keyboardType="number-pad"
              secureTextEntry
              maxLength={6}
              autoComplete="new-password"
              textContentType="newPassword"
            />
            {/* Progress dots */}
            <View style={styles.dotRow}>
              {[0,1,2,3,4,5].map(i => (
                <View key={i} style={[styles.dot, i < form.passcode.length && styles.dotFilled]} />
              ))}
            </View>
          </Field>

          {/* Confirm Passcode */}
          <Field
            label={isHausa ? 'TABBATAR DA LAMBA' : 'CONFIRM PASSCODE'}
            icon={passcodeMatch ? '✅' : passcodeMismatch ? '❌' : '🔐'}
          >
            <TextInput
              style={[styles.input, passcodeMismatch && styles.inputError]}
              value={form.confirmPasscode}
              onChangeText={(v) => { if (/^\d{0,6}$/.test(v)) set('confirmPasscode')(v); }}
              placeholder=""
              keyboardType="number-pad"
              secureTextEntry
              maxLength={6}
              autoComplete="new-password"
              textContentType="newPassword"
            />
            {passcodeMismatch && (
              <Text style={styles.errorText}>{isHausa ? 'Lamba ta sirri ba ta dace ba' : 'Passcodes do not match'}</Text>
            )}
            {passcodeMatch && (
              <Text style={styles.successText}>{isHausa ? 'Lamba ta dace ✓' : 'Passcodes match ✓'}</Text>
            )}
          </Field>

          {/* State */}
          <Field
            label={isHausa ? 'JIHA' : 'STATE'}
            icon="📍"
          >
            <TextInput
              style={styles.input}
              value={form.state}
              onChangeText={set('state')}
              placeholder=""
              autoComplete="address-level1"
              textContentType="addressState"
              autoCorrect={false}
              autoCapitalize="words"
            />
          </Field>

          {/* Terms */}
          <View style={styles.termsBox}>
            <Text style={styles.termsText}>
              {isHausa
                ? 'Ta hanyar ƙirƙirar asusun, kun yarda da Terms of Service da Privacy Policy.'
                : 'By creating an account, you agree to our Terms of Service and Privacy Policy.'}
            </Text>
          </View>

          {/* Submit */}
          <TouchableOpacity
            style={[styles.submitBtn, (loading || passcodeMismatch) && { opacity: 0.6 }]}
            onPress={handleRegister}
            disabled={loading || passcodeMismatch}
            activeOpacity={0.85}
          >
            <Text style={styles.submitText}>
              {loading
                ? (isHausa ? 'Ana yin rajistar...' : 'Creating Account…')
                : (isHausa ? 'Ƙirƙiri Asusun' : 'Create My Account')}
            </Text>
          </TouchableOpacity>

          {/* Login link */}
          <View style={styles.switchRow}>
            <Text style={styles.switchPrompt}>{isHausa ? 'Kuna da asusun?' : 'Already have an account?'}</Text>
            <TouchableOpacity onPress={() => router.back()}>
              <Text style={styles.switchLink}>{isHausa ? ' Shiga' : ' Sign In'}</Text>
            </TouchableOpacity>
          </View>
        </View>

      </ScrollView>
    </KeyboardAvoidingView>
  );
}

// ── Reusable field wrapper ────────────────────────
function Field({ label, icon, children }) {
  return (
    <View style={styles.fieldWrap}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <View style={styles.inputWrap}>
        <View style={styles.inputIconBox}>
          <Text style={styles.inputIconText}>{icon}</Text>
        </View>
        <View style={{ flex: 1 }}>{children}</View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flexGrow: 1 },

  // ── Hero ─────────────────────────────────────────
  hero: {
    backgroundColor: C.navy,
    paddingTop: 56, paddingBottom: 28, paddingHorizontal: 20,
    alignItems: 'center', position: 'relative', overflow: 'hidden',
  },
  orb: { position: 'absolute', borderRadius: 999 },
  langBtn: {
    position: 'absolute', top: 16, right: 16,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 20, paddingHorizontal: 11, paddingVertical: 5,
    borderWidth: 1, borderColor: 'rgba(255,255,255,0.15)',
  },
  langBtnText: { color: C.gold, fontWeight: '700', fontSize: 12 },

  logoWrap: { marginBottom: 12, alignItems: 'center' },
  logoImg: {
    width: 90, height: 90, borderRadius: 16,
    shadowColor: '#000', shadowOffset: { width: 0, height: 5 },
    shadowOpacity: 0.38, shadowRadius: 14, elevation: 10,
    borderWidth: 1.5, borderColor: 'rgba(255,255,255,0.15)',
  },

  brandName: { color: C.white, fontSize: 22, fontWeight: '800', letterSpacing: -0.4, marginBottom: 3 },
  brandSub: { color: 'rgba(255,255,255,0.5)', fontSize: 11, fontWeight: '500', marginBottom: 18, textAlign: 'center' },

  featGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, width: '100%', justifyContent: 'center' },
  featCard: {
    width: '46%', alignItems: 'center', paddingVertical: 12,
    borderRadius: 12, borderWidth: 1,
  },
  featLabel: { color: 'rgba(255,255,255,0.8)', fontSize: 11, fontWeight: '600', textAlign: 'center' },

  // ── Card ─────────────────────────────────────────
  card: {
    backgroundColor: C.white,
    borderTopLeftRadius: 28, borderTopRightRadius: 28,
    paddingHorizontal: 22, paddingBottom: 40, paddingTop: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 }, shadowOpacity: 0.07, shadowRadius: 18, elevation: 10,
  },
  cardTitle: { fontSize: 20, fontWeight: '800', color: C.textDark, marginBottom: 3, letterSpacing: -0.3 },
  cardSubtitle: { fontSize: 13, color: C.textMid, marginBottom: 20 },

  // ── Fields ───────────────────────────────────────
  fieldWrap: { marginBottom: 14 },
  fieldLabel: { fontSize: 10, fontWeight: '700', color: C.textMid, letterSpacing: 0.07, marginBottom: 6, textTransform: 'uppercase' },
  inputWrap: {
    flexDirection: 'row', alignItems: 'center',
    borderWidth: 1.5, borderColor: C.border,
    borderRadius: 10, backgroundColor: C.cardBg, overflow: 'hidden',
  },
  inputIconBox: { paddingHorizontal: 10, paddingVertical: 11 },
  inputIconText: { fontSize: 16 },
  input: {
    paddingVertical: 11, paddingRight: 12,
    fontSize: 14, color: C.textDark, width: '100%',
  },
  inputError: { backgroundColor: C.errorBg },

  // Passcode dots
  dotRow: { flexDirection: 'row', gap: 6, paddingHorizontal: 12, paddingBottom: 10 },
  dot: { width: 8, height: 8, borderRadius: 4, backgroundColor: C.border },
  dotFilled: { backgroundColor: C.green },

  errorText: { color: C.errorText, fontSize: 11, marginTop: 4, marginLeft: 2 },
  successText: { color: C.greenLt, fontSize: 11, fontWeight: '700', marginTop: 4, marginLeft: 2 },

  // Terms
  termsBox: {
    backgroundColor: C.successBg, borderRadius: 10,
    borderWidth: 1, borderColor: C.successBorder,
    padding: 12, marginBottom: 18,
  },
  termsText: { fontSize: 12, color: C.textMid, lineHeight: 18 },

  // Submit
  submitBtn: {
    backgroundColor: C.green, borderRadius: 12,
    paddingVertical: 14, alignItems: 'center', marginBottom: 18,
    shadowColor: C.green,
    shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.32, shadowRadius: 12, elevation: 8,
  },
  submitText: { color: C.white, fontSize: 15, fontWeight: '800', letterSpacing: 0.3 },

  // Switch link
  switchRow: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center' },
  switchPrompt: { fontSize: 13, color: C.textLight },
  switchLink: { fontSize: 13, color: C.green, fontWeight: '800' },
});
