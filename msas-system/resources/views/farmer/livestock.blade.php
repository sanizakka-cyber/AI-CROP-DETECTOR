<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Livestock Management') }}
            </h2>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-[#0F6B3E] text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-[#047857]">
                + Register Animal
            </button>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-4 bg-green-100 text-green-700 p-4 rounded-xl font-bold shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-xl border border-slate-100 overflow-hidden">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 rounded-l-lg">Tag / ID</th>
                                    <th class="px-4 py-3">Species</th>
                                    <th class="px-4 py-3">Breed</th>
                                    <th class="px-4 py-3">Gender</th>
                                    <th class="px-4 py-3">Weight (kg)</th>
                                    <th class="px-4 py-3 rounded-r-lg text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($livestock as $animal)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                        <td class="px-4 py-3 font-bold text-[#0F6B3E]">{{ $animal->tag_number }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ $animal->species }}</td>
                                        <td class="px-4 py-3">{{ $animal->breed ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ $animal->gender }}</td>
                                        <td class="px-4 py-3">{{ $animal->weight_kg ?? '--' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <button class="text-[#0F6B3E] font-bold text-xs hover:bg-emerald-50 px-2 py-1 rounded">Log Health</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                            <div class="text-4xl mb-2 opacity-50">🐄</div>
                                            No livestock registered yet.
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

    <!-- Add Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="bg-[#0F6B3E] p-4 text-white flex justify-between items-center">
                <h3 class="font-bold">Register New Animal</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-white hover:text-emerald-200 font-bold">&times;</button>
            </div>
            <form action="{{ route('farmer.livestock.store') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tag Number *</label>
                        <input type="text" name="tag_number" required class="w-full border-slate-200 rounded-lg focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Species *</label>
                            <select name="species" required class="w-full border-slate-200 rounded-lg focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                                <option value="Cattle">Cattle</option>
                                <option value="Goat">Goat</option>
                                <option value="Sheep">Sheep</option>
                                <option value="Pig">Pig</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Gender *</label>
                            <select name="gender" required class="w-full border-slate-200 rounded-lg focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Breed</label>
                            <input type="text" name="breed" class="w-full border-slate-200 rounded-lg focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Weight (kg)</label>
                            <input type="number" step="0.1" name="weight_kg" class="w-full border-slate-200 rounded-lg focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="w-full py-3 bg-[#fbbf24] text-white rounded-xl font-bold shadow hover:bg-[#f59e0b] transition">
                        Register Animal
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
