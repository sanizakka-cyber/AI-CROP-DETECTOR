<x-app-layout>
<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">Welcome to MSAS FarmAI</h2>
</x-slot>

<div class="max-w-2xl mx-auto py-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 px-8 py-10 text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-2xl font-extrabold text-white">Your password has been set</h1>
            <p class="text-emerald-50 mt-1">Welcome to the team, {{ $user->display_first_name ?? $user->first_name }}.</p>
        </div>

        <div class="px-8 py-8">
            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3">Your Account</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm mb-8">
                <div>
                    <dt class="text-slate-400">Name</dt>
                    <dd class="font-semibold text-slate-800">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Email</dt>
                    <dd class="font-semibold text-slate-800">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Role</dt>
                    <dd class="font-semibold text-slate-800">{{ $user->roleLabel }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Department</dt>
                    <dd class="font-semibold text-slate-800">{{ $user->department ?? 'Not assigned' }}</dd>
                </div>
            </dl>

            <p class="text-sm text-slate-600 mb-6">
                Take a moment to review your profile — add your phone number, location, and a profile photo.
                You can always update these later from your account settings.
            </p>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('profile.edit') }}"
                   class="flex-1 text-center bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl shadow transition">
                    Review My Profile
                </a>
                <a href="{{ route('dashboard') }}"
                   class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-6 py-3 rounded-xl transition">
                    Skip — Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
