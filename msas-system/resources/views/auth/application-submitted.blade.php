<x-guest-layout>
    {{-- Success icon + heading --}}
    <div style="text-align:center; margin-bottom:24px;">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:#f0fdf4;margin-bottom:14px;">
            <svg style="width:32px;height:32px;color:#16a34a;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin-bottom:4px;">Application Submitted!</div>
        <p style="font-size:13px;color:#64748b;margin:0;">We've received your registration</p>
    </div>

    <p style="font-size:13px;color:#475569;line-height:1.75;margin:0 0 20px;">
        Thank you for applying to join MSAS FarmAI. Your application and uploaded documents are now
        under review by our administration team. You will receive an email notification once a
        decision has been made.
    </p>

    {{-- 3-step tracker --}}
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:16px;">
        <div style="display:flex;flex-direction:column;gap:14px;">
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <div style="width:24px;height:24px;border-radius:50%;background:#16a34a;color:#fff;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">✓</div>
                <div>
                    <p style="font-size:13px;font-weight:700;color:#1e293b;margin:0 0 2px;">Application Submitted</p>
                    <p style="font-size:12px;color:#64748b;margin:0;">Your registration and documents have been received.</p>
                </div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <div style="width:24px;height:24px;border-radius:50%;background:#f59e0b;color:#fff;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</div>
                <div>
                    <p style="font-size:13px;font-weight:700;color:#1e293b;margin:0 0 2px;">Document Review</p>
                    <p style="font-size:12px;color:#64748b;margin:0;">Our team will verify your credentials and qualifications.</p>
                </div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <div style="width:24px;height:24px;border-radius:50%;background:#cbd5e1;color:#fff;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</div>
                <div>
                    <p style="font-size:13px;font-weight:600;color:#94a3b8;margin:0 0 2px;">Account Activation</p>
                    <p style="font-size:12px;color:#94a3b8;margin:0;">Once approved, you'll receive an email to log in.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Estimated time warning --}}
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;margin-bottom:28px;">
        <p style="font-size:13px;color:#92400e;margin:0;">
            <span style="font-weight:700;">Estimated review time:</span> 1–3 business days.
            We may contact you if additional information is needed.
        </p>
    </div>

    {{-- Return to login --}}
    <div style="text-align:center;">
        <a href="{{ route('login') }}"
           style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:#0F6B3E;text-decoration:none;"
           onmouseover="this.style.color='#0a4a2b'" onmouseout="this.style.color='#0F6B3E'">
            <svg style="width:15px;height:15px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Return to Login
        </a>
    </div>
</x-guest-layout>
