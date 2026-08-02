<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Farm Reports</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Download your farm data as CSV or print as PDF</p>
        </div>
        <span style="background:{{ config('subscription.plans.'.$activeSub->plan.'.badge_color', '#1FA84A') }};color:#fff;font-size:11px;font-weight:800;padding:5px 14px;border-radius:20px;">
            {{ strtoupper($activeSub->plan) }} PLAN
        </span>
    </div>
</x-slot>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#15803d;font-size:13px;font-weight:600;">{{ session('success') }}</div>
@endif

<!-- ── Report Cards ───────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin-bottom:28px;">

@php
$reportTypes = [
    [
        'key'     => 'livestock',
        'title'   => 'Livestock Report',
        'desc'    => 'All registered animals with health status, breed, weight, and history.',
        'icon'    => '<svg width="28" height="28" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5S3 9 3 7.5A2.5 2.5 0 018 7.5H9M4.5 10.5H19.5M19.5 10.5S21 9 21 7.5A2.5 2.5 0 0016 7.5H15M19.5 10.5V16M4.5 10.5V16M7 16v3m10-3v3M7 16h10"/></svg>',
        'color'   => '#0F6B3E',
        'bg'      => '#f0fdf4',
        'count'   => $livestock->count(),
        'label'   => 'animals',
    ],
    [
        'key'     => 'finance',
        'title'   => 'Financial Report',
        'desc'    => 'Complete income and expense records with net farm balance summary.',
        'icon'    => '<svg width="28" height="28" fill="none" stroke="#7c3aed" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
        'color'   => '#7c3aed',
        'bg'      => '#f5f3ff',
        'count'   => $finances->count(),
        'label'   => 'transactions',
    ],
    [
        'key'     => 'consultations',
        'title'   => 'Vet Consultations',
        'desc'    => 'All veterinary consultation requests with status and priority levels.',
        'icon'    => '<svg width="28" height="28" fill="none" stroke="#2D9CDB" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
        'color'   => '#2D9CDB',
        'bg'      => '#eff6ff',
        'count'   => $consultations->count(),
        'label'   => 'consultations',
    ],
    [
        'key'     => 'poultry',
        'title'   => 'Poultry Report',
        'desc'    => 'All poultry batches with bird types, quantities, and acquisition dates.',
        'icon'    => '<svg width="28" height="28" fill="none" stroke="#F4A300" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>',
        'color'   => '#F4A300',
        'bg'      => '#fffbeb',
        'count'   => $poultry->count(),
        'label'   => 'batches',
    ],
];
@endphp

@foreach($reportTypes as $r)
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
    <!-- Card Header -->
    <div style="padding:18px 20px;background:{{ $r['bg'] }};border-bottom:1px solid {{ $r['color'] }}18;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
            <span style="line-height:1;flex-shrink:0">{!! $r['icon'] !!}</span>
            <div>
                <div style="font-size:15px;font-weight:800;color:#0f172a;">{{ $r['title'] }}</div>
                <div style="font-size:20px;font-weight:900;color:{{ $r['color'] }};">{{ $r['count'] }} <span style="font-size:11px;color:#64748b;font-weight:500;">{{ $r['label'] }}</span></div>
            </div>
        </div>
        <p style="font-size:12px;color:#64748b;line-height:1.5;margin:0;">{{ $r['desc'] }}</p>
    </div>

    <!-- Download Buttons -->
    <div style="padding:16px 20px;display:flex;gap:10px;flex-wrap:wrap;">
        <!-- CSV Download -->
        <a href="{{ route('farmer.reports.download', ['format' => 'csv', 'type' => $r['key']]) }}"
           style="flex:1;min-width:100px;display:flex;align-items:center;justify-content:center;gap:6px;padding:10px 14px;background:{{ $r['color'] }};color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            CSV
        </a>
        <!-- PDF Print View -->
        <a href="{{ route('farmer.reports.download', ['format' => 'pdf', 'type' => $r['key']]) }}" target="_blank"
           style="flex:1;min-width:100px;display:flex;align-items:center;justify-content:center;gap:6px;padding:10px 14px;background:#fff;color:{{ $r['color'] }};border:2px solid {{ $r['color'] }};border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print PDF
        </a>
    </div>
</div>
@endforeach
</div>

<!-- ── Farm Summary ───────────────────────────────────────────────── -->
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;margin-bottom:24px;">
    <div style="padding:16px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#0B2447,#0F6B3E);">
        <div style="font-size:16px;font-weight:800;color:#fff;">Farm Summary</div>
        <div style="font-size:12px;color:rgba(255,255,255,0.6);margin-top:2px;">Overview as of {{ now()->format('F d, Y') }}</div>
    </div>
    <div style="padding:20px 24px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:20px;">
            @php
            $summaryCards = [
                ['label' => 'Total Livestock',    'val' => $livestock->count(),           'color' => '#0F6B3E', 'icon' => '<svg width="18" height="18" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5S3 9 3 7.5A2.5 2.5 0 018 7.5H9M4.5 10.5H19.5M19.5 10.5S21 9 21 7.5A2.5 2.5 0 0016 7.5H15M19.5 10.5V16M4.5 10.5V16M7 16v3m10-3v3M7 16h10"/></svg>'],
                ['label' => 'Poultry Batches',    'val' => $poultry->count(),             'color' => '#F4A300', 'icon' => '<svg width="18" height="18" fill="none" stroke="#F4A300" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>'],
                ['label' => 'Total Income',        'val' => '₦'.number_format($totalIncome),  'color' => '#1FA84A', 'icon' => '<svg width="18" height="18" fill="none" stroke="#1FA84A" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>'],
                ['label' => 'Total Expenses',      'val' => '₦'.number_format($totalExpense), 'color' => '#dc2626', 'icon' => '<svg width="18" height="18" fill="none" stroke="#dc2626" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 17H5m0 0v-8m0 8l8-8 4 4 6-6"/></svg>'],
                ['label' => 'Net Balance',         'val' => '₦'.number_format($totalIncome - $totalExpense), 'color' => ($totalIncome >= $totalExpense ? '#0F6B3E' : '#dc2626'), 'icon' => '<svg width="18" height="18" fill="none" stroke="#64748b" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'],
                ['label' => 'Vet Consultations',  'val' => $consultations->count(),       'color' => '#2D9CDB', 'icon' => '<svg width="18" height="18" fill="none" stroke="#2D9CDB" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>'],
            ];
            @endphp
            @foreach($summaryCards as $sc)
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;border:1px solid #f1f5f9;">
                <div style="margin-bottom:6px;line-height:1;">{!! $sc['icon'] !!}</div>
                <div style="font-size:18px;font-weight:800;color:{{ $sc['color'] }};">{{ $sc['val'] }}</div>
                <div style="font-size:11px;color:#64748b;font-weight:600;margin-top:2px;text-transform:uppercase;letter-spacing:0.05em;">{{ $sc['label'] }}</div>
            </div>
            @endforeach
        </div>

        <!-- Livestock by species breakdown -->
        @if($livestock->count() > 0)
        <div style="margin-top:4px;">
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:10px;">Livestock by Species</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                @foreach($livestock->groupBy('species') as $species => $animals)
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:6px 12px;display:flex;align-items:center;gap:6px;">
                    <span style="font-size:14px;font-weight:800;color:#0F6B3E;">{{ $animals->count() }}</span>
                    <span style="font-size:12px;color:#374151;font-weight:600;">{{ $species }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Health status breakdown -->
        @php
        $healthGroups = $livestock->groupBy(fn($a) => strtolower($a->health_status ?? 'healthy'));
        $healthColors = ['healthy' => ['#f0fdf4','#15803d'], 'sick' => ['#fef2f2','#dc2626'], 'recovering' => ['#fef3c7','#92400e'], 'critical' => ['#fef2f2','#7f1d1d']];
        @endphp
        @if($livestock->count() > 0)
        <div style="margin-top:14px;">
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:10px;">Health Status</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                @foreach($healthGroups as $status => $animals)
                @php $hc = $healthColors[$status] ?? ['#f8fafc','#64748b']; @endphp
                <div style="background:{{ $hc[0] }};border-radius:8px;padding:6px 12px;display:flex;align-items:center;gap:6px;">
                    <span style="font-size:14px;font-weight:800;color:{{ $hc[1] }};">{{ $animals->count() }}</span>
                    <span style="font-size:12px;color:{{ $hc[1] }};font-weight:600;">{{ ucfirst($status) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<!-- ── Recent Financial Records Table ───────────────────────────────── -->
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:15px;font-weight:800;color:#0f172a;">Recent Financial Transactions</div>
        <a href="{{ route('farmer.reports.download', ['format' => 'csv', 'type' => 'finance']) }}"
           style="font-size:12px;color:#0F6B3E;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:4px;">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download CSV
        </a>
    </div>
    @if($finances->count())
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:10px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Date</th>
                    <th style="text-align:left;padding:10px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Type</th>
                    <th style="text-align:left;padding:10px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Category</th>
                    <th style="text-align:left;padding:10px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Description</th>
                    <th style="text-align:right;padding:10px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($finances->take(20) as $fin)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:11px 20px;font-size:12px;color:#64748b;">{{ \Carbon\Carbon::parse($fin->transaction_date)->format('M d, Y') }}</td>
                    <td style="padding:11px 14px;">
                        <span style="font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px;
                            background:{{ $fin->type === 'Income' ? '#f0fdf4' : '#fef2f2' }};
                            color:{{ $fin->type === 'Income' ? '#15803d' : '#dc2626' }};">
                            {{ $fin->type }}
                        </span>
                    </td>
                    <td style="padding:11px 14px;font-size:13px;color:#374151;font-weight:500;">{{ $fin->category }}</td>
                    <td style="padding:11px 14px;font-size:12px;color:#64748b;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $fin->description ?? '—' }}</td>
                    <td style="padding:11px 20px;text-align:right;font-size:13px;font-weight:800;color:{{ $fin->type === 'Income' ? '#0F6B3E' : '#dc2626' }};">
                        {{ $fin->type === 'Income' ? '+' : '-' }}₦{{ number_format($fin->amount) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                    <td colspan="4" style="padding:12px 20px;font-size:13px;font-weight:700;color:#374151;">Net Balance</td>
                    <td style="padding:12px 20px;text-align:right;font-size:15px;font-weight:900;color:{{ $totalIncome >= $totalExpense ? '#0F6B3E' : '#dc2626' }};">
                        ₦{{ number_format($totalIncome - $totalExpense) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div style="text-align:center;padding:32px;color:#94a3b8;">No financial records yet.</div>
    @endif
</div>
</x-app-layout>
