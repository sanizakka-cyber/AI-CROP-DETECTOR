<x-app-layout>
    <x-slot name="header">Extension Officer Dashboard</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-teal-900 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-teal-400/10 rounded-full blur-3xl"></div>
            <p class="text-teal-200 text-sm mb-1">Extension Services</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-teal-100 text-sm mt-2">Support farmers in your region, track field visits, and coordinate advisory services.</p>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#0F6B3E] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Farmers in Region</p>
                <p class="text-4xl font-extrabold text-[#0F6B3E] mt-2">{{ $farmersAssigned }}</p>
                <p class="text-xs text-slate-400 mt-1">In {{ auth()->user()->state ?? 'your state' }}</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#1FA84A] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Platform Farmers</p>
                <p class="text-4xl font-extrabold text-[#1FA84A] mt-2">{{ $totalFarmers }}</p>
                <p class="text-xs text-slate-400 mt-1">Nationwide</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#b45309] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Visits This Month</p>
                <p class="text-4xl font-extrabold text-[#b45309] mt-2">{{ $visitsThisMonth }}</p>
                <p class="text-xs text-slate-400 mt-1">Field visits logged</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Upcoming Visits</p>
                <p class="text-4xl font-extrabold text-blue-600 mt-2">{{ count($upcomingVisits) }}</p>
                <p class="text-xs text-slate-400 mt-1">Scheduled</p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('extension.farmers') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#9776; Farmer Directory
                </a>
                <a href="{{ route('extension.visits') }}" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                    &#9654; Farm Visits
                </a>
                <a href="{{ route('extension.advisory') }}" class="px-5 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-600 transition shadow-sm">
                    &#9654; Advisory Records
                </a>
                <a href="{{ route('diagnostics.scan') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9654; AI Scan
                </a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9998; My Profile
                </a>
            </div>
        </div>

        {{-- Farmers in Region --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center justify-between border-b pb-3 mb-4">
                <h3 class="font-bold text-slate-800 text-lg">Farmers in Your Region</h3>
                <span class="text-xs font-semibold text-slate-500">{{ auth()->user()->state ?? 'All States' }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="pb-3 pr-4">Name</th>
                            <th class="pb-3 pr-4">LGA</th>
                            <th class="pb-3 pr-4">Phone</th>
                            <th class="pb-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentFarmers as $farmer)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $farmer->first_name }} {{ $farmer->last_name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $farmer->lga ?? 'N/A' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $farmer->phone ?? 'N/A' }}</td>
                            <td class="py-3">
                                @if($farmer->is_active)
                                    <span class="text-emerald-600 text-xs font-bold">&#10003; Active</span>
                                @else
                                    <span class="text-red-500 text-xs font-bold">&#10005; Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500 text-sm">No farmers found in your region.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
