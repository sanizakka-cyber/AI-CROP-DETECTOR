'use client';
import { MessageCircle, LayoutDashboard, Bell, ClipboardList, ShoppingCart, Stethoscope, BookOpen } from 'lucide-react';

function PhoneMockup() {
  return (
    <div className="relative mx-auto w-56 h-96 bg-slate-900 rounded-3xl border-4 border-slate-700 shadow-2xl overflow-hidden">
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-16 h-4 bg-slate-900 rounded-b-xl z-10" />
      <div className="bg-emerald-700 px-3 pt-6 pb-3">
        <div className="text-white text-xs font-bold">Dashboard</div>
        <div className="text-emerald-200 text-[10px]">Welcome back, Farmer 🌿</div>
      </div>
      <div className="bg-white p-2 space-y-2">
        <div className="grid grid-cols-2 gap-1.5">
          {[['🐄','Cattle','120'],['🐔','Poultry','350'],['🌽','Crops','5'],['⚠️','Alerts','3']].map(([ic,lb,v]) => (
            <div key={lb} className="bg-slate-50 rounded-lg p-2 text-center">
              <div className="text-sm">{ic}</div>
              <div className="text-[9px] text-slate-500">{lb}</div>
              <div className="text-xs font-bold text-emerald-700">{v}</div>
            </div>
          ))}
        </div>
        <div className="text-[10px] font-bold text-slate-700 mt-1">Quick Access</div>
        {[['🩺','Health Check'],['📞','Vet Consultation'],['🛒','Marketplace'],['📋','Record Keeping']].map(([ic,lb]) => (
          <div key={lb} className="flex items-center gap-2 bg-slate-50 rounded-lg px-2 py-1.5">
            <span className="text-xs">{ic}</span>
            <span className="text-[10px] text-slate-600">{lb}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

export default function Hero({ onRegister, onLogin }) {
  return (
    <section className="bg-gradient-to-br from-slate-50 to-emerald-50 dark:from-slate-900 dark:to-slate-800 py-16 lg:py-24 overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 grid lg:grid-cols-2 gap-12 items-center">
        {/* Left */}
        <div>
          <h1 className="text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-800 dark:text-white leading-tight mb-4">
            Welcome to<br />
            <span className="text-emerald-700">MSAS Livestock</span><br />
            <span className="text-emerald-700">&amp; Agro Services</span>
          </h1>
          <p className="text-slate-600 dark:text-slate-400 text-lg mb-8 leading-relaxed">
            A smart digital platform for livestock farmers, poultry owners, and agribusiness operators.
            Manage your animals, access expert veterinary support, buy &amp; sell inputs, and grow your farm business with confidence.
          </p>
          <div className="flex flex-wrap gap-3 mb-8">
            <button onClick={onLogin} className="flex items-center gap-2 px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl transition shadow-lg">
              👤 Sign In
            </button>
            <button onClick={onRegister} className="flex items-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl transition shadow-lg">
              🚀 Sign Up
            </button>
            <button className="flex items-center gap-2 px-6 py-3 border-2 border-emerald-700 text-emerald-700 font-bold rounded-xl hover:bg-emerald-50 transition">
              ✅ Check In
            </button>
            <button className="flex items-center gap-2 px-6 py-3 border-2 border-slate-300 text-slate-600 font-semibold rounded-xl hover:bg-slate-50 transition">
              Learn More →
            </button>
          </div>
          <div className="flex flex-wrap gap-6 text-sm font-medium text-slate-600 dark:text-slate-400">
            {['📊 Smart Management','👨‍⚕️ Expert Support','🤖 AI Powered','🔒 Secure & Reliable'].map(f => (
              <span key={f} className="flex items-center gap-1">{f}</span>
            ))}
          </div>
        </div>

        {/* Right */}
        <div className="flex flex-col lg:flex-row items-center gap-6">
          <PhoneMockup />
          <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 max-w-xs border border-slate-100 dark:border-slate-700">
            <div className="text-2xl mb-2">🌾</div>
            <h3 className="text-lg font-bold text-slate-800 dark:text-white mb-2">Your Farm, In Your Hand</h3>
            <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">Manage, monitor and grow your farm business anytime, anywhere.</p>
            <ul className="space-y-2 text-sm">
              {['Health Alerts','Record Keeping','Vet Consultation','Buy &amp; Sell'].map(i => (
                <li key={i} className="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                  <span className="w-4 h-4 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center text-xs">✓</span>
                  <span dangerouslySetInnerHTML={{ __html: i }} />
                </li>
              ))}
            </ul>
            <button onClick={onRegister} className="mt-4 w-full py-2 bg-emerald-700 text-white rounded-xl font-bold text-sm hover:bg-emerald-800 transition">
              Get Started Today
            </button>
          </div>
        </div>
      </div>

      {/* WhatsApp Floating Button */}
      <a href="https://wa.me/2348129582957" target="_blank" rel="noopener noreferrer"
        className="fixed bottom-6 right-6 z-50 flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-full shadow-2xl font-semibold text-sm transition">
        <MessageCircle size={20} /> Chat on WhatsApp
      </a>
    </section>
  );
}
