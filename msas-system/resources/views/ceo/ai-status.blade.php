<x-app-layout>
<x-slot name="header">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800">AI Engine Status</h2>
            <p class="text-sm text-slate-500 mt-0.5">Live diagnostics for the MSAS FarmAI inference engine</p>
        </div>
        <a href="{{ route('ceo.ai-status') }}"
           class="text-sm bg-white border border-slate-200 hover:border-emerald-400 text-slate-700 font-semibold px-4 py-2 rounded-xl transition">
            Refresh
        </a>
    </div>
</x-slot>

<div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    @php
        $isUp      = $httpStatus && $httpStatus >= 200 && $httpStatus < 300;
        $aiReady   = $isUp && !empty($health['ai_ready']);
        $noUrl     = empty($baseUrl);
        $keyOk     = $aiKey && $aiKey !== 'REPLACE_WITH_AI_ENGINE_KEY';

        // Auth test result classification
        $authOk       = $authStatus && $authStatus >= 200 && $authStatus < 300;
        $authUnauth   = $authStatus === 401;
        $authFmt      = $authStatus === 422;  // 422 = request format issue (not auth)
        $authAiErr    = $authStatus === 503;  // 503 = AI model not configured
        $authOkOrFmt  = $authOk || $authFmt; // 200 or 422 = key is accepted
    @endphp

    {{-- ── Overall Status ── --}}
    @if($noUrl)
    <div class="flex items-start gap-4 bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div>
            <p class="font-bold text-red-800">AI_ENGINE_URL Not Set</p>
            <p class="text-sm text-red-700 mt-1">Add <code class="bg-red-100 px-1 rounded">AI_ENGINE_URL</code> to the Render.com environment variables for the Laravel app.</p>
        </div>
    </div>
    @elseif($error)
    <div class="flex items-start gap-4 bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div>
            <p class="font-bold text-red-800">Cannot Reach AI Engine</p>
            <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
        </div>
    </div>
    @elseif($authUnauth)
    <div class="flex items-start gap-4 bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <div>
            <p class="font-bold text-red-800">API Key Mismatch — Every Scan is Failing with 401 Unauthorized</p>
            <p class="text-sm text-red-700 mt-1">The AI engine is running and healthy, but <strong>rejects every scan request</strong> because the <code class="bg-red-100 px-1 rounded">AI_ENGINE_KEY</code> on the Laravel app does not match the <code class="bg-red-100 px-1 rounded">API_KEY</code> on the AI engine service. Both are set in Render.com — they must be identical.</p>
        </div>
    </div>
    @elseif(!$aiReady)
    <div class="flex items-start gap-4 bg-amber-50 border border-amber-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <p class="font-bold text-amber-800">Engine Online — ANTHROPIC_API_KEY Missing</p>
            <p class="text-sm text-amber-700 mt-1">Add <code class="bg-amber-100 px-1 rounded">ANTHROPIC_API_KEY</code> to the AI engine's Render.com service environment variables.</p>
        </div>
    </div>
    @elseif($authOkOrFmt)
    <div class="flex items-start gap-4 bg-emerald-50 border border-emerald-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="font-bold text-emerald-800">AI Engine Fully Operational</p>
            <p class="text-sm text-emerald-700 mt-1">Health check: {{ $latency }}ms. Auth test: {{ $authLatency }}ms (HTTP {{ $authStatus }}). Scans should succeed.</p>
        </div>
    </div>
    @else
    <div class="flex items-start gap-4 bg-slate-50 border border-slate-200 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div>
            <p class="font-bold text-slate-700">Engine Online — Auth Test Result: HTTP {{ $authStatus ?? 'N/A' }}</p>
            <p class="text-sm text-slate-600 mt-1">{{ Str::limit($authBody ?? $authError, 200) }}</p>
        </div>
    </div>
    @endif

    {{-- ── Diagnostic Grid ── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Health Check Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-700">Health Check <code class="text-xs font-normal text-slate-400">GET /health</code></h3>
                @if($isUp)
                <span class="text-xs bg-emerald-100 text-emerald-700 font-bold px-2 py-1 rounded-full">{{ $httpStatus }} OK</span>
                @elseif($error)
                <span class="text-xs bg-red-100 text-red-700 font-bold px-2 py-1 rounded-full">FAILED</span>
                @else
                <span class="text-xs bg-red-100 text-red-700 font-bold px-2 py-1 rounded-full">HTTP {{ $httpStatus }}</span>
                @endif
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Latency</dt>
                    <dd class="font-mono text-slate-700">{{ $latency ? "{$latency}ms" : '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Service</dt>
                    <dd class="text-slate-700">{{ $health['service'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">AI Ready</dt>
                    <dd class="{{ isset($health['ai_ready']) ? ($health['ai_ready'] ? 'text-emerald-600 font-semibold' : 'text-red-600 font-semibold') : 'text-slate-400' }}">
                        {{ isset($health['ai_ready']) ? ($health['ai_ready'] ? 'Yes' : 'No') : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Model</dt>
                    <dd class="font-mono text-slate-700 text-xs">{{ $health['model'] ?? '—' }}</dd>
                </div>
            </dl>
            @if($error)
            <p class="text-xs text-red-600 mt-3 bg-red-50 rounded-lg p-2">{{ Str::limit($error, 150) }}</p>
            @endif
        </div>

        {{-- Auth Test Card --}}
        <div class="bg-white rounded-2xl border {{ $authUnauth ? 'border-red-300' : 'border-slate-200' }} p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-700">Auth Test <code class="text-xs font-normal text-slate-400">POST /predict/crop</code></h3>
                @if($authOkOrFmt)
                <span class="text-xs bg-emerald-100 text-emerald-700 font-bold px-2 py-1 rounded-full">{{ $authStatus }} ACCEPTED</span>
                @elseif($authUnauth)
                <span class="text-xs bg-red-100 text-red-700 font-bold px-2 py-1 rounded-full">401 DENIED</span>
                @elseif($authStatus)
                <span class="text-xs bg-amber-100 text-amber-700 font-bold px-2 py-1 rounded-full">{{ $authStatus }}</span>
                @elseif($authError)
                <span class="text-xs bg-red-100 text-red-700 font-bold px-2 py-1 rounded-full">ERROR</span>
                @else
                <span class="text-xs bg-slate-100 text-slate-500 font-bold px-2 py-1 rounded-full">—</span>
                @endif
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Latency</dt>
                    <dd class="font-mono text-slate-700">{{ $authLatency ? "{$authLatency}ms" : '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">AI_ENGINE_KEY</dt>
                    <dd class="{{ $keyOk ? 'text-emerald-600' : 'text-amber-600' }} font-semibold text-xs">
                        {{ $keyOk ? 'Set (' . strlen($aiKey) . ' chars)' : 'Placeholder / not set' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Auth result</dt>
                    <dd class="font-semibold {{ $authUnauth ? 'text-red-600' : ($authOkOrFmt ? 'text-emerald-600' : 'text-slate-500') }}">
                        @if($authOkOrFmt) Key accepted
                        @elseif($authUnauth) Key rejected — mismatch!
                        @elseif($authError) Connection error
                        @else {{ $authStatus ? "HTTP {$authStatus}" : 'Not tested' }}
                        @endif
                    </dd>
                </div>
            </dl>
            @if($authUnauth)
            <p class="text-xs text-red-700 mt-3 bg-red-50 rounded-lg p-2 font-medium">
                The <code>AI_ENGINE_KEY</code> this app is sending does not match the <code>API_KEY</code> set on the AI engine service. Fix this in Render.com.
            </p>
            @elseif($authError)
            <p class="text-xs text-red-600 mt-3 bg-red-50 rounded-lg p-2">{{ Str::limit($authError, 150) }}</p>
            @endif
        </div>

    </div>

    {{-- ── Fix Instructions: Key Mismatch ── --}}
    @if($authUnauth)
    <div class="bg-white rounded-2xl border border-red-200 p-6">
        <h3 class="text-base font-bold text-red-800 border-b border-red-100 pb-3 mb-4">How to Fix the API Key Mismatch</h3>
        <div class="space-y-4 text-sm">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="font-semibold text-amber-800">Root cause: two Render.com services, each with an env var that must match — they currently don't.</p>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-red-100 text-red-700 text-xs font-bold flex items-center justify-center">1</span>
                <div>
                    <p class="font-semibold text-slate-800">Open the AI Engine service on Render.com</p>
                    <p class="text-slate-500 mt-0.5">Go to <strong>render.com → msas-ai-engine</strong> service → <strong>Environment</strong> tab. Find the <code class="bg-slate-100 px-1 rounded">API_KEY</code> variable and copy its value.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-red-100 text-red-700 text-xs font-bold flex items-center justify-center">2</span>
                <div>
                    <p class="font-semibold text-slate-800">Open the Laravel app service on Render.com</p>
                    <p class="text-slate-500 mt-0.5">Go to the <strong>msas-farmai</strong> (or similar) Laravel service → <strong>Environment</strong> tab. Find <code class="bg-slate-100 px-1 rounded">AI_ENGINE_KEY</code> and update it to the exact same value as <code class="bg-slate-100 px-1 rounded">API_KEY</code> from step 1.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-red-100 text-red-700 text-xs font-bold flex items-center justify-center">3</span>
                <div>
                    <p class="font-semibold text-slate-800">Trigger a manual redeploy of the Laravel app</p>
                    <p class="text-slate-500 mt-0.5">Render.com auto-redeploys when env vars change, but if not: click <strong>Manual Deploy → Deploy latest commit</strong>.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-red-100 text-red-700 text-xs font-bold flex items-center justify-center">4</span>
                <div>
                    <p class="font-semibold text-slate-800">Refresh this page to confirm</p>
                    <p class="text-slate-500 mt-0.5">The Auth Test card should turn green (HTTP 200 or 422). Then run a new scan — it should complete immediately.</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Config Summary ── --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Environment Summary</h3>
        <dl class="space-y-3 text-sm">
            <div class="flex items-start justify-between">
                <dt class="text-slate-500">AI_ENGINE_URL <span class="text-xs text-slate-400">(Laravel app env)</span></dt>
                <dd class="font-mono text-xs text-slate-700 text-right max-w-xs break-all">{{ $baseUrl ?: '— not set' }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">AI_ENGINE_KEY <span class="text-xs text-slate-400">(Laravel app env)</span></dt>
                <dd class="{{ $keyOk ? 'text-emerald-600' : 'text-amber-600' }} font-semibold text-xs">
                    {{ $keyOk ? 'Set (' . strlen($aiKey) . ' chars)' : ($aiKey ? 'Placeholder value — update this' : 'Not set') }}
                </dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">API_KEY match <span class="text-xs text-slate-400">(AI engine accepts it?)</span></dt>
                <dd class="{{ $authOkOrFmt ? 'text-emerald-600 font-semibold' : ($authUnauth ? 'text-red-600 font-semibold' : 'text-slate-400') }}">
                    @if($authOkOrFmt) Yes — accepted
                    @elseif($authUnauth) No — rejected (401)
                    @else Unknown
                    @endif
                </dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">ANTHROPIC_API_KEY <span class="text-xs text-slate-400">(AI engine env)</span></dt>
                <dd class="{{ $aiReady ? 'text-emerald-600 font-semibold' : 'text-red-600 font-semibold' }}">
                    {{ $aiReady ? 'Configured' : ($isUp ? 'Missing — add to AI engine service' : 'Unknown') }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- ── Raw Health Response ── --}}
    @if($rawBody)
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="text-sm font-bold text-slate-700 mb-3">Raw /health Response</h3>
        <pre class="text-xs bg-slate-50 border border-slate-200 rounded-xl p-4 overflow-x-auto text-slate-700">{{ json_encode(json_decode($rawBody), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?? $rawBody }}</pre>
    </div>
    @endif

    {{-- ── Recent Failed Scans ── --}}
    @if($recentFailed->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">
            Recent Scans Awaiting Expert Review
            <span class="ml-2 text-xs font-normal text-slate-400">(failed AI, queued for manual review)</span>
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
