// Shared "Sample Collection Location" UI for all four scan screens
// (crop/livestock/soil/pest) — spec Section 5 / MSAS FarmAI Phase 6.
// Pair with the useLocationCapture() hook (../lib/locationCapture) for state.
//
// Mirrors the web scan form's location section (resources/views/
// diagnostics/scan.blade.php) field-for-field, and reuses the same
// state/LGA picker pattern already established in app/(auth)/register.jsx
// for visual consistency rather than inventing a second picker UI.
import React, { useMemo, useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, Modal, FlatList,
  StyleSheet, ActivityIndicator,
} from 'react-native';
import { Colors, Spacing, Radius, Typography, Shadows } from '../constants/Theme';

export default function LocationCaptureSection({ loc }) {
  const [statePickerOpen, setStatePickerOpen] = useState(false);
  const [lgaPickerOpen, setLgaPickerOpen]     = useState(false);
  const [stateSearch, setStateSearch] = useState('');
  const [lgaSearch, setLgaSearch]     = useState('');

  const filteredStates = useMemo(() => {
    const q = stateSearch.trim().toLowerCase();
    return q ? loc.locations.states.filter(s => s.name.toLowerCase().includes(q)) : loc.locations.states;
  }, [loc.locations.states, stateSearch]);

  const filteredLgas = useMemo(() => {
    const q = lgaSearch.trim().toLowerCase();
    return q ? loc.lgasForState.filter(l => l.toLowerCase().includes(q)) : loc.lgasForState;
  }, [loc.lgasForState, lgaSearch]);

  return (
    <View style={styles.wrap}>
      <View style={styles.headerRow}>
        <Text style={styles.title}>📍 Sample Collection Location <Text style={styles.optional}>(optional)</Text></Text>
        <TouchableOpacity style={styles.gpsBtn} onPress={loc.captureGps} disabled={loc.gpsLoading}>
          {loc.gpsLoading
            ? <ActivityIndicator size="small" color={Colors.primary} />
            : <Text style={styles.gpsBtnText}>Use my location</Text>}
        </TouchableOpacity>
      </View>
      <Text style={styles.hint}>
        Recording where this sample was actually collected helps track outbreaks and makes your scan useful for
        agricultural research. This never blocks your diagnosis — leave it blank if unsure.
      </Text>

      {loc.gpsStatus && (
        <View style={[
          styles.statusBox,
          loc.gpsStatus.type === 'success' && styles.statusSuccess,
          loc.gpsStatus.type === 'warning' && styles.statusWarning,
          loc.gpsStatus.type === 'error'   && styles.statusError,
        ]}>
          <Text style={[
            styles.statusText,
            loc.gpsStatus.type === 'success' && styles.statusTextSuccess,
            loc.gpsStatus.type === 'warning' && styles.statusTextWarning,
            loc.gpsStatus.type === 'error'   && styles.statusTextError,
          ]}>{loc.gpsStatus.message}</Text>
        </View>
      )}

      {loc.coords && (
        <View style={styles.coordsBox}>
          <Text style={styles.coordsText}>
            📡 {loc.coords.latitude.toFixed(6)}, {loc.coords.longitude.toFixed(6)}
            {loc.coords.accuracy != null ? `  (±${Math.round(loc.coords.accuracy)}m)` : ''}
          </Text>
        </View>
      )}

      <View style={styles.row}>
        <TouchableOpacity style={styles.pickerField} onPress={() => setStatePickerOpen(true)}>
          <Text style={styles.pickerLabel}>State</Text>
          <Text style={[styles.pickerValue, !loc.state && styles.pickerPlaceholder]}>
            {loc.state || 'Select state'}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.pickerField, !loc.state && styles.pickerFieldDisabled]}
          onPress={() => loc.state && setLgaPickerOpen(true)}
        >
          <Text style={styles.pickerLabel}>LGA</Text>
          <Text style={[styles.pickerValue, !loc.lga && styles.pickerPlaceholder]}>
            {loc.lga || 'Select LGA'}
          </Text>
        </TouchableOpacity>
      </View>

      <TextInput
        style={styles.input}
        placeholder="Community / town / village (e.g. Funtua town)"
        placeholderTextColor={Colors.textMuted}
        value={loc.community}
        onChangeText={loc.setCommunity}
      />
      <View style={styles.row}>
        <TextInput
          style={[styles.input, styles.inputHalf]}
          placeholder="Postal code (if known)"
          placeholderTextColor={Colors.textMuted}
          value={loc.postalCode}
          onChangeText={loc.setPostalCode}
        />
        <TextInput
          style={[styles.input, styles.inputHalf]}
          placeholder="Address / landmark"
          placeholderTextColor={Colors.textMuted}
          value={loc.address}
          onChangeText={loc.setAddress}
        />
      </View>

      <TouchableOpacity style={styles.checkRow} onPress={() => loc.setDiffersFromScan(!loc.differsFromScan)}>
        <View style={[styles.checkbox, loc.differsFromScan && styles.checkboxChecked]}>
          {loc.differsFromScan && <Text style={styles.checkboxTick}>✓</Text>}
        </View>
        <Text style={styles.checkLabel}>
          I&apos;m scanning this sample at a different location from where it was collected (e.g. a lab or office, away from the farm).
        </Text>
      </TouchableOpacity>

      <TouchableOpacity style={styles.checkRow} onPress={() => loc.setConfirmed(!loc.confirmed)}>
        <View style={[styles.checkbox, loc.confirmed && styles.checkboxChecked]}>
          {loc.confirmed && <Text style={styles.checkboxTick}>✓</Text>}
        </View>
        <Text style={styles.checkLabel}>I confirm the location entered above is correct.</Text>
      </TouchableOpacity>

      <PickerModal
        visible={statePickerOpen}
        title="Select State"
        search={stateSearch}
        onSearch={setStateSearch}
        items={filteredStates.map(s => s.name)}
        onSelect={(name) => { loc.selectState(name); setStateSearch(''); setStatePickerOpen(false); }}
        onClose={() => { setStatePickerOpen(false); setStateSearch(''); }}
      />
      <PickerModal
        visible={lgaPickerOpen}
        title="Select LGA"
        search={lgaSearch}
        onSearch={setLgaSearch}
        items={filteredLgas}
        onSelect={(name) => { loc.selectLga(name); setLgaSearch(''); setLgaPickerOpen(false); }}
        onClose={() => { setLgaPickerOpen(false); setLgaSearch(''); }}
      />
    </View>
  );
}

function PickerModal({ visible, title, search, onSearch, items, onSelect, onClose }) {
  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.modalOverlay}>
        <View style={styles.modalSheet}>
          <View style={styles.modalHandle} />
          <Text style={styles.modalTitle}>{title}</Text>
          <TextInput
            style={styles.modalSearch}
            value={search}
            onChangeText={onSearch}
            placeholder="Search…"
            placeholderTextColor={Colors.textMuted}
            autoCapitalize="none"
          />
          <FlatList
            data={items}
            keyExtractor={(item) => item}
            style={{ maxHeight: 360 }}
            keyboardShouldPersistTaps="handled"
            ListEmptyComponent={<Text style={styles.modalEmpty}>No matches found.</Text>}
            renderItem={({ item }) => (
              <TouchableOpacity style={styles.modalOption} onPress={() => onSelect(item)}>
                <Text style={styles.modalOptionText}>{item}</Text>
              </TouchableOpacity>
            )}
          />
          <TouchableOpacity style={styles.modalCloseBtn} onPress={onClose}>
            <Text style={styles.modalCloseText}>Cancel</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  wrap: {
    backgroundColor: Colors.card, borderRadius: Radius.lg, borderWidth: 1, borderColor: Colors.border,
    padding: Spacing.md, marginTop: Spacing.md, marginBottom: Spacing.md, ...Shadows.sm,
  },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  title:     { ...Typography.label, color: Colors.textPrimary, flexShrink: 1, marginRight: Spacing.sm },
  optional:  { ...Typography.tiny, color: Colors.textMuted, fontWeight: '400' },
  gpsBtn: {
    backgroundColor: '#EAF7EF', borderRadius: Radius.full, paddingHorizontal: Spacing.sm, paddingVertical: 6,
  },
  gpsBtnText: { ...Typography.tiny, color: Colors.primary, fontWeight: '700' },
  hint: { ...Typography.tiny, color: Colors.textSecondary, marginBottom: Spacing.sm, lineHeight: 16 },

  statusBox: { borderRadius: Radius.sm, padding: Spacing.sm, marginBottom: Spacing.sm },
  statusSuccess: { backgroundColor: '#EAF7EF' },
  statusWarning: { backgroundColor: '#FEF3C7' },
  statusError:   { backgroundColor: '#FEF2F2' },
  statusText:        { ...Typography.tiny, lineHeight: 16 },
  statusTextSuccess: { color: Colors.success },
  statusTextWarning: { color: '#92400E' },
  statusTextError:   { color: Colors.danger },

  coordsBox: { backgroundColor: Colors.background, borderRadius: Radius.sm, padding: Spacing.sm, marginBottom: Spacing.sm },
  coordsText: { ...Typography.tiny, color: Colors.textSecondary, fontFamily: 'monospace' },

  row: { flexDirection: 'row', gap: Spacing.sm, marginBottom: Spacing.sm },
  pickerField: {
    flex: 1, backgroundColor: Colors.background, borderRadius: Radius.sm,
    borderWidth: 1, borderColor: Colors.border, padding: Spacing.sm,
  },
  pickerFieldDisabled: { opacity: 0.5 },
  pickerLabel: { ...Typography.tiny, color: Colors.textMuted, marginBottom: 2 },
  pickerValue: { ...Typography.small, color: Colors.textPrimary, fontWeight: '600' },
  pickerPlaceholder: { color: Colors.textMuted, fontWeight: '400' },

  input: {
    backgroundColor: Colors.background, borderRadius: Radius.sm, borderWidth: 1, borderColor: Colors.border,
    padding: Spacing.sm, marginBottom: Spacing.sm, ...Typography.small, color: Colors.textPrimary,
  },
  inputHalf: { flex: 1, marginBottom: 0 },

  checkRow: { flexDirection: 'row', alignItems: 'flex-start', gap: Spacing.sm, marginTop: 4 },
  checkbox: {
    width: 20, height: 20, borderRadius: 5, borderWidth: 1.5, borderColor: Colors.border,
    alignItems: 'center', justifyContent: 'center', marginTop: 1,
  },
  checkboxChecked: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  checkboxTick: { color: Colors.white, fontSize: 12, fontWeight: '700' },
  checkLabel: { ...Typography.tiny, color: Colors.textSecondary, flex: 1, lineHeight: 16 },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalSheet: { backgroundColor: Colors.white, borderTopLeftRadius: Radius.xl, borderTopRightRadius: Radius.xl, padding: Spacing.lg, maxHeight: '75%' },
  modalHandle: { width: 40, height: 4, backgroundColor: Colors.border, borderRadius: 2, alignSelf: 'center', marginBottom: Spacing.sm },
  modalTitle: { ...Typography.h3, color: Colors.textPrimary, marginBottom: Spacing.sm },
  modalSearch: {
    backgroundColor: Colors.background, borderRadius: Radius.sm, borderWidth: 1, borderColor: Colors.border,
    padding: Spacing.sm, marginBottom: Spacing.sm, ...Typography.small, color: Colors.textPrimary,
  },
  modalEmpty: { ...Typography.small, color: Colors.textMuted, textAlign: 'center', padding: Spacing.md },
  modalOption: { paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: Colors.border },
  modalOptionText: { ...Typography.body, color: Colors.textPrimary },
  modalCloseBtn: { marginTop: Spacing.sm, alignItems: 'center', padding: Spacing.sm },
  modalCloseText: { ...Typography.body, color: Colors.danger, fontWeight: '600' },
});
