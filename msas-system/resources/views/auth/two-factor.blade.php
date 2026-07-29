<x-guest-layout>
    <div class="mb-4 text-center">
        <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-green-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h2 class="text-xl font-extrabold text-slate-800 dark:text-white">Two-Factor Verification</h2>
        <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">Enter the 6-digit code sent to your email address.</p>
    </div>

    @if(session('status'))
    <div class="mb-4 text-sm font-medium text-green-600 dark:text-green-400 text-center bg-green-50 dark:bg-green-900/20 rounded-xl px-4 py-3">
        {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('2fa.verify.post') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Security Code')" />
            <input id="code" type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                   class="mt-1 block w-full text-center text-2xl font-bold tracking-[0.5em] border-slate-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-xl shadow-sm focus:border-green-500 focus:ring-green-500"
                   placeholder="000000" autofocus>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        {{-- Trust device checkbox --}}
        <label class="flex items-start gap-3 cursor-pointer group">
            <div class="relative mt-0.5">
                <input type="checkbox" name="trust_device" value="1"
                       class="sr-only peer">
                <div class="w-5 h-5 rounded border-2 border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-900
                            peer-checked:bg-[#0F6B3E] peer-checked:border-[#0F6B3E]
                            group-hover:border-[#0F6B3E] transition-colors flex items-center justify-center">
                    <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <div>
                <span class="text-sm font-semibold text-slate-700 dark:text-gray-200">Trust this device for 30 days</span>
                <p class="text-xs text-slate-400 dark:text-gray-500 mt-0.5">Skip verification on this device until the trust period expires or you remove it.</p>
            </div>
        </label>

        <x-primary-button class="w-full justify-center py-3">
            {{ __('Verify & Sign In') }}
        </x-primary-button>
    </form>

    <div class="mt-4 text-center">
        <form method="POST" action="{{ route('2fa.resend') }}">
            @csrf
            <button type="submit" class="text-sm text-green-700 dark:text-green-400 hover:underline font-semibold">
                Resend Code
            </button>
        </form>
        <form method="POST" action="{{ route('2fa.cancel') }}" style="display:inline;">
            @csrf
            <button type="submit" class="block mt-2 text-xs text-slate-400 hover:text-slate-600 bg-transparent border-none cursor-pointer p-0">Back to Login</button>
        </form>
    </div>

    {{-- Sync the custom visual checkbox with the hidden <input> state --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const label = [...document.querySelectorAll('label')].find(l => l.textContent.includes('Trust this device'));
        if (!label) return;
        const checkbox = label.querySelector('input[type=checkbox]');
        const box      = label.querySelector('div > div');
        const tick     = label.querySelector('svg');
        if (!checkbox || !box || !tick) return;
        // Listen on the checkbox itself — the browser toggles it automatically
        // when the wrapping <label> is clicked, then fires 'change'. No manual
        // toggle needed; adding one would double-toggle and cancel the click.
        checkbox.addEventListener('change', function () {
            box.classList.toggle('bg-[#0F6B3E]', this.checked);
            box.classList.toggle('border-[#0F6B3E]', this.checked);
            tick.classList.toggle('hidden', !this.checked);
        });
    });
    </script>
</x-guest-layout>
