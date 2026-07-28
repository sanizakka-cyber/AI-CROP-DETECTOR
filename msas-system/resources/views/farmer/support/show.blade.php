<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Ticket #{{ $ticket->ticket_number }}</h2>
            <p style="font-size:12px;color:#64748b;margin:3px 0 0;">{{ $ticket->subject }}</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;{{ $ticket->priorityBadge() }}">{{ ucfirst($ticket->priority) }}</span>
            <span style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;{{ $ticket->statusBadge() }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span>
        </div>
    </div>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:760px;margin:0 auto;padding:0 20px;">
    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;color:#166534;font-weight:700;font-size:13px;margin-bottom:16px;">{{ session('success') }}</div>
    @endif

    {{-- Thread --}}
    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">

    {{-- Original message --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-size:12px;font-weight:700;color:#0f172a;">You</span>
            <span style="font-size:11px;color:#94a3b8;">{{ $ticket->created_at->format('M d, Y H:i') }}</span>
        </div>
        <div style="font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;">{{ $ticket->message }}</div>
    </div>

    {{-- Replies --}}
    @foreach($ticket->replies as $reply)
    <div style="background:{{ $reply->is_staff ? '#f0fdf4' : '#fff' }};border-radius:14px;border:1px solid {{ $reply->is_staff ? '#bbf7d0' : '#e2e8f0' }};padding:20px;{{ $reply->is_staff ? '' : '' }}box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-size:12px;font-weight:700;color:{{ $reply->is_staff ? '#0F6B3E' : '#0f172a' }};">{{ $reply->is_staff ? '🛡️ MSAS Support — '.$reply->user->first_name : 'You' }}</span>
            <span style="font-size:11px;color:#94a3b8;">{{ $reply->created_at->format('M d, Y H:i') }}</span>
        </div>
        <div style="font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;">{{ $reply->message }}</div>
    </div>
    @endforeach

    {{-- Resolution --}}
    @if($ticket->resolution)
    <div style="background:#f0fdf4;border:2px solid #0F6B3E;border-radius:14px;padding:20px;">
        <div style="font-size:12px;font-weight:700;color:#0F6B3E;margin-bottom:8px;">✓ Resolution</div>
        <div style="font-size:13px;color:#374151;line-height:1.6;">{{ $ticket->resolution }}</div>
    </div>
    @endif

    </div>

    {{-- Reply form --}}
    @if(!in_array($ticket->status, ['resolved','closed']))
    <form method="POST" action="{{ route('support.reply', $ticket) }}">
    @csrf
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <textarea name="message" rows="4" placeholder="Write your reply..." style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;resize:vertical;outline:none;box-sizing:border-box;" required></textarea>
        <div style="margin-top:12px;display:flex;gap:10px;">
            <button type="submit" style="background:#0F6B3E;color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Send Reply</button>
            <a href="{{ route('support.index') }}" style="background:#f1f5f9;color:#475569;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">← Back</a>
        </div>
    </div>
    </form>
    @else
    <div style="text-align:center;padding:16px;font-size:12px;color:#94a3b8;">This ticket is {{ $ticket->status }}. <a href="{{ route('support.create') }}" style="color:#0F6B3E;font-weight:700;">Open a new ticket</a> if you need further help.</div>
    @endif

</div>
</div>
</x-app-layout>