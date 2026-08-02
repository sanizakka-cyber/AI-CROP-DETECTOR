<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Veterinary Diagnosis Report') }}
            </h2>
            <a href="{{ route('farmer.vet') }}" class="text-indigo-600 font-bold text-sm hover:underline">
                &larr; Back to History
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-slate-100">
                
                <!-- Report Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-8 text-white text-center">
                    <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 border border-white/30 backdrop-blur-sm">
                        <svg width="36" height="36" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <h3 class="text-3xl font-black mb-2">Professional Diagnosis</h3>
                    <p class="text-emerald-100 opacity-90">Case #{{ $consultation->id }} • {{ $consultation->animal_type }}</p>
                </div>

                <div class="p-8">
                    <!-- Case Summary -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                            <p class="font-bold text-emerald-600 uppercase text-xs">{{ $consultation->status }}</p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Consultant</p>
                            <p class="font-bold text-slate-800 text-xs">{{ $consultation->expert->name ?? 'Awaiting Assignment' }}</p>
                        </div>
                    </div>

                    <!-- Symptoms Recall -->
                    <div class="mb-8">
                        <h4 class="text-xs font-bold text-slate-400 uppercase mb-3 flex items-center gap-1">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Reported Symptoms
                        </h4>
                        <div class="p-4 bg-slate-50 rounded-xl border-l-4 border-slate-200 text-slate-600 italic text-sm">
                            "{{ $consultation->symptoms }}"
                        </div>
                    </div>

                    <hr class="border-slate-100 mb-8">

                    @if($consultation->video_room_id)
                    <!-- Video Call Join -->
                    <div class="mb-6 flex items-center justify-between bg-violet-50 border border-violet-200 rounded-2xl p-4">
                        <div>
                            <p class="text-sm font-bold text-violet-800 flex items-center gap-1.5"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.868v6.264a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Video session started</p>
                            <p class="text-xs text-violet-600 mt-0.5">Your expert has opened a video consultation room.</p>
                        </div>
                        <a href="{{ route('consultation.video', $consultation) }}"
                           class="bg-violet-600 hover:bg-violet-700 text-white px-5 py-2 rounded-xl font-bold text-sm transition-colors shadow">
                            Join Video Call
                        </a>
                    </div>
                    @endif

                    <!-- The Response -->
                    <div class="mb-10">
                        <h4 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <svg width="18" height="18" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg> Expert Diagnosis &amp; Treatment Plan
                        </h4>
                        @if($consultation->expert_response)
                            <div class="bg-white border-2 border-emerald-50 p-6 rounded-2xl shadow-sm leading-relaxed text-slate-700 whitespace-pre-line">
                                {{ $consultation->expert_response }}
                            </div>
                        @else
                            <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                <p class="text-slate-500 font-medium">Your request is still being reviewed by our veterinary experts.</p>
                                <p class="text-xs text-slate-400 mt-1">Expected response time: < 24 hours.</p>
                            </div>
                        @endif
                    </div>

                    @if($consultation->expert_response)
                        <!-- Action Buttons -->
                        <div class="flex gap-4">
                            <button onclick="window.print()" class="flex-1 py-3 bg-slate-800 text-white rounded-xl font-bold shadow-lg hover:bg-slate-900 transition flex items-center justify-center gap-2">
                                <svg width="17" height="17" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg> Print Report
                            </button>
                            <a href="tel:080FARMAIVET" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-bold shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition flex items-center justify-center gap-2 text-center">
                                <svg width="17" height="17" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> Call Support
                            </a>
                        </div>
                        
                        <p class="mt-6 text-center text-[10px] text-slate-400 leading-tight">
                            Disclaimer: This report is provided for informational purposes. If the animal's condition worsens, please contact an emergency veterinary service immediately.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
