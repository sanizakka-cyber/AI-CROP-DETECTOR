<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">My Subscription</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Manage your plan, usage, and billing</p>
        </div>
        <a href="{{ route('subscription.plans') }}"
           style="background:linear-gradient(135deg,#0F6B3E,#1FA84A);color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:7px;box-shadow:0 4px 14px #0F6B3E44;">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/></svg>
            View All Plans
        </a>
    </div>
</x-slot>

@foreach(['success','info','warning','error'] as $type)
    @if(session($type))
    @php
    $colors = ['success'=>['#f0fdf4','#bbf7d0','#15803d'],'info'=>['#eff6ff','#bfdbfe','#1d4ed8'],'warning'=>['#fef3c7','#fcd34d','#92400e'],'error'=>['#fef2f2','#fecaca','#dc2626']];
    $c = $colors[$type];
    @endphp
    <div style="background:{{ $c[0] }};border:1px solid {{ $c[1] }};border-radius:10px;padding:12px 16px;margin-bottom:16px;color:{{ $c[2] }};font-size:13px;font-weight:600;">
        {{ session($type) }}
    </div>
    @endif
@endforeach

<!-- ── No Subscription ──────────────────────────────────────────────── -->
@if(!$activeSub && !$latestSub)
<div style="text-align:center;padding:60px 24px;background:#fff;border-radius:16px;border:2px dashed #e2e8f0;">
    <div style="font-size:56px;margin-bottom:16px;">🌾</div>
    <div style="font-size:22px;font-weight:800;color:#0f172a;margin-bottom:8px;">No Active Subscription</div>
    <div style="font-size:14px;color:#64748b;max-width:400px;margin:0 auto 24px;">Choose a plan to unlock powerful farm management tools designed to help you grow.</div>
    <a href="{{ route('subscription.plans') }}" style="background:#0F6B3E;color:#fff;padding:13px 28px;border-radius:10px;font-size:14px;font-weight:800;text-decoration:none;">
        Start Your 14-Day Free Trial →
    </a>
</div>
@else

<!-- ── Active Subscription Hero ─────────────────────────────────────── -->
@php
$plan   = $activeSub ?? $latestSub;
$cfg    = config('subscription.plans.'.$plan->plan) ?? ['name' => ucfirst($plan->plan), 'badge_color' => '#64748b'];
$status = config('subscription.statuses.'.$plan->status) ?? ['label' => ucfirst($plan->status), 'color' => '#64748b'];
@endphp

<div style="background:linear-gradient(135deg,#0B2447,#0F6B3E);border-radius:16px;padding:28px;margin-bottom:24px;position:relative;overflow:hidden;">
    <!-- Decorative orbs -->
    <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,0.04);top:-60px;right:-40px;"></div>
    <div style="position:absolute;width:120px;height:120px;border-radius:50%;background:rgba(244,163,0,0.08);bottom:-20px;left:100px;"></div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:20px;position:relative;">
        <!-- Plan -->
        <div>
            <div style="color:rgba(255,255,255,0.6);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Current Plan</div>
            <div style="font-size:26px;font-weight:900;color:#fff;line-height:1.2;">{{ $cfg['name'] }}</div>
            <div style="margin-top:8px;">
                <span style="background:{{ $status['color'] }};color:#fff;font-size:11px;font-weight:800;padding:3px 12px;border-radius:20px;">
                    {{ $status['label'] }}
                </span>
            </div>
        </div>

        <!-- Expires -->
        <div>
            <div style="color:rgba(255,255,255,0.6);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">
                {{ $plan->isTrial() ? 'Trial Ends' : 'Renews / Expires' }}
            </div>
            <div style="font-size:20px;font-weight:800;color:#F4A300;">{{ $plan->endsAt()->format('M d, Y') }}</div>
            @if($plan->isActive())
            <div style="color:rgba(255,255,255,0.55);font-size:12px;margin-top:4px;">{{ $plan->daysRemaining() }} days remaining</div>
            @endif
        </div>

        <!-- Billing -->
        <div>
            <div style="color:rgba(255,255,255,0.6);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Billing Cycle</div>
            <div style="font-size:20px;font-weight:800;color:#fff;">{{ ucfirst($plan->billing_cycle) }}</div>
            @if($plan->amount_paid > 0)
            <div style="color:rgba(255,255,255,0.55);font-size:12px;margin-top:4px;">₦{{ number_format($plan->amount_paid) }} paid</div>
            @endif
        </div>

        <!-- Auto-Renew -->
        <div>
            <div style="color:rgba(255,255,255,0.6);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Auto-Renewal</div>
            <div style="font-size:16px;font-weight:800;color:{{ $plan->auto_renew ? '#1FA84A' : '#F4A300' }};">
                {{ $plan->auto_renew ? '✓ Enabled' : '✗ Disabled' }}
            </div>
            @if($plan->isActive())
            <form method="POST" action="{{ route('subscription.toggle.autorenew') }}" style="margin-top:6px;">
                @csrf
                <button type="submit" style="background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.25);padding:4px 12px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">
                    Toggle
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Days Remaining Bar -->
    @if($plan->isActive())
    @php
    $totalDays = $plan->starts_at?->diffInDays($plan->endsAt()) ?: 30;
    $remainPct = max(0, min(100, ($plan->daysRemaining() / $totalDays) * 100));
    @endphp
    <div style="margin-top:20px;position:relative;">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
            <span style="color:rgba(255,255,255,0.6);font-size:11px;font-weight:600;">Subscription Period</span>
            <span style="color:rgba(255,255,255,0.6);font-size:11px;font-weight:600;">{{ round($remainPct) }}% remaining</span>
        </div>
        <div style="height:6px;background:rgba(255,255,255,0.15);border-radius:3px;overflow:hidden;">
            <div style="height:100%;width:{{ $remainPct }}%;background:linear-gradient(90deg,#1FA84A,#F4A300);border-radius:3px;"></div>
        </div>
    </div>
    @endif
</div>

<!-- ── Usage Meters (Basic plan only) ───────────────────────────────── -->
@if($activeSub && $activeSub->plan === 'basic')
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px 24px;margin-bottom:24px;">
    <div style="font-size:15px;font-weight:800;color:#0f172a;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" fill="none" stroke="#0F6B3E" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Usage This Month
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        @php
        $meters = [
            'livestock_records'   => ['label' => 'Livestock Records', 'icon' => '🐄', 'color' => '#1FA84A'],
            'reports_per_month'   => ['label' => 'Reports Generated', 'icon' => '📊', 'color' => '#2D9CDB'],
            'ai_scans_per_month'  => ['label' => 'AI Scans Used',     'icon' => '🔬', 'color' => '#F4A300'],
        ];
        @endphp
        @foreach($meters as $key => $m)
        @php
        $u = $usage[$key] ?? ['count' => 0, 'limit' => 0];
        $pct = $u['limit'] > 0 ? min(100, ($u['count'] / $u['limit']) * 100) : 0;
        $isWarning = $pct >= 80;
        @endphp
        <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:18px;">{{ $m['icon'] }}</span>
                    <span style="font-size:12px;font-weight:700;color:#374151;">{{ $m['label'] }}</span>
                </div>
                <span style="font-size:13px;font-weight:800;color:{{ $isWarning ? '#dc2626' : '#0f172a' }};">
                    {{ $u['count'] }}/{{ $u['limit'] }}
                </span>
            </div>
            <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;background:{{ $isWarning ? 'linear-gradient(90deg,#F4A300,#dc2626)' : "linear-gradient(90deg,{$m['color']},#0F6B3E)" }};border-radius:4px;transition:width 0.3s;"></div>
            </div>
            @if($isWarning)
            <div style="color:#dc2626;font-size:11px;font-weight:600;margin-top:6px;">
                ⚠ Approaching limit — <a href="{{ route('subscription.plans') }}" style="color:#dc2626;text-decoration:underline;">Upgrade</a>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- ── Quick Actions ──────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px;">

    @if($activeSub)
    <!-- Upgrade -->
    @php $nextPlan = $activeSub->plan === 'basic' ? 'pro' : ($activeSub->plan === 'pro' ? 'premium' : null); @endphp
    @if($nextPlan)
    @php $nc = config('subscription.plans.'.$nextPlan); @endphp
    <a href="{{ route('subscription.plans') }}" style="background:linear-gradient(135deg,{{ $nc['badge_color'] }},{{ $nc['badge_color'] }}cc);color:#fff;border-radius:12px;padding:16px;text-decoration:none;display:block;">
        <div style="font-size:20px;margin-bottom:8px;">⬆</div>
        <div style="font-size:14px;font-weight:800;margin-bottom:2px;">Upgrade to {{ $nc['name'] }}</div>
        <div style="font-size:11px;opacity:0.8;">Unlock more features</div>
    </a>
    @endif

    <!-- Cancel -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;" x-data="{ open: false }">
        <div style="font-size:20px;margin-bottom:8px;">⏸</div>
        <div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:2px;">Cancel Subscription</div>
        <div style="font-size:11px;color:#64748b;margin-bottom:10px;">Access continues until {{ $activeSub->endsAt()->format('M d') }}</div>
        <button @click="open=true" style="background:#fee2e2;color:#dc2626;border:none;padding:7px 14px;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">
            Cancel Plan
        </button>

        <!-- Cancel Modal -->
        <div x-show="open" x-cloak style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;">
            <div style="background:#fff;border-radius:14px;padding:24px;max-width:380px;width:90%;">
                <div style="font-size:16px;font-weight:800;color:#0f172a;margin-bottom:8px;">Cancel Subscription?</div>
                <div style="font-size:13px;color:#64748b;margin-bottom:16px;">Your access continues until <strong>{{ $activeSub->endsAt()->format('M d, Y') }}</strong>. You won't be charged again.</div>
                <form method="POST" action="{{ route('subscription.cancel') }}">
                    @csrf
                    <textarea name="reason" placeholder="Optional: Tell us why you're cancelling..." style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px;font-size:13px;resize:none;height:80px;margin-bottom:14px;box-sizing:border-box;"></textarea>
                    <div style="display:flex;gap:10px;">
                        <button type="button" @click="open=false" style="flex:1;padding:10px;border:1px solid #e2e8f0;background:#fff;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                            Keep Plan
                        </button>
                        <button type="submit" style="flex:1;padding:10px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">
                            Yes, Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Support -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
        <div style="font-size:20px;margin-bottom:8px;">💬</div>
        <div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:2px;">Need Help?</div>
        <div style="font-size:11px;color:#64748b;margin-bottom:10px;">Contact our support team</div>
        <a href="mailto:sanizakka@gmail.com" style="background:#f0fdf4;color:#0F6B3E;padding:7px 14px;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none;display:inline-block;">
            Contact Support
        </a>
    </div>
</div>

<!-- ── Subscription History ─────────────────────────────────────────── -->
@if($history->count() > 1)
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;">
        <div style="font-size:15px;font-weight:800;color:#0f172a;">Subscription History</div>
    </div>
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#f8fafc;">
                <th style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Plan</th>
                <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Status</th>
                <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Started</th>
                <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Ended</th>
                <th style="text-align:right;padding:11px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $h)
            @php $sc = config('subscription.statuses.'.$h->status); $pc = config('subscription.plans.'.$h->plan); @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:11px 20px;font-size:13px;font-weight:700;color:#0f172a;">
                    <span style="background:{{ $pc['badge_color'] ?? '#64748b' }}18;color:{{ $pc['badge_color'] ?? '#64748b' }};padding:2px 8px;border-radius:5px;font-size:11px;font-weight:800;">
                        {{ strtoupper($h->plan) }}
                    </span>
                </td>
                <td style="padding:11px 16px;">
                    <span style="background:{{ ($sc['color'] ?? '#64748b') }}18;color:{{ $sc['color'] ?? '#64748b' }};padding:2px 8px;border-radius:5px;font-size:11px;font-weight:700;">
                        {{ $sc['label'] ?? ucfirst($h->status) }}
                    </span>
                </td>
                <td style="padding:11px 16px;font-size:12px;color:#64748b;">{{ $h->starts_at?->format('M d, Y') ?? '—' }}</td>
                <td style="padding:11px 16px;font-size:12px;color:#64748b;">{{ $h->ends_at?->format('M d, Y') ?? '—' }}</td>
                <td style="padding:11px 20px;text-align:right;font-size:13px;font-weight:700;color:#0f172a;">
                    {{ $h->amount_paid > 0 ? '₦'.number_format($h->amount_paid) : 'Free Trial' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endif {{-- end has subscription --}}
</x-app-layout>
