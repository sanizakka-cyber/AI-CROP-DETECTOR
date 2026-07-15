<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Agronomist Advisory</h2>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="bg-[#0F6B3E] text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-[#047857]">
                + Request Advisory
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
                                    <th class="px-4 py-3 rounded-l-lg">Date</th>
                                    <th class="px-4 py-3">Crop Type</th>
                                    <th class="px-4 py-3">Issue Description</th>
                                    <th class="px-4 py-3">Priority</th>
                                    <th class="px-4 py-3 rounded-r-lg">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $req->created_at->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ $req->crop_type }}</td>
                                        <td class="px-4 py-3">
                                            <p class="text-slate-600">{{ Str::limit($req->symptoms, 60) }}</p>
                                            @if($req->expert_response)
                                                <p class="text-emerald-700 text-xs mt-1 font-semibold">
                                                    Advisory: {{ Str::limit($req->expert_response, 80) }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $pColors = [
                                                    'low'      => 'bg-slate-100 text-slate-700',
                                                    'medium'   => 'bg-blue-100 text-blue-700',
                                                    'high'     => 'bg-amber-100 text-amber-700',
                                                    'critical' => 'bg-red-100 text-red-700',
                                                ];
                                            @endphp
                                            <span class="px-2 py-1 rounded font-bold text-[10px] uppercase {{ $pColors[$req->priority] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $req->priority }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($req->status === 'awaiting_payment')
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-700">Awaiting Payment</span>
                                            @elseif($req->status === 'resolved')
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Resolved</span>
                                            @elseif($req->status === 'pending')
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">Pending Review</span>
                                            @else
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">In Progress</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-12 text-center text-slate-400">
                                            <div class="text-5xl mb-3">🌱</div>
                                            <p class="font-semibold">No advisory requests yet.</p>
                                            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                                                    class="mt-3 text-[#0F6B3E] font-bold text-sm hover:underline">
                                                Submit your first request →
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

    {{-- ── Request Advisory Modal ───────────────────────────────────────── --}}
    <div id="addModal" class="hidden fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="bg-[#0F6B3E] p-4 text-white flex justify-between items-center">
                <h3 class="font-bold text-lg">Request Agronomist Advisory</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="text-white hover:text-emerald-200 font-bold text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('farmer.agro.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Crop Type *</label>
                        <input type="text" name="crop_type" required placeholder="e.g., Maize, Tomato, Yam"
                               class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Priority *</label>
                        <select name="priority" required
                                class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]">
                            <option value="low">Low (Routine)</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High (Urgent)</option>
                            <option value="critical">Critical (Emergency)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Consultation Channel *</label>
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        <label class="cursor-pointer">
                            <input type="radio" name="channel" value="in_app" class="peer sr-only" required checked onchange="updateAgroFee(1500)">
                            <div class="border-2 border-slate-200 rounded-lg p-3 text-center peer-checked:border-[#1FA84A] peer-checked:bg-emerald-50 transition">
                                <div class="text-lg mb-0.5">💬</div>
                                <div class="text-xs font-bold">In-App</div>
                                <div class="text-xs text-emerald-600 font-bold">₦1,500</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="channel" value="whatsapp" class="peer sr-only" onchange="updateAgroFee(2500)">
                            <div class="border-2 border-slate-200 rounded-lg p-3 text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition">
                                <div class="text-lg mb-0.5">📱</div>
                                <div class="text-xs font-bold">WhatsApp</div>
                                <div class="text-xs text-emerald-600 font-bold">₦2,500</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="channel" value="phone_call" class="peer sr-only" onchange="updateAgroFee(3500)">
                            <div class="border-2 border-slate-200 rounded-lg p-3 text-center peer-checked:border-amber-500 peer-checked:bg-amber-50 transition">
                                <div class="text-lg mb-0.5">📞</div>
                                <div class="text-xs font-bold">Phone Call</div>
                                <div class="text-xs text-emerald-600 font-bold">₦3,500</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Issue / Symptoms *</label>
                    <textarea name="symptoms" required rows="4"
                              placeholder="Describe the crop problem — symptoms, location on plant, when it started, how many plants affected..."
                              class="w-full border-slate-200 rounded-lg text-sm focus:ring-[#1FA84A] focus:border-[#1FA84A]"></textarea>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 flex justify-between items-center">
                    <span class="text-sm text-slate-600">Consultation Fee</span>
                    <span id="agroFeeDisplay" class="text-base font-extrabold text-[#0F6B3E]">₦1,500</span>
                </div>
                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-3 bg-[#0F6B3E] text-white rounded-xl font-bold shadow hover:bg-[#047857] transition text-sm">
                        Proceed to Payment →
                    </button>
                    <p class="text-xs text-center text-slate-400 mt-2">Payment via Paystack. Request is only sent after payment is confirmed.</p>
                </div>
            </form>
            <script>
            function updateAgroFee(amount) {
                document.getElementById('agroFeeDisplay').textContent = '₦' + amount.toLocaleString();
            }
            </script>
        </div>
    </div>

    @if($errors->any())
    <script>document.getElementById('addModal').classList.remove('hidden');</script>
    @endif
</x-app-layout>
