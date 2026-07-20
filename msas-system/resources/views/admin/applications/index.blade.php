<x-app-layout>
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800">Applications</h1>
                <p class="text-slate-500 text-sm mt-0.5">Review and manage registration applications</p>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">
            {{ session('success') }}
        </div>
        @endif

        {{-- Status tabs --}}
        <div class="flex gap-2 mb-6 border-b border-slate-200">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.applications.index', ['status' => $key]) }}"
               class="px-4 py-2.5 text-sm font-semibold border-b-2 transition
                      {{ $status === $key
                          ? ($key === 'pending' ? 'border-amber-500 text-amber-700' : ($key === 'approved' ? 'border-green-600 text-green-700' : 'border-red-500 text-red-700'))
                          : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                {{ $label }}
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs
                             {{ $key === 'pending' ? 'bg-amber-100 text-amber-700' : ($key === 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                    {{ $counts[$key] }}
                </span>
            </a>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            @if($applications->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="font-semibold">No {{ $status }} applications</p>
            </div>
            @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="text-left px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wider">Applicant</th>
                        <th class="text-left px-4 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wider">Role</th>
                        <th class="text-left px-4 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wider">Location</th>
                        <th class="text-left px-4 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wider">Docs</th>
                        <th class="text-left px-4 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wider">Applied</th>
                        <th class="text-right px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($applications as $app)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-800">{{ $app->name }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $app->email ?? $app->phone }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-block px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold">
                                {{ $app->roleLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-500 text-xs">
                            {{ implode(', ', array_filter([$app->state, $app->country])) ?: '—' }}
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold
                                         {{ $app->documents_count ?? $app->documents()->count() > 0 ? 'text-green-600' : 'text-slate-400' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ $app->documents()->count() }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-xs text-slate-500">
                            {{ $app->created_at->format('d M Y') }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.applications.show', $app) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                                Review
                            </a>
                            @if($status === 'pending')
                            <form method="POST" action="{{ route('admin.applications.approve', $app) }}" class="inline"
                                  onsubmit="return confirm('Approve {{ addslashes($app->name) }}?')">
                                @csrf
                                <button type="submit"
                                        class="ml-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-green-100 text-green-700 hover:bg-green-200 transition">
                                    Approve
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($applications->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">
                {{ $applications->appends(['status' => $status])->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>
</x-app-layout>
