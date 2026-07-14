<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $role = Auth::user()->role;
    @endphp

    <div class="space-y-6">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-64 h-64 bg-emerald-500/20 rounded-full blur-3xl"></div>
            
            <p class="text-emerald-100 text-sm mb-1 relative z-10">Welcome back,</p>
            <h1 class="text-3xl font-extrabold relative z-10">{{ Auth::user()->name ?: Auth::user()->email }} 👋</h1>
            <p class="text-emerald-50 text-sm mt-2 opacity-90 relative z-10">
                Your personal <span class="font-bold text-amber-400">{{ Auth::user()->roleLabel }}</span> portal is ready.
            </p>
        </div>

        <!-- Role Specific Dashboards -->

        @if($role === 'admin')
            <!-- ADMIN DASHBOARD -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="/admin/users" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:border-emerald-500 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">👥</div>
                    <h3 class="font-bold text-slate-800 text-sm">User Management</h3>
                </a>
                <a href="/admin/staff" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:border-emerald-500 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">🏢</div>
                    <h3 class="font-bold text-slate-800 text-sm">Staff Records</h3>
                </a>
                <a href="/admin/settings" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:border-emerald-500 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">⚙️</div>
                    <h3 class="font-bold text-slate-800 text-sm">System Settings</h3>
                </a>
                <a href="/admin/reports" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:border-emerald-500 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">📊</div>
                    <h3 class="font-bold text-slate-800 text-sm">System Reports</h3>
                </a>
            </div>
            
        @elseif($role === 'finance')
            <!-- FINANCE DASHBOARD -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-emerald-50 p-6 rounded-2xl border border-emerald-100 shadow-sm">
                    <p class="text-sm font-bold text-emerald-800 uppercase mb-1">Total Income</p>
                    <h2 class="text-3xl font-extrabold text-emerald-600">₦2,450,000</h2>
                </div>
                <div class="bg-red-50 p-6 rounded-2xl border border-red-100 shadow-sm">
                    <p class="text-sm font-bold text-red-800 uppercase mb-1">Total Expenses</p>
                    <h2 class="text-3xl font-extrabold text-red-600">₦850,000</h2>
                </div>
                <div class="bg-amber-50 p-6 rounded-2xl border border-amber-100 shadow-sm">
                    <p class="text-sm font-bold text-amber-800 uppercase mb-1">Pending Payroll</p>
                    <h2 class="text-3xl font-extrabold text-amber-600">₦1,200,000</h2>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3">📈</div>
                    <h3 class="font-bold text-slate-800 text-sm">Income</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3">📉</div>
                    <h3 class="font-bold text-slate-800 text-sm">Expenses</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3">🧾</div>
                    <h3 class="font-bold text-slate-800 text-sm">Ledger</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3">💳</div>
                    <h3 class="font-bold text-slate-800 text-sm">Payroll</h3>
                </div>
            </div>

        @elseif($role === 'vet')
            <!-- VET DASHBOARD -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('vet.queue') }}" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:border-indigo-500 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">🩺</div>
                    <h3 class="font-bold text-slate-800 text-sm">Animal Consultations</h3>
                </a>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:border-indigo-500 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">📋</div>
                    <h3 class="font-bold text-slate-800 text-sm">Health Reports</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:border-amber-500 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">⚠️</div>
                    <h3 class="font-bold text-slate-800 text-sm">Pending Cases</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:border-emerald-500 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">💉</div>
                    <h3 class="font-bold text-slate-800 text-sm">Vaccinations</h3>
                </div>
            </div>

        @elseif($role === 'agronomist')
            <!-- AGRONOMIST DASHBOARD -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition cursor-pointer group flex items-center gap-4">
                    <div class="text-5xl group-hover:scale-110 transition">🌾</div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Crop Support</h3>
                        <p class="text-xs text-slate-500">Provide advisory tools</p>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition cursor-pointer group flex items-center gap-4">
                    <div class="text-5xl group-hover:scale-110 transition">📊</div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Farm Records</h3>
                        <p class="text-xs text-slate-500">Review yield data</p>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition cursor-pointer group flex items-center gap-4">
                    <div class="text-5xl group-hover:scale-110 transition">💬</div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Advisory Requests</h3>
                        <p class="text-xs text-slate-500">Respond to farmers</p>
                    </div>
                </div>
            </div>

        @elseif($role === 'rider')
            <!-- RIDER DASHBOARD -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3">📦</div>
                    <h3 class="font-bold text-slate-800 text-sm">My Deliveries</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3">⏱️</div>
                    <h3 class="font-bold text-slate-800 text-sm">Pending Dispatch</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3">💵</div>
                    <h3 class="font-bold text-slate-800 text-sm">My Earnings</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group">
                    <div class="text-4xl mb-3">🗺️</div>
                    <h3 class="font-bold text-slate-800 text-sm">Route Updates</h3>
                </div>
            </div>

        @elseif($role === 'farmer')
            <!-- FARMER DASHBOARD -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <a href="{{ route('farmer.livestock') }}" class="block bg-white p-6 rounded-2xl shadow-sm border border-slate-100 text-center hover:scale-105 transition cursor-pointer hover:border-[#1FA84A] group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">🐄</div>
                    <h3 class="font-bold text-slate-800 text-sm">My Livestock</h3>
                </a>
                <a href="{{ route('farmer.poultry') }}" class="block bg-white p-6 rounded-2xl shadow-sm border border-slate-100 text-center hover:scale-105 transition cursor-pointer hover:border-[#b45309] group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">🐔</div>
                    <h3 class="font-bold text-slate-800 text-sm">Poultry & Eggs</h3>
                </a>
                <a href="{{ route('farmer.finance') }}" class="block bg-white p-6 rounded-2xl shadow-sm border border-slate-100 text-center hover:scale-105 transition cursor-pointer hover:border-blue-500 group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">💰</div>
                    <h3 class="font-bold text-slate-800 text-sm">Financials</h3>
                </a>
                <a href="{{ route('farmer.vet') }}" class="block bg-white p-6 rounded-2xl shadow-sm border border-slate-100 text-center hover:scale-105 transition cursor-pointer hover:border-indigo-500 group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition">🩺</div>
                    <h3 class="font-bold text-slate-800 text-sm">Vet Consult</h3>
                </a>
            </div>

            <!-- Recent Notifications & Orders (Farmer) -->
            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <h3 class="font-bold text-slate-800 mb-4 border-b pb-2">Recent Orders & Requests</h3>
                    <div class="text-slate-500 text-sm text-center py-4">No recent orders found.</div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <h3 class="font-bold text-slate-800 mb-4 border-b pb-2">Notifications</h3>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 bg-emerald-50 rounded-lg">
                            <div class="text-emerald-500">🎉</div>
                            <div>
                                <p class="text-sm font-bold text-slate-800">Welcome to MSAS!</p>
                                <p class="text-xs text-slate-600">Your farmer account has been created successfully.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        @else
            <!-- DEFAULT FALLBACK DASHBOARD -->
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-100">
                <div class="p-6 text-slate-800">
                    {{ __("You're logged in as a general user. Please update your profile.") }}
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
