<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Payment Receipt</h1>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Transaction confirmation</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('payment.history') }}" style="padding:9px 18px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;font-weight:600;color:#374151;text-decoration:none;">← All Payments</a>
            <button onclick="window.print()" style="background:#0B2447;color:#fff;border:none;border-radius:9px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;">🖨 Print</button>
        </div>
    </div>
</x-slot>

<style>
@media print {
    .sidebar, .top-header, .no-print { display:none!important; }
    .main-content { margin:0!important; padding:0!important; }
    .receipt-card { box-shadow:none!important; border:none!important; }
}
</style>

<div style="max-width:640px;margin:0 auto;">
<div class="receipt-card" style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,#0B2447,#0F6B3E);padding:32px;text-align:center;">
        <div style="font-size:36px;margin-bottom:8px;">✅</div>
        <div style="font-size:22px;font-weight:900;color:#fff;margin-bottom:4px;">Payment Successful</div>
        <div style="font-size:14px;color:rgba(255,255,255,0.7);">Thank you for your payment</div>
        <div style="font-size:38px;font-weight:900;color:#fff;margin-top:16px;">₦{{ number_format($payment->amount, 2) }}</div>
    </div>

    {{-- Receipt Details --}}
    <div style="padding:28px;">
        @php
        $rows = [
            'Receipt Number'   => $payment->receipt_number ?? '—',
            'Transaction ID'   => $payment->transaction_id ?? '—',
            'Reference'        => $payment->reference,
            'Description'      => $payment->description,
            'Service'          => ucfirst(str_replace('_', ' ', $payment->module)),
            'Payment Method'   => ucfirst($payment->channel ?? $payment->payment_method ?? 'Online'),
            'Date & Time'      => ($payment->paid_at ?? $payment->created_at)->format('d M Y, H:i'),
            'Status'           => ucfirst($payment->status),
            'Verified'         => $payment->verification_status === 'verified' ? '✓ Verified' : '—',
        ];
        @endphp

        <table style="width:100%;border-collapse:collapse;">
        @foreach($rows as $label => $value)
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:12px 0;font-size:13px;color:#64748b;font-weight:600;width:45%;">{{ $label }}</td>
                <td style="padding:12px 0;font-size:13px;color:#0f172a;font-weight:700;text-align:right;word-break:break-all;">{{ $value }}</td>
            </tr>
        @endforeach
        </table>

        {{-- Paid By --}}
        <div style="margin-top:24px;background:#f8fafc;border-radius:10px;padding:16px;">
            <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Paid By</div>
            <div style="font-size:14px;font-weight:700;color:#0f172a;">{{ auth()->user()->name ?: auth()->user()->email }}</div>
            <div style="font-size:13px;color:#64748b;">{{ auth()->user()->email }}</div>
        </div>

        {{-- Footer --}}
        <div style="margin-top:24px;text-align:center;border-top:1px dashed #e2e8f0;padding-top:20px;">
            <div style="font-size:13px;font-weight:700;color:#0F6B3E;margin-bottom:4px;">MSAS Livestock & Agro Services</div>
            <div style="font-size:12px;color:#94a3b8;">This receipt confirms your payment. Keep it for your records.</div>
            <div style="font-size:11px;color:#cbd5e1;margin-top:8px;">Generated: {{ now()->format('d M Y, H:i') }}</div>
        </div>
    </div>
</div>
</div>

</x-app-layout>
