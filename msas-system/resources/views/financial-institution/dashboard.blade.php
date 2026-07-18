<x-app-layout>
    <x-slot name="header">Financial Institution Dashboard</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-blue-900 to-blue-700 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-blue-400/10 rounded-full blur-3xl"></div>
            <p class="text-blue-200 text-sm mb-1">Financial Institution Portal</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-blue-100 text-sm mt-2">Access verified farmer profiles, livestock data, and agricultural activity records to assess creditworthiness.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-700 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Farmers</p>
                <p class="text-4xl font-extrabold text-blue-700 mt-2">{{ $totalFarmers }}</p>
                <p class="text-xs text-slate-400 mt-1">Registered on platform</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-emerald-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Active Farmers</p>
                <p class="text-4xl font-extrabold text-emerald-600 mt-2">{{ $verifiedFarmers }}</p>
                <p class="text-xs text-slate-400 mt-1">Verified accounts</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-amber-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Livestock Records</p>
                <p class="text-4xl font-extrabold text-amber-600 mt-2">{{ $totalAnimals }}</p>
                <p class="text-xs text-slate-400 mt-1">Animals registered</p>
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
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Farmers by State</h3>
                <div class="space-y-2">
                    @forelse($stateBreakdown as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-700 font-medium">{{ $row->state ?? 'Unknown' }}</span>
                        <span class="font-bold text-blue-700 bg-blue-50 px-3 py-0.5 rounded-full text-xs">{{ $row->count }}</span>
                    </div>
                    @empty
                    <p class="text-slate-400 text-sm text-center py-4">No data available.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent Farmer Profiles</h3>
                <div class="space-y-3">
                    @forelse($recentFarmers as $farmer)
                    <div class="flex items-center gap-3 py-2 border-b border-slate-50 last:border-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-700 font-bold text-sm">
                            {{ strtoupper(substr($farmer->first_name ?? 'F', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $farmer->first_name }} {{ $farmer->last_name }}</p>
                            <p class="text-xs text-slate-400">{{ $farmer->state ?? 'N/A' }} &middot; {{ $farmer->lga ?? 'N/A' }}</p>
                        </div>
                        <span class="text-xs {{ $farmer->is_active ? 'text-emerald-600 font-bold' : 'text-red-400' }}">
                            {{ $farmer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @empty
                    <p class="text-slate-400 text-sm text-center py-4">No farmers found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
