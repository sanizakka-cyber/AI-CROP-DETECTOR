{{--
    "Listen to your result": the result in the farmer's chosen language,
    read aloud with sentence highlighting. Nothing is translated or generated
    until the farmer picks a language or presses Listen (auto-play is off).
    Expects: $diagnosis
--}}
@php
    $speechUser   = auth()->user();
    $speechLangs  = \App\Models\SpeechLanguage::availableFor($speechUser);
    $speechCodes  = $speechLangs->pluck('code')->all();
    $speechDefault = collect([$speechUser->preferred_language, $speechUser->language, 'en'])
        ->first(fn ($c) => $c && in_array($c, $speechCodes, true)) ?? ($speechCodes[0] ?? 'en');
    $lid = 'listen-' . $diagnosis->id;
@endphp

@if($speechLangs->isNotEmpty())
<section id="{{ $lid }}" class="listen-panel bg-slate-800 text-white px-5 py-3 space-y-3"
         data-scan="{{ $diagnosis->id }}"
         data-translate-url="{{ route('speech.translate', $diagnosis) }}"
         data-translation-url="{{ route('speech.translation', [$diagnosis, '__LANG__']) }}"
         data-audio-url="{{ route('speech.audio', $diagnosis) }}"
         data-audio-show-url="{{ route('speech.audio.show', [$diagnosis, '__LANG__']) }}"
         data-feedback-url="{{ route('speech.feedback') }}"
         data-review-url="{{ \App\Support\SpeechAccess::canReview($speechUser) ? route('speech.review', [$diagnosis, '__LANG__']) : '' }}">

    <div class="flex flex-wrap items-center gap-3">
        <span class="text-sm font-bold flex items-center gap-2"><i class="fa-solid fa-volume-high text-emerald-400"></i>{{ __('Listen to your result') }}</span>

        <label class="text-xs text-slate-300 flex items-center gap-2">
            <span class="sr-only">{{ __('Language') }}</span>
            <select class="listen-lang bg-slate-700 border border-slate-600 text-white text-sm rounded-lg px-2 py-1.5" aria-label="{{ __('Language') }}">
                @foreach($speechLangs as $l)
                    <option value="{{ $l->code }}" @selected($l->code === $speechDefault) data-voice="{{ $l->provider ? 1 : 0 }}">{{ $l->name_native }}{{ $l->status !== 'live' ? ' (review)' : '' }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs text-slate-300 flex items-center gap-1.5">
            <input type="checkbox" class="listen-remember rounded border-slate-500" @checked($speechUser->preferred_language)>
            {{ __('Remember my choice') }}
        </label>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <button type="button" class="listen-play flex items-center gap-2 px-5 py-3 bg-emerald-500 hover:bg-emerald-400 rounded-xl text-base font-extrabold" aria-live="polite">
            <i class="fa-solid fa-play"></i> <span class="listen-play-label">{{ __('Listen') }}</span>
        </button>
        <button type="button" class="listen-replay hidden px-3 py-3 bg-slate-600 hover:bg-slate-500 rounded-xl text-sm" title="{{ __('Replay') }}"><i class="fa-solid fa-rotate-left"></i> {{ __('Replay') }}</button>
        <div class="flex items-center gap-1 text-xs" role="group" aria-label="{{ __('Speed') }}">
            @foreach(['0.8', '1', '1.2'] as $s)
                <button type="button" class="listen-speed px-2.5 py-2 rounded-lg {{ $s === '1' ? 'bg-emerald-600' : 'bg-slate-600' }}" data-speed="{{ $s }}">{{ $s }}×</button>
            @endforeach
        </div>
        <a class="listen-download hidden text-xs text-emerald-300 underline ml-auto" href="#" download>
            <i class="fa-solid fa-download"></i> {{ __('Download audio') }} <span class="listen-size"></span>
        </a>
    </div>

    <div class="listen-progress hidden h-2 bg-slate-700 rounded-full overflow-hidden cursor-pointer" title="{{ __('Seek') }}">
        <div class="listen-progress-bar h-full bg-emerald-400" style="width:0%"></div>
    </div>

    <p class="listen-status text-xs text-amber-200 hidden" role="status"></p>

    {{-- The result in the chosen language. Spoken sentences are highlighted. --}}
    <div class="listen-text hidden bg-slate-900 rounded-xl p-4 text-sm leading-relaxed space-y-3"></div>

    <div class="listen-feedback hidden text-xs text-slate-300 flex flex-wrap items-center gap-2">
        <span>{{ __('Was this audio clear?') }}</span>
        <button type="button" class="listen-fb px-2 py-1 bg-slate-600 rounded" data-clear="1" aria-label="{{ __('Yes') }}">👍</button>
        <button type="button" class="listen-fb px-2 py-1 bg-slate-600 rounded" data-clear="0" aria-label="{{ __('No') }}">👎</button>
        <input type="text" maxlength="1000" class="listen-fb-comment hidden bg-slate-700 border border-slate-600 rounded px-2 py-1 text-white flex-1 min-w-[12rem]" placeholder="{{ __('What was unclear? (optional)') }}">
        <button type="button" class="listen-fb-send hidden px-2 py-1 bg-emerald-600 rounded">{{ __('Send') }}</button>
        <span class="listen-fb-done hidden text-emerald-300">{{ __('Thank you!') }}</span>
    </div>

    <button type="button" class="listen-review hidden text-xs px-3 py-1.5 bg-indigo-600 rounded-lg">{{ __('Mark translation reviewed') }}</button>

    <audio class="listen-audio" preload="none"></audio>
</section>

@once
@php
    $listenStrings = [
        'listen' => __('Listen'), 'pause' => __('Pause'), 'preparing' => __('Preparing your result in this language…'),
        'preparingAudio' => __('Preparing audio…'), 'noVoice' => __('Audio is not available for this language yet. Read the result below.'),
        'unavailable' => __('Audio is temporarily unavailable. Read the result below; we are still trying.'),
        'failed' => __('Audio is temporarily unavailable. Read the result below and try again later.'),
        'slow' => __('This is taking longer than usual. Read the result below; the audio will be ready soon.'),
        'error' => __('Could not reach MSAS. Check your connection and try again.'),
        'observations' => __('Observations'), 'explanation' => __('Explanation'),
        'recommendations' => __('Recommendations'), 'safety_note' => __('Safety note'),
        'reviewed' => __('Marked reviewed'),
    ];
@endphp
<style>
    .listen-sentence { border-radius: .25rem; transition: background-color .15s; }
    .listen-sentence.is-current { background: #065f46; color: #fff; outline: 2px solid #34d399; }
    .listen-text h4 { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: #6ee7b7; font-weight: 800; }
</style>
<script>
(function () {
    var T = @json($listenStrings);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function req(method, url, body) {
        return fetch(url, {
            method: method, credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (r) { return r.json().then(function (j) { j._http = r.status; return j; }); });
    }
    function wait(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function kb(n) { return n ? (n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB') : ''; }

    function Panel(root) {
        this.root = root;
        this.q = function (sel) { return root.querySelector(sel); };
        this.audio = this.q('.listen-audio');
        this.lang = this.q('.listen-lang').value;
        this.speed = 1;
        this.data = null;      // audio payload
        this.loadToken = 0;
        var self = this;

        this.q('.listen-lang').addEventListener('change', function () { self.lang = this.value; self.audio.pause(); self.load(false); });
        this.q('.listen-play').addEventListener('click', function () { self.toggle(); });
        this.q('.listen-replay').addEventListener('click', function () { self.audio.currentTime = 0; self.audio.play(); });
        root.querySelectorAll('.listen-speed').forEach(function (b) {
            b.addEventListener('click', function () {
                self.speed = parseFloat(b.dataset.speed);
                self.audio.playbackRate = self.speed;
                root.querySelectorAll('.listen-speed').forEach(function (x) { x.classList.toggle('bg-emerald-600', x === b); x.classList.toggle('bg-slate-600', x !== b); });
            });
        });
        this.q('.listen-progress').addEventListener('click', function (e) {
            if (!self.audio.duration) return;
            var r = this.getBoundingClientRect();
            self.audio.currentTime = self.audio.duration * (e.clientX - r.left) / r.width;
        });
        this.audio.addEventListener('timeupdate', function () { self.tick(); });
        this.audio.addEventListener('play', function () { self.playing(true); });
        this.audio.addEventListener('pause', function () { self.playing(false); });
        this.audio.addEventListener('ended', function () { self.playing(false); self.highlight(-1); self.q('.listen-feedback').classList.remove('hidden'); });
        root.querySelectorAll('.listen-fb').forEach(function (b) {
            b.addEventListener('click', function () {
                self.fbClear = b.dataset.clear === '1';
                self.sendFeedback();
                self.q('.listen-fb-comment').classList.remove('hidden');
                self.q('.listen-fb-send').classList.remove('hidden');
            });
        });
        this.q('.listen-fb-send').addEventListener('click', function () { self.sendFeedback(self.q('.listen-fb-comment').value); });
        this.q('.listen-review').addEventListener('click', function () { self.review(); });
    }

    Panel.prototype.status = function (msg) {
        var s = this.q('.listen-status');
        s.textContent = msg || '';
        s.classList.toggle('hidden', !msg);
    };

    Panel.prototype.playing = function (on) {
        this.q('.listen-play-label').textContent = on ? T.pause : T.listen;
        this.q('.listen-play i').className = on ? 'fa-solid fa-pause' : 'fa-solid fa-play';
    };

    Panel.prototype.toggle = function () {
        if (this.data && this.data.status === 'ready') {
            if (this.audio.paused) { this.audio.playbackRate = this.speed; this.audio.play(); } else { this.audio.pause(); }
            return;
        }
        this.load(true);
    };

    /** Translate (once, cached on the server), show the text, then fetch audio. */
    Panel.prototype.load = async function (thenPlay) {
        var token = ++this.loadToken, root = this.root, lang = this.lang, self = this;
        var url = function (k) { return root.dataset[k].replace('__LANG__', encodeURIComponent(lang)); };
        this.data = null;
        this.q('.listen-download').classList.add('hidden');
        this.q('.listen-progress').classList.add('hidden');
        this.q('.listen-replay').classList.add('hidden');
        this.status(T.preparing);
        try {
            var t = await req('POST', root.dataset.translateUrl, { lang: lang, remember: this.q('.listen-remember').checked, audio: true });
            for (var i = 0; t.status === 'pending' && i < 60 && token === this.loadToken; i++) {
                await wait(1500);
                t = await req('GET', url('translationUrl'));
            }
            if (token !== this.loadToken) return;
            if (t._http >= 400 && !t.status) { this.status(t.error || T.error); return; }
            this.renderText(t.content || (t.source && t.source.content), null);
            if (t.status !== 'ready') { this.status(t.message || T.failed); return; }
            if (root.dataset.reviewUrl && !t.reviewed) this.q('.listen-review').classList.remove('hidden');

            var opt = this.q('.listen-lang').selectedOptions[0];
            if (opt && opt.dataset.voice === '0') { this.status(T.noVoice); return; }

            this.status(T.preparingAudio);
            var a = await req('POST', root.dataset.audioUrl, { lang: lang });
            var started = Date.now();
            while (token === this.loadToken && ['pending', 'retrying', 'translating', 'none'].indexOf(a.status) !== -1 && Date.now() - started < 240000) {
                if (a.status === 'retrying') this.status(a.message || T.unavailable);
                else if (Date.now() - started > 20000) this.status(T.slow);
                await wait(2000);
                a = await req('GET', url('audioShowUrl'));
            }
            if (token !== this.loadToken) return;
            if (a.status !== 'ready') { this.status(a.message || T.failed); return; }

            this.data = a;
            this.status('');
            this.renderText(null, a.sentences);
            this.audio.src = a.url;
            this.audio.playbackRate = this.speed;
            var dl = this.q('.listen-download');
            dl.href = a.downloadUrl;
            this.q('.listen-size').textContent = a.sizeBytes ? '(' + kb(a.sizeBytes) + ')' : '';
            dl.classList.remove('hidden');
            this.q('.listen-progress').classList.remove('hidden');
            this.q('.listen-replay').classList.remove('hidden');
            if (thenPlay) this.audio.play().catch(function () {});
        } catch (e) {
            if (token === this.loadToken) this.status(T.error);
        }
    };

    /** Four sections; when sentences are given, each is a highlightable span. */
    Panel.prototype.renderText = function (content, sentences) {
        var box = this.q('.listen-text'), html = '';
        var sections = ['observations', 'explanation', 'recommendations', 'safety_note'];
        if (sentences && sentences.length) {
            sections.forEach(function (sec) {
                var items = {};
                sentences.forEach(function (s, i) {
                    if (s.section !== sec) return;
                    (items[s.item] = items[s.item] || []).push('<span class="listen-sentence" data-i="' + i + '">' + esc(s.text) + '</span>');
                });
                var keys = Object.keys(items);
                if (!keys.length) return;
                var list = sec === 'observations' || sec === 'recommendations';
                html += '<div><h4>' + esc(T[sec]) + '</h4>' + (list ? '<ul class="list-disc ml-5 space-y-1">' : '<p>')
                    + keys.map(function (k) { return list ? '<li>' + items[k].join(' ') + '</li>' : items[k].join(' '); }).join('')
                    + (list ? '</ul>' : '</p>') + '</div>';
            });
        } else if (content) {
            sections.forEach(function (sec) {
                var v = content[sec];
                if (!v || (Array.isArray(v) && !v.length)) return;
                html += '<div><h4>' + esc(T[sec]) + '</h4>' + (Array.isArray(v)
                    ? '<ul class="list-disc ml-5 space-y-1">' + v.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul>'
                    : '<p>' + esc(v) + '</p>') + '</div>';
            });
        }
        box.innerHTML = html;
        box.classList.toggle('hidden', !html);
        this.current = -1;
    };

    Panel.prototype.tick = function () {
        var a = this.audio, d = this.data;
        if (!d || !a.duration) return;
        this.q('.listen-progress-bar').style.width = (100 * a.currentTime / a.duration) + '%';
        var t = a.currentTime, idx = -1;
        (d.sentences || []).forEach(function (s, i) { if (s.start != null && t >= s.start - 0.05 && t < s.end + 0.35) idx = i; });
        this.highlight(idx);
    };

    Panel.prototype.highlight = function (idx) {
        if (idx === this.current) return;
        this.current = idx;
        this.root.querySelectorAll('.listen-sentence').forEach(function (el) { el.classList.toggle('is-current', +el.dataset.i === idx); });
        var cur = this.root.querySelector('.listen-sentence.is-current');
        if (cur && cur.scrollIntoView) cur.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    };

    Panel.prototype.sendFeedback = function (comment) {
        var self = this;
        req('POST', this.root.dataset.feedbackUrl, { scan_id: +this.root.dataset.scan, lang: this.lang, clear: !!this.fbClear, comment: comment || null })
            .then(function () { if (comment !== undefined) { self.q('.listen-fb-done').classList.remove('hidden'); self.q('.listen-fb-send').classList.add('hidden'); } });
    };

    Panel.prototype.review = function () {
        var self = this, btn = this.q('.listen-review');
        req('POST', this.root.dataset.reviewUrl.replace('__LANG__', encodeURIComponent(this.lang))).then(function (r) {
            if (r.reviewed) { btn.textContent = T.reviewed + ' (' + r.reviewedCount + '/' + r.target + ')'; btn.disabled = true; }
        });
    };

    function init() {
        document.querySelectorAll('.listen-panel').forEach(function (el) { if (!el._listen) el._listen = new Panel(el); });
        // Only one result plays at a time.
        document.addEventListener('play', function (e) {
            document.querySelectorAll('.listen-audio').forEach(function (a) { if (a !== e.target) a.pause(); });
        }, true);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
@endonce
@endif
