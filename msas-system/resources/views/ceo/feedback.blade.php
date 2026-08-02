<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800">User Feedback</h2>
    </x-slot>
    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
            <div class="bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl font-semibold text-sm">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-4 gap-4">
                @foreach(['total'=>['label'=>'Total','color'=>'slate'], 'new'=>['label'=>'New','color'=>'sky'], 'reviewed'=>['label'=>'Reviewed','color'=>'amber'], 'resolved'=>['label'=>'Resolved','color'=>'emerald']] as $key=>$cfg)
                <div class="bg-white rounded-2xl border border-slate-100 p-5 text-center shadow-sm">
                    <div class="text-3xl font-black text-{{ $cfg['color'] }}-600">{{ $counts[$key] }}</div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mt-1">{{ $cfg['label'] }}</div>
                </div>
                @endforeach
            </div>

            <div class="flex gap-3">
                <form method="GET" class="flex gap-2">
                    <select name="status" onchange="this.form.submit()" class="border border-slate-200 rounded-xl px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="new" {{ request('status')=='new'?'selected':'' }}>New</option>
                        <option value="reviewed" {{ request('status')=='reviewed'?'selected':'' }}>Reviewed</option>
                        <option value="resolved" {{ request('status')=='resolved'?'selected':'' }}>Resolved</option>
                    </select>
                    <select name="type" onchange="this.form.submit()" class="border border-slate-200 rounded-xl px-3 py-2 text-sm">
                        <option value="">All Types</option>
                        <option value="general" {{ request('type')=='general'?'selected':'' }}>General</option>
                        <option value="bug" {{ request('type')=='bug'?'selected':'' }}>Bug</option>
                        <option value="feature" {{ request('type')=='feature'?'selected':'' }}>Feature</option>
                        <option value="praise" {{ request('type')=='praise'?'selected':'' }}>Praise</option>
                    </select>
                </form>
            </div>

            <div class="space-y-4">
                @forelse($feedbacks as $item)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 flex-wrap mb-2">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full
                                    {{ $item->type==='bug' ? 'bg-red-100 text-red-700' :
                                      ($item->type==='feature' ? 'bg-sky-100 text-sky-700' :
                                      ($item->type==='praise' ? 'bg-emerald-100 text-emerald-700' :
                                       'bg-slate-100 text-slate-600')) }}">
                                    {{ $item->typeLabel() }}
                                </span>
                                @if($item->rating)
                                <span class="inline-flex gap-0.5">@for($i=0;$i<$item->rating;$i++)<svg width="13" height="13" fill="#f59e0b" stroke="#f59e0b" stroke-width="1" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>@endfor</span>
                                @endif
                                <span class="text-xs text-slate-400">{{ $item->created_at->diffForHumans() }}</span>
                                @if($item->user)
                                <span class="text-xs text-slate-500 font-medium">{{ $item->user->first_name }} {{ $item->user->last_name }} ({{ $item->user->role }})</span>
                                @else
                                <span class="text-xs text-slate-400">Anonymous</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-700 leading-relaxed">{{ $item->message }}</p>
                            @if($item->page)
                            <div class="text-xs text-slate-400 mt-1 truncate max-w-md">Page: {{ $item->page }}</div>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('ceo.feedback.update', $item) }}" class="flex flex-col gap-2 min-w-[180px]">
                            @csrf @method('PATCH')
                            <select name="status" class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs">
                                <option value="new" {{ $item->status==='new'?'selected':'' }}>New</option>
                                <option value="reviewed" {{ $item->status==='reviewed'?'selected':'' }}>Reviewed</option>
                                <option value="resolved" {{ $item->status==='resolved'?'selected':'' }}>Resolved</option>
                            </select>
                            <textarea name="admin_notes" rows="2" placeholder="Notes..." class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs resize-none">{{ $item->admin_notes }}</textarea>
                            <button type="submit" class="bg-slate-800 text-white text-xs font-bold py-1.5 rounded-lg hover:bg-slate-700">Save</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="bg-white rounded-2xl border border-slate-100 p-10 text-center text-slate-400">No feedback yet.</div>
                @endforelse
            </div>
            @if($feedbacks->hasPages())<div class="mt-4">{{ $feedbacks->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>