<x-app-layout>
    <x-slot name="header">Vaccinations</x-slot>

    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800">Vaccination Records</h2>
                <p class="text-slate-500 text-sm mt-0.5">Track and manage livestock vaccination schedules</p>
            </div>
            <a href="{{ route('vet.dashboard') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                ← Dashboard
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
            <div class="text-5xl mb-4">💉</div>
            <h3 class="text-xl font-extrabold text-slate-700 mb-2">Vaccination Module</h3>
            <p class="text-slate-500 text-sm max-w-md mx-auto">
                Vaccination scheduling and record management is coming in the next update.
                You'll be able to log vaccination events, set reminders, and track herd immunity here.
            </p>
            <div class="mt-6">
                <a href="{{ route('vet.queue') }}" class="px-5 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition">
                    Go to Consultation Queue
                </a>
            </div>
        </div>

    </div>
</x-app-layout>
