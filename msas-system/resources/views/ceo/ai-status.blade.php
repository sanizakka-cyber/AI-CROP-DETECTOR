<x-app-layout>
<x-slot name="header">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800">AI Engine Status</h2>
            <p class="text-sm text-slate-500 mt-0.5">Live health check for the MSAS FarmAI inference engine</p>
        </div>
        <a href="{{ route('ceo.ai-status') }}"
           class="text-sm bg-white border border-slate-200 hover:border-emerald-400 text-slate-700 font-semibold px-4 py-2 rounded-xl transition">
            Refresh
        </a>
    </div>
</x-slot>

<div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- ── Overall Status Banner ── --}}
    @php
        $isUp     = $httpStatus && $httpStatus >= 200 && $httpStatus < 300;
        $aiReady  = $isUp && !empty($health['ai_ready']);
        $noUrl    = empty($baseUrl);
        $keyOk    = $aiKey && $aiKey !== 'REPLACE_WITH_AI_ENGINE_KEY';
    @endphp

    @if($noUrl)
    <div class="flex items-start gap-4 bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div>
            <p class="font-bold text-red-800">AI Engine URL Not Configured</p>
            <p class="text-sm text-red-700 mt-1"><code class="bg-red-100 px-1 rounded">AI_ENGINE_URL</code> is not set in the production environment. Add it to the Render.com environment variables.</p>
        </div>
    </div>
    @elseif($error)
    <div class="flex items-start gap-4 bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div>
            <p class="font-bold text-red-800">Connection Failed — AI Engine Unreachable</p>
            <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
            <p class="text-xs text-red-600 mt-2">The server at <code class="bg-red-100 px-1 rounded">{{ $baseUrl }}</code> could not be reached. Check that the Python app is running on cPanel and the domain resolves correctly.</p>
        </div>
    </div>
    @elseif(!$isUp)
    <div class="flex items-start gap-4 bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div>
            <p class="font-bold text-red-800">AI Engine Returned HTTP {{ $httpStatus }}</p>
            @if($httpStatus === 401)
            <p class="text-sm text-red-700 mt-1">Authentication failed. The <code class="bg-red-100 px-1 rounded">API_KEY</code> set on the cPanel Python app does not match <code class="bg-red-100 px-1 rounded">AI_ENGINE_KEY</code> in Render.com environment variables.</p>
            @elseif($httpStatus === 503)
            <p class="text-sm text-red-700 mt-1">AI engine is running but not configured. <code class="bg-red-100 px-1 rounded">ANTHROPIC_API_KEY</code> is missing from the cPanel Python app environment variables.</p>
            @else
            <p class="text-sm text-red-700 mt-1">Raw response: <code class="bg-red-100 px-1 rounded text-xs">{{ Str::limit($rawBody, 200) }}</code></p>
            @endif
        </div>
    </div>
    @elseif(!$aiReady)
    <div class="flex items-start gap-4 bg-amber-50 border border-amber-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <p class="font-bold text-amber-800">AI Engine Online — But ANTHROPIC_API_KEY Not Set</p>
            <p class="text-sm text-amber-700 mt-1">The FastAPI server is reachable but <code class="bg-amber-100 px-1 rounded">ANTHROPIC_API_KEY</code> is missing from the cPanel environment. Every scan will fail with 503 until this is added.</p>
        </div>
    </div>
    @else
    <div class="flex items-start gap-4 bg-emerald-50 border border-emerald-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="font-bold text-emerald-800">AI Engine Online &amp; Ready</p>
            <p class="text-sm text-emerald-700 mt-1">Health check passed in {{ $latency }}ms. The engine is configured and accepting scans.</p>
        </div>
    </div>
    @endif

    {{-- ── Config Checklist ── --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Configuration Checklist</h3>
        <div class="space-y-3">

            {{-- AI Engine URL --}}
            <div class="flex items-center gap-3">
                @if($baseUrl)
                <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </span>
                @else
                <span class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </span>
                @endif
                <div>
                    <p class="text-sm font-semibold text-slate-700">
                        <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">AI_ENGINE_URL</code>
                        (Render.com environment)
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $baseUrl ?: 'Not set' }}
                    </p>
                </div>
            </div>

            {{-- AI Engine Key --}}
            <div class="flex items-center gap-3">
                @if($keyOk)
                <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </span>
                @else
                <span class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                </span>
                @endif
                <div>
                    <p class="text-sm font-semibold text-slate-700">
                        <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">AI_ENGINE_KEY</code>
                        (Render.com environment)
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        @if(!$aiKey) Not set (auth skipped if cPanel API_KEY is also empty)
                        @elseif(!$keyOk) Still set to placeholder <code class="bg-amber-50 px-1 rounded">REPLACE_WITH_AI_ENGINE_KEY</code> — update to match the cPanel Python app <code>API_KEY</code>
                        @else Set ({{ strlen($aiKey) }} chars)
                        @endif
                    </p>
                </div>
            </div>

            {{-- cPanel Python App Running --}}
            <div class="flex items-center gap-3">
                @if($isUp)
                <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </span>
                @elseif($error)
                <span class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </span>
                @else
                <span class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                </span>
                @endif
                <div>
                    <p class="text-sm font-semibold text-slate-700">cPanel Python App Running
                        @if($latency) <span class="text-xs text-slate-400 font-normal">({{ $latency }}ms)</span> @endif
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        @if($error) Unreachable — {{ Str::limit($error, 100) }}
                        @elseif($isUp) Responding — HTTP {{ $httpStatus }}
                        @else HTTP {{ $httpStatus }} — check cPanel Python App logs
                        @endif
                    </p>
                </div>
            </div>

            {{-- ANTHROPIC_API_KEY on cPanel --}}
            <div class="flex items-center gap-3">
                @if($aiReady)
                <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </span>
                @elseif($isUp)
                <span class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </span>
                @else
                <span class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="8"/></svg>
                </span>
                @endif
                <div>
                    <p class="text-sm font-semibold text-slate-700">
                        <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">ANTHROPIC_API_KEY</code>
                        (cPanel Python App env)
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        @if($aiReady) Configured — engine reports ai_ready: true
                        @elseif($isUp) MISSING — the Python app is running but ANTHROPIC_API_KEY is not set in cPanel. Every scan will fail until this is added.
                        @else Cannot check — engine is not reachable
                        @endif
                    </p>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Raw Health Response ── --}}
    @if($health || $rawBody)
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Raw Health Response</h3>
        <pre class="text-xs bg-slate-50 border border-slate-200 rounded-xl p-4 overflow-x-auto text-slate-700">{{ json_encode($health ?? json_decode($rawBody), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?? $rawBody }}</pre>
    </div>
    @endif

    {{-- ── Fix Instructions ── --}}
    @if(!$aiReady)
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">How to Fix</h3>

        @if($error)
        <div class="space-y-4 text-sm text-slate-700">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center">1</span>
                <div>
                    <p class="font-semibold">Log in to cPanel at <code class="bg-slate-100 px-1 rounded">ai.msas.online</code></p>
                    <p class="text-slate-500 mt-0.5">Navigate to <strong>Setup Python App</strong> and find your Python application.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center">2</span>
                <div>
                    <p class="font-semibold">Verify the app is configured and running</p>
                    <p class="text-slate-500 mt-0.5">Make sure Python 3.10+ is selected, the app root points to the correct directory, and the startup file is <code class="bg-slate-100 px-1 rounded">passenger_wsgi.py</code>.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center">3</span>
                <div>
                    <p class="font-semibold">Install dependencies</p>
                    <p class="text-slate-500 mt-0.5">Run <code class="bg-slate-100 px-1 rounded">pip install -r requirements.txt</code> inside the Python app's virtual environment. Required packages: <code class="bg-slate-100 px-1 rounded">fastapi uvicorn[standard] python-multipart anthropic a2wsgi</code></p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center">4</span>
                <div>
                    <p class="font-semibold">Add environment variables in cPanel</p>
                    <p class="text-slate-500 mt-0.5">In the Python App environment variables section, add: <code class="bg-slate-100 px-1 rounded">ANTHROPIC_API_KEY = sk-ant-...</code></p>
                </div>
            </div>
        </div>
        @elseif($isUp && !$aiReady)
        <div class="space-y-4 text-sm text-slate-700">
            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                <p class="font-bold text-red-800 mb-1">Critical: ANTHROPIC_API_KEY is missing from the cPanel Python App</p>
                <p class="text-red-700">The AI engine server is running but cannot process any scans without a Claude API key.</p>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center">1</span>
                <div>
                    <p class="font-semibold">Go to cPanel → Setup Python App → Edit your app</p>
                    <p class="text-slate-500 mt-0.5">Find the <strong>Environment Variables</strong> section at the bottom of the Python App configuration page.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center">2</span>
                <div>
                    <p class="font-semibold">Add <code class="bg-slate-100 px-1 rounded">ANTHROPIC_API_KEY</code></p>
                    <p class="text-slate-500 mt-0.5">Get your key from <strong>console.anthropic.com</strong> → API Keys. The key starts with <code class="bg-slate-100 px-1 rounded">sk-ant-</code></p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center">3</span>
                <div>
                    <p class="font-semibold">Optionally set <code class="bg-slate-100 px-1 rounded">API_KEY</code> for authentication</p>
                    <p class="text-slate-500 mt-0.5">Choose any secret string (e.g. a UUID). Add the same value as <code class="bg-slate-100 px-1 rounded">AI_ENGINE_KEY</code> in the Render.com environment variables for this Laravel app.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center">4</span>
                <div>
                    <p class="font-semibold">Restart the Python app</p>
                    <p class="text-slate-500 mt-0.5">Click <strong>Restart</strong> in the cPanel Python App config to pick up the new environment variables, then refresh this page to confirm.</p>
                </div>
            </div>
        </div>
        @elseif($httpStatus === 401)
        <div class="space-y-4 text-sm text-slate-700">
            <p>The AI engine is running but rejecting requests. Fix the API key mismatch:</p>
            <ol class="list-decimal list-inside space-y-2 text-slate-600">
                <li>In cPanel Python App env, check the value of <code class="bg-slate-100 px-1 rounded">API_KEY</code></li>
                <li>Set <code class="bg-slate-100 px-1 rounded">AI_ENGINE_KEY</code> in Render.com to the same value</li>
                <li>Redeploy the Laravel app on Render.com to pick up the new env var</li>
            </ol>
        </div>
        @elseif(!$keyOk && $isUp)
        <div class="text-sm text-slate-700">
            <p>Update <code class="bg-slate-100 px-1 rounded">AI_ENGINE_KEY</code> in Render.com environment variables from the placeholder <code class="bg-amber-100 px-1 rounded">REPLACE_WITH_AI_ENGINE_KEY</code> to match whatever <code class="bg-slate-100 px-1 rounded">API_KEY</code> is set to on the cPanel Python App (or leave both empty to disable auth).</p>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Recent Failed Scans ── --}}
    @if($recentFailed->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">
            Recent Scans Awaiting Expert Review
            <span class="ml-2 text-xs font-normal text-slate-400">(these failed AI engine, queued for manual review)</span>
        </h3>
        <div class="space-y-2">
            @foreach($recentFailed as $d)
            <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                <div>
                    <p class="text-sm font-medium text-slate-700">#{{ $d->id }} — {{ ucfirst($d->type) }} scan</p>
                    <p class="text-xs text-slate-400">{{ $d->created_at->diffForHumans() }} · User #{{ $d->user_id }}</p>
                </div>
                <span class="text-xs bg-amber-100 text-amber-700 font-semibold px-2 py-1 rounded-full">Needs Review</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
</x-app-layout>
