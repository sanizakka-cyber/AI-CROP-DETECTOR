<x-guest-layout>
    <div style="max-width:480px;margin:0 auto;">
        <h2 style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:#0f172a;margin:0 0 6px;">Replace Document</h2>
        <p style="font-size:13px;color:#64748b;margin:0 0 18px;">{{ $document->document_label }}</p>

        @if($document->review_reason)
        <div style="padding:12px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:16px;">
            <p style="font-size:11px;font-weight:700;color:#92400e;margin:0 0 3px;">What needs to change</p>
            <p style="font-size:12px;color:#92400e;margin:0;line-height:1.6;">{{ $document->review_reason }}</p>
        </div>
        @endif

        @if ($errors->any())
        <div style="padding:9px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12px;color:#dc2626;font-weight:600;margin-bottom:14px;">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ url()->full() }}" enctype="multipart/form-data">
            @csrf
            <label class="fl" style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;">Upload replacement (PDF, JPG or PNG, max 5MB)</label>
            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="form-input" style="padding:8px;margin-bottom:16px;">

            <button type="submit" class="btn-primary" style="width:100%;">Submit Replacement</button>
        </form>
    </div>
</x-guest-layout>
