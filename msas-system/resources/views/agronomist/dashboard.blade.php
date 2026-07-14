<x-app-layout>
    <x-slot name="header">Agronomist Dashboard</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-green-900 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-green-400/10 rounded-full blur-3xl"></div>
            <p class="text-green-200 text-sm mb-1">Agronomy Portal</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-green-100 text-sm mt-2">Review crop diagnoses, advise farmers, and track field interventions.</p>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#b45309] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Pending Consults</p>
                <p class="text-4xl font-extrabold text-[#b45309] mt-2">{{ $pendingConsults }}</p>
                <p class="text-xs text-slate-400 mt-1">Awaiting review</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#1FA84A] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Diagnosed</p>
                <p class="text-4xl font-extrabold text-[#1FA84A] mt-2">{{ $reviewedDiagnoses }}</p>
                <p class="text-xs text-slate-400 mt-1">Cases reviewed</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Farmers</p>
                <p class="text-4xl font-extrabold text-blue-600 mt-2">{{ $totalFarmers }}</p>
                <p class="text-xs text-slate-400 mt-1">Platform farmers</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#0F6B3E] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Your State</p>
                <p class="text-xl font-extrabold text-[#0F6B3E] mt-2">{{ auth()->user()->state ?? 'N/A' }}</p>
                <p class="text-xs text-slate-400 mt-1">Assigned region</p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('vet.queue') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#9654; Open Consult Queue
                </a>
                <a href="{{ route('diagnostics.scan') }}" class="px-5 py-2.5 bg-amber-600 text-white rounded-xl text-sm font-semibold hover:bg-amber-700 transition shadow-sm">
                    &#9654; Run AI Crop Scan
                </a>
                <a href="{{ route('diagnostics.history') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9776; Diagnosis History
                </a>
            </div>
        </div>

        {{-- Crop Diagnosis Queue --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center justify-between border-b pb-3 mb-4">
                <h3 class="font-bold text-slate-800 text-lg">Recent Consultation Requests</h3>
                @if($pendingConsults > 0)
                <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">{{ $pendingConsults }} pending</span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="pb-3 pr-4">Farmer</th>
                            <th class="pb-3 pr-4">Subject</th>
                            <th class="pb-3 pr-4">Status</th>
                            <th class="pb-3 pr-4">Date</th>
                            <th class="pb-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentConsults as $consult)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">
                                {{ optional($consult->user)->first_name }} {{ optional($consult->user)->last_name }}
                            </td>
                            <td class="py-3 pr-4 text-slate-600">{{ ucfirst($consult->crop_type ?? $consult->animal_type ?? 'Crop consultation') }}</td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ $consult->status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : ($consult->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ ucfirst($consult->status) }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-slate-500 text-xs">{{ $consult->created_at->format('M d, Y') }}</td>
                            <td class="py-3">
                                <a href="{{ route('vet.show', $consult->id) }}" class="px-3 py-1.5 bg-[#0F6B3E] text-white rounded-lg text-xs font-semibold hover:bg-[#047857] transition">
                                    Review
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 text-sm">No consultation requests found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
