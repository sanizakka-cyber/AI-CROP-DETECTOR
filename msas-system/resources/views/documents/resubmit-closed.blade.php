<x-guest-layout>
    <div style="max-width:480px;margin:0 auto;text-align:center;">
        <h2 style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:#0f172a;margin:0 0 8px;">Link Already Used</h2>
        <p style="font-size:13px;color:#64748b;margin:0;line-height:1.6;">
            This document has already been resubmitted, or its status has changed since this link was sent.
            If you still need to update your application, please contact support at
            <a href="mailto:{{ config('mail.from.address') }}" style="color:#0F6B3E;font-weight:700;">{{ config('mail.from.address') }}</a>.
        </p>
    </div>
</x-guest-layout>
