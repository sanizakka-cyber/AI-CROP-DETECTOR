{{-- Phase 11: scan outcome + sample collection location for the downloadable report.
     Mirrors what the history page and the mobile app show for the same record. --}}
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
    $loc = $diagnosis->collectionLocation;
@endphp

@if(in_array($outcome, ['poor_image', 'unsupported_subject', 'uncertain'], true))
<div class="section-card s-orange" style="margin-bottom:10px">
    <div class="section-label">
        @if($outcome === 'poor_image') {{ __('Photo could not be assessed') }}
        @elseif($outcome === 'unsupported_subject') {{ __('Photo does not show the scanned subject') }}
        @else {{ __('Inconclusive result') }} @endif
    </div>
    @if($diagnosis->what_cannot_be_confirmed)<p>{{ $diagnosis->what_cannot_be_confirmed }}</p>@endif
    @if(!empty($diagnosis->alternative_findings))
        <p><strong>{{ __('Other possibilities') }}:</strong>
            {{ collect($diagnosis->alternative_findings)->map(fn ($a) => ($a['name'] ?? '') . (!empty($a['why_considered']) ? ' (' . $a['why_considered'] . ')' : ''))->filter()->implode('; ') }}
        </p>
    @endif
    @if(!empty($diagnosis->image_issues))
        <p><strong>{{ __('Photo issues') }}:</strong> {{ collect($diagnosis->image_issues)->map(fn ($i) => $issueLabels[$i] ?? str_replace('_', ' ', $i))->implode('; ') }}</p>
    @endif
    @if(!empty($diagnosis->photo_tips))
        <p><strong>{{ __('How to retake the photo') }}:</strong> {{ implode(' ', array_map(fn ($t, $n) => ($n + 1) . '. ' . $t, $diagnosis->photo_tips, array_keys($diagnosis->photo_tips))) }}</p>
    @endif
    @if($outcome !== 'uncertain' || !$diagnosis->recommended_medication)
        <p>{{ __('No treatment is suggested for this result.') }}</p>
    @endif
</div>
@endif

@if($diagnosis->organic_options)
<div class="section-card s-lime" style="margin-bottom:10px">
    <div class="section-label">{{ __('Organic / non-chemical options') }}</div>
    <p>{{ $diagnosis->organic_options }}</p>
</div>
@endif

<div class="section-card s-slate" style="margin-bottom:10px">
    <div class="section-label">{{ __('Sample Collection Location') }}</div>
    @if(!$loc)
        <p>{{ __('No collection location was recorded for this scan.') }}</p>
    @else
        <p>
            @if($loc->state){{ __('State') }}: <strong>{{ $loc->state }}</strong> @endif
            @if($loc->lga) &middot; {{ __('LGA') }}: <strong>{{ $loc->lga }}</strong> @endif
            @if($loc->ward) &middot; {{ __('Ward') }}: <strong>{{ $loc->ward }}</strong> @endif
            @if($loc->community) &middot; {{ __('Community') }}: <strong>{{ $loc->community }}</strong> @endif
            @if($loc->postal_code) &middot; {{ __('Postal Code') }}: <strong>{{ $loc->postal_code }}</strong> @endif
        </p>
        @if($loc->address_landmark)<p>{{ __('Address/Landmark') }}: {{ $loc->address_landmark }}</p>@endif
        @if($loc->hasCoordinates())
            <p>{{ __('Coordinates') }}: {{ number_format($loc->latitude, 6) }}, {{ number_format($loc->longitude, 6) }}@if($loc->accuracy_meters) (±{{ round($loc->accuracy_meters) }}m)@endif</p>
        @endif
        <p>
            {{ $loc->provenanceLabel }}
            @if($loc->verification_status === 'boundary_mismatch_flagged') &middot; {{ __('Location outside expected range — unverified') }} @endif
            @if($loc->differs_from_scan_location) &middot; {{ __('Scanned at a different location than collected') }} @endif
            @if($loc->collected_at) &middot; {{ __('Collected') }}: {{ $loc->collected_at->format('M j, Y H:i') }} @endif
        </p>
    @endif
</div>
