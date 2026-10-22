import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
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
import { useLanguage, LANGUAGES } from '../../context/LanguageContext';
import { VoiceService } from '../../services/VoiceService';

// A scan normally leaves 'pending' within seconds. If it hasn't after this
// long, stop polling and tell the farmer instead of spinning forever.
const POLL_INTERVAL_MS = 3000;
const POLL_TIMEOUT_MS = 3 * 60 * 1000;

const DEFAULT_EXPERT_PHONE = '08129582957';
const DEFAULT_WHATSAPP = 'https://wa.me/2348129582957';

/** Renders a labeled field, or an explicit "Not applicable" note instead of
 * leaving the section blank — per the platform-parity requirement that
 * every diagnosis-result category always show something. Label above value,
 * both free to wrap, so a long value never runs into its label. */
export function InfoField({ label, value, testID }) {
  return (
    <View style={styles.infoField}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue} testID={testID}>{value || 'Not applicable for this diagnosis.'}</Text>
    </View>
  );
}

export default function DiagnosisDetailScreen() {
  const { id } = useLocalSearchParams();
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const { language, voiceEnabled } = useLanguage();
  const isHausa = i18n.language === 'ha';
  // Shown on every result screen (assessed, not certain, rejected photo, AI outage).
  const aiDisclaimer = (
    <Text style={styles.aiDisclaimer} testID="ai-disclaimer">⚠ {t('aiDisclaimer')}</Text>
  );

  const [diag, setDiag] = useState(null);
  const [activeTab, setActiveTab] = useState('treatment');
  const [feedbackSent, setFeedbackSent] = useState(false);
  const [feedbackSending, setFeedbackSending] = useState(false);
  const feedbackInFlight = useRef(false);
  const [recProducts, setRecProducts] = useState([]);
  const [addingId, setAddingId] = useState(null);
  // A failed fetch used to stop polling but leave diag null, so the screen
  // sat on the "AI is validating" spinner forever with no way out.
  const [loadError, setLoadError] = useState(null);
  const [timedOut, setTimedOut] = useState(false);

  // Voice state
  const [voicePlaying, setVoicePlaying] = useState(false);
  const [voiceSpeed, setVoiceSpeed] = useState(1.0);
  const voiceNarration = useRef(null);

  // Polling is a chain of setTimeouts, not a setInterval: the next request
  // is only scheduled after the previous one settles, so slow responses
  // can never stack up. Each run gets a number; Retry or unmount bumps it,
  // which makes any still-in-flight response from an older run a no-op.
  const pollRun = useRef(0);
  const pollTimer = useRef(null);
  const voiceTimer = useRef(null);
  // Latest-render handler for a finished result, so the poll loop (created
  // once per id) never calls a stale playVoice/voiceEnabled/language.
  const onFinishedRef = useRef(null);

  const stopPolling = useCallback(() => {
    pollRun.current += 1;
    clearTimeout(pollTimer.current);
    pollTimer.current = null;
  }, []);

  const startPolling = useCallback(() => {
    stopPolling();
    const run = pollRun.current;
    const deadline = Date.now() + POLL_TIMEOUT_MS;
    setLoadError(null);
    setTimedOut(false);

    const tick = async () => {
      let diagnosis;
      try {
        ({ diagnosis } = await diagnoseAPI.get(id));
      } catch (e) {
        if (run !== pollRun.current) return;
        setLoadError(e.message || 'Could not load this diagnosis. Check your connection and try again.');
        return;
      }
      if (run !== pollRun.current) return;
      setDiag(diagnosis);
      if (diagnosis.myFeedback) setFeedbackSent(true);
      // Real terminal states are 'processed', 'reviewed' (the status a
      // fresh successful scan actually gets — see
      // DiagnosisResultMapper::fromAiResult()) and 'needs_review' (AI
      // genuinely produced no result). Only 'pending' means "still
      // working" — there is no 'failed' status anywhere in the schema.
      if (diagnosis.status === 'pending') {
        if (Date.now() >= deadline) { setTimedOut(true); return; }
        pollTimer.current = setTimeout(tick, POLL_INTERVAL_MS);
        return;
      }
      onFinishedRef.current?.(diagnosis);
    };
    tick();
  }, [id, stopPolling]);

  const retryLoad = () => startPolling();

  const fetchRecommendations = async (diagnosis) => {
    try {
      const result = diagnosis.aiResult || {};
      const plan   = diagnosis.treatmentPlan || {};
      const tags   = [
        diagnosis.type,
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
    switch (diagnosis.type) {
      case 'livestock': return VoiceService.buildLivestockNarration(result, plan, language);
      case 'soil':       return VoiceService.buildSoilNarration(result, plan, language);
      case 'pest':       return VoiceService.buildPestNarration(result, plan, language);
      default:           return VoiceService.buildCropNarration(result, plan, language);
    }
  };

  const playVoice = (diagnosis, speed = voiceSpeed) => {
    const text = voiceNarration.current || buildNarration(diagnosis);
    voiceNarration.current = text;
    setVoicePlaying(true);
    VoiceService.speak(text, language, {
      rate: speed,
      onDone: () => setVoicePlaying(false),
      onError: () => setVoicePlaying(false),
      onUnavailableVoice: (langCode) => {
        const langName = LANGUAGES.find(l => l.code === langCode)?.name || langCode;
        Alert.alert(
          'Voice Not Available',
          `This device doesn't have a ${langName} voice installed, so narration will play in a different language instead.`
        );
      },
    });
  };

  const stopVoice = () => {
    VoiceService.stop();
    setVoicePlaying(false);
  };

  onFinishedRef.current = (diagnosis) => {
    if (diagnosis.status === 'processed' || diagnosis.status === 'reviewed') {
      fetchRecommendations(diagnosis);
      if (voiceEnabled && !voiceNarration.current) {
        clearTimeout(voiceTimer.current);
        voiceTimer.current = setTimeout(() => playVoice(diagnosis), 600);
      }
    }
  };

  const addToCart = async (product) => {
    setAddingId(product.id);
    try {
      await cartAPI.add(product.id, 1);
      Alert.alert('Added to Cart', `${product.name} added to cart!`);
    } catch (e) {
      Alert.alert('Error', e.message || 'Could not add to cart');
    } finally {
      setAddingId(null);
    }
  };

  useEffect(() => {
    startPolling();
    return () => {
      // Leaving the screen: no further requests, no late voice playback.
      stopPolling();
      clearTimeout(voiceTimer.current);
      VoiceService.stop();
    };
  }, [startPolling, stopPolling]);

  const sendFeedback = async (wasHelpful) => {
    if (feedbackInFlight.current) return; // double-tap would submit twice
    feedbackInFlight.current = true;
    setFeedbackSending(true);
    try {
      await diagnoseAPI.feedback(id, { rating: wasHelpful ? 'thumbs_up' : 'thumbs_down' });
      setFeedbackSent(true);
    } catch (e) {
      Alert.alert('Error', e.message || 'Could not send your feedback. Please try again.');
    } finally {
      feedbackInFlight.current = false;
      setFeedbackSending(false);
    }
  };

  if ((loadError || timedOut) && (!diag || diag.status === 'pending')) {
    return (
      <View style={styles.loadingScreen}>
        <Text style={styles.failedIcon}>!</Text>
        <Text style={styles.loadingText}>
          {timedOut
            ? (isHausa ? 'Binciken yana ɗaukar lokaci' : 'Analysis is taking longer than expected')
            : (isHausa ? 'An kasa samun sakamakon' : 'Could not load result')}
        </Text>
        <Text style={styles.loadingTip}>
          {timedOut
            ? (isHausa ? 'Za ka iya sake dubawa, ko ka duba Bayanan ka daga baya.' : 'Your scan is saved. Check again now, or find it later under Records.')
            : loadError}
        </Text>
        <Button title={timedOut ? (isHausa ? 'Sake dubawa' : 'Check again') : (isHausa ? 'Sake gwadawa' : 'Retry')} onPress={retryLoad} />
        <TouchableOpacity onPress={() => router.back()} style={{ marginTop: Spacing.md }} accessibilityRole="button">
          <Text style={{ color: Colors.primary, fontWeight: '600' }}>{isHausa ? 'Koma' : 'Go back'}</Text>
        </TouchableOpacity>
      </View>
    );
  }

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

  // A scan the AI never completed (older records; new failures create no
  // record): no diagnosis, urgency, severity or narration, and not counted.
  if (diag.status === 'needs_review' || diag.assessment?.failedScan || diag.assessment?.outcome === 'provider_error') {
    return (
      <View style={styles.loadingScreen} testID="failed-scan">
        <Text style={styles.failedIcon}>!</Text>
        <Text style={styles.loadingText}>{isHausa ? 'Wannan binciken bai kammala ba' : 'This scan did not complete'}</Text>
        <Text style={styles.loadingTip}>
          {isHausa
            ? 'Sabis ɗin bincikenmu ba ya aiki, don haka babu sakamako kuma ba a ƙirga wannan binciken ba. Don Allah ka sake bincike.'
            : 'Our analysis service was unavailable, so there is no result and this scan was not counted. Please scan again.'}
        </Text>
        {aiDisclaimer}
        <Button title={isHausa ? 'Sake gwadawa' : 'Try again'} onPress={() => router.back()} />
      </View>
    );
  }

  const assessment = diag.assessment || {};
  const outcome = assessment.outcome || null;

  // Phase 11: a photo that could not be assessed, or that shows the wrong
  // subject, is a recoverable state with specific retake guidance, not a
  // diagnosis with empty "treatment" tabs.
  if (outcome === 'poor_image' || outcome === 'unsupported_subject') {
    const wrongSubject = outcome === 'unsupported_subject';
    return (
      <ScrollView style={styles.root} contentContainerStyle={[styles.content, { padding: Spacing.lg, paddingTop: 60 }]}>
        <Text style={styles.outcomeIcon}>{wrongSubject ? '🔍' : '📷'}</Text>
        <Text style={styles.outcomeTitle} testID="outcome-title">
          {wrongSubject
            ? (isHausa ? 'Hoton bai nuna abin da ake bincika ba' : 'This photo does not show what this scan needs')
            : (isHausa ? 'Hoton bai fito sosai ba' : 'Photo too unclear to assess')}
        </Text>
        {diag.aiResult?.explanation ? <Text style={styles.outcomeBody}>{diag.aiResult.explanation}</Text> : null}
        {assessment.imageIssues?.length > 0 && (
          <Card style={styles.outcomeCard}>
            <Text style={styles.cardTitle}>{isHausa ? 'Matsalolin hoto' : 'What was wrong with the photo'}</Text>
            {assessment.imageIssues.map(issue => (
              <Text key={issue} style={styles.treatBody}>- {(isHausa ? IMAGE_ISSUE_LABELS_HA : IMAGE_ISSUE_LABELS)[issue] || issue.replace(/_/g, ' ')}</Text>
            ))}
          </Card>
        )}
        {assessment.photoTips?.length > 0 && (
          <Card style={styles.outcomeCard}>
            <Text style={styles.cardTitle}>{isHausa ? 'Yadda za a dauki hoto mai kyau' : 'How to retake the photo'}</Text>
            {assessment.photoTips.map((tip, i) => (
              <Text key={i} style={styles.treatBody}>{i + 1}. {tip}</Text>
            ))}
          </Card>
        )}
        <Text style={styles.outcomeNote}>
          {isHausa ? 'Ba a bayar da magani ba saboda ba a iya tantance hoton ba.' : 'No treatment is suggested because the photo could not be assessed.'}
        </Text>
        {aiDisclaimer}
        <Button title={isHausa ? 'Sake daukar hoto' : 'Retake photo'} onPress={() => router.push('/(tabs)/scan')} style={{ marginTop: Spacing.md }} />
        <Button title={isHausa ? 'Koma zuwa bayanai' : 'Back to records'} onPress={() => router.back()} variant="outline" style={{ marginTop: Spacing.sm }} />
      </ScrollView>
    );
  }

  const result = diag.aiResult || {};
  const plan = diag.treatmentPlan || {};
  const validation = diag.imageValidation || result.validation || {};
  // null means "no score", never 0%.
  const hasConfidence = result.confidence !== null && result.confidence !== undefined;
  const confidence = hasConfidence ? Number(result.confidence) : null;
  const imageQuality = assessment.imageQuality || validation.quality?.status || null;
  const isUncertain = outcome === 'uncertain';
  // What to show comes from the server (assessment.result, App\Support\ResultView),
  // the same source as the web page and the PDF. Older servers don't send it:
  // the rules below are then applied here.
  const view = assessment.result || null;
  // Low-confidence rule: below the threshold (server-configured, 65% by
  // default) never present one diagnosis.
  const minConfidence = Number(assessment.minConfidence ?? 65);
  const notCertain = assessment.notCertain ?? (!isUncertain && hasConfidence && confidence < minConfidence);
  // Inconclusive: no single finding, or below the threshold. The title is
  // never one candidate's name; no severity or urgency; at most 3 causes.
  const inconclusive = view ? view.inconclusive === true : (isUncertain || notCertain);
  const possibleConditions = !inconclusive ? [] : view ? (view.possible_causes || []) : (
    assessment.possibleConditions?.length
      ? assessment.possibleConditions
      : [...new Set([result.primaryDiagnosis, ...(assessment.alternativeFindings || []).map(a => a.name)].filter(Boolean))]
  ).slice(0, 3);
  // One severity value (server: severity_level). `severity` is how bad the
  // condition is; `urgency` is how soon to act, and is shown separately.
  // Before the server fix, `severity` carried the urgency; an old server
  // without `urgency` still has it there, so fall back to it only then.
  const severity = inconclusive ? null : view ? view.severity
    : result.urgency !== undefined ? result.severity : (result.severityLevel ? String(result.severityLevel).toLowerCase() : null);
  const urgency = inconclusive ? null : view ? view.urgency : result.urgency !== undefined ? result.urgency : result.severity;
  const isEmergency = urgency === 'emergency' || severity === 'critical';
  // Buffer zone: between the Not-certain line and confirmBelow the result is
  // "Likely X", with a request to confirm (server decides; older servers: never).
  const needsConfirmation = !inconclusive && (view ? view.kind === 'likely' : assessment.needsConfirmation === true);
  const findingName = view?.name ?? (isHausa ? result.primaryDiagnosisHa || result.primaryDiagnosis : result.primaryDiagnosis);
  const headline = inconclusive ? t('inconclusiveHeadline') : `${needsConfirmation ? `${t('likely')}: ` : ''}${findingName}`;
  // Fixed steps and tips come with a key the app translates; the AI's own come as text.
  const tr = (key, text) => { const v = key ? t(key) : null; return v && v !== key ? v : text; };
  const healthLabel = view?.health ? tr(`health_${view.health.key}`, view.health.label) : result.healthStatus;
  const steps = view?.steps || [];
  const retakeTips = view ? (view.retake?.tips || []) : (assessment.photoTips || []).map(text => ({ key: null, text }));
  const technical = diag.technical || null;
  const consultation = plan.consultation || {};
  const expertType = consultation.expertType || result.expertType || (diag.type === 'livestock' ? 'vet' : 'agronomist');
  const expertLabel = expertType === 'agronomist' ? 'Agronomist' : 'Vet Doctor';
  const callNumber = consultation.callNumber || DEFAULT_EXPERT_PHONE;
  const whatsappUrl = consultation.whatsapp || DEFAULT_WHATSAPP;
  const consultationRecommended =
    consultation.recommended ||
    result.needsExpertReview ||
    result.needsVetVisit ||
    inconclusive ||
    (hasConfidence && confidence < 70) ||
    needsConfirmation ||
    ['severe', 'critical'].includes(severity) ||
    ['high', 'emergency'].includes(urgency);

  return (
    <ScrollView style={styles.root} contentContainerStyle={styles.content}>
      <View style={[styles.resultHeader, isEmergency && styles.emergencyBg]}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backBtn}>Back to records</Text>
        </TouchableOpacity>
        <Text style={styles.resultTitle}>{t('result')}</Text>
        <Text style={styles.diagnosisName} testID="diagnosis-name">{headline}</Text>
        <View style={styles.metaRow}>
          {severity && <SeverityBadge severity={severity} label={t('severity')} testID="header-severity" />}
          {urgency && <SeverityBadge severity={urgency} label={t('urgency')} testID="header-urgency" />}
          <View style={styles.confBadge}>
            <Text style={styles.confText}>{hasConfidence ? `${confidence}% ${isHausa ? 'tabbaci' : 'confidence'}` : (isHausa ? 'Babu maki' : 'No score')}</Text>
          </View>
          {imageQuality && (
            <View style={styles.qualityBadge}>
              <Text style={styles.qualityText}>{isHausa ? 'Hoto' : 'Image'}: {imageQuality}</Text>
            </View>
          )}
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

      {/* First thing under the title: the causes, what to do now, and the retake button. */}
      {inconclusive && (
        <Card style={[styles.card, styles.uncertainCard]} testID="inconclusive-card">
          {assessment.whatCannotBeConfirmed ? <Text style={styles.treatBody}>{assessment.whatCannotBeConfirmed}</Text> : null}
          <Text style={[styles.cardTitle, { marginTop: Spacing.sm }]}>{t('possibleCauses')}</Text>
          {possibleConditions.map((name, i) => (
            <Text key={name} style={styles.treatBody} testID="possible-condition">{i + 1}. {name}</Text>
          ))}
          <Text style={[styles.treatBody, { marginTop: Spacing.sm, fontWeight: '700' }]} testID="confirm-with-expert">{t('confirmWithExpert')}</Text>
          <Text style={[styles.treatBody, { marginTop: Spacing.xs }]}>{t('noTreatmentUntilConfirmed')}</Text>
        </Card>
      )}

      {steps.length > 0 && (
        <Card style={[styles.card, styles.whatToDoCard]} testID="what-to-do">
          <Text style={styles.cardTitle}>{t('whatToDoNow')}</Text>
          {steps.map((step, i) => (
            <Text key={i} style={styles.treatBody} testID="what-to-do-step">{i + 1}. {tr(step.key, step.text)}</Text>
          ))}
        </Card>
      )}

      {inconclusive && (
        <Card style={styles.card} testID="retake-card">
          {retakeTips.length > 0 && <Text style={styles.cardTitle}>{t('photoTips')}</Text>}
          {retakeTips.map((tip, i) => (
            <Text key={i} style={styles.treatBody}>• {tr(tip.key, tip.text)}</Text>
          ))}
          <Button title={t('retakeCloser')} onPress={() => router.push('/(tabs)/scan')} style={{ marginTop: Spacing.sm }} testID="retake-button" />
        </Card>
      )}

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

      <Card style={styles.card}>
        <Text style={styles.cardTitle}>Diagnostic Details</Text>
        <InfoField label="Detected Subject" value={diag.subjectName} />
        {/* Latin names are for staff (technical details), not the farmer's view. */}
        {technical && <InfoField label="Scientific Name" value={diag.scientificName} />}
        <InfoField label="Plant/Body Part" value={diag.detectedPart} testID="details-part" />
        <InfoField label="Health Status" value={healthLabel} testID="details-health" />
        <InfoField label="Severity" value={inconclusive ? t('notDetermined') : view ? (severity ? severity.charAt(0).toUpperCase() + severity.slice(1) : null) : result.severityLevel} testID="details-severity" />
        <InfoField label="Confidence" value={result.confidenceLabel} />
        <InfoField label="Symptoms Identified" value={result.symptomsIdentified} />
        <InfoField label="Environmental Factors" value={result.environmentalFactors} />
        <InfoField label="Nutrient Deficiency" value={result.nutrientDeficiencies} />
        <InfoField label="Pest Detection" value={result.pestDetection} />
        <InfoField label="Explanation" value={result.explanation} />
      </Card>

      {/* Sample Collection Location — spec Section 6C. Null when the
          scan was submitted with no location fields at all; never
          backfilled from the account's registered address, device IP, or
          server region. */}
      <Card style={styles.card}>
        <Text style={styles.cardTitle}>📍 Sample Collection Location</Text>
        {!diag.collectionLocation ? (
          <Text style={styles.noLocationText}>No collection location was recorded for this scan.</Text>
        ) : (
          <>
            <InfoField label="State" value={diag.collectionLocation.state} />
            <InfoField label="LGA" value={diag.collectionLocation.lga} />
            {diag.collectionLocation.community && <InfoField label="Community" value={diag.collectionLocation.community} />}
            {diag.collectionLocation.postalCode && <InfoField label="Postal Code" value={diag.collectionLocation.postalCode} />}
            {diag.collectionLocation.addressLandmark && <InfoField label="Address / Landmark" value={diag.collectionLocation.addressLandmark} />}
            {diag.collectionLocation.latitude != null && (
              <View style={styles.coordsRow}>
                <Text style={styles.coordsRowText}>
                  {Number(diag.collectionLocation.latitude).toFixed(6)}, {Number(diag.collectionLocation.longitude).toFixed(6)}
                  {diag.collectionLocation.accuracyMeters != null ? `  (±${Math.round(diag.collectionLocation.accuracyMeters)}m)` : ''}
                </Text>
                <TouchableOpacity onPress={() => Linking.openURL(`https://www.google.com/maps?q=${diag.collectionLocation.latitude},${diag.collectionLocation.longitude}`)}>
                  <Text style={styles.coordsRowLink}>Open in Maps ↗</Text>
                </TouchableOpacity>
              </View>
            )}
            <View style={styles.provenanceRow}>
              <View style={styles.provenanceBadge}>
                <Text style={styles.provenanceBadgeText}>{diag.collectionLocation.provenanceLabel}</Text>
              </View>
              {diag.collectionLocation.verificationStatus === 'boundary_mismatch_flagged' && (
                <View style={[styles.provenanceBadge, styles.provenanceBadgeWarn]}>
                  <Text style={[styles.provenanceBadgeText, styles.provenanceBadgeTextWarn]}>Location outside expected range — unverified</Text>
                </View>
              )}
              {diag.collectionLocation.differsFromScanLocation && (
                <View style={[styles.provenanceBadge, styles.provenanceBadgeInfo]}>
                  <Text style={[styles.provenanceBadgeText, styles.provenanceBadgeTextInfo]}>Scanned at a different location than collected</Text>
                </View>
              )}
            </View>
          </>
        )}
      </Card>

      {/* What this confidence score means — spec Section 4.5. Always
          shown: the number alone never explains itself. */}
      <Card style={styles.card}>
        <Text style={styles.cardTitle}>ℹ️ What this confidence score means</Text>
        <Text style={styles.provenanceExplainer}>
          {diag.confidenceProvenance?.confidenceInterpretation
            || "This confidence value is the AI model's own self-reported certainty, not a statistically calibrated probability. Treat it as directional guidance, not clinical certainty."}
        </Text>
        <View style={styles.provenanceMetaRow}>
          {diag.confidenceProvenance?.aiModelName && <Text style={styles.provenanceMeta}>Model: {diag.confidenceProvenance.aiModelName}</Text>}
          <Text style={styles.provenanceMeta}>Validation status: {(diag.confidenceProvenance?.validationStatus || 'unvalidated')}</Text>
          {diag.confidenceProvenance?.confidenceDecision && (
            <Text style={styles.provenanceMeta}>Decision: {String(diag.confidenceProvenance.confidenceDecision).replace(/_/g, ' ')}</Text>
          )}
          {diag.dataQualityStatus && <Text style={styles.provenanceMeta}>Data quality: {String(diag.dataQualityStatus).replace(/_/g, ' ')}</Text>}
        </View>
      </Card>

      <View style={{ paddingHorizontal: Spacing.md, paddingTop: Spacing.sm }}>{aiDisclaimer}</View>

      {needsConfirmation && (
        <Card style={[styles.card, styles.uncertainCard]} testID="likely-confirm-card">
          <Text style={[styles.treatBody, { fontWeight: '700' }]}>{t('likelyConfirm')}</Text>
        </Card>
      )}

      {!inconclusive && assessment.alternativeFindings?.length > 0 && (
        <Card style={styles.card}>
          <Text style={styles.cardTitle}>{isHausa ? "Wasu yiwuwar da aka yi la'akari da su" : 'Other possibilities considered'}</Text>
          {assessment.alternativeFindings.map((alt, i) => (
            <Text key={i} style={styles.treatBody}>- {alt.name}{alt.why_considered ? `: ${alt.why_considered}` : ''}</Text>
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

      {activeTab === 'organic' && plan.organicOptions && (
        <Card style={styles.treatCard}>
          <Text style={styles.treatBody}>{plan.organicOptions}</Text>
        </Card>
      )}

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

      {activeTab === 'prevention' && (
        plan.prevention?.length > 0
          ? plan.prevention.map((item, index) => (
              <Card key={index} style={styles.treatCard}>
                <Text style={styles.treatBody}>- {item.measure}</Text>
              </Card>
            ))
          : <Card style={styles.treatCard}>
              <Text style={styles.treatBody}>{plan.preventiveMeasures || 'Not applicable for this diagnosis.'}</Text>
            </Card>
      )}

      {(() => {
        const empty = {
          treatment: !plan.immediateActions?.length,
          organic: !plan.organicOptions && !plan.organicRemedies?.length,
          chemical: !plan.chemicalTreatments?.length && !plan.dosageGuidance?.length,
          prevention: false,
        }[activeTab];
        return empty ? (
          <Card style={styles.treatCard} testID="empty-tab">
            <Text style={styles.treatBody}>
              {isHausa ? 'Babu shawarar da ta dace da wannan sakamakon.' : 'Nothing is recommended here for this result.'}
            </Text>
          </Card>
        ) : null;
      })()}

      {/* Staff only (admin, ceo, customer-support): the unsimplified record. */}
      {technical && (
        <Card style={styles.card} testID="technical-details">
          <Text style={styles.cardTitle}>Technical details (staff only)</Text>
          <InfoField label="Technical name" value={technical.technical_name} />
          <InfoField label="Scientific name" value={technical.scientific_name} />
          <InfoField label="Detected part" value={technical.detected_part} />
          <InfoField label="Condition label" value={technical.condition_label ? `${technical.condition_label}${technical.condition_category ? ` (${technical.condition_category})` : ''}` : null} />
          <InfoField label="Repeat analyses" value={(technical.pass_findings || []).map((f, i) => `${f}${technical.pass_labels?.[i] ? ` [${technical.pass_labels[i]}]` : ''}${technical.pass_confidences?.[i] != null ? ` ${technical.pass_confidences[i]}%` : ''}`).join('; ')} />
        </Card>
      )}

      <Card style={styles.card}>
        <Text style={styles.cardTitle}>Additional Recommendations</Text>
        <InfoField label="Fertilizer Recommendation" value={plan.fertilizerRecommendation} />
        <InfoField label="Recovery Period" value={plan.recoveryPeriod} />
        <InfoField label="Best Practices" value={plan.bestPractices} />
        <InfoField label="Expert Recommendation" value={plan.vetReferralAdvice} />
      </Card>

      {!feedbackSent && (
        <Card style={styles.card}>
          <Text style={styles.cardTitle}>{t('wasHelpful')}</Text>
          <View style={styles.feedbackRow}>
            <TouchableOpacity style={styles.feedbackBtn} onPress={() => sendFeedback(true)} disabled={feedbackSending}>
              <Text style={styles.feedbackLabel}>{isHausa ? 'Eh' : 'Yes'}</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.feedbackBtn} onPress={() => sendFeedback(false)} disabled={feedbackSending}>
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

const IMAGE_ISSUE_LABELS = {
  blurred: 'The photo is blurred',
  too_dark: 'The photo is too dark',
  overexposed_or_glare: 'Too bright, or glare on the subject',
  too_far: 'The subject is too far away',
  too_close: 'Too close: the subject is cut off',
  subject_partly_visible: 'Only part of the subject is visible',
  subject_not_visible: 'The subject is not visible',
  low_resolution: 'The image resolution is too low',
  colour_distorted: 'Colours look distorted',
  screen_or_printed_photo: 'This looks like a photo of a screen or print',
  multiple_subjects: 'Several subjects are mixed in one photo',
};

const IMAGE_ISSUE_LABELS_HA = {
  blurred: 'Hoton ya dushe',
  too_dark: 'Hoton ya yi duhu sosai',
  overexposed_or_glare: 'Haske ya yi yawa, ko akwai walƙiya a kan abin',
  too_far: 'Abin da ake ɗauka ya yi nisa',
  too_close: 'Ya yi kusa sosai: an yanke wani ɓangare',
  subject_partly_visible: 'Wani ɓangare kawai na abin ake gani',
  subject_not_visible: 'Ba a ganin abin da ake bincika',
  low_resolution: 'Hoton bai da inganci sosai',
  colour_distorted: 'Launuka sun canza',
  screen_or_printed_photo: 'Kamar hoton allo ne ko na takarda',
  multiple_subjects: 'Abubuwa da yawa sun haɗu a hoto ɗaya',
};

const styles = StyleSheet.create({
  outcomeIcon: { fontSize: 56, textAlign: 'center' },
  outcomeTitle: { ...Typography.h2, color: Colors.textPrimary, textAlign: 'center', marginTop: Spacing.sm },
  outcomeBody: { ...Typography.body, color: Colors.textSecondary, textAlign: 'center', marginTop: Spacing.sm },
  outcomeCard: { marginTop: Spacing.md },
  outcomeNote: { ...Typography.small, color: Colors.textSecondary, textAlign: 'center', marginTop: Spacing.md },
  aiDisclaimer: { fontSize: 12, fontWeight: '600', color: Colors.textSecondary || '#475569', marginVertical: 6 },
  uncertainCard: { borderLeftWidth: 4, borderLeftColor: Colors.warning },
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
  infoField: { marginBottom: Spacing.sm, flexDirection: 'column', alignSelf: 'stretch' },
  infoLabel: { ...Typography.tiny, color: Colors.textMuted, textTransform: 'uppercase', letterSpacing: 0.5, fontWeight: '700', flexShrink: 1 },
  infoValue: { ...Typography.body, color: Colors.textPrimary, marginTop: 2, flexShrink: 1 },
  whatToDoCard: { borderLeftWidth: 4, borderLeftColor: Colors.primary },
  noLocationText: { ...Typography.small, color: Colors.textMuted, fontStyle: 'italic' },
  coordsRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: Colors.background, borderRadius: Radius.sm, padding: Spacing.sm, marginTop: 4, marginBottom: Spacing.sm },
  coordsRowText: { ...Typography.tiny, color: Colors.textSecondary, fontFamily: 'monospace' },
  coordsRowLink: { ...Typography.tiny, color: Colors.primary, fontWeight: '700' },
  provenanceRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 4 },
  provenanceBadge: { backgroundColor: Colors.background, borderRadius: Radius.full, paddingHorizontal: 10, paddingVertical: 4 },
  provenanceBadgeWarn: { backgroundColor: '#FEF3C7' },
  provenanceBadgeInfo: { backgroundColor: '#DBEAFE' },
  provenanceBadgeText: { ...Typography.tiny, color: Colors.textSecondary, fontWeight: '700' },
  provenanceBadgeTextWarn: { color: '#92400E' },
  provenanceBadgeTextInfo: { color: '#1D4ED8' },
  provenanceExplainer: { ...Typography.small, color: Colors.textSecondary, lineHeight: 19 },
  provenanceMetaRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginTop: Spacing.sm },
  provenanceMeta: { ...Typography.tiny, color: Colors.textMuted },
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
