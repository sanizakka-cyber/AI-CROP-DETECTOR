<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpeechLanguage;
use App\Models\SpeechReview;
use App\Models\SpeechReviewer;
use App\Models\SpeechReviewItem;
use App\Models\SpeechReviewPack;
use App\Services\Speech\GoLiveRule;
use App\Support\SpeechAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Native-speaker / expert review of spoken results.
 *
 * Reviewers see each item's source text, its translation and one audio file
 * per voice labelled A/B/C (shuffled per item). They never see which
 * provider or voice is behind a label; only admins do, on the results page.
 */
class SpeechReviewController extends Controller
{
    /** Packs in the languages this user reviews (admins: all). */
    public function index(Request $request)
    {
        $user = $request->user();
        $langs = SpeechReviewer::languagesFor($user);
        $admin = SpeechAccess::canManage($user);
        abort_unless($langs || $admin, 403);

        $packs = SpeechReviewPack::query()
            ->when(!$admin, fn ($q) => $q->whereIn('lang', array_keys($langs)))
            ->where('status', 'open')->withCount('items')->latest()->get()
            ->each(function ($p) use ($user) {
                $p->done = SpeechReview::where('user_id', $user->id)->whereIn('item_id', $p->items()->pluck('id'))->count();
            });

        return view('admin.speech.review.index', [
            'packs' => $packs, 'langs' => $langs, 'admin' => $admin,
            'languages' => SpeechLanguage::pluck('name_english', 'code'),
        ]);
    }

    public function pack(Request $request, SpeechReviewPack $pack)
    {
        $this->authorizePack($request, $pack);
        $mine = SpeechReview::where('user_id', $request->user()->id)
            ->whereIn('item_id', $pack->items()->pluck('id'))->pluck('item_id')->flip();

        return view('admin.speech.review.pack', [
            'pack' => $pack, 'items' => $pack->items, 'mine' => $mine,
            'language' => SpeechLanguage::findCode($pack->lang),
        ]);
    }

    public function item(Request $request, SpeechReviewItem $item)
    {
        $this->authorizePack($request, $item->pack);
        $review = SpeechReview::where('item_id', $item->id)->where('user_id', $request->user()->id)->first();
        $items = $item->pack->items()->pluck('id')->all();
        $pos = array_search($item->id, $items, true);

        return view('admin.speech.review.item', [
            'item' => $item, 'pack' => $item->pack, 'review' => $review,
            'labels' => $item->playableLabels(),
            'failedLabels' => array_keys(array_filter($item->audio ?? [], fn ($a) => empty($a['path']))),
            'language' => SpeechLanguage::findCode($item->pack->lang),
            'prevId' => $pos > 0 ? $items[$pos - 1] : null,
            'nextId' => $items[$pos + 1] ?? null,
            'position' => $pos + 1, 'count' => count($items),
            'errorTypes' => SpeechReview::ERROR_TYPES,
        ]);
    }

    public function store(Request $request, SpeechReviewItem $item)
    {
        $kind = $this->authorizePack($request, $item->pack);
        $labels = $item->playableLabels();
        $isScan = $item->kind === 'scan';
        $rating = ['integer', 'min:1', 'max:5'];

        $data = $request->validate([
            'accuracy'                     => array_merge(['required'], $rating),
            'voice_ratings'                => [$labels ? 'required' : 'nullable', 'array'],
            'voice_ratings.*.naturalness'  => array_merge([$isScan ? 'required' : 'nullable'], $rating),
            'voice_ratings.*.pronunciation'=> array_merge(['required'], $rating),
            'voice_ratings.*.speed'        => array_merge([$isScan ? 'required' : 'nullable'], $rating),
            'preferred_label'              => ['nullable', Rule::in($labels)],
            'errors'                       => 'nullable|array|max:50',
            'errors.*.section'             => 'nullable|string|max:40',
            'errors.*.quote'               => 'required|string|max:500',
            'errors.*.type'                => ['required', Rule::in(array_keys(SpeechReview::ERROR_TYPES))],
            'errors.*.note'                => 'nullable|string|max:500',
            'comment'                      => 'nullable|string|max:2000',
        ]);
        $ratings = array_intersect_key($data['voice_ratings'] ?? [], array_flip($labels));
        if (count($ratings) !== count($labels)) {
            return back()->withInput()->withErrors(['voice_ratings' => 'Rate every voice (' . implode(', ', $labels) . ').']);
        }
        $errors = array_values($data['errors'] ?? []);
        $preferred = $data['preferred_label'] ?? null;

        SpeechReview::updateOrCreate(
            ['item_id' => $item->id, 'user_id' => $request->user()->id],
            [
                'reviewer_kind'       => $kind,
                'accuracy'            => (int) $data['accuracy'],
                'voice_ratings'       => array_map(fn ($r) => array_map(fn ($v) => $v === null ? null : (int) $v, $r), $ratings),
                'preferred_label'     => $preferred,
                'preferred_candidate' => $preferred !== null ? ($item->label_map[$preferred] ?? null) : null,
                'errors'              => $errors,
                'dosage_errors'       => count(array_filter($errors, fn ($e) => in_array($e['type'], SpeechReview::BLOCKING, true))),
                'comment'             => $data['comment'] ?? null,
            ],
        );

        $next = $item->pack->items()->where('position', '>', $item->position)
            ->whereDoesntHave('reviews', fn ($q) => $q->where('user_id', $request->user()->id))->first();
        return $next
            ? redirect()->route('admin.speech.review.item', $next)->with('success', 'Saved. Next item.')
            : redirect()->route('admin.speech.review.pack', $item->pack)->with('success', 'Saved. You have reviewed every item in this pack.');
    }

    /** One voice of one item, by its label only. */
    public function audio(Request $request, SpeechReviewItem $item, string $label)
    {
        $this->authorizePack($request, $item->pack);
        $file = $item->audio[$label] ?? null;
        abort_unless($file && !empty($file['path']), 404);
        $disk = Storage::disk(config('speech.disk', 'local'));
        abort_unless($disk->exists($file['path']), 404);
        // No file name or provider in the response: the label is all the reviewer learns.
        return $disk->response($file['path'], "voice-{$label}.mp3", [
            'Content-Type' => $file['mime'] ?? 'audio/mpeg', 'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /** Admin: go-live checks, voice preference (unblinded) and every marked error for one language. */
    public function results(string $lang)
    {
        $language = SpeechLanguage::where('code', $lang)->firstOrFail();
        $packs = SpeechReviewPack::where('lang', $lang)->get();
        $reviews = SpeechReview::whereHas('item.pack', fn ($q) => $q->where('lang', $lang))
            ->with('item.pack', 'user:id,name')->get();

        $voices = [];
        foreach ($reviews as $r) {
            foreach ($r->item->pack->candidates as $i => $c) {
                $key = "{$c['provider']}: {$c['voice']}";
                $voices[$key] ??= ['candidate' => $c, 'preferred' => ['native' => 0, 'expert' => 0, 'other' => 0], 'ratings' => []];
                if ($r->preferred_candidate === $i) {
                    $voices[$key]['preferred'][in_array($r->reviewer_kind, ['native', 'expert'], true) ? $r->reviewer_kind : 'other']++;
                }
                if ($rt = $r->ratingsForCandidate($i)) {
                    $voices[$key]['ratings'][] = $rt;
                }
            }
        }
        foreach ($voices as &$v) {
            foreach (['naturalness', 'pronunciation', 'speed'] as $f) {
                $vals = array_filter(array_column($v['ratings'], $f), fn ($x) => $x !== null);
                $v['avg'][$f] = $vals ? round(array_sum($vals) / count($vals), 2) : null;
            }
            $v['rated'] = count($v['ratings']);
        }
        unset($v);

        return view('admin.speech.review.results', [
            'language' => $language, 'packs' => $packs, 'goLive' => GoLiveRule::evaluate($lang),
            'voices' => $voices,
            'markedErrors' => $reviews->filter(fn ($r) => !empty($r->errors))->sortByDesc('dosage_errors'),
            'reviewCount' => $reviews->count(),
        ]);
    }

    /** @return string the reviewer kind for this pack's language (admins without an assignment: "admin") */
    private function authorizePack(Request $request, SpeechReviewPack $pack): string
    {
        $user = $request->user();
        $kind = SpeechReviewer::languagesFor($user)[$pack->lang] ?? null;
        if ($kind) {
            return $kind;
        }
        abort_unless(SpeechAccess::canManage($user), 403);
        return 'admin';
    }
}
