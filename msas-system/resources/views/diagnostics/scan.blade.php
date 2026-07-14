<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
            <span class="text-2xl">🧠</span> {{ __('Smart AI Diagnostic Scan') }}
        </h2>
    </x-slot>

    <div class="space-y-6 max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-900 text-white p-6">
                <h3 class="text-xl font-bold mb-2">Automated Disease Detection</h3>
                <p class="text-slate-400 text-sm">Upload an image of the affected plant or animal part. Our AI engine will analyze it instantly and provide treatment recommendations.</p>
            </div>

            <form action="{{ route('diagnostics.analyze') }}" method="POST" enctype="multipart/form-data" class="p-8" id="scanForm">
                @csrf
                
                <!-- Target Selection -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-3">What are you scanning?</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="plant" class="peer sr-only" required checked>
                            <div class="rounded-xl border-2 border-slate-200 p-4 text-center hover:bg-slate-50 transition peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700">
                                <div class="text-4xl mb-2">🌿</div>
                                <div class="font-bold">Plant / Crop</div>
                                <div class="text-xs text-slate-500 mt-1">Leaves, Stems, Roots, Soil</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="animal" class="peer sr-only">
                            <div class="rounded-xl border-2 border-slate-200 p-4 text-center hover:bg-slate-50 transition peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700">
                                <div class="text-4xl mb-2">🐄</div>
                                <div class="font-bold">Livestock</div>
                                <div class="text-xs text-slate-500 mt-1">Skin, Hooves, Eyes, Stool</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Image Upload Area -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-3">Upload Image or Take Photo</label>
                    
                    <div id="drop-area" class="border-2 border-dashed border-slate-300 rounded-2xl p-10 text-center bg-slate-50 hover:bg-slate-100 transition cursor-pointer relative">
                        <input type="file" name="image" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*" required onchange="previewImage(event)">
                        
                        <div id="upload-prompt">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-3xl mx-auto shadow-sm text-slate-400 mb-4">📷</div>
                            <h4 class="font-bold text-slate-700 mb-1">Click to Upload or Drag & Drop</h4>
                            <p class="text-xs text-slate-500">Supports JPG, PNG (Max 5MB)</p>
                            
                            <button type="button" class="mt-4 px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium hover:bg-slate-50 shadow-sm flex items-center gap-2 mx-auto">
                                📱 Open Camera
                            </button>
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

                <!-- Scanning Progress (Hidden by default) -->
                <div id="scanning-progress" class="hidden mb-8 bg-slate-900 rounded-xl p-6 text-center text-white">
                    <div class="inline-block w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                    <h4 class="font-bold text-lg mb-1">AI Engine Analyzing...</h4>
                    <p class="text-sm text-slate-400">Processing image features against our database. Please wait...</p>
                    
                    <div class="w-full bg-slate-700 rounded-full h-2 mt-4 overflow-hidden">
                        <div class="bg-emerald-500 h-2 rounded-full w-0 animate-[fillProgress_3s_ease-in-out_forwards]"></div>
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
            if(document.getElementById('file-upload').files.length > 0) {
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
            50% { width: 70%; }
            100% { width: 100%; }
        }
    </style>
</x-app-layout>
