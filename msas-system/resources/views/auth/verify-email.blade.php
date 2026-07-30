<x-guest-layout>
    {{-- Icon + Heading --}}
    <div style="text-align:center; margin-bottom:24px;">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:16px;margin-bottom:14px;background:linear-gradient(135deg,#0F6B3E,#1FA84A);">
            <svg style="width:28px;height:28px;color:#fff;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <div style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin-bottom:4px;">Verify Your Email</div>
        <p style="font-size:13px;color:#64748b;margin:0;">Check your inbox for a verification link</p>
    </div>

    {{-- Success status --}}
    @if (session('status') == 'verification-link-sent')
        <div style="display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;margin-bottom:20px;">
            <svg style="width:18px;height:18px;color:#16a34a;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p style="font-size:13px;color:#15803d;font-weight:600;margin:0;">{{ __('A new verification link has been sent to your email address.') }}</p>
        </div>
    @endif

    {{-- Instructions --}}
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:24px;">
        <p style="font-size:13px;color:#475569;line-height:1.75;margin:0;">
            {{ __("Thanks for signing up! Before getting started, please verify your email address by clicking the link we sent you. If you didn't receive it, we'll gladly send another.") }}
        </p>
    </div>

    {{-- Resend button --}}
    <form method="POST" action="{{ route('verification.send') }}" style="margin-bottom:16px;">
        @csrf
        <button type="submit" class="btn-primary">
            {{ __('Resend Verification Email') }}
        </button>
    </form>

    {{-- Log out --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <div style="text-align:center;">
            <button type="submit"
                style="background:none;border:none;cursor:pointer;font-size:13px;color:#64748b;font-weight:500;text-decoration:underline;padding:4px 8px;border-radius:6px;transition:color 0.15s;"
                onmouseover="this.style.color='#1e293b'" onmouseout="this.style.color='#64748b'">
                {{ __('Log Out') }}
            </button>
        </div>
    </form>
</x-guest-layout>
