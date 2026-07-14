import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useAuth } from './AuthContext';

// Shares the same root URL as api.js — strip the trailing /api if present
const API_ROOT = (process.env.EXPO_PUBLIC_API_URL || 'http://172.20.10.3:8000/api').replace(/\/api$/, '');

const PLAN_LEVELS = { none: 0, basic: 1, pro: 2, premium: 3 };

const PLAN_META = {
  basic: {
    name: 'Basic Plan', color: '#1FA84A', level: 1,
    price: { monthly: 2500, yearly: 25000 },
    limits: { livestock_records: 50, reports_per_month: 5, ai_scans_per_month: 10 },
  },
  pro: {
    name: 'Pro Plan', color: '#2D9CDB', level: 2,
    price: { monthly: 10000, yearly: 100000 },
    limits: { livestock_records: -1, reports_per_month: -1, ai_scans_per_month: -1 },
  },
  premium: {
    name: 'Premium Plan', color: '#F4A300', level: 3,
    price: { monthly: 35000, yearly: 350000 },
    limits: { livestock_records: -1, reports_per_month: -1, ai_scans_per_month: -1 },
  },
};

const SubscriptionContext = createContext(null);

export function SubscriptionProvider({ children }) {
  const { user, token } = useAuth();
  const [subscription, setSubscription] = useState(null);
  const [usage, setUsage]               = useState({});
  const [loading, setLoading]           = useState(false);

  const fetchSubscription = useCallback(async () => {
    if (!token || !user || user.role !== 'farmer') return;
    try {
      setLoading(true);
      const res = await fetch(`${API_ROOT}/api/subscription/status`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });
      if (res.ok) {
        const data = await res.json();
        setSubscription(data.subscription ?? null);
        setUsage(data.usage ?? {});
        await AsyncStorage.setItem('msas_subscription', JSON.stringify(data.subscription ?? null));
      }
    } catch {
      // Offline: fall back to cached value
      const cached = await AsyncStorage.getItem('msas_subscription');
      if (cached) setSubscription(JSON.parse(cached));
    } finally {
      setLoading(false);
    }
  }, [token, user]);

  useEffect(() => { fetchSubscription(); }, [fetchSubscription]);

  const currentPlan = subscription?.plan ?? 'none';
  const status      = subscription?.status ?? 'none';
  const isActive    = ['active', 'trial'].includes(status);
  const isTrial     = status === 'trial';

  const hasFeature = (feature) => {
    if (!user || user.role !== 'farmer') return true; // non-farmers bypass
    if (!isActive) return false;
    const plan = subscription?.plan;
    const featureMap = {
      // Basic features
      livestock_registration: ['basic', 'pro', 'premium'],
      basic_health_records:   ['basic', 'pro', 'premium'],
      feeding_schedule:       ['basic', 'pro', 'premium'],
      vaccination_reminders:  ['basic', 'pro', 'premium'],
      farm_activity_log:      ['basic', 'pro', 'premium'],
      mobile_access:          ['basic', 'pro', 'premium'],
      monthly_reports:        ['basic', 'pro', 'premium'],
      // Pro features
      unlimited_livestock:    ['pro', 'premium'],
      advanced_health_records:['pro', 'premium'],
      breeding_reproduction:  ['pro', 'premium'],
      production_tracking:    ['pro', 'premium'],
      vet_service_requests:   ['pro', 'premium'],
      inventory_management:   ['pro', 'premium'],
      financial_records:      ['pro', 'premium'],
      pdf_excel_reports:      ['pro', 'premium'],
      direct_messaging:       ['pro', 'premium'],
      disease_alerts:         ['pro', 'premium'],
      // Premium features
      ai_recommendations:     ['premium'],
      predictive_disease:     ['premium'],
      market_intelligence:    ['premium'],
      multi_farm:             ['premium'],
      multi_user:             ['premium'],
      traceability:           ['premium'],
      api_integration:        ['premium'],
    };
    return (featureMap[feature] ?? []).includes(plan);
  };

  const hasMinPlan = (minPlan) => {
    if (!user || user.role !== 'farmer') return true;
    if (!isActive) return false;
    return PLAN_LEVELS[currentPlan] >= PLAN_LEVELS[minPlan];
  };

  const getLimit = (key) => {
    const meta = PLAN_META[currentPlan];
    return meta?.limits?.[key] ?? 0;
  };

  const getCurrentUsage = (key) => usage[key] ?? 0;

  const hasReachedLimit = (key) => {
    const limit = getLimit(key);
    if (limit === -1) return false;
    return getCurrentUsage(key) >= limit;
  };

  const planMeta = PLAN_META[currentPlan] ?? null;

  const daysRemaining = () => {
    if (!subscription) return 0;
    const end = isTrial ? subscription.trial_ends_at : subscription.ends_at;
    if (!end) return 0;
    const diff = Math.ceil((new Date(end) - new Date()) / (1000 * 60 * 60 * 24));
    return Math.max(0, diff);
  };

  return (
    <SubscriptionContext.Provider value={{
      subscription, usage, loading,
      currentPlan, status, isActive, isTrial,
      planMeta, PLAN_META,
      hasFeature, hasMinPlan,
      getLimit, getCurrentUsage, hasReachedLimit,
      daysRemaining, refresh: fetchSubscription,
    }}>
      {children}
    </SubscriptionContext.Provider>
  );
}

export const useSubscription = () => useContext(SubscriptionContext);
