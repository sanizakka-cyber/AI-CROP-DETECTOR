import { Tabs } from 'expo-router';
import { View, Text } from 'react-native';
import { Colors } from '../../constants/Theme';
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

  return (
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
  );
}
