<x-guest-layout>

@php
$masked = '';
if ($identifier ?? null) {
    if (str_contains($identifier, '@')) {
        [$local, $domain] = explode('@', $identifier, 2);
        $masked = substr($local, 0, 3) . str_repeat('*', max(2, strlen($local) - 3)) . '@' . $domain;
    } else {
        $clean = preg_replace('/\D/', '', $identifier);
        $masked = substr($clean, 0, 3) . str_repeat('*', max(4, strlen($clean) - 6)) . substr($clean, -3);
    }
}
$isEmail = str_contains($identifier ?? '', '@');
@endphp

<style>
.otp-wrap { text-align:center; }
.otp-inputs { display:flex; justify-content:center; gap:10px; margin:24px 0; }
.otp-box {
    width:52px; height:60px;
    border:2px solid #e2e8f0; border-radius:12px;
    font-size:26px; font-weight:800; text-align:center;
    color:#0f172a; background:#f8fafc;
    outline:none; transition:all .18s;
    caret-color: #0F6B3E;
}
.otp-box:focus { border-color:#0F6B3E; background:#fff; box-shadow:0 0 0 3px rgba(15,107,62,.12); }
.otp-box.filled { border-color:#0F6B3E; background:#f0fdf4; }
.otp-box.error-box { border-color:#dc2626; background:#fef2f2; }

.countdown { font-size:13px; color:#64748b; margin-bottom:4px; }
.countdown strong { color:#0F6B3E; font-variant-numeric:tabular-nums; }
.countdown.expired strong { color:#dc2626; }

.resend-btn { background:none; border:none; color:#0F6B3E; font-size:13px; font-weight:700; cursor:pointer; padding:4px 8px; border-radius:6px; transition:background .15s; }
.resend-btn:hover { background:#f0fdf4; }
.resend-btn:disabled { color:#94a3b8; cursor:not-allowed; }

.change-link { font-size:12px; color:#94a3b8; text-decoration:none; display:inline-block; margin-top:12px; }
.change-link:hover { color:#64748b; }
</style>

<div class="otp-wrap">

    {{-- Icon --}}
    <div style="width:60px;height:60px;background:linear-gradient(135deg,#0F6B3E,#1FA84A);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 4px 16px rgba(15,107,62,0.25);">
        @if($isEmail)
        <svg width="28" height="28" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        @else
        <svg width="28" height="28" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        @endif
    </div>

    <h2 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:800;color:#0f172a;margin:0 0 6px;">Verify Your Account</h2>
    <p style="font-size:13px;color:#64748b;margin:0 0 4px;">Enter the 6-digit code sent to</p>
    <p style="font-size:14px;font-weight:700;color:#0f172a;margin:0 0 4px;">{{ $masked }}</p>

    @if(session('status'))
    <div style="margin:12px 0;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;font-size:13px;color:#15803d;font-weight:600;">
        {{ session('status') }}
    </div>
    @endif

    @if($errors->any())
    <div style="margin:12px 0;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:9px;font-size:13px;color:#dc2626;font-weight:600;">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('otp.verify.post') }}" id="otp-form">
        @csrf
        <input type="hidden" name="code" id="otp-hidden">

        <div class="otp-inputs" id="otp-inputs">
            @for($i = 0; $i < 6; $i++)
            <input type="text"
                inputmode="numeric"
                pattern="\d"
                maxlength="1"
                class="otp-box"
                data-index="{{ $i }}"
                autocomplete="one-time-code"
                {{ $i === 0 ? 'autofocus' : '' }} />
            @endfor
        </div>

        {{-- Countdown --}}
        <div class="countdown" id="countdown-wrap">
            Code expires in <strong id="countdown">05:00</strong>
        </div>

        <button type="submit" class="btn-primary" id="verify-btn" style="margin-top:18px;width:100%;" disabled>
            <span style="display:flex;align-items:center;justify-content:center;gap:8px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Verify Code
            </span>
        </button>
    </form>

    <div style="margin-top:16px;">
        <p class="countdown" id="resend-label">Didn't receive the code?</p>
        <form method="POST" action="{{ route('otp.resend') }}" style="display:inline;">
            @csrf
            <button type="submit" class="resend-btn" id="resend-btn" disabled>Resend Code</button>
        </form>
    </div>

    <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;">
        @if($context === 'registration')
            <a href="{{ route('register') }}" class="change-link">
                ← Change email / phone
            </a>
        @else
            <a href="{{ route('password.request') }}" class="change-link">
                ← Try a different email / phone
            </a>
        @endif
    </div>

</div>

<script>
(function () {
    const boxes       = Array.from(document.querySelectorAll('.otp-box'));
    const hiddenInput = document.getElementById('otp-hidden');
    const verifyBtn   = document.getElementById('verify-btn');
    const resendBtn   = document.getElementById('resend-btn');
    const countdownEl = document.getElementById('countdown');
    const countdownWrap = document.getElementById('countdown-wrap');

    // ── OTP input logic ───────────────────────────
    function updateHidden() {
        hiddenInput.value = boxes.map(b => b.value).join('');
        const allFilled = hiddenInput.value.length === 6;
        verifyBtn.disabled = !allFilled;
        boxes.forEach((b, i) => {
            b.classList.toggle('filled', b.value !== '');
        });
    }

    boxes.forEach((box, idx) => {
        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace') {
                if (!box.value && idx > 0) {
                    boxes[idx - 1].value = '';
                    boxes[idx - 1].focus();
                    e.preventDefault();
                }
            }
        });

        box.addEventListener('input', e => {
            // Accept only digits
            box.value = box.value.replace(/\D/g, '').slice(-1);
            if (box.value && idx < boxes.length - 1) {
                boxes[idx + 1].focus();
            }
            updateHidden();
        });

        box.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            if (!pasted) return;
            pasted.split('').slice(0, 6).forEach((ch, i) => {
                if (boxes[i]) boxes[i].value = ch;
            });
            const last = Math.min(pasted.length, 5);
            boxes[last].focus();
            updateHidden();
        });

        box.addEventListener('focus', () => box.select());
    });

    // ── Countdown timer ───────────────────────────
    let seconds = 5 * 60;
    let timerExpired = false;

    function tick() {
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        countdownEl.textContent = m + ':' + s;

        if (seconds <= 0) {
            timerExpired = true;
            countdownEl.textContent = 'Expired';
            countdownWrap.classList.add('expired');
            verifyBtn.disabled = true;
            resendBtn.disabled = false;
            clearInterval(timer);
            return;
        }
        // Enable resend after 30 seconds
        if (seconds <= (5 * 60 - 30)) resendBtn.disabled = false;
        seconds--;
    }

    tick();
    const timer = setInterval(tick, 1000);

    // ── Auto-submit when all 6 filled ─────────────
    document.getElementById('otp-form').addEventListener('submit', function () {
        boxes.forEach(b => b.disabled = true);
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span style="display:flex;align-items:center;justify-content:center;gap:8px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Verifying…</span>';
    });
})();
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }
</style>

</x-guest-layout>
