import React, { useState } from 'react';
import {
  Modal, View, Text, TouchableOpacity, FlatList,
  StyleSheet, Pressable, Animated,
} from 'react-native';
import { useLanguage, LANGUAGES } from '../context/LanguageContext';
import { Colors, Typography, Spacing, BorderRadius } from '../constants/Theme';

export default function LanguagePicker({ visible, onClose }) {
  const { language, changeLanguage } = useLanguage();

  const select = async (code) => {
    await changeLanguage(code);
    onClose();
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      onRequestClose={onClose}
    >
      <Pressable style={styles.overlay} onPress={onClose} />
      <View style={styles.sheet}>
        <View style={styles.handle} />
        <Text style={styles.title}>Select Language / Zaɓi Harshe</Text>
        <FlatList
          data={LANGUAGES}
          keyExtractor={l => l.code}
          renderItem={({ item }) => {
            const selected = item.code === language;
            return (
              <TouchableOpacity
                style={[styles.row, selected && styles.rowSelected]}
                onPress={() => select(item.code)}
                activeOpacity={0.7}
              >
                <Text style={styles.flag}>{item.flag}</Text>
                <View style={styles.names}>
                  <Text style={[styles.nativeName, selected && styles.textSelected]}>
                    {item.nativeName}
                  </Text>
                  <Text style={styles.enName}>{item.name}</Text>
                </View>
                {selected && (
                  <View style={styles.checkCircle}>
                    <Text style={styles.check}>✓</Text>
                  </View>
                )}
                {item.code === 'ff' && (
                  <View style={styles.badge}>
                    <Text style={styles.badgeText}>No TTS</Text>
                  </View>
                )}
              </TouchableOpacity>
            );
          }}
          ItemSeparatorComponent={() => <View style={styles.sep} />}
        />
        <TouchableOpacity style={styles.cancelBtn} onPress={onClose}>
          <Text style={styles.cancelText}>Cancel / Soke</Text>
        </TouchableOpacity>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
  },
  sheet: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingBottom: 32,
    paddingHorizontal: 20,
    maxHeight: '65%',
  },
  handle: {
    width: 40,
    height: 4,
    backgroundColor: '#DDD',
    borderRadius: 2,
    alignSelf: 'center',
    marginTop: 10,
    marginBottom: 16,
  },
  title: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0B2447',
    textAlign: 'center',
    marginBottom: 16,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 14,
    paddingHorizontal: 12,
    borderRadius: 10,
  },
  rowSelected: {
    backgroundColor: '#EEF7EF',
  },
  flag: {
    fontSize: 26,
    marginRight: 14,
    width: 32,
    textAlign: 'center',
  },
  names: { flex: 1 },
  nativeName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1A1A2E',
  },
  textSelected: { color: '#0F6B3E' },
  enName: {
    fontSize: 12,
    color: '#888',
    marginTop: 2,
  },
  checkCircle: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#0F6B3E',
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 8,
  },
  check: { color: '#fff', fontSize: 14, fontWeight: '700' },
  badge: {
    backgroundColor: '#FFF3CD',
    borderRadius: 4,
    paddingHorizontal: 6,
    paddingVertical: 2,
    marginLeft: 6,
  },
  badgeText: { fontSize: 9, color: '#856404', fontWeight: '600' },
  sep: { height: 1, backgroundColor: '#F0F0F0' },
  cancelBtn: {
    marginTop: 16,
    backgroundColor: '#F5F5F5',
    borderRadius: 10,
    paddingVertical: 13,
    alignItems: 'center',
  },
  cancelText: { fontSize: 15, color: '#555', fontWeight: '600' },
});
