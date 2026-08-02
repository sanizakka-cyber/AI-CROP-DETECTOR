<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Privacy & Your Data</h2>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:760px;margin:0 auto;padding:0 20px;">
    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;color:#166534;font-weight:700;font-size:13px;margin-bottom:20px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;color:#dc2626;font-weight:700;font-size:13px;margin-bottom:20px;">{{ session('error') }}</div>
    @endif

    {{-- Data Export --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:28px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:15px;font-weight:800;color:#0f172a;margin-bottom:8px;display:flex;align-items:center;gap:8px;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11"/></svg> Export Your Data (NDPR)</div>
        <p style="font-size:13px;color:#475569;line-height:1.6;margin-bottom:16px;">In line with the Nigeria Data Protection Regulation (NDPR), you can request a copy of all personal data we hold about you. The export will be emailed to <strong>{{ auth()->user()->email }}</strong> within 15 minutes.</p>
        @if(auth()->user()->data_export_requested_at && !auth()->user()->data_export_completed_at)
        <div style="background:#fef3c7;border-radius:10px;padding:12px 16px;color:#b45309;font-size:13px;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px;"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Your export is being prepared and will be emailed shortly.</div>
        @elseif(auth()->user()->data_export_completed_at)
        <div style="background:#f0fdf4;border-radius:10px;padding:12px 16px;color:#166534;font-size:13px;margin-bottom:12px;display:flex;align-items:center;gap:8px;"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Last export sent on {{ auth()->user()->data_export_completed_at->format('M d, Y') }}.</div>
        @endif
        <form method="POST" action="{{ route('compliance.export') }}">
        @csrf
        @if(!auth()->user()->email)
        <div style="background:#fef2f2;border-radius:10px;padding:12px;color:#dc2626;font-size:13px;">You need a verified email address to receive a data export.</div>
        @else
        <button type="submit" style="background:#0F6B3E;color:#fff;border:none;padding:12px 24px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Request Data Export</button>
        @endif
        </form>
    </div>

    {{-- Account Deletion --}}
    <div style="background:#fff;border-radius:16px;border:2px solid #fecaca;padding:28px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:15px;font-weight:800;color:#dc2626;margin-bottom:8px;display:flex;align-items:center;gap:8px;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg> Delete Account</div>
        <p style="font-size:13px;color:#475569;line-height:1.6;margin-bottom:16px;">Requesting deletion will immediately deactivate your account. Per our data retention policy, your data will be permanently deleted within 30 days. This cannot be undone.</p>
        <form method="POST" action="{{ route('compliance.delete') }}" onsubmit="return confirm('This will permanently delete your account. Type DELETE to confirm in the field below.')">
        @csrf @method('DELETE')
        <input type="text" name="confirm" placeholder='Type "DELETE" to confirm' style="border:2px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:13px;width:100%;margin-bottom:12px;box-sizing:border-box;" required>
        @if($errors->has('confirm'))
        <div style="color:#dc2626;font-size:12px;margin-bottom:8px;">{{ $errors->first('confirm') }}</div>
        @endif
        <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:12px 24px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Delete My Account</button>
        </form>
    </div>

</div>
</div>
</x-app-layout>