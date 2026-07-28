<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Notifications</h2>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:760px;margin:0 auto;padding:0 20px;">
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    @forelse($notifications as $notif)
    @php $isRead = !is_null($notif->read_at); @endphp
    <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-bottom:1px solid #f1f5f9;background:{{ $isRead ? '#fff' : '#f0fdf4' }};">
        <div style="font-size:22px;flex-shrink:0;margin-top:2px;">{{ $notif->icon ?? '🔔' }}</div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:{{ $isRead ? '600' : '800' }};color:#0f172a;">{{ $notif->title }}</div>
            <div style="font-size:12px;color:#475569;margin-top:3px;line-height:1.5;">{{ $notif->body }}</div>
            <div style="font-size:10px;color:#94a3b8;margin-top:6px;">{{ $notif->created_at->diffForHumans() }}</div>
        </div>
        @if(!$isRead)
        <div style="width:8px;height:8px;background:#0F6B3E;border-radius:50%;flex-shrink:0;margin-top:6px;"></div>
        @endif
    </div>
    @empty
    <div style="text-align:center;padding:60px 20px;">
        <div style="font-size:40px;margin-bottom:12px;">🔔</div>
        <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:6px;">All caught up!</div>
        <div style="font-size:12px;color:#94a3b8;">No notifications yet.</div>
    </div>
    @endforelse
    </div>
    <div style="margin-top:16px;">{{ $notifications->links() }}</div>
</div>
</div>
</x-app-layout>