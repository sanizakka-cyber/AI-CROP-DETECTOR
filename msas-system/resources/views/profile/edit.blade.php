<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ $user->role === 'ceo' ? __('Executive Profile Overview') : __('My Profile & Settings') }}
        </h2>
    </x-slot>

    @php
        $user = auth()->user();
        $isCEO = $user->role === 'ceo';
    @endphp

    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Top Profile Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
            <!-- Cover Photo Area -->
            <div class="h-32 {{ $isCEO ? 'bg-gradient-to-r from-slate-900 to-[#0F6B3E]' : 'bg-gradient-to-r from-emerald-500 to-teal-500' }} w-full relative">
                @if($isCEO)
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20"></div>
                @endif
            </div>
            
            <div class="px-8 pb-8 pt-0 relative">
                <!-- Avatar -->
                <div class="relative -mt-16 inline-block">
                    <img src="{{ $user->avatarUrl }}" alt="Profile" class="w-32 h-32 rounded-full border-4 border-white shadow-lg object-cover">
                    <div class="absolute bottom-2 right-2 w-5 h-5 bg-green-500 border-2 border-white rounded-full {{ $user->is_active ? 'animate-pulse' : '' }}" title="Online"></div>
                </div>

                <div class="mt-4 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-900 font-heading">
                            {{ $user->name ?: $user->email }}
                        </h1>
                        <p class="text-lg font-bold uppercase tracking-wider {{ $isCEO ? 'text-amber-600' : 'text-emerald-600' }} mt-1">
                            {{ $user->roleLabel }}
                        </p>
                        <div class="flex items-center gap-4 mt-3 text-sm text-slate-600 font-medium">
                            <span class="flex items-center gap-1"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> {{ $user->email }}</span>
                            <span class="flex items-center gap-1"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> {{ $user->phone }}</span>
                            <span class="flex items-center gap-1"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg> {{ $user->department ?? 'General' }}</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="text-right text-sm">
                            <p class="text-slate-500">System Status</p>
                            <p class="font-bold text-green-600">Active Now</p>
                        </div>
                        <div class="text-right text-sm border-l pl-3">
                            <p class="text-slate-500">Staff ID</p>
                            <p class="font-bold text-slate-800 tracking-widest text-xs">{{ $user->staffId }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isCEO)
        <!-- CEO Quick Access Executive Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-navy rounded-xl p-6 shadow-sm border border-slate-200">
                <h4 class="text-emerald-400 font-bold mb-1">Company Summary</h4>
                <p class="text-slate-300 text-sm">MSAS Livestock & Agro Services is leading the digital agricultural revolution in Nigeria.</p>
            </div>
            <a href="{{ route('ceo.reports') }}" class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:border-emerald-500 hover:shadow-md transition group block cursor-pointer">
                <div class="mb-2 group-hover:scale-110 transition"><svg width="32" height="32" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 20V10M12 20V4M6 20v-6"/></svg></div>
                <h4 class="font-bold text-slate-800">Financial Reports</h4>
                <p class="text-xs text-slate-500">View company performance</p>
            </a>
            <a href="{{ route('ceo.users') }}" class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:border-emerald-500 hover:shadow-md transition group block cursor-pointer">
                <div class="mb-2 group-hover:scale-110 transition"><svg width="32" height="32" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div>
                <h4 class="font-bold text-slate-800">Staff Management</h4>
                <p class="text-xs text-slate-500">Manage all MSAS personnel</p>
            </a>
            <a href="{{ route('ceo.dashboard') }}" class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:border-emerald-500 hover:shadow-md transition group block cursor-pointer">
                <div class="mb-2 group-hover:scale-110 transition"><svg width="32" height="32" fill="none" stroke="#0F6B3E" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l3.057 3.516L12 2l3.943 4.516L19 3v13H5V3zm0 13v5h14v-5"/></svg></div>
                <h4 class="font-bold text-slate-800">CEO Privileges</h4>
                <p class="text-xs text-slate-500">Full system override</p>
            </a>
        </div>
        @endif

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Left Column: Settings -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Information -->
                <div class="bg-white shadow-sm rounded-xl p-8 border border-slate-200">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <!-- Update Password -->
                <div class="bg-white shadow-sm rounded-xl p-8 border border-slate-200">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>

            <!-- Right Column: Extras -->
            <div class="space-y-6">
                <!-- Account Status Card -->
                <div class="bg-white shadow-sm rounded-xl p-6 border border-slate-200">
                    <h3 class="font-bold text-lg text-slate-800 mb-4 border-b pb-2">Account Summary</h3>
                    <ul class="space-y-3 text-sm">
                        <li class="flex justify-between">
                            <span class="text-slate-500">Role Privilege</span>
                            <span class="font-bold text-slate-800 uppercase">{{ $user->role }}</span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-slate-500">Registration Date</span>
                            <span class="font-bold text-slate-800">{{ $user->created_at->format('M d, Y') }}</span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-slate-500">Last Login</span>
                            <span class="font-bold text-slate-800">Just Now</span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-slate-500">Verification</span>
                            @if($user->email_verified_at)
                                <span class="font-bold text-emerald-600 bg-emerald-50 px-2 rounded-lg">Verified</span>
                            @else
                                <span class="font-bold text-amber-600 bg-amber-50 px-2 rounded-lg">Pending</span>
                            @endif
                        </li>
                    </ul>
                </div>

                <!-- Delete Account -->
                <div class="bg-red-50 shadow-sm rounded-xl p-8 border border-red-100">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
