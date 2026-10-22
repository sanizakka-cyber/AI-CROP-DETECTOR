{{-- Buffer zone (config/diagnosis.php confirm_below): the result is shown as
     "Likely X" with a request to confirm (a vet for livestock, an agronomist or
     extension officer otherwise). Same rule as the app
     (assessment.needsConfirmation). Expects $diagnosis; $pdf for the report. --}}
@php $expert = \App\Support\ResultView::expert($diagnosis); @endphp
@if(!empty($pdf))
<div class="low-conf-box" data-needs-confirmation="1" style="display:block">
    <p style="font-weight:700">{{ $expert['likely_confirm'] }}</p>
</div>
@else
<p class="text-xs font-bold text-amber-800 mt-1" data-needs-confirmation="1">
    <i class="fa-solid fa-user-doctor"></i> {{ $expert['likely_confirm'] }}
</p>
@endif
