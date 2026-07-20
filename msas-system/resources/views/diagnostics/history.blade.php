<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
                <span class="text-2xl">📋</span> {{ __('AI Diagnostic Reports') }}
            </h2>
            <a href="{{ route('diagnostics.scan') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold shadow hover:bg-emerald-700 transition flex items-center gap-2 text-sm">
                <i class="fa-solid fa-camera text-xs"></i> <span data-i18n="New Scan">{{ __('New Scan') }}</span>
            </a>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-5xl mx-auto">

        @if(session('success'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl shadow-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <p class="text-emerald-800 font-medium text-sm">{{ session('success') }}</p>
        </div>
        @endif

        @forelse($diagnoses as $diagnosis)
        @php
            // ── Severity → colour tokens ──────────────────────────────────────
            $sev = $diagnosis->severity_level ?? '';
            $urgency = $diagnosis->urgency_level ?? 'Medium';

            $theme = match(true) {
                $sev === 'Critical'                => ['border'=>'border-red-500',    'hbg'=>'bg-red-600',    'htext'=>'text-white',   'ibg'=>'bg-red-50',    'badge'=>'bg-red-100 text-red-800 border-red-200',   'icon'=>'🔴'],
                $sev === 'Severe'                  => ['border'=>'border-orange-500', 'hbg'=>'bg-orange-500', 'htext'=>'text-white',   'ibg'=>'bg-orange-50', 'badge'=>'bg-orange-100 text-orange-800 border-orange-200','icon'=>'🟠'],
                $sev === 'Moderate'                => ['border'=>'border-amber-500',  'hbg'=>'bg-amber-500',  'htext'=>'text-white',   'ibg'=>'bg-amber-50',  'badge'=>'bg-amber-100 text-amber-800 border-amber-200',  'icon'=>'🟡'],
                $sev === 'Mild'                    => ['border'=>'border-yellow-400', 'hbg'=>'bg-yellow-400', 'htext'=>'text-slate-900','ibg'=>'bg-yellow-50', 'badge'=>'bg-yellow-100 text-yellow-800 border-yellow-200','icon'=>'🟡'],
                ($diagnosis->health_status ?? '') === 'Healthy' => ['border'=>'border-emerald-500','hbg'=>'bg-emerald-600','htext'=>'text-white','ibg'=>'bg-emerald-50','badge'=>'bg-emerald-100 text-emerald-800 border-emerald-200','icon'=>'🟢'],
                default                            => ['border'=>'border-blue-400',   'hbg'=>'bg-blue-600',   'htext'=>'text-white',   'ibg'=>'bg-blue-50',   'badge'=>'bg-blue-100 text-blue-800 border-blue-200',   'icon'=>'🔵'],
            };

            // ── Type label ────────────────────────────────────────────────────
            $typeIcon  = match($diagnosis->type) { 'plant'=>'🌿', 'soil'=>'🌱', default=>'🐄' };
            $typeLbl   = match($diagnosis->type) { 'plant'=>'Crop / Plant', 'soil'=>'Soil Assessment', default=>'Livestock' };

            // ── Build plain TTS text ──────────────────────────────────────────
            $ttsLines = ["AI Diagnostic Report. {$typeLbl} scan."];
            if ($diagnosis->subject_name)     $ttsLines[] = "Subject: {$diagnosis->subject_name}.";
            if ($diagnosis->scientific_name && $diagnosis->scientific_name !== 'Unknown') $ttsLines[] = "Scientific name: {$diagnosis->scientific_name}.";
            if ($diagnosis->detected_part)    $ttsLines[] = "Detected part: {$diagnosis->detected_part}.";
            if ($diagnosis->health_status)    $ttsLines[] = "Health status: {$diagnosis->health_status}.";
            $ttsLines[] = "Condition: {$diagnosis->disease_name}.";
            $ttsLines[] = "Confidence: {$diagnosis->confidence_score} percent.";
            if ($sev)                         $ttsLines[] = "Severity: {$sev}.";
            $ttsLines[] = "Urgency: {$urgency}.";
            if ($diagnosis->symptoms_identified) $ttsLines[] = "Symptoms observed: {$diagnosis->symptoms_identified}.";
            if ($diagnosis->cause)            $ttsLines[] = "Root cause: {$diagnosis->cause}.";
            if ($diagnosis->environmental_factors) $ttsLines[] = "Environmental factors: {$diagnosis->environmental_factors}.";
            if ($diagnosis->nutrient_deficiencies && $diagnosis->nutrient_deficiencies !== 'None detected') $ttsLines[] = "Nutrient deficiencies: {$diagnosis->nutrient_deficiencies}.";
            if ($diagnosis->pest_detection && $diagnosis->pest_detection !== 'No pest detected') $ttsLines[] = "Pest detection: {$diagnosis->pest_detection}.";
            if ($diagnosis->first_aid_steps)  $ttsLines[] = "Immediate action: {$diagnosis->first_aid_steps}.";
            if ($diagnosis->recommended_medication) $ttsLines[] = "Treatment: {$diagnosis->recommended_medication}.";
            if ($diagnosis->fertilizer_recommendation) $ttsLines[] = "Fertilizer: {$diagnosis->fertilizer_recommendation}.";
            if ($diagnosis->preventive_measures) $ttsLines[] = "Prevention: {$diagnosis->preventive_measures}.";
            if ($diagnosis->recovery_period)  $ttsLines[] = "Estimated recovery: {$diagnosis->recovery_period}.";
            if ($diagnosis->vet_referral_advice) $ttsLines[] = "Expert advice: {$diagnosis->vet_referral_advice}.";
            $ttsLines[] = "Always consult a certified specialist before applying any treatment.";
            $ttsText = e(implode(' ', $ttsLines));
            $ttsId   = 'tts-' . $diagnosis->id;

            // ── Feedback ──────────────────────────────────────────────────────
            $myFeedback = $feedbackReady ? $diagnosis->myFeedback : null;
        @endphp

        {{-- ═══ REPORT CARD ═══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl shadow-md border-l-4 {{ $theme['border'] }} overflow-hidden">

            {{-- ── Card Header ──────────────────────────────────────────────── --}}
            <div class="{{ $theme['hbg'] }} {{ $theme['htext'] }} px-5 py-3 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">{{ $typeIcon }}</span>
                    <div>
                        <div class="font-extrabold text-sm leading-tight">{{ $diagnosis->subject_name ?? $typeLbl }}</div>
                        @if($diagnosis->scientific_name && $diagnosis->scientific_name !== 'Unknown')
                        <div class="opacity-80 italic text-xs leading-tight">{{ $diagnosis->scientific_name }}</div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs opacity-90">
                    <span>{{ $diagnosis->created_at->format('M j, Y  g:i A') }}</span>
                    <span class="bg-white/20 px-2 py-0.5 rounded-full font-bold capitalize text-[10px]">{{ $diagnosis->status }}</span>
                </div>
            </div>

            {{-- ── AI Unavailable Banner (only for failed scans) ───────────────── --}}
            @if($diagnosis->status === 'needs_review')
            <div class="bg-amber-50 border-b border-amber-200 px-5 py-3 flex items-start gap-3">
                <span class="text-xl shrink-0">🔄</span>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-amber-800 text-sm">AI Engine was unavailable during this scan</p>
                    <p class="text-xs text-amber-700 mt-0.5">Your image was saved. Please run a new scan — if this keeps happening, the AI service may be temporarily offline.</p>
                </div>
                <a href="{{ route('diagnostics.scan') }}"
                   class="shrink-0 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition">
                    Try Again
                </a>
            </div>
            @endif

            {{-- ── Voice Narration Bar ───────────────────────────────────────── --}}
            @php $sessionLang = session('locale', 'en'); $validTtsLangs = ['en','ha','fr','yo','ig','ar','sw']; $defaultTtsLang = in_array($sessionLang, $validTtsLangs) ? $sessionLang : 'en'; @endphp
            <div class="bg-slate-800 text-white px-5 py-2.5 flex flex-wrap items-center gap-3">
                <i class="fa-solid fa-volume-high text-emerald-400 text-sm shrink-0"></i>
                <span class="text-xs text-slate-300 font-medium shrink-0" data-i18n="Voice Narration">{{ __('Voice Narration') }}</span>

                {{-- Language selector — pre-set to the user's current locale --}}
                <select id="{{ $ttsId }}-lang"
                    onchange="ttsChangeLang('{{ $ttsId }}', this.value, '{{ route('diagnostics.translate') }}')"
                    class="bg-slate-700 border border-slate-600 text-white text-xs rounded-lg px-2 py-1.5 focus:outline-none focus:border-emerald-400">
                    <option value="en"  {{ $defaultTtsLang === 'en' ? 'selected' : '' }}>🇺🇸 English</option>
                    <option value="ha"  {{ $defaultTtsLang === 'ha' ? 'selected' : '' }}>🇳🇬 Hausa</option>
                    <option value="fr"  {{ $defaultTtsLang === 'fr' ? 'selected' : '' }}>🇫🇷 Français</option>
                    <option value="yo"  {{ $defaultTtsLang === 'yo' ? 'selected' : '' }}>🇳🇬 Yorùbá</option>
                    <option value="ig"  {{ $defaultTtsLang === 'ig' ? 'selected' : '' }}>🇳🇬 Igbo</option>
                    <option value="ar"  {{ $defaultTtsLang === 'ar' ? 'selected' : '' }}>🇸🇦 Arabic</option>
                    <option value="sw"  {{ $defaultTtsLang === 'sw' ? 'selected' : '' }}>🌍 Swahili</option>
                </select>

                {{-- Controls --}}
                <div class="flex items-center gap-1.5" id="{{ $ttsId }}-controls">
                    <button onclick="ttsPlay('{{ $ttsId }}')" id="{{ $ttsId }}-playbtn"
                        class="flex items-center gap-1 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-400 text-white rounded-lg text-xs font-bold transition">
                        <i class="fa-solid fa-play text-[9px]"></i> <span data-i18n="Play">{{ __('Play') }}</span>
                    </button>
                    <button onclick="ttsPause('{{ $ttsId }}')" id="{{ $ttsId }}-pause"
                        class="p-1.5 bg-slate-600 hover:bg-slate-500 text-white rounded-lg text-xs transition hidden">
                        <i class="fa-solid fa-pause text-[9px]"></i>
                    </button>
                    <button onclick="ttsStop('{{ $ttsId }}')" id="{{ $ttsId }}-stop"
                        class="p-1.5 bg-red-900 hover:bg-red-800 text-red-200 rounded-lg text-xs transition hidden">
                        <i class="fa-solid fa-stop text-[9px]"></i>
                    </button>
                    <button onclick="ttsReplay('{{ $ttsId }}')" id="{{ $ttsId }}-replay"
                        class="p-1.5 bg-slate-600 hover:bg-slate-500 text-white rounded-lg text-xs transition hidden" title="Replay">
                        <i class="fa-solid fa-rotate-right text-[9px]"></i>
                    </button>
                    <select onchange="ttsSetSpeed('{{ $ttsId }}', this.value)"
                        class="bg-slate-700 border border-slate-600 text-white text-xs rounded-lg px-2 py-1.5 focus:outline-none focus:border-emerald-400">
                        <option value="0.75">0.75×</option>
                        <option value="1" selected>1×</option>
                        <option value="1.25">1.25×</option>
                        <option value="1.5">1.5×</option>
                    </select>
                    {{-- Transcript toggle --}}
                    <button onclick="ttsToggleTranscript('{{ $ttsId }}')" id="{{ $ttsId }}-transcript-btn"
                        class="flex items-center gap-1 px-2.5 py-1.5 bg-slate-600 hover:bg-slate-500 text-slate-200 rounded-lg text-xs font-medium transition" title="Show/hide transcript">
                        <i class="fa-solid fa-closed-captioning text-[9px]"></i>
                        <span data-i18n="Transcript">{{ __('Transcript') }}</span>
                    </button>
                </div>

                <div id="{{ $ttsId }}-translating" class="hidden text-xs text-amber-300 flex items-center gap-1 ml-auto">
                    <i class="fa-solid fa-spinner fa-spin text-[10px]"></i>
                    <span data-i18n="Translating...">{{ __('Translating...') }}</span>
                </div>

                {{-- Hidden data stores --}}
                <span id="{{ $ttsId }}-text" class="hidden">{{ $ttsText }}</span>
                <span id="{{ $ttsId }}-translated" class="hidden"></span>
                <span id="{{ $ttsId }}-state" class="hidden">stopped</span>
            </div>
            {{-- Voice warning: shown when device lacks a native voice for the selected language --}}
            <div id="{{ $ttsId }}-voice-warning" class="hidden bg-amber-900/20 border-t border-amber-700/20 px-5 py-1.5 text-[10px] text-amber-300"></div>

            {{-- ── Voice Transcript Panel ───────────────────────────────────────── --}}
            <div id="{{ $ttsId }}-transcript-panel" class="hidden bg-slate-900 border-t border-slate-700">
                <div class="px-5 py-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-closed-captioning text-emerald-400"></i>
                            <span data-i18n="Transcript">{{ __('Transcript') }}</span>
                        </span>
                        <button onclick="ttsToggleTranscript('{{ $ttsId }}')" class="text-[10px] text-slate-500 hover:text-slate-300 transition">✕ Hide</button>
                    </div>
                    <div id="{{ $ttsId }}-transcript-box"
                         class="text-xs text-slate-300 leading-relaxed max-h-36 overflow-y-auto p-2 bg-slate-800 rounded-lg">
                        <span class="text-slate-500 italic">Press Play to start narration — transcript will appear here.</span>
                    </div>
                </div>
            </div>

            {{-- ── Main Report Grid ──────────────────────────────────────────── --}}
            <div class="p-5 grid grid-cols-1 md:grid-cols-4 gap-5">

                {{-- Left column: image + meta --}}
                <div class="md:col-span-1 space-y-3">
                    {{-- Image --}}
                    <div class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 aspect-square">
                        {{-- Prefer DB thumbnail (survives restarts); fall back to storage URL --}}
                        <img src="{{ $diagnosis->image_thumbnail ?? Storage::url($diagnosis->image_path) }}"
                             alt="Scanned Image"
                             class="w-full h-full object-cover" loading="lazy"
                             onerror="imgError(this)">

                        {{-- Confidence overlay --}}
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-3">
                            <div class="text-white text-[10px] font-bold uppercase opacity-70" data-i18n="AI Confidence">{{ __('AI Confidence') }}</div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-white font-extrabold text-2xl leading-none">{{ number_format($diagnosis->confidence_score, 0) }}</span>
                                <span class="text-white/70 text-xs font-bold">%</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-1.5 mt-1.5">
                                <div class="h-1.5 rounded-full transition-all {{ $diagnosis->confidence_score >= 80 ? 'bg-emerald-400' : ($diagnosis->confidence_score >= 60 ? 'bg-amber-400' : 'bg-red-400') }}"
                                     style="width:{{ min((float)$diagnosis->confidence_score,100) }}%"></div>
                            </div>
                        </div>

                        @if($diagnosis->confidence_score < 60)
                        <div class="absolute top-2 right-2 bg-amber-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full">LOW CONF.</div>
                        @endif
                    </div>

                    {{-- Badges --}}
                    <div class="space-y-1.5">
                        @if($sev)
                        <div class="flex items-center justify-between text-xs px-3 py-1.5 rounded-lg border {{ $theme['badge'] }}">
                            <span class="font-bold uppercase tracking-wide text-[10px]" data-i18n="Severity">{{ __('Severity') }}</span>
                            <span class="font-extrabold">{{ $theme['icon'] }} {{ $sev }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between text-xs px-3 py-1.5 rounded-lg border
                            {{ $urgency === 'Emergency' || $urgency === 'High' ? 'bg-red-100 text-red-800 border-red-200' : ($urgency === 'Medium' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200') }}">
                            <span class="font-bold uppercase tracking-wide text-[10px]" data-i18n="Urgency">{{ __('Urgency') }}</span>
                            <span class="font-extrabold">{{ $urgency }}</span>
                        </div>
                        @if($diagnosis->detected_part)
                        <div class="flex items-center justify-between text-xs px-3 py-1.5 rounded-lg border bg-slate-50 border-slate-200 text-slate-700">
                            <span class="font-bold uppercase tracking-wide text-[10px]" data-i18n="Detected Part">{{ __('Detected Part') }}</span>
                            <span class="font-bold">{{ $diagnosis->detected_part }}</span>
                        </div>
                        @endif
                        @if($diagnosis->health_status)
                        <div class="flex items-center justify-between text-xs px-3 py-1.5 rounded-lg border bg-slate-50 border-slate-200 text-slate-700">
                            <span class="font-bold uppercase tracking-wide text-[10px]" data-i18n="Health">{{ __('Health') }}</span>
                            <span class="font-bold">{{ $diagnosis->health_status }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Download button --}}
                    <a href="{{ route('diagnostics.report', $diagnosis) }}" target="_blank"
                        class="w-full flex items-center justify-center gap-2 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition">
                        <i class="fa-solid fa-file-pdf text-red-400"></i> <span data-i18n="Download Report">{{ __('Download Report') }}</span>
                    </a>
                </div>

                {{-- Right column: all findings --}}
                <div class="md:col-span-3 space-y-4">

                    {{-- Disease headline --}}
                    <div class="{{ $theme['ibg'] }} border border-opacity-30 rounded-xl px-4 py-3 flex items-start justify-between gap-3 flex-wrap"
                         style="border-color: inherit">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-0.5" data-i18n="Diagnosis">{{ __('Diagnosis') }}</div>
                            <h3 class="text-lg font-extrabold text-slate-800 leading-tight">{{ $diagnosis->disease_name }}</h3>
                            @if($diagnosis->recovery_period)
                            <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                <i class="fa-regular fa-clock"></i> Recovery: {{ $diagnosis->recovery_period }}
                            </p>
                            @endif
                        </div>
                        @if($diagnosis->confidence_score < 60)
                        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs px-3 py-2 rounded-lg max-w-xs">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span data-i18n="Confidence is limited. Please capture a clearer image or consult an expert.">{{ __('Confidence is limited. Please capture a clearer image or consult an expert.') }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Findings row: symptoms + cause + environment --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @if($diagnosis->symptoms_identified)
                        <div class="bg-red-50 border border-red-100 rounded-xl p-3">
                            <div class="text-[10px] font-bold text-red-500 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-microscope text-[9px]"></i> <span data-i18n="Symptoms">{{ __('Symptoms') }}</span>
                            </div>
                            <p class="text-xs text-red-800 leading-relaxed">{{ $diagnosis->symptoms_identified }}</p>
                        </div>
                        @endif
                        @if($diagnosis->cause)
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3">
                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-magnifying-glass text-[9px]"></i> <span data-i18n="Root Cause">{{ __('Root Cause') }}</span>
                            </div>
                            <p class="text-xs text-slate-700 leading-relaxed">{{ $diagnosis->cause }}</p>
                        </div>
                        @endif
                        @if($diagnosis->environmental_factors)
                        <div class="bg-sky-50 border border-sky-100 rounded-xl p-3">
                            <div class="text-[10px] font-bold text-sky-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-cloud-sun text-[9px]"></i> <span data-i18n="Environment">{{ __('Environment') }}</span>
                            </div>
                            <p class="text-xs text-sky-800 leading-relaxed">{{ $diagnosis->environmental_factors }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Nutrients + Pests --}}
                    @php $hasExtra = ($diagnosis->nutrient_deficiencies && $diagnosis->nutrient_deficiencies !== 'None detected') || ($diagnosis->pest_detection && $diagnosis->pest_detection !== 'No pest detected'); @endphp
                    @if($hasExtra)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @if($diagnosis->nutrient_deficiencies && $diagnosis->nutrient_deficiencies !== 'None detected')
                        <div class="bg-lime-50 border border-lime-100 rounded-xl p-3">
                            <div class="text-[10px] font-bold text-lime-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-flask text-[9px]"></i> <span data-i18n="Nutrient Deficiency">{{ __('Nutrient Deficiency') }}</span>
                            </div>
                            <p class="text-xs text-lime-900 leading-relaxed">{{ $diagnosis->nutrient_deficiencies }}</p>
                        </div>
                        @endif
                        @if($diagnosis->pest_detection && $diagnosis->pest_detection !== 'No pest detected')
                        <div class="bg-orange-50 border border-orange-100 rounded-xl p-3">
                            <div class="text-[10px] font-bold text-orange-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-bug text-[9px]"></i> <span data-i18n="Pest Detection">{{ __('Pest Detection') }}</span>
                            </div>
                            <p class="text-xs text-orange-800 leading-relaxed">{{ $diagnosis->pest_detection }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Immediate Action --}}
                    @if($diagnosis->first_aid_steps)
                    <div class="bg-blue-600 text-white rounded-xl p-4">
                        <div class="text-[10px] font-bold uppercase tracking-wider mb-2 opacity-80 flex items-center gap-1">
                            <i class="fa-solid fa-kit-medical text-[9px]"></i> <span data-i18n="Immediate Action Required">{{ __('Immediate Action Required') }}</span>
                        </div>
                        <p class="text-sm leading-relaxed font-medium whitespace-pre-line">{{ $diagnosis->first_aid_steps }}</p>
                    </div>
                    @endif

                    {{-- Treatment + Fertilizer --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @if($diagnosis->recommended_medication)
                        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3">
                            <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-pills text-[9px]"></i> <span data-i18n="Recommended Treatment">{{ __('Recommended Treatment') }}</span>
                            </div>
                            <p class="text-xs text-emerald-900 leading-relaxed">{{ $diagnosis->recommended_medication }}</p>
                        </div>
                        @endif
                        @if($diagnosis->fertilizer_recommendation)
                        <div class="bg-teal-50 border border-teal-100 rounded-xl p-3">
                            <div class="text-[10px] font-bold text-teal-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-seedling text-[9px]"></i> <span data-i18n="Fertilizer">{{ __('Fertilizer') }}</span>
                            </div>
                            <p class="text-xs text-teal-900 leading-relaxed">{{ $diagnosis->fertilizer_recommendation }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Prevention + Best Practices --}}
                    @if($diagnosis->preventive_measures || $diagnosis->best_practices)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @if($diagnosis->preventive_measures)
                        <div class="bg-violet-50 border border-violet-100 rounded-xl p-3">
                            <div class="text-[10px] font-bold text-violet-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-shield-halved text-[9px]"></i> <span data-i18n="Prevention">{{ __('Prevention') }}</span>
                            </div>
                            <p class="text-xs text-violet-900 leading-relaxed">{{ $diagnosis->preventive_measures }}</p>
                        </div>
                        @endif
                        @if($diagnosis->best_practices)
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3">
                            <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-book-open text-[9px]"></i> <span data-i18n="Best Practices">{{ __('Best Practices') }}</span>
                            </div>
                            <p class="text-xs text-indigo-900 leading-relaxed">{{ $diagnosis->best_practices }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Expert Advice --}}
                    @if($diagnosis->vet_referral_advice)
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-start gap-2">
                        <i class="fa-solid fa-user-doctor text-amber-600 mt-0.5 shrink-0"></i>
                        <div>
                            <div class="text-[10px] font-bold text-amber-700 uppercase tracking-wider mb-1" data-i18n="Expert Recommendation">{{ __('Expert Recommendation') }}</div>
                            <p class="text-xs text-amber-900">{{ $diagnosis->vet_referral_advice }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- Explainable AI --}}
                    @if($diagnosis->explanation)
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <button onclick="toggleSection('explain-{{ $diagnosis->id }}')"
                            class="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left">
                            <span class="text-xs font-bold text-slate-700 flex items-center gap-2">
                                <i class="fa-solid fa-brain text-purple-500"></i> <span data-i18n="Why this diagnosis?">{{ __('Why this diagnosis?') }}</span>
                                <span class="text-slate-400 font-normal">(<span data-i18n="Explainable AI">{{ __('Explainable AI') }}</span>)</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform" id="icon-explain-{{ $diagnosis->id }}"></i>
                        </button>
                        <div id="explain-{{ $diagnosis->id }}" class="hidden px-4 py-3 text-xs text-slate-700 bg-white border-t border-slate-100 leading-relaxed">
                            <p>{{ $diagnosis->explanation }}</p>
                            <p class="text-slate-400 italic mt-2">This describes the specific visual features the AI detected to reach this conclusion.</p>
                        </div>
                    </div>
                    @endif

                    {{-- Disclaimer --}}
                    <p class="text-[10px] text-slate-400 flex items-start gap-1.5">
                        <i class="fa-solid fa-triangle-exclamation shrink-0 mt-0.5"></i>
                        AI analysis provides guidance based on visual symptoms only. Always consult a certified
                        {{ $diagnosis->type === 'soil' ? 'Agronomist or Soil Scientist' : ($diagnosis->type === 'animal' ? 'Veterinary Doctor' : 'Agronomist') }}
                        before applying any treatment.
                    </p>

                    {{-- Feedback --}}
                    @if($feedbackReady && $diagnosis->status === 'reviewed')
                    <div class="border-t border-slate-100 pt-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider" data-i18n="Was this accurate?">{{ __('Was this accurate?') }}</span>

                            @if($myFeedback)
                            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $myFeedback->rating === 'thumbs_up' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $myFeedback->rating === 'thumbs_up' ? '👍 Marked Accurate' : '👎 Marked Inaccurate' }}
                            </span>
                            <button onclick="toggleSection('fb-{{ $diagnosis->id }}')" class="text-[10px] text-slate-400 hover:text-slate-600 ml-auto">Edit</button>
                            @else
                            <button onclick="submitFeedback('{{ $diagnosis->id }}','thumbs_up')"
                                class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold transition">👍 <span data-i18n="Accurate">{{ __('Accurate') }}</span></button>
                            <button onclick="submitFeedback('{{ $diagnosis->id }}','thumbs_down')"
                                class="px-3 py-1 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-full text-xs font-bold transition">👎 <span data-i18n="Not Accurate">{{ __('Not Accurate') }}</span></button>
                            @endif
                        </div>

                        <form id="fb-{{ $diagnosis->id }}" action="{{ route('diagnostics.feedback', $diagnosis) }}" method="POST"
                              class="hidden mt-3 space-y-2">
                            @csrf
                            <input type="hidden" name="rating" id="fb-rating-{{ $diagnosis->id }}" value="thumbs_up">
                            <input type="text" name="correct_disease" value="{{ $myFeedback?->correct_disease }}"
                                   placeholder="Correct diagnosis (optional)"
                                   class="w-full border-slate-200 rounded-lg text-xs focus:ring-emerald-400 focus:border-emerald-400 py-1.5">
                            <textarea name="notes" rows="2" placeholder="Additional notes (optional)"
                                      class="w-full border-slate-200 rounded-lg text-xs focus:ring-emerald-400 focus:border-emerald-400">{{ $myFeedback?->notes }}</textarea>
                            <div class="flex gap-2">
                                <button type="submit" class="bg-emerald-600 text-white text-xs font-bold px-4 py-1.5 rounded-lg hover:bg-emerald-700 transition">Submit</button>
                                <button type="button" onclick="toggleSection('fb-{{ $diagnosis->id }}')" class="text-xs text-slate-400 hover:text-slate-600">Cancel</button>
                            </div>
                        </form>
                    </div>
                    @endif

                </div>{{-- end right col --}}
            </div>{{-- end grid --}}
        </div>{{-- end card --}}

        @empty
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-5xl mx-auto mb-4">🗂️</div>
            <h3 class="text-xl font-bold text-slate-800 mb-2" data-i18n="No Scans Yet">{{ __('No Scans Yet') }}</h3>
            <p class="text-slate-500 mb-6 max-w-sm mx-auto text-sm" data-i18n="Upload a photo of a plant, animal, or soil sample to get an instant AI-powered diagnosis.">{{ __('Upload a photo of a plant, animal, or soil sample to get an instant AI-powered diagnosis.') }}</p>
            <a href="{{ route('diagnostics.scan') }}" class="inline-flex items-center gap-2 bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-emerald-700 hover:-translate-y-0.5 transition text-sm">
                <i class="fa-solid fa-camera"></i> <span data-i18n="Run Your First Scan">{{ __('Run Your First Scan') }}</span>
            </a>
        </div>
        @endforelse

    </div>

    {{-- ─── Global Scripts ──────────────────────────────────────────────────── --}}
    <style>
        .tts-speaking { animation: tts-pulse 1.4s ease-in-out infinite; }
        @keyframes tts-pulse { 0%,100%{opacity:1} 50%{opacity:.55} }
    </style>

    <script>
    // ── Image error fallback ───────────────────────────────────────────────────
    function imgError(img) {
        img.onerror = null;
        img.style.objectFit = 'contain';
        img.style.padding = '16px';
        img.style.opacity = '0.35';
        img.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Crect width='80' height='80' fill='%23f1f5f9'/%3E%3Ctext x='50%25' y='42%25' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-size='26'%3E%F0%9F%93%B7%3C/text%3E%3Ctext x='50%25' y='68%25' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-size='8'%3ENo image%3C/text%3E%3C/svg%3E";
    }

    // ── Toggle accordion ───────────────────────────────────────────────────────
    function toggleSection(id) {
        var el = document.getElementById(id);
        var icon = document.getElementById('icon-' + id);
        if (!el) return;
        el.classList.toggle('hidden');
        if (icon) icon.style.transform = el.classList.contains('hidden') ? '' : 'rotate(180deg)';
    }

    // ── Feedback quick-submit ──────────────────────────────────────────────────
    function submitFeedback(diagId, rating) {
        var form = document.getElementById('fb-' + diagId);
        if (!form) return;
        document.getElementById('fb-rating-' + diagId).value = rating;
        form.classList.remove('hidden');
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ── TTS Engine ─────────────────────────────────────────────────────────────
    (function(){
        var states      = {};  // id → 'stopped' | 'playing' | 'paused'
        var speeds      = {};  // id → float
        var cached      = {};  // id+lang → translated text
        var translating = {};  // id → bool
        var keepalive   = null; // Chrome 15-second silence bug workaround

        // Chrome silently stops speaking after ~15s — pause/resume keeps it alive
        function startKeepalive() {
            if (keepalive) return;
            keepalive = setInterval(function() {
                if ('speechSynthesis' in window && window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
                    window.speechSynthesis.pause();
                    window.speechSynthesis.resume();
                }
            }, 14000);
        }
        function stopKeepalive() {
            if (keepalive) { clearInterval(keepalive); keepalive = null; }
        }

        var voiceCache = [];
        function loadVoices() {
            var v = 'speechSynthesis' in window ? window.speechSynthesis.getVoices() : [];
            if (v.length) voiceCache = v;
            return voiceCache;
        }
        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = loadVoices;
            loadVoices();
        }

        function el(id){ return document.getElementById(id); }

        function getDisplayText(id) {
            var translated = el(id + '-translated');
            if (translated && translated.textContent.trim()) return translated.textContent.trim();
            return (el(id + '-text') || {textContent:''}).textContent.trim();
        }

        function getLangCode(id) {
            var sel = el(id + '-lang');
            if (!sel) return 'en-US';
            var map = { en:'en-US', ha:'ha-NG', fr:'fr-FR', yo:'yo-NG', ig:'ig-NG', ar:'ar-SA', sw:'sw-KE' };
            return map[sel.value] || 'en-US';
        }

        function setVoiceWarning(id, msg) {
            var warn = el(id + '-voice-warning');
            if (!warn) return;
            if (msg) { warn.textContent = msg; warn.classList.remove('hidden'); }
            else { warn.classList.add('hidden'); }
        }

        function updateUI(id, state) {
            var playBtn   = el(id + '-playbtn');
            var pauseBtn  = el(id + '-pause');
            var stopBtn   = el(id + '-stop');
            var replayBtn = el(id + '-replay');
            if (!playBtn) return;
            var dict = (window.MSAS_TRANS && window.MSAS_LOCALE) ? (window.MSAS_TRANS[window.MSAS_LOCALE] || {}) : {};
            var lPlay    = dict['Play']       || 'Play';
            var lResume  = dict['Resume']     || 'Resume';
            var lPlaying = dict['Playing...'] || 'Playing...';
            if (state === 'playing') {
                playBtn.innerHTML = '<i class="fa-solid fa-volume-high fa-beat text-[9px]"></i> ' + lPlaying;
                playBtn.classList.add('tts-speaking', '!bg-emerald-400');
                if (pauseBtn)  pauseBtn.classList.remove('hidden');
                if (stopBtn)   stopBtn.classList.remove('hidden');
                if (replayBtn) replayBtn.classList.remove('hidden');
            } else if (state === 'paused') {
                playBtn.innerHTML = '<i class="fa-solid fa-play text-[9px]"></i> ' + lResume;
                playBtn.classList.remove('tts-speaking', '!bg-emerald-400');
                if (pauseBtn)  pauseBtn.classList.add('hidden');
            } else {
                playBtn.innerHTML = '<i class="fa-solid fa-play text-[9px]"></i> ' + lPlay;
                playBtn.classList.remove('tts-speaking', '!bg-emerald-400');
                if (pauseBtn)  pauseBtn.classList.add('hidden');
                if (stopBtn)   stopBtn.classList.add('hidden');
                if (replayBtn) replayBtn.classList.add('hidden');
                stopKeepalive();
            }
        }

        var wordMaps = {};

        function buildTranscript(id, text) {
            var box = el(id + '-transcript-box');
            if (!box) return null;
            box.innerHTML = '';
            var map = [];
            var charIdx = 0;
            text.split(/(\s+)/).forEach(function(part) {
                if (!part.length) return;
                if (/^\s+$/.test(part)) {
                    box.appendChild(document.createTextNode(part));
                    charIdx += part.length;
                } else {
                    var span = document.createElement('span');
                    span.textContent = part;
                    span.className = 'tts-word';
                    span.style.cssText = 'border-radius:3px;padding:0 1px;transition:background .1s,color .1s;';
                    box.appendChild(span);
                    map.push({ start: charIdx, end: charIdx + part.length, el: span });
                    charIdx += part.length;
                }
            });
            return map;
        }

        window.ttsToggleTranscript = function(id) {
            var panel = el(id + '-transcript-panel');
            if (panel) panel.classList.toggle('hidden');
        };

        // iOS/Android-safe cancel + speak: poll until synthesis actually stops
        function cancelThenSpeak(id) {
            window.speechSynthesis.cancel();
            stopKeepalive();
            var attempts = 0;
            function tryNow() {
                if (!window.speechSynthesis.speaking || attempts++ > 12) {
                    setTimeout(function(){ startSpeaking(id); }, 120);
                } else {
                    setTimeout(tryNow, 60);
                }
            }
            setTimeout(tryNow, 60);
        }

        function startSpeaking(id) {
            if (!('speechSynthesis' in window)) {
                alert('Voice playback is not supported in this browser. Please use Chrome, Edge, or Safari.');
                return;
            }
            var text = getDisplayText(id);
            if (!text) return;

            var langCode = getLangCode(id);
            var u = new SpeechSynthesisUtterance(text);
            u.lang   = langCode;
            u.rate   = parseFloat(speeds[id] || 1);
            u.pitch  = 1.0;
            u.volume = 1.0;

            var voices = loadVoices();
            if (voices.length) {
                var code  = langCode.split('-')[0];
                var match = voices.find(function(v){ return v.lang.startsWith(code); });
                if (match) {
                    u.voice = match;
                    setVoiceWarning(id, null);
                } else if (code !== 'en') {
                    setVoiceWarning(id, '⚠ No ' + langCode + ' voice on this device — voice is English but text is translated');
                }
            }

            var map = buildTranscript(id, text);
            wordMaps[id] = map || [];

            u.onstart = function() { states[id] = 'playing'; updateUI(id, 'playing'); startKeepalive(); };
            u.onend   = function() {
                states[id] = 'stopped'; updateUI(id, 'stopped'); stopKeepalive();
                (wordMaps[id] || []).forEach(function(w){ w.el.style.background = ''; w.el.style.color = ''; });
            };
            u.onerror = function(e) {
                if (e.error === 'interrupted' || e.error === 'canceled') return;
                console.warn('TTS error', e.error);
                states[id] = 'stopped'; updateUI(id, 'stopped'); stopKeepalive();
            };

            u.onboundary = function(e) {
                if (e.name !== 'word') return;
                var map = wordMaps[id];
                if (!map || !map.length) return;
                var ci = e.charIndex;
                map.forEach(function(w){ w.el.style.background = ''; w.el.style.color = ''; });
                for (var i = 0; i < map.length; i++) {
                    if (map[i].start <= ci && ci < map[i].end) {
                        map[i].el.style.background = '#10b981';
                        map[i].el.style.color = '#fff';
                        map[i].el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        break;
                    }
                }
            };

            window.speechSynthesis.speak(u);
        }

        window.ttsPlay = function(id) {
            if (translating[id]) {
                var btn = el(id + '-playbtn');
                if (btn) {
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[9px]"></i> Translating...';
                    setTimeout(function(){ if (states[id] !== 'playing') updateUI(id, 'stopped'); }, 4000);
                }
                return;
            }
            if (states[id] === 'paused') {
                window.speechSynthesis.resume();
                states[id] = 'playing';
                updateUI(id, 'playing');
                startKeepalive();
                return;
            }
            // Cancel any other card that may be playing
            window.speechSynthesis.cancel();
            setTimeout(function(){ startSpeaking(id); }, 80);
        };

        window.ttsPause = function(id) {
            if (window.speechSynthesis.speaking) {
                window.speechSynthesis.pause();
                states[id] = 'paused';
                updateUI(id, 'paused');
                stopKeepalive();
            }
        };

        window.ttsStop = function(id) {
            window.speechSynthesis.cancel();
            states[id] = 'stopped';
            updateUI(id, 'stopped');
            stopKeepalive();
        };

        window.ttsReplay = function(id) {
            cancelThenSpeak(id);
        };

        window.ttsSetSpeed = function(id, rate) {
            speeds[id] = parseFloat(rate);
            if (states[id] === 'playing') { cancelThenSpeak(id); }
        };

        // ── MyMemory fallback translation (free, no API key needed) ────────────
        function myMemoryTranslate(id, text, langCode, cacheKey, onDone) {
            // MyMemory supports ha, yo, ig, fr, ar, sw with varying quality
            // Chunked to 480 chars to stay within free-tier per-request limit
            var chunks   = [];
            var maxChunk = 480;
            for (var i = 0; i < text.length; i += maxChunk) {
                chunks.push(text.slice(i, i + maxChunk));
            }
            var results  = new Array(chunks.length).fill('');
            var pending  = chunks.length;
            if (!pending) { onDone(null); return; }

            chunks.forEach(function(chunk, idx) {
                var url = 'https://api.mymemory.translated.net/get?q='
                    + encodeURIComponent(chunk) + '&langpair=en|' + langCode;
                fetch(url)
                    .then(function(r){ return r.json(); })
                    .then(function(data) {
                        if (data.responseStatus === 200 && data.responseData && data.responseData.translatedText) {
                            results[idx] = data.responseData.translatedText;
                        } else {
                            results[idx] = chunk; // keep original on failure
                        }
                    })
                    .catch(function() { results[idx] = chunk; })
                    .finally(function() {
                        if (--pending === 0) { onDone(results.join(' ')); }
                    });
            });
        }

        window.ttsChangeLang = function(id, langCode, translateUrl) {
            // English — clear translation and revert to original text
            if (langCode === 'en') {
                var t = el(id + '-translated');
                if (t) t.textContent = '';
                translating[id] = false;
                setVoiceWarning(id, null);
                var origText = (el(id + '-text') || {textContent:''}).textContent.trim();
                if (origText) { var m = buildTranscript(id, origText); if (m) wordMaps[id] = m; }
                if (states[id] === 'playing' || states[id] === 'paused') { cancelThenSpeak(id); }
                return;
            }

            // Serve from in-session cache
            var cacheKey = id + '-' + langCode;
            if (cached[cacheKey]) {
                var tc = el(id + '-translated');
                if (tc) tc.textContent = cached[cacheKey];
                var cm = buildTranscript(id, cached[cacheKey]);
                if (cm) wordMaps[id] = cm;
                if (states[id] === 'playing' || states[id] === 'paused') { cancelThenSpeak(id); }
                return;
            }

            translating[id] = true;
            var indicator = el(id + '-translating');
            if (indicator) indicator.classList.remove('hidden');

            var originalText = (el(id + '-text') || {textContent:''}).textContent.trim();
            if (!originalText || !translateUrl) {
                translating[id] = false;
                if (indicator) indicator.classList.add('hidden');
                if (states[id] === 'playing' || states[id] === 'paused') { cancelThenSpeak(id); }
                return;
            }

            var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

            function applyTranslation(text, source) {
                cached[cacheKey] = text;
                var t = el(id + '-translated');
                if (t) t.textContent = text;
                var newMap = buildTranscript(id, text);
                if (newMap) wordMaps[id] = newMap;
                if (source === 'mymemory') {
                    setVoiceWarning(id, 'ℹ Translated via MyMemory (AI engine unavailable)');
                } else {
                    setVoiceWarning(id, null);
                }
            }

            function onTranslationDone(wasActive) {
                translating[id] = false;
                if (indicator) indicator.classList.add('hidden');
                if (wasActive) { cancelThenSpeak(id); }
            }

            var wasActive = states[id] === 'playing' || states[id] === 'paused';

            // Try AI engine first
            fetch(translateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ text: originalText, target_language: langCode }),
            })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data.translated_text) {
                    applyTranslation(data.translated_text, 'ai');
                    onTranslationDone(wasActive);
                } else {
                    throw new Error('no translated_text in response');
                }
            })
            .catch(function() {
                // AI engine failed → try MyMemory fallback
                console.info('AI translation unavailable, trying MyMemory...');
                myMemoryTranslate(id, originalText, langCode, cacheKey, function(translated) {
                    if (translated) {
                        applyTranslation(translated, 'mymemory');
                    } else {
                        setVoiceWarning(id, '⚠ Translation unavailable — narrating in English');
                    }
                    onTranslationDone(wasActive);
                });
            });
        };
    })();
    </script>
</x-app-layout>
