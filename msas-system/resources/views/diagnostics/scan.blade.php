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
                    <label class="block text-sm font-bold text-slate-700 mb-1">{{ __('Sample Collection Location') }} <span class="font-normal text-slate-400">{{ __('(optional)') }}</span></label>
                    <p class="text-xs text-slate-400 mb-3">{{ __('Recording where this sample was actually collected helps track outbreaks and makes your scan useful for agricultural research. This never blocks your diagnosis — leave it blank if unsure.') }}</p>

                    {{-- Two ways to record the location: GPS fills State and LGA from the boundaries, or the farmer picks them --}}
                    <div class="grid grid-cols-2 gap-2 mb-3" role="radiogroup" aria-label="{{ __('How to record the location') }}">
                        <button type="button" id="locModeGps" role="radio" aria-checked="false" onclick="useMyLocation()"
                            class="loc-mode text-sm font-bold border rounded-lg px-3 py-2.5 flex items-center justify-center gap-1.5 border-slate-200 text-slate-600 hover:bg-emerald-50">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.25a7 7 0 1114 0C19 12.75 16.5 16.5 12 21z"/><circle cx="12" cy="9.75" r="2.25"/></svg>
                            {{ __('Use GPS') }}
                        </button>
                        <button type="button" id="locModeManual" role="radio" aria-checked="false" onclick="useManualEntry()"
                            class="loc-mode text-sm font-bold border rounded-lg px-3 py-2.5 flex items-center justify-center gap-1.5 border-slate-200 text-slate-600 hover:bg-emerald-50">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.1 2.1 0 113 2.97L7.5 19.82 3 21l1.18-4.5L16.862 4.487z"/></svg>
                            {{ __('Enter by hand') }}
                        </button>
                    </div>

                    <div id="locGpsStatus" class="hidden mb-3 text-xs rounded-lg p-2" aria-live="polite"></div>

                    {{-- While the GPS is being watched: the accuracy as it improves --}}
                    <div id="locProgress" class="hidden mb-3 flex flex-wrap items-center gap-2 bg-emerald-50 text-emerald-800 text-xs rounded-lg p-2" aria-live="polite" data-loc-progress="1">
                        <span class="inline-block w-3.5 h-3.5 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin"></span>
                        <span id="locProgressText" class="font-semibold"></span>
                        <button type="button" id="locStopNow" onclick="stopCaptureNow()" class="hidden ml-auto font-bold underline">{{ __('Use this reading now') }}</button>
                    </div>

                    {{-- A failure never blocks the scan: what to do, try again, or enter by hand --}}
                    <div id="locError" class="hidden mb-3 bg-red-50 border border-red-200 text-red-800 text-xs rounded-lg p-3" role="alert" data-loc-error="">
                        <p id="locErrorText" class="font-semibold"></p>
                        <ul id="locErrorHelp" class="list-disc ml-4 mt-1 space-y-0.5"></ul>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <button type="button" onclick="useMyLocation()" class="font-bold text-white bg-red-700 hover:bg-red-800 px-3 py-1.5 rounded-lg">{{ __('Try again') }}</button>
                            <button type="button" onclick="useManualEntry()" class="font-bold text-red-800 bg-white border border-red-300 px-3 py-1.5 rounded-lg">{{ __('Enter manually instead') }}</button>
                        </div>
                    </div>

                    {{-- The captured point, to check before submitting --}}
                    <div id="locResult" class="hidden mb-3 border border-slate-200 rounded-lg p-3" data-loc-result="1">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span id="locRating" class="px-2 py-0.5 rounded-full font-bold" data-rating=""></span>
                            <span id="locAccText" class="font-semibold text-slate-700"></span>
                            <span id="locAdjusted" class="hidden text-slate-500">{{ __('Point adjusted on the map by you.') }}</span>
                            <a id="locGmaps" href="#" target="_blank" rel="noopener" class="ml-auto font-bold text-emerald-700 hover:underline">{{ __('Open in Google Maps') }} ↗</a>
                        </div>
                        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-3 gap-y-1 text-xs mt-2">
                            <div><dt class="text-slate-400">{{ __('Latitude') }}</dt><dd id="locLatText" class="font-mono text-slate-700"></dd></div>
                            <div><dt class="text-slate-400">{{ __('Longitude') }}</dt><dd id="locLonText" class="font-mono text-slate-700"></dd></div>
                            <div><dt class="text-slate-400">{{ __('Accuracy') }}</dt><dd id="locAccCell" class="text-slate-700"></dd></div>
                            <div><dt class="text-slate-400">{{ __('Time captured') }}</dt><dd id="locTimeText" class="text-slate-700"></dd></div>
                        </dl>
                        <div id="locMap" class="mt-2 h-56 rounded-lg border border-slate-200 bg-slate-100 relative z-0 isolate" role="img" aria-label="{{ __('Map preview of the captured location') }}"></div>
                        <p class="text-[11px] text-slate-500 mt-1">{{ __('Drag the pin if it is not where the sample was collected.') }}</p>
                        <div id="locAdvice" class="hidden mt-2 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-lg p-2" data-loc-advice="1">
                            <p id="locAdviceText" class="font-semibold"></p>
                            <ul class="list-disc ml-4 mt-1 space-y-0.5">
                                <li>{{ __('Go outside, away from buildings and trees.') }}</li>
                                <li>{{ __('Wait a few seconds for the signal to settle.') }}</li>
                                <li>{{ __('Turn on your phone\'s high-accuracy location mode.') }}</li>
                            </ul>
                            <p id="locDesktopHint" class="hidden mt-1 font-semibold">{{ __('This computer has no GPS, so its location comes from Wi-Fi and can be hundreds of metres out. For a precise location, use the MSAS app or your phone\'s browser in the field.') }}</p>
                            <button type="button" onclick="useMyLocation()" class="mt-2 font-bold text-white bg-amber-600 hover:bg-amber-700 px-3 py-1.5 rounded-lg">{{ __('Try again') }}</button>
                        </div>
                    </div>

                    <p id="locNamesSource" class="hidden mb-3 text-xs text-slate-500"></p>
                    <p class="mb-3 text-[11px] text-slate-400">{{ __('Privacy: the exact point is visible only to you and authorised MSAS staff. Reports, exports, research data and outbreak maps show the State and LGA only.') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('State') }}</label>
                            <select id="locState" name="loc_state" class="w-full border-slate-200 rounded-lg text-sm" onchange="populateLgas(); namesChangedByUser();">
                                <option value="">{{ __('Select State') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('LGA') }}</label>
                            <select id="locLga" name="loc_lga" class="w-full border-slate-200 rounded-lg text-sm" onchange="namesChangedByUser();">
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
                        <input type="checkbox" id="locDiffers" name="loc_differs_from_scan" value="1" class="mt-0.5 rounded border-slate-300" onchange="differsChanged();">
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
                    <input type="hidden" id="locSource" name="loc_location_source" value="">
                    <input type="hidden" id="locGpsAt" name="loc_gps_captured_at" value="">
                    <input type="hidden" id="locNamesFrom" name="loc_place_names_source" value="">
                    <input type="hidden" id="locScanLatitude" name="loc_scan_latitude" value="">
                    <input type="hidden" id="locScanLongitude" name="loc_scan_longitude" value="">
                    <input type="hidden" id="locScanAccuracy" name="loc_scan_accuracy_meters" value="">
                    <input type="hidden" id="locOrigLatitude" name="loc_gps_original_latitude" value="">
                    <input type="hidden" id="locOrigLongitude" name="loc_gps_original_longitude" value="">
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
        $locText = [
            'lookupUrl'      => route('diagnostics.location-lookup'),
            'requesting'     => __('Requesting your location…'),
            'gettingFirst'   => __('Getting your location… ±:to m'),
            'improving'      => __('Improving accuracy… ±:from m → ±:to m'),
            'captured'       => __('Location captured: ±:acc m (:rating).'),
            'bestIn30'       => __('This is the best reading in 30 seconds.'),
            'adviceFairLow'  => __('The accuracy is :rating (±:acc m). For a better reading:'),
            'checkNames'     => __('Check the State and LGA below.'),
            'ratings'        => ['excellent' => __('Excellent'), 'good' => __('Good'), 'fair' => __('Fair'), 'low' => __('Low')],
            'deniedHelp'     => [__('Click the lock or site-settings icon next to the web address.'), __('Open Location and choose Allow.'), __('Then press Try again.')],
            'unavailableHelp' => [__('Check that location (GPS) is turned on for this device.'), __('Go outside, away from buildings and trees.')],
            'noSignal'       => __('No location was found within 30 seconds. Go outside, away from buildings and trees, and try again.'),
            'lookingUp'      => __('Finding your State and LGA…'),
            'filledFrom'     => __('Filled from your GPS point: :place. Change them if they are wrong.'),
            'notFound'       => __('Your GPS point is not inside a Nigerian LGA we know. Please select the State and LGA below.'),
            'fillOnSubmit'   => __('Could not look up your State and LGA now. Leave them blank and they will be filled from your GPS point when you submit, or select them below.'),
            'namesChanged'   => __('You changed the State or LGA found from GPS. Your choice will be saved.'),
            'scanPlaceSaved' => __('Your GPS point is saved as the place you are scanning from. Enter where the sample was collected below.'),
            'unsupported'    => __('Your browser does not support location capture. Please select your state/LGA manually.'),
            'denied'         => __('This site is not allowed to use your location.'),
            'unavailable'    => __('Your device could not find its location.'),
            'failed'         => __('Could not get your location. You can still select your state/LGA manually below.'),
        ];
    @endphp
    {{-- GPS capture (best reading over 30 s) and the map preview with a draggable pin --}}
    <script src="{{ asset('js/gps-quality.js') }}"></script>
    @vite('resources/js/location-map.js')
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

    // ── Location: two ways ────────────────────────────────────────────────────
    // GPS: the fix is stored and State/LGA are filled from the boundaries
    // (LocationLookupController); if that call fails the server fills them on
    // submit. By hand: the farmer picks State/LGA. loc_location_source says
    // which (gps, manual, or gps_edited when GPS-filled names were changed).
    // With "scanning somewhere else" ticked, the fix is where the phone is,
    // so it is sent as the scan place, never as the collection place.
    var LOC_TEXT = @json($locText);
    var locFix = null;         // the GPS fix whose names are being looked up
    var locFilled = false;     // State/LGA currently hold the lookup's answer

    function setVal(id, v) { document.getElementById(id).value = v == null ? '' : v; }
    function val(id) { return document.getElementById(id).value; }

    function setLocMode(mode) {
        [['locModeGps', 'gps'], ['locModeManual', 'manual']].forEach(function (b) {
            var el = document.getElementById(b[0]), on = mode === b[1];
            el.setAttribute('aria-checked', on ? 'true' : 'false');
            el.classList.toggle('border-emerald-500', on);
            el.classList.toggle('bg-emerald-50', on);
            el.classList.toggle('text-emerald-800', on);
            el.classList.toggle('border-slate-200', !on);
            el.classList.toggle('text-slate-600', !on);
        });
    }

    function showLocStatus(type, msg) {
        var el = document.getElementById('locGpsStatus');
        el.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-emerald-50', 'text-emerald-700', 'bg-amber-50', 'text-amber-800');
        if (!msg) { el.classList.add('hidden'); return; }
        el.classList.add.apply(el.classList, {
            ok: ['bg-emerald-50', 'text-emerald-700'], warn: ['bg-amber-50', 'text-amber-800'], error: ['bg-red-50', 'text-red-700'],
        }[type]);
        el.textContent = msg;
    }

    function showNamesSource(msg) {
        var el = document.getElementById('locNamesSource');
        el.textContent = msg || '';
        el.classList.toggle('hidden', !msg);
    }

    function setStateAndLga(state, lga) {
        document.getElementById('locState').value = state || '';
        populateLgas();
        document.getElementById('locLga').value = lga || '';
    }

    var locCapture = null;     // the running MsasGps.captureBest(), if any
    var locOriginal = null;    // the device's own fix once the pin has been moved

    function fmtAcc(m) { return m < 10 ? (Math.round(m * 10) / 10).toString() : String(Math.round(m)); }
    function ratingLabel(r) { return LOC_TEXT.ratings[r] || r; }

    function hide(id, hidden) { document.getElementById(id).classList.toggle('hidden', hidden !== false); }

    function showProgress(text, canStop) {
        document.getElementById('locProgressText').textContent = text;
        hide('locStopNow', !canStop);
        hide('locProgress', false);
    }

    function showLocError(code) {
        var text = { denied: LOC_TEXT.denied, unavailable: LOC_TEXT.unavailable, timeout: LOC_TEXT.noSignal, unsupported: LOC_TEXT.unsupported }[code] || LOC_TEXT.failed;
        var help = { denied: LOC_TEXT.deniedHelp, unavailable: LOC_TEXT.unavailableHelp }[code] || [];
        document.getElementById('locErrorText').textContent = text;
        var list = document.getElementById('locErrorHelp');
        list.innerHTML = '';
        help.forEach(function (h) { var li = document.createElement('li'); li.textContent = h; list.appendChild(li); });
        document.getElementById('locError').setAttribute('data-loc-error', code);
        hide('locError', false);
    }

    function clearCollectionFix() {
        ['locLatitude', 'locLongitude', 'locAccuracy', 'locGpsAt', 'locNamesFrom', 'locOrigLatitude', 'locOrigLongitude'].forEach(function (id) { setVal(id, ''); });
        if (locFilled) { setStateAndLga('', ''); }
        locFix = null;
        locFilled = false;
        locOriginal = null;
        showNamesSource('');
        hide('locResult');
    }

    function useManualEntry() {
        if (locCapture) { var c = locCapture; locCapture = null; c.stop(); }
        setLocMode('manual');
        clearCollectionFix();
        hide('locProgress');
        hide('locError');
        setVal('locSource', 'manual');
        setVal('locCaptureMethod', val('locState') ? 'administrative_selection' : '');
        showLocStatus(null);
    }

    function namesChangedByUser() {
        if (!val('locSource')) {
            setVal('locSource', 'manual');
            setLocMode('manual');
        }
        if (val('locCaptureMethod') !== 'gps_device') {
            setVal('locCaptureMethod', val('locState') ? 'administrative_selection' : '');
        }
        if (locFilled) {
            locFilled = false;
            // A moved pin stays "adjusted"; the names are recorded as the farmer's either way.
            if (!locOriginal) { setVal('locSource', 'gps_edited'); }
            setVal('locNamesFrom', 'user');
            showNamesSource(LOC_TEXT.namesChanged);
        }
    }

    function setScanPlace(fix) {
        setVal('locScanLatitude', fix ? fix.lat : '');
        setVal('locScanLongitude', fix ? fix.lon : '');
        setVal('locScanAccuracy', fix ? fix.acc : '');
    }

    function differsChanged() {
        if (!document.getElementById('locDiffers').checked) {
            setScanPlace(null);
            return;
        }
        if (val('locLatitude')) {
            // The device's fix is where the phone is: the scan place.
            setScanPlace({ lat: locOriginal ? locOriginal.lat : val('locLatitude'), lon: locOriginal ? locOriginal.lon : val('locLongitude'), acc: val('locAccuracy') });
            useManualEntry();
            showLocStatus('ok', LOC_TEXT.scanPlaceSaved);
        }
    }

    function lookupNames(fix) {
        showNamesSource(LOC_TEXT.lookingUp);
        fetch(LOC_TEXT.lookupUrl + '?lat=' + encodeURIComponent(fix.lat) + '&lon=' + encodeURIComponent(fix.lon), {
            headers: { 'Accept': 'application/json' }, credentials: 'same-origin',
        }).then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); }).then(function (d) {
            if (locFix !== fix) { return; } // a newer fix or a switch to manual came first
            if (!d.found) { showNamesSource(LOC_TEXT.notFound); return; }
            setStateAndLga(d.state, d.lga);
            locFilled = true;
            setVal('locNamesFrom', 'lookup');
            showNamesSource(LOC_TEXT.filledFrom.replace(':place', d.lga + ', ' + d.state) + ' ' + d.attribution);
        }).catch(function () {
            // Keep the coordinates: the server fills the names on submit, or the farmer picks them.
            if (locFix === fix) { showNamesSource(LOC_TEXT.fillOnSubmit); }
        });
    }

    // The point the form will submit, shown for checking: coordinates, the
    // accuracy the device reported (never rounded down to look better), its
    // rating, the time, and the map with a pin the farmer can drag.
    function showResult(fix) {
        var r = MsasGps.rating(fix.acc);
        var badge = document.getElementById('locRating');
        badge.textContent = ratingLabel(r);
        badge.setAttribute('data-rating', r);
        badge.className = 'px-2 py-0.5 rounded-full font-bold ' + ({
            excellent: 'bg-emerald-100 text-emerald-800', good: 'bg-sky-100 text-sky-800',
            fair: 'bg-amber-100 text-amber-800', low: 'bg-red-100 text-red-800',
        }[r]);
        document.getElementById('locAccText').textContent = '±' + fmtAcc(fix.acc) + ' m';
        document.getElementById('locAccCell').textContent = '±' + fmtAcc(fix.acc) + ' m';
        document.getElementById('locTimeText').textContent = new Date(fix.at).toLocaleString();
        showPoint(fix.lat, fix.lon);
        hide('locAdjusted');

        var advice = MsasGps.needsRetry(r);
        hide('locAdvice', !advice);
        if (advice) {
            document.getElementById('locAdviceText').textContent = LOC_TEXT.adviceFairLow.replace(':rating', ratingLabel(r)).replace(':acc', fmtAcc(fix.acc));
        }
        hide('locDesktopHint', !(advice && fix.acc > 50 && MsasGps.isLikelyDesktop(navigator, window)));
        hide('locResult', false);

        if (window.MsasMap) {
            window.MsasMap.show(document.getElementById('locMap'), {
                lat: fix.lat, lon: fix.lon, accuracy: fix.acc, onMove: onPinMoved,
            });
        } else {
            hide('locMap');
        }
    }

    function showPoint(lat, lon) {
        document.getElementById('locLatText').textContent = Number(lat).toFixed(6);
        document.getElementById('locLonText').textContent = Number(lon).toFixed(6);
        document.getElementById('locGmaps').href = 'https://www.google.com/maps?q=' + lat + ',' + lon;
    }

    // The farmer moved the pin: that point is submitted, the device's fix is kept beside it.
    function onPinMoved(lat, lon) {
        if (!locOriginal) {
            locOriginal = { lat: val('locLatitude'), lon: val('locLongitude') };
            setVal('locOrigLatitude', locOriginal.lat);
            setVal('locOrigLongitude', locOriginal.lon);
        }
        setVal('locLatitude', lat);
        setVal('locLongitude', lon);
        setVal('locSource', 'gps_adjusted');
        showPoint(lat, lon);
        hide('locAdjusted', false);
        // Names the lookup filled follow the pin; names the farmer chose stay.
        if (locFilled || !val('locState')) {
            locFix = { lat: lat, lon: lon };
            lookupNames(locFix);
        }
    }

    function applyFix(fix, reason) {
        fix = { lat: fix.lat, lon: fix.lon, acc: fix.accuracy, at: new Date(fix.timestamp || Date.now()).toISOString() };
        if (document.getElementById('locDiffers').checked) {
            setScanPlace(fix);
            useManualEntry();
            showLocStatus('ok', LOC_TEXT.scanPlaceSaved);
            return;
        }
        clearCollectionFix();
        setVal('locLatitude', fix.lat);
        setVal('locLongitude', fix.lon);
        setVal('locAccuracy', Math.round(fix.acc * 10) / 10);
        setVal('locGpsAt', fix.at);
        setVal('locCaptureMethod', 'gps_device');
        setVal('locSource', 'gps');
        locFix = fix;
        var r = MsasGps.rating(fix.acc);
        showLocStatus(MsasGps.needsRetry(r) ? 'warn' : 'ok',
            LOC_TEXT.captured.replace(':acc', fmtAcc(fix.acc)).replace(':rating', ratingLabel(r))
            + (reason === 'window' ? ' ' + LOC_TEXT.bestIn30 : '') + ' ' + LOC_TEXT.checkNames);
        showResult(fix);
        lookupNames(fix);
    }

    function stopCaptureNow() { if (locCapture) { locCapture.stop(); } }

    // Watches the GPS for up to 30 s for a fresh position (no cached one),
    // keeps the best reading, and stops as soon as it is 5 m or better.
    function useMyLocation() {
        setLocMode('gps');
        hide('locError');
        showLocStatus(null);
        if (locCapture) { var old = locCapture; locCapture = null; old.stop(); }
        if (!('geolocation' in navigator) || !window.MsasGps) {
            showLocError('unsupported');
            return;
        }
        showProgress(LOC_TEXT.requesting, false);
        var capture = MsasGps.captureBest(navigator.geolocation, {
            onProgress: function (best, first) {
                showProgress(first.accuracy > best.accuracy
                    ? LOC_TEXT.improving.replace(':from', fmtAcc(first.accuracy)).replace(':to', fmtAcc(best.accuracy))
                    : LOC_TEXT.gettingFirst.replace(':to', fmtAcc(best.accuracy)), true);
            },
        });
        locCapture = capture;
        capture.promise.then(function (res) {
            if (locCapture !== capture) { return; } // replaced or cancelled
            locCapture = null;
            hide('locProgress');
            applyFix(res.fix, res.reason);
        }, function (err) {
            if (locCapture !== capture) { return; }
            locCapture = null;
            hide('locProgress');
            showLocError(err.code);
        });
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
