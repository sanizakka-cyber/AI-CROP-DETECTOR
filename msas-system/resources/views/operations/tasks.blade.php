<x-app-layout>
    <x-slot name="header">Task Management</x-slot>

    <div class="space-y-6">

        <div class="bg-gradient-to-r from-slate-800 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Operations</p>
                <h1 class="text-2xl font-extrabold">Task Management</h1>
                <p class="text-emerald-100 text-sm mt-1">Plan, assign, and track operational tasks.</p>
            </div>
            <a href="{{ route('operations.users') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; User Overview</a>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">&#9888; {{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([['Pending',$stats['pending'],'border-l-amber-500','text-amber-600'],['In Progress',$stats['in_progress'],'border-l-blue-500','text-blue-600'],['Completed',$stats['completed'],'border-l-emerald-500','text-emerald-600'],['Total',$stats['total'],'border-l-slate-500','text-slate-700']] as [$l,$v,$b,$c])
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 {{ $b }}">
                <p class="text-xs font-bold text-slate-500 uppercase">{{ $l }}</p>
                <p class="text-3xl font-extrabold {{ $c }} mt-1">{{ $v }}</p>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Add Task --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">New Task</h3>
                <form method="POST" action="{{ route('operations.tasks.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Task Title</label>
                        <input type="text" name="title" required placeholder="e.g. Deploy field teams to Kano"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Assign To</label>
                        <select name="assigned_to" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="">Unassigned</option>
                            @foreach($staff as $s)
                            <option value="{{ $s->id }}">{{ $s->name ?: $s->email }} ({{ $s->roleLabel }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Priority</label>
                            <select name="priority" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Due Date</label>
                            <input type="date" name="due_date"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">Create Task</button>
                </form>
            </div>

            {{-- Tasks List --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <form method="GET" action="{{ route('operations.tasks') }}" class="flex gap-3">
                        <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="">All Status</option>
                            <option value="pending"     {{ request('status')==='pending'     ?'selected':'' }}>Pending</option>
                            <option value="in_progress" {{ request('status')==='in_progress' ?'selected':'' }}>In Progress</option>
                            <option value="completed"   {{ request('status')==='completed'   ?'selected':'' }}>Completed</option>
                        </select>
                        <select name="priority" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="">All Priority</option>
                            <option value="high"   {{ request('priority')==='high'   ?'selected':'' }}>High</option>
                            <option value="medium" {{ request('priority')==='medium' ?'selected':'' }}>Medium</option>
                            <option value="low"    {{ request('priority')==='low'    ?'selected':'' }}>Low</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">Filter</button>
                    </form>
                </div>
                @forelse($tasks as $task)
                @php $ts = $task->status ?? 'pending'; $tp = $task->priority ?? 'low'; @endphp
                <div class="p-4 border-b border-slate-50 hover:bg-slate-50 last:border-0">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-slate-800 text-sm truncate">{{ $task->title }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold flex-shrink-0 {{
                                    $tp==='high'?'bg-red-100 text-red-700':
                                    ($tp==='medium'?'bg-amber-100 text-amber-700':'bg-slate-100 text-slate-500') }}">{{ ucfirst($tp) }}</span>
                            </div>
                            @if($task->description)
                            <p class="text-xs text-slate-500 truncate">{{ $task->description }}</p>
                            @endif
                            <div class="flex gap-3 mt-1 text-xs text-slate-400">
                                @if($task->due_date)<span>Due: {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}</span>@endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{
                                $ts==='completed'?'bg-emerald-100 text-emerald-800':
                                ($ts==='in_progress'?'bg-blue-100 text-blue-800':'bg-amber-100 text-amber-700') }}">
                                {{ str_replace('_',' ', ucfirst($ts)) }}
                            </span>
                            @if($ts !== 'completed')
                            <form method="POST" action="{{ route('operations.tasks.status', $task->id) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $ts==='pending' ? 'in_progress' : 'completed' }}">
                                <button type="submit" class="text-xs px-2 py-1 bg-[#0F6B3E] text-white rounded hover:bg-[#047857]">
                                    {{ $ts==='pending' ? 'Start' : 'Complete' }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-4 py-12 text-center text-slate-400 text-sm">No tasks yet. Create the first one.</div>
                @endforelse
                <div class="px-4 py-3 border-t border-slate-100">{{ $tasks->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
