<x-app-layout>
    <div class="max-w-4xl mx-auto">
        {{-- Back --}}
        <a href="{{ route('admin.applications.index') }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-700 mb-6 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Applications
        </a>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main panel --}}
            <div class="lg:col-span-2 space-y-5">
                {{-- Applicant info --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h2 class="text-lg font-extrabold text-slate-800 mb-4">Applicant Details</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Full Name</p>
                            <p class="font-semibold text-slate-700">{{ $user->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Role Applied For</p>
                            <span class="inline-block px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold">
                                {{ $user->roleLabel }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Email</p>
                            <p class="text-slate-700">{{ $user->email ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Phone</p>
                            <p class="text-slate-700">{{ $user->phone ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-0.5">State / Country</p>
                            <p class="text-slate-700">{{ implode(', ', array_filter([$user->state, $user->country])) ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Applied</p>
                            <p class="text-slate-700">{{ $user->created_at->format('d M Y, g:i A') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Documents --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h2 class="text-lg font-extrabold text-slate-800 mb-4">Uploaded Documents</h2>
                    @if($documents->isEmpty())
                    <div class="text-center py-8 text-slate-400">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm font-medium">No documents uploaded</p>
                        <p class="text-xs mt-1">The applicant did not upload any supporting documents.</p>
                    </div>
                    @else
                    <div class="space-y-3">
                        @foreach($documents as $doc)
                        <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center
                                            {{ str_contains($doc->mime_type, 'pdf') ? 'bg-red-100' : 'bg-blue-100' }}">
                                    @if(str_contains($doc->mime_type, 'pdf'))
                                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                    @else
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                    </svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">{{ $doc->document_label }}</p>
                                    <p class="text-xs text-slate-400">{{ $doc->original_name }} · {{ $doc->file_size_human }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.applications.document', $doc) }}"
                               target="_blank"
                               class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white border border-slate-300 text-slate-600 hover:bg-slate-100 transition">
                                View
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar: status + actions --}}
            <div class="space-y-5">
                {{-- Current status --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">Application Status</h3>
                    @php $s = $user->application_status ?? 'approved'; @endphp
                    <div class="flex items-center gap-2 mb-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold
                                     {{ $s === 'pending' ? 'bg-amber-100 text-amber-700' : ($s === 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                            <span class="w-1.5 h-1.5 rounded-full
                                         {{ $s === 'pending' ? 'bg-amber-500' : ($s === 'approved' ? 'bg-green-500' : 'bg-red-500') }}"></span>
                            {{ ucfirst($s) }}
                        </span>
                    </div>
                    @if($user->reviewed_at)
                    <p class="text-xs text-slate-400">
                        Reviewed {{ $user->reviewed_at->format('d M Y') }}
                        @if($user->reviewed_by && $reviewer = \App\Models\User::find($user->reviewed_by))
                            by {{ $reviewer->first_name }}
                        @endif
                    </p>
                    @endif
                    @if($user->rejection_reason)
                    <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-xs font-semibold text-red-700 mb-0.5">Rejection Reason</p>
                        <p class="text-xs text-red-600">{{ $user->rejection_reason }}</p>
                    </div>
                    @endif
                </div>

                {{-- Approve action --}}
                @if($s === 'pending' || $s === 'rejected')
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">Approve Application</h3>
                    <p class="text-xs text-slate-500 mb-4">This will activate the user's account and send them an approval email.</p>
                    <form method="POST" action="{{ route('admin.applications.approve', $user) }}"
                          onsubmit="return confirm('Approve {{ addslashes($user->name) }}\'s application?')">
                        @csrf
                        <button type="submit"
                                class="w-full py-2.5 px-4 rounded-xl text-sm font-bold bg-green-600 text-white hover:bg-green-700 transition">
                            Approve &amp; Activate Account
                        </button>
                    </form>
                </div>
                @endif

                {{-- Reject action --}}
                @if($s === 'pending' || $s === 'approved')
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">Reject Application</h3>
                    <form method="POST" action="{{ route('admin.applications.reject', $user) }}"
                          onsubmit="return confirm('Reject this application? The applicant will be notified.')">
                        @csrf
                        <div class="mb-3">
                            <textarea name="reason" rows="3" required
                                      placeholder="Reason for rejection (sent to applicant)…"
                                      class="w-full text-xs border border-slate-300 rounded-xl p-3 resize-none focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
                        </div>
                        <button type="submit"
                                class="w-full py-2.5 px-4 rounded-xl text-sm font-bold bg-red-600 text-white hover:bg-red-700 transition">
                            Reject Application
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
