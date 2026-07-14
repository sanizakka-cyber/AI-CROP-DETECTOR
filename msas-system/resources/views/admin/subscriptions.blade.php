<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Subscription Management</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Monitor and manage all farmer subscriptions</p>
        </div>
    </div>
</x-slot>

@foreach(['success','info','warning','error'] as $t)
    @if(session($t))
    @php $cl=['success'=>['#f0fdf4','#bbf7d0','#15803d'],'info'=>['#eff6ff','#bfdbfe','#1d4ed8'],'warning'=>['#fef3c7','#fcd34d','#92400e'],'error'=>['#fef2f2','#fecaca','#dc2626']][$t]; @endphp
    <div style="background:{{ $cl[0] }};border:1px solid {{ $cl[1] }};border-radius:10px;padding:12px 16px;margin-bottom:14px;color:{{ $cl[2] }};font-size:13px;font-weight:600;">{{ session($t) }}</div>
    @endif
@endforeach

<!-- ── Stats Grid ────────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:24px;">
    @php
    $statCards = [
        ['label'=>'Total Subscriptions', 'val'=>$stats['total'],     'icon'=>'📋', 'color'=>'#0B2447'],
        ['label'=>'Active',              'val'=>$stats['active'],    'icon'=>'✅', 'color'=>'#1FA84A'],
        ['label'=>'Free Trials',         'val'=>$stats['trial'],     'icon'=>'🎁', 'color'=>'#2D9CDB'],
        ['label'=>'Expired',             'val'=>$stats['expired'],   'icon'=>'⌛', 'color'=>'#dc2626'],
        ['label'=>'Cancelled',           'val'=>$stats['cancelled'], 'icon'=>'❌', 'color'=>'#64748b'],
        ['label'=>'Revenue',             'val'=>'₦'.number_format($stats['revenue']), 'icon'=>'💰', 'color'=>'#F4A300'],
        ['label'=>'Basic Active',        'val'=>$stats['basic'],     'icon'=>'🏠', 'color'=>'#1FA84A'],
        ['label'=>'Pro Active',          'val'=>$stats['pro'],       'icon'=>'⚡', 'color'=>'#2D9CDB'],
        ['label'=>'Premium Active',      'val'=>$stats['premium'],   'icon'=>'👑', 'color'=>'#F4A300'],
    ];
    @endphp
    @foreach($statCards as $sc)
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:16px;">
        <div style="font-size:22px;margin-bottom:6px;">{{ $sc['icon'] }}</div>
        <div style="font-size:20px;font-weight:800;color:{{ $sc['color'] }};">{{ $sc['val'] }}</div>
        <div style="font-size:11px;color:#64748b;font-weight:600;margin-top:2px;">{{ $sc['label'] }}</div>
    </div>
    @endforeach
</div>

<!-- ── Filters ────────────────────────────────────────────────────────── -->
<div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:16px 20px;margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:5px;">Search User</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, phone..."
                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:5px;">Plan</label>
            <select name="plan" style="padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;min-width:120px;">
                <option value="">All Plans</option>
                @foreach(array_keys($plans) as $pk)
                <option value="{{ $pk }}" {{ request('plan') === $pk ? 'selected' : '' }}>{{ ucfirst($pk) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:5px;">Status</label>
            <select name="status" style="padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;min-width:120px;">
                <option value="">All Statuses</option>
                @foreach($statuses as $sk => $sv)
                <option value="{{ $sk }}" {{ request('status') === $sk ? 'selected' : '' }}>{{ $sv['label'] }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" style="background:#0F6B3E;color:#fff;padding:9px 18px;border-radius:8px;border:none;font-size:13px;font-weight:700;cursor:pointer;">Filter</button>
        @if(request()->hasAny(['search','plan','status']))
        <a href="{{ route('admin.subscriptions.index') }}" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;color:#64748b;text-decoration:none;font-weight:600;">Clear</a>
        @endif
    </form>
</div>

<!-- ── Subscriptions Table ───────────────────────────────────────────── -->
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:900px;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Farmer</th>
                    <th style="text-align:left;padding:12px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Plan</th>
                    <th style="text-align:left;padding:12px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Status</th>
                    <th style="text-align:left;padding:12px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Billing</th>
                    <th style="text-align:left;padding:12px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Expires</th>
                    <th style="text-align:right;padding:12px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Paid</th>
                    <th style="text-align:center;padding:12px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                @php
                    $sc  = config('subscription.statuses.'.$sub->status);
                    $pc  = config('subscription.plans.'.$sub->plan);
                    $exp = $sub->isTrial() ? $sub->trial_ends_at : $sub->ends_at;
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;" x-data="{ showActivate: false, showTrial: false, showCancel: false }">
                    <td style="padding:13px 20px;">
                        <div style="font-size:13px;font-weight:700;color:#0f172a;">{{ $sub->user->name }}</div>
                        <div style="font-size:11px;color:#64748b;">{{ $sub->user->email ?? $sub->user->phone }}</div>
                    </td>
                    <td style="padding:13px 14px;">
                        <span style="background:{{ ($pc['badge_color'] ?? '#64748b') }}18;color:{{ $pc['badge_color'] ?? '#64748b' }};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:800;">
                            {{ strtoupper($sub->plan) }}
                        </span>
                    </td>
                    <td style="padding:13px 14px;">
                        <span style="background:{{ ($sc['color'] ?? '#64748b') }}18;color:{{ $sc['color'] ?? '#64748b' }};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                            {{ $sc['label'] ?? ucfirst($sub->status) }}
                        </span>
                    </td>
                    <td style="padding:13px 14px;font-size:12px;color:#64748b;font-weight:600;">{{ ucfirst($sub->billing_cycle) }}</td>
                    <td style="padding:13px 14px;">
                        <div style="font-size:12px;font-weight:700;color:{{ $exp && $exp->isPast() ? '#dc2626' : '#0f172a' }};">
                            {{ $exp?->format('M d, Y') ?? '—' }}
                        </div>
                        @if($exp && $exp->isFuture())
                        <div style="font-size:10px;color:#64748b;">{{ $exp->diffForHumans() }}</div>
                        @endif
                    </td>
                    <td style="padding:13px 14px;text-align:right;font-size:13px;font-weight:700;color:#0f172a;">
                        {!! $sub->amount_paid > 0 ? '₦'.number_format($sub->amount_paid) : '<span style="color:#64748b;font-weight:400;font-size:11px;">Free</span>' !!}
                    </td>
                    <td style="padding:13px 20px;text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                            <!-- Activate -->
                            <button @click="showActivate=true" title="Activate/Extend"
                                style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">
                                Activate
                            </button>
                            <!-- Grant Trial -->
                            <button @click="showTrial=true" title="Grant Trial"
                                style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">
                                Trial
                            </button>
                            <!-- Suspend / Reinstate -->
                            @if($sub->status === 'suspended')
                            <form method="POST" action="{{ route('admin.subscriptions.reinstate', $sub) }}">
                                @csrf
                                <button type="submit" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">
                                    Reinstate
                                </button>
                            </form>
                            @elseif(in_array($sub->status, ['active','trial']))
                            <form method="POST" action="{{ route('admin.subscriptions.suspend', $sub) }}">
                                @csrf
                                <button type="submit" style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">
                                    Suspend
                                </button>
                            </form>
                            @endif
                            <!-- Terminate -->
                            @if(!in_array($sub->status, ['cancelled']))
                            <button @click="showCancel=true"
                                style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">
                                Cancel
                            </button>
                            @endif
                        </div>

                        <!-- Activate Modal -->
                        <div x-show="showActivate" x-cloak style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;">
                            <div style="background:#fff;border-radius:14px;padding:24px;max-width:380px;width:90%;text-align:left;">
                                <div style="font-size:15px;font-weight:800;margin-bottom:4px;">Activate Subscription</div>
                                <div style="font-size:12px;color:#64748b;margin-bottom:16px;">for {{ $sub->user->name }}</div>
                                <form method="POST" action="{{ route('admin.subscriptions.activate', $sub->user) }}">
                                    @csrf
                                    <div style="margin-bottom:10px;">
                                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Plan</label>
                                        <select name="plan" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;">
                                            <option value="basic">Basic Plan</option>
                                            <option value="pro">Pro Plan</option>
                                            <option value="premium">Premium Plan</option>
                                        </select>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                                        <div>
                                            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Billing</label>
                                            <select name="billing_cycle" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;">
                                                <option value="monthly">Monthly</option>
                                                <option value="yearly">Yearly</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Months</label>
                                            <input type="number" name="months" value="1" min="1" max="24" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;box-sizing:border-box;">
                                        </div>
                                    </div>
                                    <div style="margin-bottom:14px;">
                                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Notes (optional)</label>
                                        <textarea name="notes" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;resize:none;height:60px;box-sizing:border-box;"></textarea>
                                    </div>
                                    <div style="display:flex;gap:10px;">
                                        <button type="button" @click="showActivate=false" style="flex:1;padding:9px;border:1px solid #e2e8f0;background:#fff;border-radius:7px;font-size:13px;cursor:pointer;">Cancel</button>
                                        <button type="submit" style="flex:1;padding:9px;background:#0F6B3E;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;">Activate</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Trial Modal -->
                        <div x-show="showTrial" x-cloak style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;">
                            <div style="background:#fff;border-radius:14px;padding:24px;max-width:340px;width:90%;text-align:left;">
                                <div style="font-size:15px;font-weight:800;margin-bottom:4px;">Grant Free Trial</div>
                                <div style="font-size:12px;color:#64748b;margin-bottom:16px;">for {{ $sub->user->name }}</div>
                                <form method="POST" action="{{ route('admin.subscriptions.trial', $sub->user) }}">
                                    @csrf
                                    <div style="margin-bottom:10px;">
                                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Plan</label>
                                        <select name="plan" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;">
                                            <option value="basic">Basic</option><option value="pro">Pro</option><option value="premium">Premium</option>
                                        </select>
                                    </div>
                                    <div style="margin-bottom:14px;">
                                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Trial Days</label>
                                        <input type="number" name="days" value="14" min="1" max="90" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;box-sizing:border-box;">
                                    </div>
                                    <div style="display:flex;gap:10px;">
                                        <button type="button" @click="showTrial=false" style="flex:1;padding:9px;border:1px solid #e2e8f0;background:#fff;border-radius:7px;font-size:13px;cursor:pointer;">Cancel</button>
                                        <button type="submit" style="flex:1;padding:9px;background:#2D9CDB;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;">Grant Trial</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Cancel/Terminate Modal -->
                        <div x-show="showCancel" x-cloak style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;">
                            <div style="background:#fff;border-radius:14px;padding:24px;max-width:340px;width:90%;text-align:left;">
                                <div style="font-size:15px;font-weight:800;margin-bottom:4px;color:#dc2626;">Terminate Subscription</div>
                                <div style="font-size:12px;color:#64748b;margin-bottom:16px;">This will immediately cancel access for {{ $sub->user->name }}.</div>
                                <form method="POST" action="{{ route('admin.subscriptions.terminate', $sub) }}">
                                    @csrf
                                    <div style="margin-bottom:14px;">
                                        <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Reason (required)</label>
                                        <textarea name="reason" required style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;resize:none;height:70px;box-sizing:border-box;"></textarea>
                                    </div>
                                    <div style="display:flex;gap:10px;">
                                        <button type="button" @click="showCancel=false" style="flex:1;padding:9px;border:1px solid #e2e8f0;background:#fff;border-radius:7px;font-size:13px;cursor:pointer;">Back</button>
                                        <button type="submit" style="flex:1;padding:9px;background:#dc2626;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;">Terminate</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;font-size:14px;">No subscriptions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($subscriptions->hasPages())
    <div style="padding:14px 20px;border-top:1px solid #f1f5f9;">
        {{ $subscriptions->links() }}
    </div>
    @endif
</div>
</x-app-layout>
