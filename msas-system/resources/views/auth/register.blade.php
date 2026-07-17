@php
use App\Data\NigeriaLocations;
$nigeriaStates = NigeriaLocations::states();
$countries     = NigeriaLocations::countries();
$oldRole       = old('role', '');
$oldCountry    = old('country', 'Nigeria');
$oldState      = old('state', '');
$oldLga        = old('lga', '');
$oldWard       = old('ward', '');
@endphp

<x-guest-layout>

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css">
<style>
/* ── Progress bar ── */
.reg-progress{display:flex;align-items:center;gap:0;margin-bottom:22px;}
.reg-step{display:flex;flex-direction:column;align-items:center;gap:4px;flex:1;position:relative;}
.reg-step-circle{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;transition:all .25s;z-index:1;}
.reg-step-circle.done{background:#0F6B3E;color:#fff;}
.reg-step-circle.active{background:#0F6B3E;color:#fff;box-shadow:0 0 0 4px rgba(15,107,62,.15);}
.reg-step-circle.inactive{background:#f1f5f9;color:#94a3b8;border:2px solid #e2e8f0;}
.reg-step-label{font-size:10px;font-weight:600;white-space:nowrap;}
.reg-step-label.done,.reg-step-label.active{color:#0F6B3E;}
.reg-step-label.inactive{color:#94a3b8;}
.reg-step-line{flex:1;height:2px;background:#e2e8f0;margin-top:-17px;position:relative;z-index:0;}
.reg-step-line.done{background:#0F6B3E;}

/* ── Role cards ── */
.role-card{display:flex;align-items:flex-start;gap:9px;padding:10px;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;cursor:pointer;text-align:left;transition:all .18s;width:100%;}
.role-card:hover{border-color:#0F6B3E;background:#f0fdf4;}
.role-card.selected{border-color:#0F6B3E;background:#f0fdf4;box-shadow:0 0 0 3px rgba(15,107,62,.12);}
.role-icon{font-size:20px;line-height:1;flex-shrink:0;margin-top:1px;}
.role-title{display:block;font-size:12px;font-weight:700;color:#1e293b;line-height:1.3;}
.role-desc{display:block;font-size:10px;color:#94a3b8;margin-top:2px;line-height:1.4;}

/* ── Tom Select overrides ── */
.ts-wrapper{margin:0;}
.ts-control{border:1.5px solid #e2e8f0!important;border-radius:10px!important;padding:10px 14px!important;font-size:14px!important;font-family:'Inter',sans-serif!important;background:#f8fafc!important;min-height:44px!important;box-shadow:none!important;}
.ts-control:focus-within{border-color:#0F6B3E!important;background:#fff!important;box-shadow:0 0 0 3px rgba(15,107,62,.1)!important;}
.ts-dropdown{border:1.5px solid #e2e8f0!important;border-radius:10px!important;box-shadow:0 8px 30px rgba(0,0,0,.1)!important;font-size:13px!important;}
.ts-dropdown .option{padding:9px 14px!important;}
.ts-dropdown .option:hover,.ts-dropdown .option.active{background:#f0fdf4!important;color:#0F6B3E!important;}
.ts-wrapper.disabled .ts-control{background:#f8fafc!important;opacity:.55!important;cursor:not-allowed!important;}
.ts-control input{font-family:'Inter',sans-serif!important;}

/* ── Step nav buttons ── */
.btn-back{display:inline-flex;align-items:center;gap:5px;padding:11px 18px;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;transition:all .18s;}
.btn-back:hover{border-color:#cbd5e1;background:#f1f5f9;}
.btn-next{display:inline-flex;align-items:center;gap:5px;padding:11px 22px;border-radius:10px;background:linear-gradient(135deg,#0F6B3E,#1FA84A);color:#fff;font-size:13px;font-weight:700;cursor:pointer;border:none;transition:all .2s;flex:1;justify-content:center;}
.btn-next:hover{filter:brightness(1.08);transform:translateY(-1px);}

/* ── Form label ── */
.fl{display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;}
.fe{color:#dc2626;font-size:11px;margin-top:4px;}
</style>
@endpush

@php
$startStep = $oldRole ? 3 : 1;
// If there are validation errors, we jump straight to step 3 (form) with role from old()
@endphp

<div x-data="regWizard()" x-init="init()">

    {{-- ── Progress bar ── --}}
    <div class="reg-progress" x-show="step > 0">
        <div class="reg-step">
            <div class="reg-step-circle" :class="step > 1 ? 'done' : step === 1 ? 'active' : 'inactive'">
                <span x-show="step > 1">✓</span><span x-show="step <= 1">1</span>
            </div>
            <div class="reg-step-label" :class="step >= 1 ? 'active' : 'inactive'">Role</div>
        </div>
        <div class="reg-step-line" :class="step > 1 ? 'done' : ''"></div>
        <div class="reg-step">
            <div class="reg-step-circle" :class="step > 2 ? 'done' : step === 2 ? 'active' : 'inactive'">
                <span x-show="step > 2">✓</span><span x-show="step <= 2">2</span>
            </div>
            <div class="reg-step-label" :class="step >= 2 ? 'active' : 'inactive'">Details</div>
        </div>
        <div class="reg-step-line" :class="step > 2 ? 'done' : ''"></div>
        <div class="reg-step">
            <div class="reg-step-circle" :class="step === 3 ? 'active' : 'inactive'">3</div>
            <div class="reg-step-label" :class="step === 3 ? 'active' : 'inactive'">Security</div>
        </div>
    </div>

    {{-- ════════════════════════════════════════ --}}
    {{-- STEP 1 — Role selection                 --}}
    {{-- ════════════════════════════════════════ --}}
    <div x-show="step === 1" x-cloak>
        <div style="margin-bottom:16px;">
            <h2 style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:#0f172a;margin:0 0 3px 0;">I am joining as…</h2>
            <p style="font-size:12px;color:#64748b;margin:0;">Choose your role to get the right dashboard and tools.</p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-bottom:16px;">
            @foreach([
                ['farmer',              '🌾','Farmer','Crops, livestock & farm management'],
                ['vet',                 '💉','Veterinarian','Animal health & diagnostics'],
                ['agronomist',          '🌿','Agronomist','Crop advisory & soil science'],
                ['agro-dealer',         '🏪','Agro Dealer','Inputs, seeds & agro products'],
                ['equipment-dealer',    '🚜','Equipment Dealer','Farm machinery & equipment'],
                ['agribusiness-owner',  '💼','Agribusiness Owner','Agri enterprises & processing'],
                ['cooperative',         '🏢','Cooperative','Farmer groups & cooperatives'],
                ['government-agency',   '🏛','Gov\'t Agency','Policy, extension & regulation'],
                ['ngo',                 '🌍','NGO','Development & humanitarian aid'],
                ['research-institution','🎓','Research Inst.','Agriculture research & academia'],
                ['input-supplier',      '📦','Input Supplier','Fertilisers, chemicals & seeds'],
                ['logistics-provider',  '🚛','Logistics','Transport & cold chain'],
                ['investor',            '📈','Investor','Agri finance & investments'],
                ['general-user',        '👤','General User','Learning & staying informed'],
            ] as [$rval, $icon, $rtitle, $rdesc])
            <button type="button"
                class="role-card"
                :class="role === '{{ $rval }}' ? 'selected' : ''"
                @click="selectRole('{{ $rval }}', '{{ $rtitle }}')">
                <span class="role-icon">{{ $icon }}</span>
                <span>
                    <span class="role-title">{{ $rtitle }}</span>
                    <span class="role-desc">{{ $rdesc }}</span>
                </span>
            </button>
            @endforeach
        </div>

        <button type="button" class="btn-next" style="width:100%"
            @click="nextFromStep1()"
            :disabled="!role"
            :style="!role ? 'opacity:.45;cursor:not-allowed;transform:none;filter:none' : ''">
            Continue <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        <div style="text-align:center;padding-top:14px;margin-top:12px;border-top:1px solid #f1f5f9;">
            <span style="font-size:13px;color:#94a3b8;">Already have an account?</span>
            <a href="{{ route('login') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;margin-left:5px;">Sign In</a>
        </div>
    </div>

    {{-- ════════════════════════════════════════ --}}
    {{-- STEP 2 — Personal info + Location       --}}
    {{-- ════════════════════════════════════════ --}}
    <div x-show="step === 2" x-cloak>

        {{-- Role badge --}}
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <button type="button" @click="step=1"
                style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:#64748b;background:#f1f5f9;border:none;border-radius:7px;padding:5px 10px;cursor:pointer;">
                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg> Change
            </button>
            <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#0F6B3E;" x-text="roleName"></span>
        </div>

        <h2 style="font-family:'Poppins',sans-serif;font-size:17px;font-weight:800;color:#0f172a;margin:0 0 14px 0;">Your Details</h2>

        {{-- Name row --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label class="fl">First Name *</label>
                <div style="position:relative;">
                    <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
                    <input id="s2_first_name" class="form-input" style="padding-left:32px;" type="text" placeholder="" value="{{ old('first_name') }}" readonly onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
                </div>
            </div>
            <div>
                <label class="fl">Last Name *</label>
                <div style="position:relative;">
                    <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
                    <input id="s2_last_name" class="form-input" style="padding-left:32px;" type="text" placeholder="" value="{{ old('last_name') }}" readonly onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
                </div>
            </div>
        </div>

        <div style="margin-bottom:10px;">
            <label class="fl">Middle Name <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8;">(optional)</span></label>
            <input id="s2_middle_name" class="form-input" type="text" value="{{ old('middle_name') }}" readonly onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
        </div>

        {{-- Single identifier field (email OR phone) --}}
        <div style="margin-bottom:10px;">
            <label class="fl">Email Address or Phone Number *</label>
            <div style="position:relative;">
                <div id="s2-id-icon" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;transition:opacity .2s;">
                    <svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <input id="s2_identifier" class="form-input" style="padding-left:32px;padding-right:130px;"
                    type="text" inputmode="email"
                    placeholder="Enter your email address or phone number"
                    value="{{ old('identifier') }}"
                    readonly
                    onfocus="this.removeAttribute('readonly')"
                    onclick="this.removeAttribute('readonly')"
                    oninput="detectIdentifier(this.value)">
                {{-- Auto-detected type badge --}}
                <div id="s2-id-badge" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);display:none;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;"></div>
            </div>
            <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">e.g. name@email.com or 08012345678</p>
        </div>

        {{-- Location --}}
        <div style="padding:12px;background:#f0fdf4;border-radius:10px;border:1px solid #dcfce7;margin-bottom:14px;">
            <div style="font-size:11px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;display:flex;align-items:center;gap:5px;">
                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Your Location
            </div>

            <div style="margin-bottom:8px;">
                <label class="fl" style="color:#374151;">Country *</label>
                <select id="ts-country" name="_country_ts" style="display:none;"></select>
            </div>

            <div style="margin-bottom:8px;" id="state-group">
                <label class="fl" style="color:#374151;">State / Province *</label>
                <select id="ts-state" name="_state_ts" style="display:none;"></select>
                <p id="state-hint" style="font-size:10px;color:#94a3b8;margin:3px 0 0 0;display:none;">Select a country first</p>
            </div>

            <div style="margin-bottom:8px;" id="lga-group">
                <label class="fl" style="color:#374151;">Local Government Area</label>
                <select id="ts-lga" name="_lga_ts" style="display:none;"></select>
                <p id="lga-hint" style="font-size:10px;color:#94a3b8;margin:3px 0 0 0;display:none;">Select a state first</p>
            </div>

            <div>
                <label class="fl" style="color:#374151;">Ward / Village <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8;">(optional)</span></label>
                <input id="s2_ward" class="form-input" type="text" placeholder="Enter ward or village name" value="{{ $oldWard }}" style="background:#fff;">
            </div>
        </div>

        <div id="s2-error" style="display:none;padding:9px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12px;color:#dc2626;font-weight:600;margin-bottom:12px;"></div>

        <div style="display:flex;gap:8px;">
            <button type="button" class="btn-back" @click="step=1">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg> Back
            </button>
            <button type="button" class="btn-next" @click="nextFromStep2()">
                Continue <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    {{-- ════════════════════════════════════════ --}}
    {{-- STEP 3 — Password + Submit (real form)  --}}
    {{-- ════════════════════════════════════════ --}}
    <div x-show="step === 3" x-cloak>

        @if ($errors->any())
        <div style="display:flex;align-items:flex-start;gap:9px;padding:10px 12px;border-radius:9px;background:#fef2f2;border:1px solid #fecaca;margin-bottom:14px;">
            <svg width="15" height="15" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span style="color:#dc2626;font-size:12px;font-weight:600;">{{ $errors->first() }}</span>
        </div>
        @endif

        {{-- Role badge + back --}}
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <button type="button" @click="step=2"
                style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:#64748b;background:#f1f5f9;border:none;border-radius:7px;padding:5px 10px;cursor:pointer;">
                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg> Back
            </button>
            <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#0F6B3E;" x-text="roleName"></span>
        </div>

        <h2 style="font-family:'Poppins',sans-serif;font-size:17px;font-weight:800;color:#0f172a;margin:0 0 14px 0;">Create Password</h2>

        <form method="POST" action="{{ route('register') }}" autocomplete="off" id="reg-form">
            @csrf

            {{-- Hidden fields populated by JS from steps 1 & 2 --}}
            <input type="hidden" name="role"        id="h_role"        :value="role">
            <input type="hidden" name="first_name"  id="h_first_name"  :value="step2.first_name">
            <input type="hidden" name="middle_name" id="h_middle_name" :value="step2.middle_name">
            <input type="hidden" name="last_name"   id="h_last_name"   :value="step2.last_name">
            <input type="hidden" name="identifier"  id="h_identifier"  :value="step2.identifier">
            <input type="hidden" name="country"     id="h_country"     :value="step2.country">
            <input type="hidden" name="state"       id="h_state"       :value="step2.state">
            <input type="hidden" name="lga"         id="h_lga"         :value="step2.lga">
            <input type="hidden" name="ward"        id="h_ward"        :value="step2.ward">

            {{-- Summary card --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;margin-bottom:14px;font-size:12px;">
                <div style="font-weight:700;color:#374151;margin-bottom:6px;">Registration Summary</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;color:#64748b;">
                    <span>Name:</span><span style="color:#1e293b;font-weight:600;" x-text="step2.first_name + ' ' + step2.last_name"></span>
                    <span>Contact:</span><span style="color:#1e293b;font-weight:600;word-break:break-all;" x-text="step2.identifier"></span>
                    <span>Location:</span><span style="color:#1e293b;font-weight:600;" x-text="(step2.lga ? step2.lga + ', ' : '') + step2.state + (step2.country !== 'Nigeria' ? ', ' + step2.country : '')"></span>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                <div>
                    <label class="fl">Password *</label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
                        <input id="password" class="form-input {{ $errors->has('password') ? 'error' : '' }}" style="padding-left:32px;"
                            type="password" name="password" required autocomplete="new-password" readonly
                            onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
                    </div>
                    @error('password')<div class="fe">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="fl">Confirm *</label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                        <input id="password_confirmation" class="form-input" style="padding-left:32px;"
                            type="password" name="password_confirmation" required autocomplete="new-password" readonly
                            onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:flex-start;gap:9px;margin-bottom:16px;padding:10px 12px;background:#f0fdf4;border-radius:9px;border:1px solid #bbf7d0;">
                <input type="checkbox" id="terms" required style="width:16px;height:16px;border-radius:4px;accent-color:#0F6B3E;cursor:pointer;margin-top:1px;flex-shrink:0;">
                <label for="terms" style="font-size:12px;color:#475569;cursor:pointer;line-height:1.5;">
                    I agree to the <a href="#" style="color:#0F6B3E;font-weight:700;text-decoration:none;">Terms of Service</a> and <a href="#" style="color:#0F6B3E;font-weight:700;text-decoration:none;">Privacy Policy</a>
                </label>
            </div>

            <div style="margin-bottom:14px;">
                <button type="submit" class="btn-primary">
                    <span style="display:flex;align-items:center;justify-content:center;gap:8px;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Create My Account
                    </span>
                </button>
            </div>
        </form>

        <div style="text-align:center;padding-top:12px;border-top:1px solid #f1f5f9;">
            <span style="font-size:13px;color:#94a3b8;">Already have an account?</span>
            <a href="{{ route('login') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;margin-left:5px;">Sign In</a>
        </div>
    </div>

</div>{{-- end x-data --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
// ── Identifier auto-detection ─────────────────────────────────────────────
function isValidEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
function isValidPhone(v) { return /^(\+?234|0)[789]\d{9}$/.test(v.replace(/[\s\-\(\)]/g,'')); }

function detectIdentifier(val) {
    const badge = document.getElementById('s2-id-badge');
    const icon  = document.getElementById('s2-id-icon');
    if (!val) { badge.style.display='none'; return; }
    if (isValidEmail(val)) {
        badge.style.display='block';
        badge.textContent='📧 Email';
        badge.style.background='#dbeafe'; badge.style.color='#1d4ed8';
        icon.innerHTML='<svg width="13" height="13" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
    } else if (isValidPhone(val)) {
        badge.style.display='block';
        badge.textContent='📱 Phone';
        badge.style.background='#dcfce7'; badge.style.color='#15803d';
        icon.innerHTML='<svg width="13" height="13" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>';
    } else {
        badge.style.display='none';
        icon.innerHTML='<svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
    }
}

const NG_STATES = @json($nigeriaStates);
const COUNTRIES = @json($countries);
const OLD = {
    country: @json($oldCountry),
    state:   @json($oldState),
    lga:     @json($oldLga),
};

let tsCountry, tsState, tsLga;
let locInitialised = false;

function initLocationDrops() {
    if (locInitialised) return;
    locInitialised = true;

    // Country
    tsCountry = new TomSelect('#ts-country', {
        options:     COUNTRIES.map(c => ({value: c, text: c})),
        placeholder: 'Select country…',
        maxItems:    1,
        onChange:    onCountryChange,
    });

    // State (starts locked)
    tsState = new TomSelect('#ts-state', {
        options:     [],
        placeholder: 'Select state / province…',
        maxItems:    1,
        onChange:    onStateChange,
    });
    tsState.disable();

    // LGA (starts locked)
    tsLga = new TomSelect('#ts-lga', {
        options:     [],
        placeholder: 'Select LGA…',
        maxItems:    1,
    });
    tsLga.disable();

    // Restore old() values after validation error
    if (OLD.country) {
        tsCountry.setValue(OLD.country, true);
        onCountryChange(OLD.country, true);
        if (OLD.state) {
            tsState.setValue(OLD.state, true);
            onStateChange(OLD.state, true);
            if (OLD.lga) tsLga.setValue(OLD.lga, true);
        }
    }
}

function onCountryChange(val, silent) {
    tsState.clear(true);
    tsState.clearOptions();
    tsLga.clear(true);
    tsLga.clearOptions();
    tsLga.disable();
    document.getElementById('state-hint').style.display = 'none';
    document.getElementById('lga-hint').style.display = 'none';

    if (!val) { tsState.disable(); document.getElementById('state-hint').style.display = 'block'; return; }

    if (val === 'Nigeria') {
        tsState.addOptions(NG_STATES.map(s => ({value: s.name, text: s.name})));
    }
    // For non-Nigeria: enable as free-text entry (Tom Select creates option on type)
    tsState.enable();
    if (!silent) tsState.focus();
}

function onStateChange(val, silent) {
    tsLga.clear(true);
    tsLga.clearOptions();
    tsLga.disable();
    document.getElementById('lga-hint').style.display = 'none';

    if (!val) { document.getElementById('lga-hint').style.display = 'block'; return; }

    // Only Nigeria has structured LGA data
    if (tsCountry.getValue() === 'Nigeria') {
        const found = NG_STATES.find(s => s.name === val);
        if (found && found.lgas.length) {
            tsLga.addOptions(found.lgas.map(l => ({value: l, text: l})));
            tsLga.enable();
            if (!silent) tsLga.focus();
        }
    }
}

function regWizard() {
    return {
        step:     {{ $startStep }},
        role:     '{{ $oldRole }}',
        roleName: '',
        step2: {
            first_name:  '{{ old('first_name', '') }}',
            middle_name: '{{ old('middle_name', '') }}',
            last_name:   '{{ old('last_name', '') }}',
            identifier:  '{{ old('identifier', '') }}',
            country:     '{{ $oldCountry }}',
            state:       '{{ $oldState }}',
            lga:         '{{ $oldLga }}',
            ward:        '{{ $oldWard }}',
        },

        roleMap: {
            'farmer':'Farmer','vet':'Veterinarian','agronomist':'Agronomist',
            'agro-dealer':'Agro Dealer','equipment-dealer':'Equipment Dealer',
            'agribusiness-owner':'Agribusiness Owner','cooperative':'Cooperative',
            'government-agency':'Government Agency','ngo':'NGO',
            'research-institution':'Research Institution','input-supplier':'Input Supplier',
            'logistics-provider':'Logistics Provider','investor':'Investor',
            'general-user':'General User',
        },

        init() {
            if (this.role && this.roleMap[this.role]) {
                this.roleName = this.roleMap[this.role];
            }
            if (this.step >= 2) {
                this.$nextTick(() => initLocationDrops());
            }
        },

        selectRole(val, label) {
            this.role     = val;
            this.roleName = label;
        },

        nextFromStep1() {
            if (!this.role) return;
            this.step = 2;
            this.$nextTick(() => initLocationDrops());
        },

        nextFromStep2() {
            const err = document.getElementById('s2-error');
            err.style.display = 'none';
            const msgs = [];

            const fn  = document.getElementById('s2_first_name').value.trim();
            const ln  = document.getElementById('s2_last_name').value.trim();
            const id  = document.getElementById('s2_identifier').value.trim();
            const co  = tsCountry ? tsCountry.getValue() : '';
            const st  = tsState   ? tsState.getValue()   : '';

            if (!fn)  msgs.push('First name is required.');
            if (!ln)  msgs.push('Last name is required.');
            if (!id)  msgs.push('Email address or phone number is required.');
            else if (!isValidEmail(id) && !isValidPhone(id))
                      msgs.push('Enter a valid email address or Nigerian phone number.');
            if (!co)  msgs.push('Please select your country.');
            if (!st)  msgs.push('Please select your state / province.');

            if (msgs.length) {
                err.textContent = msgs[0];
                err.style.display = 'block';
                err.scrollIntoView({behavior:'smooth', block:'nearest'});
                return;
            }

            this.step2 = {
                first_name:  fn,
                middle_name: document.getElementById('s2_middle_name').value.trim(),
                last_name:   ln,
                identifier:  id,
                country:     co,
                state:       st,
                lga:         tsLga ? tsLga.getValue() : '',
                ward:        document.getElementById('s2_ward').value.trim(),
            };
            this.step = 3;
        },
    };
}
</script>
@endpush

</x-guest-layout>
