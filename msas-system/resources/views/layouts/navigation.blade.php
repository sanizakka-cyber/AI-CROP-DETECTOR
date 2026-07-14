@php
    $user = auth()->user();
    $role = $user->role ?? 'user';
    $fullName = $user->name ?: 'User';
    $initial = strtoupper(substr($user->displayFirstName ?: 'U', 0, 1));
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- Logo & Primary Links --}}
            <div class="flex items-center">
                <div class="shrink-0 flex items-center">
                    <a href="/" class="flex items-center gap-2 group">
                        <div class="w-8 h-8 rounded-full bg-[#1FA84A] flex items-center justify-center shadow group-hover:scale-105 transition">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>
                        </div>
                        <span class="font-bold text-[#0F6B3E] tracking-tight">MSAS Portal</span>
                    </a>
                </div>

                {{-- Desktop Nav Links --}}
                <div class="hidden sm:flex sm:items-center sm:ms-8 space-x-1">

                    {{-- Dashboard link (role-aware) --}}
                    @php
                        $dashRoute = match($role) {
                            'ceo'               => route('ceo.dashboard'),
                            'admin'             => route('admin.dashboard'),
                            'farmer'            => route('farmer.dashboard'),
                            'vet'               => route('vet.dashboard'),
                            'agronomist'        => route('agronomist.dashboard'),
                            'agro-dealer'       => route('dealer.dashboard'),
                            'extension-officer' => route('extension.dashboard'),
                            'finance'           => route('finance.dashboard'),
                            'hr'                => route('hr.dashboard'),
                            'operations'        => route('operations.dashboard'),
                            default             => route('dashboard'),
                        };
                    @endphp
                    <a href="{{ $dashRoute }}" class="px-3 py-2 rounded-lg text-sm font-medium transition
                        {{ request()->routeIs('*.dashboard') || request()->routeIs('dashboard') || request()->routeIs('ceo.dashboard')
                            ? 'bg-emerald-50 text-[#0F6B3E] font-semibold'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E]' }}">
                        Dashboard
                    </a>

                    {{-- CEO / Admin links --}}
                    @if(in_array($role, ['ceo', 'admin']))
                        <a href="{{ route('ceo.users') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('ceo.users') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                            Users
                        </a>
                        <a href="{{ route('ceo.reports') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('ceo.reports') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                            Reports
                        </a>
                        <a href="{{ route('admin.users') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-[#0F6B3E] transition {{ request()->routeIs('admin.users') ? 'bg-emerald-50 text-[#0F6B3E]' : '' }}">
                            Admin
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

                </div>
            </div>

            {{-- Right: User Dropdown --}}
            <div class="hidden sm:flex sm:items-center sm:ms-6">
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

                        <div class="border-t border-slate-100 mt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="text-red-600 hover:text-red-700">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
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
            @if($role === 'farmer')
                <x-responsive-nav-link :href="route('farmer.livestock')">My Livestock</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('farmer.poultry')">Poultry</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('farmer.vet')">Request Vet</x-responsive-nav-link>
            @endif
            @if(in_array($role, ['vet', 'agronomist']))
                <x-responsive-nav-link :href="route('vet.queue')">Consult Queue</x-responsive-nav-link>
            @endif
            @if(in_array($role, ['ceo', 'admin']))
                <x-responsive-nav-link :href="route('ceo.users')">Users</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ceo.reports')">Reports</x-responsive-nav-link>
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
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        Log Out
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
