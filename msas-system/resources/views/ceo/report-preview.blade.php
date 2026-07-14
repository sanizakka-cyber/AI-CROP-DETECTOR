<x-app-layout>
    <x-slot name="header">{{ $data['title'] ?? 'Report Preview' }}</x-slot>

    <div class="space-y-6">

        {{-- Header with Print/Export --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-wrap items-center justify-between gap-4 print:hidden">
            <div>
                <h2 class="text-xl font-bold text-slate-800">{{ $data['title'] ?? 'Report' }}</h2>
                <p class="text-sm text-slate-500 mt-0.5">Generated on {{ now()->format('F d, Y \a\t h:i A') }} &mdash; MSAS FarmAI Platform</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('ceo.reports') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &larr; Back to Reports
                </a>
                <button onclick="window.print()" class="px-4 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">
                    &#9112; Print Report
                </button>
                <button onclick="alert('Export coming soon!')" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition">
                    &#8659; Export CSV
                </button>
            </div>
        </div>

        {{-- Summary Stats (Financial only) --}}
        @if($type === 'financial' && isset($data['income']))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 print:grid-cols-3">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#1FA84A]">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Income</p>
                <p class="text-3xl font-extrabold text-[#1FA84A] mt-1">&#8358;{{ number_format($data['income']) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-red-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Expenses</p>
                <p class="text-3xl font-extrabold text-red-600 mt-1">&#8358;{{ number_format($data['expenses']) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 {{ ($data['income'] - $data['expenses']) >= 0 ? 'border-l-[#0F6B3E]' : 'border-l-red-700' }}">
                <p class="text-xs font-bold text-slate-500 uppercase">Net Profit</p>
                <p class="text-3xl font-extrabold {{ ($data['income'] - $data['expenses']) >= 0 ? 'text-[#0F6B3E]' : 'text-red-700' }} mt-1">
                    &#8358;{{ number_format(abs($data['income'] - $data['expenses'])) }}
                </p>
            </div>
        </div>
        @endif

        {{-- Geographic Summary --}}
        @if($type === 'geographic')
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Distribution by State</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($data['records'] as $row)
                <div class="bg-emerald-50 rounded-xl p-4 text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase">{{ $row->state ?? 'Unknown' }}</p>
                    <p class="text-2xl font-extrabold text-[#0F6B3E]">{{ $row->count }}</p>
                    <p class="text-xs text-slate-400">users</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Data Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center justify-between border-b pb-3 mb-4">
                <h3 class="font-bold text-slate-800 text-lg">{{ $data['title'] }}</h3>
                <span class="text-sm font-semibold text-slate-500">
                    {{ $data['records']->count() }} records
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase bg-slate-50">
                            @foreach(($data['columns'] ?? []) as $col)
                            <th class="px-4 py-3">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($data['records'] as $record)
                        <tr class="hover:bg-slate-50">
                            @foreach(($data['row_keys'] ?? []) as $key)
                            <td class="px-4 py-3 text-slate-700">
                                @php
                                    // Support dot notation for relations (e.g. user.name)
                                    if (str_contains($key, '.')) {
                                        [$rel, $attr] = explode('.', $key, 2);
                                        $val = optional($record->$rel)->$attr;
                                    } else {
                                        $val = $record->$key ?? null;
                                    }
                                    // Format booleans
                                    if (is_bool($val)) $val = $val ? 'Yes' : 'No';
                                    // Format dates
                                    if ($val instanceof \Carbon\Carbon || $val instanceof \Illuminate\Support\Carbon) {
                                        $val = $val->format('M d, Y');
                                    }
                                @endphp
                                @if($key === 'amount')
                                    <span class="font-semibold">&#8358;{{ number_format((float)$val) }}</span>
                                @elseif($key === 'type' && in_array(strtolower($val ?? ''), ['income','expense']))
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ strtolower($val) === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $val }}
                                    </span>
                                @elseif($key === 'status')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ $val === 'resolved' ? 'bg-emerald-100 text-emerald-800' : ($val === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ ucfirst($val ?? 'N/A') }}
                                    </span>
                                @elseif($key === 'is_active' || $key === 'is_verified')
                                    @if($val === 'Yes' || $val === true || $val == 1)
                                        <span class="text-emerald-600 font-bold text-xs">&#10003; Yes</span>
                                    @else
                                        <span class="text-red-500 font-bold text-xs">&#10005; No</span>
                                    @endif
                                @else
                                    {{ $val ?? 'N/A' }}
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ count($data['columns'] ?? []) }}" class="px-4 py-8 text-center text-slate-500">
                                No records found for this report.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            body { background: white !important; }
            .print\:hidden { display: none !important; }
            aside, header { display: none !important; }
        }
    </style>

</x-app-layout>
