<x-guest-layout>

    @if ($errors->any())
    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;margin-bottom:20px;">
        <svg width="16" height="16" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span style="color:#dc2626;font-size:13px;font-weight:600;">{{ $errors->first() }}</span>
    </div>
    @endif

    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- Icon --}}
    <div style="width:56px;height:56px;background:linear-gradient(135deg,#0F6B3E,#1FA84A);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;box-shadow:0 4px 14px rgba(15,107,62,0.25);">
        <svg width="26" height="26" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
    </div>

    <h2 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:800;color:#0f172a;margin:0 0 6px;">Reset your password</h2>
    <p style="font-size:13px;color:#64748b;margin:0 0 22px;line-height:1.6;">
        Enter your email address or phone number and we'll send a 6-digit verification code to reset your password.
    </p>

    <form method="POST" action="{{ route('password.email') }}" autocomplete="off" id="forgot-form">
        @csrf

        <div style="margin-bottom:18px;">
            <label for="identifier" style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;">Email or Phone Number</label>
            <div style="position:relative;">
                <div style="position:absolute;left:13px;top:50%;transform:translateY(-50%);pointer-events:none;">
                    <svg width="15" height="15" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <input id="identifier" type="text" name="identifier"
                    class="form-input" style="padding-left:40px;"
                    placeholder="Enter your email address or phone number"
                    value="{{ old('identifier') }}"
                    required autofocus autocomplete="off" />
            </div>
        </div>

        <button type="submit" class="btn-primary" id="reset-btn">
            <span style="display:flex;align-items:center;justify-content:center;gap:8px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Send Verification Code
            </span>
        </button>

        <div style="text-align:center;margin-top:18px;padding-top:14px;border-top:1px solid #f1f5f9;">
            <a href="{{ route('login') }}" style="font-size:13px;color:#0F6B3E;font-weight:700;text-decoration:none;">← Back to Sign In</a>
        </div>
    </form>

    <script>
    document.getElementById('forgot-form').addEventListener('submit', function() {
        var btn = document.getElementById('reset-btn');
        btn.disabled = true;
        btn.innerHTML = '<span style="display:flex;align-items:center;justify-content:center;gap:8px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Sending…</span>';
    });
    </script>
    <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

</x-guest-layout>
