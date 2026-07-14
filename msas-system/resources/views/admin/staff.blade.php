<x-app-layout>
    <x-slot name="header">Staff Directory</x-slot>

    <div class="space-y-6">

        <div class="bg-gradient-to-r from-[#0B2447] to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Administration</p>
                <h1 class="text-2xl font-extrabold">Staff Directory</h1>
                <p class="text-emerald-100 text-sm mt-1">{{ $staff->total() }} staff members registered.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.users') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&larr; All Users</a>
                <a href="{{ route('admin.settings') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">Settings</a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">&#9888; {{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="px-5 py-3">Staff Member</th>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3">Contact</th>
                            <th class="px-5 py-3">State</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Joined</th>
                            <th class="px-5 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($staff as $member)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $member->avatarUrl }}" class="w-9 h-9 rounded-full object-cover" alt="">
                                    <div>
                                        <div class="font-semibold text-slate-800 text-xs">{{ $member->name ?: $member->email }}</div>
                                        <div class="text-slate-400 text-xs">{{ $member->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold capitalize">
                                    {{ $member->roleLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $member->phone ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $member->state ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $member->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' }}">
                                    {{ $member->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $member->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-3">
                                <form method="POST" action="{{ route('admin.users.toggle', $member->id) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ $member->is_active ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} transition">
                                        {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400 text-sm">No staff members found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-slate-100">{{ $staff->links() }}</div>
        </div>
    </div>
</x-app-layout>
