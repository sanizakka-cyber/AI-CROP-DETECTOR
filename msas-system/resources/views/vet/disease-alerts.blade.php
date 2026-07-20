<x-app-layout>
    <x-slot name="header">Disease Alerts</x-slot>

    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800">Disease Alerts</h2>
                <p class="text-slate-500 text-sm mt-0.5">Active outbreaks and disease surveillance in your region</p>
            </div>
            <a href="{{ route('vet.dashboard') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                ← Dashboard
            </a>
        </div>

        {{-- No active alerts banner --}}
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-800 font-medium flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            No active disease alerts in your region at this time.
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
            <div class="text-5xl mb-4">⚠</div>
            <h3 class="text-xl font-extrabold text-slate-700 mb-2">Disease Surveillance Module</h3>
            <p class="text-slate-500 text-sm max-w-md mx-auto">
                Real-time disease alert management and reporting is coming in the next update.
                You'll be able to raise alerts, track outbreaks by state, and coordinate responses here.
            </p>
            <div class="mt-6">
                <a href="{{ route('vet.queue') }}" class="px-5 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition">
                    Go to Consultation Queue
                </a>
            </div>
        </div>

    </div>
</x-app-layout>
