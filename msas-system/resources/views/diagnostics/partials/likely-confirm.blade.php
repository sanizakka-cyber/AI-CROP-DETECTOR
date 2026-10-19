{{-- Buffer zone (config/diagnosis.php confirm_below): the result is shown as
     "Likely X" with a request to confirm. Same rule as the app
     (assessment.needsConfirmation). Expects $diagnosis; $pdf for the report. --}}
@if(!empty($pdf))
<div class="low-conf-box" data-needs-confirmation="1" style="display:block">
    <p style="font-weight:700">{{ __('The AI thinks this is likely, but is not fully sure. Please confirm with a vet or extension officer before treating.') }}</p>
</div>
@else
<p class="text-xs font-bold text-amber-800 mt-1" data-needs-confirmation="1">
    <i class="fa-solid fa-user-doctor"></i> {{ __('The AI thinks this is likely, but is not fully sure. Please confirm with a vet or extension officer before treating.') }}
</p>
@endif
