<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            @php $greetingKey = now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening'); @endphp
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">
                <span data-i18n="{{ $greetingKey }}">{{ __($greetingKey) }}</span>,
                {{ auth()->user()->displayFirstName }}
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
        <div style="width:44px;height:44px;border-radius:12px;background:rgba(244,163,0,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="22" height="22" fill="none" stroke="#F4A300" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg></div>
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
        <div style="width:40px;height:40px;border-radius:10px;background:{{ $planCfg['badge_color'] ?? '#1FA84A' }};display:flex;align-items:center;justify-content:center;"><svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg></div>
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

{{-- ── Onboarding Checklist ─────────────────────────────────────────── --}}
@if($showOnboarding ?? false)
@php $doneCount = collect($onboardingSteps)->where('done', true)->count(); @endphp
<div id="onboarding-card" style="background:#fff;border:2px solid #bbf7d0;border-radius:16px;padding:22px 24px;margin-bottom:24px;position:relative;">
    <button onclick="dismissOnboarding()" title="Dismiss"
        style="position:absolute;top:14px;right:16px;background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer;line-height:1;">&times;</button>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#0F6B3E,#1FA84A);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12m0 0C12 7 7 2 2 2c0 5 5 10 10 10zm0 0c0-5 5-10 10-10-5 0-10 5-10 10"/></svg></div>
        <div>
            <div style="font-size:15px;font-weight:800;color:#0f172a;">{{ __('Get Started') }} ({{ $doneCount }}/{{ count($onboardingSteps) }} {{ __('done') }})</div>
            <div style="font-size:12px;color:#64748b;">{{ __('Complete these steps to unlock the full power of MSAS FarmAI') }}</div>
        </div>
    </div>
    <div style="width:100%;height:6px;background:#e2e8f0;border-radius:3px;margin-bottom:18px;">
        <div style="width:{{ ($doneCount / count($onboardingSteps)) * 100 }}%;height:6px;background:linear-gradient(90deg,#0F6B3E,#1FA84A);border-radius:3px;transition:width 0.4s;"></div>
    </div>
    <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach($onboardingSteps as $step)
        <a href="{{ $step['url'] }}" style="display:flex;align-items:center;gap:14px;padding:12px 14px;border-radius:10px;background:{{ $step['done'] ? '#f0fdf4' : '#f8fafc' }};border:1px solid {{ $step['done'] ? '#bbf7d0' : '#e2e8f0' }};text-decoration:none;">
            <div style="width:24px;height:24px;border-radius:50%;background:{{ $step['done'] ? '#0F6B3E' : '#e2e8f0' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                @if($step['done'])
                    <svg width="12" height="12" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                @else
                    <div style="width:8px;height:8px;border-radius:50%;background:#94a3b8;"></div>
                @endif
            </div>
            <div>
                <div style="font-size:13px;font-weight:700;color:{{ $step['done'] ? '#0F6B3E' : '#0f172a' }};">{{ __($step['label']) }}</div>
                <div style="font-size:11px;color:#64748b;">{{ __($step['detail']) }}</div>
            </div>
            @if(!$step['done'])
            <div style="margin-left:auto;font-size:12px;color:#0F6B3E;font-weight:700;">{{ __('Start') }} →</div>
            @endif
        </a>
        @endforeach
    </div>
</div>
<script>
function dismissOnboarding() {
    fetch('{{ route('farmer.onboarding.dismiss') }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
    }).then(() => {
        const card = document.getElementById('onboarding-card');
        if (card) { card.style.opacity='0'; card.style.transition='opacity 0.3s'; setTimeout(()=>card.remove(),300); }
    });
}
</script>
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

    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;position:relative;overflow:hidden;">
        @php
            $scanLimit = $scanCheck['limit'] ?? -1;
            $scanUsed  = $scanCheck['used'] ?? 0;
            $scanPct   = ($scanLimit > 0 && $scanLimit !== -1) ? min(100, ($scanUsed / $scanLimit) * 100) : 0;
            $scanNearLimit = $scanLimit > 0 && $scanLimit !== -1 && $scanPct >= 80;
        @endphp
        @if($scanLimit > 0 && $scanLimit !== -1)
        <div style="position:absolute;top:0;left:0;width:{{ $scanPct }}%;height:3px;background:{{ $scanNearLimit?'#dc2626':'#1FA84A' }};border-radius:2px 0 0 0;"></div>
        @endif
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;" data-i18n="AI Diagnoses">{{ __('AI Diagnoses') }}</div>
        <div style="font-size:34px;font-weight:900;color:#1FA84A;line-height:1;">{{ $diagnosesCount }}</div>
        @if($scanLimit === -1)
        <div style="font-size:11px;color:#94a3b8;margin-top:4px;font-weight:600;">{{ __('Unlimited scans') }}</div>
        @elseif($scanLimit > 0)
        <div style="font-size:11px;color:{{ $scanNearLimit?'#dc2626':'#94a3b8' }};margin-top:4px;font-weight:600;">
            {{ $scanUsed }}/{{ $scanLimit }} {{ __('used this month') }}
            @if(!$scanCheck['allowed'])
            · <a href="{{ route('subscription.plans') }}" style="color:#0F6B3E;">{{ __('Upgrade') }}</a>
            @endif
        </div>
        @else
        <div style="font-size:11px;color:#94a3b8;margin-top:4px;font-weight:600;">{{ __('Total scans') }}</div>
        @endif
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
                <div style="width:38px;height:38px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg></div>
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
                <div style="width:56px;height:56px;border-radius:16px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><svg width="28" height="28" fill="none" stroke="#0F6B3E" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg></div>
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
                <div style="width:38px;height:38px;border-radius:10px;background:#fffbeb;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" fill="none" stroke="#b45309" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7c0 2.21-3.58 4-8 4S4 9.21 4 7s3.58-4 8-4 8 1.79 8 4zm-16 5c0 2.21 3.58 4 8 4s8-1.79 8-4M4 12v5c0 2.21 3.58 4 8 4s8-1.79 8-4v-5"/></svg></div>
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
                <div style="margin-bottom:10px;display:flex;justify-content:center;"><svg width="40" height="40" fill="none" stroke="#b45309" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7c0 2.21-3.58 4-8 4S4 9.21 4 7s3.58-4 8-4 8 1.79 8 4zm-16 5c0 2.21 3.58 4 8 4s8-1.79 8-4M4 12v5c0 2.21 3.58 4 8 4s8-1.79 8-4v-5"/></svg></div>
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
                <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="20" height="20" fill="none" stroke="#3b82f6" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg></div>
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
                <div style="margin-bottom:10px;display:flex;justify-content:center;"><svg width="36" height="36" fill="none" stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
                <p style="font-size:13px;color:#64748b;margin-bottom:10px;" data-i18n="Vet consultations require Pro plan">{{ __('Vet consultations require Pro plan') }}</p>
                <a href="{{ route('subscription.plans') }}" style="font-size:13px;color:#2D9CDB;font-weight:700;text-decoration:none;" data-i18n="Upgrade to Pro">{{ __('Upgrade to Pro') }}</a> →
                @else
                <div style="margin-bottom:10px;display:flex;justify-content:center;"><svg width="36" height="36" fill="none" stroke="#3b82f6" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg></div>
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
                <div style="width:38px;height:38px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="20" height="20" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-linecap="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg></div>
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
                <div style="margin-bottom:10px;display:flex;justify-content:center;"><svg width="36" height="36" fill="none" stroke="#0F6B3E" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-linecap="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg></div>
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

{{-- ── AI Weather Advisory Widget ──────────────────────────────────── --}}
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;margin-top:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f1f5f9;cursor:pointer;" onclick="toggleWeatherWidget()">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#0ea5e9,#2D9CDB);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg></div>
            <div>
                <div style="font-weight:800;font-size:14px;color:#0f172a;">AI Weather Advisory</div>
                <div style="font-size:11px;color:#94a3b8;">Farming-specific forecast for your region</div>
            </div>
        </div>
        <div id="weather-chevron" style="color:#94a3b8;transition:transform .2s;display:flex;"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg></div>
    </div>

    <div id="weather-body" style="display:none;padding:16px 20px;">
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
            <input type="text" id="weather-location" placeholder="Your location (e.g. Kano, Ogun)" value="{{ auth()->user()->state ?? '' }}"
                   style="flex:1;min-width:160px;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;">
            <input type="text" id="weather-crop" placeholder="Current crop (optional)"
                   style="flex:1;min-width:130px;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;">
            <button onclick="loadWeather()" style="padding:8px 18px;background:#0ea5e9;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;">Get Forecast</button>
        </div>
        <div id="weather-result" style="min-height:60px;font-size:13px;color:#475569;line-height:1.6;">
            <span style="color:#94a3b8;">Press "Get Forecast" to load AI weather advisory for your farm.</span>
        </div>
    </div>
</div>

<script>
function toggleWeatherWidget() {
    var body = document.getElementById('weather-body');
    var chevron = document.getElementById('weather-chevron');
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    chevron.style.transform = open ? '' : 'rotate(180deg)';
}
function loadWeather() {
    var loc = document.getElementById('weather-location').value || 'Nigeria';
    var crop = document.getElementById('weather-crop').value;
    var result = document.getElementById('weather-result');
    result.innerHTML = '<span style="color:#94a3b8;">Loading AI weather advisory...</span>';
    var fd = new FormData();
    fd.append('location', loc);
    if (crop) fd.append('crop', crop);
    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
    fetch('/ai/weather', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            result.textContent = '';
            if (d.error) {
                var e = document.createElement('span'); e.style.color='#ef4444'; e.textContent='Error: ' + d.error; result.appendChild(e); return;
            }
            function safeAppend(prefix, val, styles) {
                if (!val) return;
                var el = document.createElement('div'); if (styles) el.setAttribute('style', styles);
                el.textContent = prefix ? prefix + val : val; result.appendChild(el);
            }
            var summary = d.summary || d.forecast || d.advisory || d.recommendation || '';
            safeAppend('', summary, 'margin-bottom:10px;');
            safeAppend('Temp: ', d.temperature, 'font-size:12px;color:#64748b;');
            safeAppend('Rainfall: ', d.rainfall, 'font-size:12px;color:#64748b;');
            safeAppend('', d.farming_advice, 'margin-top:8px;padding:10px 12px;background:#f0fdf4;border-left:3px solid #0F6B3E;border-radius:6px;font-size:12px;color:#166534;');
            if (!result.hasChildNodes()) result.textContent = JSON.stringify(d, null, 2);
        })
        .catch(function() { result.textContent = 'Weather service temporarily unavailable.'; });
}
</script>
</x-app-layout>
