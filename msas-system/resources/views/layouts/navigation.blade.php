@php
    $user = auth()->user();
    $role = $user->role ?? 'user';
    $fullName = $user->name ?: 'User';
    $initial = strtoupper(substr($user->displayFirstName ?: 'U', 0, 1));
    $unreadNotifications = \App\Models\Notification::where('user_id', $user->id)->where('is_read', false)->count();
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm" role="navigation" aria-label="Main navigation">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- Logo & Primary Links --}}
            <div class="flex items-center">
                <div class="shrink-0 flex items-center">
                    <a href="/" class="flex items-center gap-2 group" style="text-decoration:none">
                        <div style="width:36px;height:36px;border-radius:9px;overflow:hidden;flex-shrink:0;background:#0B2447;box-shadow:0 2px 6px rgba(0,0,0,0.18);transition:transform 0.2s;" class="group-hover:scale-105">
                            <img src="{{ asset('images/msas-logo.png') }}" alt="MSAS Agro"
                                 style="width:100%;height:100%;object-fit:cover;display:block;">
                        </div>
                        <span class="font-bold tracking-tight" style="color:#0F6B3E">MSAS Portal</span>
                    </a>
                </div>

                {{-- Desktop Nav Links --}}
                <div class="hidden sm:flex sm:items-center sm:ms-8 space-x-1">

                    {{-- Dashboard link (role-aware) --}}
                    @php
                        $dashRoute = match($role) {
                            'ceo'                   => route('ceo.dashboard'),
                            'admin'                 => route('admin.dashboard'),
                            'farmer'                => route('farmer.dashboard'),
                            'vet'                   => route('vet.dashboard'),
                            'agronomist'            => route('agronomist.dashboard'),
                            'agro-dealer'           => route('dealer.dashboard'),
                            'equipment-dealer'      => route('equipment-dealer.dashboard'),
                            'extension-officer'     => route('extension.dashboard'),
                            'finance'               => route('finance.dashboard'),
                            'hr'                    => route('hr.dashboard'),
                            'operations'            => route('operations.dashboard'),
                            'rider'                 => route('rider.dashboard'),
                            'cooperative'           => route('cooperative.dashboard'),
                            'ngo'                   => route('ngo.dashboard'),
                            'government',
                            'government-agency'     => route('government.dashboard'),
                            'research-institution'  => route('research-institution.dashboard'),
                            'investor'              => route('investor.dashboard'),
                            'financial-institution' => route('financial-institution.dashboard'),
                            'logistics-provider'    => route('logistics.dashboard'),
                            'agribusiness-owner'    => route('agribusiness.dashboard'),
                            'input-supplier'        => route('input-supplier.dashboard'),
                            'data-analyst'          => route('data-analyst.dashboard'),
                            'm-e-officer',
                            'me-officer',
                            'monitoring-evaluation' => route('monitoring-evaluation.dashboard'),
                            'field-officer'         => route('field-officer.dashboard'),
                            'customer-support'      => route('customer-support.dashboard'),
                            'researcher'            => route('research-institution.dashboard'),
                            'student'               => route('farmer.dashboard'),
                            'general-user'          => route('dashboard'),
                            default                 => route('dashboard'),
                        };
                    @endphp
                    <a href="{{ $dashRoute }}" class="px-3 py-2 rounded-lg text-sm font-medium transition
                        {{ request()->routeIs('*.dashboard') || request()->routeIs('dashboard') || request()->routeIs('ceo.dashboard')
                            ? 'bg-emerald-50 text-[#0F6B3E] font-semibold'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E]' }}">
                        Dashboard
                    </a>

                    {{-- ── CEO Dropdown Menus ── --}}
                    @if($role === 'ceo')
                        {{-- Staff Management dropdown --}}
                        <div class="relative" x-data="{ staffOpen: false }">
                            <button @click="staffOpen=!staffOpen" @click.outside="staffOpen=false"
                                class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('ceo.staff*') || request()->routeIs('ceo.staff-roles*') || request()->routeIs('ceo.audit') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                                Staff
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="staffOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-cloak
                                class="absolute left-0 mt-1 w-48 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50">
                                <a href="{{ route('ceo.staff.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('ceo.staff*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    All Staff
                                </a>
                                <a href="{{ route('ceo.staff.create') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    Add Staff
                                </a>
                                <a href="{{ route('ceo.staff-roles.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('ceo.staff-roles*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Roles & Permissions
                                </a>
                                <a href="{{ route('ceo.audit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('ceo.audit') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    Audit Log
                                </a>
                            </div>
                        </div>

                        {{-- Operations dropdown --}}
                        <div class="relative" x-data="{ opsOpen: false }">
                            <button @click="opsOpen=!opsOpen" @click.outside="opsOpen=false"
                                class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('admin.orders*') || request()->routeIs('admin.riders*') || request()->routeIs('admin.consultations*') || request()->routeIs('admin.applications*') || request()->routeIs('ceo.orders*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                                Operations
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="opsOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-cloak
                                class="absolute left-0 mt-1 w-52 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50">
                                <a href="{{ route('ceo.orders') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('ceo.orders*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    Order Oversight
                                </a>
                                <a href="{{ route('admin.consultations.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('admin.consultations*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    Consultations
                                </a>
                                <a href="{{ route('admin.riders.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('admin.riders*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                    Riders
                                </a>
                                <a href="{{ route('admin.applications.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('admin.applications*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Applications
                                </a>
                            </div>
                        </div>

                        {{-- Finance dropdown --}}
                        <div class="relative" x-data="{ finOpen: false }">
                            <button @click="finOpen=!finOpen" @click.outside="finOpen=false"
                                class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('admin.payments*') || request()->routeIs('admin.payouts*') || request()->routeIs('admin.subscriptions*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                                Finance
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="finOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-cloak
                                class="absolute left-0 mt-1 w-48 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50">
                                <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E]">Payments</a>
                                <a href="{{ route('admin.payouts.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E]">Payouts</a>
                                <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E]">Subscriptions</a>
                                <a href="{{ route('ceo.reports') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E]">Reports</a>
                            </div>
                        </div>

                        <a href="{{ route('ceo.users') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('ceo.users*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">Users</a>
                        <a href="{{ route('ceo.ai-status') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('ceo.ai-status') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">AI</a>
                        <a href="{{ route('ceo.health') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('ceo.health*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">Health</a>
                    @endif

                    {{-- ── Admin Dropdown Menus ── --}}
                    @if($role === 'admin')
                        {{-- Operations dropdown --}}
                        <div class="relative" x-data="{ opsOpen: false }">
                            <button @click="opsOpen=!opsOpen" @click.outside="opsOpen=false"
                                class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('admin.orders*') || request()->routeIs('admin.consultations*') || request()->routeIs('admin.riders*') || request()->routeIs('admin.applications*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                                Operations
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="opsOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-cloak
                                class="absolute left-0 mt-1 w-52 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50">
                                <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('admin.orders*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    Orders
                                </a>
                                <a href="{{ route('admin.consultations.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('admin.consultations*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    Consultations
                                </a>
                                <a href="{{ route('admin.riders.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('admin.riders*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                    Riders
                                </a>
                                <a href="{{ route('admin.applications.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E] {{ request()->routeIs('admin.applications*') ? 'text-[#0F6B3E] font-semibold' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Applications
                                </a>
                            </div>
                        </div>

                        {{-- Management dropdown --}}
                        <div class="relative" x-data="{ mgmtOpen: false }">
                            <button @click="mgmtOpen=!mgmtOpen" @click.outside="mgmtOpen=false"
                                class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('admin.users') || request()->routeIs('admin.payments*') || request()->routeIs('admin.payouts*') || request()->routeIs('admin.subscriptions*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                                Manage
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="mgmtOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-cloak
                                class="absolute left-0 mt-1 w-48 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50">
                                <a href="{{ route('admin.users') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E]">Users</a>
                                <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E]">Payments</a>
                                <a href="{{ route('admin.payouts.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E]">Payouts</a>
                                <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0F6B3E]">Subscriptions</a>
                            </div>
                        </div>
                    @endif

                    {{-- Finance officer --}}
                    @if($role === 'finance')
                        <a href="{{ route('admin.payments.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('admin.payments.*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">Payments</a>
                        <a href="{{ route('admin.payouts.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('admin.payouts.*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">Payouts</a>
                    @endif

                    {{-- Rider links --}}
                    @if($role === 'rider')
                        <a href="{{ route('rider.orders') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('rider.orders*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                            My Deliveries
                        </a>
                    @endif

                    {{-- Farmer links --}}
                    @if($role === 'farmer')
                        <a href="{{ route('farmer.livestock') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition">
                            Livestock
                        </a>
                        <a href="{{ route('farmer.poultry') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition">
                            Poultry
                        </a>
                        <a href="{{ route('farmer.vet') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition">
                            Vet Request
                        </a>
                        <a href="{{ route('farmer.agro') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition">
                            Agro Advisory
                        </a>
                    @endif

                    {{-- Vet links --}}
                    @if(in_array($role, ['vet', 'agronomist']))
                        <a href="{{ route('vet.queue') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('vet.*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                            Consult Queue
                        </a>
                    @endif

                    {{-- HR links --}}
                    @if(in_array($role, ['hr', 'admin', 'ceo']))
                        <a href="{{ route('hr.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('hr.*') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                            HR
                        </a>
                    @endif

                    {{-- Diagnostics (for all) --}}
                    <a href="{{ route('diagnostics.scan') }}" class="px-3 py-2 rounded-lg text-sm font-semibold text-[#0F6B3E] hover:bg-emerald-50 transition {{ request()->routeIs('diagnostics.*') ? 'bg-emerald-50' : '' }}">
                        AI Scan
                    </a>

                    {{-- Marketplace (for all) --}}
                    <a href="{{ route('marketplace') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('marketplace') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                        Marketplace
                    </a>

                    {{-- Wallet (for all authenticated users) --}}
                    @php $walletBalance = auth()->user()->wallet?->available_balance ?? 0; @endphp
                    <a href="{{ route('wallet.show') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5
                        {{ request()->routeIs('wallet.*') ? 'bg-emerald-50 text-[#0F6B3E] font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E]' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span>Wallet</span>
                        @if($walletBalance > 0)
                            <span class="text-xs font-semibold text-[#0F6B3E]">₦{{ number_format($walletBalance, 0) }}</span>
                        @endif
                    </a>

                </div>
            </div>

            {{-- Right: Language + Notifications + User --}}
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                {{-- Notifications Bell --}}
                <a href="{{ route('notifications.index') }}"
                    class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-[#0F6B3E] transition"
                    title="Notifications" aria-label="View notifications">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($unreadNotifications > 0)
                    <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 leading-none">
                        {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                    </span>
                    @endif
                </a>
                {{-- Language Selector --}}
                @php $loc = session('locale', app()->getLocale()); @endphp
                <div class="relative" x-data="{ langOpen: false }" @click.outside="langOpen=false" @keydown.escape.window="langOpen=false">
                    <button @click="langOpen=!langOpen"
                        :aria-expanded="langOpen.toString()" aria-haspopup="true" aria-label="Select language"
                        class="flex items-center gap-1 px-2 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 transition border border-slate-200">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> {{ strtoupper($loc) }}
                        <svg class="w-3 h-3 text-slate-400 transition-transform duration-200" :class="langOpen ? 'rotate-180' : ''"
                             fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                    <div x-show="langOpen" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         style="display:none;transform-origin:top right"
                        class="absolute right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-gray-100 py-1 w-36 z-50">
                        @foreach([['en','English'],['ha','Hausa'],['fr','Francais'],['yo','Yoruba'],['ig','Igbo'],['ff','Fulfulde'],['ar','Arabic']] as [$code,$name])
                        <form method="POST" action="{{ route('locale.set') }}" class="msas-locale-form">@csrf<input type="hidden" name="locale" value="{{ $code }}">
                        <button type="submit" data-locale-code="{{ $code }}" @click="langOpen=false"
                            class="w-full text-left px-3 py-2 text-xs hover:bg-green-50 hover:text-green-700 flex items-center gap-2 {{ $loc === $code ? 'font-bold text-green-700' : 'text-gray-700' }}">
                            {{ $name }}
                            @if($loc === $code)<span class="ml-auto" data-locale-check="{{ $code }}"><svg width="12" height="12" fill="none" stroke="#0F6B3E" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>@endif
                        </button></form>
                        @endforeach
                    </div>
                </div>
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-xl text-slate-600 bg-white hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150 group">
                            {{-- Avatar --}}
                            @if($user->profile_photo)
                                <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $fullName }}" class="w-8 h-8 rounded-full object-cover border-2 border-[#1FA84A]">
                            @else
                                <div class="w-8 h-8 rounded-full bg-[#0F6B3E] flex items-center justify-center text-white text-sm font-bold border-2 border-[#1FA84A]">
                                    {{ $initial }}
                                </div>
                            @endif
                            {{-- Name & Role --}}
                            <div class="text-left hidden md:block">
                                <div class="text-sm font-semibold text-slate-800 leading-tight">{{ $fullName }}</div>
                                <div class="text-xs font-bold text-[#1FA84A] uppercase tracking-wide">{{ str_replace('-', ' ', $role) }}</div>
                            </div>
                            <svg class="fill-current h-4 w-4 text-slate-400 group-hover:text-[#1FA84A] transition" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="text-sm font-bold text-slate-800">{{ $fullName }}</p>
                            <p class="text-xs text-[#1FA84A] font-semibold uppercase tracking-wide">{{ str_replace('-', ' ', $role) }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $user->email }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ __('My Profile') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('diagnostics.history')">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('Scan History') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('payment.history')">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            {{ __('Payment History') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('profile.security')">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            {{ __('Security') }}
                        </x-dropdown-link>

                        <div class="border-t border-slate-100 mt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 hover:text-red-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    {{ __('Log Out') }}
                                </button>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>{{-- end right panel --}}

            {{-- Hamburger --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" aria-label="Toggle navigation menu" :aria-expanded="open.toString()">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Responsive Menu --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1 px-2">
            <x-responsive-nav-link :href="$dashRoute" :active="request()->routeIs('*.dashboard') || request()->routeIs('ceo.dashboard')">
                Dashboard
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('diagnostics.scan')">AI Scan</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('marketplace')">Marketplace</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('wallet.show')">Wallet</x-responsive-nav-link>
            @if($role === 'farmer')
                <x-responsive-nav-link :href="route('farmer.livestock')">My Livestock</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('farmer.poultry')">Poultry</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('farmer.vet')">Request Vet</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('farmer.agro')">Agro Advisory</x-responsive-nav-link>
            @endif
            @if(in_array($role, ['vet', 'agronomist']))
                <x-responsive-nav-link :href="route('vet.queue')">Consult Queue</x-responsive-nav-link>
            @endif
            @if($role === 'ceo')
                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Staff</div>
                <x-responsive-nav-link :href="route('ceo.staff.index')">All Staff</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ceo.staff.create')">Add Staff</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ceo.staff-roles.index')">Roles & Permissions</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ceo.audit')">Audit Log</x-responsive-nav-link>
                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-2">Operations</div>
                <x-responsive-nav-link :href="route('ceo.orders')">Order Oversight</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.consultations.index')">Consultations</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.riders.index')">Riders</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.applications.index')">Applications</x-responsive-nav-link>
                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-2">Finance & Users</div>
                <x-responsive-nav-link :href="route('ceo.users')">Users</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.payments.index')">Payments</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.payouts.index')">Payouts</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.subscriptions.index')">Subscriptions</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ceo.reports')">Reports</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ceo.ai-status')">AI Status</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ceo.health')">System Health</x-responsive-nav-link>
            @endif
            @if($role === 'admin')
                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Operations</div>
                <x-responsive-nav-link :href="route('admin.orders.index')">Orders</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.consultations.index')">Consultations</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.riders.index')">Riders</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.applications.index')">Applications</x-responsive-nav-link>
                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-2">Management</div>
                <x-responsive-nav-link :href="route('admin.users')">Users</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.payments.index')">Payments</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.payouts.index')">Payouts</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.subscriptions.index')">Subscriptions</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ceo.health')">System Health</x-responsive-nav-link>
            @endif
            @if($role === 'finance')
                <x-responsive-nav-link :href="route('admin.payments.index')">Payments</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.payouts.index')">Payouts</x-responsive-nav-link>
            @endif
            @if($role === 'rider')
                <x-responsive-nav-link :href="route('rider.orders')">My Deliveries</x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4 flex items-center gap-3">
                @if($user->profile_photo)
                    <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $fullName }}" class="w-10 h-10 rounded-full object-cover">
                @else
                    <div class="w-10 h-10 rounded-full bg-[#0F6B3E] flex items-center justify-center text-white font-bold">
                        {{ $initial }}
                    </div>
                @endif
                <div>
                    <div class="font-bold text-base text-slate-800">{{ $fullName }}</div>
                    <div class="text-xs font-semibold text-[#1FA84A] uppercase">{{ str_replace('-', ' ', $role) }}</div>
                    <div class="text-sm text-slate-500">{{ $user->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1 px-2">
                <x-responsive-nav-link :href="route('profile.edit')">My Profile</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('payment.history')">Payment History</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notifications.index')">
                    Notifications
                    @if($unreadNotifications > 0)
                    <span class="ml-2 inline-flex items-center justify-center min-w-[20px] h-5 bg-red-500 text-white text-[10px] font-bold rounded-full px-1">{{ $unreadNotifications }}</span>
                    @endif
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
