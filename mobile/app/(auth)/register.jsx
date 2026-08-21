import React, { useEffect, useMemo, useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, Image, Modal, FlatList,
  StyleSheet, ScrollView, KeyboardAvoidingView,
  Platform, Alert, StatusBar, ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { locationsAPI } from '../../lib/api';

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
  errorBg:   '#FEF2F2',
  errorBorder:'#FECACA',
  errorText: '#DC2626',
  successBg: '#F0FDF4',
  successBorder: '#BBF7D0',
};

// Matches the backend's Password::min(8)->mixedCase()->numbers()->symbols()
// (App\Http\Requests\Api\RegisterRequest) — checked client-side purely for
// instant feedback; the server remains the authority.
const PASSWORD_RULES = [
  { key: 'length',  label: 'At least 8 characters', test: (v) => v.length >= 8 },
  { key: 'upper',   label: 'One uppercase letter',   test: (v) => /[A-Z]/.test(v) },
  { key: 'lower',   label: 'One lowercase letter',   test: (v) => /[a-z]/.test(v) },
  { key: 'number',  label: 'One number',             test: (v) => /[0-9]/.test(v) },
  { key: 'symbol',  label: 'One special character',  test: (v) => /[^A-Za-z0-9]/.test(v) },
];

export default function RegisterScreen() {
  const { t, i18n } = useTranslation();
  const { register } = useAuth();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  const [form, setForm] = useState({
    firstName: '', middleName: '', lastName: '', identifier: '',
    country: 'Nigeria', state: '', lga: '',
    password: '', confirmPassword: '',
  });
  const [showPass, setShowPass] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [loading, setLoading] = useState(false);
  const set = (k) => (v) => setForm(f => ({ ...f, [k]: v }));

  // ── Location data (states/LGAs from the same backend source as web) ──
  const [locations, setLocations] = useState({ countries: ['Nigeria'], states: [] });
  const [locLoading, setLocLoading] = useState(true);
  const [statePickerOpen, setStatePickerOpen] = useState(false);
  const [lgaPickerOpen, setLgaPickerOpen] = useState(false);
  const [countryPickerOpen, setCountryPickerOpen] = useState(false);
  const [stateSearch, setStateSearch] = useState('');
  const [lgaSearch, setLgaSearch] = useState('');
  const [countrySearch, setCountrySearch] = useState('');

  useEffect(() => {
    locationsAPI.list()
      .then(res => setLocations({ countries: res.countries || ['Nigeria'], states: res.states || [] }))
      .catch(() => {}) // keep defaults; state/LGA pickers just show empty lists
      .finally(() => setLocLoading(false));
  }, []);

  const lgasForState = useMemo(() => {
    return locations.states.find(s => s.name === form.state)?.lgas || [];
  }, [locations.states, form.state]);

  const filteredStates = useMemo(() => {
    const q = stateSearch.trim().toLowerCase();
    return q ? locations.states.filter(s => s.name.toLowerCase().includes(q)) : locations.states;
  }, [locations.states, stateSearch]);

  const filteredLgas = useMemo(() => {
    const q = lgaSearch.trim().toLowerCase();
    return q ? lgasForState.filter(l => l.toLowerCase().includes(q)) : lgasForState;
  }, [lgasForState, lgaSearch]);

  const filteredCountries = useMemo(() => {
    const q = countrySearch.trim().toLowerCase();
    return q ? locations.countries.filter(c => c.toLowerCase().includes(q)) : locations.countries;
  }, [locations.countries, countrySearch]);

  const isNigeria = form.country === 'Nigeria';

  const selectState = (name) => {
    // Changing state invalidates any previously selected LGA — never let an
    // LGA from a different state remain selected.
    setForm(f => ({ ...f, state: name, lga: '' }));
    setStateSearch('');
    setStatePickerOpen(false);
  };

  const selectCountry = (name) => {
    // The state/LGA dropdown data (App\Data\NigeriaLocations) only covers
    // Nigeria — switching away from it clears state/LGA rather than leaving
    // a Nigerian state selected under a different country. Switching back
    // to Nigeria also clears them so a free-typed value from another
    // country isn't submitted as if it were a real Nigerian state/LGA.
    setForm(f => ({ ...f, country: name, state: '', lga: '' }));
    setCountrySearch('');
    setCountryPickerOpen(false);
  };

  const passwordChecks = PASSWORD_RULES.map(r => ({ ...r, passed: r.test(form.password) }));
  const passwordValid  = passwordChecks.every(c => c.passed);
  const passwordsMatch = form.confirmPassword.length > 0 && form.confirmPassword === form.password;

  const handleRegister = async () => {
    const { firstName, lastName, identifier, state, lga, password, confirmPassword } = form;
    if (!firstName.trim())
      return Alert.alert('', isHausa ? 'Shigar da sunan farko' : 'Please enter your first name.');
    if (!lastName.trim())
      return Alert.alert('', isHausa ? 'Shigar da sunan ƙarshe' : 'Please enter your last name.');
    if (!identifier.trim())
      return Alert.alert('', isHausa ? 'Shigar da imel ko lambar waya' : 'Please enter your email address or phone number.');
    // State/LGA are only required as real selections for Nigeria, where the
    // backend's location dataset can actually validate/populate them —
    // other countries take a free-text state and an optional local area.
    if (isNigeria && !state)
      return Alert.alert('', isHausa ? 'Zaɓi jiha' : 'Please select your state.');
    if (isNigeria && !lga)
      return Alert.alert('', isHausa ? 'Zaɓi ƙananan hukuma' : 'Please select your LGA.');
    if (!isNigeria && !state.trim())
      return Alert.alert('', isHausa ? 'Shigar da jiha' : 'Please enter your state or region.');
    if (!passwordValid)
      return Alert.alert('', isHausa ? 'Kalmar sirri ba ta cika ka’idoji ba' : 'Password does not meet the requirements below.');
    if (password !== confirmPassword)
      return Alert.alert('', isHausa ? 'Kalmar sirri ba ta dace ba' : 'Passwords do not match.');

    setLoading(true);
    try {
      const result = await register({
        first_name: firstName.trim(),
        middle_name: form.middleName.trim() || null,
        last_name: lastName.trim(),
        identifier: identifier.trim(),
        country: form.country,
        state,
        lga,
        password,
        password_confirmation: confirmPassword,
        language: i18n.language,
      });

      if (result.status === 'authenticated') {
        router.replace('/(tabs)/home');
      } else if (result.status === 'needs_verification') {
        router.replace({ pathname: '/(auth)/verify-otp', params: { identifier: result.identifier } });
      } else {
        Alert.alert(
          isHausa ? 'An Karɓi Neman' : 'Application Submitted',
          result.message || 'Your application has been submitted and is pending review.',
          [{ text: 'OK', onPress: () => router.replace('/(auth)/login') }]
        );
      }
    } catch (e) {
      Alert.alert(isHausa ? 'Kuskure' : 'Registration Failed', e.message || 'Something went wrong. Please try again.');
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
          <TouchableOpacity style={styles.langBtn} onPress={() => i18n.changeLanguage(isHausa ? 'en' : 'ha')}>
            <Text style={styles.langBtnText}>{isHausa ? '🇬🇧 EN' : '🇳🇬 HA'}</Text>
          </TouchableOpacity>

          <View style={styles.logoWrap}>
            <Image source={require('../../assets/images/msas_logo.png')} style={styles.logoImg} resizeMode="cover" />
          </View>

          <Text style={styles.brandName}>MSAS FarmAI</Text>
          <Text style={styles.brandSub}>{isHausa ? 'Dandalin Aikin Gona na Najeriya' : 'Livestock & Agro Services Platform'}</Text>
        </View>

        {/* ── FORM CARD ─────────────────────────────── */}
        <View style={styles.card}>
          <View style={{ flexDirection: 'row', height: 4, borderRadius: 2, marginBottom: 22, marginTop: 6, overflow: 'hidden' }}>
            <View style={{ flex: 1, backgroundColor: C.green }} />
            <View style={{ flex: 1, backgroundColor: C.greenLt }} />
            <View style={{ flex: 1, backgroundColor: C.blue }} />
            <View style={{ flex: 1, backgroundColor: C.gold }} />
          </View>

          <Text style={styles.cardTitle}>{isHausa ? 'Ƙirƙirar Asusun' : 'Create Account'}</Text>
          <Text style={styles.cardSubtitle}>{isHausa ? 'Shiga MSAS Livestock & Agro Services' : 'Join MSAS Livestock & Agro Services today'}</Text>

          {/* Name fields */}
          <Field label={isHausa ? 'SUNAN FARKO' : 'FIRST NAME'} icon="👤">
            <TextInput style={styles.input} value={form.firstName} onChangeText={set('firstName')}
              autoComplete="given-name" textContentType="givenName" autoCapitalize="words" />
          </Field>
          <Field label={isHausa ? 'SUNAN TSAKIYA (ZAƁI)' : 'MIDDLE NAME (OPTIONAL)'} icon="👤">
            <TextInput style={styles.input} value={form.middleName} onChangeText={set('middleName')}
              autoComplete="additional-name" autoCapitalize="words" />
          </Field>
          <Field label={isHausa ? 'SUNAN ƘARSHE' : 'LAST NAME'} icon="👤">
            <TextInput style={styles.input} value={form.lastName} onChangeText={set('lastName')}
              autoComplete="family-name" textContentType="familyName" autoCapitalize="words" />
          </Field>

          {/* Identifier: email OR phone, matching the backend's single-identifier account model */}
          <Field label={isHausa ? 'IMEL KO LAMBAR WAYA' : 'EMAIL OR PHONE NUMBER'} icon="✉️">
            <TextInput style={styles.input} value={form.identifier} onChangeText={set('identifier')}
              placeholder="you@example.com or 08012345678" placeholderTextColor={C.textLight}
              keyboardType="email-address" autoComplete="email" autoCapitalize="none" autoCorrect={false} />
          </Field>

          {/* Country — searchable dropdown */}
          <Field label={isHausa ? 'ƘASA' : 'COUNTRY'} icon="🌍">
            <TouchableOpacity onPress={() => setCountryPickerOpen(true)} disabled={locLoading}>
              <View style={styles.pickerRow}>
                <Text style={styles.pickerValue}>
                  {locLoading ? 'Loading…' : form.country}
                </Text>
                <Text style={styles.pickerChevron}>▼</Text>
              </View>
            </TouchableOpacity>
          </Field>

          {/* State — searchable dropdown for Nigeria (real data source);
              free-text for every other country, since the backend's
              location dataset only covers Nigerian states/LGAs. */}
          <Field label={isHausa ? 'JIHA' : 'STATE'} icon="📍">
            {isNigeria ? (
              <TouchableOpacity onPress={() => setStatePickerOpen(true)} disabled={locLoading}>
                <View style={styles.pickerRow}>
                  <Text style={form.state ? styles.pickerValue : styles.pickerPlaceholder}>
                    {locLoading ? 'Loading states…' : (form.state || 'Select State')}
                  </Text>
                  <Text style={styles.pickerChevron}>▼</Text>
                </View>
              </TouchableOpacity>
            ) : (
              <TextInput style={styles.input} value={form.state} onChangeText={set('state')}
                placeholder="State / Region" placeholderTextColor={C.textLight} autoCapitalize="words" />
            )}
          </Field>

          {/* LGA — dependent dropdown for Nigeria; free-text otherwise */}
          <Field label={isHausa ? 'ƘARAMAR HUKUMA' : 'LOCAL GOVERNMENT AREA'} icon="📍">
            {!isNigeria ? (
              <TextInput style={styles.input} value={form.lga} onChangeText={set('lga')}
                placeholder="Local area (optional)" placeholderTextColor={C.textLight} autoCapitalize="words" />
            ) : (
            <TouchableOpacity onPress={() => form.state && setLgaPickerOpen(true)} disabled={!form.state}>
              <View style={[styles.pickerRow, !form.state && { opacity: 0.5 }]}>
                <Text style={form.lga ? styles.pickerValue : styles.pickerPlaceholder}>
                  {form.state ? (form.lga || 'Select LGA') : 'Select a state first'}
                </Text>
                <Text style={styles.pickerChevron}>▼</Text>
              </View>
            </TouchableOpacity>
            )}
          </Field>

          {/* Password */}
          <Field label={isHausa ? 'KALMAR SIRRI' : 'PASSWORD'} icon="🔒">
            <View style={{ flexDirection: 'row', alignItems: 'center' }}>
              <TextInput
                style={[styles.input, { flex: 1 }]}
                value={form.password}
                onChangeText={set('password')}
                secureTextEntry={!showPass}
                autoComplete="new-password"
                textContentType="newPassword"
                autoCapitalize="none"
              />
              <TouchableOpacity onPress={() => setShowPass(v => !v)} style={styles.eyeBtn}>
                <Text style={styles.eyeText}>{showPass ? '👁' : '🙈'}</Text>
              </TouchableOpacity>
            </View>
          </Field>
          {form.password.length > 0 && (
            <View style={styles.ruleBox}>
              {passwordChecks.map(c => (
                <Text key={c.key} style={[styles.ruleText, c.passed && styles.ruleTextPassed]}>
                  {c.passed ? '✓' : '•'} {c.label}
                </Text>
              ))}
            </View>
          )}

          {/* Confirm Password */}
          <Field label={isHausa ? 'TABBATAR KALMAR SIRRI' : 'CONFIRM PASSWORD'} icon={passwordsMatch ? '✅' : '🔐'}>
            <View style={{ flexDirection: 'row', alignItems: 'center' }}>
              <TextInput
                style={[styles.input, { flex: 1 }, form.confirmPassword && !passwordsMatch && styles.inputError]}
                value={form.confirmPassword}
                onChangeText={set('confirmPassword')}
                secureTextEntry={!showConfirm}
                autoComplete="new-password"
                textContentType="newPassword"
                autoCapitalize="none"
              />
              <TouchableOpacity onPress={() => setShowConfirm(v => !v)} style={styles.eyeBtn}>
                <Text style={styles.eyeText}>{showConfirm ? '👁' : '🙈'}</Text>
              </TouchableOpacity>
            </View>
            {form.confirmPassword.length > 0 && !passwordsMatch && (
              <Text style={styles.errorText}>{isHausa ? 'Kalmar sirri ba ta dace ba' : 'Passwords do not match'}</Text>
            )}
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
            style={[styles.submitBtn, loading && { opacity: 0.6 }]}
            onPress={handleRegister}
            disabled={loading}
            activeOpacity={0.85}
          >
            {loading ? <ActivityIndicator color={C.white} /> : (
              <Text style={styles.submitText}>{isHausa ? 'Ƙirƙiri Asusun' : 'Create My Account'}</Text>
            )}
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

      {/* ── Country picker modal ──────────────────────── */}
      <PickerModal
        visible={countryPickerOpen}
        title={isHausa ? 'Zaɓi Ƙasa' : 'Select Country'}
        search={countrySearch}
        onSearch={setCountrySearch}
        items={filteredCountries}
        onSelect={selectCountry}
        onClose={() => { setCountryPickerOpen(false); setCountrySearch(''); }}
      />

      {/* ── State picker modal ─────────────────────────── */}
      <PickerModal
        visible={statePickerOpen}
        title={isHausa ? 'Zaɓi Jiha' : 'Select State'}
        search={stateSearch}
        onSearch={setStateSearch}
        items={filteredStates.map(s => s.name)}
        onSelect={selectState}
        onClose={() => { setStatePickerOpen(false); setStateSearch(''); }}
      />

      {/* ── LGA picker modal ───────────────────────────── */}
      <PickerModal
        visible={lgaPickerOpen}
        title={isHausa ? 'Zaɓi Ƙaramar Hukuma' : 'Select LGA'}
        search={lgaSearch}
        onSearch={setLgaSearch}
        items={filteredLgas}
        onSelect={(v) => { set('lga')(v); setLgaSearch(''); setLgaPickerOpen(false); }}
        onClose={() => { setLgaPickerOpen(false); setLgaSearch(''); }}
      />
    </KeyboardAvoidingView>
  );
}

function PickerModal({ visible, title, search, onSearch, items, onSelect, onClose }) {
  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.modalOverlay}>
        <View style={styles.modalSheet}>
          <View style={styles.modalHandle} />
          <Text style={styles.modalTitle}>{title}</Text>
          <TextInput
            style={styles.modalSearch}
            value={search}
            onChangeText={onSearch}
            placeholder="Search…"
            placeholderTextColor={C.textLight}
            autoCapitalize="none"
          />
          <FlatList
            data={items}
            keyExtractor={(item) => item}
            style={{ maxHeight: 360 }}
            keyboardShouldPersistTaps="handled"
            ListEmptyComponent={<Text style={styles.modalEmpty}>No matches found.</Text>}
            renderItem={({ item }) => (
              <TouchableOpacity style={styles.modalOption} onPress={() => onSelect(item)}>
                <Text style={styles.modalOptionText}>{item}</Text>
              </TouchableOpacity>
            )}
          />
          <TouchableOpacity style={styles.modalCloseBtn} onPress={onClose}>
            <Text style={styles.modalCloseText}>Cancel</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
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

  hero: {
    backgroundColor: C.navy,
    paddingTop: 56, paddingBottom: 24, paddingHorizontal: 20,
    alignItems: 'center', position: 'relative',
  },
  langBtn: {
    position: 'absolute', top: 16, right: 16,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 20, paddingHorizontal: 11, paddingVertical: 5,
    borderWidth: 1, borderColor: 'rgba(255,255,255,0.15)',
  },
  langBtnText: { color: C.gold, fontWeight: '700', fontSize: 12 },

  logoWrap: { marginBottom: 12, alignItems: 'center' },
  logoImg: {
    width: 80, height: 80, borderRadius: 16,
    borderWidth: 1.5, borderColor: 'rgba(255,255,255,0.15)',
  },

  brandName: { color: C.white, fontSize: 20, fontWeight: '800', letterSpacing: -0.4, marginBottom: 3 },
  brandSub: { color: 'rgba(255,255,255,0.5)', fontSize: 11, fontWeight: '500' },

  card: {
    backgroundColor: C.white,
    borderTopLeftRadius: 28, borderTopRightRadius: 28,
    paddingHorizontal: 22, paddingBottom: 40, paddingTop: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 }, shadowOpacity: 0.07, shadowRadius: 18, elevation: 10,
  },
  cardTitle: { fontSize: 20, fontWeight: '800', color: C.textDark, marginBottom: 3, letterSpacing: -0.3 },
  cardSubtitle: { fontSize: 13, color: C.textMid, marginBottom: 20 },

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
  eyeBtn: { paddingHorizontal: 12, paddingVertical: 11 },
  eyeText: { fontSize: 16 },

  pickerRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingVertical: 11, paddingRight: 12 },
  pickerValue: { fontSize: 14, color: C.textDark, fontWeight: '600' },
  pickerPlaceholder: { fontSize: 14, color: C.textLight },
  pickerChevron: { fontSize: 11, color: C.green },

  ruleBox: { marginTop: -6, marginBottom: 14, paddingHorizontal: 4 },
  ruleText: { fontSize: 11, color: C.textLight, marginBottom: 2 },
  ruleTextPassed: { color: C.greenLt, fontWeight: '600' },

  errorText: { color: C.errorText, fontSize: 11, marginTop: 4, marginLeft: 2 },

  termsBox: {
    backgroundColor: C.successBg, borderRadius: 10,
    borderWidth: 1, borderColor: C.successBorder,
    padding: 12, marginBottom: 18,
  },
  termsText: { fontSize: 12, color: C.textMid, lineHeight: 18 },

  submitBtn: {
    backgroundColor: C.green, borderRadius: 12,
    paddingVertical: 14, alignItems: 'center', marginBottom: 18,
    shadowColor: C.green,
    shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.32, shadowRadius: 12, elevation: 8,
  },
  submitText: { color: C.white, fontSize: 15, fontWeight: '800', letterSpacing: 0.3 },

  switchRow: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center' },
  switchPrompt: { fontSize: 13, color: C.textLight },
  switchLink: { fontSize: 13, color: C.green, fontWeight: '800' },

  // ── Picker modal ──────────────────────────────────
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.45)', justifyContent: 'flex-end' },
  modalSheet: {
    backgroundColor: C.white, borderTopLeftRadius: 20, borderTopRightRadius: 20,
    paddingHorizontal: 20, paddingBottom: 24, paddingTop: 12, maxHeight: '75%',
  },
  modalHandle: { width: 40, height: 4, borderRadius: 2, backgroundColor: C.border, alignSelf: 'center', marginBottom: 16 },
  modalTitle: { fontSize: 17, fontWeight: '800', color: C.textDark, marginBottom: 12 },
  modalSearch: {
    borderWidth: 1.5, borderColor: C.border, borderRadius: 10,
    paddingHorizontal: 14, paddingVertical: 10, fontSize: 14, color: C.textDark,
    backgroundColor: C.cardBg, marginBottom: 10,
  },
  modalEmpty: { textAlign: 'center', color: C.textLight, paddingVertical: 20, fontSize: 13 },
  modalOption: { paddingVertical: 13, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  modalOptionText: { fontSize: 14, color: C.textDark, fontWeight: '600' },
  modalCloseBtn: { marginTop: 10, paddingVertical: 12, alignItems: 'center' },
  modalCloseText: { color: C.textMid, fontWeight: '700', fontSize: 13 },
});
