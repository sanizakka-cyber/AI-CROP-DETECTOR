<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

// Shared scan-filtering logic for any CEO page that lets the CEO filter AI
// scan records (currently CeoScanAnalyticsController's full page and
// CEOController's Overview AI Analytics section). Extracted so both stay on
// exactly one definition of "what a filter/date-range means" instead of two
// independently-maintained copies.
trait HasCeoScanFilters
{
    private function dateRange(Request $request): array
    {
        $preset = $request->input('range', '30d');

        if ($preset === 'custom' && $request->filled('from') && $request->filled('to')) {
            return [
                Carbon::parse($request->from)->startOfDay(),
                Carbon::parse($request->to)->endOfDay(),
            ];
        }

        return match ($preset) {
            'today'      => [now()->startOfDay(), now()->endOfDay()],
            'yesterday'  => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '7d'         => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            default      => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
        };
    }

    private function applyNonGeoFilters(Builder $query, Request $request): Builder
    {
        [$from, $to] = $this->dateRange($request);
        $query->whereBetween('diagnoses.created_at', [$from, $to]);

        $query->when($request->filled('crop'), fn (Builder $q) => $q->where('diagnoses.subject_name', 'ilike', '%'.$request->crop.'%'));
        $query->when($request->filled('diagnosis'), fn (Builder $q) => $q->where('diagnoses.disease_name', 'ilike', '%'.$request->diagnosis.'%'));
        $query->when($request->filled('scan_ref'), fn (Builder $q) => $q->where('diagnoses.scan_ref', 'ilike', '%'.$request->scan_ref.'%'));
        $query->when($request->filled('user'), function (Builder $q) use ($request) {
            $s = $request->user;
            $q->where(fn (Builder $qq) => $qq->where('users.first_name', 'ilike', "%{$s}%")
                ->orWhere('users.last_name', 'ilike', "%{$s}%")
                ->orWhere('users.email', 'ilike', "%{$s}%"));
        });

        $query->when($request->filled('confidence'), function (Builder $q) use ($request) {
            match ($request->confidence) {
                'high'   => $q->where('diagnoses.confidence_score', '>=', 80),
                'medium' => $q->whereBetween('diagnoses.confidence_score', [60, 79.999]),
                'low'    => $q->where('diagnoses.confidence_score', '<', 60),
                default  => null,
            };
        });

        $query->when($request->filled('status'), function (Builder $q) use ($request) {
            match ($request->status) {
                'processing'     => $q->where('diagnoses.status', 'pending'),
                'failed'         => $q->where('diagnoses.status', 'needs_review'),
                // 65% is the spec's automated-acceptance line — below it a scan is
                // "Low Confidence" even though the AI did run and produce a diagnosis.
                'low_confidence' => $q->whereIn('diagnoses.status', ['reviewed', 'confirmed'])
                                       ->where('diagnoses.confidence_score', '<', 65),
                'completed'      => $q->whereIn('diagnoses.status', ['reviewed', 'confirmed'])
                                       ->where(fn (Builder $qq) => $qq->whereNull('diagnoses.confidence_score')
                                           ->orWhere('diagnoses.confidence_score', '>=', 65)),
                default => null,
            };
        });

        return $query;
    }

    // Derives a display status from the two divergent write paths (web:
    // pending/reviewed/needs_review, mobile API: pending/confirmed) plus
    // confidence — never touches the raw `status` column other code relies on.
    private function displayStatusCaseSql(): string
    {
        return <<<SQL
            CASE
                WHEN diagnoses.status = 'needs_review' THEN 'Failed'
                WHEN diagnoses.status = 'pending' THEN 'Processing'
                WHEN diagnoses.status IN ('reviewed','confirmed') AND diagnoses.confidence_score IS NOT NULL AND diagnoses.confidence_score < 65 THEN 'Low Confidence'
                WHEN diagnoses.status IN ('reviewed','confirmed') THEN 'Completed'
                ELSE 'Unknown'
            END
        SQL;
    }
}
