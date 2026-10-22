{{-- Inconclusive result (App\Support\ResultView): the AI gave no single finding,
     or its confidence is below the Not-certain line. The headline never names one
     candidate; at most three possible causes in plain words; a retake button with
     photo tips. Same data as the app (assessment.result). Expects $diagnosis, $rv;
     $pdf for the report. --}}
@if(!empty($pdf))
<div class="low-conf-box" data-inconclusive="1" style="display:block">
    <p style="font-weight:800;font-size:14px;margin-bottom:4px" data-headline="1">{{ $rv['headline'] }}</p>
    @if($rv['possible_causes'])
    <ul style="margin:4px 0 4px 16px;list-style:disc">
        @foreach($rv['possible_causes'] as $name)<li data-possible-cause="1">{{ $name }}</li>@endforeach
    </ul>
    @endif
    <p style="font-weight:700">{{ __('Please confirm with a vet or extension officer before treating.') }}</p>
</div>
@else
<div class="bg-amber-50 border border-amber-300 rounded-xl px-4 py-3" data-inconclusive="1">
    <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 mb-0.5">{{ __('Diagnosis') }}</div>
    <h3 class="text-lg font-extrabold text-amber-900 leading-tight flex items-center gap-2" data-headline="1">
        <i class="fa-solid fa-circle-question"></i> {{ $rv['headline'] }}
    </h3>
    @if(\App\Support\FarmerText::plain($diagnosis->what_cannot_be_confirmed))
    <p class="text-xs text-amber-900 mt-1">{{ \App\Support\FarmerText::plain($diagnosis->what_cannot_be_confirmed) }}</p>
    @endif
    @if($rv['possible_causes'])
    <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 mt-2 mb-1">{{ __('Possible causes') }}</div>
    <ol class="text-sm text-amber-900 list-decimal ml-5 space-y-0.5">
        @foreach($rv['possible_causes'] as $name)<li data-possible-cause="1">{{ $name }}</li>@endforeach
    </ol>
    @endif
    <p class="text-xs font-bold text-amber-900 mt-2"><i class="fa-solid fa-user-doctor"></i> {{ __('Please confirm with a vet or extension officer before treating.') }} {{ __('No treatment is recommended until the problem is confirmed.') }}</p>

    @if($rv['retake']['show'])
    <div class="mt-3 bg-white/70 border border-amber-200 rounded-lg p-3" data-retake="1">
        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 mb-1">{{ __('Photo tips') }}</div>
        <ul class="text-xs text-amber-900 list-disc ml-4 space-y-0.5">
            @foreach($rv['retake']['tips'] as $tip)<li>{{ $tip['text'] }}</li>@endforeach
        </ul>
        <a href="{{ route('diagnostics.scan') }}" data-retake-button="1"
           class="inline-flex items-center gap-1.5 mt-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 px-3 py-2 rounded-lg">
            <i class="fa-solid fa-camera"></i> {{ __('Retake a closer photo') }}
        </a>
    </div>
    @endif
</div>
@endif
