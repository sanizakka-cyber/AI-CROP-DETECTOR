'use client';
import { createContext, useContext, useState, useEffect } from 'react';

const API = process.env.NEXT_PUBLIC_API_URL || 'https://msas-api.onrender.com/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser]       = useState(null);
  const [token, setToken]     = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Rehydrate AND re-validate the token against the server on every page load.
    // This prevents stale or tampered localStorage values from being trusted.
    const t = localStorage.getItem('msas_token');
    if (!t) { setLoading(false); return; }

    fetch(`${API}/auth/me`, { headers: { Authorization: `Bearer ${t}` } })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.user) {
          setToken(t);
          setUser(data.user);
          // Refresh stored user from server (catches role changes, deactivation, etc.)
          localStorage.setItem('msas_user', JSON.stringify(data.user));
        } else {
          // Token rejected by server — clear everything
          localStorage.removeItem('msas_token');
          localStorage.removeItem('msas_user');
        }
      })
      .catch(() => {
        // Network failure — use cached user as fallback, but mark session as unverified
        const u = localStorage.getItem('msas_user');
        if (t && u) { setToken(t); setUser(JSON.parse(u)); }
      })
      .finally(() => setLoading(false));
  }, []);

  const login = async (phone, password) => {
    const res = await fetch(`${API}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone, password }),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Login failed');
    localStorage.setItem('msas_token', data.token);
    localStorage.setItem('msas_user', JSON.stringify(data.user));
    setToken(data.token); setUser(data.user);
    return data.user;
  };

  const register = async (payload) => {
    const res = await fetch(`${API}/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Registration failed');
    localStorage.setItem('msas_token', data.token);
    localStorage.setItem('msas_user', JSON.stringify(data.user));
    setToken(data.token); setUser(data.user);
    return data.user;
  };

  const logout = () => {
    localStorage.removeItem('msas_token');
    localStorage.removeItem('msas_user');
    setToken(null); setUser(null);
  };

  // Refresh user profile from server (handles profile photo changes, role updates, etc.)
  const refreshProfile = async () => {
    if (!token) return null;
    try {
      const res = await fetch(`${API}/auth/me`, { headers: { Authorization: `Bearer ${token}` } });
      const data = await res.json();
      if (data.success && data.user) {
        setUser(data.user);
        localStorage.setItem('msas_user', JSON.stringify(data.user));
        return data.user;
      }
    } catch (err) {
      console.error('Profile refresh failed:', err);
    }
    return null;
  };

  // Update user profile (name, avatar, etc.)
  const updateProfile = async (updates) => {
    if (!token) throw new Error('Not authenticated');
    const res = await fetch(`${API}/auth/profile`, {
      method: 'PATCH',
      headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(updates),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Update failed');
    setUser(data.user);
    localStorage.setItem('msas_user', JSON.stringify(data.user));
    return data.user;
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
