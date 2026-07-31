@props(['label', 'name', 'required' => false])

<div style="margin-top:16px;">
    <label for="{{ $name }}" style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:6px;">
        {{ $label }}@if($required)<span style="color:#dc2626;margin-left:2px;">*</span>@endif
    </label>
    {{ $slot }}
</div>
