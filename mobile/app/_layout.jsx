import '../lib/i18n';
import { Stack } from 'expo-router';
import { useEffect, useRef } from 'react';
import { StatusBar } from 'expo-status-bar';
import { Platform } from 'react-native';
import { AuthProvider, useAuth } from '../context/AuthContext';
import { SubscriptionProvider } from '../context/SubscriptionContext';
import { LanguageProvider } from '../context/LanguageContext';
import { useRouter, useSegments } from 'expo-router';
import * as Notifications from 'expo-notifications';
import { profileAPI } from '../lib/api';
import { setupGlobalErrorHandler } from '../lib/betaLogger';
import { initOfflineQueue, offlineQueue } from '../lib/offlineQueue';
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_URL = process.env.EXPO_PUBLIC_API_URL || 'https://msasagro.com/api';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge:  true,
  }),
});

async function getStoredToken() {
  try { return await AsyncStorage.getItem('auth_token'); } catch { return null; }
}

async function registerForPushNotifications() {
  try {
    const { status: existing } = await Notifications.getPermissionsAsync();
    let finalStatus = existing;
    if (existing !== 'granted') {
      const { status } = await Notifications.requestPermissionsAsync();
      finalStatus = status;
    }
    if (finalStatus !== 'granted') return null;

    const token = (await Notifications.getExpoPushTokenAsync()).data;
    return token;
  } catch {
    return null;
  }
}

function RootGuard({ children }) {
  const { user, loading } = useAuth();
  const router = useRouter();
  const segments = useSegments();
  const notifListener = useRef(null);
  const responseListener = useRef(null);

  useEffect(() => {
    setupGlobalErrorHandler();
    initOfflineQueue(API_URL, getStoredToken);
  }, []);

  useEffect(() => {
    if (loading || !user) return;

    // Request push token and send to backend
    registerForPushNotifications().then(token => {
      if (token) profileAPI.updateFcmToken({ expo_push_token: token }).catch(() => {});
    });

    // Flush any offline queue items when user is authenticated
    offlineQueue.flush().catch(() => {});

    // Notification received while app is foregrounded
    notifListener.current = Notifications.addNotificationReceivedListener(() => {});

    // Notification tapped — handle routing
    responseListener.current = Notifications.addNotificationResponseReceivedListener(response => {
      const data = response.notification.request.content.data;
      if (data?.route) router.push(data.route);
    });

    return () => {
      if (notifListener.current)  Notifications.removeNotificationSubscription(notifListener.current);
      if (responseListener.current) Notifications.removeNotificationSubscription(responseListener.current);
    };
  }, [user, loading]);

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
            <Stack.Screen name="notifications" />
            <Stack.Screen name="weather" />
          </Stack>
        </RootGuard>
        </LanguageProvider>
      </SubscriptionProvider>
    </AuthProvider>
  );
}
