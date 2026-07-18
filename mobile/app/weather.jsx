import React, { useEffect, useState, useCallback } from 'react';
import {
  View, Text, ScrollView, StyleSheet,
  TouchableOpacity, ActivityIndicator, RefreshControl,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { weatherAPI } from '../lib/api';
import { Colors, Spacing, Radius, Typography, Shadows } from '../constants/Theme';

const UV_LABEL = (uv) =>
  uv <= 2 ? 'Low' : uv <= 5 ? 'Moderate' : uv <= 7 ? 'High' : uv <= 10 ? 'Very High' : 'Extreme';

const UV_COLOR = (uv) =>
  uv <= 2 ? '#16A34A' : uv <= 5 ? '#D97706' : uv <= 7 ? '#EA580C' : '#DC2626';

function MetricCard({ icon, label, value, unit, color }) {
  return (
    <View style={[styles.metricCard, { borderTopColor: color || Colors.primary }]}>
      <Text style={styles.metricIcon}>{icon}</Text>
      <Text style={[styles.metricValue, { color: color || Colors.primary }]}>{value}<Text style={styles.metricUnit}>{unit}</Text></Text>
      <Text style={styles.metricLabel}>{label}</Text>
    </View>
  );
}

export default function WeatherScreen() {
  const { t, i18n } = useTranslation();
  const { user } = useAuth();
  const router = useRouter();
  const isHausa = i18n.language === 'ha';

  const [data, setData]         = useState(null);
  const [loading, setLoading]   = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError]       = useState(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const res = await weatherAPI.current({ state: user?.state || 'Katsina' });
      setData(res);
    } catch (e) {
      setError(e.message || 'Failed to load weather');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [user]);

  useEffect(() => { load(); }, [load]);

  const onRefresh = () => { setRefreshing(true); load(); };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color={Colors.primary} />
        <Text style={styles.loadingText}>Loading weather...</Text>
      </View>
    );
  }

  if (error || !data) {
    return (
      <View style={styles.center}>
        <Text style={{ fontSize: 48 }}>🌡️</Text>
        <Text style={styles.errorText}>{error || 'Weather unavailable'}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={load}>
          <Text style={styles.retryBtnText}>Try Again</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const { current, forecast = [], hourly_next6 = [], advisory = {}, location } = data;

  return (
    <ScrollView
      style={styles.root}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#fff" colors={[Colors.primary]} />}
    >
      {/* Hero */}
      <View style={styles.hero}>
        <TouchableOpacity style={styles.back} onPress={() => router.back()}>
          <Text style={styles.backText}>‹ {isHausa ? 'Koma' : 'Back'}</Text>
        </TouchableOpacity>
        <Text style={styles.heroLocation}>📍 {location}</Text>
        <Text style={styles.heroTemp}>{current.temperature}°C</Text>
        <Text style={styles.heroEmoji}>{current.emoji}</Text>
        <Text style={styles.heroDesc}>{current.description}</Text>
        <Text style={styles.heroFeels}>Feels like {current.feels_like}°C</Text>
        <Text style={styles.heroUpdated}>Updated just now · {new Date().toLocaleTimeString()}</Text>
      </View>

      {/* Alerts */}
      {advisory.alerts?.length > 0 && (
        <View style={styles.alertsBox}>
          {advisory.alerts.map((a, i) => (
            <View key={i} style={styles.alertRow}>
              <Text style={styles.alertText}>{a}</Text>
            </View>
          ))}
        </View>
      )}

      {/* Current metrics */}
      <Text style={styles.sectionTitle}>{isHausa ? 'Yanayi Na Yanzu' : 'Current Conditions'}</Text>
      <View style={styles.metricsGrid}>
        <MetricCard icon="💧" label="Humidity"      value={current.humidity}    unit="%" color="#2563EB" />
        <MetricCard icon="🌬"  label="Wind Speed"   value={current.wind_speed}  unit=" km/h" color="#7C3AED" />
        <MetricCard icon="🌧"  label="Precipitation" value={current.precipitation} unit=" mm" color="#0891B2" />
        <MetricCard icon="☀️" label={`UV: ${UV_LABEL(current.uv_index)}`} value={current.uv_index} unit="" color={UV_COLOR(current.uv_index)} />
      </View>

      {/* Next 6 hours */}
      {hourly_next6.length > 0 && (
        <>
          <Text style={styles.sectionTitle}>{isHausa ? 'Awanni 6 Masu Zuwa' : 'Next 6 Hours'}</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.hourlyRow}>
            {hourly_next6.map((h, i) => (
              <View key={i} style={styles.hourlyCard}>
                <Text style={styles.hourlyTime}>{h.time}</Text>
                <Text style={styles.hourlyTemp}>{h.temp}°</Text>
                <Text style={styles.hourlyRain}>{h.rain_chance}% 🌧</Text>
              </View>
            ))}
          </ScrollView>
        </>
      )}

      {/* 7-day forecast */}
      <Text style={styles.sectionTitle}>{isHausa ? 'Hasashen Makonni 7' : '7-Day Forecast'}</Text>
      {forecast.map((day, i) => (
        <View key={i} style={styles.forecastRow}>
          <Text style={styles.forecastDay}>{i === 0 ? 'Today' : day.day}</Text>
          <Text style={styles.forecastEmoji}>{day.emoji}</Text>
          <Text style={styles.forecastDesc}>{day.description}</Text>
          <View style={styles.forecastRight}>
            <Text style={styles.forecastRain}>{day.rain_chance}%🌧</Text>
            <Text style={styles.forecastTemps}>{day.temp_min}° / <Text style={{ color: Colors.primary, fontWeight: '700' }}>{day.temp_max}°</Text></Text>
          </View>
        </View>
      ))}

      {/* Farm advisory */}
      {advisory.tips?.length > 0 && (
        <>
          <Text style={styles.sectionTitle}>{isHausa ? '🌾 Shawarwari Don Noma' : '🌾 Farm Advisory'}</Text>
          <View style={styles.advisoryCard}>
            {advisory.tips.map((tip, i) => (
              <Text key={i} style={styles.advisoryTip}>{tip}</Text>
            ))}
          </View>
        </>
      )}

      <Text style={styles.footer}>Weather data from Open-Meteo · Free & Open Source</Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root:    { flex: 1, backgroundColor: Colors.background },
  content: { paddingBottom: 80 },
  center:  { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.background, padding: 20 },

  hero: {
    backgroundColor: Colors.primary,
    alignItems: 'center',
    paddingTop: 55, paddingBottom: 28,
    paddingHorizontal: Spacing.md,
  },
  back:         { alignSelf: 'flex-start', marginBottom: 12 },
  backText:     { color: 'rgba(255,255,255,0.8)', fontSize: 16, fontWeight: '600' },
  heroLocation: { color: 'rgba(255,255,255,0.85)', fontSize: 13, marginBottom: 8 },
  heroTemp:     { fontSize: 72, fontWeight: '800', color: '#fff', lineHeight: 78 },
  heroEmoji:    { fontSize: 40, marginVertical: 4 },
  heroDesc:     { fontSize: 18, color: '#fff', fontWeight: '600' },
  heroFeels:    { fontSize: 13, color: 'rgba(255,255,255,0.75)', marginTop: 4 },
  heroUpdated:  { fontSize: 11, color: 'rgba(255,255,255,0.55)', marginTop: 6 },

  alertsBox:  { margin: Spacing.md, gap: 8 },
  alertRow:   { backgroundColor: '#FEF2F2', borderLeftWidth: 4, borderLeftColor: '#DC2626', borderRadius: 8, padding: 12 },
  alertText:  { fontSize: 13, color: '#991B1B', fontWeight: '600' },

  sectionTitle: { ...Typography.h3, color: Colors.textPrimary, paddingHorizontal: Spacing.md, marginTop: Spacing.lg, marginBottom: Spacing.sm },

  metricsGrid: { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: Spacing.md, gap: Spacing.sm },
  metricCard: {
    flex: 1, minWidth: '44%', backgroundColor: Colors.white,
    borderRadius: Radius.md, padding: Spacing.md,
    alignItems: 'center', ...Shadows.sm, borderTopWidth: 3,
  },
  metricIcon:  { fontSize: 24, marginBottom: 4 },
  metricValue: { fontSize: 22, fontWeight: '800' },
  metricUnit:  { fontSize: 13, fontWeight: '500' },
  metricLabel: { fontSize: 12, color: Colors.textSecondary, marginTop: 4, textAlign: 'center' },

  hourlyRow:  { paddingLeft: Spacing.md, marginBottom: Spacing.sm },
  hourlyCard: {
    alignItems: 'center', backgroundColor: Colors.white,
    borderRadius: Radius.md, padding: 12, marginRight: 10, minWidth: 68,
    ...Shadows.sm,
  },
  hourlyTime: { fontSize: 11, color: Colors.textMuted, fontWeight: '600' },
  hourlyTemp: { fontSize: 20, fontWeight: '800', color: Colors.primary, marginVertical: 4 },
  hourlyRain: { fontSize: 10, color: Colors.textSecondary },

  forecastRow: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: Colors.white, marginHorizontal: Spacing.md,
    marginBottom: 8, borderRadius: Radius.md, padding: 14,
    ...Shadows.sm, gap: 10,
  },
  forecastDay:   { width: 44, fontSize: 13, fontWeight: '700', color: Colors.textPrimary },
  forecastEmoji: { fontSize: 22 },
  forecastDesc:  { flex: 1, fontSize: 12, color: Colors.textSecondary },
  forecastRight: { alignItems: 'flex-end', gap: 2 },
  forecastRain:  { fontSize: 11, color: Colors.textSecondary },
  forecastTemps: { fontSize: 13, color: Colors.textSecondary },

  advisoryCard: {
    marginHorizontal: Spacing.md, backgroundColor: '#F0FDF4',
    borderRadius: Radius.md, padding: Spacing.md,
    borderWidth: 1, borderColor: '#BBF7D0', gap: 8,
  },
  advisoryTip: { fontSize: 13, color: Colors.textPrimary, lineHeight: 20 },

  loadingText: { marginTop: 12, color: Colors.textSecondary, fontSize: 14 },
  errorText:   { marginTop: 12, fontSize: 15, color: Colors.danger, textAlign: 'center' },
  retryBtn:    { marginTop: 16, backgroundColor: Colors.primary, borderRadius: 8, paddingHorizontal: 24, paddingVertical: 10 },
  retryBtnText:{ color: '#fff', fontWeight: '700' },
  footer:      { textAlign: 'center', color: Colors.textMuted, fontSize: 11, padding: Spacing.lg },
});
