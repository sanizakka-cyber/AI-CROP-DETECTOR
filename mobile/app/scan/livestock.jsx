import React, { useRef, useState } from 'react';
import ScanImageButtons from '../../components/ScanImageButtons';
import { acquireScanImages, newClientScanId } from '../../lib/scanImagePicker';
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  Image, Alert, TextInput,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { diagnoseAPI } from '../../lib/api';
import { validateImageForScanning } from '../../lib/imageValidator';
import { useLocationCapture } from '../../lib/locationCapture';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';
import { Button, LoadingOverlay } from '../../components/UI';
import LocationCaptureSection from '../../components/LocationCaptureSection';
import { showScanError } from '../../lib/scanErrors';

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
  const [qualityResults, setQualityResults] = useState([]);
  const [validating, setValidating] = useState(false);
  const [symptoms, setSymptoms] = useState([]);
  const [loading, setLoading] = useState(false);
  const [notes, setNotes] = useState('');
  const loc = useLocationCapture();

  // Mirrors crop/pest/soil's pickImage exactly (see app/scan/crop.jsx) --
  // livestock was the one scan screen that never ran images through
  // validateImageForScanning() at all, so a blurry, tiny, or stale photo
  // (the exact cases the other three scans reject) sailed through
  // unchecked here. Looped, not single-asset, since this screen alone
  // allows multi-select.
  // Generated once per submission and reused if the farmer retries after a
  // network failure, so the backend returns the scan it already created
  // instead of creating (and counting) a duplicate.
  const scanIdRef = useRef(null);
  // Upload percentage while the photo is sent (null = not uploading).
  const [uploadPct, setUploadPct] = useState(null);

  const pickImage = async (source) => {
    // A new photo means a new scan: a later submit must not reuse the old id.
    scanIdRef.current = null;
    const result = await acquireScanImages(source, { multiple: true, quality: 0.75 });
    if (result.error) return Alert.alert(isHausa ? 'Ba a karɓi hoton ba' : 'Photo not accepted', result.error);
    if (result.canceled) return;

    setValidating(true);
    const newQuality = [];
    const validAssets = [];
    for (const asset of result.assets) {
      asset._source = source;
      const validation = await validateImageForScanning(asset, 'livestock');
      if (!validation.valid) {
        Alert.alert('Image Rejected', validation.error);
        setValidating(false);
        return;
      }
      newQuality.push({ score: validation.qualityScore, warnings: validation.warnings });
      validAssets.push(asset);
    }
    setImages(prev => [...prev, ...validAssets]);
    setQualityResults(prev => [...prev, ...newQuality]);
    setValidating(false);
  };

  const toggleSymptom = (s) =>
    setSymptoms(prev => prev.includes(s) ? prev.filter(x => x !== s) : [...prev, s]);

  const handleSubmit = async () => {
    if (loading) return;
    if (!animal) return Alert.alert('', 'Please select an animal type');
    if (images.length === 0 && assessment !== 'behavioral')
      return Alert.alert('', 'Please add at least one photo');

    // Final quality gate, matching crop/pest/soil exactly.
    const lowQuality = qualityResults.find(q => q.score < 40);
    if (lowQuality) {
      return Alert.alert('Quality Check Failed', '❌ One or more images failed quality validation. Please retake in better conditions.');
    }

    setLoading(true);
    try {
      if (!scanIdRef.current) scanIdRef.current = newClientScanId();
      setUploadPct(0);
      const { diagnosisId } = await diagnoseAPI.livestock({
        clientScanId: scanIdRef.current,
        onProgress: setUploadPct,
        animalType: animal.id,
        assessmentType: assessment,
        images,          // empty array is fine for behavioral-only assessments
        symptoms,
        behavioral: { notes },
        location: loc.toApiFields(),
      });
      router.replace(`/diagnosis/${diagnosisId}`);
    } catch (e) {
      // Coded failure from the server (or a lost connection): plain message,
      // reference, next step; staff also get technical details.
      showScanError(e, {
        lang: i18n.language,
        onRetry: () => handleSubmit(),
        onSignIn: () => router.replace('/(auth)/login'),
        onUpgrade: () => router.push('/(tabs)/subscription'),
        onChoosePhoto: () => setImages([]),
      });
    } finally {
      setLoading(false);
      setUploadPct(null);
    }
  };

  return (
    <View style={{ flex: 1 }}>
      {loading && <LoadingOverlay message={uploadPct !== null && uploadPct < 100 ? `${isHausa ? 'Ana aika hoto' : 'Uploading photo'}… ${uploadPct}%` : (isHausa ? 'Ana bincika lafiyar dabba...' : 'Analysing animal health...')} />}
      {validating && <LoadingOverlay message="Validating image quality..." />}
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
            <ScanImageButtons onPick={pickImage} isHausa={isHausa} disabled={validating || loading} />
            {images.length > 0 && (
              <ScrollView horizontal style={styles.previews} showsHorizontalScrollIndicator={false}>
                {images.map((img, i) => (
                  <View key={i} style={styles.previewWrap}>
                    <Image source={{ uri: img.uri }} style={styles.previewImg} />
{img.fileName ? <Text numberOfLines={1} style={{ fontSize: 10, maxWidth: 80, color: '#64748B' }}>{img.fileName}</Text> : null}
                    <TouchableOpacity
                      style={styles.removeImg}
                      onPress={() => {
                        scanIdRef.current = null;
                        setImages(imgs => imgs.filter((_, j) => j !== i));
                        setQualityResults(qs => qs.filter((_, j) => j !== i));
                      }}
                    >
                      <Text style={{ color: 'white', fontSize: 11 }}>✕</Text>
                    </TouchableOpacity>
                  </View>
                ))}
              </ScrollView>
            )}
          </>
        )}

        <LocationCaptureSection loc={loc} />

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
