<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlossaryTerm;
use App\Models\ScanResultAudio;
use App\Models\ScanResultTranslation;
use App\Models\SpeechLanguage;
use App\Models\TtsFeedback;
use App\Models\SpeechReviewer;
use App\Models\User;
use App\Services\Speech\GoLiveRule;
use App\Services\Speech\Providers\ProviderRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin for "Listen to your result": languages (status, provider, voice,
 * rate), glossary / pronunciation / unit / protected terms, and per-language
 * quality numbers (review progress, failures, latency, listener feedback).
 */
class SpeechAdminController extends Controller
{
    public function index()
    {
        $languages = SpeechLanguage::orderBy('sort')->get();
        $since = now()->subDays(30);

        $stats = [];
        foreach ($languages as $l) {
            $audio = ScanResultAudio::where('lang', $l->code)->where('created_at', '>=', $since);
            $fb = TtsFeedback::where('lang', $l->code)->where('created_at', '>=', $since);
            $stats[$l->code] = [
                'reviewed'    => $l->reviewedCount(),
                'translated'  => ScanResultTranslation::where('lang', $l->code)->where('status', 'ready')->count(),
                'rejected'    => ScanResultTranslation::where('lang', $l->code)->where('status', 'rejected')->count(),
                'audio_ready' => (clone $audio)->where('status', 'ready')->count(),
                'audio_failed'=> (clone $audio)->where('status', 'failed')->count(),
                'latency_ms'  => (int) (clone $audio)->where('status', 'ready')->where('latency_ms', '>', 0)->avg('latency_ms'),
                'feedback'    => (clone $fb)->count(),
                'clear_pct'   => ($n = (clone $fb)->count()) ? (int) round((clone $fb)->where('clear', true)->count() * 100 / $n) : null,
            ];
        }

        return view('admin.speech.index', [
            'languages'   => $languages,
            'stats'       => $stats,
            'goLive'      => $languages->mapWithKeys(fn ($l) => [$l->code => GoLiveRule::evaluate($l->code)])->all(),
            'reviewers'   => SpeechReviewer::with('user:id,name,email')->orderBy('lang')->get(),
            'packs'       => \App\Models\SpeechReviewPack::withCount('items')->latest()->get(),
            'providers'   => ProviderRegistry::selectable(),
            'threshold'   => (int) config('speech.review_threshold', 50),
            'recentComments' => TtsFeedback::whereNotNull('comment')->latest()->limit(20)->get(),
        ]);
    }

    public function storeLanguage(Request $request)
    {
        $data = $this->validated($request, null);
        $data['status'] = 'draft'; // every new language starts in draft and must pass review to go live
        SpeechLanguage::create($data);
        return back()->with('success', "Language {$data['name_english']} added as draft.");
    }

    public function updateLanguage(Request $request, SpeechLanguage $language)
    {
        $data = $this->validated($request, $language);
        if ($data['status'] === 'live' && !$language->isLive() && $language->code !== 'en') {
            $rule = GoLiveRule::evaluate($language->code);
            if (!$rule['passes']) {
                return back()->withErrors(['status' => "{$language->name_english} cannot go live yet. " . GoLiveRule::failures($rule)]);
            }
        }
        $language->update($data);
        return back()->with('success', "{$language->name_english} updated.");
    }

    /** Assign an existing account (created by the product owner) to review a language. */
    public function storeReviewer(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            'lang'  => 'required|string|exists:speech_languages,code',
            'kind'  => ['required', Rule::in(array_keys(SpeechReviewer::KINDS))],
        ]);
        $user = User::where('email', $data['email'])->firstOrFail();
        SpeechReviewer::updateOrCreate(['user_id' => $user->id, 'lang' => $data['lang']], ['kind' => $data['kind'], 'active' => true]);
        return back()->with('success', "{$user->name} now reviews {$data['lang']} as " . strtolower(SpeechReviewer::KINDS[$data['kind']]) . '.');
    }

    public function destroyReviewer(SpeechReviewer $reviewer)
    {
        $reviewer->update(['active' => false]);
        return back()->with('success', 'Reviewer removed. Their reviews are kept.');
    }

    public function glossary(string $lang)
    {
        $language = SpeechLanguage::where('code', $lang)->first();
        abort_unless($language || $lang === '*', 404);
        return view('admin.speech.glossary', [
            'lang'     => $lang,
            'language' => $language,
            'terms'    => GlossaryTerm::where('lang', $lang)->orderBy('kind')->orderBy('english_term')->paginate(100),
            'kinds'    => GlossaryTerm::KINDS,
        ]);
    }

    public function storeTerm(Request $request, string $lang)
    {
        $data = $request->validate([
            'kind'         => ['required', Rule::in(GlossaryTerm::KINDS)],
            'english_term' => 'required|string|max:120',
            'local_term'   => 'nullable|string|max:200',
            'notes'        => 'nullable|string|max:1000',
        ]);
        GlossaryTerm::updateOrCreate(
            ['lang' => $lang, 'kind' => $data['kind'], 'english_term' => trim($data['english_term'])],
            ['local_term' => trim((string) ($data['local_term'] ?? $data['english_term'])), 'notes' => $data['notes'] ?? null, 'created_by' => $request->user()->id],
        );
        return back()->with('success', 'Term saved.');
    }

    /** CSV: kind,english_term,local_term,notes (header row optional). */
    public function importTerms(Request $request, string $lang)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $count = 0;
        DB::transaction(function () use ($handle, $lang, $request, &$count) {
            while (($row = fgetcsv($handle)) !== false) {
                [$kind, $en, $local, $notes] = array_pad(array_map('trim', $row), 4, null);
                if (!in_array($kind, GlossaryTerm::KINDS, true) || !$en) {
                    continue; // header or malformed row
                }
                GlossaryTerm::updateOrCreate(
                    ['lang' => $lang, 'kind' => $kind, 'english_term' => mb_substr($en, 0, 120)],
                    ['local_term' => mb_substr($local ?: $en, 0, 200), 'notes' => $notes, 'created_by' => $request->user()->id],
                );
                $count++;
            }
        });
        fclose($handle);
        return back()->with('success', "{$count} terms imported.");
    }

    public function destroyTerm(GlossaryTerm $term)
    {
        $term->delete();
        return back()->with('success', 'Term removed.');
    }

    private function validated(Request $request, ?SpeechLanguage $language): array
    {
        $providers = array_keys(ProviderRegistry::selectable());
        return $request->validate([
            'code'              => [$language ? 'prohibited' : 'required', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[A-Za-z]{2,4})?$/', Rule::unique('speech_languages', 'code')],
            'name_english'      => 'required|string|max:60',
            'name_native'       => 'required|string|max:60',
            'status'            => [$language ? 'required' : 'sometimes', Rule::in(SpeechLanguage::STATUSES)],
            'provider'          => ['nullable', Rule::in($providers)],
            'voice'             => 'nullable|string|max:60|required_with:provider',
            'fallback_provider' => ['nullable', Rule::in($providers)],
            'fallback_voice'    => 'nullable|string|max:60|required_with:fallback_provider',
            'rate'              => 'required|numeric|min:0.5|max:1.5',
            'safety_note'       => 'nullable|string|max:500',
            'sort'              => 'nullable|integer|min:0|max:1000',
        ]);
    }
}
