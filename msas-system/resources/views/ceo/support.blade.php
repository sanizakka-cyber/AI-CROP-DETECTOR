<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Support Tickets</h2>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:1100px;margin:0 auto;padding:0 20px;">

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
@php $stats = [['Open',$openCount,'#dbeafe','#1d4ed8'],['In Progress',$inProgressCount,'#fef3c7','#b45309'],['Resolved',$resolvedCount,'#dcfce7','#166534'],['Urgent',$urgentCount,'#fef2f2','#dc2626']]; @endphp
@foreach($stats as [$label,$val,$bg,$col])
<div style="background:{{ $bg }};border-radius:14px;border:1px solid #e2e8f0;padding:18px 20px;">
    <div style="font-size:26px;font-weight:900;color:{{ $col }};">{{ $val }}</div>
    <div style="font-size:11px;font-weight:700;color:#64748b;margin-top:4px;text-transform:uppercase;letter-spacing:0.05em;">{{ $label }}</div>
</div>
@endforeach
</div>

{{-- Filters --}}
<form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search tickets..." style="border:1px solid #e2e8f0;border-radius:10px;padding:9px 14px;font-size:13px;background:#fff;flex:1;min-width:180px;">
    <select name="status" style="border:1px solid #e2e8f0;border-radius:10px;padding:9px 14px;font-size:13px;background:#fff;">
        <option value="">All Status</option>
        @foreach(['open','in_progress','resolved','closed'] as $s)
        <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
        @endforeach
    </select>
    <select name="priority" style="border:1px solid #e2e8f0;border-radius:10px;padding:9px 14px;font-size:13px;background:#fff;">
        <option value="">All Priority</option>
        @foreach(['urgent','high','normal','low'] as $p)
        <option value="{{ $p }}" {{ request('priority')===$p?'selected':'' }}>{{ ucfirst($p) }}</option>
        @endforeach
    </select>
    <button type="submit" style="background:#0F6B3E;color:#fff;border:none;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Filter</button>
</form>

<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
<table style="width:100%;border-collapse:collapse;font-size:12px;">
<thead>
<tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">#</th>
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">Subject</th>
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">Farmer</th>
    <th style="text-align:center;padding:12px 16px;font-weight:700;color:#64748b;">Priority</th>
    <th style="text-align:center;padding:12px 16px;font-weight:700;color:#64748b;">Status</th>
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">Created</th>
    <th style="text-align:center;padding:12px 16px;font-weight:700;color:#64748b;"></th>
</tr>
</thead>
<tbody>
@forelse($tickets as $ticket)
<tr style="border-top:1px solid #f1f5f9;">
    <td style="padding:12px 16px;color:#94a3b8;font-family:monospace;">{{ $ticket->ticket_number }}</td>
    <td style="padding:12px 16px;font-weight:700;color:#0f172a;max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ticket->subject }}</td>
    <td style="padding:12px 16px;color:#475569;">{{ $ticket->user?->first_name }} {{ $ticket->user?->last_name }}</td>
    <td style="padding:12px 16px;text-align:center;"><span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;{{ $ticket->priorityBadge() }}">{{ ucfirst($ticket->priority) }}</span></td>
    <td style="padding:12px 16px;text-align:center;"><span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;{{ $ticket->statusBadge() }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></td>
    <td style="padding:12px 16px;color:#94a3b8;">{{ $ticket->created_at->diffForHumans() }}</td>
    <td style="padding:12px 16px;text-align:center;"><a href="{{ route('ceo.support.show', $ticket) }}" style="color:#0F6B3E;font-weight:700;font-size:11px;text-decoration:none;">View →</a></td>
</tr>
@empty
<tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">No tickets found.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div style="margin-top:16px;">{{ $tickets->links() }}</div>

</div>
</div>
</x-app-layout>