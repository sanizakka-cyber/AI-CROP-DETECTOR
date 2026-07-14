<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Veterinary Consultations') }}
            </h2>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-indigo-700">
                + Request Consultation
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
                                    <th class="px-4 py-3 rounded-l-lg">Date</th>
                                    <th class="px-4 py-3">Animal Type</th>
                                    <th class="px-4 py-3">Symptoms</th>
                                    <th class="px-4 py-3">Priority</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 rounded-r-lg text-right">Diagnosis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($consultations as $consult)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                        <td class="px-4 py-3">{{ $consult->created_at->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ $consult->animal_type }}</td>
                                        <td class="px-4 py-3">{{ Str::limit($consult->symptoms, 40) }}</td>
                                        <td class="px-4 py-3">
                                            @php
                                                $colors = [
                                                    'low' => 'bg-slate-100 text-slate-700',
                                                    'medium' => 'bg-blue-100 text-blue-700',
                                                    'high' => 'bg-amber-100 text-amber-700',
                                                    'critical' => 'bg-red-100 text-red-700',
                                                ];
                                            @endphp
                                            <span class="px-2 py-1 rounded font-bold text-[10px] uppercase {{ $colors[$consult->priority] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $consult->priority }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($consult->status === 'pending')
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">Pending</span>
                                            @elseif($consult->status === 'resolved')
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Resolved</span>
                                            @else
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">In Progress</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if($consult->expert_response)
                                                <a href="{{ route('farmer.vet.view', $consult) }}" class="inline-block text-white bg-indigo-600 font-bold text-[10px] uppercase hover:bg-indigo-700 px-3 py-1.5 rounded-lg shadow-sm transition">View Report</a>
                                            @else
                                                <span class="text-xs text-slate-400 italic">Awaiting Vet</span>
                                            @endif
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                            <div class="text-4xl mb-2 opacity-50">🩺</div>
                                            No consultations requested yet.
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
            <div class="bg-indigo-600 p-4 text-white flex justify-between items-center">
                <h3 class="font-bold">Request Vet Consultation</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-white hover:text-indigo-200 font-bold">&times;</button>
            </div>
            <form action="{{ route('farmer.vet.store') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Animal Type *</label>
                            <input type="text" name="animal_type" required placeholder="E.g., Cattle, Layer Hen" class="w-full border-slate-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Priority *</label>
                            <select name="priority" required class="w-full border-slate-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="low">Low (Routine)</option>
                                <option value="medium">Medium</option>
                                <option value="high">High (Urgent)</option>
                                <option value="critical">Critical (Emergency)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Symptoms / Issue Description *</label>
                        <textarea name="symptoms" required rows="4" placeholder="Describe the symptoms your animal is experiencing..." class="w-full border-slate-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow hover:bg-indigo-700 transition">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
