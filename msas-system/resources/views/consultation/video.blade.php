<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ auth()->user()->role === 'farmer' ? route('farmer.vet.view', $consultation) : route('vet.show', $consultation) }}"
               class="text-slate-500 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Video Consultation — Case #{{ $consultation->id }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                {{-- Info bar --}}
                <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-8 py-5 text-white flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest opacity-75">Video Consultation</p>
                        <h3 class="text-lg font-black mt-0.5">
                            @if(auth()->user()->role === 'farmer')
                                Consulting with {{ $consultation->expert?->first_name ?? 'Expert' }}
                            @else
                                Farmer: {{ $consultation->farmer?->first_name }} {{ $consultation->farmer?->last_name }}
                            @endif
                        </h3>
                    </div>
                    <a href="{{ $jitsiUrl }}" target="_blank" rel="noopener"
                       class="flex items-center gap-2 bg-white text-indigo-700 font-bold text-sm px-5 py-2.5 rounded-xl shadow hover:bg-indigo-50 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.868v6.264a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Open in New Tab
                    </a>
                </div>

                {{-- Embedded video room --}}
                <div class="relative" style="padding-bottom: 56.25%; height:0;">
                    <iframe
                        src="{{ $jitsiUrl }}"
                        allow="camera; microphone; display-capture; autoplay; clipboard-write"
                        allowfullscreen
                        style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                    ></iframe>
                </div>

                {{-- Tips --}}
                <div class="px-8 py-5 bg-amber-50 border-t border-amber-100 text-xs text-amber-700 flex items-start gap-3">
                    <svg class="h-4 w-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>
                        Allow camera and microphone access when prompted. If the video room does not load, click <strong>Open in New Tab</strong>.
                        The session room is private — only participants with this link can join.
                    </span>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
