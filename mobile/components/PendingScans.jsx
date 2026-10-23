// Scans saved on the phone without internet (lib/scanQueue), at the top of
// Records: "Saved on phone – not yet synced", "Synced" (open the result) or
// "Could not be sent" (with the reason, and Remove). Nothing when empty.
import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { useTranslation } from 'react-i18next';
import { useFocusEffect, useRouter } from 'expo-router';
import { scanQueue } from '../lib/scanQueue';
import { Colors, Spacing, Radius, Typography } from '../constants/Theme';

const STATUS_STYLE = {
  pending: { backgroundColor: '#FEF3C7', color: '#92400E' },
  synced:  { backgroundColor: '#D1FAE5', color: '#065F46' },
  failed:  { backgroundColor: '#FEE2E2', color: '#991B1B' },
};

export default function PendingScans({ onSynced }) {
  const { t } = useTranslation();
  const router = useRouter();
  const [items, setItems] = useState([]);
  const [syncing, setSyncing] = useState(false);

  const load = useCallback(() => { scanQueue.list().then(setItems).catch(() => {}); }, []);
  useFocusEffect(load);
  useEffect(() => scanQueue.subscribe(() => load()), [load]);

  if (items.length === 0) return null;

  const syncNow = async () => {
    setSyncing(true);
    try {
      const sent = await scanQueue.flush();
      if (sent > 0 && onSynced) onSynced();
    } finally {
      setSyncing(false);
      load();
    }
  };
  const anyPending = items.some(it => it.status === 'pending');

  return (
    <View style={styles.wrap} testID="pending-scans">
      <View style={styles.head}>
        <Text style={styles.title}>{t('pending_title')}</Text>
        {anyPending && (
          <TouchableOpacity onPress={syncNow} disabled={syncing} testID="pending-sync-now">
            {syncing ? <ActivityIndicator size="small" color={Colors.primary} /> : <Text style={styles.link}>{t('pending_sync_now')}</Text>}
          </TouchableOpacity>
        )}
      </View>
      {items.map(it => (
        <View key={it.id} style={styles.row} testID={`pending-scan-${it.status}`}>
          <View style={{ flex: 1 }}>
            <Text style={styles.name}>{t(`pending_type_${it.type}`)} · {new Date(it.createdAt).toLocaleString()}</Text>
            {it.params?.location?.loc_latitude && (
              <Text style={styles.meta}>📡 ±{it.params.location.loc_accuracy_meters ?? '?'} m</Text>
            )}
            {it.status === 'failed' && <Text style={styles.meta}>{String(t('pending_status_failed', { reason: it.error })).replace('{{reason}}', it.error || '')}</Text>}
          </View>
          <Text style={[styles.badge, STATUS_STYLE[it.status]]}>{t(`pending_status_${it.status === 'failed' ? 'failed_short' : it.status}`)}</Text>
          {it.status === 'synced' && it.diagnosisId && (
            <TouchableOpacity onPress={() => router.push(`/diagnosis/${it.diagnosisId}`)} style={styles.action}>
              <Text style={styles.link}>{t('pending_view_result')}</Text>
            </TouchableOpacity>
          )}
          {it.status === 'failed' && (
            <TouchableOpacity onPress={() => scanQueue.remove(it.id).then(load)} style={styles.action}>
              <Text style={styles.link}>{t('pending_remove')}</Text>
            </TouchableOpacity>
          )}
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginHorizontal: Spacing.md, marginTop: Spacing.sm, padding: Spacing.sm, borderRadius: Radius.md, backgroundColor: Colors.white, borderWidth: 1, borderColor: Colors.border },
  head: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  title: { ...Typography.label, color: Colors.textPrimary },
  row: { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm, paddingVertical: 6, borderTopWidth: 1, borderTopColor: Colors.border },
  name: { ...Typography.small, color: Colors.textPrimary },
  meta: { ...Typography.tiny, color: Colors.textSecondary },
  badge: { ...Typography.tiny, fontWeight: '700', paddingHorizontal: 8, paddingVertical: 2, borderRadius: Radius.full, overflow: 'hidden' },
  action: { paddingLeft: 4 },
  link: { ...Typography.tiny, color: Colors.primary, fontWeight: '700' },
});
