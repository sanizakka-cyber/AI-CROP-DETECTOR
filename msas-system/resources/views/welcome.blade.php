<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MSAS Livestock & Agro Services | Smart Agriculture</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800|poppins:500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tailwind CSS (compiled via Vite — custom colors defined in tailwind.config.js) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background-color:#1e3a5f; color:#ffffff; }

        /* ── Card hover ── */
        .hover-card { transition:all .3s ease; border:1px solid transparent; }
        .hover-card:hover {
            transform:translateY(-5px);
            box-shadow:0 10px 25px -5px rgba(0,0,0,.5), 0 0 15px rgba(16,185,129,.3);
            border-color:#1FA84A;
        }

        /* ── Feature badges ── */
        .feature-badge {
            display:flex; flex-direction:column; align-items:center; text-align:center;
            padding:.75rem 1rem; border-radius:.75rem; cursor:pointer;
            border:1px solid transparent;
            transition:background .25s, border-color .25s, transform .2s;
            background:transparent;
            color:inherit;
        }
        .feature-badge:hover, .feature-badge:focus-visible {
            background:rgba(16,185,129,.1);
            border-color:rgba(16,185,129,.4);
            transform:translateY(-3px);
            outline:none;
        }
        .feature-badge:focus-visible { outline:2px solid #1FA84A; outline-offset:3px; }
        .feature-badge .badge-icon {
            font-size:1.75rem; margin-bottom:.5rem; color:#34d399;
            line-height:1;
        }

        /* ── Modal ── */
        .modal-overlay {
            position:fixed; inset:0; z-index:9999;
            display:flex; align-items:center; justify-content:center;
            background:rgba(0,0,0,.75); backdrop-filter:blur(4px);
            opacity:0; pointer-events:none; transition:opacity .25s;
        }
        .modal-overlay.open { opacity:1; pointer-events:auto; }
        .modal-box {
            background:#1e293b; border:1px solid #334155; border-radius:1.25rem;
            padding:2.5rem; max-width:520px; width:90%;
            transform:translateY(20px) scale(.97); transition:transform .25s;
            position:relative;
        }
        .modal-overlay.open .modal-box { transform:translateY(0) scale(1); }
        .modal-icon { font-size:2.25rem; color:#34d399; margin-bottom:1rem; line-height:1; }

        /* ── Gallery zoom ── */
        .gallery-img { transition:transform .7s ease; }
        .gallery-tile:hover .gallery-img { transform:scale(1.1); }

        /* ── WhatsApp pulse ── */
        .whatsapp-float { animation:pulse-wa 2s infinite; }
        @keyframes pulse-wa {
            0%   { transform:scale(1);    box-shadow:0 0 0 0   rgba(37,211,102,.7); }
            70%  { transform:scale(1.05); box-shadow:0 0 0 15px rgba(37,211,102,0); }
            100% { transform:scale(1);    box-shadow:0 0 0 0   rgba(37,211,102,0); }
        }

        /* ── CEO photo ring ── */
        .ceo-photo-ring {
            padding:4px;
            background:linear-gradient(135deg,#1FA84A,#14b8a6,#059669);
            border-radius:9999px;
        }
        .ceo-photo-ring img {
            display:block; border-radius:9999px; object-fit:cover;
            border:3px solid #1e293b;
        }

        /* ── Credential pill ── */
        .cred-pill {
            display:inline-flex; align-items:center; gap:.4rem;
            background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.3);
            border-radius:9999px; padding:.3rem .85rem;
            font-size:.78rem; font-weight:600; color:#6ee7b7;
        }

        /* ── Phone mockup stat icon ── */
        .stat-icon { font-size:1.1rem; margin-bottom:.125rem; line-height:1; }

        /* ── Social icon size fallback (when Tailwind not yet loaded) ── */
        .social-icon-link { display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:9999px; }
        .social-icon-link svg { width:20px; height:20px; flex-shrink:0; }

        /* ── Print styles ── */
        @media print {
            .social-icon-link { width:24px !important; height:24px !important; }
            .social-icon-link svg { width:14px !important; height:14px !important; }
            .whatsapp-float { display:none !important; }
            .modal-overlay { display:none !important; }
        }
    </style>
</head>
<body class="antialiased font-sans">

{{-- ════════════════════════════════════════════════════════════════════════
     IMAGE SLOTS — replace these paths once you have the final photos:
     • /images/ceo-sani-yawale-zakka.jpg  ← CEO portrait (SUPPLIED ✅)
     • /images/service-livestock.jpg      ← cattle/goats being handled
     • /images/service-poultry.jpg        ← poultry house or egg collection
     • /images/service-crops.jpg          ← green crop fields
     • /images/service-vet.jpg            ← vet examining/treating an animal
     • /images/service-marketplace.jpg    ← farm market or agro-inputs
     • /images/service-finance.jpg        ← farmer using phone/calculator
     • /images/gallery-poultry.jpg        ← real poultry operation
     • /images/gallery-goats.jpg          ← goats/rams on a farm
     • /images/gallery-crops.jpg          ← healthy crop fields
     • /images/gallery-vet.jpg            ← vet care in action
     • /images/gallery-farmers.jpg        ← Northern Nigerian farmers (with consent)
     • /images/gallery-dashboard.jpg      ← screenshot of MSAS app dashboard
════════════════════════════════════════════════════════════════════════ --}}

    <!-- Top Banner -->
    <div class="bg-emerald-600 text-white text-xs md:text-sm py-2 px-4 flex justify-between items-center overflow-hidden">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-leaf" aria-hidden="true"></i>
            <span>Welcome to MSAS Livestock &amp; Agro Services</span>
        </div>
        <div class="hidden md:block font-medium">
            Empowering Farmers. Improving Livestock. Building a Better Future.
        </div>
        <div class="flex items-center gap-2">
            <select class="bg-transparent text-white text-xs outline-none cursor-pointer border-none font-medium">
                <option value="en" class="text-black">English</option>
                <option value="ha" class="text-black">Hausa</option>
            </select>
        </div>
    </div>

    <!-- ── Navigation ── -->
    <header class="sticky top-0 z-50 bg-navy/95 backdrop-blur-sm border-b border-cardlight" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">

            <!-- Logo -->
            <a href="/" class="flex items-center gap-2 group" aria-label="MSAS Home">
                <img src="{{ asset('images/msas-logo.png') }}" alt="MSAS Logo" class="h-12 w-auto shrink-0" style="filter:drop-shadow(0 2px 4px rgba(0,0,0,.5))">
            </a>

            <!-- Desktop Nav -->
            <nav class="hidden lg:flex items-center gap-5 text-sm font-medium text-gray-300" aria-label="Main navigation">
                <a href="#home"         class="text-white border-b-2 border-emerald-500 pb-0.5">Home</a>
                <a href="#about"        class="hover:text-white hover:border-b-2 hover:border-emerald-500 pb-0.5 transition">About Us</a>
                <a href="#services"     class="hover:text-white hover:border-b-2 hover:border-emerald-500 pb-0.5 transition">Services</a>
                <a href="#marketplace"  class="hover:text-white hover:border-b-2 hover:border-emerald-500 pb-0.5 transition">Marketplace</a>
                <a href="#testimonials" class="hover:text-white hover:border-b-2 hover:border-emerald-500 pb-0.5 transition">Feedback</a>
                <a href="#contact"      class="hover:text-white hover:border-b-2 hover:border-emerald-500 pb-0.5 transition">Contact Us</a>
            </nav>

            <!-- Desktop Action Buttons -->
            <div class="hidden md:flex items-center gap-2">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="px-4 py-1.5 rounded-md bg-emerald-500 text-white text-sm font-semibold hover:bg-emerald-600 transition shadow-md shadow-emerald-500/20 flex items-center gap-1.5">
                        <i class="fa-solid fa-gauge-high text-xs" aria-hidden="true"></i> Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="px-4 py-1.5 rounded-md border border-red-400 text-red-400 text-sm font-medium hover:bg-red-400/10 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-right-from-bracket text-xs" aria-hidden="true"></i> Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="px-4 py-1.5 rounded-md border border-emerald-400 text-emerald-400 text-sm font-medium hover:bg-emerald-400/10 transition min-h-[36px] flex items-center gap-1.5">
                        <i class="fa-solid fa-right-to-bracket text-xs" aria-hidden="true"></i> Sign In
                    </a>
                    <a href="{{ route('register') }}"
                       class="px-4 py-1.5 rounded-md bg-emerald-500 text-white text-sm font-semibold hover:bg-emerald-600 transition shadow-md shadow-emerald-500/20 min-h-[36px] flex items-center gap-1.5">
                        <i class="fa-solid fa-user-plus text-xs" aria-hidden="true"></i> Sign Up
                    </a>
                    <a href="{{ route('register') }}"
                       class="px-4 py-1.5 rounded-md text-white text-sm font-semibold transition min-h-[36px] flex items-center gap-1.5"
                       style="background:#F4A300;">
                        <i class="fa-solid fa-circle-check text-xs" aria-hidden="true"></i> Check In
                    </a>
                @endauth
            </div>

            <!-- Mobile Hamburger -->
            <button @click="mobileOpen = !mobileOpen"
                    class="md:hidden text-white text-xl p-2 rounded-md hover:bg-white/10 transition"
                    :aria-expanded="mobileOpen" aria-label="Toggle menu"
                    :aria-label="mobileOpen ? 'Close menu' : 'Open menu'">
                <i x-show="!mobileOpen" class="fa-solid fa-bars" aria-hidden="true"></i>
                <i x-show="mobileOpen"  class="fa-solid fa-xmark" aria-hidden="true" x-cloak></i>
            </button>
        </div>

        <!-- Mobile Dropdown Menu -->
        <div x-show="mobileOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             x-cloak
             class="md:hidden border-t border-white/10 bg-[#0B2447]/98 backdrop-blur-sm">

            <!-- Mobile Nav Links -->
            <nav class="px-4 pt-3 pb-2 flex flex-col gap-1 text-sm font-medium border-b border-white/10">
                <a href="#home"         @click="mobileOpen=false" class="py-2.5 px-3 text-white rounded-lg hover:bg-white/10 transition flex items-center gap-2"><i class="fa-solid fa-house w-4 text-emerald-400"></i> Home</a>
                <a href="#about"        @click="mobileOpen=false" class="py-2.5 px-3 text-gray-300 rounded-lg hover:bg-white/10 hover:text-white transition flex items-center gap-2"><i class="fa-solid fa-circle-info w-4 text-emerald-400"></i> About Us</a>
                <a href="#services"     @click="mobileOpen=false" class="py-2.5 px-3 text-gray-300 rounded-lg hover:bg-white/10 hover:text-white transition flex items-center gap-2"><i class="fa-solid fa-leaf w-4 text-emerald-400"></i> Services</a>
                <a href="#marketplace"  @click="mobileOpen=false" class="py-2.5 px-3 text-gray-300 rounded-lg hover:bg-white/10 hover:text-white transition flex items-center gap-2"><i class="fa-solid fa-store w-4 text-emerald-400"></i> Marketplace</a>
                <a href="#testimonials" @click="mobileOpen=false" class="py-2.5 px-3 text-gray-300 rounded-lg hover:bg-white/10 hover:text-white transition flex items-center gap-2"><i class="fa-solid fa-star w-4 text-emerald-400"></i> Feedback</a>
                <a href="#contact"      @click="mobileOpen=false" class="py-2.5 px-3 text-gray-300 rounded-lg hover:bg-white/10 hover:text-white transition flex items-center gap-2"><i class="fa-solid fa-phone w-4 text-emerald-400"></i> Contact Us</a>
            </nav>

            <!-- Mobile Auth Buttons -->
            <div class="px-4 py-4 flex flex-col gap-3">
                @auth
                    <div class="flex items-center gap-3 bg-white/5 rounded-xl p-3 mb-1">
                        <div class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                            {{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-white font-semibold text-sm truncate">{{ auth()->user()->name }}</p>
                            <p class="text-emerald-400 text-xs font-medium uppercase tracking-wide">{{ auth()->user()->role }}</p>
                        </div>
                    </div>
                    <a href="{{ url('/dashboard') }}" @click="mobileOpen=false"
                       class="w-full py-3 rounded-xl bg-emerald-500 text-white text-sm font-bold text-center hover:bg-emerald-600 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-gauge-high"></i> Go to Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full py-3 rounded-xl border border-red-400/50 text-red-400 text-sm font-semibold hover:bg-red-400/10 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" @click="mobileOpen=false"
                       class="w-full py-3 rounded-xl border border-emerald-400 text-emerald-400 text-sm font-bold text-center hover:bg-emerald-400/10 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-to-bracket"></i> Sign In
                    </a>
                    <a href="{{ route('register') }}" @click="mobileOpen=false"
                       class="w-full py-3 rounded-xl bg-emerald-500 text-white text-sm font-bold text-center hover:bg-emerald-600 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-plus"></i> Sign Up
                    </a>
                    <a href="{{ route('register') }}" @click="mobileOpen=false"
                       class="w-full py-3 rounded-xl text-white text-sm font-bold text-center hover:opacity-90 transition flex items-center justify-center gap-2"
                       style="background:#F4A300;">
                        <i class="fa-solid fa-circle-check"></i> Check In
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- ── Hero ── -->
    <section id="home" class="relative pt-20 pb-24 overflow-hidden">
        <div class="absolute top-20 left-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-[100px] -z-10"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-teal/10 rounded-full blur-[100px] -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-12 gap-12 items-center relative z-10">
            <!-- Left (60%) -->
            <div class="lg:col-span-7">
                <h1 class="font-heading text-4xl md:text-[62px] font-extrabold text-white leading-[1.2] mb-6">
                    Welcome to<br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal">MSAS Livestock</span><br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal">&amp; Agro Services</span>
                </h1>
                <p class="text-graytext text-lg leading-[1.7] max-w-[600px] mb-8">
                    A smart digital platform for livestock farmers, poultry owners, and agribusiness operators. Manage your animals, access expert veterinary support, buy &amp; sell inputs, and grow your farm business with confidence.
                </p>

                <a href="#services" class="inline-flex items-center gap-2 px-8 py-3 bg-transparent border border-white/20 text-white font-medium rounded-full hover:bg-white/10 transition">
                    Learn More <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                </a>

                <!-- ── Feature Badges (interactive) ── -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-14" role="list">

                    <button type="button" class="feature-badge" data-modal="modal-smart"
                            aria-haspopup="dialog" aria-controls="modal-smart" role="listitem">
                        <i class="fa-solid fa-mobile-screen badge-icon" aria-hidden="true"></i>
                        <span class="text-sm font-medium text-gray-300">Smart Management</span>
                    </button>

                    <button type="button" class="feature-badge" data-modal="modal-expert"
                            aria-haspopup="dialog" aria-controls="modal-expert" role="listitem">
                        <i class="fa-solid fa-stethoscope badge-icon" aria-hidden="true"></i>
                        <span class="text-sm font-medium text-gray-300">Expert Support</span>
                    </button>

                    <button type="button" class="feature-badge" data-modal="modal-ai"
                            aria-haspopup="dialog" aria-controls="modal-ai" role="listitem">
                        <i class="fa-solid fa-robot badge-icon" aria-hidden="true"></i>
                        <span class="text-sm font-medium text-gray-300">AI Powered</span>
                    </button>

                    <button type="button" class="feature-badge" data-modal="modal-secure"
                            aria-haspopup="dialog" aria-controls="modal-secure" role="listitem">
                        <i class="fa-solid fa-shield-halved badge-icon" aria-hidden="true"></i>
                        <span class="text-sm font-medium text-gray-300">Secure &amp; Reliable</span>
                    </button>
                </div>
            </div>

            <!-- Right — phone mockup -->
            <div class="lg:col-span-5 relative flex flex-col items-center">
                <div class="relative w-[280px] h-[560px] bg-black rounded-[3rem] border-[10px] border-carddark shadow-[0_20px_50px_rgba(16,185,129,0.2)] overflow-hidden transform hover:-translate-y-2 transition duration-500">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-36 h-6 bg-carddark rounded-b-3xl z-20"></div>
                    <div class="bg-emerald-500 text-white px-5 pt-10 pb-6 relative z-10">
                        <h3 class="font-heading font-bold text-xl">Dashboard</h3>
                        <p class="text-xs opacity-90 mt-1 flex items-center gap-1">
                            Welcome back, Farmer
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                        </p>
                    </div>
                    <div class="p-4 bg-slate-50 h-full rounded-t-3xl -mt-4 relative z-20">
                        <div class="grid grid-cols-2 gap-2 mb-4">
                            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100">
                                <div class="stat-icon text-emerald-600"><i class="fa-solid fa-cow" aria-hidden="true"></i></div>
                                <div class="text-xs text-gray-500 font-medium">Cattle</div>
                                <div class="font-bold text-navy text-lg">120</div>
                            </div>
                            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100">
                                <div class="stat-icon text-amber-500"><i class="fa-solid fa-egg" aria-hidden="true"></i></div>
                                <div class="text-xs text-gray-500 font-medium">Poultry</div>
                                <div class="font-bold text-navy text-lg">350</div>
                            </div>
                            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100">
                                <div class="stat-icon text-green-600"><i class="fa-solid fa-seedling" aria-hidden="true"></i></div>
                                <div class="text-xs text-gray-500 font-medium">Crops</div>
                                <div class="font-bold text-navy text-lg">5</div>
                            </div>
                            <div class="bg-red-50 p-3 rounded-2xl shadow-sm border border-red-100">
                                <div class="stat-icon text-red-500"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div>
                                <div class="text-xs text-red-500 font-medium">Alerts</div>
                                <div class="font-bold text-red-700 text-lg">3</div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
                                <div class="bg-emerald-100 w-9 h-9 rounded-xl flex items-center justify-center text-emerald-600">
                                    <i class="fa-solid fa-stethoscope" aria-hidden="true"></i>
                                </div>
                                <span class="text-sm font-semibold text-navy">Health Check</span>
                            </div>
                            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
                                <div class="bg-teal/10 w-9 h-9 rounded-xl flex items-center justify-center text-teal">
                                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                                </div>
                                <span class="text-sm font-semibold text-navy">Vet Consultation</span>
                            </div>
                            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
                                <div class="bg-amber/10 w-9 h-9 rounded-xl flex items-center justify-center text-amber">
                                    <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                                </div>
                                <span class="text-sm font-semibold text-navy">Marketplace</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── About / Company Stats ── -->
    <section id="about" class="py-16 bg-carddark border-b border-cardlight">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-navy p-5 rounded-2xl hover-card border border-cardlight shadow-lg text-center">
                    <div class="text-[40px] font-heading font-extrabold text-emerald-500 mb-0.5 leading-none">500+</div>
                    <div class="text-graytext text-sm font-medium">Farmers Served</div>
                </div>
                <div class="bg-navy p-5 rounded-2xl hover-card border border-cardlight shadow-lg text-center">
                    <div class="text-[40px] font-heading font-extrabold text-emerald-500 mb-0.5 leading-none">2k+</div>
                    <div class="text-graytext text-sm font-medium">Livestock Managed</div>
                </div>
                <div class="bg-navy p-5 rounded-2xl hover-card border border-cardlight shadow-lg text-center">
                    <div class="text-[40px] font-heading font-extrabold text-emerald-500 mb-0.5 leading-none">50+</div>
                    <div class="text-graytext text-sm font-medium">Partner Vets</div>
                </div>
                <div class="bg-navy p-5 rounded-2xl hover-card border border-cardlight shadow-lg text-center">
                    <div class="text-[40px] font-heading font-extrabold text-emerald-500 mb-0.5 leading-none">24/7</div>
                    <div class="text-graytext text-sm font-medium">Expert Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Services ── -->
    <section id="services" class="py-24 bg-navy">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-14">
                <h2 class="font-heading text-[38px] font-bold text-white mb-3">Our Services</h2>
                <p class="text-graytext text-lg">Everything you need to run a successful farm business</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- IMAGE SLOT: /images/service-livestock.jpg --}}
                <div class="bg-carddark rounded-2xl overflow-hidden hover-card border border-cardlight group">
                    <div class="relative overflow-hidden h-48">
                        <img src="{{ file_exists(public_path('images/service-livestock.jpg')) ? asset('images/service-livestock.jpg') : 'https://images.unsplash.com/photo-1545468800-85cc9bc6ecf7?w=800&q=80&auto=format&fit=crop' }}"
                             alt="Farmer recording livestock data on a cattle farm" loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-7">
                        <h3 class="font-heading font-bold text-xl text-white mb-2">Livestock Management</h3>
                        <p class="text-graytext text-[15px] mb-5">Record animals, track health, breeding, weight, and more.</p>
                        <a href="{{ route('services.livestock') }}" class="text-emerald-400 font-medium hover:underline flex items-center gap-1.5 text-sm">
                            Learn More <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/service-poultry.jpg --}}
                <div class="bg-carddark rounded-2xl overflow-hidden hover-card border border-cardlight group">
                    <div class="relative overflow-hidden h-48">
                        <img src="{{ file_exists(public_path('images/service-poultry.jpg')) ? asset('images/service-poultry.jpg') : 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800&q=80&auto=format&fit=crop' }}"
                             alt="Poultry house with chickens and egg collection" loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-7">
                        <h3 class="font-heading font-bold text-xl text-white mb-2">Poultry Management</h3>
                        <p class="text-graytext text-[15px] mb-5">Manage poultry, egg production, feed, sales and performance.</p>
                        <a href="{{ route('services.poultry') }}" class="text-emerald-400 font-medium hover:underline flex items-center gap-1.5 text-sm">
                            Learn More <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/service-crops.jpg --}}
                <div class="bg-carddark rounded-2xl overflow-hidden hover-card border border-cardlight group">
                    <div class="relative overflow-hidden h-48">
                        <img src="{{ file_exists(public_path('images/service-crops.jpg')) ? asset('images/service-crops.jpg') : 'https://images.unsplash.com/photo-1586771107445-d3ca888129ff?w=800&q=80&auto=format&fit=crop' }}"
                             alt="Green maize and sorghum crop fields" loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-7">
                        <h3 class="font-heading font-bold text-xl text-white mb-2">Crop Farming</h3>
                        <p class="text-graytext text-[15px] mb-5">Plan, monitor and record your crops for better yield.</p>
                        <a href="{{ route('services.crops') }}" class="text-emerald-400 font-medium hover:underline flex items-center gap-1.5 text-sm">
                            Learn More <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/service-vet.jpg --}}
                <div class="bg-carddark rounded-2xl overflow-hidden hover-card border border-cardlight group">
                    <div class="relative overflow-hidden h-48">
                        <img src="{{ file_exists(public_path('images/service-vet.jpg')) ? asset('images/service-vet.jpg') : 'https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?w=800&q=80&auto=format&fit=crop' }}"
                             alt="Veterinarian examining and treating a farm animal" loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-7">
                        <h3 class="font-heading font-bold text-xl text-white mb-2">Veterinary Services</h3>
                        <p class="text-graytext text-[15px] mb-5">Consult with experts, check symptoms and get advice.</p>
                        <a href="{{ route('services.vet') }}" class="text-emerald-400 font-medium hover:underline flex items-center gap-1.5 text-sm">
                            Learn More <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/service-marketplace.jpg --}}
                <div class="bg-carddark rounded-2xl overflow-hidden hover-card border border-cardlight group">
                    <div class="relative overflow-hidden h-48">
                        <img src="{{ file_exists(public_path('images/service-marketplace.jpg')) ? asset('images/service-marketplace.jpg') : 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&q=80&auto=format&fit=crop' }}"
                             alt="Agricultural market with farm inputs and livestock feed bags" loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-7">
                        <h3 class="font-heading font-bold text-xl text-white mb-2">Marketplace</h3>
                        <p class="text-graytext text-[15px] mb-5">Buy &amp; sell livestock, farm inputs and agricultural products.</p>
                        <a href="{{ route('marketplace') }}" class="text-emerald-400 font-medium hover:underline flex items-center gap-1.5 text-sm">
                            Learn More <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/service-finance.jpg --}}
                <div class="bg-carddark rounded-2xl overflow-hidden hover-card border border-cardlight group">
                    <div class="relative overflow-hidden h-48">
                        <img src="{{ file_exists(public_path('images/service-finance.jpg')) ? asset('images/service-finance.jpg') : 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800&q=80&auto=format&fit=crop' }}"
                             alt="Farmer using a smartphone for financial record-keeping" loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-7">
                        <h3 class="font-heading font-bold text-xl text-white mb-2">Finance Tools</h3>
                        <p class="text-graytext text-[15px] mb-5">Track income, expenses and profits easily.</p>
                        <a href="{{ route('services.finance') }}" class="text-emerald-400 font-medium hover:underline flex items-center gap-1.5 text-sm">
                            Learn More <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ── Gallery ── -->
    <section class="py-24 bg-carddark border-y border-cardlight/50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-14">
                <h2 class="font-heading text-[38px] font-bold text-white mb-3">Agriculture In Action</h2>
                <p class="text-graytext text-lg">Real farms. Real people. Real impact.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

                {{-- IMAGE SLOT: /images/gallery-poultry.jpg --}}
                <div class="gallery-tile relative rounded-[18px] overflow-hidden group aspect-[4/3]">
                    <img src="{{ file_exists(public_path('images/gallery-poultry.jpg')) ? asset('images/gallery-poultry.jpg') : 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=800&q=80&auto=format&fit=crop' }}"
                         alt="A real poultry farm operation" loading="lazy" class="gallery-img w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex items-end p-7">
                        <h3 class="text-white font-bold text-2xl">Poultry Farm</h3>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/gallery-goats.jpg --}}
                <div class="gallery-tile relative rounded-[18px] overflow-hidden group aspect-[4/3] lg:translate-y-8">
                    <img src="{{ file_exists(public_path('images/gallery-goats.jpg')) ? asset('images/gallery-goats.jpg') : 'https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?w=800&q=80&auto=format&fit=crop' }}"
                         alt="Goats and rams grazing on a Northern Nigerian farm" loading="lazy" class="gallery-img w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex items-end p-7">
                        <h3 class="text-white font-bold text-2xl">Goats &amp; Rams</h3>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/gallery-crops.jpg --}}
                <div class="gallery-tile relative rounded-[18px] overflow-hidden group aspect-[4/3]">
                    <img src="{{ file_exists(public_path('images/gallery-crops.jpg')) ? asset('images/gallery-crops.jpg') : 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800&q=80&auto=format&fit=crop' }}"
                         alt="Healthy green maize and sorghum crop fields" loading="lazy" class="gallery-img w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex items-end p-7">
                        <h3 class="text-white font-bold text-2xl">Green Crops</h3>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/gallery-vet.jpg --}}
                <div class="gallery-tile relative rounded-[18px] overflow-hidden group aspect-[4/3]">
                    <img src="{{ file_exists(public_path('images/gallery-vet.jpg')) ? asset('images/gallery-vet.jpg') : 'https://images.unsplash.com/photo-1582560475093-ba66accbc424?w=800&q=80&auto=format&fit=crop' }}"
                         alt="Veterinarian providing animal health care on a farm" loading="lazy" class="gallery-img w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex items-end p-7">
                        <h3 class="text-white font-bold text-2xl">Vet Services</h3>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/gallery-farmers.jpg — Northern Nigerian farmers (with consent) --}}
                <div class="gallery-tile relative rounded-[18px] overflow-hidden group aspect-[4/3] lg:translate-y-8">
                    <img src="{{ file_exists(public_path('images/gallery-farmers.jpg')) ? asset('images/gallery-farmers.jpg') : 'https://images.unsplash.com/photo-1605000797499-95a51c5269ae?w=800&q=80&auto=format&fit=crop' }}"
                         alt="Happy Northern Nigerian farmers using MSAS on their smartphones" loading="lazy" class="gallery-img w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex items-end p-7">
                        <h3 class="text-white font-bold text-2xl">Happy Farmers</h3>
                    </div>
                </div>

                {{-- IMAGE SLOT: /images/gallery-dashboard.jpg — actual MSAS app screenshot --}}
                <div class="gallery-tile relative rounded-[18px] overflow-hidden group aspect-[4/3]">
                    <img src="{{ file_exists(public_path('images/gallery-dashboard.jpg')) ? asset('images/gallery-dashboard.jpg') : 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&q=80&auto=format&fit=crop' }}"
                         alt="MSAS app dashboard showing farm analytics and livestock records" loading="lazy" class="gallery-img w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex items-end p-7">
                        <h3 class="text-white font-bold text-2xl">Dashboard Analytics</h3>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ── Testimonials ── -->
    <section id="testimonials" class="py-24 bg-navy">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="font-heading text-[38px] font-bold text-white text-center mb-14">What Our Farmers Say</h2>

            <div class="grid lg:grid-cols-4 gap-8">
                <div class="lg:col-span-3 grid md:grid-cols-3 gap-6">

                    <div class="bg-carddark p-8 rounded-2xl shadow-lg flex flex-col justify-between border border-cardlight hover-card">
                        <div>
                            <div class="flex gap-0.5 text-amber mb-4" aria-label="5 stars">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                            </div>
                            <p class="text-graytext text-[15px] italic mb-6">"MSAS platform has improved how I manage my poultry farm. The health alerts and expert advice are just amazing!"</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-cardlight text-graytext rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-user" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-[15px]">Amina Hassan</h4>
                                <p class="text-graytext text-[13px]">Poultry Farmer, Katsina</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-carddark p-8 rounded-2xl shadow-lg flex flex-col justify-between border border-cardlight hover-card">
                        <div>
                            <div class="flex gap-0.5 text-amber mb-4" aria-label="5 stars">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                            </div>
                            <p class="text-graytext text-[15px] italic mb-6">"I can now record my animals, track expenses and even sell my goats online. Very helpful platform!"</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-cardlight text-graytext rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-user" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-[15px]">Kabiru Usman</h4>
                                <p class="text-graytext text-[13px]">Livestock Farmer, Kano</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-carddark p-8 rounded-2xl shadow-lg flex flex-col justify-between border border-cardlight hover-card">
                        <div>
                            <div class="flex gap-0.5 text-amber mb-4" aria-label="5 stars">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                            </div>
                            <p class="text-graytext text-[15px] italic mb-6">"The veterinary support is fast and reliable. I recommend MSAS to every farmer."</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 bg-cardlight text-graytext rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-user" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-[15px]">Fatima Bello</h4>
                                <p class="text-graytext text-[13px]">Crop Farmer, Katsina</p>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="bg-emerald-500 p-8 rounded-2xl shadow-xl text-white relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                    <h3 class="font-heading text-[22px] font-bold mb-2 relative z-10">Share Your Feedback</h3>
                    <p class="text-emerald-100 text-[15px] mb-5 relative z-10">Help us serve you better</p>
                    <textarea class="w-full bg-emerald-600/60 border border-emerald-400 rounded-lg p-4 text-white placeholder-emerald-200 mb-5 focus:outline-none focus:ring-2 focus:ring-white/50 resize-none h-28 relative z-10" placeholder="Write your feedback..."></textarea>
                    <button class="w-full bg-white text-emerald-600 font-bold py-3 rounded-lg hover:bg-gray-50 transition shadow-lg relative z-10 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Give Feedback
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ── CEO & Contact ── -->
    <section id="contact" class="py-24 bg-carddark">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid lg:grid-cols-2 gap-16 items-start">

                <!-- CEO Profile -->
                <div>
                    <span class="cred-pill mb-4 inline-flex">
                        <i class="fa-solid fa-crown" aria-hidden="true"></i> Founder &amp; CEO
                    </span>
                    <h2 class="font-heading text-3xl font-bold text-white mb-8 leading-snug">
                        Meet the Visionary<br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal">
                            Behind MSAS
                        </span>
                    </h2>
                    <div class="flex flex-col sm:flex-row gap-8 items-start">
                        <div class="shrink-0 ceo-photo-ring">
                            <img
                                src="{{ asset('images/ceo-sani-yawale-zakka.jpg') }}"
                                alt="Sani Yawale Zakka — Founder & CEO of MSAS Livestock & Agro Services"
                                width="168" height="168" loading="lazy"
                                class="w-[168px] h-[168px]"
                                onerror="this.src='https://ui-avatars.com/api/?name=Sani+Yawale+Zakka&background=10b981&color=fff&size=200&rounded=true'"
                            >
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-heading text-2xl font-bold text-white mb-0.5">Sani Yawale Zakka</h3>
                            <p class="text-emerald-400 font-semibold text-sm mb-1">Founder &amp; Chief Executive Officer</p>
                            <p class="text-graytext text-xs mb-4">MSAS Livestock &amp; Agro Services · Katsina, Nigeria</p>
                            <div class="flex flex-wrap gap-2 mb-5">
                                <span class="cred-pill"><i class="fa-solid fa-wheat-awn" aria-hidden="true"></i> Agribusiness</span>
                                <span class="cred-pill"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> Digital Innovation</span>
                                <span class="cred-pill"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Entrepreneur</span>
                            </div>
                            <p class="text-graytext text-[15px] leading-relaxed mb-6">
                                Sani Yawale Zakka is a visionary entrepreneur passionate about agriculture, livestock development and digital innovation. He founded MSAS to transform traditional farming into a profitable, efficient and technology-driven industry that benefits farmers, communities and the wider economy.
                            </p>
                            <div class="flex flex-col gap-3 text-sm">
                                <a href="tel:08032459879" class="flex items-center gap-3 text-gray-300 hover:text-emerald-400 transition group">
                                    <span class="w-8 h-8 rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 group-hover:bg-emerald-500/25 transition">
                                        <i class="fa-solid fa-phone text-xs" aria-hidden="true"></i>
                                    </span>
                                    08032459879
                                </a>
                                <a href="mailto:sanizakka@gmail.com" class="flex items-center gap-3 text-gray-300 hover:text-emerald-400 transition group">
                                    <span class="w-8 h-8 rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 group-hover:bg-emerald-500/25 transition">
                                        <i class="fa-solid fa-envelope text-xs" aria-hidden="true"></i>
                                    </span>
                                    sanizakka@gmail.com
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="space-y-6">
                    <div>
                        <h2 class="font-heading text-[30px] font-bold text-white mb-2">Contact Us</h2>
                        <p class="text-graytext text-[15px] mb-6">We are always here to help you grow</p>
                        <div class="space-y-4 mb-6">
                            <div class="flex items-start gap-4">
                                <i class="fa-solid fa-envelope text-emerald-500 text-lg mt-0.5 w-5 shrink-0" aria-hidden="true"></i>
                                <a href="mailto:msaslivestockagroservices@gmail.com" class="text-gray-300 hover:text-emerald-400 transition break-all">msaslivestockagroservices@gmail.com</a>
                            </div>
                            <div class="flex items-start gap-4">
                                <i class="fa-solid fa-phone text-emerald-500 text-lg mt-0.5 w-5 shrink-0" aria-hidden="true"></i>
                                <a href="tel:08129582957" class="text-gray-300 hover:text-emerald-400 transition">08129582957</a>
                            </div>
                            <div class="flex items-start gap-4">
                                <i class="fa-solid fa-location-dot text-emerald-500 text-lg mt-0.5 w-5 shrink-0" aria-hidden="true"></i>
                                <span class="text-gray-300 leading-relaxed">No 21 Sarkin maska street dutsin safe lowcost Katsina State, Nigeria</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="tel:08129582957" class="px-6 py-3 rounded-lg border border-emerald-500 text-emerald-400 font-bold hover:bg-emerald-500/10 transition flex items-center justify-center gap-2">
                                <i class="fa-solid fa-phone" aria-hidden="true"></i> Call Now
                            </a>
                            <a href="https://wa.me/2348129582957" class="px-6 py-3 rounded-lg bg-emerald-500 text-white font-bold hover:bg-emerald-600 transition flex items-center justify-center gap-2">
                                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp Us
                            </a>
                        </div>
                    </div>

                    <div class="bg-navy p-6 rounded-2xl border border-cardlight shadow-lg flex items-center gap-5">
                        <div class="w-12 h-12 bg-cardlight rounded-full flex items-center justify-center text-emerald-500 text-xl shrink-0">
                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3 class="font-heading text-lg font-bold text-white mb-1">Visit Our Office</h3>
                            <p class="text-graytext text-sm">MSAS Livestock &amp; Agro Services · Katsina State, Nigeria</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ── Footer ── -->
    <footer class="bg-[#0f172a] pt-20">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-12 mb-14">

                <div>
                    <a href="/" class="inline-flex mb-6" aria-label="MSAS Home">
                        <img src="{{ asset('images/msas-logo.png') }}" alt="MSAS Logo" class="h-14 w-auto" style="filter:drop-shadow(0 2px 4px rgba(0,0,0,.5))">
                    </a>
                    <div class="text-amber font-bold mb-2 text-sm">Livestock &amp; Agro Services</div>
                    <p class="text-graytext text-sm mb-6">Smart Agriculture · Healthy Livestock · Better Future</p>
                    <div class="flex gap-3">
                        <a href="#" aria-label="Facebook" class="social-icon-link w-10 h-10 rounded-full bg-cardlight flex items-center justify-center text-gray-400 hover:bg-[#3b82f6] hover:text-white transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.325v21.351C0 23.403.597 24 1.325 24H12.82v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116c.73 0 1.323-.597 1.323-1.324V1.325C24 .597 23.403 0 22.675 0z"/></svg>
                        </a>
                        <a href="#" aria-label="Instagram" class="social-icon-link w-10 h-10 rounded-full bg-cardlight flex items-center justify-center text-gray-400 hover:text-white transition hover:bg-[#E1306C]">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        <a href="#" aria-label="LinkedIn" class="social-icon-link w-10 h-10 rounded-full bg-cardlight flex items-center justify-center text-gray-400 hover:bg-[#0077b5] hover:text-white transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-white font-bold text-lg mb-5 font-heading">Quick Links</h4>
                    <ul class="space-y-3 text-graytext text-sm">
                        <li><a href="#home"         class="hover:text-emerald-400 transition">Home</a></li>
                        <li><a href="#about"        class="hover:text-emerald-400 transition">About Us</a></li>
                        <li><a href="#services"     class="hover:text-emerald-400 transition">Services</a></li>
                        <li><a href="#marketplace"  class="hover:text-emerald-400 transition">Marketplace</a></li>
                        <li><a href="#contact"      class="hover:text-emerald-400 transition">Contact Us</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold text-lg mb-5 font-heading">Services</h4>
                    <ul class="space-y-3 text-graytext text-sm">
                        <li><a href="{{ route('services.livestock') }}" class="hover:text-emerald-400 transition">Livestock Management</a></li>
                        <li><a href="{{ route('services.poultry') }}"  class="hover:text-emerald-400 transition">Poultry Management</a></li>
                        <li><a href="{{ route('services.crops') }}"    class="hover:text-emerald-400 transition">Crop Farming</a></li>
                        <li><a href="{{ route('services.finance') }}"  class="hover:text-emerald-400 transition">Finance Tools</a></li>
                        <li><a href="{{ route('services.vet') }}"      class="hover:text-emerald-400 transition">Partner Vets</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold text-lg mb-5 font-heading">Reach Us</h4>
                    <div class="space-y-3 text-graytext text-sm">
                        <p class="flex items-center gap-2">
                            <i class="fa-solid fa-location-dot text-emerald-500 w-4 shrink-0" aria-hidden="true"></i>
                            Katsina State, Nigeria
                        </p>
                        <p class="flex items-center gap-2">
                            <i class="fa-solid fa-phone text-emerald-500 w-4 shrink-0" aria-hidden="true"></i>
                            <a href="tel:08129582957" class="hover:text-emerald-400 transition">08129582957</a>
                        </p>
                        <p class="flex items-start gap-2">
                            <i class="fa-solid fa-envelope text-emerald-500 w-4 shrink-0 mt-0.5" aria-hidden="true"></i>
                            <a href="mailto:msaslivestockagroservices@gmail.com" class="hover:text-emerald-400 transition break-all">msaslivestockagroservices@gmail.com</a>
                        </p>
                    </div>
                </div>

            </div>

            <div class="border-t border-cardlight py-8 text-center md:flex justify-between items-center text-sm text-gray-500">
                <p>&copy; {{ date('Y') }} MSAS Livestock &amp; Agro Services. All rights reserved.</p>
                <div class="flex gap-6 mt-4 md:mt-0 justify-center">
                    <a href="#" class="hover:text-gray-300 transition">Privacy Policy</a>
                    <a href="#" class="hover:text-gray-300 transition">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ── Floating WhatsApp ── -->
    <a href="https://wa.me/2348129582957" target="_blank" rel="noopener noreferrer"
       class="fixed bottom-8 right-8 bg-[#25d366] text-white py-4 px-6 rounded-full font-bold flex items-center gap-3 shadow-[0_10px_25px_rgba(37,211,102,0.5)] whatsapp-float z-50 hover:scale-105 transition"
       aria-label="Chat with MSAS on WhatsApp">
        <i class="fa-brands fa-whatsapp text-2xl" aria-hidden="true"></i>
        <span class="hidden sm:inline">Chat on WhatsApp</span>
    </a>

    <!-- ══ Modals ══════════════════════════════════════════════════════════ -->

    <!-- Modal: Smart Management -->
    <div id="modal-smart" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-smart-title">
        <div class="modal-box">
            <button type="button" class="modal-close absolute top-4 right-4 text-gray-400 hover:text-white" aria-label="Close">
                <i class="fa-solid fa-xmark text-xl" aria-hidden="true"></i>
            </button>
            <i class="fa-solid fa-mobile-screen modal-icon" aria-hidden="true"></i>
            <h2 id="modal-smart-title" class="font-heading text-2xl font-bold text-white mb-3">Smart Farm Management</h2>
            <p class="text-graytext text-[15px] leading-relaxed mb-5">
                MSAS gives every farmer a complete digital back-office for their farm — no paper, no guesswork.
            </p>
            <ul class="space-y-3 text-graytext text-sm mb-6">
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Register and track all animals (cattle, goats, sheep, poultry)</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Digital health records: vaccinations, weight, treatments, breeding</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Crop records: planting dates, growth stages, harvest yield</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Income &amp; expense ledger — know your profit at a glance</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Works in English and Hausa on any smartphone</li>
            </ul>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-emerald-500 text-white font-semibold hover:bg-emerald-600 transition">
                Get Started Free <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <!-- Modal: Expert Support -->
    <div id="modal-expert" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-expert-title">
        <div class="modal-box">
            <button type="button" class="modal-close absolute top-4 right-4 text-gray-400 hover:text-white" aria-label="Close">
                <i class="fa-solid fa-xmark text-xl" aria-hidden="true"></i>
            </button>
            <i class="fa-solid fa-stethoscope modal-icon" aria-hidden="true"></i>
            <h2 id="modal-expert-title" class="font-heading text-2xl font-bold text-white mb-3">Expert Veterinary &amp; Agro Support</h2>
            <p class="text-graytext text-[15px] leading-relaxed mb-5">
                Get access to a network of 50+ verified veterinarians and agronomists — directly from your phone.
            </p>
            <ul class="space-y-3 text-graytext text-sm mb-6">
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Chat, voice, or video consultation at your convenience</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Vets respond within 2–4 hours; emergency cases prioritised</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Receive digital prescriptions and treatment plans</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Agronomists advise on crop diseases, soil, and inputs</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Rate your expert after every session</li>
            </ul>
            <p class="text-graytext text-xs mb-4">To book: sign up &rarr; run a scan &rarr; tap "Consult Expert" on your diagnosis result.</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-emerald-500 text-white font-semibold hover:bg-emerald-600 transition">
                Book a Consultation <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <!-- Modal: AI Powered -->
    <div id="modal-ai" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-ai-title">
        <div class="modal-box">
            <button type="button" class="modal-close absolute top-4 right-4 text-gray-400 hover:text-white" aria-label="Close">
                <i class="fa-solid fa-xmark text-xl" aria-hidden="true"></i>
            </button>
            <i class="fa-solid fa-robot modal-icon" aria-hidden="true"></i>
            <h2 id="modal-ai-title" class="font-heading text-2xl font-bold text-white mb-3">AI Diagnostic Scanning</h2>
            <p class="text-graytext text-[15px] leading-relaxed mb-5">
                Point your camera at a sick animal or diseased plant — MSAS AI gives you a diagnosis in seconds.
            </p>
            <ul class="space-y-3 text-graytext text-sm mb-6">
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Supports 10+ crops: maize, tomato, cassava, rice, yam and more</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Livestock: cattle, goats, sheep, poultry — visual &amp; stool analysis</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Returns disease name, confidence score (0–100%), and severity</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Instant treatment plan: organic remedies + chemical options</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Low-confidence cases are automatically escalated to a human expert</li>
            </ul>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-emerald-500 text-white font-semibold hover:bg-emerald-600 transition">
                Try a Free Scan <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <!-- Modal: Secure & Reliable -->
    <div id="modal-secure" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-secure-title">
        <div class="modal-box">
            <button type="button" class="modal-close absolute top-4 right-4 text-gray-400 hover:text-white" aria-label="Close">
                <i class="fa-solid fa-xmark text-xl" aria-hidden="true"></i>
            </button>
            <i class="fa-solid fa-shield-halved modal-icon" aria-hidden="true"></i>
            <h2 id="modal-secure-title" class="font-heading text-2xl font-bold text-white mb-3">Secure &amp; Reliable Platform</h2>
            <p class="text-graytext text-[15px] leading-relaxed mb-5">
                Your farm data and personal information are protected by industry-standard security measures.
            </p>
            <ul class="space-y-3 text-graytext text-sm mb-6">
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> NDPR compliant — Nigeria Data Protection Regulation</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Data encrypted in transit (TLS) and at rest (AES-256)</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Offline mode — works without internet, syncs when reconnected</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> 24/7 uptime monitoring with automatic failover</li>
                <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-400 shrink-0 mt-0.5" aria-hidden="true"></i> Your data is never sold to third parties</li>
            </ul>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-emerald-500 text-white font-semibold hover:bg-emerald-600 transition">
                Create Your Account <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <!-- ── Modal JS ── -->
    <script>
    (function () {
        document.querySelectorAll('[data-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () { openModal(btn.getAttribute('data-modal')); });
            btn.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(btn.getAttribute('data-modal')); }
            });
        });
        document.querySelectorAll('.modal-close').forEach(function (btn) {
            btn.addEventListener('click', closeAll);
        });
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) { if (e.target === overlay) closeAll(); });
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });

        function openModal(id) {
            var overlay = document.getElementById(id);
            if (!overlay) return;
            overlay.classList.add('open');
            var focusable = overlay.querySelector('button, a, [tabindex]:not([tabindex="-1"])');
            if (focusable) focusable.focus();
        }
        function closeAll() {
            document.querySelectorAll('.modal-overlay.open').forEach(function (el) { el.classList.remove('open'); });
        }
    })();
    </script>

</body>
</html>
