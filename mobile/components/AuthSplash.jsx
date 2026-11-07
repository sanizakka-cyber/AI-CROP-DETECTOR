// Shown while the saved login is being checked at app start (AuthContext
// restoreSession: /auth/me, which can take 10+ seconds on a slow
// connection or a sleeping server). Before this, the sign-in form showed for
// that whole time even for a farmer who was signed in.
import React from 'react';
import { View, Text, Image, ActivityIndicator, StyleSheet } from 'react-native';
import { useTranslation } from 'react-i18next';

export default function AuthSplash() {
  const { t } = useTranslation();
  return (
    <View style={styles.root} testID="auth-splash" accessibilityLabel={t('auth_checking')}>
      <View style={styles.logoWrap}>
        <Image source={require('../assets/images/msas_logo.png')} style={styles.logo} resizeMode="cover" />
      </View>
      <Text style={styles.brand}>MSAS FarmAI</Text>
      <ActivityIndicator size="large" color="#FFFFFF" style={styles.spinner} />
      <Text style={styles.note}>{t('auth_checking')}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  // Same navy as the sign-in screen's header, so the hand-over is not a flash.
  root: { flex: 1, backgroundColor: '#0D1F3C', alignItems: 'center', justifyContent: 'center', padding: 24 },
  logoWrap: { width: 112, height: 112, borderRadius: 28, overflow: 'hidden', marginBottom: 16 },
  logo: { width: '100%', height: '100%' },
  brand: { color: '#FFFFFF', fontSize: 26, fontWeight: '800' },
  spinner: { marginTop: 28 },
  note: { color: 'rgba(255,255,255,0.75)', fontSize: 14, marginTop: 12, textAlign: 'center' },
});
