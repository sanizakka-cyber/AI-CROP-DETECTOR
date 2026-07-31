<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FAQ — MSAS FarmAI</title>
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
.hero p{font-size:14px;opacity:.8}
.search-wrap{max-width:520px;margin:24px auto 0;position:relative}
.search-wrap input{width:100%;padding:12px 18px 12px 44px;border-radius:12px;border:none;font-size:15px;color:#0f172a;outline:none}
.search-wrap svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8}
.container{max-width:780px;margin:0 auto;padding:48px 24px 80px}
.category{margin-bottom:36px}
.category-title{font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0F6B3E;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.faq-item{background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:10px;overflow:hidden}
.faq-q{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;cursor:pointer;font-size:14px;font-weight:600;color:#0f172a;user-select:none;gap:12px}
.faq-q:hover{background:#f8fafc}
.faq-q svg{flex-shrink:0;transition:transform .2s;color:#0F6B3E}
.faq-item.open .faq-q svg{transform:rotate(180deg)}
.faq-a{display:none;padding:0 20px 16px;font-size:14px;color:#475569;line-height:1.8}
.faq-a ul{padding-left:20px;margin:8px 0}
.faq-a li{margin-bottom:4px}
.faq-a a{color:#0F6B3E;font-weight:600}
.faq-item.open .faq-a{display:block}
.no-results{text-align:center;padding:40px;color:#94a3b8;font-size:14px;display:none}
.cta{background:linear-gradient(135deg,#0F6B3E,#1FA84A);border-radius:14px;padding:32px;text-align:center;margin-top:48px}
.cta h3{color:#fff;font-size:18px;font-weight:800;margin-bottom:8px}
.cta p{color:rgba(255,255,255,.85);font-size:14px;margin-bottom:20px}
.cta a{display:inline-block;background:#fff;color:#0F6B3E;font-weight:800;font-size:14px;padding:10px 24px;border-radius:8px;text-decoration:none}
.cta a:hover{background:#f0fdf4}
footer{text-align:center;padding:24px;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0}
@media(max-width:600px){.nav-links{display:none}}
</style>
</head>
<body>

<nav class="nav">
    <a href="{{ url('/') }}" class="nav-brand">MSAS FarmAI</a>
    <div class="nav-links">
        <a href="{{ route('legal.help') }}">Help Centre</a>
        <a href="{{ route('legal.privacy') }}">Privacy</a>
        <a href="{{ route('legal.terms') }}">Terms</a>
        <a href="{{ route('login') }}">Log in</a>
        <a href="{{ route('register') }}">Sign up</a>
    </div>
</nav>

<div class="hero">
    <h1>Frequently Asked Questions</h1>
    <p>Quick answers to the questions we hear most often</p>
    <div class="search-wrap">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" id="faq-search" placeholder="Search questions…" autocomplete="off">
    </div>
</div>

<div class="container">

    <div class="no-results" id="no-results">No questions match your search. Try different keywords or <a href="{{ route('legal.help') }}" style="color:#0F6B3E;">contact support</a>.</div>

    {{-- Getting Started --}}
    <div class="category" data-cat>
        <div class="category-title">🌱 Getting Started</div>

        <div class="faq-item">
            <div class="faq-q">What is MSAS FarmAI? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">MSAS FarmAI is a Nigerian agricultural technology platform that gives farmers, veterinarians, agronomists, and agro-dealers AI-powered tools for crop and livestock diagnosis, marketplace access, expert consultations, logistics, and farm financial management — all in one place and available in multiple Nigerian languages.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">Who can use MSAS FarmAI? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">
                MSAS FarmAI is open to farmers, livestock farmers, poultry farmers, veterinarians, agronomists, agro-dealers, equipment dealers, cooperatives, NGOs, government agencies, research institutions, logistics providers, and investors — across Nigeria and beyond.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-q">Is there a free trial? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">Yes. All new farmer accounts come with a <strong>14-day free trial</strong> on the Pro plan. No credit card is required to sign up. You can explore all Pro features during the trial and decide whether to continue with a paid plan afterward.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">How do I create an account? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">
                <ul>
                    <li>Go to <a href="{{ route('register') }}">msasagro.com/register</a></li>
                    <li>Enter your name, email or phone number, and choose a strong password</li>
                    <li>Select your role (farmer, vet, agro-dealer, etc.)</li>
                    <li>Verify your email or phone with the 6-digit code we send</li>
                    <li>You're in — no approval required for farmers and general users</li>
                </ul>
                Expert roles (vet, agronomist) require document upload and team review, which usually takes 1–2 business days.
            </div>
        </div>
    </div>

    {{-- AI Smart Scan --}}
    <div class="category" data-cat>
        <div class="category-title">🤖 AI Smart Scan</div>

        <div class="faq-item">
            <div class="faq-q">How accurate is the AI diagnosis? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">Our AI engine is powered by Claude (Anthropic) and trained to identify common Nigerian crop diseases, livestock conditions, and soil deficiencies. Accuracy is highest when you upload a <strong>clear, well-lit, close-up photo</strong> of the affected area. The AI shows a confidence percentage with each diagnosis — for anything below 80%, we recommend confirming with a certified vet or agronomist before applying treatment.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">What types of scans are supported? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">
                <ul>
                    <li><strong>Crop / Plant</strong> — disease, pest infestation, nutrient deficiency</li>
                    <li><strong>Livestock / Animal</strong> — visible symptoms, skin conditions, behavioural signs</li>
                    <li><strong>Soil</strong> — visual soil texture and colour analysis (for basic deficiency indicators)</li>
                </ul>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-q">Can I use the AI scan in Hausa, Yoruba, or Igbo? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">Yes. The full platform — including AI scan results and voice narration — is available in English, Hausa, Yoruba, Igbo, Fulfulde, and French. Switch your language from the top navigation bar at any time.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">How many scans can I run? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">Basic plan users get <strong>10 AI scans per month</strong>. Pro and Enterprise users get <strong>unlimited scans</strong>. Scan history is saved permanently so you can track disease trends over time.</div>
        </div>
    </div>

    {{-- Subscriptions & Billing --}}
    <div class="category" data-cat>
        <div class="category-title">💳 Subscriptions &amp; Billing</div>

        <div class="faq-item">
            <div class="faq-q">What plans are available? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">
                <ul>
                    <li><strong>Basic</strong> — core features, 10 AI scans/month, marketplace access</li>
                    <li><strong>Pro</strong> — unlimited AI scans, vet consultations, advanced analytics, full marketplace</li>
                    <li><strong>Enterprise</strong> — multi-user, API access, dedicated support, custom integrations</li>
                </ul>
                Visit <a href="{{ route('register') }}?plan=pro">our pricing page</a> for current rates.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-q">How do I cancel my subscription? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">Go to <strong>My Profile → Manage Plan → Cancel Subscription</strong>. You will continue to have access until the end of your current billing period. We do not charge cancellation fees.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">What payment methods are accepted? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">We accept all major debit and credit cards (Visa, Mastercard, Verve) via Paystack. Bank transfers are also supported. All payments are processed securely — we do not store your card details.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">Can I get a refund? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">Yes. See our full <a href="{{ route('legal.refund') }}">Refund Policy</a>. In summary: cancellations within 7 days of first charge receive a full cash refund; later cancellations receive pro-rata wallet credit.</div>
        </div>
    </div>

    {{-- Account & Security --}}
    <div class="category" data-cat>
        <div class="category-title">🔐 Account &amp; Security</div>

        <div class="faq-item">
            <div class="faq-q">I forgot my password. How do I reset it? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">Go to the <a href="{{ route('password.request') }}">Forgot Password</a> page, enter your registered email or phone number, and we'll send a 6-digit reset code. Enter the code, then set a new password.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">What is two-factor authentication (2FA)? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">For staff accounts (CEO, Admin, Finance, HR, Operations), MSAS requires a 6-digit security code sent to your email on each new-device login. This protects your account even if your password is stolen. Trusted devices skip 2FA for 30 days.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">How is my farm data protected? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">All data is encrypted in transit (TLS 1.2+) and at rest. We do not sell your data to third parties. Your farm records, animal health data, and financial information are only accessible to you and staff with explicit permission. See our <a href="{{ route('legal.privacy') }}">Privacy Policy</a> for full details.</div>
        </div>
    </div>

    {{-- Marketplace --}}
    <div class="category" data-cat>
        <div class="category-title">🛒 Marketplace</div>

        <div class="faq-item">
            <div class="faq-q">How do I buy agro-inputs on the marketplace? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">Go to the <strong>Marketplace</strong> section, browse products by category, add items to your cart, and pay securely with your card or wallet balance. Delivery is arranged through our logistics network — you can track your order in real time.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">How do I become an agro-dealer on MSAS? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a"><a href="{{ route('register') }}">Register an account</a> and select <strong>Agro-Dealer</strong> or <strong>Equipment Dealer</strong> as your role. Upload your CAC registration certificate. Our team will review and approve your application within 1–2 business days, after which you can list products.</div>
        </div>
    </div>

    {{-- Technical --}}
    <div class="category" data-cat>
        <div class="category-title">⚙️ Technical</div>

        <div class="faq-item">
            <div class="faq-q">Which browsers and devices are supported? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">MSAS FarmAI works on all modern browsers (Chrome, Edge, Firefox, Safari) on desktop and mobile. For the best experience, use the latest version of Chrome or Edge. Voice narration requires a browser that supports the Web Speech API (Chrome and Edge on Android provide the widest language support).</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">Is there a mobile app? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">An Android APK is available for download from our website. The full platform also works as a Progressive Web App (PWA) — open the site in Chrome on Android and tap <em>Add to Home Screen</em> for an app-like experience.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">What should I do if the AI scan fails? <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></div>
            <div class="faq-a">
                <ul>
                    <li>Ensure the photo is clear, well-lit, and focused on the affected area</li>
                    <li>File size must be under 10 MB and in JPG, PNG, or WEBP format</li>
                    <li>Check your internet connection and try again</li>
                    <li>If the problem persists, use the <strong>Re-run Analysis</strong> button or contact support</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="cta">
        <h3>Still have a question?</h3>
        <p>Our support team is available Monday–Friday, 8 AM – 6 PM WAT</p>
        <a href="{{ route('legal.help') }}">Visit Help Centre →</a>
    </div>

</div>

<footer>
    &copy; {{ date('Y') }} MSAS Livestock &amp; Agro Services. All rights reserved. &nbsp;·&nbsp;
    <a href="{{ route('legal.privacy') }}" style="color:#0F6B3E;">Privacy</a> &nbsp;·&nbsp;
    <a href="{{ route('legal.terms') }}" style="color:#0F6B3E;">Terms</a> &nbsp;·&nbsp;
    <a href="{{ route('legal.refund') }}" style="color:#0F6B3E;">Refund Policy</a>
</footer>

<script>
(function(){
    var items = document.querySelectorAll('.faq-item');
    items.forEach(function(item){
        item.querySelector('.faq-q').addEventListener('click', function(){
            var isOpen = item.classList.contains('open');
            items.forEach(function(i){ i.classList.remove('open'); });
            if(!isOpen) item.classList.add('open');
        });
    });

    var searchInput = document.getElementById('faq-search');
    var noResults   = document.getElementById('no-results');
    searchInput.addEventListener('input', function(){
        var q = this.value.toLowerCase().trim();
        var visible = 0;
        document.querySelectorAll('.category').forEach(function(cat){
            var catVisible = 0;
            cat.querySelectorAll('.faq-item').forEach(function(item){
                var text = item.textContent.toLowerCase();
                var show = !q || text.includes(q);
                item.style.display = show ? '' : 'none';
                if(show){ catVisible++; visible++; }
            });
            cat.style.display = catVisible ? '' : 'none';
        });
        noResults.style.display = visible ? 'none' : 'block';
    });
})();
</script>
</body>
</html>
