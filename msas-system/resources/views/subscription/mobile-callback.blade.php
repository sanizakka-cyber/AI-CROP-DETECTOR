<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $success ? 'Payment Confirmed' : 'Payment Issue' }} — MSAS FarmAI</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: #0B2447; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    padding: 24px;
  }
  .card {
    background: #ffffff; border-radius: 20px; padding: 36px 28px; max-width: 380px; width: 100%;
    text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.35);
  }
  .icon {
    width: 68px; height: 68px; border-radius: 50%; margin: 0 auto 18px;
    display: flex; align-items: center; justify-content: center; font-size: 34px;
    background: {{ $success ? '#DCFCE7' : '#FEE2E2' }};
  }
  h1 { font-size: 19px; font-weight: 800; color: #0F172A; margin: 0 0 8px; }
  p { font-size: 14px; color: #475569; line-height: 1.5; margin: 0 0 22px; }
  .hint {
    font-size: 12px; color: #94A3B8; padding-top: 16px; border-top: 1px solid #E2E8F0;
  }
</style>
</head>
<body>
  <div class="card">
    <div class="icon">{{ $success ? '✅' : '⚠️' }}</div>
    <h1>{{ $success ? 'Payment Confirmed' : 'Payment Not Completed' }}</h1>
    <p>{{ $message }}</p>
    <div class="hint">You can close this window and return to the MSAS FarmAI app.</div>
  </div>
</body>
</html>
