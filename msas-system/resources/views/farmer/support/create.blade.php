<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">New Support Ticket</h2>
</x-slot>
<style>.msas-field:focus{outline:none;border-color:#0F6B3E!important;box-shadow:0 0 0 3px rgba(15,107,62,.15)}</style>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:680px;margin:0 auto;padding:0 20px;">
<form method="POST" action="{{ route('support.store') }}">
@csrf
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:28px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <x-form-field label="Subject" name="subject" required>
        <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Brief description of your issue" class="msas-field" style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;" required>
    </x-form-field>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
        <div>
            <label style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:6px;">Category</label>
            <select name="category" style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;background:#fff;">
                @foreach(['general'=>'General','technical'=>'Technical Issue','billing'=>'Billing & Payments','consultation'=>'Consultation','other'=>'Other'] as $val => $label)
                <option value="{{ $val }}" {{ old('category')===$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:6px;">Priority</label>
            <select name="priority" style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;background:#fff;">
                <option value="low">Low</option>
                <option value="normal" selected>Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>
    </div>
    <div style="margin-top:16px;">
        <label style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:6px;">Message <span style="color:#dc2626;">*</span></label>
        <textarea name="message" rows="6" placeholder="Describe your issue in detail..." class="msas-field" style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;resize:vertical;box-sizing:border-box;" required>{{ old('message') }}</textarea>
    </div>
    @if($errors->any())
    <div style="margin-top:12px;background:#fef2f2;border-radius:10px;padding:12px 16px;color:#dc2626;font-size:12px;">{{ $errors->first() }}</div>
    @endif
    <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" style="background:#0F6B3E;color:#fff;border:none;padding:12px 24px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Submit Ticket</button>
        <a href="{{ route('support.index') }}" style="background:#f1f5f9;color:#475569;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">Cancel</a>
    </div>
</div>
</form>
</div>
</div>
</x-app-layout>