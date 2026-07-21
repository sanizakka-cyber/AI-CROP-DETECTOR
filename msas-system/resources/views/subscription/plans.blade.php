<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Subscription Plans</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Choose the plan that fits your farm's needs</p>
        </div>
        @if($activeSub)
        <a href="{{ route('subscription.dashboard') }}"
           style="background:#0F6B3E;color:#fff;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
            My Subscription
        </a>
        @endif
    </div>
</x-slot>

@if(session('warning'))
<div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <svg width="16" height="16" fill="none" stroke="#b45309" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <span style="color:#92400e;font-size:13px;font-weight:600;">{{ session('warning') }}</span>
</div>
@endif

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <svg width="16" height="16" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span style="color:#15803d;font-size:13px;font-weight:600;">{{ session('success') }}</span>
</div>
@endif

@php
$isProfessional = !in_array($user->role, ['farmer', 'ceo', 'admin', 'general-user']);
$proPlans = ['professional_starter', 'professional_business'];
@endphp

@if(!$isProfessional)
<!-- ── Quick Subscribe Dropdown ─────────────────────────────────────── -->
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px 24px;margin-bottom:24px;" x-data="{ qplan: '{{ $activeSub ? $activeSub->plan : 'basic' }}', qcycle: 'monthly', open: false }">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <div style="font-size:15px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px;">
                <svg width="16" height="16" fill="none" stroke="#0F6B3E" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Quick Subscribe
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">Select a plan and billing cycle, then click Subscribe</div>
        </div>
        <!-- Toggle expand -->
        <button @click="open=!open" type="button"
            style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;color:#374151;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <span x-text="open ? 'Hide' : 'Open Quick Subscribe'"></span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                 :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s;">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
    </div>

    <!-- Expandable form -->
    <div x-show="open" x-cloak style="margin-top:18px;padding-top:18px;border-top:1px solid #f1f5f9;">
        <form method="POST" action="{{ route('subscription.subscribe') }}" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
            @csrf

            <!-- Plan dropdown -->
            <div style="flex:1;min-width:180px;">
                <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;display:block;margin-bottom:6px;">Select Plan</label>
                <div style="position:relative;">
                    <select name="plan" x-model="qplan"
                        style="width:100%;padding:10px 36px 10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;font-weight:600;color:#0f172a;background:#f8fafc;appearance:none;cursor:pointer;outline:none;">
                        <option value="basic">🏠 Basic Plan — ₦2,500/month</option>
                        <option value="pro">⚡ Pro Plan — ₦8,000/month</option>
                        <option value="premium">👑 Premium Plan — ₦25,000/month</option>
                    </select>
                    <div style="position:absolute;right:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <svg width="14" height="14" fill="none" stroke="#64748b" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
            </div>

            <!-- Billing cycle dropdown -->
            <div style="min-width:160px;">
                <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;display:block;margin-bottom:6px;">Billing Cycle</label>
                <div style="position:relative;">
                    <select name="billing_cycle" x-model="qcycle"
                        style="width:100%;padding:10px 36px 10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;font-weight:600;color:#0f172a;background:#f8fafc;appearance:none;cursor:pointer;outline:none;">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly (Save 17%)</option>
                    </select>
                    <div style="position:absolute;right:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <svg width="14" height="14" fill="none" stroke="#64748b" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
            </div>

            <!-- Price preview -->
            <div style="min-width:120px;padding:10px 14px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:9px;text-align:center;">
                <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">You pay</div>
                <div style="font-size:16px;font-weight:900;color:#0F6B3E;" x-text="
                    qplan === 'basic'   ? (qcycle === 'yearly' ? '₦30,000/yr'  : '₦2,500/mo')  :
                    qplan === 'pro'     ? (qcycle === 'yearly' ? '₦100,000/yr' : '₦10,000/mo') :
                                          (qcycle === 'yearly' ? '₦350,000/yr' : '₦35,000/mo')
                "></div>
            </div>

            <!-- Submit -->
            <button type="submit"
                style="padding:10px 24px;background:#0F6B3E;color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:7px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Subscribe Now
            </button>
        </form>
        <p style="font-size:11px;color:#94a3b8;margin-top:10px;">
            New users get a <strong>14-day free trial</strong> before any payment is required.
        </p>
    </div>
</div>

@else
{{-- Professional Quick Subscribe --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px 24px;margin-bottom:24px;" x-data="{ qplan: '{{ $activeSub ? $activeSub->plan : 'professional_starter' }}', qcycle: 'monthly', open: false }">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <div style="font-size:15px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px;">
                <svg width="16" height="16" fill="none" stroke="#0F6B3E" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Quick Subscribe
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">Select a plan and billing cycle, then click Subscribe</div>
        </div>
        <button @click="open=!open" type="button"
            style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;color:#374151;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <span x-text="open ? 'Hide' : 'Open Quick Subscribe'"></span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                 :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s;">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
    </div>
    <div x-show="open" x-cloak style="margin-top:18px;padding-top:18px;border-top:1px solid #f1f5f9;">
        <form method="POST" action="{{ route('subscription.subscribe') }}" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
            @csrf
            <div style="flex:1;min-width:200px;">
                <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;display:block;margin-bottom:6px;">Select Plan</label>
                <div style="position:relative;">
                    <select name="plan" x-model="qplan"
                        style="width:100%;padding:10px 36px 10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;font-weight:600;color:#0f172a;background:#f8fafc;appearance:none;cursor:pointer;outline:none;">
                        <option value="professional_starter">🚀 Professional Starter — ₦15,000/month</option>
                        <option value="professional_business">💼 Professional Business — ₦35,000/month</option>
                    </select>
                    <div style="position:absolute;right:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <svg width="14" height="14" fill="none" stroke="#64748b" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
            </div>
            <div style="min-width:160px;">
                <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;display:block;margin-bottom:6px;">Billing Cycle</label>
                <div style="position:relative;">
                    <select name="billing_cycle" x-model="qcycle"
                        style="width:100%;padding:10px 36px 10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;font-weight:600;color:#0f172a;background:#f8fafc;appearance:none;cursor:pointer;outline:none;">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly (Save 17%)</option>
                    </select>
                    <div style="position:absolute;right:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <svg width="14" height="14" fill="none" stroke="#64748b" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
            </div>
            <div style="min-width:130px;padding:10px 14px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:9px;text-align:center;">
                <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">You pay</div>
                <div style="font-size:15px;font-weight:900;color:#0F6B3E;" x-text="
                    qplan === 'professional_starter' ? (qcycle === 'yearly' ? '₦150,000/yr' : '₦15,000/mo') :
                                                       (qcycle === 'yearly' ? '₦350,000/yr' : '₦35,000/mo')
                "></div>
            </div>
            <button type="submit"
                style="padding:10px 24px;background:#0F6B3E;color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:7px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Subscribe Now
            </button>
        </form>
        <p style="font-size:11px;color:#94a3b8;margin-top:10px;">New accounts get a <strong>14-day free trial</strong> before any payment is required.</p>
    </div>
</div>
@endif

<!-- Current Plan Banner -->
@if($activeSub)
@php $cfg = config('subscription.plans.'.$activeSub->plan); @endphp
<div style="background:linear-gradient(135deg,#0B2447,#0F6B3E);border-radius:14px;padding:20px 24px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
        </div>
        <div>
            <div style="color:rgba(255,255,255,0.65);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Current Plan</div>
            <div style="color:#fff;font-size:20px;font-weight:800;">{{ $cfg['name'] }}</div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
        <div style="text-align:center;">
            <div style="color:rgba(255,255,255,0.65);font-size:11px;font-weight:600;text-transform:uppercase;">Status</div>
            <div style="color:#fff;font-size:14px;font-weight:700;margin-top:2px;">
                @if($activeSub->isTrial())
                    <span style="background:#2D9CDB;padding:3px 10px;border-radius:20px;font-size:12px;">Free Trial</span>
                @else
                    <span style="background:#1FA84A;padding:3px 10px;border-radius:20px;font-size:12px;">Active</span>
                @endif
            </div>
        </div>
        <div style="text-align:center;">
            <div style="color:rgba(255,255,255,0.65);font-size:11px;font-weight:600;text-transform:uppercase;">Expires</div>
            <div style="color:#F4A300;font-size:14px;font-weight:700;margin-top:2px;">
                {{ $activeSub->endsAt()->format('M d, Y') }}
                <span style="color:rgba(255,255,255,0.55);font-size:11px;">({{ $activeSub->daysRemaining() }} days)</span>
            </div>
        </div>
    </div>
</div>
@endif

@if(!$isProfessional)
<style>
    .plan-card { transition: transform 0.2s, box-shadow 0.2s; }
    .plan-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.12); }
</style>

<!-- Single Alpine scope wraps toggle + cards so yearly state is shared -->
<div x-data="{ yearly: false }">

<!-- Billing Toggle -->
<div style="display:flex;justify-content:center;margin-bottom:32px;">
    <div style="background:#f1f5f9;border-radius:12px;padding:4px;display:flex;align-items:center;gap:4px;">
        <button @click="yearly=false" :style="!yearly ? 'background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.12);color:#0F6B3E;font-weight:700;' : 'color:#64748b;background:transparent;'"
            style="padding:8px 20px;border-radius:9px;border:none;cursor:pointer;font-size:14px;transition:all 0.2s;">
            Monthly
        </button>
        <button @click="yearly=true" :style="yearly ? 'background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.12);color:#0F6B3E;font-weight:700;' : 'color:#64748b;background:transparent;'"
            style="padding:8px 20px;border-radius:9px;border:none;cursor:pointer;font-size:14px;transition:all 0.2s;">
            Yearly
            <span style="background:#F4A300;color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:20px;margin-left:4px;">Save 17%</span>
        </button>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;">

@php
$planKeys = ['basic', 'pro', 'premium'];
$planIcons = [
    'basic'   => '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    'pro'     => '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
    'premium' => '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l3.057-3 3.943 4 3.943-4L19 3l-1 8H6L5 3zM6 11v10h12V11"/></svg>',
];
@endphp

@foreach($planKeys as $key)
@php
    $p = $plans[$key];
    $isCurrent = $activeSub && $activeSub->plan === $key;
    $isPro = $key === 'pro';
@endphp

<div class="plan-card" style="background:#fff;border-radius:16px;border:2px solid {{ $isCurrent ? $p['badge_color'] : ($isPro ? '#2D9CDB' : '#e2e8f0') }};overflow:hidden;position:relative;{{ $isPro ? 'box-shadow:0 8px 32px rgba(45,156,219,0.18);' : '' }}">

    @if($isPro)
    <div style="position:absolute;top:0;left:0;right:0;background:linear-gradient(90deg,#2D9CDB,#0F6B3E);height:3px;"></div>
    <div style="position:absolute;top:14px;right:14px;background:#2D9CDB;color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:0.05em;">MOST POPULAR</div>
    @endif

    @if($isCurrent)
    <div style="position:absolute;top:14px;right:14px;background:{{ $p['badge_color'] }};color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;">CURRENT PLAN</div>
    @endif

    <!-- Card Header -->
    <div style="padding:24px 24px 20px;background:{{ $key === 'premium' ? 'linear-gradient(135deg,#0B2447,#1a3a6e)' : ($isPro ? 'linear-gradient(135deg,#f0f9ff,#e0f2fe)' : '#f8fafc') }};">
        <div style="width:44px;height:44px;border-radius:11px;background:{{ $p['badge_color'] }};display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:{{ $key === 'premium' ? '#0B2447' : '#fff' }};">
            {!! $planIcons[$key] !!}
        </div>
        <div style="font-size:18px;font-weight:800;color:{{ $key === 'premium' ? '#fff' : '#0f172a' }};margin-bottom:4px;">{{ $p['name'] }}</div>
        <div style="font-size:12px;color:{{ $key === 'premium' ? 'rgba(255,255,255,0.65)' : '#64748b' }};line-height:1.5;margin-bottom:16px;">{{ $p['description'] }}</div>

        <!-- Price -->
        <div x-show="!yearly">
            <span style="font-size:32px;font-weight:900;color:{{ $key === 'premium' ? '#F4A300' : $p['badge_color'] }};">₦{{ number_format($p['price']['monthly']) }}</span>
            <span style="font-size:13px;color:{{ $key === 'premium' ? 'rgba(255,255,255,0.55)' : '#94a3b8' }};font-weight:500;">/month</span>
        </div>
        <div x-show="yearly" style="display:none;">
            <span style="font-size:32px;font-weight:900;color:{{ $key === 'premium' ? '#F4A300' : $p['badge_color'] }};">₦{{ number_format($p['price']['yearly']) }}</span>
            <span style="font-size:13px;color:{{ $key === 'premium' ? 'rgba(255,255,255,0.55)' : '#94a3b8' }};font-weight:500;">/year</span>
            <div style="font-size:11px;color:#1FA84A;font-weight:700;margin-top:2px;">
                Save ₦{{ number_format(($p['price']['monthly'] * 12) - $p['price']['yearly']) }} per year
            </div>
        </div>
    </div>

    <!-- Features List -->
    <div style="padding:20px 24px;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">What's included</div>
        @foreach($p['highlights'] as $hl)
        <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:9px;">
            <div style="width:18px;height:18px;border-radius:50%;background:{{ $p['badge_color'] }}18;flex-shrink:0;display:flex;align-items:center;justify-content:center;margin-top:1px;">
                <svg width="10" height="10" fill="none" stroke="{{ $p['badge_color'] }}" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span style="font-size:13px;color:#374151;font-weight:500;line-height:1.4;">{{ $hl }}</span>
        </div>
        @endforeach
    </div>

    <!-- Limits Summary -->
    <div style="margin:0 24px 20px;background:{{ $key === 'premium' ? '#f0fdf4' : '#f8fafc' }};border-radius:10px;padding:12px 14px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Livestock Records</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $p['limits']['livestock_records'] === -1 ? 'Unlimited' : number_format($p['limits']['livestock_records']) }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Monthly Reports</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $p['limits']['reports_per_month'] === -1 ? 'Unlimited' : $p['limits']['reports_per_month'] }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Farm Staff</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $p['limits']['farm_staff'] === -1 ? 'Unlimited' : $p['limits']['farm_staff'] }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">AI Scans/Month</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $p['limits']['ai_scans_per_month'] === -1 ? 'Unlimited' : $p['limits']['ai_scans_per_month'] }}</div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div style="padding:0 24px 24px;">
        @if($isCurrent)
            <div style="text-align:center;padding:12px;background:#f0fdf4;border-radius:10px;border:1.5px solid #bbf7d0;">
                <span style="color:#15803d;font-weight:700;font-size:13px;">✓ Your Current Plan</span>
            </div>
        @elseif($activeSub && $activeSub->planLevel() > $p['plan_level'])
            <form method="POST" action="{{ route('subscription.subscribe') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">
                <input type="hidden" name="billing_cycle" :value="yearly ? 'yearly' : 'monthly'">
                <button type="submit" style="width:100%;padding:12px;border-radius:10px;border:2px solid {{ $p['badge_color'] }};background:transparent;color:{{ $p['badge_color'] }};font-size:14px;font-weight:700;cursor:pointer;">
                    Downgrade to {{ $p['name'] }}
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('subscription.subscribe') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">
                <input type="hidden" name="billing_cycle" :value="yearly ? 'yearly' : 'monthly'">
                <button type="submit"
                    style="width:100%;padding:13px;border-radius:10px;border:none;background:{{ $p['badge_color'] }};color:{{ $key === 'basic' ? '#fff' : '#fff' }};font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 4px 14px {{ $p['badge_color'] }}44;">
                    @if(!$activeSub && !$user->latestSubscription())
                        Start 14-Day Free Trial
                    @elseif($activeSub)
                        Upgrade to {{ $p['name'] }}
                    @else
                        Subscribe Now
                    @endif
                </button>
            </form>
        @endif
    </div>
</div>
@endforeach
</div>
</div>{{-- end x-data="{ yearly: false }" outer wrapper --}}

<!-- Feature Comparison Table -->
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;margin-top:40px;overflow:hidden;">
    <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;background:linear-gradient(135deg,#0B2447,#0F6B3E);">
        <h3 style="font-size:17px;font-weight:800;color:#fff;margin:0;">Full Feature Comparison</h3>
        <p style="font-size:12px;color:rgba(255,255,255,0.6);margin:4px 0 0;">See exactly what's included in each plan</p>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:14px 20px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;width:40%;">Feature</th>
                    <th style="text-align:center;padding:14px 16px;font-size:13px;font-weight:800;color:#1FA84A;width:20%;">Basic</th>
                    <th style="text-align:center;padding:14px 16px;font-size:13px;font-weight:800;color:#2D9CDB;width:20%;background:#f0f9ff;">Pro</th>
                    <th style="text-align:center;padding:14px 16px;font-size:13px;font-weight:800;color:#F4A300;width:20%;background:#fffbeb;">Premium</th>
                </tr>
            </thead>
            <tbody>
@php
$comparisonRows = [
    ['category' => 'Core Features', 'rows' => [
        ['label' => 'Livestock Registration & Management', 'basic' => true,  'pro' => true,  'premium' => true],
        ['label' => 'Basic Animal Health Records',         'basic' => true,  'pro' => true,  'premium' => true],
        ['label' => 'Feeding Schedule Tracking',           'basic' => true,  'pro' => true,  'premium' => true],
        ['label' => 'Vaccination Reminders',               'basic' => true,  'pro' => true,  'premium' => true],
        ['label' => 'Farm Activity Logging',               'basic' => true,  'pro' => true,  'premium' => true],
        ['label' => 'Mobile Application Access',           'basic' => true,  'pro' => true,  'premium' => true],
        ['label' => 'FAQ & Chatbot Support',               'basic' => true,  'pro' => true,  'premium' => true],
        ['label' => 'Monthly Farm Summary Reports',        'basic' => true,  'pro' => true,  'premium' => true],
    ]],
    ['category' => 'Advanced Management (Pro+)', 'rows' => [
        ['label' => 'Unlimited Livestock Records',         'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Advanced Health & Treatment Records', 'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Breeding & Reproduction Management',  'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Production Tracking (milk, meat, eggs)', 'basic' => false, 'pro' => true, 'premium' => true],
        ['label' => 'Veterinary Service Requests',         'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Inventory Management',                'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Financial Record Tracking',           'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Geo-tagged Farm Profiling',           'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'PDF & Excel Report Downloads',        'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Direct Messaging (Vets & Extension)', 'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Farm Profitability Analysis',         'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Early Disease Alerts',                'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Benchmarking Against Similar Farms',  'basic' => false, 'pro' => true,  'premium' => true],
    ]],
    ['category' => 'Enterprise Features (Premium Only)', 'rows' => [
        ['label' => 'AI-Powered Management Recommendations', 'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Predictive Disease Monitoring',        'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Advanced Farm Intelligence Dashboard', 'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Market Price Intelligence',            'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Supply Chain Management',              'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Multi-Farm Management',                'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Multi-User Farm Staff Access',         'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Digital Livestock Traceability',       'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Custom KPI Dashboards',                'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Advanced Forecasting & Planning',      'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Dedicated Account Manager',            'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'API Integration Capabilities',         'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => '24/7 Priority Support',                'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Quarterly Business Performance Reviews','basic'=> false, 'pro' => false, 'premium' => true],
    ]],
    ['category' => 'Support', 'rows' => [
        ['label' => 'FAQ & Chatbot',              'basic' => true,  'pro' => true,  'premium' => true],
        ['label' => 'Priority Customer Support',  'basic' => false, 'pro' => true,  'premium' => true],
        ['label' => 'Priority Vet Consultation',  'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => '24/7 Dedicated Support',     'basic' => false, 'pro' => false, 'premium' => true],
        ['label' => 'Personalized Training',      'basic' => false, 'pro' => false, 'premium' => true],
    ]],
];
$rowIdx = 0;
@endphp

@foreach($comparisonRows as $section)
<tr>
    <td colspan="4" style="padding:10px 20px 6px;background:#f1f5f9;font-size:11px;font-weight:800;color:#374151;text-transform:uppercase;letter-spacing:0.08em;">
        {{ $section['category'] }}
    </td>
</tr>
@foreach($section['rows'] as $row)
@php $rowIdx++; @endphp
<tr style="border-bottom:1px solid #f1f5f9;{{ $rowIdx % 2 === 0 ? 'background:#fafafa;' : '' }}">
    <td style="padding:11px 20px;font-size:13px;color:#374151;font-weight:500;">{{ $row['label'] }}</td>
    <td style="text-align:center;padding:11px 16px;">
        @if($row['basic'])
            <svg width="16" height="16" fill="none" stroke="#1FA84A" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        @else
            <svg width="16" height="16" fill="none" stroke="#cbd5e1" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        @endif
    </td>
    <td style="text-align:center;padding:11px 16px;background:{{ $row['pro'] ? '#f0f9ff' : '#f8fafc' }};">
        @if($row['pro'])
            <svg width="16" height="16" fill="none" stroke="#2D9CDB" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        @else
            <svg width="16" height="16" fill="none" stroke="#cbd5e1" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        @endif
    </td>
    <td style="text-align:center;padding:11px 16px;background:{{ $row['premium'] ? '#fffbeb' : '#f8fafc' }};">
        @if($row['premium'])
            <svg width="16" height="16" fill="none" stroke="#F4A300" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        @else
            <svg width="16" height="16" fill="none" stroke="#cbd5e1" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        @endif
    </td>
</tr>
@endforeach
@endforeach
            </tbody>
        </table>
    </div>
</div>

@else
{{-- ── Professional Plans Section ─────────────────────────────────── --}}
<style>
    .plan-card { transition: transform 0.2s, box-shadow 0.2s; }
    .plan-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.12); }
</style>
<div x-data="{ yearly: false }">
<div style="display:flex;justify-content:center;margin-bottom:32px;">
    <div style="background:#f1f5f9;border-radius:12px;padding:4px;display:flex;align-items:center;gap:4px;">
        <button @click="yearly=false" :style="!yearly ? 'background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.12);color:#0F6B3E;font-weight:700;' : 'color:#64748b;background:transparent;'"
            style="padding:8px 20px;border-radius:9px;border:none;cursor:pointer;font-size:14px;transition:all 0.2s;">Monthly</button>
        <button @click="yearly=true" :style="yearly ? 'background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.12);color:#0F6B3E;font-weight:700;' : 'color:#64748b;background:transparent;'"
            style="padding:8px 20px;border-radius:9px;border:none;cursor:pointer;font-size:14px;transition:all 0.2s;">
            Yearly
            <span style="background:#F4A300;color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:20px;margin-left:4px;">Save 17%</span>
        </button>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;max-width:800px;margin:0 auto;">
@foreach($proPlans as $key)
@php
    $p = $plans[$key] ?? null;
    $isCurrent = $activeSub && $activeSub->plan === $key;
    $isBusiness = $key === 'professional_business';
@endphp
@if($p)
<div class="plan-card" style="background:#fff;border-radius:16px;border:2px solid {{ $isCurrent ? $p['badge_color'] : ($isBusiness ? '#2D9CDB' : '#e2e8f0') }};overflow:hidden;position:relative;{{ $isBusiness ? 'box-shadow:0 8px 32px rgba(45,156,219,0.18);' : '' }}">
    @if($isBusiness)
    <div style="position:absolute;top:0;left:0;right:0;background:linear-gradient(90deg,#2D9CDB,#0F6B3E);height:3px;"></div>
    @if(!$isCurrent)<div style="position:absolute;top:14px;right:14px;background:#2D9CDB;color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:0.05em;">MOST POPULAR</div>@endif
    @endif
    @if($isCurrent)
    <div style="position:absolute;top:14px;right:14px;background:{{ $p['badge_color'] }};color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;">CURRENT PLAN</div>
    @endif

    <div style="padding:24px 24px 20px;background:{{ $isBusiness ? 'linear-gradient(135deg,#f0f9ff,#e0f2fe)' : '#f8fafc' }};">
        <div style="width:44px;height:44px;border-radius:11px;background:{{ $p['badge_color'] }};display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#fff;">
            @if($isBusiness)
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            @else
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            @endif
        </div>
        <div style="font-size:18px;font-weight:800;color:#0f172a;margin-bottom:4px;">{{ $p['name'] }}</div>
        <div style="font-size:12px;color:#64748b;line-height:1.5;margin-bottom:16px;">{{ $p['description'] }}</div>
        <div x-show="!yearly">
            <span style="font-size:32px;font-weight:900;color:{{ $p['badge_color'] }};">₦{{ number_format($p['price']['monthly']) }}</span>
            <span style="font-size:13px;color:#94a3b8;font-weight:500;">/month</span>
        </div>
        <div x-show="yearly" style="display:none;">
            <span style="font-size:32px;font-weight:900;color:{{ $p['badge_color'] }};">₦{{ number_format($p['price']['yearly']) }}</span>
            <span style="font-size:13px;color:#94a3b8;font-weight:500;">/year</span>
            <div style="font-size:11px;color:#1FA84A;font-weight:700;margin-top:2px;">Save ₦{{ number_format(($p['price']['monthly'] * 12) - $p['price']['yearly']) }} per year</div>
        </div>
    </div>

    <div style="padding:20px 24px;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">What's included</div>
        @foreach($p['highlights'] as $hl)
        <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:9px;">
            <div style="width:18px;height:18px;border-radius:50%;background:{{ $p['badge_color'] }}18;flex-shrink:0;display:flex;align-items:center;justify-content:center;margin-top:1px;">
                <svg width="10" height="10" fill="none" stroke="{{ $p['badge_color'] }}" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span style="font-size:13px;color:#374151;font-weight:500;line-height:1.4;">{{ $hl }}</span>
        </div>
        @endforeach
    </div>

    <div style="margin:0 24px 20px;background:#f8fafc;border-radius:10px;padding:12px 14px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Listings</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $p['limits']['product_listings'] === -1 ? 'Unlimited' : number_format($p['limits']['product_listings']) }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Orders/Month</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $p['limits']['orders_per_month'] === -1 ? 'Unlimited' : number_format($p['limits']['orders_per_month']) }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Team Members</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $p['limits']['team_members'] === -1 ? 'Unlimited' : $p['limits']['team_members'] }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Analytics History</div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $p['limits']['analytics_history'] === -1 ? 'All Time' : $p['limits']['analytics_history'].' days' }}</div>
            </div>
        </div>
    </div>

    <div style="padding:0 24px 24px;">
        @if($isCurrent)
            <div style="text-align:center;padding:12px;background:#f0fdf4;border-radius:10px;border:1.5px solid #bbf7d0;">
                <span style="color:#15803d;font-weight:700;font-size:13px;">✓ Your Current Plan</span>
            </div>
        @elseif($activeSub && $activeSub->planLevel() > $p['plan_level'])
            <form method="POST" action="{{ route('subscription.subscribe') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">
                <input type="hidden" name="billing_cycle" :value="yearly ? 'yearly' : 'monthly'">
                <button type="submit" style="width:100%;padding:12px;border-radius:10px;border:2px solid {{ $p['badge_color'] }};background:transparent;color:{{ $p['badge_color'] }};font-size:14px;font-weight:700;cursor:pointer;">
                    Downgrade to {{ $p['name'] }}
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('subscription.subscribe') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">
                <input type="hidden" name="billing_cycle" :value="yearly ? 'yearly' : 'monthly'">
                <button type="submit" style="width:100%;padding:13px;border-radius:10px;border:none;background:{{ $p['badge_color'] }};color:#fff;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 4px 14px {{ $p['badge_color'] }}44;">
                    @if(!$activeSub && !$user->latestSubscription())
                        Start 14-Day Free Trial
                    @elseif($activeSub)
                        Upgrade to {{ $p['name'] }}
                    @else
                        Subscribe Now
                    @endif
                </button>
            </form>
        @endif
    </div>
</div>
@endif
@endforeach
</div>
</div>

{{-- Professional comparison table --}}
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;margin-top:40px;overflow:hidden;">
    <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;background:linear-gradient(135deg,#0B2447,#0F6B3E);">
        <h3 style="font-size:17px;font-weight:800;color:#fff;margin:0;">Plan Comparison</h3>
        <p style="font-size:12px;color:rgba(255,255,255,0.6);margin:4px 0 0;">What's included in each professional plan</p>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:14px 20px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;width:55%;">Feature</th>
                    <th style="text-align:center;padding:14px 16px;font-size:13px;font-weight:800;color:#1FA84A;width:22%;">Starter</th>
                    <th style="text-align:center;padding:14px 16px;font-size:13px;font-weight:800;color:#2D9CDB;width:23%;background:#f0f9ff;">Business</th>
                </tr>
            </thead>
            <tbody>
@php
$proRows = [
    ['label' => 'Role Dashboard & Analytics',          'starter' => true,  'business' => true],
    ['label' => 'Product / Service Listings (up to 50)','starter' => true,  'business' => true],
    ['label' => 'Unlimited Product Listings',           'starter' => false, 'business' => true],
    ['label' => 'Order & Request Management',           'starter' => true,  'business' => true],
    ['label' => 'Marketplace Presence',                 'starter' => true,  'business' => true],
    ['label' => 'Priority Marketplace Placement',       'starter' => false, 'business' => true],
    ['label' => 'Basic Analytics Dashboard',            'starter' => true,  'business' => true],
    ['label' => 'Advanced Analytics & Reports',         'starter' => false, 'business' => true],
    ['label' => 'PDF & Excel Exports',                  'starter' => false, 'business' => true],
    ['label' => 'Mobile App Access',                    'starter' => true,  'business' => true],
    ['label' => 'Team Members (up to 2 / up to 10)',    'starter' => true,  'business' => true],
    ['label' => 'API Integration',                      'starter' => false, 'business' => true],
    ['label' => 'Dedicated Account Manager',            'starter' => false, 'business' => true],
    ['label' => '24/7 Priority Support',                'starter' => false, 'business' => true],
    ['label' => 'Email & In-app Support',               'starter' => true,  'business' => true],
];
$rIdx = 0;
@endphp
@foreach($proRows as $row)
@php $rIdx++; @endphp
<tr style="border-bottom:1px solid #f1f5f9;{{ $rIdx % 2 === 0 ? 'background:#fafafa;' : '' }}">
    <td style="padding:11px 20px;font-size:13px;color:#374151;font-weight:500;">{{ $row['label'] }}</td>
    <td style="text-align:center;padding:11px 16px;">
        @if($row['starter'])
            <svg width="16" height="16" fill="none" stroke="#1FA84A" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        @else
            <svg width="16" height="16" fill="none" stroke="#cbd5e1" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        @endif
    </td>
    <td style="text-align:center;padding:11px 16px;background:{{ $row['business'] ? '#f0f9ff' : '#f8fafc' }};">
        @if($row['business'])
            <svg width="16" height="16" fill="none" stroke="#2D9CDB" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        @else
            <svg width="16" height="16" fill="none" stroke="#cbd5e1" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        @endif
    </td>
</tr>
@endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Trial Notice -->
@if(!$activeSub && !$latestSub)
<div style="background:linear-gradient(135deg,#f0fdf4,#e0f2fe);border:1px solid #bbf7d0;border-radius:14px;padding:20px 24px;margin-top:24px;text-align:center;">
    <div style="font-size:28px;margin-bottom:8px;">🎉</div>
    <div style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:6px;">Start with a 14-Day Free Trial</div>
    <div style="font-size:13px;color:#475569;margin-bottom:0;">No credit card required. Try any plan free for 14 days. Cancel anytime.</div>
</div>
@endif

</x-app-layout>
