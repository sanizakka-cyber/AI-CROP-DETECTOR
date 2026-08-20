import React, { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  KeyboardAvoidingView, Platform, Alert, StatusBar, ActivityIndicator,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useAuth } from '../../context/AuthContext';

const C = {
  navy: '#0B2447', green: '#0F6B3E', greenLt: '#1FA84A',
  white: '#FFFFFF', textDark: '#0F172A', textMid: '#475569',
  textLight: '#94A3B8', border: '#E2E8F0', cardBg: '#F8FAFC',
};

export default function VerifyOtpScreen() {
  const { identifier } = useLocalSearchParams();
  const { verifyOtp, resendOtp } = useAuth();
  const router = useRouter();
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);

  const handleVerify = async () => {
    if (code.length !== 6) {
      return Alert.alert('', 'Enter the 6-digit code we emailed you.');
    }
    setLoading(true);
    try {
      await verifyOtp(identifier, code);
      router.replace('/(tabs)/home');
    } catch (e) {
      Alert.alert('Verification Failed', e.message || 'That code is incorrect. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    setResending(true);
    try {
      await resendOtp(identifier);
      Alert.alert('Code Sent', 'A new verification code has been sent to ' + identifier + '.');
    } catch (e) {
      Alert.alert('Error', e.message || 'Could not resend the code. Please try again shortly.');
    } finally {
      setResending(false);
    }
  };

  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: C.navy }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <StatusBar barStyle="light-content" backgroundColor={C.navy} />
      <View style={styles.hero}>
        <Text style={styles.heroIcon}>✉️</Text>
        <Text style={styles.heroTitle}>Verify Your Email</Text>
        <Text style={styles.heroSub}>We sent a 6-digit code to</Text>
        <Text style={styles.heroIdentifier}>{identifier}</Text>
      </View>

      <View style={styles.card}>
        <Text style={styles.fieldLabel}>VERIFICATION CODE</Text>
        <TextInput
          style={styles.codeInput}
          value={code}
          onChangeText={(v) => { if (/^\d{0,6}$/.test(v)) setCode(v); }}
          keyboardType="number-pad"
          maxLength={6}
          placeholder="000000"
          placeholderTextColor={C.textLight}
          textAlign="center"
          autoFocus
        />

        <TouchableOpacity
          style={[styles.submitBtn, (loading || code.length !== 6) && { opacity: 0.6 }]}
          onPress={handleVerify}
          disabled={loading || code.length !== 6}
        >
          {loading ? <ActivityIndicator color={C.white} /> : <Text style={styles.submitText}>Verify Account</Text>}
        </TouchableOpacity>

        <TouchableOpacity onPress={handleResend} disabled={resending} style={{ marginTop: 20 }}>
          <Text style={styles.resendText}>
            {resending ? 'Sending…' : "Didn't get the code? Resend"}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity onPress={() => router.replace('/(auth)/login')} style={{ marginTop: 16 }}>
          <Text style={styles.backText}>← Back to Sign In</Text>
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  hero: { backgroundColor: C.navy, paddingTop: 72, paddingBottom: 36, alignItems: 'center', paddingHorizontal: 24 },
  heroIcon: { fontSize: 44, marginBottom: 12 },
  heroTitle: { color: C.white, fontSize: 22, fontWeight: '800', marginBottom: 8 },
  heroSub: { color: 'rgba(255,255,255,0.6)', fontSize: 13 },
  heroIdentifier: { color: C.white, fontSize: 15, fontWeight: '700', marginTop: 4 },

  card: {
    flex: 1, backgroundColor: C.white,
    borderTopLeftRadius: 28, borderTopRightRadius: 28,
    paddingHorizontal: 24, paddingTop: 28,
  },
  fieldLabel: { fontSize: 10, fontWeight: '700', color: C.textMid, letterSpacing: 0.5, marginBottom: 10, textAlign: 'center' },
  codeInput: {
    borderWidth: 1.5, borderColor: C.border, borderRadius: 14,
    backgroundColor: C.cardBg, fontSize: 28, fontWeight: '800', letterSpacing: 10,
    color: C.textDark, paddingVertical: 16, marginBottom: 24,
  },
  submitBtn: {
    backgroundColor: C.green, borderRadius: 12, paddingVertical: 14,
    alignItems: 'center',
  },
  submitText: { color: C.white, fontSize: 15, fontWeight: '800' },
  resendText: { textAlign: 'center', color: C.greenLt, fontWeight: '700', fontSize: 13 },
  backText: { textAlign: 'center', color: C.textLight, fontWeight: '600', fontSize: 13 },
});
