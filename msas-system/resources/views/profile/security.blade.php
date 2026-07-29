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
                        Required on any unrecognised device. Trusted devices skip the code prompt.
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

        {{-- Trusted Devices --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Trusted Devices</h2>
                    <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                        Devices you've chosen to trust. They skip 2FA verification for up to 30 days.
                    </p>
                </div>
                @if($trustedDevices->isNotEmpty())
                <form method="POST" action="{{ route('devices.destroyAll') }}" class="flex-shrink-0"
                      onsubmit="return confirm('Remove all trusted devices? You will be asked to verify on your next login from each device.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-semibold text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition">
                        Remove All
                    </button>
                </form>
                @endif
            </div>

            @if($trustedDevices->isEmpty())
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-slate-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/>
                    </svg>
                </div>
                <p class="text-sm text-slate-400">No trusted devices yet.</p>
                <p class="text-xs text-slate-400 mt-1">Check "Trust this device for 30 days" when verifying your next sign-in.</p>
            </div>
            @else
            <div class="space-y-3">
                @foreach($trustedDevices as $device)
                <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-slate-50 dark:bg-gray-700/50 border border-slate-100 dark:border-gray-600">
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Device icon --}}
                        <div class="w-9 h-9 rounded-xl bg-[#0F6B3E]/10 dark:bg-[#0F6B3E]/20 flex items-center justify-center flex-shrink-0">
                            @if($device->device_name === 'Mobile')
                            <svg class="w-5 h-5 text-[#0F6B3E]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18h3"/>
                            </svg>
                            @elseif($device->device_name === 'Tablet')
                            <svg class="w-5 h-5 text-[#0F6B3E]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 002.25-2.25v-15a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                            @else
                            <svg class="w-5 h-5 text-[#0F6B3E]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/>
                            </svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                                {{ $device->device_label }}
                            </p>
                            <p class="text-xs text-slate-400 dark:text-gray-400">
                                {{ $device->ip_address ?? 'Unknown IP' }}
                                &middot;
                                @if($device->last_used_at)
                                    Last used {{ $device->last_used_at->diffForHumans() }}
                                @else
                                    Added {{ $device->created_at->diffForHumans() }}
                                @endif
                                &middot;
                                Expires {{ $device->expires_at->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('devices.destroy', $device) }}"
                          onsubmit="return confirm('Remove this trusted device?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-xs font-bold text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition flex-shrink-0">
                            Remove
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
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
