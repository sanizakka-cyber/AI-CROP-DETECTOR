import { Tabs, useRouter } from 'expo-router';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { Colors, Shadows } from '../../constants/Theme';
import { useSubscription } from '../../context/SubscriptionContext';
import { useAuth } from '../../context/AuthContext';

const TAB_ICON = { home: '🏠', scan: '🔬', records: '📋', market: '🛒', subscription: '⭐', profile: '👤' };

function SubscriptionTabIcon({ focused }) {
  const { user }    = useAuth();
  const { isActive, isTrial, currentPlan } = useSubscription() ?? {};
  const isWarning   = !isActive;

  return (
    <View style={{ alignItems: 'center' }}>
      <Text style={{ fontSize: focused ? 26 : 22 }}>⭐</Text>
      {isWarning && user?.role === 'farmer' && (
        <View style={{
          position: 'absolute', top: -2, right: -6,
          width: 8, height: 8, borderRadius: 4,
          backgroundColor: '#dc2626',
          borderWidth: 1.5, borderColor: '#fff',
        }} />
      )}
    </View>
  );
}

export default function TabsLayout() {
  const { user } = useAuth();
  const router = useRouter();

  return (
    <View style={{ flex: 1 }}>
      <Tabs
        screenOptions={({ route }) => ({
          headerShown: false,
          tabBarStyle: {
            backgroundColor: Colors.white,
            borderTopColor: Colors.border,
            height: 64,
            paddingBottom: 8,
          },
          tabBarActiveTintColor: Colors.primary,
          tabBarInactiveTintColor: Colors.textMuted,
          tabBarLabelStyle: { fontSize: 11, fontWeight: '600' },
          tabBarIcon: ({ focused }) => {
            if (route.name === 'subscription') {
              return <SubscriptionTabIcon focused={focused} />;
            }
            return <Text style={{ fontSize: focused ? 26 : 22 }}>{TAB_ICON[route.name] ?? '●'}</Text>;
          },
        })}
      >
        <Tabs.Screen name="home"         options={{ title: 'Home' }} />
        <Tabs.Screen name="scan"         options={{ title: 'Scan' }} />
        <Tabs.Screen name="records"      options={{ title: 'Records' }} />
        <Tabs.Screen name="market"       options={{ title: 'Market' }} />
        <Tabs.Screen name="subscription" options={{ title: 'Plans' }} />
        <Tabs.Screen name="profile"      options={{ title: 'Profile' }} />
      </Tabs>

      {/* Persistent floating access to the AI Assistant from any tab —
          mirrors the web app's always-visible chat bubble. */}
      <TouchableOpacity
        style={styles.aiFab}
        onPress={() => router.push('/assistant')}
        activeOpacity={0.85}
      >
        <MaterialCommunityIcons name="robot-outline" size={26} color={Colors.white} />
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  aiFab: {
    position: 'absolute', right: 18, bottom: 82,
    width: 54, height: 54, borderRadius: 27,
    backgroundColor: Colors.primary,
    alignItems: 'center', justifyContent: 'center',
    ...Shadows.md,
  },
});
