<?php

namespace App\Support;

use App\Models\Diagnosis;
use App\Models\User;

/**
 * What the farmer is shown for one scan: one source for the web history
 * card, the PDF report and the app (through the API), so the headline, the
 * side panel and the advice can never disagree with each other.
 *
 * Rules (from a real banana scan where they did disagree):
 * - Inconclusive (the AI gave no single finding, or its confidence is below
 *   the Not-certain line): the headline is "Inconclusive – possible causes
 *   below", never one candidate's name; health is "Needs checking"; there is
 *   no severity or urgency; at most three possible causes, in plain words;
 *   a "Retake a closer photo" button with photo tips.
 * - Healthy or normal ageing as the finding: health never says there is a
 *   problem, severity is None, no urgency.
 * - Every judged result has "What to do now": 2 to 4 simple steps.
 * - Latin names and technical words stay out of the farmer's view
 *   (FarmerText); staff see them in the technical details, and the PDF in
 *   its technical appendix.
 */
final class ResultView
{
    public const MAX_CAUSES = 3;
    public const MIN_STEPS = 2;
    public const MAX_STEPS = 4;

    /** Fixed, translated steps (lang/*.json and the app's i18n use the key's English text / the key). */
    public const STEPS = [
        'check_rot'          => 'Look closely for soft, rotting or bad-smelling parts.',
        'remove_dead_layers' => 'Cut off dead outer leaves or sheaths with a clean tool and take them away from the field.',
        'retake_closer'      => 'Take a closer, sharper photo of the affected part in daylight and scan again.',
        'contact_extension'  => 'If the problem spreads or you are not sure, contact an extension officer.',
        'no_treatment_needed' => 'No treatment is needed for this.',
        'check_weekly'       => 'Check the plant every week for new spots, rot or wilting.',
        'isolate_animal'     => 'Keep the animal apart from the others and watch it closely.',
        'check_eating'       => 'Check that the animal is eating, drinking and moving normally.',
        'call_vet'           => 'If the animal gets weaker or stops eating, call a vet now.',
        'keep_care'          => 'Keep giving clean water, good feed and the usual vaccinations.',
        'look_for_pests'     => 'Look under the leaves and along the stems for insects or eggs.',
        'no_spray_yet'       => 'Do not spray until the pest is confirmed.',
        'soil_lab_test'      => 'Take a soil sample to a laboratory for testing before you add fertiliser.',
        'remove_affected'    => 'Remove badly affected leaves or plants and keep them away from healthy ones.',
        'confirm_before_treating' => 'Ask an extension officer or vet to confirm before you treat.',
    ];

    /** Fallback photo tips when the AI gave none. */
    public const TIPS = [
        'tip_fill_frame' => 'Move closer so the affected part fills most of the photo.',
        'tip_daylight'   => 'Take the photo in daylight, without shadow on the affected part.',
        'tip_focus'      => 'Hold the phone still and tap the screen on the affected part to focus.',
        'tip_compare'    => 'If you can, include a healthy part next to the damaged one.',
    ];

    private const SETS = [
        'inconclusive' => [
            'crop'      => ['check_rot', 'remove_dead_layers', 'retake_closer', 'contact_extension'],
            'livestock' => ['isolate_animal', 'check_eating', 'retake_closer', 'call_vet'],
            'pest'      => ['look_for_pests', 'no_spray_yet', 'retake_closer', 'contact_extension'],
            'soil'      => ['retake_closer', 'soil_lab_test', 'contact_extension'],
        ],
        'normal_ageing' => [
            'crop'      => ['no_treatment_needed', 'remove_dead_layers', 'check_rot', 'check_weekly'],
            'livestock' => ['no_treatment_needed', 'keep_care', 'call_vet'],
            'pest'      => ['no_spray_yet', 'look_for_pests', 'contact_extension'],
            'soil'      => ['soil_lab_test', 'contact_extension'],
        ],
        'healthy' => [
            'crop'      => ['no_treatment_needed', 'check_weekly', 'contact_extension'],
            'livestock' => ['no_treatment_needed', 'keep_care', 'call_vet'],
            'pest'      => ['no_spray_yet', 'look_for_pests', 'contact_extension'],
            'soil'      => ['soil_lab_test', 'contact_extension'],
        ],
        'problem' => [
            'crop'      => ['remove_affected', 'confirm_before_treating'],
            'livestock' => ['isolate_animal', 'call_vet'],
            'pest'      => ['remove_affected', 'confirm_before_treating'],
            'soil'      => ['soil_lab_test', 'contact_extension'],
        ],
    ];

    public const HEALTH = [
        'needs_checking' => 'Needs checking',
        'healthy'        => 'Healthy',
        'normal_ageing'  => 'Healthy (normal ageing)',
        'problem'        => 'Problem found',
    ];

    public const HEADLINE_INCONCLUSIVE = 'Inconclusive – possible causes below';

    public static function for(Diagnosis $d, ?User $viewer = null): array
    {
        $mode = match ($d->type) { 'animal', 'livestock' => 'livestock', 'soil' => 'soil', 'pest' => 'pest', default => 'crop' };
        $kind = self::kind($d);
        $inconclusive = $kind === 'inconclusive';
        $health = self::healthKey($d, $kind);
        $healthyish = in_array($health, ['healthy', 'normal_ageing'], true);
        $name = $d->farmer_name ?: FarmerText::plain($d->disease_name);

        $headline = match ($kind) {
            'failed'       => __('No result: this scan did not complete'),
            'rejected'     => $d->assessment_outcome === 'poor_image' ? __('Photo too unclear to assess') : __('This photo does not show what this scan needs'),
            'inconclusive' => __(self::HEADLINE_INCONCLUSIVE),
            'likely'       => __('Likely') . ': ' . $name,
            default        => (string) $name,
        };

        $view = [
            'kind'          => $kind,
            'inconclusive'  => $inconclusive,
            'headline'      => $headline,
            'name'          => in_array($kind, ['likely', 'diagnosis'], true) ? $name : null,
            'health'        => $health ? ['key' => $health, 'label' => __(self::HEALTH[$health])] : null,
            // One severity and urgency for every surface: none for an inconclusive
            // result, None / no urgency for a healthy or normally ageing one.
            'severity'      => match (true) {
                in_array($kind, ['failed', 'rejected', 'inconclusive'], true) => null,
                $healthyish => 'none',
                default     => $d->severity_key,
            },
            'urgency'       => in_array($kind, ['failed', 'rejected', 'inconclusive'], true) || $healthyish ? null : $d->urgency_key,
            'possible_causes' => $inconclusive ? self::possibleCauses($d) : [],
            'steps'         => in_array($kind, ['failed', 'rejected'], true) ? [] : self::steps($d, $mode, $inconclusive ? 'inconclusive' : ($healthyish ? $health : 'problem')),
            'retake'        => $inconclusive ? ['show' => true, 'tips' => self::tips($d)] : ['show' => false, 'tips' => []],
            'detected_part' => FarmerText::plain($d->detected_part),
            'technical'     => self::isStaff($viewer) ? self::technical($d) : null,
        ];
        $view['severity_label'] = $view['severity'] ? __(ucfirst($view['severity'])) : null;
        $view['urgency_label']  = $view['urgency'] ? __(ucfirst($view['urgency'])) : null;
        return $view;
    }

    public static function kind(Diagnosis $d): string
    {
        if ($d->is_failed_scan || $d->status === 'needs_review') {
            return 'failed';
        }
        if (in_array($d->assessment_outcome, ['poor_image', 'unsupported_subject'], true)) {
            return 'rejected';
        }
        if ($d->assessment_outcome === 'uncertain' || $d->is_not_certain) {
            return 'inconclusive';
        }
        return $d->needs_confirmation ? 'likely' : 'diagnosis';
    }

    private static function healthKey(Diagnosis $d, string $kind): ?string
    {
        if ($kind === 'inconclusive') {
            return 'needs_checking';
        }
        if (in_array($kind, ['failed', 'rejected'], true)) {
            return null;
        }
        if ($d->condition_category === 'normal_ageing' || self::looksLikeNormalAgeing($d)) {
            return 'normal_ageing';
        }
        if ($d->condition_category === 'healthy') {
            return 'healthy';
        }
        $h = mb_strtolower(trim((string) $d->health_status));
        if ($h === '' && $d->condition_category === null) {
            return null;
        }
        return preg_match('/^healthy\b/u', $h) ? 'healthy' : 'problem';
    }

    /** Older scans have no category: recognise natural ageing from the finding's own words. */
    private static function looksLikeNormalAgeing(Diagnosis $d): bool
    {
        if ($d->condition_category !== null) {
            return false;
        }
        $text = mb_strtolower(($d->farmer_name ?? '') . ' ' . ($d->disease_name ?? ''));
        return (bool) preg_match('/\b(natural|normal)\b.*\b(age?ing|senescen\w*|drying|shedding)\b/u', $text);
    }

    private static function possibleCauses(Diagnosis $d): array
    {
        $candidates = [];
        if ($d->disease_name && !in_array(mb_strtolower(trim($d->disease_name)), ['unknown', 'none', 'n/a', 'inconclusive'], true)) {
            $candidates[] = ['label' => $d->condition_label, 'name' => $d->farmer_name ?: $d->disease_name];
        }
        foreach ($d->alternative_findings ?? [] as $alt) {
            if (!empty($alt['name'])) {
                $candidates[] = ['label' => $alt['condition_label'] ?? null, 'name' => ($alt['farmer_name'] ?? null) ?: $alt['name']];
            }
        }
        $out = [];
        $seen = [];
        foreach ($candidates as $c) {
            $plain = FarmerText::plain($c['name']);
            $keys = array_filter(['n:' . mb_strtolower((string) $plain), $c['label'] && $c['label'] !== 'unknown' ? 'l:' . $c['label'] : null]);
            if (!$plain || array_intersect($keys, $seen)) {
                continue;
            }
            $seen = array_merge($seen, $keys);
            $out[] = $plain;
            if (count($out) === self::MAX_CAUSES) {
                break;
            }
        }
        return $out;
    }

    /** 2 to 4 steps: the AI's own (already in the farmer's language) for a problem it found, else the fixed set. */
    private static function steps(Diagnosis $d, string $mode, string $set): array
    {
        $steps = [];
        if ($set === 'problem') {
            foreach (self::splitNumbered($d->first_aid_steps) as $text) {
                $steps[] = ['key' => null, 'text' => FarmerText::plain($text)];
            }
        }
        foreach (self::SETS[$set][$mode] as $key) {
            if (count($steps) >= ($set === 'problem' ? self::MIN_STEPS : self::MAX_STEPS)) {
                break;
            }
            $steps[] = ['key' => $key, 'text' => __(self::STEPS[$key])];
        }
        return array_slice($steps, 0, self::MAX_STEPS);
    }

    /** "1) Do this 2) Do that" (engine) or one step per line. */
    public static function splitNumbered(?string $text): array
    {
        if (!$text || trim($text) === '') {
            return [];
        }
        $parts = preg_split('/(?:^|\s)\d+[).]\s+|\R+/u', trim($text));
        return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
    }

    private static function tips(Diagnosis $d): array
    {
        $tips = [];
        foreach (array_slice($d->photo_tips ?? [], 0, 4) as $t) {
            if (is_string($t) && trim($t) !== '') {
                $tips[] = ['key' => null, 'text' => FarmerText::plain($t)];
            }
        }
        if ($tips) {
            return $tips;
        }
        foreach (self::TIPS as $key => $text) {
            $tips[] = ['key' => $key, 'text' => __($text)];
        }
        return $tips;
    }

    public static function isStaff(?User $viewer): bool
    {
        return $viewer !== null && Roles::isAny($viewer->role, config('scan_errors.staff_roles', []));
    }

    /** The unsimplified record, for staff (admin technical details) and the PDF appendix. */
    public static function technical(Diagnosis $d): array
    {
        $c = $d->ai_consensus ?? [];
        return [
            'technical_name'     => $d->disease_name,
            'scientific_name'    => $d->scientific_name,
            'detected_part'      => $d->detected_part,
            'health_status'      => $d->health_status,
            'condition_label'    => $d->condition_label,
            'condition_category' => $d->condition_category,
            'stored_severity'    => $d->severity_level,
            'stored_urgency'     => $d->urgency_level,
            'alternatives'       => array_values(array_map(fn ($a) => [
                'name' => $a['name'] ?? null, 'condition_label' => $a['condition_label'] ?? null,
                'why_considered' => $a['why_considered'] ?? null,
            ], $d->alternative_findings ?? [])),
            'pass_findings'      => $c['pass_findings'] ?? [],
            'pass_labels'        => $c['pass_labels'] ?? [],
            'pass_confidences'   => $c['pass_confidences'] ?? [],
            'vote_method'        => $c['method'] ?? null,
            'confidence_decision' => $d->confidence_decision,
            'contract_version'   => $d->contract_version,
        ];
    }
}
