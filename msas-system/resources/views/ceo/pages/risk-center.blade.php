<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    @include('ceo.partials.styles')

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    {{-- ═══════════════════════════════════════════════════════════
         RISK & ALERT CENTER
    ═══════════════════════════════════════════════════════════ --}}
    @php
    $criticals = [];
    $warnings  = [];

    foreach(($diseaseAlerts ?? []) as $da) {
        if ($da['severity'] === 'high')
            $criticals[] = "Disease outbreak: {$da['disease']} — {$da['cases']} cases in 30 days";
        elseif ($da['severity'] === 'medium')
            $warnings[]  = "Disease alert: {$da['disease']} — {$da['cases']} cases";
    }
    if (($failedPaymentsToday ?? 0) > 0)
        $criticals[] = "{$failedPaymentsToday} payment(s) failed today — requires immediate review";
    if ($orderStats['pending'] > 20)
        $criticals[] = "{$orderStats['pending']} orders stuck in pending — dispatch backlog";
    elseif ($orderStats['pending'] > 5)
        $warnings[]  = "{$orderStats['pending']} orders pending dispatch";
    if ($churnRate > 10)
        $criticals[] = "High subscription churn: {$churnRate}% this month";
    elseif ($churnRate > 5)
        $warnings[]  = "Elevated churn rate: {$churnRate}% this month";
    if (($walletStats['pending_withdrawals'] ?? 0) > 0)
        $warnings[]  = "{$walletStats['pending_withdrawals']} pending withdrawal(s) totalling ₦".number_format($walletStats['withdrawals_value'] ?? 0);
    if ($pendingExperts > 10)
        $criticals[] = "{$pendingExperts} expert accounts awaiting approval";
    elseif ($pendingExperts > 3)
        $warnings[]  = "{$pendingExperts} expert(s) pending approval";
    if (($pendingVerifications ?? 0) > $pendingExperts)
        $warnings[]  = (($pendingVerifications ?? 0) - $pendingExperts)." professional account(s) pending verification";
    @endphp

    <div class="bi-card" style="padding:16px 20px;">
        <div class="bi-card-title" style="margin-bottom:12px;">
            <span class="bi-dot" style="background:#ef4444;"></span>
            Risk & Alert Center
            <div class="ml-auto flex gap-2 flex-wrap">
                @if(count($criticals) > 0)
                <span class="spill" style="background:#fef2f2;color:#dc2626;">{{ count($criticals) }} Critical</span>
                @endif
                @if(count($warnings) > 0)
                <span class="spill" style="background:#fffbeb;color:#b45309;">{{ count($warnings) }} Warning</span>
                @endif
                @if(count($criticals) === 0 && count($warnings) === 0)
                <span class="spill" style="background:#f0fdf4;color:#16a34a;">All Systems Operational</span>
                @endif
            </div>
        </div>
        @if(count($criticals) > 0 || count($warnings) > 0)
        <div class="space-y-2">
            @foreach($criticals as $c)
            <div class="risk-crit px-4 py-2.5 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span style="font-size:12px;font-weight:600;color:#7f1d1d;">{{ $c }}</span>
            </div>
            @endforeach
            @foreach($warnings as $w)
            <div class="risk-warn px-4 py-2.5 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span style="font-size:12px;font-weight:600;color:#78350f;">{{ $w }}</span>
            </div>
            @endforeach
        </div>
        @else
        <div class="risk-ok px-4 py-3 flex items-center gap-3">
            <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span style="font-size:13px;font-weight:600;color:#14532d;">Platform operating normally — no critical alerts at this time.</span>
        </div>
        @endif
    </div>

    </div>
</x-app-layout>
