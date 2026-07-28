<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Audit Log</h2>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:1100px;margin:0 auto;padding:0 20px;">

<form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="action" value="{{ request('action') }}" placeholder="Filter by action..." style="border:1px solid #e2e8f0;border-radius:10px;padding:9px 14px;font-size:13px;background:#fff;flex:1;min-width:180px;">
    <input type="text" name="user_id" value="{{ request('user_id') }}" placeholder="User ID" style="border:1px solid #e2e8f0;border-radius:10px;padding:9px 14px;font-size:13px;background:#fff;width:100px;">
    <input type="date" name="from" value="{{ request('from') }}" style="border:1px solid #e2e8f0;border-radius:10px;padding:9px 14px;font-size:13px;background:#fff;">
    <input type="date" name="to"   value="{{ request('to') }}"   style="border:1px solid #e2e8f0;border-radius:10px;padding:9px 14px;font-size:13px;background:#fff;">
    <button type="submit" style="background:#0F6B3E;color:#fff;border:none;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Filter</button>
    <a href="{{ route('ceo.audit-log') }}" style="background:#f1f5f9;color:#475569;padding:9px 16px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">Clear</a>
</form>

<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
<div style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;font-size:12px;">
<thead>
<tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;white-space:nowrap;">Time</th>
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">Action</th>
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">User</th>
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">Model</th>
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">IP</th>
    <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">Details</th>
</tr>
</thead>
<tbody>
@forelse($logs as $log)
@php
$isSecure = str_contains($log->action,'login.failed')||str_contains($log->action,'locked')||str_contains($log->action,'breach');
$isDelete = str_contains($log->action,'delete')||str_contains($log->action,'deletion');
$rowBg = $isSecure ? '#fef2f2' : ($isDelete ? '#fff7ed' : '');
@endphp
<tr style="border-top:1px solid #f1f5f9;{{ $rowBg ? 'background:'.$rowBg.';' : '' }}">
    <td style="padding:10px 16px;color:#94a3b8;white-space:nowrap;font-family:monospace;font-size:11px;">{{ $log->created_at->format('M d H:i:s') }}</td>
    <td style="padding:10px 16px;font-weight:700;color:{{ $isSecure ? '#dc2626' : ($isDelete ? '#ea580c' : '#0f172a') }};white-space:nowrap;">{{ $log->action }}</td>
    <td style="padding:10px 16px;color:#475569;white-space:nowrap;">
        @if($log->user)
        <span style="font-weight:700;">{{ $log->user->first_name }} {{ $log->user->last_name }}</span>
        <span style="color:#94a3b8;font-size:10px;">#{{ $log->user_id }}</span>
        @else
        <span style="color:#94a3b8;">—</span>
        @endif
    </td>
    <td style="padding:10px 16px;color:#64748b;font-size:11px;">{{ $log->model }}@if($log->model_id) #{{ $log->model_id }}@endif</td>
    <td style="padding:10px 16px;color:#64748b;font-family:monospace;font-size:11px;">{{ $log->ip_address }}</td>
    <td style="padding:10px 16px;color:#64748b;font-size:11px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $log->details }}">{{ $log->details ? \Illuminate\Support\Str::limit($log->details, 60) : '—' }}</td>
</tr>
@empty
<tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">No audit log entries found.</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
<div style="margin-top:16px;">{{ $logs->links() }}</div>
</div>
</div>
</x-app-layout>