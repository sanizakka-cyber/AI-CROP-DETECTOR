<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Customer Support Dashboard</h2>
                <p class="text-sm text-gray-500 mt-0.5">Help desk, user queries, and service quality</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-cyan-100 text-cyan-700">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Support Officer
            </span>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">

        <!-- Hero Banner -->
        <div class="bg-gradient-to-r from-[#0B2447] to-[#2D9CDB] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden mb-8">
            <div class="absolute right-0 top-0 w-56 h-56 bg-blue-400/10 rounded-full blur-3xl"></div>
            <p class="text-blue-200 text-sm mb-1">Customer Support</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-blue-100 text-sm mt-2">Handle user queries, resolve support tickets, and maintain service quality.</p>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h3 class="font-bold text-gray-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('support.tickets') }}" class="px-5 py-2.5 bg-[#2D9CDB] text-white rounded-xl text-sm font-semibold hover:bg-blue-600 transition shadow-sm">
                    &#9776; All Tickets
                </a>
                <a href="{{ route('support.tickets.create') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#43; New Ticket
                </a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9998; My Profile
                </a>
            </div>
        </div>

        <!-- Ticket Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @php
                $metrics = [
                    ['label'=>'Open Tickets','value'=>$openTickets,'color'=>'red','icon'=>'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'],
                    ['label'=>'Resolved Today','value'=>$resolvedToday,'color'=>'green','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label'=>'Avg Response','value'=>$avgResponseTime . 'h','color'=>'blue','icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label'=>'Satisfaction','value'=>$satisfactionScore . '%','color'=>'amber','icon'=>'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                ];
                $clr=['red'=>['bg'=>'bg-red-50','text'=>'text-red-600','num'=>'text-red-700'],
                      'green'=>['bg'=>'bg-green-50','text'=>'text-green-600','num'=>'text-green-700'],
                      'blue'=>['bg'=>'bg-blue-50','text'=>'text-blue-600','num'=>'text-blue-700'],
                      'amber'=>['bg'=>'bg-amber-50','text'=>'text-amber-600','num'=>'text-amber-700']];
            @endphp
            @foreach($metrics as $m)
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $m['label'] }}</p>
                        <p class="text-2xl font-black {{ $clr[$m['color']]['num'] }} mt-1">{{ $m['value'] }}</p>
                    </div>
                    <div class="w-10 h-10 {{ $clr[$m['color']]['bg'] }} rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $clr[$m['color']]['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/></svg>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Recent Tickets -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    Recent Support Requests
                </h3>
                @if($recentUsers->count())
                <div class="space-y-2">
                    @foreach($recentUsers as $u)
                    <div class="flex items-center gap-3 p-3 bg-gray-50/70 rounded-xl hover:bg-cyan-50/50 transition-colors">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                            {{ strtoupper(substr($u->first_name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $u->name }}</p>
                            <p class="text-xs text-gray-400 capitalize">{{ str_replace('-', ' ', $u->role) }} &middot; {{ $u->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">Active</span>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-10 text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7"/></svg>
                    <p class="text-sm">No recent activity.</p>
                </div>
                @endif
            </div>

            <!-- Ticket Categories & SLA -->
            <div class="space-y-4">

                <!-- Ticket Breakdown -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
                        Issue Categories
                    </h3>
                    @php
                        $categories = [
                            ['name'=>'App Technical Issues', 'count'=>$techIssues, 'color'=>'bg-red-400'],
                            ['name'=>'Login & Access', 'count'=>$loginIssues, 'color'=>'bg-amber-400'],
                            ['name'=>'Marketplace Queries', 'count'=>$marketplaceIssues, 'color'=>'bg-blue-400'],
                            ['name'=>'AI Scan Queries', 'count'=>$aiQueryIssues, 'color'=>'bg-teal-400'],
                            ['name'=>'General Enquiries', 'count'=>$generalIssues, 'color'=>'bg-gray-300'],
                        ];
                        $totalCat = max(1, array_sum(array_column($categories, 'count')));
                    @endphp
                    <div class="space-y-2.5">
                        @foreach($categories as $cat)
                        @php $pct = round(($cat['count']/$totalCat)*100); @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-600 font-medium">{{ $cat['name'] }}</span>
                                <span class="text-gray-500">{{ $cat['count'] }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full">
                                <div class="h-full {{ $cat['color'] }} rounded-full" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- SLA Status -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        SLA Compliance
                    </h3>
                    <div class="flex items-center gap-4">
                        <div class="relative w-20 h-20 flex-shrink-0">
                            <svg viewBox="0 0 36 36" class="w-20 h-20 -rotate-90">
                                <circle cx="18" cy="18" r="15" fill="none" stroke="#f1f5f9" stroke-width="3"/>
                                <circle cx="18" cy="18" r="15" fill="none" stroke="#1FA84A" stroke-width="3"
                                    stroke-dasharray="{{ $slaCompliance }}, 100"
                                    stroke-linecap="round"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg font-black text-green-600">{{ $slaCompliance }}%</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">SLA Met</p>
                            <p class="text-xs text-gray-500 mt-0.5">Target: 90% within 4h</p>
                            <p class="text-xs {{ $slaCompliance >= 90 ? 'text-green-600' : 'text-amber-600' }} font-semibold mt-1">
                                {{ $slaCompliance >= 90 ? 'On target' : 'Needs improvement' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
