import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, ScrollView, TouchableOpacity, StyleSheet, RefreshControl, ActivityIndicator } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { analyticsAPI, weatherAPI, notificationsAPI } from '../../lib/api';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';

const QUICK_ACTIONS = [
  { id: 'crop',      icon: '🌽', en: 'Scan Plant',    ha: 'Bincika Shuka',  color: '#D1FAE5', border: '#6EE7B7', to: '/scan/crop' },
  { id: 'livestock', icon: '🐄', en: 'Scan Animal',   ha: 'Bincika Dabba',  color: '#FEF3C7', border: '#FCD34D', to: '/scan/livestock' },
  { id: 'market',    icon: '🛒', en: 'Marketplace',   ha: 'Kasuwa',         color: '#EDE9FE', border: '#C4B5FD', to: '/(tabs)/market' },
  { id: 'records',   icon: '📋', en: 'My Records',    ha: 'Bayananaina',    color: '#DBEAFE', border: '#93C5FD', to: '/(tabs)/records' },
  { id: 'vet',       icon: '📞', en: 'Call Vet',      ha: 'Kira Likita',    color: '#FCE7F3', border: '#F9A8D4', to: '/(tabs)/market' },
  { id: 'reports',   icon: '📊', en: 'View Reports',  ha: 'Duba Rahotanni', color: '#ECFDF5', border: '#6EE7B7', to: '/(tabs)/records' },
];

function StatCard({ icon, value, label, sub, color }) {
  return (
    <View style={[styles.statCard, { borderTopColor: color || Colors.primary }]}>
      <Text style={styles.statIcon}>{icon}</Text>
      <Text style={[styles.statNum, { color: color || Colors.primary }]}>{value ?? '—'}</Text>
      <Text style={styles.statLabel}>{label}</Text>
      {sub ? <Text style={styles.statSub}>{sub}</Text> : null}
    </View>
  );
}

function AlertBanner({ icon, text, color, onPress }) {
  return (
    <TouchableOpacity style={[styles.alertBanner, { backgroundColor: color + '20', borderLeftColor: color }]} onPress={onPress} activeOpacity={0.8}>
      <Text style={styles.alertBannerIcon}>{icon}</Text>
      <Text style={[styles.alertBannerText, { color }]}>{text}</Text>
      <Text style={{ color, fontSize: 18 }}>›</Text>
    </TouchableOpacity>
  );
}

function SectionHeader({ title, sub }) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {sub ? <Text style={styles.sectionSub}>{sub}</Text> : null}
    </View>
  );
}

function FarmerDashboard({ summary, outbreaks, credit, router, isHausa }) {
  const total    = summary?.total ?? 0;
  const processed = summary?.processed ?? 0;
  const crop     = summary?.crop ?? 0;
  const livestock = summary?.livestock ?? 0;
  const recent   = summary?.recent ?? [];
  const score    = credit?.creditScore ?? null;

  const ALERTS = [
    ...(outbreaks.filter(o => o.severity === 'critical' || o.severity === 'high').slice(0, 2).map(o => ({
      icon: '⚠️', color: '#DC2626',
      text: `${o.disease} outbreak in ${o.region} — ${o.forecast}`,
    }))),
    ...(total === 0 ? [{ icon: '📸', color: Colors.primary, text: isHausa ? 'Fara dubawa — ɗauki hoto na farko' : 'Start scanning — take your first diagnostic photo' }] : []),
  ];

  const INSIGHTS = [
    processed < total ? `🔄 ${total - processed} scan(s) still processing — check back shortly.` : null,
    crop > livestock ? '🌽 You scan crops more — add livestock for full farm coverage.' : null,
    score && score < 65 ? '📈 Boost your insurance score by completing more scans and treatments.' : null,
    score && score >= 80 ? `⭐ Excellent credit score (${score}) — you qualify for farm insurance!` : null,
  ].filter(Boolean).slice(0, 3);

  return (
    <>
      {/* Quick Stats */}
      <SectionHeader title={isHausa ? '📊 Takaitaccen Bayani' : '📊 Farm Overview'} />
      <View style={styles.statsGrid}>
        <StatCard icon="🔬" value={total}     label="Total Scans"   sub={`${processed} processed`} color={Colors.primary} />
        <StatCard icon="🌽" value={crop}      label="Crop Scans"    color="#16A34A" />
        <StatCard icon="🐄" value={livestock} label="Animal Scans"  color="#D97706" />
        <StatCard icon="📈" value={score ? `${score}` : '—'} label="Credit Score" sub={credit?.tier} color={score >= 80 ? '#7C3AED' : Colors.primary} />
      </View>

      {/* Health Alerts */}
      {ALERTS.length > 0 && (
        <>
          <SectionHeader title={isHausa ? '🚨 Faɗakarwa' : '🚨 Health Alerts & Notifications'} />
          <View style={styles.alertsWrap}>
            {ALERTS.map((a, i) => <AlertBanner key={i} icon={a.icon} text={a.text} color={a.color} onPress={() => {}} />)}
          </View>
        </>
      )}

      {/* Outbreak Alerts */}
      {outbreaks.length > 0 && (
        <>
          <SectionHeader title={isHausa ? '🌍 Annobar Yanki' : '🌍 Regional Disease Outbreaks'} />
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.outbreakRow}>
            {outbreaks.map((o, i) => (
              <View key={i} style={[styles.outbreakCard, { borderColor: o.severity === 'high' || o.severity === 'critical' ? '#DC2626' : '#D97706' }]}>
                <Text style={styles.outbreakDisease}>{o.disease}</Text>
                <Text style={styles.outbreakRegion}>📍 {o.region}</Text>
                <View style={[styles.outbreakBadge, { backgroundColor: o.severity === 'high' || o.severity === 'critical' ? '#FEE2E2' : '#FEF3C7' }]}>
                  <Text style={{ fontSize: 11, fontWeight: '700', color: o.severity === 'high' || o.severity === 'critical' ? '#DC2626' : '#D97706' }}>
                    {o.severity?.toUpperCase()} · {o.forecast}
                  </Text>
                </View>
              </View>
            ))}
          </ScrollView>
        </>
      )}

      {/* Recent Diagnoses */}
      <SectionHeader title={isHausa ? '🔬 Bincike Na Baya-Bayan Nan' : '🔬 Recent Diagnoses'} sub={isHausa ? 'Danna don duba cikakken rahoto' : 'Tap for full report'} />
      {recent.length === 0 ? (
        <View style={styles.emptyBox}>
          <Text style={styles.emptyIcon}>🔍</Text>
          <Text style={styles.emptyText}>{isHausa ? 'Babu bincike tukuna. Fara dubawa!' : 'No diagnoses yet. Run your first scan!'}</Text>
          <TouchableOpacity style={styles.emptyBtn} onPress={() => router.push('/scan/crop')}>
            <Text style={styles.emptyBtnText}>{isHausa ? 'Fara Dubawa' : 'Start Scanning'}</Text>
          </TouchableOpacity>
        </View>
      ) : (
        recent.map((d, i) => (
          <TouchableOpacity key={d.id || i} style={styles.dxCard} onPress={() => router.push(`/diagnosis/${d.id}`)}>
            <Text style={styles.dxTypeIcon}>{d.type === 'crop' ? '🌽' : '🐄'}</Text>
            <View style={{ flex: 1 }}>
              <Text style={styles.dxTitle}>{d.disease_name || d.aiResult?.primaryDiagnosis || (isHausa ? 'Ana sarrafa...' : 'Processing...')}</Text>
              <Text style={styles.dxMeta}>{d.type === 'crop' ? 'Crop' : 'Livestock'} · {new Date(d.created_at || d.createdAt).toLocaleDateString()}</Text>
            </View>
            {d.urgency_level && (
              <View style={[styles.sevBadge, { backgroundColor: '#FEF3C7' }]}>
                <Text style={[styles.sevText, { color: '#D97706' }]}>{d.urgency_level}</Text>
              </View>
            )}
            <Text style={styles.dxArrow}>›</Text>
          </TouchableOpacity>
        ))
      )}

      {/* AI Insights */}
      {INSIGHTS.length > 0 && (
        <>
          <SectionHeader title="💡 AI Farm Insights" />
          <View style={styles.insightsCard}>
            {INSIGHTS.map((tip, i) => (
              <Text key={i} style={styles.insightItem}>{tip}</Text>
            ))}
          </View>
        </>
      )}
    </>
  );
}

function AdminDashboard({ data, isHausa }) {
  if (!data) return (
    <View style={{ alignItems: 'center', paddingVertical: 40 }}>
      <ActivityIndicator color={Colors.primary} size="large" />
      <Text style={{ color: Colors.textMuted, marginTop: 12, fontSize: 13 }}>Loading platform data…</Text>
    </View>
  );
  const { users = {}, scans = {}, consultations = {}, treatment = {} } = data;
  return (
    <>
      <SectionHeader title="📊 Platform Overview" />
      <View style={styles.statsGrid}>
        <StatCard icon="👥" value={users.total}      label="Total Users"    color={Colors.primary} />
        <StatCard icon="🌾" value={users.farmers}    label="Farmers"        color="#16A34A" />
        <StatCard icon="🩺" value={users.vets}       label="Vets"           color="#7C3AED" />
        <StatCard icon="⏳" value={users.pendingExperts} label="Pending Review" color="#D97706" />
      </View>
      <SectionHeader title="🔬 Diagnostic Activity" />
      <View style={styles.statsGrid}>
        <StatCard icon="📸" value={scans.total}     label="Total Scans"     color={Colors.primary} />
        <StatCard icon="✅" value={scans.processed} label="Processed"       color="#16A34A" />
        <StatCard icon="👨‍⚕️" value={scans.expertReviews} label="Expert Reviews" color="#7C3AED" />
        <StatCard icon="📈" value={`${scans.processingRate ?? 0}%`} label="Processing Rate" color={Colors.accent} />
      </View>
      <SectionHeader title="💬 Consultations" />
      <View style={styles.statsGrid}>
        <StatCard icon="📋" value={consultations.total}     label="Total"          color={Colors.primary} />
        <StatCard icon="✔️" value={consultations.completed} label="Completed"      color="#16A34A" />
        <StatCard icon="🎯" value={`${consultations.completionRate ?? 0}%`} label="Completion Rate" color={Colors.accent} />
        <StatCard icon="💊" value={`${treatment.successRate ?? 0}%`} label="Treatment Success" color="#7C3AED" />
      </View>
      <SectionHeader title="👤 Recent Users" />
      {users.activeMonthly !== undefined && (
        <View style={styles.insightsCard}>
          <Text style={styles.insightItem}>🟢 {users.activeMonthly} users active in the last 30 days</Text>
          <Text style={styles.insightItem}>🌱 {users.agronomists} agronomists on platform</Text>
        </View>
      )}
    </>
  );
}

function StaffDashboard({ user, isHausa }) {
  const roleLabels = {
    'agro-dealer':        { label: 'Agro-Dealer Portal',        icon: '🏪', color: '#D97706' },
    'extension-officer':  { label: 'Extension Officer Portal',   icon: '🌾', color: '#16A34A' },
    'hr':                 { label: 'HR Department Portal',       icon: '👥', color: '#7C3AED' },
    'finance':            { label: 'Finance Department Portal',  icon: '💰', color: '#0891B2' },
    'operations':         { label: 'Operations Portal',          icon: '⚙️', color: '#374151' },
    'data-analyst':       { label: 'Data Analytics Portal',      icon: '📊', color: '#6D28D9' },
    'monitoring-evaluation': { label: 'M&E Officer Portal',      icon: '📈', color: '#1D4ED8' },
    'field-officer':      { label: 'Field Officer Portal',       icon: '📍', color: '#B45309' },
    'customer-support':   { label: 'Customer Support Portal',    icon: '🎧', color: '#0E7490' },
  };
  const info = roleLabels[user?.role] || { label: 'Staff Portal', icon: '🏢', color: Colors.primary };
  return (
    <>
      <SectionHeader title={`${info.icon} ${info.label}`} />
      <View style={[styles.insightsCard, { borderColor: info.color + '40', backgroundColor: info.color + '08' }]}>
        <Text style={[styles.insightItem, { color: info.color, fontWeight: '700', fontSize: 15, marginBottom: 8 }]}>
          Welcome back, {user?.display_first_name || user?.first_name || 'Staff Member'}
        </Text>
        <Text style={styles.insightItem}>
          Your web dashboard has full tools for your role. Access it at{'\n'}
          <Text style={{ color: info.color, fontWeight: '600' }}>msas.ng/dashboard</Text>
        </Text>
        <Text style={styles.insightItem}>
          Use the Scan tab below to run AI diagnostics for farmers in your area.
        </Text>
      </View>
    </>
  );
}

function VetDashboard({ outbreaks, isHausa }) {
  return (
    <>
      <SectionHeader title="🩺 Veterinary Portal" />
      <View style={styles.statsGrid}>
        <StatCard icon="📋" value={outbreaks.length} label="Active Alerts"  color="#DC2626" />
        <StatCard icon="🌍" value="Katsina"          label="Coverage Area"  color={Colors.primary} />
        <StatCard icon="⚕️" value="Ready"            label="Status"         color="#16A34A" />
      </View>
      <SectionHeader title="🌍 Regional Outbreak Map" />
      {outbreaks.length === 0 ? (
        <View style={styles.insightsCard}>
          <Text style={styles.insightItem}>✅ No active outbreaks in your region.</Text>
        </View>
      ) : outbreaks.map((o, i) => (
        <View key={i} style={[styles.dxCard, { borderLeftWidth: 4, borderLeftColor: '#DC2626' }]}>
          <Text style={styles.dxTypeIcon}>⚠️</Text>
          <View style={{ flex: 1 }}>
            <Text style={styles.dxTitle}>{o.disease}</Text>
            <Text style={styles.dxMeta}>{o.region} · Count: {o.count} · {o.forecast}</Text>
          </View>
        </View>
      ))}
    </>
  );
}

export default function HomeScreen() {
  const { t, i18n } = useTranslation();
  const { user } = useAuth();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  const [summary, setSummary]         = useState(null);
  const [adminData, setAdminData]     = useState(null);
  const [outbreaks, setOutbreaks]     = useState([]);
  const [credit, setCredit]           = useState(null);
  const [weather, setWeather]         = useState(null);
  const [unreadCount, setUnreadCount] = useState(0);
  const [refreshing, setRefreshing]   = useState(false);
  const [loading, setLoading]         = useState(true);

  const load = useCallback(async () => {
    try {
      const role = user?.role;

      // Load weather + notification count for all roles
      try {
        const [w, n] = await Promise.all([
          weatherAPI.current({ state: user?.state || 'Katsina' }),
          notificationsAPI.list(),
        ]);
        setWeather(w);
        setUnreadCount(n.unread_count || 0);
      } catch { /* non-critical */ }

      if (role === 'admin' || role === 'ceo') {
        const res = await analyticsAPI.adminSummary();
        setAdminData(res.summary);
      } else if (role === 'vet' || role === 'agronomist') {
        const res = await analyticsAPI.outbreaks();
        setOutbreaks(res.outbreaks || []);
      } else {
        const [s, o] = await Promise.all([
          analyticsAPI.summary(),
          analyticsAPI.outbreaks(),
        ]);
        setSummary(s.summary);
        setOutbreaks(o.outbreaks || []);
        try {
          const c = await analyticsAPI.insurability();
          setCredit(c);
        } catch { /* insurability is optional */ }
      }
    } catch { /* offline — data stays null */ }
    finally { setLoading(false); }
  }, [user]);

  useEffect(() => { load(); }, [load]);
  const onRefresh = async () => { setRefreshing(true); await load(); setRefreshing(false); };

  const hour = new Date().getHours();
  const greeting = hour < 12 ? (isHausa ? 'Ina kwana' : 'Good morning') : hour < 18 ? (isHausa ? 'Ina wuni' : 'Good afternoon') : (isHausa ? 'Ina yini' : 'Good evening');
  const today = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

  const getRoleLabel = () => {
    const r = user?.role;
    if (r === 'admin' || r === 'ceo') return '👑 CEO Dashboard';
    if (r === 'vet') return '🩺 Vet Portal';
    if (r === 'agronomist') return '🌱 Agronomist Portal';
    return '🌿 FarmAI Dashboard';
  };

  return (
    <ScrollView
      style={styles.root}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={Colors.white} colors={[Colors.primary]} />}
    >
      {/* ── Hero Header ── */}
      <View style={styles.hero}>
        <View style={styles.heroLeft}>
          <Text style={styles.greeting}>{greeting},</Text>
          <Text style={styles.userName}>{user?.display_first_name || user?.name?.split(' ')[0] || 'Farmer'} 👋</Text>
          <Text style={styles.dateText}>{today}</Text>
        </View>
        <View style={{ alignItems: 'flex-end', gap: 8 }}>
          {/* Notifications bell */}
          <TouchableOpacity style={styles.notifBtn} onPress={() => router.push('/notifications')}>
            <Text style={{ fontSize: 20 }}>🔔</Text>
            {unreadCount > 0 && (
              <View style={styles.notifBadge}>
                <Text style={styles.notifBadgeText}>{unreadCount > 9 ? '9+' : unreadCount}</Text>
              </View>
            )}
          </TouchableOpacity>
          {/* Weather pill */}
          {weather?.current && (
            <TouchableOpacity style={styles.weatherPill} onPress={() => router.push('/weather')}>
              <Text style={{ fontSize: 14 }}>{weather.current.emoji}</Text>
              <Text style={styles.weatherPillText}>{weather.current.temperature}°C</Text>
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* ── Loading state ── */}
      {loading && (
        <View style={styles.loadingBox}>
          <ActivityIndicator color={Colors.primary} size="large" />
          <Text style={styles.loadingText}>{isHausa ? 'Ana loda bayanan gonarka...' : 'Loading your farm data...'}</Text>
        </View>
      )}

      {!loading && (
        <>
          {/* ── Role-specific dashboard ── */}
          {(user?.role === 'admin' || user?.role === 'ceo') && <AdminDashboard data={adminData} isHausa={isHausa} />}
          {(user?.role === 'vet' || user?.role === 'agronomist') && <VetDashboard outbreaks={outbreaks} isHausa={isHausa} />}
          {(user?.role === 'farmer' || !user?.role) && (
            <FarmerDashboard summary={summary} outbreaks={outbreaks} credit={credit} router={router} isHausa={isHausa} />
          )}
          {user?.role && !['admin','ceo','vet','agronomist','farmer'].includes(user.role) && (
            <StaffDashboard user={user} isHausa={isHausa} />
          )}

          {/* ── Quick Actions (all roles) ── */}
          <SectionHeader title={isHausa ? '⚡ Ayyuka Masu Sauri' : '⚡ Quick Actions'} />
          <View style={styles.actionsGrid}>
            {QUICK_ACTIONS.map(a => (
              <TouchableOpacity key={a.id} style={[styles.actionCard, { backgroundColor: a.color, borderColor: a.border }]} onPress={() => router.push(a.to)} activeOpacity={0.82}>
                <Text style={styles.actionIcon}>{a.icon}</Text>
                <Text style={styles.actionLabel}>{isHausa ? a.ha : a.en}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* ── Footer ── */}
          <Text style={styles.footer}>MSAS FarmAI · Katsina State · Real-Time Diagnostics 🌿</Text>
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root:    { flex: 1, backgroundColor: Colors.background },
  content: { paddingBottom: 100 },

  hero: {
    backgroundColor: Colors.primary,
    padding: Spacing.lg, paddingTop: 60, paddingBottom: Spacing.xl + 8,
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start',
  },
  heroLeft:  { flex: 1 },
  greeting:  { ...Typography.body,  color: 'rgba(255,255,255,0.75)' },
  userName:  { ...Typography.h2,    color: Colors.white, fontWeight: '800', marginTop: 2 },
  dateText:  { ...Typography.tiny,  color: 'rgba(255,255,255,0.55)', marginTop: 6 },
  notifBtn: {
    position: 'relative',
    backgroundColor: 'rgba(255,255,255,0.18)',
    borderRadius: 20, width: 40, height: 40,
    alignItems: 'center', justifyContent: 'center',
  },
  notifBadge: {
    position: 'absolute', top: -3, right: -3,
    backgroundColor: '#DC2626', borderRadius: 9,
    minWidth: 18, height: 18, alignItems: 'center', justifyContent: 'center',
    paddingHorizontal: 4, borderWidth: 1.5, borderColor: Colors.primary,
  },
  notifBadgeText: { color: '#fff', fontSize: 10, fontWeight: '800' },
  weatherPill: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    backgroundColor: 'rgba(255,255,255,0.18)',
    borderRadius: 14, paddingHorizontal: 10, paddingVertical: 5,
  },
  weatherPillText: { ...Typography.small, color: '#fff', fontWeight: '700' },

  loadingBox:  { alignItems: 'center', paddingVertical: 48 },
  loadingText: { ...Typography.body, color: Colors.textSecondary, marginTop: Spacing.md },

  sectionHeader: { paddingHorizontal: Spacing.md, marginTop: Spacing.lg, marginBottom: Spacing.xs },
  sectionTitle:  { ...Typography.h3, color: Colors.textPrimary, fontWeight: '700' },
  sectionSub:    { ...Typography.small, color: Colors.textMuted, marginTop: 2 },

  statsGrid: {
    flexDirection: 'row', flexWrap: 'wrap',
    paddingHorizontal: Spacing.md, gap: Spacing.sm, marginTop: Spacing.xs,
  },
  statCard: {
    flex: 1, minWidth: '44%', backgroundColor: Colors.white,
    borderRadius: Radius.md, padding: Spacing.md,
    alignItems: 'center', ...Shadows.sm,
    borderTopWidth: 3, borderTopColor: Colors.primary,
  },
  statIcon:  { fontSize: 22, marginBottom: 4 },
  statNum:   { ...Typography.h2, fontWeight: '800', color: Colors.primary },
  statLabel: { ...Typography.tiny, color: Colors.textSecondary, textAlign: 'center', marginTop: 2 },
  statSub:   { ...Typography.tiny, color: Colors.textMuted, marginTop: 2 },

  alertsWrap: { paddingHorizontal: Spacing.md, gap: Spacing.xs },
  alertBanner: {
    flexDirection: 'row', alignItems: 'center', gap: Spacing.sm,
    padding: Spacing.sm + 2, borderRadius: Radius.md, borderLeftWidth: 4,
    marginBottom: Spacing.xs,
  },
  alertBannerIcon: { fontSize: 20 },
  alertBannerText: { ...Typography.small, fontWeight: '600', flex: 1 },

  outbreakRow: { paddingLeft: Spacing.md, marginBottom: Spacing.sm },
  outbreakCard: {
    backgroundColor: Colors.white, borderRadius: Radius.md,
    padding: Spacing.md, marginRight: Spacing.sm, minWidth: 160,
    ...Shadows.sm, borderWidth: 1.5,
  },
  outbreakDisease: { ...Typography.label, color: Colors.textPrimary, fontWeight: '700' },
  outbreakRegion:  { ...Typography.tiny,  color: Colors.textMuted, marginTop: 4 },
  outbreakBadge:   { borderRadius: Radius.full, paddingHorizontal: 8, paddingVertical: 3, marginTop: 8, alignSelf: 'flex-start' },

  dxCard: {
    flexDirection: 'row', alignItems: 'center', gap: Spacing.sm,
    backgroundColor: Colors.white, marginHorizontal: Spacing.md,
    marginBottom: Spacing.xs, borderRadius: Radius.md, padding: Spacing.md, ...Shadows.sm,
  },
  dxTypeIcon: { fontSize: 28 },
  dxTitle:    { ...Typography.body, fontWeight: '600', color: Colors.textPrimary },
  dxMeta:     { ...Typography.tiny, color: Colors.textMuted, marginTop: 2 },
  dxArrow:    { fontSize: 20, color: Colors.textMuted },
  sevBadge:   { borderRadius: Radius.full, paddingHorizontal: 8, paddingVertical: 3 },
  sevText:    { fontSize: 11, fontWeight: '700' },

  emptyBox:    { alignItems: 'center', padding: Spacing.xl, marginHorizontal: Spacing.md, backgroundColor: Colors.white, borderRadius: Radius.lg, ...Shadows.sm },
  emptyIcon:   { fontSize: 48, marginBottom: Spacing.sm },
  emptyText:   { ...Typography.body, color: Colors.textSecondary, textAlign: 'center' },
  emptyBtn:    { marginTop: Spacing.md, backgroundColor: Colors.primary, borderRadius: Radius.md, paddingVertical: 10, paddingHorizontal: Spacing.lg },
  emptyBtnText:{ ...Typography.label, color: Colors.white },

  insightsCard: {
    marginHorizontal: Spacing.md, backgroundColor: '#F0FDF4',
    borderRadius: Radius.md, padding: Spacing.md, borderWidth: 1, borderColor: '#BBF7D0',
  },
  insightItem: { ...Typography.small, color: Colors.textPrimary, marginBottom: Spacing.xs, lineHeight: 20 },

  actionsGrid: { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: Spacing.md, gap: Spacing.sm },
  actionCard:  {
    width: '30%', flexGrow: 1, alignItems: 'center', borderRadius: Radius.lg,
    padding: Spacing.md, ...Shadows.sm, borderWidth: 1.5,
  },
  actionIcon:  { fontSize: 30, marginBottom: 6 },
  actionLabel: { ...Typography.tiny, color: Colors.textPrimary, fontWeight: '600', textAlign: 'center' },

  footer: { ...Typography.tiny, color: Colors.textMuted, textAlign: 'center', padding: Spacing.lg },
});
