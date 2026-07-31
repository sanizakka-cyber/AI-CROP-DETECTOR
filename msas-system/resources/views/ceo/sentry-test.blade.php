<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sentry Test — MSAS FarmAI</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; color: #0f172a; padding: 60px 24px; max-width: 600px; margin: 0 auto; }
        h1 { font-size: 22px; font-weight: 800; margin-bottom: 8px; }
        .badge { display: inline-block; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 6px; padding: 3px 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 20px; }
        p { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 16px; }
        .checklist { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin-bottom: 28px; }
        .checklist li { font-size: 13px; color: #334155; padding: 5px 0; list-style: none; }
        .checklist li::before { content: '□ '; color: #94a3b8; }
        form button { background: #dc2626; color: #fff; border: none; border-radius: 8px; padding: 14px 28px; font-size: 15px; font-weight: 700; cursor: pointer; width: 100%; }
        form button:hover { background: #b91c1c; }
        .warning { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #991b1b; margin-top: 20px; font-weight: 600; }
        .back { display: inline-block; margin-top: 24px; font-size: 13px; color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="badge">⚠ Temporary Diagnostic — CEO Only</div>
    <h1>Sentry Connectivity Test</h1>
    <p>This page throws a controlled test exception so you can verify Sentry is capturing production errors correctly.</p>

    <div class="checklist">
        <strong style="font-size:13px;font-weight:700;color:#0f172a">After clicking the button, verify in Sentry:</strong>
        <ul style="margin-top:12px">
            <li>Exception appears in your Sentry project inbox</li>
            <li>Environment is set to <code>production</code></li>
            <li>Release is set to <code>v1.0.0</code></li>
            <li>User context is attached (your CEO account)</li>
            <li>Full PHP stack trace is visible</li>
            <li>Server name / hostname is correct</li>
        </ul>
    </div>

    @if(session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;padding:12px 16px;border-radius:8px;font-size:13px;color:#991b1b;margin-bottom:20px">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('ceo.sentry.test') }}"
          onsubmit="return confirm('This will intentionally throw an exception to test Sentry. Proceed?')">
        @csrf
        <button type="submit">🚨 Throw Test Exception Now</button>
    </form>

    <div class="warning">
        ⚠ Remove this route and view from the codebase immediately after Sentry is confirmed working.<br>
        Delete: <code>routes/web.php</code> (the TEMPORARY block) and <code>resources/views/ceo/sentry-test.blade.php</code>
    </div>

    <a href="{{ route('ceo.health') }}" class="back">← Back to System Health</a>
</body>
</html>
