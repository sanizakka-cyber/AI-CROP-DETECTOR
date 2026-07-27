<x-app-layout>
    <x-slot name="header">Security Settings</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">

        @foreach(['success','error'] as $t)
        @if(session($t))
        <div class="px-4 py-3 rounded-xl text-sm font-medium {{ $t==='success'?'bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300':'bg-red-50 border border-red-200 text-red-700 dark:bg-red-900/20 dark:text-red-300' }}">
            {{ session($t) }}
        </div>
        @endif
        @endforeach

        {{-- 2FA Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Two-Factor Authentication</h2>
                    <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                        When enabled, you'll be asked for a 6-digit email code each time you sign in.
                        @if(in_array($user->role, \App\Http\Controllers\TwoFactorController::REQUIRED_ROLES))
                        <span class="text-amber-600 dark:text-amber-400 font-semibold">Your role requires 2FA — it cannot be disabled.</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @php $is2FAOn = $user->two_factor_enabled || in_array($user->role, \App\Http\Controllers\TwoFactorController::REQUIRED_ROLES); @endphp
                    <span class="text-xs font-bold px-3 py-1 rounded-full {{ $is2FAOn ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-gray-700 text-slate-500' }}">
                        {{ $is2FAOn ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            </div>
            @if(!in_array($user->role, \App\Http\Controllers\TwoFactorController::REQUIRED_ROLES))
            <form method="POST" action="{{ route('2fa.toggle') }}" class="mt-4">
                @csrf
                <button type="submit"
                        class="px-5 py-2 text-sm font-bold rounded-xl transition {{ $user->two_factor_enabled ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200' : 'bg-[#0F6B3E] text-white hover:bg-[#047857]' }}">
                    {{ $user->two_factor_enabled ? 'Disable 2FA' : 'Enable 2FA' }}
                </button>
            </form>
            @endif
        </div>

        {{-- Login History --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
            <h2 class="text-base font-extrabold text-slate-800 dark:text-white mb-1">Login History</h2>
            <p class="text-sm text-slate-500 dark:text-gray-400 mb-5">Your 20 most recent sign-in events.</p>

            @if($loginHistory->isEmpty())
            <p class="text-center text-slate-400 text-sm py-6">No login history recorded yet.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase text-left">
                            <th class="pb-3 pr-4">Date & Time</th>
                            <th class="pb-3 pr-4">Browser</th>
                            <th class="pb-3 pr-4">Platform</th>
                            <th class="pb-3 pr-4">Device</th>
                            <th class="pb-3 pr-4">IP Address</th>
                            <th class="pb-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-gray-700">
                        @foreach($loginHistory as $entry)
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/50">
                            <td class="py-3 pr-4 text-slate-600 dark:text-gray-300 text-xs whitespace-nowrap">
                                {{ $entry->created_at->format('M d, Y') }}<br>
                                <span class="text-slate-400">{{ $entry->created_at->format('g:i A') }}</span>
                            </td>
                            <td class="py-3 pr-4 text-slate-600 dark:text-gray-300">{{ $entry->browser ?? '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600 dark:text-gray-300">{{ $entry->platform ?? '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600 dark:text-gray-300">
                                <span class="text-xs px-2 py-0.5 rounded-full {{ match($entry->device) { 'Mobile'=>'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300','Tablet'=>'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300', default=>'bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-gray-300' } }}">
                                    {{ $entry->device ?? 'Desktop' }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 font-mono text-xs text-slate-500 dark:text-gray-400">{{ $entry->ip_address ?? '—' }}</td>
                            <td class="py-3">
                                <span class="text-xs font-bold {{ $entry->success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                    {{ $entry->success ? 'Success' : 'Failed' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <div class="text-center">
            <a href="{{ route('profile.edit') }}" class="text-sm text-[#0F6B3E] font-semibold hover:underline">← Back to Profile</a>
        </div>
    </div>
</x-app-layout>
