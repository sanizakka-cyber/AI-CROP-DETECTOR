import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

const PLAN_STYLE = {
  basic:   { bg: '#1FA84A18', border: '#1FA84A40', text: '#0F6B3E', label: 'BASIC'   },
  pro:     { bg: '#2D9CDB18', border: '#2D9CDB40', text: '#1a6fa0', label: 'PRO'     },
  premium: { bg: '#F4A30018', border: '#F4A30040', text: '#b47a00', label: 'PREMIUM' },
  trial:   { bg: '#2D9CDB12', border: '#2D9CDB30', text: '#1d4ed8', label: 'TRIAL'   },
  none:    { bg: '#f1f5f9',   border: '#e2e8f0',   text: '#64748b', label: 'FREE'    },
};

/**
 * Small badge displaying the current subscription plan.
 *
 * Props:
 *   plan   — 'basic' | 'pro' | 'premium' | 'none'
 *   status — 'trial' | 'active' | 'expired' etc (overrides label when trial)
 *   size   — 'sm' | 'md' (default 'sm')
 */
export default function PlanBadge({ plan = 'none', status, size = 'sm' }) {
  const effectiveKey = status === 'trial' ? 'trial' : (plan ?? 'none');
  const s = PLAN_STYLE[effectiveKey] ?? PLAN_STYLE.none;
  const isLg = size === 'md';

  return (
    <View style={[
      styles.badge,
      { backgroundColor: s.bg, borderColor: s.border },
      isLg && styles.badgeLg,
    ]}>
      <Text style={[styles.label, { color: s.text }, isLg && styles.labelLg]}>
        {s.label}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    borderRadius: 20, borderWidth: 1,
    paddingHorizontal: 8, paddingVertical: 3,
    alignSelf: 'flex-start',
  },
  badgeLg: { paddingHorizontal: 12, paddingVertical: 5 },
  label: { fontSize: 10, fontWeight: '800', letterSpacing: 0.5 },
  labelLg: { fontSize: 12 },
});
