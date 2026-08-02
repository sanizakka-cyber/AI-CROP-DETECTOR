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
                                <div class="text-xs text-slate-500 mt-1">{{ __('Nutrients, pH, Recommendations') }}</div>
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
                           accept="image/*" required onchange="previewImage(event)">

                    {{-- Drop zone for desktop drag-and-drop --}}
                    <div id="drop-area" class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center bg-slate-50 transition cursor-pointer"
                         onclick="triggerBrowse()">
                        <div id="upload-prompt">
                            <div class="mb-2 flex justify-center"><svg width="40" height="40" fill="none" stroke="#cbd5e1" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 10l-4-4m0 0L8 10m4-4v12"/></svg></div>
                            <h4 class="font-bold text-slate-600 mb-1 text-sm">{{ __('or drag & drop here') }}</h4>
                            <p class="text-xs text-slate-400">{{ __('JPG, PNG, HEIC — Max 5 MB') }}</p>
                        </div>
                        <div id="image-preview-container" class="hidden">
                            <img id="image-preview" src="#" alt="Preview" class="max-h-64 mx-auto rounded-lg shadow-md">
                            <p class="text-emerald-600 font-bold mt-3 text-sm flex items-center justify-center gap-1"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> {{ __('Image Ready') }}</p>
                            <button type="button" onclick="resetUpload(); event.stopPropagation();"
                                class="mt-2 text-xs text-red-500 hover:underline relative z-10">{{ __('Remove & try again') }}</button>
                        </div>
                    </div>
                    @error('image')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
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
                    <div class="w-full bg-slate-700 rounded-full h-2 mt-4 overflow-hidden">
                        <div class="bg-emerald-500 h-2 rounded-full w-0 animate-[fillProgress_30s_ease-in-out_forwards]"></div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" id="submitBtn" class="bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition flex items-center gap-2 ml-auto">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg> {{ __('Run Full AI Diagnosis') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
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
            if (files && files[0] && files[0].type.startsWith('image/')) {
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

    function previewImage(event) {
        var input = event.target;
        if (input.files && input.files[0]) {
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
        document.getElementById('image-preview-container').classList.add('hidden');
        document.getElementById('upload-prompt').classList.remove('hidden');
    }
    document.getElementById('scanForm').addEventListener('submit', function() {
        if (document.getElementById('file-upload').files.length > 0) {
            document.getElementById('scanning-progress').classList.remove('hidden');
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').classList.add('opacity-50', 'cursor-not-allowed');
            document.getElementById('submitBtn').innerHTML = 'Analysing...';
        }
    });
    </script>

    <style>
        @keyframes fillProgress {
            0%   { width: 0%; }
            60%  { width: 75%; }
            100% { width: 100%; }
        }
    </style>
</x-app-layout>
