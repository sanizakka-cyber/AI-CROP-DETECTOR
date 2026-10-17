<x-app-layout>
    <x-slot name="header">Review: item {{ $position }} of {{ $count }}</x-slot>

    @php
        $sections = ['observations' => 'Observations', 'explanation' => 'Explanation', 'recommendations' => 'Recommendations', 'safety_note' => 'Safety note'];
        $tr = $item->translation_json ?? [];
        $src = $item->source_json ?? [];
        $old = fn ($k, $d = null) => old($k, data_get($review, $k, $d));
        $existingErrors = old('errors', $review->errors ?? []);
    @endphp

    <div class="space-y-5">
        <div class="flex items-center justify-between text-sm">
            <a href="{{ route('admin.speech.review.pack', $pack) }}" class="text-emerald-700 font-semibold">&larr; {{ $pack->title }}</a>
            <span class="space-x-3">
                @if($prevId)<a class="text-slate-600" href="{{ route('admin.speech.review.item', $prevId) }}">&larr; Previous</a>@endif
                @if($nextId)<a class="text-slate-600" href="{{ route('admin.speech.review.item', $nextId) }}">Next &rarr;</a>@endif
            </span>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-400 font-bold">
                {{ \App\Models\SpeechReviewItem::KINDS[$item->kind] ?? $item->kind }}
                @if($item->scan_type) &middot; {{ \App\Models\SpeechReviewItem::SCAN_TYPES[$item->scan_type] ?? $item->scan_type }} @endif
                &middot; {{ $language?->name_english }}
            </p>

            <div class="grid md:grid-cols-2 gap-5 mt-3">
                <div>
                    <h3 class="font-bold text-slate-700 mb-2">Original ({{ strtoupper($item->source_lang ?? 'en') }})</h3>
                    <div class="bg-slate-50 rounded-xl p-4 text-sm leading-relaxed space-y-3">
                        @if($item->kind === 'scan')
                            @foreach($sections as $key => $title)
                                @php $v = $src[$key] ?? null; @endphp
                                @if($v)
                                    <div><h4 class="text-xs font-bold uppercase text-slate-500">{{ $title }}</h4>
                                        @if(is_array($v))<ul class="list-disc ml-5">@foreach($v as $x)<li>{{ $x }}</li>@endforeach</ul>@else<p>{{ $v }}</p>@endif
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <ul class="space-y-1">@foreach($tr['rows'] ?? [] as $row)<li>{{ $row['source'] ?? '' }}</li>@endforeach</ul>
                        @endif
                    </div>
                </div>
                <div>
                    <h3 class="font-bold text-slate-700 mb-2">What the farmer reads and hears</h3>
                    <div id="review-translation" class="bg-emerald-50 rounded-xl p-4 text-base leading-relaxed space-y-3">
                        @if($item->kind === 'scan')
                            @foreach($sections as $key => $title)
                                @php $v = $tr[$key] ?? null; @endphp
                                @if($v)
                                    <div data-section="{{ $key }}"><h4 class="text-xs font-bold uppercase text-emerald-700">{{ $title }}</h4>
                                        @if(is_array($v))<ul class="list-disc ml-5">@foreach($v as $x)<li>{{ $x }}</li>@endforeach</ul>@else<p>{{ $v }}</p>@endif
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <ul class="space-y-1" data-section="{{ $item->kind }}">@foreach($tr['rows'] ?? [] as $row)<li>{{ $row['text'] ?? '' }}</li>@endforeach</ul>
                        @endif
                    </div>
                    <button type="button" id="mark-selection" class="mt-2 text-sm px-3 py-1.5 bg-red-600 text-white rounded-lg">Mark selected text as an error</button>
                    <p class="text-xs text-slate-500 mt-1">Select words in the green box, then press the button.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.speech.review.store', $item) }}" class="space-y-5">
            @csrf
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-bold text-slate-800 mb-2">1. Translation accuracy</h3>
                <p class="text-sm text-slate-500 mb-2">Does the text say exactly what the original says? (1 = wrong, 5 = exact)</p>
                <div class="flex gap-3">
                    @for($n = 1; $n <= 5; $n++)
                        <label class="flex items-center gap-1 text-sm"><input type="radio" name="accuracy" value="{{ $n }}" @checked((int) $old('accuracy') === $n) required> {{ $n }}</label>
                    @endfor
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-bold text-slate-800 mb-1">2. Voices</h3>
                <p class="text-sm text-slate-500 mb-3">Listen to each voice. Naturalness and pronunciation: 1 = poor, 5 = like a native speaker. Speed: 1 = far too slow, 3 = right, 5 = far too fast.</p>
                @forelse($labels as $label)
                    <div class="border border-slate-200 rounded-xl p-4 mb-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="text-xl font-extrabold text-slate-700 w-20">Voice {{ $label }}</span>
                            <audio controls preload="none" src="{{ route('admin.speech.review.audio', [$item, $label]) }}" class="h-10"></audio>
                        </div>
                        <div class="grid sm:grid-cols-3 gap-3 mt-3 text-sm">
                            @foreach(['naturalness' => 'Naturalness', 'pronunciation' => 'Pronunciation', 'speed' => 'Speed'] as $f => $fname)
                                <div>
                                    <span class="font-semibold text-slate-600">{{ $fname }}</span>
                                    <div class="flex gap-2 mt-1">
                                        @for($n = 1; $n <= 5; $n++)
                                            <label class="flex items-center gap-1"><input type="radio" name="voice_ratings[{{ $label }}][{{ $f }}]" value="{{ $n }}"
                                                @checked((int) old("voice_ratings.$label.$f", $review->voice_ratings[$label][$f] ?? 0) === $n)
                                                @if($f === 'pronunciation' || $item->kind === 'scan') required @endif> {{ $n }}</label>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-amber-700">No audio for this item.</p>
                @endforelse
                @if($failedLabels)
                    <p class="text-xs text-amber-700">Voice {{ implode(', ', $failedLabels) }} could not be generated for this item.</p>
                @endif

                @if(count($labels) > 1)
                    <h4 class="font-semibold text-slate-700 mt-3">Which voice do you prefer?</h4>
                    <div class="flex gap-4 mt-1 text-sm">
                        @foreach($labels as $label)
                            <label class="flex items-center gap-1"><input type="radio" name="preferred_label" value="{{ $label }}" @checked($old('preferred_label') === $label)> Voice {{ $label }}</label>
                        @endforeach
                        <label class="flex items-center gap-1"><input type="radio" name="preferred_label" value="" @checked($old('preferred_label') === null || $old('preferred_label') === '')> No preference</label>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-bold text-slate-800 mb-1">3. Errors in the text</h3>
                <p class="text-sm text-slate-500 mb-3">A wrong dose, amount, unit, chemical, medicine or product name stops the language going live. Mark every one.</p>
                <div id="error-rows" class="space-y-2"></div>
                <button type="button" id="add-error" class="text-sm px-3 py-1.5 bg-slate-600 text-white rounded-lg">Add an error</button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-bold text-slate-800 mb-1">4. Comments (optional)</h3>
                <textarea name="comment" rows="3" maxlength="2000" class="w-full border-slate-300 rounded-lg text-sm">{{ $old('comment') }}</textarea>
            </div>

            <button type="submit" class="px-6 py-3 bg-emerald-600 text-white font-bold rounded-xl">Save and go to the next item</button>
        </form>
    </div>

    @php
        $reviewJs = ['types' => $errorTypes, 'existing' => array_values($existingErrors ?? [])];
    @endphp
    <script>
    (function () {
        var cfg = @json($reviewJs);
        var rows = document.getElementById('error-rows'), n = 0;
        function opt(sel) {
            return Object.keys(cfg.types).map(function (k) {
                return '<option value="' + k + '"' + (k === sel ? ' selected' : '') + '>' + cfg.types[k].replace(/</g, '&lt;') + '</option>';
            }).join('');
        }
        function add(e) {
            e = e || {};
            var i = n++, div = document.createElement('div');
            div.className = 'grid sm:grid-cols-12 gap-2 items-start bg-red-50 p-2 rounded-lg';
            div.innerHTML =
                '<input type="hidden" name="errors[' + i + '][section]">' +
                '<input name="errors[' + i + '][quote]" required maxlength="500" placeholder="Wrong words" class="sm:col-span-4 border-slate-300 rounded text-sm">' +
                '<select name="errors[' + i + '][type]" class="sm:col-span-3 border-slate-300 rounded text-sm">' + opt(e.type) + '</select>' +
                '<input name="errors[' + i + '][note]" maxlength="500" placeholder="What it should say (optional)" class="sm:col-span-4 border-slate-300 rounded text-sm">' +
                '<button type="button" class="sm:col-span-1 text-red-700 text-sm" aria-label="Remove">&times;</button>';
            div.querySelector('[name$="[section]"]').value = e.section || '';
            div.querySelector('[name$="[quote]"]').value = e.quote || '';
            div.querySelector('[name$="[note]"]').value = e.note || '';
            div.querySelector('button').addEventListener('click', function () { div.remove(); });
            rows.appendChild(div);
        }
        (cfg.existing || []).forEach(add);
        document.getElementById('add-error').addEventListener('click', function () { add(); });
        document.getElementById('mark-selection').addEventListener('click', function () {
            var sel = window.getSelection(), text = sel ? String(sel).trim() : '';
            var box = document.getElementById('review-translation');
            if (!text || !sel.anchorNode || !box.contains(sel.anchorNode)) { alert('Select the wrong words in the green box first.'); return; }
            var el = sel.anchorNode.nodeType === 1 ? sel.anchorNode : sel.anchorNode.parentElement;
            var sec = el.closest('[data-section]');
            add({ quote: text.slice(0, 500), section: sec ? sec.dataset.section : '', type: 'meaning' });
        });
    })();
    </script>
</x-app-layout>
