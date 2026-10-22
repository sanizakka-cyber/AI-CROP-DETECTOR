<?php

namespace App\Support;

use App\Models\Diagnosis;
use App\Models\User;

/**
 * A scan attached to a vet or agronomist request ("Request Vet / Ask an
 * expert" on a result). Used by the web forms (FarmerController) and the API
 * (ConsultationApiController) so both attach a scan the same way.
 */
final class ScanAttachment
{
    /** The scan, only when it belongs to $user and the AI actually judged it. */
    public static function forUser(?User $user, mixed $id): ?Diagnosis
    {
        if ($user === null || !is_numeric($id)) {
            return null;
        }
        $d = Diagnosis::where('id', (int) $id)->where('user_id', $user->id)->first();
        return $d && !$d->is_failed_scan ? $d : null;
    }

    /** Livestock scans go to a vet, everything else to an agronomist. */
    public static function isLivestock(Diagnosis $d): bool
    {
        return in_array($d->type, ['animal', 'livestock'], true);
    }

    /** Consultation columns filled from the scan (the vet's "AI Pre-Analysis" box reads these). */
    public static function consultationFields(Diagnosis $d): array
    {
        $view = $d->resultView();
        $what = $view['inconclusive'] && $view['possible_causes']
            ? $view['headline'] . ': ' . implode('; ', $view['possible_causes'])
            : $view['headline'];
        return [
            'diagnosis_id'  => $d->id,
            'ai_diagnosis'  => mb_strimwidth(($d->scan_ref ? "{$d->scan_ref}: " : '') . $what, 0, 255, '...'),
            'ai_confidence' => $d->confidence_score,
        ];
    }

    /** What the form opens with: the subject and a description of the scan, editable by the farmer. */
    public static function prefill(Diagnosis $d): array
    {
        $view = $d->resultView();
        $lines = [__('About my scan :ref (:date):', ['ref' => $d->scan_ref ?? "#{$d->id}", 'date' => $d->created_at?->format('j M Y')])];
        $lines[] = $view['headline'] . '.';
        if ($view['possible_causes']) {
            $lines[] = __('Possible causes') . ': ' . implode('; ', $view['possible_causes']) . '.';
        }
        if ($symptoms = FarmerText::plain($d->symptoms_identified)) {
            $lines[] = __('Symptoms') . ': ' . $symptoms . '.';
        }
        if ($view['confidence']) {
            $lines[] = __('Confidence') . ': ' . $view['confidence']['label'] . '.';
        }
        return [
            'subject'  => (string) ($d->subject_name ?? ''),
            'symptoms' => mb_substr(implode("\n", $lines), 0, 2000),
            'priority' => match ($view['urgency']) { 'emergency' => 'critical', 'high' => 'high', 'low' => 'low', default => 'medium' },
        ];
    }
}
