<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Referral Leaderboard</h2>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:900px;margin:0 auto;padding:0 20px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:24px;">
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px;">
            <div style="font-size:28px;font-weight:900;color:#0F6B3E;">{{ number_format($totalReferrals) }}</div>
            <div style="font-size:11px;font-weight:700;color:#64748b;margin-top:4px;text-transform:uppercase;">Total Referrals</div>
        </div>
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px;">
            <div style="font-size:28px;font-weight:900;color:#b45309;">{{ number_format($pendingRewards) }}</div>
            <div style="font-size:11px;font-weight:700;color:#64748b;margin-top:4px;text-transform:uppercase;">Pending Rewards</div>
        </div>
    </div>
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
    <thead>
    <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
        <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">Rank</th>
        <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">Farmer</th>
        <th style="text-align:left;padding:12px 16px;font-weight:700;color:#64748b;">State</th>
        <th style="text-align:center;padding:12px 16px;font-weight:700;color:#64748b;">Referrals</th>
    </tr>
    </thead>
    <tbody>
    @forelse($leaders as $i => $leader)
    <tr style="border-top:1px solid #f1f5f9;{{ $i===0?'background:#fffbeb;':'' }}">
        <td style="padding:12px 16px;text-align:center;font-size:14px;font-weight:900;color:{{ $i===0?'#b45309':($i===1?'#64748b':($i===2?'#92400e':'#94a3b8')) }};">{{ $i+1 }}</td>
        <td style="padding:12px 16px;font-weight:700;color:#0f172a;">{{ $leader->first_name }} {{ $leader->last_name }}</td>
        <td style="padding:12px 16px;color:#64748b;">{{ $leader->state ?: '—' }}</td>
        <td style="padding:12px 16px;text-align:center;font-weight:900;font-size:18px;color:#0F6B3E;">{{ $leader->referral_count }}</td>
    </tr>
    @empty
    <tr><td colspan="4" style="text-align:center;padding:40px;color:#94a3b8;">No referrals yet.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
</div>
</x-app-layout>