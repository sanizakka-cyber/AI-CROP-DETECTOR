{{-- The scan a vet/agronomist request is about, when opened with "Request Vet /
     Ask an expert" from a result (App\Support\ScanAttachment). Sends its id with
     the form; the expert sees the photo and the AI's result. Expects $attachedScan. --}}
@if(!empty($attachedScan))
@php $scanView = $attachedScan->resultView(); @endphp
<input type="hidden" name="diagnosis_id" value="{{ $attachedScan->id }}">
<div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-lg p-2.5 mb-4" data-attached-scan="{{ $attachedScan->id }}">
    @if($attachedScan->image_thumbnail)
    <img src="{{ $attachedScan->image_thumbnail }}" alt="" class="w-12 h-12 rounded-md object-cover shrink-0">
    @endif
    <div class="min-w-0 text-xs text-emerald-900">
        <div class="font-bold uppercase tracking-wide text-[10px] text-emerald-700">{{ __('Scan attached') }}</div>
        <div class="font-semibold break-words">{{ $scanView['headline'] }}</div>
        <div class="text-emerald-700">{{ $attachedScan->scan_ref }} · {{ $attachedScan->created_at?->format('j M Y') }}</div>
    </div>
</div>
@endif
