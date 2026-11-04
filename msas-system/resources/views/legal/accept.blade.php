<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('Updated Terms and Privacy Policy') }} — MSAS FarmAI</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#f8fafc;color:#0f172a;font-size:15px;line-height:1.6;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;max-width:520px;width:100%;padding:28px 26px;box-shadow:0 10px 30px rgba(15,23,42,.06)}
.brand{font-weight:800;color:#0F6B3E;font-size:16px;margin-bottom:14px}
h1{font-size:20px;font-weight:800;margin-bottom:10px}
p{font-size:14px;color:#475569;margin-bottom:12px}
.links{display:flex;gap:14px;flex-wrap:wrap;margin:6px 0 16px}
.links a{color:#0F6B3E;font-weight:700;font-size:14px}
.check{display:flex;gap:10px;align-items:flex-start;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px;font-size:13px;color:#334155;cursor:pointer}
.check input{width:18px;height:18px;accent-color:#0F6B3E;margin-top:1px;flex-shrink:0}
.err{color:#dc2626;font-size:12px;margin-top:8px}
.btn{display:block;width:100%;margin-top:16px;background:#0F6B3E;color:#fff;border:none;border-radius:10px;padding:12px;font-size:15px;font-weight:700;cursor:pointer}
.btn[disabled]{opacity:.55;cursor:not-allowed}
.out{display:block;text-align:center;margin-top:12px;font-size:12px;color:#64748b;background:none;border:none;cursor:pointer;width:100%}
</style>
</head>
<body>
<main class="card">
    <div class="brand">MSAS FarmAI</div>
    <h1>{{ __('We have updated our Terms and Privacy Policy') }}</h1>
    <p>{{ __('Please read and accept them to keep using MSAS FarmAI. They explain how the service works, that subscription payments are non-refundable, that users must be 18 or older, and how we protect your personal data.') }}</p>
    <div class="links">
        <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">{{ __('Terms of Service') }}</a>
        <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">{{ __('Privacy Policy') }}</a>
        <a href="{{ route('legal.refund') }}" target="_blank" rel="noopener">{{ __('Refund Policy') }}</a>
    </div>
    <form method="POST" action="{{ route('legal.accept.store') }}">
        @csrf
        <label class="check" for="terms_accepted">
            <input type="checkbox" id="terms_accepted" name="terms_accepted" value="1" required
                onchange="document.getElementById('accept-btn').disabled = !this.checked">
            <span>{{ __('I agree to the Terms of Service and Privacy Policy.') }}</span>
        </label>
        @error('terms_accepted')<div class="err" role="alert">{{ $message }}</div>@enderror
        <button type="submit" class="btn" id="accept-btn" disabled>{{ __('Accept and continue') }}</button>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="out">{{ __('Not now — sign out') }}</button>
    </form>
</main>
</body>
</html>
