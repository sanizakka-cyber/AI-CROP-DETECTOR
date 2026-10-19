<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg> {{ __('Smart AI Diagnostic Scan') }}
        </h2>
    </x-slot>

    <div class="space-y-6 max-w-4xl mx-auto">
        {{-- AI Feature Banner --}}
        <div class="bg-gradient-to-r from-emerald-600 to-teal-500 text-white rounded-2xl px-6 py-4 flex items-start gap-4 shadow-lg">
            <div class="shrink-0 w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <h3 class="font-bold text-base mb-0.5">{{ __('Auto-Detection Enabled') }}</h3>
                <p class="text-sm text-emerald-100">{{ __('Our AI automatically identifies the plant species, animal breed, detected organ, and condition. Optional fields below can improve accuracy if you already know the type.') }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-900 text-white p-6">
                <h3 class="text-xl font-bold mb-2">{{ __('Automated Disease & Condition Detection') }}</h3>
                <p class="text-slate-400 text-sm">{{ __('Upload a clear photo of the affected plant, animal, or soil sample. Claude AI will identify the subject, detect diseases, and provide a comprehensive 20-point report.') }}</p>
            </div>

            <form action="{{ route('diagnostics.analyze') }}" method="POST" enctype="multipart/form-data" class="p-8" id="scanForm">
                @csrf

                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Scan Type Selection -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-3">{{ __('What are you scanning?') }}</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="plant" class="peer sr-only" required checked
                                   onchange="setScanType('plant')">
                            <div class="rounded-xl border-2 border-slate-200 p-4 text-center hover:bg-slate-50 transition peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700">
                                <div class="mb-2 flex justify-center"><svg width="40" height="40" fill="none" stroke="#10b981" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12m0 0C12 7 7 2 2 2c0 5 5 10 10 10zm0 0c0-5 5-10 10-10-5 0-10 5-10 10"/></svg></div>
                                <div class="font-bold text-sm">{{ __('Plant / Crop') }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ __('Auto-detects species & disease') }}</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="animal" class="peer sr-only"
                                   onchange="setScanType('animal')">
                            <div class="rounded-xl border-2 border-slate-200 p-4 text-center hover:bg-slate-50 transition peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700">
                                <div class="mb-2 flex justify-center"><svg width="40" height="40" fill="none" stroke="#f59e0b" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg></div>
                                <div class="font-bold text-sm">{{ __('Livestock') }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ __('Auto-detects breed & condition') }}</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="soil" class="peer sr-only"
                                   onchange="setScanType('soil')">
                            <div class="rounded-xl border-2 border-slate-200 p-4 text-center hover:bg-slate-50 transition peer-checked:border-amber-700 peer-checked:bg-amber-50 peer-checked:text-amber-800">
                                <div class="mb-2 flex justify-center"><svg width="40" height="40" fill="none" stroke="#b45309" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
                                <div class="font-bold text-sm">{{ __('Soil Sample') }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ __('Nutrients, Recommendations') }}</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="pest" class="peer sr-only"
                                   onchange="setScanType('pest')">
                            <div class="rounded-xl border-2 border-slate-200 p-4 text-center hover:bg-slate-50 transition peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700">
                                <div class="mb-2 flex justify-center"><svg width="40" height="40" fill="none" stroke="#ef4444" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                <div class="font-bold text-sm">{{ __('Pest ID') }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ __('Insects, weeds & pathogens') }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Optional Context Fields -->
                <div id="ctx-plant" class="mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('Crop Type') }} <span class="font-normal normal-case text-slate-400">{{ __('(optional)') }}</span></label>
                            <input type="text" name="crop_type" placeholder="e.g., Maize, Tomato, Cassava"
                                   class="w-full border-slate-200 rounded-lg text-sm focus:ring-emerald-400 focus:border-emerald-400">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('Plant Part') }} <span class="font-normal normal-case text-slate-400">{{ __('(optional)') }}</span></label>
                            <select name="crop_part" class="w-full border-slate-200 rounded-lg text-sm focus:ring-emerald-400 focus:border-emerald-400">
                                <option value="">{{ __('Let AI Detect') }}</option>
                                <option value="leaf">Leaf</option>
                                <option value="stem">Stem</option>
                                <option value="root">Root</option>
                                <option value="fruit">Fruit / Pod</option>
                                <option value="whole plant">Whole Plant</option>
                                <option value="flower">Flower</option>
                                <option value="seed">Seed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="ctx-animal" class="mb-6 hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('Animal Type') }} <span class="font-normal normal-case text-slate-400">{{ __('(optional)') }}</span></label>
                            <input type="text" name="animal_type" placeholder="e.g., Cattle, Chicken, Goat"
                                   class="w-full border-slate-200 rounded-lg text-sm focus:ring-amber-400 focus:border-amber-400">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('Body Area') }} <span class="font-normal normal-case text-slate-400">{{ __('(optional)') }}</span></label>
                            <select name="assessment_type" class="w-full border-slate-200 rounded-lg text-sm focus:ring-amber-400 focus:border-amber-400">
                                <option value="">{{ __('Let AI Detect') }}</option>
                                <option value="skin/coat">Skin / Coat</option>
                                <option value="droppings">Droppings</option>
                                <option value="eyes">Eyes</option>
                                <option value="hooves">Hooves / Feet</option>
                                <option value="wound">Wound / Lesion</option>
                                <option value="whole body">Whole Body</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="ctx-soil" class="mb-6 hidden">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('Additional Context') }} <span class="font-normal normal-case text-slate-400">{{ __('(optional)') }}</span></label>
                        <input type="text" name="soil_context" placeholder="e.g., Farm location, current crop, known issues"
                               class="w-full border-slate-200 rounded-lg text-sm focus:ring-amber-700 focus:border-amber-700">
                    </div>
                </div>

                <div id="ctx-pest" class="mb-6 hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Crop / Plant Affected <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                            <input type="text" name="crop_type" placeholder="e.g., Maize, Tomato, Cassava"
                                   class="w-full border-slate-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Location / Region <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                            <input type="text" name="pest_location" placeholder="e.g., Kano, Northern Nigeria"
                                   class="w-full border-slate-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500">
                        </div>
                    </div>
                </div>

                <!-- Image Upload Area -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-3">{{ __('Choose Image Source') }}</label>

                    {{-- Source picker buttons --}}
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <button type="button" onclick="triggerCamera()"
                            class="flex flex-col items-center justify-center gap-2 py-5 px-3 bg-slate-900 hover:bg-slate-700 text-white rounded-xl font-bold transition shadow-sm">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="text-xs leading-tight text-center">{{ __('Take Photo') }}<br><span class="font-normal opacity-60 text-[10px]">Camera</span></span>
                        </button>
                        <button type="button" onclick="triggerGallery()"
                            class="flex flex-col items-center justify-center gap-2 py-5 px-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition shadow-sm">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 16M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-xs leading-tight text-center">{{ __('Select Photo') }}<br><span class="font-normal opacity-60 text-[10px]">Gallery</span></span>
                        </button>
                        <button type="button" onclick="triggerBrowse()"
                            class="flex flex-col items-center justify-center gap-2 py-5 px-3 bg-sky-600 hover:bg-sky-500 text-white rounded-xl font-bold transition shadow-sm">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                            <span class="text-xs leading-tight text-center">{{ __('Browse Files') }}<br><span class="font-normal opacity-60 text-[10px]">Desktop / Cloud</span></span>
                        </button>
                    </div>

                    {{-- Hidden file input — capture attribute toggled by JS --}}
                    <input type="file" name="image" id="file-upload" class="hidden"
                           accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" required onchange="previewImage(event)"
                           aria-describedby="upload-hint upload-error">

                    {{-- Drop zone for desktop drag-and-drop --}}
                    <div id="drop-area" class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center bg-slate-50 transition cursor-pointer"
                         onclick="triggerBrowse()">
                        <div id="upload-prompt">
                            <div class="mb-2 flex justify-center"><svg width="40" height="40" fill="none" stroke="#cbd5e1" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 10l-4-4m0 0L8 10m4-4v12"/></svg></div>
                            <h4 class="font-bold text-slate-600 mb-1 text-sm">{{ __('or drag & drop here') }}</h4>
                            {{-- Previously stated an image type the server rejects and a 5 MB limit; the server allows 10 MB. --}}
                            <p id="upload-hint" class="text-xs text-slate-400">{{ __('JPG, PNG, WebP or HEIC, up to 10 MB') }}</p>
                        </div>
                        <div id="image-preview-container" class="hidden">
                            <img id="image-preview" src="#" alt="Preview" class="max-h-64 mx-auto rounded-lg shadow-md">
                            <p class="text-emerald-600 font-bold mt-3 text-sm flex items-center justify-center gap-1"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> {{ __('Image Ready') }}</p>
                            <p id="image-file-name" class="text-xs text-slate-500 mt-1 break-all"></p>
                            <button type="button" onclick="resetUpload(); event.stopPropagation();"
                                class="mt-2 text-xs text-red-500 hover:underline relative z-10">{{ __('Remove & try again') }}</button>
                        </div>
                    </div>
                    <p id="upload-error" class="text-red-600 text-xs mt-2 font-semibold hidden" role="alert"></p>
                    {{-- A failed scan: plain message, reference, next step; staff also get technical details. --}}
                    <div id="scan-error-box" class="hidden mt-3 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-800" role="alert" aria-live="assertive">
                        <p class="scan-error-message font-semibold"></p>
                        <p class="scan-error-reference text-xs mt-1 font-mono text-red-700"></p>
                        <div class="scan-error-actions flex flex-wrap gap-2 mt-3"></div>
                        <details class="scan-error-technical hidden mt-3 bg-white border border-red-100 rounded-lg p-3 text-xs text-slate-700">
                            <summary class="cursor-pointer font-bold">{{ __('Technical details') }}</summary>
                            <dl class="scan-error-technical-list grid grid-cols-3 gap-x-2 gap-y-1 mt-2"></dl>
                            <button type="button" class="scan-error-copy mt-2 px-3 py-1 bg-slate-700 text-white rounded-lg">{{ __('Copy details') }}</button>
                        </details>
                    </div>
                    @error('image')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sample Collection Location (optional) -->
                <div class="mb-8 border border-slate-200 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-bold text-slate-700">{{ __('Sample Collection Location') }} <span class="font-normal text-slate-400">{{ __('(optional)') }}</span></label>
                        <button type="button" id="useMyLocationBtn" onclick="useMyLocation()"
                            class="text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg flex items-center gap-1">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.25a7 7 0 1114 0C19 12.75 16.5 16.5 12 21z"/><circle cx="12" cy="9.75" r="2.25"/></svg>
                            {{ __('Use my location') }}
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 mb-3">{{ __('Recording where this sample was actually collected helps track outbreaks and makes your scan useful for agricultural research. This never blocks your diagnosis — leave it blank if unsure.') }}</p>

                    <div id="locGpsStatus" class="hidden mb-3 text-xs rounded-lg p-2"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('State') }}</label>
                            <select id="locState" name="loc_state" class="w-full border-slate-200 rounded-lg text-sm" onchange="populateLgas(); markAdministrative();">
                                <option value="">{{ __('Select State') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('LGA') }}</label>
                            <select id="locLga" name="loc_lga" class="w-full border-slate-200 rounded-lg text-sm" onchange="markAdministrative();">
                                <option value="">{{ __('Select LGA') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('Community / Town / Village') }}</label>
                            <input type="text" name="loc_community" class="w-full border-slate-200 rounded-lg text-sm" placeholder="{{ __('e.g., Funtua town') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('Postal Code') }}</label>
                            <input type="text" name="loc_postal_code" class="w-full border-slate-200 rounded-lg text-sm" placeholder="{{ __('If known') }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('Address / Landmark') }}</label>
                            <input type="text" name="loc_address" class="w-full border-slate-200 rounded-lg text-sm" placeholder="{{ __('Optional — nearest landmark or address') }}">
                        </div>
                    </div>

                    <div class="mt-3 flex items-start gap-2">
                        <input type="checkbox" id="locDiffers" name="loc_differs_from_scan" value="1" class="mt-0.5 rounded border-slate-300">
                        <label for="locDiffers" class="text-xs text-slate-600">{{ __('I am scanning this sample at a different location from where it was actually collected (e.g. a lab or office, away from the farm).') }}</label>
                    </div>
                    <div class="mt-2 flex items-start gap-2">
                        <input type="checkbox" id="locConfirmed" name="loc_user_confirmed" value="1" class="mt-0.5 rounded border-slate-300">
                        <label for="locConfirmed" class="text-xs text-slate-600">{{ __('I confirm the location entered above is correct.') }}</label>
                    </div>

                    {{-- Hidden coordinate + provenance fields, populated by JS only — never guessed server-side --}}
                    <input type="hidden" name="loc_country" value="Nigeria">
                    <input type="hidden" id="locLatitude" name="loc_latitude" value="">
                    <input type="hidden" id="locLongitude" name="loc_longitude" value="">
                    <input type="hidden" id="locAccuracy" name="loc_accuracy_meters" value="">
                    <input type="hidden" id="locCaptureMethod" name="loc_capture_method" value="">
                </div>

                <!-- Tips -->
                <div class="mb-6 bg-blue-50 border border-blue-100 rounded-xl p-4 text-xs text-blue-700">
                    <div class="font-bold mb-1 flex items-center gap-1"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707M12 21a9 9 0 01-6.364-15.364A9 9 0 0112 3a9 9 0 016.364 15.364A9 9 0 0112 21z"/></svg> {{ __('Tips for the best diagnosis:') }}</div>
                    <ul class="list-disc list-inside space-y-1 text-blue-600">
                        <li>Photograph in natural daylight whenever possible</li>
                        <li>Get close enough to fill the frame with the affected area</li>
                        <li>For plants, capture the most affected leaf or part clearly</li>
                        <li>For animals, photograph the specific problem area (wound, skin, eyes)</li>
                        <li>Avoid blurry or dark images — they reduce AI accuracy</li>
                    </ul>
                </div>

                <!-- Scanning Progress -->
                <div id="scanning-progress" class="hidden mb-8 bg-slate-900 rounded-xl p-6 text-center text-white">
                    <div class="inline-block w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                    <h4 class="font-bold text-lg mb-1">{{ __('AI Engine Analysing...') }}</h4>
                    <p class="text-sm text-slate-400">{{ __('Claude Vision is identifying the subject and examining for diseases. This may take 15–30 seconds.') }}</p>
                    <div class="w-full bg-slate-700 rounded-full h-2 mt-4 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="upload-progress-bar">
                        <div id="upload-progress-fill" class="bg-emerald-500 h-2 rounded-full transition-all" style="width:0%"></div>
                    </div>
                    <p id="upload-progress-text" class="text-xs text-slate-400 mt-2">{{ __('Uploading photo…') }}</p>
                </div>

                <div class="text-right">
                    <button type="submit" id="submitBtn" class="bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition flex items-center gap-2 ml-auto">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg> {{ __('Run Full AI Diagnosis') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        // Built here, not inline in @json: Blade's @json splits a literal array on commas.
        $scanErrorText = [
            'unavailable' => __('Our analysis service is temporarily unavailable. Your scan was NOT counted. Please try again in a few minutes.'),
            'interrupted' => __('The photo did not finish uploading. Check your connection and try again.'),
            'tooLarge'    => __('This photo is too large (over 10 MB). Choose a smaller photo or take a new one.'),
            'session'     => __('Your login has expired. Please sign in again.'),
            'reference'   => __('Reference:'),
            'retry'       => __('Try again'),
            'signIn'      => __('Sign in'),
            'upgrade'     => __('Upgrade plan'),
            'choosePhoto' => __('Choose another photo'),
            'copyDetails' => __('Copy details'),
            'copied'      => __('Copied'),
            'tCode'       => __('Error code'),
            'tStatus'     => __('HTTP status'),
            'tService'    => __('Failed service'),
            'tMessage'    => __('Error message'),
            'tTime'       => __('Time'),
            'loginUrl'    => route('login'),
            'upgradeUrl'  => route('subscription.plans'),
        ];
    @endphp
    <script>
    // Nigeria states + LGAs — same server-side dataset the backend
    // validates against (App\Data\NigeriaLocations), so client and server
    // never disagree about what a valid state/LGA pair looks like.
    const NIGERIA_STATES = @json($nigeriaStates ?? []);

    (function populateStates() {
        var sel = document.getElementById('locState');
        NIGERIA_STATES.forEach(function (s) {
            var opt = document.createElement('option');
            opt.value = s.name; opt.textContent = s.name;
            sel.appendChild(opt);
        });
    })();

    function populateLgas() {
        var stateName = document.getElementById('locState').value;
        var lgaSel = document.getElementById('locLga');
        lgaSel.innerHTML = '<option value="">{{ __("Select LGA") }}</option>';
        var state = NIGERIA_STATES.find(function (s) { return s.name === stateName; });
        if (state) {
            state.lgas.forEach(function (lga) {
                var opt = document.createElement('option');
                opt.value = lga; opt.textContent = lga;
                lgaSel.appendChild(opt);
            });
        }
    }

    // Selecting a state/LGA manually is its own valid provenance
    // ('administrative_selection') — distinct from GPS capture — unless a
    // GPS fix has already been captured, which takes precedence.
    function markAdministrative() {
        var methodField = document.getElementById('locCaptureMethod');
        if (methodField.value !== 'gps_device') {
            methodField.value = 'administrative_selection';
        }
    }

    function useMyLocation() {
        var statusEl = document.getElementById('locGpsStatus');
        statusEl.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-emerald-50', 'text-emerald-700');
        if (!('geolocation' in navigator)) {
            statusEl.textContent = '{{ __("Your browser does not support location capture. Please select your state/LGA manually.") }}';
            statusEl.classList.add('bg-red-50', 'text-red-700');
            return;
        }
        statusEl.textContent = '{{ __("Requesting your location…") }}';
        statusEl.classList.add('bg-emerald-50', 'text-emerald-700');

        navigator.geolocation.getCurrentPosition(function (pos) {
            document.getElementById('locLatitude').value = pos.coords.latitude;
            document.getElementById('locLongitude').value = pos.coords.longitude;
            document.getElementById('locAccuracy').value = Math.round(pos.coords.accuracy || 0);
            document.getElementById('locCaptureMethod').value = 'gps_device';
            var acc = Math.round(pos.coords.accuracy || 0);
            statusEl.textContent = '{{ __("Location captured") }} (±' + acc + 'm). '
                + ( acc > 100 ? '{{ __("Accuracy is low — consider moving to open ground and trying again, or proceed with this estimate.") }}' : '{{ __("Please still confirm the state/LGA below.") }}' );
        }, function (err) {
            var msg = {
                1: '{{ __("Location permission denied. You can still select your state/LGA manually below.") }}',
                2: '{{ __("Location unavailable right now. You can still select your state/LGA manually below.") }}',
                3: '{{ __("Location request timed out. You can still select your state/LGA manually below.") }}',
            }[err.code] || '{{ __("Could not get your location. You can still select your state/LGA manually below.") }}';
            statusEl.textContent = msg;
            statusEl.classList.remove('bg-emerald-50', 'text-emerald-700');
            statusEl.classList.add('bg-red-50', 'text-red-700');
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
    }

    function setScanType(type) {
        document.getElementById('ctx-plant').classList.toggle('hidden', type !== 'plant');
        document.getElementById('ctx-animal').classList.toggle('hidden', type !== 'animal');
        document.getElementById('ctx-soil').classList.toggle('hidden', type !== 'soil');
        document.getElementById('ctx-pest').classList.toggle('hidden', type !== 'pest');
    }

    // ── Image source triggers ──────────────────────────────────────────────────
    function triggerCamera() {
        var input = document.getElementById('file-upload');
        input.setAttribute('capture', 'environment');
        input.click();
    }
    function triggerGallery() {
        var input = document.getElementById('file-upload');
        input.removeAttribute('capture');
        input.click();
    }
    function triggerBrowse() {
        var input = document.getElementById('file-upload');
        input.removeAttribute('capture');
        input.click();
    }

    // ── Drag-and-drop support ──────────────────────────────────────────────────
    (function() {
        var da = document.getElementById('drop-area');
        ['dragenter', 'dragover'].forEach(function(ev) {
            da.addEventListener(ev, function(e) {
                e.preventDefault();
                da.classList.add('border-emerald-400', 'bg-emerald-50');
            });
        });
        ['dragleave', 'dragend'].forEach(function(ev) {
            da.addEventListener(ev, function() {
                da.classList.remove('border-emerald-400', 'bg-emerald-50');
            });
        });
        da.addEventListener('drop', function(e) {
            e.preventDefault();
            da.classList.remove('border-emerald-400', 'bg-emerald-50');
            var files = e.dataTransfer && e.dataTransfer.files;
            var dropProblem = files && files[0] ? validateFile(files[0]) : '{{ __("Please choose a photo.") }}';
            if (dropProblem) { showUploadError(dropProblem); return; }
            if (files && files[0]) {
                try {
                    var dt = new DataTransfer();
                    dt.items.add(files[0]);
                    var input = document.getElementById('file-upload');
                    input.removeAttribute('capture');
                    input.files = dt.files;
                    previewImage({ target: input });
                } catch (err) {
                    console.warn('Drag-and-drop file assignment failed:', err);
                }
            }
        });
    })();

    var ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
    // HEIC (iPhone) is converted to JPEG on the server; some browsers report it with no type.
    function isHeic(file) { return /\.(heic|heif)$/i.test(file.name || '') || /^image\/hei[cf]/i.test(file.type || ''); }
    var MAX_BYTES = 10 * 1024 * 1024; // matches the server's max:10240 KB
    var SCAN_TEXT = @json($scanErrorText);
    /**
     * Shows a failed scan: the server's plain message (never raw errors),
     * "Reference: SCN-XXXXX", the next step as a button, and for staff
     * roles only (the server decides) a Technical details box to copy.
     */
    function showScanError(e) {
        var box = document.getElementById('scan-error-box');
        if (!e) { box.classList.add('hidden'); return; }
        box.querySelector('.scan-error-message').textContent = e.error || e.message || SCAN_TEXT.unavailable;
        box.querySelector('.scan-error-reference').textContent = e.reference ? (e.reference_label || (SCAN_TEXT.reference + ' ' + e.reference)) : '';
        var actions = box.querySelector('.scan-error-actions');
        actions.textContent = '';
        function addButton(label, onClick, href) {
            var el = document.createElement(href ? 'a' : 'button');
            if (href) { el.href = href; } else { el.type = 'button'; el.addEventListener('click', onClick); }
            el.className = 'px-4 py-2 rounded-lg font-bold text-white bg-red-600 hover:bg-red-700';
            el.textContent = label;
            el.setAttribute('data-scan-action', e.action || '');
            actions.appendChild(el);
        }
        if (e.action === 'retry') addButton(SCAN_TEXT.retry, function () { box.classList.add('hidden'); document.getElementById('submitBtn').click(); });
        if (e.action === 'sign_in') addButton(SCAN_TEXT.signIn, null, e.action_url || SCAN_TEXT.loginUrl);
        if (e.action === 'upgrade') addButton(SCAN_TEXT.upgrade, null, e.action_url || SCAN_TEXT.upgradeUrl);
        if (e.action === 'choose_photo') addButton(SCAN_TEXT.choosePhoto, function () { resetUpload(); document.getElementById('file-upload').click(); });

        var tech = box.querySelector('.scan-error-technical');
        var list = box.querySelector('.scan-error-technical-list');
        list.textContent = '';
        if (e.technical) {
            var rows = [[SCAN_TEXT.tCode, e.technical.code], [SCAN_TEXT.tStatus, e.technical.http_status], [SCAN_TEXT.tService, e.technical.service_label],
                        [SCAN_TEXT.tMessage, e.technical.message], [SCAN_TEXT.tTime, e.technical.timestamp], [SCAN_TEXT.reference, e.technical.reference]];
            rows.forEach(function (r) {
                var dt = document.createElement('dt'); dt.className = 'font-bold'; dt.textContent = r[0];
                var dd = document.createElement('dd'); dd.className = 'col-span-2 break-words font-mono'; dd.textContent = r[1] == null ? '' : String(r[1]);
                list.appendChild(dt); list.appendChild(dd);
            });
            var copy = box.querySelector('.scan-error-copy');
            copy.onclick = function () {
                var text = rows.map(function (r) { return r[0] + ': ' + (r[1] == null ? '' : r[1]); }).join('\n');
                (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () {
                    copy.textContent = SCAN_TEXT.copied;
                }, function () { window.prompt(SCAN_TEXT.copyDetails, text); });
            };
            tech.classList.remove('hidden');
        } else {
            tech.classList.add('hidden');
        }
        box.classList.remove('hidden');
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function showUploadError(msg) {
        var el = document.getElementById('upload-error');
        el.textContent = msg || '';
        el.classList.toggle('hidden', !msg);
    }
    function validateFile(file) {
        if (!file) return '{{ __("Please choose a photo.") }}';
        if (ACCEPTED_TYPES.indexOf(file.type) === -1 && !isHeic(file)) return '{{ __("This file type is not supported. Choose a JPG, PNG, WebP or HEIC photo.") }}';
        if (file.size > MAX_BYTES) return '{{ __("This photo is larger than 10 MB. Choose a smaller photo.") }}';
        return null;
    }

    function previewImage(event) {
        var input = event.target;
        if (input.files && input.files[0]) {
            var problem = validateFile(input.files[0]);
            if (problem) { resetUpload(); showUploadError(problem); return; }
            showUploadError(null);
            var f = input.files[0];
            document.getElementById('image-file-name').textContent = f.name + ' (' + (f.size / 1024 / 1024).toFixed(1) + ' MB)';
            if (isHeic(f)) {
                // Most browsers cannot draw HEIC; the server converts it to JPEG.
                document.getElementById('image-file-name').textContent += ' — {{ __("HEIC photo: it will be converted to JPEG when you analyse it.") }}';
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('image-preview').src = e.target.result;
                document.getElementById('upload-prompt').classList.add('hidden');
                document.getElementById('image-preview-container').classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    function resetUpload() {
        var input = document.getElementById('file-upload');
        input.value = '';
        input.removeAttribute('capture');
        document.getElementById('image-preview').src = '#';
        document.getElementById('image-file-name').textContent = '';
        document.getElementById('image-preview-container').classList.add('hidden');
        document.getElementById('upload-prompt').classList.remove('hidden');
    }
    (function() {
        var form = document.getElementById('scanForm');
        var btn = document.getElementById('submitBtn');
        var btnHtml = btn.innerHTML;
        var submitting = false;
        function setBusy(busy) {
            submitting = busy;
            btn.disabled = busy;
            btn.classList.toggle('opacity-50', busy);
            btn.classList.toggle('cursor-not-allowed', busy);
            btn.innerHTML = busy ? '{{ __("Analysing...") }}' : btnHtml;
            document.getElementById('scanning-progress').classList.toggle('hidden', !busy);
        }
        function setProgress(pct, text) {
            document.getElementById('upload-progress-fill').style.width = pct + '%';
            document.getElementById('upload-progress-bar').setAttribute('aria-valuenow', String(pct));
            if (text) document.getElementById('upload-progress-text').textContent = text;
        }
        form.addEventListener('submit', function(e) {
            var input = document.getElementById('file-upload');
            var problem = validateFile(input.files[0]);
            if (problem) { e.preventDefault(); showUploadError(problem); return; }
            if (submitting) { e.preventDefault(); return; }       // no duplicate scans
            if (!window.XMLHttpRequest || !window.FormData) { setBusy(true); return; } // native post fallback
            e.preventDefault();
            showUploadError(null);
            setBusy(true);
            setProgress(0, '{{ __("Uploading photo…") }}');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.onprogress = function(ev) {
                if (ev.lengthComputable) {
                    var pct = Math.round(ev.loaded / ev.total * 100);
                    setProgress(pct, pct < 100 ? '{{ __("Uploading photo…") }} ' + pct + '%' : '{{ __("Photo uploaded. The AI is analysing it, this can take 15–30 seconds.") }}');
                }
            };
            xhr.onload = function() {
                var body = null;
                try { body = JSON.parse(xhr.responseText); } catch (err) {}
                if (xhr.status >= 200 && xhr.status < 300 && body && body.redirect) {
                    window.location.href = body.redirect;   // result message was flashed server-side
                    return;
                }
                setBusy(false);
                if (body && body.error_code) { showScanError(body); return; }
                // No coded answer from MSAS (e.g. a proxy page): explain what the status means.
                var local = xhr.status === 413 ? SCAN_TEXT.tooLarge : (xhr.status === 419 || xhr.status === 401 ? SCAN_TEXT.session : SCAN_TEXT.unavailable);
                showScanError({ error: local, action: xhr.status === 413 ? 'choose_photo' : (xhr.status === 419 || xhr.status === 401 ? 'sign_in' : 'retry'),
                    action_url: xhr.status === 419 || xhr.status === 401 ? SCAN_TEXT.loginUrl : null });
            };
            xhr.onerror = function() {
                setBusy(false);
                // Never reached MSAS: nothing was recorded or counted.
                showScanError({ error: SCAN_TEXT.interrupted, action: 'retry' });
            };
            showScanError(null);
            xhr.send(new FormData(form));
        });
    })();
    </script>

    <style>
        @keyframes fillProgress {
            0%   { width: 0%; }
            60%  { width: 75%; }
            100% { width: 100%; }
        }
    </style>
</x-app-layout>
