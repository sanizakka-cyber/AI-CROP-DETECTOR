<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Consultation Queue</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Pending farmer consultation requests awaiting your response</p>
        </div>
        <a href="{{ route('vet.dashboard') }}"
           style="background:#f1f5f9;color:#374151;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Dashboard
        </a>
    </div>
</x-slot>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <svg width="16" height="16" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span style="color:#15803d;font-size:13px;font-weight:600;">{{ session('success') }}</span>
</div>
@endif

<!-- Stats bar -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:24px;">
    @php
    $pending  = $consultations->where('status','pending')->count();
    $high     = $consultations->whereIn('priority',['high','critical'])->count();
    $total    = $consultations->count();
    @endphp
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:14px 18px;border-left:4px solid #F4A300;">
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">In Queue</div>
        <div style="font-size:28px;font-weight:900;color:#F4A300;margin-top:2px;">{{ $total }}</div>
    </div>
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:14px 18px;border-left:4px solid #dc2626;">
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">High Priority</div>
        <div style="font-size:28px;font-weight:900;color:#dc2626;margin-top:2px;">{{ $high }}</div>
    </div>
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:14px 18px;border-left:4px solid #0F6B3E;">
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Pending</div>
        <div style="font-size:28px;font-weight:900;color:#0F6B3E;margin-top:2px;">{{ $pending }}</div>
    </div>
</div>

<!-- Consultation Cards -->
@forelse($consultations as $consult)
@php
$priorityColors = [
    'low'      => ['bg' => '#f0fdf4', 'text' => '#15803d', 'border' => '#bbf7d0'],
    'medium'   => ['bg' => '#fef3c7', 'text' => '#92400e', 'border' => '#fcd34d'],
    'high'     => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'],
    'critical' => ['bg' => '#fef2f2', 'text' => '#7f1d1d', 'border' => '#dc2626'],
];
$pc = $priorityColors[$consult->priority ?? 'low'];
@endphp

<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;margin-bottom:16px;overflow:hidden;box-shadow:0 1px 8px rgba(0,0,0,0.04);">
    <!-- Card Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:linear-gradient(135deg,#0B2447,#0F6B3E);flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                @if($consult->case_type === 'crop')
                <svg width="22" height="22" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12m0 0C12 7 7 4 2 5c0 5 4 8 10 7zm0 0c0-5 5-8 10-7-1 5-5 8-10 7"/></svg>
                @else
                <svg width="22" height="22" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                @endif
            </div>
            <div>
                <div style="color:rgba(255,255,255,0.65);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">
                    Case #{{ $consult->id }} &bull; {{ $consult->created_at->diffForHumans() }}
                </div>
                <div style="color:#fff;font-size:15px;font-weight:800;margin-top:2px;">
                    {{ ucfirst($consult->animal_type ?? $consult->crop_type ?? 'General') }} — {{ ucfirst($consult->case_type) }} Case
                </div>
            </div>
        </div>
        <span style="background:{{ $pc['bg'] }};color:{{ $pc['text'] }};border:1px solid {{ $pc['border'] }};font-size:11px;font-weight:800;padding:4px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:0.05em;">
            {{ ucfirst($consult->priority ?? 'Low') }} Priority
        </span>
    </div>

    <!-- Card Body -->
    <div style="padding:16px 20px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Reported Symptoms</div>
            <p style="font-size:13px;color:#374151;line-height:1.6;margin:0;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                {{ $consult->symptoms }}
            </p>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;min-width:160px;">
            <!-- Farmer info -->
            @if($consult->farmer)
            <div style="background:#f8fafc;border-radius:8px;padding:10px 12px;border:1px solid #f1f5f9;">
                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px;">Farmer</div>
                <div style="font-size:13px;font-weight:700;color:#0f172a;">{{ $consult->farmer->first_name }} {{ $consult->farmer->last_name }}</div>
                <div style="font-size:11px;color:#64748b;margin-top:1px;">{{ $consult->farmer->phone ?? $consult->farmer->email ?? '—' }}</div>
            </div>
            @endif
            @if($consult->channel)
            <div style="background:#f0fdf4;border-radius:8px;padding:8px 12px;border:1px solid #bbf7d0;">
                <div style="font-size:10px;font-weight:700;color:#15803d;text-transform:uppercase;margin-bottom:2px;">Consult Channel</div>
                <div style="font-size:12px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:5px;">
                    @if($consult->channel === 'in_app') <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg> In-App Chat
                    @elseif($consult->channel === 'whatsapp') <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" d="M12 18h.01"/></svg> WhatsApp
                    @else <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> Phone Call
                    @endif
                    &nbsp;·&nbsp; ₦{{ number_format($consult->fee ?? 0) }}
                </div>
            </div>
            @endif

            <a href="{{ route('vet.show', $consult) }}"
               style="display:flex;align-items:center;justify-content:center;gap:7px;padding:11px 20px;background:#0F6B3E;color:#fff;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Respond to Case
            </a>
        </div>
    </div>
</div>

@empty
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:48px 24px;text-align:center;">
    <div style="margin-bottom:16px;display:flex;justify-content:center;"><svg width="48" height="48" fill="none" stroke="#0F6B3E" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
    <div style="font-size:18px;font-weight:800;color:#0f172a;margin-bottom:8px;">All Clear!</div>
    <div style="font-size:14px;color:#64748b;">No pending consultations. All farmer cases have been handled.</div>
    <a href="{{ route('vet.dashboard') }}" style="display:inline-block;margin-top:20px;padding:10px 24px;background:#0F6B3E;color:#fff;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;">
        Back to Dashboard
    </a>
</div>
@endforelse

</x-app-layout>
