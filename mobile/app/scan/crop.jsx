import React, { useState } from 'react';
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  Image, Alert, ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import * as ImagePicker from 'expo-image-picker';
import { diagnoseAPI } from '../../lib/api';
import { validateImageForScanning, qualityLabel } from '../../lib/imageValidator';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';
import { Button, LoadingOverlay } from '../../components/UI';

const CROPS = [
  { id: 'maize',    icon: '🌽', name: 'Maize / Masara' },
  { id: 'sorghum',  icon: '🌾', name: 'Sorghum / Dawa' },
  { id: 'millet',   icon: '🌿', name: 'Millet / Gero' },
  { id: 'rice',     icon: '🍚', name: 'Rice / Shinkafa' },
  { id: 'tomato',   icon: '🍅', name: 'Tomato / Tumatir' },
  { id: 'beans',    icon: '🫘', name: 'Beans / Wake' },
  { id: 'cowpea',   icon: '🫛', name: 'Cowpea / Waken Gizo' },
  { id: 'groundnut',icon: '🥜', name: 'Groundnut / Gyada' },
  { id: 'cassava',  icon: '🥔', name: 'Cassava / Rogo' },
  { id: 'yam',      icon: '🍠', name: 'Yam / Doya' },
  { id: 'pepper',   icon: '🌶️', name: 'Pepper / Barkono' },
  { id: 'onions',   icon: '🧅', name: 'Onions / Albasa' },
  { id: 'soybeans', icon: '🌱', name: 'Soybeans / Waken Soya' },
];

const PARTS = ['Leaf Top', 'Leaf Bottom', 'Stem', 'Whole Plant', 'Root', 'Fruit'];

function QualityIndicator({ score, warnings }) {
  const { label, color } = qualityLabel(score);
  return (
    <View style={[styles.qualityBar, { borderColor: color }]}>
      <View style={styles.qualityRow}>
        <Text style={[styles.qualityLabel, { color }]}>📊 Image Quality: {label} ({score}%)</Text>
      </View>
      {warnings.map((w, i) => <Text key={i} style={styles.qualityWarning}>{w}</Text>)}
    </View>
  );
}

export default function CropScanScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  const [crop, setCrop]           = useState(null);
  const [part, setPart]           = useState('Leaf Top');
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
      asset._source = source; // tag for trust scoring
      const validation = await validateImageForScanning(asset, 'crop');
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
    if (!crop) return Alert.alert('', isHausa ? 'Da fatan zaɓi amfanin gona' : 'Please select a crop first.');
    if (images.length === 0) return Alert.alert('', isHausa ? 'Da fatan ɗauki hoto' : 'Please capture at least one photo.');

    // Final quality gate — reject if any image score < 40
    const lowQuality = qualityResults.find(q => q.score < 40);
    if (lowQuality) {
      return Alert.alert('Quality Check Failed', `❌ One or more images failed quality validation. Please retake in better conditions.`);
    }

    setLoading(true);
    try {
      const { diagnosisId } = await diagnoseAPI.crop({ cropType: crop.id, cropPart: part, images });
      router.replace(`/diagnosis/${diagnosisId}`);
    } catch (e) {
      Alert.alert('Scan Failed', e.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={{ flex: 1 }}>
      {loading && <LoadingOverlay message={isHausa ? 'Ana bincika hoto ta AI...' : 'AI is analysing your photo...'} />}
      {validating && <LoadingOverlay message={isHausa ? 'Ana tabbatar da ingancin hoto...' : 'Validating image quality...'} />}

      <ScrollView style={styles.root} contentContainerStyle={styles.content}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} style={styles.back}>
            <Text style={styles.backText}>‹ Back</Text>
          </TouchableOpacity>
          <Text style={styles.headerTitle}>🌽 {isHausa ? 'Bincika Amfanin Gona' : 'Crop Disease Scanner'}</Text>
          <Text style={styles.headerSub}>{isHausa ? 'Hotuna na gaske ne kawai za a yarda' : 'Only authentic, fresh photos are accepted'}</Text>
        </View>

        {/* Authenticity Notice */}
        <View style={styles.authNotice}>
          <Text style={styles.authNoticeText}>
            🔒 {isHausa
              ? 'Tsarin yana tabbatar da ingancinchancin hoto kafin bincike. Hotuna daga kamara kai tsaye sun fi dacewa.'
              : 'System validates image authenticity before scanning. Direct camera captures give most accurate results.'}
          </Text>
        </View>

        {/* Step 1: Crop */}
        <Text style={styles.stepTitle}>{isHausa ? 'Mataki 1: Zaɓi Amfanin Gona' : 'Step 1: Select Crop Type'}</Text>
        <View style={styles.cropGrid}>
          {CROPS.map(c => (
            <TouchableOpacity key={c.id} style={[styles.cropItem, crop?.id === c.id && styles.cropSelected]} onPress={() => setCrop(c)}>
              <Text style={styles.cropIcon}>{c.icon}</Text>
              <Text style={[styles.cropName, crop?.id === c.id && styles.cropNameSel]}>{c.name}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Step 2: Part */}
        <Text style={styles.stepTitle}>{isHausa ? 'Mataki 2: Wane ɓangare?' : 'Step 2: Affected Plant Part'}</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.partsRow}>
          {PARTS.map(p => (
            <TouchableOpacity key={p} style={[styles.partChip, part === p && styles.partChipSel]} onPress={() => setPart(p)}>
              <Text style={[styles.partText, part === p && styles.partTextSel]}>{p}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>

        {/* Step 3: Photos */}
        <Text style={styles.stepTitle}>{isHausa ? 'Mataki 3: Ɗauki Hoto (Daga Kamara)' : 'Step 3: Capture Photo (Camera Preferred)'}</Text>

        {/* Camera recommended banner */}
        <View style={styles.cameraTip}>
          <Text style={styles.cameraTipText}>
            📷 {isHausa ? 'Kamara kai tsaye ta fi amfani — na hotuna da aka ɗauka sama da awanni 48 za a ƙi' : 'Direct camera capture preferred — images older than 48 hours will be rejected'}
          </Text>
        </View>

        <View style={styles.photoButtons}>
          <TouchableOpacity style={[styles.photoBtn, styles.photoBtnPrimary]} onPress={() => pickImage('camera')}>
            <Text style={styles.photoBtnIcon}>📷</Text>
            <Text style={styles.photoBtnTextPrimary}>{isHausa ? 'Ɗauki Hoto' : 'Take Photo'}</Text>
            <Text style={styles.photoBtnBadge}>RECOMMENDED</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.photoBtn} onPress={() => pickImage('gallery')}>
            <Text style={styles.photoBtnIcon}>🖼️</Text>
            <Text style={styles.photoBtnText}>{isHausa ? 'Daga Gallery' : 'From Gallery'}</Text>
          </TouchableOpacity>
        </View>

        {/* Image Previews with Quality */}
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
                  <Text style={{ color: 'white', fontSize: 11 }}>✕</Text>
                </TouchableOpacity>
              </View>
            ))}
          </ScrollView>
        )}

        {/* Per-image quality warnings */}
        {qualityResults.map((q, i) => q.warnings.length > 0 && (
          <QualityIndicator key={i} score={q.score} warnings={q.warnings} />
        ))}

        {/* Submit */}
        <Button
          title={loading ? (isHausa ? 'Ana bincika...' : 'Analysing...') : (isHausa ? 'Bincika Amfanin Gona' : 'Analyse Crop Now')}
          onPress={handleSubmit}
          loading={loading}
          style={styles.submitBtn}
        />

        {/* Tips */}
        <View style={styles.tipsBox}>
          <Text style={styles.tipsTitle}>{isHausa ? '💡 Shawarwari don Sakamako Mafi Kyau' : '💡 Tips for Best Accuracy'}</Text>
          {(isHausa ? [
            '✓ Ɗauki hoto a haske mai kyau na yanayi',
            '✓ Nuna sashin da yake cutar a sarari',
            '✓ Ku yi kusa da shuka — cike firam',
            '✓ Ɗauki hoto daga kusurwoyi daban-daban',
            '✗ Kar a yi amfani da hotuna da aka gyara',
            '✗ Hotuna daga intanet ba za a yarda ba',
          ] : [
            '✓ Capture in good natural light — avoid shadows',
            '✓ Show the affected area clearly and in focus',
            '✓ Get close to the plant — fill the frame',
            '✓ Take photos from multiple angles',
            '✗ Do not use edited, filtered or old photos',
            '✗ Downloaded or stock images will be rejected',
          ]).map((tip, i) => (
            <Text key={i} style={[styles.tip, { color: tip.startsWith('✗') ? Colors.danger : Colors.textSecondary }]}>{tip}</Text>
          ))}
        </View>
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
  headerSub:   { ...Typography.small, color: Colors.textSecondary, marginTop: 2 },

  authNotice: {
    backgroundColor: '#F0FDF4', borderRadius: Radius.md,
    padding: Spacing.sm, marginBottom: Spacing.sm,
    borderWidth: 1, borderColor: '#BBF7D0',
  },
  authNoticeText: { ...Typography.small, color: Colors.success, lineHeight: 20 },

  stepTitle: { ...Typography.h3, color: Colors.textPrimary, marginTop: Spacing.md, marginBottom: Spacing.sm },

  cropGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm },
  cropItem: {
    width: '22%', alignItems: 'center', backgroundColor: Colors.white,
    borderRadius: Radius.md, padding: Spacing.sm, ...Shadows.sm,
    borderWidth: 2, borderColor: 'transparent',
  },
  cropSelected:   { borderColor: Colors.primary, backgroundColor: '#D1FAE5' },
  cropIcon:       { fontSize: 28 },
  cropName:       { ...Typography.tiny, color: Colors.textSecondary, textAlign: 'center', marginTop: 4 },
  cropNameSel:    { color: Colors.primary, fontWeight: '700' },

  partsRow: { marginBottom: Spacing.sm },
  partChip: {
    paddingHorizontal: Spacing.sm, paddingVertical: 6, marginRight: Spacing.xs,
    backgroundColor: Colors.white, borderRadius: Radius.full,
    borderWidth: 1.5, borderColor: Colors.border,
  },
  partChipSel: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  partText:    { ...Typography.small, color: Colors.textSecondary },
  partTextSel: { color: Colors.white, fontWeight: '600' },

  cameraTip: { backgroundColor: '#EFF6FF', borderRadius: Radius.md, padding: Spacing.sm, marginBottom: Spacing.sm, borderWidth: 1, borderColor: '#BFDBFE' },
  cameraTipText: { ...Typography.small, color: '#1D4ED8' },

  photoButtons:   { flexDirection: 'row', gap: Spacing.sm, marginBottom: Spacing.sm },
  photoBtn: {
    flex: 1, alignItems: 'center', backgroundColor: Colors.white,
    borderRadius: Radius.md, padding: Spacing.md, ...Shadows.sm,
    borderWidth: 1.5, borderColor: Colors.border, borderStyle: 'dashed',
  },
  photoBtnPrimary: { backgroundColor: Colors.primary, borderColor: Colors.primary, borderStyle: 'solid' },
  photoBtnIcon:    { fontSize: 32, marginBottom: 4 },
  photoBtnText:    { ...Typography.small, color: Colors.textSecondary },
  photoBtnTextPrimary: { ...Typography.small, color: Colors.white, fontWeight: '600' },
  photoBtnBadge:   { ...Typography.tiny, color: 'rgba(255,255,255,0.8)', marginTop: 2, fontWeight: '700' },

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
  qualityRow:    { flexDirection: 'row', alignItems: 'center' },
  qualityLabel:  { ...Typography.small, fontWeight: '700' },
  qualityWarning:{ ...Typography.tiny, color: Colors.textSecondary, marginTop: 4 },

  submitBtn: { marginTop: Spacing.md },

  tipsBox: { backgroundColor: '#F8FAFC', borderRadius: Radius.md, padding: Spacing.md, marginTop: Spacing.md, borderWidth: 1, borderColor: Colors.border },
  tipsTitle: { ...Typography.label, color: Colors.textPrimary, marginBottom: Spacing.sm },
  tip:       { ...Typography.small, color: Colors.textSecondary, marginBottom: 4 },
});
