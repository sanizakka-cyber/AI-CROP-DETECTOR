<x-app-layout>
    <x-slot name="header">Review spoken results</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-6 text-white">
            <p class="text-emerald-200 text-sm mb-1">Native-speaker review</p>
            <h1 class="text-2xl font-extrabold">Review packs</h1>
            <p class="text-emerald-100 text-sm mt-1">
                Each item is a real scan result (with personal details removed) as a farmer would read and hear it.
                Rate the translation, rate each voice, mark any wrong words, and choose the voice you prefer.
                The voices are labelled A, B and C so you do not know which is which.
            </p>
            @if($langs)
                <p class="text-emerald-200 text-xs mt-2">You review:
                    @foreach($langs as $code => $kind){{ $languages[$code] ?? $code }} ({{ strtolower(\App\Models\SpeechReviewer::KINDS[$kind] ?? $kind) }})@if(!$loop->last), @endif @endforeach
                </p>
            @endif
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            @forelse($packs as $p)
                <a href="{{ route('admin.speech.review.pack', $p) }}" class="flex items-center justify-between py-3 border-b border-slate-100 last:border-0 hover:bg-slate-50 px-2 rounded-lg">
                    <span>
                        <span class="font-semibold text-slate-800">{{ $p->title }}</span>
                        <span class="text-slate-400 text-sm ml-2">{{ $languages[$p->lang] ?? $p->lang }}</span>
                    </span>
                    <span class="text-sm {{ $p->done >= $p->items_count ? 'text-emerald-700 font-semibold' : 'text-slate-500' }}">{{ $p->done }} / {{ $p->items_count }} reviewed</span>
                </a>
            @empty
                <p class="text-slate-500 text-sm">No review packs yet for your languages.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
