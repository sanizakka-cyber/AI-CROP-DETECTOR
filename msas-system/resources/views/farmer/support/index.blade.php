<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Support Tickets</h2>
            <p style="font-size:12px;color:#64748b;margin:3px 0 0;">Track your support requests</p>
        </div>
        <a href="{{ route('support.create') }}" style="background:#0F6B3E;color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">+ New Ticket</a>
    </div>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:900px;margin:0 auto;padding:0 20px;">
    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;color:#166534;font-weight:700;font-size:13px;margin-bottom:20px;">{{ session('success') }}</div>
    @endif
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    @forelse($tickets as $ticket)
    <a href="{{ route('support.show', $ticket) }}" style="display:flex;align-items:center;gap:14px;padding:16px 20px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;transition:background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
        <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ticket->subject }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">#{{ $ticket->ticket_number }} · {{ $ticket->created_at->diffForHumans() }}</div>
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
            <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;{{ $ticket->priorityBadge() }}">{{ ucfirst($ticket->priority) }}</span>
            <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;{{ $ticket->statusBadge() }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span>
        </div>
    </a>
    @empty
    <div style="text-align:center;padding:48px 20px;">
        <div style="font-size:40px;margin-bottom:12px;">🎫</div>
        <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:6px;">No tickets yet</div>
        <div style="font-size:12px;color:#94a3b8;margin-bottom:20px;">Submit a support request and our team will respond within 24 hours.</div>
        <a href="{{ route('support.create') }}" style="background:#0F6B3E;color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">Open a Ticket</a>
    </div>
    @endforelse
    </div>
    <div style="margin-top:16px;">{{ $tickets->links() }}</div>
</div>
</div>
</x-app-layout>