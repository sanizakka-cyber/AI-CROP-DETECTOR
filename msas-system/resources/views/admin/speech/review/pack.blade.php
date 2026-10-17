<x-app-layout>
    <x-slot name="header">{{ $pack->title }}</x-slot>

    <div class="space-y-6">
        <a href="{{ route('admin.speech.review.index') }}" class="text-emerald-700 text-sm font-semibold">&larr; All packs</a>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h2 class="font-bold text-slate-800 text-lg mb-1">{{ $pack->title }}</h2>
            <p class="text-slate-500 text-sm mb-4">{{ $language?->name_english }} &middot; {{ $mine->count() }} of {{ $items->count() }} reviewed by you</p>
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-slate-500"><th class="py-2 pr-4">#</th><th class="pr-4">Item</th><th class="pr-4">Type</th><th>Your review</th></tr></thead>
                <tbody>
                @foreach($items as $it)
                    <tr class="border-t border-slate-100">
                        <td class="py-2 pr-4 text-slate-400">{{ $it->position }}</td>
                        <td class="pr-4"><a class="text-emerald-700 font-semibold" href="{{ route('admin.speech.review.item', $it) }}">{{ \App\Models\SpeechReviewItem::KINDS[$it->kind] ?? $it->kind }}</a></td>
                        <td class="pr-4">{{ $it->scan_type ? (\App\Models\SpeechReviewItem::SCAN_TYPES[$it->scan_type] ?? $it->scan_type) : '—' }}</td>
                        <td>{!! isset($mine[$it->id]) ? '<span class="text-emerald-700">&#10003; done</span>' : '<span class="text-slate-400">to do</span>' !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
