<x-app-layout>
    <x-slot name="header">Research Institution Dashboard</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-indigo-900 to-indigo-700 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-indigo-400/10 rounded-full blur-3xl"></div>
            <p class="text-indigo-200 text-sm mb-1">Research Institution Portal</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-indigo-100 text-sm mt-2">Access anonymised agricultural data, disease trends, and diagnostic records for research purposes.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-indigo-600 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Diagnoses</p>
                <p class="text-4xl font-extrabold text-indigo-600 mt-2">{{ $totalDiagnoses }}</p>
                <p class="text-xs text-slate-400 mt-1">AI scan records</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-emerald-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Farmers</p>
                <p class="text-4xl font-extrabold text-emerald-600 mt-2">{{ $totalFarmers }}</p>
                <p class="text-xs text-slate-400 mt-1">Platform participants</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Animals Tracked</p>
                <p class="text-4xl font-extrabold text-blue-600 mt-2">{{ $totalAnimals }}</p>
                <p class="text-xs text-slate-400 mt-1">Livestock records</p>
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
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Top Disease Frequencies</h3>
                <div class="space-y-3">
                    @forelse($diseaseFrequency as $disease)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-700 font-medium">{{ $disease->disease_name ?: 'Unknown' }}</span>
                        <span class="text-xs font-bold bg-indigo-100 text-indigo-700 px-3 py-0.5 rounded-full">{{ $disease->count }} cases</span>
                    </div>
                    @empty
                    <p class="text-slate-400 text-sm text-center py-4">No diagnosis data yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent Diagnostic Records</h3>
                <div class="space-y-3">
                    @forelse($recentDiagnoses as $diag)
                    <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ $diag->disease_name ?: ($diag->case_type ?? 'Scan') }}</p>
                            <p class="text-xs text-slate-400">{{ $diag->created_at->format('M d, Y') }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $diag->status === 'reviewed' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($diag->status ?? 'pending') }}</span>
                    </div>
                    @empty
                    <p class="text-slate-400 text-sm text-center py-4">No records yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
