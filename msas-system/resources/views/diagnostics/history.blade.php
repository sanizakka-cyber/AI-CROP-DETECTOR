<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
                <span class="text-2xl">📋</span> {{ __('Diagnostic Reports & History') }}
            </h2>
            <a href="{{ route('diagnostics.scan') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold shadow hover:bg-emerald-700 transition flex items-center gap-2 text-sm">
                <span>📷</span> New Scan
            </a>
        </div>
    </x-slot>

    <div class="space-y-8">

        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-md shadow-sm">
                <p class="text-green-700 font-medium flex items-center gap-2"><span>✅</span> {{ session('success') }}</p>
            </div>
        @endif

        @forelse($diagnoses as $diagnosis)
        @php
            // ── Colour scheme by urgency ──────────────────────────────────────
            $urgColor  = match($diagnosis->urgency_level) {
                'Emergency' => 'red',
                'High'      => 'red',
                'Medium'    => 'amber',
                default     => 'emerald',
            };
            $sevColor  = match($diagnosis->severity_level) {
                'Critical' => 'bg-red-100 text-red-700 border-red-200',
                'Severe'   => 'bg-orange-100 text-orange-700 border-orange-200',
                'Moderate' => 'bg-amber-100 text-amber-700 border-amber-200',
                'Mild'     => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                default    => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            };

            // ── TTS text ──────────────────────────────────────────────────────
            $typeLbl = match($diagnosis->type) { 'plant'=>'Crop', 'soil'=>'Soil', default=>'Livestock' };
            $ttsLines = ["AI Diagnostic Report. Scan type: {$typeLbl}."];
            if ($diagnosis->subject_name)     $ttsLines[] = "Subject identified: {$diagnosis->subject_name}.";
            if ($diagnosis->scientific_name && $diagnosis->scientific_name !== 'Unknown') {
                $ttsLines[] = "Scientific name: {$diagnosis->scientific_name}.";
            }
            if ($diagnosis->detected_part)    $ttsLines[] = "Detected part: {$diagnosis->detected_part}.";
            if ($diagnosis->health_status)    $ttsLines[] = "Health status: {$diagnosis->health_status}.";
            $ttsLines[] = "Condition identified: {$diagnosis->disease_name}.";
            $ttsLines[] = "AI confidence: {$diagnosis->confidence_score} percent.";
            if ($diagnosis->severity_level)   $ttsLines[] = "Severity: {$diagnosis->severity_level}.";
            $ttsLines[] = "Urgency level: {$diagnosis->urgency_level}.";
            if ($diagnosis->symptoms_identified) $ttsLines[] = "Symptoms: {$diagnosis->symptoms_identified}.";
            if ($diagnosis->cause)            $ttsLines[] = "Root cause: {$diagnosis->cause}.";
            if ($diagnosis->environmental_factors) $ttsLines[] = "Environmental factors: {$diagnosis->environmental_factors}.";
            if ($diagnosis->nutrient_deficiencies && $diagnosis->nutrient_deficiencies !== 'None detected') {
                $ttsLines[] = "Nutrient deficiencies: {$diagnosis->nutrient_deficiencies}.";
            }
            if ($diagnosis->pest_detection && $diagnosis->pest_detection !== 'No pest detected') {
                $ttsLines[] = "Pest detection: {$diagnosis->pest_detection}.";
            }
            if ($diagnosis->first_aid_steps)  $ttsLines[] = "First aid: {$diagnosis->first_aid_steps}.";
            if ($diagnosis->recommended_medication) $ttsLines[] = "Recommended treatment: {$diagnosis->recommended_medication}.";
            if ($diagnosis->preventive_measures) $ttsLines[] = "Prevention: {$diagnosis->preventive_measures}.";
            if ($diagnosis->fertilizer_recommendation) $ttsLines[] = "Fertilizer recommendation: {$diagnosis->fertilizer_recommendation}.";
            if ($diagnosis->recovery_period)  $ttsLines[] = "Estimated recovery: {$diagnosis->recovery_period}.";
            if ($diagnosis->vet_referral_advice) $ttsLines[] = "Specialist advice: {$diagnosis->vet_referral_advice}.";
            $ttsLines[] = "Always consult a certified specialist before applying any treatment.";
            $ttsText = implode(' ', $ttsLines);
            $ttsId   = 'tts-' . $diagnosis->id;

            // ── Existing user feedback ────────────────────────────────────────
            $myFeedback = $feedbackReady ? $diagnosis->myFeedback : null;
        @endphp

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            {{-- ── Status Bar ──────────────────────────────────────────────── --}}
            <div class="bg-{{ $urgColor }}-500 px-4 md:px-6 py-2.5 flex justify-between items-center text-white flex-wrap gap-2">
                <div class="font-bold text-sm uppercase tracking-wider flex items-center gap-2">
                    @if($diagnosis->type === 'plant') <span>🌿</span> Plant / Crop Analysis
                    @elseif($diagnosis->type === 'soil') <span>🌱</span> Soil Assessment
                    @else <span>🐄</span> Livestock Health Analysis
                    @endif
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="opacity-90">{{ $diagnosis->created_at->format('D, M j, Y g:i A') }}</span>
                    <span class="bg-white/20 px-2.5 py-1 rounded-full font-bold capitalize">{{ $diagnosis->status }}</span>
                </div>
            </div>

            {{-- ── AI Voice Banner ─────────────────────────────────────────── --}}
            <div class="bg-slate-50 border-b border-slate-100 px-4 md:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Language selector --}}
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-language text-slate-500 text-sm"></i>
                        <select id="{{ $ttsId }}-lang" onchange="ttsChangeLang('{{ $ttsId }}', this.value)"
                            class="border border-slate-200 rounded-lg text-xs px-2 py-1.5 text-slate-600 focus:outline-none focus:border-emerald-400 bg-white">
                            <option value="en">English</option>
                            <option value="ha">Hausa</option>
                            <option value="fr">Français</option>
                            <option value="yo">Yorùbá</option>
                            <option value="ig">Igbo</option>
                        </select>
                    </div>

                    {{-- Controls --}}
                    <div class="flex items-center gap-1.5" id="{{ $ttsId }}-controls">
                        <button onclick="ttsPlay('{{ $ttsId }}')" title="Play"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition">
                            <i class="fa-solid fa-play text-[10px]"></i> Listen
                        </button>
                        <button onclick="ttsPause('{{ $ttsId }}')" title="Pause"
                            class="p-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs transition hidden" id="{{ $ttsId }}-pause">
                            <i class="fa-solid fa-pause text-[10px]"></i>
                        </button>
                        <button onclick="ttsStop('{{ $ttsId }}')" title="Stop"
                            class="p-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg text-xs transition hidden" id="{{ $ttsId }}-stop">
                            <i class="fa-solid fa-stop text-[10px]"></i>
                        </button>
                        <button onclick="ttsReplay('{{ $ttsId }}')" title="Replay"
                            class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs transition hidden" id="{{ $ttsId }}-replay">
                            <i class="fa-solid fa-rotate-right text-[10px]"></i>
                        </button>
                        <select onchange="ttsSetSpeed('{{ $ttsId }}', this.value)"
                            class="border border-slate-200 rounded-lg text-xs px-2 py-1.5 text-slate-600 focus:outline-none focus:border-emerald-400 bg-white">
                            <option value="0.75">0.75×</option>
                            <option value="1" selected>1×</option>
                            <option value="1.25">1.25×</option>
                            <option value="1.5">1.5×</option>
                        </select>
                    </div>

                    <span class="text-slate-400 text-xs hidden md:inline flex-1">AI Voice Summary — listen to this diagnosis read aloud</span>

                    {{-- Hidden data --}}
                    <span id="{{ $ttsId }}-text" class="hidden">{{ $ttsText }}</span>
                    <span id="{{ $ttsId }}-translated" class="hidden"></span>
                    <span id="{{ $ttsId }}-state" class="hidden">stopped</span>
                    <span id="{{ $ttsId }}-speed" class="hidden">1</span>
                    <span id="{{ $ttsId }}-ai-url" class="hidden">{{ rtrim(config('services.ai_engine.url', ''), '/') }}/translate</span>
                </div>
                {{-- Translating indicator --}}
                <div id="{{ $ttsId }}-translating" class="hidden mt-2 text-xs text-amber-600 flex items-center gap-1">
                    <i class="fa-solid fa-spinner fa-spin"></i> Translating to selected language...
                </div>
            </div>

            {{-- ── Subject Identity Hero ───────────────────────────────────── --}}
            @if($diagnosis->subject_name || $diagnosis->scientific_name)
            <div class="bg-gradient-to-r from-slate-800 to-slate-700 text-white px-4 md:px-6 py-4 flex flex-wrap items-center gap-4">
                <div class="text-4xl">
                    @if($diagnosis->type === 'plant') 🌿
                    @elseif($diagnosis->type === 'soil') 🌱
                    @else 🐄
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">AI Identified Subject</div>
                    <h3 class="text-xl font-extrabold text-white truncate">{{ $diagnosis->subject_name ?? 'Unknown' }}</h3>
                    @if($diagnosis->scientific_name && $diagnosis->scientific_name !== 'Unknown')
                        <p class="text-slate-300 text-sm italic">{{ $diagnosis->scientific_name }}</p>
                    @endif
                </div>
                @if($diagnosis->detected_part)
                <div class="shrink-0 text-right">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Detected Part</div>
                    <div class="text-white font-bold text-sm">{{ $diagnosis->detected_part }}</div>
                </div>
                @endif
            </div>
            @endif

            <div class="p-4 md:p-6 lg:p-8">
                <div class="grid md:grid-cols-3 gap-6 md:gap-8">

                    {{-- ── Left Column: Image + Confidence ─────────────────── --}}
                    <div class="col-span-1 space-y-4">
                        <div class="rounded-xl overflow-hidden shadow-sm border border-slate-200 relative aspect-square bg-slate-100">
                            <img src="{{ Storage::url($diagnosis->image_path) }}" alt="Scanned Image"
                                 class="w-full h-full object-cover" loading="lazy">
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-3">
                                <div class="flex justify-between items-end">
                                    <div>
                                        <div class="text-white/60 text-[10px] font-bold uppercase">AI Confidence</div>
                                        <div class="text-white font-extrabold text-xl">{{ number_format($diagnosis->confidence_score, 0) }}%</div>
                                    </div>
                                    @if($diagnosis->health_status)
                                    <div class="text-right">
                                        <div class="text-white/60 text-[10px] font-bold uppercase">Health</div>
                                        <div class="text-white font-bold text-sm">{{ $diagnosis->health_status }}</div>
                                    </div>
                                    @endif
                                </div>
                                {{-- Confidence bar --}}
                                <div class="w-full bg-white/20 rounded-full h-1.5 mt-2">
                                    <div class="h-1.5 rounded-full {{ $diagnosis->confidence_score >= 75 ? 'bg-emerald-400' : ($diagnosis->confidence_score >= 50 ? 'bg-amber-400' : 'bg-red-400') }}"
                                         style="width: {{ min($diagnosis->confidence_score, 100) }}%"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Severity + Urgency badges --}}
                        <div class="flex gap-2 flex-wrap">
                            @if($diagnosis->severity_level)
                            <span class="flex-1 text-center px-3 py-1.5 rounded-lg text-xs font-bold border {{ $sevColor }}">
                                ⚡ {{ $diagnosis->severity_level }} Severity
                            </span>
                            @endif
                            <span class="flex-1 text-center px-3 py-1.5 rounded-lg text-xs font-bold border
                                {{ $urgColor === 'red' ? 'bg-red-100 text-red-700 border-red-200' :
                                  ($urgColor === 'amber' ? 'bg-amber-100 text-amber-700 border-amber-200' :
                                  'bg-emerald-100 text-emerald-700 border-emerald-200') }}">
                                🔔 {{ $diagnosis->urgency_level }} Urgency
                            </span>
                        </div>

                        {{-- Download button --}}
                        <button onclick="downloadReport({{ $diagnosis->id }})"
                            class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-download text-xs"></i> Download Report
                        </button>
                    </div>

                    {{-- ── Right Column: Full Report ────────────────────────── --}}
                    <div class="col-span-1 md:col-span-2 space-y-4">

                        {{-- Disease Name & Status --}}
                        <div class="flex justify-between items-start flex-wrap gap-2">
                            <div>
                                <h3 class="text-2xl font-extrabold text-slate-800">{{ $diagnosis->disease_name }}</h3>
                                <p class="text-slate-500 text-sm mt-0.5">Status: <span class="capitalize font-semibold text-slate-700">{{ $diagnosis->status }}</span></p>
                            </div>
                        </div>

                        {{-- Symptoms + Cause --}}
                        @if($diagnosis->symptoms_identified || $diagnosis->cause)
                        <div class="grid sm:grid-cols-2 gap-3">
                            @if($diagnosis->symptoms_identified)
                            <div class="bg-red-50 p-4 rounded-xl border border-red-100">
                                <div class="text-xs font-bold text-red-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <span>🔬</span> Symptoms Identified
                                </div>
                                <p class="text-sm text-red-800">{{ $diagnosis->symptoms_identified }}</p>
                            </div>
                            @endif
                            @if($diagnosis->cause)
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <span>🔍</span> Root Cause
                                </div>
                                <p class="text-sm text-slate-700">{{ $diagnosis->cause }}</p>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- Environmental + Nutrients + Pests --}}
                        @php
                            $hasEnvRow = $diagnosis->environmental_factors || $diagnosis->nutrient_deficiencies || $diagnosis->pest_detection;
                        @endphp
                        @if($hasEnvRow)
                        <div class="grid sm:grid-cols-3 gap-3">
                            @if($diagnosis->environmental_factors)
                            <div class="bg-sky-50 p-3 rounded-xl border border-sky-100">
                                <div class="text-[10px] font-bold text-sky-500 uppercase tracking-wider mb-1.5 flex items-center gap-1"><span>🌡️</span> Environmental</div>
                                <p class="text-xs text-sky-800">{{ $diagnosis->environmental_factors }}</p>
                            </div>
                            @endif
                            @if($diagnosis->nutrient_deficiencies && $diagnosis->nutrient_deficiencies !== 'None detected')
                            <div class="bg-lime-50 p-3 rounded-xl border border-lime-100">
                                <div class="text-[10px] font-bold text-lime-600 uppercase tracking-wider mb-1.5 flex items-center gap-1"><span>🧪</span> Nutrient Deficiency</div>
                                <p class="text-xs text-lime-800">{{ $diagnosis->nutrient_deficiencies }}</p>
                            </div>
                            @endif
                            @if($diagnosis->pest_detection && $diagnosis->pest_detection !== 'No pest detected')
                            <div class="bg-orange-50 p-3 rounded-xl border border-orange-100">
                                <div class="text-[10px] font-bold text-orange-500 uppercase tracking-wider mb-1.5 flex items-center gap-1"><span>🐛</span> Pest Detection</div>
                                <p class="text-xs text-orange-800">{{ $diagnosis->pest_detection }}</p>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- First Aid --}}
                        @if($diagnosis->first_aid_steps)
                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                            <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-2 flex items-center gap-1">
                                <span>🚑</span> Immediate First Aid
                            </div>
                            <p class="text-sm text-blue-800 whitespace-pre-line">{{ $diagnosis->first_aid_steps }}</p>
                        </div>
                        @endif

                        {{-- Treatment + Fertilizer --}}
                        <div class="grid sm:grid-cols-2 gap-3">
                            @if($diagnosis->recommended_medication)
                            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                                <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <span>💊</span> Recommended Treatment
                                </div>
                                <p class="text-sm text-emerald-800">{{ $diagnosis->recommended_medication }}</p>
                            </div>
                            @endif
                            @if($diagnosis->fertilizer_recommendation)
                            <div class="bg-teal-50 p-4 rounded-xl border border-teal-100">
                                <div class="text-xs font-bold text-teal-600 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <span>🌾</span> Fertilizer Recommendation
                                </div>
                                <p class="text-sm text-teal-800">{{ $diagnosis->fertilizer_recommendation }}</p>
                            </div>
                            @endif
                        </div>

                        {{-- Prevention + Best Practices --}}
                        @if($diagnosis->preventive_measures || $diagnosis->best_practices)
                        <div class="grid sm:grid-cols-2 gap-3">
                            @if($diagnosis->preventive_measures)
                            <div class="bg-violet-50 p-4 rounded-xl border border-violet-100">
                                <div class="text-xs font-bold text-violet-600 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <span>🛡️</span> Preventive Measures
                                </div>
                                <p class="text-sm text-violet-800">{{ $diagnosis->preventive_measures }}</p>
                            </div>
                            @endif
                            @if($diagnosis->best_practices)
                            <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                                <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <span>📚</span> Best Practices
                                </div>
                                <p class="text-sm text-indigo-800">{{ $diagnosis->best_practices }}</p>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- Recovery + Specialist Advice --}}
                        @if($diagnosis->recovery_period || $diagnosis->vet_referral_advice)
                        <div class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                            @if($diagnosis->recovery_period)
                            <div class="flex items-center gap-2 mb-2">
                                <span>⏱️</span>
                                <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Estimated Recovery</span>
                                <span class="text-amber-800 font-bold text-sm">{{ $diagnosis->recovery_period }}</span>
                            </div>
                            @endif
                            @if($diagnosis->vet_referral_advice)
                            <div class="flex items-start gap-2 {{ $diagnosis->recovery_period ? 'border-t border-amber-200 pt-2 mt-1' : '' }}">
                                <span class="shrink-0">👨‍⚕️</span>
                                <p class="text-xs text-amber-800">{{ $diagnosis->vet_referral_advice }}</p>
                            </div>
                            @endif
                        </div>
                        @endif

                    </div>{{-- end right col --}}
                </div>{{-- end grid --}}

                {{-- ── Explainable AI Section ──────────────────────────────── --}}
                @if($diagnosis->explanation)
                <div class="mt-6 border border-slate-200 rounded-xl overflow-hidden">
                    <button onclick="toggleExplain('{{ $diagnosis->id }}')"
                        class="w-full flex items-center justify-between px-5 py-3.5 bg-slate-50 hover:bg-slate-100 transition text-left">
                        <div class="flex items-center gap-2 font-bold text-slate-700 text-sm">
                            <span>🧠</span> Why this diagnosis? <span class="text-slate-400 font-normal">(Explainable AI)</span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform" id="explain-icon-{{ $diagnosis->id }}"></i>
                    </button>
                    <div id="explain-body-{{ $diagnosis->id }}" class="hidden px-5 py-4 text-sm text-slate-700 bg-white border-t border-slate-100 leading-relaxed">
                        <p>{{ $diagnosis->explanation }}</p>
                        <p class="text-xs text-slate-400 mt-3 italic">This explanation describes the specific visual evidence the AI detected in your uploaded image to reach this conclusion.</p>
                    </div>
                </div>
                @endif

                {{-- ── Disclaimer ───────────────────────────────────────────── --}}
                <div class="mt-4 flex items-start gap-2 text-[10px] text-slate-500 leading-tight">
                    <span class="shrink-0">⚠️</span>
                    <p>Disclaimer: This AI analysis provides guidance based on visual symptoms only. Always consult a certified
                    {{ $diagnosis->type === 'soil' ? 'Agronomist or Soil Scientist' : ($diagnosis->type === 'animal' ? 'Veterinary Doctor' : 'Agronomist') }}
                    before applying treatments. Visual-only diagnosis may not detect all conditions.</p>
                </div>

                {{-- ── Feedback Section ─────────────────────────────────────── --}}
                @if($feedbackReady && $diagnosis->status === 'reviewed')
                <div class="mt-6 border-t border-slate-100 pt-5">
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <span>💬</span> Was this diagnosis accurate?
                        @if($myFeedback)
                        <span class="ml-auto text-emerald-600 font-bold normal-case">✓ Feedback submitted</span>
                        @endif
                    </div>

                    @if($myFeedback)
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="px-4 py-2 rounded-xl text-sm font-bold {{ $myFeedback->rating === 'thumbs_up' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ $myFeedback->rating === 'thumbs_up' ? '👍 Accurate' : '👎 Not Accurate' }}
                        </span>
                        @if($myFeedback->correct_disease)
                        <span class="text-sm text-slate-600">Corrected to: <strong>{{ $myFeedback->correct_disease }}</strong></span>
                        @endif
                        <button onclick="document.getElementById('feedback-form-{{ $diagnosis->id }}').classList.toggle('hidden')"
                            class="ml-auto text-xs text-slate-400 hover:text-slate-600 transition">Edit feedback</button>
                    </div>
                    @else
                    <div class="flex gap-3">
                        <button onclick="document.getElementById('feedback-form-{{ $diagnosis->id }}').classList.toggle('hidden')"
                            class="feedback-btn px-5 py-2.5 rounded-xl text-sm font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition flex items-center gap-2">
                            👍 Accurate
                        </button>
                        <button onclick="showNegativeFeedback('{{ $diagnosis->id }}')"
                            class="px-5 py-2.5 rounded-xl text-sm font-bold bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 transition flex items-center gap-2">
                            👎 Not Accurate
                        </button>
                    </div>
                    @endif

                    {{-- Feedback Form --}}
                    <form id="feedback-form-{{ $diagnosis->id }}"
                          action="{{ route('diagnostics.feedback', $diagnosis) }}"
                          method="POST"
                          class="{{ $myFeedback ? 'hidden' : 'hidden' }} mt-4 bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-3">
                        @csrf
                        <input type="hidden" name="rating" id="feedback-rating-{{ $diagnosis->id }}" value="thumbs_up">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">What was the correct diagnosis? (optional)</label>
                            <input type="text" name="correct_disease"
                                   value="{{ $myFeedback?->correct_disease }}"
                                   placeholder="e.g., Cassava Brown Streak Disease"
                                   class="w-full border-slate-200 rounded-lg text-sm focus:ring-emerald-400 focus:border-emerald-400">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Additional notes (optional)</label>
                            <textarea name="notes" rows="2"
                                      placeholder="Any extra context that might help improve the AI..."
                                      class="w-full border-slate-200 rounded-lg text-sm focus:ring-emerald-400 focus:border-emerald-400">{{ $myFeedback?->notes }}</textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm px-5 py-2 rounded-xl transition">
                                Submit Feedback
                            </button>
                            <button type="button"
                                onclick="document.getElementById('feedback-form-{{ $diagnosis->id }}').classList.add('hidden')"
                                class="text-sm text-slate-500 hover:text-slate-700 transition px-3">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
                @endif

            </div>{{-- end card body --}}
        </div>{{-- end card --}}

        @empty
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-10 md:p-12 text-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-4xl mx-auto mb-4 border border-slate-100">🗂️</div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">No Scans Found</h3>
            <p class="text-slate-500 mb-6 max-w-md mx-auto">You haven't run any AI diagnostic scans yet. Upload a photo of a plant or animal to detect issues and get an instant 20-point AI report.</p>
            <a href="{{ route('diagnostics.scan') }}" class="inline-block bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-emerald-500/30 hover:bg-emerald-700 hover:-translate-y-0.5 transition">
                Run Your First Scan
            </a>
        </div>
        @endforelse

    </div>

    {{-- ─── Scripts ─────────────────────────────────────────────────────── --}}
    <style>
        .tts-speaking { animation: tts-pulse 1.4s ease-in-out infinite; }
        @keyframes tts-pulse { 0%,100%{opacity:1} 50%{opacity:.55} }
    </style>

    <script>
    // ── Explainable AI toggle ──────────────────────────────────────────────────
    function toggleExplain(id) {
        var body = document.getElementById('explain-body-' + id);
        var icon = document.getElementById('explain-icon-' + id);
        body.classList.toggle('hidden');
        if(icon) icon.style.transform = body.classList.contains('hidden') ? '' : 'rotate(180deg)';
    }

    // ── Feedback helpers ──────────────────────────────────────────────────────
    function showNegativeFeedback(id) {
        document.getElementById('feedback-rating-' + id).value = 'thumbs_down';
        document.getElementById('feedback-form-' + id).classList.remove('hidden');
    }
    document.querySelectorAll('.feedback-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var form = btn.closest('.mt-6').querySelector('form');
            if (!form) return;
            form.querySelector('input[name="rating"]').value = 'thumbs_up';
            form.classList.remove('hidden');
        });
    });

    // ── Report download ───────────────────────────────────────────────────────
    function downloadReport(diagId) {
        var card   = document.querySelector('[data-diag-id="' + diagId + '"]') || document.querySelectorAll('.bg-white.rounded-2xl')[diagId - 1];
        var text   = document.getElementById('tts-' + diagId + '-text');
        if (!text) return;
        var blob = new Blob([text.textContent.trim()], { type: 'text/plain' });
        var a    = document.createElement('a');
        a.href   = URL.createObjectURL(blob);
        a.download = 'msas-diagnosis-' + diagId + '.txt';
        a.click();
    }

    // ── TTS Engine ────────────────────────────────────────────────────────────
    (function(){
        var states    = {};  // id → 'stopped' | 'playing' | 'paused'
        var speeds    = {};  // id → float
        var utterances = {};

        function el(id) { return document.getElementById(id); }

        function getActiveText(id) {
            var translated = el(id + '-translated');
            if (translated && translated.textContent.trim()) {
                return translated.textContent.trim();
            }
            return (el(id + '-text') || {textContent: ''}).textContent.trim();
        }

        function getLang(id) {
            var sel = el(id + '-lang');
            if (!sel) return 'en-NG';
            var code = sel.value;
            var map = { en:'en-NG', ha:'ha-NG', fr:'fr-FR', yo:'yo-NG', ig:'ig-NG' };
            return map[code] || 'en-NG';
        }

        function updateUI(id, state) {
            var playBtn  = document.querySelector('#' + id + '-controls button[onclick*="ttsPlay"]');
            var pauseBtn = el(id + '-pause');
            var stopBtn  = el(id + '-stop');
            var replayBtn = el(id + '-replay');
            if (!playBtn) return;
            if (state === 'playing') {
                playBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Playing...';
                playBtn.classList.add('tts-speaking');
                if (pauseBtn)  pauseBtn.classList.remove('hidden');
                if (stopBtn)   stopBtn.classList.remove('hidden');
                if (replayBtn) replayBtn.classList.remove('hidden');
            } else if (state === 'paused') {
                playBtn.innerHTML = '<i class="fa-solid fa-play text-[10px]"></i> Resume';
                playBtn.classList.remove('tts-speaking');
                if (pauseBtn)  pauseBtn.classList.add('hidden');
            } else {
                playBtn.innerHTML = '<i class="fa-solid fa-play text-[10px]"></i> Listen';
                playBtn.classList.remove('tts-speaking');
                if (pauseBtn)  pauseBtn.classList.add('hidden');
                if (stopBtn)   stopBtn.classList.add('hidden');
                if (replayBtn) replayBtn.classList.add('hidden');
            }
        }

        function startSpeaking(id) {
            if (!('speechSynthesis' in window)) {
                alert('Your browser does not support voice playback. Please use Chrome or Edge.');
                return;
            }
            var text = getActiveText(id);
            if (!text) return;

            var u = new SpeechSynthesisUtterance(text);
            u.lang   = getLang(id);
            u.rate   = parseFloat(speeds[id] || 1);
            u.pitch  = 1;
            u.volume = 1;

            var voices = window.speechSynthesis.getVoices();
            if (voices.length) {
                var langCode = u.lang.split('-')[0];
                var match = voices.find(function(v){ return v.lang.startsWith(langCode); });
                if (match) u.voice = match;
            }

            u.onstart = function(){ states[id] = 'playing'; updateUI(id, 'playing'); };
            u.onend   = function(){ states[id] = 'stopped'; updateUI(id, 'stopped'); };
            u.onerror = function(e){ console.warn('TTS error', e); states[id]='stopped'; updateUI(id,'stopped'); };

            utterances[id] = u;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(u);
        }

        window.ttsPlay = function(id) {
            if (states[id] === 'paused') {
                window.speechSynthesis.resume();
                states[id] = 'playing';
                updateUI(id, 'playing');
                return;
            }
            startSpeaking(id);
        };

        window.ttsPause = function(id) {
            if (window.speechSynthesis.speaking) {
                window.speechSynthesis.pause();
                states[id] = 'paused';
                updateUI(id, 'paused');
            }
        };

        window.ttsStop = function(id) {
            window.speechSynthesis.cancel();
            states[id] = 'stopped';
            updateUI(id, 'stopped');
        };

        window.ttsReplay = function(id) {
            window.speechSynthesis.cancel();
            setTimeout(function(){ startSpeaking(id); }, 100);
        };

        window.ttsSetSpeed = function(id, rate) {
            speeds[id] = parseFloat(rate);
            el(id + '-speed').textContent = rate;
            if (states[id] === 'playing') {
                window.ttsStop(id);
                setTimeout(function(){ startSpeaking(id); }, 150);
            }
        };

        window.ttsChangeLang = function(id, langCode) {
            if (langCode === 'en') {
                // Clear any translated text and use original
                var translated = el(id + '-translated');
                if (translated) translated.textContent = '';
                if (states[id] === 'playing') { window.ttsStop(id); setTimeout(function(){ startSpeaking(id); }, 150); }
                return;
            }

            // Show translating indicator
            var indicator = el(id + '-translating');
            if (indicator) indicator.classList.remove('hidden');

            var aiUrl = (el(id + '-ai-url') || {textContent:''}).textContent.trim();
            var originalText = (el(id + '-text') || {textContent:''}).textContent.trim();

            if (!aiUrl || !originalText) {
                if (indicator) indicator.classList.add('hidden');
                return;
            }

            var formData = new FormData();
            formData.append('text', originalText);
            formData.append('target_language', langCode);

            fetch(aiUrl, { method: 'POST', body: formData })
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    if (indicator) indicator.classList.add('hidden');
                    var translated = el(id + '-translated');
                    if (translated && data.translated_text) {
                        translated.textContent = data.translated_text;
                    }
                    if (states[id] === 'playing') {
                        window.ttsStop(id);
                        setTimeout(function(){ startSpeaking(id); }, 150);
                    }
                })
                .catch(function(err) {
                    console.warn('Translation failed:', err);
                    if (indicator) indicator.classList.add('hidden');
                });
        };

        // Preload voices
        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = function(){};
            window.speechSynthesis.getVoices();
        }
    })();
    </script>
</x-app-layout>
