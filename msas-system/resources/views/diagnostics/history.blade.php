<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg> {{ __('AI Diagnostic Reports') }}
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
                $sev === 'Critical'                => ['border'=>'border-red-500',    'hbg'=>'bg-red-600',    'htext'=>'text-white',   'ibg'=>'bg-red-50',    'badge'=>'bg-red-100 text-red-800 border-red-200',   'icon'=>'<svg width="10" height="10" viewBox="0 0 10 10" fill="#dc2626"><circle cx="5" cy="5" r="5"/></svg>'],
                $sev === 'Severe'                  => ['border'=>'border-orange-500', 'hbg'=>'bg-orange-500', 'htext'=>'text-white',   'ibg'=>'bg-orange-50', 'badge'=>'bg-orange-100 text-orange-800 border-orange-200','icon'=>'<svg width="10" height="10" viewBox="0 0 10 10" fill="#f97316"><circle cx="5" cy="5" r="5"/></svg>'],
                $sev === 'Moderate'                => ['border'=>'border-amber-500',  'hbg'=>'bg-amber-500',  'htext'=>'text-white',   'ibg'=>'bg-amber-50',  'badge'=>'bg-amber-100 text-amber-800 border-amber-200',  'icon'=>'<svg width="10" height="10" viewBox="0 0 10 10" fill="#fbbf24"><circle cx="5" cy="5" r="5"/></svg>'],
                $sev === 'Mild'                    => ['border'=>'border-yellow-400', 'hbg'=>'bg-yellow-400', 'htext'=>'text-slate-900','ibg'=>'bg-yellow-50', 'badge'=>'bg-yellow-100 text-yellow-800 border-yellow-200','icon'=>'<svg width="10" height="10" viewBox="0 0 10 10" fill="#fbbf24"><circle cx="5" cy="5" r="5"/></svg>'],
                ($diagnosis->health_status ?? '') === 'Healthy' => ['border'=>'border-emerald-500','hbg'=>'bg-emerald-600','htext'=>'text-white','ibg'=>'bg-emerald-50','badge'=>'bg-emerald-100 text-emerald-800 border-emerald-200','icon'=>'<svg width="10" height="10" viewBox="0 0 10 10" fill="#10b981"><circle cx="5" cy="5" r="5"/></svg>'],
                default                            => ['border'=>'border-blue-400',   'hbg'=>'bg-blue-600',   'htext'=>'text-white',   'ibg'=>'bg-blue-50',   'badge'=>'bg-blue-100 text-blue-800 border-blue-200',   'icon'=>'<svg width="10" height="10" viewBox="0 0 10 10" fill="#3b82f6"><circle cx="5" cy="5" r="5"/></svg>'],
            };

            // ── Type label ────────────────────────────────────────────────────
            $typeIcon  = match($diagnosis->type) { 'plant'=>'<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12m0 0C12 7 7 4 2 5c0 5 4 8 10 7zm0 0c0-5 5-8 10-7-1 5-5 8-10 7"/></svg>', 'soil'=>'<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22v-5m0 0c-2-1-5-1.5-7 0m7 0c2-1 5-1.5 7 0M5 21h14M12 17V8m0-5v2M9 5.5C9 4 10 3 12 3s3 1 3 2.5"/></svg>', default=>'<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5S3 9 3 7.5A2.5 2.5 0 018 7.5H9M4.5 10.5H19.5M19.5 10.5S21 9 21 7.5A2.5 2.5 0 0016 7.5H15M19.5 10.5V16M4.5 10.5V16M7 16v3m10-3v3M7 16h10"/></svg>' };
            $typeLbl   = match($diagnosis->type) { 'plant'=>'Crop / Plant', 'soil'=>'Soil Assessment', default=>'Livestock' };


            // ── Feedback ──────────────────────────────────────────────────────
            $myFeedback = $feedbackReady ? $diagnosis->myFeedback : null;
        @endphp

        {{-- ═══ REPORT CARD ═══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl shadow-md border-l-4 {{ $theme['border'] }} overflow-hidden">

            {{-- ── Card Header ──────────────────────────────────────────────── --}}
            <div class="{{ $theme['hbg'] }} {{ $theme['htext'] }} px-5 py-3 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                    <span>{!! $typeIcon !!}</span>
                    <div>
                        <div class="font-extrabold text-sm leading-tight">{{ $diagnosis->subject_name ?? $typeLbl }}</div>
                        @if($diagnosis->scientific_name && $diagnosis->scientific_name !== 'Unknown')
                        <div class="opacity-80 italic text-xs leading-tight">{{ $diagnosis->scientific_name }}</div>
                        @endif
                        @if($diagnosis->scan_ref)
                        <div class="opacity-70 text-[10px] font-mono leading-tight mt-0.5">{{ $diagnosis->scan_ref }}</div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs opacity-90">
                    <span>{{ $diagnosis->created_at->format('M j, Y  g:i A') }}</span>
                    <span class="bg-white/20 px-2 py-0.5 rounded-full font-bold text-[10px]">{{ $diagnosis->statusLabel }}</span>
                </div>
            </div>

            {{-- ── AI Unavailable Banner (only for failed scans) ───────────────── --}}
            @if($diagnosis->status === 'needs_review')
            <div class="bg-amber-50 border-b border-amber-200 px-5 py-3 flex items-start gap-3">
                <svg width="22" height="22" fill="none" stroke="#b45309" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0115-6.7M20 15a9 9 0 01-15 6.7"/></svg>
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

            {{-- ── Listen to your result (translated, spoken, highlighted) ── --}}
            @include('diagnostics.partials.listen', ['diagnosis' => $diagnosis])

            {{-- ── Main Report Grid ──────────────────────────────────────────── --}}
            <div class="p-5 grid grid-cols-1 md:grid-cols-4 gap-5">

                {{-- Left column: image + meta --}}
                <div class="md:col-span-1 space-y-3">
                    {{-- Image --}}
                    <div class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 aspect-square">
                        {{-- Prefer DB thumbnail (survives restarts); fall back to the authenticated image route --}}
                        <img src="{{ $diagnosis->image_thumbnail ?? route('diagnostics.image', $diagnosis) }}"
                             alt="Scanned Image"
                             class="w-full h-full object-cover" loading="lazy"
                             onerror="imgError(this)">

                        {{-- Confidence overlay --}}
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-3">
                            <div class="text-white text-[10px] font-bold uppercase opacity-70" data-i18n="AI Confidence">{{ __('AI Confidence') }}</div>
                            @if($diagnosis->confidence_score !== null)
                            <div class="flex items-baseline gap-1">
                                <span class="text-white font-extrabold text-2xl leading-none">{{ number_format($diagnosis->confidence_score, 0) }}</span>
                                <span class="text-white/70 text-xs font-bold">%</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-1.5 mt-1.5">
                                <div class="h-1.5 rounded-full transition-all {{ $diagnosis->confidence_score >= 65 ? 'bg-emerald-400' : 'bg-red-400' }}"
                                     style="width:{{ min((float)$diagnosis->confidence_score,100) }}%"></div>
                            </div>
                            @else
                            <div class="text-white font-extrabold text-sm leading-none">N/A</div>
                            <div class="text-white/70 text-[10px] mt-0.5">AI Analysis Unavailable</div>
                            @endif
                        </div>

                        @if($diagnosis->confidence_score !== null && $diagnosis->confidence_score < 65)
                        <div class="absolute top-2 right-2 bg-amber-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full">LOW CONF.</div>
                        @endif
                    </div>

                    {{-- Badges --}}
                    <div class="space-y-1.5">
                        @if($sev)
                        <div class="flex items-center justify-between text-xs px-3 py-1.5 rounded-lg border {{ $theme['badge'] }}">
                            <span class="font-bold uppercase tracking-wide text-[10px]" data-i18n="Severity">{{ __('Severity') }}</span>
                            <span class="font-extrabold inline-flex items-center gap-1">{!! $theme['icon'] !!} {{ $sev }}</span>
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
                        @if($diagnosis->confidence_score !== null && $diagnosis->confidence_score < 60)
                        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs px-3 py-2 rounded-lg max-w-xs">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span data-i18n="Confidence is limited. Please capture a clearer image or consult an expert.">{{ __('Confidence is limited. Please capture a clearer image or consult an expert.') }}</span>
                        </div>
                        @endif
                    </div>

                    @include('diagnostics.partials.outcome-history')

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

                    {{-- ── Sample Collection Location (spec Section 6C) ──────────────── --}}
                    @php $loc = $diagnosis->collectionLocation; @endphp
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                            <i class="fa-solid fa-location-dot text-[9px]"></i> <span data-i18n="Sample Collection Location">{{ __('Sample Collection Location') }}</span>
                        </div>
                        @if(!$loc)
                            <p class="text-xs text-slate-400 italic">{{ __('No collection location was recorded for this scan.') }}</p>
                        @else
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs mb-2">
                                @if($loc->state)<div><span class="text-slate-400">{{ __('State') }}:</span> <span class="font-semibold text-slate-700">{{ $loc->state }}</span></div>@endif
                                @if($loc->lga)<div><span class="text-slate-400">{{ __('LGA') }}:</span> <span class="font-semibold text-slate-700">{{ $loc->lga }}</span></div>@endif
                                @if($loc->ward)<div><span class="text-slate-400">{{ __('Ward') }}:</span> <span class="font-semibold text-slate-700">{{ $loc->ward }}</span></div>@endif
                                @if($loc->community)<div><span class="text-slate-400">{{ __('Community') }}:</span> <span class="font-semibold text-slate-700">{{ $loc->community }}</span></div>@endif
                                @if($loc->postal_code)<div><span class="text-slate-400">{{ __('Postal Code') }}:</span> <span class="font-semibold text-slate-700">{{ $loc->postal_code }}</span></div>@endif
                                @if($loc->address_landmark)<div class="col-span-2 sm:col-span-3"><span class="text-slate-400">{{ __('Address/Landmark') }}:</span> <span class="font-semibold text-slate-700">{{ $loc->address_landmark }}</span></div>@endif
                            </div>

                            @if($loc->hasCoordinates())
                            <div class="flex items-center gap-2 text-xs bg-slate-50 rounded-lg px-3 py-2 mb-2">
                                <i class="fa-solid fa-crosshairs text-slate-400"></i>
                                <span class="font-mono text-slate-700">{{ number_format($loc->latitude, 6) }}, {{ number_format($loc->longitude, 6) }}</span>
                                @if($loc->accuracy_meters)<span class="text-slate-400">(±{{ round($loc->accuracy_meters) }}m)</span>@endif
                                <a href="https://www.google.com/maps?q={{ $loc->latitude }},{{ $loc->longitude }}" target="_blank" rel="noopener" class="ml-auto text-emerald-600 hover:underline font-semibold">{{ __('Open in Maps') }} ↗</a>
                            </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-full
                                    {{ $loc->verification_status === 'boundary_mismatch_flagged' ? 'bg-amber-100 text-amber-800' : ($loc->user_confirmed ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $loc->provenanceLabel }}
                                </span>
                                @if($loc->verification_status === 'boundary_mismatch_flagged')
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-800">
                                    <i class="fa-solid fa-triangle-exclamation"></i> {{ __('Location outside expected range — unverified') }}
                                </span>
                                @endif
                                @if($loc->differs_from_scan_location)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-full bg-sky-100 text-sky-800">
                                    {{ __('Scanned at a different location than collected') }}
                                </span>
                                @endif
                                @if($loc->collected_at)
                                <span class="text-[10px] text-slate-400 ml-auto">{{ __('Collected') }}: {{ $loc->collected_at->format('M j, Y H:i') }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- ── AI Confidence Provenance (spec Section 4.5 / 6B) ──────────── --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                            <i class="fa-solid fa-circle-info text-[9px]"></i> <span data-i18n="What this confidence score means">{{ __('What this confidence score means') }}</span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            {{ $diagnosis->confidence_interpretation ?: __('This confidence value is the AI model\'s own self-reported certainty, not a statistically calibrated probability. Treat it as directional guidance, not clinical certainty.') }}
                        </p>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[10px] text-slate-400">
                            @if($diagnosis->ai_model_name)<span>{{ __('Model') }}: {{ $diagnosis->ai_model_name }}</span>@endif
                            <span>{{ __('Validation status') }}: {{ ucfirst($diagnosis->validation_status ?? 'unvalidated') }}</span>
                            @if($diagnosis->confidence_decision)<span>{{ __('Decision') }}: {{ str_replace('_', ' ', $diagnosis->confidence_decision) }}</span>@endif
                            <span>{{ __('Data quality') }}: {{ str_replace('_', ' ', $diagnosis->dataQualityStatus) }}</span>
                        </div>
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
                                {{ $myFeedback->rating === 'thumbs_up' ? 'Marked Accurate' : 'Marked Inaccurate' }}
                            </span>
                            <button onclick="toggleSection('fb-{{ $diagnosis->id }}')" class="text-[10px] text-slate-400 hover:text-slate-600 ml-auto">Edit</button>
                            @else
                            <button onclick="submitFeedback('{{ $diagnosis->id }}','thumbs_up')"
                                class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold transition"><span data-i18n="Accurate">{{ __('Accurate') }}</span></button>
                            <button onclick="submitFeedback('{{ $diagnosis->id }}','thumbs_down')"
                                class="px-3 py-1 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-full text-xs font-bold transition"><span data-i18n="Not Accurate">{{ __('Not Accurate') }}</span></button>
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
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4"><svg width="48" height="48" fill="none" stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/></svg></div>
            <h3 class="text-xl font-bold text-slate-800 mb-2" data-i18n="No Scans Yet">{{ __('No Scans Yet') }}</h3>
            <p class="text-slate-500 mb-6 max-w-sm mx-auto text-sm" data-i18n="Upload a photo of a plant, animal, or soil sample to get an instant AI-powered diagnosis.">{{ __('Upload a photo of a plant, animal, or soil sample to get an instant AI-powered diagnosis.') }}</p>
            <a href="{{ route('diagnostics.scan') }}" class="inline-flex items-center gap-2 bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-emerald-700 hover:-translate-y-0.5 transition text-sm">
                <i class="fa-solid fa-camera"></i> <span data-i18n="Run Your First Scan">{{ __('Run Your First Scan') }}</span>
            </a>
        </div>
        @endforelse

    </div>

    {{-- ─── Global Scripts ──────────────────────────────────────────────────── --}}
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

    </script>
</x-app-layout>
