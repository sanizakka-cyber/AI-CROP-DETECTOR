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

  // identifier: email or phone — the API's 'phone' field name is kept for
  // wire compatibility, but LoginRequest on the backend accepts either.
  const login = async (identifier, password, remember = false) => {
    const { token: t, user: u } = await authAPI.login({ phone: identifier, password });
    if (remember) await AsyncStorage.setItem('token', t);
    setToken(t);
    setUser(u);
  };

  /**
   * Mirrors the web registration state machine: a new account isn't always
   * immediately usable. Returns one of:
   *  - { status: 'authenticated' }              — phone signup, logged in now
   *  - { status: 'needs_verification', identifier, message } — email signup, OTP sent
   *  - { status: 'needs_approval', message }     — non-farmer role, pending review
   */
  const register = async (data) => {
    const res = await authAPI.register(data);
    if (res.token) {
      await AsyncStorage.setItem('token', res.token);
      setToken(res.token);
      setUser(res.user);
      return { status: 'authenticated' };
    }
    if (res.needs_verification) {
      return { status: 'needs_verification', identifier: res.identifier, message: res.message };
    }
    return { status: 'needs_approval', message: res.message };
  };

  const verifyOtp = async (identifier, code) => {
    const { token: t, user: u } = await authAPI.verifyOtp(identifier, code);
    await AsyncStorage.setItem('token', t);
    setToken(t);
    setUser(u);
  };

  const resendOtp = (identifier) => authAPI.resendOtp(identifier);

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
    <AuthContext.Provider value={{ user, token, loading, login, register, verifyOtp, resendOtp, logout, refreshProfile, updateProfile }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
};
