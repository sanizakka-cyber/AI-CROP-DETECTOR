'use client';
import { useState, useEffect } from 'react';
import { Sun, Moon, Globe, Menu, X, LogIn, UserPlus } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

const LANGS = { en: 'English', ha: 'Hausa', ig: 'Igbo', yo: 'Yoruba', ff: 'Fulfulde', fr: 'Français' };
const NAV = ['Home', 'About Us', 'Services', 'Marketplace', 'Resources', 'Contact Us', 'Feedback', 'FAQ'];

export default function Navbar({ onLogin, onRegister }) {
  const { user, logout } = useAuth();
  const [dark, setDark] = useState(false);
  const [lang, setLang] = useState('en');
  const [open, setOpen] = useState(false);

  useEffect(() => {
    document.documentElement.classList.toggle('dark', dark);
  }, [dark]);

  return (
    <>
      {/* Top announcement bar */}
      <div className="bg-emerald-800 text-white text-xs py-2 px-4 flex justify-between items-center">
        <span>🐄 Welcome to MSAS Livestock &amp; Agro Services</span>
        <span className="hidden md:block">🌿 Empowering Farmers. Improving Livestock. Building a Better Future.</span>
        <div className="flex items-center gap-2">
          <Globe size={14} />
          <select value={lang} onChange={e => setLang(e.target.value)}
            className="bg-transparent text-white text-xs border-none outline-none cursor-pointer">
            {Object.entries(LANGS).map(([k, v]) => <option key={k} value={k} className="text-black">{v}</option>)}
          </select>
        </div>
      </div>

      {/* Main Navbar */}
      <nav className="sticky top-0 z-50 bg-white dark:bg-slate-900 shadow-md">
        <div className="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">
          {/* Logo */}
          <div className="flex items-center gap-2">
            <div className="w-10 h-10 bg-emerald-700 rounded-full flex items-center justify-center text-white text-xl">🐄</div>
            <div className="leading-tight">
              <div className="font-extrabold text-emerald-800 dark:text-emerald-400 text-sm">MSAS</div>
              <div className="text-xs text-amber-600 font-semibold">Livestock &amp; Agro Services</div>
              <div className="text-[10px] text-slate-500">Smart Agriculture · Healthy Livestock · Better Future</div>
            </div>
          </div>

          {/* Desktop Nav Links */}
          <div className="hidden lg:flex items-center gap-6 text-sm font-medium text-slate-700 dark:text-slate-300">
            {NAV.map(n => (
              <a key={n} href={`#${n.toLowerCase().replace(/\s/g,'-')}`}
                className="hover:text-emerald-700 transition whitespace-nowrap">{n}</a>
            ))}
          </div>

          {/* Actions */}
          <div className="flex items-center gap-2">
            <button onClick={() => setDark(d => !d)} className="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">
              {dark ? <Sun size={18} /> : <Moon size={18} />}
            </button>
            {user ? (
              <div className="flex items-center gap-3">
                {/* Profile Avatar */}
                <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900 border border-emerald-200 dark:border-emerald-700">
                  <div className="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm font-bold">
                    {user.name?.[0]?.toUpperCase() || '?'}
                  </div>
                  <div className="hidden md:block">
                    <div className="text-sm font-semibold text-emerald-900 dark:text-emerald-100">{user.name}</div>
                    <div className="text-xs text-emerald-700 dark:text-emerald-300">{user.roleDisplay || user.role}</div>
                  </div>
                </div>
                <button onClick={logout} className="px-3 py-1.5 text-xs border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition text-slate-700 dark:text-slate-300">
                  Logout
                </button>
              </div>
            ) : (
              <>
                <button onClick={onLogin} className="hidden md:flex items-center gap-1 px-3 py-1.5 rounded-lg border-2 border-emerald-700 text-emerald-700 font-bold text-xs hover:bg-emerald-50 transition h-10 min-w-max">
                  <LogIn size={14} /> Sign In
                </button>
                <button onClick={onRegister} className="hidden md:flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-700 text-white font-bold text-xs hover:bg-emerald-800 transition h-10 min-w-max">
                  <UserPlus size={14} /> Sign Up
                </button>
                <button className="hidden md:flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-500 text-white font-bold text-xs hover:bg-amber-600 transition h-10 min-w-max">
                  Check In
                </button>
              </>
            )}
            <button onClick={() => setOpen(o => !o)} className="lg:hidden p-2 text-slate-600">
              {open ? <X size={22} /> : <Menu size={22} />}
            </button>
          </div>
        </div>

        {/* Mobile Menu */}
        {open && (
          <div className="lg:hidden bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 px-4 pb-4 space-y-2">
            {NAV.map(n => <a key={n} href="#" className="block py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-emerald-700">{n}</a>)}
            <div className="flex gap-2 pt-2">
              <button onClick={onLogin} className="flex-1 py-2 border-2 border-emerald-700 text-emerald-700 rounded-lg text-xs font-bold">Sign In</button>
              <button onClick={onRegister} className="flex-1 py-2 bg-emerald-700 text-white rounded-lg text-xs font-bold">Sign Up</button>
            </div>
          </div>
        )}
      </nav>
    </>
  );
}
