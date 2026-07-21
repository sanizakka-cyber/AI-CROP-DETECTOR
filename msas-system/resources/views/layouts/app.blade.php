<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MSAS — {{ config('app.name', 'Livestock & Agro Services') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800|poppins:600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        h1,h2,h3,h4,h5,h6 { font-family: 'Poppins', sans-serif; }

        /* ── Brand variables ──────────────────────────── */
        :root {
            --green-primary: #0F6B3E;
            --green-secondary: #1FA84A;
            --blue-tech: #2D9CDB;
            --gold: #F4A300;
            --navy: #0B2447;
            --white: #FFFFFF;
        }

        /* ── Sidebar ──────────────────────────────────── */
        .sidebar { background: linear-gradient(180deg, #0B2447 0%, #0F3460 50%, #0B2447 100%); }
        .sidebar-logo-area { border-bottom: 1px solid rgba(255,255,255,0.08); }

        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px; border-radius: 10px; margin: 1px 8px;
            font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.65);
            text-decoration: none; transition: all 0.2s ease; position: relative;
        }
        .nav-link:hover { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.95); }
        .nav-link.active { background: linear-gradient(135deg, #0F6B3E, #1FA84A); color: #fff; box-shadow: 0 4px 15px rgba(31,168,74,0.3); }
        .nav-link .nav-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: rgba(255,255,255,0.07); transition: background 0.2s; }
        .nav-link:hover .nav-icon { background: rgba(255,255,255,0.12); }
        .nav-link.active .nav-icon { background: rgba(255,255,255,0.2); }

        .nav-section { font-size: 10px; font-weight: 700; letter-spacing: 0.1em; color: rgba(255,255,255,0.3); text-transform: uppercase; padding: 16px 24px 6px; }

        /* ── Top header ───────────────────────────────── */
        .top-header { background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

        /* ── Search ───────────────────────────────────── */
        .search-input {
            background: #f8fafc; border: 1.5px solid #e2e8f0;
            border-radius: 10px; padding: 8px 14px 8px 38px;
            font-size: 13px; color: #475569; outline: none;
            transition: all 0.2s; width: 220px;
        }
        .search-input:focus { border-color: #0F6B3E; width: 280px; background: #fff; box-shadow: 0 0 0 3px rgba(15,107,62,0.1); }

        /* ── Status pulse ─────────────────────────────── */
        @@keyframes pulse-green { 0%,100% { box-shadow: 0 0 0 0 rgba(31,168,74,0.5); } 70% { box-shadow: 0 0 0 6px rgba(31,168,74,0); } }
        .status-dot { width: 9px; height: 9px; border-radius: 50%; background: #1FA84A; border: 2px solid #fff; animation: pulse-green 2.5s infinite; }

        /* ── Notification badge ───────────────────────── */
        .notif-btn { position: relative; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s; color: #64748b; }
        .notif-btn:hover { background: #f0fdf4; border-color: #0F6B3E; color: #0F6B3E; }
        .notif-badge { position: absolute; top: -4px; right: -4px; width: 16px; height: 16px; border-radius: 50%; background: #ef4444; color: #fff; font-size: 9px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; }

        /* ── Role badge ───────────────────────────────── */
        .role-badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.05em; }

        /* ── Role colors ──────────────────────────────── */
        .role-ceo,.role-admin { background: rgba(244,163,0,0.15); color: #92400e; }
        .role-farmer { background: rgba(31,168,74,0.15); color: #065f46; }
        .role-vet,.role-agronomist { background: rgba(45,156,219,0.15); color: #1e40af; }
        .role-finance,.role-hr { background: rgba(99,102,241,0.15); color: #4338ca; }
        .role-other { background: rgba(100,116,139,0.15); color: #475569; }

        /* ── Scrollbar ────────────────────────────────── */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 2px; }

        /* ── Page content ─────────────────────────────── */
        .page-content { background: #f1f5f9; }

        /* ── Dropdown ─────────────────────────────────── */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased" x-data="{ sidebarOpen: true, notifOpen: false, profileOpen: false }">

<div class="flex h-screen overflow-hidden">

    <!-- ══════════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════════ -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-[70px]'" class="sidebar flex flex-col transition-all duration-300 z-30 shadow-2xl flex-shrink-0">

        <!-- Logo -->
        <div class="sidebar-logo-area h-16 flex items-center px-4 gap-3 flex-shrink-0">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#0F6B3E,#1FA84A); box-shadow:0 4px 12px rgba(31,168,74,0.4);">
                <svg width="20" height="20" viewBox="0 0 32 32" fill="none">
                    <path d="M16 3C9.373 3 4 8.373 4 15c0 4.418 2.239 8.309 5.636 10.6L9 29h14l-.636-3.4C25.761 23.309 28 19.418 28 15c0-6.627-5.373-12-12-12z" fill="white" fill-opacity="0.9"/>
                    <path d="M13 15l2 2 5-5" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div x-show="sidebarOpen" class="overflow-hidden">
                <div class="font-bold text-white text-sm leading-tight whitespace-nowrap">MSAS FarmAI</div>
                <div class="text-xs whitespace-nowrap" style="color:rgba(255,255,255,0.45);">Agro Services Portal</div>
            </div>
        </div>

        <!-- User summary -->
        <div class="px-4 py-4 border-b flex-shrink-0" style="border-color:rgba(255,255,255,0.08);">
            <div class="flex items-center gap-3">
                <div class="relative flex-shrink-0">
                    <img src="{{ auth()->user()->avatarUrl }}"
                         alt="Profile" class="w-9 h-9 rounded-xl object-cover border-2" style="border-color:rgba(255,255,255,0.2);">
                    <div class="status-dot absolute -bottom-1 -right-1"></div>
                </div>
                <div x-show="sidebarOpen" class="overflow-hidden">
                    <div class="text-sm font-semibold text-white leading-tight truncate max-w-[140px]">{{ auth()->user()->name }}</div>
                    <div class="text-xs mt-0.5 font-medium" style="color:#F4A300;">{{ auth()->user()->roleLabel }}</div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-3 sidebar-scroll">

            <div x-show="sidebarOpen" class="nav-section">Main</div>

            <!-- Dashboard -->
            <a href="{{ auth()->user()->role === 'ceo' ? route('ceo.dashboard') : route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard','ceo.dashboard') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                </span>
                <span x-show="sidebarOpen" data-i18n="Dashboard">{{ __('Dashboard') }}</span>
            </a>

            <!-- AI Scan — only for roles that can reach /diagnostics/scan -->
            @if(in_array(auth()->user()->role, ['farmer','admin','ceo','vet','agronomist']))
            <a href="{{ route('diagnostics.scan') }}" class="nav-link {{ request()->routeIs('diagnostics.*') ? 'active' : '' }}">
                <span class="nav-icon" style="background:rgba(31,168,74,0.2);">
                    <svg width="16" height="16" fill="none" stroke="#1FA84A" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </span>
                <span x-show="sidebarOpen" class="font-semibold" style="color:#1FA84A;" data-i18n="AI Smart Scan">{{ __('AI Smart Scan') }}</span>
            </a>
            @endif

            @php $role = auth()->user()->role; @endphp

            {{-- CEO / Admin --}}
            @if(in_array($role, ['ceo','admin']))
            <div x-show="sidebarOpen" class="nav-section" data-i18n="Management">{{ __('Management') }}</div>
            <a href="{{ route('ceo.users') }}" class="nav-link {{ request()->routeIs('ceo.users') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Users & Staff">{{ __('Users & Staff') }}</span>
            </a>
            @if($role === 'ceo')
            <a href="{{ route('ceo.staff.index') }}" class="nav-link {{ request()->routeIs('ceo.staff*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
                <span x-show="sidebarOpen">Staff Management</span>
            </a>
            <a href="{{ route('ceo.staff-roles.index') }}" class="nav-link {{ request()->routeIs('ceo.staff-roles*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></span>
                <span x-show="sidebarOpen">Staff Roles</span>
            </a>
            @endif
            <a href="{{ route('ceo.reports') }}" class="nav-link {{ request()->routeIs('ceo.reports*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Reports & Analytics">{{ __('Reports & Analytics') }}</span>
            </a>
            <a href="{{ route('admin.applications.index') }}" class="nav-link {{ request()->routeIs('admin.applications.*') ? 'active' : '' }}">
                <span class="nav-icon" style="position:relative;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    @php
                        try {
                            $pendingCount = \App\Models\User::where('application_status','pending')->whereNotIn('role',['farmer','general-user','ceo','admin'])->count();
                        } catch (\Exception $e) {
                            $pendingCount = 0;
                        }
                    @endphp
                    @if($pendingCount > 0)
                    <span style="position:absolute;top:-4px;right:-4px;min-width:14px;height:14px;background:#ef4444;color:#fff;font-size:9px;font-weight:800;border-radius:99px;display:inline-flex;align-items:center;justify-content:center;padding:0 2px;">{{ $pendingCount > 9 ? '9+' : $pendingCount }}</span>
                    @endif
                </span>
                <span x-show="sidebarOpen">Applications</span>
            </a>
            <a href="{{ route('admin.subscriptions.index') }}" class="nav-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Subscriptions">{{ __('Subscriptions') }}</span>
            </a>
            @endif

            {{-- Farmer --}}
            @if($role === 'farmer')
            <div x-show="sidebarOpen" class="nav-section" data-i18n="Farm Management">{{ __('Farm Management') }}</div>
            <a href="{{ route('farmer.livestock') }}" class="nav-link {{ request()->routeIs('farmer.livestock') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="My Livestock">{{ __('My Livestock') }}</span>
            </a>
            <a href="{{ route('farmer.poultry') }}" class="nav-link {{ request()->routeIs('farmer.poultry') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="My Poultry">{{ __('My Poultry') }}</span>
            </a>
            <a href="{{ route('farmer.vet') }}" class="nav-link {{ request()->routeIs('farmer.vet') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Request Vet">{{ __('Request Vet') }}</span>
            </a>
            <a href="{{ route('farmer.agro') }}" class="nav-link {{ request()->routeIs('farmer.agro') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Agro Advisory">{{ __('Agro Advisory') }}</span>
            </a>
            {{-- Farmer Selling + Orders --}}
            <div x-show="sidebarOpen" class="nav-section">{{ __('Marketplace') }}</div>
            <a href="{{ route('marketplace.sell') }}" class="nav-link {{ request()->routeIs('marketplace.sell*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                <span x-show="sidebarOpen">{{ __('Sell Produce') }}</span>
            </a>
            <a href="{{ route('marketplace.orders') }}" class="nav-link {{ request()->routeIs('marketplace.orders*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></span>
                <span x-show="sidebarOpen">{{ __('My Orders') }}</span>
            </a>
            @endif

            {{-- Vet --}}
            @if($role === 'vet')
            <div x-show="sidebarOpen" class="nav-section" data-i18n="Veterinary">{{ __('Veterinary') }}</div>
            <a href="{{ route('vet.queue') }}" class="nav-link {{ request()->routeIs('vet.queue','vet.show','vet.respond') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Consultations">{{ __('Consultations') }}</span>
            </a>
            <a href="{{ route('vet.vaccinations') }}" class="nav-link {{ request()->routeIs('vet.vaccinations') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Vaccinations">{{ __('Vaccinations') }}</span>
            </a>
            <a href="{{ route('vet.disease-alerts') }}" class="nav-link {{ request()->routeIs('vet.disease-alerts') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Disease Alerts">{{ __('Disease Alerts') }}</span>
            </a>
            @endif

            {{-- Agronomist --}}
            @if($role === 'agronomist')
            <div x-show="sidebarOpen" class="nav-section" data-i18n="Agronomy">{{ __('Agronomy') }}</div>
            <a href="#" class="nav-link">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Crop Requests">{{ __('Crop Requests') }}</span>
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Soil Reports">{{ __('Soil Reports') }}</span>
            </a>
            @endif

            {{-- Finance --}}
            @if($role === 'finance')
            <div x-show="sidebarOpen" class="nav-section" data-i18n="Finance">{{ __('Finance') }}</div>
            <a href="#" class="nav-link">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Income & Expenses">{{ __('Income & Expenses') }}</span>
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Payroll">{{ __('Payroll') }}</span>
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Financial Reports">{{ __('Financial Reports') }}</span>
            </a>
            @endif

            {{-- HR --}}
            @if($role === 'hr')
            <div x-show="sidebarOpen" class="nav-section" data-i18n="Human Resources">{{ __('Human Resources') }}</div>
            <a href="#" class="nav-link">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Staff Records">{{ __('Staff Records') }}</span>
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Attendance & Leave">{{ __('Attendance & Leave') }}</span>
            </a>
            @endif

            {{-- Agro Dealer --}}
            @if($role === 'agro-dealer')
            <div x-show="sidebarOpen" class="nav-section">Inventory</div>
            <a href="{{ route('dealer.products.index') }}" class="nav-link {{ request()->routeIs('dealer.products.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                <span x-show="sidebarOpen">Product Catalog</span>
            </a>
            <a href="{{ route('dealer.orders') }}" class="nav-link {{ request()->routeIs('dealer.orders') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></span>
                <span x-show="sidebarOpen">Sales & Orders</span>
            </a>
            @endif

            {{-- Equipment Dealer --}}
            @if($role === 'equipment-dealer')
            <div x-show="sidebarOpen" class="nav-section">Inventory</div>
            <a href="{{ route('equipment-dealer.products.index') }}" class="nav-link {{ request()->routeIs('equipment-dealer.products.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                <span x-show="sidebarOpen">My Inventory</span>
            </a>
            <a href="{{ route('equipment-dealer.orders') }}" class="nav-link {{ request()->routeIs('equipment-dealer.orders') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></span>
                <span x-show="sidebarOpen">Sales & Orders</span>
            </a>
            @endif

            {{-- Logistics Provider --}}
            @if($role === 'logistics-provider')
            <div x-show="sidebarOpen" class="nav-section">Fleet</div>
            <a href="{{ route('logistics.vehicles') }}" class="nav-link {{ request()->routeIs('logistics.vehicles') ? 'active' : '' }}">
                <span class="nav-icon">🚛</span>
                <span x-show="sidebarOpen">My Vehicles</span>
            </a>
            <a href="{{ route('logistics.drivers') }}" class="nav-link {{ request()->routeIs('logistics.drivers') ? 'active' : '' }}">
                <span class="nav-icon">👨‍✈️</span>
                <span x-show="sidebarOpen">Drivers</span>
            </a>
            <a href="{{ route('logistics.deliveries') }}" class="nav-link {{ request()->routeIs('logistics.deliveries') ? 'active' : '' }}">
                <span class="nav-icon">📦</span>
                <span x-show="sidebarOpen">Deliveries</span>
            </a>
            @endif

            {{-- Agribusiness Owner --}}
            @if($role === 'agribusiness-owner')
            <div x-show="sidebarOpen" class="nav-section">Business</div>
            <a href="{{ route('marketplace.sell') }}" class="nav-link {{ request()->routeIs('marketplace.sell*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                <span x-show="sidebarOpen">My Products</span>
            </a>
            @endif

            {{-- Input Supplier --}}
            @if($role === 'input-supplier')
            <div x-show="sidebarOpen" class="nav-section">Supply</div>
            <a href="{{ route('marketplace.sell') }}" class="nav-link {{ request()->routeIs('marketplace.sell*') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                <span x-show="sidebarOpen">My Inputs</span>
            </a>
            @endif

            {{-- Subscription link — all roles that need a subscription --}}
            @if(!in_array($role, ['ceo', 'admin', 'general-user']))
            @php $sub = auth()->user()->activeSubscription(); @endphp
            <div x-show="sidebarOpen" class="nav-section">Subscription</div>
            <a href="{{ route('subscription.dashboard') }}" class="nav-link {{ request()->routeIs('subscription.*') ? 'active' : '' }}"
               style="{{ !$sub ? 'border-left:3px solid #F4A300;' : '' }}">
                <span class="nav-icon">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </span>
                <span x-show="sidebarOpen" style="display:flex;align-items:center;gap:6px;">
                    <span data-i18n="My Plan">{{ __('My Plan') }}</span>
                    @if($sub)
                        <span style="background:{{ config('subscription.plans.'.$sub->plan.'.badge_color','#1FA84A') }};color:#fff;font-size:9px;font-weight:800;padding:1px 6px;border-radius:10px;">{{ strtoupper(str_replace(['professional_', '_'], ['', ' '], $sub->plan)) }}</span>
                    @else
                        <span style="background:#F4A300;color:#fff;font-size:9px;font-weight:800;padding:1px 6px;border-radius:10px;">FREE</span>
                    @endif
                </span>
            </a>
            @endif

            <!-- Common for all -->
            <div x-show="sidebarOpen" class="nav-section" data-i18n="General">{{ __('General') }}</div>
            <a href="{{ route('marketplace') }}" class="nav-link {{ request()->routeIs('marketplace') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="Marketplace">{{ __('Marketplace') }}</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></span>
                <span x-show="sidebarOpen" data-i18n="My Profile">{{ __('My Profile') }}</span>
            </a>
        </nav>

        <!-- Logout -->
        <div class="p-3 border-t flex-shrink-0" style="border-color:rgba(255,255,255,0.08);">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all" style="color:rgba(255,100,100,0.8);" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'">
                    <span class="nav-icon" style="background:rgba(239,68,68,0.1); flex-shrink:0;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </span>
                    <span x-show="sidebarOpen" class="text-sm font-semibold" data-i18n="Sign Out">{{ __('Sign Out') }}</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- ══════════════════════════════════════════════════
         MAIN CONTENT
    ══════════════════════════════════════════════════ -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Top Header -->
        <header class="top-header h-16 flex items-center justify-between px-5 gap-4 flex-shrink-0 z-20">

            <!-- Left: Toggle + Page title -->
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all"
                    style="border-color:#e2e8f0; color:#64748b;"
                    onmouseover="this.style.borderColor='#0F6B3E';this.style.color='#0F6B3E'"
                    onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                </button>

                <!-- Breadcrumb / Page header -->
                <div class="hidden sm:block">
                    @isset($header)
                        <div class="text-slate-800">{{ $header }}</div>
                    @endisset
                </div>
            </div>

            <!-- Right: Search + Actions + Profile -->
            <div class="flex items-center gap-3">

                <!-- Search -->
                <div class="relative hidden md:block">
                    <div style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    </div>
                    <input class="search-input" type="text" placeholder="{{ __('Search...') }}" data-i18n-placeholder="Search..."/>
                </div>

                <!-- Notifications -->
                @php
                    try {
                        $notifCount  = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
                        $recentNotifs = \App\Models\Notification::where('user_id', auth()->id())->latest()->limit(5)->get();
                    } catch (\Exception $e) {
                        $notifCount = 0;
                        $recentNotifs = collect();
                    }
                @endphp
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="notif-btn" aria-label="Notifications" :aria-expanded="open.toString()">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if($notifCount > 0)<span class="notif-badge">{{ $notifCount > 99 ? '99+' : $notifCount }}</span>@endif
                    </button>
                    <div x-show="open" @click.outside="open=false" x-cloak
                         class="absolute right-0 top-12 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                            <span class="font-bold text-slate-800 text-sm">Notifications</span>
                            @if($notifCount > 0)
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background:rgba(15,107,62,0.1);color:#0F6B3E;">{{ $notifCount }} new</span>
                            @endif
                        </div>
                        <div class="divide-y divide-slate-50 max-h-72 overflow-y-auto">
                            @forelse($recentNotifs as $notif)
                            @php
                                $nColor = match($notif->type) { 'success'=>'#1FA84A','warning'=>'#F4A300','danger'=>'#EF4444',default=>'#2D9CDB' };
                                $nBg    = match($notif->type) { 'success'=>'rgba(31,168,74,0.1)','warning'=>'rgba(244,163,0,0.1)','danger'=>'rgba(239,68,68,0.1)',default=>'rgba(45,156,219,0.1)' };
                            @endphp
                            <a href="{{ $notif->link ?: route('notifications.index') }}" class="flex gap-3 p-3 hover:bg-slate-50 transition {{ !$notif->is_read ? 'bg-emerald-50/50' : '' }}">
                                <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center" style="background:{{ $nBg }}">
                                    <svg width="14" height="14" fill="none" stroke="{{ $nColor }}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-700 truncate">{{ $notif->title }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ $notif->message }}</p>
                                    <p class="text-[10px] text-slate-300 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                                </div>
                            </a>
                            @empty
                            <div class="p-6 text-center">
                                <p class="text-xs text-slate-400 font-medium">No notifications yet</p>
                            </div>
                            @endforelse
                        </div>
                        <div class="p-3 text-center border-t border-slate-100">
                            <a href="{{ route('notifications.index') }}" class="text-xs font-semibold" style="color:#0F6B3E;">View all notifications</a>
                        </div>
                    </div>
                </div>

                <!-- Language Switcher -->
                <div class="relative" x-data="{ open: false }">
                    @php
                        $locales = ['en'=>'English','ha'=>'Hausa','yo'=>'Yorùbá','ig'=>'Igbo','ff'=>'Fulfulde','fr'=>'Français'];
                        $cur = app()->getLocale();
                        $flags = ['en'=>'🇬🇧','ha'=>'🇳🇬','yo'=>'🇳🇬','ig'=>'🇳🇬','ff'=>'🇳🇬','fr'=>'🇫🇷'];
                    @endphp
                    <button @click="open = !open" class="notif-btn" style="width:auto;padding:0 10px;gap:5px;font-size:12px;font-weight:700;color:#475569;">
                        <span data-locale-current="flag">{{ $flags[$cur] ?? '🌍' }}</span>
                        <span class="hidden sm:inline" data-locale-current="code">{{ strtoupper($cur) }}</span>
                        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open=false" x-cloak
                         class="absolute right-0 top-12 w-44 bg-white rounded-xl shadow-xl border border-slate-100 z-50 overflow-hidden py-1">
                        @foreach($locales as $code => $name)
                        <form method="POST" action="{{ route('locale.set') }}" class="msas-locale-form">
                            @csrf
                            <input type="hidden" name="locale" value="{{ $code }}">
                            <button type="submit" data-locale-code="{{ $code }}"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm hover:bg-slate-50 transition text-left {{ $cur === $code ? 'font-bold' : '' }}"
                                style="{{ $cur === $code ? 'color:#0F6B3E;' : 'color:#475569;' }}">
                                <span>{{ $flags[$code] }}</span>
                                <span>{{ $name }}</span>
                                @if($cur === $code)<span class="ml-auto text-xs" style="color:#0F6B3E;" data-locale-check="{{ $code }}">✓</span>@endif
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>

                <!-- Divider -->
                <div class="w-px h-6 bg-slate-200"></div>

                <!-- Profile dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-200">
                        <img src="{{ auth()->user()->avatarUrl }}"
                             alt="" class="w-8 h-8 rounded-lg object-cover">
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-bold text-slate-800 leading-tight">{{ auth()->user()->displayFirstName }}</div>
                            @php
                            $roleClass = match(auth()->user()->role) {
                                'ceo','admin'                                                        => 'role-ceo',
                                'farmer'                                                             => 'role-farmer',
                                'vet','agronomist','extension-officer','field-officer','data-analyst' => 'role-vet',
                                'finance','hr','m-e-officer','operations'                            => 'role-finance',
                                default                                                              => 'role-other'
                            };
                            @endphp
                            <span class="role-badge {{ $roleClass }}">{{ auth()->user()->roleLabel }}</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open=false" x-cloak
                         class="absolute right-0 top-12 w-56 bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden">
                        <div class="p-4 border-b border-slate-100">
                            <div class="font-bold text-sm text-slate-800">{{ auth()->user()->name ?: auth()->user()->email }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ auth()->user()->email }}</div>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                My Profile
                            </a>
                            <a href="{{ route('profile.edit') }}#settings" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Settings
                            </a>
                        </div>
                        <div class="p-2 border-t border-slate-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-sm font-semibold text-red-500 hover:bg-red-50 rounded-xl transition">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto page-content">

            {{-- ── Impersonation banner ────────────────────────────────────────────── --}}
            @if(session()->has('impersonate.original_id'))
            @php $imp = \App\Models\User::find(session('impersonate.original_id')); @endphp
            <div class="sticky top-0 z-50 bg-amber-500 text-amber-950 text-xs font-bold px-4 py-2 flex items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="text-base">👁</span>
                    <span>
                        Viewing as <strong>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</strong>
                        ({{ auth()->user()->role }})
                        &mdash; logged in as {{ $imp?->first_name }} {{ $imp?->last_name }}
                    </span>
                </div>
                <a href="{{ route('impersonate.leave') }}"
                   class="bg-amber-900 hover:bg-amber-800 text-amber-100 px-3 py-1 rounded-lg text-[11px] font-bold transition">
                    ✕ Leave Impersonation
                </a>
            </div>
            @endif

            {{-- ── Subscription nag for professional roles without active plan ─── --}}
            @php
                $__authUser = auth()->user();
                $__isProfRole = $__authUser && !in_array($__authUser->role, ['farmer', 'ceo', 'admin', 'general-user']);
                $__hasSub = $__isProfRole && $__authUser->activeSubscription();
            @endphp
            @if($__isProfRole && !$__hasSub)
            <div style="background:#fffbeb;border-bottom:2px solid #fcd34d;padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <svg width="18" height="18" fill="none" stroke="#b45309" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <span style="font-size:13px;font-weight:600;color:#92400e;">No active subscription — some features are restricted until you subscribe.</span>
                </div>
                <a href="{{ route('subscription.plans') }}" style="background:#F4A300;color:#fff;padding:6px 16px;border-radius:8px;font-size:12px;font-weight:800;text-decoration:none;white-space:nowrap;">
                    View Plans →
                </a>
            </div>
            @endif

            <div class="p-6">
            {{ $slot }}
            </div>{{-- end p-6 --}}
        </main>

    </div>

</div>

{{-- ── Language transition overlay ───────────────────────────────────── --}}
<div id="locale-overlay" style="display:none;position:fixed;inset:0;background:rgba(15,27,71,0.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:20px 32px;display:flex;align-items:center;gap:12px;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <svg style="width:22px;height:22px;animation:spin 0.8s linear infinite;color:#0F6B3E;" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40 20"/></svg>
        <span style="font-weight:700;font-size:14px;color:#0F3460;" id="locale-overlay-msg">Switching language…</span>
    </div>
</div>
<style>@@keyframes spin{to{transform:rotate(360deg)}}</style>

{{-- ── Translations bundle + instant-switch engine ──────────────────── --}}
@php
    $allTrans = [];
    foreach (['en','ha','fr','yo','ig','ff'] as $_loc) {
        $p = lang_path($_loc.'.json');
        $allTrans[$_loc] = file_exists($p) ? json_decode(file_get_contents($p), true) : [];
    }
@endphp
<script>
(function(){
    window.MSAS_TRANS  = {!! json_encode($allTrans) !!};
    window.MSAS_LOCALE = '{{ app()->getLocale() }}';

    var flagMap = { en:'🇬🇧', ha:'🇳🇬', fr:'🇫🇷', yo:'🇳🇬', ig:'🇳🇬', ff:'🇳🇬' };
    var nameMap = { en:'English', ha:'Hausa', fr:'Français', yo:'Yorùbá', ig:'Igbo', ff:'Fulfulde' };
    var overlayMsgs = { en:'Switching language…', ha:'Ana canza harshe…', fr:'Changement de langue…', yo:'Ń paarọ èdè…', ig:'Na-agbanwe asụsụ…', ff:'Binnditii hakkunde picce…' };

    /* Apply translations to all [data-i18n] and [data-i18n-placeholder] elements */
    function applyLocale(locale) {
        var dict = window.MSAS_TRANS[locale] || window.MSAS_TRANS['en'] || {};
        document.querySelectorAll('[data-i18n]').forEach(function(el) {
            var key = el.getAttribute('data-i18n');
            if (dict[key] !== undefined) el.textContent = dict[key];
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach(function(el) {
            var key = el.getAttribute('data-i18n-placeholder');
            if (dict[key] !== undefined) el.placeholder = dict[key];
        });
        /* Sync voice-narration language selectors and restart active narration in new language */
        document.querySelectorAll('select[id$="-lang"]').forEach(function(sel) {
            if (sel.querySelector('option[value="'+locale+'"]')) {
                var prevLocale = sel.value;
                sel.value = locale;
                if (locale !== prevLocale && typeof window.ttsChangeLang === 'function') {
                    var ttsId        = sel.id.replace(/-lang$/, '');
                    var translateUrl = sel.getAttribute('data-translate-url') || '';
                    window.ttsChangeLang(ttsId, locale, translateUrl);
                }
            }
        });
        /* Update current-locale indicators */
        document.querySelectorAll('[data-locale-current]').forEach(function(el) {
            var t = el.getAttribute('data-locale-current');
            if (t === 'flag') el.textContent = flagMap[locale] || '🌍';
            if (t === 'code') el.textContent = locale.toUpperCase();
            if (t === 'name') el.textContent = nameMap[locale] || locale;
        });
        /* Sync the mobile nav selector value if present */
        var mobileLocSel = document.getElementById('mobile-locale-select');
        if (mobileLocSel) mobileLocSel.value = locale;
        window.MSAS_LOCALE = locale;
    }

    /* Intercept locale form submits for instant client-side switch */
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form.classList.contains('msas-locale-form')) return;
        e.preventDefault();
        var locale = (form.querySelector('[name=locale]') || {}).value || 'en';

        /* Show overlay */
        var ov = document.getElementById('locale-overlay');
        var msg = document.getElementById('locale-overlay-msg');
        if (ov) { ov.style.display = 'flex'; }
        if (msg) msg.textContent = overlayMsgs[locale] || 'Switching language…';

        /* Apply translations instantly */
        applyLocale(locale);

        /* Persist to server — no redirect needed */
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'locale=' + encodeURIComponent(locale) + '&_token=' + encodeURIComponent(document.querySelector('meta[name="csrf-token"]').content),
        })
        .then(function(){ setTimeout(function(){ if(ov) ov.style.display='none'; }, 300); })
        .catch(function(){ if(ov) ov.style.display='none'; });
    });

    /* Close Alpine dropdown after selection */
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.msas-locale-form button[data-locale-code]');
        if (!btn) return;
        var locale = btn.getAttribute('data-locale-code');
        /* Update checkmarks */
        document.querySelectorAll('[data-locale-check]').forEach(function(el){ el.style.display='none'; });
        var check = document.querySelector('[data-locale-check="'+locale+'"]');
        if (!check) {
            /* Create checkmark span if it doesn't exist yet */
            var newCheck = document.createElement('span');
            newCheck.setAttribute('data-locale-check', locale);
            newCheck.style.cssText = 'margin-left:auto;font-size:12px;color:#0F6B3E;';
            newCheck.textContent = '✓';
            btn.appendChild(newCheck);
        } else {
            check.style.display = '';
        }
        /* Update button styles */
        document.querySelectorAll('[data-locale-code]').forEach(function(b){
            b.style.color = b.getAttribute('data-locale-code') === locale ? '#0F6B3E' : '#475569';
            b.style.fontWeight = b.getAttribute('data-locale-code') === locale ? 'bold' : '';
        });
    });
})();
</script>
</body>
</html>
