<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    @include('ceo.partials.styles')

    <style>
    .aa-badge { font-size:10px; font-weight:700; padding:2px 9px; border-radius:99px; display:inline-flex; align-items:center; white-space:nowrap; }
    .aa-input { font-size:12px; border:1px solid #e2e8f0; border-radius:8px; padding:7px 10px; width:100%; background:#fff; }
    .aa-label { font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; display:block; }
    .aa-table { width:100%; font-size:12px; border-collapse:collapse; }
    .aa-table th { text-align:left; padding:0 10px 8px 0; font-size:9px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
    .aa-table td { padding:9px 10px 9px 0; border-top:1px solid #f8fafc; white-space:nowrap; }
    </style>

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    <div class="bi-section-eyebrow">AI Smart Scan Analytics</div>

    {{-- ═══════════════ Headline (unfiltered, always-on reference) ═══════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    @php
    $headlineKpis = [
        ['Scans Today',   number_format($summary['today']), '#0D9488','#f0fdfa'],
        ['Scans 7 Days',  number_format($summary['week']),  '#2563eb','#eff6ff'],
        ['Scans 30 Days', number_format($summary['month']), '#7c3aed','#f5f3ff'],
        ['All-Time Total',number_format($summary['total']), '#0f172a','#f8fafc'],
    ];
    @endphp
    @foreach($headlineKpis as [$hl,$hv,$hc,$hbg])
    <div class="bi-card text-center" style="background:{{ $hbg }};border-top:3px solid {{ $hc }};padding:14px;">
        <div class="bi-eyebrow">{{ $hl }}</div>
        <div class="bi-num mt-1" style="color:{{ $hc }};font-size:22px;">{{ $hv }}</div>
    </div>
    @endforeach
    </div>

    {{-- ═══════════════ Filters ═══════════════ --}}
    <div class="bi-card">
        <div class="bi-card-title"><span class="bi-dot" style="background:#0F6B3E;"></span>Filters</div>
        <form method="GET" action="{{ route('ceo.ai-analytics') }}" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="aa-label">Date Range</label>
                <select name="range" class="aa-input" onchange="this.form.submit()">
                    @foreach(['30d'=>'Last 30 Days','7d'=>'Last 7 Days','today'=>'Today','yesterday'=>'Yesterday','this_month'=>'This Month','last_month'=>'Last Month','custom'=>'Custom'] as $rv=>$rl)
                    <option value="{{ $rv }}" @selected(request('range','30d')===$rv)>{{ $rl }}</option>
                    @endforeach
                </select>
            </div>
            @if(request('range')==='custom')
            <div>
                <label class="aa-label">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="aa-input">
            </div>
            <div>
                <label class="aa-label">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="aa-input">
            </div>
            @endif
            <div>
                <label class="aa-label">State</label>
                <select name="state" class="aa-input" onchange="var l=this.form.querySelector('select[name=lga]'); if(l) l.value=''; this.form.submit()">
                    <option value="">All States</option>
                    @foreach($states as $st)
                    <option value="{{ $st }}" @selected(request('state')===$st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="aa-label">LGA</label>
                <select name="lga" class="aa-input" @if(!request('state')) disabled @endif>
                    <option value="">All LGAs</option>
                    @foreach($lgasForState as $lg)
                    <option value="{{ $lg }}" @selected(request('lga')===$lg)>{{ $lg }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="aa-label">Crop / Subject</label>
                <input type="text" name="crop" value="{{ request('crop') }}" placeholder="e.g. Maize" class="aa-input">
            </div>
            <div>
                <label class="aa-label">Diagnosis</label>
                <input type="text" name="diagnosis" value="{{ request('diagnosis') }}" placeholder="e.g. Blight" class="aa-input">
            </div>
            <div>
                <label class="aa-label">Confidence</label>
                <select name="confidence" class="aa-input">
                    <option value="">Any</option>
                    <option value="high"   @selected(request('confidence')==='high')>High (&ge;80%)</option>
                    <option value="medium" @selected(request('confidence')==='medium')>Medium (60&ndash;79%)</option>
                    <option value="low"    @selected(request('confidence')==='low')>Low (&lt;60%)</option>
                </select>
            </div>
            <div>
                <label class="aa-label">Status</label>
                <select name="status" class="aa-input">
                    <option value="">Any</option>
                    <option value="completed"      @selected(request('status')==='completed')>Completed</option>
                    <option value="low_confidence" @selected(request('status')==='low_confidence')>Low Confidence</option>
                    <option value="processing"     @selected(request('status')==='processing')>Processing</option>
                    <option value="failed"         @selected(request('status')==='failed')>Failed</option>
                </select>
            </div>
            <div>
                <label class="aa-label">User</label>
                <input type="text" name="user" value="{{ request('user') }}" placeholder="Name or email" class="aa-input">
            </div>
            <div>
                <label class="aa-label">Scan ID</label>
                <input type="text" name="scan_ref" value="{{ request('scan_ref') }}" placeholder="MSAS-SCN-..." class="aa-input">
            </div>
            <div class="flex items-end gap-2 col-span-2 sm:col-span-1">
                <button type="submit" class="text-xs font-semibold text-white bg-[#0F6B3E] hover:bg-[#0B2447] px-4 py-2 rounded-lg transition-colors w-full">Apply Filters</button>
            </div>
            <div class="flex items-end gap-2">
                <a href="{{ route('ceo.ai-analytics') }}" class="text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-lg transition-colors w-full text-center">Reset</a>
            </div>
            <div class="flex items-end gap-2">
                <a href="{{ route('ceo.ai-analytics.export', request()->query()) }}" class="text-xs font-semibold text-white bg-[#4f46e5] hover:bg-[#4338ca] px-4 py-2 rounded-lg transition-colors w-full text-center">Export CSV →</a>
            </div>
        </form>
    </div>

    {{-- ═══════════════ Filtered Results ═══════════════ --}}
    <div>
        <div class="bi-section-eyebrow">Filtered Results ({{ number_format($filteredCount) }} scans matching current filters)</div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Status split --}}
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#0D9488;"></span>Status Breakdown</div>
                @php
                $statusColors = ['Completed'=>'#16a34a','Low Confidence'=>'#d97706','Processing'=>'#2563eb','Failed'=>'#dc2626','Unknown'=>'#94a3b8'];
                $statusTotal  = max(1, $statusBreakdown->sum());
                @endphp
                @forelse($statusBreakdown as $label => $cnt)
                @php $pct = round($cnt/$statusTotal*100); $clr = $statusColors[$label] ?? '#94a3b8'; @endphp
                <div style="margin-bottom:8px;">
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                        <span style="color:#374151;font-weight:600;">{{ $label }}</span>
                        <span style="font-weight:800;color:{{ $clr }};">{{ $cnt }} ({{ $pct }}%)</span>
                    </div>
                    <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $clr }};border-radius:3px;"></div>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:24px;color:#94a3b8;font-size:13px;">No scans match these filters</div>
                @endforelse
            </div>

            {{-- Confidence + processing time --}}
            <div class="bi-card text-center">
                <div class="bi-card-title" style="justify-content:center;"><span class="bi-dot" style="background:#0D9488;"></span>Avg Confidence (Filtered)</div>
                @php $fc = $filteredAvgConf; $fcClr = $fc>=80?'#16a34a':($fc>=60?'#d97706':'#dc2626'); @endphp
                <div class="bi-num" style="color:{{ $fcClr }};font-size:32px;">{{ $fc }}%</div>
                <div class="bi-sub" style="margin-top:8px;">Avg processing time: <strong>{{ $filteredAvgMinutes }} min</strong></div>
            </div>

            {{-- Top crops --}}
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#0F6B3E;"></span>Top Crops/Subjects</div>
                @forelse($topCrops as $tc)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;':'' }}">
                    <span style="font-size:12px;font-weight:600;color:#374151;">{{ Str::limit($tc->subject_name, 24) }}</span>
                    <span style="font-size:11px;font-weight:800;color:#0F6B3E;background:#f0fdf4;padding:2px 8px;border-radius:99px;">{{ $tc->cnt }}</span>
                </div>
                @empty
                <div style="text-align:center;padding:16px;color:#94a3b8;font-size:13px;">No data</div>
                @endforelse
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" style="margin-top:16px;">
            {{-- Daily chart --}}
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#2563eb;"></span>Scans Per Day</div>
                <div style="height:200px;position:relative;">
                    <canvas id="dailyScanChart"></canvas>
                </div>
            </div>

            {{-- Top diagnoses --}}
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#ef4444;"></span>Top Diagnoses</div>
                @forelse($topDiagnoses as $td)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;':'' }}">
                    <span style="font-size:12px;font-weight:600;color:#374151;">{{ Str::limit($td->disease_name, 28) }}</span>
                    <span style="font-size:11px;font-weight:800;color:#dc2626;background:#fef2f2;padding:2px 8px;border-radius:99px;">{{ $td->cnt }}</span>
                </div>
                @empty
                <div style="text-align:center;padding:16px;color:#94a3b8;font-size:13px;">No data</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ═══════════════ Geographic drill-down: State → LGA ═══════════════ --}}
    <div class="bi-card">
        <div class="bi-card-title">
            <span class="bi-dot" style="background:#7c3aed;"></span>
            @if(request('state'))
                LGA Breakdown — {{ request('state') }}
                <a href="{{ request()->fullUrlWithQuery(['state' => null, 'lga' => null]) }}" style="margin-left:auto;font-size:11px;font-weight:700;color:#7c3aed;text-decoration:none;">← Back to States</a>
            @else
                State Breakdown — click a state to drill into LGAs
            @endif
        </div>
        @php $rows = request('state') ? $lgaBreakdown : $stateBreakdown; $rowKey = request('state') ? 'lga' : 'state'; @endphp
        @if($rows->isEmpty())
        <div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">No geographic data for these filters</div>
        @else
        @php $maxRow = max($rows->pluck('cnt')->toArray() ?: [1]); @endphp
        <div class="space-y-2.5">
        @foreach($rows as $row)
        @php
        $name = $row->{$rowKey};
        $cnt  = $row->cnt;
        $pct  = $maxRow > 0 ? round($cnt/$maxRow*100) : 0;
        $href = request('state')
            ? request()->fullUrlWithQuery(['lga' => $name])
            : request()->fullUrlWithQuery(['state' => $name]);
        @endphp
        <a href="{{ $href }}" style="display:block;text-decoration:none;">
            <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                <span style="font-weight:600;color:#374151;">{{ $name }}</span>
                <span style="font-weight:800;color:#7c3aed;">{{ $cnt }}</span>
            </div>
            <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,#7c3aed,#6d28d9);border-radius:3px;"></div>
            </div>
        </a>
        @endforeach
        </div>
        @endif
    </div>

    {{-- ═══════════════ Scan Table ═══════════════ --}}
    <div class="bi-card">
        <div class="bi-card-title"><span class="bi-dot" style="background:#0f172a;"></span>Scan Records</div>
        <div style="overflow-x:auto;">
        <table class="aa-table">
            <thead>
                <tr>
                    <th>Scan ID</th><th>Date/Time</th><th>User</th><th>State</th><th>LGA</th>
                    <th>Crop/Subject</th><th>Diagnosis</th><th>Confidence</th><th>Severity</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($scans as $scan)
            @php
            $conf = $scan->confidence_score;
            $confClr = $conf === null ? '#94a3b8' : ($conf>=65?'#16a34a':'#dc2626');
            $displayStatus = $scan->statusLabel;
            $statusColors2 = ['Completed'=>['#f0fdf4','#16a34a'],'Low Confidence'=>['#fffbeb','#d97706'],'Pending Review'=>['#eff6ff','#2563eb'],'Failed'=>['#fef2f2','#dc2626'],'Unknown'=>['#f8fafc','#94a3b8']];
            [$sBg, $sClr] = $statusColors2[$displayStatus] ?? ['#f8fafc','#94a3b8'];
            @endphp
            <tr>
                <td style="color:#94a3b8;font-family:monospace;">{{ $scan->scan_ref ?? '#'.$scan->id }}</td>
                <td style="color:#64748b;">{{ optional($scan->created_at)->timezone('Africa/Lagos')->format('d M Y, H:i') }}</td>
                <td style="font-weight:700;color:#0f172a;">{{ trim(($scan->user_first_name ?? '').' '.($scan->user_last_name ?? '')) ?: '—' }}</td>
                <td style="color:#64748b;">{{ $scan->user_state ?? '—' }}</td>
                <td style="color:#64748b;">{{ $scan->user_lga ?? '—' }}</td>
                <td style="color:#374151;">{{ Str::limit($scan->subject_name ?? '—', 22) }}</td>
                <td style="color:#374151;">{{ Str::limit($scan->disease_name ?? '—', 26) }}</td>
                <td>@if($conf!==null)<span class="aa-badge" style="background:{{ $confClr }}1A;color:{{ $confClr }};">{{ number_format($conf, 0) }}%</span>@else <span style="color:#cbd5e1;">N/A</span>@endif</td>
                <td style="color:#64748b;text-transform:capitalize;">{{ $scan->severity_level ?? '—' }}</td>
                <td><span class="aa-badge" style="background:{{ $sBg }};color:{{ $sClr }};">{{ $displayStatus }}</span></td>
                <td><a href="{{ route('diagnostics.report', $scan->id) }}" target="_blank" style="font-size:11px;font-weight:700;color:#0F6B3E;text-decoration:none;">View →</a></td>
            </tr>
            @empty
            <tr><td colspan="11" style="padding:24px;text-align:center;color:#94a3b8;">No scans match these filters</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div style="margin-top:14px;">
            {{ $scans->links() }}
        </div>
    </div>

    </div>

    <script>
    const dailyLabels = {!! json_encode($dailySeries->pluck('label'), JSON_HEX_TAG | JSON_HEX_AMP) !!};
    const dailyValues = {!! json_encode($dailySeries->pluck('value'), JSON_HEX_TAG | JSON_HEX_AMP) !!};
    new Chart(document.getElementById('dailyScanChart'), {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [{ label: 'Scans', data: dailyValues, backgroundColor: 'rgba(37,99,235,0.75)', borderRadius: 4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: '#f1f5f9' } }
            }
        }
    });
    </script>
</x-app-layout>
