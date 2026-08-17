@props(['errors' => []])

@if(!empty($errors))
<div {{ $attributes->merge(['class' => 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 mb-4 flex items-start gap-3']) }}>
    <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="text-sm font-semibold text-red-800">Some dashboard data couldn't be loaded.</p>
        <p class="text-xs text-red-600 mt-0.5">
            Affected: {{ implode(', ', $errors) }}. The figures below may be showing 0 instead of the real value — please refresh, or try again shortly.
        </p>
    </div>
</div>
@endif
