<x-app-layout>
    <x-slot name="header">Cooperative Dashboard</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-emerald-900 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-emerald-400/10 rounded-full blur-3xl"></div>
            <p class="text-emerald-200 text-sm mb-1">Cooperative Portal</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-emerald-100 text-sm mt-2">Monitor member farmers, coordinate services, and track agricultural activities in your region.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-emerald-600 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Farmers in State</p>
                <p class="text-4xl font-extrabold text-emerald-700 mt-2">{{ $totalFarmers }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ auth()->user()->state ?? 'Your state' }}</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Consultations</p>
                <p class="text-4xl font-extrabold text-blue-600 mt-2">{{ $totalConsults }}</p>
                <p class="text-xs text-slate-400 mt-1">Platform-wide</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-purple-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">AI Diagnoses</p>
                <p class="text-4xl font-extrabold text-purple-600 mt-2">{{ $totalDiagnoses }}</p>
                <p class="text-xs text-slate-400 mt-1">Total scans run</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-amber-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Platform</p>
                <p class="text-sm font-bold text-amber-600 mt-3">MSAS FarmAI</p>
                <p class="text-xs text-slate-400 mt-1">Cooperative access</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('marketplace') }}" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition shadow-sm">Browse Marketplace</a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">My Profile</a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent Member Farmers</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs font-bold text-slate-500 uppercase text-left">
                        <th class="pb-3 pr-4">Name</th><th class="pb-3 pr-4">LGA</th><th class="pb-3 pr-4">Phone</th><th class="pb-3">Status</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentFarmers as $farmer)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $farmer->first_name }} {{ $farmer->last_name }}</td>
                            <td class="py-3 pr-4 text-slate-500">{{ $farmer->lga ?? 'N/A' }}</td>
                            <td class="py-3 pr-4 text-slate-500">{{ $farmer->phone ?? 'N/A' }}</td>
                            <td class="py-3"><span class="text-xs font-bold {{ $farmer->is_active ? 'text-emerald-600' : 'text-red-500' }}">{{ $farmer->is_active ? 'Active' : 'Inactive' }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-400 text-sm">No farmers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
