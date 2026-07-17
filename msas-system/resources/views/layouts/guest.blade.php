<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MSAS — Livestock & Agro Services</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900|poppins:600,700,800,900&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; margin: 0; min-height: 100vh; background: #f8fafc; overflow-x: hidden; }
        h1,h2,h3,h4 { font-family: 'Poppins', sans-serif; }

        /* ── Hero panel gradient ─────────────── */
        .hero-bg {
            background: linear-gradient(160deg, #051530 0%, #0B2447 30%, #0d4a2e 62%, #0F6B3E 100%);
            background-size: 300% 300%;
            animation: heroShift 16s ease infinite;
        }
        @keyframes heroShift {
            0%   { background-position: 0% 0%; }
            50%  { background-position: 100% 100%; }
            100% { background-position: 0% 0%; }
        }

        /* ── Grid texture ────────────────────── */
        .grid-overlay {
            position: absolute; inset: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        /* ── Glow orbs ───────────────────────── */
        .orb { position: absolute; border-radius: 50%; filter: blur(88px); pointer-events: none; }
        .orb-1 { width: 400px; height: 400px; background: rgba(31,168,74,0.22); top: -80px; left: -60px; animation: orbFloat 14s ease-in-out infinite; }
        .orb-2 { width: 280px; height: 280px; background: rgba(45,156,219,0.18); bottom: -60px; right: 10px; animation: orbFloat 18s ease-in-out infinite reverse; }
        .orb-3 { width: 180px; height: 180px; background: rgba(244,163,0,0.14); top: 45%; right: 5%; animation: orbFloat 11s ease-in-out infinite 3s; }
        @keyframes orbFloat {
            0%,100% { transform: translate(0,0); }
            33%  { transform: translate(18px,-28px); }
            66%  { transform: translate(-14px,18px); }
        }

        /* ── Feature cards ───────────────────── */
        .feat-card {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px; padding: 16px;
            transition: all 0.3s;
        }
        .feat-card:hover { background: rgba(255,255,255,0.11); transform: translateY(-3px); }

        /* ── Stat pills ──────────────────────── */
        .stat-pill { border-radius: 14px; padding: 14px 10px; text-align: center; }

        /* ── Form inputs ─────────────────────── */
        .form-input {
            width: 100%; padding: 12px 14px;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 14px; font-family: 'Inter', sans-serif;
            background: #f8fafc; color: #1e293b;
            transition: all 0.2s; outline: none;
        }
        .form-input:focus { border-color: #0F6B3E; background: #fff; box-shadow: 0 0 0 3px rgba(15,107,62,0.1); }

        /* ── Submit button ───────────────────── */
        .btn-primary {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #0F6B3E 0%, #1FA84A 100%);
            color: #fff; font-weight: 700; font-size: 15px;
            border: none; border-radius: 12px; cursor: pointer;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 20px rgba(15,107,62,0.32);
            transition: all 0.25s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(15,107,62,0.42); filter: brightness(1.07); }
        .btn-primary:active { transform: translateY(0); }

        /* ── Fade animations ─────────────────── */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up-1 { animation: fadeUp 0.5s ease 0.08s both; }
        .fade-up-2 { animation: fadeUp 0.5s ease 0.16s both; }
        .fade-up-3 { animation: fadeUp 0.5s ease 0.24s both; }
        .fade-up-4 { animation: fadeUp 0.5s ease 0.32s both; }
        .fade-up-5 { animation: fadeUp 0.5s ease 0.40s both; }

        /* ── Logo image container ────────────── */
        .logo-img-hero {
            width: 130px; height: 130px;
            border-radius: 20px; overflow: hidden;
            border: 2px solid rgba(255,255,255,0.15);
            box-shadow: 0 10px 36px rgba(0,0,0,0.4);
        }
        .logo-img-form {
            width: 80px; height: 80px;
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            border: 2px solid rgba(15,107,62,0.2);
        }

        /* ── Right panel scrollbar ───────────── */
        .right-scroll { overflow-y: auto; }
        .right-scroll::-webkit-scrollbar { width: 3px; }
        .right-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }

        .field-label { display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 6px; }
        .field-error { color: #dc2626; font-size: 11px; margin-top: 4px; }
    </style>
</head>
<body>
<div style="display:flex; min-height:100vh;">

    <!-- ══════════════════ LEFT — Hero Panel ══════════════════ -->
    <div class="hero-bg" id="heroPanel"
         style="display:none; width:48%; position:relative; overflow:hidden; flex-direction:column; padding:44px 40px; flex-shrink:0;">
        <div class="grid-overlay"></div>
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <!-- ── MSAS Logo (real image) ── -->
        <div class="fade-up-1" style="position:relative; z-index:10; margin-bottom:32px;">
            <div class="logo-img-hero">
                <img src="{{ asset('images/msas-logo.png') }}" alt="MSAS Logo"
                     style="width:100%; height:100%; object-fit:cover; display:block;">
            </div>
        </div>

        <!-- ── Headline ── -->
        <div class="fade-up-2" style="position:relative; z-index:10; margin-bottom:28px;">
            <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(244,163,0,0.18); border:1px solid rgba(244,163,0,0.35); border-radius:20px; padding:5px 14px; margin-bottom:16px;">
                <span style="width:6px;height:6px;border-radius:50%;background:#F4A300;display:inline-block;"></span>
                <span style="font-size:11px;font-weight:700;color:#F4A300;letter-spacing:0.07em;text-transform:uppercase;">Nigeria's #1 AgriTech Platform</span>
            </div>
            <h2 style="font-size:30px; font-weight:800; color:#fff; line-height:1.25; margin:0 0 14px 0; letter-spacing:-0.3px;">
                Intelligent Agriculture<br>
                <span style="color:#1FA84A;">Management</span>
                <span style="color:#F4A300;"> Platform</span>
            </h2>
            <p style="font-size:13px; color:rgba(255,255,255,0.65); line-height:1.75; max-width:340px;">
                AI-powered diagnostics, veterinary services, agronomy support and enterprise management — all in one secure platform built for Nigeria's agricultural ecosystem.
            </p>
        </div>

        <!-- ── Feature cards 2×2 ── -->
        <div class="fade-up-3" style="position:relative; z-index:10; display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:28px;">
            <div class="feat-card">
                <div style="width:34px;height:34px;border-radius:9px;background:rgba(31,168,74,0.2);border:1px solid rgba(31,168,74,0.35);display:flex;align-items:center;justify-content:center;margin-bottom:10px;">
                    <svg width="17" height="17" fill="none" stroke="#1FA84A" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:3px;">AI Diagnostics</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.5);">Instant crop & livestock disease detection</div>
            </div>
            <div class="feat-card">
                <div style="width:34px;height:34px;border-radius:9px;background:rgba(45,156,219,0.2);border:1px solid rgba(45,156,219,0.35);display:flex;align-items:center;justify-content:center;margin-bottom:10px;">
                    <svg width="17" height="17" fill="none" stroke="#2D9CDB" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:3px;">14 Role Dashboards</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.5);">From CEO to farmer — every role covered</div>
            </div>
            <div class="feat-card">
                <div style="width:34px;height:34px;border-radius:9px;background:rgba(244,163,0,0.2);border:1px solid rgba(244,163,0,0.35);display:flex;align-items:center;justify-content:center;margin-bottom:10px;">
                    <svg width="17" height="17" fill="none" stroke="#F4A300" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:3px;">Real-Time Analytics</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.5);">Executive reports & live charts</div>
            </div>
            <div class="feat-card">
                <div style="width:34px;height:34px;border-radius:9px;background:rgba(129,140,248,0.2);border:1px solid rgba(129,140,248,0.35);display:flex;align-items:center;justify-content:center;margin-bottom:10px;">
                    <svg width="17" height="17" fill="none" stroke="#818cf8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:3px;">Enterprise Security</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.5);">RBAC, audit logs & compliance</div>
            </div>
        </div>

        <!-- ── Stats row ── -->
        <div class="fade-up-4" style="position:relative; z-index:10; display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:auto;">
            <div class="stat-pill" style="background:rgba(31,168,74,0.14);border:1px solid rgba(31,168,74,0.28);">
                <div style="font-size:20px;font-weight:800;color:#1FA84A;font-family:'Poppins',sans-serif;">14</div>
                <div style="font-size:10px;color:rgba(255,255,255,0.5);margin-top:3px;font-weight:600;">Roles</div>
            </div>
            <div class="stat-pill" style="background:rgba(45,156,219,0.14);border:1px solid rgba(45,156,219,0.28);">
                <div style="font-size:20px;font-weight:800;color:#2D9CDB;font-family:'Poppins',sans-serif;">AI</div>
                <div style="font-size:10px;color:rgba(255,255,255,0.5);margin-top:3px;font-weight:600;">Powered</div>
            </div>
            <div class="stat-pill" style="background:rgba(244,163,0,0.14);border:1px solid rgba(244,163,0,0.28);">
                <div style="font-size:20px;font-weight:800;color:#F4A300;font-family:'Poppins',sans-serif;">36+</div>
                <div style="font-size:10px;color:rgba(255,255,255,0.5);margin-top:3px;font-weight:600;">States</div>
            </div>
            <div class="stat-pill" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);">
                <div style="font-size:20px;font-weight:800;color:#fff;font-family:'Poppins',sans-serif;">24/7</div>
                <div style="font-size:10px;color:rgba(255,255,255,0.5);margin-top:3px;font-weight:600;">Support</div>
            </div>
        </div>

        <!-- ── Footer ── -->
        <div class="fade-up-5" style="position:relative; z-index:10; margin-top:32px; padding-top:18px; border-top:1px solid rgba(255,255,255,0.1);">
            <p style="font-size:11px;color:rgba(255,255,255,0.3);">&copy; {{ date('Y') }} MSAS Livestock & Agro Services &mdash; Technology. Knowledge. Productivity. Profitability.</p>
        </div>
    </div>

    <!-- ══════════════════ RIGHT — Form Panel ══════════════════ -->
    <div class="right-scroll" style="flex:1; display:flex; align-items:center; justify-content:center; background:#fff; position:relative; padding:40px 24px; min-height:100vh;">

        <!-- Top color bar -->
        <div style="position:absolute;top:0;left:0;right:0;height:4px;display:flex;">
            <div style="flex:1;background:#0F6B3E;"></div>
            <div style="flex:1;background:#1FA84A;"></div>
            <div style="flex:1;background:#2D9CDB;"></div>
            <div style="flex:1;background:#F4A300;"></div>
        </div>

        <!-- Language switcher (top-right of form panel) -->
        @php
            $guestLocale  = app()->getLocale();
            $guestLocales = ['en'=>'English','ha'=>'Hausa','yo'=>'Yorùbá','ig'=>'Igbo','ff'=>'Fulfulde'];
            $guestFlags   = ['en'=>'🇬🇧','ha'=>'🟢','yo'=>'🟡','ig'=>'🔵','ff'=>'🔴'];
        @endphp
        <div style="position:absolute;top:12px;right:16px;" x-data="{ open: false }">
            <button @click="open = !open"
                style="display:inline-flex;align-items:center;gap:5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;font-size:12px;font-weight:700;color:#475569;cursor:pointer;">
                <span>{{ $guestFlags[$guestLocale] ?? '🌍' }}</span>
                <span>{{ strtoupper($guestLocale) }}</span>
                <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" @click.outside="open=false" x-cloak
                 style="position:absolute;right:0;top:36px;width:160px;background:#fff;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,0.12);border:1px solid #e2e8f0;z-index:100;overflow:hidden;padding:4px 0;">
                @foreach($guestLocales as $code => $name)
                <form method="POST" action="{{ route('locale.set') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $code }}">
                    <button type="submit"
                        style="width:100%;display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:13px;background:none;border:none;cursor:pointer;color:{{ $guestLocale === $code ? '#0F6B3E' : '#475569' }};font-weight:{{ $guestLocale === $code ? '700' : '400' }}; text-align:left;">
                        <span>{{ $guestFlags[$code] }}</span>
                        <span>{{ $name }}</span>
                        @if($guestLocale === $code)<span style="margin-left:auto;color:#0F6B3E;">✓</span>@endif
                    </button>
                </form>
                @endforeach
            </div>
        </div>

        <div style="width:100%; max-width:420px;">

            <!-- ── LOGO + BRAND (always visible on right panel) ── -->
            <div class="fade-up-1" style="text-align:center; margin-bottom:32px;">
                <div style="display:inline-block; margin-bottom:14px;">
                    <div class="logo-img-form">
                        <img src="{{ asset('images/msas-logo.png') }}" alt="MSAS Logo"
                             style="width:100%; height:100%; object-fit:cover; display:block;">
                    </div>
                </div>
                <div style="font-family:'Poppins',sans-serif; font-size:22px; font-weight:800; color:#0f172a; letter-spacing:-0.3px; margin-bottom:3px;">MSAS FarmAI</div>
                <div style="font-size:12px; color:#64748b; font-weight:500; margin-bottom:14px;">Livestock & Agro Services</div>

                <!-- Only "Secured" badge — removed System Operational -->
                <div style="display:inline-flex; align-items:center; gap:6px; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:700; background:#eff6ff; color:#2D9CDB; border:1px solid #bfdbfe;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Secured
                </div>
            </div>

            <!-- ── FORM SLOT ── -->
            {{ $slot }}

        </div>
    </div>

</div>

<script>
(function() {
    var panel = document.getElementById('heroPanel');
    function check() {
        panel.style.display = window.innerWidth >= 1024 ? 'flex' : 'none';
    }
    check();
    window.addEventListener('resize', check);
})();
</script>
@stack('scripts')
</body>
</html>
