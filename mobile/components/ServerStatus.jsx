import React, { useEffect, useState } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { healthAPI } from '../lib/api';

// Only shown in development / preview environments
const IS_DEV = process.env.EXPO_PUBLIC_ENV !== 'production';

export default function ServerStatus() {
  const [status, setStatus] = useState(null); // null | { ok, url, app, version, error }
  const [checking, setChecking] = useState(false);

  const check = async () => {
    setChecking(true);
    const result = await healthAPI.check();
    setStatus(result);
    setChecking(false);
  };

  useEffect(() => {
    if (IS_DEV) check();
  }, []);

  if (!IS_DEV || !status) return null;

  return (
    <TouchableOpacity style={[styles.bar, status.ok ? styles.barOk : styles.barErr]} onPress={check} activeOpacity={0.8}>
      {checking ? (
        <ActivityIndicator size="small" color="#fff" />
      ) : (
        <>
          <Text style={styles.dot}>{status.ok ? '●' : '●'}</Text>
          <Text style={styles.txt} numberOfLines={1}>
            {status.ok
              ? `API: ${status.app || 'MSAS'} v${status.version || '?'} · ${status.url}`
              : `API unreachable: ${status.error || `HTTP ${status.status}`} · ${status.url}`}
          </Text>
          <Text style={styles.tap}>↻</Text>
        </>
      )}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 6,
    gap: 6,
  },
  barOk:  { backgroundColor: '#065F46' },
  barErr: { backgroundColor: '#991B1B' },
  dot:  { fontSize: 8, color: '#A7F3D0' },
  txt:  { flex: 1, fontSize: 10, color: 'rgba(255,255,255,0.9)', fontWeight: '600' },
  tap:  { fontSize: 14, color: 'rgba(255,255,255,0.7)' },
});
