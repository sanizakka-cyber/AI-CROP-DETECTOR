<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Poultry & Egg Production') }}
            </h2>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-[#b45309] text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-[#92400e]">
                + Add New Flock
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
                                    <th class="px-4 py-3 rounded-l-lg">Batch Number</th>
                                    <th class="px-4 py-3">Bird Type</th>
                                    <th class="px-4 py-3">Initial Quantity</th>
                                    <th class="px-4 py-3">Current Count</th>
                                    <th class="px-4 py-3">Date Acquired</th>
                                    <th class="px-4 py-3 rounded-r-lg text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($flocks as $flock)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                        <td class="px-4 py-3 font-bold text-[#b45309]">{{ $flock->batch_number }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ $flock->bird_type }}</td>
                                        <td class="px-4 py-3">{{ $flock->quantity }}</td>
                                        <td class="px-4 py-3 font-bold">{{ $flock->quantity }}</td> <!-- To be updated dynamically with mortality -->
                                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($flock->date_acquired)->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            @if($flock->bird_type === 'Layers')
                                                <button class="text-amber-600 font-bold text-[10px] uppercase hover:bg-amber-50 px-2 py-1 rounded">Log Eggs</button>
                                            @endif
                                            <button class="text-red-600 font-bold text-[10px] uppercase hover:bg-red-50 px-2 py-1 rounded">Log Mortality</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                            <div class="text-4xl mb-2 opacity-50">🐔</div>
                                            No poultry flocks added yet.
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
            <div class="bg-[#b45309] p-4 text-white flex justify-between items-center">
                <h3 class="font-bold">Add New Flock</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-white hover:text-amber-200 font-bold">&times;</button>
            </div>
            <form action="{{ route('farmer.poultry.store') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Batch Number *</label>
                        <input type="text" name="batch_number" required placeholder="E.g., BATCH-2026-01" class="w-full border-slate-200 rounded-lg focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bird Type *</label>
                            <select name="bird_type" required class="w-full border-slate-200 rounded-lg focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                                <option value="Broilers">Broilers</option>
                                <option value="Layers">Layers</option>
                                <option value="Cockerels">Cockerels</option>
                                <option value="Turkeys">Turkeys</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Quantity *</label>
                            <input type="number" name="quantity" min="1" required class="w-full border-slate-200 rounded-lg focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Date Acquired *</label>
                        <input type="date" name="date_acquired" required class="w-full border-slate-200 rounded-lg focus:ring-[#fbbf24] focus:border-[#fbbf24]">
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="w-full py-3 bg-[#0F6B3E] text-white rounded-xl font-bold shadow hover:bg-[#047857] transition">
                        Save Flock
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
