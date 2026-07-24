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

@foreach(['success','info','warning','error'] as $_t)
@if(session($_t))
@php $sc=['success'=>['#f0fdf4','#bbf7d0','#15803d'],'info'=>['#eff6ff','#bfdbfe','#1d4ed8'],'warning'=>['#fef3c7','#fcd34d','#92400e'],'error'=>['#fef2f2','#fecaca','#dc2626']][$_t]; @endphp
<div style="background:{{ $sc[0] }};border:1px solid {{ $sc[1] }};border-radius:10px;padding:12px 16px;margin-bottom:16px;color:{{ $sc[2] }};font-size:13px;font-weight:600;">{{ session($_t) }}</div>
@endif
@endforeach

@php
$isProfessional = !in_array($user->role, ['farmer', 'ceo', 'admin', 'general-user']);
$proPlans = ['professional_starter', 'professional_business'];
// Farmer plan keys in display order
$farmerPlanKeys = ['basic', 'basic_pro', 'premium', 'enterprise', 'enterprise_plus'];
@endphp

@if(!$isProfessional)

{{-- ══ Free Trial Banner ══════════════════════════════════════════════════ --}}
@if(!$activeSub && !$user->latestSubscription())
<div style="background:linear-gradient(135deg,#0F6B3E,#1FA84A);border-radius:14px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
    <div>
        <div style="color:#fff;font-size:16px;font-weight:800;margin-bottom:4px;">🎉 Start with a 14-Day Free Trial</div>
        <div style="color:rgba(255,255,255,0.8);font-size:13px;">10 AI Smart Scans · Basic Farm Dashboard · Marketplace Preview · No credit card required</div>
    </div>
    <a href="{{ route('subscription.subscribe') }}" style="display:none;"></a>
    <div style="color:rgba(255,255,255,0.7);font-size:12px;font-weight:600;">Subscribe to any plan below to activate your free trial →</div>
</div>
@endif

{{-- ══ Quick Subscribe ════════════════════════════════════════════════════ --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px 24px;margin-bottom:24px;"
     x-data="{ qplan: '{{ $activeSub ? $activeSub->plan : 'basic_pro' }}', qcycle: 'monthly', open: false }">
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
                 :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s;"><polyline points="6 9 12 15 18 9"/></svg>
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
                        <option value="basic">🏠 Basic Plan — ₦2,500/month</option>
                        <option value="basic_pro">🌿 Basic Pro Plan — ₦5,000/month</option>
                        <option value="premium">⭐ Premium Plan — ₦10,000/month</option>
                        <option value="enterprise">🏢 Enterprise Plan — ₦30,000/month</option>
                        <option value="enterprise_plus">💎 Enterprise Plus — ₦75,000/month</option>
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
                    qplan === 'basic'          ? (qcycle === 'yearly' ? '₦25,000/yr'  : '₦2,500/mo')  :
                    qplan === 'basic_pro'      ? (qcycle === 'yearly' ? '₦50,000/yr'  : '₦5,000/mo')  :
                    qplan === 'premium'        ? (qcycle === 'yearly' ? '₦100,000/yr' : '₦10,000/mo') :
                    qplan === 'enterprise'     ? (qcycle === 'yearly' ? '₦300,000/yr' : '₦30,000/mo') :
                                                 (qcycle === 'yearly' ? '₦750,000/yr' : '₦75,000/mo')
                "></div>
            </div>
            <button type="submit"
                style="padding:10px 24px;background:#0F6B3E;color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:7px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Subscribe Now
            </button>
        </form>
        <p style="font-size:11px;color:#94a3b8;margin-top:10px;">New users get a <strong>14-day free trial</strong> before any payment is required.</p>
    </div>
</div>

{{-- ══ Current Plan Banner ════════════════════════════════════════════════ --}}
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

{{-- ══ Plan Cards ══════════════════════════════════════════════════════════ --}}
<style>
    .plan-card { transition: transform 0.2s, box-shadow 0.2s; }
    .plan-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.12); }
</style>

<div x-data="{ yearly: false }">

<!-- Billing Toggle -->
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

@php
$planIcons = [
    'basic'          => '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    'basic_pro'      => '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2v-4M9 21H5a2 2 0 01-2-2v-4m0 0h18"/></svg>',
    'premium'        => '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
    'enterprise'     => '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
    'enterprise_plus'=> '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l3.057-3 3.943 4 3.943-4L19 3l-1 8H6L5 3zM6 11v10h12V11"/></svg>',
];
@endphp

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;">
@foreach($farmerPlanKeys as $key)
@php
    $p           = $plans[$key] ?? null;
    if (!$p) continue;
    $isCurrent   = $activeSub && $activeSub->plan === $key;
    $isBasicPro  = $key === 'basic_pro';
    $isPremium   = $key === 'premium';
    $isEnterprise= $key === 'enterprise';
    $isEntPlus   = $key === 'enterprise_plus';

    $highlightCard = $isBasicPro || $isPremium;
    $headerBg = $isEntPlus    ? 'linear-gradient(135deg,#0B2447,#1a3a6e)'
              : ($isEnterprise? 'linear-gradient(135deg,#f5f3ff,#ede9fe)'
              : ($isPremium   ? 'linear-gradient(135deg,#f0f9ff,#e0f2fe)'
              : ($isBasicPro  ? 'linear-gradient(135deg,#f0fdfa,#ccfbf1)'
              : '#f8fafc')));
    $textOnDark = $isEntPlus;
@endphp

<div class="plan-card" style="background:#fff;border-radius:16px;border:2px solid {{ $isCurrent ? $p['badge_color'] : ($highlightCard ? $p['badge_color'] : ($isEnterprise ? $p['badge_color'] : ($isEntPlus ? '#1a3a6e' : '#e2e8f0'))) }};overflow:hidden;position:relative;{{ ($highlightCard || $isEnterprise || $isEntPlus) ? 'box-shadow:0 8px 32px '.$p['badge_color'].'33;' : '' }}">

    {{-- Badges --}}
    @if($isBasicPro && !$isCurrent)
    <div style="position:absolute;top:0;left:0;right:0;background:linear-gradient(90deg,#0D9488,#1FA84A);height:3px;"></div>
    <div style="position:absolute;top:14px;right:14px;background:#0D9488;color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:0.05em;">BEST VALUE</div>
    @elseif($isPremium && !$isCurrent)
    <div style="position:absolute;top:0;left:0;right:0;background:linear-gradient(90deg,#2D9CDB,#0F6B3E);height:3px;"></div>
    <div style="position:absolute;top:14px;right:14px;background:#2D9CDB;color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:0.05em;">MOST POPULAR</div>
    @elseif($isEntPlus && !$isCurrent)
    <div style="position:absolute;top:0;left:0;right:0;background:linear-gradient(90deg,#0B2447,#7C3AED);height:3px;"></div>
    <div style="position:absolute;top:14px;right:14px;background:#F4A300;color:#0B2447;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:0.05em;">RECOMMENDED</div>
    @endif
    @if($isCurrent)
    <div style="position:absolute;top:14px;right:14px;background:{{ $p['badge_color'] }};color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;">CURRENT PLAN</div>
    @endif

    <!-- Card Header -->
    <div style="padding:24px 24px 20px;background:{{ $headerBg }};">
        <div style="width:44px;height:44px;border-radius:11px;background:{{ $p['badge_color'] }};display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#fff;">
            {!! $planIcons[$key] !!}
        </div>
        <div style="font-size:18px;font-weight:800;color:{{ $textOnDark ? '#fff' : '#0f172a' }};margin-bottom:4px;">{{ $p['name'] }}</div>
        <div style="font-size:12px;color:{{ $textOnDark ? 'rgba(255,255,255,0.65)' : '#64748b' }};line-height:1.5;margin-bottom:16px;">{{ $p['description'] }}</div>

        <div x-show="!yearly">
            <span style="font-size:30px;font-weight:900;color:{{ $textOnDark ? '#F4A300' : $p['badge_color'] }};">₦{{ number_format($p['price']['monthly']) }}</span>
            <span style="font-size:13px;color:{{ $textOnDark ? 'rgba(255,255,255,0.55)' : '#94a3b8' }};font-weight:500;">/month</span>
        </div>
        <div x-show="yearly" style="display:none;">
            <span style="font-size:30px;font-weight:900;color:{{ $textOnDark ? '#F4A300' : $p['badge_color'] }};">₦{{ number_format($p['price']['yearly']) }}</span>
            <span style="font-size:13px;color:{{ $textOnDark ? 'rgba(255,255,255,0.55)' : '#94a3b8' }};font-weight:500;">/year</span>
            <div style="font-size:11px;color:#1FA84A;font-weight:700;margin-top:2px;">
                Save ₦{{ number_format(($p['price']['monthly'] * 12) - $p['price']['yearly']) }} per year
            </div>
        </div>
    </div>

    <!-- Highlights -->
    <div style="padding:18px 24px;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">What's included</div>
        @foreach($p['highlights'] as $hl)
        <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:8px;">
            <div style="width:17px;height:17px;border-radius:50%;background:{{ $p['badge_color'] }}18;flex-shrink:0;display:flex;align-items:center;justify-content:center;margin-top:1px;">
                <svg width="9" height="9" fill="none" stroke="{{ $p['badge_color'] }}" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span style="font-size:12px;color:#374151;font-weight:500;line-height:1.4;">{{ $hl }}</span>
        </div>
        @endforeach
    </div>

    <!-- Key Limits -->
    <div style="margin:0 20px 18px;background:#f8fafc;border-radius:10px;padding:12px 14px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">AI Scans/Month</div>
                <div style="font-size:13px;font-weight:800;color:#0f172a;">{{ ($p['limits']['ai_scans_per_month'] ?? 0) === -1 ? 'Unlimited' : ($p['limits']['ai_scans_per_month'] ?? '—') }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Records</div>
                <div style="font-size:13px;font-weight:800;color:#0f172a;">{{ ($p['limits']['livestock_records'] ?? 0) === -1 ? 'Unlimited' : number_format($p['limits']['livestock_records'] ?? 0) }}</div>
            </div>
            @if(isset($p['limits']['vet_consultations_per_cycle']))
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Vet Consults</div>
                <div style="font-size:13px;font-weight:800;color:#0f172a;">{{ $p['limits']['vet_consultations_per_cycle'] === -1 ? 'Unlimited' : $p['limits']['vet_consultations_per_cycle'].'/mo' }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Agro Consults</div>
                <div style="font-size:13px;font-weight:800;color:#0f172a;">{{ ($p['limits']['agronomist_consultations_per_cycle'] ?? 0) === -1 ? 'Unlimited' : ($p['limits']['agronomist_consultations_per_cycle'] ?? '—').'/mo' }}</div>
            </div>
            @else
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Reports/Month</div>
                <div style="font-size:13px;font-weight:800;color:#0f172a;">{{ ($p['limits']['reports_per_month'] ?? 0) === -1 ? 'Unlimited' : ($p['limits']['reports_per_month'] ?? '—') }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;">Farm Staff</div>
                <div style="font-size:13px;font-weight:800;color:#0f172a;">{{ ($p['limits']['farm_staff'] ?? 0) === -1 ? 'Unlimited' : ($p['limits']['farm_staff'] ?? '—') }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- CTA -->
    <div style="padding:0 20px 20px;">
        @if($isCurrent)
            <div style="text-align:center;padding:12px;background:#f0fdf4;border-radius:10px;border:1.5px solid #bbf7d0;">
                <span style="color:#15803d;font-weight:700;font-size:13px;">✓ Your Current Plan</span>
            </div>
        @elseif($activeSub && $activeSub->planLevel() > $p['plan_level'])
            <form method="POST" action="{{ route('subscription.subscribe') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">
                <input type="hidden" name="billing_cycle" :value="yearly ? 'yearly' : 'monthly'">
                <button type="submit" style="width:100%;padding:12px;border-radius:10px;border:2px solid {{ $p['badge_color'] }};background:transparent;color:{{ $p['badge_color'] }};font-size:13px;font-weight:700;cursor:pointer;">
                    Downgrade to {{ $p['name'] }}
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('subscription.subscribe') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">
                <input type="hidden" name="billing_cycle" :value="yearly ? 'yearly' : 'monthly'">
                <button type="submit"
                    style="width:100%;padding:12px;border-radius:10px;border:none;background:{{ $p['badge_color'] }};color:#fff;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 4px 14px {{ $p['badge_color'] }}44;">
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
</div>{{-- end plan grid --}}
</div>{{-- end x-data yearly --}}

{{-- ══ Feature Comparison Table ═══════════════════════════════════════════ --}}
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;margin-top:40px;overflow:hidden;">
    <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;background:linear-gradient(135deg,#0B2447,#0F6B3E);">
        <h3 style="font-size:17px;font-weight:800;color:#fff;margin:0;">Full Feature Comparison</h3>
        <p style="font-size:12px;color:rgba(255,255,255,0.6);margin:4px 0 0;">See exactly what's included in each plan</p>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:700px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:14px 16px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;width:30%;">Feature</th>
                    <th style="text-align:center;padding:14px 10px;font-size:12px;font-weight:800;color:#1FA84A;width:14%;">Basic</th>
                    <th style="text-align:center;padding:14px 10px;font-size:12px;font-weight:800;color:#0D9488;width:14%;background:#f0fdfa;">Basic Pro</th>
                    <th style="text-align:center;padding:14px 10px;font-size:12px;font-weight:800;color:#2D9CDB;width:14%;background:#f0f9ff;">Premium</th>
                    <th style="text-align:center;padding:14px 10px;font-size:12px;font-weight:800;color:#7C3AED;width:14%;background:#f5f3ff;">Enterprise</th>
                    <th style="text-align:center;padding:14px 10px;font-size:12px;font-weight:800;color:#0B2447;width:14%;background:#eef2ff;">Ent. Plus</th>
                </tr>
            </thead>
            <tbody>
@php
$check = fn($c) => '<svg width="15" height="15" fill="none" stroke="'.$c.'" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
$cross  = '<svg width="15" height="15" fill="none" stroke="#cbd5e1" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

$compRows = [
  ['cat' => 'Core Features', 'rows' => [
    ['AI Smart Scans',              [15,   25,   100,  '∞','∞']],
    ['Livestock Records',           ['50','100','∞',  '∞','∞']],
    ['Crop Records',                ['50','100','∞',  '∞','∞']],
    ['Feeding & Vaccination Reminders', [1,1,1,1,1]],
    ['Farm Activity Logging',       [1,1,1,1,1]],
    ['Mobile App Access',           [1,1,1,1,1]],
    ['Multilingual Support',        [1,1,1,1,1]],
    ['FAQ & Chatbot Support',       [1,1,1,1,1]],
    ['Marketplace Access',          [1,1,1,1,1]],
  ]],
  ['cat' => 'Consultations', 'rows' => [
    ['Vet Consultations/Month',     [0,3,  15, '∞','∞']],
    ['Agronomist Consultations/Month',[0,3,15, '∞','∞']],
    ['Marketplace Seller Account',  [0,0,1,1,1]],
    ['Priority Support',            [0,1,1,1,1]],
  ]],
  ['cat' => 'Reports & Analytics', 'rows' => [
    ['Basic Farm Reports',          [1,1,1,1,1]],
    ['Report Downloads',            [1,1,1,1,1]],
    ['AI-Generated Reports',        [0,1,1,1,1]],
    ['PDF & Excel Export',          [0,1,1,1,1]],
    ['Advanced Reports',            [0,0,1,1,1]],
    ['Custom Reports',              [0,0,0,1,1]],
    ['AI Predictive Analytics',     [0,0,1,1,1]],
    ['Advanced Analytics Dashboard',[0,0,0,1,1]],
    ['Executive Dashboard',         [0,0,0,0,1]],
  ]],
  ['cat' => 'Advanced Farm Management', 'rows' => [
    ['Advanced Health Records',     [0,0,1,1,1]],
    ['Breeding & Reproduction',     [0,0,1,1,1]],
    ['Production Tracking',         [0,0,1,1,1]],
    ['Inventory Management',        [0,0,1,1,1]],
    ['Financial Records',           [0,0,1,1,1]],
    ['Geo-tagged Farm Profiling',   [0,0,1,1,1]],
    ['Weather Intelligence',        [0,0,1,1,1]],
    ['Disease Alerts',              [0,1,1,1,1]],
    ['SMS Notifications',           [0,0,1,1,1]],
    ['Direct Messaging',            [0,0,1,1,1]],
  ]],
  ['cat' => 'Enterprise Features', 'rows' => [
    ['Multi-Branch Management',     [0,0,0,1,1]],
    ['Role-Based Access Control',   [0,0,0,1,1]],
    ['API Access',                  [0,0,0,1,1]],
    ['Bulk Data Upload',            [0,0,0,1,1]],
    ['Enterprise Security',         [0,0,0,1,1]],
    ['Dedicated Account Manager',   [0,0,0,1,1]],
    ['Unlimited Users',             [0,0,0,1,1]],
    ['AI Decision Intelligence',    [0,0,0,0,1]],
    ['ML Analytics',                [0,0,0,0,1]],
    ['Custom AI Models',            [0,0,0,0,1]],
    ['White Label Option',          [0,0,0,0,1]],
    ['Custom Integrations',         [0,0,0,0,1]],
    ['Dedicated Cloud Resources',   [0,0,0,0,1]],
    ['24/7 Technical Support',      [0,0,0,0,1]],
    ['Dedicated Success Manager',   [0,0,0,0,1]],
    ['Multi-country Operations',    [0,0,0,0,1]],
    ['Quarterly Reviews',           [0,0,0,0,1]],
  ]],
];
$colColors = ['#1FA84A','#0D9488','#2D9CDB','#7C3AED','#0B2447'];
$colBg     = ['','#f0fdfa','#f0f9ff','#f5f3ff','#eef2ff'];
$rowIdx    = 0;
@endphp

@foreach($compRows as $section)
<tr>
    <td colspan="6" style="padding:9px 16px 5px;background:#f1f5f9;font-size:11px;font-weight:800;color:#374151;text-transform:uppercase;letter-spacing:0.08em;">
        {{ $section['cat'] }}
    </td>
</tr>
@foreach($section['rows'] as [$label, $vals])
@php $rowIdx++; @endphp
<tr style="border-bottom:1px solid #f1f5f9;{{ $rowIdx % 2 === 0 ? 'background:#fafafa;' : '' }}">
    <td style="padding:10px 16px;font-size:12px;color:#374151;font-weight:500;">{{ $label }}</td>
    @foreach($vals as $ci => $v)
    @php
        $bg = $colBg[$ci];
        if ($v === 1)        { $cell = $check($colColors[$ci]); }
        elseif ($v === 0)    { $cell = $cross; }
        elseif ($v === '∞')  { $cell = '<span style="font-weight:900;font-size:14px;color:'.$colColors[$ci].'">∞</span>'; }
        else                 { $cell = '<span style="font-weight:800;font-size:12px;color:'.$colColors[$ci].'">'.$v.'</span>'; }
    @endphp
    <td style="text-align:center;padding:10px 10px;{{ $bg ? 'background:'.$bg.';' : '' }}">{!! $cell !!}</td>
    @endforeach
</tr>
@endforeach
@endforeach
            </tbody>
        </table>
    </div>
</div>

@else
{{-- ══════ Professional Plans Section ════════════════════════════════════ --}}
<style>.plan-card{transition:transform 0.2s,box-shadow 0.2s;}.plan-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,0.12);}</style>
<div x-data="{ yearly: false }">
<div style="display:flex;justify-content:center;margin-bottom:32px;">
    <div style="background:#f1f5f9;border-radius:12px;padding:4px;display:flex;align-items:center;gap:4px;">
        <button @click="yearly=false" :style="!yearly ? 'background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.12);color:#0F6B3E;font-weight:700;' : 'color:#64748b;background:transparent;'"
            style="padding:8px 20px;border-radius:9px;border:none;cursor:pointer;font-size:14px;transition:all 0.2s;">Monthly</button>
        <button @click="yearly=true" :style="yearly ? 'background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.12);color:#0F6B3E;font-weight:700;' : 'color:#64748b;background:transparent;'"
            style="padding:8px 20px;border-radius:9px;border:none;cursor:pointer;font-size:14px;transition:all 0.2s;">
            Yearly <span style="background:#F4A300;color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:20px;margin-left:4px;">Save 17%</span>
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
    @if(!$isCurrent)<div style="position:absolute;top:14px;right:14px;background:#2D9CDB;color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;">MOST POPULAR</div>@endif
    @endif
    @if($isCurrent)<div style="position:absolute;top:14px;right:14px;background:{{ $p['badge_color'] }};color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;">CURRENT PLAN</div>@endif

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
            <div style="font-size:11px;color:#1FA84A;font-weight:700;margin-top:2px;">Save ₦{{ number_format(($p['price']['monthly'] * 12) - $p['price']['yearly']) }}/yr</div>
        </div>
    </div>
    <div style="padding:20px 24px;">
        @foreach($p['highlights'] as $hl)
        <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:9px;">
            <div style="width:18px;height:18px;border-radius:50%;background:{{ $p['badge_color'] }}18;flex-shrink:0;display:flex;align-items:center;justify-content:center;margin-top:1px;">
                <svg width="10" height="10" fill="none" stroke="{{ $p['badge_color'] }}" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span style="font-size:13px;color:#374151;font-weight:500;line-height:1.4;">{{ $hl }}</span>
        </div>
        @endforeach
    </div>
    <div style="padding:0 24px 24px;">
        @if($isCurrent)
            <div style="text-align:center;padding:12px;background:#f0fdf4;border-radius:10px;border:1.5px solid #bbf7d0;"><span style="color:#15803d;font-weight:700;font-size:13px;">✓ Your Current Plan</span></div>
        @elseif($activeSub && $activeSub->planLevel() > $p['plan_level'])
            <form method="POST" action="{{ route('subscription.subscribe') }}">@csrf<input type="hidden" name="plan" value="{{ $key }}"><input type="hidden" name="billing_cycle" :value="yearly ? 'yearly' : 'monthly'"><button type="submit" style="width:100%;padding:12px;border-radius:10px;border:2px solid {{ $p['badge_color'] }};background:transparent;color:{{ $p['badge_color'] }};font-size:14px;font-weight:700;cursor:pointer;">Downgrade to {{ $p['name'] }}</button></form>
        @else
            <form method="POST" action="{{ route('subscription.subscribe') }}">@csrf<input type="hidden" name="plan" value="{{ $key }}"><input type="hidden" name="billing_cycle" :value="yearly ? 'yearly' : 'monthly'"><button type="submit" style="width:100%;padding:13px;border-radius:10px;border:none;background:{{ $p['badge_color'] }};color:#fff;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 4px 14px {{ $p['badge_color'] }}44;">@if(!$activeSub && !$user->latestSubscription()) Start 14-Day Free Trial @elseif($activeSub) Upgrade to {{ $p['name'] }} @else Subscribe Now @endif</button></form>
        @endif
    </div>
</div>
@endif
@endforeach
</div>
</div>
@endif

<!-- Trial Notice -->
@if(!$activeSub && !$latestSub)
<div style="background:linear-gradient(135deg,#f0fdf4,#e0f2fe);border:1px solid #bbf7d0;border-radius:14px;padding:20px 24px;margin-top:24px;text-align:center;">
    <div style="font-size:28px;margin-bottom:8px;">🎉</div>
    <div style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:6px;">Start with a 14-Day Free Trial</div>
    <div style="font-size:13px;color:#475569;">No credit card required. Try any plan free for 14 days. Cancel anytime.</div>
</div>
@endif

</x-app-layout>
