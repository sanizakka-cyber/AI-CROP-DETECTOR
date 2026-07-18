<x-app-layout>
    <x-slot name="header">Government Agency Dashboard</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-green-900 to-green-700 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-white/5 rounded-full blur-3xl"></div>
            <p class="text-green-200 text-sm mb-1">Government Agency Portal</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-green-100 text-sm mt-2">Monitor national agricultural data, disease alerts, and farmer statistics across all states.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-green-700 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Registered Farmers</p>
                <p class="text-4xl font-extrabold text-green-700 mt-2">{{ $totalFarmers }}</p>
                <p class="text-xs text-slate-400 mt-1">Nationwide</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Livestock Records</p>
                <p class="text-4xl font-extrabold text-blue-600 mt-2">{{ $totalAnimals }}</p>
                <p class="text-xs text-slate-400 mt-1">Animals tracked</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-red-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Disease Scans</p>
                <p class="text-4xl font-extrabold text-red-600 mt-2">{{ $totalDiagnoses }}</p>
                <p class="text-xs text-slate-400 mt-1">AI diagnoses completed</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">My Profile</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent Disease Alerts</h3>
                <div class="space-y-3">
                    @forelse($diseaseAlerts as $alert)
                    <div class="flex items-start gap-3 p-3 bg-red-50 rounded-xl border border-red-100">
                        <span class="text-red-500 font-bold text-lg mt-0.5">!</span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">{{ $alert->disease_name ?? $alert->case_type ?? 'Unknown' }}</p>
                            <p class="text-xs text-slate-500">{{ $alert->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-slate-400 text-sm text-center py-4">No recent alerts.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Farmers by State</h3>
                <div class="space-y-2">
                    @forelse($stateBreakdown as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-700 font-medium">{{ $row->state ?? 'Unknown' }}</span>
                        <span class="font-bold text-green-700 bg-green-50 px-3 py-0.5 rounded-full text-xs">{{ $row->count }}</span>
                    </div>
                    @empty
                    <p class="text-slate-400 text-sm text-center py-4">No data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
