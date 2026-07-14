<x-app-layout>
    <x-slot name="header">System Settings</x-slot>

    <div class="space-y-6">

        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-6 text-white">
            <p class="text-emerald-200 text-sm mb-1">Administration</p>
            <h1 class="text-2xl font-extrabold">System Settings</h1>
            <p class="text-emerald-100 text-sm mt-1">Manage platform configuration, roles, and system behaviour.</p>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Platform Info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Platform Information</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-2 border-b border-slate-50">
                        <span class="text-slate-500 font-medium">Application</span>
                        <span class="font-bold text-slate-800">MSAS FarmAI</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-50">
                        <span class="text-slate-500 font-medium">Version</span>
                        <span class="font-bold text-slate-800">1.0.0</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-50">
                        <span class="text-slate-500 font-medium">Framework</span>
                        <span class="font-bold text-slate-800">Laravel {{ app()->version() }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-50">
                        <span class="text-slate-500 font-medium">PHP Version</span>
                        <span class="font-bold text-slate-800">{{ phpversion() }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-50">
                        <span class="text-slate-500 font-medium">Environment</span>
                        <span class="font-bold {{ config('app.env') === 'production' ? 'text-emerald-700' : 'text-amber-600' }}">{{ ucfirst(config('app.env')) }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-50">
                        <span class="text-slate-500 font-medium">Debug Mode</span>
                        <span class="font-bold {{ config('app.debug') ? 'text-red-600' : 'text-emerald-700' }}">{{ config('app.debug') ? 'ON (disable before go-live)' : 'OFF' }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-500 font-medium">Database</span>
                        <span class="font-bold text-slate-800">{{ config('database.default') }}</span>
                    </div>
                </div>
            </div>

            {{-- User Role Summary --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Active Roles</h3>
                @php
                    $roles = \App\Models\User::select('role', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                        ->groupBy('role')->orderByDesc('total')->get();
                @endphp
                <div class="space-y-2">
                    @foreach($roles as $r)
                    <div class="flex items-center justify-between py-2 border-b border-slate-50">
                        <span class="text-sm font-semibold text-slate-700 capitalize">{{ str_replace(['-','_'],' ', $r->role) }}</span>
                        <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">{{ $r->total }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Quick Admin Actions --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.users') }}" class="flex items-center gap-3 p-3 bg-slate-50 hover:bg-emerald-50 rounded-xl transition group">
                        <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Manage Users</p>
                            <p class="text-xs text-slate-400">View, activate, or deactivate accounts</p>
                        </div>
                    </a>
                    <a href="{{ route('admin.staff') }}" class="flex items-center gap-3 p-3 bg-slate-50 hover:bg-blue-50 rounded-xl transition group">
                        <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Staff Directory</p>
                            <p class="text-xs text-slate-400">View internal staff accounts</p>
                        </div>
                    </a>
                    <a href="{{ route('admin.reports') }}" class="flex items-center gap-3 p-3 bg-slate-50 hover:bg-amber-50 rounded-xl transition group">
                        <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">System Reports</p>
                            <p class="text-xs text-slate-400">Platform usage and analytics</p>
                        </div>
                    </a>
                    <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center gap-3 p-3 bg-slate-50 hover:bg-indigo-50 rounded-xl transition group">
                        <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Subscription Management</p>
                            <p class="text-xs text-slate-400">Manage farmer subscriptions</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- System Health --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">System Health</h3>
                @php
                    $totalUsers   = \App\Models\User::count();
                    $activeUsers  = \App\Models\User::where('is_active', true)->count();
                    $activePct    = $totalUsers > 0 ? round(($activeUsers/$totalUsers)*100) : 0;
                @endphp
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-semibold text-slate-700">Active Users</span>
                            <span class="font-bold text-emerald-700">{{ $activePct }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="h-2 bg-emerald-500 rounded-full" style="width:{{ $activePct }}%"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">{{ $activeUsers }} of {{ $totalUsers }} users active</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <div class="bg-slate-50 rounded-xl p-3 text-center">
                            <p class="text-2xl font-extrabold text-[#0F6B3E]">{{ $totalUsers }}</p>
                            <p class="text-xs text-slate-500 font-semibold mt-0.5">Total Users</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-3 text-center">
                            <p class="text-2xl font-extrabold text-blue-600">{{ \App\Models\Consultation::count() }}</p>
                            <p class="text-xs text-slate-500 font-semibold mt-0.5">Consultations</p>
                        </div>
                    </div>
                    <div class="mt-2 p-3 {{ config('app.debug') ? 'bg-red-50 border border-red-200' : 'bg-emerald-50 border border-emerald-200' }} rounded-xl text-xs font-semibold {{ config('app.debug') ? 'text-red-700' : 'text-emerald-700' }}">
                        {{ config('app.debug') ? '⚠ Debug mode is ON — disable before production deployment.' : '✓ System running in production mode.' }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
