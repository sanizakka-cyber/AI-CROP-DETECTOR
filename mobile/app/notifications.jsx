import React, { useEffect, useState, useCallback } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  ActivityIndicator, RefreshControl, Alert,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { notificationsAPI } from '../lib/api';
import { Colors, Spacing, Radius, Typography, Shadows } from '../constants/Theme';

const TYPE_ICON = {
  scan:        '🔬',
  marketplace: '🛒',
  weather:     '🌦',
  message:     '💬',
  system:      '🔔',
  alert:       '⚠️',
};

function NotifCard({ item, onMarkRead, onDelete }) {
  const isUnread = !item.read_at;
  const timeStr = new Date(item.created_at).toLocaleString([], {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  });

  return (
    <TouchableOpacity
      onPress={() => !item.read_at && onMarkRead(item.id)}
      onLongPress={() =>
        Alert.alert('Delete Notification', 'Remove this notification?', [
          { text: 'Cancel', style: 'cancel' },
          { text: 'Delete', style: 'destructive', onPress: () => onDelete(item.id) },
        ])
      }
      activeOpacity={0.8}
    >
      <View style={[styles.card, isUnread && styles.cardUnread]}>
        <View style={styles.cardLeft}>
          <Text style={styles.cardIcon}>{TYPE_ICON[item.type] || '🔔'}</Text>
          {isUnread && <View style={styles.unreadDot} />}
        </View>
        <View style={styles.cardBody}>
          <Text style={[styles.cardTitle, isUnread && styles.cardTitleUnread]}>{item.title}</Text>
          <Text style={styles.cardMsg} numberOfLines={2}>{item.body}</Text>
          <Text style={styles.cardTime}>{timeStr}</Text>
        </View>
      </View>
    </TouchableOpacity>
  );
}

export default function NotificationsScreen() {
  const { i18n } = useTranslation();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  const [items, setItems]         = useState([]);
  const [loading, setLoading]     = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [error, setError]         = useState(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const res = await notificationsAPI.list({ per_page: 50 });
      const list = res.data || res.notifications || [];
      setItems(list);
      setUnreadCount(res.unread_count ?? list.filter(n => !n.read_at).length);
    } catch (e) {
      setError(e.message || 'Failed to load notifications');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  const onRefresh = () => { setRefreshing(true); load(); };

  const markRead = async (id) => {
    try {
      await notificationsAPI.markRead(id);
      setItems(prev => prev.map(n => n.id === id ? { ...n, read_at: new Date().toISOString() } : n));
      setUnreadCount(c => Math.max(0, c - 1));
    } catch {}
  };

  const markAllRead = async () => {
    try {
      await notificationsAPI.markAllRead();
      const now = new Date().toISOString();
      setItems(prev => prev.map(n => ({ ...n, read_at: n.read_at || now })));
      setUnreadCount(0);
    } catch {}
  };

  const deleteItem = async (id) => {
    try {
      await notificationsAPI.delete(id);
      const removed = items.find(n => n.id === id);
      setItems(prev => prev.filter(n => n.id !== id));
      if (removed && !removed.read_at) setUnreadCount(c => Math.max(0, c - 1));
    } catch {}
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  return (
    <View style={styles.root}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
          <Text style={styles.backText}>‹</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>
          {isHausa ? 'Sanarwa' : 'Notifications'}
          {unreadCount > 0 && <Text style={styles.headerBadge}> · {unreadCount} new</Text>}
        </Text>
        {unreadCount > 0 && (
          <TouchableOpacity onPress={markAllRead} style={styles.markAllBtn}>
            <Text style={styles.markAllText}>{isHausa ? 'Karanta Duka' : 'Mark all read'}</Text>
          </TouchableOpacity>
        )}
      </View>

      {error ? (
        <View style={styles.center}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity style={styles.retryBtn} onPress={load}>
            <Text style={styles.retryBtnText}>Retry</Text>
          </TouchableOpacity>
        </View>
      ) : items.length === 0 ? (
        <View style={styles.center}>
          <Text style={{ fontSize: 52 }}>🔔</Text>
          <Text style={styles.emptyTitle}>{isHausa ? 'Babu sanarwa' : 'No notifications yet'}</Text>
          <Text style={styles.emptyMsg}>{isHausa ? "Za a nuna sanarwa anan" : "Notifications will appear here"}</Text>
        </View>
      ) : (
        <ScrollView
          contentContainerStyle={styles.list}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={Colors.primary} colors={[Colors.primary]} />}
        >
          <Text style={styles.hint}>{isHausa ? 'Danna & riƙe don share' : 'Long-press to delete'}</Text>
          {items.map(item => (
            <NotifCard
              key={item.id}
              item={item}
              onMarkRead={markRead}
              onDelete={deleteItem}
            />
          ))}
          <Text style={styles.footer}>{items.length} notification{items.length !== 1 ? 's' : ''}</Text>
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root:   { flex: 1, backgroundColor: Colors.background },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },

  header: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: Colors.primary, paddingTop: 52,
    paddingBottom: 16, paddingHorizontal: Spacing.md, gap: 12,
  },
  backBtn:    { padding: 4 },
  backText:   { fontSize: 28, color: '#fff', fontWeight: '600', lineHeight: 32 },
  headerTitle:{ ...Typography.h2, color: '#fff', flex: 1 },
  headerBadge:{ fontSize: 13, fontWeight: '500', color: 'rgba(255,255,255,0.8)' },
  markAllBtn: { paddingHorizontal: 12, paddingVertical: 6, backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: Radius.sm },
  markAllText:{ fontSize: 12, color: '#fff', fontWeight: '600' },

  list: { paddingVertical: Spacing.sm, paddingHorizontal: Spacing.md, paddingBottom: 80 },
  hint: { fontSize: 11, color: Colors.textMuted, textAlign: 'center', marginBottom: Spacing.sm },

  card: {
    flexDirection: 'row', gap: 12,
    backgroundColor: Colors.white, borderRadius: Radius.md,
    padding: Spacing.md, marginBottom: 8, ...Shadows.sm,
  },
  cardUnread: { borderLeftWidth: 3, borderLeftColor: Colors.primary },
  cardLeft:   { alignItems: 'center', gap: 6 },
  cardIcon:   { fontSize: 26 },
  unreadDot:  { width: 8, height: 8, borderRadius: 4, backgroundColor: Colors.primary },
  cardBody:   { flex: 1, gap: 3 },
  cardTitle:  { fontSize: 14, color: Colors.textSecondary, fontWeight: '500' },
  cardTitleUnread: { color: Colors.textPrimary, fontWeight: '700' },
  cardMsg:    { fontSize: 13, color: Colors.textSecondary, lineHeight: 18 },
  cardTime:   { fontSize: 11, color: Colors.textMuted, marginTop: 2 },

  errorText:   { fontSize: 15, color: Colors.danger, textAlign: 'center', marginTop: 12 },
  retryBtn:    { marginTop: 16, backgroundColor: Colors.primary, borderRadius: 8, paddingHorizontal: 24, paddingVertical: 10 },
  retryBtnText:{ color: '#fff', fontWeight: '700' },
  emptyTitle:  { fontSize: 18, fontWeight: '700', color: Colors.textPrimary, marginTop: 16 },
  emptyMsg:    { fontSize: 13, color: Colors.textMuted, marginTop: 6, textAlign: 'center' },
  footer:      { textAlign: 'center', color: Colors.textMuted, fontSize: 11, paddingTop: 16 },
});
