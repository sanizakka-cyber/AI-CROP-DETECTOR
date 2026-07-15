<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Poultry & Egg Production</h2>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="bg-[#b45309] text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-[#92400e]">
                + Add New Flock
            </button>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl font-semibold shadow-sm">
                    {!! session('success') !!}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-300 text-red-800 p-4 rounded-xl font-semibold shadow-sm">
                    {!! session('error') !!}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl shadow-sm">
                    <p class="font-bold mb-1">Please fix the following:</p>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-xl border border-slate-100 overflow-hidden">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 rounded-l-lg">Batch ID</th>
                                    <th class="px-4 py-3">Bird Type</th>
                                    <th class="px-4 py-3">Breed</th>
                                    <th class="px-4 py-3">Purpose</th>
                                    <th class="px-4 py-3">Qty</th>
                                    <th class="px-4 py-3">Current</th>
                                    <th class="px-4 py-3">Date Acquired</th>
                                    <th class="px-4 py-3 rounded-r-lg text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($flocks as $flock)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                        <td class="px-4 py-3 font-bold text-[#b45309] font-mono text-xs">{{ $flock->batch_number }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ $flock->bird_type }}</td>
                                        <td class="px-4 py-3 text-slate-500">{{ $flock->breed ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            @if($flock->purpose)
                                                <span class="text-xs font-semibold capitalize bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full">{{ str_replace('-', ' ', $flock->purpose) }}</span>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">{{ number_format($flock->quantity) }}</td>
                                        <td class="px-4 py-3 font-bold">{{ number_format($flock->quantity - ($flock->mortality ?? 0)) }}</td>
                                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($flock->date_acquired)->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 text-right space-x-1">
                                            @if(in_array(strtolower($flock->bird_type), ['layers', 'chicken', 'duck']))
                                                <button class="text-amber-600 font-bold text-[10px] uppercase hover:bg-amber-50 px-2 py-1 rounded">Log Eggs</button>
                                            @endif
                                            <button class="text-red-600 font-bold text-[10px] uppercase hover:bg-red-50 px-2 py-1 rounded">Log Mortality</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                                            <div class="text-5xl mb-3">🐔</div>
                                            <p class="font-semibold">No poultry flocks added yet.</p>
                                            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                                                    class="mt-3 text-[#b45309] font-bold text-sm hover:underline">
                                                Register your first flock →
                                            </button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Add Flock Modal ──────────────────────────────────────────────── --}}
    <div id="addModal" class="hidden fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 px-4 py-8 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden my-auto">
            <div class="bg-[#b45309] p-4 text-white flex justify-between items-center">
                <h3 class="font-bold text-lg">Add New Flock</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="text-white hover:text-amber-200 font-bold text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('farmer.poultry.store') }}" method="POST" class="p-6 space-y-4">
                @csrf

                {{-- Batch number: auto-generated notice --}}
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-2 text-xs text-amber-700 font-semibold">
                    Batch ID is auto-generated on save (e.g., PLT-CHK-KTS-2607-00001). It will be shown immediately after registering.
                </div>

                {{-- Bird Type --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bird Type *</label>
                    <select name="bird_type" id="bird-type-select" required onchange="handleBirdTypeChange(this.value)"
                            class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                        <option value="">— Select bird type —</option>
                        @foreach(['Chicken','Turkey','Duck','Guinea Fowl','Quail','Pigeon','Ostrich'] as $bt)
                            <option value="{{ $bt }}" {{ old('bird_type') === $bt ? 'selected' : '' }}>{{ $bt }}</option>
                        @endforeach
                        <option value="Other" {{ old('bird_type') === 'Other' ? 'selected' : '' }}>Other (not listed)</option>
                    </select>
                </div>

                {{-- Other bird type free-text --}}
                <div id="other-bird-type-div" style="{{ old('bird_type') === 'Other' ? '' : 'display:none' }}">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Specify Bird Type *</label>
                    <input type="text" name="bird_type_other" value="{{ old('bird_type_other') }}"
                           placeholder="Enter bird type..."
                           class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                    <p class="text-xs text-amber-600 mt-1">Flagged for admin review to add to the master list.</p>
                </div>

                {{-- Chicken breed dropdown (only for Chicken) --}}
                <div id="chicken-breed-div" style="{{ old('bird_type') === 'Chicken' ? '' : 'display:none' }}">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Breed / Type</label>
                    <select name="breed" id="chicken-breed-select" onchange="handleChickenBreedChange(this.value)"
                            class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                        <option value="">— Select breed —</option>
                        @foreach([
                            'Fulani ecotype (heavy)', 'Yoruba ecotype (light)', 'Igbo ecotype (medium)',
                            'Noiler', 'FUNAAB Alpha', 'Shika-Brown', 'Kuroiler', 'Sasso',
                            'Broiler (Arbor Acres, Marshall, Ross, Cobb)',
                            'Layer (Isa Brown, Lohmann Brown, Hy-Line)',
                            'Other'
                        ] as $br)
                            <option value="{{ $br }}" {{ old('breed') === $br ? 'selected' : '' }}>{{ $br }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Other breed free-text (for Chicken → Other breed) --}}
                <div id="other-breed-div" style="display:none">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Specify Breed</label>
                    <input type="text" name="breed_other_poultry"
                           placeholder="Enter breed name..."
                           class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                </div>

                {{-- Quantity & Purpose row --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Quantity *</label>
                        <input type="number" name="quantity" min="1" required value="{{ old('quantity') }}"
                               placeholder="e.g., 500"
                               class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Purpose</label>
                        <select name="purpose"
                                class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                            <option value="">— Select —</option>
                            <option value="meat" {{ old('purpose') === 'meat' ? 'selected' : '' }}>Meat</option>
                            <option value="egg" {{ old('purpose') === 'egg' ? 'selected' : '' }}>Egg Production</option>
                            <option value="breeding" {{ old('purpose') === 'breeding' ? 'selected' : '' }}>Breeding</option>
                            <option value="dual-purpose" {{ old('purpose') === 'dual-purpose' ? 'selected' : '' }}>Dual Purpose</option>
                        </select>
                    </div>
                </div>

                {{-- Date acquired --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Date Acquired *</label>
                    <input type="date" name="date_acquired" required value="{{ old('date_acquired') }}"
                           max="{{ now()->toDateString() }}"
                           class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-3 bg-[#b45309] text-white rounded-xl font-bold shadow hover:bg-[#92400e] transition text-sm">
                        Save Flock & Generate Batch ID
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function handleBirdTypeChange(type) {
        document.getElementById('other-bird-type-div').style.display  = type === 'Other' ? '' : 'none';
        document.getElementById('chicken-breed-div').style.display    = type === 'Chicken' ? '' : 'none';
        document.getElementById('other-breed-div').style.display      = 'none';
    }

    function handleChickenBreedChange(breed) {
        document.getElementById('other-breed-div').style.display = breed === 'Other' ? '' : 'none';
    }

    @if($errors->any())
        document.getElementById('addModal').classList.remove('hidden');
        const savedBirdType = '{{ old('bird_type') }}';
        if (savedBirdType) handleBirdTypeChange(savedBirdType);
    @endif
    </script>
</x-app-layout>
