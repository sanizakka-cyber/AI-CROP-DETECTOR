import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';
import { useSubscription } from '../context/SubscriptionContext';
import { useAuth } from '../context/AuthContext';

const C = {
  navy: '#0B2447', green: '#0F6B3E', blue: '#2D9CDB',
  gold: '#F4A300', white: '#FFFFFF', bg: '#F1F5F9',
  textMid: '#475569', border: '#E2E8F0',
};

/**
 * Wrap any component tree that requires a subscription feature.
 *
 * Usage:
 *   <SubscriptionGate feature="pdf_excel_reports" minPlan="pro">
 *     <YourComponent />
 *   </SubscriptionGate>
 *
 * Props:
 *   feature  — specific feature key (optional, checks hasFeature)
 *   minPlan  — minimum plan required: 'basic' | 'pro' | 'premium'
 *   fallback — custom JSX to show instead of the default locked card
 *   inline   — use compact inline lock banner instead of full card
 */
export default function SubscriptionGate({ children, feature, minPlan = 'basic', fallback, inline = false }) {
  const { user }       = useAuth();
  const sub            = useSubscription();
  const router         = useRouter();

  // Non-farmer roles always pass through
  if (!user || user.role !== 'farmer') return children;

  // Check access
  const hasAccess = feature
    ? sub.hasFeature(feature)
    : sub.hasMinPlan(minPlan);

  if (hasAccess) return children;

  if (fallback) return fallback;

  const planColors = { basic: C.green, pro: C.blue, premium: C.gold };
  const planNames  = { basic: 'Basic', pro: 'Pro', premium: 'Premium' };
  const required   = minPlan;
  const accentColor = planColors[required] ?? C.blue;

  if (inline) {
    return (
      <TouchableOpacity
        style={[styles.inlineLock, { borderColor: accentColor + '40', backgroundColor: accentColor + '0C' }]}
        onPress={() => router.push('/(tabs)/subscription')}
        activeOpacity={0.8}
      >
        <View style={[styles.inlineLockIcon, { backgroundColor: accentColor + '20' }]}>
          <Text style={{ fontSize: 14 }}>🔒</Text>
        </View>
        <View style={{ flex: 1 }}>
          <Text style={[styles.inlineLockTitle, { color: accentColor }]}>
            {planNames[required]} Plan Required
          </Text>
          <Text style={styles.inlineLockSub}>Tap to upgrade and unlock this feature</Text>
        </View>
        <Text style={{ color: accentColor, fontSize: 16, fontWeight: '800' }}>›</Text>
      </TouchableOpacity>
    );
  }

  return (
    <View style={styles.card}>
      {/* Lock Icon */}
      <View style={[styles.lockCircle, { backgroundColor: accentColor + '18', borderColor: accentColor + '30' }]}>
        <Text style={{ fontSize: 32 }}>🔒</Text>
      </View>

      <Text style={styles.title}>Feature Locked</Text>
      <Text style={styles.subtitle}>
        This feature is available on the{' '}
        <Text style={{ color: accentColor, fontWeight: '800' }}>{planNames[required]} Plan</Text>
        {required !== 'basic' ? ' and above' : ''}.
      </Text>

      {/* Current status */}
      {sub.isActive ? (
        <View style={[styles.currentBadge, { backgroundColor: C.green + '18', borderColor: C.green + '30' }]}>
          <Text style={{ color: C.green, fontSize: 12, fontWeight: '700' }}>
            You&apos;re on the {planNames[sub.currentPlan] ?? 'Basic'} Plan
          </Text>
        </View>
      ) : (
        <View style={[styles.currentBadge, { backgroundColor: '#fef3c7', borderColor: '#fcd34d' }]}>
          <Text style={{ color: '#92400e', fontSize: 12, fontWeight: '700' }}>
            No Active Subscription
          </Text>
        </View>
      )}

      <TouchableOpacity
        style={[styles.upgradeBtn, { backgroundColor: accentColor }]}
        onPress={() => router.push('/(tabs)/subscription')}
        activeOpacity={0.85}
      >
        <Text style={styles.upgradeBtnText}>
          {sub.isActive ? `Upgrade to ${planNames[required]}` : 'View Plans'}
        </Text>
      </TouchableOpacity>

      <TouchableOpacity onPress={() => router.back()} style={{ marginTop: 10 }}>
        <Text style={{ color: C.textMid, fontSize: 13, textAlign: 'center' }}>Go Back</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: C.white, borderRadius: 16,
    margin: 20, padding: 28,
    alignItems: 'center',
    shadowColor: '#000', shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08, shadowRadius: 20, elevation: 6,
    borderWidth: 1, borderColor: C.border,
  },
  lockCircle: {
    width: 80, height: 80, borderRadius: 40,
    alignItems: 'center', justifyContent: 'center',
    borderWidth: 2, marginBottom: 18,
  },
  title: { fontSize: 20, fontWeight: '800', color: '#0F172A', marginBottom: 8, textAlign: 'center' },
  subtitle: { fontSize: 13, color: C.textMid, textAlign: 'center', lineHeight: 20, marginBottom: 16 },
  currentBadge: {
    paddingHorizontal: 16, paddingVertical: 6, borderRadius: 20,
    borderWidth: 1, marginBottom: 20,
  },
  upgradeBtn: {
    paddingVertical: 14, paddingHorizontal: 32, borderRadius: 12,
    shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 12, elevation: 6,
  },
  upgradeBtnText: { color: C.white, fontSize: 14, fontWeight: '800' },

  // Inline
  inlineLock: {
    flexDirection: 'row', alignItems: 'center', gap: 10,
    padding: 12, borderRadius: 10, borderWidth: 1, marginVertical: 6,
  },
  inlineLockIcon: {
    width: 32, height: 32, borderRadius: 8,
    alignItems: 'center', justifyContent: 'center',
  },
  inlineLockTitle: { fontSize: 13, fontWeight: '700' },
  inlineLockSub: { fontSize: 11, color: C.textMid, marginTop: 1 },
});

