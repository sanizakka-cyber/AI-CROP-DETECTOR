<x-guest-layout>

    @if ($errors->any())
    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;margin-bottom:20px;">
        <svg width="16" height="16" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span style="color:#dc2626;font-size:13px;font-weight:600;">{{ $errors->first() }}</span>
    </div>
    @endif

    <div style="width:56px;height:56px;background:linear-gradient(135deg,#0F6B3E,#1FA84A);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;box-shadow:0 4px 14px rgba(15,107,62,0.25);">
        <svg width="26" height="26" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
    </div>

    <h2 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:800;color:#0f172a;margin:0 0 6px;">Create New Password</h2>
    <p style="font-size:13px;color:#64748b;margin:0 0 22px;">Choose a strong password for your account.</p>

    <form method="POST" action="{{ route('password.store') }}" autocomplete="off" id="reset-form">
        @csrf

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;">New Password</label>
            <div style="position:relative;">
                <div style="position:absolute;left:13px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="15" height="15" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
                <input id="password" type="password" name="password" required
                    class="form-input" style="padding-left:40px;padding-right:44px;"
                    placeholder="Minimum 8 characters" autocomplete="new-password"
                    oninput="checkStrength(this.value)" />
                <button type="button" onclick="togglePwd('password','e1s','e1h')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;">
                    <svg id="e1s" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg id="e1h" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            <div style="margin-top:8px;"><div style="height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden;"><div id="strength-bar" style="height:100%;width:0;transition:all .3s;border-radius:2px;"></div></div><p id="strength-label" style="font-size:11px;font-weight:600;margin:4px 0 0;color:#94a3b8;"></p></div>
            <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">Uppercase · Lowercase · Number · Special character</p>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;">Confirm Password</label>
            <div style="position:relative;">
                <div style="position:absolute;left:13px;top:50%;transform:translateY(-50%);pointer-events:none;"><svg width="15" height="15" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    class="form-input" style="padding-left:40px;padding-right:44px;"
                    placeholder="Re-enter your new password" autocomplete="new-password" />
                <button type="button" onclick="togglePwd('password_confirmation','e2s','e2h')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;">
                    <svg id="e2s" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg id="e2h" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary" id="save-btn">
            <span style="display:flex;align-items:center;justify-content:center;gap:8px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Save New Password
            </span>
        </button>
    </form>

    <script>
    function togglePwd(id,sId,hId){var p=document.getElementById(id),s=document.getElementById(sId),h=document.getElementById(hId);if(p.type==='password'){p.type='text';s.style.display='none';h.style.display='block';}else{p.type='password';s.style.display='block';h.style.display='none';}}
    function checkStrength(v){var bar=document.getElementById('strength-bar'),lbl=document.getElementById('strength-label');if(!v){bar.style.width='0';lbl.textContent='';return;}var s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[a-z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;var l=[{w:'20%',c:'#dc2626',t:'Weak'},{w:'40%',c:'#f97316',t:'Fair'},{w:'60%',c:'#eab308',t:'Good'},{w:'80%',c:'#22c55e',t:'Strong'},{w:'100%',c:'#16a34a',t:'Very Strong'}][Math.max(0,s-1)];bar.style.width=l.w;bar.style.background=l.c;lbl.textContent=l.t;lbl.style.color=l.c;}
    document.getElementById('reset-form').addEventListener('submit',function(){var b=document.getElementById('save-btn');b.disabled=true;b.innerHTML='<span style="display:flex;align-items:center;justify-content:center;gap:8px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Saving…</span>';});
    </script>
    <style>@keyframes spin{to{transform:rotate(360deg);}}</style>

</x-guest-layout>
