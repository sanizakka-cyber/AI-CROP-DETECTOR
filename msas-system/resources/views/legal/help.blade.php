<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Help Centre — MSAS FarmAI</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#f8fafc;color:#0f172a;font-size:15px;line-height:1.7}
.nav{background:#fff;border-bottom:1px solid #e2e8f0;padding:16px 24px;display:flex;align-items:center;justify-content:space-between}
.nav-brand{font-weight:800;font-size:17px;color:#0F6B3E;text-decoration:none}
.nav-links{display:flex;gap:20px;font-size:13px}
.nav-links a{color:#475569;text-decoration:none;font-weight:500}
.nav-links a:hover{color:#0F6B3E}
.hero{background:linear-gradient(135deg,#0B2447 0%,#0d4a2e 60%,#0F6B3E 100%);color:#fff;padding:52px 24px 44px;text-align:center}
.hero h1{font-size:28px;font-weight:800;margin-bottom:8px}
.hero p{font-size:14px;opacity:.8;margin-bottom:24px}
.hero-channels{display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:8px}
.hero-channel{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:12px 20px;font-size:13px;display:flex;align-items:center;gap:8px}
.hero-channel strong{display:block;font-size:14px;font-weight:700}
.hero-channel span{opacity:.8;font-size:12px}
.container{max-width:960px;margin:0 auto;padding:48px 24px 80px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:48px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;transition:box-shadow .15s}
.card:hover{box-shadow:0 4px 20px rgba(0,0,0,.08)}
.card-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;font-size:20px}
.card h3{font-size:15px;font-weight:700;margin-bottom:8px}
.card p{font-size:13px;color:#64748b;margin-bottom:16px;line-height:1.6}
.card a{font-size:13px;font-weight:700;color:#0F6B3E;text-decoration:none}
.card a:hover{text-decoration:underline}
.steps{margin-bottom:48px}
.steps h2{font-size:18px;font-weight:800;margin-bottom:20px}
.step{display:flex;gap:16px;margin-bottom:20px}
.step-num{flex-shrink:0;width:32px;height:32px;background:#0F6B3E;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;margin-top:2px}
.step-body h4{font-size:14px;font-weight:700;margin-bottom:4px}
.step-body p{font-size:13px;color:#64748b;line-height:1.6}
.contact-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:48px}
.contact-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px}
.contact-card .label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:8px}
.contact-card .value{font-size:15px;font-weight:700;color:#0f172a}
.contact-card .value a{color:#0F6B3E;text-decoration:none}
.contact-card .sub{font-size:12px;color:#64748b;margin-top:4px}
.alert{background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:14px 18px;font-size:13px;color:#78350f;margin-bottom:24px}
footer{text-align:center;padding:24px;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0}
@media(max-width:600px){.nav-links{display:none}.hero-channels{flex-direction:column;align-items:center}}
</style>
</head>
<body>

<nav class="nav">
    <a href="{{ url('/') }}" class="nav-brand">MSAS FarmAI</a>
    <div class="nav-links">
        <a href="{{ route('legal.faq') }}">FAQ</a>
        <a href="{{ route('legal.privacy') }}">Privacy</a>
        <a href="{{ route('legal.terms') }}">Terms</a>
        <a href="{{ route('login') }}">Log in</a>
        <a href="{{ route('register') }}">Sign up</a>
    </div>
</nav>

<div class="hero">
    <h1>Help Centre</h1>
    <p>We're here to help. Choose the option that works best for you.</p>
    <div class="hero-channels">
        <div class="hero-channel">
            <svg width="22" height="22" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <div><strong>Email</strong><span>support@msasagro.com</span></div>
        </div>
        <div class="hero-channel">
            <svg width="22" height="22" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            <div><strong>Phone</strong><span>+234 (0) 800 MSAS FARM</span></div>
        </div>
        <div class="hero-channel">
            <svg width="22" height="22" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <div><strong>In-app Chat</strong><span>Available in your dashboard</span></div>
        </div>
    </div>
</div>

<div class="container">

    <div class="alert">
        <strong>Support hours:</strong> Monday – Friday, 8:00 AM – 6:00 PM WAT (UTC+1). We aim to respond to all enquiries within <strong>4 business hours</strong>.
    </div>

    {{-- Topic cards --}}
    <div class="grid">

        <div class="card">
            <div class="card-icon" style="background:#f0fdf4;"><svg width="22" height="22" fill="none" stroke="#16a34a" stroke-width="1.8" viewBox="0 0 24 24"><rect x="7" y="7" width="10" height="10" rx="1" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 9H5M7 12H5M7 15H5M17 9h2M17 12h2M17 15h2M9 7V5M12 7V5M15 7V5M9 17v2M12 17v2M15 17v2"/></svg></div>
            <h3>AI Smart Scan</h3>
            <p>Having trouble uploading a photo, getting a result, or understanding a diagnosis? Check our scan guide.</p>
            <a href="{{ route('legal.faq') }}#ai">AI Scan FAQ →</a>
        </div>

        <div class="card">
            <div class="card-icon" style="background:#eff6ff;"><svg width="22" height="22" fill="none" stroke="#3b82f6" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" d="M2 10h20M6 15h2"/></svg></div>
            <h3>Billing &amp; Subscriptions</h3>
            <p>Questions about your plan, billing date, invoices, or upgrading? We can help immediately.</p>
            <a href="{{ route('legal.refund') }}">Refund Policy →</a>
        </div>

        <div class="card">
            <div class="card-icon" style="background:#fefce8;"><svg width="22" height="22" fill="none" stroke="#ca8a04" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
            <h3>Account &amp; Login</h3>
            <p>Locked out of your account, not receiving the verification code, or need to reset your password?</p>
            <a href="{{ route('password.request') }}">Reset Password →</a>
        </div>

        <div class="card">
            <div class="card-icon" style="background:#fdf4ff;"><svg width="22" height="22" fill="none" stroke="#9333ea" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div>
            <h3>Marketplace</h3>
            <p>Order not arrived, wrong item delivered, or want to become a seller? Raise a support ticket.</p>
            <a href="mailto:support@msasagro.com?subject=Marketplace%20Issue">Email Support →</a>
        </div>

        <div class="card">
            <div class="card-icon" style="background:#fff7ed;"><svg width="22" height="22" fill="none" stroke="#ea580c" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <h3>Language &amp; Voice</h3>
            <p>The platform is available in English, Hausa, Yoruba, Igbo, Fulfulde, and French. Switch from the top nav.</p>
            <a href="{{ route('legal.faq') }}">View FAQ →</a>
        </div>

        <div class="card">
            <div class="card-icon" style="background:#f0fdf4;"><svg width="22" height="22" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 20V10M12 20V4M6 20v-6"/></svg></div>
            <h3>Reports &amp; Data</h3>
            <p>Need to export your farm data, download a diagnosis report, or understand a financial record?</p>
            <a href="{{ route('legal.privacy') }}#export">Data Rights →</a>
        </div>

    </div>

    {{-- How to submit a ticket --}}
    <div class="steps">
        <h2>How to Submit a Support Ticket</h2>

        <div class="step">
            <div class="step-num">1</div>
            <div class="step-body">
                <h4>Log in to your account</h4>
                <p>Go to <a href="{{ route('login') }}" style="color:#0F6B3E;font-weight:600;">msasagro.com/login</a> and sign in. Support tickets filed while logged in receive faster responses because we can immediately see your account details.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-num">2</div>
            <div class="step-body">
                <h4>Open the Support section</h4>
                <p>From your dashboard sidebar, go to <strong>Support → New Ticket</strong>. Select the category that best describes your issue.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-num">3</div>
            <div class="step-body">
                <h4>Describe your issue</h4>
                <p>Include: what you were trying to do, what happened, any error messages you saw, and if relevant, which device and browser you were using. Screenshots are always helpful.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-num">4</div>
            <div class="step-body">
                <h4>Track your ticket</h4>
                <p>You'll receive a confirmation email. Our team will respond within 4 business hours. You can view ticket status in <strong>Support → My Tickets</strong> at any time.</p>
            </div>
        </div>
    </div>

    {{-- Contact cards --}}
    <div class="contact-grid">
        <div class="contact-card">
            <div class="label">General Support</div>
            <div class="value"><a href="mailto:support@msasagro.com">support@msasagro.com</a></div>
            <div class="sub">Response within 4 business hours</div>
        </div>
        <div class="contact-card">
            <div class="label">Billing &amp; Payments</div>
            <div class="value"><a href="mailto:billing@msasagro.com">billing@msasagro.com</a></div>
            <div class="sub">Payment issues &amp; refunds</div>
        </div>
        <div class="contact-card">
            <div class="label">Data &amp; Privacy</div>
            <div class="value"><a href="mailto:privacy@msasagro.com">privacy@msasagro.com</a></div>
            <div class="sub">NDPR requests &amp; data exports</div>
        </div>
        <div class="contact-card">
            <div class="label">Partner &amp; Business</div>
            <div class="value"><a href="mailto:partnerships@msasagro.com">partnerships@msasagro.com</a></div>
            <div class="sub">Enterprise, NGO &amp; government</div>
        </div>
    </div>

    <p style="text-align:center;font-size:13px;color:#64748b;">Looking for quick answers? Try our <a href="{{ route('legal.faq') }}" style="color:#0F6B3E;font-weight:700;">FAQ page</a> — most common questions are answered there.</p>

</div>

<footer>
    &copy; {{ date('Y') }} MSAS Livestock &amp; Agro Services. All rights reserved. &nbsp;·&nbsp;
    <a href="{{ route('legal.privacy') }}" style="color:#0F6B3E;">Privacy</a> &nbsp;·&nbsp;
    <a href="{{ route('legal.terms') }}" style="color:#0F6B3E;">Terms</a> &nbsp;·&nbsp;
    <a href="{{ route('legal.refund') }}" style="color:#0F6B3E;">Refund Policy</a> &nbsp;·&nbsp;
    <a href="{{ route('legal.faq') }}" style="color:#0F6B3E;">FAQ</a>
</footer>
</body>
</html>
