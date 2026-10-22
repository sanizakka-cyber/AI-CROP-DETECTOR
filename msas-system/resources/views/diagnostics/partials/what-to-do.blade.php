{{-- "What to do now": 2 to 4 simple steps on every judged result (App\Support\ResultView).
     Fixed steps are translated; the AI's own steps are already in the farmer's language.
     Expects $rv; $pdf for the report. --}}
@if(!empty($rv['steps']))
@if(!empty($pdf))
<div class="section-card s-blue" style="margin-bottom:10px" data-what-to-do="1">
    <div class="section-label">{{ __('What to do now') }}</div>
    <ol style="margin:0 0 0 16px">
        @foreach($rv['steps'] as $step)<li data-step="1">{{ $step['text'] }}</li>@endforeach
    </ol>
</div>
@else
<div class="bg-blue-600 text-white rounded-xl p-4" data-what-to-do="1">
    <div class="text-[10px] font-bold uppercase tracking-wider mb-2 opacity-90 flex items-center gap-1">
        <i class="fa-solid fa-list-check text-[9px]"></i> {{ __('What to do now') }}
    </div>
    <ol class="text-sm leading-relaxed font-medium list-decimal ml-5 space-y-1">
        @foreach($rv['steps'] as $step)<li data-step="1">{{ $step['text'] }}</li>@endforeach
    </ol>
</div>
@endif
@endif
