<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Consultation #{{ $consultation->id }}</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Review the farmer's report and provide your expert diagnosis</p>
        </div>
        <a href="{{ route('vet.queue') }}"
           style="background:#f1f5f9;color:#374151;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Queue
        </a>
    </div>
</x-slot>

@php
$priorityColors = [
    'low'      => ['bg' => '#f0fdf4', 'text' => '#15803d', 'dot' => '#1FA84A'],
    'medium'   => ['bg' => '#fef3c7', 'text' => '#92400e', 'dot' => '#F4A300'],
    'high'     => ['bg' => '#fef2f2', 'text' => '#dc2626', 'dot' => '#dc2626'],
    'critical' => ['bg' => '#fef2f2', 'text' => '#7f1d1d', 'dot' => '#7f1d1d'],
];
$pc = $priorityColors[$consultation->priority ?? 'low'];
$farmer = $consultation->farmer;
@endphp

<div style="max-width:860px;margin:0 auto;">

    <!-- Case header card -->
    <div style="background:linear-gradient(135deg,#0B2447,#0F6B3E);border-radius:16px;padding:24px 28px;margin-bottom:22px;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <div style="color:rgba(255,255,255,0.6);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">
                Farmer Request &bull; Submitted {{ $consultation->created_at->format('M d, Y @ g:i A') }}
            </div>
            <div style="color:#fff;font-size:22px;font-weight:800;margin-bottom:4px;">
                {{ $farmer?->first_name }} {{ $farmer?->last_name ?? 'Unknown Farmer' }}
            </div>
            <div style="color:rgba(255,255,255,0.65);font-size:13px;">
                {{ $farmer?->phone ?? '' }}{{ ($farmer?->phone && $farmer?->email) ? ' · ' : '' }}{{ $farmer?->email ?? '' }}
            </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:10px;">
            <span style="background:{{ $pc['bg'] }};color:{{ $pc['text'] }};font-size:11px;font-weight:800;padding:5px 14px;border-radius:20px;text-transform:uppercase;letter-spacing:0.06em;">
                {{ ucfirst($consultation->priority ?? 'Low') }} Priority
            </span>
            <span style="background:rgba(255,255,255,0.15);color:#fff;font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;border:1px solid rgba(255,255,255,0.25);">
                {{ ucfirst($consultation->status) }}
            </span>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:22px;">
        <!-- Case Info -->
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px 22px;">
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:14px;">Case Information</div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;border-radius:9px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;">🐄</div>
                    <div>
                        <div style="font-size:10px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Animal / Crop Type</div>
                        <div style="font-size:14px;font-weight:700;color:#0f172a;">{{ ucfirst($consultation->animal_type ?? $consultation->crop_type ?? 'Not specified') }}</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;border-radius:9px;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;">🩺</div>
                    <div>
                        <div style="font-size:10px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Case Type</div>
                        <div style="font-size:14px;font-weight:700;color:#0f172a;">{{ ucfirst($consultation->case_type) }} Health</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;border-radius:9px;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;">💬</div>
                    <div>
                        <div style="font-size:10px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Consultation Type</div>
                        <div style="font-size:14px;font-weight:700;color:#0f172a;">{{ ucfirst($consultation->consultation_type ?? 'Chat') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reported Symptoms -->
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:20px 22px;">
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:14px;">Reported Symptoms</div>
            <div style="background:#f8fafc;border-radius:10px;border:1px solid #f1f5f9;padding:14px;font-size:13px;color:#374151;line-height:1.7;font-style:italic;">
                "{{ $consultation->symptoms }}"
            </div>
            @if($consultation->ai_diagnosis)
            <div style="margin-top:12px;padding:12px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;">
                <div style="font-size:10px;font-weight:700;color:#15803d;text-transform:uppercase;margin-bottom:4px;">🤖 AI Pre-Analysis</div>
                <div style="font-size:12px;color:#166534;">{{ $consultation->ai_diagnosis }}
                    @if($consultation->ai_confidence)
                    <span style="font-weight:700;">({{ $consultation->ai_confidence }}% confidence)</span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($consultation->status === 'resolved' && $consultation->expert_response)
    <!-- Already resolved -->
    <div style="background:#fff;border-radius:14px;border:2px solid #bbf7d0;padding:22px 24px;margin-bottom:22px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:32px;height:32px;border-radius:8px;background:#0F6B3E;display:flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">Consultation Resolved</div>
                <div style="font-size:12px;color:#64748b;">
                    Responded by {{ $consultation->expert?->first_name }} {{ $consultation->expert?->last_name }} &bull;
                    {{ $consultation->completed_at?->format('M d, Y @ g:i A') ?? $consultation->updated_at->format('M d, Y') }}
                </div>
            </div>
        </div>
        <div style="background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;padding:16px;font-size:13px;color:#374151;line-height:1.7;">
            {!! nl2br(e($consultation->expert_response)) !!}
        </div>
    </div>
    @else
    <!-- Expert Response Form -->
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:24px 26px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div style="width:36px;height:36px;border-radius:9px;background:#0F6B3E;display:flex;align-items:center;justify-content:center;">
                <svg width="17" height="17" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </div>
            <div>
                <div style="font-size:15px;font-weight:800;color:#0f172a;">Your Professional Diagnosis & Advice</div>
                <div style="font-size:12px;color:#64748b;">Provide a clear, detailed response the farmer can act on immediately</div>
            </div>
        </div>

        @if ($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:9px;padding:12px 14px;margin-bottom:16px;color:#dc2626;font-size:13px;font-weight:600;">
            {{ $errors->first('expert_response') }}
        </div>
        @endif

        <form action="{{ route('vet.respond', $consultation) }}" method="POST">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">
                    Diagnosis & Treatment Plan <span style="color:#dc2626;">*</span>
                </label>
                <textarea name="expert_response" required rows="10"
                    placeholder="Provide a detailed diagnosis, recommended medication (name, dosage, frequency), management advice, and follow-up instructions..."
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:14px;font-size:13px;color:#374151;line-height:1.7;resize:vertical;font-family:inherit;box-sizing:border-box;outline:none;"
                    onfocus="this.style.borderColor='#0F6B3E'" onblur="this.style.borderColor='#e2e8f0'">{{ old('expert_response') }}</textarea>
            </div>

            <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:9px;padding:12px 14px;margin-bottom:18px;">
                <div style="font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">💡 Vet Reminder</div>
                <div style="font-size:12px;color:#78350f;line-height:1.5;">
                    Include drug names, dosage, withdrawal periods, and when to seek further care.
                    Responses are visible to the farmer immediately after submission.
                </div>
            </div>

            <button type="submit"
                style="width:100%;padding:14px;background:#0F6B3E;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Submit Consultation Response
            </button>
        </form>
    </div>
    @endif

</div>
</x-app-layout>
