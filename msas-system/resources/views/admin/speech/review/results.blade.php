<x-app-layout>
    <x-slot name="header">Review results: {{ $language->name_english }}</x-slot>

    <div class="space-y-6">
        <a href="{{ route('admin.speech.index') }}" class="text-emerald-700 text-sm font-semibold">&larr; Spoken results administration</a>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h2 class="font-bold text-slate-800 text-lg mb-1">Go-live rule</h2>
            <p class="text-sm mb-4 {{ $goLive['passes'] ? 'text-emerald-700' : 'text-amber-700' }} font-semibold">
                {{ $goLive['passes'] ? $language->name_english . ' meets every condition and can be set live.' : $language->name_english . ' cannot go live yet.' }}
            </p>
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-slate-500"><th class="py-2 pr-4">Condition</th><th class="pr-4">Needed</th><th class="pr-4">Now</th><th></th></tr></thead>
                <tbody>
                @foreach($goLive['checks'] as $c)
                    <tr class="border-t border-slate-100">
                        <td class="py-2 pr-4">{{ $c['label'] }}</td>
                        <td class="pr-4">{{ $c['required'] }}</td>
                        <td class="pr-4">{{ $c['actual'] ?? '—' }}</td>
                        <td>{!! $c['ok'] ? '<span class="text-emerald-700">&#10003;</span>' : '<span class="text-red-700">&#10007;</span>' !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="text-xs text-slate-400 mt-3">The numbers are set in config/speech.php (go_live) and the SPEECH_GO_LIVE_* environment variables.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 overflow-x-auto">
            <h2 class="font-bold text-slate-800 text-lg mb-1">Voices (unblinded)</h2>
            <p class="text-sm text-slate-500 mb-4">{{ $reviewCount }} reviews. Reviewers saw these only as A, B and C, shuffled per item.</p>
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-slate-500">
                    <th class="py-2 pr-4">Voice</th><th class="pr-4">Licence</th><th class="pr-4">Rated</th>
                    <th class="pr-4">Naturalness</th><th class="pr-4">Pronunciation</th><th class="pr-4">Speed (3 = right)</th>
                    <th class="pr-4">Preferred by native speakers</th><th class="pr-4">by experts</th>
                </tr></thead>
                <tbody>
                @forelse($voices as $name => $v)
                    <tr class="border-t border-slate-100">
                        <td class="py-2 pr-4 font-semibold">{{ $name }}
                            @if($language->provider === $v['candidate']['provider'] && $language->voice === $v['candidate']['voice'])<span class="text-xs text-emerald-700 ml-1">(configured)</span>@endif
                        </td>
                        <td class="pr-4">{{ $v['candidate']['license'] ?? '' }}</td>
                        <td class="pr-4">{{ $v['rated'] }}</td>
                        <td class="pr-4">{{ $v['avg']['naturalness'] ?? '—' }}</td>
                        <td class="pr-4">{{ $v['avg']['pronunciation'] ?? '—' }}</td>
                        <td class="pr-4">{{ $v['avg']['speed'] ?? '—' }}</td>
                        <td class="pr-4">{{ $v['preferred']['native'] }}</td>
                        <td class="pr-4">{{ $v['preferred']['expert'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-3 text-slate-500">No reviews yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h2 class="font-bold text-slate-800 text-lg mb-3">Errors marked by reviewers</h2>
            @forelse($markedErrors as $r)
                @foreach($r->errors as $e)
                    <div class="border-t border-slate-100 py-2 text-sm {{ in_array($e['type'], \App\Models\SpeechReview::BLOCKING, true) ? 'bg-red-50' : '' }}">
                        <span class="font-semibold">{{ \App\Models\SpeechReview::ERROR_TYPES[$e['type']] ?? $e['type'] }}</span>
                        &middot; "{{ $e['quote'] }}" @if(!empty($e['note'])) &rarr; {{ $e['note'] }} @endif
                        <span class="text-slate-400">({{ $r->user?->name }}, {{ $r->reviewer_kind }}, {{ $r->item->pack->title }} item {{ $r->item->position }})</span>
                    </div>
                @endforeach
            @empty
                <p class="text-sm text-slate-500">None.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
