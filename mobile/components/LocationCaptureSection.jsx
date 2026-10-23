// Shared "Sample Collection Location" UI for all four scan screens
// (crop/livestock/soil/pest) — spec Section 5 / MSAS FarmAI Phase 6.
// Pair with the useLocationCapture() hook (../lib/locationCapture) for state.
//
// Mirrors the web scan form's location section (resources/views/
// diagnostics/scan.blade.php): GPS with live accuracy, a quality rating,
// the captured point to check (coordinates, accuracy, time, map with a
// draggable pin), or entry by hand. Every message comes from the hook as an
// i18n key and is shown in the farmer's language.
import React, { useMemo, useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, Modal, FlatList, Linking,
  StyleSheet, ActivityIndicator,
} from 'react-native';
import { useTranslation } from 'react-i18next';
import { Colors, Spacing, Radius, Typography, Shadows } from '../constants/Theme';
import { formatAccuracy, needsRetry } from '../lib/gpsQuality';
import LocationMapPreview from './LocationMapPreview';

const RATING_STYLE = {
  excellent: { backgroundColor: '#D1FAE5', color: '#065F46' },
  good:      { backgroundColor: '#E0F2FE', color: '#075985' },
  fair:      { backgroundColor: '#FEF3C7', color: '#92400E' },
  low:       { backgroundColor: '#FEE2E2', color: '#991B1B' },
};

export default function LocationCaptureSection({ loc }) {
  const { t } = useTranslation();
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

  // i18next interpolates {{x}}; the explicit replace keeps tests with a plain t() mock honest.
  const tx = (key, params = {}) => Object.entries(params)
    .reduce((s, [k, v]) => s.replaceAll(`{{${k}}}`, String(v)), String(t(key, params)));
  const status = loc.gpsStatus;
  const statusParams = status?.params
    ? { ...status.params, acc: formatAccuracy(status.params.acc), rating: t(`rating_${status.params.rating}`) }
    : {};
  const progress = loc.gpsProgress;
  const fix = loc.originalCoords || loc.coords;

  return (
    <View style={styles.wrap}>
      <View style={styles.headerRow}>
        <Text style={styles.title}>📍 {t('loc_title')} <Text style={styles.optional}>{t('loc_optional')}</Text></Text>
      </View>
      <Text style={styles.hint}>{t('loc_hint')}</Text>

      {/* Two ways: GPS fills State and LGA from the boundaries, or the farmer picks them. */}
      <View style={styles.modeRow} accessibilityRole="radiogroup">
        <TouchableOpacity
          style={[styles.modeBtn, loc.mode === 'gps' && styles.modeBtnOn]}
          onPress={loc.captureGps}
          disabled={loc.gpsLoading}
          accessibilityRole="radio"
          accessibilityState={{ checked: loc.mode === 'gps', busy: loc.gpsLoading }}
          testID="loc-mode-gps"
        >
          {loc.gpsLoading
            ? <ActivityIndicator size="small" color={Colors.primary} />
            : <Text style={[styles.modeBtnText, loc.mode === 'gps' && styles.modeBtnTextOn]}>📡 {t('loc_use_gps')}</Text>}
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.modeBtn, loc.mode === 'manual' && styles.modeBtnOn]}
          onPress={loc.enterManually}
          accessibilityRole="radio"
          accessibilityState={{ checked: loc.mode === 'manual' }}
          testID="loc-mode-manual"
        >
          <Text style={[styles.modeBtnText, loc.mode === 'manual' && styles.modeBtnTextOn]}>✏️ {t('loc_enter_by_hand')}</Text>
        </TouchableOpacity>
      </View>

      {/* While watching the GPS: the accuracy as it improves. */}
      {loc.gpsLoading && (
        <View style={[styles.statusBox, styles.statusSuccess]} testID="loc-progress">
          <Text style={[styles.statusText, styles.statusTextSuccess]}>
            {!progress ? t('loc_requesting')
              : progress.first > progress.best
                ? tx('loc_improving', { from: formatAccuracy(progress.first), to: formatAccuracy(progress.best) })
                : tx('loc_getting', { to: formatAccuracy(progress.best) })}
          </Text>
          {progress && (
            <TouchableOpacity onPress={loc.stopCapture} style={styles.settingsBtn} testID="loc-use-now">
              <Text style={styles.settingsBtnText}>{t('loc_use_now')}</Text>
            </TouchableOpacity>
          )}
        </View>
      )}

      {status && !loc.gpsLoading && (
        <View testID="loc-status" style={[
          styles.statusBox,
          status.type === 'success' && styles.statusSuccess,
          status.type === 'warning' && styles.statusWarning,
          status.type === 'error'   && styles.statusError,
        ]}>
          <Text style={[
            styles.statusText,
            status.type === 'success' && styles.statusTextSuccess,
            status.type === 'warning' && styles.statusTextWarning,
            status.type === 'error'   && styles.statusTextError,
          ]}>
            {tx(status.key, statusParams)}{status.bestIn30 ? ` ${t('loc_best_in_30')}` : ''}
          </Text>
          {(status.help || []).map(h => (
            <Text key={h} style={[styles.statusText, styles.helpLine]}>• {t(h)}</Text>
          ))}
          <View style={styles.actionRow}>
            {status.canRetry && (
              <TouchableOpacity onPress={loc.captureGps} style={styles.actionBtn} testID="loc-try-again">
                <Text style={styles.actionBtnText}>{t('loc_try_again')}</Text>
              </TouchableOpacity>
            )}
            {status.canOpenSettings && (
              <TouchableOpacity onPress={loc.openLocationSettings} style={styles.actionBtn} testID="loc-open-settings">
                <Text style={styles.actionBtnText}>{t('loc_open_settings')}</Text>
              </TouchableOpacity>
            )}
            {status.canManual && (
              <TouchableOpacity onPress={loc.enterManually} style={styles.actionBtn} testID="loc-manual-instead">
                <Text style={styles.actionBtnText}>{t('loc_manual_instead')}</Text>
              </TouchableOpacity>
            )}
          </View>
        </View>
      )}

      {/* The captured point, to check before submitting. */}
      {loc.coords && (
        <View style={styles.coordsBox} testID="loc-result">
          <View style={styles.ratingRow}>
            {loc.quality && (
              <Text style={[styles.ratingBadge, RATING_STYLE[loc.quality]]} testID="loc-rating">{t(`rating_${loc.quality}`)}</Text>
            )}
            <Text style={styles.coordsText}>±{formatAccuracy(loc.coords.accuracy)} m</Text>
            <TouchableOpacity onPress={() => Linking.openURL(`https://www.google.com/maps?q=${loc.coords.latitude},${loc.coords.longitude}`)}>
              <Text style={styles.settingsBtnText}>{t('loc_open_maps')} ↗</Text>
            </TouchableOpacity>
          </View>
          <Text style={styles.coordsText}>
            {t('loc_latitude')}: {loc.coords.latitude.toFixed(6)}   {t('loc_longitude')}: {loc.coords.longitude.toFixed(6)}
          </Text>
          {loc.gpsCapturedAt && (
            <Text style={styles.coordsText}>{t('loc_time_captured')}: {new Date(loc.gpsCapturedAt).toLocaleString()}</Text>
          )}
          {loc.locationSource === 'gps_adjusted' && <Text style={styles.coordsText} testID="loc-adjusted">{t('loc_adjusted')}</Text>}
          {loc.isMocked && <Text style={[styles.coordsText, styles.statusTextError]} testID="loc-mocked">{t('loc_mocked_badge')}</Text>}
          {needsRetry(loc.quality) && !status?.canRetry && (
            <TouchableOpacity onPress={loc.captureGps} style={styles.actionBtn}>
              <Text style={styles.actionBtnText}>{t('loc_try_again')}</Text>
            </TouchableOpacity>
          )}
          <LocationMapPreview key={`${fix.latitude},${fix.longitude}`} fix={fix} onMove={loc.adjustPoint} t={t} />
        </View>
      )}

      {loc.namesNote && (
        <Text style={styles.namesNote} testID="loc-names-note">
          {tx(loc.namesNote.key, loc.namesNote.params)}{loc.namesNote.attribution ? ` ${loc.namesNote.attribution}` : ''}
        </Text>
      )}

      <View style={styles.row}>
        <TouchableOpacity style={styles.pickerField} onPress={() => setStatePickerOpen(true)}>
          <Text style={styles.pickerLabel}>{t('loc_state')}</Text>
          <Text style={[styles.pickerValue, !loc.state && styles.pickerPlaceholder]}>
            {loc.state || t('loc_select_state')}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.pickerField, !loc.state && styles.pickerFieldDisabled]}
          onPress={() => loc.state && setLgaPickerOpen(true)}
        >
          <Text style={styles.pickerLabel}>{t('loc_lga')}</Text>
          <Text style={[styles.pickerValue, !loc.lga && styles.pickerPlaceholder]}>
            {loc.lga || t('loc_select_lga')}
          </Text>
        </TouchableOpacity>
      </View>

      <TextInput
        style={styles.input}
        placeholder={t('loc_community_ph')}
        placeholderTextColor={Colors.textMuted}
        value={loc.community}
        onChangeText={loc.setCommunity}
      />
      <View style={styles.row}>
        <TextInput
          style={[styles.input, styles.inputHalf]}
          placeholder={t('loc_postal_ph')}
          placeholderTextColor={Colors.textMuted}
          value={loc.postalCode}
          onChangeText={loc.setPostalCode}
        />
        <TextInput
          style={[styles.input, styles.inputHalf]}
          placeholder={t('loc_address_ph')}
          placeholderTextColor={Colors.textMuted}
          value={loc.address}
          onChangeText={loc.setAddress}
        />
      </View>

      <TouchableOpacity style={styles.checkRow} onPress={() => loc.setDiffersFromScan(!loc.differsFromScan)}>
        <View style={[styles.checkbox, loc.differsFromScan && styles.checkboxChecked]}>
          {loc.differsFromScan && <Text style={styles.checkboxTick}>✓</Text>}
        </View>
        <Text style={styles.checkLabel}>{t('loc_differs')}</Text>
      </TouchableOpacity>

      <TouchableOpacity style={styles.checkRow} onPress={() => loc.setConfirmed(!loc.confirmed)}>
        <View style={[styles.checkbox, loc.confirmed && styles.checkboxChecked]}>
          {loc.confirmed && <Text style={styles.checkboxTick}>✓</Text>}
        </View>
        <Text style={styles.checkLabel}>{t('loc_confirm')}</Text>
      </TouchableOpacity>

      <Text style={[styles.hint, { marginTop: Spacing.sm }]}>{t('loc_privacy')}</Text>

      <PickerModal
        visible={statePickerOpen}
        title={t('loc_select_state')}
        search={stateSearch}
        onSearch={setStateSearch}
        items={filteredStates.map(s => s.name)}
        onSelect={(name) => { loc.selectState(name); setStateSearch(''); setStatePickerOpen(false); }}
        onClose={() => { setStatePickerOpen(false); setStateSearch(''); }}
        t={t}
      />
      <PickerModal
        visible={lgaPickerOpen}
        title={t('loc_select_lga')}
        search={lgaSearch}
        onSearch={setLgaSearch}
        items={filteredLgas}
        onSelect={(name) => { loc.selectLga(name); setLgaSearch(''); setLgaPickerOpen(false); }}
        onClose={() => { setLgaPickerOpen(false); setLgaSearch(''); }}
        t={t}
      />
    </View>
  );
}

function PickerModal({ visible, title, search, onSearch, items, onSelect, onClose, t }) {
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
            placeholder={t('loc_search')}
            placeholderTextColor={Colors.textMuted}
            autoCapitalize="none"
          />
          <FlatList
            data={items}
            keyExtractor={(item) => item}
            style={{ maxHeight: 360 }}
            keyboardShouldPersistTaps="handled"
            ListEmptyComponent={<Text style={styles.modalEmpty}>{t('loc_no_matches')}</Text>}
            renderItem={({ item }) => (
              <TouchableOpacity style={styles.modalOption} onPress={() => onSelect(item)}>
                <Text style={styles.modalOptionText}>{item}</Text>
              </TouchableOpacity>
            )}
          />
          <TouchableOpacity style={styles.modalCloseBtn} onPress={onClose}>
            <Text style={styles.modalCloseText}>{t('loc_cancel')}</Text>
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
  hint: { ...Typography.tiny, color: Colors.textSecondary, marginBottom: Spacing.sm, lineHeight: 16 },

  modeRow: { flexDirection: 'row', gap: Spacing.sm, marginBottom: Spacing.sm },
  modeBtn: {
    flex: 1, minHeight: 44, alignItems: 'center', justifyContent: 'center', borderRadius: Radius.sm,
    borderWidth: 1, borderColor: Colors.border, backgroundColor: Colors.background, paddingHorizontal: Spacing.sm,
  },
  modeBtnOn: { borderColor: Colors.primary, backgroundColor: '#EAF7EF' },
  modeBtnText: { ...Typography.small, color: Colors.textSecondary, fontWeight: '700' },
  modeBtnTextOn: { color: Colors.primary },
  helpLine: { marginTop: 2 },
  actionRow: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm, marginTop: Spacing.xs },
  actionBtn: { borderWidth: 1, borderColor: Colors.border, backgroundColor: Colors.card, borderRadius: Radius.sm, paddingHorizontal: Spacing.sm, paddingVertical: 6, alignSelf: 'flex-start', marginTop: 4 },
  actionBtnText: { ...Typography.tiny, color: Colors.primary, fontWeight: '700' },
  ratingRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm, flexWrap: 'wrap', marginBottom: 4 },
  ratingBadge: { ...Typography.tiny, fontWeight: '700', paddingHorizontal: 8, paddingVertical: 2, borderRadius: Radius.full, overflow: 'hidden' },
  namesNote: { ...Typography.tiny, color: Colors.textSecondary, marginBottom: Spacing.sm, lineHeight: 16 },

  statusBox: { borderRadius: Radius.sm, padding: Spacing.sm, marginBottom: Spacing.sm },
  statusSuccess: { backgroundColor: '#EAF7EF' },
  statusWarning: { backgroundColor: '#FEF3C7' },
  statusError:   { backgroundColor: '#FEF2F2' },
  statusText:        { ...Typography.tiny, lineHeight: 16 },
  statusTextSuccess: { color: Colors.success },
  statusTextWarning: { color: '#92400E' },
  statusTextError:   { color: Colors.danger },
  settingsBtn: { marginTop: Spacing.xs, alignSelf: 'flex-start' },
  settingsBtnText: { ...Typography.tiny, color: Colors.primary, fontWeight: '700', textDecorationLine: 'underline' },

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
