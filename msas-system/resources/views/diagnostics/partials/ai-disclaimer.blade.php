{{-- The standard AI disclaimer, on every result (web history card and PDF report),
     same words as the app's result screens. $pdf for the report styling. --}}
@if(!empty($pdf))
<p class="ai-disclaimer" data-ai-disclaimer="1" style="font-weight:700;margin-bottom:4px">{{ __('AI guidance can be wrong; confirm with an expert.') }}</p>
@else
<p class="text-xs font-semibold text-slate-600 flex items-center gap-1.5" data-ai-disclaimer="1">
    <i class="fa-solid fa-triangle-exclamation text-amber-500"></i> {{ __('AI guidance can be wrong; confirm with an expert.') }}
</p>
@endif
