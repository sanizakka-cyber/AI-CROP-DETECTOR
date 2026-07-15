<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
            <span class="text-2xl">🧠</span> {{ __('Smart AI Diagnostic Scan') }}
        </h2>
    </x-slot>

    <div class="space-y-6 max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-900 text-white p-6">
                <h3 class="text-xl font-bold mb-2">Automated Disease & Condition Detection</h3>
                <p class="text-slate-400 text-sm">Upload a clear photo of the affected plant, animal, or soil sample. Claude AI will analyse it and provide specific, image-grounded recommendations.</p>
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
                    <label class="block text-sm font-bold text-slate-700 mb-3">What are you scanning?</label>
                    <div class="grid grid-cols-3 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="plant" class="peer sr-only" required checked
                                   onchange="setScanType('plant')">
                            <div class="rounded-xl border-2 border-slate-200 p-4 text-center hover:bg-slate-50 transition peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700">
                                <div class="text-4xl mb-2">🌿</div>
                                <div class="font-bold text-sm">Plant / Crop</div>
                                <div class="text-xs text-slate-500 mt-1">Leaves, Stems, Roots</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="animal" class="peer sr-only"
                                   onchange="setScanType('animal')">
                            <div class="rounded-xl border-2 border-slate-200 p-4 text-center hover:bg-slate-50 transition peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700">
                                <div class="text-4xl mb-2">🐄</div>
                                <div class="font-bold text-sm">Livestock</div>
                                <div class="text-xs text-slate-500 mt-1">Skin, Eyes, Stool</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="soil" class="peer sr-only"
                                   onchange="setScanType('soil')">
                            <div class="rounded-xl border-2 border-slate-200 p-4 text-center hover:bg-slate-50 transition peer-checked:border-amber-700 peer-checked:bg-amber-50 peer-checked:text-amber-800">
                                <div class="text-4xl mb-2">🌱</div>
                                <div class="font-bold text-sm">Soil Sample</div>
                                <div class="text-xs text-slate-500 mt-1">Nutrients, pH, Crops</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Context Fields — shown conditionally -->
                <div id="ctx-plant" class="mb-6 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Crop Type *</label>
                        <input type="text" name="crop_type" placeholder="e.g., Maize, Tomato, Cassava"
                               class="w-full border-slate-200 rounded-lg text-sm focus:ring-emerald-400 focus:border-emerald-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Part / Symptom Area</label>
                        <select name="crop_part" class="w-full border-slate-200 rounded-lg text-sm focus:ring-emerald-400 focus:border-emerald-400">
                            <option value="leaf">Leaf</option>
                            <option value="stem">Stem / Stalk</option>
                            <option value="root">Root</option>
                            <option value="fruit">Fruit / Pod</option>
                            <option value="whole plant">Whole Plant</option>
                        </select>
                    </div>
                </div>

                <div id="ctx-animal" class="mb-6 grid grid-cols-2 gap-4 hidden">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Animal Type *</label>
                        <input type="text" name="animal_type" placeholder="e.g., Cattle, Chicken, Goat"
                               class="w-full border-slate-200 rounded-lg text-sm focus:ring-amber-400 focus:border-amber-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">What You're Showing</label>
                        <select name="assessment_type" class="w-full border-slate-200 rounded-lg text-sm focus:ring-amber-400 focus:border-amber-400">
                            <option value="skin/coat">Skin / Coat</option>
                            <option value="droppings">Droppings / Stool</option>
                            <option value="eyes">Eyes</option>
                            <option value="hooves">Hooves / Feet</option>
                            <option value="wound">Wound / Lesion</option>
                            <option value="general">General / Other</option>
                        </select>
                    </div>
                </div>

                <div id="ctx-soil" class="mb-6 hidden">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Additional Context (optional)</label>
                    <input type="text" name="soil_context" placeholder="e.g., Farm location, current crop, known issues"
                           class="w-full border-slate-200 rounded-lg text-sm focus:ring-amber-700 focus:border-amber-700">
                </div>

                <!-- Image Upload Area -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-3">Upload Image or Take Photo</label>

                    <div id="drop-area" class="border-2 border-dashed border-slate-300 rounded-2xl p-10 text-center bg-slate-50 hover:bg-slate-100 transition cursor-pointer relative">
                        <input type="file" name="image" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*" required onchange="previewImage(event)">

                        <div id="upload-prompt">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-3xl mx-auto shadow-sm text-slate-400 mb-4">📷</div>
                            <h4 class="font-bold text-slate-700 mb-1">Click to Upload or Drag & Drop</h4>
                            <p class="text-xs text-slate-500">Supports JPG, PNG — Max 5 MB</p>
                        </div>

                        <div id="image-preview-container" class="hidden">
                            <img id="image-preview" src="#" alt="Preview" class="max-h-64 mx-auto rounded-lg shadow-md">
                            <p class="text-emerald-600 font-bold mt-3 text-sm flex items-center justify-center gap-1"><span>✅</span> Image Selected</p>
                            <button type="button" onclick="resetUpload()" class="mt-2 text-xs text-red-500 hover:underline relative z-10">Remove and select another</button>
                        </div>
                    </div>
                    @error('image')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Scanning Progress -->
                <div id="scanning-progress" class="hidden mb-8 bg-slate-900 rounded-xl p-6 text-center text-white">
                    <div class="inline-block w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                    <h4 class="font-bold text-lg mb-1">AI Engine Analysing...</h4>
                    <p class="text-sm text-slate-400">Claude Vision is examining your image. This may take 10–20 seconds.</p>
                    <div class="w-full bg-slate-700 rounded-full h-2 mt-4 overflow-hidden">
                        <div class="bg-emerald-500 h-2 rounded-full w-0 animate-[fillProgress_20s_ease-in-out_forwards]"></div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" id="submitBtn" class="bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition flex items-center gap-2 ml-auto">
                        <span>🔍</span> Run Diagnostics
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
    }
    function previewImage(event) {
        const input = event.target;
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('image-preview').src = e.target.result;
                document.getElementById('upload-prompt').classList.add('hidden');
                document.getElementById('image-preview-container').classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    function resetUpload() {
        document.getElementById('file-upload').value = "";
        document.getElementById('image-preview').src = "#";
        document.getElementById('image-preview-container').classList.add('hidden');
        document.getElementById('upload-prompt').classList.remove('hidden');
    }
    document.getElementById('scanForm').addEventListener('submit', function(e) {
        if (document.getElementById('file-upload').files.length > 0) {
            document.getElementById('scanning-progress').classList.remove('hidden');
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').classList.add('opacity-50', 'cursor-not-allowed');
            document.getElementById('submitBtn').innerHTML = 'Processing...';
        }
    });
    </script>

    <style>
        @keyframes fillProgress {
            0% { width: 0%; }
            60% { width: 80%; }
            100% { width: 100%; }
        }
    </style>
</x-app-layout>
