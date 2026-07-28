<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Pilot Invite Codes</h2>
                <p class="text-sm text-slate-500 mt-0.5">Generate codes that auto-start a trial and flag the user as a pilot farmer</p>
            </div>
            <a href="{{ route('ceo.pilot') }}" class="text-sm font-bold text-emerald-600 hover:underline">← Pilot Dashboard</a>
        </div>
    </x-slot>
    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
            <div class="bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl font-semibold text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h3 class="font-bold text-slate-800 mb-4">Create New Invite Code</h3>
                <form method="POST" action="{{ route('ceo.invite-codes.store') }}" class="grid grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Plan *</label>
                        <select name="plan" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
                            @foreach($plans as $key=>$plan)
                            <option value="{{ $key }}">{{ $plan['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Max Uses *</label>
                        <input type="number" name="max_uses" value="1" min="1" max="500" required
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Expires At</label>
                        <input type="date" name="expires_at" min="{{ now()->addDay()->format('Y-m-d') }}"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
                    </div>
                    <div class="col-span-3">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow">
                            Generate Code
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100"><h3 class="text-sm font-bold text-slate-700">All Invite Codes</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="px-5 py-3 text-left">Code</th>
                                <th class="px-4 py-3 text-left">Plan</th>
                                <th class="px-4 py-3 text-center">Uses</th>
                                <th class="px-4 py-3 text-left">Expires</th>
                                <th class="px-4 py-3 text-left">Created By</th>
                                <th class="px-4 py-3 text-left">Created</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($codes as $code)
                            @php $valid = $code->isValid(); @endphp
                            <tr class="hover:bg-slate-50 {{ !$valid ? 'opacity-50' : '' }}">
                                <td class="px-5 py-3">
                                    <span class="font-mono font-bold text-slate-800 text-base tracking-wider">{{ $code->code }}</span>
                                    <button onclick="navigator.clipboard.writeText('{{ $code->code }}')" title="Copy"
                                        class="ml-2 text-xs text-emerald-600 hover:underline">Copy</button>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ ucfirst($code->plan) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="{{ $code->used_count >= $code->max_uses ? 'text-red-500 font-bold' : 'text-slate-700' }}">
                                        {{ $code->used_count }} / {{ $code->max_uses }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ $code->expires_at?->format('M d, Y') ?? '∞ Never' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $code->creator->first_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-400 text-xs">{{ $code->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('ceo.invite-codes.delete', $code) }}"
                                        onsubmit="return confirm('Delete this invite code?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:underline font-bold">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No invite codes yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($codes->hasPages())<div class="px-5 py-4 border-t border-slate-100">{{ $codes->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>