import React, { useRef, useState } from 'react';
import ScanImageButtons from '../../components/ScanImageButtons';
import { acquireScanImages, newClientScanId } from '../../lib/scanImagePicker';
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  Image, Alert, TextInput,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { diagnoseAPI } from '../../lib/api';
import { validateImageForScanning, qualityLabel } from '../../lib/imageValidator';
import { useLocationCapture } from '../../lib/locationCapture';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';
import { Button, LoadingOverlay } from '../../components/UI';
import LocationCaptureSection from '../../components/LocationCaptureSection';
import { showScanError } from '../../lib/scanErrors';
import { saveScanOffline } from '../../lib/scanQueue';

function QualityIndicator({ score, warnings }) {
  const { label, color } = qualityLabel(score);
  return (
    <View style={[styles.qualityBar, { borderColor: color }]}>
      <Text style={[styles.qualityLabel, { color }]}>Image Quality: {label} ({score}%)</Text>
      {warnings.map((w, i) => <Text key={i} style={styles.qualityWarning}>{w}</Text>)}
    </View>
  );
}

export default function SoilScanScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  const [context, setContext]     = useState('');
  const [images, setImages]       = useState([]);
  const [loading, setLoading]     = useState(false);
  const [validating, setValidating] = useState(false);
  const [qualityResults, setQualityResults] = useState([]);
  const loc = useLocationCapture();

  // Generated once per submission and reused if the farmer retries after a
  // network failure, so the backend returns the scan it already created
  // instead of creating (and counting) a duplicate.
  const scanIdRef = useRef(null);
  // Upload percentage while the photo is sent (null = not uploading).
  const [uploadPct, setUploadPct] = useState(null);

  const pickImage = async (source) => {
    // A new photo means a new scan: a later submit must not reuse the old id.
    scanIdRef.current = null;
    const result = await acquireScanImages(source, { multiple: false, quality: 0.85 });
    if (result.error) return Alert.alert(isHausa ? 'Ba a karɓi hoton ba' : 'Photo not accepted', result.error);
    if (result.canceled) return;

    setValidating(true);
    const newQuality = [];
    const validAssets = [];

    for (const asset of result.assets) {
      asset._source = source;
      const validation = await validateImageForScanning(asset, 'soil');
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

  const removeImage = (i) => {
    scanIdRef.current = null;
    setImages(prev => prev.filter((_, j) => j !== i));
    setQualityResults(prev => prev.filter((_, j) => j !== i));
  };

  const handleSubmit = async () => {
    if (loading) return;
    if (images.length === 0) return Alert.alert('', isHausa ? 'Da fatan ɗauki hoto' : 'Please capture at least one photo.');

    const lowQuality = qualityResults.find(q => q.score < 40);
    if (lowQuality) {
      return Alert.alert('Quality Check Failed', 'One or more images failed quality validation. Please retake in better conditions.');
    }

    setLoading(true);
    let payload = null;
    try {
      if (!scanIdRef.current) scanIdRef.current = newClientScanId();
      setUploadPct(0);
      payload = {
        clientScanId: scanIdRef.current,
        onProgress: setUploadPct,
        soilContext: context.trim() || undefined,
        images,
        location: loc.toApiFields(),
      };
      const { diagnosisId } = await diagnoseAPI.soil(payload);
      router.replace(`/diagnosis/${diagnosisId}`);
    } catch (e) {
      // No internet: the scan, its photos and GPS point are saved on the phone and sent later.
      if (await saveScanOffline(e, 'soil', payload, { t, router })) return;      // Coded failure from the server (or a lost connection): plain message,
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
      {loading && <LoadingOverlay message={uploadPct !== null && uploadPct < 100 ? `${isHausa ? 'Ana aika hoto' : 'Uploading photo'}… ${uploadPct}%` : (isHausa ? 'Ana bincika hoto ta AI...' : 'AI is analysing your soil sample...')} />}
      {validating && <LoadingOverlay message={isHausa ? 'Ana tabbatar da ingancin hoto...' : 'Validating image quality...'} />}

      <ScrollView style={styles.root} contentContainerStyle={styles.content}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} style={styles.back}>
            <Text style={styles.backText}>‹ Back</Text>
          </TouchableOpacity>
          <View style={styles.headerTitleRow}>
            <MaterialCommunityIcons name="shovel" size={22} color={Colors.textPrimary} />
            <Text style={styles.headerTitle}>{isHausa ? 'Bincika Ƙasa' : 'Soil Sample Scanner'}</Text>
          </View>
          <Text style={styles.headerSub}>{isHausa ? 'Abinci da shawarwarin gudanarwa' : 'Nutrients & management recommendations'}</Text>
        </View>

        <View style={styles.noticeBox}>
          <Text style={styles.noticeText}>
            {isHausa
              ? 'Wannan kimantawa ce ta AI daga hoto, ba sakamakon lab ba ne. Domin ainihin ma\'aunin sinadarai, tuntuɓi jami\'in fadadawa don gwajin ƙasa na lab.'
              : 'This is an AI visual estimate, not a laboratory-confirmed result. For precise nutrient/pH values, consult an extension officer for a lab soil test.'}
          </Text>
        </View>

        <Text style={styles.stepTitle}>{isHausa ? 'Bayanin Gona (na zaɓi)' : 'Farm Context (optional)'}</Text>
        <TextInput
          style={styles.contextInput}
          placeholder={isHausa ? 'misali: filin masara, ana shayarwa akai-akai...' : 'e.g. maize field, irrigated regularly, sandy area...'}
          placeholderTextColor={Colors.textMuted}
          value={context}
          onChangeText={setContext}
          multiline
        />

        <LocationCaptureSection loc={loc} />

        <Text style={styles.stepTitle}>{isHausa ? 'Ɗauki Hoton Ƙasa' : 'Capture Soil Photo'}</Text>
        <ScanImageButtons onPick={pickImage} isHausa={isHausa} disabled={validating || loading} />

        {images.length > 0 && (
          <ScrollView horizontal style={styles.previews} showsHorizontalScrollIndicator={false}>
            {images.map((img, i) => (
              <View key={i} style={styles.previewWrap}>
                <Image source={{ uri: img.uri }} style={styles.previewImg} />
{img.fileName ? <Text numberOfLines={1} style={{ fontSize: 10, maxWidth: 80, color: '#64748B' }}>{img.fileName}</Text> : null}
                {qualityResults[i] && (
                  <View style={[styles.qualityOverlay, { backgroundColor: qualityLabel(qualityResults[i].score).color }]}>
                    <Text style={styles.qualityOverlayText}>{qualityResults[i].score}%</Text>
                  </View>
                )}
                <TouchableOpacity style={styles.removeImg} onPress={() => removeImage(i)}>
                  <MaterialCommunityIcons name="close" size={13} color={Colors.white} />
                </TouchableOpacity>
              </View>
            ))}
          </ScrollView>
        )}

        {qualityResults.map((q, i) => q.warnings.length > 0 && (
          <QualityIndicator key={i} score={q.score} warnings={q.warnings} />
        ))}

        <Button
          title={loading ? (isHausa ? 'Ana bincika...' : 'Analysing...') : (isHausa ? 'Bincika Ƙasa' : 'Analyse Soil Now')}
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
  headerTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  headerTitle: { ...Typography.h2, color: Colors.textPrimary },
  headerSub:   { ...Typography.small, color: Colors.textSecondary, marginTop: 2 },

  noticeBox: {
    backgroundColor: '#FFFBEB', borderRadius: Radius.md,
    padding: Spacing.sm, marginBottom: Spacing.sm,
    borderWidth: 1, borderColor: '#FDE68A',
  },
  noticeText: { ...Typography.small, color: '#92400E', lineHeight: 20 },

  stepTitle: { ...Typography.h3, color: Colors.textPrimary, marginTop: Spacing.md, marginBottom: Spacing.sm },

  contextInput: {
    backgroundColor: Colors.white, borderRadius: Radius.md, borderWidth: 1.5, borderColor: Colors.border,
    padding: Spacing.sm, minHeight: 60, textAlignVertical: 'top', color: Colors.textPrimary, ...Typography.body,
  },

  photoButtons:   { flexDirection: 'row', gap: Spacing.sm, marginBottom: Spacing.sm },
  photoBtn: {
    flex: 1, alignItems: 'center', backgroundColor: Colors.white,
    borderRadius: Radius.md, padding: Spacing.md, ...Shadows.sm,
    borderWidth: 1.5, borderColor: Colors.border, borderStyle: 'dashed', gap: 4,
  },
  photoBtnPrimary: { backgroundColor: Colors.primary, borderColor: Colors.primary, borderStyle: 'solid' },
  photoBtnText:    { ...Typography.small, color: Colors.textSecondary },
  photoBtnTextPrimary: { ...Typography.small, color: Colors.white, fontWeight: '600' },

  previews:    { marginBottom: Spacing.sm },
  previewWrap: { marginRight: Spacing.sm, position: 'relative' },
  previewImg:  { width: 100, height: 100, borderRadius: Radius.md },
  qualityOverlay: {
    position: 'absolute', bottom: 4, left: 4,
    borderRadius: 8, paddingHorizontal: 6, paddingVertical: 2,
  },
  qualityOverlayText: { ...Typography.tiny, color: Colors.white, fontWeight: '700' },
  removeImg: {
    position: 'absolute', top: 4, right: 4,
    backgroundColor: Colors.danger, borderRadius: 10,
    width: 20, height: 20, alignItems: 'center', justifyContent: 'center',
  },

  qualityBar: {
    borderRadius: Radius.md, borderWidth: 1.5,
    padding: Spacing.sm, marginBottom: Spacing.xs,
  },
  qualityLabel:  { ...Typography.small, fontWeight: '700' },
  qualityWarning:{ ...Typography.tiny, color: Colors.textSecondary, marginTop: 4 },

  submitBtn: { marginTop: Spacing.md },
});
