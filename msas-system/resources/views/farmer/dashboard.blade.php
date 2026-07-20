<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            @php $greetingKey = now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening'); @endphp
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">
                <span data-i18n="{{ $greetingKey }}">{{ __($greetingKey) }}</span>,
                {{ auth()->user()->displayFirstName }} 👋
            </h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;" data-i18n="Here's what's happening on your farm today">{{ __("Here's what's happening on your farm today") }}</p>
        </div>
        <a href="{{ route('diagnostics.scan') }}"
           style="background:linear-gradient(135deg,#0F6B3E,#1FA84A);color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:7px;box-shadow:0 4px 14px #0F6B3E44;">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 8v6M8 11h6"/></svg>
            <span data-i18n="Run AI Scan">{{ __('Run AI Scan') }}</span>
        </a>
    </div>
</x-slot>

@php
    $user      = auth()->user();
    $activeSub = $user->activeSubscription();
    $subPlan   = $activeSub?->plan ?? 'none';
    $subStatus = $activeSub?->status ?? 'none';
    $planCfg   = $activeSub ? (config('subscription.plans.'.$subPlan) ?? []) : null;
@endphp

{{-- ── Subscription Status Banner ───────────────────────────────────── --}}
@if(!$activeSub)
{{-- No subscription — prominent upgrade CTA --}}
<div style="background:linear-gradient(135deg,#0B2447,#0F6B3E);border-radius:16px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
    <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;border-radius:12px;background:rgba(244,163,0,0.2);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">⭐</div>
        <div>
            <div style="color:rgba(255,255,255,0.7);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;" data-i18n="No Active Subscription">{{ __('No Active Subscription') }}</div>
            <div style="color:#fff;font-size:16px;font-weight:800;margin-top:2px;" data-i18n="Start your 14-day free trial today">{{ __('Start your 14-day free trial today') }}</div>
            <div style="color:rgba(255,255,255,0.55);font-size:12px;margin-top:2px;" data-i18n="Unlock livestock management, reports, and AI-powered tools">{{ __('Unlock livestock management, reports, and AI-powered tools') }}</div>
        </div>
    </div>
    <a href="{{ route('subscription.plans') }}"
       style="background:#F4A300;color:#0B2447;padding:10px 22px;border-radius:10px;font-size:13px;font-weight:800;text-decoration:none;white-space:nowrap;box-shadow:0 4px 14px rgba(244,163,0,0.4);">
        <span data-i18n="View Plans">{{ __('View Plans') }}</span> →
    </a>
</div>

@elseif($activeSub->isTrial())
{{-- Trial active --}}
<div style="background:linear-gradient(135deg,#1a3a6e,#0F6B3E);border-radius:16px;padding:18px 24px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <span style="background:rgba(45,156,219,0.25);color:#7dd3fc;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:800;border:1px solid rgba(45,156,219,0.3);" data-i18n="FREE TRIAL">{{ __('FREE TRIAL') }}</span>
        <div>
            <span style="color:#fff;font-weight:800;font-size:14px;">{{ $planCfg['name'] ?? ucfirst($subPlan) }}</span>
            <span style="color:rgba(255,255,255,0.55);font-size:12px;margin-left:8px;">{{ $activeSub->daysRemaining() }} <span data-i18n="days remaining">{{ __('days remaining') }}</span></span>
        </div>
    </div>
    <a href="{{ route('subscription.plans') }}"
       style="background:#F4A300;color:#0B2447;padding:8px 18px;border-radius:8px;font-size:12px;font-weight:800;text-decoration:none;">
        <span data-i18n="Upgrade Now">{{ __('Upgrade Now') }}</span>
    </a>
</div>

@else
{{-- Active paid plan --}}
<div style="background:linear-gradient(135deg,#0B2447,#0F6B3E);border-radius:16px;padding:18px 24px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;position:relative;overflow:hidden;">
    <div style="position:absolute;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,0.04);top:-40px;right:60px;"></div>
    <div style="display:flex;align-items:center;gap:12px;position:relative;">
        <div style="width:40px;height:40px;border-radius:10px;background:{{ $planCfg['badge_color'] ?? '#1FA84A' }};display:flex;align-items:center;justify-content:center;font-size:18px;">⭐</div>
        <div>
            <div style="color:rgba(255,255,255,0.6);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;" data-i18n="Active Plan">{{ __('Active Plan') }}</div>
            <div style="color:#fff;font-size:16px;font-weight:800;">{{ $planCfg['name'] ?? ucfirst($subPlan) }}</div>
        </div>
    </div>
    <div style="display:flex;gap:20px;position:relative;">
        <div style="text-align:center;">
            <div style="color:#F4A300;font-size:16px;font-weight:800;">{{ $activeSub->daysRemaining() }}</div>
            <div style="color:rgba(255,255,255,0.5);font-size:10px;font-weight:600;text-transform:uppercase;" data-i18n="Days Left">{{ __('Days Left') }}</div>
        </div>
        <div style="text-align:center;">
            <div style="color:#fff;font-size:16px;font-weight:800;">{{ ucfirst($activeSub->billing_cycle) }}</div>
            <div style="color:rgba(255,255,255,0.5);font-size:10px;font-weight:600;text-transform:uppercase;" data-i18n="Billing">{{ __('Billing') }}</div>
        </div>
    </div>
    <a href="{{ route('subscription.dashboard') }}"
       style="background:rgba(255,255,255,0.12);color:#fff;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid rgba(255,255,255,0.2);position:relative;">
        <span data-i18n="Manage Plan">{{ __('Manage Plan') }}</span>
    </a>
</div>
@endif

{{-- ── KPI Cards ──────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px;">

    {{-- Livestock count with limit indicator --}}
    @php
        $livestockLimit = $activeSub ? $activeSub->getLimit('livestock_records') : 0;
        $limitLabel = $livestockLimit === -1 ? __('Unlimited') : ($livestockLimit > 0 ? "of {$livestockLimit}" : __('Subscribe to add'));
        $limitPct = ($livestockLimit > 0 && $livestockLimit !== -1) ? min(100, ($animalsCount / $livestockLimit) * 100) : 100;
        $isNearLimit = $livestockLimit > 0 && $livestockLimit !== -1 && $limitPct >= 80;
    @endphp
    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;position:relative;overflow:hidden;">
        <div style="position:absolute;top:0;left:0;width:{{ $activeSub ? min(100,$limitPct) : 0 }}%;height:3px;background:{{ $isNearLimit ? '#dc2626' : '#1FA84A' }};border-radius:2px 0 0 0;transition:width 0.3s;"></div>
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;" data-i18n="My Animals">{{ __('My Animals') }}</div>
        <div style="font-size:34px;font-weight:900;color:#0F6B3E;line-height:1;">{{ $animalsCount }}</div>
        <div style="font-size:11px;color:{{ $isNearLimit ? '#dc2626' : '#94a3b8' }};margin-top:4px;font-weight:600;">{{ $limitLabel }}</div>
    </div>

    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;" data-i18n="Poultry Flocks">{{ __('Poultry Flocks') }}</div>
        <div style="font-size:34px;font-weight:900;color:#b45309;line-height:1;">{{ $poultryCount }}</div>
        <div style="font-size:11px;color:#94a3b8;margin-top:4px;font-weight:600;"><a href="{{ route('farmer.poultry') }}" style="color:#b45309;" data-i18n="View flocks">{{ __('View flocks') }}</a> →</div>
    </div>

    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;" data-i18n="AI Diagnoses">{{ __('AI Diagnoses') }}</div>
        <div style="font-size:34px;font-weight:900;color:#1FA84A;line-height:1;">{{ $diagnosesCount }}</div>
        <div style="font-size:11px;color:#94a3b8;margin-top:4px;font-weight:600;" data-i18n="Total scans">{{ __('Total scans') }}</div>
    </div>

    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;" data-i18n="Vet Consults">{{ __('Vet Consults') }}</div>
        <div style="font-size:34px;font-weight:900;color:#b45309;line-height:1;">{{ $pendingVetConsults }}</div>
        <div style="font-size:11px;color:#94a3b8;margin-top:4px;font-weight:600;" data-i18n="Pending response">{{ __('Pending response') }}</div>
    </div>

    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;" data-i18n="Net Balance">{{ __('Net Balance') }}</div>
        <div style="font-size:28px;font-weight:900;color:{{ $netBalance >= 0 ? '#0F6B3E' : '#dc2626' }};line-height:1;">
            ₦{{ number_format(abs($netBalance)) }}
        </div>
        <div style="font-size:11px;color:{{ $netBalance >= 0 ? '#94a3b8' : '#dc2626' }};margin-top:4px;font-weight:600;">
            {{ $netBalance >= 0 ? __('Farm income surplus') : __('Farm deficit') }}
        </div>
    </div>
</div>

{{-- ── Quick Actions ──────────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:20px 24px;margin-bottom:24px;">
    <div style="font-size:15px;font-weight:800;color:#0f172a;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;" data-i18n="Quick Actions">{{ __('Quick Actions') }}</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;">

        {{-- Livestock — Basic+ --}}
        <a href="{{ route('farmer.livestock') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#f0fdf4;border-radius:12px;text-decoration:none;border:1px solid transparent;transition:all 0.2s;"
           onmouseenter="this.style.borderColor='#1FA84A'" onmouseleave="this.style.borderColor='transparent'">
            <svg width="28" height="28" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <span style="font-size:12px;font-weight:700;color:#0F6B3E;text-align:center;" data-i18n="My Livestock">{{ __('My Livestock') }}</span>
        </a>

        {{-- Poultry — Basic+ --}}
        <a href="{{ route('farmer.poultry') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#fffbeb;border-radius:12px;text-decoration:none;border:1px solid transparent;"
           onmouseenter="this.style.borderColor='#F4A300'" onmouseleave="this.style.borderColor='transparent'">
            <svg width="28" height="28" fill="none" stroke="#b45309" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span style="font-size:12px;font-weight:700;color:#b45309;text-align:center;" data-i18n="Poultry & Eggs">{{ __('Poultry & Eggs') }}</span>
        </a>

        {{-- Vet Consult — Pro+ --}}
        @if($activeSub && $activeSub->hasFeature('vet_service_requests'))
        <a href="{{ route('farmer.vet') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#eff6ff;border-radius:12px;text-decoration:none;border:1px solid transparent;"
           onmouseenter="this.style.borderColor='#2D9CDB'" onmouseleave="this.style.borderColor='transparent'">
            <svg width="28" height="28" fill="none" stroke="#2D9CDB" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            <span style="font-size:12px;font-weight:700;color:#2D9CDB;text-align:center;" data-i18n="Request Vet">{{ __('Request Vet') }}</span>
        </a>
        @else
        <a href="{{ route('subscription.plans') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#f8fafc;border-radius:12px;text-decoration:none;border:1px solid #e2e8f0;position:relative;opacity:0.7;">
            <svg width="28" height="28" fill="none" stroke="#94a3b8" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
            <span style="font-size:12px;font-weight:700;color:#94a3b8;text-align:center;" data-i18n="Request Vet">{{ __('Request Vet') }}</span>
            <span style="position:absolute;top:6px;right:6px;background:#2D9CDB;color:#fff;font-size:8px;font-weight:800;padding:1px 5px;border-radius:8px;">PRO</span>
        </a>
        @endif

        {{-- Agro Advisory — Pro+ --}}
        @if($activeSub && $activeSub->hasFeature('vet_service_requests'))
        <a href="{{ route('farmer.agro') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#f0fdf4;border-radius:12px;text-decoration:none;border:1px solid transparent;"
           onmouseenter="this.style.borderColor='#1FA84A'" onmouseleave="this.style.borderColor='transparent'">
            <svg width="28" height="28" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            <span style="font-size:12px;font-weight:700;color:#0F6B3E;text-align:center;" data-i18n="Agro Advisory">{{ __('Agro Advisory') }}</span>
        </a>
        @else
        <a href="{{ route('subscription.plans') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#f8fafc;border-radius:12px;text-decoration:none;border:1px solid #e2e8f0;position:relative;opacity:0.7;">
            <svg width="28" height="28" fill="none" stroke="#94a3b8" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            <span style="font-size:12px;font-weight:700;color:#94a3b8;text-align:center;" data-i18n="Agro Advisory">{{ __('Agro Advisory') }}</span>
            <span style="position:absolute;top:6px;right:6px;background:#0F6B3E;color:#fff;font-size:8px;font-weight:800;padding:1px 5px;border-radius:8px;">PRO</span>
        </a>
        @endif

        {{-- Finance -- Basic+ --}}
        <a href="{{ route('farmer.finance') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#f5f3ff;border-radius:12px;text-decoration:none;border:1px solid transparent;"
           onmouseenter="this.style.borderColor='#7c3aed'" onmouseleave="this.style.borderColor='transparent'">
            <svg width="28" height="28" fill="none" stroke="#7c3aed" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span style="font-size:12px;font-weight:700;color:#7c3aed;text-align:center;" data-i18n="Finance">{{ __('Finance') }}</span>
        </a>

        {{-- Reports -- Pro+ --}}
        @if($activeSub && $activeSub->hasFeature('pdf_excel_reports'))
        <a href="{{ route('farmer.reports') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#fef3c7;border-radius:12px;text-decoration:none;border:1px solid transparent;"
           onmouseenter="this.style.borderColor='#F4A300'" onmouseleave="this.style.borderColor='transparent'">
            <svg width="28" height="28" fill="none" stroke="#b45309" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span style="font-size:12px;font-weight:700;color:#b45309;text-align:center;" data-i18n="Reports">{{ __('Reports') }}</span>
        </a>
        @else
        <a href="{{ route('subscription.plans') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#f8fafc;border-radius:12px;text-decoration:none;border:1px solid #e2e8f0;position:relative;opacity:0.7;">
            <svg width="28" height="28" fill="none" stroke="#94a3b8" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span style="font-size:12px;font-weight:700;color:#94a3b8;text-align:center;" data-i18n="Reports">{{ __('Reports') }}</span>
            <span style="position:absolute;top:6px;right:6px;background:#F4A300;color:#fff;font-size:8px;font-weight:800;padding:1px 5px;border-radius:8px;">PRO</span>
        </a>
        @endif

        {{-- AI Scan --}}
        <a href="{{ route('diagnostics.scan') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:linear-gradient(135deg,#f0fdf4,#e0f2fe);border-radius:12px;text-decoration:none;border:1px solid #bbf7d0;">
            <svg width="28" height="28" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 8v6M8 11h6"/></svg>
            <span style="font-size:12px;font-weight:700;color:#0F6B3E;text-align:center;" data-i18n="AI Scan">{{ __('AI Scan') }}</span>
        </a>

        {{-- Marketplace --}}
        <a href="{{ route('marketplace') }}"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:#eff6ff;border-radius:12px;text-decoration:none;border:1px solid transparent;"
           onmouseenter="this.style.borderColor='#2D9CDB'" onmouseleave="this.style.borderColor='transparent'">
            <svg width="28" height="28" fill="none" stroke="#2D9CDB" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span style="font-size:12px;font-weight:700;color:#2D9CDB;text-align:center;" data-i18n="Marketplace">{{ __('Marketplace') }}</span>
        </a>

        {{-- Premium: AI Recommendations --}}
        @if($activeSub && $activeSub->hasFeature('ai_recommendations'))
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px 8px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-radius:12px;border:1px solid #fcd34d;position:relative;">
            <svg width="28" height="28" fill="none" stroke="#F4A300" stroke-width="1.8" viewBox="0 0 24 24" style="margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            <span style="font-size:12px;font-weight:700;color:#b45309;text-align:center;" data-i18n="AI Advisor">{{ __('AI Advisor') }}</span>
            <span style="position:absolute;top:6px;right:6px;background:#F4A300;color:#fff;font-size:8px;font-weight:800;padding:1px 5px;border-radius:8px;">PREMIUM</span>
        </div>
        @endif
    </div>
</div>

{{-- ── Main Content Grid ──────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;">

    {{-- Recent Animals --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:15px;font-weight:800;color:#0f172a;" data-i18n="Recent Animals">{{ __('Recent Animals') }}</div>
            <a href="{{ route('farmer.livestock') }}" style="font-size:12px;color:#0F6B3E;font-weight:700;text-decoration:none;" data-i18n="View all">{{ __('View all') }}</a> →
        </div>
        <div style="padding:0 4px;">
            @forelse($recentAnimals as $animal)
            <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f8fafc;">
                <div style="width:38px;height:38px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">🐄</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $animal->name ?? 'Unnamed' }} <span style="font-weight:400;color:#94a3b8;">#{{ $animal->tag_number ?? '—' }}</span>
                    </div>
                    <div style="font-size:11px;color:#64748b;margin-top:1px;">{{ $animal->species ?? '—' }} · {{ $animal->breed ?? '—' }}</div>
                </div>
                @php $hs = strtolower($animal->health_status ?? 'healthy'); @endphp
                <span style="font-size:10px;font-weight:800;padding:3px 8px;border-radius:20px;white-space:nowrap;
                    background:{{ $hs === 'healthy' ? '#f0fdf4' : ($hs === 'sick' ? '#fef2f2' : '#fef3c7') }};
                    color:{{ $hs === 'healthy' ? '#15803d' : ($hs === 'sick' ? '#dc2626' : '#92400e') }};">
                    {{ ucfirst($animal->health_status ?? 'Healthy') }}
                </span>
            </div>
            @empty
            <div style="text-align:center;padding:32px 20px;">
                <div style="font-size:36px;margin-bottom:10px;">🐄</div>
                <p style="font-size:13px;color:#64748b;margin-bottom:10px;" data-i18n="No animals registered yet">{{ __('No animals registered yet') }}</p>
                <a href="{{ route('farmer.livestock') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;" data-i18n="Add your first animal">{{ __('Add your first animal') }}</a> →
            </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Poultry Flocks --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:15px;font-weight:800;color:#0f172a;" data-i18n="Poultry Flocks">{{ __('Poultry Flocks') }}</div>
            <a href="{{ route('farmer.poultry') }}" style="font-size:12px;color:#b45309;font-weight:700;text-decoration:none;" data-i18n="View all">{{ __('View all') }}</a> →
        </div>
        <div style="padding:0 4px;">
            @forelse($recentFlocks as $flock)
            <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f8fafc;">
                <div style="width:38px;height:38px;border-radius:10px;background:#fffbeb;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">🐔</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12px;font-weight:700;color:#0f172a;font-family:monospace;">{{ $flock->batch_number }}</div>
                    <div style="font-size:11px;color:#64748b;margin-top:1px;">{{ $flock->bird_type }} · {{ number_format($flock->quantity) }} birds</div>
                </div>
                @if($flock->purpose)
                <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:#fef3c7;color:#92400e;white-space:nowrap;">{{ ucfirst(str_replace('-',' ',$flock->purpose)) }}</span>
                @endif
            </div>
            @empty
            <div style="text-align:center;padding:32px 20px;">
                <div style="font-size:36px;margin-bottom:10px;">🐔</div>
                <p style="font-size:13px;color:#64748b;margin-bottom:10px;" data-i18n="No poultry flocks yet">{{ __('No poultry flocks yet') }}</p>
                <a href="{{ route('farmer.poultry') }}" style="font-size:13px;color:#b45309;font-weight:700;text-decoration:none;" data-i18n="Register a flock">{{ __('Register a flock') }}</a> →
            </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Vet Consultations --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:15px;font-weight:800;color:#0f172a;" data-i18n="Recent Consultations">{{ __('Recent Consultations') }}</div>
            @if($activeSub && $activeSub->hasFeature('vet_service_requests'))
            <a href="{{ route('farmer.vet') }}" style="font-size:12px;color:#0F6B3E;font-weight:700;text-decoration:none;" data-i18n="View all">{{ __('View all') }}</a> →
            @endif
        </div>
        <div style="padding:0 4px;">
            @forelse($recentConsults as $consult)
            <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f8fafc;">
                <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">🩺</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ ucfirst($consult->animal_type ?? 'Livestock') }} Consultation
                    </div>
                    <div style="font-size:11px;color:#64748b;margin-top:1px;">{{ $consult->created_at->diffForHumans() }}</div>
                </div>
                @php $st = $consult->status ?? 'pending'; @endphp
                <span style="font-size:10px;font-weight:800;padding:3px 8px;border-radius:20px;white-space:nowrap;
                    background:{{ $st === 'resolved' ? '#f0fdf4' : ($st === 'pending' ? '#fef3c7' : '#eff6ff') }};
                    color:{{ $st === 'resolved' ? '#15803d' : ($st === 'pending' ? '#92400e' : '#1d4ed8') }};">
                    {{ ucfirst($st) }}
                </span>
            </div>
            @empty
            <div style="text-align:center;padding:32px 20px;">
                @if(!$activeSub || !$activeSub->hasFeature('vet_service_requests'))
                <div style="font-size:36px;margin-bottom:10px;">🔒</div>
                <p style="font-size:13px;color:#64748b;margin-bottom:10px;" data-i18n="Vet consultations require Pro plan">{{ __('Vet consultations require Pro plan') }}</p>
                <a href="{{ route('subscription.plans') }}" style="font-size:13px;color:#2D9CDB;font-weight:700;text-decoration:none;" data-i18n="Upgrade to Pro">{{ __('Upgrade to Pro') }}</a> →
                @else
                <div style="font-size:36px;margin-bottom:10px;">🩺</div>
                <p style="font-size:13px;color:#64748b;margin-bottom:10px;" data-i18n="No consultations yet">{{ __('No consultations yet') }}</p>
                <a href="{{ route('farmer.vet') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;" data-i18n="Request a vet consult">{{ __('Request a vet consult') }}</a> →
                @endif
            </div>
            @endforelse
        </div>
    </div>

    {{-- Recent AI Diagnoses --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:15px;font-weight:800;color:#0f172a;" data-i18n="Recent AI Diagnoses">{{ __('Recent AI Diagnoses') }}</div>
            <a href="{{ route('diagnostics.history') }}" style="font-size:12px;color:#0F6B3E;font-weight:700;text-decoration:none;" data-i18n="View Diagnosis History">{{ __('View Diagnosis History') }}</a> →
        </div>
        <div style="padding:0 4px;">
            @forelse($recentScans as $scan)
            <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f8fafc;">
                <div style="width:38px;height:38px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">🔬</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $scan->disease_name ?? 'AI Scan Result' }}
                    </div>
                    <div style="font-size:11px;color:#64748b;margin-top:1px;">{{ $scan->created_at->diffForHumans() }}</div>
                </div>
                @php $st = $scan->status ?? 'pending'; @endphp
                <span style="font-size:10px;font-weight:800;padding:3px 8px;border-radius:20px;white-space:nowrap;
                    background:{{ $st === 'resolved' ? '#f0fdf4' : ($st === 'pending' ? '#fef3c7' : '#f8fafc') }};
                    color:{{ $st === 'resolved' ? '#15803d' : ($st === 'pending' ? '#92400e' : '#475569') }};">
                    {{ ucfirst($st) }}
                </span>
            </div>
            @empty
            <div style="text-align:center;padding:32px 20px;">
                <div style="font-size:36px;margin-bottom:10px;">🔬</div>
                <p style="font-size:13px;color:#64748b;margin-bottom:10px;" data-i18n="No scans yet">{{ __('No scans yet') }}</p>
                <a href="{{ route('diagnostics.scan') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;" data-i18n="Run your first scan">{{ __('Run your first scan') }}</a> →
            </div>
            @endforelse
        </div>
    </div>

    {{-- Finance Summary --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:15px;font-weight:800;color:#0f172a;" data-i18n="Farm Finance">{{ __('Farm Finance') }}</div>
            <a href="{{ route('farmer.finance') }}" style="font-size:12px;color:#0F6B3E;font-weight:700;text-decoration:none;" data-i18n="View all">{{ __('View all') }}</a> →
        </div>
        <div style="padding:16px 20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div style="background:#f0fdf4;border-radius:10px;padding:14px;">
                    <div style="font-size:10px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;" data-i18n="Total Income">{{ __('Total Income') }}</div>
                    <div style="font-size:20px;font-weight:800;color:#0F6B3E;">₦{{ number_format($totalIncome) }}</div>
                </div>
                <div style="background:#fef2f2;border-radius:10px;padding:14px;">
                    <div style="font-size:10px;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;" data-i18n="Total Expenses">{{ __('Total Expenses') }}</div>
                    <div style="font-size:20px;font-weight:800;color:#dc2626;">₦{{ number_format($totalExpense) }}</div>
                </div>
            </div>
            @forelse($recentFinances as $fin)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f8fafc;">
                <div>
                    <div style="font-size:12px;font-weight:600;color:#0f172a;">{{ $fin->category ?? 'Uncategorised' }}</div>
                    <div style="font-size:11px;color:#94a3b8;">{{ $fin->transaction_date ? \Carbon\Carbon::parse($fin->transaction_date)->format('M d') : '—' }}</div>
                </div>
                <div style="font-size:13px;font-weight:800;color:{{ $fin->type === 'Income' ? '#0F6B3E' : '#dc2626' }};">
                    {{ $fin->type === 'Income' ? '+' : '-' }}₦{{ number_format($fin->amount) }}
                </div>
            </div>
            @empty
            <p style="text-align:center;color:#94a3b8;font-size:12px;padding:10px 0;" data-i18n="No finance records yet">{{ __('No finance records yet') }}</p>
            @endforelse
        </div>
    </div>
</div>
</x-app-layout>
