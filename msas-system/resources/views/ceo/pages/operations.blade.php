<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    @include('ceo.partials.styles')

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    <x-dashboard-error-banner :errors="$dashboardErrors ?? []" />

    <div class="space-y-4">
        <div class="bi-section-eyebrow">Operations Intelligence</div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            {{-- Logistics --}}
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#ea580c;"></span>Logistics & Delivery</div>
                <div class="grid grid-cols-2 gap-2 mb-4">
                @php
                $logCols = [
                    ['Pending Dispatch', $logisticsStats['pending_dispatch'], '#d97706','#fffbeb'],
                    ['Riders Available', $logisticsStats['riders_available'], '#16a34a','#f0fdf4'],
                    ['In Transit',       $logisticsStats['in_transit'],       '#2563eb','#eff6ff'],
                    ['Delivered Total',  $logisticsStats['delivered'],         '#0f172a','#f8fafc'],
                ];
                @endphp
                @foreach($logCols as [$ll,$lv,$lc,$lbg])
                <div style="background:{{ $lbg }};border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">{{ $ll }}</div>
                    <div style="font-size:22px;font-weight:900;color:{{ $lc }};">{{ $lv }}</div>
                </div>
                @endforeach
                </div>
                @php $totalRiders = max(1, $logisticsStats['riders_available'] + $logisticsStats['riders_busy']); @endphp
                <div style="padding-top:10px;border-top:1px solid #f1f5f9;">
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px;">
                        <span style="color:#64748b;">Rider utilisation</span>
                        <span style="font-weight:800;color:#ea580c;">{{ round($logisticsStats['riders_busy']/$totalRiders*100) }}% active</span>
                    </div>
                    <div style="height:7px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
                        <div style="height:100%;background:linear-gradient(90deg,#ea580c,#f97316);border-radius:4px;width:{{ round($logisticsStats['riders_busy']/$totalRiders*100) }}%;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:#94a3b8;margin-top:4px;">
                        <span>{{ $logisticsStats['riders_busy'] }} busy</span>
                        <span>{{ $logisticsStats['riders_available'] }} available</span>
                        <span>{{ $totalRiders }} total</span>
                    </div>
                </div>
            </div>

            {{-- Consultations --}}
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#2563eb;"></span>Expert Consultations</div>
                <div class="grid grid-cols-2 gap-2 mb-4">
                @php
                $cCols = [
                    ['Pending',      $consultStats['pending'],     '#d97706','#fffbeb'],
                    ['In Progress',  $consultStats['in_progress'], '#2563eb','#eff6ff'],
                    ['Completed',    $consultStats['completed'],   '#16a34a','#f0fdf4'],
                    ['Avg Response', (round($consultStats['avg_hours']??0,1)).'h', '#7c3aed','#f5f3ff'],
                ];
                @endphp
                @foreach($cCols as [$cl,$cv,$cc,$cbg])
                <div style="background:{{ $cbg }};border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">{{ $cl }}</div>
                    <div style="font-size:22px;font-weight:900;color:{{ $cc }};">{{ $cv }}</div>
                </div>
                @endforeach
                </div>
                @php $cTotal = max(1,$consultStats['pending']+$consultStats['in_progress']+$consultStats['completed']); @endphp
                <div style="padding-top:10px;border-top:1px solid #f1f5f9;space-y:4px;">
                @foreach([['Pending',$consultStats['pending'],'#d97706'],['In Progress',$consultStats['in_progress'],'#2563eb'],['Completed',$consultStats['completed'],'#16a34a']] as [$rl,$rv,$rc])
                @php $rp = round($rv/$cTotal*100); @endphp
                <div style="margin-bottom:6px;">
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                        <span style="color:#64748b;">{{ $rl }}</span>
                        <span style="font-weight:700;color:{{ $rc }};">{{ $rv }}</span>
                    </div>
                    <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $rp }}%;background:{{ $rc }};border-radius:3px;"></div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>
        </div>

        {{-- Staff & HR quick panel --}}
        <div class="bi-card">
            <div class="bi-card-title"><span class="bi-dot" style="background:#475569;"></span>Staff & HR</div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                @php
                $hrKpis = [
                    ['Staff Present',    "{$presentToday} / {$staffCount}", '#0f172a','#f8fafc'],
                    ['Pending Leave',    $pendingLeaves,  $pendingLeaves>0?'#d97706':'#16a34a', $pendingLeaves>0?'#fffbeb':'#f0fdf4'],
                    ['Expert Approvals', $pendingExperts, $pendingExperts>0?'#d97706':'#16a34a', $pendingExperts>0?'#fffbeb':'#f0fdf4'],
                    ['Pending Listings', $pendingListings,$pendingListings>0?'#d97706':'#475569',$pendingListings>0?'#fffbeb':'#f8fafc'],
                ];
                @endphp
                @foreach($hrKpis as [$hl,$hv,$hc,$hbg])
                <div style="background:{{ $hbg }};border-radius:10px;padding:14px;text-align:center;">
                    <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">{{ $hl }}</div>
                    <div style="font-size:20px;font-weight:900;color:{{ $hc }};">{{ $hv }}</div>
                </div>
                @endforeach
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @php
                $quickLinks = [
                    [route('ceo.staff.create'),       'Add Staff',    '#16a34a','#f0fdf4','M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                    [route('ceo.staff.index'),         'All Staff',    '#475569','#f8fafc','M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    [route('ceo.staff-roles.index'),   'Roles & Perms','#2563eb','#eff6ff','M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                    [route('ceo.audit'),               'Audit Log',    '#d97706','#fffbeb','M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                ];
                @endphp
                @foreach($quickLinks as [$ql_href,$ql_label,$ql_c,$ql_bg,$ql_icon])
                <a href="{{ $ql_href }}" style="display:flex;flex-direction:column;align-items:center;padding:14px;background:{{ $ql_bg }};border-radius:10px;text-decoration:none;transition:opacity .15s;" onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                    <svg style="width:22px;height:22px;color:{{ $ql_c }};" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ql_icon }}"/></svg>
                    <span style="font-size:11px;font-weight:700;color:{{ $ql_c }};margin-top:5px;">{{ $ql_label }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    </div>
</x-app-layout>
