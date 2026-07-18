<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Payment History</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">All your transactions on MSAS</p>
        </div>
    </div>
</x-slot>

@foreach(['success','info','warning','error'] as $type)
    @if(session($type))
    @php $colors=['success'=>['#f0fdf4','#bbf7d0','#15803d'],'info'=>['#eff6ff','#bfdbfe','#1d4ed8'],'warning'=>['#fef3c7','#fcd34d','#92400e'],'error'=>['#fef2f2','#fecaca','#dc2626']]; $c=$colors[$type]; @endphp
    <div style="background:{{$c[0]}};border:1px solid {{$c[1]}};border-radius:10px;padding:12px 16px;margin-bottom:16px;color:{{$c[2]}};font-size:13px;font-weight:600;">{{ session($type) }}</div>
    @endif
@endforeach

{{-- Summary Cards --}}
@php
$summary = [
    'total'   => auth()->user()->payments()->successful()->sum('amount'),
    'count'   => auth()->user()->payments()->successful()->count(),
    'pending' => auth()->user()->payments()->pending()->count(),
    'failed'  => auth()->user()->payments()->failed()->count(),
];
@endphp

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px;margin-bottom:28px;">
    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Total Paid</div>
        <div style="font-size:26px;font-weight:900;color:#0F6B3E;margin-top:6px;">₦{{ number_format($summary['total'], 2) }}</div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Successful</div>
        <div style="font-size:26px;font-weight:900;color:#0f172a;margin-top:6px;">{{ $summary['count'] }}</div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Pending</div>
        <div style="font-size:26px;font-weight:900;color:#d97706;margin-top:6px;">{{ $summary['pending'] }}</div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;">Failed</div>
        <div style="font-size:26px;font-weight:900;color:#dc2626;margin-top:6px;">{{ $summary['failed'] }}</div>
    </div>
</div>

{{-- Filters --}}
<div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;margin-bottom:20px;">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Status</label>
            <select name="status" style="border:1.5px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;min-width:130px;">
                <option value="">All</option>
                <option value="success" {{ request('status')=='success'?'selected':'' }}>Successful</option>
                <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                <option value="failed"  {{ request('status')=='failed'?'selected':'' }}>Failed</option>
                <option value="refunded"{{ request('status')=='refunded'?'selected':'' }}>Refunded</option>
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Service</label>
            <select name="module" style="border:1.5px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;min-width:150px;">
                <option value="">All Services</option>
                @foreach($modules as $mod)
                <option value="{{ $mod }}" {{ request('module')==$mod?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$mod)) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" style="background:#0F6B3E;color:#fff;border:none;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:700;cursor:pointer;">Filter</button>
        <a href="{{ route('payment.history') }}" style="padding:9px 16px;font-size:13px;color:#64748b;text-decoration:none;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:600;">Clear</a>
    </form>
</div>

{{-- Transactions Table --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;">
    @if($payments->isEmpty())
    <div style="text-align:center;padding:60px 24px;">
        <div style="font-size:48px;margin-bottom:12px;">💳</div>
        <div style="font-size:18px;font-weight:700;color:#0f172a;">No payments found</div>
        <p style="color:#64748b;font-size:13px;">Your payment history will appear here.</p>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Date</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Description</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Reference</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Service</th>
                    <th style="padding:12px 16px;text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Amount</th>
                    <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Status</th>
                    <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Receipt</th>
                </tr>
            </thead>
            <tbody>
            @foreach($payments as $payment)
            @php
            $statusColors = [
                'success'  => ['#f0fdf4','#15803d'],
                'pending'  => ['#fef9c3','#92400e'],
                'failed'   => ['#fef2f2','#dc2626'],
                'cancelled'=> ['#f1f5f9','#475569'],
                'refunded' => ['#eff6ff','#1d4ed8'],
            ];
            $sc = $statusColors[$payment->status] ?? ['#f1f5f9','#475569'];
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:14px 16px;font-size:13px;color:#374151;">{{ $payment->created_at->format('d M Y') }}<br><span style="font-size:11px;color:#94a3b8;">{{ $payment->created_at->format('H:i') }}</span></td>
                <td style="padding:14px 16px;font-size:13px;color:#0f172a;font-weight:600;max-width:220px;">{{ $payment->description }}</td>
                <td style="padding:14px 16px;font-size:12px;color:#64748b;font-family:monospace;">{{ $payment->reference }}</td>
                <td style="padding:14px 16px;font-size:12px;color:#374151;">{{ ucfirst(str_replace('_',' ',$payment->module)) }}</td>
                <td style="padding:14px 16px;font-size:14px;font-weight:800;color:#0f172a;text-align:right;">₦{{ number_format($payment->amount, 2) }}</td>
                <td style="padding:14px 16px;text-align:center;">
                    <span style="background:{{$sc[0]}};color:{{$sc[1]}};font-size:11px;font-weight:800;padding:3px 10px;border-radius:20px;text-transform:capitalize;">{{ $payment->status }}</span>
                </td>
                <td style="padding:14px 16px;text-align:center;">
                    @if($payment->status === 'success')
                    <a href="{{ route('payment.receipt', $payment->id) }}" style="background:#0F6B3E;color:#fff;font-size:11px;font-weight:700;padding:5px 12px;border-radius:7px;text-decoration:none;">Receipt</a>
                    @else
                    <span style="color:#94a3b8;font-size:12px;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($payments->hasPages())
    <div style="padding:16px 20px;border-top:1px solid #f1f5f9;">
        {{ $payments->links() }}
    </div>
    @endif
    @endif
</div>

</x-app-layout>
