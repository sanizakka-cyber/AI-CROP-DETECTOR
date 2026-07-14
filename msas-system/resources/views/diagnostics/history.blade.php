<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
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
            <!-- Result Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
                <!-- Header Status Bar -->
                @php
                    $color = 'emerald';
                    if($diagnosis->urgency_level === 'High') $color = 'red';
                    elseif($diagnosis->urgency_level === 'Medium') $color = 'amber';
                @endphp
                <div class="bg-{{ $color }}-500 px-6 py-2 flex justify-between items-center text-white">
                    <div class="font-bold text-sm uppercase tracking-wider">{{ $diagnosis->type === 'plant' ? '🌿 Plant Analysis' : '🐄 Livestock Analysis' }}</div>
                    <div class="text-xs opacity-90">{{ $diagnosis->created_at->format('D, M j, Y g:i A') }}</div>
                </div>

                <div class="p-6 md:p-8 grid md:grid-cols-3 gap-8">
                    <!-- Image -->
                    <div class="col-span-1">
                        <div class="rounded-xl overflow-hidden shadow-sm border border-slate-200 relative aspect-square bg-slate-100">
                            <img src="{{ Storage::url($diagnosis->image_path) }}" alt="Scanned Image" class="w-full h-full object-cover">
                            
                            <!-- Confidence Score overlay -->
                            <div class="absolute bottom-2 right-2 bg-black/70 backdrop-blur text-white text-xs font-bold px-3 py-1.5 rounded-lg border border-white/20">
                                AI Confidence: <span class="{{ $diagnosis->confidence_score > 90 ? 'text-green-400' : 'text-amber-400' }}">{{ $diagnosis->confidence_score }}%</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex gap-2">
                            <button class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-bold transition">📥 Download Report</button>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="col-span-1 md:col-span-2 space-y-5">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-2xl font-extrabold text-slate-800">{{ $diagnosis->disease_name }}</h3>
                                <p class="text-slate-500 text-sm mt-1">Status: <span class="capitalize font-semibold text-slate-700">{{ $diagnosis->status }}</span></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold border 
                                {{ $color === 'red' ? 'bg-red-100 text-red-700 border-red-200' : 
                                  ($color === 'amber' ? 'bg-amber-100 text-amber-700 border-amber-200' : 
                                  'bg-emerald-100 text-emerald-700 border-emerald-200') }}">
                                Urgency: {{ $diagnosis->urgency_level }}
                            </span>
                        </div>

                        <!-- Analysis Grid -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1"><span>🔍</span> Detected Cause</div>
                                <p class="text-sm font-medium text-slate-700">{{ $diagnosis->cause }}</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                                <div class="text-xs font-bold text-blue-400 uppercase tracking-wider mb-1 flex items-center gap-1"><span>🚑</span> First Aid / Action</div>
                                <p class="text-sm font-medium text-blue-800">{{ $diagnosis->first_aid_steps }}</p>
                            </div>
                        </div>

                        <div class="bg-emerald-50 p-5 rounded-xl border border-emerald-100 mt-2">
                            <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-2 flex items-center gap-1"><span>💊</span> Recommended Treatment</div>
                            <p class="font-bold text-emerald-900 mb-1">{{ $diagnosis->recommended_medication }}</p>
                            <p class="text-xs text-emerald-700 mt-2 border-t border-emerald-200 pt-2"><span class="font-bold">Advice:</span> {{ $diagnosis->vet_referral_advice }}</p>
                            
                            <!-- Disclaimer -->
                            <div class="flex items-start gap-2 mt-3 text-[10px] text-emerald-600/80 leading-tight">
                                <span>⚠️</span>
                                <p>Disclaimer: This AI analysis provides guidance based on visual symptoms. Always consult with a certified Agronomist or Veterinary Doctor before applying heavy chemical treatments or restricted antibiotics.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center text-4xl mx-auto mb-4 border border-slate-100 text-slate-300">
                    🗂️
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">No Scans Found</h3>
                <p class="text-slate-500 mb-6 max-w-md mx-auto">You haven't run any AI diagnostic scans yet. Upload a photo of a plant or animal to detect issues and get instant solutions.</p>
                <a href="{{ route('diagnostics.scan') }}" class="inline-block bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-emerald-500/30 hover:bg-emerald-700 hover:-translate-y-0.5 transition">
                    Run Your First Scan
                </a>
            </div>
        @endforelse

    </div>
</x-app-layout>
