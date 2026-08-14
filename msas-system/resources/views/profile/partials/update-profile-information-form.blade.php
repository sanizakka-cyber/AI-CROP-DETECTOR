<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information, email address, and photo.") }}
        </p>
    </header>

    {{-- Toast notification --}}
    <div id="profile-toast" class="hidden mt-4 px-4 py-3 rounded-lg text-sm font-medium" role="alert" aria-live="polite"></div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form id="profile-form" method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        {{-- Profile Photo --}}
        <div>
            <x-input-label value="{{ __('Profile Photo') }}" />
            <div class="mt-2 flex items-center gap-5">
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
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, or WebP. Compressed automatically before upload.</p>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
        </div>

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

        {{-- Country / State / LGA / Ward --}}
        @php
            $locCountries    = \App\Data\NigeriaLocations::countries();
            $locNigeriaStates = \App\Data\NigeriaLocations::states();
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="country" :value="__('Country')" />
                <select id="profile-country" name="country" class="mt-1 block w-full"></select>
                <x-input-error class="mt-2" :messages="$errors->get('country')" />
            </div>
            <div>
                <x-input-label for="state" :value="__('State / Province')" />
                <select id="profile-state" name="state" class="mt-1 block w-full"></select>
                <p id="profile-state-hint" class="text-xs text-gray-400 mt-1" style="display:none">{{ __('Select a country first') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('state')" />
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="lga" :value="__('LGA')" />
                <select id="profile-lga" name="lga" class="mt-1 block w-full"></select>
                <p id="profile-lga-hint" class="text-xs text-gray-400 mt-1" style="display:none">{{ __('Select a state first') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('lga')" />
            </div>
            <div>
                <x-input-label for="ward" :value="__('Ward / Village')" />
                <x-text-input id="ward" name="ward" type="text" class="mt-1 block w-full" :value="old('ward', $user->ward)" />
                <x-input-error class="mt-2" :messages="$errors->get('ward')" />
            </div>
        </div>

        {{-- Upload progress bar --}}
        <div id="profile-progress" class="hidden">
            <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                <div id="profile-progress-bar" class="bg-[#1FA84A] h-1.5 rounded-full transition-all duration-500" style="width:0%"></div>
            </div>
            <p id="profile-progress-text" class="text-xs text-gray-500 mt-1">Uploading...</p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button id="profile-save-btn">
                <span id="profile-save-label">{{ __('Save Changes') }}</span>
            </x-primary-button>
        </div>
    </form>

    <script>
    (function () {
        var submitting = false;
        var saveLabel = '{{ addslashes(__("Save Changes")) }}';

        function previewPhoto(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var preview = document.getElementById('photo-preview');
                    var initials = document.getElementById('photo-initials');
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    if (initials) initials.classList.add('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        window.previewPhoto = previewPhoto;

        function showToast(message, type) {
            var toast = document.getElementById('profile-toast');
            toast.className = 'mt-4 px-4 py-3 rounded-lg text-sm font-medium ' +
                (type === 'success'
                    ? 'bg-green-50 text-green-800 border border-green-200'
                    : 'bg-red-50 text-red-800 border border-red-200');
            toast.textContent = message;
            toast.classList.remove('hidden');
            toast.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            if (type === 'success') {
                setTimeout(function () { toast.classList.add('hidden'); }, 5000);
            }
        }

        function setProgress(pct, text) {
            document.getElementById('profile-progress').classList.remove('hidden');
            document.getElementById('profile-progress-bar').style.width = pct + '%';
            if (text) document.getElementById('profile-progress-text').textContent = text;
        }

        function hideProgress() {
            document.getElementById('profile-progress').classList.add('hidden');
            document.getElementById('profile-progress-bar').style.width = '0%';
        }

        function setBtnState(saving) {
            var btn = document.getElementById('profile-save-btn');
            var label = document.getElementById('profile-save-label');
            btn.disabled = saving;
            btn.style.opacity = saving ? '0.7' : '';
            btn.style.cursor = saving ? 'not-allowed' : '';
            label.textContent = saving ? 'Saving...' : saveLabel;
        }

        function compressImage(file) {
            return new Promise(function (resolve) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var img = new Image();
                    img.onload = function () {
                        var maxDim = 1200;
                        var w = img.width, h = img.height;
                        if (w > maxDim || h > maxDim) {
                            var ratio = Math.min(maxDim / w, maxDim / h);
                            w = Math.round(w * ratio);
                            h = Math.round(h * ratio);
                        }
                        var canvas = document.createElement('canvas');
                        canvas.width = w;
                        canvas.height = h;
                        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                        var quality = 0.85;
                        var targetBytes = 500 * 1024;
                        var attempt = function () {
                            canvas.toBlob(function (blob) {
                                if (!blob) { resolve(file); return; }
                                if (blob.size <= targetBytes || quality <= 0.3) {
                                    var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                                } else {
                                    quality = Math.round((quality - 0.1) * 10) / 10;
                                    attempt();
                                }
                            }, 'image/jpeg', quality);
                        };
                        attempt();
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        document.getElementById('profile-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            if (submitting) return;
            submitting = true;

            document.getElementById('profile-toast').classList.add('hidden');
            setBtnState(true);
            setProgress(10, 'Preparing...');

            var formData = new FormData(this);

            var fileInput = document.getElementById('profile_photo');
            if (fileInput.files && fileInput.files[0]) {
                setProgress(25, 'Compressing image...');
                try {
                    var compressed = await compressImage(fileInput.files[0]);
                    formData.set('profile_photo', compressed, compressed.name);
                } catch (err) {
                    // use original if compression fails
                }
            }

            setProgress(50, 'Uploading...');

            var controller = new AbortController();
            var timeoutId = setTimeout(function () { controller.abort(); }, 45000);

            try {
                setProgress(70, 'Saving profile...');
                var response = await fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                    signal: controller.signal,
                });
                clearTimeout(timeoutId);
                setProgress(95, 'Almost done...');

                var data = {};
                try { data = await response.json(); } catch (err) {}

                if (response.ok && data.success) {
                    setProgress(100, 'Saved!');
                    showToast(data.message || 'Profile updated successfully.', 'success');

                    if (data.photo_url) {
                        // Update every avatar image in the nav / page
                        document.querySelectorAll('img.rounded-full').forEach(function (img) {
                            img.src = data.photo_url + '?t=' + Date.now();
                        });
                        var preview = document.getElementById('photo-preview');
                        if (preview) {
                            preview.src = data.photo_url + '?t=' + Date.now();
                            preview.classList.remove('hidden');
                        }
                        var initials = document.getElementById('photo-initials');
                        if (initials) initials.classList.add('hidden');
                    }
                } else {
                    var msg = 'Something went wrong. Please try again.';
                    if (data && data.message) msg = data.message;
                    if (data && data.errors) {
                        var errs = Object.values(data.errors).flat();
                        if (errs.length) msg = errs.join(' ');
                    }
                    showToast(msg, 'error');
                }
            } catch (err) {
                clearTimeout(timeoutId);
                if (err.name === 'AbortError') {
                    showToast('Request timed out. The server may be starting up — please try again in a moment.', 'error');
                } else {
                    showToast('Network error. Please check your connection and try again.', 'error');
                }
            } finally {
                setTimeout(hideProgress, 1000);
                setBtnState(false);
                submitting = false;
            }
        });
    })();
    </script>

    {{-- Country / State / LGA cascading selects — same Tom Select pattern as registration --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
    (function () {
        var NG_STATES = @json($locNigeriaStates);
        var COUNTRIES = @json($locCountries);
        var CURRENT   = {
            country: @json(old('country', $user->country)),
            state:   @json(old('state', $user->state)),
            lga:     @json(old('lga', $user->lga)),
        };

        var tsCountry = new TomSelect('#profile-country', {
            options:     COUNTRIES.map(function (c) { return { value: c, text: c }; }),
            placeholder: '{{ addslashes(__('Select country…')) }}',
            maxItems:    1,
            onChange:    onCountryChange,
        });

        var tsState = new TomSelect('#profile-state', {
            options:     [],
            placeholder: '{{ addslashes(__('Select state / province…')) }}',
            maxItems:    1,
            onChange:    onStateChange,
        });

        var tsLga = new TomSelect('#profile-lga', {
            options:     [],
            placeholder: '{{ addslashes(__('Select LGA…')) }}',
            maxItems:    1,
        });

        function onCountryChange(val, silent) {
            tsState.clear(true);
            tsState.clearOptions();
            tsLga.clear(true);
            tsLga.clearOptions();
            tsLga.disable();
            document.getElementById('profile-state-hint').style.display = 'none';
            document.getElementById('profile-lga-hint').style.display = 'none';

            if (!val) { tsState.disable(); document.getElementById('profile-state-hint').style.display = 'block'; return; }

            if (val === 'Nigeria') {
                tsState.addOptions(NG_STATES.map(function (s) { return { value: s.name, text: s.name }; }));
            }
            // Non-Nigeria: free-text entry via Tom Select's create-on-type behavior
            tsState.enable();
        }

        function onStateChange(val) {
            tsLga.clear(true);
            tsLga.clearOptions();
            tsLga.disable();
            document.getElementById('profile-lga-hint').style.display = 'none';

            if (!val) { document.getElementById('profile-lga-hint').style.display = 'block'; return; }

            if (tsCountry.getValue() === 'Nigeria') {
                var found = NG_STATES.find(function (s) { return s.name === val; });
                if (found && found.lgas.length) {
                    tsLga.addOptions(found.lgas.map(function (l) { return { value: l, text: l }; }));
                    tsLga.enable();
                }
            }
        }

        tsState.disable();
        tsLga.disable();

        // Restore the user's existing/old() location on load
        if (CURRENT.country) {
            tsCountry.setValue(CURRENT.country, true);
            onCountryChange(CURRENT.country, true);
            if (CURRENT.state) {
                tsState.setValue(CURRENT.state, true);
                onStateChange(CURRENT.state);
                if (CURRENT.lga) tsLga.setValue(CURRENT.lga, true);
            }
        } else {
            document.getElementById('profile-state-hint').style.display = 'block';
        }
    })();
    </script>
</section>
