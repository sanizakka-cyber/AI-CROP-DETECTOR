import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, ScrollView, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { locationsAPI } from '../../lib/api';
import { formatAccuracy } from '../../lib/gpsQuality';
import { apply as applySuggestions, edited } from '../../lib/locationSuggest';
import { useProfileLocation } from '../../lib/profileLocation';
import LocationMapPreview from '../../components/LocationMapPreview';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';
import { Button, LoadingOverlay } from '../../components/UI';

export default function EditProfileScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';
  const { user, updateProfile } = useAuth();

  const [firstName, setFirstName]   = useState(user?.first_name || user?.display_first_name || '');
  const [middleName, setMiddleName] = useState(user?.middle_name || '');
  const [lastName, setLastName]     = useState(user?.last_name || '');
  const [email, setEmail]           = useState(user?.email || '');
  const [phone, setPhone]           = useState(user?.phone || '');
  const [country, setCountry]     = useState(user?.country || 'Nigeria');
  const [state, setState]         = useState(user?.state || '');
  const [lga, setLga]             = useState(user?.lga || '');
  // Village carries its source: 'gps' (suggested from the location, unchanged) or 'user'.
  const [villageField, setVillageField] = useState({ value: user?.village || '', source: user?.location_field_sources?.village || (user?.village ? 'user' : null) });
  const village = villageField.value;
  const setVillage = (v) => setVillageField(edited(v));
  // Where State and LGA came from: 'gps' (filled from the location) or 'user'.
  const [namesSource, setNamesSource] = useState(null);

  const location = useProfileLocation({
    onNames: useCallback((s, l) => { setCountry('Nigeria'); setState(s); setLga(l); setNamesSource('gps'); }, []),
    onVillage: useCallback((community) => {
      const none = { value: '', source: null };
      setVillageField(f => applySuggestions({ community: f, landmark: none, postal_code: none },
        { community, landmark: null, postal_code: null }).community);
    }, []),
  });

  const [locations, setLocations] = useState({ countries: [], states: [] });
  const [locationsError, setLocationsError] = useState(false);
  const [saving, setSaving]       = useState(false);

  const isNigeria = country === 'Nigeria';
  const lgasForState = locations.states.find(s => s.name === state)?.lgas || [];

  useEffect(() => {
    locationsAPI.list()
      .then(data => setLocations({ countries: data.countries || [], states: data.states || [] }))
      .catch(() => setLocationsError(true));
  }, []);

  const selectCountry = (c) => {
    setCountry(c);
    // A Nigerian LGA doesn't mean anything paired with a different country,
    // and vice versa — clear both when the country actually changes.
    if (c !== country) { setState(''); setLga(''); setNamesSource(null); }
  };

  const selectState = (s) => {
    setState(s);
    setLga('');
    setNamesSource('user');
  };

  const selectLga = (l) => {
    setLga(l);
    setNamesSource('user');
  };

  const handleSave = async () => {
    if (!firstName.trim()) return Alert.alert('', isHausa ? 'Da fatan shigar da sunan farko' : 'First name is required.');
    if (isNigeria && !state) return Alert.alert('', isHausa ? 'Da fatan zaɓi jiha' : 'Please select a state.');

    setSaving(true);
    try {
      await updateProfile({
        first_name: firstName.trim(),
        middle_name: middleName.trim() || null,
        last_name: lastName.trim(),
        email: email.trim() || null,
        phone: phone.trim() || null,
        country,
        state: state.trim(),
        lga: lga.trim(),
        village: village.trim() || null,
        state_source: namesSource === 'gps' ? 'gps' : 'user',
        lga_source: namesSource === 'gps' ? 'gps' : 'user',
        village_source: villageField.source === 'gps' ? 'gps' : 'user',
      });
      Alert.alert(
        isHausa ? 'An yi nasara' : 'Profile updated successfully',
        '',
        [{ text: 'OK', onPress: () => router.back() }]
      );
    } catch (e) {
      Alert.alert('Update Failed', e.message || (isHausa ? 'An kasa adana bayanai' : 'Could not save your changes.'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={{ flex: 1 }}>
      {saving && <LoadingOverlay message={isHausa ? 'Ana adanawa...' : 'Saving...'} />}
      <ScrollView style={styles.root} contentContainerStyle={styles.content}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} style={styles.back}>
            <Text style={styles.backText}>‹ Back</Text>
          </TouchableOpacity>
          <Text style={styles.headerTitle}>{isHausa ? 'Gyara Bayanai' : 'Edit Profile'}</Text>
        </View>

        {(user?.role_label || user?.department) && (
          <View style={styles.readOnlyCard}>
            {user?.role_label && (
              <View style={styles.readOnlyRow}>
                <Text style={styles.readOnlyLabel}>{isHausa ? 'Matsayi' : 'Role'}</Text>
                <Text style={styles.readOnlyValue}>{user.role_label}</Text>
              </View>
            )}
            {user?.department && (
              <View style={styles.readOnlyRow}>
                <Text style={styles.readOnlyLabel}>{isHausa ? 'Sashe' : 'Department'}</Text>
                <Text style={styles.readOnlyValue}>{user.department}</Text>
              </View>
            )}
            <Text style={styles.readOnlyHint}>
              {isHausa ? 'Tuntuɓi mai kula da ku don canza wannan.' : "Contact your administrator to change this."}
            </Text>
          </View>
        )}

        <Text style={styles.label}>{isHausa ? 'Sunan Farko' : 'First Name'}</Text>
        <TextInput style={styles.input} value={firstName} onChangeText={setFirstName} placeholder="First name" placeholderTextColor={Colors.textMuted} />

        <Text style={styles.label}>{isHausa ? 'Sunan Tsakiya (na zaɓi)' : 'Middle Name (optional)'}</Text>
        <TextInput style={styles.input} value={middleName} onChangeText={setMiddleName} placeholder="Middle name" placeholderTextColor={Colors.textMuted} />

        <Text style={styles.label}>{isHausa ? 'Sunan Ƙarshe' : 'Last Name'}</Text>
        <TextInput style={styles.input} value={lastName} onChangeText={setLastName} placeholder="Last name" placeholderTextColor={Colors.textMuted} />

        <Text style={styles.label}>{isHausa ? 'Imel' : 'Email'}</Text>
        <TextInput style={styles.input} value={email} onChangeText={setEmail} placeholder="you@example.com" placeholderTextColor={Colors.textMuted} keyboardType="email-address" autoCapitalize="none" />
        {email !== (user?.email || '') && email.trim() !== '' && (
          <Text style={styles.hint}>{isHausa ? 'Za a sake tabbatar da sabon imel din' : 'Changing your email will require re-verification.'}</Text>
        )}

        <Text style={styles.label}>{isHausa ? 'Lambar Waya' : 'Phone'}</Text>
        <TextInput style={styles.input} value={phone} onChangeText={setPhone} placeholder="+234..." placeholderTextColor={Colors.textMuted} keyboardType="phone-pad" />

        {/* Fill State, LGA and Village from the location (same rules as the scan screens). */}
        <View style={styles.gpsBox}>
          <TouchableOpacity style={styles.gpsBtn} onPress={location.capture} disabled={location.loading} testID="profile-use-gps">
            {location.loading ? <ActivityIndicator size="small" color={Colors.primary} /> : null}
            <Text style={styles.gpsBtnText}>{t('loc_use_gps')}</Text>
          </TouchableOpacity>
          {location.fix ? (
            <Text style={styles.hint}>±{formatAccuracy(location.fix.accuracy)} m · {t(`rating_${location.fix.rating}`)}</Text>
          ) : null}
          {location.note ? (
            <Text style={[styles.hint, location.note.key === 'loc_low_accuracy' && styles.warnText]} testID="profile-gps-note">
              {t(location.note.key, location.note.params)}{location.note.attribution ? ` ${location.note.attribution}` : ''}
            </Text>
          ) : null}
          {location.fix ? (
            <LocationMapPreview key={`${location.fix.latitude},${location.fix.longitude}`} fix={location.fix} onMove={location.movePin} t={t} />
          ) : null}
        </View>

        <Text style={styles.label}>{isHausa ? 'Ƙasa' : 'Country'}</Text>
        {locationsError ? (
          <Text style={styles.errorText}>{isHausa ? 'An kasa loda ƙasashe. Da fatan sake gwadawa.' : 'Unable to load countries. Please try again.'}</Text>
        ) : (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipRow}>
            {(locations.countries.length > 0 ? locations.countries : [country]).map(c => (
              <TouchableOpacity key={c} style={[styles.chip, country === c && styles.chipSel]} onPress={() => selectCountry(c)}>
                <Text style={[styles.chipText, country === c && styles.chipTextSel]}>{c}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        <Text style={styles.label}>{isHausa ? 'Jiha' : 'State / Region'}</Text>
        {isNigeria ? (
          locationsError ? (
            <Text style={styles.errorText}>{isHausa ? 'An kasa loda jihohi. Da fatan sake gwadawa.' : 'Unable to load states. Please try again.'}</Text>
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipRow}>
              {locations.states.map(s => (
                <TouchableOpacity key={s.name} style={[styles.chip, state === s.name && styles.chipSel]} onPress={() => selectState(s.name)}>
                  <Text style={[styles.chipText, state === s.name && styles.chipTextSel]}>{s.name}</Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          )
        ) : (
          <TextInput style={styles.input} value={state} onChangeText={selectState} placeholder={isHausa ? 'Jiha/Yanki' : 'State / Region'} placeholderTextColor={Colors.textMuted} />
        )}

        <Text style={styles.label}>{isHausa ? 'ƘM/LGA' : 'LGA / District'}</Text>
        {isNigeria ? (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipRow}>
            {lgasForState.length === 0
              ? <Text style={styles.hint}>{isHausa ? 'Zaɓi jiha da farko' : 'Select a state first'}</Text>
              : lgasForState.map(l => (
                  <TouchableOpacity key={l} style={[styles.chip, lga === l && styles.chipSel]} onPress={() => selectLga(l)}>
                    <Text style={[styles.chipText, lga === l && styles.chipTextSel]}>{l}</Text>
                  </TouchableOpacity>
                ))
            }
          </ScrollView>
        ) : (
          <TextInput style={styles.input} value={lga} onChangeText={setLga} placeholder={isHausa ? 'Yanki (na zaɓi)' : 'Local area (optional)'} placeholderTextColor={Colors.textMuted} />
        )}

        <Text style={styles.label}>{isHausa ? 'Ƙauye (na zaɓi)' : 'Village (optional)'}</Text>
        <TextInput style={[styles.input, villageField.source === 'gps' && styles.inputSuggested]} value={village} onChangeText={setVillage} placeholder={isHausa ? 'Sunan ƙauye' : 'Village name'} placeholderTextColor={Colors.textMuted} testID="profile-village" />
        {villageField.source === 'gps' ? (
          <>
            <Text style={styles.suggestedNote} testID="profile-village-suggested">{t('loc_suggested')}</Text>
            <Text style={styles.hint}>© OpenStreetMap contributors</Text>
          </>
        ) : null}

        <Button
          title={saving ? (isHausa ? 'Ana adanawa...' : 'Saving...') : (isHausa ? 'Ajiye Bayanai' : 'Update Profile')}
          onPress={handleSave}
          loading={saving}
          style={styles.submitBtn}
        />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root:    { flex: 1, backgroundColor: Colors.background },
  content: { padding: Spacing.md, paddingBottom: 80 },
  header:  { paddingTop: 50, marginBottom: Spacing.md },
  back:    { marginBottom: Spacing.sm },
  backText:{ ...Typography.body, color: Colors.primary, fontWeight: '600' },
  headerTitle: { ...Typography.h2, color: Colors.textPrimary },

  label: { ...Typography.label, color: Colors.textPrimary, marginTop: Spacing.md, marginBottom: Spacing.xs },
  input: {
    backgroundColor: Colors.white, borderRadius: Radius.md, borderWidth: 1.5, borderColor: Colors.border,
    padding: Spacing.sm, color: Colors.textPrimary, ...Typography.body,
  },
  hint: { ...Typography.tiny, color: Colors.textMuted, marginTop: 4 },
  warnText: { color: '#B45309', fontWeight: '600' },
  inputSuggested: { borderColor: '#F59E0B' },
  suggestedNote: { ...Typography.tiny, color: '#B45309', marginTop: 4 },
  gpsBox: { marginTop: Spacing.md, padding: Spacing.sm, borderRadius: Radius.md, borderWidth: 1.5, borderColor: Colors.border, backgroundColor: Colors.white, gap: 4 },
  gpsBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, paddingVertical: 10, borderRadius: Radius.md, borderWidth: 1.5, borderColor: Colors.primary },
  gpsBtnText: { ...Typography.body, color: Colors.primary, fontWeight: '700' },
  errorText: { ...Typography.small, color: Colors.danger },

  readOnlyCard: {
    backgroundColor: Colors.white, borderRadius: Radius.md, borderWidth: 1.5, borderColor: Colors.border,
    padding: Spacing.sm, marginBottom: Spacing.sm,
  },
  readOnlyRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 4 },
  readOnlyLabel: { ...Typography.small, color: Colors.textMuted },
  readOnlyValue: { ...Typography.small, color: Colors.textPrimary, fontWeight: '600' },
  readOnlyHint: { ...Typography.tiny, color: Colors.textMuted, marginTop: Spacing.xs },

  chipRow: { flexDirection: 'row' },
  chip: {
    paddingHorizontal: Spacing.sm, paddingVertical: 8, marginRight: Spacing.xs,
    backgroundColor: Colors.white, borderRadius: Radius.full,
    borderWidth: 1.5, borderColor: Colors.border,
  },
  chipSel: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  chipText: { ...Typography.small, color: Colors.textSecondary },
  chipTextSel: { color: Colors.white, fontWeight: '600' },

  submitBtn: { marginTop: Spacing.lg },
});
