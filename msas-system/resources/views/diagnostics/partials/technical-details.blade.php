{{-- The unsimplified record: Latin and technical names, condition labels and how the
     repeat analyses voted. Staff only on the web page ($rv['technical'] is null for
     farmers); always in the PDF's technical appendix. Expects $tech; $pdf for the report. --}}
@php
    $rows = array_filter([
        __('Technical name')   => $tech['technical_name'] ?? null,
        __('Scientific name')  => $tech['scientific_name'] ?? null,
        __('Detected part')    => $tech['detected_part'] ?? null,
        __('Health status')    => $tech['health_status'] ?? null,
        __('AI explanation')   => $tech['explanation'] ?? null,
        __('Condition label')  => isset($tech['condition_label']) ? $tech['condition_label'] . (isset($tech['condition_category']) ? ' (' . $tech['condition_category'] . ')' : '') : null,
        __('Stored severity')  => $tech['stored_severity'] ?? null,
        __('Stored urgency')   => $tech['stored_urgency'] ?? null,
        __('Vote')             => $tech['vote_method'] ?? null,
        __('Decision')         => $tech['confidence_decision'] ?? null,
        __('Contract')         => $tech['contract_version'] ?? null,
    ], fn ($v) => $v !== null && $v !== '');
    $passes = [];
    foreach ($tech['pass_findings'] ?? [] as $i => $f) {
        $passes[] = trim($f . (isset($tech['pass_labels'][$i]) ? ' [' . $tech['pass_labels'][$i] . ']' : '')
            . (isset($tech['pass_confidences'][$i]) ? ' ' . $tech['pass_confidences'][$i] . '%' : ''));
    }
@endphp
@if(!empty($pdf))
<div class="section-card s-slate" style="margin-top:14px" data-technical-appendix="1">
    <div class="section-label">{{ __('Technical appendix') }}</div>
    @foreach($rows as $k => $v)<p><strong>{{ $k }}:</strong> {{ $v }}</p>@endforeach
    @if($passes)<p><strong>{{ __('Repeat analyses') }}:</strong> {{ implode('; ', $passes) }}</p>@endif
    @if(!empty($tech['alternatives']))
    <p><strong>{{ __('Other possibilities') }}:</strong>
        {{ collect($tech['alternatives'])->map(fn ($a) => trim(($a['name'] ?? '') . (!empty($a['condition_label']) ? ' [' . $a['condition_label'] . ']' : '')))->filter()->implode('; ') }}</p>
    @endif
</div>
@else
<details class="border border-slate-300 rounded-xl overflow-hidden" data-technical-details="1">
    <summary class="px-4 py-2.5 bg-slate-100 text-xs font-bold text-slate-700 cursor-pointer">
        <i class="fa-solid fa-screwdriver-wrench text-slate-500"></i> {{ __('Technical details') }} <span class="font-normal text-slate-500">({{ __('staff only') }})</span>
    </summary>
    <dl class="px-4 py-3 text-xs text-slate-700 grid grid-cols-1 sm:grid-cols-[max-content_1fr] gap-x-4 gap-y-1">
        @foreach($rows as $k => $v)
        <dt class="font-bold text-slate-500">{{ $k }}</dt><dd class="break-words">{{ $v }}</dd>
        @endforeach
        @if($passes)
        <dt class="font-bold text-slate-500">{{ __('Repeat analyses') }}</dt><dd class="break-words">{{ implode('; ', $passes) }}</dd>
        @endif
        @foreach($tech['alternatives'] ?? [] as $a)
        <dt class="font-bold text-slate-500">{{ __('Other possibility') }}</dt>
        <dd class="break-words">{{ $a['name'] ?? '' }}@if(!empty($a['condition_label'])) [{{ $a['condition_label'] }}]@endif @if(!empty($a['why_considered']))— {{ $a['why_considered'] }}@endif</dd>
        @endforeach
    </dl>
</details>
@endif
