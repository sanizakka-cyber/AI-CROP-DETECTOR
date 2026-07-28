<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Broadcast Notification</h2>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:680px;margin:0 auto;padding:0 20px;">
    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;color:#166534;font-weight:700;font-size:13px;margin-bottom:20px;">{{ session('success') }}</div>
    @endif
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:28px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;color:#64748b;margin-bottom:20px;line-height:1.6;">Send a push notification to a segment of active users. Each user will receive an in-app notification and an Expo push (if they have a push token).</div>
        <form method="POST" action="{{ route('ceo.broadcast.send') }}">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <label style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:6px;">Segment *</label>
                <select name="segment" style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;background:#fff;">
                    <option value="all">All active users</option>
                    <option value="farmers">Farmers only</option>
                    <option value="vets">Vets only</option>
                    <option value="agro">Agronomists only</option>
                    <option value="pilots">Pilot users only</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:6px;">Filter by State (optional)</label>
                <input type="text" name="state" placeholder="e.g. Kano" style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;box-sizing:border-box;">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 60px;gap:16px;margin-bottom:16px;">
            <div>
                <label style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:6px;">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="100" placeholder="Notification title" style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;box-sizing:border-box;" required>
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:6px;">Icon</label>
                <input type="text" name="icon" value="{{ old('icon','📢') }}" maxlength="5" style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:16px;text-align:center;box-sizing:border-box;">
            </div>
        </div>
        <div style="margin-bottom:20px;">
            <label style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:6px;">Message *</label>
            <textarea name="body" rows="4" maxlength="500" placeholder="Notification body text..." style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;resize:vertical;box-sizing:border-box;" required>{{ old('body') }}</textarea>
        </div>
        @if($errors->any())
        <div style="background:#fef2f2;border-radius:10px;padding:12px 16px;color:#dc2626;font-size:12px;margin-bottom:16px;">{{ $errors->first() }}</div>
        @endif
        <button type="submit" onclick="return confirm('Send this broadcast to all matched users?')" style="background:#0F6B3E;color:#fff;border:none;padding:12px 28px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;width:100%;">📢 Send Broadcast</button>
        </form>
    </div>
</div>
</div>
</x-app-layout>