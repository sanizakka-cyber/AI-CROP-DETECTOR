import React, { useEffect, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  RefreshControl, FlatList,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { diagnoseAPI, animalsAPI } from '../../lib/api';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';
import { Card, SeverityBadge, EmptyState, Button } from '../../components/UI';

export default function RecordsScreen() {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';
  const [tab, setTab] = useState('diagnoses'); // diagnoses | animals
  const [diagnoses, setDiagnoses] = useState([]);
  const [animals, setAnimals] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = async () => {
    try {
      const [d, a] = await Promise.all([diagnoseAPI.history(), animalsAPI.list()]);
      // API returns { data, diagnoses, ... } — use whichever key is present
      setDiagnoses(d.diagnoses || d.data || []);
      setAnimals(a.animals || a.data || []);
    } catch {}
    setLoading(false);
  };

  useEffect(() => { load(); }, []);
  const onRefresh = async () => { setRefreshing(true); await load(); setRefreshing(false); };

  const STATUS_ICON = { healthy: '✅', sick: '🔴', recovering: '🟡', deceased: '⬛' };

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

      {tab === 'diagnoses' && (
        <View style={styles.section}>
          {diagnoses.length === 0
            ? <EmptyState icon="🔬" title={t('noDiagnoses')} />
            : diagnoses.map(d => (
                <TouchableOpacity key={d.id} onPress={() => router.push(`/diagnosis/${d.id}`)}>
                  <Card style={styles.dxCard}>
                    <View style={styles.dxRow}>
                      <Text style={styles.dxIcon}>{d.type === 'crop' ? '🌽' : '🐄'}</Text>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.dxTitle}>{d.disease_name || d.aiResult?.primaryDiagnosis || 'Processing...'}</Text>
                        <Text style={styles.dxMeta}>
                          {d.type === 'crop' ? 'Crop' : 'Livestock'} · {new Date(d.created_at || d.createdAt).toLocaleDateString()}
                        </Text>
                        <Text style={[styles.dxStatus, { color: d.status === 'processed' ? Colors.success : Colors.warning }]}>
                          {d.status === 'processed' ? '✅ Processed' : d.status === 'pending' ? '⏳ Processing' : '❌ Failed'}
                        </Text>
                      </View>
                      {d.urgency_level && <SeverityBadge severity={d.urgency_level?.toLowerCase()} />}
                    </View>
                  </Card>
                </TouchableOpacity>
              ))
          }
        </View>
      )}

      {tab === 'animals' && (
        <View style={styles.section}>
          <Button
            title={`+ ${t('addAnimal')}`}
            onPress={() => router.push('/animals/add')}
            variant="outline"
            style={{ marginBottom: Spacing.md }}
          />
          {animals.length === 0
            ? <EmptyState icon="🐄" title={isHausa ? 'Ba dabbobi tukuna' : 'No animals added yet'} />
            : animals.map(a => (
                <Card key={a._id} style={styles.animalCard}>
                  <View style={styles.animalRow}>
                    <Text style={styles.animalIcon}>
                      {{ cattle: '🐄', goat: '🐐', sheep: '🐑', poultry: '🐓' }[a.type] || '🐾'}
                    </Text>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.animalName}>{a.name || a.tagId || 'Unnamed'}</Text>
                      <Text style={styles.animalMeta}>{a.breed || a.type} · {a.sex || 'unknown'}</Text>
                    </View>
                    <Text style={styles.statusIcon}>{STATUS_ICON[a.status] || '❓'}</Text>
                  </View>
                </Card>
              ))
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
