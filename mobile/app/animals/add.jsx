import React, { useRef, useState } from 'react';
import {
  View, Text, ScrollView, TextInput, TouchableOpacity, StyleSheet, Alert,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { animalsAPI } from '../../lib/api';
import { Colors, Spacing, Radius, Typography } from '../../constants/Theme';
import { Button } from '../../components/UI';

// Must match AnimalApiController::$speciesCodes (used to build the tag
// number). Anything else is sent as 'Other' + species_other, which the
// backend flags for admin review.
const SPECIES = ['Cattle', 'Goat', 'Sheep', 'Pig', 'Camel', 'Donkey', 'Horse', 'Rabbit', 'Other'];
const GENDERS = ['Male', 'Female', 'Unknown'];

function Chips({ options, value, onChange, testIDPrefix }) {
  return (
    <View style={styles.chips}>
      {options.map(opt => (
        <TouchableOpacity
          key={opt}
          testID={`${testIDPrefix}-${opt}`}
          onPress={() => onChange(opt)}
          style={[styles.chip, value === opt && styles.chipActive]}
          accessibilityRole="button"
          accessibilityState={{ selected: value === opt }}
        >
          <Text style={[styles.chipText, value === opt && styles.chipTextActive]}>{opt}</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

/**
 * Add-animal form. The Records tab's "+ Add Animal" button has always
 * pushed /animals/add, but no such route existed, so the button led to
 * expo-router's unmatched-route screen and farmers could not register
 * livestock from the app at all.
 */
export default function AddAnimalScreen() {
  const router = useRouter();
  const { i18n } = useTranslation();
  const isHausa = i18n.language === 'ha';

  const [species, setSpecies] = useState(null);
  const [speciesOther, setSpeciesOther] = useState('');
  const [name, setName] = useState('');
  const [breed, setBreed] = useState('');
  const [gender, setGender] = useState('Unknown');
  const [error, setError] = useState(null);
  const [saving, setSaving] = useState(false);
  // State updates are async, so a fast double-tap can get past `saving`.
  // Creating an animal is not idempotent (each call mints a new tag), so
  // guard with a ref as well.
  const submitting = useRef(false);

  const submit = async () => {
    if (submitting.current) return;
    if (!species) { setError(isHausa ? 'Zaɓi nau\'in dabba.' : 'Choose a species.'); return; }
    if (species === 'Other' && !speciesOther.trim()) {
      setError(isHausa ? 'Rubuta nau\'in dabbar.' : 'Type the species name.');
      return;
    }
    submitting.current = true;
    setSaving(true);
    setError(null);
    try {
      const body = { species, gender };
      if (species === 'Other') body.species_other = speciesOther.trim();
      if (name.trim()) body.name = name.trim();
      if (breed.trim()) body.breed = breed.trim();
      const res = await animalsAPI.create(body);
      Alert.alert(
        isHausa ? 'An yi rajista' : 'Animal registered',
        res.tag_id ? `${isHausa ? 'Lambar tag' : 'Tag number'}: ${res.tag_id}` : undefined,
      );
      router.back();
    } catch (e) {
      // Stay on the form with the user's input intact; never auto-retry a create.
      setError(e.message || 'Could not register this animal. Please try again.');
    } finally {
      submitting.current = false;
      setSaving(false);
    }
  };

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} accessibilityRole="button">
          <Text style={styles.back}>‹ {isHausa ? 'Koma' : 'Back'}</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>🐄 {isHausa ? 'Ƙara Dabba' : 'Add Animal'}</Text>
      </View>

      <Text style={styles.label}>{isHausa ? 'Nau\'i *' : 'Species *'}</Text>
      <Chips options={SPECIES} value={species} onChange={setSpecies} testIDPrefix="species" />
      {species === 'Other' && (
        <TextInput
          style={styles.input}
          value={speciesOther}
          onChangeText={setSpeciesOther}
          placeholder={isHausa ? 'Nau\'in dabba' : 'Species name'}
          placeholderTextColor={Colors.textMuted}
          maxLength={100}
          testID="species-other"
        />
      )}

      <Text style={styles.label}>{isHausa ? 'Suna (na zaɓi)' : 'Name (optional)'}</Text>
      <TextInput style={styles.input} value={name} onChangeText={setName} maxLength={100}
        placeholder={isHausa ? 'Sunan dabba' : 'e.g. Daisy'} placeholderTextColor={Colors.textMuted} testID="name" />

      <Text style={styles.label}>{isHausa ? 'Irin (na zaɓi)' : 'Breed (optional)'}</Text>
      <TextInput style={styles.input} value={breed} onChangeText={setBreed} maxLength={200}
        placeholder={isHausa ? 'misali White Fulani' : 'e.g. White Fulani'} placeholderTextColor={Colors.textMuted} testID="breed" />

      <Text style={styles.label}>{isHausa ? 'Jinsi' : 'Sex'}</Text>
      <Chips options={GENDERS} value={gender} onChange={setGender} testIDPrefix="gender" />

      {error && <Text style={styles.error} accessibilityRole="alert">{error}</Text>}

      <Button
        title={isHausa ? 'Yi Rajista' : 'Register Animal'}
        onPress={submit}
        loading={saving}
        style={{ marginTop: Spacing.lg }}
      />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: Colors.background },
  content: { padding: Spacing.md, paddingBottom: 60 },
  header: { paddingTop: 50, marginBottom: Spacing.md },
  back: { color: Colors.primary, fontWeight: '600', marginBottom: Spacing.sm },
  headerTitle: { ...Typography.h2, color: Colors.textPrimary },
  label: { ...Typography.label, color: Colors.textPrimary, marginTop: Spacing.md, marginBottom: Spacing.xs },
  input: {
    backgroundColor: Colors.white, borderWidth: 1, borderColor: Colors.border,
    borderRadius: Radius.md, padding: 12, fontSize: 15, color: Colors.textPrimary, marginTop: Spacing.xs,
  },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: {
    paddingHorizontal: 14, paddingVertical: 8, borderRadius: Radius.full,
    borderWidth: 1, borderColor: Colors.border, backgroundColor: Colors.white,
  },
  chipActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  chipText: { color: Colors.textPrimary, fontSize: 14 },
  chipTextActive: { color: Colors.white, fontWeight: '600' },
  error: { color: Colors.danger, marginTop: Spacing.md, ...Typography.small },
});
