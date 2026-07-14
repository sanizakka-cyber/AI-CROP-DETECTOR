import AsyncStorage from '@react-native-async-storage/async-storage';
import { authAPI } from '../lib/api';

// Simple auth context using React Context + AsyncStorage
import React, { createContext, useContext, useEffect, useState } from 'react';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Rehydrate session on app start
    (async () => {
      try {
        const stored = await AsyncStorage.getItem('token');
        if (stored) {
          setToken(stored);
          const { user: me } = await authAPI.me();
          setUser(me);
        }
      } catch {
        await AsyncStorage.removeItem('token');
      } finally {
        setLoading(false);
      }
    })();

    // Register global callback so api.js can trigger logout on 401
    globalThis.__onAuthExpired = () => {
      setToken(null);
      setUser(null);
    };
    return () => { globalThis.__onAuthExpired = null; };
  }, []);

  const login = async (phone, password, remember = false) => {
    const { token: t, user: u } = await authAPI.login({ phone, password });
    if (remember) await AsyncStorage.setItem('token', t);
    setToken(t);
    setUser(u);
  };

  const register = async (data) => {
    const { token: t, user: u } = await authAPI.register(data);
    await AsyncStorage.setItem('token', t);
    setToken(t);
    setUser(u);
  };

  const logout = async () => {
    await AsyncStorage.removeItem('token');
    setToken(null);
    setUser(null);
  };

  // Refresh user profile from server (handles profile photo changes, role updates, etc.)
  const refreshProfile = async () => {
    if (!token) return null;
    try {
      const { user: u } = await authAPI.me();
      setUser(u);
      return u;
    } catch (err) {
      console.error('Profile refresh failed:', err);
    }
    return null;
  };

  // Update user profile (name, avatar, etc.)
  const updateProfile = async (updates) => {
    if (!token) throw new Error('Not authenticated');
    try {
      const { user: u } = await authAPI.updateProfile(updates);
      setUser(u);
      return u;
    } catch (err) {
      throw err;
    }
  };

  return (
    <AuthContext.Provider value={{ user, token, loading, login, register, logout, refreshProfile, updateProfile }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
};
