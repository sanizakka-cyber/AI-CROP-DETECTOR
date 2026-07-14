<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MSAS Farm Report — {{ ucfirst($type) }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size:13px; color:#1e293b; background:#fff; padding:0; }

        /* ── Print styles ── */
        @media print {
            .no-print { display:none !important; }
            body { padding:0; }
            @page { margin: 15mm 12mm; size: A4; }
        }

        /* ── Screen preview ── */
        @media screen {
            body { padding:24px; background:#f1f5f9; }
            .page { max-width:900px; margin:0 auto; background:#fff; box-shadow:0 4px 24px rgba(0,0,0,0.1); border-radius:8px; overflow:hidden; }
        }

        /* ── Header ── */
        .header { background:linear-gradient(135deg,#0B2447,#0F6B3E); color:#fff; padding:28px 32px; display:flex; justify-content:space-between; align-items:flex-start; }
        .header-left .org { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; opacity:0.7; margin-bottom:4px; }
        .header-left .title { font-size:22px; font-weight:900; margin-bottom:3px; }
        .header-left .sub   { font-size:12px; opacity:0.6; }
        .header-right { text-align:right; }
        .header-right .date { font-size:12px; opacity:0.6; margin-bottom:4px; }
        .header-right .plan { font-size:11px; font-weight:800; background:rgba(244,163,0,0.25); border:1px solid rgba(244,163,0,0.5); color:#F4A300; padding:3px 10px; border-radius:20px; display:inline-block; }

        /* ── Farmer info bar ── */
        .farmer-bar { background:#f8fafc; border-bottom:2px solid #e2e8f0; padding:14px 32px; display:flex; gap:28px; flex-wrap:wrap; }
        .farmer-bar .fi { }
        .farmer-bar .fi .fl { font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.08em; }
        .farmer-bar .fi .fv { font-size:13px; font-weight:700; color:#0f172a; margin-top:2px; }

        /* ── Section heading ── */
        .section-head { padding:14px 32px 10px; background:#f1f5f9; border-bottom:1px solid #e2e8f0; margin-top:20px; }
        .section-head h2 { font-size:14px; font-weight:800; color:#0f172a; }

        /* ── Tables ── */
        .content { padding:0 32px 24px; }
        table { width:100%; border-collapse:collapse; margin-top:12px; font-size:12px; }
        thead tr { background:#0F6B3E; color:#fff; }
        thead th { padding:9px 10px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; }
        tbody tr { border-bottom:1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tbody td { padding:9px 10px; vertical-align:middle; }
        tfoot tr { background:#f1f5f9; border-top:2px solid #e2e8f0; }
        tfoot td { padding:10px 10px; font-weight:800; font-size:13px; }

        /* ── Summary grid ── */
        .summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; padding:16px 32px; }
        .stat-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; }
        .stat-card .sl { font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px; }
        .stat-card .sv { font-size:18px; font-weight:900; color:#0F6B3E; }

        /* ── Badge ── */
        .badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:800; }
        .badge-green  { background:#f0fdf4; color:#15803d; }
        .badge-red    { background:#fef2f2; color:#dc2626; }
        .badge-blue   { background:#eff6ff; color:#1d4ed8; }
        .badge-amber  { background:#fef3c7; color:#92400e; }
        .badge-purple { background:#f5f3ff; color:#7c3aed; }

        /* ── Footer ── */
        .footer { padding:14px 32px; background:#0B2447; color:rgba(255,255,255,0.5); font-size:10px; text-align:center; margin-top:20px; }
        .footer strong { color:rgba(255,255,255,0.8); }

        /* ── Print button ── */
        .print-bar { background:#fff; padding:12px 24px; display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; border-radius:8px; border:1px solid #e2e8f0; }
        .print-btn { background:#0F6B3E; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
        .back-btn  { color:#64748b; text-decoration:none; font-size:13px; }
    </style>
</head>
<body>

<!-- Screen-only controls -->
<div class="no-print print-bar">
    <a href="{{ route('farmer.reports') }}" class="back-btn">← Back to Reports</a>
    <div style="font-size:13px;color:#64748b;">
        Press <strong>Ctrl+P</strong> to print or save as PDF
    </div>
    <button onclick="window.print()" class="print-btn">🖨 Print / Save PDF</button>
</div>

<div class="page">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="org">MSAS — Livestock & Agro Services</div>
            <div class="title">
                @if($type === 'livestock')     Livestock Report
                @elseif($type === 'finance')   Financial Report
                @elseif($type === 'consultations') Veterinary Consultations Report
                @elseif($type === 'poultry')   Poultry Report
                @else All-Farm Report
                @endif
            </div>
            <div class="sub">Generated {{ now()->format('F d, Y \a\t g:i A') }}</div>
        </div>
        <div class="header-right">
            <div class="date">Report Period: All Time</div>
            <div class="plan">{{ strtoupper(auth()->user()->activeSubscription()?->plan ?? 'pro') }} PLAN</div>
        </div>
    </div>

    <!-- Farmer Info -->
    <div class="farmer-bar">
        <div class="fi"><div class="fl">Farmer</div><div class="fv">{{ $user->name }}</div></div>
        <div class="fi"><div class="fl">Phone</div><div class="fv">{{ $user->phone ?? '—' }}</div></div>
        <div class="fi"><div class="fl">Email</div><div class="fv">{{ $user->email ?? '—' }}</div></div>
        <div class="fi"><div class="fl">Report ID</div><div class="fv">RPT-{{ now()->format('Ymd') }}-{{ $user->id }}</div></div>
    </div>

    @if($type === 'livestock' || $type === 'all')
    <!-- Livestock Summary -->
    <div class="summary-grid">
        <div class="stat-card"><div class="sl">Total Animals</div><div class="sv">{{ $livestock->count() }}</div></div>
        <div class="stat-card"><div class="sl">Healthy</div><div class="sv" style="color:#15803d;">{{ $livestock->filter(fn($a)=>strtolower($a->health_status??'')=='healthy')->count() }}</div></div>
        <div class="stat-card"><div class="sl">Sick / Critical</div><div class="sv" style="color:#dc2626;">{{ $livestock->filter(fn($a)=>in_array(strtolower($a->health_status??''),['sick','critical']))->count() }}</div></div>
        <div class="stat-card"><div class="sl">Species Count</div><div class="sv" style="color:#2D9CDB;">{{ $livestock->pluck('species')->unique()->count() }}</div></div>
    </div>

    <div class="section-head"><h2>Livestock Records ({{ $livestock->count() }})</h2></div>
    <div class="content">
        @if($livestock->count())
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Tag No.</th><th>Name</th><th>Species</th>
                    <th>Breed</th><th>Gender</th><th>Weight (kg)</th>
                    <th>DOB</th><th>Health</th><th>Added</th>
                </tr>
            </thead>
            <tbody>
                @foreach($livestock as $i => $a)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $a->tag_number ?? '—' }}</strong></td>
                    <td>{{ $a->name ?? 'Unnamed' }}</td>
                    <td>{{ $a->species ?? '—' }}</td>
                    <td>{{ $a->breed ?? '—' }}</td>
                    <td>{{ $a->gender ?? '—' }}</td>
                    <td>{{ $a->weight_kg ?? '—' }}</td>
                    <td>{{ $a->date_of_birth ? \Carbon\Carbon::parse($a->date_of_birth)->format('M d, Y') : '—' }}</td>
                    <td>
                        @php $hs = strtolower($a->health_status ?? 'healthy'); @endphp
                        <span class="badge {{ $hs === 'healthy' ? 'badge-green' : ($hs === 'sick' ? 'badge-red' : 'badge-amber') }}">
                            {{ ucfirst($hs) }}
                        </span>
                    </td>
                    <td>{{ $a->created_at->format('M d, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#94a3b8;padding:16px 0;text-align:center;">No livestock records found.</p>
        @endif
    </div>
    @endif

    @if($type === 'finance' || $type === 'all')
    <!-- Finance Summary -->
    <div class="summary-grid">
        <div class="stat-card"><div class="sl">Total Income</div><div class="sv">₦{{ number_format($finances->where('type','Income')->sum('amount')) }}</div></div>
        <div class="stat-card"><div class="sl">Total Expenses</div><div class="sv" style="color:#dc2626;">₦{{ number_format($finances->where('type','Expense')->sum('amount')) }}</div></div>
        <div class="stat-card"><div class="sl">Net Balance</div><div class="sv" style="color:{{ $finances->where('type','Income')->sum('amount') >= $finances->where('type','Expense')->sum('amount') ? '#0F6B3E' : '#dc2626' }};">₦{{ number_format($finances->where('type','Income')->sum('amount') - $finances->where('type','Expense')->sum('amount')) }}</div></div>
        <div class="stat-card"><div class="sl">Transactions</div><div class="sv" style="color:#2D9CDB;">{{ $finances->count() }}</div></div>
    </div>

    <div class="section-head"><h2>Financial Records ({{ $finances->count() }})</h2></div>
    <div class="content">
        @if($finances->count())
        <table>
            <thead>
                <tr><th>#</th><th>Date</th><th>Type</th><th>Category</th><th>Description</th><th style="text-align:right;">Amount (₦)</th></tr>
            </thead>
            <tbody>
                @foreach($finances as $i => $f)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($f->transaction_date)->format('M d, Y') }}</td>
                    <td><span class="badge {{ $f->type === 'Income' ? 'badge-green' : 'badge-red' }}">{{ $f->type }}</span></td>
                    <td>{{ $f->category }}</td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $f->description ?? '—' }}</td>
                    <td style="text-align:right;font-weight:700;color:{{ $f->type === 'Income' ? '#0F6B3E' : '#dc2626' }};">
                        {{ $f->type === 'Income' ? '+' : '-' }}{{ number_format($f->amount) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Net Balance</td>
                    <td style="text-align:right;color:{{ $finances->where('type','Income')->sum('amount') >= $finances->where('type','Expense')->sum('amount') ? '#0F6B3E' : '#dc2626' }};">
                        ₦{{ number_format($finances->where('type','Income')->sum('amount') - $finances->where('type','Expense')->sum('amount')) }}
                    </td>
                </tr>
            </tfoot>
        </table>
        @else
        <p style="color:#94a3b8;padding:16px 0;text-align:center;">No financial records found.</p>
        @endif
    </div>
    @endif

    @if($type === 'consultations' || $type === 'all')
    <div class="section-head"><h2>Veterinary Consultations ({{ $consultations->count() }})</h2></div>
    <div class="content">
        @if($consultations->count())
        <table>
            <thead>
                <tr><th>#</th><th>Date</th><th>Animal Type</th><th>Symptoms</th><th>Priority</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($consultations as $i => $c)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $c->created_at->format('M d, Y') }}</td>
                    <td>{{ ucfirst($c->animal_type ?? '—') }}</td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $c->symptoms ?? '—' }}</td>
                    <td>
                        @php $p = $c->priority ?? 'low'; @endphp
                        <span class="badge {{ in_array($p,['high','critical']) ? 'badge-red' : ($p === 'medium' ? 'badge-amber' : 'badge-blue') }}">
                            {{ ucfirst($p) }}
                        </span>
                    </td>
                    <td>
                        @php $st = $c->status ?? 'pending'; @endphp
                        <span class="badge {{ $st === 'resolved' ? 'badge-green' : ($st === 'pending' ? 'badge-amber' : 'badge-blue') }}">
                            {{ ucfirst($st) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#94a3b8;padding:16px 0;text-align:center;">No consultations found.</p>
        @endif
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <strong>MSAS — Livestock & Agro Services Management System</strong> &nbsp;|&nbsp;
        Confidential — for {{ $user->name }} only &nbsp;|&nbsp;
        Generated {{ now()->format('F d, Y \a\t g:i A') }} &nbsp;|&nbsp;
        Page 1
    </div>
</div>

</body>
</html>
