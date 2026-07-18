<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Livestock Management</h2>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="bg-[#0F6B3E] text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-[#047857]">
                + Register Animal
            </button>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl font-semibold shadow-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-300 text-red-800 p-4 rounded-xl font-semibold shadow-sm">
                    {{ session('error') }}
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
                                    <th class="px-4 py-3 rounded-l-lg">Tag / ID</th>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Species</th>
                                    <th class="px-4 py-3">Breed</th>
                                    <th class="px-4 py-3">Gender</th>
                                    <th class="px-4 py-3">Weight (kg)</th>
                                    <th class="px-4 py-3">Health</th>
                                    <th class="px-4 py-3 rounded-r-lg text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($livestock as $animal)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                        <td class="px-4 py-3 font-bold text-[#0F6B3E] font-mono text-xs">{{ $animal->tag_number }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ $animal->name ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $animal->species }}</td>
                                        <td class="px-4 py-3 text-slate-500">{{ $animal->breed ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ $animal->gender }}</td>
                                        <td class="px-4 py-3">{{ $animal->weight_kg ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            @php $hs = strtolower($animal->health_status ?? 'healthy'); @endphp
                                            <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;
                                                background:{{ $hs === 'healthy' ? '#f0fdf4' : ($hs === 'sick' ? '#fef2f2' : '#fef3c7') }};
                                                color:{{ $hs === 'healthy' ? '#15803d' : ($hs === 'sick' ? '#dc2626' : '#92400e') }};">
                                                {{ ucfirst($hs) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button onclick="openEditLivestock({{ $animal->id }}, '{{ addslashes($animal->name ?? '') }}', '{{ addslashes($animal->species) }}', '{{ addslashes($animal->breed ?? '') }}', '{{ addslashes($animal->gender ?? '') }}', '{{ $animal->weight_kg ?? '' }}')"
                                                    class="text-[#0F6B3E] font-bold text-[10px] uppercase hover:bg-emerald-50 px-2 py-1 rounded">Edit</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                                            <div class="text-5xl mb-3">🐄</div>
                                            <p class="font-semibold">No livestock registered yet.</p>
                                            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                                                    class="mt-3 text-[#0F6B3E] font-bold text-sm hover:underline">
                                                Register your first animal →
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

    {{-- ── Register Animal Modal ──────────────────────────────────────── --}}
    <div id="addModal" class="hidden fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 px-4 py-8 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden my-auto">
            <div class="bg-[#0F6B3E] p-4 text-white flex justify-between items-center">
                <h3 class="font-bold text-lg">Register New Animal</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="text-white hover:text-emerald-200 font-bold text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('farmer.livestock.store') }}" method="POST" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Animal Name (optional)</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           placeholder="e.g., Fatima, Bull-1"
                           class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Species *</label>
                    <select name="species" id="species-select" required onchange="handleSpeciesChange(this.value)"
                            class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                        <option value="">Select species</option>
                        @foreach(['Cattle','Goat','Sheep','Pig','Camel','Donkey','Horse','Rabbit'] as $sp)
                            <option value="{{ $sp }}" {{ old('species') === $sp ? 'selected' : '' }}>{{ $sp }}</option>
                        @endforeach
                        <option value="Other" {{ old('species') === 'Other' ? 'selected' : '' }}>Others</option>
                    </select>
                </div>

                <div id="other-species-div" style="{{ old('species') === 'Other' ? '' : 'display:none' }}">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Specify Species *</label>
                    <input type="text" name="species_other" value="{{ old('species_other') }}"
                           placeholder="Enter species name..."
                           class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                    <p class="text-xs text-amber-600 mt-1">This will be flagged for admin review.</p>
                </div>

                <div id="breed-dropdown-div" style="{{ old('species') === 'Other' ? 'display:none' : '' }}">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Breed</label>
                    <select name="breed" id="breed-select" onchange="handleBreedChange(this.value)"
                            class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                        <option value="">Select species first</option>
                    </select>
                </div>

                <div id="other-breed-div" style="display:none">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Specify Breed</label>
                    <input type="text" name="breed_other" value="{{ old('breed_other') }}"
                           placeholder="Enter breed name..."
                           class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Gender *</label>
                        <select name="gender" required
                                class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                            <option value="">Select gender</option>
                            <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Weight (kg)</label>
                        <input type="number" step="0.1" min="0" name="weight_kg" value="{{ old('weight_kg') }}"
                               placeholder="e.g., 350"
                               class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-3 bg-[#0F6B3E] text-white rounded-xl font-bold shadow hover:bg-[#047857] transition text-sm">
                        Register Animal
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Edit Animal Modal ──────────────────────────────────────────── --}}
    <div id="editModal" class="hidden fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 px-4 py-8 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden my-auto">
            <div class="bg-slate-700 p-4 text-white flex justify-between items-center">
                <h3 class="font-bold text-lg">Edit Animal</h3>
                <button onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="text-white hover:text-slate-300 font-bold text-2xl leading-none">&times;</button>
            </div>

            <form id="editForm" action="" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Animal Name (optional)</label>
                    <input type="text" name="name" id="edit-name" placeholder="e.g., Fatima"
                           class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Species</label>
                    <input type="text" name="species" id="edit-species"
                           class="w-full border-slate-200 bg-slate-50 rounded-lg text-sm text-slate-500" readonly>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Breed</label>
                    <input type="text" name="breed" id="edit-breed" placeholder="e.g., Bunaji"
                           class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Gender</label>
                        <select name="gender" id="edit-gender"
                                class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                            <option value="">Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Weight (kg)</label>
                        <input type="number" step="0.1" min="0" name="weight_kg" id="edit-weight"
                               placeholder="e.g., 350"
                               class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-3 bg-slate-700 text-white rounded-xl font-bold shadow hover:bg-slate-800 transition text-sm">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const breedData = {
        Cattle:  ['White Fulani (Bunaji)', 'Sokoto Gudali', 'Red Bororo (Rahaji)', 'Azawak', 'Muturu', "N'Dama", 'Kuri', 'Wadara (Shuwa)', 'Others'],
        Goat:    ['Red Sokoto (Maradi/Kano Brown)', 'Sahel (West African Long-legged)', 'West African Dwarf (WAD)', 'Others'],
        Sheep:   ['Yankasa', 'Balami', 'Uda (Ouda)', 'West African Dwarf (WAD)', 'Others'],
        Pig:     ['Local/Indigenous pig', 'Large White', 'Landrace', 'Duroc', 'Others'],
        Camel:   ['Others'], Donkey: ['Others'], Horse: ['Others'], Rabbit: ['Others'],
    };

    function handleSpeciesChange(species) {
        const otherDiv   = document.getElementById('other-species-div');
        const breedDiv   = document.getElementById('breed-dropdown-div');
        const otherBreed = document.getElementById('other-breed-div');
        const breedSel   = document.getElementById('breed-select');

        if (species === 'Other') {
            otherDiv.style.display = ''; breedDiv.style.display = 'none'; otherBreed.style.display = 'none';
            return;
        }
        otherDiv.style.display = 'none';
        const breeds = breedData[species] || [];
        breedSel.innerHTML = '<option value="">Select breed</option>';
        breeds.forEach(b => { const o = document.createElement('option'); o.value = b; o.textContent = b; breedSel.appendChild(o); });
        breedDiv.style.display = breeds.length > 0 ? '' : 'none';
        otherBreed.style.display = 'none';
    }

    function handleBreedChange(breed) {
        document.getElementById('other-breed-div').style.display = breed === 'Others' ? '' : 'none';
    }

    function openEditLivestock(id, name, species, breed, gender, weight) {
        document.getElementById('edit-name').value    = name;
        document.getElementById('edit-species').value = species;
        document.getElementById('edit-breed').value   = breed;
        document.getElementById('edit-gender').value  = gender;
        document.getElementById('edit-weight').value  = weight;
        document.getElementById('editForm').action    = '/farmer/livestock/' + id;
        document.getElementById('editModal').classList.remove('hidden');
    }

    @if($errors->any())
        document.getElementById('addModal').classList.remove('hidden');
        const savedSpecies = '{{ old('species') }}';
        if (savedSpecies) handleSpeciesChange(savedSpecies);
        if ('{{ old('breed') }}' === 'Others') document.getElementById('other-breed-div').style.display = '';
    @endif
    </script>
</x-app-layout>
