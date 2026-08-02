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
$startStep       = $oldRole ? 3 : 1;
$preselectedPlan = $preselectedPlan ?? old('plan', '');
$planName        = $preselectedPlan && isset($plans[$preselectedPlan]) ? $plans[$preselectedPlan]['name'] : '';
@endphp

@if($planName)
<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0;border-radius:10px;margin-bottom:14px;">
    <svg width="16" height="16" fill="none" stroke="#0F6B3E" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span style="font-size:13px;color:#15803d;font-weight:600;">You selected <strong>{{ $planName }}</strong> — complete registration to start your free 14-day trial.</span>
</div>
@endif

<div x-data="regWizard()" x-init="init()">

    {{-- ── Progress bar ── --}}
    <div class="reg-progress" x-show="step > 0">
        <div class="reg-step">
            <div class="reg-step-circle" :class="step > 1 ? 'done' : step === 1 ? 'active' : 'inactive'">
                <span x-show="step > 1"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span><span x-show="step <= 1">1</span>
            </div>
            <div class="reg-step-label" :class="step >= 1 ? 'active' : 'inactive'">{{ __('Role') }}</div>
        </div>
        <div class="reg-step-line" :class="step > 1 ? 'done' : ''"></div>
        <div class="reg-step">
            <div class="reg-step-circle" :class="step > 2 ? 'done' : step === 2 ? 'active' : 'inactive'">
                <span x-show="step > 2"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span><span x-show="step <= 2">2</span>
            </div>
            <div class="reg-step-label" :class="step >= 2 ? 'active' : 'inactive'">{{ __('Details') }}</div>
        </div>
        <div class="reg-step-line" :class="step > 2 ? 'done' : ''"></div>
        <div class="reg-step">
            <div class="reg-step-circle" :class="step === 3 ? 'active' : 'inactive'">3</div>
            <div class="reg-step-label" :class="step === 3 ? 'active' : 'inactive'">{{ __('Security') }}</div>
        </div>
    </div>

    {{-- ════════════════════════════════════════ --}}
    {{-- STEP 1 — Role selection                 --}}
    {{-- ════════════════════════════════════════ --}}
    <div x-show="step === 1" x-cloak>
        <div style="margin-bottom:16px;">
            <h2 style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:#0f172a;margin:0 0 3px 0;">{{ __('I am joining as…') }}</h2>
            <p style="font-size:12px;color:#64748b;margin:0;">{{ __('Choose your role to get the right dashboard and tools.') }}</p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-bottom:16px;">
            @foreach([
                ['farmer',              '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12m0 0C12 7 7 4 2 5c0 5 4 8 10 7zm0 0c0-5 5-8 10-7-1 5-5 8-10 7"/></svg>','Farmer','Crops, livestock & farm management'],
                ['vet',                 '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>','Veterinarian','Animal health & diagnostics'],
                ['agronomist',          '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>','Agronomist','Crop advisory & soil science'],
                ['agro-dealer',         '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>','Agro Dealer','Inputs, seeds & agro products'],
                ['equipment-dealer',    '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>','Equipment Dealer','Farm machinery & equipment'],
                ['agribusiness-owner',  '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>','Agribusiness Owner','Agri enterprises & processing'],
                ['cooperative',         '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>','Cooperative','Farmer groups & cooperatives'],
                ['government-agency',   '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>','Gov\'t Agency','Policy, extension & regulation'],
                ['ngo',                 '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>','NGO','Development & humanitarian aid'],
                ['research-institution','<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6M6 11v5a6 6 0 006 6 6 6 0 006-6v-5"/></svg>','Research Inst.','Agriculture research & academia'],
                ['input-supplier',      '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/></svg>','Input Supplier','Fertilisers, chemicals & seeds'],
                ['logistics-provider',  '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>','Logistics','Transport & cold chain'],
                ['investor',            '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>','Investor','Agri finance & investments'],
                ['general-user',        '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>','General User','Learning & staying informed'],
            ] as [$rval, $icon, $rtitle, $rdesc])
            <button type="button"
                class="role-card"
                :class="role === '{{ $rval }}' ? 'selected' : ''"
                @click="selectRole('{{ $rval }}', '{{ $rtitle }}')">
                <span class="role-icon">{!! $icon !!}</span>
                <span>
                    <span class="role-title">{{ __($rtitle) }}</span>
                    <span class="role-desc">{{ __($rdesc) }}</span>
                </span>
            </button>
            @endforeach
        </div>

        <button type="button" class="btn-next" style="width:100%"
            @click="nextFromStep1()"
            :disabled="!role"
            :style="!role ? 'opacity:.45;cursor:not-allowed;transform:none;filter:none' : ''">
            {{ __('Continue') }} <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        <div style="text-align:center;padding-top:14px;margin-top:12px;border-top:1px solid #f1f5f9;">
            <span style="font-size:13px;color:#94a3b8;">{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;margin-left:5px;">{{ __('Sign In') }}</a>
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
                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg> {{ __('Change') }}
            </button>
            <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#0F6B3E;" x-text="roleName"></span>
        </div>

        <h2 style="font-family:'Poppins',sans-serif;font-size:17px;font-weight:800;color:#0f172a;margin:0 0 14px 0;">{{ __('Your Details') }}</h2>

        {{-- Name row --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label class="fl">{{ __('First Name') }} *</label>
                <div style="position:relative;">
                    <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
                    <input id="s2_first_name" class="form-input" style="padding-left:32px;" type="text" placeholder="" value="{{ old('first_name') }}" readonly onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
                </div>
            </div>
            <div>
                <label class="fl">{{ __('Last Name') }} *</label>
                <div style="position:relative;">
                    <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
                    <input id="s2_last_name" class="form-input" style="padding-left:32px;" type="text" placeholder="" value="{{ old('last_name') }}" readonly onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
                </div>
            </div>
        </div>

        <div style="margin-bottom:10px;">
            <label class="fl">{{ __('Middle Name') }} <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8;">{{ __('(optional)') }}</span></label>
            <input id="s2_middle_name" class="form-input" type="text" value="{{ old('middle_name') }}" readonly onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
        </div>

        {{-- Single identifier field (email OR phone) --}}
        <div style="margin-bottom:10px;">
            <label class="fl">{{ __('Email Address or Phone Number *') }}</label>
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
                {{ __('Your Location') }}
            </div>

            <div style="margin-bottom:8px;">
                <label class="fl" style="color:#374151;">{{ __('Country *') }}</label>
                <select id="ts-country" name="_country_ts" style="display:none;"></select>
            </div>

            <div style="margin-bottom:8px;" id="state-group">
                <label class="fl" style="color:#374151;">{{ __('State / Province *') }}</label>
                <select id="ts-state" name="_state_ts" style="display:none;"></select>
                <p id="state-hint" style="font-size:10px;color:#94a3b8;margin:3px 0 0 0;display:none;">Select a country first</p>
            </div>

            <div style="margin-bottom:8px;" id="lga-group">
                <label class="fl" style="color:#374151;">{{ __('Local Government Area') }}</label>
                <select id="ts-lga" name="_lga_ts" style="display:none;"></select>
                <p id="lga-hint" style="font-size:10px;color:#94a3b8;margin:3px 0 0 0;display:none;">Select a state first</p>
            </div>

            <div>
                <label class="fl" style="color:#374151;">{{ __('Ward / Village') }} <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8;">{{ __('(optional)') }}</span></label>
                <input id="s2_ward" class="form-input" type="text" placeholder="{{ __('Enter ward or village name') }}" value="{{ $oldWard }}" style="background:#fff;">
            </div>
        </div>

        <div id="s2-error" style="display:none;padding:9px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12px;color:#dc2626;font-weight:600;margin-bottom:12px;"></div>

        <div style="display:flex;gap:8px;">
            <button type="button" class="btn-back" @click="step=1">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg> {{ __('Back') }}
            </button>
            <button type="button" class="btn-next" @click="nextFromStep2()">
                {{ __('Continue') }} <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
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
                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg> {{ __('Back') }}
            </button>
            <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#0F6B3E;" x-text="roleName"></span>
        </div>

        <h2 style="font-family:'Poppins',sans-serif;font-size:17px;font-weight:800;color:#0f172a;margin:0 0 14px 0;">{{ __('Create Password') }}</h2>

        <form method="POST" action="{{ route('register') }}" autocomplete="off" id="reg-form" enctype="multipart/form-data">
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
            <input type="hidden" name="plan"        value="{{ $preselectedPlan }}">

            {{-- Summary card --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;margin-bottom:14px;font-size:12px;">
                <div style="font-weight:700;color:#374151;margin-bottom:6px;">{{ __('Registration Summary') }}</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;color:#64748b;">
                    <span>{{ __('Name:') }}</span><span style="color:#1e293b;font-weight:600;" x-text="step2.first_name + ' ' + step2.last_name"></span>
                    <span>{{ __('Contact:') }}</span><span style="color:#1e293b;font-weight:600;word-break:break-all;" x-text="step2.identifier"></span>
                    <span>{{ __('Location:') }}</span><span style="color:#1e293b;font-weight:600;" x-text="(step2.lga ? step2.lga + ', ' : '') + step2.state + (step2.country !== 'Nigeria' ? ', ' + step2.country : '')"></span>
                </div>
            </div>

            {{-- ── Documents (professional roles only) ── --}}
            <div x-show="!['farmer','general-user'].includes(role)" x-cloak style="margin-bottom:14px;">
                <div style="padding:12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;margin-bottom:10px;">
                    <p style="font-size:12px;font-weight:700;color:#1e40af;margin:0 0 2px;">{{ __('Supporting Documents Required') }}</p>
                    <p style="font-size:11px;color:#3b82f6;margin:0;line-height:1.5;">
                        {{ __('Professional roles require verification documents before account activation. Upload PDFs or images (max 5MB each).') }}
                    </p>
                </div>

                {{-- Vet --}}
                <div x-show="role === 'vet'">
                    <div style="margin-bottom:8px;"><label class="fl">Veterinary License</label><input type="file" name="documents[vet_license]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                    <div style="margin-bottom:8px;"><label class="fl">Professional Accreditation</label><input type="file" name="documents[accreditation]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Agronomist --}}
                <div x-show="role === 'agronomist'">
                    <div style="margin-bottom:8px;"><label class="fl">Professional License</label><input type="file" name="documents[professional_license]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                    <div style="margin-bottom:8px;"><label class="fl">Proof of Qualification</label><input type="file" name="documents[proof_of_qualification]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Agro Dealer --}}
                <div x-show="role === 'agro-dealer'">
                    <div style="margin-bottom:8px;"><label class="fl">CAC / Business Registration</label><input type="file" name="documents[cac_registration]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Equipment Dealer --}}
                <div x-show="role === 'equipment-dealer'">
                    <div style="margin-bottom:8px;"><label class="fl">Business Registration Certificate</label><input type="file" name="documents[business_registration]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Agribusiness Owner --}}
                <div x-show="role === 'agribusiness-owner'">
                    <div style="margin-bottom:8px;"><label class="fl">Company Registration Certificate</label><input type="file" name="documents[company_registration]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Cooperative --}}
                <div x-show="role === 'cooperative'">
                    <div style="margin-bottom:8px;"><label class="fl">Cooperative Certificate</label><input type="file" name="documents[cooperative_certificate]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                    <div style="margin-bottom:8px;"><label class="fl">Members List (min. 5 members)</label><input type="file" name="documents[members_list]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Government Agency --}}
                <div x-show="role === 'government-agency'">
                    <div style="margin-bottom:8px;"><label class="fl">Official Government Documentation</label><input type="file" name="documents[official_documents]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- NGO --}}
                <div x-show="role === 'ngo'">
                    <div style="margin-bottom:8px;"><label class="fl">Registration Certificate</label><input type="file" name="documents[registration_cert]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                    <div style="margin-bottom:8px;"><label class="fl">Tax Exemption Certificate</label><input type="file" name="documents[tax_exemption]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Research Institution --}}
                <div x-show="role === 'research-institution'">
                    <div style="margin-bottom:8px;"><label class="fl">Institutional Affiliation Letter</label><input type="file" name="documents[institutional_affiliation]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                    <div style="margin-bottom:8px;"><label class="fl">Research Proposal</label><input type="file" name="documents[research_proposal]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Input Supplier --}}
                <div x-show="role === 'input-supplier'">
                    <div style="margin-bottom:8px;"><label class="fl">CAC / Business Registration</label><input type="file" name="documents[cac_registration]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Logistics --}}
                <div x-show="role === 'logistics-provider'">
                    <div style="margin-bottom:8px;"><label class="fl">Transport / Haulage License</label><input type="file" name="documents[transport_license]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>
                {{-- Investor --}}
                <div x-show="role === 'investor'">
                    <div style="margin-bottom:8px;"><label class="fl">Valid ID</label><input type="file" name="documents[id_document]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                    <div style="margin-bottom:8px;"><label class="fl">Investment Profile / Portfolio</label><input type="file" name="documents[investment_profile]" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;"></div>
                </div>

                <p style="font-size:10px;color:#94a3b8;margin-top:4px;">You can still submit without documents — they can be added later, but approval may be delayed.</p>
            </div>
            {{-- ── END Documents ── --}}

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                <div>
                    <label class="fl">{{ __('Password *') }}</label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
                        <input id="password" class="form-input {{ $errors->has('password') ? 'error' : '' }}" style="padding-left:32px;"
                            type="password" name="password" required autocomplete="new-password" readonly
                            placeholder="{{ __('Minimum 8 characters') }}"
                            onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
                    </div>
                    @error('password')<div class="fe">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="fl">{{ __('Confirm Password *') }}</label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="13" height="13" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                        <input id="password_confirmation" class="form-input" style="padding-left:32px;"
                            type="password" name="password_confirmation" required autocomplete="new-password" readonly
                            placeholder="{{ __('Re-enter your password') }}"
                            onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')">
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:flex-start;gap:9px;margin-bottom:16px;padding:10px 12px;background:#f0fdf4;border-radius:9px;border:1px solid #bbf7d0;">
                <input type="checkbox" id="terms" required style="width:16px;height:16px;border-radius:4px;accent-color:#0F6B3E;cursor:pointer;margin-top:1px;flex-shrink:0;">
                <label for="terms" style="font-size:12px;color:#475569;cursor:pointer;line-height:1.5;">
                    {{ __('I agree to the') }} <a href="{{ route('legal.terms') }}" target="_blank" style="color:#0F6B3E;font-weight:700;text-decoration:none;">{{ __('Terms of Service') }}</a> and <a href="{{ route('legal.privacy') }}" target="_blank" style="color:#0F6B3E;font-weight:700;text-decoration:none;">{{ __('Privacy Policy') }}</a>
                </label>
            </div>

            <div style="margin-bottom:14px;">
                <button type="submit" class="btn-primary">
                    <span style="display:flex;align-items:center;justify-content:center;gap:8px;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        <span x-text="['farmer','general-user'].includes(role) ? createMyAccount : submitApplication">{{ __('Create My Account') }}</span>
                    </span>
                </button>
            </div>
        </form>

        <div style="text-align:center;padding-top:12px;border-top:1px solid #f1f5f9;">
            <span style="font-size:13px;color:#94a3b8;">{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;margin-left:5px;">{{ __('Sign In') }}</a>
        </div>
    </div>

</div>{{-- end x-data --}}

@push('scripts')
@php
$roleMap = [
    'farmer'               => __('Farmer'),
    'vet'                  => __('Veterinarian'),
    'agronomist'           => __('Agronomist'),
    'agro-dealer'          => __('Agro Dealer'),
    'equipment-dealer'     => __('Equipment Dealer'),
    'agribusiness-owner'   => __('Agribusiness Owner'),
    'cooperative'          => __('Cooperative'),
    'government-agency'    => __("Gov't Agency"),
    'ngo'                  => __('NGO'),
    'research-institution' => __('Research Inst.'),
    'input-supplier'       => __('Input Supplier'),
    'logistics-provider'   => __('Logistics'),
    'investor'             => __('Investor'),
    'general-user'         => __('General User'),
];
@endphp
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
        badge.textContent='Email';
        badge.style.background='#dbeafe'; badge.style.color='#1d4ed8';
        icon.innerHTML='<svg width="13" height="13" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
    } else if (isValidPhone(val)) {
        badge.style.display='block';
        badge.textContent='Phone';
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
        step:            {{ $startStep }},
        role:            '{{ $oldRole }}',
        roleName:        '',
        createMyAccount: @json(__('Create My Account')),
        submitApplication: @json(__('Submit Application')),
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

        roleMap: @json($roleMap),

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
