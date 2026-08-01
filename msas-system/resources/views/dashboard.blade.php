<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            <span data-i18n="Dashboard">{{ __('Dashboard') }}</span>
        </h2>
    </x-slot>

    @php
        $role = Auth::user()->role;
        $firstName = Auth::user()->displayFirstName;
    @endphp

    <div class="space-y-6">
        {{-- Welcome Banner --}}
        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-64 h-64 bg-emerald-500/20 rounded-full blur-3xl"></div>
            <p class="text-emerald-100 text-sm mb-1 relative z-10" data-i18n="Welcome back,">{{ __('Welcome back,') }}</p>
            <h1 class="text-3xl font-extrabold relative z-10">{{ $firstName }}</h1>
            <p class="text-emerald-50 text-sm mt-2 opacity-90 relative z-10">
                Your personal <span class="font-bold text-amber-400">{{ Auth::user()->roleLabel }}</span> portal is ready.
            </p>
        </div>

        @if($role === 'admin')
        {{-- ADMIN DASHBOARD --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                [route('admin.users'),     'User Management',  '#0F6B3E', '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
                [route('admin.staff'),     'Staff Records',    '#2D9CDB', '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>'],
                [route('admin.settings'),  'System Settings',  '#7C3AED', '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'],
                [route('admin.reports'),   'System Reports',   '#F4A300', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
            ] as [$href, $label, $color, $path])
            <a href="{{ $href }}" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 hover:border-emerald-500 hover:-translate-y-1 transition text-center cursor-pointer group flex flex-col items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition" style="background:{{ $color }}22">
                    <svg width="22" height="22" fill="none" stroke="{{ $color }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $path !!}</svg>
                </div>
                <h3 class="font-bold text-slate-800 text-sm" data-i18n="{{ $label }}">{{ __($label) }}</h3>
            </a>
            @endforeach
        </div>

        @elseif($role === 'finance')
        {{-- FINANCE DASHBOARD --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
            <div class="bg-emerald-50 p-6 rounded-2xl border border-emerald-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <svg width="22" height="22" fill="none" stroke="#059669" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <div><p class="text-xs font-bold text-emerald-800 uppercase mb-1">{{ __('Financial Ledger') }}</p><h2 class="text-2xl font-extrabold text-emerald-700">₦2,450,000</h2></div>
            </div>
            <div class="bg-red-50 p-6 rounded-2xl border border-red-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg width="22" height="22" fill="none" stroke="#dc2626" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 17H5m0 0v-8m0 8l8-8 4 4 6-6"/></svg>
                </div>
                <div><p class="text-xs font-bold text-red-800 uppercase mb-1">{{ __('Financials') }}</p><h2 class="text-2xl font-extrabold text-red-700">₦850,000</h2></div>
            </div>
            <div class="bg-amber-50 p-6 rounded-2xl border border-amber-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg width="22" height="22" fill="none" stroke="#d97706" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div><p class="text-xs font-bold text-amber-800 uppercase mb-1">{{ __('Pending Cases') }}</p><h2 class="text-2xl font-extrabold text-amber-700">₦1,200,000</h2></div>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['Financial Ledger', '#059669', '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>'],
                ['Expenses',        '#dc2626', '<path stroke-linecap="round" stroke-linejoin="round" d="M13 17H5m0 0v-8m0 8l8-8 4 4 6-6"/>'],
                ['Invoices',        '#7C3AED', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
                ['Payment History', '#0288D1', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>'],
            ] as [$label, $color, $path])
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group flex flex-col items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition" style="background:{{ $color }}22">
                    <svg width="22" height="22" fill="none" stroke="{{ $color }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $path !!}</svg>
                </div>
                <h3 class="font-bold text-slate-800 text-sm" data-i18n="{{ $label }}">{{ __($label) }}</h3>
            </div>
            @endforeach
        </div>

        @elseif($role === 'vet')
        {{-- VET DASHBOARD --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                [route('vet.queue'),          'Animal Consultations', '#7C3AED', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>'],
                ['#',                          'Health Reports',      '#0288D1', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
                [route('vet.disease-alerts'),  'Disease Alerts',      '#dc2626', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'],
                [route('vet.vaccinations'),    'Vaccinations',        '#0F6B3E', '<path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>'],
            ] as [$href, $label, $color, $path])
            <a href="{{ $href }}" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group flex flex-col items-center gap-3" style="border-left:3px solid {{ $color }}">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition" style="background:{{ $color }}18">
                    <svg width="22" height="22" fill="none" stroke="{{ $color }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $path !!}</svg>
                </div>
                <h3 class="font-bold text-slate-800 text-sm" data-i18n="{{ $label }}">{{ __($label) }}</h3>
            </a>
            @endforeach
        </div>

        @elseif($role === 'agronomist')
        {{-- AGRONOMIST DASHBOARD --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach([
                [route('vet.queue'), 'Crop Support',       '#0F6B3E', '<path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>'],
                ['#',               'Farm Records',        '#0288D1', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
                [route('vet.queue'), 'Advisory Requests',  '#7C3AED', '<path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>'],
            ] as [$href, $label, $color, $path])
            <a href="{{ $href }}" class="bg-white p-7 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition cursor-pointer group flex items-center gap-5">
                <div class="w-14 h-14 rounded-xl flex-shrink-0 flex items-center justify-center group-hover:scale-110 transition" style="background:{{ $color }}18">
                    <svg width="26" height="26" fill="none" stroke="{{ $color }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $path !!}</svg>
                </div>
                <h3 class="font-bold text-slate-800 text-lg" data-i18n="{{ $label }}">{{ __($label) }}</h3>
            </a>
            @endforeach
        </div>

        @elseif($role === 'rider')
        {{-- RIDER DASHBOARD --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                [route('rider.orders'), 'My Deliveries',   '#0F6B3E', '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>'],
                [route('rider.orders'), 'Pending Dispatch','#F4A300',  '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                ['#',                   'My Earnings',     '#0288D1',  '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                ['#',                   'Route Updates',   '#7C3AED',  '<path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>'],
            ] as [$href, $label, $color, $path])
            <a href="{{ $href }}" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 hover:-translate-y-1 transition text-center cursor-pointer group flex flex-col items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition" style="background:{{ $color }}18">
                    <svg width="22" height="22" fill="none" stroke="{{ $color }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $path !!}</svg>
                </div>
                <h3 class="font-bold text-slate-800 text-sm" data-i18n="{{ $label }}">{{ __($label) }}</h3>
            </a>
            @endforeach
        </div>

        @elseif($role === 'farmer')
        {{-- FARMER DASHBOARD --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            @foreach([
                [route('farmer.livestock'), 'My Livestock',  '#1FA84A', '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>'],
                [route('farmer.poultry'),  'Poultry & Eggs','#b45309',  '<circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M2 12h2m16 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>'],
                [route('farmer.finance'),  'Financials',    '#0288D1',  '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                [route('farmer.vet'),      'Vet Consult',   '#7C3AED',  '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>'],
            ] as [$href, $label, $color, $path])
            <a href="{{ $href }}" class="block bg-white p-5 rounded-2xl shadow-sm border border-slate-100 text-center hover:-translate-y-1 hover:border-current transition cursor-pointer group flex flex-col items-center gap-3" style="--c:{{ $color }}">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition" style="background:{{ $color }}18">
                    <svg width="22" height="22" fill="none" stroke="{{ $color }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $path !!}</svg>
                </div>
                <h3 class="font-bold text-slate-800 text-sm" data-i18n="{{ $label }}">{{ __($label) }}</h3>
            </a>
            @endforeach
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-4 border-b pb-2" data-i18n="Recent Orders & Requests">{{ __('Recent Orders & Requests') }}</h3>
                <div class="text-slate-500 text-sm text-center py-6" data-i18n="No recent orders found.">{{ __('No recent orders found.') }}</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-4 border-b pb-2" data-i18n="Notifications">{{ __('Notifications') }}</h3>
                <div class="flex items-start gap-3 p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <svg width="14" height="14" fill="none" stroke="#0F6B3E" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800" data-i18n="Welcome to MSAS!">{{ __('Welcome to MSAS!') }}</p>
                        <p class="text-xs text-slate-500 mt-0.5" data-i18n="Your farmer account has been created successfully.">{{ __('Your farmer account has been created successfully.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        @else
        {{-- DEFAULT FALLBACK --}}
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <svg width="28" height="28" fill="none" stroke="#94a3b8" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            <p class="text-slate-600 text-sm" data-i18n="You're logged in as a general user. Please update your profile.">{{ __("You're logged in as a general user. Please update your profile.") }}</p>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Update Profile
            </a>
        </div>
        @endif

    </div>
</x-app-layout>
