<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-xl text-gray-800">User Management</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $users->total() }} total users</p>
            </div>
            <a href="{{ route('ceo.dashboard') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                ← Dashboard
            </a>
        </div>
    </x-slot>

    <div class="space-y-5">

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-semibold">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm font-semibold">⚠ {{ session('error') }}</div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name, email, phone…"
                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Role</label>
                <select name="role" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    <option value="">All Roles</option>
                    @foreach($roles as $r)
                    <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ ucwords(str_replace('-',' ',$r)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    <option value="">All</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <button class="px-5 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">Filter</button>
            @if(request()->hasAny(['search','role','status']))
            <a href="{{ route('ceo.users') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr class="text-xs font-bold text-slate-500 uppercase text-left">
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3">Contact</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Joined</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($users as $u)
                        <tr class="hover:bg-slate-50/60 transition group">
                            {{-- User --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $u->avatarUrl }}" alt="" class="w-9 h-9 rounded-xl object-cover flex-shrink-0">
                                    <div>
                                        <div class="font-semibold text-slate-800 text-sm leading-tight">{{ $u->name ?: '—' }}</div>
                                        <div class="text-xs text-slate-400 mt-0.5">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            {{-- Role --}}
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 capitalize">
                                    {{ str_replace('-',' ',$u->role) }}
                                </span>
                            </td>
                            {{-- Contact --}}
                            <td class="px-5 py-3 text-xs text-slate-500">
                                <div>{{ $u->phone ?: '—' }}</div>
                                <div class="text-slate-400">{{ $u->state ?? '' }}{{ $u->state && $u->lga ? ', ' : '' }}{{ $u->lga ?? '' }}</div>
                            </td>
                            {{-- Status --}}
                            <td class="px-5 py-3">
                                <div class="flex flex-col gap-1">
                                    @if($u->is_active)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>Active</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-600"><span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>Suspended</span>
                                    @endif
                                    @if($u->is_verified)
                                        <span class="text-[10px] text-blue-600 font-semibold">✓ Verified</span>
                                    @endif
                                </div>
                            </td>
                            {{-- Joined --}}
                            <td class="px-5 py-3 text-xs text-slate-400">
                                {{ $u->created_at->format('d M Y') }}
                            </td>
                            {{-- Actions --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- View --}}
                                    <a href="{{ route('ceo.users.show', $u) }}"
                                       class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition"
                                       title="View Profile">View</a>

                                    {{-- Edit --}}
                                    <a href="{{ route('ceo.users.edit', $u) }}"
                                       class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-blue-100 text-blue-700 hover:bg-blue-200 transition"
                                       title="Edit User">Edit</a>

                                    {{-- Suspend / Activate --}}
                                    @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('ceo.users.toggle', $u) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                            onclick="return confirm('{{ $u->is_active ? 'Suspend' : 'Activate' }} {{ addslashes($u->first_name) }}?')"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition {{ $u->is_active ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }}"
                                            title="{{ $u->is_active ? 'Suspend' : 'Activate' }}">
                                            {{ $u->is_active ? 'Suspend' : 'Activate' }}
                                        </button>
                                    </form>
                                    @endif

                                    {{-- Impersonate --}}
                                    @if($u->id !== auth()->id() && !session('impersonate.original_id'))
                                    <a href="{{ route('impersonate.start', $u) }}"
                                       class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-violet-100 text-violet-700 hover:bg-violet-200 transition"
                                       onclick="return confirm('Login as {{ addslashes($u->first_name . ' ' . $u->last_name) }} ({{ $u->role }})?')"
                                       title="Login As">Login As</a>
                                    @endif

                                    {{-- Delete --}}
                                    @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('ceo.users.delete', $u) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('PERMANENTLY delete {{ addslashes($u->name) }}? This cannot be undone.')"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-red-100 text-red-600 hover:bg-red-200 transition"
                                            title="Delete User">Delete</button>
                                    </form>
                                    @else
                                    <span class="text-xs text-slate-300 italic px-2">You</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center text-slate-400 text-sm">No users found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">{{ $users->links() }}</div>
            @endif
        </div>

    </div>
</x-app-layout>
