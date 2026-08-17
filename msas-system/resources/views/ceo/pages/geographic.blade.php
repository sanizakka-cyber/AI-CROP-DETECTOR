<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    @include('ceo.partials.styles')

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    <x-dashboard-error-banner :errors="$dashboardErrors ?? []" />

    <div class="space-y-4">
        <div class="bi-section-eyebrow">Geographic Intelligence</div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#7c3aed;"></span>Top States — Users &amp; AI Scans</div>
                <div style="height:200px;position:relative;">
                    <canvas id="geoBarChart"></canvas>
                </div>
                @if($geoChart->isEmpty())
                <p style="font-size:13px;color:#94a3b8;text-align:center;padding:12px;">No state data yet</p>
                @endif
            </div>
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#7c3aed;"></span>State Activity Ranking</div>
                @if(!empty($stateActivity))
                @php $maxState = max($stateActivity ?: [1]); @endphp
                <div class="space-y-2.5">
                @foreach($stateActivity as $state => $cnt)
                @php $sp = $maxState > 0 ? round(($cnt/$maxState)*100) : 0; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                        <span style="font-weight:600;color:#374151;">{{ $state }}</span>
                        <span style="font-weight:800;color:#7c3aed;">{{ $cnt }}</span>
                    </div>
                    <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $sp }}%;background:linear-gradient(90deg,#7c3aed,#6d28d9);border-radius:3px;"></div>
                    </div>
                </div>
                @endforeach
                </div>
                @else
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:120px;color:#cbd5e1;">
                    <svg style="width:36px;height:36px;margin-bottom:8px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    <span style="font-size:13px;">No state data yet</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    </div>

    <script>
    const _baseOpts = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } };
    @if($geoChart->isNotEmpty())
    new Chart(document.getElementById('geoBarChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($geoChart->keys()->toArray(), JSON_HEX_TAG | JSON_HEX_AMP) !!},
            datasets: [
                { label:'Users', data:{!! json_encode($geoChart->pluck('users')->toArray(),     JSON_HEX_TAG|JSON_HEX_AMP) !!}, backgroundColor:'rgba(15,107,62,0.8)',  borderRadius:4 },
                { label:'Scans', data:{!! json_encode($geoChart->pluck('diagnoses')->toArray(), JSON_HEX_TAG|JSON_HEX_AMP) !!}, backgroundColor:'rgba(37,99,235,0.65)', borderRadius:4 },
            ]
        },
        options: { ..._baseOpts,
            scales: {
                x: { ticks:{ font:{size:10} }, grid:{ display:false } },
                y: { beginAtZero:true, ticks:{ precision:0, font:{size:10} }, grid:{ color:'#f1f5f9' } }
            },
            plugins: { legend:{ display:true, position:'top', labels:{ font:{size:10}, boxWidth:10, usePointStyle:true } } }
        }
    });
    @endif
    </script>
</x-app-layout>
