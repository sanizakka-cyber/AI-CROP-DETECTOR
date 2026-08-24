import React, { useState } from 'react';
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  Image, Alert, TextInput,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import * as ImagePicker from 'expo-image-picker';
import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { diagnoseAPI } from '../../lib/api';
import { validateImageForScanning, qualityLabel } from '../../lib/imageValidator';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';
import { Button, LoadingOverlay } from '../../components/UI';

function QualityIndicator({ score, warnings }) {
  const { label, color } = qualityLabel(score);
  return (
    <View style={[styles.qualityBar, { borderColor: color }]}>
      <Text style={[styles.qualityLabel, { color }]}>Image Quality: {label} ({score}%)</Text>
      {warnings.map((w, i) => <Text key={i} style={styles.qualityWarning}>{w}</Text>)}
    </View>
  );
}

export default function PestScanScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  const [cropType, setCropType]   = useState('');
  const [location, setLocation]   = useState('');
  const [images, setImages]       = useState([]);
  const [loading, setLoading]     = useState(false);
  const [validating, setValidating] = useState(false);
  const [qualityResults, setQualityResults] = useState([]);

  const pickImage = async (source) => {
    const perms = source === 'camera'
      ? await ImagePicker.requestCameraPermissionsAsync()
      : await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perms.granted) return Alert.alert('Permission Required', 'Please allow camera access in your device settings.');

    const result = source === 'camera'
      ? await ImagePicker.launchCameraAsync({ quality: 0.85, exif: true })
      : await ImagePicker.launchImageLibraryAsync({ allowsMultipleSelection: false, quality: 0.85, exif: true });

    if (result.canceled) return;

    setValidating(true);
    const newQuality = [];
    const validAssets = [];

    for (const asset of result.assets) {
      asset._source = source;
      const validation = await validateImageForScanning(asset, 'pest');
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
    setImages(prev => prev.filter((_, j) => j !== i));
    setQualityResults(prev => prev.filter((_, j) => j !== i));
  };

  const handleSubmit = async () => {
    if (images.length === 0) return Alert.alert('', isHausa ? 'Da fatan ɗauki hoto' : 'Please capture at least one photo.');

    const lowQuality = qualityResults.find(q => q.score < 40);
    if (lowQuality) {
      return Alert.alert('Quality Check Failed', 'One or more images failed quality validation. Please retake in better conditions.');
    }

    setLoading(true);
    try {
      const { diagnosisId } = await diagnoseAPI.pest({
        cropType: cropType.trim() || undefined,
        location: location.trim() || undefined,
        images,
      });
      router.replace(`/diagnosis/${diagnosisId}`);
    } catch (e) {
      Alert.alert('Scan Failed', e.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={{ flex: 1 }}>
      {loading && <LoadingOverlay message={isHausa ? 'Ana bincika hoto ta AI...' : 'AI is identifying the pest...'} />}
      {validating && <LoadingOverlay message={isHausa ? 'Ana tabbatar da ingancin hoto...' : 'Validating image quality...'} />}

      <ScrollView style={styles.root} contentContainerStyle={styles.content}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} style={styles.back}>
            <Text style={styles.backText}>‹ Back</Text>
          </TouchableOpacity>
          <View style={styles.headerTitleRow}>
            <MaterialCommunityIcons name="bug" size={22} color={Colors.textPrimary} />
            <Text style={styles.headerTitle}>{isHausa ? 'Gano Kwari' : 'Pest Identification'}</Text>
          </View>
          <Text style={styles.headerSub}>{isHausa ? 'Kwari, ciyayi da cututtukan shuka' : 'Insects, weeds & pathogens'}</Text>
        </View>

        <Text style={styles.stepTitle}>{isHausa ? 'Amfanin Gona da abin ya shafa (na zaɓi)' : 'Affected Crop (optional)'}</Text>
        <TextInput
          style={styles.textInput}
          placeholder={isHausa ? 'misali: masara' : 'e.g. maize'}
          placeholderTextColor={Colors.textMuted}
          value={cropType}
          onChangeText={setCropType}
        />

        <Text style={styles.stepTitle}>{isHausa ? 'Wuri (na zaɓi)' : 'Location (optional)'}</Text>
        <TextInput
          style={styles.textInput}
          placeholder={isHausa ? 'misali: Jihar Katsina' : 'e.g. Katsina State'}
          placeholderTextColor={Colors.textMuted}
          value={location}
          onChangeText={setLocation}
        />

        <Text style={styles.stepTitle}>{isHausa ? 'Ɗauki Hoton Kwaro' : 'Capture Pest Photo'}</Text>
        <View style={styles.photoButtons}>
          <TouchableOpacity style={[styles.photoBtn, styles.photoBtnPrimary]} onPress={() => pickImage('camera')}>
            <MaterialCommunityIcons name="camera" size={28} color={Colors.white} />
            <Text style={styles.photoBtnTextPrimary}>{isHausa ? 'Ɗauki Hoto' : 'Take Photo'}</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.photoBtn} onPress={() => pickImage('gallery')}>
            <MaterialCommunityIcons name="image-multiple-outline" size={28} color={Colors.textSecondary} />
            <Text style={styles.photoBtnText}>{isHausa ? 'Daga Gallery' : 'From Gallery'}</Text>
          </TouchableOpacity>
        </View>

        {images.length > 0 && (
          <ScrollView horizontal style={styles.previews} showsHorizontalScrollIndicator={false}>
            {images.map((img, i) => (
              <View key={i} style={styles.previewWrap}>
                <Image source={{ uri: img.uri }} style={styles.previewImg} />
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
          title={loading ? (isHausa ? 'Ana bincika...' : 'Analysing...') : (isHausa ? 'Gano Kwaro Yanzu' : 'Identify Pest Now')}
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

  stepTitle: { ...Typography.h3, color: Colors.textPrimary, marginTop: Spacing.md, marginBottom: Spacing.sm },

  textInput: {
    backgroundColor: Colors.white, borderRadius: Radius.md, borderWidth: 1.5, borderColor: Colors.border,
    padding: Spacing.sm, color: Colors.textPrimary, ...Typography.body,
  },

  photoButtons:   { flexDirection: 'row', gap: Spacing.sm, marginBottom: Spacing.sm, marginTop: Spacing.xs },
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
