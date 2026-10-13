{{-- Phase 11: scan outcome block for the web history page. Same data and
     wording as the mobile result screen, from the same backend record. --}}
@php
    $outcome = $diagnosis->assessment_outcome;
    $issueLabels = [
        'blurred' => __('The photo is blurred'), 'too_dark' => __('The photo is too dark'),
        'overexposed_or_glare' => __('Too bright, or glare on the subject'), 'too_far' => __('The subject is too far away'),
        'too_close' => __('Too close: the subject is cut off'), 'subject_partly_visible' => __('Only part of the subject is visible'),
        'subject_not_visible' => __('The subject is not visible'), 'low_resolution' => __('The image resolution is too low'),
        'colour_distorted' => __('Colours look distorted'), 'screen_or_printed_photo' => __('This looks like a photo of a screen or print'),
        'multiple_subjects' => __('Several subjects are mixed in one photo'),
    ];
@endphp

@if(in_array($outcome, ['poor_image', 'unsupported_subject', 'uncertain'], true))
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4" data-outcome="{{ $outcome }}">
    <div class="text-sm font-extrabold text-amber-900 flex items-center gap-2 mb-1">
        <i class="fa-solid {{ $outcome === 'uncertain' ? 'fa-circle-question' : ($outcome === 'poor_image' ? 'fa-camera' : 'fa-magnifying-glass') }}"></i>
        @if($outcome === 'poor_image') {{ __('Photo too unclear to assess') }}
        @elseif($outcome === 'unsupported_subject') {{ __('This photo does not show what this scan needs') }}
        @else {{ __('Inconclusive result') }} @endif
    </div>
    @if($diagnosis->what_cannot_be_confirmed)
        <p class="text-xs text-amber-900 mb-2">{{ $diagnosis->what_cannot_be_confirmed }}</p>
    @endif
    @if(!empty($diagnosis->alternative_findings))
        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 mt-2 mb-1">{{ __('Other possibilities') }}</div>
        <ul class="text-xs text-amber-900 list-disc ml-4 space-y-0.5">
            @foreach($diagnosis->alternative_findings as $alt)
                <li><strong>{{ $alt['name'] ?? '' }}</strong>@if(!empty($alt['why_considered'])): {{ $alt['why_considered'] }}@endif</li>
            @endforeach
        </ul>
    @endif
    @if(!empty($diagnosis->image_issues))
        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 mt-2 mb-1">{{ __('What was wrong with the photo') }}</div>
        <ul class="text-xs text-amber-900 list-disc ml-4 space-y-0.5">
            @foreach($diagnosis->image_issues as $issue)<li>{{ $issueLabels[$issue] ?? str_replace('_', ' ', $issue) }}</li>@endforeach
        </ul>
    @endif
    @if(!empty($diagnosis->photo_tips))
        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 mt-2 mb-1">{{ $outcome === 'uncertain' ? __('To get a clearer answer') : __('How to retake the photo') }}</div>
        <ol class="text-xs text-amber-900 list-decimal ml-4 space-y-0.5">
            @foreach($diagnosis->photo_tips as $tip)<li>{{ $tip }}</li>@endforeach
        </ol>
    @endif
    <p class="text-xs text-amber-800 mt-2 italic">
        {{ $outcome === 'uncertain' ? __('No treatment is recommended until the problem is confirmed.') : __('No treatment is suggested because the photo could not be assessed.') }}
    </p>
    <a href="{{ route('diagnostics.scan') }}" class="inline-flex items-center gap-1 mt-3 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 rounded-lg">
        <i class="fa-solid fa-camera"></i> {{ __('Retake photo') }}
    </a>
</div>
@endif

@if($diagnosis->organic_options)
<div class="bg-lime-50 border border-lime-100 rounded-xl p-3">
    <div class="text-[10px] font-bold text-lime-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
        <i class="fa-solid fa-leaf text-[9px]"></i> {{ __('Organic / non-chemical options') }}
    </div>
    <p class="text-xs text-lime-900 leading-relaxed">{{ $diagnosis->organic_options }}</p>
</div>
@endif
