<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ $user->force_password_reset ? 'Set Your New Password' : 'Change Password' }}
        </h2>
    </x-slot>

    <div class="min-h-[60vh] flex items-center justify-center">
        <div class="w-full max-w-md">

            @if($user->force_password_reset)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-6 flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <p class="font-bold text-amber-800">Temporary Password Detected</p>
                    <p class="text-sm text-amber-700 mt-1">Your account was provisioned with a temporary password. You must set a new secure password before you can access the system.</p>
                </div>
            </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <h3 class="text-lg font-bold text-slate-800 mb-6">
                    {{ $user->force_password_reset ? 'Choose a New Password' : 'Update Your Password' }}
                </h3>

                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('status') === 'password-changed')
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl text-sm">
                        Password changed successfully.
                    </div>
                @endif

                <form method="POST" action="{{ route('password.change.update') }}" class="space-y-5">
                    @csrf

                    {{-- Require current password only for voluntary (non-forced) changes --}}
                    @if(!$user->force_password_reset)
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" required autofocus
                               class="w-full border-slate-200 rounded-xl focus:ring-emerald-400 focus:border-emerald-400 text-sm"
                               placeholder="Enter your current password">
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">New Password</label>
                        <input type="password" name="password" required {{ $user->force_password_reset ? 'autofocus' : '' }}
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
                        {{ $user->force_password_reset ? 'Set Password & Continue' : 'Update Password' }}
                    </button>
                </form>

                <div class="mt-5 pt-4 border-t border-slate-100 text-center">
                    @if($user->force_password_reset)
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-slate-500 hover:text-red-600 transition">
                            Log out instead
                        </button>
                    </form>
                    @else
                    <a href="{{ url()->previous() }}" class="text-sm text-slate-500 hover:text-emerald-600 transition">
                        Cancel
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
