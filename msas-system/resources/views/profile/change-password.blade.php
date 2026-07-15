<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Set Your New Password</h2>
    </x-slot>

    <div class="min-h-[60vh] flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-6 flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <p class="font-bold text-amber-800">Temporary Password Detected</p>
                    <p class="text-sm text-amber-700 mt-1">Your account was provisioned with a temporary password. You must set a new secure password before you can access the system.</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Choose a New Password</h3>

                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.change.update') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">New Password</label>
                        <input type="password" name="password" required autofocus
                               class="w-full border-slate-200 rounded-xl focus:ring-emerald-400 focus:border-emerald-400 text-sm"
                               placeholder="Minimum 8 characters">
                        <p class="text-xs text-slate-400 mt-1">Use a mix of letters, numbers, and symbols.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full border-slate-200 rounded-xl focus:ring-emerald-400 focus:border-emerald-400 text-sm"
                               placeholder="Repeat your new password">
                    </div>

                    <button type="submit"
                            class="w-full bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-bold py-3 rounded-xl shadow hover:shadow-md transition">
                        Set Password & Continue
                    </button>
                </form>

                <div class="mt-5 pt-4 border-t border-slate-100 text-center">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-slate-500 hover:text-red-600 transition">
                            Log out instead
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
