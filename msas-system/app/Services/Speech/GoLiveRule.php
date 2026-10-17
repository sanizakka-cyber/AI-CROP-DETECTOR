<?php

namespace App\Services\Speech;

use App\Models\SpeechReview;
use App\Models\SpeechLanguage;
use App\Models\SpeechReviewItem;

/**
 * Whether a language may go live, from native-speaker and expert reviews.
 * Every number comes from config('speech.go_live').
 *
 *  - at least N reviewed scan results, and at least M per scan type;
 *    a result counts once each required reviewer kind has reviewed it
 *  - average translation accuracy at least X / 5, and average naturalness
 *    of the voice the language is set to use (its provider + voice) at
 *    least Y / 5; a voice nobody has rated cannot go live
 *  - no dose/amount/unit or chemical/medicine/product error on any item
 */
class GoLiveRule
{
    /**
     * @return array{passes: bool, checks: array<int, array{key: string, label: string, required: mixed, actual: mixed, ok: bool}>}
     */
    public static function evaluate(string $lang): array
    {
        $cfg = config('speech.go_live');
        $kinds = array_values($cfg['reviewer_kinds'] ?: ['native']);

        $itemIds = SpeechReviewItem::whereHas('pack', fn ($q) => $q->where('lang', $lang))->pluck('id');
        $reviews = SpeechReview::whereIn('item_id', $itemIds)->with('item:id,pack_id,kind,scan_type,label_map', 'item.pack:id,candidates')->get();
        $scanReviews = $reviews->filter(fn ($r) => $r->item?->kind === 'scan');

        // Results reviewed by every required kind, per scan type.
        $reviewed = $scanReviews->groupBy('item_id')
            ->filter(fn ($rs) => !array_diff($kinds, $rs->pluck('reviewer_kind')->unique()->all()))
            ->map(fn ($rs) => $rs->first()->item->scan_type);
        $perType = [];
        foreach ($cfg['scan_types'] as $t) {
            $perType[$t] = $reviewed->filter(fn ($st) => $st === $t)->count();
        }

        $accuracy = ($v = $scanReviews->whereNotNull('accuracy')->avg('accuracy')) === null ? null : round((float) $v, 2);

        // Naturalness of the voice that would go live.
        $language = SpeechLanguage::findCode($lang);
        $scores = [];
        foreach ($scanReviews as $r) {
            foreach ($r->item->pack->candidates ?? [] as $i => $c) {
                if ($language && ($c['provider'] ?? null) === $language->provider && ($c['voice'] ?? null) === $language->voice) {
                    $n = $r->ratingsForCandidate($i)['naturalness'] ?? null;
                    if ($n !== null) {
                        $scores[] = (int) $n;
                    }
                }
            }
        }
        $naturalness = $scores ? round(array_sum($scores) / count($scores), 2) : null;
        $voiceName = $language ? "{$language->provider}: {$language->voice}" : 'none';
        $dosage = (int) $reviews->sum('dosage_errors');

        $checks = [
            ['key' => 'reviewed', 'label' => 'Scan results reviewed (by ' . implode(' and ', $kinds) . ')',
             'required' => (int) $cfg['min_reviewed_results'], 'actual' => $reviewed->count(),
             'ok' => $reviewed->count() >= (int) $cfg['min_reviewed_results']],
        ];
        foreach ($perType as $t => $n) {
            $checks[] = ['key' => "type_{$t}", 'label' => (SpeechReviewItem::SCAN_TYPES[$t] ?? $t) . ' results reviewed',
                'required' => (int) $cfg['min_per_scan_type'], 'actual' => $n, 'ok' => $n >= (int) $cfg['min_per_scan_type']];
        }
        $checks[] = ['key' => 'accuracy', 'label' => 'Average translation accuracy (1-5)', 'required' => (float) $cfg['min_accuracy'],
            'actual' => $accuracy, 'ok' => $accuracy !== null && $accuracy >= (float) $cfg['min_accuracy']];
        $checks[] = ['key' => 'naturalness', 'label' => "Average naturalness of the configured voice ({$voiceName}, 1-5)", 'required' => (float) $cfg['min_naturalness'],
            'actual' => $naturalness, 'ok' => $naturalness !== null && $naturalness >= (float) $cfg['min_naturalness']];
        $checks[] = ['key' => 'dosage', 'label' => 'Dose or chemical errors marked', 'required' => '≤ ' . (int) $cfg['max_dosage_errors'],
            'actual' => $dosage, 'ok' => $dosage <= (int) $cfg['max_dosage_errors']];

        return ['passes' => collect($checks)->every(fn ($c) => $c['ok']), 'checks' => $checks];
    }

    /** One line naming what is still missing, for the admin error message. */
    public static function failures(array $result): string
    {
        return collect($result['checks'])->reject(fn ($c) => $c['ok'])
            ->map(fn ($c) => "{$c['label']}: " . ($c['actual'] ?? 'none') . " (needs {$c['required']})")
            ->implode('; ');
    }
}
