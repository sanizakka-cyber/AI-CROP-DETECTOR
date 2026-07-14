<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information, email address, and photo.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        {{-- Profile Photo --}}
        <div>
            <x-input-label value="{{ __('Profile Photo') }}" />
            <div class="mt-2 flex items-center gap-5">
                {{-- Current photo or initials --}}
                @if($user->profile_photo)
                    <img src="{{ asset('storage/' . $user->profile_photo) }}"
                         alt="{{ $user->first_name }}"
                         id="photo-preview"
                         class="w-20 h-20 rounded-full object-cover border-4 border-[#1FA84A] shadow">
                @else
                    <div id="photo-initials"
                         class="w-20 h-20 rounded-full bg-[#0F6B3E] flex items-center justify-center text-white text-3xl font-bold border-4 border-[#1FA84A] shadow">
                        {{ strtoupper(substr($user->first_name ?? 'U', 0, 1)) }}
                    </div>
                    <img id="photo-preview" src="" alt="Preview" class="hidden w-20 h-20 rounded-full object-cover border-4 border-[#1FA84A] shadow">
                @endif

                <div>
                    <label for="profile_photo" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Upload Photo
                    </label>
                    <input id="profile_photo" name="profile_photo" type="file" accept="image/*" class="sr-only"
                        onchange="previewPhoto(this)">
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, or GIF. Max 2MB.</p>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
        </div>

        <script>
            function previewPhoto(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('photo-preview');
                        const initials = document.getElementById('photo-initials');
                        preview.src = e.target.result;
                        preview.classList.remove('hidden');
                        if (initials) initials.classList.add('hidden');
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>

        {{-- Name fields --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <x-input-label for="first_name" :value="__('First Name')" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="middle_name" :value="__('Middle Name')" />
                <x-text-input id="middle_name" name="middle_name" type="text" class="mt-1 block w-full" :value="old('middle_name', $user->middle_name)" autocomplete="additional-name" />
                <x-input-error class="mt-2" :messages="$errors->get('middle_name')" />
            </div>
            <div>
                <x-input-label for="last_name" :value="__('Last Name')" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
            </div>
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Phone --}}
        <div>
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        {{-- State / LGA --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="state" :value="__('State')" />
                <x-text-input id="state" name="state" type="text" class="mt-1 block w-full" :value="old('state', $user->state)" />
                <x-input-error class="mt-2" :messages="$errors->get('state')" />
            </div>
            <div>
                <x-input-label for="lga" :value="__('LGA')" />
                <x-text-input id="lga" name="lga" type="text" class="mt-1 block w-full" :value="old('lga', $user->lga)" />
                <x-input-error class="mt-2" :messages="$errors->get('lga')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Changes') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
