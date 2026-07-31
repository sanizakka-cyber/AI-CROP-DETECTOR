<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cookie Policy — MSAS FarmAI</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#f8fafc;color:#0f172a;font-size:15px;line-height:1.7}
.nav{background:#fff;border-bottom:1px solid #e2e8f0;padding:16px 24px;display:flex;align-items:center;justify-content:space-between}
.nav-brand{font-weight:800;font-size:17px;color:#0F6B3E;text-decoration:none}
.nav-links{display:flex;gap:20px;font-size:13px}
.nav-links a{color:#475569;text-decoration:none;font-weight:500}
.nav-links a:hover{color:#0F6B3E}
.hero{background:linear-gradient(135deg,#0B2447 0%,#0d4a2e 60%,#0F6B3E 100%);color:#fff;padding:52px 24px 44px;text-align:center}
.hero h1{font-size:clamp(24px,4vw,40px);font-weight:800;margin-bottom:10px}
.hero p{font-size:15px;opacity:.85;max-width:560px;margin:0 auto}
.container{max-width:860px;margin:0 auto;padding:48px 24px}
.updated{font-size:13px;color:#64748b;margin-bottom:32px}
h2{font-size:19px;font-weight:700;color:#0B2447;margin:36px 0 12px}
p,li{color:#334155;margin-bottom:10px}
ul{padding-left:20px;margin-bottom:10px}
table{width:100%;border-collapse:collapse;margin:20px 0;font-size:13px}
th{background:#f1f5f9;text-align:left;padding:10px 14px;font-weight:700;color:#0B2447;border:1px solid #e2e8f0}
td{padding:10px 14px;border:1px solid #e2e8f0;vertical-align:top;color:#334155}
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700}
.badge-essential{background:#dcfce7;color:#166534}
.badge-functional{background:#dbeafe;color:#1e40af}
.badge-analytics{background:#fef3c7;color:#92400e}
.badge-third{background:#f3e8ff;color:#6b21a8}
.info-box{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:18px 20px;margin:24px 0}
.info-box strong{color:#166534}
.footer-nav{border-top:1px solid #e2e8f0;padding:32px 24px;display:flex;flex-wrap:wrap;gap:16px;justify-content:center;font-size:13px}
.footer-nav a{color:#475569;text-decoration:none}
.footer-nav a:hover{color:#0F6B3E}
</style>
</head>
<body>

<nav class="nav">
    <a href="{{ route('welcome') }}" class="nav-brand">🌿 MSAS FarmAI</a>
    <div class="nav-links">
        <a href="{{ route('legal.privacy') }}">Privacy</a>
        <a href="{{ route('legal.terms') }}">Terms</a>
        <a href="{{ route('legal.help') }}">Help</a>
    </div>
</nav>

<div class="hero">
    <h1>Cookie Policy</h1>
    <p>How MSAS FarmAI uses cookies and similar technologies — and the choices available to you.</p>
</div>

<div class="container">
    <p class="updated">Last updated: 31 July 2026 &nbsp;·&nbsp; Effective: 1 August 2026</p>

    <p>
        This Cookie Policy explains what cookies are, how MSAS Agricultural Management and Support System
        ("MSAS", "we", "us") uses them on the <strong>msas.farm</strong> platform, and the options you
        have to control them. Reading this Policy alongside our
        <a href="{{ route('legal.privacy') }}" style="color:#0F6B3E">Privacy Policy</a> will give you
        a complete picture of how we handle your data.
    </p>

    <h2>1. What Are Cookies?</h2>
    <p>
        Cookies are small text files that a website stores on your device when you visit it. They help
        the site remember your preferences, keep you logged in, and understand how the site is being used.
        We also use similar technologies such as browser localStorage for client-side language preferences.
    </p>

    <h2>2. Why We Use Cookies</h2>
    <p>MSAS FarmAI uses cookies for three purposes:</p>
    <ul>
        <li><strong>Security &amp; authentication</strong> — keeping your session secure and preventing cross-site request forgery (CSRF).</li>
        <li><strong>Preferences</strong> — remembering your language choice (Hausa, Yorùbá, Igbo, Fulfulde, Français, العربية, or English).</li>
        <li><strong>Monitoring</strong> — detecting and diagnosing technical errors to keep the platform stable.</li>
    </ul>
    <p>We do <strong>not</strong> use advertising, tracking, or profiling cookies.</p>

    <h2>3. Cookies We Use</h2>

    <table>
        <thead>
            <tr>
                <th>Cookie / Technology</th>
                <th>Type</th>
                <th>Purpose</th>
                <th>Duration</th>
                <th>Set by</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>msas_session</code></td>
                <td><span class="badge badge-essential">Essential</span></td>
                <td>Maintains your authenticated login session. Without this cookie you cannot stay logged in.</td>
                <td>Session (or up to 120 minutes of inactivity)</td>
                <td>MSAS</td>
            </tr>
            <tr>
                <td><code>XSRF-TOKEN</code></td>
                <td><span class="badge badge-essential">Essential</span></td>
                <td>Prevents cross-site request forgery attacks. Required for all form submissions.</td>
                <td>Session</td>
                <td>MSAS</td>
            </tr>
            <tr>
                <td><code>locale</code> (session)</td>
                <td><span class="badge badge-functional">Functional</span></td>
                <td>Stores your language preference for the current visit so pages render in your chosen language.</td>
                <td>Session</td>
                <td>MSAS</td>
            </tr>
            <tr>
                <td>Language preference (DB)</td>
                <td><span class="badge badge-functional">Functional</span></td>
                <td>If you are logged in, your chosen language is saved to your account profile so it persists across devices and logins.</td>
                <td>Until you change it</td>
                <td>MSAS</td>
            </tr>
            <tr>
                <td>Sentry session replay / error trace</td>
                <td><span class="badge badge-analytics">Monitoring</span></td>
                <td>Captures anonymous technical error context (stack traces, browser version, page URL) to help us fix bugs. No personal data is collected. Enabled only when <code>SENTRY_LARAVEL_DSN</code> is configured.</td>
                <td>Session</td>
                <td>Sentry.io</td>
            </tr>
            <tr>
                <td>Paystack <code>__paystack_*</code></td>
                <td><span class="badge badge-third">Third-party</span></td>
                <td>Set by Paystack when you make a payment. Used by Paystack to detect fraud and complete transactions securely. Governed by <a href="https://paystack.com/privacy" target="_blank" rel="noopener" style="color:#0F6B3E">Paystack's Privacy Policy</a>.</td>
                <td>Up to 30 days</td>
                <td>Paystack</td>
            </tr>
        </tbody>
    </table>

    <div class="info-box">
        <strong>No advertising cookies.</strong> MSAS FarmAI does not use Google Ads, Facebook Pixel,
        or any behavioural advertising technology. We do not sell your data.
    </div>

    <h2>4. Essential Cookies — No Opt-Out</h2>
    <p>
        The session and CSRF cookies are strictly necessary for the platform to function. Without them
        you cannot log in, submit forms, or use any feature of MSAS FarmAI. Because they are essential,
        they do not require your consent under applicable law, and there is no opt-out option for them.
    </p>

    <h2>5. Functional Cookies — Your Choice</h2>
    <p>
        The language preference cookie is set automatically when you choose a language from the switcher.
        If you prefer, you can clear it by deleting your browser cookies — your next visit will default
        to English (or your browser's detected language).
    </p>

    <h2>6. Monitoring Cookies — Your Choice</h2>
    <p>
        Error monitoring via Sentry helps us fix bugs faster. Sentry operates under a data processing
        agreement with MSAS and is configured to <strong>not send personally identifiable information</strong>
        (PII is explicitly disabled in our configuration). If you wish to prevent Sentry from running,
        you can use a browser extension such as uBlock Origin or disable JavaScript for the monitoring
        script domain (<code>browser.sentry-cdn.com</code>).
    </p>

    <h2>7. Third-Party Cookies — Paystack</h2>
    <p>
        When you make a payment on MSAS FarmAI, Paystack (our payment processor) may set cookies to
        authenticate and secure the transaction. These cookies are subject to
        <a href="https://paystack.com/privacy" target="_blank" rel="noopener" style="color:#0F6B3E">Paystack's Privacy Policy</a>.
        We have no control over the cookies set by Paystack.
    </p>

    <h2>8. How to Manage Cookies</h2>
    <p>You can control cookies through your browser settings:</p>
    <ul>
        <li><strong>Chrome:</strong> Settings → Privacy and security → Cookies and other site data</li>
        <li><strong>Firefox:</strong> Settings → Privacy &amp; Security → Cookies and Site Data</li>
        <li><strong>Safari:</strong> Preferences → Privacy → Manage Website Data</li>
        <li><strong>Edge:</strong> Settings → Privacy, search, and services → Cookies</li>
    </ul>
    <p>
        Note that blocking essential cookies will prevent you from logging in and using MSAS FarmAI.
        Blocking functional cookies means we cannot remember your language preference between pages.
    </p>

    <h2>9. Changes to This Policy</h2>
    <p>
        We may update this Cookie Policy when we introduce new technologies or change how we use existing
        ones. Material changes will be announced via the platform notification system at least 14 days
        before they take effect. The "Last updated" date at the top of this page always reflects the
        current version.
    </p>

    <h2>10. Contact Us</h2>
    <p>
        For questions about this Cookie Policy or our data practices, contact our Privacy Team:
    </p>
    <ul>
        <li>Email: <a href="mailto:privacy@msas.farm" style="color:#0F6B3E">privacy@msas.farm</a></li>
        <li>Help Centre: <a href="{{ route('legal.help') }}" style="color:#0F6B3E">msas.farm/help</a></li>
    </ul>
</div>

<div class="footer-nav">
    <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
    <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a>
    <a href="{{ route('legal.refund') }}">Refund Policy</a>
    <a href="{{ route('legal.faq') }}">FAQ</a>
    <a href="{{ route('legal.help') }}">Help Centre</a>
    <a href="{{ route('welcome') }}">← Back to MSAS FarmAI</a>
</div>

</body>
</html>
