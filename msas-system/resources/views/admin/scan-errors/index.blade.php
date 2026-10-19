<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Scan errors</h2>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 flex flex-wrap gap-3 items-end text-sm">
                <label class="flex flex-col">Code
                    <select name="code" class="rounded-lg border-slate-300">
                        <option value="">All codes</option>
                        @foreach($codes as $c)<option value="{{ $c }}" @selected(request('code') === $c)>{{ $c }}</option>@endforeach
                    </select>
                </label>
                <label class="flex flex-col">Channel
                    <select name="channel" class="rounded-lg border-slate-300">
                        <option value="">Web and app</option>
                        <option value="web" @selected(request('channel') === 'web')>Web</option>
                        <option value="api" @selected(request('channel') === 'api')>App (API)</option>
                    </select>
                </label>
                <label class="flex flex-col">Reference
                    <input name="reference" value="{{ request('reference') }}" placeholder="SCN-7F3K2" class="rounded-lg border-slate-300">
                </label>
                <button class="bg-slate-800 text-white font-bold px-4 py-2 rounded-lg">Filter</button>
                <a href="{{ route('admin.scan-errors.index') }}" class="text-slate-500 underline">Clear</a>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-4 text-sm md:col-span-2">
                    <h3 class="font-bold mb-2">Failures per hour, last 24 hours{{ request('code') ? ' (' . request('code') . ')' : '' }}</h3>
                    @php $max = max(1, max($perHour)); @endphp
                    <div class="flex items-end gap-0.5 h-24" data-per-hour="1">
                        @foreach($perHour as $hour => $n)
                            <div class="flex-1 bg-red-400 rounded-t" style="height: {{ (int) round($n / $max * 100) }}%" title="{{ $hour }}: {{ $n }}"></div>
                        @endforeach
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-400 mt-1"><span>{{ array_key_first($perHour) }}</span><span>{{ array_key_last($perHour) }}</span></div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 text-sm">
                    <h3 class="font-bold mb-2">Last 24 hours by code</h3>
                    @forelse($totals as $code => $n)
                        <div class="flex justify-between"><a class="text-emerald-700" href="{{ route('admin.scan-errors.index', ['code' => $code]) }}">{{ $code }}</a><strong>{{ $n }}</strong></div>
                    @empty
                        <p class="text-slate-500">No failed scans.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr><th class="px-4 py-3">Time</th><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">User</th><th class="px-4 py-3">Where</th><th class="px-4 py-3">Details</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($scanErrors as $e)
                        <tr class="align-top" data-scan-error="{{ $e->reference }}">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $e->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-3 font-mono font-bold text-slate-800">{{ $e->reference }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $e->code }}</td>
                            <td class="px-4 py-3 text-xs">{{ $e->user ? trim($e->user->first_name . ' ' . $e->user->last_name) : 'Signed out' }}<br><span class="text-slate-400">{{ $e->user?->email }} {{ $e->user_role ? '· ' . $e->user_role : '' }}</span></td>
                            <td class="px-4 py-3 text-xs">{{ $e->channel === 'api' ? 'App' : 'Web' }} · {{ $e->scan_type ?? '—' }}<br><span class="text-slate-400">{{ $services[$e->service] ?? $e->service }}</span></td>
                            <td class="px-4 py-3 text-xs max-w-md">
                                <details>
                                    <summary class="cursor-pointer text-emerald-700">HTTP {{ $e->upstream_status ?? $e->http_status }}</summary>
                                    <pre class="whitespace-pre-wrap break-words bg-slate-50 rounded p-2 mt-1 text-[11px]">{{ $e->detail }}</pre>
                                    @if($e->context)<pre class="whitespace-pre-wrap break-words text-[11px] text-slate-400">{{ json_encode($e->context, JSON_UNESCAPED_SLASHES) }}</pre>@endif
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No failed scans match.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $scanErrors->links() }}
        </div>
    </div>
</x-app-layout>
