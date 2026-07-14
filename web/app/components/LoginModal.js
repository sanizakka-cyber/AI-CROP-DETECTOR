'use client';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { X, Eye, EyeOff, Loader2, CheckCircle, AlertCircle } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

export default function LoginModal({ onClose, onSwitchToRegister }) {
  const { login } = useAuth();
  const router = useRouter();
  const [form, setForm] = useState({ phone: '', password: '' });
  const [showPw, setShowPw] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const set = (k) => (e) => setForm(f => ({ ...f, [k]: e.target.value }));

  const handleLogin = async (e) => {
    e.preventDefault();
    setError('');
    if (!form.phone || !form.password) { setError('Please fill in all fields.'); return; }
    setLoading(true);
    try {
      await login(form.phone, form.password);
      setSuccess(true);
      setTimeout(() => { onClose(); router.push('/dashboard'); }, 1000);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={onClose} />
      <div className="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl p-8 z-10">
        <button onClick={onClose} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
          <X size={22} />
        </button>

        <div className="text-center mb-8">
          <div className="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <span className="text-3xl">🌿</span>
          </div>
          <h2 className="text-2xl font-bold text-slate-800 dark:text-white">Welcome Back</h2>
          <p className="text-slate-500 dark:text-slate-400 mt-1">Sign in to your MSAS account</p>
        </div>

        {error && (
          <div className="flex items-center gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl mb-6 text-red-600 dark:text-red-400 text-sm">
            <AlertCircle size={16} /> {error}
          </div>
        )}
        {success && (
          <div className="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 rounded-xl mb-6 text-green-600 text-sm">
            <CheckCircle size={16} /> Login successful! Redirecting...
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-5">
          <div>
            <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Phone Number</label>
            <input
              id="login-phone"
              type="tel"
              value={form.phone}
              onChange={set('phone')}
              placeholder="e.g. 08012345678"
              className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 transition"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Password</label>
            <div className="relative">
              <input
                id="login-password"
                type={showPw ? 'text' : 'password'}
                value={form.password}
                onChange={set('password')}
                placeholder="••••••••"
                className="w-full px-4 py-3 pr-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 transition"
              />
              <button type="button" onClick={() => setShowPw(s => !s)} className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                {showPw ? <EyeOff size={18} /> : <Eye size={18} />}
              </button>
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            id="login-submit"
            className="w-full py-3.5 bg-green-700 hover:bg-green-800 disabled:opacity-60 text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
          >
            {loading ? <><Loader2 size={18} className="animate-spin" /> Signing In...</> : 'Sign In'}
          </button>
        </form>

        <div className="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
          Don&apos;t have an account?{' '}
          <button onClick={onSwitchToRegister} className="text-green-700 dark:text-green-400 font-semibold hover:underline">
            Register Free
          </button>
        </div>

        <div className="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 text-xs text-amber-700 dark:text-amber-400">
          <strong>Demo accounts:</strong><br/>
          Farmer: <code>08012345678</code> / <code>farmer123</code><br/>
          CEO: <code>admin</code> / <code>admin</code><br/>
          Vet: <code>vet</code> / <code>vet</code>
        </div>
      </div>
    </div>
  );
}
