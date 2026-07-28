<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Pilot Program</h2>
                <p class="text-sm text-slate-500 mt-0.5">Pilot farmers and recent sign-ups (last 30 days)</p>
            </div>
            <a href="{{ route('ceo.invite-codes') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-emerald-700">
                Invite Codes
            </a>
        </div>
    </x-slot>
    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
            <div class="bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl font-semibold text-sm">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-slate-100 p-5 text-center shadow-sm">
                    <div class="text-3xl font-black text-emerald-600">{{ $pilotCount }}</div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mt-1">Pilot Farmers</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-5 text-center shadow-sm">
                    <div class="text-3xl font-black text-sky-600">{{ $newThisWeek }}</div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mt-1">New This Week</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-5 text-center shadow-sm">
                    <div class="text-3xl font-black text-violet-600">{{ $pilots->total() }}</div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mt-1">Showing</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-700">Farmer Engagement</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="px-5 py-3 text-left">Farmer</th>
                                <th class="px-4 py-3 text-left">State</th>
                                <th class="px-4 py-3 text-center">Scans</th>
                                <th class="px-4 py-3 text-center">Consults</th>
                                <th class="px-4 py-3 text-left">Plan</th>
                                <th class="px-4 py-3 text-left">Joined</th>
                                <th class="px-4 py-3 text-center">Pilot</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pilots as $farmer)
                            @php
                                $sub = $farmer->subscriptions->first();
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <div class="font-semibold text-slate-800">{{ $farmer->first_name }} {{ $farmer->last_name }}</div>
                                    <div class="text-xs text-slate-400">{{ $farmer->email ?? $farmer->phone }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $farmer->state ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-bold {{ $farmer->scan_count > 0 ? 'text-emerald-600' : 'text-slate-400' }}">{{ $farmer->scan_count }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-bold {{ $farmer->consult_count > 0 ? 'text-sky-600' : 'text-slate-400' }}">{{ $farmer->consult_count }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($sub)
                                    <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ ucfirst($sub->plan) }}</span>
                                    @else
                                    <span class="text-slate-400 text-xs">No plan</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ $farmer->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($farmer->is_pilot)
                                    <span class="bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full">✓ Pilot</span>
                                    @else
                                    <span class="text-slate-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('ceo.pilot.flag', $farmer) }}">
                                        @csrf
                                        <button type="submit"
                                            class="text-xs font-bold px-3 py-1.5 rounded-lg {{ $farmer->is_pilot ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                                            {{ $farmer->is_pilot ? 'Remove Pilot' : 'Flag Pilot' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-400">No farmers found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($pilots->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">{{ $pilots->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>