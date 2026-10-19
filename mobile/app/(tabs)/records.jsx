import React, { useCallback, useRef, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  RefreshControl, ActivityIndicator,
} from 'react-native';
import { useRouter, useLocalSearchParams, useFocusEffect } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { diagnoseAPI, animalsAPI } from '../../lib/api';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';
import { Card, SeverityBadge, EmptyState, Button } from '../../components/UI';
import { subjectIcon, typeLabel } from '../../lib/subjectIcon';
import { statusMeta } from '../../lib/diagnosisStatus';

const LOAD_FALLBACK = 'Could not load your records. Check your connection and try again.';
const ANIMAL_ICON = {
  cattle: '🐄', goat: '🐐', sheep: '🐑', poultry: '🐓', pig: '🐖',
  camel: '🐪', donkey: '🫏', horse: '🐎', rabbit: '🐇',
};

export default function RecordsScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const params = useLocalSearchParams();
  const isHausa = i18n.language === 'ha';
  const [tab, setTab] = useState(params.tab === 'animals' ? 'animals' : 'diagnoses'); // diagnoses | animals
  const [diagnoses, setDiagnoses] = useState([]);
  const [animals, setAnimals] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  // Each list keeps its own error. The two requests are independent, so a
  // failed animals call must not hide (or discard) a successful diagnoses
  // response, and vice versa. A list's empty state only shows after its
  // own request succeeded with zero rows.
  const [diagError, setDiagError] = useState(null);
  const [animalError, setAnimalError] = useState(null);
  const inFlight = useRef(false);

  const load = useCallback(async () => {
    if (inFlight.current) return; // repeated Retry taps / focus + refresh overlap
    inFlight.current = true;
    const [d, a] = await Promise.allSettled([diagnoseAPI.history(), animalsAPI.list()]);
    if (d.status === 'fulfilled') {
      // API returns { data, diagnoses, ... } — use whichever key is present
      setDiagnoses(d.value.diagnoses || d.value.data || []);
      setDiagError(null);
    } else {
      setDiagError(d.reason?.message || LOAD_FALLBACK);
    }
    if (a.status === 'fulfilled') {
      setAnimals(a.value.animals || a.value.data || []);
      setAnimalError(null);
    } else {
      setAnimalError(a.reason?.message || LOAD_FALLBACK);
    }
    inFlight.current = false;
    setLoading(false);
  }, []);

  // Reload whenever the tab regains focus, so an animal added on the
  // add-animal screen or a fresh scan appears without a manual refresh.
  useFocusEffect(useCallback(() => { load(); }, [load]));
  const onRefresh = async () => { setRefreshing(true); await load(); setRefreshing(false); };

  const renderError = (message) => (
    <EmptyState
      icon="⚠️"
      title={message}
      action={
        <TouchableOpacity onPress={() => load()} style={{ marginTop: 12 }} accessibilityRole="button">
          <Text style={{ color: Colors.primary, fontWeight: '600' }}>{isHausa ? 'Sake gwadawa' : 'Retry'}</Text>
        </TouchableOpacity>
      }
    />
  );

  // health_status vocabulary from AnimalApiController: healthy | sick | under_treatment | deceased
  const STATUS_ICON = { healthy: '✅', sick: '🔴', under_treatment: '🟡', recovering: '🟡', deceased: '⬛' };

  return (
    <ScrollView
      style={styles.root}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={Colors.primary} />}
    >
      <View style={styles.header}>
        <Text style={styles.headerTitle}>📋 {t('records')}</Text>
      </View>

      {/* Tabs */}
      <View style={styles.tabRow}>
        <TouchableOpacity style={[styles.tab, tab === 'diagnoses' && styles.tabActive]} onPress={() => setTab('diagnoses')}>
          <Text style={[styles.tabText, tab === 'diagnoses' && styles.tabTextActive]}>
            🔬 {isHausa ? 'Bincike' : 'Diagnoses'}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.tab, tab === 'animals' && styles.tabActive]} onPress={() => setTab('animals')}>
          <Text style={[styles.tabText, tab === 'animals' && styles.tabTextActive]}>
            🐄 {t('myAnimals')}
          </Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={[styles.section, { paddingVertical: 48, alignItems: 'center' }]}>
          <ActivityIndicator size="large" color={Colors.primary} testID="records-loading" />
        </View>
      ) : tab === 'diagnoses' && (
        <View style={styles.section}>
          {diagError
            ? renderError(diagError)
            : diagnoses.length === 0
            ? <EmptyState icon="🔬" title={t('noDiagnoses')} />
            : diagnoses.map(d => {
                const sm = statusMeta(d.status, d.status_label);
                return (
                <TouchableOpacity key={d.id} onPress={() => router.push(`/diagnosis/${d.id}`)}>
                  <Card style={styles.dxCard}>
                    <View style={styles.dxRow}>
                      <Text style={styles.dxIcon}>{subjectIcon(d)}</Text>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.dxTitle}>{d.subject_name || d.disease_name || d.aiResult?.primaryDiagnosis || 'Processing...'}</Text>
                        <Text style={styles.dxMeta}>
                          {typeLabel(d.type)} · {new Date(d.created_at || d.createdAt).toLocaleDateString()}
                          {d.location_label ? ` · 📍 ${d.location_label}` : ''}
                        </Text>
                        <Text style={[styles.dxStatus, { color: Colors[sm.colorKey] }]}>
                          {sm.icon} {sm.label}
                        </Text>
                      </View>
                      {/* Severity, not urgency: the same value as the result screen and the web. */}
                      {d.severity_level && <SeverityBadge severity={d.severity_level.toLowerCase()} testID="records-severity" />}
                    </View>
                  </Card>
                </TouchableOpacity>
                );
              })
          }
        </View>
      )}

      {!loading && tab === 'animals' && (
        <View style={styles.section}>
          <Button
            title={`+ ${t('addAnimal')}`}
            onPress={() => router.push('/animals/add')}
            variant="outline"
            style={{ marginBottom: Spacing.md }}
          />
          {animalError
            ? renderError(animalError)
            : animals.length === 0
            ? <EmptyState icon="🐄" title={isHausa ? 'Ba dabbobi tukuna' : 'No animals added yet'} />
            : animals.map(a => {
                // The Laravel API returns id/tag_number/species/gender/
                // health_status. The old Mongo-era names (_id, tagId, type,
                // sex, status) are kept only as fallbacks; reading them alone
                // rendered every animal as an unnamed, unknown "🐾 ❓".
                const species = a.species_other || a.species || a.type || '';
                const status = a.health_status || a.status;
                return (
                <Card key={a.id ?? a._id ?? a.tag_number} style={styles.animalCard}>
                  <View style={styles.animalRow}>
                    <Text style={styles.animalIcon}>
                      {ANIMAL_ICON[String(a.species || a.type || '').toLowerCase()] || '🐾'}
                    </Text>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.animalName}>{a.name || a.tag_number || a.tagId || 'Unnamed'}</Text>
                      <Text style={styles.animalMeta}>
                        {[a.breed || species, a.gender || a.sex || 'Unknown'].filter(Boolean).join(' · ')}
                        {a.name && a.tag_number ? ` · ${a.tag_number}` : ''}
                      </Text>
                    </View>
                    <Text style={styles.statusIcon} accessibilityLabel={status || 'unknown'}>{STATUS_ICON[status] || '❓'}</Text>
                  </View>
                </Card>
                );
              })
          }
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: Colors.background },
  header: { backgroundColor: Colors.primary, padding: Spacing.lg, paddingTop: 55 },
  headerTitle: { ...Typography.h2, color: Colors.white, fontWeight: '800' },
  tabRow: { flexDirection: 'row', padding: Spacing.md, gap: Spacing.sm },
  tab: {
    flex: 1, paddingVertical: 10, borderRadius: Radius.md,
    alignItems: 'center', backgroundColor: Colors.white,
    borderWidth: 1.5, borderColor: Colors.border,
  },
  tabActive:     { backgroundColor: Colors.primary, borderColor: Colors.primary },
  tabText:       { ...Typography.label, color: Colors.textSecondary },
  tabTextActive: { color: Colors.white },
  section: { paddingHorizontal: Spacing.md, paddingBottom: 80 },
  dxCard:  { marginBottom: Spacing.xs },
  dxRow:   { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm },
  dxIcon:  { fontSize: 28 },
  dxTitle: { ...Typography.body, fontWeight: '600', color: Colors.textPrimary },
  dxMeta:  { ...Typography.tiny, color: Colors.textMuted },
  dxStatus:{ ...Typography.tiny, fontWeight: '600', marginTop: 2 },
  animalCard: { marginBottom: Spacing.xs },
  animalRow:  { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm },
  animalIcon: { fontSize: 32 },
  animalName: { ...Typography.body, fontWeight: '600', color: Colors.textPrimary },
  animalMeta: { ...Typography.tiny, color: Colors.textMuted },
  statusIcon: { fontSize: 22 },
});
