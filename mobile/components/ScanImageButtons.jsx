import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Colors, Spacing, Radius, Typography } from '../constants/Theme';

/**
 * Shared image-input controls for Plant/Crop, Livestock, Soil and Pest
 * scans (Phase 11). Take Picture uses the camera; Select Picture the
 * system photo picker; Browse Files the system document browser. No
 * drag-and-drop on mobile: it has no meaningful touch interaction here.
 */
export default function ScanImageButtons({ onPick, isHausa = false, disabled = false }) {
  const buttons = [
    { source: 'camera', icon: '📷', label: isHausa ? 'Ɗauki Hoto' : 'Take Picture', primary: true },
    { source: 'gallery', icon: '🖼️', label: isHausa ? 'Zaɓi Hoto' : 'Select Picture' },
    { source: 'files', icon: '📁', label: isHausa ? 'Bincika Fayiloli' : 'Browse Files' },
  ];
  return (
    <View style={styles.row}>
      {buttons.map(b => (
        <TouchableOpacity
          key={b.source}
          testID={`pick-${b.source}`}
          accessibilityRole="button"
          accessibilityLabel={b.label}
          accessibilityState={{ disabled }}
          disabled={disabled}
          onPress={() => onPick(b.source)}
          style={[styles.btn, b.primary && styles.btnPrimary, disabled && styles.btnDisabled]}
        >
          <Text style={styles.icon}>{b.icon}</Text>
          <Text style={[styles.text, b.primary && styles.textPrimary]}>{b.label}</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', gap: Spacing.sm, marginBottom: Spacing.md },
  btn: {
    flex: 1, alignItems: 'center', justifyContent: 'center', minHeight: 84,
    paddingVertical: Spacing.sm, borderRadius: Radius.md, borderWidth: 1.5,
    borderColor: Colors.border, borderStyle: 'dashed', backgroundColor: Colors.white,
  },
  btnPrimary: { backgroundColor: Colors.primary, borderColor: Colors.primary, borderStyle: 'solid' },
  btnDisabled: { opacity: 0.5 },
  icon: { fontSize: 26, marginBottom: 4 },
  text: { ...Typography.small, color: Colors.textSecondary, textAlign: 'center' },
  textPrimary: { color: Colors.white, fontWeight: '600' },
});
