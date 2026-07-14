<x-app-layout>
    <x-slot name="header">Veterinary Dashboard</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-indigo-900 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-indigo-400/10 rounded-full blur-3xl"></div>
            <p class="text-indigo-200 text-sm mb-1">Veterinary Portal</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-indigo-100 text-sm mt-2">Manage consultation queue, respond to farmers, and track animal health.</p>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#b45309] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Pending Queue</p>
                <p class="text-4xl font-extrabold text-[#b45309] mt-2">{{ $pendingConsultations }}</p>
                <p class="text-xs text-slate-400 mt-1">Awaiting your response</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#1FA84A] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Completed Today</p>
                <p class="text-4xl font-extrabold text-[#1FA84A] mt-2">{{ $completedToday }}</p>
                <p class="text-xs text-slate-400 mt-1">Resolved consultations</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Farmers</p>
                <p class="text-4xl font-extrabold text-blue-600 mt-2">{{ $totalFarmers }}</p>
                <p class="text-xs text-slate-400 mt-1">Registered on platform</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#0F6B3E] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Handled</p>
                <p class="text-4xl font-extrabold text-[#0F6B3E] mt-2">{{ $totalHandled }}</p>
                <p class="text-xs text-slate-400 mt-1">Lifetime consultations</p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('vet.queue') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#9654; Open Consultation Queue
                </a>
                <a href="{{ route('diagnostics.history') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9776; Diagnosis History
                </a>
                <a href="{{ route('marketplace') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9776; Marketplace
                </a>
            </div>
        </div>

        {{-- Pending Consultation Queue --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center justify-between border-b pb-3 mb-4">
                <h3 class="font-bold text-slate-800 text-lg">Pending Consultations</h3>
                @if($pendingConsultations > 0)
                <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">{{ $pendingConsultations }} pending</span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="pb-3 pr-4">Farmer</th>
                            <th class="pb-3 pr-4">Subject</th>
                            <th class="pb-3 pr-4">Submitted</th>
                            <th class="pb-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($pendingQueue as $consult)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">
                                {{ optional($consult->user)->first_name }} {{ optional($consult->user)->last_name }}
                            </td>
                            <td class="py-3 pr-4 text-slate-600">{{ ucfirst($consult->animal_type ?? $consult->case_type ?? 'General') }}</td>
                            <td class="py-3 pr-4 text-slate-500 text-xs">{{ $consult->created_at->diffForHumans() }}</td>
                            <td class="py-3">
                                <a href="{{ route('vet.show', $consult->id) }}" class="px-3 py-1.5 bg-[#0F6B3E] text-white rounded-lg text-xs font-semibold hover:bg-[#047857] transition">
                                    Respond
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center">
                                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-slate-500 text-sm font-medium">All consultations handled!</p>
                                <p class="text-slate-400 text-xs mt-1">No pending cases in queue.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pendingConsultations > 8)
            <div class="mt-4 text-center">
                <a href="{{ route('vet.queue') }}" class="text-[#1FA84A] text-sm font-semibold hover:underline">View all {{ $pendingConsultations }} pending cases &rarr;</a>
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
