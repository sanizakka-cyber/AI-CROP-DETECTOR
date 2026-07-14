<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Field Officer Dashboard</h2>
                <p class="text-sm text-gray-500 mt-0.5">Field operations, farmer visits, and community outreach</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Field Officer
            </span>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">

        <!-- Hero Banner -->
        <div class="bg-gradient-to-r from-[#0B2447] to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden mb-8">
            <div class="absolute right-0 top-0 w-56 h-56 bg-emerald-400/10 rounded-full blur-3xl"></div>
            <p class="text-emerald-200 text-sm mb-1">Field Operations</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-emerald-100 text-sm mt-2">Manage farmer visits, field reports, and community outreach activities.</p>
        </div>

        <!-- Stats -->
        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h3 class="font-bold text-gray-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('extension.visits') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#9654; Log Farm Visit
                </a>
                <a href="{{ route('extension.farmers') }}" class="px-5 py-2.5 bg-orange-500 text-white rounded-xl text-sm font-semibold hover:bg-orange-600 transition shadow-sm">
                    &#9776; Farmer Directory
                </a>
                <a href="{{ route('extension.advisory') }}" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                    &#9776; Advisory Records
                </a>
                <a href="{{ route('diagnostics.scan') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9654; AI Scan
                </a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9998; My Profile
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @php
                $stats = [
                    ['label'=>'Assigned Farmers','value'=>number_format($assignedFarmers),'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','color'=>'orange'],
                    ['label'=>'Visits This Month','value'=>$visitsThisMonth,'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z','color'=>'blue'],
                    ['label'=>'Pending Follow-ups','value'=>$pendingFollowups,'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z','color'=>'amber'],
                    ['label'=>'Reports Submitted','value'=>$reportsSubmitted,'icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','color'=>'green'],
                ];
                $colorMap=['orange'=>['bg'=>'bg-orange-50','text'=>'text-orange-600','num'=>'text-orange-700'],
                           'blue'=>['bg'=>'bg-blue-50','text'=>'text-blue-600','num'=>'text-blue-700'],
                           'amber'=>['bg'=>'bg-amber-50','text'=>'text-amber-600','num'=>'text-amber-700'],
                           'green'=>['bg'=>'bg-green-50','text'=>'text-green-600','num'=>'text-green-700']];
            @endphp
            @foreach($stats as $s)
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $s['label'] }}</p>
                        <p class="text-2xl font-black {{ $colorMap[$s['color']]['num'] }} mt-1">{{ $s['value'] }}</p>
                    </div>
                    <div class="w-10 h-10 {{ $colorMap[$s['color']]['bg'] }} rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $colorMap[$s['color']]['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}"/></svg>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Assigned Farmers -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    My Farmer List
                </h3>
                @if($recentFarmers->count())
                <div class="space-y-2">
                    @foreach($recentFarmers as $farmer)
                    <div class="flex items-center gap-3 p-3 bg-gray-50/70 rounded-xl hover:bg-orange-50/50 transition-colors">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                            {{ strtoupper(substr($farmer->first_name ?? 'F', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $farmer->name }}</p>
                            <p class="text-xs text-gray-400">{{ $farmer->state ?? 'State N/A' }} &middot; Joined {{ $farmer->created_at->format('M Y') }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $farmer->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} font-medium">
                            {{ $farmer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-10 text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857"/></svg>
                    <p class="text-sm">No farmers assigned yet.</p>
                </div>
                @endif
            </div>

            <!-- Visit Schedule & Activity -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Field Activity Summary
                </h3>
                <div class="space-y-3">
                    @php
                        $activities = [
                            ['label'=>'Farm Visits Completed', 'value'=>$visitsThisMonth, 'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'text-green-500'],
                            ['label'=>'Pending Follow-ups', 'value'=>$pendingFollowups, 'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'text-amber-500'],
                            ['label'=>'Reports Submitted', 'value'=>$reportsSubmitted, 'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2', 'color'=>'text-blue-500'],
                            ['label'=>'Farmers Registered', 'value'=>$farmersRegistered, 'icon'=>'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z', 'color'=>'text-purple-500'],
                        ];
                    @endphp
                    @foreach($activities as $act)
                    <div class="flex items-center justify-between p-3 bg-gray-50/60 rounded-xl">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 {{ $act['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $act['icon'] }}"/></svg>
                            <span class="text-sm text-gray-700 font-medium">{{ $act['label'] }}</span>
                        </div>
                        <span class="font-bold text-gray-800 text-sm">{{ $act['value'] }}</span>
                    </div>
                    @endforeach
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Outreach This Week</p>
                    <div class="flex gap-1.5">
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                        @php $active = in_array($d, ['Mon','Tue','Thu']); @endphp
                        <div class="flex-1 text-center">
                            <div class="h-8 rounded-md {{ $active ? 'bg-orange-400' : 'bg-gray-100' }} mb-1 flex items-center justify-center">
                                @if($active)
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400">{{ $d }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
