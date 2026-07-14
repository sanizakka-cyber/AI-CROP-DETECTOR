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
                    <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 border border-white/30 backdrop-blur-sm">
                        🩺
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
                            <span>📝</span> Reported Symptoms
                        </h4>
                        <div class="p-4 bg-slate-50 rounded-xl border-l-4 border-slate-200 text-slate-600 italic text-sm">
                            "{{ $consultation->symptoms }}"
                        </div>
                    </div>

                    <hr class="border-slate-100 mb-8">

                    <!-- The Response -->
                    <div class="mb-10">
                        <h4 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span class="text-emerald-600">📋</span> Expert Diagnosis & Treatment Plan
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
                                <span>🖨️</span> Print Report
                            </button>
                            <a href="tel:080FARMAIVET" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-bold shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition flex items-center justify-center gap-2 text-center">
                                <span>📞</span> Call Support
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
