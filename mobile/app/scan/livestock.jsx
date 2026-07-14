import React, { useState } from 'react';
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  Image, Alert, TextInput,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import * as ImagePicker from 'expo-image-picker';
import { diagnoseAPI } from '../../lib/api';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';
import { Button, LoadingOverlay } from '../../components/UI';

const ANIMALS = [
  { id: 'cattle',  icon: '🐄', name: 'Cattle / Shanu' },
  { id: 'goat',    icon: '🐐', name: 'Goat / Awaki' },
  { id: 'sheep',   icon: '🐑', name: 'Sheep / Tumaki' },
  { id: 'poultry', icon: '🐓', name: 'Poultry / Kaji' },
];

const ASSESSMENT_TYPES = [
  { id: 'fecal',        icon: '💩', labelEn: 'Stool Analysis', labelHa: 'Binciken Najasa' },
  { id: 'visual',       icon: '👁️', labelEn: 'Visual Symptoms', labelHa: 'Alamomi Masu Ganuwa' },
  { id: 'behavioral',   icon: '🔍', labelEn: 'Behavior Changes', labelHa: 'Canjin Halayya' },
  { id: 'comprehensive',icon: '⚕️', labelEn: 'Full Check', labelHa: 'Cikakken Duba' },
];

const COMMON_SYMPTOMS = [
  'Diarrhea', 'Blood in stool', 'Loss of appetite', 'Weakness/Lethargy',
  'Pale gums', 'Coughing', 'Nasal discharge', 'Swollen joints',
  'Hair/coat loss', 'Limping', 'Bloating', 'High temperature',
];

export default function LivestockScanScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  const [animal, setAnimal] = useState(null);
  const [assessment, setAssessment] = useState('fecal');
  const [images, setImages] = useState([]);
  const [symptoms, setSymptoms] = useState([]);
  const [loading, setLoading] = useState(false);
  const [notes, setNotes] = useState('');

  const pickImage = async (source) => {
    const perms = source === 'camera'
      ? await ImagePicker.requestCameraPermissionsAsync()
      : await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perms.granted) return Alert.alert('Permission required', 'Please allow camera access.');
    const result = source === 'camera'
      ? await ImagePicker.launchCameraAsync({ quality: 0.75 })
      : await ImagePicker.launchImageLibraryAsync({ allowsMultipleSelection: true, quality: 0.75 });
    if (!result.canceled) setImages(prev => [...prev, ...result.assets]);
  };

  const toggleSymptom = (s) =>
    setSymptoms(prev => prev.includes(s) ? prev.filter(x => x !== s) : [...prev, s]);

  const handleSubmit = async () => {
    if (!animal) return Alert.alert('', 'Please select an animal type');
    if (images.length === 0 && assessment !== 'behavioral')
      return Alert.alert('', 'Please add at least one photo');
    setLoading(true);
    try {
      const { diagnosisId } = await diagnoseAPI.livestock({
        animalType: animal.id,
        assessmentType: assessment,
        images,          // empty array is fine for behavioral-only assessments
        symptoms,
        behavioral: { notes },
      });
      router.replace(`/diagnosis/${diagnosisId}`);
    } catch (e) {
      Alert.alert('Error', e.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={{ flex: 1 }}>
      {loading && <LoadingOverlay message="Analysing animal health..." />}
      <ScrollView style={styles.root} contentContainerStyle={styles.content}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} style={styles.back}>
            <Text style={styles.backText}>‹ Back</Text>
          </TouchableOpacity>
          <Text style={styles.headerTitle}>🐄 {t('scanAnimal')}</Text>
        </View>

        {/* Animal Selector */}
        <Text style={styles.stepTitle}>{isHausa ? 'Mataki 1: Zaɓi Dabba' : 'Step 1: Select Animal'}</Text>
        <View style={styles.animalRow}>
          {ANIMALS.map(a => (
            <TouchableOpacity
              key={a.id}
              style={[styles.animalCard, animal?.id === a.id && styles.animalSelected]}
              onPress={() => setAnimal(a)}
            >
              <Text style={styles.animalIcon}>{a.icon}</Text>
              <Text style={[styles.animalName, animal?.id === a.id && styles.animalNameSel]}>{a.name}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Assessment Type */}
        <Text style={styles.stepTitle}>{isHausa ? 'Mataki 2: Nau\'in Duba' : 'Step 2: Assessment Type'}</Text>
        <View style={styles.assessGrid}>
          {ASSESSMENT_TYPES.map(at => (
            <TouchableOpacity
              key={at.id}
              style={[styles.assessCard, assessment === at.id && styles.assessSelected]}
              onPress={() => setAssessment(at.id)}
            >
              <Text style={styles.assessIcon}>{at.icon}</Text>
              <Text style={[styles.assessLabel, assessment === at.id && styles.assessLabelSel]}>
                {isHausa ? at.labelHa : at.labelEn}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Photos */}
        {assessment !== 'behavioral' && (
          <>
            <Text style={styles.stepTitle}>{isHausa ? 'Mataki 3: Ɗauki Hoto' : 'Step 3: Add Photos'}</Text>
            <View style={styles.photoButtons}>
              <TouchableOpacity style={styles.photoBtn} onPress={() => pickImage('camera')}>
                <Text style={styles.photoBtnIcon}>📷</Text>
                <Text style={styles.photoBtnText}>{t('takePhoto')}</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.photoBtn} onPress={() => pickImage('gallery')}>
                <Text style={styles.photoBtnIcon}>🖼️</Text>
                <Text style={styles.photoBtnText}>{t('fromGallery')}</Text>
              </TouchableOpacity>
            </View>
            {images.length > 0 && (
              <ScrollView horizontal style={styles.previews} showsHorizontalScrollIndicator={false}>
                {images.map((img, i) => (
                  <View key={i} style={styles.previewWrap}>
                    <Image source={{ uri: img.uri }} style={styles.previewImg} />
                    <TouchableOpacity
                      style={styles.removeImg}
                      onPress={() => setImages(imgs => imgs.filter((_, j) => j !== i))}
                    >
                      <Text style={{ color: 'white', fontSize: 11 }}>✕</Text>
                    </TouchableOpacity>
                  </View>
                ))}
              </ScrollView>
            )}
          </>
        )}

        {/* Symptoms Checklist */}
        <Text style={styles.stepTitle}>{isHausa ? 'Alamomi da aka lura da su' : 'Observed Symptoms (select all)'}</Text>
        <View style={styles.symptomsGrid}>
          {COMMON_SYMPTOMS.map(s => (
            <TouchableOpacity
              key={s}
              style={[styles.symptomChip, symptoms.includes(s) && styles.symptomSelected]}
              onPress={() => toggleSymptom(s)}
            >
              <Text style={[styles.symptomText, symptoms.includes(s) && styles.symptomTextSel]}>
                {symptoms.includes(s) ? '✓ ' : ''}{s}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Notes */}
        <Text style={styles.stepTitle}>{isHausa ? 'Ƙarin Bayani (na zaɓi)' : 'Additional Notes (optional)'}</Text>
        <TextInput
          style={styles.notesInput}
          value={notes}
          onChangeText={setNotes}
          multiline
          numberOfLines={3}
          placeholder={isHausa ? 'Rubuta ƙarin bayanai...' : 'Describe any other observations...'}
          placeholderTextColor={Colors.textMuted}
        />

        <Button
          title={loading ? 'Analysing...' : (isHausa ? 'Bincika Dabba' : 'Analyse Animal Health')}
          onPress={handleSubmit}
          loading={loading}
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
  stepTitle: { ...Typography.h3, color: Colors.textPrimary, marginTop: Spacing.md, marginBottom: Spacing.sm },
  animalRow: { flexDirection: 'row', gap: Spacing.sm, marginBottom: Spacing.xs },
  animalCard: {
    flex: 1, alignItems: 'center', backgroundColor: Colors.white,
    borderRadius: Radius.md, padding: Spacing.sm, ...Shadows.sm,
    borderWidth: 2, borderColor: 'transparent',
  },
  animalSelected: { borderColor: Colors.primary, backgroundColor: '#D1FAE5' },
  animalIcon: { fontSize: 30 },
  animalName: { ...Typography.tiny, color: Colors.textSecondary, textAlign: 'center', marginTop: 4 },
  animalNameSel: { color: Colors.primary, fontWeight: '700' },
  assessGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm },
  assessCard: {
    width: '47%', backgroundColor: Colors.white, borderRadius: Radius.md,
    padding: Spacing.sm, alignItems: 'center', ...Shadows.sm,
    borderWidth: 2, borderColor: 'transparent',
  },
  assessSelected: { borderColor: Colors.accent, backgroundColor: '#FEF3C7' },
  assessIcon:  { fontSize: 28 },
  assessLabel: { ...Typography.small, color: Colors.textSecondary, textAlign: 'center', marginTop: 4 },
  assessLabelSel: { color: Colors.accentDark, fontWeight: '700' },
  photoButtons: { flexDirection: 'row', gap: Spacing.sm, marginBottom: Spacing.sm },
  photoBtn: {
    flex: 1, alignItems: 'center', backgroundColor: Colors.white,
    borderRadius: Radius.md, padding: Spacing.md, ...Shadows.sm,
    borderWidth: 1.5, borderColor: Colors.border, borderStyle: 'dashed',
  },
  photoBtnIcon: { fontSize: 28, marginBottom: 4 },
  photoBtnText: { ...Typography.small, color: Colors.textSecondary },
  previews:     { marginBottom: Spacing.sm },
  previewWrap:  { marginRight: Spacing.sm, position: 'relative' },
  previewImg:   { width: 90, height: 90, borderRadius: Radius.md },
  removeImg: {
    position: 'absolute', top: 4, right: 4,
    backgroundColor: Colors.danger, borderRadius: 10,
    width: 20, height: 20, alignItems: 'center', justifyContent: 'center',
  },
  symptomsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.xs },
  symptomChip: {
    paddingHorizontal: Spacing.sm, paddingVertical: 6,
    backgroundColor: Colors.white, borderRadius: Radius.full,
    borderWidth: 1.5, borderColor: Colors.border,
  },
  symptomSelected: { backgroundColor: Colors.danger, borderColor: Colors.danger },
  symptomText:     { ...Typography.small, color: Colors.textSecondary },
  symptomTextSel:  { color: Colors.white, fontWeight: '600' },
  notesInput: {
    backgroundColor: Colors.white, borderRadius: Radius.md,
    padding: Spacing.md, ...Typography.body, color: Colors.textPrimary,
    borderWidth: 1, borderColor: Colors.border,
    textAlignVertical: 'top', minHeight: 80,
  },
  submitBtn: { marginTop: Spacing.md },
});
