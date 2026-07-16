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
        :root{--green:#2E7D32;--green-dark:#1B5E20;--green-light:#E8F5E9;--gold:#F9A825;--gold-dark:#F57F17;--blue:#0288D1;--blue-light:#E1F5FE;}
        body{font-family:'Inter',sans-serif;color:#212121;background:#fff;}
        h1,h2,h3,h4,.font-heading{font-family:'Poppins',sans-serif;}

        /* Navbar */
        #main-nav{transition:background .3s,box-shadow .3s;}
        #main-nav.scrolled{background:#fff!important;box-shadow:0 2px 20px rgba(0,0,0,.1);}
        .nav-link{position:relative;padding-bottom:2px;color:#374151;font-weight:500;font-size:.875rem;transition:color .2s;}
        .nav-link::after{content:'';position:absolute;bottom:0;left:0;width:0;height:2px;background:var(--green);transition:width .25s;}
        .nav-link:hover{color:var(--green);}
        .nav-link:hover::after{width:100%;}
        .btn-primary{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem 1.25rem;background:var(--green);color:#fff;border-radius:8px;font-weight:600;font-size:.875rem;transition:background .2s,transform .15s;border:2px solid var(--green);}
        .btn-primary:hover{background:var(--green-dark);border-color:var(--green-dark);transform:translateY(-1px);}
        .btn-outline{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem 1.25rem;background:transparent;color:var(--green);border:2px solid var(--green);border-radius:8px;font-weight:600;font-size:.875rem;transition:all .2s;}
        .btn-outline:hover{background:var(--green);color:#fff;transform:translateY(-1px);}
        .btn-gold{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem 1.25rem;background:var(--gold);color:#1a1a1a;border:2px solid var(--gold);border-radius:8px;font-weight:700;font-size:.875rem;transition:all .2s;}
        .btn-gold:hover{background:var(--gold-dark);border-color:var(--gold-dark);transform:translateY(-1px);}

        /* Hero */
        .hero-bg{background:linear-gradient(135deg,rgba(27,94,32,.92) 0%,rgba(46,125,50,.80) 60%,rgba(2,136,209,.70) 100%),url('https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=1600&q=80&auto=format&fit=crop') center/cover no-repeat;}
        .stat-card{background:rgba(255,255,255,.15);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.25);border-radius:12px;padding:1rem 1.25rem;text-align:center;}

        /* Section */
        .section-tag{display:inline-flex;align-items:center;gap:.4rem;background:var(--green-light);color:var(--green);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:.35rem .9rem;border-radius:999px;margin-bottom:1rem;}
        .section-title{font-size:clamp(1.6rem,3vw,2.25rem);font-weight:800;color:#111;line-height:1.25;margin-bottom:.75rem;}
        .section-sub{font-size:1rem;color:#555;max-width:600px;margin:0 auto;line-height:1.7;}

        /* Solution cards */
        .sol-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:1.5rem;text-align:center;transition:all .3s;cursor:pointer;}
        .sol-card:hover{transform:translateY(-6px);box-shadow:0 12px 32px rgba(46,125,50,.15);border-color:var(--green);}
        .sol-icon{width:52px;height:52px;border-radius:14px;background:var(--green-light);display:flex;align-items:center;justify-content:center;font-size:1.375rem;color:var(--green);margin:0 auto .875rem;}

        /* Feature cards */
        .feat-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:1.375rem;display:flex;gap:1rem;align-items:flex-start;transition:all .3s;}
        .feat-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.08);border-color:var(--green);}
        .feat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.125rem;flex-shrink:0;}

        /* Pricing */
        .price-card{background:#fff;border:2px solid #e5e7eb;border-radius:20px;padding:2rem;transition:all .3s;}
        .price-card.featured{border-color:var(--green);box-shadow:0 16px 48px rgba(46,125,50,.18);}
        .price-card:hover{transform:translateY(-4px);}

        /* FAQ */
        .faq-item{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:.75rem;}
        .faq-q{padding:1.125rem 1.25rem;font-weight:600;font-size:.9375rem;color:#111;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:1rem;background:#fff;transition:background .2s;}
        .faq-q:hover{background:#f9fafb;}
        .faq-a{padding:0 1.25rem;font-size:.9rem;color:#555;line-height:1.7;max-height:0;overflow:hidden;transition:max-height .35s ease,padding .3s;}
        .faq-a.open{max-height:400px;padding-bottom:1.25rem;padding-top:.25rem;}

        /* Testimonial */
        .testi-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:1.75rem;}

        /* Partner logo */
        .partner-logo{height:44px;filter:grayscale(1);opacity:.55;transition:all .3s;}
        .partner-logo:hover{filter:grayscale(0);opacity:1;}

        /* How it works connector */
        .step-connector{position:absolute;top:26px;left:calc(50% + 40px);right:calc(-50% + 40px);height:2px;background:linear-gradient(90deg,#2E7D32,#0288D1);}
        @media(max-width:767px){.step-connector{display:none;}}

        /* Floating WhatsApp */
        .wa-float{animation:wa-pulse 2.5s infinite;}
        @keyframes wa-pulse{0%,100%{box-shadow:0 0 0 0 rgba(37,211,102,.6);}70%{box-shadow:0 0 0 14px rgba(37,211,102,0);}}

        /* Animations */
        .fade-up{opacity:0;transform:translateY(24px);transition:opacity .6s,transform .6s;}
        .fade-up.visible{opacity:1;transform:translateY(0);}
        @media(prefers-reduced-motion:reduce){.fade-up{opacity:1;transform:none;}}

        [x-cloak]{display:none!important;}
    </style>
</head>
<body class="antialiased">

{{-- ═══════════════════════════════════════ NAVBAR ═══════════════════════════════════════ --}}
<header id="main-nav" class="fixed top-0 inset-x-0 z-50 transition-all" x-data="{ open:false, scrolled:false }"
    x-init="window.addEventListener('scroll',()=>{ scrolled=window.scrollY>40; document.getElementById('main-nav').classList.toggle('scrolled',scrolled); })">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
        {{-- Logo --}}
        <a href="/" class="flex items-center gap-2 shrink-0">
            <img src="{{ asset('images/msas-logo.png') }}" alt="MSAS Agro" class="h-10 w-auto" onerror="this.style.display='none'">
            <div class="leading-none">
                <div class="font-heading font-extrabold text-base" :class="scrolled ? 'text-gray-900' : 'text-white'" style="line-height:1.1">MSAS Agro</div>
                <div class="text-[9px] font-medium tracking-wider" :class="scrolled ? 'text-green-700' : 'text-green-200'">Smart Agriculture, Better Tomorrow</div>
            </div>
        </a>

        {{-- Desktop Nav --}}
        <nav class="hidden lg:flex items-center gap-5">
            <a href="#home"          class="nav-link" :class="scrolled ? '' : 'text-white hover:text-green-200'">Home</a>
            <a href="#about"         class="nav-link" :class="scrolled ? '' : 'text-white hover:text-green-200'">About</a>
            <a href="#solutions"     class="nav-link" :class="scrolled ? '' : 'text-white hover:text-green-200'">Solutions</a>
            <a href="#features"      class="nav-link" :class="scrolled ? '' : 'text-white hover:text-green-200'">Features</a>
            <a href="#marketplace"   class="nav-link" :class="scrolled ? '' : 'text-white hover:text-green-200'">Marketplace</a>
            <a href="#pricing"       class="nav-link" :class="scrolled ? '' : 'text-white hover:text-green-200'">Pricing</a>
            <a href="#contact"       class="nav-link" :class="scrolled ? '' : 'text-white hover:text-green-200'">Contact</a>
        </nav>

        {{-- Desktop Auth --}}
        <div class="hidden md:flex items-center gap-2">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary text-xs py-2 px-4"><i class="fa-solid fa-gauge-high text-xs"></i> Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="font-medium text-sm transition px-3 py-1.5 rounded-lg" :class="scrolled ? 'text-gray-700 hover:text-green-700' : 'text-white hover:bg-white/10'">Sign In</a>
                <a href="{{ route('register') }}" class="btn-primary text-xs py-2 px-4">Sign Up</a>
                <a href="{{ route('register') }}" class="btn-gold text-xs py-2 px-4">Check In</a>
            @endauth
        </div>

        {{-- Hamburger --}}
        <button @click="open=!open" class="lg:hidden p-2 rounded-lg transition" :class="scrolled ? 'text-gray-800 hover:bg-gray-100' : 'text-white hover:bg-white/10'" :aria-expanded="open">
            <i x-show="!open" class="fa-solid fa-bars text-lg"></i>
            <i x-show="open"  class="fa-solid fa-xmark text-lg" x-cloak></i>
        </button>
    </div>

    {{-- Mobile menu --}}
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="lg:hidden bg-white border-t border-gray-100 shadow-xl">
        <nav class="px-4 py-3 space-y-1 border-b border-gray-100">
            @foreach([['#home','Home','house'],['#about','About','circle-info'],['#solutions','Solutions','leaf'],['#features','Features','star'],['#marketplace','Marketplace','store'],['#pricing','Pricing','tag'],['#contact','Contact','phone']] as [$href,$label,$icon])
            <a href="{{ $href }}" @click="open=false" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-green-50 hover:text-green-800 font-medium text-sm transition"><i class="fa-solid fa-{{ $icon }} w-4 text-green-600"></i> {{ $label }}</a>
            @endforeach
        </nav>
        <div class="px-4 py-4 flex flex-col gap-3">
            @auth
                <a href="{{ url('/dashboard') }}" @click="open=false" class="btn-primary justify-center">Dashboard</a>
            @else
                <a href="{{ route('login') }}"    @click="open=false" class="btn-outline justify-center">Sign In</a>
                <a href="{{ route('register') }}" @click="open=false" class="btn-primary justify-center">Sign Up</a>
                <a href="{{ route('register') }}" @click="open=false" class="btn-gold justify-center">Check In</a>
            @endauth
        </div>
    </div>
</header>

{{-- ═══════════════════════════════════════ HERO ═══════════════════════════════════════ --}}
<section id="home" class="hero-bg min-h-screen flex flex-col justify-center pt-16">
    <div class="max-w-7xl mx-auto px-4 py-20 grid lg:grid-cols-12 gap-12 items-center">
        <div class="lg:col-span-7 text-white">
            <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur border border-white/25 rounded-full px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-green-100 mb-6">
                <span class="w-2 h-2 rounded-full bg-green-300 animate-pulse inline-block"></span>
                Nigeria's #1 AgriTech Platform
            </div>
            <h1 class="font-heading text-4xl md:text-5xl lg:text-[58px] font-extrabold leading-[1.15] mb-5">
                Empowering Agriculture<br/>with <span class="text-yellow-300">Artificial Intelligence</span>
            </h1>
            <p class="text-green-100 text-lg leading-relaxed mb-8 max-w-lg">Helping farmers, livestock owners, agribusinesses, governments and development partners make smarter decisions through AI, data and digital innovation.</p>
            <div class="flex flex-wrap gap-3 mb-12">
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold text-base shadow-lg transition hover:-translate-y-1" style="background:#F9A825;color:#1a1a1a;box-shadow:0 8px 24px rgba(249,168,37,.4)"><i class="fa-solid fa-seedling"></i> Start Farming</a>
                <a href="#solutions"              class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-base bg-white/15 backdrop-blur border border-white/30 text-white hover:bg-white/25 transition">Watch Demo <i class="fa-solid fa-play text-xs"></i></a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-base bg-white/10 backdrop-blur border border-white/20 text-white hover:bg-white/20 transition"><i class="fa-solid fa-mobile-screen text-xs"></i> Download App</a>
            </div>
            {{-- Stats row --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                @foreach([['20,000+','Registered Farmers','users'],['150+','Cooperatives','handshake'],['100+','Projects','folder'],['36','States Coverage','map'],['99.9%','System Uptime','server']] as [$num,$label,$ico])
                <div class="stat-card">
                    <i class="fa-solid fa-{{ $ico }} text-green-300 text-sm mb-1"></i>
                    <div class="font-heading font-extrabold text-xl text-white leading-none">{{ $num }}</div>
                    <div class="text-green-200 text-[11px] mt-0.5 font-medium">{{ $label }}</div>
                </div>
                @endforeach
            </div>
        </div>
        {{-- Hero visual --}}
        <div class="lg:col-span-5 hidden lg:flex justify-center">
            <div class="relative w-72 h-72 rounded-full flex items-center justify-center" style="background:rgba(255,255,255,.1);border:2px solid rgba(255,255,255,.2)">
                <div class="absolute inset-4 rounded-full flex items-center justify-center" style="background:rgba(255,255,255,.08)">
                    <i class="fa-solid fa-leaf text-8xl text-green-200 opacity-80"></i>
                </div>
                {{-- Floating badges --}}
                <div class="absolute -top-4 -right-4 bg-white rounded-2xl shadow-xl p-3 flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center"><i class="fa-solid fa-robot text-green-700 text-sm"></i></div><div class="text-xs"><div class="font-bold text-gray-800">AI Scan</div><div class="text-green-600 font-semibold">94% accuracy</div></div></div>
                <div class="absolute -bottom-4 -left-4 bg-white rounded-2xl shadow-xl p-3 flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center"><i class="fa-solid fa-chart-line text-yellow-600 text-sm"></i></div><div class="text-xs"><div class="font-bold text-gray-800">Yield Up</div><div class="text-yellow-600 font-semibold">+40% avg</div></div></div>
                <div class="absolute top-1/2 -right-10 -translate-y-1/2 bg-white rounded-2xl shadow-xl p-3"><div class="text-xs text-center"><div class="font-bold text-gray-800 mb-1">System</div><div class="text-green-600 font-bold text-base">Live</div></div></div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ TRUSTED BY ═══════════════════════════════════════ --}}
<section class="py-12 bg-gray-50 border-y border-gray-100">
    <div class="max-w-6xl mx-auto px-4">
        <p class="text-center text-xs font-bold uppercase tracking-widest text-gray-400 mb-7">Trusted By Leading Organizations</p>
        <div class="flex flex-wrap items-center justify-center gap-8 lg:gap-14">
            @foreach(['Federal Ministry of Agriculture','The World Bank','IFAD','NIRSAL','AGRA','CGIAR','FMARD'] as $org)
            <div class="text-gray-400 font-heading font-bold text-sm hover:text-green-700 transition cursor-default">{{ $org }}</div>
            @endforeach
            <div class="text-gray-300 font-semibold text-sm">And Many More...</div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ ABOUT ═══════════════════════════════════════ --}}
<section id="about" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-2 gap-16 items-center">
        {{-- Image grid --}}
        <div class="grid grid-cols-2 gap-4">
            <img src="https://images.unsplash.com/photo-1586771107445-d3ca888129ff?w=400&q=80&auto=format&fit=crop" alt="Crop farming" class="rounded-2xl object-cover h-44 w-full">
            <img src="https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=400&q=80&auto=format&fit=crop" alt="Farmers" class="rounded-2xl object-cover h-44 w-full mt-8">
            <img src="https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=400&q=80&auto=format&fit=crop" alt="Poultry" class="rounded-2xl object-cover h-44 w-full -mt-8">
            <img src="https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?w=400&q=80&auto=format&fit=crop" alt="Livestock" class="rounded-2xl object-cover h-44 w-full">
        </div>
        {{-- Text --}}
        <div>
            <div class="section-tag"><i class="fa-solid fa-circle-info"></i> About MSAS Agro</div>
            <h2 class="section-title text-left">Transforming Agriculture<br/>Across Nigeria</h2>
            <p class="text-gray-500 leading-relaxed mb-6">MSAS Agro is an AI-powered digital platform that provides innovative solutions for crop farming, livestock management, poultry, fish farming, marketplace, finance, insurance, and data analytics. Since 2019, we have been driving agriculture transformation.</p>
            <div class="grid sm:grid-cols-2 gap-5 mb-8">
                @foreach([['Our Mission','To empower agriculture stakeholders with smart digital solutions.','bullseye','green'],['Our Vision','To become Africa\'s leading digital agriculture platform.','eye','blue'],['Core Values','Innovation, Integrity, Impact, Collaboration, Sustainability.','heart','yellow'],['Our Journey','Since 2019, we have been driving agriculture transformation.','route','green']] as [$title,$text,$ico,$color])
                <div class="flex gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:var(--green-light)"><i class="fa-solid fa-{{ $ico }} text-sm" style="color:var(--green)"></i></div>
                    <div><div class="font-bold text-gray-800 text-sm mb-0.5">{{ $title }}</div><div class="text-gray-500 text-xs leading-relaxed">{{ $text }}</div></div>
                </div>
                @endforeach
            </div>
            <a href="{{ route('register') }}" class="btn-primary">Read More <i class="fa-solid fa-arrow-right text-xs"></i></a>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ SOLUTIONS ═══════════════════════════════════════ --}}
<section id="solutions" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <div class="section-tag mx-auto"><i class="fa-solid fa-layer-group"></i> Platform Solutions</div>
            <h2 class="section-title">Everything You Need to<br/><span style="color:var(--green)">Grow Your Agribusiness</span></h2>
            <p class="section-sub">20+ integrated modules covering every aspect of modern agricultural management.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach([
                ['Crop Farming','seedling','services.crops'],
                ['Livestock','cow','services.livestock'],
                ['Poultry','egg','services.poultry'],
                ['Fish Farming','fish','register'],
                ['Greenhouse','house','register'],
                ['Farm Marketplace','store','marketplace'],
                ['Warehouse','warehouse','register'],
                ['AI Assistant','robot','register'],
                ['Weather Intel','cloud-sun','register'],
                ['Satellite Monitoring','satellite-dish','register'],
                ['Inventory','boxes-stacking','register'],
                ['Farm Finance','coins','services.finance'],
                ['Insurance','shield-halved','register'],
                ['Extension Services','person-chalkboard','register'],
                ['GIS Mapping','map-location-dot','register'],
                ['Govt Dashboard','landmark','register'],
                ['NGO Dashboard','hand-holding-heart','register'],
                ['Research Portal','microscope','register'],
                ['Data Analytics','chart-bar','register'],
                ['IoT Devices','microchip','register'],
            ] as [$name,$icon,$route])
            <div class="sol-card fade-up">
                <div class="sol-icon"><i class="fa-solid fa-{{ $icon }}"></i></div>
                <div class="font-heading font-bold text-gray-800 text-[13px] mb-1">{{ $name }}</div>
                <a href="{{ route($route) }}" class="text-[11px] font-semibold transition" style="color:var(--green)">Learn More →</a>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-10">
            <a href="{{ route('register') }}" class="btn-primary inline-flex px-8 py-3 text-sm">Explore All Solutions <i class="fa-solid fa-arrow-right text-xs"></i></a>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ AI ASSISTANT ═══════════════════════════════════════ --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-2 gap-16 items-center">
        <div>
            <div class="section-tag"><i class="fa-solid fa-robot"></i> AI Powered</div>
            <h2 class="section-title text-left">Meet Your<br/><span style="color:var(--green)">AI Farm Assistant</span></h2>
            <p class="text-gray-500 leading-relaxed mb-7">Get instant answers, smart recommendations and real-time insights to improve your productivity. Powered by advanced vision AI that diagnoses crop diseases, livestock conditions, and soil health from a single photo.</p>
            <ul class="space-y-3 mb-8">
                @foreach(['Image-based disease detection','Fertilizer & crop recommendation','Weather & market price updates','Yield prediction & alerts','Soil sample analysis']) as $feat)
                <li class="flex items-center gap-3 text-sm text-gray-700"><span class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 text-[10px]" style="background:var(--green-light);color:var(--green)"><i class="fa-solid fa-check"></i></span>{{ $feat }}</li>
                @endforeach
            </ul>
            <a href="{{ route('register') }}" class="btn-primary">Try AI Assistant Now <i class="fa-solid fa-arrow-right text-xs"></i></a>
        </div>
        {{-- AI Chat Card --}}
        <div class="bg-gray-50 rounded-2xl border border-gray-200 overflow-hidden shadow-lg">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 bg-white">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold" style="background:var(--green)"><i class="fa-solid fa-robot"></i></div>
                    <div><div class="font-bold text-gray-800 text-sm">AI Farm Assistant</div><div class="text-xs text-green-600 font-medium">● Online</div></div>
                </div>
                <i class="fa-solid fa-ellipsis text-gray-400"></i>
            </div>
            <div class="p-5 space-y-4 min-h-[200px]">
                <div class="flex gap-3"><div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs shrink-0" style="background:var(--green)"><i class="fa-solid fa-robot"></i></div><div class="bg-white rounded-2xl rounded-tl-none px-4 py-2.5 shadow-sm border border-gray-100 text-sm text-gray-700 max-w-xs">Hello! I am your AI Farm Assistant. How can I help you today?</div></div>
                <div class="flex justify-end"><div class="rounded-2xl rounded-tr-none px-4 py-2.5 text-sm text-white max-w-xs" style="background:var(--green)">What is the best fertilizer for maize at vegetative stage?</div></div>
                <div class="flex gap-3"><div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs shrink-0" style="background:var(--green)"><i class="fa-solid fa-robot"></i></div><div class="bg-white rounded-2xl rounded-tl-none px-4 py-2.5 shadow-sm border border-gray-100 text-sm text-gray-700 max-w-xs">For maize at vegetative stage, <strong>NPK 20:10:10</strong> at 100kg/ha is recommended. Ensure good weed control and adequate irrigation.</div></div>
            </div>
            <div class="flex gap-2 px-5 pb-5">
                <input type="text" placeholder="Ask something..." class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-green-400">
                <button class="w-10 h-10 rounded-xl text-white flex items-center justify-center shrink-0" style="background:var(--green)"><i class="fa-solid fa-paper-plane text-xs"></i></button>
            </div>
            <div class="grid grid-cols-2 gap-3 px-5 pb-5">
                @foreach([['Disease Detection','Upload image & detect','bug','red'],['Market Prices','Real-time commodity prices','chart-line','blue'],['Weather Alerts','Forecasts & alerts','cloud-sun','yellow'],['Smart Insights','AI productivity tips','lightbulb','green']]) as [$t,$d,$i,$c])
                <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm"><i class="fa-solid fa-{{ $i }} text-{{ $c }}-500 text-sm mb-1.5 block"></i><div class="text-[11px] font-bold text-gray-800">{{ $t }}</div><div class="text-[10px] text-gray-500">{{ $d }}</div></div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ FEATURES ═══════════════════════════════════════ --}}
<section id="features" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <div class="section-tag mx-auto"><i class="fa-solid fa-star"></i> Features</div>
            <h2 class="section-title">Built for the Modern<br/><span style="color:var(--green)">Agri-Professional</span></h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
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
                <div><div class="font-bold text-gray-800 text-sm mb-1">{{ $title }}</div><div class="text-gray-500 text-xs leading-relaxed">{{ $desc }}</div></div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ HOW IT WORKS ═══════════════════════════════════════ --}}
<section class="py-24 bg-white">
    <div class="max-w-5xl mx-auto px-4">
        <div class="text-center mb-14">
            <div class="section-tag mx-auto">How It Works</div>
            <h2 class="section-title">Get Started in<br/><span style="color:var(--green)">Four Simple Steps</span></h2>
        </div>
        <div class="grid md:grid-cols-4 gap-8 relative">
            @foreach([['1','Register','Create your free account as a farmer, vet, agronomist, or any role','user-plus'],['2','Create Farm','Add your farm profile, crops, livestock, and field details','plus-circle'],['3','Collect Data','Record activities, run AI scans, log health events daily','database'],['4','Get AI Insights','Receive smart recommendations and actionable AI insights','brain']] as [$n,$t,$d,$i])
            <div class="text-center fade-up relative">
                @if(!$loop->last)<div class="step-connector hidden md:block"></div>@endif
                <div class="w-14 h-14 rounded-full text-white font-heading font-black text-xl flex items-center justify-center mx-auto mb-4 shadow-lg" style="background:linear-gradient(135deg,#2E7D32,#0288D1)">{{ $n }}</div>
                <i class="fa-solid fa-{{ $i }} mb-3 text-2xl block" style="color:var(--green)"></i>
                <h3 class="font-heading font-bold text-gray-800 text-base mb-2">{{ $t }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed">{{ $d }}</p>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-12"><a href="{{ route('register') }}" class="btn-primary px-8 py-3">Start Farming Today <i class="fa-solid fa-arrow-right text-xs"></i></a></div>
    </div>
</section>

{{-- ═══════════════════════════════════════ MARKETPLACE ═══════════════════════════════════════ --}}
<section id="marketplace" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <div class="section-tag"><i class="fa-solid fa-store"></i> MSAS Marketplace</div>
                <h2 class="section-title text-left">Buy &amp; Sell Farm<br/><span style="color:var(--gold)">Inputs &amp; Produce</span></h2>
                <p class="text-gray-500 leading-relaxed mb-7">Access Nigeria's largest agricultural marketplace. Buy seeds, fertilizer, equipment and livestock — or sell your produce directly to buyers, cooperatives, and processors.</p>
                <div class="grid grid-cols-2 gap-4 mb-7">
                    @foreach([['Seeds & Seedlings','seedling','green'],['Fertilizers','sack-dollar','yellow'],['Livestock & Poultry','cow','blue'],['Farm Equipment','tractor','purple'],['Veterinary Supplies','syringe','red'],['Processing Tools','gears','gray']]) as [$n,$i,$c])
                    <div class="flex items-center gap-3 bg-white rounded-xl p-3.5 border border-gray-100 shadow-sm"><div class="w-9 h-9 rounded-lg bg-{{ $c }}-100 flex items-center justify-center shrink-0"><i class="fa-solid fa-{{ $i }} text-{{ $c }}-600 text-sm"></i></div><span class="font-semibold text-gray-700 text-sm">{{ $n }}</span></div>
                    @endforeach
                </div>
                <a href="{{ route('marketplace') }}" class="btn-primary">Visit Marketplace <i class="fa-solid fa-arrow-right text-xs"></i></a>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 rounded-2xl overflow-hidden h-48"><img src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&q=80&auto=format&fit=crop" alt="Marketplace" class="w-full h-full object-cover"></div>
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm text-center"><div class="text-2xl font-extrabold mb-1" style="color:var(--green)">120+</div><div class="text-gray-500 text-xs font-medium">Active Listings</div></div>
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm text-center"><div class="text-2xl font-extrabold text-yellow-500 mb-1">₦0</div><div class="text-gray-500 text-xs font-medium">Listing Fee</div></div>
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm text-center"><div class="text-2xl font-extrabold mb-1" style="color:var(--blue)">35+</div><div class="text-gray-500 text-xs font-medium">Verified Dealers</div></div>
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm text-center"><div class="text-2xl font-extrabold text-purple-600 mb-1">NGN</div><div class="text-gray-500 text-xs font-medium">Naira Payments</div></div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ MOBILE APP ═══════════════════════════════════════ --}}
<section class="py-24 text-white relative overflow-hidden" style="background:linear-gradient(135deg,#1B5E20 0%,#2E7D32 50%,#0288D1 100%)">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(#fff 1px,transparent 1px);background-size:20px 20px;"></div>
    <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-2 gap-12 items-center relative z-10">
        <div>
            <div class="inline-flex items-center gap-2 bg-white/15 border border-white/25 rounded-full px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-green-200 mb-5">Mobile App</div>
            <h2 class="font-heading text-4xl font-extrabold mb-4">Take MSAS Agro<br/><span class="text-yellow-300">Anywhere You Go</span></h2>
            <p class="text-green-100 leading-relaxed mb-8">Our mobile app is fast, offline-ready and designed for farmers. Record data in the field, get AI diagnoses, check market prices — even without internet.</p>
            <div class="grid grid-cols-2 gap-3 mb-8">
                @foreach([['Offline Data Collection','wifi-slash'],['Real-time Cloud Sync','cloud'],['Push Notifications','bell'],['Biometric Login','fingerprint']]) as [$f,$i])
                <div class="flex items-center gap-2.5 bg-white/10 border border-white/15 rounded-xl px-4 py-3"><i class="fa-solid fa-{{ $i }} text-green-300 text-sm shrink-0"></i><span class="text-sm font-medium text-green-50">{{ $f }}</span></div>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="flex items-center gap-2.5 bg-black text-white rounded-xl px-5 py-3 hover:bg-gray-900 transition"><i class="fa-brands fa-google-play text-lg text-green-400"></i><div><div class="text-[9px] text-gray-300 uppercase tracking-wide">Get it on</div><div class="text-sm font-bold">Google Play</div></div></a>
                <a href="{{ route('register') }}" class="flex items-center gap-2.5 bg-black text-white rounded-xl px-5 py-3 hover:bg-gray-900 transition"><i class="fa-brands fa-apple text-lg"></i><div><div class="text-[9px] text-gray-300 uppercase tracking-wide">Download on</div><div class="text-sm font-bold">App Store</div></div></a>
            </div>
        </div>
        <div class="flex justify-center gap-6">
            <div class="w-40 h-72 bg-white/15 backdrop-blur border border-white/25 rounded-3xl flex flex-col items-center justify-center shadow-2xl"><i class="fa-solid fa-mobile-screen text-5xl text-green-200 mb-3"></i><div class="text-xs text-green-200 font-semibold">Android</div></div>
            <div class="w-40 h-72 bg-white/20 backdrop-blur border border-white/30 rounded-3xl flex flex-col items-center justify-center shadow-2xl mt-8"><i class="fa-brands fa-apple text-5xl text-green-100 mb-3"></i><div class="text-xs text-green-200 font-semibold">iOS</div></div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ TESTIMONIALS ═══════════════════════════════════════ --}}
<section id="testimonials" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <div class="section-tag mx-auto"><i class="fa-solid fa-star"></i> Success Stories</div>
            <h2 class="section-title">What Farmers Are Saying</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['Amina Yusuf','Maize Farmer, Kano State','MSAS Agro has improved my yield by 40% through smart recommendations. The AI disease detection saved my entire farm from a devastating fungal outbreak last season.','5','photo-1494790108377-be9c29b29330'],
                ['Bello Salisu','Livestock Farmer, Kaduna','The livestock monitoring system helps me track my animals health in real-time. I can now detect illness early and call the vet before it spreads to the whole herd.','5','photo-1570295999919-56ceb5ecca61'],
                ['Grace Okafor','Poultry Farmer, Enugu','I get market prices, alerts and training all in one platform. The marketplace helps me sell my eggs directly to buyers without middlemen.','5','photo-1508214751196-bcfd4ca60f91'],
            ] as [$name,$role,$text,$stars,$img])
            <div class="testi-card fade-up">
                <div class="flex gap-0.5 text-yellow-400 mb-4">@for($i=0;$i<5;$i++)<i class="fa-solid fa-star text-sm"></i>@endfor</div>
                <p class="text-gray-600 text-sm leading-relaxed mb-5 italic">&ldquo;{{ $text }}&rdquo;</p>
                <div class="flex items-center gap-3">
                    <img src="https://images.unsplash.com/{{ $img }}?w=80&h=80&q=80&auto=format&fit=crop&crop=face" alt="{{ $name }}" class="w-11 h-11 rounded-full object-cover">
                    <div><div class="font-bold text-gray-800 text-sm">{{ $name }}</div><div class="text-gray-400 text-xs">{{ $role }}</div></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ PARTNERS ═══════════════════════════════════════ --}}
<section class="py-16 bg-gray-50 border-y border-gray-100">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-10"><div class="section-tag mx-auto">Development Partners</div></div>
        <div class="flex flex-wrap items-center justify-center gap-8 lg:gap-12">
            @foreach(['FAO','USAID','GIZ','African Union','ECOWAS','CBN','BOI','NIRSAL','AfDB','Bill & Melinda Gates Foundation']) as $p)
            <div class="text-gray-400 font-heading font-bold text-sm hover:text-green-700 transition cursor-default">{{ $p }}</div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ PRICING ═══════════════════════════════════════ --}}
<section id="pricing" class="py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-14">
            <div class="section-tag mx-auto"><i class="fa-solid fa-tag"></i> Pricing</div>
            <h2 class="section-title">Simple, Transparent<br/><span style="color:var(--green)">Pricing Plans</span></h2>
            <p class="section-sub">Start free, scale as you grow. No hidden charges.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                ['Free Farmer','₦0','month','Basic farm management, AI scans (3/month), Marketplace access','register',false,['Farm records','AI Scans ×3','Marketplace','Mobile app','Community forum']],
                ['Premium','₦2,500','month','Unlimited AI scans, Vet consultations, Weather alerts, Priority support','register',true,['Everything in Free','Unlimited AI Scans','Vet consultation','Weather intelligence','SMS alerts','Priority support']],
                ['Enterprise','₦15,000','month','All features + custom integrations, dedicated support, bulk user management','register',false,['Everything in Premium','Custom integrations','API access','Dedicated manager','White-label option','SLA guarantee']],
                ['Government / NGO','Custom','project','Tailored for large-scale deployments, nationwide coverage, M&E dashboards','register',false,['All Enterprise features','GIS & satellite mapping','M&E dashboards','Bulk registration','Training & onboarding','Policy reporting']],
            ] as [$plan,$price,$per,$desc,$route,$featured,$items])
            <div class="price-card {{ $featured ? 'featured' : '' }} fade-up">
                @if($featured)<div class="text-center mb-4"><span class="text-xs font-bold uppercase tracking-widest text-white px-3 py-1 rounded-full" style="background:var(--green)">Most Popular</span></div>@endif
                <div class="text-gray-500 font-semibold text-sm mb-2">{{ $plan }}</div>
                <div class="font-heading font-extrabold text-3xl mb-0.5 text-gray-900">{{ $price }}</div>
                <div class="text-gray-400 text-xs mb-5">per {{ $per }}</div>
                <p class="text-gray-500 text-xs leading-relaxed mb-5">{{ $desc }}</p>
                <ul class="space-y-2.5 mb-6">
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

{{-- ═══════════════════════════════════════ FAQ ═══════════════════════════════════════ --}}
<section class="py-24 bg-gray-50">
    <div class="max-w-3xl mx-auto px-4">
        <div class="text-center mb-14">
            <div class="section-tag mx-auto"><i class="fa-solid fa-circle-question"></i> FAQ</div>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div id="faq-list">
            @foreach([
                ['What is MSAS Agro?','MSAS Agro is an AI-powered digital agriculture platform built for Nigerian farmers, livestock owners, cooperatives, governments, and development partners. It provides tools for farm management, AI diagnostics, marketplace, vet consultations, and data analytics.'],
                ['Is the platform free to use?','Yes! Our Free Farmer plan is completely free and includes basic farm management, 3 AI scans per month, and marketplace access. Premium features are available from ₦2,500/month.'],
                ['Does it work without internet?','Yes. Our mobile app supports offline data collection. Once you reconnect, all data syncs automatically to the cloud.'],
                ['How does the AI diagnostic work?','Simply upload a photo of your sick animal, diseased crop, or soil sample. Our AI engine — powered by advanced computer vision — identifies the condition and provides a treatment plan within seconds.'],
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

{{-- ═══════════════════════════════════════ CONTACT ═══════════════════════════════════════ --}}
<section id="contact" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <div class="section-tag mx-auto"><i class="fa-solid fa-phone"></i> Contact Us</div>
            <h2 class="section-title">Get In Touch</h2>
            <p class="section-sub">We are always here to help you grow your agribusiness.</p>
        </div>
        <div class="grid lg:grid-cols-3 gap-8">
            {{-- Contact details --}}
            <div class="space-y-5">
                @foreach([['fa-location-dot','Office Address','No 21 Sarkin Maska Street, Dutsin Safe Lowcost, Katsina State, Nigeria','var(--green)'],['fa-phone','Phone / WhatsApp','08129582957','var(--blue)'],['fa-envelope','Email','msaslivestockagroservices@gmail.com','var(--gold)']]) as [$ico,$label,$val,$color])
                <div class="flex gap-4 bg-gray-50 rounded-2xl p-5 border border-gray-100">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0" style="background:{{ $color }}"><i class="{{ $ico }} text-sm"></i></div>
                    <div><div class="font-bold text-gray-800 text-sm mb-0.5">{{ $label }}</div><div class="text-gray-500 text-sm">{{ $val }}</div></div>
                </div>
                @endforeach
                <div class="flex gap-3 pt-2">
                    <a href="https://wa.me/2348129582957" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl text-white font-semibold text-sm transition hover:opacity-90" style="background:#25D366"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                    <a href="tel:08129582957"             class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl font-semibold text-sm border-2 transition hover:opacity-90" style="border-color:var(--green);color:var(--green)"><i class="fa-solid fa-phone"></i> Call Now</a>
                </div>
                {{-- CEO card --}}
                <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 flex items-center gap-4">
                    <img src="{{ asset('images/ceo-sani-yawale-zakka.jpg') }}" alt="Sani Yawale Zakka" class="w-14 h-14 rounded-full object-cover border-2 border-green-200" onerror="this.src='https://ui-avatars.com/api/?name=Sani+Zakka&background=2E7D32&color=fff&size=80&rounded=true'">
                    <div><div class="font-bold text-gray-800">Sani Yawale Zakka</div><div class="text-xs text-green-700 font-semibold">Founder &amp; CEO</div><div class="text-xs text-gray-400">MSAS Agro · Katsina, Nigeria</div></div>
                </div>
            </div>

            {{-- Contact Form --}}
            <div class="lg:col-span-2 bg-gray-50 rounded-2xl p-8 border border-gray-100">
                <h3 class="font-heading font-bold text-xl text-gray-800 mb-6">Send a Message</h3>
                <form action="https://wa.me/2348129582957" method="get" target="_blank" onsubmit="return sendWhatsApp(this)">
                    <div class="grid sm:grid-cols-2 gap-5 mb-5">
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Full Name *</label><input type="text" name="name" required placeholder="Your full name" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Phone / WhatsApp *</label><input type="tel" name="phone" required placeholder="08xxxxxxxxx" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Email</label><input type="email" name="email" placeholder="you@example.com" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Subject</label><input type="text" name="subject" placeholder="How can we help?" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white"></div>
                    </div>
                    <div class="mb-5"><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Message *</label><textarea name="message" required rows="4" placeholder="Tell us about your farm or enquiry..." class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-green-400 bg-white resize-none"></textarea></div>
                    <button type="submit" class="btn-primary w-full justify-center py-3 text-sm">Send Message <i class="fa-solid fa-paper-plane text-xs"></i></button>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════ NEWSLETTER ═══════════════════════════════════════ --}}
<section class="py-16" style="background:linear-gradient(135deg,#2E7D32,#1B5E20)">
    <div class="max-w-2xl mx-auto px-4 text-center text-white">
        <i class="fa-solid fa-envelope-open-text text-3xl text-green-300 mb-4 block"></i>
        <h2 class="font-heading font-extrabold text-2xl mb-2">Subscribe to Our Newsletter</h2>
        <p class="text-green-200 text-sm mb-6">Get the latest agri-tech news, tips, market prices and platform updates delivered to your inbox.</p>
        <form class="flex gap-3 max-w-md mx-auto" onsubmit="return subscribeNewsletter(this)">
            <input type="email" required placeholder="Enter your email address" class="flex-1 rounded-xl px-4 py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-green-300 bg-white">
            <button type="submit" class="bg-yellow-400 text-gray-900 font-bold px-5 py-3 rounded-xl hover:bg-yellow-300 transition text-sm shrink-0">Subscribe</button>
        </form>
    </div>
</section>

{{-- ═══════════════════════════════════════ FOOTER ═══════════════════════════════════════ --}}
<footer class="bg-gray-900 text-gray-400 pt-16 pb-6">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-5 gap-10 mb-12">
            <div class="md:col-span-2">
                <div class="font-heading font-extrabold text-xl text-white mb-1">MSAS Agro</div>
                <div class="text-xs text-green-400 font-semibold mb-4">Smart Agriculture, Better Tomorrow</div>
                <p class="text-sm leading-relaxed mb-5">Nigeria's leading AI-powered agribusiness platform connecting farmers, experts, governments, and development partners through digital innovation.</p>
                <div class="flex gap-3">
                    @foreach([['facebook-f','#3b82f6'],['twitter','#38bdf8'],['linkedin-in','#0077b5'],['youtube','#ef4444'],['whatsapp','#25d366']]) as [$ico,$col])
                    <a href="#" class="w-9 h-9 rounded-full bg-gray-800 flex items-center justify-center hover:scale-110 transition" style="--hc:{{ $col }}" onmouseover="this.style.background=this.style.getPropertyValue('--hc')" onmouseout="this.style.background='#1f2937'"><i class="fa-brands fa-{{ $ico }} text-gray-300 text-sm"></i></a>
                    @endforeach
                </div>
            </div>
            <div><h4 class="text-white font-bold text-sm mb-4">Quick Links</h4><ul class="space-y-2.5 text-sm">@foreach([['#home','Home'],['#about','About Us'],['#solutions','Solutions'],['#marketplace','Marketplace'],['#pricing','Pricing'],['#contact','Contact']]) as [$href,$label])<li><a href="{{ $href }}" class="hover:text-green-400 transition">{{ $label }}</a></li>@endforeach</ul></div>
            <div><h4 class="text-white font-bold text-sm mb-4">Services</h4><ul class="space-y-2.5 text-sm">@foreach([['services.livestock','Livestock Management'],['services.poultry','Poultry Management'],['services.crops','Crop Farming'],['services.finance','Finance Tools'],['services.vet','Vet Consultations'],['marketplace','Marketplace']]) as [$r,$l])<li><a href="{{ route($r) }}" class="hover:text-green-400 transition">{{ $l }}</a></li>@endforeach</ul></div>
            <div><h4 class="text-white font-bold text-sm mb-4">Contact</h4><div class="space-y-3 text-sm"><p><i class="fa-solid fa-location-dot text-green-500 mr-2"></i>Katsina State, Nigeria</p><p><a href="tel:08129582957" class="hover:text-green-400 transition"><i class="fa-solid fa-phone text-green-500 mr-2"></i>08129582957</a></p><p><a href="mailto:msaslivestockagroservices@gmail.com" class="hover:text-green-400 transition break-all"><i class="fa-solid fa-envelope text-green-500 mr-1"></i>msaslivestockagroservices<br/>@gmail.com</a></p></div></div>
        </div>
        <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs">
            <p>&copy; {{ date('Y') }} MSAS Agro. All rights reserved.</p>
            <div class="flex gap-5"><a href="#" class="hover:text-gray-200 transition">Privacy Policy</a><a href="#" class="hover:text-gray-200 transition">Terms of Service</a><a href="#" class="hover:text-gray-200 transition">Data Protection</a></div>
        </div>
    </div>
</footer>

{{-- Floating WhatsApp --}}
<a href="https://wa.me/2348129582957" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp" class="fixed bottom-6 right-6 w-14 h-14 rounded-full flex items-center justify-center text-white text-2xl shadow-xl wa-float z-50 hover:scale-110 transition" style="background:#25D366"><i class="fa-brands fa-whatsapp"></i></a>

{{-- Scripts --}}
<script>
// FAQ accordion
function toggleFaq(el) {
    var ans = el.nextElementSibling;
    var icon = el.querySelector('i');
    var isOpen = ans.classList.contains('open');
    document.querySelectorAll('.faq-a.open').forEach(function(a){ a.classList.remove('open'); a.previousElementSibling.querySelector('i').style.transform=''; });
    if (!isOpen) { ans.classList.add('open'); icon.style.transform='rotate(180deg)'; }
}

// Contact form → WhatsApp
function sendWhatsApp(form) {
    var name = form.name.value;
    var phone = form.phone.value;
    var subject = form.subject ? form.subject.value : '';
    var message = form.message.value;
    var text = 'MSAS Agro Enquiry\n\nName: ' + name + '\nPhone: ' + phone + (subject ? '\nSubject: ' + subject : '') + '\n\nMessage:\n' + message;
    window.open('https://wa.me/2348129582957?text=' + encodeURIComponent(text), '_blank');
    return false;
}

// Newsletter
function subscribeNewsletter(form) {
    var email = form.querySelector('input[type=email]').value;
    alert('Thank you for subscribing! We will be in touch at ' + email);
    form.reset();
    return false;
}

// Intersection Observer for fade-up and counters
(function(){
    // Fade-up animations
    var fadeEls = document.querySelectorAll('.fade-up');
    var fadeObs = new IntersectionObserver(function(entries){
        entries.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('visible'); fadeObs.unobserve(e.target); } });
    }, { threshold: 0.1 });
    fadeEls.forEach(function(el){ fadeObs.observe(el); });

    // Counter animation for stat numbers in hero
    var counters = document.querySelectorAll('.stat-card .font-extrabold');
    var countObs = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(e.isIntersecting){
                var el = e.target;
                var target = parseInt(el.textContent.replace(/[^0-9]/g,''));
                if(!target||el.dataset.counted) return;
                el.dataset.counted = 1;
                var suffix = el.textContent.replace(/[0-9]/g,'');
                var start = 0; var dur = 1500; var step = 16;
                var inc = target / (dur / step);
                var timer = setInterval(function(){
                    start += inc;
                    if(start >= target){ el.textContent = target + suffix; clearInterval(timer); }
                    else { el.textContent = Math.floor(start) + suffix; }
                }, step);
                countObs.unobserve(el);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(function(el){ countObs.observe(el); });
})();
</script>
</body>
</html>
