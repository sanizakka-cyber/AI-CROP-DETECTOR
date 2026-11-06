<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MSAS FarmAI | Smart Agriculture. Healthy Future.</title>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/branding/favicon-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/branding/msas-farmai-mark.png') }}">
<meta name="description" content="MSAS FarmAI is an AI-powered agribusiness platform for farmers, livestock owners, cooperatives, government agencies and development partners in Nigeria: crop, livestock and soil scans, expert consultations and a farm marketplace.">
<meta property="og:type" content="website">
<meta property="og:title" content="MSAS FarmAI | Smart Agriculture. Healthy Future.">
<meta property="og:description" content="AI-powered diagnostics, veterinary services, agronomy support and farm management for Nigeria's agricultural sector.">
<meta property="og:url" content="{{ config('app.url') }}">
<meta property="og:image" content="{{ asset('images/branding/msas-farmai-full.png') }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="MSAS FarmAI | Smart Agriculture. Healthy Future.">
<meta name="twitter:description" content="AI-powered agribusiness platform for Nigerian farmers and agri-enterprises.">
<meta name="theme-color" content="#0B3B24">
{{-- No web fonts: the phone's own UI font renders instantly. (fonts.bunny.net now serves its files from
     cdn.jsdelivr.net, which the site's Content Security Policy blocks, so they never loaded anyway.) --}}
@vite(['resources/js/app.js'])
@php
    // Real numbers only: plans, prices, scan counts and trial length come from config/subscription.php.
    $planKeys  = ['basic', 'basic_pro', 'premium', 'enterprise', 'enterprise_plus'];
    $plans     = collect($planKeys)->mapWithKeys(fn ($k) => [$k => config("subscription.plans.$k")])->filter();
    $trialDays = (int) (config('subscription.plans.basic.trial_days') ?? 14);
    $basicFrom = (int) (config('subscription.plans.basic.price.monthly') ?? 0);
    $naira     = fn ($n) => '₦' . number_format((int) $n);

    // Inline icons (no icon font: keeps the page light on slow networks).
    $paths = [
        'leaf'    => '<path d="M11 20A7 7 0 0 1 4 13c0-5 4-9 16-9 0 12-4 16-9 16Z"/><path d="M4 20c4-4 7-6 11-8"/>',
        'soil'    => '<path d="M3 17h18M3 21h18"/><path d="M12 13V3"/><path d="M12 7c-2-2-5-2-6 0 2 2 4 2 6 0Z"/><path d="M12 9c2-2 5-2 6 0-2 2-4 2-6 0Z"/>',
        'cow'     => '<path d="M5 9c-1.5 0-3-1-3-3 1.5 0 3 .5 4 1.5"/><path d="M19 9c1.5 0 3-1 3-3-1.5 0-3 .5-4 1.5"/><path d="M6 7.5C6 12 7 19 12 19s6-7 6-11.5C18 6 15.5 5 12 5S6 6 6 7.5Z"/><circle cx="9.5" cy="10.5" r=".8"/><circle cx="14.5" cy="10.5" r=".8"/><path d="M10 15.5h4"/>',
        'expert'  => '<path d="M6 3v5a5 5 0 0 0 10 0V3"/><path d="M11 13v3a4 4 0 0 0 8 0v-2"/><circle cx="19" cy="12" r="2"/>',
        'store'   => '<path d="M3 9 4.5 4h15L21 9"/><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0V9Z"/><path d="M5 13v7h14v-7"/><path d="M10 20v-4h4v4"/>',
        'check'   => '<path d="m5 12 4.5 4.5L19 7"/>',
        'globe'   => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/>',
        'file'    => '<path d="M14 3H6v18h12V7Z"/><path d="M14 3v4h4M9 13h6M9 17h6"/>',
        'pin'     => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'phone'   => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
        'card'    => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/>',
        'records' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'menu'    => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close'   => '<path d="M6 6l12 12M18 6 6 18"/>',
        'down'    => '<path d="m6 9 6 6 6-6"/>',
        'bell'    => '<path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15Z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'gauge'   => '<path d="M4 18a8 8 0 1 1 16 0"/><path d="m12 18 4-6"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'gear'    => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/>',
        'logout'  => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
        'login'   => '<path d="M9 4H5v16h4M14 8l4 4-4 4M18 12H8"/>',
        'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'call'    => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2Z"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'route'   => '<path d="M12 3 21 12l-9 9-9-9Z"/><path d="M9 13v-2h5V9l2.5 2.5L14 14v-2"/>',
        'shield'  => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6Z"/><path d="m9 12 2 2 4-4"/>',
    ];
    $icon = fn ($name, $size = 20) => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.($paths[$name] ?? '').'</svg>';
    $whatsapp = fn ($size = 20) => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.6 11.6 0 0 0 4.4 3.9c1.6.7 2.3.8 3.1.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z"/></svg>';
    $locale = session('locale', app()->getLocale());
    $languages = [['en', 'English', 'EN'], ['ha', 'Hausa', 'HA'], ['fr', 'Francais', 'FR'], ['yo', 'Yoruba', 'YO'], ['ig', 'Igbo', 'IG']];
    $localeLabels = ['en' => 'EN', 'ha' => 'HA', 'fr' => 'FR', 'yo' => 'YO', 'ig' => 'IG', 'ff' => 'FF'];
    // "Partners" and "Resources" used to point at "#"; they now go to the contact section and the Help Center.
    $navLinks = [['#home', 'Home'], ['#about', 'About'], ['#solutions', 'Solutions'], ['#features', 'Features'], ['#marketplace', 'Marketplace'], ['#pricing', 'Pricing'], ['#contact', 'Partners'], [route('legal.help'), 'Resources'], ['#contact', 'Contact']];
@endphp
<style>
:root{
    --g950:#06291A;--g900:#0B3B24;--g800:#0E5332;--g700:#0F6B3E;--g600:#16803F;--g500:#1FA84A;--g100:#DCFCE7;--g50:#F0FDF4;
    --gold:#F4A300;--ink:#0F172A;--body:#334155;--muted:#526173;--line:#E2E8F0;--bg:#F7FAF8;--white:#fff;
    --r-sm:10px;--r:16px;--r-lg:24px;--shadow:0 1px 2px rgba(15,23,42,.05),0 8px 24px rgba(15,23,42,.06);
    --font:system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;--head:var(--font);
}
*,*::before,*::after{box-sizing:border-box;}
html{-webkit-text-size-adjust:100%;}
body{margin:0;font-family:var(--font);color:var(--body);background:var(--white);line-height:1.6;font-size:16px;-webkit-font-smoothing:antialiased;}
img,svg{display:block;max-width:100%;}
a{color:inherit;text-decoration:none;}
h1,h2,h3{font-family:var(--head);color:var(--ink);margin:0;line-height:1.2;letter-spacing:-.01em;}
p{margin:0;}
ul,ol{margin:0;padding:0;list-style:none;}
[x-cloak]{display:none!important;}
:focus-visible{outline:3px solid var(--g500);outline-offset:2px;border-radius:6px;}
.wrap{max-width:1180px;margin:0 auto;padding:0 20px;}
.skip{position:absolute;left:12px;top:-60px;z-index:100;background:var(--white);color:var(--g700);padding:10px 14px;border-radius:var(--r-sm);font-weight:700;}
.skip:focus{top:12px;}
.link{color:var(--g700);font-weight:700;text-decoration:underline;text-underline-offset:2px;}

/* Buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:0 22px;border-radius:12px;font-weight:700;font-size:15px;border:2px solid transparent;cursor:pointer;transition:transform .15s ease,background-color .15s ease,border-color .15s ease;white-space:nowrap;font-family:var(--font);}
.btn:hover{transform:translateY(-1px);}
.btn-main{background:var(--gold);color:#1a1300;box-shadow:0 6px 20px rgba(244,163,0,.28);}
.btn-main:hover{background:#ffb41f;}
.btn-ghost{background:transparent;color:var(--white);border-color:rgba(255,255,255,.6);}
.btn-ghost:hover{background:rgba(255,255,255,.1);border-color:var(--white);}
.btn-green{background:var(--g700);color:var(--white);}
.btn-green:hover{background:var(--g800);}
.btn-line{background:var(--white);color:var(--g700);border-color:var(--g700);}
.btn-line:hover{background:var(--g50);}
.btn-sm{min-height:40px;padding:0 14px;font-size:14px;border-radius:10px;}

/* Header */
.hdr{position:fixed;inset:0 0 auto 0;z-index:50;transition:background-color .2s ease,box-shadow .2s ease;}
.hdr.solid{background:rgba(255,255,255,.97);box-shadow:0 1px 0 var(--line),0 4px 16px rgba(15,23,42,.06);}
.hdr-in{display:flex;align-items:center;justify-content:space-between;gap:12px;height:68px;}
.brand{display:flex;align-items:center;gap:10px;color:var(--white);min-width:0;}
.hdr.solid .brand{color:var(--ink);}
.brand img{width:40px;height:40px;border-radius:10px;background:var(--white);flex-shrink:0;}
.brand b{font-family:var(--head);font-weight:800;font-size:17px;line-height:1.1;display:block;}
.brand small{display:block;font-size:11px;opacity:.85;letter-spacing:.02em;white-space:nowrap;}
.nav{display:none;align-items:center;gap:20px;}
.nav a{color:rgba(255,255,255,.9);font-size:14px;font-weight:600;padding:6px 0;position:relative;}
.nav a::after{content:'';position:absolute;left:0;right:0;bottom:0;height:2px;background:currentColor;transform:scaleX(0);transition:transform .2s ease;}
.nav a:hover::after{transform:scaleX(1);}
.hdr.solid .nav a{color:var(--body);}
.hdr.solid .nav a:hover{color:var(--g700);}
.hdr-actions{display:none;align-items:center;gap:8px;}
.hdr-icon{display:inline-flex;align-items:center;justify-content:center;gap:4px;min-width:44px;height:44px;padding:0 8px;border-radius:10px;background:transparent;border:0;cursor:pointer;color:var(--white);font-weight:700;font-size:14px;font-family:var(--font);position:relative;}
.hdr-icon:hover{background:rgba(255,255,255,.12);}
.hdr.solid .hdr-icon{color:var(--body);}
.hdr.solid .hdr-icon:hover{background:#f1f5f9;}
.hdr .btn-ghost{min-height:40px;padding:0 14px;font-size:14px;}
.hdr.solid .btn-ghost{color:var(--g700);border-color:var(--g700);}
.menu{position:absolute;right:0;top:calc(100% + 8px);background:var(--white);border:1px solid var(--line);border-radius:14px;box-shadow:0 16px 40px rgba(15,23,42,.14);padding:6px;min-width:200px;z-index:60;color:var(--body);}
.menu a,.menu button{display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border-radius:10px;font-size:14px;font-weight:500;background:none;border:0;cursor:pointer;text-align:left;color:inherit;font-family:var(--font);}
.menu a:hover,.menu button:hover{background:var(--g50);color:var(--g700);}
.menu .on{font-weight:700;color:var(--g700);}
.menu .danger{color:#B91C1C;}
.menu .danger:hover{background:#FEF2F2;color:#B91C1C;}
.menu-head{padding:10px 12px;border-bottom:1px solid var(--line);margin-bottom:4px;}
.menu-head b{display:block;color:var(--ink);font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.menu-head span{font-size:12px;color:var(--muted);}
.avatar{width:32px;height:32px;border-radius:50%;background:var(--g700);color:var(--white);font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;}
.badge-dot{position:absolute;top:2px;right:2px;min-width:16px;height:16px;padding:0 4px;border-radius:8px;background:#DC2626;color:var(--white);font-size:10px;font-weight:800;line-height:16px;text-align:center;}
.burger{display:inline-flex;}
.drawer{background:var(--white);border-top:1px solid var(--line);box-shadow:0 16px 32px rgba(15,23,42,.12);max-height:calc(100vh - 68px);overflow-y:auto;}
.drawer nav a{display:block;padding:12px 4px;border-bottom:1px solid #f1f5f9;font-weight:600;color:var(--body);}
.drawer .langs{display:flex;flex-wrap:wrap;gap:6px;padding:14px 0;}
.drawer .langs button{min-width:44px;min-height:44px;padding:0 12px;border-radius:8px;border:1px solid var(--line);background:#f8fafc;font-weight:700;font-size:13px;cursor:pointer;color:var(--body);font-family:var(--font);}
.drawer .langs button.on{background:var(--g50);border-color:var(--g500);color:var(--g700);}
.drawer .acts{display:grid;gap:10px;padding:6px 0 20px;}
.hdr.open{background:var(--white);}
.hdr.open .brand,.hdr.open .burger{color:var(--ink);}
@media (min-width:1100px){.nav,.hdr-actions{display:flex;}.burger{display:none;}}
@media (min-width:720px) and (max-width:1099px){.hdr-actions{display:flex;}.hdr-actions .lang-wrap,.hdr-actions .user-extra{display:none;}}
@media (max-width:380px),(min-width:1100px) and (max-width:1279px){.hdr .brand small{display:none;}}

/* Hero */
.hero{position:relative;color:var(--white);background:radial-gradient(1200px 520px at 85% -10%,rgba(31,168,74,.35),transparent 60%),radial-gradient(800px 400px at -10% 110%,rgba(244,163,0,.14),transparent 60%),linear-gradient(160deg,var(--g950) 0%,var(--g900) 45%,var(--g800) 100%);overflow:hidden;}
.hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.07) 1px,transparent 1px);background-size:22px 22px;-webkit-mask-image:linear-gradient(180deg,#000 0%,transparent 85%);mask-image:linear-gradient(180deg,#000 0%,transparent 85%);pointer-events:none;}
.hero-in{position:relative;display:grid;gap:40px;padding-top:112px;padding-bottom:64px;align-items:center;}
.eyebrow{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);font-size:13px;font-weight:600;color:#E3F8EC;}
.eyebrow i{width:8px;height:8px;border-radius:50%;background:#4ADE80;box-shadow:0 0 0 4px rgba(74,222,128,.2);flex-shrink:0;}
.hero h1{color:var(--white);font-size:clamp(32px,6vw,54px);font-weight:800;margin:18px 0 16px;letter-spacing:-.02em;}
.hero h1 em{font-style:normal;color:#FCD34D;}
.hero .lead{font-size:clamp(16px,2.2vw,19px);color:#DDF1E5;max-width:600px;}
.hero-cta{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px;}
.hero-facts{display:flex;flex-wrap:wrap;gap:8px 20px;margin-top:24px;font-size:14px;color:#D2ECDD;}
.hero-facts span{display:inline-flex;align-items:center;gap:6px;}
.hero-facts svg{color:#4ADE80;flex-shrink:0;}
.demo{position:relative;justify-self:center;width:100%;max-width:380px;}
.demo-card{background:var(--white);color:var(--body);border-radius:var(--r-lg);box-shadow:0 30px 60px rgba(0,0,0,.35);padding:18px;}
.demo-tag{display:inline-block;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#475569;background:#f1f5f9;border-radius:6px;padding:3px 8px;}
.demo-photo{margin:12px 0;height:120px;border-radius:14px;background:linear-gradient(135deg,#3f7d2b 0%,#7fb342 45%,#d9c94a 80%,#c9a53a 100%);position:relative;overflow:hidden;}
.demo-photo::before{content:'';position:absolute;left:12%;right:-10%;top:42%;height:30px;transform:rotate(-12deg);background:linear-gradient(90deg,#4c8f2f 0%,#9ccc4a 40%,#f2e27a 85%,#f7ec9c 100%);border-radius:50%;}
.demo-photo span{position:absolute;left:10px;bottom:8px;font-size:11px;font-weight:700;color:var(--white);background:rgba(0,0,0,.55);padding:2px 8px;border-radius:6px;}
.demo-label{font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.06em;}
.demo-title{font-family:var(--head);font-size:20px;font-weight:800;color:var(--ink);margin:2px 0 10px;}
.demo-row{display:flex;gap:10px;padding:10px 0;border-top:1px solid #f1f5f9;font-size:14px;}
.demo-row svg{color:var(--g600);flex-shrink:0;margin-top:2px;}
.demo-btn{margin-top:12px;display:flex;align-items:center;justify-content:center;gap:8px;min-height:44px;border-radius:12px;background:var(--g50);color:var(--g700);font-weight:700;font-size:14px;border:1px solid var(--g100);}
.demo-note{margin-top:10px;font-size:12px;color:#D2ECDD;text-align:center;}
@media (min-width:960px){.hero-in{grid-template-columns:1.25fr .9fr;padding-top:140px;padding-bottom:96px;}.demo{justify-self:end;}}

/* Sections */
.sec{padding:72px 0;}
.sec-alt{background:var(--bg);}
.sec-head{max-width:700px;margin:0 auto 40px;text-align:center;}
.kicker{display:inline-block;font-size:13px;font-weight:700;color:var(--g700);letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px;}
.sec-head h2,.h2{font-size:clamp(26px,4vw,38px);font-weight:800;}
.sec-head p{margin-top:12px;color:var(--muted);font-size:17px;}
@media (min-width:960px){.sec{padding:96px 0;}}

.steps{display:grid;gap:16px;}
.step{background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:24px;}
.step-n{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:var(--g700);color:var(--white);font-weight:800;font-family:var(--head);}
.step h3{font-size:19px;margin:14px 0 6px;}
.step p{color:var(--muted);font-size:15px;}
@media (min-width:760px){.steps{grid-template-columns:repeat(3,1fr);}}

.about{display:grid;gap:28px;align-items:center;margin-top:56px;}
.about p{color:var(--muted);font-size:16px;}
.about p+p{margin-top:12px;}
.about-box{background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:24px;}
.about-box h3{font-size:17px;margin-bottom:8px;}
@media (min-width:900px){.about{grid-template-columns:1.2fr 1fr;}}

.cards{display:grid;gap:16px;}
.card{display:flex;flex-direction:column;background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:26px;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;}
.card:hover{transform:translateY(-3px);box-shadow:var(--shadow);border-color:#c9e9d5;}
.card-ico{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:var(--g50);color:var(--g700);margin-bottom:16px;flex-shrink:0;}
.card h3{font-size:20px;margin-bottom:8px;}
.card p{color:var(--muted);font-size:15px;}
.card ul{margin:14px 0 0;display:grid;gap:8px;}
.card li{display:flex;gap:8px;font-size:14px;color:var(--body);}
.card li svg{color:var(--g600);flex-shrink:0;margin-top:3px;}
.card-link{margin-top:auto;padding-top:18px;display:inline-flex;align-items:center;gap:6px;color:var(--g700);font-weight:700;font-size:15px;transition:gap .15s ease;}
.card-link:hover{gap:10px;}
@media (min-width:700px){.cards{grid-template-columns:repeat(2,1fr);}}
@media (min-width:1080px){.cards{grid-template-columns:repeat(3,1fr);}}

.feats{display:grid;gap:14px;}
.feat{display:flex;gap:14px;background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:20px;}
.feat .card-ico{margin:0;width:44px;height:44px;}
.feat h3{font-size:16px;margin-bottom:4px;}
.feat p{font-size:14px;color:var(--muted);}
@media (min-width:700px){.feats{grid-template-columns:repeat(2,1fr);}}
@media (min-width:1080px){.feats{grid-template-columns:repeat(3,1fr);}}

.market{display:grid;gap:32px;align-items:center;}
.chips{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin:22px 0 26px;}
.chip{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;background:var(--bg);border:1px solid var(--line);font-weight:600;font-size:14px;color:var(--ink);}
.chip svg{color:var(--g700);flex-shrink:0;}
.market-panel{background:linear-gradient(160deg,var(--g900),var(--g700));color:var(--white);border-radius:var(--r-lg);padding:28px;}
.market-panel h3{color:var(--white);font-size:20px;margin-bottom:14px;}
.market-panel li{display:flex;gap:10px;padding:10px 0;border-top:1px solid rgba(255,255,255,.14);font-size:15px;color:#E6F6EC;}
.market-panel li:first-child{border-top:0;}
.market-panel svg{color:#4ADE80;flex-shrink:0;margin-top:3px;}
@media (min-width:900px){.market{grid-template-columns:1.1fr 1fr;}}

/* Pricing */
.plans{display:grid;gap:16px;}
.plan{display:flex;flex-direction:column;background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:24px;}
.plan-name{font-size:13px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--g700);}
.plan-price{font-family:var(--head);font-size:30px;font-weight:800;color:var(--ink);margin-top:8px;line-height:1.1;}
.plan-price small{font-family:var(--font);font-size:14px;font-weight:500;color:var(--muted);}
.plan-year{font-size:13px;color:var(--muted);margin-top:2px;}
.plan-desc{font-size:14px;color:var(--muted);margin:12px 0 14px;}
.plan ul{display:grid;gap:8px;margin-bottom:20px;}
.plan li{display:flex;gap:8px;font-size:14px;}
.plan li svg{color:var(--g600);flex-shrink:0;margin-top:3px;}
.plan .btn{margin-top:auto;width:100%;}
.plans-note{margin-top:24px;text-align:center;font-size:14px;color:var(--muted);}
@media (min-width:700px){.plans{grid-template-columns:repeat(2,1fr);}}
@media (min-width:1080px){.plans{grid-template-columns:repeat(3,1fr);}}
@media (min-width:1280px){.plans{grid-template-columns:repeat(5,1fr);}.plan{padding:20px;}.plan-price{font-size:26px;}}

/* Founder + FAQ */
.ff{display:grid;gap:32px;}
.founder{background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:24px;align-self:start;}
.founder-top{display:flex;gap:14px;align-items:center;margin-bottom:16px;}
.founder-top img{width:72px;height:72px;border-radius:16px;object-fit:cover;border:2px solid var(--g500);flex-shrink:0;}
.founder-top h3{font-size:18px;}
.founder-top p{font-size:14px;color:var(--g700);font-weight:600;}
.founder blockquote{margin:0 0 12px;padding-left:12px;border-left:3px solid var(--g500);font-style:italic;color:var(--muted);font-size:15px;}
.founder .bio{font-size:14px;color:var(--muted);}
.founder-links{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;}
.soc{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:10px;background:var(--bg);border:1px solid var(--line);color:var(--body);font-weight:800;font-size:14px;}
.soc:hover{background:var(--g50);color:var(--g700);}
.faq details{background:var(--white);border:1px solid var(--line);border-radius:14px;margin-bottom:10px;}
.faq summary{list-style:none;cursor:pointer;padding:16px 18px;font-weight:700;color:var(--ink);display:flex;justify-content:space-between;gap:12px;align-items:center;font-size:16px;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary svg{flex-shrink:0;transition:transform .2s ease;color:var(--muted);}
.faq details[open] summary svg{transform:rotate(180deg);}
.faq details p{padding:0 18px 16px;color:var(--muted);font-size:15px;}
@media (min-width:960px){.ff{grid-template-columns:.85fr 1.15fr;gap:48px;}}

/* Contact */
.contact{display:grid;gap:24px;}
.cinfo{display:grid;gap:12px;align-content:start;}
.ci{display:flex;gap:14px;background:var(--white);border:1px solid var(--line);border-radius:14px;padding:16px;}
.ci-ico{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--g50);color:var(--g700);flex-shrink:0;}
.ci-ico.wa{background:#DCFCE7;color:#0E7A40;}
.ci b{display:block;color:var(--ink);font-size:15px;}
.ci span,.ci a.val{font-size:14px;color:var(--muted);}
.ci a.val{text-decoration:underline;text-underline-offset:2px;}
.ci a.val:hover{color:var(--g700);}
.ci .mini{display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:13px;font-weight:700;color:var(--g700);min-height:32px;}
.form{background:var(--white);border:1px solid var(--line);border-radius:var(--r);padding:24px;}
.form h3{font-size:20px;margin-bottom:4px;}
.form > p{font-size:14px;color:var(--muted);margin-bottom:18px;}
.fgrid{display:grid;gap:14px;}
.fld label{display:block;font-size:13px;font-weight:700;color:var(--ink);margin-bottom:6px;}
.fld input,.fld textarea{width:100%;min-height:44px;padding:10px 14px;border:1px solid #CBD5E1;border-radius:10px;background:#f8fafc;font:inherit;font-size:16px;color:var(--ink);}
.fld textarea{min-height:120px;resize:vertical;}
.fld input:focus,.fld textarea:focus{outline:none;border-color:var(--g700);background:var(--white);box-shadow:0 0 0 3px rgba(15,107,62,.15);}
.req{color:#B91C1C;}
@media (min-width:640px){.fgrid.two{grid-template-columns:1fr 1fr;}}
@media (min-width:960px){.contact{grid-template-columns:1fr 1.2fr;}}

/* Closing band + footer */
.band{background:linear-gradient(135deg,var(--g800),var(--g700));color:var(--white);border-radius:var(--r-lg);padding:36px 28px;display:grid;gap:20px;align-items:center;}
.band h2{color:var(--white);font-size:clamp(24px,3.4vw,32px);}
.band p{color:#DDF1E5;margin-top:6px;}
.band .acts{display:flex;flex-wrap:wrap;gap:12px;}
@media (min-width:900px){.band{grid-template-columns:1.4fr 1fr;padding:44px;}.band .acts{justify-content:flex-end;}}
.ftr{background:var(--g950);color:#B9D3C4;padding:64px 0 28px;margin-top:72px;font-size:14px;}
.ftr-grid{display:grid;gap:28px;grid-template-columns:1fr 1fr;}
.ftr h3{color:var(--white);font-size:15px;margin-bottom:12px;font-family:var(--font);font-weight:700;}
.ftr li{margin-bottom:4px;}
.ftr li a{display:inline-block;padding:4px 0;}
.ftr a:hover{color:var(--white);text-decoration:underline;}
.ftr-brand{grid-column:1/-1;}
.ftr-brand .brand{color:var(--white);margin-bottom:12px;}
.ftr-brand p{max-width:340px;}
.ftr-contact{grid-column:1/-1;}
.ftr-contact p{display:flex;gap:8px;margin-bottom:8px;}
.ftr-contact svg{color:var(--g500);flex-shrink:0;margin-top:3px;}
.ftr-bottom{border-top:1px solid rgba(255,255,255,.12);margin-top:36px;padding-top:20px;display:flex;flex-wrap:wrap;gap:12px 24px;justify-content:space-between;font-size:13px;}
.ftr-bottom nav{display:flex;flex-wrap:wrap;gap:16px;}
@media (min-width:900px){.ftr-grid{grid-template-columns:1.6fr 1fr 1fr 1.2fr 1.4fr;}.ftr-brand,.ftr-contact{grid-column:auto;}}
.wa-float{position:fixed;right:18px;bottom:18px;z-index:40;width:56px;height:56px;border-radius:50%;background:#128C4A;color:var(--white);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(0,0,0,.25);transition:transform .15s ease;}
.wa-float:hover{transform:scale(1.06);}

/* Light motion: fade-up on scroll; nothing moves for people who ask for less motion. */
.js .reveal{opacity:0;transform:translateY(14px);transition:opacity .5s ease,transform .5s ease;}
.js .reveal.in{opacity:1;transform:none;}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto!important;}*{transition:none!important;animation:none!important;}.js .reveal{opacity:1;transform:none;}}
</style>
<script>document.documentElement.classList.add('js');</script>
</head>
<body>
<a href="#main" class="skip">Skip to content</a>

{{-- ═══════════ HEADER ═══════════ --}}
<header id="main-nav" class="hdr" x-data="{ open:false }" :class="open ? 'open' : ''">
    <div class="wrap hdr-in">
        <a href="/" class="brand">
            <img src="{{ asset('images/branding/msas-farmai-mark-96.png') }}" alt="" width="40" height="40" fetchpriority="high">
            <span><b>MSAS FarmAI</b><small>Smart Agriculture. Healthy Future.</small></span>
        </a>

        <nav class="nav" aria-label="Main">
            @foreach($navLinks as [$href, $label])
            <a href="{{ $href }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="hdr-actions">
            <div class="lang-wrap" style="position:relative" x-data="{ langOpen:false }" @click.outside="langOpen=false" @keydown.escape.window="langOpen=false">
                <button type="button" class="hdr-icon" @click="langOpen=!langOpen" :aria-expanded="langOpen.toString()" aria-haspopup="true" aria-label="Select language">
                    {!! $icon('globe', 18) !!} {{ $localeLabels[$locale] ?? 'EN' }}
                </button>
                <div class="menu" x-show="langOpen" x-cloak x-transition.opacity.duration.150ms>
                    @foreach($languages as [$code, $name, $short])
                    <form method="POST" action="{{ route('locale.set') }}">@csrf<input type="hidden" name="locale" value="{{ $code }}">
                        <button type="submit" class="{{ $locale === $code ? 'on' : '' }}">{{ $name }}@if($locale === $code)<span style="margin-left:auto">{!! $icon('check', 16) !!}</span>@endif</button>
                    </form>
                    @endforeach
                </div>
            </div>
            @auth
            @php
                $navUser = auth()->user();
                $navNotifCount = 0;
                try { $navNotifCount = \App\Models\Notification::where('user_id', $navUser->id)->where('is_read', false)->count(); } catch (\Exception $e) {}
                $initials = strtoupper(substr($navUser->first_name ?? $navUser->name ?? 'U', 0, 1)) . strtoupper(substr($navUser->last_name ?? '', 0, 1));
            @endphp
            <a href="{{ route('notifications.index') }}" class="hdr-icon user-extra" aria-label="{{ $navNotifCount }} unread notifications">
                {!! $icon('bell', 18) !!}
                @if($navNotifCount > 0)<span class="badge-dot">{{ $navNotifCount > 9 ? '9+' : $navNotifCount }}</span>@endif
            </a>
            <div style="position:relative" x-data="{ userOpen:false }" @click.outside="userOpen=false" @keydown.escape.window="userOpen=false">
                <button type="button" class="hdr-icon" @click="userOpen=!userOpen" :aria-expanded="userOpen.toString()" aria-haspopup="true" aria-label="User menu">
                    <span class="avatar" aria-hidden="true">{{ $initials }}</span> {!! $icon('down', 14) !!}
                </button>
                <div class="menu" x-show="userOpen" x-cloak x-transition.opacity.duration.150ms style="min-width:230px">
                    <div class="menu-head">
                        <b>{{ trim(($navUser->first_name ?? '') . ' ' . ($navUser->last_name ?? '')) ?: $navUser->name }}</b>
                        <span>{{ $navUser->roleLabel ?? $navUser->role ?? 'User' }}</span>
                    </div>
                    <a href="{{ url('/dashboard') }}">{!! $icon('gauge', 16) !!} Dashboard</a>
                    <a href="{{ route('profile.edit') }}">{!! $icon('user', 16) !!} My Profile</a>
                    <a href="{{ route('profile.edit') }}#settings">{!! $icon('gear', 16) !!} Settings</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button type="submit" class="danger">{!! $icon('logout', 16) !!} Sign Out</button>
                    </form>
                </div>
            </div>
            @else
            <a href="{{ route('login') }}" class="btn btn-ghost">{!! $icon('login', 16) !!} Sign In</a>
            <a href="{{ route('register') }}" class="btn btn-main btn-sm">Register</a>
            @endauth
        </div>

        <button type="button" class="hdr-icon burger" @click="open=!open" :aria-expanded="open.toString()" aria-controls="mobile-nav-drawer" aria-label="Toggle navigation menu">
            <span x-show="!open">{!! $icon('menu', 24) !!}</span><span x-show="open" x-cloak>{!! $icon('close', 24) !!}</span>
        </button>
    </div>

    <div id="mobile-nav-drawer" class="drawer" x-show="open" x-cloak x-transition.opacity.duration.150ms>
        <div class="wrap">
            <nav aria-label="Mobile">
                @foreach($navLinks as [$href, $label])
                <a href="{{ $href }}" @click="open=false">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="langs" role="group" aria-label="Language">
                @foreach($languages as [$code, $name, $short])
                <form method="POST" action="{{ route('locale.set') }}">@csrf<input type="hidden" name="locale" value="{{ $code }}">
                    <button type="submit" class="{{ $locale === $code ? 'on' : '' }}" aria-label="{{ $name }}">{{ $short }}</button>
                </form>
                @endforeach
            </div>
            <div class="acts">
                @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-green" @click="open=false">{!! $icon('gauge', 18) !!} Dashboard</a>
                <a href="{{ route('profile.edit') }}" class="btn btn-line" @click="open=false">{!! $icon('user', 18) !!} My Profile</a>
                <a href="{{ route('notifications.index') }}" class="btn btn-line" @click="open=false">{!! $icon('bell', 18) !!} Notifications</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="btn btn-line" style="width:100%;color:#B91C1C;border-color:#FCA5A5">{!! $icon('logout', 18) !!} Sign Out</button>
                </form>
                @else
                <a href="{{ route('register') }}" class="btn btn-main" @click="open=false">Register</a>
                <a href="{{ route('login') }}" class="btn btn-line" @click="open=false">{!! $icon('login', 18) !!} Sign In</a>
                @endauth
            </div>
        </div>
    </div>
</header>

<main id="main">
{{-- ═══════════ HERO ═══════════ --}}
<section id="home" class="hero">
    <div class="wrap hero-in">
        <div>
            <span class="eyebrow"><i aria-hidden="true"></i> AI farm assistant for Nigerian agriculture</span>
            <h1>Know what is wrong with your <em>crops and animals</em> — and what to do next.</h1>
            <p class="lead">Take a photo of a sick plant, animal or soil sample. MSAS FarmAI explains the likely problem in plain words, suggests next steps, and lets you ask a vet or agronomist.</p>
            <div class="hero-cta">
                @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-main">Go to my dashboard {!! $icon('arrow', 18) !!}</a>
                @else
                <a href="{{ route('register') }}" class="btn btn-main">Register {!! $icon('arrow', 18) !!}</a>
                <a href="{{ route('login') }}" class="btn btn-ghost">Sign in</a>
                @endauth
            </div>
            <div class="hero-facts">
                <span>{!! $icon('check', 16) !!} {{ $trialDays }}-day free trial on every plan</span>
                <span>{!! $icon('check', 16) !!} English, Hausa, Yoruba, Igbo, French</span>
                <span>{!! $icon('check', 16) !!} Web and Android</span>
            </div>
        </div>

        <figure class="demo" style="margin:0">
            <div class="demo-card">
                <span class="demo-tag">Example result</span>
                <div class="demo-photo" role="img" aria-label="Illustration of a maize leaf"><span>Maize leaf</span></div>
                <div class="demo-label">Likely problem</div>
                <div class="demo-title">Nitrogen deficiency</div>
                <div class="demo-row">{!! $icon('leaf', 18) !!}<span><b>Why:</b> yellowing starts at the tip of older leaves and runs along the middle vein.</span></div>
                <div class="demo-row">{!! $icon('check', 18) !!}<span><b>Next step:</b> top-dress with a nitrogen fertiliser; ask an agronomist for the right amount for your field.</span></div>
                <div class="demo-btn" aria-hidden="true">{!! $icon('expert', 18) !!} Ask an agronomist</div>
            </div>
            <figcaption class="demo-note">Illustration of how a result is laid out. Your result depends on your photo.</figcaption>
        </figure>
    </div>
</section>

{{-- ═══════════ HOW IT WORKS + ABOUT ═══════════ --}}
<section id="about" class="sec sec-alt">
    <div class="wrap">
        <div class="sec-head reveal">
            <span class="kicker">How it works</span>
            <h2>From a photo to a plan in three steps</h2>
        </div>
        <ol class="steps">
            <li class="step reveal"><span class="step-n" aria-hidden="true">1</span><h3>Take a photo</h3><p>Photograph the leaf, the animal or the soil sample with your phone, on the website or in the Android app.</p></li>
            <li class="step reveal"><span class="step-n" aria-hidden="true">2</span><h3>Read the result</h3><p>See the likely problem, the signs that point to it and practical next steps, in your language. On Basic Pro and higher you can save it as a PDF report.</p></li>
            <li class="step reveal"><span class="step-n" aria-hidden="true">3</span><h3>Ask an expert</h3><p>Not sure? Send the result to a vet or agronomist from the same screen and get advice for your farm.</p></li>
        </ol>

        <div class="about">
            <div class="reveal">
                <span class="kicker">About MSAS FarmAI</span>
                <h2 class="h2">Built in Katsina for farmers across Nigeria</h2>
                <p style="margin-top:14px">MSAS FarmAI is a digital agriculture platform by MSAS Livestock &amp; Agro Services. It brings AI scans, farm records, expert advice and a marketplace together for farmers, livestock owners, cooperatives, agribusinesses, government agencies and development partners.</p>
                <p>AI results can be wrong, so every scan tells you how sure it is and points you to a vet or agronomist when it matters.</p>
            </div>
            <div class="about-box reveal">
                <h3>Our mission</h3>
                <p>To empower agriculture stakeholders with smart digital solutions.</p>
                <h3 style="margin-top:18px">Who it is for</h3>
                <p>Farmers and livestock owners, vets and agronomists, agro-dealers, cooperatives, agribusinesses, NGOs, research institutions and government agencies — each with their own dashboard.</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ SOLUTIONS ═══════════ --}}
<section id="solutions" class="sec">
    <div class="wrap">
        <div class="sec-head reveal">
            <span class="kicker">What you can do</span>
            <h2>Everything for a healthier farm, in one place</h2>
            <p>Tools that work together, from diagnosis to buying what you need.</p>
        </div>
        <div class="cards">
            <article class="card reveal">
                <div class="card-ico">{!! $icon('leaf', 24) !!}</div>
                <h3>Crop scan</h3>
                <p>Photograph a sick leaf, stem or fruit and get the likely disease, pest or nutrient problem with next steps.</p>
                <ul><li>{!! $icon('check', 16) !!} Plain-language causes and signs</li><li>{!! $icon('check', 16) !!} Practical treatment and prevention steps</li></ul>
                <a href="{{ route('register') }}" class="card-link">Start scanning {!! $icon('arrow', 16) !!}</a>
            </article>
            <article class="card reveal">
                <div class="card-ico">{!! $icon('soil', 24) !!}</div>
                <h3>Soil analysis</h3>
                <p>Photograph a soil sample to understand its condition and get advice on improving it before you plant.</p>
                <ul><li>{!! $icon('check', 16) !!} Soil condition in plain words</li><li>{!! $icon('check', 16) !!} Suggestions for what to do next</li></ul>
                <a href="{{ route('register') }}" class="card-link">Analyse your soil {!! $icon('arrow', 16) !!}</a>
            </article>
            <article class="card reveal">
                <div class="card-ico">{!! $icon('cow', 24) !!}</div>
                <h3>Livestock</h3>
                <p>Scan a sick animal, and keep records of your herd and flock with health, vaccination and feeding reminders.</p>
                <ul><li>{!! $icon('check', 16) !!} Animal health scans</li><li>{!! $icon('check', 16) !!} Livestock records and reminders</li></ul>
                <a href="{{ route('register') }}" class="card-link">Manage your animals {!! $icon('arrow', 16) !!}</a>
            </article>
            <article class="card reveal">
                <div class="card-ico">{!! $icon('expert', 24) !!}</div>
                <h3>Experts</h3>
                <p>Request a veterinarian or agronomist, with your scan attached, so they can see what you see.</p>
                <ul><li>{!! $icon('check', 16) !!} Vet and agronomist consultations</li><li>{!! $icon('check', 16) !!} Included in Basic Pro and higher plans</li></ul>
                <a href="{{ route('register') }}" class="card-link">Talk to an expert {!! $icon('arrow', 16) !!}</a>
            </article>
            <article class="card reveal">
                <div class="card-ico">{!! $icon('store', 24) !!}</div>
                <h3>Marketplace</h3>
                <p>Buy seeds, fertiliser, feed, equipment and veterinary supplies, or sell your produce to other users.</p>
                <ul><li>{!! $icon('check', 16) !!} Inputs and produce in one place</li><li>{!! $icon('check', 16) !!} Prices in Naira</li></ul>
                <a href="{{ route('marketplace') }}" class="card-link">Visit the marketplace {!! $icon('arrow', 16) !!}</a>
            </article>
            <article class="card reveal" style="background:var(--g50);border-color:var(--g100)">
                <div class="card-ico" style="background:var(--white)">{!! $icon('records', 24) !!}</div>
                <h3>Farm records</h3>
                <p>Keep crop and livestock records, an activity log and monthly reports, so you can see how your farm is doing.</p>
                <a href="{{ route('register') }}" class="card-link">Create your account {!! $icon('arrow', 16) !!}</a>
            </article>
        </div>
    </div>
</section>

{{-- ═══════════ FEATURES ═══════════ --}}
<section id="features" class="sec sec-alt">
    <div class="wrap">
        <div class="sec-head reveal">
            <span class="kicker">Built for real farms</span>
            <h2>Made for the way you work</h2>
        </div>
        <div class="feats">
            <div class="feat reveal"><div class="card-ico">{!! $icon('globe', 22) !!}</div><div><h3>Your language</h3><p>Use the website in English, Hausa, Yoruba, Igbo or French.</p></div></div>
            <div class="feat reveal"><div class="card-ico">{!! $icon('phone', 22) !!}</div><div><h3>Web and Android</h3><p>Use MSAS FarmAI in your browser or in the Android app, with the same account.</p></div></div>
            <div class="feat reveal"><div class="card-ico">{!! $icon('file', 22) !!}</div><div><h3>Downloadable reports</h3><p>Save scan results as PDF reports to share or keep (Basic Pro and higher).</p></div></div>
            <div class="feat reveal"><div class="card-ico">{!! $icon('pin', 22) !!}</div><div><h3>Location you control</h3><p>Use your phone's GPS or choose your State and LGA. Exact points are seen only by you and authorised MSAS staff.</p></div></div>
            <div class="feat reveal"><div class="card-ico">{!! $icon('card', 22) !!}</div><div><h3>Pay in Naira</h3><p>Monthly or yearly plans, paid securely through Paystack.</p></div></div>
            <div class="feat reveal"><div class="card-ico">{!! $icon('shield', 22) !!}</div><div><h3>Your data, protected</h3><p>We do not sell your personal data. See our <a href="{{ route('legal.privacy') }}" class="link">Privacy Policy</a>.</p></div></div>
        </div>
    </div>
</section>

{{-- ═══════════ MARKETPLACE ═══════════ --}}
<section id="marketplace" class="sec">
    <div class="wrap market">
        <div class="reveal">
            <span class="kicker">MSAS Marketplace</span>
            <h2 class="h2">Buy and sell farm inputs and produce</h2>
            <p style="margin-top:12px;color:var(--muted)">Find what your farm needs, or sell what it produces, to farmers, cooperatives and processors on the platform.</p>
            <div class="chips">
                @foreach([['Seeds & seedlings', 'leaf'], ['Fertilisers', 'soil'], ['Livestock & poultry', 'cow'], ['Farm equipment', 'gear'], ['Veterinary supplies', 'expert'], ['Fresh produce', 'store']] as [$label, $ic])
                <div class="chip">{!! $icon($ic, 18) !!} {{ $label }}</div>
                @endforeach
            </div>
            <a href="{{ route('marketplace') }}" class="btn btn-green">Visit the marketplace {!! $icon('arrow', 18) !!}</a>
        </div>
        <div class="market-panel reveal">
            <h3>Why use it</h3>
            <ul>
                <li>{!! $icon('check', 18) !!} Inputs, equipment, animals and produce in one place.</li>
                <li>{!! $icon('check', 18) !!} Prices shown in Naira.</li>
                <li>{!! $icon('check', 18) !!} Linked to your MSAS FarmAI account, next to your scans and records.</li>
            </ul>
        </div>
    </div>
</section>

{{-- ═══════════ PRICING ═══════════ --}}
<section id="pricing" class="sec sec-alt">
    <div class="wrap">
        <div class="sec-head reveal">
            <span class="kicker">Pricing</span>
            <h2>Simple plans in Naira</h2>
            <p>Start with a {{ $trialDays }}-day free trial on any plan.</p>
        </div>
        <div class="plans">
            @foreach($plans as $key => $plan)
            <div class="plan reveal">
                <h3 class="plan-name">{{ \Illuminate\Support\Str::of($plan['name'] ?? $key)->replaceLast(' Plan', '') }}</h3>
                <div class="plan-price">{{ $naira($plan['price']['monthly'] ?? 0) }} <small>/ month</small></div>
                <div class="plan-year">or {{ $naira($plan['price']['yearly'] ?? 0) }} / year</div>
                <p class="plan-desc">{{ $plan['description'] ?? '' }}</p>
                <ul>
                    @foreach(array_slice($plan['highlights'] ?? [], 0, 6) as $item)
                    <li>{!! $icon('check', 16) !!} <span>{{ $item }}</span></li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}?plan={{ $key }}" class="btn {{ $key === 'basic_pro' ? 'btn-green' : 'btn-line' }}" aria-label="Start free trial: {{ $plan['name'] ?? $key }}">Start free trial</a>
            </div>
            @endforeach
        </div>
        <p class="plans-note">Subscription payments are non-refundable once your plan is activated — see the <a href="{{ route('legal.refund') }}" class="link">Refund Policy</a>. Need something different? <a href="#contact" class="link">Contact us</a>.</p>
    </div>
</section>

{{-- ═══════════ FOUNDER + FAQ ═══════════ --}}
<section id="founder" class="sec">
    <div class="wrap ff">
        <div class="founder reveal">
            <span class="kicker">Leadership</span>
            <div class="founder-top">
                <img src="{{ asset('images/ceo-sani-yawale-zakka-160.jpg') }}" alt="Sani Yawale Zakka, Founder and CEO of MSAS FarmAI" width="72" height="72" loading="lazy">
                <div><h3>Sani Yawale Zakka</h3><p>Founder &amp; CEO, MSAS FarmAI</p></div>
            </div>
            <blockquote>"Technology should serve every farmer — from the smallholder in Katsina to the cooperative in Lagos. That is the vision behind MSAS FarmAI."</blockquote>
            <p class="bio">Sani Yawale Zakka is an entrepreneur passionate about agriculture, livestock development and digital innovation. He founded MSAS to help make farming more profitable, efficient and technology-driven for farmers and their communities.</p>
            <div class="founder-links">
                <a class="soc" href="https://www.linkedin.com/search/results/all/?keywords=Sani+Yawale+Zakka" target="_blank" rel="noopener noreferrer" aria-label="Sani Yawale Zakka on LinkedIn">in</a>
                <a class="soc" href="https://www.facebook.com/search/top/?q=Sani%20Yawale%20Zakka" target="_blank" rel="noopener noreferrer" aria-label="Sani Yawale Zakka on Facebook">f</a>
                <a class="soc" href="https://twitter.com/Sanizakka" target="_blank" rel="noopener noreferrer" aria-label="Sani Yawale Zakka on X">X</a>
                <a class="soc" href="https://wa.me/2348032459879" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp Sani Yawale Zakka" style="color:#0E7A40">{!! $whatsapp(20) !!}</a>
                <a class="soc" href="tel:+2348032459879" aria-label="Call +234 803 245 9879">{!! $icon('call', 18) !!}</a>
                <a class="soc" href="mailto:sanizakka@gmail.com" aria-label="Email sanizakka@gmail.com">{!! $icon('mail', 18) !!}</a>
            </div>
        </div>

        <div class="faq">
            <span class="kicker">FAQ</span>
            <h2 class="h2" style="margin-bottom:20px">Frequently asked questions</h2>
            @foreach([
                ['What is MSAS FarmAI?', 'A digital agriculture platform for farmers, livestock owners, cooperatives, agribusinesses, government agencies and development partners. It combines AI crop, livestock and soil scans, farm records, expert consultations and a marketplace.'],
                ['Is there a free trial?', 'Yes. Every plan starts with a '.$trialDays.'-day free trial. The Basic plan is '.$naira($basicFrom).' a month after the trial. All plans are listed in the Pricing section above.'],
                ['How does the AI scan work?', 'Take a clear photo of the affected leaf, animal or soil sample. MSAS FarmAI suggests the most likely problem, the signs that point to it and practical next steps. AI can be wrong, so the result says how sure it is, and you can ask a vet or agronomist to check.'],
                ['Can I talk to a vet or agronomist?', 'Yes. You can request a consultation from a scan result, with your photo attached. Basic Pro and higher plans include a number of vet and agronomist consultations each month.'],
                ['Which languages can I use?', 'The website is available in English, Hausa, Yoruba, Igbo and French. Use the language button at the top of the page to switch.'],
                ['Is my data safe?', 'We do not sell your personal data. Exact GPS locations are seen only by you and authorised MSAS staff; reports show State and LGA only. Our Privacy Policy has the details.'],
                ['Who can register?', 'Anyone aged 18 or older. You need your name, an email address or phone number, your location and a password, and you agree to the Terms of Service and Privacy Policy.'],
            ] as [$q, $a])
            <details class="reveal">
                <summary>{{ $q }} {!! $icon('down', 18) !!}</summary>
                <p>{{ $a }}</p>
            </details>
            @endforeach
            <p style="margin-top:14px;font-size:14px;color:var(--muted)">More questions? Read the <a href="{{ route('legal.faq') }}" class="link">full FAQ</a> or <a href="#contact" class="link">contact us</a>.</p>
        </div>
    </div>
</section>

{{-- ═══════════ CONTACT ═══════════ --}}
<section id="contact" class="sec sec-alt">
    <div class="wrap">
        <div class="sec-head reveal">
            <span class="kicker">Contact and partnerships</span>
            <h2>Talk to us or partner with us</h2>
            <p>Questions about the platform, or interested in working with MSAS FarmAI as a cooperative, agency, NGO or business? Reach us on the channel that suits you.</p>
        </div>
        <div class="contact">
            <div class="cinfo">
                <div class="ci reveal">
                    <div class="ci-ico">{!! $icon('pin', 20) !!}</div>
                    <div><b>Office address</b><span>No. 21 Sarkin Maska Street, Dutsin Safe Lowcost, Katsina State, Nigeria</span><br>
                        <a class="mini" href="https://maps.google.com?q=Dutsin+Safe+Lowcost+Katsina+Nigeria" target="_blank" rel="noopener noreferrer">{!! $icon('route', 14) !!} Get directions</a></div>
                </div>
                <div class="ci reveal">
                    <div class="ci-ico">{!! $icon('call', 20) !!}</div>
                    <div><b>Phone</b><a class="val" href="tel:+2348032459879">08032459879</a> · <a class="val" href="tel:+2348129582957">08129582957</a></div>
                </div>
                <div class="ci reveal">
                    <div class="ci-ico">{!! $icon('mail', 20) !!}</div>
                    <div><b>Email</b><a class="val" href="mailto:sanizakka@gmail.com" style="word-break:break-all">sanizakka@gmail.com</a></div>
                </div>
                <div class="ci reveal">
                    <div class="ci-ico wa">{!! $whatsapp(20) !!}</div>
                    <div><b>WhatsApp</b><a class="val" href="https://wa.me/2348129582957" target="_blank" rel="noopener noreferrer">08129582957</a> · <a class="val" href="https://wa.me/2348032459879" target="_blank" rel="noopener noreferrer">08032459879</a></div>
                </div>
                <div class="ci reveal">
                    <div class="ci-ico">{!! $icon('clock', 20) !!}</div>
                    <div><b>Business hours</b><span>Mon – Fri: 8:00 AM – 6:00 PM · Sat: 9:00 AM – 2:00 PM (WAT)</span></div>
                </div>
            </div>

            <div class="form reveal">
                <h3>Send a message</h3>
                <p>This opens WhatsApp with your message ready to send.</p>
                <form action="https://wa.me/2348129582957" method="get" target="_blank" onsubmit="return sendWhatsApp(this)">
                    <div class="fgrid two">
                        <div class="fld"><label for="c-name">Full name <span class="req">*</span></label><input id="c-name" type="text" name="name" required autocomplete="name" placeholder="Your full name"></div>
                        <div class="fld"><label for="c-phone">Phone number <span class="req">*</span></label><input id="c-phone" type="tel" name="phone" required autocomplete="tel" placeholder="08xxxxxxxxx"></div>
                        <div class="fld"><label for="c-email">Email address</label><input id="c-email" type="email" name="email" autocomplete="email" placeholder="you@example.com"></div>
                        <div class="fld"><label for="c-subject">Subject</label><input id="c-subject" type="text" name="subject" placeholder="How can we help?"></div>
                    </div>
                    <div class="fld" style="margin-top:14px"><label for="c-message">Message <span class="req">*</span></label><textarea id="c-message" name="message" required rows="5" placeholder="Tell us about your farm, your organisation or your question."></textarea></div>
                    <button type="submit" class="btn btn-green" style="width:100%;margin-top:16px">{!! $whatsapp(20) !!} Send on WhatsApp</button>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ CLOSING BAND ═══════════ --}}
<section class="sec" style="padding-bottom:0" aria-label="Get started">
    <div class="wrap">
        <div class="band reveal">
            <div>
                <h2>Ready to look after your farm with MSAS FarmAI?</h2>
                <p>Create your account in a few minutes. Every plan starts with a {{ $trialDays }}-day free trial.</p>
            </div>
            <div class="acts">
                @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-main">Go to my dashboard</a>
                @else
                <a href="{{ route('register') }}" class="btn btn-main">Register</a>
                <a href="{{ route('login') }}" class="btn btn-ghost">Sign in</a>
                @endauth
            </div>
        </div>
    </div>
</section>
</main>

{{-- ═══════════ FOOTER ═══════════ --}}
<footer class="ftr">
    <div class="wrap">
        <div class="ftr-grid">
            <div class="ftr-brand">
                <a href="/" class="brand"><img src="{{ asset('images/branding/msas-farmai-mark-96.png') }}" alt="" width="40" height="40" loading="lazy"><span><b>MSAS FarmAI</b><small>Smart Agriculture. Healthy Future.</small></span></a>
                <p>An AI-powered agribusiness platform connecting farmers, experts, governments and development partners.</p>
            </div>
            <div>
                <h3>Quick links</h3>
                <ul>
                    @foreach([['#home', 'Home'], ['#about', 'About Us'], ['#solutions', 'Solutions'], ['#marketplace', 'Marketplace'], ['#pricing', 'Pricing']] as [$href, $label])
                    <li><a href="{{ $href }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h3>Resources</h3>
                <ul>
                    <li><a href="{{ route('legal.faq') }}">FAQs</a></li>
                    <li><a href="{{ route('legal.help') }}">Help Center</a></li>
                </ul>
            </div>
            <div>
                <h3>Legal</h3>
                <ul>
                    <li><a href="{{ route('legal.privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('legal.privacy') }}">Data Protection</a></li>
                    <li><a href="{{ route('legal.cookie') }}">Cookie Policy</a></li>
                    <li><a href="{{ route('legal.refund') }}">Refund Policy</a></li>
                </ul>
            </div>
            <div class="ftr-contact">
                <h3>Contact us</h3>
                <p>{!! $icon('pin', 16) !!}<span>No. 21 Sarkin Maska Street, Dutsin Safe Lowcost, Katsina State, Nigeria</span></p>
                <p>{!! $icon('call', 16) !!}<a href="tel:+2348032459879">08032459879</a></p>
                <p>{!! $icon('mail', 16) !!}<a href="mailto:sanizakka@gmail.com" style="word-break:break-all">sanizakka@gmail.com</a></p>
                <p><span style="color:var(--g500);display:flex">{!! $whatsapp(16) !!}</span><a href="https://wa.me/2348129582957" target="_blank" rel="noopener noreferrer">08129582957</a></p>
            </div>
        </div>
        <div class="ftr-bottom">
            <p>&copy; {{ date('Y') }} MSAS FarmAI. All rights reserved.</p>
            <nav aria-label="Legal">
                <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
                <a href="{{ route('legal.terms') }}">Terms of Service</a>
                <a href="{{ route('legal.privacy') }}">Data Protection</a>
            </nav>
        </div>
    </div>
</footer>

<a href="https://wa.me/2348129582957" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp" class="wa-float">{!! $whatsapp(28) !!}</a>

<script>
// Solid header once the page scrolls past the top of the hero.
(function () {
    var nav = document.getElementById('main-nav');
    function onScroll() { nav.classList.toggle('solid', window.scrollY > 40); }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
// Contact form: open WhatsApp with the message filled in.
function sendWhatsApp(form) {
    var name = form.name.value, phone = form.phone.value, subject = form.subject ? form.subject.value : '', message = form.message.value;
    var text = 'MSAS FarmAI Enquiry\n\nName: ' + name + '\nPhone: ' + phone + (subject ? '\nSubject: ' + subject : '') + '\n\nMessage:\n' + message;
    window.open('https://wa.me/2348129582957?text=' + encodeURIComponent(text), '_blank');
    return false;
}
// Fade sections in as they scroll into view (everything shows at once without IntersectionObserver).
(function () {
    var els = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) { els.forEach(function (el) { el.classList.add('in'); }); return; }
    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); obs.unobserve(e.target); } });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    els.forEach(function (el) { obs.observe(el); });
})();
</script>
</body>
</html>
