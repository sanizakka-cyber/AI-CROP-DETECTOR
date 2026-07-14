import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { Colors, Radius, Spacing, Typography, Shadows } from '../constants/Theme';

// ── Button ────────────────────────────────────────────────────────────────────
export function Button({ title, onPress, variant = 'primary', loading, disabled, style }) {
  const isPrimary = variant === 'primary';
  const isDanger  = variant === 'danger';
  return (
    <TouchableOpacity
      style={[
        styles.btn,
        isPrimary && styles.btnPrimary,
        isDanger  && styles.btnDanger,
        variant === 'outline' && styles.btnOutline,
        (disabled || loading) && styles.btnDisabled,
        style,
      ]}
      onPress={onPress}
      disabled={disabled || loading}
      activeOpacity={0.82}
    >
      {loading
        ? <ActivityIndicator color={isPrimary ? Colors.white : Colors.primary} />
        : <Text style={[styles.btnText, !isPrimary && styles.btnTextAlt]}>{title}</Text>
      }
    </TouchableOpacity>
  );
}

// ── Card ──────────────────────────────────────────────────────────────────────
export function Card({ children, style }) {
  return <View style={[styles.card, style]}>{children}</View>;
}

// ── SeverityBadge ─────────────────────────────────────────────────────────────
const SEVERITY_COLORS = {
  mild:      { bg: '#D1FAE5', text: '#065F46' },
  moderate:  { bg: '#FEF3C7', text: '#92400E' },
  severe:    { bg: '#FEE2E2', text: '#991B1B' },
  emergency: { bg: '#991B1B', text: '#FFFFFF' },
};

export function SeverityBadge({ severity }) {
  const colors = SEVERITY_COLORS[severity] || SEVERITY_COLORS.mild;
  return (
    <View style={[styles.badge, { backgroundColor: colors.bg }]}>
      <Text style={[styles.badgeText, { color: colors.text }]}>
        {severity?.toUpperCase()}
      </Text>
    </View>
  );
}

// ── SectionTitle ──────────────────────────────────────────────────────────────
export function SectionTitle({ title, subtitle }) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {subtitle && <Text style={styles.sectionSubtitle}>{subtitle}</Text>}
    </View>
  );
}

// ── EmptyState ────────────────────────────────────────────────────────────────
export function EmptyState({ icon = '🌿', title, subtitle, action }) {
  return (
    <View style={styles.empty}>
      <Text style={styles.emptyIcon}>{icon}</Text>
      <Text style={styles.emptyTitle}>{title}</Text>
      {subtitle && <Text style={styles.emptySubtitle}>{subtitle}</Text>}
      {action}
    </View>
  );
}

// ── LoadingOverlay ────────────────────────────────────────────────────────────
export function LoadingOverlay({ message }) {
  return (
    <View style={styles.overlay}>
      <View style={styles.overlayCard}>
        <ActivityIndicator size="large" color={Colors.primary} />
        {message && <Text style={styles.overlayText}>{message}</Text>}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  btn: {
    paddingVertical: 14,
    paddingHorizontal: Spacing.lg,
    borderRadius: Radius.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnPrimary:   { backgroundColor: Colors.primary, ...Shadows.md },
  btnDanger:    { backgroundColor: Colors.danger,  ...Shadows.md },
  btnOutline:   { backgroundColor: 'transparent', borderWidth: 2, borderColor: Colors.primary },
  btnDisabled:  { opacity: 0.5 },
  btnText:      { ...Typography.body, fontWeight: '700', color: Colors.white },
  btnTextAlt:   { color: Colors.primary },
  card: {
    backgroundColor: Colors.card,
    borderRadius: Radius.lg,
    padding: Spacing.md,
    marginVertical: Spacing.xs,
    ...Shadows.sm,
  },
  badge: {
    paddingHorizontal: Spacing.sm,
    paddingVertical: 3,
    borderRadius: Radius.full,
    alignSelf: 'flex-start',
  },
  badgeText:   { ...Typography.tiny, fontWeight: '700' },
  sectionHeader: { marginBottom: Spacing.sm },
  sectionTitle:  { ...Typography.h3, color: Colors.textPrimary },
  sectionSubtitle: { ...Typography.small, color: Colors.textSecondary, marginTop: 2 },
  empty: { alignItems: 'center', paddingVertical: Spacing.xxl },
  emptyIcon:    { fontSize: 56, marginBottom: Spacing.md },
  emptyTitle:   { ...Typography.h3, color: Colors.textPrimary, textAlign: 'center' },
  emptySubtitle:{ ...Typography.body, color: Colors.textSecondary, textAlign: 'center', marginTop: Spacing.xs },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.55)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 999,
  },
  overlayCard: {
    backgroundColor: Colors.white,
    borderRadius: Radius.lg,
    padding: Spacing.xl,
    alignItems: 'center',
    ...Shadows.lg,
  },
  overlayText: { ...Typography.body, color: Colors.textSecondary, marginTop: Spacing.md, textAlign: 'center' },
});
