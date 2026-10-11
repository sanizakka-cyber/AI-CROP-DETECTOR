<x-guest-layout>
    <div style="max-width:480px;margin:0 auto;text-align:center;">
        <div style="width:56px;height:56px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg width="26" height="26" fill="none" stroke="#0F6B3E" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2 style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:#0f172a;margin:0 0 8px;">Document Received</h2>
        <p style="font-size:13px;color:#64748b;margin:0;line-height:1.6;">
            Your replacement for <strong>{{ $document->document_label }}</strong> has been submitted and is now pending review.
            You'll hear from us once a decision has been made.
        </p>
    </div>
</x-guest-layout>
