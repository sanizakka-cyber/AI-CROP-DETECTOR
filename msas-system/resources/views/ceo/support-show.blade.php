<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Ticket #{{ $ticket->ticket_number }}</h2>
            <p style="font-size:12px;color:#64748b;margin:3px 0 0;">{{ $ticket->subject }} · {{ $ticket->user?->first_name }} {{ $ticket->user?->last_name }}</p>
        </div>
        <a href="{{ route('ceo.support') }}" style="background:#f1f5f9;color:#475569;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">← Back</a>
    </div>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:900px;margin:0 auto;padding:0 20px;display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;">

    {{-- Thread --}}
    <div>
        @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;color:#166534;font-weight:700;font-size:13px;margin-bottom:16px;">{{ session('success') }}</div>
        @endif

        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <span style="font-size:12px;font-weight:700;">{{ $ticket->user?->first_name }} (Farmer)</span>
                <span style="font-size:11px;color:#94a3b8;">{{ $ticket->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div style="font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;">{{ $ticket->message }}</div>
        </div>
        @foreach($ticket->replies as $reply)
        <div style="background:{{ $reply->is_staff ? '#f0fdf4' : '#fff' }};border-radius:14px;border:1px solid {{ $reply->is_staff ? '#bbf7d0' : '#e2e8f0' }};padding:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <span style="font-size:12px;font-weight:700;color:{{ $reply->is_staff ? '#0F6B3E' : '#0f172a' }};">{{ $reply->is_staff ? '🛡️ '.$reply->user->first_name.' (Staff)' : $reply->user->first_name.' (Farmer)' }}</span>
                <span style="font-size:11px;color:#94a3b8;">{{ $reply->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div style="font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;">{{ $reply->message }}</div>
        </div>
        @endforeach
        </div>

        <form method="POST" action="{{ route('ceo.support.reply', $ticket) }}">
        @csrf
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px;">
            <textarea name="message" rows="4" placeholder="Write staff reply..." style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;resize:vertical;box-sizing:border-box;" required></textarea>
            <button type="submit" style="margin-top:12px;background:#0F6B3E;color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Send Reply</button>
        </div>
        </form>
    </div>

    {{-- Sidebar: update status --}}
    <div>
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:14px;">Update Ticket</div>
            <form method="POST" action="{{ route('ceo.support.update', $ticket) }}">
            @csrf @method('PATCH')
            <div style="margin-bottom:12px;">
                <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">Status</label>
                <select name="status" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;background:#fff;">
                    @foreach(['open','in_progress','resolved','closed'] as $s)
                    <option value="{{ $s }}" {{ $ticket->status===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">Assign to</label>
                <select name="assigned_to" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;background:#fff;">
                    <option value="">Unassigned</option>
                    @foreach($staff as $s)
                    <option value="{{ $s->id }}" {{ $ticket->assigned_to==$s->id?'selected':'' }}>{{ $s->first_name }} {{ $s->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:16px;">
                <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">Resolution note</label>
                <textarea name="resolution" rows="3" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;resize:vertical;box-sizing:border-box;">{{ $ticket->resolution }}</textarea>
            </div>
            <button type="submit" style="width:100%;background:#0F6B3E;color:#fff;border:none;padding:10px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Save Changes</button>
            </form>
        </div>
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:16px;margin-top:12px;font-size:11px;color:#64748b;line-height:1.8;">
            <div><strong>Category:</strong> {{ ucfirst($ticket->category) }}</div>
            <div><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</div>
            <div><strong>Created:</strong> {{ $ticket->created_at->format('M d, Y') }}</div>
            @if($ticket->resolved_at)
            <div><strong>Resolved:</strong> {{ $ticket->resolved_at->format('M d, Y') }}</div>
            @endif
        </div>
    </div>

</div>
</div>
</x-app-layout>