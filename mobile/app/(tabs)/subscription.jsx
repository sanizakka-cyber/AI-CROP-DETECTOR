import React, { useState } from 'react';
import {
  View, Text, ScrollView, TouchableOpacity, Modal,
  StyleSheet, Alert, ActivityIndicator, StatusBar,
} from 'react-native';
import { useSubscription } from '../../context/SubscriptionContext';
import { useAuth } from '../../context/AuthContext';
import PlanBadge from '../../components/PlanBadge';

const C = {
  navy: '#0B2447', green: '#0F6B3E', greenLt: '#1FA84A',
  blue: '#2D9CDB', gold: '#F4A300', white: '#FFFFFF',
  bg: '#F1F5F9', textDark: '#0F172A', textMid: '#475569',
  textLight: '#94A3B8', border: '#E2E8F0', card: '#F8FAFC',
};

const PLANS = [
  {
    key: 'basic', name: 'Basic Plan', color: C.greenLt, icon: '🏠',
    price: { monthly: 2500, yearly: 25000 },
    highlights: [
      'Up to 50 livestock records',
      'Basic health records',
      'Vaccination reminders',
      'Feeding schedule tracking',
      'Monthly farm reports',
      'Mobile app access',
    ],
  },
  {
    key: 'pro', name: 'Pro Plan', color: C.blue, icon: '⚡', popular: true,
    price: { monthly: 10000, yearly: 100000 },
    highlights: [
      'Unlimited livestock records',
      'Advanced health & breeding records',
      'Production tracking (milk, meat, eggs)',
      'Veterinary service requests',
      'Inventory & financial management',
      'PDF & Excel report downloads',
      'Direct messaging with vets',
      'Priority customer support',
    ],
  },
  {
    key: 'premium', name: 'Premium Plan', color: C.gold, icon: '👑',
    price: { monthly: 35000, yearly: 350000 },
    highlights: [
      'AI-powered recommendations',
      'Predictive disease monitoring',
      'Multi-farm & multi-user management',
      'Market price intelligence',
      'Executive analytics & KPI dashboards',
      'Supply chain management',
      'Livestock digital traceability',
      'Dedicated account manager',
      '24/7 priority support',
    ],
  },
];

const fmt = (n) => '₦' + n.toLocaleString('en-NG');

export default function SubscriptionScreen() {
  const { user }    = useAuth();
  const sub         = useSubscription();
  const [yearly, setYearly]         = useState(false);
  const [loading, setLoading]       = useState(false);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [selectedPlan, setSelectedPlan] = useState(sub.currentPlan !== 'none' ? sub.currentPlan : 'basic');

  const handleSubscribe = (plan) => {
    Alert.alert(
      `Subscribe to ${plan.name}`,
      `You'll be redirected to complete payment via the MSAS portal.\n\nPlan: ${plan.name}\nPrice: ${fmt(yearly ? plan.price.yearly : plan.price.monthly)}/${yearly ? 'year' : 'month'}`,
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Open Portal', onPress: () => Alert.alert('Info', 'Please log in at your MSAS web portal to complete your subscription payment.') },
      ]
    );
  };

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      <StatusBar barStyle="light-content" backgroundColor={C.navy} />

      {/* ── Header ────────────────────────────────────────── */}
      <View style={styles.header}>
        <View style={[styles.blob, { width: 180, height: 180, top: -50, right: -50, backgroundColor: C.greenLt, opacity: 0.12 }]} />
        <View style={[styles.blob, { width: 100, height: 100, bottom: -20, left: -20, backgroundColor: C.gold, opacity: 0.1 }]} />

        <View style={styles.headerContent}>
          <Text style={styles.headerTitle}>My Subscription</Text>
          <Text style={styles.headerSub}>Unlock the full potential of your farm</Text>
        </View>

        {/* Current plan card */}
        <View style={styles.currentPlanCard}>
          {sub.loading ? (
            <ActivityIndicator color={C.white} />
          ) : sub.isActive ? (
            <>
              <View style={styles.cpRow}>
                <View>
                  <Text style={styles.cpLabel}>Current Plan</Text>
                  <Text style={styles.cpName}>{sub.planMeta?.name ?? 'Unknown'}</Text>
                </View>
                <PlanBadge plan={sub.currentPlan} status={sub.status} size="md" />
              </View>
              <View style={styles.cpStats}>
                <View style={styles.cpStat}>
                  <Text style={styles.cpStatVal}>{sub.isTrial ? 'Trial' : 'Active'}</Text>
                  <Text style={styles.cpStatLabel}>Status</Text>
                </View>
                <View style={[styles.cpStat, { borderLeftWidth: 1, borderLeftColor: 'rgba(255,255,255,0.15)', paddingLeft: 16 }]}>
                  <Text style={[styles.cpStatVal, { color: C.gold }]}>{sub.daysRemaining()}</Text>
                  <Text style={styles.cpStatLabel}>Days Left</Text>
                </View>
                <View style={[styles.cpStat, { borderLeftWidth: 1, borderLeftColor: 'rgba(255,255,255,0.15)', paddingLeft: 16 }]}>
                  <Text style={styles.cpStatVal}>{sub.isTrial ? 'Free' : fmt(sub.subscription?.amount_paid ?? 0)}</Text>
                  <Text style={styles.cpStatLabel}>Paid</Text>
                </View>
              </View>
            </>
          ) : (
            <View style={{ alignItems: 'center', paddingVertical: 8 }}>
              <Text style={{ color: 'rgba(255,255,255,0.7)', fontSize: 13, marginBottom: 4 }}>No active subscription</Text>
              <Text style={{ color: C.white, fontSize: 15, fontWeight: '700' }}>Start your 14-day free trial below</Text>
            </View>
          )}
        </View>
      </View>

      {/* ── Usage Meters (Basic only) ─────────────────────── */}
      {sub.isActive && sub.currentPlan === 'basic' && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Usage This Month</Text>
          {[
            { key: 'livestock_records',  label: 'Livestock Records', icon: '🐄', color: C.greenLt },
            { key: 'reports_generated',  label: 'Reports Generated', icon: '📊', color: C.blue    },
            { key: 'ai_scans_per_month', label: 'AI Scans',          icon: '🔬', color: C.gold    },
          ].map((m) => {
            const count  = sub.getCurrentUsage(m.key);
            const limit  = sub.getLimit(m.key);
            const pct    = limit > 0 ? Math.min(1, count / limit) : 0;
            const isWarn = pct >= 0.8;
            return (
              <View key={m.key} style={styles.meterCard}>
                <View style={styles.meterRow}>
                  <View style={styles.meterLeft}>
                    <Text style={{ fontSize: 18 }}>{m.icon}</Text>
                    <Text style={styles.meterLabel}>{m.label}</Text>
                  </View>
                  <Text style={[styles.meterCount, isWarn && { color: '#dc2626' }]}>
                    {count}/{limit === -1 ? '∞' : limit}
                  </Text>
                </View>
                <View style={styles.meterTrack}>
                  <View style={[styles.meterFill, {
                    width: `${pct * 100}%`,
                    backgroundColor: isWarn ? '#dc2626' : m.color,
                  }]} />
                </View>
                {isWarn && (
                  <Text style={styles.meterWarn}>Approaching limit — upgrade to get unlimited</Text>
                )}
              </View>
            );
          })}
        </View>
      )}

      {/* ── Quick Plan Picker (Dropdown) ─────────────────── */}
      <View style={[styles.section, { paddingBottom: 0 }]}>
        <Text style={styles.sectionTitle}>Quick Plan Select</Text>
        <TouchableOpacity style={styles.dropdownTrigger} onPress={() => setPickerOpen(true)} activeOpacity={0.8}>
          <View style={{ flex: 1 }}>
            <Text style={styles.dropdownLabel}>Selected Plan</Text>
            <Text style={styles.dropdownValue}>
              {PLANS.find(p => p.key === selectedPlan)?.icon} {PLANS.find(p => p.key === selectedPlan)?.name ?? 'Select…'}
            </Text>
          </View>
          <View style={styles.dropdownChevron}>
            <Text style={{ fontSize: 14, color: C.green }}>▼</Text>
          </View>
        </TouchableOpacity>

        {/* Price chip */}
        <View style={styles.dropdownPriceRow}>
          {['monthly', 'yearly'].map(cycle => (
            <TouchableOpacity
              key={cycle}
              style={[styles.cyclePill, yearly === (cycle === 'yearly') && styles.cyclePillActive]}
              onPress={() => setYearly(cycle === 'yearly')}
            >
              <Text style={[styles.cyclePillText, yearly === (cycle === 'yearly') && styles.cyclePillTextActive]}>
                {cycle === 'monthly' ? 'Monthly' : 'Yearly (−17%)'}{'\n'}
                {fmt(PLANS.find(p => p.key === selectedPlan)?.price?.[cycle] ?? 0)}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        <TouchableOpacity
          style={styles.dropdownSubscribeBtn}
          onPress={() => handleSubscribe(PLANS.find(p => p.key === selectedPlan))}
          activeOpacity={0.85}
        >
          <Text style={styles.dropdownSubscribeTxt}>
            Subscribe to {PLANS.find(p => p.key === selectedPlan)?.name}
          </Text>
        </TouchableOpacity>
      </View>

      {/* ── Plan picker modal ─────────────────────────────── */}
      <Modal visible={pickerOpen} transparent animationType="slide" onRequestClose={() => setPickerOpen(false)}>
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setPickerOpen(false)}>
          <View style={styles.modalSheet}>
            <View style={styles.modalHandle} />
            <Text style={styles.modalTitle}>Select a Subscription Plan</Text>
            {PLANS.map((plan) => (
              <TouchableOpacity
                key={plan.key}
                style={[
                  styles.modalOption,
                  selectedPlan === plan.key && { borderColor: plan.color, backgroundColor: plan.color + '0A' },
                ]}
                onPress={() => { setSelectedPlan(plan.key); setPickerOpen(false); }}
                activeOpacity={0.75}
              >
                <View style={[styles.modalOptionIcon, { backgroundColor: plan.color }]}>
                  <Text style={{ fontSize: 18 }}>{plan.icon}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={[styles.modalOptionName, selectedPlan === plan.key && { color: plan.color }]}>
                    {plan.name}
                    {plan.popular ? '  ⚡ Popular' : ''}
                  </Text>
                  <Text style={styles.modalOptionPrice}>
                    {fmt(plan.price.monthly)}/mo · {fmt(plan.price.yearly)}/yr
                  </Text>
                </View>
                {selectedPlan === plan.key && (
                  <Text style={{ color: plan.color, fontSize: 18, fontWeight: '700' }}>✓</Text>
                )}
              </TouchableOpacity>
            ))}
          </View>
        </TouchableOpacity>
      </Modal>

      {/* ── Billing Toggle ───────────────────────────────── */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Compare Plans</Text>
        <View style={styles.toggleRow}>
          <TouchableOpacity style={[styles.toggleBtn, !yearly && styles.toggleActive]} onPress={() => setYearly(false)}>
            <Text style={[styles.toggleText, !yearly && styles.toggleActiveText]}>Monthly</Text>
          </TouchableOpacity>
          <TouchableOpacity style={[styles.toggleBtn, yearly && styles.toggleActive]} onPress={() => setYearly(true)}>
            <Text style={[styles.toggleText, yearly && styles.toggleActiveText]}>Yearly</Text>
            <View style={styles.saveBadge}><Text style={styles.saveText}>Save 17%</Text></View>
          </TouchableOpacity>
        </View>
      </View>

      {/* ── Plan Cards ───────────────────────────────────── */}
      <View style={{ paddingHorizontal: 16, paddingBottom: 8 }}>
        {PLANS.map((plan) => {
          const isCurrent = sub.currentPlan === plan.key && sub.isActive;
          return (
            <View key={plan.key} style={[
              styles.planCard,
              plan.popular && styles.planCardPopular,
              isCurrent && { borderColor: plan.color, borderWidth: 2 },
            ]}>
              {plan.popular && (
                <View style={[styles.popularBadge, { backgroundColor: plan.color }]}>
                  <Text style={styles.popularText}>MOST POPULAR</Text>
                </View>
              )}
              {isCurrent && (
                <View style={[styles.popularBadge, { backgroundColor: plan.color }]}>
                  <Text style={styles.popularText}>CURRENT PLAN</Text>
                </View>
              )}

              {/* Plan Header */}
              <View style={[styles.planHeader, { backgroundColor: plan.color + '0F' }]}>
                <View style={[styles.planIconWrap, { backgroundColor: plan.color }]}>
                  <Text style={{ fontSize: 20 }}>{plan.icon}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.planName}>{plan.name}</Text>
                  <View style={styles.priceRow}>
                    <Text style={[styles.priceMain, { color: plan.color }]}>
                      {fmt(yearly ? plan.price.yearly : plan.price.monthly)}
                    </Text>
                    <Text style={styles.pricePer}>/{yearly ? 'yr' : 'mo'}</Text>
                  </View>
                  {yearly && (
                    <Text style={{ color: C.greenLt, fontSize: 11, fontWeight: '700', marginTop: 2 }}>
                      Save {fmt((plan.price.monthly * 12) - plan.price.yearly)}/year
                    </Text>
                  )}
                </View>
              </View>

              {/* Features */}
              <View style={styles.planFeatures}>
                {plan.highlights.map((h, i) => (
                  <View key={i} style={styles.featureRow}>
                    <View style={[styles.featureCheck, { backgroundColor: plan.color + '20' }]}>
                      <Text style={{ color: plan.color, fontSize: 10, fontWeight: '800' }}>✓</Text>
                    </View>
                    <Text style={styles.featureText}>{h}</Text>
                  </View>
                ))}
              </View>

              {/* CTA */}
              <TouchableOpacity
                style={[
                  styles.ctaBtn,
                  { backgroundColor: isCurrent ? C.card : plan.color },
                  isCurrent && { borderWidth: 1.5, borderColor: plan.color },
                ]}
                onPress={isCurrent ? undefined : () => handleSubscribe(plan)}
                disabled={isCurrent}
                activeOpacity={0.85}
              >
                <Text style={[styles.ctaText, isCurrent && { color: plan.color }]}>
                  {isCurrent ? '✓ Current Plan' : (
                    sub.isActive
                      ? (sub.planMeta?.level < PLANS.find(p => p.key === plan.key)?.key ? `Upgrade to ${plan.name}` : `Switch to ${plan.name}`)
                      : 'Start 14-Day Free Trial'
                  )}
                </Text>
              </TouchableOpacity>
            </View>
          );
        })}
      </View>

      {/* ── Feature Comparison ───────────────────────────── */}
      <View style={[styles.section, { paddingBottom: 40 }]}>
        <Text style={styles.sectionTitle}>Feature Comparison</Text>
        {[
          { label: 'Livestock Records',         basic: '50 max',    pro: 'Unlimited', premium: 'Unlimited' },
          { label: 'Health Records',            basic: 'Basic',     pro: 'Advanced',  premium: 'Advanced'  },
          { label: 'Reports per Month',         basic: '5',         pro: 'Unlimited', premium: 'Unlimited' },
          { label: 'AI Scans per Month',        basic: '10',        pro: 'Unlimited', premium: 'Unlimited' },
          { label: 'Veterinary Requests',       basic: '✗',         pro: '✓',         premium: '✓'         },
          { label: 'PDF/Excel Reports',         basic: '✗',         pro: '✓',         premium: '✓'         },
          { label: 'Disease Alerts',            basic: '✗',         pro: '✓',         premium: '✓'         },
          { label: 'AI Recommendations',       basic: '✗',         pro: '✗',         premium: '✓'         },
          { label: 'Multi-Farm Management',    basic: '✗',         pro: '✗',         premium: '✓'         },
          { label: 'Market Price Intelligence',basic: '✗',         pro: '✗',         premium: '✓'         },
          { label: 'Dedicated Account Manager',basic: '✗',         pro: '✗',         premium: '✓'         },
          { label: 'Support Level',            basic: 'FAQ/Bot',   pro: 'Priority',  premium: '24/7'      },
        ].map((row, i) => (
          <View key={i} style={[styles.compRow, i % 2 === 0 && { backgroundColor: '#f8fafc' }]}>
            <Text style={styles.compLabel}>{row.label}</Text>
            <Text style={[styles.compVal, { color: C.greenLt }]}>{row.basic}</Text>
            <Text style={[styles.compVal, { color: C.blue    }]}>{row.pro}</Text>
            <Text style={[styles.compVal, { color: C.gold    }]}>{row.premium}</Text>
          </View>
        ))}
        <View style={styles.compHeader}>
          <Text style={[styles.compHeaderText, { flex: 2 }]}>Feature</Text>
          <Text style={[styles.compHeaderText, { color: C.greenLt }]}>Basic</Text>
          <Text style={[styles.compHeaderText, { color: C.blue }]}>Pro</Text>
          <Text style={[styles.compHeaderText, { color: C.gold }]}>Premium</Text>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: C.bg },

  // Header
  header: { backgroundColor: C.navy, paddingTop: 56, paddingBottom: 24, paddingHorizontal: 20, overflow: 'hidden' },
  blob: { position: 'absolute', borderRadius: 999 },
  headerContent: { marginBottom: 16 },
  headerTitle: { color: C.white, fontSize: 24, fontWeight: '900', letterSpacing: -0.5 },
  headerSub: { color: 'rgba(255,255,255,0.55)', fontSize: 13, marginTop: 3 },

  currentPlanCard: {
    backgroundColor: 'rgba(255,255,255,0.1)', borderRadius: 14,
    padding: 16, borderWidth: 1, borderColor: 'rgba(255,255,255,0.15)',
  },
  cpRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 },
  cpLabel: { color: 'rgba(255,255,255,0.6)', fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5 },
  cpName: { color: C.white, fontSize: 20, fontWeight: '800', marginTop: 2 },
  cpStats: { flexDirection: 'row', gap: 16 },
  cpStat: {},
  cpStatVal: { color: C.white, fontSize: 16, fontWeight: '800' },
  cpStatLabel: { color: 'rgba(255,255,255,0.55)', fontSize: 10, fontWeight: '600', marginTop: 2 },

  // Sections
  section: { padding: 16 },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: C.textDark, marginBottom: 12 },

  // Usage Meters
  meterCard: { backgroundColor: C.white, borderRadius: 12, padding: 14, marginBottom: 10, borderWidth: 1, borderColor: C.border },
  meterRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  meterLeft: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  meterLabel: { fontSize: 13, fontWeight: '600', color: C.textDark },
  meterCount: { fontSize: 14, fontWeight: '800', color: C.textDark },
  meterTrack: { height: 8, backgroundColor: C.border, borderRadius: 4, overflow: 'hidden' },
  meterFill: { height: '100%', borderRadius: 4 },
  meterWarn: { color: '#dc2626', fontSize: 11, fontWeight: '600', marginTop: 6 },

  // Toggle
  toggleRow: { flexDirection: 'row', backgroundColor: C.border, borderRadius: 10, padding: 3, gap: 3 },
  toggleBtn: { flex: 1, paddingVertical: 9, borderRadius: 8, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6 },
  toggleActive: { backgroundColor: C.white, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.1, shadowRadius: 4, elevation: 3 },
  toggleText: { fontSize: 13, fontWeight: '600', color: C.textMid },
  toggleActiveText: { color: C.green, fontWeight: '800' },
  saveBadge: { backgroundColor: C.gold, borderRadius: 10, paddingHorizontal: 6, paddingVertical: 2 },
  saveText: { color: C.white, fontSize: 9, fontWeight: '800' },

  // Plan cards
  planCard: {
    backgroundColor: C.white, borderRadius: 16, marginBottom: 16,
    borderWidth: 1.5, borderColor: C.border, overflow: 'hidden',
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06, shadowRadius: 12, elevation: 3,
  },
  planCardPopular: { shadowOpacity: 0.12, shadowRadius: 20, elevation: 8 },
  popularBadge: { paddingVertical: 5, paddingHorizontal: 16, alignItems: 'center' },
  popularText: { color: C.white, fontSize: 10, fontWeight: '900', letterSpacing: 0.5 },
  planHeader: { flexDirection: 'row', alignItems: 'center', gap: 14, padding: 16 },
  planIconWrap: { width: 48, height: 48, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  planName: { fontSize: 16, fontWeight: '800', color: C.textDark, marginBottom: 2 },
  priceRow: { flexDirection: 'row', alignItems: 'baseline', gap: 2 },
  priceMain: { fontSize: 22, fontWeight: '900' },
  pricePer: { fontSize: 12, color: C.textLight, fontWeight: '500' },
  planFeatures: { paddingHorizontal: 16, paddingVertical: 14 },
  featureRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 10, marginBottom: 8 },
  featureCheck: { width: 18, height: 18, borderRadius: 9, alignItems: 'center', justifyContent: 'center', marginTop: 1 },
  featureText: { flex: 1, fontSize: 13, color: C.textMid, lineHeight: 19 },
  ctaBtn: {
    margin: 16, marginTop: 4, paddingVertical: 14,
    borderRadius: 11, alignItems: 'center',
  },
  ctaText: { color: C.white, fontSize: 14, fontWeight: '800' },

  // Comparison table
  compHeader: {
    flexDirection: 'row', backgroundColor: C.navy,
    padding: 12, borderRadius: 8, marginBottom: 4,
  },
  compHeaderText: { flex: 1, fontSize: 11, fontWeight: '800', color: C.white, textAlign: 'center' },
  compRow: { flexDirection: 'row', paddingVertical: 10, paddingHorizontal: 4, borderRadius: 6 },
  compLabel: { flex: 2, fontSize: 12, color: C.textDark, fontWeight: '500' },
  compVal: { flex: 1, fontSize: 11, fontWeight: '700', textAlign: 'center' },

  // ── Quick Plan Picker Dropdown ──────────────────────
  dropdownTrigger: {
    flexDirection: 'row', alignItems: 'center',
    borderWidth: 1.5, borderColor: C.border, borderRadius: 11,
    backgroundColor: C.white, paddingHorizontal: 14, paddingVertical: 13,
    marginBottom: 10,
  },
  dropdownLabel: { fontSize: 10, fontWeight: '700', color: C.textLight, textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 3 },
  dropdownValue: { fontSize: 15, fontWeight: '700', color: C.textDark },
  dropdownChevron: {
    width: 32, height: 32, borderRadius: 8,
    backgroundColor: C.green + '15',
    alignItems: 'center', justifyContent: 'center',
  },
  dropdownPriceRow: { flexDirection: 'row', gap: 8, marginBottom: 12 },
  cyclePill: {
    flex: 1, paddingVertical: 9, borderRadius: 9,
    borderWidth: 1.5, borderColor: C.border,
    alignItems: 'center', backgroundColor: C.white,
  },
  cyclePillActive: { borderColor: C.green, backgroundColor: C.green + '10' },
  cyclePillText: { fontSize: 11, fontWeight: '600', color: C.textMid },
  cyclePillTextActive: { color: C.green, fontWeight: '800' },
  dropdownSubscribeBtn: {
    backgroundColor: C.green, borderRadius: 11,
    paddingVertical: 14, alignItems: 'center',
    shadowColor: C.green, shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3, shadowRadius: 10, elevation: 6,
    marginBottom: 4,
  },
  dropdownSubscribeTxt: { color: C.white, fontSize: 14, fontWeight: '800' },

  // ── Plan picker modal ────────────────────────────────
  modalOverlay: {
    flex: 1, backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'flex-end',
  },
  modalSheet: {
    backgroundColor: C.white, borderTopLeftRadius: 20, borderTopRightRadius: 20,
    paddingHorizontal: 20, paddingBottom: 36, paddingTop: 12,
  },
  modalHandle: {
    width: 40, height: 4, borderRadius: 2,
    backgroundColor: C.border, alignSelf: 'center', marginBottom: 16,
  },
  modalTitle: { fontSize: 17, fontWeight: '800', color: C.textDark, marginBottom: 16 },
  modalOption: {
    flexDirection: 'row', alignItems: 'center', gap: 12,
    borderWidth: 1.5, borderColor: C.border, borderRadius: 12,
    padding: 14, marginBottom: 10, backgroundColor: C.white,
  },
  modalOptionIcon: { width: 44, height: 44, borderRadius: 11, alignItems: 'center', justifyContent: 'center' },
  modalOptionName: { fontSize: 14, fontWeight: '700', color: C.textDark, marginBottom: 2 },
  modalOptionPrice: { fontSize: 12, color: C.textMid, fontWeight: '500' },
});
