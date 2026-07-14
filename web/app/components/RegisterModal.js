'use client';
import { useState } from 'react';
import { X, Eye, EyeOff, Loader2, CheckCircle, AlertCircle, ChevronRight, ChevronLeft } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

const ROLES = [
  { id: 'farmer',     icon: '🌾', label: 'Farmer',            desc: 'Manage your farm, crops & livestock' },
  { id: 'vet',        icon: '🩺', label: 'Veterinary Doctor', desc: 'Provide animal health consultations' },
  { id: 'agronomist', icon: '🌱', label: 'Agronomist',        desc: 'Advise on crop health & soil science' },
  { id: 'agro-dealer',icon: '🏪', label: 'Agro-Dealer',       desc: 'List products on the marketplace' },
];

const CROPS   = ['Maize', 'Sorghum', 'Millet', 'Tomato', 'Groundnut', 'Cotton', 'Rice', 'Wheat'];
const VET_SPECS = ['Cattle', 'Poultry', 'Small Ruminants (Goats/Sheep)', 'Equine', 'Mixed Practice'];
const AGR_SPECS = ['Crop Protection', 'Soil Science', 'Horticulture', 'Animal Nutrition', 'Agronomy'];

function StepIndicator({ step, totalSteps }) {
  return (
    <div className="flex items-center justify-center gap-2 mb-8">
      {Array.from({ length: totalSteps }, (_, i) => i + 1).map(s => (
        <div key={s} className="flex items-center gap-2">
          <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all
            ${step === s ? 'bg-green-700 text-white scale-110' : step > s ? 'bg-green-100 text-green-700' : 'bg-slate-100 dark:bg-slate-700 text-slate-400'}`}>
            {step > s ? <CheckCircle size={16} /> : s}
          </div>
          {s < totalSteps && <div className={`h-0.5 w-8 ${step > s ? 'bg-green-500' : 'bg-slate-200 dark:bg-slate-700'}`} />}
        </div>
      ))}
    </div>
  );
}

export default function RegisterModal({ onClose, onSwitchToLogin }) {
  const { register } = useAuth();
  const [step, setStep] = useState(1);
  const [role, setRole] = useState('');
  const [form, setForm] = useState({
    name: '', phone: '', email: '', password: '', confirmPassword: '',
    language: 'en', state: 'Katsina', lga: '', village: '',
    farmSize: '', cropsGrown: [], cattle: 0, goats: 0, sheep: 0, poultry: 0,
    licenseNumber: '', specialization: '', yearsExperience: '', consultationFee: '',
    organization: '', businessName: '',
    acceptTerms: false, acceptPrivacy: false,
  });
  const [showPw, setShowPw] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const set = (k) => (e) => {
    const val = e.target?.type === 'checkbox' ? e.target.checked : e.target?.value ?? e;
    setForm(f => ({ ...f, [k]: val }));
  };

  const toggleCrop = (c) => setForm(f => ({
    ...f, cropsGrown: f.cropsGrown.includes(c) ? f.cropsGrown.filter(x => x !== c) : [...f.cropsGrown, c]
  }));

  const totalSteps = 4;

  const validate = () => {
    if (step === 1 && !role) { setError('Please select your account type.'); return false; }
    if (step === 2) {
      if (!form.name || !form.phone || !form.password) { setError('Please fill all required fields.'); return false; }
      if (form.password !== form.confirmPassword) { setError('Passwords do not match.'); return false; }
      if (form.password.length < 8) { setError('Password must be at least 8 characters.'); return false; }
    }
    if (step === 3 && !form.acceptTerms) { setError('Please accept the Terms of Service to continue.'); return false; }
    setError(''); return true;
  };

  const next = () => { if (validate()) setStep(s => Math.min(s + 1, totalSteps)); };
  const prev = () => { setError(''); setStep(s => Math.max(s - 1, 1)); };

  const handleSubmit = async () => {
    if (!form.acceptTerms) { setError('Please accept the terms.'); return; }
    setLoading(true);
    try {
      const payload = {
        name: form.name, phone: form.phone, email: form.email,
        password: form.password, language: form.language, role,
        state: form.state, lga: form.lga, village: form.village,
        specialization: form.specialization, yearsExperience: form.yearsExperience,
        licenseNumber: form.licenseNumber, consultationFee: form.consultationFee,
        farmSize: form.farmSize, cropsGrown: form.cropsGrown,
        livestockCounts: { cattle: form.cattle, goats: form.goats, sheep: form.sheep, poultry: form.poultry },
      };
      await register(payload);
      setSuccess(true);
      setTimeout(() => onClose(), 2000);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const stepLabel = ['Account Type', 'Basic Info', 'Your Profile', 'Confirm & Sign Up'];

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={onClose} />
      <div className="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl z-10 max-h-[90vh] overflow-y-auto">
        <div className="sticky top-0 bg-white dark:bg-slate-900 rounded-t-3xl px-8 pt-8 pb-4 z-10 border-b border-slate-100 dark:border-slate-800">
          <button onClick={onClose} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
            <X size={22} />
          </button>
          <div className="text-center">
            <h2 className="text-2xl font-bold text-slate-800 dark:text-white">Create Account</h2>
            <p className="text-slate-500 dark:text-slate-400 text-sm mt-1">{stepLabel[step - 1]}</p>
          </div>
          <StepIndicator step={step} totalSteps={totalSteps} />
        </div>

        <div className="px-8 pb-8 pt-4">
          {error && (
            <div className="flex items-center gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl mb-6 text-red-600 dark:text-red-400 text-sm">
              <AlertCircle size={16} /> {error}
            </div>
          )}
          {success && (
            <div className="flex flex-col items-center gap-3 py-8 text-center">
              <CheckCircle size={56} className="text-green-500" />
              <h3 className="text-xl font-bold text-slate-800 dark:text-white">Account Created!</h3>
              <p className="text-slate-500">{role === 'vet' || role === 'agronomist' ? 'Your account is pending admin approval (1-3 days). We will notify you.' : 'You now have full access to MSAS. Welcome aboard!'}</p>
            </div>
          )}

          {!success && (
            <>
              {/* ── Step 1: Role Selection ── */}
              {step === 1 && (
                <div className="grid grid-cols-2 gap-4">
                  {ROLES.map(r => (
                    <button key={r.id} onClick={() => setRole(r.id)}
                      className={`p-5 rounded-2xl border-2 text-left transition-all hover:shadow-lg
                        ${role === r.id ? 'border-green-600 bg-green-50 dark:bg-green-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-green-300'}`}>
                      <div className="text-3xl mb-2">{r.icon}</div>
                      <div className="font-bold text-slate-800 dark:text-white text-sm">{r.label}</div>
                      <div className="text-xs text-slate-500 dark:text-slate-400 mt-1">{r.desc}</div>
                    </button>
                  ))}
                </div>
              )}

              {/* ── Step 2: Basic Info ── */}
              {step === 2 && (
                <div className="space-y-5">
                  {[
                    { key: 'name',     label: 'Full Name *',         type: 'text',     placeholder: 'e.g. Aminu Yusuf' },
                    { key: 'phone',    label: 'Phone Number *',      type: 'tel',      placeholder: '08012345678' },
                    { key: 'email',    label: 'Email (Optional)',     type: 'email',    placeholder: 'your@email.com' },
                  ].map(f => (
                    <div key={f.key}>
                      <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">{f.label}</label>
                      <input type={f.type} value={form[f.key]} onChange={set(f.key)} placeholder={f.placeholder}
                        className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                    </div>
                  ))}
                  <div>
                    <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Password * (min. 8 characters)</label>
                    <div className="relative">
                      <input type={showPw ? 'text' : 'password'} value={form.password} onChange={set('password')} placeholder="••••••••"
                        className="w-full px-4 py-3 pr-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                      <button type="button" onClick={() => setShowPw(s => !s)} className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                        {showPw ? <EyeOff size={18} /> : <Eye size={18} />}
                      </button>
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Confirm Password *</label>
                    <input type="password" value={form.confirmPassword} onChange={set('confirmPassword')} placeholder="••••••••"
                      className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Preferred Language</label>
                    <select value={form.language} onChange={set('language')}
                      className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                      <option value="en">English</option>
                      <option value="ha">Hausa</option>
                      <option value="ig">Igbo</option>
                      <option value="yo">Yoruba</option>
                      <option value="ff">Fulfulde</option>
                      <option value="fr">Français</option>
                    </select>
                  </div>
                </div>
              )}

              {/* ── Step 3: Role-Specific Profile ── */}
              {step === 3 && (
                <div className="space-y-5">
                  <div className="grid grid-cols-3 gap-3">
                    {[
                      { key: 'state',   label: 'State',   placeholder: 'Katsina' },
                      { key: 'lga',     label: 'LGA',     placeholder: 'Katsina Central' },
                      { key: 'village', label: 'Village', placeholder: 'Your village' },
                    ].map(f => (
                      <div key={f.key}>
                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">{f.label}</label>
                        <input type="text" value={form[f.key]} onChange={set(f.key)} placeholder={f.placeholder}
                          className="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                      </div>
                    ))}
                  </div>

                  {role === 'farmer' && (
                    <>
                      <div>
                        <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Farm Size (hectares)</label>
                        <input type="number" value={form.farmSize} onChange={set('farmSize')} placeholder="e.g. 4.5"
                          className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                      </div>
                      <div>
                        <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Crops Grown</label>
                        <div className="flex flex-wrap gap-2">
                          {CROPS.map(c => (
                            <button key={c} type="button" onClick={() => toggleCrop(c)}
                              className={`px-3 py-1.5 rounded-full text-sm font-medium border transition ${form.cropsGrown.includes(c) ? 'bg-green-700 text-white border-green-700' : 'border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:border-green-400'}`}>
                              {c}
                            </button>
                          ))}
                        </div>
                      </div>
                      <div>
                        <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Livestock Count</label>
                        <div className="grid grid-cols-2 gap-3">
                          {['cattle','goats','sheep','poultry'].map(a => (
                            <div key={a} className="flex items-center gap-2">
                              <label className="text-sm capitalize text-slate-600 dark:text-slate-400 w-16">{a}</label>
                              <input type="number" value={form[a]} onChange={set(a)} min={0}
                                className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                            </div>
                          ))}
                        </div>
                      </div>
                    </>
                  )}

                  {(role === 'vet' || role === 'agronomist') && (
                    <>
                      <div>
                        <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">License / Certification Number</label>
                        <input type="text" value={form.licenseNumber} onChange={set('licenseNumber')} placeholder="e.g. VCMN/2021/1234"
                          className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                      </div>
                      <div>
                        <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Specialization</label>
                        <select value={form.specialization} onChange={set('specialization')}
                          className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                          <option value="">Select specialization...</option>
                          {(role === 'vet' ? VET_SPECS : AGR_SPECS).map(s => <option key={s} value={s}>{s}</option>)}
                        </select>
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Years of Experience</label>
                          <input type="number" value={form.yearsExperience} onChange={set('yearsExperience')} min={0} placeholder="e.g. 5"
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                        </div>
                        <div>
                          <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Consultation Fee (₦)</label>
                          <input type="number" value={form.consultationFee} onChange={set('consultationFee')} min={0} placeholder="e.g. 2000"
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                        </div>
                      </div>
                      <div>
                        <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Organization / Clinic Affiliation</label>
                        <input type="text" value={form.organization} onChange={set('organization')} placeholder="e.g. Katsina State ADP"
                          className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                      </div>
                      <div className="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-700 dark:text-amber-400">
                        ⏳ Expert accounts require admin verification (1–3 business days) before you can accept consultations.
                      </div>
                    </>
                  )}

                  {role === 'agro-dealer' && (
                    <>
                      <div>
                        <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Business Name</label>
                        <input type="text" value={form.businessName} onChange={set('businessName')} placeholder="e.g. ABC Agro Store"
                          className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500" />
                      </div>
                    </>
                  )}

                  {/* Terms */}
                  <div className="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <label className="flex items-start gap-3 cursor-pointer">
                      <input type="checkbox" checked={form.acceptTerms} onChange={set('acceptTerms')}
                        className="mt-1 accent-green-700 w-4 h-4" />
                      <span className="text-sm text-slate-600 dark:text-slate-400">
                        I agree to the <span className="text-green-700 dark:text-green-400 font-medium">Terms of Service</span> and consent to my farm data being used to improve AI diagnostic accuracy.
                      </span>
                    </label>
                    <label className="flex items-start gap-3 cursor-pointer">
                      <input type="checkbox" checked={form.acceptPrivacy} onChange={set('acceptPrivacy')}
                        className="mt-1 accent-green-700 w-4 h-4" />
                      <span className="text-sm text-slate-600 dark:text-slate-400">
                        I have read and accept the <span className="text-green-700 dark:text-green-400 font-medium">Privacy Policy</span>.
                      </span>
                    </label>
                  </div>
                </div>
              )}

              {/* ── Step 4: Review & Submit ── */}
              {step === 4 && (
                <div className="space-y-6">
                  <div className="p-5 bg-slate-50 dark:bg-slate-800 rounded-2xl space-y-3">
                    <h3 className="font-bold text-slate-800 dark:text-white mb-4">Review Your Details</h3>
                    {[
                      { label: 'Account Type', value: ROLES.find(r => r.id === role)?.label },
                      { label: 'Full Name', value: form.name },
                      { label: 'Phone', value: form.phone },
                      { label: 'Email', value: form.email || 'Not provided' },
                      { label: 'Language', value: { en: 'English', ha: 'Hausa', ig: 'Igbo', yo: 'Yoruba', ff: 'Fulfulde', fr: 'Français' }[form.language] },
                      { label: 'Location', value: [form.state, form.lga, form.village].filter(Boolean).join(', ') || 'Not specified' },
                      ...(role === 'farmer' ? [{ label: 'Farm Size', value: form.farmSize ? `${form.farmSize} ha` : 'Not set' }] : []),
                      ...(role === 'vet' || role === 'agronomist' ? [{ label: 'Specialization', value: form.specialization || 'Not set' }] : []),
                    ].map(item => (
                      <div key={item.label} className="flex justify-between text-sm">
                        <span className="text-slate-500 dark:text-slate-400">{item.label}</span>
                        <span className="font-semibold text-slate-800 dark:text-white">{item.value}</span>
                      </div>
                    ))}
                  </div>

                  <button
                    id="register-submit"
                    onClick={handleSubmit}
                    disabled={loading || !form.acceptTerms}
                    className="w-full py-4 bg-green-700 hover:bg-green-800 disabled:opacity-50 text-white font-bold rounded-xl transition flex items-center justify-center gap-2 text-lg"
                  >
                    {loading ? <><Loader2 size={20} className="animate-spin" /> Creating Account...</> : '🚀 Create My Account'}
                  </button>
                </div>
              )}

              {/* ── Navigation Buttons ── */}
              <div className="flex justify-between mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
                <button onClick={prev} disabled={step === 1}
                  className="flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition">
                  <ChevronLeft size={18} /> Back
                </button>

                {step < totalSteps ? (
                  <button onClick={next}
                    className="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-green-700 hover:bg-green-800 text-white font-bold transition">
                    Continue <ChevronRight size={18} />
                  </button>
                ) : null}
              </div>
            </>
          )}

          <div className="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
            Already have an account?{' '}
            <button onClick={onSwitchToLogin} className="text-green-700 dark:text-green-400 font-semibold hover:underline">
              Sign In
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
