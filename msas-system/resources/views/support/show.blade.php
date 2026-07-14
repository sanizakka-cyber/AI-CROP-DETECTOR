<x-app-layout>
    <x-slot name="header">Ticket #{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }}</x-slot>

    <div class="space-y-6 max-w-3xl mx-auto">

        <div class="flex items-center gap-4">
            <a href="{{ route('support.tickets') }}" class="text-sm text-blue-600 font-semibold hover:underline">&larr; Back to Tickets</a>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif

        {{-- Ticket Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-slate-800 text-xl">{{ $ticket->subject }}</h2>
                    <div class="flex gap-2 mt-2 flex-wrap">
                        @php $s = $ticket->status ?? 'open'; $p = $ticket->priority ?? 'low'; @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{
                            $s==='open'?'bg-red-100 text-red-800':
                            ($s==='in_progress'?'bg-amber-100 text-amber-800':
                            ($s==='resolved'?'bg-emerald-100 text-emerald-800':'bg-slate-100 text-slate-600')) }}">
                            {{ str_replace('_',' ', ucfirst($s)) }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{
                            $p==='urgent'?'bg-red-100 text-red-800':
                            ($p==='high'?'bg-orange-100 text-orange-800':
                            ($p==='medium'?'bg-amber-100 text-amber-700':'bg-slate-100 text-slate-600')) }}">
                            {{ ucfirst($p) }} Priority
                        </span>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs font-bold">{{ $ticket->category ?? 'General' }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    @if($s !== 'resolved' && $s !== 'closed')
                    <form method="POST" action="{{ route('support.tickets.resolve', $ticket->id) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">Mark Resolved</button>
                    </form>
                    <form method="POST" action="{{ route('support.tickets.close', $ticket->id) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-300">Close</button>
                    </form>
                    @endif
                </div>
            </div>
            @if($user)
            <div class="mt-4 pt-4 border-t border-slate-100 text-sm text-slate-600">
                Customer: <span class="font-semibold text-slate-800">{{ $user->name ?: $user->email }}</span>
                &nbsp;&middot;&nbsp; {{ $user->roleLabel }}
                &nbsp;&middot;&nbsp; Opened {{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}
            </div>
            @endif
            <div class="mt-4 p-4 bg-slate-50 rounded-xl text-sm text-slate-700 leading-relaxed">
                {{ $ticket->description }}
            </div>
        </div>

        {{-- Replies --}}
        @if(count($replies) > 0)
        <div class="space-y-3">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Replies ({{ count($replies) }})</h3>
            @foreach($replies as $reply)
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
                <div class="text-xs text-slate-400 mb-2">Agent #{{ $reply->agent_id }} &middot; {{ \Carbon\Carbon::parse($reply->created_at)->format('d M Y H:i') }}</div>
                <p class="text-sm text-slate-700">{{ $reply->message }}</p>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Reply Form --}}
        @if($s !== 'closed')
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 mb-4">Add Reply</h3>
            <form method="POST" action="{{ route('support.tickets.reply', $ticket->id) }}" class="space-y-3">
                @csrf
                <textarea name="message" required rows="4" placeholder="Type your reply..."
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition">Send Reply</button>
            </form>
        </div>
        @endif

    </div>
</x-app-layout>
