<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    @include('ceo.partials.styles')

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    <x-dashboard-error-banner :errors="$dashboardErrors ?? []" />

    <div class="space-y-4">
        <div class="bi-section-eyebrow">System Health & Recent Activity</div>

        {{-- Performance gauges --}}
        <div class="bi-card">
            <div class="bi-card-title"><span class="bi-dot" style="background:#475569;"></span>Platform Performance Indicators</div>
            @php
            $gauges = [
                ['Platform Health',        $platformHealth,            90, '#0F6B3E', false],
                ['Case Resolution Rate',   $resolutionRate,            85, '#2563eb', false],
                ['Active User Rate',       $activePct,                 80, '#0D9488', false],
                ['Expert Approval Pending',min(100,$pendingExperts*10),10, '#d97706', true],
                ['Market Listings Active', min(100,$marketItems*5),    50, '#4f46e5', false],
            ];
            @endphp
            <div class="space-y-3.5">
            @foreach($gauges as [$gl,$gv,$gt,$gc,$ginv])
            @php $gp=min(100,$gv); $gok=$ginv?$gp<=$gt:$gp>=$gt; @endphp
            <div>
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px;">
                    <span style="font-weight:600;color:#374151;">{{ $gl }}</span>
                    <span style="font-weight:800;color:{{ $gok?'#16a34a':'#d97706' }};">{{ $gv }}%
                        <span style="font-size:10px;color:#94a3b8;font-weight:400;">/ {{ $gt }}% target</span>
                    </span>
                </div>
                <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
                    <div style="height:100%;background:{{ $gc }};border-radius:4px;width:{{ $gp }}%;"></div>
                </div>
            </div>
            @endforeach
            </div>
        </div>

        {{-- Recent registrations + Disease alerts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <div class="bi-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <div class="bi-card-title" style="margin-bottom:0;"><span class="bi-dot" style="background:#0F6B3E;"></span>Recent Registrations</div>
                    <a href="{{ route('ceo.users') }}" style="font-size:11px;font-weight:700;color:#0F6B3E;text-decoration:none;">View all →</a>
                </div>
                <div style="overflow-x:auto;">
                <table style="width:100%;font-size:12px;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid #f1f5f9;">
                            <th style="text-align:left;padding:0 8px 8px 0;font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">Name</th>
                            <th style="text-align:left;padding:0 8px 8px 0;font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">Role</th>
                            <th style="text-align:left;padding:0 8px 8px 0;font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">Joined</th>
                            <th style="text-align:left;padding:0 0 8px 0;font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentUsers as $u)
                    <tr style="border-bottom:1px solid #f8fafc;">
                        <td style="padding:8px 8px 8px 0;font-weight:700;color:#0f172a;">{{ $u->first_name }} {{ $u->last_name }}</td>
                        <td style="padding:8px 8px 8px 0;">
                            <span style="background:#f0fdf4;color:#16a34a;border-radius:99px;padding:2px 7px;font-size:10px;font-weight:700;text-transform:capitalize;white-space:nowrap;">{{ str_replace('-',' ',$u->role) }}</span>
                        </td>
                        <td style="padding:8px 8px 8px 0;color:#94a3b8;white-space:nowrap;">{{ $u->created_at->format('d M Y') }}</td>
                        <td style="padding:8px 0;">
                            @if($u->is_active)
                            <span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;color:#16a34a;"><span style="width:5px;height:5px;border-radius:50%;background:#16a34a;flex-shrink:0;"></span>Active</span>
                            @else
                            <span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;color:#dc2626;"><span style="width:5px;height:5px;border-radius:50%;background:#dc2626;flex-shrink:0;"></span>Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="padding:24px;text-align:center;color:#94a3b8;">No users yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#ef4444;"></span>Disease Alert Monitor</div>
                @if(!empty($diseaseAlerts))
                <div class="space-y-2">
                @foreach($diseaseAlerts as $a)
                <div style="background:{{ $a['severity']==='high'?'#fef2f2':($a['severity']==='medium'?'#fffbeb':'#f0fdf4') }};border-left:3px solid {{ $a['severity']==='high'?'#ef4444':($a['severity']==='medium'?'#f59e0b':'#22c55e') }};border-radius:8px;padding:10px 14px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                        <div>
                            <div style="font-size:12px;font-weight:700;color:#0f172a;">{{ $a['disease'] }}</div>
                            <div style="font-size:10px;color:#64748b;margin-top:2px;">{{ $a['cases'] }} {{ Str::plural('case',$a['cases']) }} · 30 days · <span style="text-transform:capitalize;">{{ $a['type']??'Unknown' }}</span></div>
                        </div>
                        <span style="background:{{ $a['severity']==='high'?'#fecaca':($a['severity']==='medium'?'#fde68a':'#bbf7d0') }};color:{{ $a['severity']==='high'?'#991b1b':($a['severity']==='medium'?'#92400e':'#14532d') }};border-radius:99px;padding:2px 8px;font-size:9px;font-weight:700;text-transform:capitalize;white-space:nowrap;">{{ $a['severity'] }}</span>
                    </div>
                </div>
                @endforeach
                </div>
                @else
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:110px;color:#cbd5e1;">
                    <svg style="width:32px;height:32px;margin-bottom:8px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span style="font-size:12px;font-weight:600;">No active disease alerts</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    </div>
</x-app-layout>
