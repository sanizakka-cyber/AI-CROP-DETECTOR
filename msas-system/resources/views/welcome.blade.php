<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MSAS Agro | Smart Agriculture Platform — Nigeria</title>
    <meta name="description" content="MSAS Agro is Nigeria's leading AI-powered agribusiness platform for farmers, livestock owners, cooperatives, governments and development partners.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800|inter:300,400,500,600,700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        :root{--green:#2E7D32;--green-dark:#1B5E20;--green-light:#E8F5E9;--gold:#F9A825;--gold-dark:#F57F17;--blue:#0288D1;--footer-bg:#071f0f;}
        *,*::before,*::after{box-sizing:border-box;}
        body{font-family:'Inter',sans-serif;color:#212121;background:#fff;overflow-x:hidden;}
        h1,h2,h3,h4,.font-heading{font-family:'Poppins',sans-serif;}

        /* ── Navbar ── */
        #main-nav{transition:background .3s,box-shadow .3s;}
        #main-nav.scrolled{background:#fff!important;box-shadow:0 2px 20px rgba(0,0,0,.1);}
        .nav-link{position:relative;padding-bottom:2px;font-weight:500;font-size:.875rem;transition:color .2s;white-space:nowrap;}
        .nav-link::after{content:'';position:absolute;bottom:0;left:0;width:0;height:2px;background:var(--green);transition:width .25s;}
        .nav-link:hover::after{width:100%;}

        /* ── Buttons ── */
        .btn-primary{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem 1.25rem;background:var(--green);color:#fff;border-radius:8px;font-weight:600;font-size:.875rem;transition:all .2s;border:2px solid var(--green);white-space:nowrap;}
        .btn-primary:hover{background:var(--green-dark);border-color:var(--green-dark);transform:translateY(-1px);}
        .btn-primary:focus-visible{outline:2px solid var(--green);outline-offset:2px;}
        .btn-outline{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem 1.25rem;background:transparent;color:var(--green);border:2px solid var(--green);border-radius:8px;font-weight:600;font-size:.875rem;transition:all .2s;white-space:nowrap;}
        .btn-outline:hover{background:var(--green);color:#fff;transform:translateY(-1px);}
        .btn-outline:focus-visible{outline:2px solid var(--green);outline-offset:2px;}
        .btn-gold{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem 1.25rem;background:var(--gold);color:#1a1a1a;border:2px solid var(--gold);border-radius:8px;font-weight:700;font-size:.875rem;transition:all .2s;white-space:nowrap;}
        .btn-gold:hover{background:var(--gold-dark);border-color:var(--gold-dark);transform:translateY(-1px);}
        .btn-gold:focus-visible{outline:2px solid var(--gold-dark);outline-offset:2px;}

        /* ── Nav Sign-In button (state-aware: white outline on transparent, green outline when scrolled) ── */
        .nav-auth-signin{display:inline-flex;align-items:center;gap:.375rem;padding:.45rem 1rem;border-radius:8px;font-weight:600;font-size:.8rem;border:2px solid rgba(255,255,255,.7);color:#fff;background:rgba(255,255,255,.1);transition:all .2s;white-space:nowrap;}
        .nav-auth-signin:hover{background:rgba(255,255,255,.22);border-color:#fff;}
        .nav-auth-signin:focus-visible{outline:2px solid #fff;outline-offset:2px;}
        #main-nav.scrolled .nav-auth-signin{border-color:var(--green);color:var(--green);background:transparent;}
        #main-nav.scrolled .nav-auth-signin:hover{background:var(--green);color:#fff;}
        #main-nav.scrolled .nav-auth-signin:focus-visible{outline-color:var(--green);}

        /* ── Nav user avatar ── */
        .nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green);color:#fff;font-size:.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2.5px solid rgba(255,255,255,.55);transition:border-color .3s;}
        #main-nav.scrolled .nav-avatar{border-color:var(--green-light);}
        .nav-user-name{font-weight:600;font-size:.8125rem;color:#fff;white-space:nowrap;transition:color .3s;}
        #main-nav.scrolled .nav-user-name{color:#111;}

        /* ── Hero ── */
        .hero-bg{background:linear-gradient(135deg,rgba(27,94,32,.88) 0%,rgba(46,125,50,.80) 55%,rgba(2,136,209,.65) 100%),url('https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=1920&q=80&auto=format&fit=crop') center/cover no-repeat;}

        /* ── Section shared ── */
        .section-tag{display:inline-flex;align-items:center;gap:.4rem;background:var(--green-light);color:var(--green);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:.3rem .85rem;border-radius:999px;margin-bottom:.5rem;}
        .section-title{font-size:clamp(1.4rem,4vw,2.1rem);font-weight:800;color:#111;line-height:1.25;margin-bottom:.5rem;}
        .section-sub{font-size:.9rem;color:#555;max-width:580px;margin:0 auto;line-height:1.65;}

        /* ── Solutions ── */
        .sol-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:.875rem .625rem;text-align:center;transition:all .3s;cursor:pointer;}
        .sol-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(46,125,50,.15);border-color:var(--green);}
        .sol-icon{width:38px;height:38px;border-radius:10px;background:var(--green-light);display:flex;align-items:center;justify-content:center;font-size:1.05rem;color:var(--green);margin:0 auto .5rem;}

        /* ── Feature cards ── */
        .feat-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:1.125rem;display:flex;gap:.75rem;align-items:flex-start;transition:all .3s;}
        .feat-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.08);border-color:var(--green);}
        .feat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9375rem;flex-shrink:0;}

        /* ── Pricing ── */
        .price-card{background:#fff;border:2px solid #e5e7eb;border-radius:18px;padding:1.5rem;transition:all .3s;}
        .price-card.featured{border-color:var(--green);box-shadow:0 16px 48px rgba(46,125,50,.18);}
        .price-card:hover{transform:translateY(-4px);}

        /* ── FAQ ── */
        .faq-item{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:.625rem;}
        .faq-q{padding:1rem 1.125rem;font-weight:600;font-size:.9rem;color:#111;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:.75rem;background:#fff;transition:background .2s;}
        .faq-q:hover{background:#f9fafb;}
        .faq-a{padding:0 1.125rem;font-size:.875rem;color:#555;line-height:1.7;max-height:0;overflow:hidden;transition:max-height .35s ease,padding .3s;}
        .faq-a.open{max-height:400px;padding-bottom:1.125rem;padding-top:.25rem;}

        /* ── Testimonials ── */
        .testi-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:1.5rem;}

        /* ── Floating WhatsApp ── */
        .wa-float{animation:wa-pulse 2.5s infinite;}
        @keyframes wa-pulse{0%,100%{box-shadow:0 0 0 0 rgba(37,211,102,.6);}70%{box-shadow:0 0 0 14px rgba(37,211,102,0);}}

        /* ── Fade-up animation ── */
        .fade-up{opacity:0;transform:translateY(20px);transition:opacity .55s,transform .55s;}
        .fade-up.visible{opacity:1;transform:translateY(0);}
        @media(prefers-reduced-motion:reduce){.fade-up{opacity:1;transform:none;}}

        [x-cloak]{display:none!important;}

        /* ── Responsive section rhythm ── */
        .s-py{padding-top:2.5rem;padding-bottom:2.5rem;}
        @media(min-width:768px){.s-py{padding-top:4rem;padding-bottom:4rem;}}
        @media(min-width:1024px){.s-py{padding-top:5rem;padding-bottom:5rem;}}

        .s-header-mb{margin-bottom:1.25rem;}
        @media(min-width:768px){.s-header-mb{margin-bottom:2rem;}}

        /* ── About column reorder (CSS-native, no Tailwind breakpoint needed) ── */
        @media(min-width:1024px){
            .about-col-text{order:2;}
            .about-col-images{order:1;}
            .about-col-video{order:3;}
        }
    </style>
</head>
<body class="antialiased">

{{-- ═══════════ NAVBAR ═══════════ --}}
<header id="main-nav" class="fixed top-0 inset-x-0 z-50 transition-all" x-data="{ open:false, scrolled:false }"
    x-init="window.addEventListener('scroll',()=>{ scrolled=window.scrollY>40; document.getElementById('main-nav').classList.toggle('scrolled',scrolled); })">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
        {{-- Logo --}}
        <a href="/" class="flex items-center gap-2 shrink-0">
            <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background:var(--green)"><i class="fa-solid fa-leaf text-white text-sm"></i></div>
            <div class="leading-none">
                <div class="font-heading font-extrabold text-sm md:text-base" :class="scrolled?'text-gray-900':'text-white'" style="line-height:1.1">MSAS Agro</div>
                <div class="text-[9px] font-medium tracking-wider hidden sm:block" :class="scrolled?'text-green-700':'text-green-200'">Smart Agriculture, Better Tomorrow</div>
            </div>
        </a>

        {{-- Desktop Nav --}}
        <nav class="hidden lg:flex items-center gap-4 xl:gap-5">
            @foreach([['#home','Home',null],['#about','About',null],['#solutions','Solutions','chevron-down'],['#features','Features',null],['#marketplace','Marketplace',null],['#pricing','Pricing',null],['#','Partners',null],['#','Resources','chevron-down'],['#contact','Contact',null]] as [$href,$label,$drop])
            <a href="{{ $href }}" class="nav-link flex items-center gap-1" :class="scrolled?'text-gray-700 hover:text-green-700':'text-white hover:text-green-200'">{{ $label }}@isset($drop)<i class="fa-solid fa-{{ $drop }} text-[9px] opacity-70"></i>@endisset</a>
            @endforeach
        </nav>

        {{-- Desktop Auth + Language --}}
        <div class="hidden lg:flex items-center gap-1.5">
            <button class="p-2 rounded-lg transition" :class="scrolled?'text-gray-700 hover:bg-gray-100':'text-white hover:bg-white/10'"><i class="fa-solid fa-magnifying-glass text-sm"></i></button>
            {{-- Language Selector --}}
            <div class="relative" x-data="{ langOpen: false }">
                @php $locale = session('locale', app()->getLocale()); $localeLabels = ['en'=>'EN','ha'=>'HA','fr'=>'FR','yo'=>'YO','ig'=>'IG','ff'=>'FF']; @endphp
                <button @click="langOpen=!langOpen" @click.outside="langOpen=false"
                    class="flex items-center gap-1 px-2 py-1.5 rounded-lg text-sm font-bold transition"
                    :class="scrolled?'text-gray-700 hover:bg-gray-100':'text-white hover:bg-white/10'">
                    {{ strtoupper($localeLabels[$locale] ?? 'EN') }} <i class="fa-solid fa-chevron-down text-[9px]"></i>
                </button>
                <div x-show="langOpen" x-cloak x-transition
                    class="absolute right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-gray-100 py-1 w-36 z-50">
                    @foreach([['en','🇬🇧','English'],['ha','🇳🇬','Hausa'],['fr','🇫🇷','Français'],['yo','🇳🇬','Yoruba'],['ig','🇳🇬','Igbo']] as [$code,$flag,$name])
                    <form method="POST" action="{{ route('locale.set') }}">@csrf<input type="hidden" name="locale" value="{{ $code }}">
                    <button type="submit" class="w-full text-left px-3 py-2 text-sm hover:bg-green-50 hover:text-green-700 flex items-center gap-2 {{ $locale === $code ? 'font-bold text-green-700' : 'text-gray-700' }}">
                        <span>{{ $flag }}</span> {{ $name }}
                    </button></form>
                    @endforeach
                </div>
            </div>
            @auth
            @php
                $navUser = auth()->user();
                $navNotifCount = 0;
                try { $navNotifCount = \App\Models\Notification::where('user_id',$navUser->id)->where('is_read',false)->count(); } catch(\Exception $e) {}
            @endphp
            <div class="relative flex items-center gap-1.5" x-data="{ userOpen: false }">
                {{-- Notification bell --}}
                <a href="{{ route('notifications.index') }}"
                   class="relative p-2 rounded-lg transition"
                   :class="scrolled ? 'text-gray-700 hover:bg-gray-100' : 'text-white hover:bg-white/10'"
                   aria-label="{{ $navNotifCount }} unread notifications">
                    <i class="fa-solid fa-bell text-sm"></i>
                    @if($navNotifCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center px-0.5 leading-none">{{ $navNotifCount > 9 ? '9+' : $navNotifCount }}</span>
                    @endif
                </a>
                {{-- User dropdown trigger --}}
                <button @click="userOpen=!userOpen" @click.outside="userOpen=false"
                    class="flex items-center gap-2 px-2 py-1 rounded-lg transition focus-visible:outline-2 focus-visible:outline-offset-2"
                    :class="scrolled ? 'hover:bg-gray-100 focus-visible:outline-gray-400' : 'hover:bg-white/10 focus-visible:outline-white'"
                    aria-label="User menu" :aria-expanded="userOpen.toString()" aria-haspopup="true">
                    <div class="nav-avatar" aria-hidden="true">{{ strtoupper(substr($navUser->first_name ?? $navUser->name ?? 'U',0,1)) }}{{ strtoupper(substr($navUser->last_name ?? '',0,1)) }}</div>
                    <span class="nav-user-name hidden xl:block">{{ $navUser->first_name ?? $navUser->name }}</span>
                    <i class="fa-solid fa-chevron-down text-[9px] transition-transform duration-200"
                       :class="[scrolled ? 'text-gray-500' : 'text-white/70', userOpen ? 'rotate-180' : '']"></i>
                </button>
                {{-- Dropdown panel --}}
                <div x-show="userOpen" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 w-56 z-50"
                     style="transform-origin:top right">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <div class="font-bold text-sm text-gray-900 truncate">{{ trim(($navUser->first_name ?? '').' '.($navUser->last_name ?? '')) ?: $navUser->name }}</div>
                        <div class="text-xs text-gray-400 truncate mt-0.5">{{ $navUser->email }}</div>
                        <span class="mt-1.5 inline-flex px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold rounded-full capitalize">{{ $navUser->roleLabel ?? $navUser->role ?? 'User' }}</span>
                    </div>
                    <a href="{{ url('/dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-800 transition group">
                        <i class="fa-solid fa-gauge-high w-4 text-center text-green-500 text-xs group-hover:text-green-700"></i> Dashboard
                    </a>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-800 transition group">
                        <i class="fa-solid fa-user w-4 text-center text-green-500 text-xs group-hover:text-green-700"></i> My Profile
                    </a>
                    <a href="{{ route('profile.edit') }}#settings" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-800 transition group">
                        <i class="fa-solid fa-gear w-4 text-center text-green-500 text-xs group-hover:text-green-700"></i> Settings
                    </a>
                    <div class="border-t border-gray-100 mt-1 pt-1">
                        <form method="POST" action="{{ route('logout') }}">@csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition font-semibold">
                            <i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Sign Out
                        </button></form>
                    </div>
                </div>
            </div>
            @else
                <a href="{{ route('login') }}"
                   class="nav-auth-signin"
                   aria-label="Sign in to your account">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i> Sign In
                </a>
                <a href="{{ route('register') }}"
                   class="btn-primary text-xs py-2 px-3.5"
                   aria-label="Create a new account">
                    <i class="fa-solid fa-user-plus text-xs"></i> Sign Up
                </a>
                <a href="{{ route('register') }}"
                   class="btn-gold text-xs py-2 px-3.5"
                   aria-label="Check in to the platform">
                    <i class="fa-solid fa-clipboard-check text-xs"></i> Check In
                </a>
            @endauth
        </div>

        {{-- Tablet auth (md–lg) --}}
        <div class="hidden md:flex lg:hidden items-center gap-2">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary text-xs py-2 px-3">
                    <i class="fa-solid fa-gauge-high text-xs"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"    class="nav-auth-signin" aria-label="Sign in"><i class="fa-solid fa-right-to-bracket text-xs"></i> Sign In</a>
                <a href="{{ route('register') }}" class="btn-primary text-xs py-2 px-3" aria-label="Create account"><i class="fa-solid fa-user-plus text-xs"></i> Sign Up</a>
                <a href="{{ route('register') }}" class="btn-gold text-xs py-2 px-3" aria-label="Check in"><i class="fa-solid fa-clipboard-check text-xs"></i> Check In</a>
            @endauth
        </div>

        {{-- Hamburger --}}
        <button @click="open=!open" class="md:hidden p-2 rounded-lg transition" :class="scrolled?'text-gray-800 hover:bg-gray-100':'text-white hover:bg-white/10'">
            <i x-show="!open" class="fa-solid fa-bars text-xl"></i>
            <i x-show="open"  class="fa-solid fa-xmark text-xl" x-cloak></i>
        </button>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="md:hidden bg-white border-t border-gray-100 shadow-xl max-h-[85vh] overflow-y-auto">
        <nav class="px-4 py-3 space-y-0.5 border-b border-gray-100">
            @foreach([['#home','Home','house'],['#about','About','circle-info'],['#solutions','Solutions','layer-group'],['#features','Features','star'],['#marketplace','Marketplace','store'],['#pricing','Pricing','tag'],['#contact','Contact','phone']] as [$href,$label,$icon])
            <a href="{{ $href }}" @click="open=false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-green-50 hover:text-green-800 font-medium text-sm transition">
                <i class="fa-solid fa-{{ $icon }} text-green-600 text-sm w-4 text-center"></i> {{ $label }}
            </a>
            @endforeach
        </nav>
        {{-- Mobile language switcher --}}
        <div class="px-4 py-2 border-b border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Language</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach([['en','🇬🇧','EN'],['ha','🇳🇬','HA'],['fr','🇫🇷','FR'],['yo','🇳🇬','YO'],['ig','🇳🇬','IG']] as [$code,$flag,$label])
                <form method="POST" action="{{ route('locale.set') }}">@csrf<input type="hidden" name="locale" value="{{ $code }}">
                <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1 transition {{ session('locale','en') === $code ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-600 hover:bg-green-50' }}">{{ $flag }} {{ $label }}</button></form>
                @endforeach
            </div>
        </div>
        <div class="px-4 py-4 flex flex-col gap-2.5">
            @auth
            @php $mobileUser = auth()->user(); @endphp
            {{-- Mobile: authenticated user info + actions --}}
            <div class="flex items-center gap-3 px-3 py-2.5 bg-green-50 rounded-xl border border-green-100 mb-1">
                <div class="w-10 h-10 rounded-full bg-green-700 text-white text-sm font-bold flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($mobileUser->first_name ?? $mobileUser->name ?? 'U',0,1)) }}{{ strtoupper(substr($mobileUser->last_name ?? '',0,1)) }}
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-sm text-gray-900 truncate">{{ trim(($mobileUser->first_name ?? '').' '.($mobileUser->last_name ?? '')) ?: $mobileUser->name }}</div>
                    <div class="text-xs text-green-700 font-semibold capitalize">{{ $mobileUser->roleLabel ?? $mobileUser->role ?? 'User' }}</div>
                </div>
            </div>
                <a href="{{ url('/dashboard') }}"     @click="open=false" class="btn-primary justify-center"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a href="{{ route('profile.edit') }}" @click="open=false" class="btn-outline justify-center"><i class="fa-solid fa-user"></i> My Profile</a>
                <a href="{{ route('notifications.index') }}" @click="open=false" class="btn-outline justify-center"><i class="fa-solid fa-bell"></i> Notifications</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" @click="open=false" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border-2 border-red-200 text-red-600 font-semibold text-sm hover:bg-red-50 transition">
                    <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                </button></form>
            @else
                <a href="{{ route('login') }}"    @click="open=false" class="btn-outline justify-center" aria-label="Sign in to your account"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
                <a href="{{ route('register') }}" @click="open=false" class="btn-primary justify-center" aria-label="Create a new account"><i class="fa-solid fa-user-plus"></i> Sign Up</a>
                <a href="{{ route('register') }}" @click="open=false" class="btn-gold justify-center" aria-label="Check in to the platform"><i class="fa-solid fa-clipboard-check"></i> Check In</a>
            @endauth
        </div>
    </div>
</header>

{{-- ═══════════ HERO ═══════════ --}}
<section id="home" class="hero-bg min-h-screen flex flex-col justify-center pt-16">
    <div class="max-w-7xl mx-auto px-4 py-12 md:py-16 lg:py-20">
        <div class="grid lg:grid-cols-12 gap-8 lg:gap-10 items-center">
            {{-- Left: headline + CTAs --}}
            <div class="lg:col-span-8 text-white">
                <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur border border-white/25 rounded-full px-3 md:px-4 py-1.5 text-[11px] md:text-xs font-bold uppercase tracking-widest text-green-100 mb-4 md:mb-6">
                    <span class="w-2 h-2 rounded-full bg-green-300 animate-pulse inline-block shrink-0"></span>
                    Nigeria's #1 AgriTech Platform
                </div>
                <h1 class="font-heading text-[2rem] sm:text-4xl md:text-5xl lg:text-[52px] xl:text-[58px] font-extrabold leading-[1.15] mb-4 md:mb-5">
                    Empowering Agriculture<br/>with <span class="text-yellow-300">Artificial Intelligence</span>
                </h1>
                <p class="text-green-100 text-base md:text-lg leading-relaxed mb-6 md:mb-8 max-w-xl">Helping farmers, livestock owners, agribusinesses, governments and development partners make smarter decisions through AI, data and digital innovation.</p>
                <div class="flex flex-wrap gap-2 md:gap-3">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-5 md:px-7 py-3 md:py-3.5 rounded-xl font-bold text-sm md:text-base shadow-lg transition hover:-translate-y-1" style="background:var(--green);color:#fff;box-shadow:0 8px 24px rgba(46,125,50,.45)"><i class="fa-solid fa-seedling"></i> Start Farming</a>
                    <a href="#ai-assistant" class="inline-flex items-center gap-2 px-5 md:px-7 py-3 md:py-3.5 rounded-xl font-semibold text-sm md:text-base bg-white/15 backdrop-blur border border-white/30 text-white hover:bg-white/25 transition"><i class="fa-solid fa-play text-xs"></i> Watch Demo</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-5 md:px-7 py-3 md:py-3.5 rounded-xl font-semibold text-sm md:text-base bg-white/10 backdrop-blur border border-white/20 text-white hover:bg-white/20 transition"><i class="fa-solid fa-download text-xs"></i> Download App</a>
                </div>
            </div>
            {{-- Right: stacked stat cards (hidden on mobile — they appear in stats bar below) --}}
            <div class="hidden lg:flex lg:col-span-4 flex-col gap-3">
                @foreach([['20,000+','Registered Farmers','users','#2E7D32'],['150+','Cooperatives','handshake','#F9A825'],['100+','Projects','folder-open','#0288D1'],['36 States','Coverage','map-location-dot','#7C3AED'],['99.9%','System Uptime','server','#059669']] as [$num,$label,$ico,$color])
                <div class="flex items-center gap-4 bg-white rounded-xl px-4 py-3.5 shadow-lg">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:{{ $color }}22">
                        <i class="fa-solid fa-{{ $ico }}" style="color:{{ $color }}"></i>
                    </div>
                    <div>
                        <div class="font-heading font-extrabold text-xl leading-none text-gray-900">{{ $num }}</div>
                        <div class="text-gray-400 text-xs font-medium mt-0.5">{{ $label }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ TRUSTED BY ═══════════ --}}
<section class="py-6 md:py-8 bg-white border-b border-gray-100">
    <div class="max-w-6xl mx-auto px-4">
        <p class="text-center text-[10px] md:text-xs font-bold uppercase tracking-widest text-gray-400 mb-6 md:mb-8">Trusted by Leading Organizations</p>
        <div class="flex flex-wrap items-center justify-center gap-5 md:gap-8 lg:gap-14">
            @foreach([['Federal Ministry of Agriculture','landmark'],['THE WORLD BANK','globe'],['IFAD','seedling'],['NIRSAL','shield-halved'],['AGRA','leaf'],['CGIAR','microscope']] as [$name,$icon])
            <div class="flex flex-col items-center gap-1.5 group cursor-default">
                <i class="fa-solid fa-{{ $icon }} text-xl md:text-2xl text-gray-300 group-hover:text-green-600 transition"></i>
                <span class="font-bold text-[10px] md:text-[11px] text-gray-400 group-hover:text-green-700 transition text-center" style="max-width:72px;line-height:1.3">{{ $name }}</span>
            </div>
            @endforeach
            <div class="text-gray-300 font-semibold text-[10px] md:text-xs border border-gray-200 rounded-lg px-3 py-2 text-center leading-tight">And Many<br/>More...</div>
        </div>
    </div>
</section>

{{-- ═══════════ ABOUT ═══════════ --}}
<section id="about" class="s-py bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid lg:grid-cols-3 gap-6 lg:gap-10 items-start">
            {{-- Text — renders first on mobile, second on desktop --}}
            <div class="about-col-text">
                <div class="section-tag"><i class="fa-solid fa-circle-info"></i> About MSAS Agro</div>
                <h2 class="font-heading font-extrabold text-2xl md:text-3xl text-gray-900 mb-3">About <span style="color:var(--green)">MSAS Agro</span></h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">MSAS Agro is an AI-powered digital platform providing innovative solutions for crop farming, livestock management, poultry, fish farming, marketplace, finance, insurance, and data analytics. Since 2019, we have been driving agriculture transformation.</p>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    @foreach([['Our Mission','To empower agriculture stakeholders with smart digital solutions.','bullseye'],['Our Vision','To become Africa\'s leading digital agriculture platform.','eye'],['Core Values','Innovation, Integrity, Impact, Collaboration, Sustainability.','heart'],['Our Journey','Since 2019, we have been driving agriculture transformation.','route']] as [$title,$text,$ico])
                    <div class="flex gap-2">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5" style="background:var(--green-light)"><i class="fa-solid fa-{{ $ico }} text-xs" style="color:var(--green)"></i></div>
                        <div><div class="font-bold text-gray-800 text-xs mb-0.5">{{ $title }}</div><div class="text-gray-500 text-[11px] leading-relaxed">{{ $text }}</div></div>
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('register') }}" class="btn-primary text-sm">Read More <i class="fa-solid fa-arrow-right text-xs"></i></a>
            </div>
            {{-- Image grid — renders second on mobile, first on desktop --}}
            <div class="about-col-images">
                <div class="grid grid-cols-2 gap-3">
                    <img loading="lazy" src="https://images.unsplash.com/photo-1586771107445-d3ca888129ff?w=300&q=80&auto=format&fit=crop" alt="Crop farming" class="rounded-xl object-cover h-32 sm:h-36 md:h-40 w-full">
                    <img loading="lazy" src="https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=300&q=80&auto=format&fit=crop" alt="Farmers" class="rounded-xl object-cover h-32 sm:h-36 md:h-40 w-full sm:mt-5 md:mt-6">
                    <img loading="lazy" src="https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=300&q=80&auto=format&fit=crop" alt="Poultry" class="rounded-xl object-cover h-32 sm:h-36 md:h-40 w-full sm:-mt-5 md:-mt-6">
                    <img loading="lazy" src="https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?w=300&q=80&auto=format&fit=crop" alt="Livestock" class="rounded-xl object-cover h-32 sm:h-36 md:h-40 w-full">
                </div>
            </div>
            {{-- Video card — renders third on both --}}
            <div class="about-col-video rounded-2xl overflow-hidden border border-gray-100 shadow-lg">
                <div class="relative">
                    <img loading="lazy" src="https://images.unsplash.com/photo-1574943320219-553eb213f72d?w=500&q=80&auto=format&fit=crop" alt="Agriculture Technology" class="w-full h-44 md:h-52 object-cover">
                    <div class="absolute inset-0 flex items-center justify-center" style="background:rgba(27,94,32,.35)">
                        <div class="w-14 h-14 rounded-full bg-white flex items-center justify-center shadow-xl cursor-pointer hover:scale-110 transition"><i class="fa-solid fa-play ml-1" style="color:var(--green)"></i></div>
                    </div>
                </div>
                <div class="p-4 bg-white">
                    <div class="font-bold text-gray-800 text-sm mb-1">Transforming Agriculture with Technology</div>
                    <div class="text-gray-400 text-xs leading-relaxed">Watch how MSAS Agro is changing lives across Africa.</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ SOLUTIONS ═══════════ --}}
<section id="solutions" class="s-py bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center s-header-mb">
            <h2 class="section-title">Our Platform Solutions</h2>
            <p class="section-sub mt-1">Comprehensive digital tools covering every aspect of modern agriculture.</p>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2 md:gap-3">
            @foreach([
                ['Crop Farming','seedling','services.crops'],
                ['Livestock','cow','services.livestock'],
                ['Poultry','egg','services.poultry'],
                ['Fish Farming','fish','register'],
                ['Greenhouse','house','register'],
                ['Marketplace','store','marketplace'],
                ['Warehouse','warehouse','register'],
                ['AI Assistant','robot','register'],
                ['Weather Intelligence','cloud-sun','register'],
                ['Satellite Monitoring','satellite-dish','register'],
                ['Inventory Management','boxes-stacking','register'],
                ['Farm Finance','coins','services.finance'],
                ['Insurance','shield-halved','register'],
                ['Extension Services','person-chalkboard','register'],
                ['GIS Mapping','map-location-dot','register'],
                ['Govt Dashboard','landmark','register'],
                ['NGO Dashboard','hand-holding-heart','register'],
                ['Research Portal','microscope','register'],
                ['Data Analytics','chart-bar','register'],
                ['Training & Learning','graduation-cap','register'],
                ['Cooperative Mgmt','people-group','register'],
                ['Project Monitoring','diagram-project','register'],
                ['IoT Devices','microchip','register'],
            ] as [$name,$icon,$route])
            <a href="{{ route($route) }}" class="sol-card fade-up group">
                <div class="sol-icon group-hover:bg-green-700 group-hover:text-white transition"><i class="fa-solid fa-{{ $icon }}"></i></div>
                <div class="font-semibold text-gray-700 text-[10px] md:text-[11px] leading-tight group-hover:text-green-700 transition">{{ $name }}</div>
            </a>
            @endforeach
            <a href="{{ route('register') }}" class="sol-card fade-up flex flex-col items-center justify-center" style="border-color:var(--green);background:var(--green-light)">
                <div class="sol-icon mb-0.5" style="background:var(--green);color:#fff"><i class="fa-solid fa-ellipsis"></i></div>
                <div class="font-bold text-[10px] md:text-xs" style="color:var(--green)">And More</div>
            </a>
        </div>
        <div class="text-center mt-8 md:mt-10">
            <a href="{{ route('register') }}" class="btn-primary inline-flex px-6 md:px-8 py-2.5 md:py-3 text-sm">Explore All Solutions <i class="fa-solid fa-arrow-right text-xs"></i></a>
        </div>
    </div>
</section>

{{-- ═══════════ AI ASSISTANT ═══════════ --}}
<section id="ai-assistant" class="s-py bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-14 items-center">
            <div>
                <div class="section-tag"><i class="fa-solid fa-robot"></i> AI Powered</div>
                <h2 class="font-heading font-extrabold text-2xl md:text-3xl text-gray-900 mb-3">Meet Your <span style="color:var(--green)">AI Farm Assistant</span></h2>
                <p class="text-gray-500 leading-relaxed mb-4 text-sm">Get instant answers, smart recommendations and real-time insights to improve your productivity. Powered by advanced vision AI that diagnoses crop diseases, livestock conditions, and soil health from a single photo.</p>
                <ul class="space-y-2 mb-5">
                    @foreach(['Image based disease detection','Fertilizer & crop recommendation','Weather & market price updates','Yield prediction & alerts'] as $feat)
                    <li class="flex items-center gap-3 text-sm text-gray-700">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center shrink-0" style="background:var(--green);color:#fff"><i class="fa-solid fa-check text-[9px]"></i></span>
                        {{ $feat }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="btn-primary text-sm"><i class="fa-solid fa-robot text-xs"></i> Try AI Assistant Now</a>
            </div>
            <div class="bg-gray-50 rounded-2xl border border-gray-200 overflow-hidden shadow-lg">
                <div class="flex items-center justify-between px-4 md:px-5 py-3.5 border-b border-gray-200 bg-white">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold shrink-0" style="background:var(--green)"><i class="fa-solid fa-robot text-xs"></i></div>
                        <div><div class="font-bold text-gray-800 text-sm">AI Farm Assistant</div><div class="text-xs text-green-600 font-medium">● Online</div></div>
                    </div>
                    <i class="fa-solid fa-ellipsis text-gray-400"></i>
                </div>
                <div class="p-4 md:p-5 space-y-3">
                    <div class="flex gap-2.5"><div class="w-7 h-7 md:w-8 md:h-8 rounded-full flex items-center justify-center text-white text-xs shrink-0" style="background:var(--green)"><i class="fa-solid fa-robot text-[10px]"></i></div><div class="bg-white rounded-2xl rounded-tl-none px-3 md:px-4 py-2 shadow-sm border border-gray-100 text-xs text-gray-700 max-w-[85%]">Hello! I am your AI Farm Assistant. How can I help you today?</div></div>
                    <div class="flex justify-end"><div class="rounded-2xl rounded-tr-none px-3 md:px-4 py-2 text-xs text-white max-w-[85%]" style="background:var(--green)">What is the best fertilizer for maize at vegetative stage?</div></div>
                    <div class="flex gap-2.5"><div class="w-7 h-7 md:w-8 md:h-8 rounded-full flex items-center justify-center text-white text-xs shrink-0" style="background:var(--green)"><i class="fa-solid fa-robot text-[10px]"></i></div><div class="bg-white rounded-2xl rounded-tl-none px-3 md:px-4 py-2 shadow-sm border border-gray-100 text-xs text-gray-700 max-w-[85%]">For maize at vegetative stage, <strong>NPK 20:10:10</strong> at 100kg/ha is recommended. Ensure good weed control and adequate irrigation.</div></div>
                </div>
                <div class="flex gap-2 px-4 md:px-5 pb-3">
                    <input type="text" placeholder="Ask something..." class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-green-400 bg-white">
                    <button class="w-9 h-9 rounded-xl text-white flex items-center justify-center shrink-0" style="background:var(--green)"><i class="fa-solid fa-paper-plane text-[10px]"></i></button>
                </div>
                <div class="grid grid-cols-2 gap-2 px-4 md:px-5 pb-4 md:pb-5">
                    @foreach([['Disease Detection','Upload image & detect','bug','red'],['Market Prices','Real-time prices','chart-line','blue'],['Weather Alerts','Forecasts & alerts','cloud-sun','yellow'],['Smart Insights','AI productivity tips','lightbulb','green']] as [$t,$d,$i,$c])
                    <div class="bg-white rounded-xl p-2.5 md:p-3 border border-gray-100 shadow-sm">
                        <i class="fa-solid fa-{{ $i }} text-{{ $c }}-500 text-sm mb-1 block"></i>
                        <div class="text-[11px] font-bold text-gray-800 leading-tight">{{ $t }}</div>
                        <div class="text-[10px] text-gray-500 mt-0.5">{{ $d }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ HOW IT WORKS ═══════════ --}}
<section class="s-py bg-gray-50">
    <div class="max-w-5xl mx-auto px-4">
        <div class="text-center s-header-mb">
            <h2 class="section-title">How It Works</h2>
        </div>
        {{-- Mobile: vertical list; Desktop: horizontal row with arrows --}}
        <div class="flex flex-col sm:flex-row items-start justify-center gap-6 sm:gap-0">
            @php
            $steps = [
                ['1','Register','Create your free account','user-plus'],
                ['2','Create Farm','Add your farm & livestock details','plus-circle'],
                ['3','Collect Data','Record activities & monitor progress','database'],
                ['4','Get Insights','Receive AI insights & increase productivity','brain'],
            ];
            @endphp
            @foreach($steps as $idx => [$n,$t,$d,$i])
            {{-- Mobile: horizontal card; Desktop: stacked column --}}
            <div class="flex sm:block sm:flex-1">
                {{-- Mobile layout: number+icon left, text right --}}
                <div class="sm:hidden flex items-start gap-4 w-full">
                    <div class="flex flex-col items-center shrink-0">
                        <div class="w-11 h-11 rounded-full text-white font-heading font-black text-base flex items-center justify-center shadow-md" style="background:var(--green)">{{ $n }}</div>
                        @if($idx < 3)<div class="w-0.5 h-8 mt-2" style="background:var(--green);opacity:.3"></div>@endif
                    </div>
                    <div class="pt-1 pb-4">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fa-solid fa-{{ $i }} text-base" style="color:var(--green)"></i>
                            <h3 class="font-heading font-bold text-gray-800 text-sm">{{ $t }}</h3>
                        </div>
                        <p class="text-gray-500 text-xs leading-relaxed">{{ $d }}</p>
                    </div>
                </div>
                {{-- Desktop layout: centered column --}}
                <div class="hidden sm:block text-center px-2 fade-up">
                    <div class="w-12 h-12 rounded-full text-white font-heading font-black text-lg flex items-center justify-center mx-auto mb-3 shadow-md" style="background:var(--green)">{{ $n }}</div>
                    <i class="fa-solid fa-{{ $i }} text-xl mb-2 block" style="color:var(--green)"></i>
                    <h3 class="font-heading font-bold text-gray-800 text-sm mb-1">{{ $t }}</h3>
                    <p class="text-gray-500 text-xs leading-relaxed">{{ $d }}</p>
                </div>
                @if($idx < 3)
                <div class="hidden sm:flex items-center pt-4 shrink-0 px-1">
                    <i class="fa-solid fa-arrow-right text-base" style="color:var(--green);opacity:.45"></i>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ MOBILE APP ═══════════ --}}
<section class="s-py text-white relative overflow-hidden" style="background:linear-gradient(135deg,#1B5E20 0%,#2E7D32 55%,#0288D1 100%)">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(#fff 1px,transparent 1px);background-size:24px 24px;pointer-events:none;"></div>
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-12 items-center">
            {{-- Text + QR --}}
            <div>
                <h2 class="font-heading font-extrabold text-2xl md:text-3xl mb-3">Take MSAS Agro<br/><span class="text-yellow-300">Anywhere You Go</span></h2>
                <p class="text-green-100 text-sm leading-relaxed mb-4">Our mobile app is fast, offline-ready and designed for farmers. Record data in the field, get AI diagnoses, check market prices — even without internet.</p>
                <div class="flex flex-wrap gap-2 md:gap-3 mb-4 md:mb-5">
                    <a href="{{ route('register') }}" class="flex items-center gap-2 bg-black text-white rounded-xl px-4 py-2.5 hover:bg-gray-900 transition"><i class="fa-brands fa-google-play text-green-400"></i><div><div class="text-[9px] text-gray-300 uppercase">Get it on</div><div class="text-xs font-bold">Google Play</div></div></a>
                    <a href="{{ route('register') }}" class="flex items-center gap-2 bg-black text-white rounded-xl px-4 py-2.5 hover:bg-gray-900 transition"><i class="fa-brands fa-apple"></i><div><div class="text-[9px] text-gray-300 uppercase">Download on</div><div class="text-xs font-bold">App Store</div></div></a>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 bg-white rounded-xl p-1.5 shrink-0 flex items-center justify-center"><i class="fa-solid fa-qrcode text-gray-800 text-2xl"></i></div>
                    <div class="text-xs text-green-200 leading-relaxed">Scan to Download<br/><span class="text-green-300 text-[10px]">Available on Android &amp; iOS</span></div>
                </div>
            </div>
            {{-- Phone mockups (hidden on small, shown md+) --}}
            <div class="hidden md:flex justify-center gap-3 items-end">
                <div class="w-24 lg:w-28 h-48 lg:h-52 bg-white/15 backdrop-blur border border-white/25 rounded-2xl overflow-hidden shadow-2xl">
                    <div class="h-2.5 bg-white/20 flex items-center px-2"><span class="w-1.5 h-1.5 rounded-full bg-white/40"></span></div>
                    <div class="p-2 flex flex-col gap-1.5">
                        <div class="bg-white/30 rounded text-[7px] text-white font-bold px-1 py-0.5">Farm Overview</div>
                        <div class="grid grid-cols-2 gap-1"><div class="bg-white/20 rounded h-7"></div><div class="bg-white/20 rounded h-7"></div><div class="bg-white/20 rounded h-7"></div><div class="bg-white/20 rounded h-7"></div></div>
                    </div>
                </div>
                <div class="w-24 lg:w-28 h-56 lg:h-60 bg-white/20 backdrop-blur border border-white/30 rounded-2xl overflow-hidden shadow-2xl">
                    <div class="h-2.5 bg-white/20 flex items-center px-2"><span class="w-1.5 h-1.5 rounded-full bg-white/40"></span></div>
                    <div class="p-2 flex flex-col gap-1.5">
                        <div class="bg-white/30 rounded text-[7px] text-white font-bold px-1 py-0.5">Analytics</div>
                        <div class="bg-white/20 rounded h-14"></div>
                        <div class="space-y-1"><div class="bg-white/20 h-1.5 rounded"></div><div class="bg-white/20 h-1.5 rounded w-3/4"></div><div class="bg-white/20 h-1.5 rounded w-1/2"></div></div>
                    </div>
                </div>
                <div class="w-24 lg:w-28 h-48 lg:h-52 bg-white/15 backdrop-blur border border-white/25 rounded-2xl overflow-hidden shadow-2xl">
                    <div class="h-2.5 bg-white/20 flex items-center px-2"><span class="w-1.5 h-1.5 rounded-full bg-white/40"></span></div>
                    <div class="p-2 flex flex-col gap-1.5">
                        <div class="bg-white/30 rounded text-[7px] text-white font-bold px-1 py-0.5">Market Prices</div>
                        <div class="space-y-1"><div class="bg-white/20 rounded h-5"></div><div class="bg-white/20 rounded h-5"></div><div class="bg-white/20 rounded h-5"></div><div class="bg-white/20 rounded h-5"></div></div>
                    </div>
                </div>
            </div>
            {{-- Feature checklist --}}
            <div class="grid grid-cols-2 md:grid-cols-1 gap-2 md:space-y-0 md:gap-2.5">
                @foreach(['Offline Data Collection','Real-time Cloud Sync','Push Notifications','Secure & Fast'] as $feat)
                <div class="flex items-center gap-3 bg-white/10 border border-white/15 rounded-xl px-3 md:px-4 py-2.5 md:py-3">
                    <div class="w-5 h-5 md:w-6 md:h-6 rounded-full flex items-center justify-center shrink-0" style="background:var(--green)"><i class="fa-solid fa-check text-white text-[8px] md:text-[9px]"></i></div>
                    <span class="text-xs md:text-sm font-medium text-green-50">{{ $feat }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ STATS BAR ═══════════ --}}
<section class="py-8 md:py-10 bg-white border-b border-gray-100">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-5 md:gap-6 text-center">
            @foreach([['20,000+','Registered Farmers','users'],['150+','Cooperatives','handshake'],['100+','Dev. Projects','folder'],['500+','Extension Agents','person-chalkboard'],['36 States','Nationwide Coverage','map'],['99.9%','System Uptime','server']] as [$num,$label,$ico])
            <div class="fade-up">
                <i class="fa-solid fa-{{ $ico }} text-xl md:text-2xl mb-2 block" style="color:var(--green)"></i>
                <div class="font-heading font-extrabold text-xl md:text-2xl text-gray-900 leading-none">{{ $num }}</div>
                <div class="text-gray-400 text-[11px] md:text-xs font-medium mt-1">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ TESTIMONIALS ═══════════ --}}
<section id="testimonials" class="s-py bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center s-header-mb">
            <h2 class="section-title">What Farmers Are Saying</h2>
        </div>
        <div class="relative px-0 md:px-8">
            <div class="grid md:grid-cols-3 gap-4 md:gap-6">
                @foreach([
                    ['Amina Yusuf','Maize Farmer, Kano State','MSAS Agro has improved my yield by 40% through smart recommendations. The AI disease detection saved my entire farm from a devastating fungal outbreak last season.','photo-1494790108377-be9c29b29330'],
                    ['Bello Salisu','Livestock Farmer, Kaduna','The livestock monitoring system helps me track my animals health in real-time. I can now detect illness early and call the vet before it spreads to the whole herd.','photo-1570295999919-56ceb5ecca61'],
                    ['Grace Okafor','Poultry Farmer, Enugu','I get market prices, alerts and training all in one platform. The marketplace helps me sell my eggs directly to buyers without middlemen.','photo-1508214751196-bcfd4ca60f91'],
                ] as [$name,$role,$text,$img])
                <div class="testi-card fade-up">
                    <div class="flex gap-0.5 text-yellow-400 mb-3">@for($i=0;$i<5;$i++)<i class="fa-solid fa-star text-sm"></i>@endfor</div>
                    <p class="text-gray-600 text-sm leading-relaxed mb-4 italic">&ldquo;{{ $text }}&rdquo;</p>
                    <div class="flex items-center gap-3">
                        <img loading="lazy" src="https://images.unsplash.com/{{ $img }}?w=80&h=80&q=80&auto=format&fit=crop&crop=face" alt="{{ $name }}" class="w-10 h-10 rounded-full object-cover shrink-0">
                        <div><div class="font-bold text-gray-800 text-sm">{{ $name }}</div><div class="text-gray-400 text-xs">{{ $role }}</div></div>
                    </div>
                </div>
                @endforeach
            </div>
            {{-- Carousel arrows: only on md+ where they don't overflow --}}
            <button class="absolute left-0 top-1/2 -translate-y-1/2 w-9 h-9 bg-white rounded-full shadow-lg border border-gray-100 items-center justify-center hover:bg-gray-50 transition hidden md:flex">
                <i class="fa-solid fa-chevron-left text-gray-600 text-xs"></i>
            </button>
            <button class="absolute right-0 top-1/2 -translate-y-1/2 w-9 h-9 bg-white rounded-full shadow-lg border border-gray-100 items-center justify-center hover:bg-gray-50 transition hidden md:flex">
                <i class="fa-solid fa-chevron-right text-gray-600 text-xs"></i>
            </button>
        </div>
    </div>
</section>

{{-- ═══════════ MARKETPLACE ═══════════ --}}
<section id="marketplace" class="s-py bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-14 items-center">
            <div>
                <div class="section-tag"><i class="fa-solid fa-store"></i> MSAS Marketplace</div>
                <h2 class="section-title text-left">Buy &amp; Sell Farm<br/><span style="color:var(--gold)">Inputs &amp; Produce</span></h2>
                <p class="text-gray-500 leading-relaxed mb-4 text-sm">Access Nigeria's largest agricultural marketplace. Buy seeds, fertilizer, equipment and livestock — or sell your produce directly to buyers, cooperatives, and processors.</p>
                <div class="grid grid-cols-2 gap-2 md:gap-2.5 mb-4 md:mb-6">
                    @foreach([['Seeds & Seedlings','seedling','green'],['Fertilizers','sack-dollar','yellow'],['Livestock & Poultry','cow','blue'],['Farm Equipment','tractor','purple'],['Veterinary Supplies','syringe','red'],['Processing Tools','gears','gray']] as [$n,$i,$c])
                    <div class="flex items-center gap-2.5 bg-gray-50 rounded-xl p-3 border border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-{{ $c }}-100 flex items-center justify-center shrink-0"><i class="fa-solid fa-{{ $i }} text-{{ $c }}-600 text-sm"></i></div>
                        <span class="font-semibold text-gray-700 text-xs md:text-sm">{{ $n }}</span>
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('marketplace') }}" class="btn-primary text-sm">Visit Marketplace <i class="fa-solid fa-arrow-right text-xs"></i></a>
            </div>
            <div class="grid grid-cols-2 gap-3 md:gap-4">
                <div class="col-span-2 rounded-2xl overflow-hidden h-44 md:h-48"><img loading="lazy" src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&q=80&auto=format&fit=crop" alt="Marketplace" class="w-full h-full object-cover"></div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 text-center"><div class="text-xl md:text-2xl font-extrabold mb-0.5" style="color:var(--green)">120+</div><div class="text-gray-500 text-xs">Active Listings</div></div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 text-center"><div class="text-xl md:text-2xl font-extrabold text-yellow-500 mb-0.5">₦0</div><div class="text-gray-500 text-xs">Listing Fee</div></div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 text-center"><div class="text-xl md:text-2xl font-extrabold mb-0.5" style="color:var(--blue)">35+</div><div class="text-gray-500 text-xs">Verified Dealers</div></div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 text-center"><div class="text-xl md:text-2xl font-extrabold text-purple-600 mb-0.5">NGN</div><div class="text-gray-500 text-xs">Naira Payments</div></div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ FEATURES ═══════════ --}}
<section id="features" class="s-py bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center s-header-mb">
            <h2 class="section-title">Built for the Modern<br/><span style="color:var(--green)">Agri-Professional</span></h2>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4">
            @foreach([
                ['Offline Capability','Works without internet — syncs when back online','wifi','bg-green-50 text-green-700'],
                ['Cloud Sync','Real-time data synchronisation across all devices','cloud','bg-blue-50 text-blue-700'],
                ['GPS Tracking','Geo-tag farms, fields and livestock locations','location-dot','bg-purple-50 text-purple-700'],
                ['Real-time Reports','Instant dashboards and downloadable reports','chart-bar','bg-yellow-50 text-yellow-700'],
                ['AI Analytics','Machine learning insights for yield optimisation','brain','bg-green-50 text-green-700'],
                ['Role Management','Granular permissions for every team member','shield-halved','bg-red-50 text-red-700'],
                ['Secure Login','2FA, biometric ready, NDPR compliant','lock','bg-gray-100 text-gray-700'],
                ['Audit Logs','Full activity trail for every action taken','clock-rotate-left','bg-blue-50 text-blue-700'],
                ['SMS & Email Alerts','Automated notifications for critical events','bell','bg-yellow-50 text-yellow-700'],
                ['QR Code Support','Animal tags, produce tracking, marketplace','qrcode','bg-purple-50 text-purple-700'],
                ['Mobile App','Android & iOS native app available now','mobile-screen','bg-green-50 text-green-700'],
                ['Multi-language','English, Hausa, Yoruba, Igbo support','language','bg-blue-50 text-blue-700'],
            ] as [$title,$desc,$icon,$color])
            <div class="feat-card fade-up">
                <div class="feat-icon {{ $color }}"><i class="fa-solid fa-{{ $icon }}"></i></div>
                <div><div class="font-bold text-gray-800 text-sm mb-0.5">{{ $title }}</div><div class="text-gray-500 text-xs leading-relaxed">{{ $desc }}</div></div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ PRICING ═══════════ --}}
<section id="pricing" class="s-py bg-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center s-header-mb">
            <div class="section-tag mx-auto"><i class="fa-solid fa-tag"></i> Pricing</div>
            <h2 class="section-title">Simple, Transparent<br/><span style="color:var(--green)">Pricing Plans</span></h2>
            <p class="section-sub">Start free, scale as you grow. No hidden charges.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            @foreach([
                ['Free Farmer','₦0','month','Basic farm management, AI scans (3/month), Marketplace access','register',false,['Farm records','AI Scans ×3','Marketplace','Mobile app','Community forum']],
                ['Premium','₦2,500','month','Unlimited AI scans, Vet consultations, Weather alerts, Priority support','register',true,['Everything in Free','Unlimited AI Scans','Vet consultation','Weather intelligence','SMS alerts','Priority support']],
                ['Enterprise','₦15,000','month','All features + custom integrations, dedicated support, bulk user management','register',false,['Everything in Premium','Custom integrations','API access','Dedicated manager','White-label option','SLA guarantee']],
                ['Government / NGO','Custom','project','Tailored for large-scale deployments, nationwide coverage, M&E dashboards','register',false,['All Enterprise features','GIS & satellite mapping','M&E dashboards','Bulk registration','Training & onboarding','Policy reporting']],
            ] as [$plan,$price,$per,$desc,$route,$featured,$items])
            <div class="price-card {{ $featured ? 'featured' : '' }} fade-up">
                @if($featured)<div class="text-center mb-3"><span class="text-xs font-bold uppercase tracking-widest text-white px-3 py-1 rounded-full" style="background:var(--green)">Most Popular</span></div>@endif
                <div class="text-gray-500 font-semibold text-sm mb-1.5">{{ $plan }}</div>
                <div class="font-heading font-extrabold text-2xl md:text-3xl mb-0.5 text-gray-900">{{ $price }}</div>
                <div class="text-gray-400 text-xs mb-4">per {{ $per }}</div>
                <p class="text-gray-500 text-xs leading-relaxed mb-4">{{ $desc }}</p>
                <ul class="space-y-2 mb-5">
                    @foreach($items as $item)
                    <li class="flex items-start gap-2 text-xs text-gray-600"><i class="fa-solid fa-check shrink-0 mt-0.5" style="color:var(--green)"></i>{{ $item }}</li>
                    @endforeach
                </ul>
                <a href="{{ route($route) }}" class="{{ $featured ? 'btn-primary' : 'btn-outline' }} w-full justify-center text-sm">Get Started</a>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ FAQ ═══════════ --}}
<section class="s-py bg-gray-50">
    <div class="max-w-3xl mx-auto px-4">
        <div class="text-center s-header-mb">
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div id="faq-list">
            @foreach([
                ['What is MSAS Agro?','MSAS Agro is an AI-powered digital agriculture platform built for Nigerian farmers, livestock owners, cooperatives, governments, and development partners. It provides tools for farm management, AI diagnostics, marketplace, vet consultations, and data analytics.'],
                ['Is the platform free to use?','Yes! Our Free Farmer plan is completely free and includes basic farm management, 3 AI scans per month, and marketplace access. Premium features are available from ₦2,500/month.'],
                ['Does it work without internet?','Yes. Our mobile app supports offline data collection. Once you reconnect, all data syncs automatically to the cloud.'],
                ['How does the AI diagnostic work?','Simply upload a photo of your sick animal, diseased crop, or soil sample. Our AI engine identifies the condition and provides a treatment plan within seconds.'],
                ['Can I consult a vet on the platform?','Yes. Farmers can request vet consultations via in-app chat (₦1,500), WhatsApp (₦2,500), or phone call (₦3,500). Vets respond within 2–4 hours.'],
                ['Is my farm data secure?','Absolutely. All data is encrypted in transit (TLS) and at rest (AES-256). MSAS Agro is NDPR compliant and your data is never sold to third parties.'],
                ['How do I register?','Click "Sign Up" on any page, enter your name, phone, email, state, and farm type, and your account is ready in under 2 minutes.'],
            ] as [$q,$a])
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>{{ $q }}</span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform shrink-0"></i>
                </div>
                <div class="faq-a">{{ $a }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ FOUNDER & CEO ═══════════ --}}
<section id="founder" class="s-py bg-white border-t border-gray-100">
    <div class="max-w-5xl mx-auto px-4">
        <div class="text-center s-header-mb">
            <div class="section-tag mx-auto"><i class="fa-solid fa-crown"></i> Leadership</div>
            <h2 class="section-title">Meet Our <span style="color:var(--green)">Founder & CEO</span></h2>
        </div>
        <div class="grid md:grid-cols-5 gap-8 md:gap-12 items-center">
            {{-- Photo + credentials --}}
            <div class="md:col-span-2 flex flex-col items-center text-center">
                <div class="relative mb-4">
                    <img src="{{ asset('images/ceo-sani-yawale-zakka.jpg') }}"
                         alt="Sani Yawale Zakka — Founder & CEO, MSAS Agro"
                         class="w-36 h-36 md:w-44 md:h-44 rounded-full object-cover border-4 shadow-xl"
                         style="border-color:var(--green)"
                         onerror="this.src='https://ui-avatars.com/api/?name=Sani+Zakka&background=2E7D32&color=fff&size=180&rounded=true&bold=true'">
                    <div class="absolute bottom-1 right-1 w-8 h-8 rounded-full bg-white shadow-md border-2 flex items-center justify-center" style="border-color:var(--green)">
                        <i class="fa-solid fa-check text-xs" style="color:var(--green)"></i>
                    </div>
                </div>
                <h3 class="font-heading font-extrabold text-xl text-gray-900 mb-0.5">Sani Yawale Zakka</h3>
                <p class="font-semibold text-sm mb-1" style="color:var(--green)">Founder & CEO, MSAS Agro</p>
                <p class="text-gray-400 text-xs mb-4 leading-relaxed">Agribusiness · Digital Innovation · Entrepreneur</p>
                <div class="flex gap-2 justify-center mb-4">
                    @foreach([['linkedin-in','#0077b5'],['twitter','#38bdf8'],['whatsapp','#25D366']] as [$ico,$col])
                    <a href="#" class="w-8 h-8 rounded-full flex items-center justify-center transition hover:scale-110 text-gray-400 hover:text-white"
                       style="background:#f3f4f6"
                       onmouseover="this.style.background='{{ $col }}';this.querySelector('i').style.color='#fff'"
                       onmouseout="this.style.background='#f3f4f6';this.querySelector('i').style.color='#9ca3af'">
                        <i class="fa-brands fa-{{ $ico }} text-xs"></i>
                    </a>
                    @endforeach
                </div>
                {{-- Contact quick-links --}}
                <div class="space-y-2 w-full max-w-xs">
                    <a href="tel:+2348032459879" class="flex items-center gap-2.5 bg-gray-50 hover:bg-green-50 border border-gray-100 hover:border-green-200 rounded-xl px-3 py-2.5 text-sm transition group">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background:var(--green-light)"><i class="fa-solid fa-phone text-xs" style="color:var(--green)"></i></div>
                        <div class="text-left"><div class="text-xs text-gray-400 leading-none">Call / WhatsApp</div><div class="font-bold text-gray-700 text-xs mt-0.5 group-hover:text-green-700">+234 8032459879</div></div>
                    </a>
                    <a href="mailto:sanizakka@gmail.com" class="flex items-center gap-2.5 bg-gray-50 hover:bg-green-50 border border-gray-100 hover:border-green-200 rounded-xl px-3 py-2.5 text-sm transition group">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background:var(--green-light)"><i class="fa-solid fa-envelope text-xs" style="color:var(--green)"></i></div>
                        <div class="text-left"><div class="text-xs text-gray-400 leading-none">Email</div><div class="font-bold text-gray-700 text-xs mt-0.5 group-hover:text-green-700">sanizakka@gmail.com</div></div>
                    </a>
                </div>
            </div>
            {{-- Bio --}}
            <div class="md:col-span-3">
                <blockquote class="text-base md:text-lg font-medium text-gray-500 italic leading-relaxed mb-5 pl-4 border-l-4" style="border-color:var(--green)">
                    "Technology should serve every farmer — from the smallholder in Katsina to the cooperative in Lagos. That is the vision behind MSAS Agro."
                </blockquote>
                <p class="text-gray-600 leading-relaxed text-sm md:text-base mb-4">
                    <strong class="text-gray-900">Sani Yawale Zakka</strong> is a visionary entrepreneur passionate about agriculture, livestock development and digital innovation. He founded MSAS to transform traditional farming into a profitable, efficient and technology-driven industry that benefits farmers, communities and the wider economy.
                </p>
                <p class="text-gray-500 leading-relaxed text-sm mb-5">
                    With deep roots in Katsina State, Nigeria, Sani combines hands-on agricultural knowledge with modern technology to build solutions that are practical, accessible and impactful. Under his leadership, MSAS Agro has grown to serve over 20,000 registered farmers across all 36 states of Nigeria.
                </p>
                {{-- Achievement badges --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                    @foreach([['20K+','Farmers Served','users'],['36','States Covered','map'],['5+','Years Experience','calendar'],['100+','Projects','folder']] as [$n,$l,$i])
                    <div class="text-center bg-gray-50 rounded-xl p-3 border border-gray-100">
                        <div class="font-heading font-extrabold text-lg" style="color:var(--green)">{{ $n }}</div>
                        <div class="text-gray-400 text-[10px] font-medium">{{ $l }}</div>
                    </div>
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="mailto:sanizakka@gmail.com" class="btn-primary text-sm py-2 px-4"><i class="fa-solid fa-envelope text-xs"></i> Send Message</a>
                    <a href="https://wa.me/2348032459879" target="_blank" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm text-white transition hover:opacity-90" style="background:#25D366"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ CONTACT ═══════════ --}}
<section id="contact" class="s-py bg-white" style="background:#f9fafb">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center s-header-mb">
            <div class="section-tag mx-auto"><i class="fa-solid fa-phone"></i> Contact Us</div>
            <h2 class="section-title">Get In Touch</h2>
            <p class="section-sub">We are always here to help you grow your agribusiness.</p>
        </div>
        <div class="grid lg:grid-cols-3 gap-5 lg:gap-8">
            <div class="space-y-3">
                @foreach([['fa-location-dot','Office Address','No. 21 Sarkin Maska Street, Dutsin Safe Lowcost, Katsina State, Nigeria','var(--green)'],['fa-phone','Call','08032459879 · 08129582957 (WhatsApp)','var(--blue)'],['fa-envelope','Email','msaslivestockagroservices@gmail.com','var(--gold)']] as [$ico,$label,$val,$color])
                <div class="flex gap-3 bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white shrink-0" style="background:{{ $color }}"><i class="{{ $ico }} text-sm"></i></div>
                    <div><div class="font-bold text-gray-800 text-sm mb-0.5">{{ $label }}</div><div class="text-gray-500 text-xs md:text-sm break-all">{{ $val }}</div></div>
                </div>
                @endforeach
                <div class="flex gap-2.5">
                    <a href="https://wa.me/2348129582957" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-white font-semibold text-sm hover:opacity-90 transition" style="background:#25D366"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                    <a href="tel:+2348032459879" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl font-semibold text-sm border-2 hover:opacity-90 transition" style="border-color:var(--green);color:var(--green)"><i class="fa-solid fa-phone"></i> Call Now</a>
                </div>
            </div>
            <div class="lg:col-span-2 bg-gray-50 rounded-2xl p-5 md:p-8 border border-gray-100">
                <h3 class="font-heading font-bold text-lg md:text-xl text-gray-800 mb-5">Send a Message</h3>
                <form action="https://wa.me/2348129582957" method="get" target="_blank" onsubmit="return sendWhatsApp(this)">
                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Full Name *</label><input type="text" name="name" required placeholder="Your full name" class="w-full border border-gray-200 rounded-xl px-3 md:px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Phone / WhatsApp *</label><input type="tel" name="phone" required placeholder="08xxxxxxxxx" class="w-full border border-gray-200 rounded-xl px-3 md:px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Email</label><input type="email" name="email" placeholder="you@example.com" class="w-full border border-gray-200 rounded-xl px-3 md:px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Subject</label><input type="text" name="subject" placeholder="How can we help?" class="w-full border border-gray-200 rounded-xl px-3 md:px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white"></div>
                    </div>
                    <div class="mb-4"><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Message *</label><textarea name="message" required rows="4" placeholder="Tell us about your farm or enquiry..." class="w-full border border-gray-200 rounded-xl px-3 md:px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white resize-none"></textarea></div>
                    <button type="submit" class="btn-primary w-full justify-center py-2.5 md:py-3 text-sm">Send Message <i class="fa-solid fa-paper-plane text-xs"></i></button>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ NEWSLETTER ═══════════ --}}
<section class="py-12 md:py-16" style="background:linear-gradient(135deg,#2E7D32,#1B5E20)">
    <div class="max-w-2xl mx-auto px-4 text-center text-white">
        <i class="fa-solid fa-envelope-open-text text-2xl md:text-3xl text-green-300 mb-3 md:mb-4 block"></i>
        <h2 class="font-heading font-extrabold text-xl md:text-2xl mb-2">Subscribe to Our Newsletter</h2>
        <p class="text-green-200 text-sm mb-5 md:mb-6">Get the latest agri-tech news, tips, market prices and platform updates delivered to your inbox.</p>
        <form class="flex gap-2 md:gap-3 max-w-md mx-auto" onsubmit="return subscribeNewsletter(this)">
            <input type="email" required placeholder="Enter your email address" class="flex-1 min-w-0 rounded-xl px-3 md:px-4 py-2.5 md:py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-green-300 bg-white">
            <button type="submit" class="bg-yellow-400 text-gray-900 font-bold px-4 md:px-5 py-2.5 md:py-3 rounded-xl hover:bg-yellow-300 transition text-sm shrink-0">Subscribe</button>
        </form>
    </div>
</section>

{{-- ═══════════ FOOTER ═══════════ --}}
<footer style="background:var(--footer-bg)" class="text-gray-400 pt-12 md:pt-16 pb-6">
    <div class="max-w-7xl mx-auto px-4">
        {{-- Main grid: brand full-width on mobile, 2-col sm, 3-col md, 6-col lg --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-5 md:gap-8 mb-6 md:mb-10">
            {{-- Brand (spans 2 cols on sm, 2 on md, 2 on lg) --}}
            <div class="col-span-2 md:col-span-1 lg:col-span-2">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background:var(--green)"><i class="fa-solid fa-leaf text-white text-xs"></i></div>
                    <div class="font-heading font-extrabold text-base text-white">MSAS Agro</div>
                </div>
                <div class="text-xs font-semibold mb-3 md:mb-4" style="color:var(--green)">Smart Agriculture, Better Tomorrow</div>
                <p class="text-sm leading-relaxed mb-4 md:mb-5 max-w-xs">Nigeria's leading AI-powered agribusiness platform connecting farmers, experts, governments, and development partners.</p>
                <div class="flex gap-2">
                    @foreach([['facebook-f','#3b82f6'],['twitter','#38bdf8'],['linkedin-in','#0077b5'],['youtube','#ef4444'],['whatsapp','#25d366']] as [$ico,$col])
                    <a href="#" class="w-8 h-8 rounded-full flex items-center justify-center transition hover:scale-110" style="background:rgba(255,255,255,.1)" onmouseover="this.style.background='{{ $col }}'" onmouseout="this.style.background='rgba(255,255,255,0.1)'"><i class="fa-brands fa-{{ $ico }} text-gray-300 text-xs"></i></a>
                    @endforeach
                </div>
            </div>
            {{-- Quick Links --}}
            <div>
                <h4 class="text-white font-bold text-sm mb-3 md:mb-4">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    @foreach([['#home','Home'],['#about','About Us'],['#solutions','Solutions'],['#marketplace','Marketplace'],['#pricing','Pricing']] as [$href,$label])
                    <li><a href="{{ $href }}" class="hover:text-green-400 transition text-xs md:text-sm">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            {{-- Resources --}}
            <div>
                <h4 class="text-white font-bold text-sm mb-3 md:mb-4">Resources</h4>
                <ul class="space-y-2 text-sm">
                    @foreach(['Blog','News & Events','Downloads','FAQs','Training'] as $item)
                    <li><a href="#" class="hover:text-green-400 transition text-xs md:text-sm">{{ $item }}</a></li>
                    @endforeach
                </ul>
            </div>
            {{-- Support --}}
            <div>
                <h4 class="text-white font-bold text-sm mb-3 md:mb-4">Support</h4>
                <ul class="space-y-2 text-sm">
                    @foreach(['Help Center','Contact Us','Privacy Policy','Terms & Conditions','Data Protection'] as $item)
                    <li><a href="#" class="hover:text-green-400 transition text-xs md:text-sm">{{ $item }}</a></li>
                    @endforeach
                </ul>
            </div>
            {{-- Contact --}}
            <div class="col-span-2 md:col-span-1">
                <h4 class="text-white font-bold text-sm mb-3 md:mb-4">Contact Us</h4>
                <div class="space-y-2.5 text-xs md:text-sm">
                    <p class="flex gap-2 items-start"><i class="fa-solid fa-location-dot text-green-500 mt-0.5 shrink-0"></i><span>No. 21 Sarkin Maska Street, Dutsin Safe Lowcost, Katsina State, Nigeria</span></p>
                    <p><a href="tel:+2348032459879" class="flex gap-2 hover:text-green-400 transition"><i class="fa-solid fa-phone text-green-500 shrink-0"></i>08032459879</a></p>
                    <p><a href="mailto:msaslivestockagroservices@gmail.com" class="flex gap-2 hover:text-green-400 transition break-all"><i class="fa-solid fa-envelope text-green-500 shrink-0"></i>msaslivestockagroservices@gmail.com</a></p>
                    <p><a href="https://wa.me/2348129582957" class="flex gap-2 hover:text-green-400 transition"><i class="fa-brands fa-whatsapp text-green-500 shrink-0"></i>08129582957 (WhatsApp)</a></p>
                </div>
            </div>
        </div>
        {{-- Newsletter strip --}}
        <div class="border-t border-white/10 pt-6 pb-5">
            <div class="grid md:grid-cols-2 gap-4 items-center">
                <div>
                    <h4 class="text-white font-bold text-sm mb-0.5">Newsletter</h4>
                    <p class="text-xs text-gray-500">Subscribe for latest updates and insights.</p>
                </div>
                <form class="flex gap-2" onsubmit="return subscribeNewsletter(this)">
                    <input type="email" required placeholder="Enter your email" class="flex-1 min-w-0 bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-green-400">
                    <button type="submit" class="btn-gold text-xs py-2 px-4 shrink-0">Subscribe</button>
                </form>
            </div>
        </div>
        <div class="border-t border-white/10 pt-5 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs">
            <p>&copy; {{ date('Y') }} MSAS Agro. All rights reserved.</p>
            <div class="flex gap-4 md:gap-5">
                <a href="#" class="hover:text-gray-200 transition">Privacy Policy</a>
                <a href="#" class="hover:text-gray-200 transition">Terms of Service</a>
                <a href="#" class="hover:text-gray-200 transition">Data Protection</a>
            </div>
        </div>
    </div>
</footer>

{{-- Floating WhatsApp --}}
<a href="https://wa.me/2348129582957" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp"
   class="fixed bottom-5 right-5 w-13 h-13 rounded-full flex items-center justify-center text-white text-2xl shadow-xl wa-float z-50 hover:scale-110 transition"
   style="background:#25D366;width:52px;height:52px;"><i class="fa-brands fa-whatsapp"></i></a>

<script>
function toggleFaq(el){
    var ans=el.nextElementSibling,icon=el.querySelector('i'),isOpen=ans.classList.contains('open');
    document.querySelectorAll('.faq-a.open').forEach(function(a){a.classList.remove('open');a.previousElementSibling.querySelector('i').style.transform='';});
    if(!isOpen){ans.classList.add('open');icon.style.transform='rotate(180deg)';}
}
function sendWhatsApp(form){
    var name=form.name.value,phone=form.phone.value,subject=form.subject?form.subject.value:'',message=form.message.value;
    var text='MSAS Agro Enquiry\n\nName: '+name+'\nPhone: '+phone+(subject?'\nSubject: '+subject:'')+'\n\nMessage:\n'+message;
    window.open('https://wa.me/2348129582957?text='+encodeURIComponent(text),'_blank');
    return false;
}
function subscribeNewsletter(form){
    var email=form.querySelector('input[type=email]').value;
    alert('Thank you for subscribing! We will be in touch at '+email);
    form.reset();return false;
}
(function(){
    var obs=new IntersectionObserver(function(entries){
        entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);}});
    },{threshold:0.08});
    document.querySelectorAll('.fade-up').forEach(function(el){obs.observe(el);});
})();
</script>
</body>
</html>
