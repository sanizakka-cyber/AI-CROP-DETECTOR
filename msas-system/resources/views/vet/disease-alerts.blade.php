<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Disease Alerts</h2>
    </x-slot>
    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($outbreaks as $alert)
                @php
                    $colors = ['high'=>['bg'=>'bg-red-50','border'=>'border-red-200','badge'=>'bg-red-600','text'=>'text-red-800'],
                               'medium'=>['bg'=>'bg-amber-50','border'=>'border-amber-200','badge'=>'bg-amber-500','text'=>'text-amber-800'],
                               'low'=>['bg'=>'bg-green-50','border'=>'border-green-200','badge'=>'bg-green-600','text'=>'text-green-800']];
                    $c = $colors[$alert['severity']];
                @endphp
                <div class="rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-white px-2.5 py-1 rounded-full {{ $c['badge'] }} uppercase">{{ $alert['severity'] }}</span>
                        <span class="text-2xl font-black {{ $c['text'] }}">{{ $alert['cases'] }}</span>
                    </div>
                    <h3 class="font-bold text-slate-800 mt-1 leading-snug">{{ $alert['disease'] }}</h3>
                    <p class="text-xs text-slate-500 mt-1 capitalize">{{ $alert['type'] === 'plant' ? 'Crop' : 'Livestock' }} &bull; Last 30 days</p>
                </div>
                @empty
                <div class="col-span-3 bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-400">
                    <p class="text-sm font-medium">No disease alerts in the last 30 days.</p>
                    <p class="text-xs mt-1">The system will show outbreak trends here as cases are diagnosed.</p>
                </div>
                @endforelse
            </div>

            @if($recentCases->isNotEmpty())
            <div class="bg-white shadow-sm rounded-2xl border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-700">Recent Cases (Last 10)</h3>
                </div>
                <table class="w-full text-sm text-slate-600 text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="px-5 py-3">Disease</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Reported</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentCases as $case)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $case->disease_name }}</td>
                            <td class="px-4 py-3 capitalize">{{ $case->type === 'plant' ? 'Crop' : 'Livestock' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full
                                    {{ $case->status === 'confirmed' ? 'bg-green-100 text-green-700' : ($case->status === 'needs_review' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                    {{ ucfirst(str_replace('_',' ',$case->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $case->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
