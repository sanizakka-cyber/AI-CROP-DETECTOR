import '../lib/i18n';
import { Stack } from 'expo-router';
import { useEffect } from 'react';
import { StatusBar } from 'expo-status-bar';
import { AuthProvider, useAuth } from '../context/AuthContext';
import { SubscriptionProvider } from '../context/SubscriptionContext';
import { LanguageProvider } from '../context/LanguageContext';
import { useRouter, useSegments } from 'expo-router';

function RootGuard({ children }) {
  const { user, loading } = useAuth();
  const router = useRouter();
  const segments = useSegments();

  useEffect(() => {
    if (loading) return;
    const inAuth = segments[0] === '(auth)';
    if (!user && !inAuth) router.replace('/(auth)/login');
    if (user && inAuth) router.replace('/(tabs)/home');
  }, [user, loading, router, segments]);

  return children;
}

export default function RootLayout() {
  return (
    <AuthProvider>
      <SubscriptionProvider>
        <LanguageProvider>
        <StatusBar style="light" backgroundColor="#1C6B38" />
        <RootGuard>
          <Stack screenOptions={{ headerShown: false }}>
            <Stack.Screen name="(auth)" />
            <Stack.Screen name="(tabs)" />
            <Stack.Screen name="scan/crop" options={{ presentation: 'modal' }} />
            <Stack.Screen name="scan/livestock" options={{ presentation: 'modal' }} />
            <Stack.Screen name="diagnosis/[id]" />
            <Stack.Screen name="market/product" />
            <Stack.Screen name="market/cart" />
            <Stack.Screen name="market/orders" />
          </Stack>
        </RootGuard>
        </LanguageProvider>
      </SubscriptionProvider>
    </AuthProvider>
  );
}
