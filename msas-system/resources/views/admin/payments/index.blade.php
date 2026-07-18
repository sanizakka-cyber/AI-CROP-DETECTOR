<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Payment Management</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">All transactions across MSAS platform</p>
        </div>
    </div>
</x-slot>

@foreach(['success','info','error'] as $type)
    @if(session($type))
    @php $c=['success'=>['#f0fdf4','#bbf7d0','#15803d'],'info'=>['#eff6ff','#bfdbfe','#1d4ed8'],'error'=>['#fef2f2','#fecaca','#dc2626']][$type]; @endphp
    <div style="background:{{$c[0]}};border:1px solid {{$c[1]}};border-radius:10px;padding:12px 16px;margin-bottom:16px;color:{{$c[2]}};font-size:13px;font-weight:600;">{{ session($type) }}</div>
    @endif
@endforeach

{{-- Revenue Summary Cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px;margin-bottom:28px;">
    <div style="background:linear-gradient(135deg,#0B2447,#0F6B3E);border-radius:14px;padding:20px;">
        <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.08em;">Total Revenue</div>
        <div style="font-size:26px;font-weight:900;color:#fff;margin-top:6px;">₦{{ number_format($stats['total_revenue'], 2) }}</div>
        <div style="font-size:12px;color:rgba(255,255,255,.5);margin-top:4px;">{{ $stats['total_count'] }} transactions</div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">This Month</div>
        <div style="font-size:26px;font-weight:900;color:#0F6B3E;margin-top:6px;">₦{{ number_format($stats['month_revenue'], 2) }}</div>
        <div style="font-size:12px;color:#94a3b8;margin-top:4px;">{{ $stats['month_count'] }} payments</div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Pending</div>
        <div style="font-size:26px;font-weight:900;color:#d97706;margin-top:6px;">{{ $stats['pending_count'] }}</div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Failed</div>
        <div style="font-size:26px;font-weight:900;color:#dc2626;margin-top:6px;">{{ $stats['failed_count'] }}</div>
    </div>
</div>

{{-- Revenue by Module --}}
@if(!empty($byModule))
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px;margin-bottom:24px;">
    <div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:16px;">Revenue by Service</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
    @foreach($byModule as $row)
    <div style="background:#f8fafc;border-radius:10px;padding:14px;border:1px solid #f1f5f9;">
        <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:capitalize;">{{ str_replace('_',' ',$row->module) }}</div>
        <div style="font-size:18px;font-weight:900;color:#0f172a;margin-top:4px;">₦{{ number_format($row->total, 2) }}</div>
        <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ $row->cnt }} transactions</div>
    </div>
    @endforeach
    </div>
</div>
@endif

{{-- Filters --}}
<div style="background:#fff;border-radius:14px;padding:18px 20px;border:1px solid #e2e8f0;margin-bottom:18px;">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Status</label>
            <select name="status" style="border:1.5px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;">
                <option value="">All</option>
                <option value="success"  {{ request('status')=='success'?'selected':'' }}>Success</option>
                <option value="pending"  {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                <option value="failed"   {{ request('status')=='failed'?'selected':'' }}>Failed</option>
                <option value="refunded" {{ request('status')=='refunded'?'selected':'' }}>Refunded</option>
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Service</label>
            <select name="module" style="border:1.5px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;">
                <option value="">All Services</option>
                @foreach($modules as $mod)
                <option value="{{ $mod }}" {{ request('module')==$mod?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$mod)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Search</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Reference / email..." style="border:1.5px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;min-width:200px;">
        </div>
        <button type="submit" style="background:#0F6B3E;color:#fff;border:none;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:700;cursor:pointer;">Filter</button>
        <a href="{{ route('admin.payments.index') }}" style="padding:9px 16px;font-size:13px;color:#64748b;text-decoration:none;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:600;">Clear</a>
    </form>
</div>

{{-- Transactions Table --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;">
    @if($payments->isEmpty())
    <div style="text-align:center;padding:60px 24px;">
        <div style="font-size:48px;margin-bottom:12px;">💳</div>
        <div style="font-size:18px;font-weight:700;color:#0f172a;">No payments found</div>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th style="padding:12px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Date</th>
                    <th style="padding:12px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">User</th>
                    <th style="padding:12px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Description</th>
                    <th style="padding:12px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Reference</th>
                    <th style="padding:12px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Service</th>
                    <th style="padding:12px 14px;text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Amount</th>
                    <th style="padding:12px 14px;text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Status</th>
                </tr>
            </thead>
            <tbody>
            @foreach($payments as $payment)
            @php
            $sc=['success'=>['#f0fdf4','#15803d'],'pending'=>['#fef9c3','#92400e'],'failed'=>['#fef2f2','#dc2626'],'cancelled'=>['#f1f5f9','#475569'],'refunded'=>['#eff6ff','#1d4ed8']][$payment->status]??['#f1f5f9','#475569'];
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:12px 14px;font-size:12px;color:#64748b;">{{ $payment->created_at->format('d M Y') }}</td>
                <td style="padding:12px 14px;">
                    <div style="font-size:13px;font-weight:700;color:#0f172a;">{{ $payment->user->name }}</div>
                    <div style="font-size:11px;color:#94a3b8;">{{ $payment->user->email }}</div>
                </td>
                <td style="padding:12px 14px;font-size:13px;color:#374151;max-width:200px;">{{ $payment->description }}</td>
                <td style="padding:12px 14px;font-size:11px;color:#64748b;font-family:monospace;">{{ $payment->reference }}</td>
                <td style="padding:12px 14px;font-size:12px;color:#374151;text-transform:capitalize;">{{ str_replace('_',' ',$payment->module) }}</td>
                <td style="padding:12px 14px;font-size:14px;font-weight:900;color:#0f172a;text-align:right;">₦{{ number_format($payment->amount,2) }}</td>
                <td style="padding:12px 14px;text-align:center;">
                    <span style="background:{{$sc[0]}};color:{{$sc[1]}};font-size:11px;font-weight:800;padding:3px 10px;border-radius:20px;text-transform:capitalize;">{{ $payment->status }}</span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div style="padding:16px 20px;border-top:1px solid #f1f5f9;">
        {{ $payments->links() }}
    </div>
    @endif
    @endif
</div>

</x-app-layout>
