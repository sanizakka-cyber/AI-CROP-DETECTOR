<x-guest-layout>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;margin-bottom:20px;">
        <svg width="16" height="16" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span style="color:#dc2626;font-size:13px;font-weight:600;">{{ $errors->first() }}</span>
    </div>
    @endif

    <!-- Page heading -->
    <div class="fade-up-2" style="margin-bottom:24px;">
        <h2 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:800;color:#0f172a;margin:0 0 5px 0;letter-spacing:-0.3px;">Welcome back</h2>
        <p style="font-size:13px;color:#64748b;margin:0;">Sign in to your MSAS portal account</p>
    </div>

    <form method="POST" action="{{ route('login') }}" autocomplete="off" id="login-form">
        @csrf

        <!-- Email / Phone -->
        <div class="fade-up-3" style="margin-bottom:16px;">
            <label for="login" class="field-label">Email or Phone Number</label>
            <div style="position:relative;">
                <div style="position:absolute;left:13px;top:14px;pointer-events:none;z-index:1;">
                    <svg width="15" height="15" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <input id="login" class="form-input" style="padding-left:40px;"
                    type="text" name="login"
                    value="" required autofocus
                    autocomplete="off" readonly placeholder=""
                    onfocus="loginFocus(this)" onclick="loginFocus(this)" oninput="hideSuggestions()"/>

                <!-- ── Saved-account suggestion dropdown ────────── -->
                <div id="login-suggestions"
                     style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.09);z-index:200;overflow:hidden;">
                    <div style="padding:7px 12px 5px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                        <span>Recent accounts</span>
                        <button type="button" onclick="clearSavedAccounts()" style="background:none;border:none;color:#94a3b8;font-size:10px;font-weight:600;cursor:pointer;padding:0;">Clear all</button>
                    </div>
                    <div id="login-suggestion-items"></div>
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="fade-up-3" style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <label for="password" class="field-label" style="margin-bottom:0;">Password</label>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="font-size:12px;color:#0F6B3E;text-decoration:none;font-weight:600;">Forgot password?</a>
                @endif
            </div>
            <div style="position:relative;">
                <div style="position:absolute;left:13px;top:50%;transform:translateY(-50%);pointer-events:none;">
                    <svg width="15" height="15" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                </div>
                <input id="password" class="form-input" style="padding-left:40px;padding-right:44px;"
                    type="password" name="password" required
                    autocomplete="new-password" readonly placeholder=""
                    onfocus="this.removeAttribute('readonly')" onclick="this.removeAttribute('readonly')"/>
                <button type="button" onclick="togglePwd()" title="Show/hide password"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;">
                    <svg id="eye-show" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg id="eye-hide" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>

        <!-- Remember me -->
        <div class="fade-up-4" style="margin-bottom:22px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none;">
                <input id="remember_me" type="checkbox" name="remember"
                    style="width:16px;height:16px;border-radius:4px;accent-color:#0F6B3E;cursor:pointer;">
                <span style="font-size:13px;color:#475569;font-weight:500;">Keep me signed in</span>
            </label>
        </div>

        <!-- Submit -->
        <div class="fade-up-4" style="margin-bottom:18px;">
            <button type="submit" class="btn-primary">
                <span style="display:flex;align-items:center;justify-content:center;gap:8px;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Sign In Securely
                </span>
            </button>
        </div>

        <!-- Sign up link -->
        <div class="fade-up-5" style="text-align:center;padding-top:16px;border-top:1px solid #f1f5f9;">
            <span style="font-size:13px;color:#94a3b8;">Don't have an account?</span>
            <a href="{{ route('register') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;margin-left:5px;">Create Account</a>
        </div>
    </form>

    <script>
    // ── Password show/hide ─────────────────────────────
    function togglePwd() {
        var p = document.getElementById('password');
        var s = document.getElementById('eye-show');
        var h = document.getElementById('eye-hide');
        if (p.type === 'password') { p.type = 'text'; s.style.display = 'none'; h.style.display = 'block'; }
        else { p.type = 'password'; s.style.display = 'block'; h.style.display = 'none'; }
    }

    // ── Saved account suggestions ───────────────────────
    var STORAGE_KEY = 'msas_saved_accounts';

    function getSavedAccounts() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
        catch (e) { return []; }
    }

    function saveAccount(identifier) {
        if (!identifier) return;
        var accounts = getSavedAccounts();
        accounts = [identifier].concat(accounts.filter(function(a){ return a !== identifier; })).slice(0, 5);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(accounts));
    }

    function clearSavedAccounts() {
        localStorage.removeItem(STORAGE_KEY);
        hideSuggestions();
    }

    function renderSuggestions(accounts) {
        var items = document.getElementById('login-suggestion-items');
        if (!items) return;
        items.innerHTML = accounts.map(function(a) {
            var safe = a.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            return '<div onclick="selectAccount(\'' + safe.replace(/'/g, "\\'") + '\')"'
                + ' style="padding:10px 14px;cursor:pointer;display:flex;align-items:center;gap:10px;font-size:13px;color:#374151;font-weight:500;border-bottom:1px solid #f8fafc;transition:background 0.1s;"'
                + ' onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'\'">'
                + '<svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'
                + '<span style="flex:1;">' + safe + '</span>'
                + '<svg width="12" height="12" fill="none" stroke="#cbd5e1" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>'
                + '</div>';
        }).join('');
    }

    function loginFocus(input) {
        input.removeAttribute('readonly');
        var accounts = getSavedAccounts();
        if (accounts.length > 0) {
            renderSuggestions(accounts);
            document.getElementById('login-suggestions').style.display = 'block';
        }
    }

    function hideSuggestions() {
        var el = document.getElementById('login-suggestions');
        if (el) el.style.display = 'none';
    }

    function selectAccount(val) {
        document.getElementById('login').value = val;
        hideSuggestions();
        document.getElementById('password').removeAttribute('readonly');
        document.getElementById('password').focus();
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        var field = document.getElementById('login');
        var dropdown = document.getElementById('login-suggestions');
        if (field && dropdown && !field.contains(e.target) && !dropdown.contains(e.target)) {
            hideSuggestions();
        }
    });

    // Save account on form submit when "Keep me signed in" is checked
    document.getElementById('login-form').addEventListener('submit', function() {
        var rememberMe = document.getElementById('remember_me');
        if (rememberMe && rememberMe.checked) {
            var val = document.getElementById('login').value.trim();
            if (val) saveAccount(val);
        }
    });
    </script>

</x-guest-layout>
