<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    @include('ceo.partials.styles')

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    <div class="space-y-4">
        <div class="bi-section-eyebrow">User & Subscription Analytics</div>

        {{-- User registration KPIs --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        @php
        $userKpis = [
            ['New Today',     $newUsersToday, '#16a34a','#f0fdf4', ''],
            ['New This Week', $newUsersWeek,  '#2563eb','#eff6ff', ''],
            ['Total Users',   $totalUsers,    '#0f172a','#f8fafc', ''],
            ['Verified',      $verifiedUsers, '#7c3aed','#f5f3ff', ''],
            ['Verify Rate',   $verifyRate,    ($verifyRate>=80?'#16a34a':($verifyRate>=50?'#d97706':'#dc2626')),'#fff','%'],
        ];
        @endphp
        @foreach($userKpis as [$ul,$uv,$uc,$ubg,$usuf])
        <div class="bi-card text-center" style="background:{{ $ubg }};border-top:3px solid {{ $uc }};padding:14px;">
            <div class="bi-eyebrow">{{ $ul }}</div>
            <div class="bi-num mt-1" style="color:{{ $uc }};font-size:22px;">{{ number_format($uv) }}{{ $usuf }}</div>
        </div>
        @endforeach
        </div>

        {{-- Growth chart + role breakdown --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#0F6B3E;"></span>User Growth (6 Months)</div>
                <div style="height:200px;position:relative;">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#0F6B3E;"></span>Users by Role</div>
                @php
                $roleClrs = ['farmer'=>'#0F6B3E','vet'=>'#2563eb','agronomist'=>'#0D9488','admin'=>'#dc2626','agro-dealer'=>'#d97706','extension-officer'=>'#4f46e5','ceo'=>'#7c3aed','finance'=>'#be185d','hr'=>'#ea580c','general-user'=>'#475569'];
                $roleTotal = max(1, $usersByRole->sum());
                @endphp
                <div class="space-y-2.5">
                @foreach($usersByRole as $role => $cnt)
                @php $pct = round(($cnt/$roleTotal)*100); $clr = $roleClrs[$role] ?? '#94a3b8'; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                        <span style="font-weight:600;color:#374151;text-transform:capitalize;">{{ str_replace('-',' ',$role) }}</span>
                        <span style="font-weight:800;color:{{ $clr }};">{{ $cnt }} <span style="color:#94a3b8;font-weight:400;">({{ $pct }}%)</span></span>
                    </div>
                    <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $clr }};border-radius:3px;"></div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>
        </div>

        {{-- Subscription analytics --}}
        <div class="bi-card">
            <div class="bi-card-title"><span class="bi-dot" style="background:#be185d;"></span>Subscription Analytics</div>

            {{-- Status row --}}
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-5">
            @php
            $subStatusCols = [
                ['Total',    $subStats['total'],     '#0f172a'],
                ['Active',   $subStats['active'],    '#16a34a'],
                ['Trial',    $subStats['trial'],     '#2563eb'],
                ['Expired',  $subStats['expired'],   '#dc2626'],
                ['Cancelled',$subStats['cancelled'], '#94a3b8'],
                ['Suspended',$subStats['suspended'], '#d97706'],
            ];
            @endphp
            @foreach($subStatusCols as [$sl,$sv,$sc])
            <div style="background:#f8fafc;border-radius:10px;padding:10px 8px;text-align:center;border-left:3px solid {{ $sc }};">
                <div style="font-size:20px;font-weight:900;color:{{ $sc }};">{{ $sv }}</div>
                <div style="font-size:9px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:2px;">{{ $sl }}</div>
            </div>
            @endforeach
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Plan doughnut --}}
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <div style="position:relative;width:140px;height:140px;flex-shrink:0;">
                        <canvas id="subPlanDonut"></canvas>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                            <span style="font-size:20px;font-weight:900;color:#0f172a;">{{ array_sum($subStats['by_plan']) ?: $subStats['active'] }}</span>
                            <span style="font-size:10px;color:#94a3b8;">Active</span>
                        </div>
                    </div>
                    <div style="flex:1;min-width:120px;">
                    @php $planDefs=['basic'=>['#16a34a','Basic'],'basic_pro'=>['#0D9488','Basic Pro'],'premium'=>['#2563eb','Premium'],'enterprise'=>['#7c3aed','Enterprise'],'enterprise_plus'=>['#0B2447','Ent. Plus'],'pro'=>['#64748b','Pro']]; @endphp
                    @foreach($planDefs as $pk => [$pc,$pn])
                    @php $cnt2 = $subStats['by_plan'][$pk] ?? 0; @endphp
                    @if($cnt2 > 0)
                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;margin-bottom:7px;">
                        <span style="display:flex;align-items:center;gap:6px;color:#374151;font-weight:500;">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $pc }};flex-shrink:0;display:inline-block;"></span>
                            {{ $pn }}
                        </span>
                        <span style="font-weight:800;color:{{ $pc }};">{{ $cnt2 }}</span>
                    </div>
                    @endif
                    @endforeach
                    </div>
                </div>

                {{-- Plan bars + growth --}}
                <div>
                    <div class="bi-eyebrow" style="margin-bottom:8px;">Subscribers by Plan</div>
                    @php $planColors2 = ['basic'=>['#16a34a','Basic'],'basic_pro'=>['#0D9488','Basic Pro'],'premium'=>['#2563eb','Premium'],'enterprise'=>['#7c3aed','Enterprise'],'enterprise_plus'=>['#0B2447','Ent. Plus'],'pro'=>['#64748b','Pro (Legacy)']]; @endphp
                    @foreach($planColors2 as $pk => [$pc,$pn])
                    @php $cnt3=$subStats['by_plan'][$pk]??0; $subTot=max(1,$subStats['active']+$subStats['trial']); $pct3=round($cnt3/$subTot*100); @endphp
                    @if($cnt3 > 0 || in_array($pk, ['basic','basic_pro','premium']))
                    <div style="margin-bottom:7px;">
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                            <span style="color:#374151;font-weight:600;">{{ $pn }}</span>
                            <span style="font-weight:800;color:{{ $pc }};">{{ $cnt3 }}</span>
                        </div>
                        <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:{{ $pct3 }}%;background:{{ $pc }};border-radius:3px;"></div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                    <div style="margin-top:10px;padding-top:8px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;font-size:12px;">
                        <span style="color:#64748b;">New this month</span>
                        <span style="font-weight:800;color:{{ $subStats['growth_pct']>=0?'#16a34a':'#dc2626' }};">
                            {{ $subStats['new_this_month'] }}
                            ({{ $subStats['growth_pct']>=0?'↑':'↓' }}{{ abs($subStats['growth_pct']) }}%)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>

    <script>
    const _baseOpts = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } };
    function _grad(ctx, top, bot) {
        const { ctx:c, chartArea } = ctx.chart;
        if (!chartArea) return top;
        const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        g.addColorStop(0, top); g.addColorStop(1, bot);
        return g;
    }

    new Chart(document.getElementById('userGrowthChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyGrowth->pluck('label'), JSON_HEX_TAG | JSON_HEX_AMP) !!},
            datasets: [
                {
                    label:'Farmers',
                    data: {!! json_encode($monthlyGrowth->pluck('farmers'), JSON_HEX_TAG | JSON_HEX_AMP) !!},
                    borderColor:'#0F6B3E', borderWidth:2,
                    backgroundColor: ctx => _grad(ctx,'rgba(15,107,62,0.30)','rgba(15,107,62,0.02)'),
                    fill:true, tension:0.4, pointRadius:3, pointBackgroundColor:'#0F6B3E',
                },
                {
                    label:'Experts',
                    data: {!! json_encode($monthlyGrowth->pluck('experts'), JSON_HEX_TAG | JSON_HEX_AMP) !!},
                    borderColor:'#2563eb', borderWidth:2,
                    backgroundColor: ctx => _grad(ctx,'rgba(37,99,235,0.20)','rgba(37,99,235,0.01)'),
                    fill:true, tension:0.4, pointRadius:3, pointBackgroundColor:'#2563eb',
                }
            ]
        },
        options: { ..._baseOpts,
            scales: {
                y: { beginAtZero:true, ticks:{ precision:0, font:{size:10} }, grid:{ color:'#f1f5f9' } },
                x: { ticks:{ font:{size:10} }, grid:{ display:false } }
            },
            plugins: { legend:{ display:true, position:'top', labels:{ font:{size:10}, boxWidth:10, usePointStyle:true } } }
        }
    });

    @php
    $spKeys   = array_keys($subStats['by_plan']);
    $spVals   = array_values($subStats['by_plan']);
    $spNames  = ['basic'=>'Basic','basic_pro'=>'Basic Pro','premium'=>'Premium','enterprise'=>'Enterprise','enterprise_plus'=>'Ent. Plus','pro'=>'Pro'];
    $spClrs   = ['basic'=>'#16a34a','basic_pro'=>'#0D9488','premium'=>'#2563eb','enterprise'=>'#7c3aed','enterprise_plus'=>'#0B2447','pro'=>'#64748b'];
    $spLabels = array_map(fn($k) => $spNames[$k] ?? $k, $spKeys);
    $spColors = array_map(fn($k) => $spClrs[$k]  ?? '#94a3b8', $spKeys);
    @endphp
    @if(!empty($spVals) && array_sum($spVals) > 0)
    new Chart(document.getElementById('subPlanDonut'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($spLabels, JSON_HEX_TAG | JSON_HEX_AMP) !!},
            datasets: [{
                data:            {!! json_encode($spVals,   JSON_HEX_TAG | JSON_HEX_AMP) !!},
                backgroundColor: {!! json_encode($spColors, JSON_HEX_TAG | JSON_HEX_AMP) !!},
                borderWidth: 0, hoverOffset: 6
            }]
        },
        options: { ..._baseOpts, cutout:'68%', plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: c => ` ${c.label}: ${c.parsed}` } } } }
    });
    @endif
    </script>
</x-app-layout>
