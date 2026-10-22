{{-- The AI disclaimer, on every result (web history card and PDF report), same words
     as the app. On the web it comes with a button that opens the Request Vet (or
     agronomist) page with this scan attached; farmers only, as those pages are theirs.
     $pdf for the report styling. --}}
@if(!empty($pdf))
<p class="ai-disclaimer" data-ai-disclaimer="1" style="font-weight:700;margin-bottom:4px">{{ __(\App\Support\ResultView::DISCLAIMER) }}</p>
@else
<div class="flex flex-wrap items-center gap-x-3 gap-y-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5" data-ai-disclaimer="1">
    <p class="text-sm font-semibold text-slate-700 flex items-start gap-1.5 min-w-0 flex-1">
        <i class="fa-solid fa-circle-info text-sky-600 mt-0.5"></i> <span>{{ __(\App\Support\ResultView::DISCLAIMER) }}</span>
    </p>
    @if(isset($diagnosis) && auth()->user()?->role === 'farmer' && !$diagnosis->is_failed_scan)
    <a href="{{ route('diagnostics.ask-expert', $diagnosis) }}" data-ask-expert="1"
       class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-2 rounded-lg shrink-0">
        <i class="fa-solid fa-user-doctor"></i> {{ __(\App\Support\ResultView::ASK_EXPERT) }}
    </a>
    @endif
</div>
@endif
