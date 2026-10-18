<x-app-layout>
    <x-slot name="header">Listen to your result</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-6 text-white">
            <p class="text-emerald-200 text-sm mb-1">Administration</p>
            <h1 class="text-2xl font-extrabold">Spoken results: languages and voices</h1>
            <p class="text-emerald-100 text-sm mt-1">A language reaches farmers only when it is <strong>live</strong>. It can go live only when it meets the go-live rule below: enough results reviewed by a native speaker and an extension officer or vet, good ratings, and no dose or chemical errors.</p>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <a href="{{ route('admin.speech.glossary', '*') }}" class="inline-block text-emerald-700 font-semibold text-sm">Protected product names and pronunciations for all languages &rarr;</a>

        {{-- Quality, last 30 days --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 overflow-x-auto">
            <h3 class="font-bold text-slate-800 text-lg mb-4">Quality (last 30 days)</h3>
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-slate-500">
                    <th class="py-2 pr-4">Language</th><th class="pr-4">Status</th><th class="pr-4">Reviewed</th>
                    <th class="pr-4">Translated</th><th class="pr-4">Rejected by checks</th><th class="pr-4">Audio ok / failed</th>
                    <th class="pr-4">Avg. generation</th><th class="pr-4">"Audio was clear"</th>
                </tr></thead>
                <tbody>
                @foreach($languages as $l)
                    @php $s = $stats[$l->code]; @endphp
                    <tr class="border-t border-slate-100">
                        <td class="py-2 pr-4 font-semibold">{{ $l->name_native }} <span class="text-slate-400">({{ $l->code }})</span></td>
                        <td class="pr-4">{{ str_replace('_', ' ', $l->status) }}</td>
                        <td class="pr-4">{{ $s['reviewed'] }} / {{ $threshold }}</td>
                        <td class="pr-4">{{ $s['translated'] }}</td>
                        <td class="pr-4 {{ $s['rejected'] ? 'text-amber-700 font-semibold' : '' }}">{{ $s['rejected'] }}</td>
                        <td class="pr-4">{{ $s['audio_ready'] }} / <span class="{{ $s['audio_failed'] ? 'text-red-700 font-semibold' : '' }}">{{ $s['audio_failed'] }}</span></td>
                        <td class="pr-4">{{ $s['latency_ms'] ? number_format($s['latency_ms'] / 1000, 1) . ' s' : '—' }}</td>
                        <td class="pr-4">{{ $s['clear_pct'] !== null ? $s['clear_pct'] . '% of ' . $s['feedback'] : 'no feedback yet' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>


        {{-- Native-speaker review: go-live rule, reviewers and packs --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-5 text-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-lg">Native-speaker review</h3>
                <a href="{{ route('admin.speech.review.index') }}" class="text-emerald-700 font-semibold">Review packs &rarr;</a>
            </div>
            <table class="min-w-full">
                <thead><tr class="text-left text-slate-500"><th class="py-2 pr-4">Language</th><th class="pr-4">Go-live rule</th><th class="pr-4">Still missing</th><th></th></tr></thead>
                <tbody>
                @foreach($languages as $l)
                    @php $g = $goLive[$l->code]; @endphp
                    <tr class="border-t border-slate-100">
                        <td class="py-2 pr-4 font-semibold">{{ $l->name_english }}</td>
                        <td class="pr-4">{!! $g['passes'] ? '<span class="text-emerald-700 font-semibold">meets it</span>' : '<span class="text-amber-700">not yet</span>' !!}</td>
                        <td class="pr-4 text-slate-500">{{ $g['passes'] ? '—' : \App\Services\Speech\GoLiveRule::failures($g) }}</td>
                        <td><a class="text-emerald-700" href="{{ route('admin.speech.review.results', $l->code) }}">Results &rarr;</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div>
                <h4 class="font-semibold text-slate-700 mb-2">Reviewers</h4>
                @forelse($reviewers as $r)
                    <div class="flex items-center justify-between py-1 border-t border-slate-100 {{ $r->active ? '' : 'opacity-50' }}">
                        <span>{{ $r->user?->name }} &lt;{{ $r->user?->email }}&gt; &middot; {{ $r->lang }} &middot; {{ \App\Models\SpeechReviewer::KINDS[$r->kind] ?? $r->kind }}{{ $r->active ? '' : ' (removed)' }}</span>
                        @if($r->active)
                        <form method="POST" action="{{ route('admin.speech.reviewers.destroy', $r) }}">@csrf @method('DELETE')<button class="text-red-700">Remove</button></form>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">No reviewers yet. Create their accounts, then assign them here: one native speaker and one extension officer or vet per language.</p>
                @endforelse
                <form method="POST" action="{{ route('admin.speech.reviewers.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-3">
                    @csrf
                    <input name="email" type="email" placeholder="Account email" class="rounded-lg border-slate-300" required>
                    <select name="lang" class="rounded-lg border-slate-300">@foreach($languages as $l)<option value="{{ $l->code }}">{{ $l->name_english }}</option>@endforeach</select>
                    <select name="kind" class="rounded-lg border-slate-300">@foreach(\App\Models\SpeechReviewer::KINDS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
                    <button class="bg-slate-800 text-white font-bold px-4 py-2 rounded-lg">Assign reviewer</button>
                </form>
            </div>

            <div>
                <h4 class="font-semibold text-slate-700 mb-2">Review packs</h4>
                @forelse($packs as $p)
                    <div class="py-1 border-t border-slate-100"><a class="text-emerald-700" href="{{ route('admin.speech.review.pack', $p) }}">{{ $p->title }}</a> &middot; {{ $p->lang }} &middot; {{ $p->items_count }} items &middot; {{ count($p->candidates) }} voices</div>
                @empty
                    <p class="text-slate-500">No packs yet. Build one with <code>php artisan speech:build-review-pack ha</code> (see docs/listen-to-result.md).</p>
                @endforelse
            </div>
        </div>

        {{-- Languages --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            @foreach($languages as $l)
            <form method="POST" action="{{ route('admin.speech.languages.update', $l) }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-3 text-sm">
                @csrf @method('PATCH')
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-lg">{{ $l->name_native }} <span class="text-slate-400 text-sm">{{ $l->code }}</span></h3>
                    <a href="{{ route('admin.speech.glossary', $l->code) }}" class="text-emerald-700 font-semibold">Glossary &amp; pronunciation &rarr;</a>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label>English name<input name="name_english" value="{{ $l->name_english }}" class="w-full rounded-lg border-slate-300" required></label>
                    <label>Name in the language<input name="name_native" value="{{ $l->name_native }}" class="w-full rounded-lg border-slate-300" required></label>
                    <label>Status
                        <select name="status" class="w-full rounded-lg border-slate-300">
                            @foreach(\App\Models\SpeechLanguage::STATUSES as $st)<option value="{{ $st }}" @selected($l->status === $st)>{{ str_replace('_', ' ', $st) }}</option>@endforeach
                        </select>
                    </label>
                    <label>Speaking rate<input name="rate" type="number" step="0.05" min="0.5" max="1.5" value="{{ $l->rate }}" class="w-full rounded-lg border-slate-300" required></label>
                    <label class="flex items-center gap-2" data-audio-toggle="{{ $l->code }}">
                        <input type="hidden" name="audio_enabled" value="0">
                        <input type="checkbox" name="audio_enabled" value="1" @checked($l->audio_enabled)>
                        Audio on (off = text only, Listen hidden)
                    </label>
                    <label>Voice provider
                        <select name="provider" class="w-full rounded-lg border-slate-300">
                            <option value="">None (text only)</option>
                            @foreach($providers as $key => $p)<option value="{{ $key }}" @selected($l->provider === $key)>{{ $p['label'] }} — {{ $p['license'] }}</option>@endforeach
                        </select>
                    </label>
                    <label>Voice<input name="voice" value="{{ $l->voice }}" list="voices-{{ $l->code }}" class="w-full rounded-lg border-slate-300"></label>
                    <label>Fallback provider
                        <select name="fallback_provider" class="w-full rounded-lg border-slate-300">
                            <option value="">None</option>
                            @foreach($providers as $key => $p)<option value="{{ $key }}" @selected($l->fallback_provider === $key)>{{ $p['label'] }}</option>@endforeach
                        </select>
                    </label>
                    <label>Fallback voice<input name="fallback_voice" value="{{ $l->fallback_voice }}" list="voices-{{ $l->code }}" class="w-full rounded-lg border-slate-300"></label>
                </div>
                <datalist id="voices-{{ $l->code }}">
                    @foreach($providers as $p)@foreach(($p['voices'][$l->code] ?? []) as $v)<option value="{{ $v }}">@endforeach @endforeach
                </datalist>
                <label class="block">Reviewed safety note (optional; otherwise translated with each result)
                    <textarea name="safety_note" rows="2" class="w-full rounded-lg border-slate-300">{{ $l->safety_note }}</textarea>
                </label>
                <input type="hidden" name="sort" value="{{ $l->sort }}">
                <button class="bg-emerald-700 text-white font-bold px-4 py-2 rounded-lg">Save</button>
            </form>
            @endforeach
        </div>

        {{-- Add a language --}}
        <form method="POST" action="{{ route('admin.speech.languages.store') }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-3 text-sm">
            @csrf
            <h3 class="font-bold text-slate-800 text-lg">Add a language</h3>
            <p class="text-slate-500">New languages start as <strong>draft</strong>: add glossary terms, build a review pack, and set it live once it meets the go-live rule.</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <label>Code (e.g. pcm, ff, kr, tiv)<input name="code" class="w-full rounded-lg border-slate-300" required></label>
                <label>English name<input name="name_english" class="w-full rounded-lg border-slate-300" required></label>
                <label>Name in the language<input name="name_native" class="w-full rounded-lg border-slate-300" required></label>
                <label>Speaking rate<input name="rate" type="number" step="0.05" value="0.9" class="w-full rounded-lg border-slate-300" required></label>
                <label>Voice provider
                    <select name="provider" class="w-full rounded-lg border-slate-300">
                        <option value="">None (text only)</option>
                        @foreach($providers as $key => $p)<option value="{{ $key }}">{{ $p['label'] }}</option>@endforeach
                    </select>
                </label>
                <label>Voice<input name="voice" class="w-full rounded-lg border-slate-300"></label>
            </div>
            <button class="bg-slate-800 text-white font-bold px-4 py-2 rounded-lg">Add as draft</button>
        </form>

        @if($recentComments->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-sm">
            <h3 class="font-bold text-slate-800 text-lg mb-3">Recent listener comments</h3>
            @foreach($recentComments as $c)
                <div class="py-2 border-t border-slate-100"><span class="font-semibold">{{ $c->lang }} · {{ $c->voice }}</span> · {{ $c->clear ? '👍' : '👎' }} — {{ $c->comment }}</div>
            @endforeach
        </div>
        @endif
    </div>
</x-app-layout>
