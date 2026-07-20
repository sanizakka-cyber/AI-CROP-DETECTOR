<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted — MSAS FarmAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: linear-gradient(135deg, #0B2447 0%, #0F6B3E 100%); min-height: 100vh; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
        {{-- Header --}}
        <div style="background:linear-gradient(135deg,#0B2447,#0F6B3E)" class="px-8 py-6 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/15 mb-3">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-white text-xl font-extrabold">MSAS FarmAI</h1>
            <p class="text-white/70 text-sm mt-1">Livestock &amp; Agro Services Platform</p>
        </div>

        {{-- Body --}}
        <div class="px-8 py-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-800">Application Submitted!</h2>
                <p class="text-slate-500 text-sm mt-1">We've received your registration</p>
            </div>

            <p class="text-slate-600 text-sm leading-relaxed mb-6">
                Thank you for applying to join MSAS FarmAI. Your application and uploaded documents are now
                under review by our administration team. You will receive an email notification once a
                decision has been made.
            </p>

            {{-- Steps --}}
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-green-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">✓</div>
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Application Submitted</p>
                        <p class="text-xs text-slate-500">Your registration and documents have been received.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-amber-400 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</div>
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Document Review</p>
                        <p class="text-xs text-slate-500">Our team will verify your credentials and qualifications.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-slate-300 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</div>
                    <div>
                        <p class="text-sm font-semibold text-slate-400">Account Activation</p>
                        <p class="text-xs text-slate-400">Once approved, you'll receive an email to log in.</p>
                    </div>
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                <p class="text-sm text-amber-800">
                    <span class="font-bold">Estimated review time:</span> 1–3 business days.
                    We may contact you if additional information is needed.
                </p>
            </div>

            <div class="text-center">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-green-700 hover:text-green-900 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Return to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
