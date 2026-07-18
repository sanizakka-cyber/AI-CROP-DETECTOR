<x-app-layout>
    <x-slot name="header">NGO Dashboard</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-orange-700 to-amber-600 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-white/10 rounded-full blur-3xl"></div>
            <p class="text-orange-100 text-sm mb-1">NGO / Development Partner</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-orange-100 text-sm mt-2">Track beneficiary farmers, monitor agricultural outcomes, and measure program impact.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-orange-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Beneficiary Farmers</p>
                <p class="text-4xl font-extrabold text-orange-600 mt-2">{{ $totalBeneficiaries }}</p>
                <p class="text-xs text-slate-400 mt-1">Platform-wide</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-emerald-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Consultations</p>
                <p class="text-4xl font-extrabold text-emerald-600 mt-2">{{ $totalConsults }}</p>
                <p class="text-xs text-slate-400 mt-1">Expert advice given</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-emerald-700 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Resolved Cases</p>
                <p class="text-4xl font-extrabold text-emerald-700 mt-2">{{ $resolvedConsults }}</p>
                <p class="text-xs text-slate-400 mt-1">Successful outcomes</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-purple-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">AI Diagnoses</p>
                <p class="text-4xl font-extrabold text-purple-600 mt-2">{{ $totalDiagnoses }}</p>
                <p class="text-xs text-slate-400 mt-1">Disease scans completed</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('marketplace') }}" class="px-5 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-semibold hover:bg-orange-700 transition shadow-sm">Marketplace</a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">My Profile</a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent Farmer Activity</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs font-bold text-slate-500 uppercase text-left">
                        <th class="pb-3 pr-4">Name</th><th class="pb-3 pr-4">State</th><th class="pb-3 pr-4">LGA</th><th class="pb-3">Joined</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentActivity as $farmer)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $farmer->first_name }} {{ $farmer->last_name }}</td>
                            <td class="py-3 pr-4 text-slate-500">{{ $farmer->state ?? 'N/A' }}</td>
                            <td class="py-3 pr-4 text-slate-500">{{ $farmer->lga ?? 'N/A' }}</td>
                            <td class="py-3 text-slate-400 text-xs">{{ $farmer->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-400 text-sm">No activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
