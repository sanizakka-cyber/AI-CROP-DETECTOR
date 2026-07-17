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

    <div class="space-y-6">

        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-md shadow-sm">
                <p class="text-green-700 font-medium flex items-center gap-2"><span>✅</span> {{ session('success') }}</p>
            </div>
        @endif

        @forelse($diagnoses as $diagnosis)
        @php
            $color = 'emerald';
            if($diagnosis->urgency_level === 'High') $color = 'red';
            elseif($diagnosis->urgency_level === 'Medium') $color = 'amber';

            // Build plain-text summary for TTS
            $typeLbl = match($diagnosis->type) { 'plant'=>'Crop', 'soil'=>'Soil', default=>'Livestock' };
            $ttsText = "Scan Summary. Scan type: {$typeLbl}. "
                . "Condition identified: {$diagnosis->disease_name}. "
                . "AI confidence: {$diagnosis->confidence_score} percent. "
                . "Urgency: {$diagnosis->urgency_level}. ";
            if($diagnosis->cause) $ttsText .= "Detected cause: {$diagnosis->cause}. ";
            if($diagnosis->first_aid_steps) $ttsText .= "First aid: {$diagnosis->first_aid_steps}. ";
            if($diagnosis->recommended_medication) $ttsText .= "Recommended treatment: {$diagnosis->recommended_medication}. ";
            if($diagnosis->vet_referral_advice) $ttsText .= "Additional advice: {$diagnosis->vet_referral_advice}. ";
            $ttsText .= "Always consult a certified specialist before applying any treatment.";
            $ttsId = 'tts-' . $diagnosis->id;
            // Locale-to-TTS lang mapping
            $ttsLang = match(session('locale','en')) {
                'ha' => 'ha-NG', 'fr' => 'fr-FR', 'yo' => 'yo-NG', 'ig' => 'ig-NG', default => 'en-NG'
            };
        @endphp

            <!-- Result Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Header Status Bar -->
                <div class="bg-{{ $color }}-500 px-4 md:px-6 py-2 flex justify-between items-center text-white flex-wrap gap-2">
                    <div class="font-bold text-sm uppercase tracking-wider">
                        @if($diagnosis->type === 'plant') 🌿 Plant / Crop Analysis
                        @elseif($diagnosis->type === 'soil') 🌱 Soil Assessment
                        @else 🐄 Livestock Health Analysis
                        @endif
                    </div>
                    <div class="text-xs opacity-90">{{ $diagnosis->created_at->format('D, M j, Y g:i A') }}</div>
                </div>

                {{-- AI Voice Summary Banner --}}
                <div class="bg-slate-50 border-b border-slate-100 px-4 md:px-6 py-2.5 flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-2 text-slate-600 text-xs font-medium">
                        <i class="fa-solid fa-volume-high text-emerald-600"></i>
                        <span>AI Voice Summary</span>
                        <span class="text-slate-400">— Listen to this diagnosis read aloud</span>
                    </div>
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
                        <select onchange="ttsSetSpeed('{{ $ttsId }}', this.value)"
                            class="border border-slate-200 rounded-lg text-xs px-2 py-1.5 text-slate-600 focus:outline-none focus:border-emerald-400 bg-white">
                            <option value="0.75">0.75×</option>
                            <option value="1" selected>1×</option>
                            <option value="1.25">1.25×</option>
                            <option value="1.5">1.5×</option>
                            <option value="2">2×</option>
                        </select>
                    </div>
                    {{-- Hidden text payload for TTS --}}
                    <span id="{{ $ttsId }}-text" class="hidden" data-lang="{{ $ttsLang }}">{{ $ttsText }}</span>
                    <span id="{{ $ttsId }}-state" class="hidden">stopped</span>
                </div>

                <div class="p-4 md:p-6 lg:p-8 grid md:grid-cols-3 gap-6 md:gap-8">
                    <!-- Image -->
                    <div class="col-span-1">
                        <div class="rounded-xl overflow-hidden shadow-sm border border-slate-200 relative aspect-square bg-slate-100">
                            <img src="{{ Storage::url($diagnosis->image_path) }}" alt="Scanned Image" class="w-full h-full object-cover" loading="lazy">
                            <div class="absolute bottom-2 right-2 bg-black/70 backdrop-blur text-white text-xs font-bold px-3 py-1.5 rounded-lg border border-white/20">
                                AI Confidence: <span class="{{ $diagnosis->confidence_score > 90 ? 'text-green-400' : 'text-amber-400' }}">{{ $diagnosis->confidence_score }}%</span>
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-bold transition">📥 Download Report</button>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="col-span-1 md:col-span-2 space-y-4">
                        <div class="flex justify-between items-start flex-wrap gap-2">
                            <div>
                                <h3 class="text-xl md:text-2xl font-extrabold text-slate-800">{{ $diagnosis->disease_name }}</h3>
                                <p class="text-slate-500 text-sm mt-1">Status: <span class="capitalize font-semibold text-slate-700">{{ $diagnosis->status }}</span></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold border shrink-0
                                {{ $color === 'red' ? 'bg-red-100 text-red-700 border-red-200' :
                                  ($color === 'amber' ? 'bg-amber-100 text-amber-700 border-amber-200' :
                                  'bg-emerald-100 text-emerald-700 border-emerald-200') }}">
                                Urgency: {{ $diagnosis->urgency_level }}
                            </span>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <span>🔍</span> {{ $diagnosis->type === 'soil' ? 'Soil Condition' : 'Detected Cause' }}
                                </div>
                                <p class="text-sm font-medium text-slate-700">{{ $diagnosis->cause ?: $diagnosis->disease_name }}</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                                <div class="text-xs font-bold text-blue-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <span>{{ $diagnosis->type === 'soil' ? '🌾' : '🚑' }}</span>
                                    {{ $diagnosis->type === 'soil' ? 'Recommended Crops' : 'First Aid / Action' }}
                                </div>
                                <p class="text-sm font-medium text-blue-800">{{ $diagnosis->first_aid_steps ?: '—' }}</p>
                            </div>
                        </div>

                        <div class="bg-emerald-50 p-4 md:p-5 rounded-xl border border-emerald-100">
                            <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-2 flex items-center gap-1">
                                <span>{{ $diagnosis->type === 'soil' ? '🪣' : '💊' }}</span>
                                {{ $diagnosis->type === 'soil' ? 'Amendment Recommendation' : 'Recommended Treatment' }}
                            </div>
                            <p class="font-bold text-emerald-900 mb-1">{{ $diagnosis->recommended_medication }}</p>
                            <p class="text-xs text-emerald-700 mt-2 border-t border-emerald-200 pt-2">
                                <span class="font-bold">{{ $diagnosis->type === 'soil' ? 'When to get a soil test:' : 'Advice:' }}</span>
                                {{ $diagnosis->vet_referral_advice }}
                            </p>
                            <div class="flex items-start gap-2 mt-3 text-[10px] text-emerald-600/80 leading-tight">
                                <span>⚠️</span>
                                <p>Disclaimer: This AI analysis provides guidance based on visual symptoms. Always consult with a certified {{ $diagnosis->type === 'soil' ? 'Agronomist or Soil Scientist' : 'Agronomist or Veterinary Doctor' }} before applying treatments.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-10 md:p-12 text-center">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-4xl mx-auto mb-4 border border-slate-100">🗂️</div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">No Scans Found</h3>
                <p class="text-slate-500 mb-6 max-w-md mx-auto">You haven't run any AI diagnostic scans yet. Upload a photo of a plant or animal to detect issues and get instant solutions.</p>
                <a href="{{ route('diagnostics.scan') }}" class="inline-block bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-emerald-500/30 hover:bg-emerald-700 hover:-translate-y-0.5 transition">
                    Run Your First Scan
                </a>
            </div>
        @endforelse

    </div>

    {{-- ─── AI Voice TTS Engine (Web Speech API) ─── --}}
    <style>
        .tts-speaking { animation: tts-pulse 1.4s ease-in-out infinite; }
        @keyframes tts-pulse { 0%,100%{opacity:1} 50%{opacity:.55} }
    </style>
    <script>
    (function(){
        var utterances = {};   // id → SpeechSynthesisUtterance
        var speeds = {};       // id → rate

        function getEl(id){ return document.getElementById(id); }

        window.ttsPlay = function(id){
            if(!('speechSynthesis' in window)){
                alert('Your browser does not support voice playback. Please use Chrome or Edge.');
                return;
            }
            var state = getEl(id+'-state');
            if(state && state.textContent === 'paused'){
                window.speechSynthesis.resume();
                state.textContent = 'playing';
                updateUI(id, 'playing');
                return;
            }
            // Build utterance
            var textEl = getEl(id+'-text');
            if(!textEl) return;
            var u = new SpeechSynthesisUtterance(textEl.textContent.trim());
            u.lang   = textEl.dataset.lang || 'en-NG';
            u.rate   = parseFloat(speeds[id] || 1);
            u.pitch  = 1;
            u.volume = 1;

            // Try to find a voice matching the locale
            var voices = window.speechSynthesis.getVoices();
            if(voices.length){
                var match = voices.find(function(v){ return v.lang.startsWith(u.lang.split('-')[0]); });
                if(match) u.voice = match;
            }

            u.onstart = function(){ updateUI(id,'playing'); if(state) state.textContent='playing'; };
            u.onend   = function(){ updateUI(id,'stopped'); if(state) state.textContent='stopped'; };
            u.onerror = function(e){ console.warn('TTS error',e); updateUI(id,'stopped'); if(state) state.textContent='stopped'; };

            utterances[id] = u;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(u);
        };

        window.ttsPause = function(id){
            if(window.speechSynthesis.speaking){
                window.speechSynthesis.pause();
                var state = getEl(id+'-state');
                if(state) state.textContent='paused';
                updateUI(id,'paused');
            }
        };

        window.ttsStop = function(id){
            window.speechSynthesis.cancel();
            var state = getEl(id+'-state');
            if(state) state.textContent='stopped';
            updateUI(id,'stopped');
        };

        window.ttsSetSpeed = function(id, rate){
            speeds[id] = parseFloat(rate);
            // If currently playing, restart at new speed
            var state = getEl(id+'-state');
            if(state && state.textContent === 'playing'){
                window.ttsStop(id);
                setTimeout(function(){ window.ttsPlay(id); }, 100);
            }
        };

        function updateUI(id, state){
            var playBtn  = document.querySelector('#'+id+'-controls button[onclick*="ttsPlay"]');
            var pauseBtn = getEl(id+'-pause');
            var stopBtn  = getEl(id+'-stop');
            if(!playBtn) return;
            if(state === 'playing'){
                playBtn.innerHTML  = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Playing...';
                playBtn.classList.add('tts-speaking');
                if(pauseBtn) pauseBtn.classList.remove('hidden');
                if(stopBtn)  stopBtn.classList.remove('hidden');
            } else if(state === 'paused'){
                playBtn.innerHTML  = '<i class="fa-solid fa-play text-[10px]"></i> Resume';
                playBtn.classList.remove('tts-speaking');
                if(pauseBtn) pauseBtn.classList.add('hidden');
            } else {
                playBtn.innerHTML  = '<i class="fa-solid fa-play text-[10px]"></i> Listen';
                playBtn.classList.remove('tts-speaking');
                if(pauseBtn) pauseBtn.classList.add('hidden');
                if(stopBtn)  stopBtn.classList.add('hidden');
            }
        }

        // Preload voices (Chrome lazy-loads them)
        if('speechSynthesis' in window){
            window.speechSynthesis.onvoiceschanged = function(){};
            window.speechSynthesis.getVoices();
        }
    })();
    </script>
</x-app-layout>
