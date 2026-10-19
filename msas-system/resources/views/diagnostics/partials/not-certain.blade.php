{{-- Low-confidence rule (config/diagnosis.php min_confidence): shown instead
     of a single diagnosis. Same data as the app's "Not certain" card
     (assessment.notCertain / possibleConditions). Expects $diagnosis; $pdf for the report. --}}
@if(!empty($pdf))
<div class="low-conf-box" data-not-certain="1" style="display:block">
    <p style="font-weight:800;font-size:14px;margin-bottom:4px">{{ __('Not certain') }}</p>
    <p>{{ __('The AI is not sure enough to give one diagnosis. These are the possible conditions:') }}</p>
    <ul style="margin:4px 0 4px 16px;list-style:disc">
        @foreach($diagnosis->possible_conditions as $name)<li>{{ $name }}</li>@endforeach
    </ul>
    <p style="font-weight:700">{{ __('Please confirm with a vet or extension officer before treating.') }}</p>
</div>
@else
<div class="bg-amber-50 border border-amber-300 rounded-xl px-4 py-3" data-not-certain="1">
    <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 mb-0.5">{{ __('Diagnosis') }}</div>
    <h3 class="text-lg font-extrabold text-amber-900 leading-tight flex items-center gap-2">
        <i class="fa-solid fa-circle-question"></i> {{ __('Not certain') }}
    </h3>
    <p class="text-xs text-amber-900 mt-1">{{ __('The AI is not sure enough to give one diagnosis. These are the possible conditions:') }}</p>
    <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 mt-2 mb-1">{{ __('Possible conditions') }}</div>
    <ul class="text-sm text-amber-900 list-disc ml-5 space-y-0.5">
        @foreach($diagnosis->possible_conditions as $name)<li>{{ $name }}</li>@endforeach
    </ul>
    <p class="text-xs font-bold text-amber-900 mt-2"><i class="fa-solid fa-user-doctor"></i> {{ __('Please confirm with a vet or extension officer before treating.') }}</p>
</div>
@endif
