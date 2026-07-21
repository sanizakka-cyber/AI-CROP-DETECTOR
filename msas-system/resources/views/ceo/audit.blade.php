<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('ceo.dashboard') }}" class="text-slate-400 hover:text-slate-600 transition">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h2 class="font-extrabold text-xl text-gray-800">System Audit</h2>
                    <p class="text-xs text-gray-400 mt-0.5 font-mono">Run at {{ $auditAt }}</p>
                </div>
            </div>
            <a href="{{ route('ceo.audit') }}"
               class="px-4 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition flex items-center gap-2">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0115-6.7M20 15a9 9 0 01-15 6.7"/></svg>
                Re-run Audit
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">

        {{-- Summary Banner --}}
        @php
            $totalChecks  = collect($checks)->flatten(1)->count();
            $failedChecks = collect($checks)->flatten(1)->where('ok', false)->count();
            $healthColor  = $failedChecks === 0 ? 'emerald' : ($failedChecks <= 3 ? 'amber' : 'red');
            $healthLabel  = $failedChecks === 0 ? 'All Systems Healthy' : "{$failedChecks} Issues Detected";
        @endphp

        <div class="bg-{{ $healthColor }}-50 border border-{{ $healthColor }}-200 rounded-2xl px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($failedChecks === 0)
                <span class="text-2xl">✅</span>
                @elseif($failedChecks <= 3)
                <span class="text-2xl">⚠️</span>
                @else
                <span class="text-2xl">❌</span>
                @endif
                <div>
                    <p class="font-extrabold text-{{ $healthColor }}-800 text-base">{{ $healthLabel }}</p>
                    <p class="text-xs text-{{ $healthColor }}-600 mt-0.5">{{ $totalChecks - $failedChecks }} of {{ $totalChecks }} checks passed</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-{{ $healthColor }}-500">Platform users</p>
                <p class="text-lg font-extrabold text-{{ $healthColor }}-800">{{ number_format($userStats['total']) }}</p>
                <p class="text-[11px] text-{{ $healthColor }}-500">{{ $userStats['active'] }} active · {{ $userStats['trial'] }} trial · {{ $userStats['paid'] }} paid</p>
            </div>
        </div>

        {{-- Check Groups --}}
        @foreach($checks as $groupName => $items)
        @php $groupFailed = collect($items)->where('ok', false)->count(); @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h4 class="font-bold text-slate-700 text-sm uppercase tracking-wide">{{ $groupName }}</h4>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $groupFailed === 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $groupFailed === 0 ? 'All OK' : $groupFailed . ' failed' }}
                </span>
            </div>
            <div class="divide-y divide-slate-50">
                @foreach($items as $item)
                <div class="flex items-center justify-between px-6 py-3 {{ $item['ok'] ? '' : 'bg-red-50/40' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="text-sm {{ $item['ok'] ? 'text-emerald-500' : 'text-red-500' }}">
                            {{ $item['ok'] ? '✓' : '✗' }}
                        </span>
                        <span class="text-sm font-mono text-slate-700">{{ $item['name'] }}</span>
                    </div>
                    <span class="text-xs {{ $item['ok'] ? 'text-slate-400' : 'text-red-600 font-bold' }}">
                        {{ $item['detail'] }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        {{-- Recent Error Log --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h4 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Recent Errors (last 10)</h4>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ count($logErrors) === 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ count($logErrors) === 0 ? 'No errors' : count($logErrors) . ' found' }}
                </span>
            </div>
            @if(count($logErrors) === 0)
            <div class="px-6 py-8 text-center text-sm text-slate-400">
                No ERROR or CRITICAL entries in recent log. 🎉
            </div>
            @else
            <div class="divide-y divide-slate-50 overflow-x-auto">
                @foreach($logErrors as $line)
                <div class="px-6 py-2.5 font-mono text-[11px] text-red-700 bg-red-50/30 whitespace-nowrap overflow-hidden text-ellipsis max-w-full">
                    {{ $line }}
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
