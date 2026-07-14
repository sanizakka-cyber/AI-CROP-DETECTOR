import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Linking,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { diagnoseAPI, marketplaceAPI, cartAPI } from '../../lib/api';
import { Colors, Radius, Spacing, Typography, Shadows } from '../../constants/Theme';
import { Button, Card, SeverityBadge } from '../../components/UI';
import { useLanguage } from '../../context/LanguageContext';
import { VoiceService } from '../../services/VoiceService';

const DEFAULT_EXPERT_PHONE = '08129582957';
const DEFAULT_WHATSAPP = 'https://wa.me/2348129582957';

export default function DiagnosisDetailScreen() {
  const { id } = useLocalSearchParams();
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const { language, voiceEnabled } = useLanguage();
  const isHausa = i18n.language === 'ha';

  const [diag, setDiag] = useState(null);
  const [polling, setPolling] = useState(true);
  const [activeTab, setActiveTab] = useState('treatment');
  const [feedbackSent, setFeedbackSent] = useState(false);
  const [recProducts, setRecProducts] = useState([]);
  const [addingId, setAddingId] = useState(null);

  // Voice state
  const [voicePlaying, setVoicePlaying] = useState(false);
  const [voiceSpeed, setVoiceSpeed] = useState(1.0);
  const voiceNarration = useRef(null);

  const load = useCallback(async () => {
    try {
      const { diagnosis } = await diagnoseAPI.get(id);
      setDiag(diagnosis);
      if (diagnosis.status === 'processed' || diagnosis.status === 'failed') {
        setPolling(false);
        if (diagnosis.status === 'processed') {
          fetchRecommendations(diagnosis);
          if (voiceEnabled && !voiceNarration.current) {
            setTimeout(() => playVoice(diagnosis), 600);
          }
        }
      }
    } catch {
      setPolling(false);
    }
  }, [id]);

  const fetchRecommendations = async (diagnosis) => {
    try {
      const result = diagnosis.aiResult || {};
      const plan   = diagnosis.treatmentPlan || {};
      const tags   = [
        diagnosis.type === 'crop' ? 'crop' : 'livestock',
        result.primaryDiagnosis,
        ...(plan.chemicalTreatments || []).map(t => t.product).filter(Boolean),
        ...(result.likelyCauses    || []),
      ].filter(Boolean).map(t => String(t).toLowerCase().trim()).slice(0, 6);
      const res = await marketplaceAPI.recommended(tags);
      setRecProducts((res.data || []).slice(0, 5));
    } catch {}
  };

  const buildNarration = (diagnosis) => {
    const result = diagnosis.aiResult    || {};
    const plan   = diagnosis.treatmentPlan || {};
    return diagnosis.type === 'livestock'
      ? VoiceService.buildLivestockNarration(result, plan, language)
      : VoiceService.buildCropNarration(result, plan, language);
  };

  const playVoice = (diagnosis, speed = voiceSpeed) => {
    const text = voiceNarration.current || buildNarration(diagnosis);
    voiceNarration.current = text;
    setVoicePlaying(true);
    VoiceService.speak(text, language, {
      rate: speed,
      onDone: () => setVoicePlaying(false),
      onError: () => setVoicePlaying(false),
    });
  };

  const stopVoice = () => {
    VoiceService.stop();
    setVoicePlaying(false);
  };

  const addToCart = async (product) => {
    setAddingId(product.id);
    try {
      await cartAPI.add(product.id, 1);
      alert(`${product.name} added to cart!`);
    } catch (e) {
      alert(e.message || 'Could not add to cart');
    } finally {
      setAddingId(null);
    }
  };

  useEffect(() => {
    load();
    const interval = setInterval(() => {
      if (polling) load();
    }, 3000);
    return () => {
      clearInterval(interval);
      VoiceService.stop();
    };
  }, [load, polling]);

  const sendFeedback = async (wasHelpful) => {
    try {
      await diagnoseAPI.feedback(id, { wasHelpful, outcome: 'pending' });
      setFeedbackSent(true);
    } catch {}
  };

  if (!diag || diag.status === 'pending') {
    return (
      <View style={styles.loadingScreen}>
        <ActivityIndicator size="large" color={Colors.primary} />
        <Text style={styles.loadingText}>{isHausa ? 'Ana sarrafa hoton...' : 'AI is validating and analysing your scan...'}</Text>
        <Text style={styles.loadingTip}>
          {isHausa ? 'Ana fara tabbatar da hoton kafin gano cuta.' : 'The system first checks that the image is agricultural or livestock related.'}
        </Text>
      </View>
    );
  }

  if (diag.status === 'failed') {
    return (
      <View style={styles.loadingScreen}>
        <Text style={styles.failedIcon}>!</Text>
        <Text style={styles.loadingText}>Analysis failed. Please try again with a clear plant or livestock image.</Text>
        <Button title="Try Again" onPress={() => router.back()} />
      </View>
    );
  }

  const result = diag.aiResult || {};
  const plan = diag.treatmentPlan || {};
  const validation = diag.imageValidation || result.validation || {};
  const confidence = Number(result.confidence || 0);
  const isEmergency = result.severity === 'emergency';
  const consultation = plan.consultation || {};
  const expertType = consultation.expertType || result.expertType || (diag.type === 'crop' ? 'agronomist' : 'vet');
  const expertLabel = expertType === 'agronomist' ? 'Agronomist' : 'Vet Doctor';
  const callNumber = consultation.callNumber || DEFAULT_EXPERT_PHONE;
  const whatsappUrl = consultation.whatsapp || DEFAULT_WHATSAPP;
  const consultationRecommended =
    consultation.recommended ||
    result.needsExpertReview ||
    result.needsVetVisit ||
    confidence < 70 ||
    ['severe', 'emergency'].includes(result.severity);

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.content}>
      <View style={[styles.resultHeader, isEmergency && styles.emergencyBg]}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backBtn}>Back to records</Text>
        </TouchableOpacity>
        <Text style={styles.resultTitle}>{t('result')}</Text>
        <Text style={styles.diagnosisName}>
          {isHausa ? result.primaryDiagnosisHa || result.primaryDiagnosis : result.primaryDiagnosis}
        </Text>
        <View style={styles.metaRow}>
          <SeverityBadge severity={result.severity} />
          <View style={styles.confBadge}>
            <Text style={styles.confText}>{confidence}% confidence</Text>
          </View>
          <View style={styles.qualityBadge}>
            <Text style={styles.qualityText}>Image: {validation.quality?.status || 'accepted'}</Text>
          </View>
        </View>
        {result.contagionRisk && result.contagionRisk !== 'none' && (
          <View style={styles.contagionRow}>
            <Text style={styles.contagionText}>Contagion risk: {String(result.contagionRisk).toUpperCase()}</Text>
          </View>
        )}
      </View>

      {/* Voice Control Bar */}
      <View style={styles.voiceBar}>
        <TouchableOpacity
          style={[styles.voiceBtn, voicePlaying && styles.voiceBtnActive]}
          onPress={() => voicePlaying ? stopVoice() : playVoice(diag)}
        >
          <Text style={styles.voiceBtnIcon}>{voicePlaying ? '⏸' : '▶'}</Text>
          <Text style={styles.voiceBtnLabel}>{voicePlaying ? (isHausa ? 'Tsaya' : 'Pause') : (isHausa ? 'Kara' : 'Play')}</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.voiceBtn}
          onPress={() => { stopVoice(); setTimeout(() => playVoice(diag), 100); }}
        >
          <Text style={styles.voiceBtnIcon}>↩</Text>
          <Text style={styles.voiceBtnLabel}>{isHausa ? 'Sake' : 'Replay'}</Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.voiceBtn} onPress={stopVoice}>
          <Text style={styles.voiceBtnIcon}>■</Text>
          <Text style={styles.voiceBtnLabel}>{isHausa ? 'Daina' : 'Stop'}</Text>
        </TouchableOpacity>

        {/* Speed selector */}
        <View style={styles.speedRow}>
          {[0.75, 1.0, 1.25, 1.5].map((sp) => (
            <TouchableOpacity
              key={sp}
              style={[styles.speedPill, voiceSpeed === sp && styles.speedPillActive]}
              onPress={() => {
                setVoiceSpeed(sp);
                if (voicePlaying) { stopVoice(); setTimeout(() => playVoice(diag, sp), 100); }
              }}
            >
              <Text style={[styles.speedText, voiceSpeed === sp && styles.speedTextActive]}>{sp}x</Text>
            </TouchableOpacity>
          ))}
        </View>
      </View>

      {consultationRecommended && (
        <Card style={styles.consultCard}>
          <Text style={styles.consultTitle}>Professional consultation is recommended.</Text>
          <Text style={styles.consultBody}>
            {diag.type === 'crop'
              ? 'This plant case should be reviewed by an agronomist for safe treatment and quality input advice.'
              : 'This animal case should be reviewed by a veterinary doctor before medication is administered.'}
          </Text>
          <View style={styles.consultGrid}>
            <TouchableOpacity style={styles.consultBtn} onPress={() => Linking.openURL(whatsappUrl)}>
              <Text style={styles.consultBtnText}>Chat with {expertLabel}</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.callBtn} onPress={() => Linking.openURL(`tel:${callNumber}`)}>
              <Text style={styles.callBtnText}>Call {expertLabel}</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.emergencyBtn} onPress={() => Linking.openURL(`tel:${callNumber}`)}>
              <Text style={styles.emergencyBtnText}>Emergency Support</Text>
            </TouchableOpacity>
          </View>
        </Card>
      )}

      {result.likelyCauses?.length > 0 && (
        <Card style={styles.card}>
          <Text style={styles.cardTitle}>{isHausa ? 'Dalilan yiwuwa' : 'Likely Causes'}</Text>
          {result.likelyCauses.map((cause, index) => (
            <Text key={index} style={styles.listItem}>- {cause}</Text>
          ))}
        </Card>
      )}

      <Text style={styles.sectionTitle}>{t('treatment')}</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.tabRow}>
        {[
          ['treatment', isHausa ? 'Gaggawa' : 'Immediate'],
          ['organic', isHausa ? 'Na halitta' : 'Organic'],
          ['chemical', isHausa ? 'Magani' : 'Medication'],
          ['prevention', isHausa ? 'Kariya' : 'Prevention'],
        ].map(([idValue, label]) => (
          <TouchableOpacity
            key={idValue}
            style={[styles.tab, activeTab === idValue && styles.tabActive]}
            onPress={() => setActiveTab(idValue)}
          >
            <Text style={[styles.tabText, activeTab === idValue && styles.tabTextActive]}>{label}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {activeTab === 'treatment' && plan.immediateActions?.map((action, index) => (
        <Card key={index} style={styles.treatCard}>
          <Text style={styles.treatTitle}>Action {index + 1}</Text>
          <Text style={styles.treatBody}>{isHausa ? action.actionHa || action.action : action.action}</Text>
        </Card>
      ))}

      {activeTab === 'organic' && plan.organicRemedies?.map((remedy, index) => (
        <Card key={index} style={styles.treatCard}>
          <Text style={styles.treatTitle}>{remedy.remedy}</Text>
          <Text style={styles.treatMeta}>Dosage: {remedy.dosage}</Text>
          <Text style={styles.treatMeta}>Method: {remedy.method}</Text>
          <Text style={styles.treatMeta}>Timing: {remedy.timing}</Text>
        </Card>
      ))}

      {activeTab === 'chemical' && plan.chemicalTreatments?.map((medicine, index) => (
        <Card key={index} style={styles.treatCard}>
          <Text style={styles.treatTitle}>{medicine.product}</Text>
          <Text style={styles.treatMeta}>Dosage: {medicine.dosage || 'Follow expert guidance'}</Text>
          <Text style={styles.treatMeta}>Method: {medicine.method || 'As directed'}</Text>
          <Text style={styles.treatMeta}>Timing: {medicine.timing || 'As directed'}</Text>
          {medicine.cost && <Text style={[styles.treatMeta, styles.costText]}>Cost: {medicine.cost}</Text>}
        </Card>
      ))}

      {activeTab === 'chemical' && plan.dosageGuidance?.map((item, index) => (
        <Card key={`dose-${index}`} style={styles.treatCard}>
          <Text style={styles.treatTitle}>Dosage Guidance {index + 1}</Text>
          <Text style={styles.treatBody}>{isHausa ? item.guidanceHa || item.guidance : item.guidance}</Text>
        </Card>
      ))}

      {activeTab === 'prevention' && plan.prevention?.map((item, index) => (
        <Card key={index} style={styles.treatCard}>
          <Text style={styles.treatBody}>- {item.measure}</Text>
        </Card>
      ))}

      {!feedbackSent && (
        <Card style={styles.card}>
          <Text style={styles.cardTitle}>{t('wasHelpful')}</Text>
          <View style={styles.feedbackRow}>
            <TouchableOpacity style={styles.feedbackBtn} onPress={() => sendFeedback(true)}>
              <Text style={styles.feedbackLabel}>{isHausa ? 'Eh' : 'Yes'}</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.feedbackBtn} onPress={() => sendFeedback(false)}>
              <Text style={styles.feedbackLabel}>{isHausa ? "A'a" : 'No'}</Text>
            </TouchableOpacity>
          </View>
        </Card>
      )}

      {feedbackSent && (
        <Card style={styles.card}>
          <Text style={styles.thanksText}>{isHausa ? 'Na gode da martanin ku!' : 'Thank you for your feedback!'}</Text>
        </Card>
      )}

      {recProducts.length > 0 && (
        <View style={styles.recSection}>
          <View style={styles.recHeader}>
            <Text style={styles.recTitle}>🛒 Recommended Products</Text>
            <TouchableOpacity onPress={() => router.push('/(tabs)/market')}>
              <Text style={styles.recSeeAll}>See all →</Text>
            </TouchableOpacity>
          </View>
          <Text style={styles.recSub}>Based on this diagnosis</Text>
          {recProducts.map(prod => (
            <View key={prod.id} style={styles.recCard}>
              <View style={{ flex: 1 }}>
                <Text style={styles.recProdName}>{prod.name}</Text>
                <Text style={styles.recProdMeta}>{prod.category} · {prod.unit}</Text>
                <Text style={styles.recProdPrice}>₦{Number(prod.selling_price).toLocaleString()}</Text>
              </View>
              <TouchableOpacity
                style={[styles.recAddBtn, prod.stock_status === 'out_of_stock' && styles.recAddBtnDisabled]}
                onPress={() => addToCart(prod)}
                disabled={prod.stock_status === 'out_of_stock' || addingId === prod.id}
              >
                {addingId === prod.id
                  ? <ActivityIndicator size="small" color={Colors.white} />
                  : <Text style={styles.recAddBtnText}>{prod.stock_status === 'out_of_stock' ? 'Out' : '+ Cart'}</Text>
                }
              </TouchableOpacity>
            </View>
          ))}
        </View>
      )}

      <View style={styles.actionsRow}>
        <Button title={t('scanAnother')} onPress={() => router.push('/(tabs)/scan')} variant="outline" style={{ flex: 1 }} />
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: Colors.background },
  content: { paddingBottom: 80 },
  loadingScreen: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.background, padding: Spacing.lg },
  loadingText: { ...Typography.h3, color: Colors.textPrimary, marginTop: Spacing.md, textAlign: 'center' },
  loadingTip: { ...Typography.small, color: Colors.textSecondary, marginTop: Spacing.md, textAlign: 'center' },
  failedIcon: { fontSize: 48, color: Colors.danger, fontWeight: '900' },
  resultHeader: { backgroundColor: Colors.primary, padding: Spacing.lg, paddingTop: 55 },
  emergencyBg: { backgroundColor: Colors.danger },
  backBtn: { color: 'rgba(255,255,255,0.8)', ...Typography.small, marginBottom: Spacing.sm },
  resultTitle: { ...Typography.small, color: 'rgba(255,255,255,0.72)', textTransform: 'uppercase', letterSpacing: 1 },
  diagnosisName: { ...Typography.h2, color: Colors.white, fontWeight: '800', marginVertical: Spacing.xs },
  metaRow: { flexDirection: 'row', gap: Spacing.xs, flexWrap: 'wrap', marginTop: Spacing.xs },
  confBadge: { backgroundColor: 'rgba(255,255,255,0.2)', paddingHorizontal: 10, paddingVertical: 3, borderRadius: Radius.full },
  confText: { ...Typography.tiny, color: Colors.white, fontWeight: '700' },
  qualityBadge: { backgroundColor: 'rgba(255,255,255,0.18)', paddingHorizontal: 10, paddingVertical: 3, borderRadius: Radius.full },
  qualityText: { ...Typography.tiny, color: Colors.white, fontWeight: '700' },
  contagionRow: { marginTop: Spacing.sm, backgroundColor: 'rgba(255,255,255,0.15)', padding: Spacing.xs, borderRadius: Radius.sm },
  contagionText: { ...Typography.small, color: Colors.white, fontWeight: '700' },
  card: { margin: Spacing.md },
  consultCard: { margin: Spacing.md, borderLeftWidth: 4, borderLeftColor: Colors.warning },
  consultTitle: { ...Typography.h3, color: Colors.warning, marginBottom: Spacing.xs },
  consultBody: { ...Typography.body, color: Colors.textSecondary, marginBottom: Spacing.md },
  consultGrid: { gap: Spacing.sm },
  consultBtn: { backgroundColor: Colors.primary, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center', ...Shadows.sm },
  callBtn: { backgroundColor: Colors.success, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center', ...Shadows.sm },
  emergencyBtn: { backgroundColor: Colors.danger, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center', ...Shadows.sm },
  consultBtnText: { color: Colors.white, fontWeight: '700', ...Typography.body },
  callBtnText: { color: Colors.white, fontWeight: '700', ...Typography.body },
  emergencyBtnText: { color: Colors.white, fontWeight: '800', ...Typography.body },
  cardTitle: { ...Typography.label, color: Colors.textPrimary, marginBottom: Spacing.xs },
  listItem: { ...Typography.body, color: Colors.textSecondary, marginBottom: 4 },
  sectionTitle: { ...Typography.h3, color: Colors.textPrimary, paddingHorizontal: Spacing.md, marginTop: Spacing.md },
  tabRow: { paddingHorizontal: Spacing.md, marginVertical: Spacing.sm },
  tab: {
    paddingHorizontal: Spacing.md,
    paddingVertical: 8,
    backgroundColor: Colors.white,
    borderRadius: Radius.full,
    marginRight: Spacing.xs,
    borderWidth: 1.5,
    borderColor: Colors.border,
  },
  tabActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  tabText: { ...Typography.small, color: Colors.textSecondary },
  tabTextActive: { color: Colors.white, fontWeight: '700' },
  treatCard: { marginHorizontal: Spacing.md },
  treatTitle: { ...Typography.label, color: Colors.primary, marginBottom: Spacing.xs },
  treatBody: { ...Typography.body, color: Colors.textPrimary },
  treatMeta: { ...Typography.small, color: Colors.textSecondary, marginTop: 2 },
  costText: { color: Colors.success, fontWeight: '700' },
  feedbackRow: { flexDirection: 'row', gap: Spacing.md, marginTop: Spacing.sm },
  feedbackBtn: { flex: 1, alignItems: 'center', padding: Spacing.sm, backgroundColor: Colors.background, borderRadius: Radius.md },
  feedbackLabel: { ...Typography.small, color: Colors.textSecondary, fontWeight: '700' },
  thanksText: { textAlign: 'center', color: Colors.success, fontWeight: '700' },
  actionsRow: { flexDirection: 'row', gap: Spacing.sm, padding: Spacing.md },
  recSection: { margin: Spacing.md, marginTop: 0 },
  recHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 2 },
  recTitle: { ...Typography.h3, color: Colors.textPrimary },
  recSeeAll: { ...Typography.small, color: Colors.primary, fontWeight: '700' },
  recSub: { ...Typography.tiny, color: Colors.textMuted, marginBottom: Spacing.sm },
  recCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: Colors.white, borderRadius: Radius.md, padding: 12, marginBottom: 8, ...Shadows.sm },
  recProdName: { ...Typography.label, color: Colors.textPrimary },
  recProdMeta: { ...Typography.tiny, color: Colors.textMuted, marginTop: 2 },
  recProdPrice: { ...Typography.small, color: Colors.primary, fontWeight: '700', marginTop: 4 },
  recAddBtn: { backgroundColor: Colors.primary, borderRadius: Radius.sm, paddingHorizontal: 14, paddingVertical: 8, minWidth: 60, alignItems: 'center' },
  recAddBtnDisabled: { backgroundColor: '#CBD5E1' },
  recAddBtnText: { color: Colors.white, fontWeight: '700', fontSize: 12 },

  // Voice bar
  voiceBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#0B2447',
    paddingVertical: 10,
    paddingHorizontal: Spacing.md,
    gap: 6,
    flexWrap: 'wrap',
  },
  voiceBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: 'rgba(255,255,255,0.12)',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
  },
  voiceBtnActive: { backgroundColor: Colors.primary },
  voiceBtnIcon: { fontSize: 14, color: '#fff' },
  voiceBtnLabel: { fontSize: 12, color: '#fff', fontWeight: '600' },
  speedRow: { flexDirection: 'row', gap: 4, marginLeft: 'auto' },
  speedPill: {
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 6,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  speedPillActive:   { backgroundColor: Colors.accent },
  speedText:         { color: 'rgba(255,255,255,0.6)', fontSize: 11, fontWeight: '700' },
  speedTextActive:   { color: '#fff' },
});
