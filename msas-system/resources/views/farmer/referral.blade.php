<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Refer a Farmer</h2>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:760px;margin:0 auto;padding:0 20px;">

    <div style="background:linear-gradient(135deg,#0F6B3E,#1FA84A);border-radius:18px;padding:32px;color:#fff;margin-bottom:24px;text-align:center;">
        <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;opacity:0.8;margin-bottom:8px;">Your Referral Code</div>
        <div style="font-size:36px;font-weight:900;letter-spacing:0.12em;margin-bottom:12px;font-family:monospace;">{{ $user->referral_code }}</div>
        <div style="font-size:12px;opacity:0.8;margin-bottom:20px;">Share this link or code with fellow farmers to earn rewards when they join MSAS FarmAI.</div>
        <div style="background:rgba(255,255,255,0.15);border-radius:10px;padding:10px 16px;font-size:12px;font-family:monospace;word-break:break-all;margin-bottom:16px;">{{ $referralLink }}</div>
        <button onclick="navigator.clipboard.writeText('{{ $referralLink }}').then(()=>this.textContent='Copied!')" style="background:#fff;color:#0F6B3E;border:none;padding:12px 28px;border-radius:10px;font-size:13px;font-weight:800;cursor:pointer;">Copy Referral Link</button>
    </div>

    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:700;color:#0f172a;">Your Referrals</div>
            <span style="font-size:12px;font-weight:800;color:#0F6B3E;background:#f0fdf4;padding:4px 12px;border-radius:20px;">{{ $referrals->count() }} total</span>
        </div>
        @forelse($referrals as $ref)
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9;">
            <div style="width:36px;height:36px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#0F6B3E;flex-shrink:0;">{{ substr($ref->referred?->first_name ?? '?',0,1) }}</div>
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:700;color:#0f172a;">{{ $ref->referred?->first_name }} {{ $ref->referred?->last_name }}</div>
                <div style="font-size:11px;color:#94a3b8;">Joined {{ $ref->referred?->created_at?->format('M d, Y') }}</div>
            </div>
            <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;background:{{ $ref->status==='rewarded'?'#dcfce7':'#fef3c7' }};color:{{ $ref->status==='rewarded'?'#166534':'#b45309' }};">{{ ucfirst($ref->status) }}</span>
        </div>
        @empty
        <div style="text-align:center;padding:32px;color:#94a3b8;font-size:13px;">No referrals yet. Share your link to get started!</div>
        @endforelse
    </div>
</div>
</div>
</x-app-layout>