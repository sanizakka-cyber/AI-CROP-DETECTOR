<x-app-layout>
    <x-slot name="header">User Overview</x-slot>

    <div class="space-y-6">

        <div class="bg-gradient-to-r from-slate-800 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Operations</p>
                <h1 class="text-2xl font-extrabold">User Overview</h1>
            </div>
            <a href="{{ route('operations.tasks') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&larr; Tasks</a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-[#0F6B3E]">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Users</p>
                <p class="text-3xl font-extrabold text-[#0F6B3E] mt-1">{{ number_format($totalUsers) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-emerald-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Active Users</p>
                <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ number_format($activeUsers) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-blue-500">
                <p class="text-xs font-bold text-slate-500 uppercase">New This Month</p>
                <p class="text-3xl font-extrabold text-blue-600 mt-1">{{ number_format($newThisMonth) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 mb-4 border-b pb-3">Users by Role</h3>
                @foreach($byRole as $r)
                @php $pct = $totalUsers > 0 ? round(($r->cnt/$totalUsers)*100) : 0; @endphp
                <div class="mb-3">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="font-semibold text-slate-700 capitalize">{{ str_replace(['-','_'],' ',$r->role) }}</span>
                        <span class="text-slate-500">{{ $r->cnt }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="h-2 bg-[#0F6B3E] rounded-full" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">Recent Users</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50"><tr class="text-xs font-bold text-slate-500 uppercase">
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Role</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($recentUsers as $u)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2.5 text-xs font-semibold text-slate-800">{{ $u->name ?: $u->email }}</td>
                                <td class="px-4 py-2.5 text-xs text-slate-600">{{ $u->roleLabel }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $u->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' }}">
                                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-slate-100">{{ $recentUsers->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
