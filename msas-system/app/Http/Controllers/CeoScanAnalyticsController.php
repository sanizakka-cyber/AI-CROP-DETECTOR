<?php

namespace App\Http\Controllers;

use App\Data\NigeriaLocations;
use App\Http\Controllers\Concerns\HasCeoScanFilters;
use App\Models\Diagnosis;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CeoScanAnalyticsController extends Controller
{
    use HasCeoScanFilters;

    // Real stored values are plant/animal/soil/pest — never crop/livestock
    // (see CEOController::aiStatsMetrics(), which still has the old, always-
    // empty crop/livestock query for the legacy Overview pulse tile only).
    public function index(Request $request)
    {
        $summary = $this->headlineMetrics();

        $filteredBase = fn () => $this->applyNonGeoFilters(
            Diagnosis::query()->join('users', 'diagnoses.user_id', '=', 'users.id'),
            $request
        );

        $filteredCount = $filteredBase()
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->count();

        $filteredAvgConf = $filteredBase()
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->whereNotNull('diagnoses.confidence_score')
            ->avg('diagnoses.confidence_score');

        $filteredAvgMinutes = $filteredBase()
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->selectRaw('AVG(' . $this->minutesBetweenSql('diagnoses.updated_at', 'diagnoses.created_at') . ') as m')
            ->value('m');

        $statusBreakdown = $filteredBase()
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->select(DB::raw($this->displayStatusCaseSql().' as display_status'), DB::raw('count(*) as cnt'))
            ->groupBy('display_status')
            ->pluck('cnt', 'display_status');

        $topCrops = $filteredBase()
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->whereNotNull('diagnoses.subject_name')
            ->select('diagnoses.subject_name', DB::raw('count(*) as cnt'))
            ->groupBy('diagnoses.subject_name')
            ->orderByDesc('cnt')
            ->take(8)
            ->get();

        $topDiagnoses = $filteredBase()
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->whereNotNull('diagnoses.disease_name')
            ->where('diagnoses.disease_name', '!=', 'Pending Expert Review')
            ->select('diagnoses.disease_name', DB::raw('count(*) as cnt'))
            ->groupBy('diagnoses.disease_name')
            ->orderByDesc('cnt')
            ->take(8)
            ->get();

        [$from, $to] = $this->dateRange($request);
        $dailyChart = $filteredBase()
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->select(DB::raw('DATE(diagnoses.created_at) as d'), DB::raw('count(*) as cnt'))
            ->groupBy('d')
            ->pluck('cnt', 'd');
        $dailyLabels = collect();
        $cursor = $from->copy();
        while ($cursor->lte($to) && $dailyLabels->count() < 62) {
            $dailyLabels->push($cursor->format('Y-m-d'));
            $cursor->addDay();
        }
        $dailySeries = $dailyLabels->map(fn ($d) => [
            'label' => Carbon::parse($d)->format('M d'),
            'value' => (int) ($dailyChart[$d] ?? 0),
        ]);

        // State breakdown never filters on state/lga itself — that's the drill-down axis.
        $stateBreakdown = $filteredBase()
            ->whereNotNull('users.state')
            ->select('users.state', DB::raw('count(*) as cnt'))
            ->groupBy('users.state')
            ->orderByDesc('cnt')
            ->get();

        $lgaBreakdown = collect();
        if ($request->filled('state')) {
            $lgaBreakdown = $filteredBase()
                ->where('users.state', $request->state)
                ->whereNotNull('users.lga')
                ->select('users.lga', DB::raw('count(*) as cnt'))
                ->groupBy('users.lga')
                ->orderByDesc('cnt')
                ->get();
        }

        $scans = $filteredBase()
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->leftJoin('collection_locations', 'collection_locations.diagnosis_id', '=', 'diagnoses.id')
            ->select(
                'diagnoses.*',
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                'users.state as user_state',
                'users.lga as user_lga',
                // Actual sample collection location — distinct from the
                // user's registered address above (spec Section 5).
                // Null when no location was ever captured for this scan.
                'collection_locations.state as collection_state',
                'collection_locations.lga as collection_lga'
            )
            ->orderByDesc('diagnoses.created_at')
            ->paginate(25)
            ->withQueryString();

        // "Location completeness" (spec Section 10) — of the scans in the
        // CURRENT filtered view, what share have any real sample-collection
        // location recorded at all. Computed from the same filtered base so
        // it can't silently diverge from what the table above is showing.
        $locationCompleteness = $filteredBase()
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->leftJoin('collection_locations', 'collection_locations.diagnosis_id', '=', 'diagnoses.id')
            ->selectRaw('count(*) as total, count(collection_locations.id) as with_location, '
                . 'count(case when collection_locations.latitude is not null then 1 end) as with_coords')
            ->first();

        return view('ceo.pages.ai-analytics', [
            'summary'            => $summary,
            'locationCompleteness' => [
                'total'        => (int) ($locationCompleteness->total ?? 0),
                'withLocation' => (int) ($locationCompleteness->with_location ?? 0),
                'withCoords'   => (int) ($locationCompleteness->with_coords ?? 0),
            ],
            'filteredCount'      => $filteredCount,
            'filteredAvgConf'    => round((float) ($filteredAvgConf ?? 0)),
            'filteredAvgMinutes' => round((float) ($filteredAvgMinutes ?? 0), 1),
            'statusBreakdown'    => $statusBreakdown,
            'topCrops'           => $topCrops,
            'topDiagnoses'       => $topDiagnoses,
            'dailySeries'        => $dailySeries,
            'stateBreakdown'     => $stateBreakdown,
            'lgaBreakdown'       => $lgaBreakdown,
            'scans'              => $scans,
            'states'             => collect(NigeriaLocations::states())->pluck('name'),
            'lgasForState'       => $request->filled('state')
                ? collect(NigeriaLocations::states())->firstWhere('name', $request->state)['lgas'] ?? []
                : [],
        ]);
    }

    /**
     * Research-quality CSV export (spec Sections 7/8). Every column is
     * named for exactly what it is — "User's..." for the farmer's
     * registered account address, "Sample Collection..." for the actual
     * sample location captured on the scan — so the two are never
     * confused downstream. A scan's `confidence_score`/`validation_status`
     * columns are exported exactly as stored: missing values stay blank,
     * never replaced with a fabricated number (spec 8).
     */
    public function exportCsv(Request $request)
    {
        $query = $this->applyNonGeoFilters(
            Diagnosis::query()->join('users', 'diagnoses.user_id', '=', 'users.id'),
            $request
        )
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->leftJoin('collection_locations', 'collection_locations.diagnosis_id', '=', 'diagnoses.id')
            ->select(
                'diagnoses.id', 'diagnoses.scan_ref', 'diagnoses.created_at', 'diagnoses.type', 'diagnoses.subject_name',
                'diagnoses.disease_name', 'diagnoses.confidence_score', 'diagnoses.severity_level',
                'diagnoses.status', DB::raw($this->displayStatusCaseSql().' as display_status'),
                'diagnoses.validation_status', 'diagnoses.confidence_decision',
                'diagnoses.ai_model_name', 'diagnoses.ai_model_version',
                'users.first_name as user_first_name', 'users.last_name as user_last_name',
                'users.state as user_state', 'users.lga as user_lga',
                'collection_locations.state as collection_state', 'collection_locations.lga as collection_lga',
                'collection_locations.community as collection_community',
                'collection_locations.latitude as collection_latitude', 'collection_locations.longitude as collection_longitude',
                'collection_locations.accuracy_meters as collection_accuracy_meters',
                'collection_locations.capture_method as collection_capture_method',
                'collection_locations.verification_status as collection_verification_status',
                'collection_locations.collected_at as collection_collected_at'
            )
            ->orderByDesc('diagnoses.created_at')
            // Sane upper bound on a single export — this platform's scan volume is
            // well under this today; revisit with chunked export if it ever isn't.
            ->limit(10000);

        $filename = 'ai-scan-analytics-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Scan ID', 'Date/Time (Africa/Lagos)', 'Type', 'Crop/Subject', 'Diagnosis',
                'Confidence %', 'Confidence Decision', 'Validation Status', 'AI Model', 'AI Model Version',
                'Severity', 'Raw Status', 'Display Status', 'User',
                "User's Registered State", "User's Registered LGA",
                'Sample Collection State', 'Sample Collection LGA', 'Sample Collection Community',
                'Sample Latitude (WGS84)', 'Sample Longitude (WGS84)', 'Sample GPS Accuracy (m)',
                'Sample Location Capture Method', 'Sample Location Verification Status',
                'Sample Collected At (Africa/Lagos)',
            ]);
            foreach ($query->cursor() as $row) {
                fputcsv($handle, [
                    $row->scan_ref ?? $row->id,
                    optional($row->created_at)->timezone('Africa/Lagos')->format('Y-m-d H:i:s'),
                    $row->type,
                    $row->subject_name,
                    $row->disease_name,
                    $row->confidence_score, // left blank by fputcsv when null — never backfilled
                    $row->confidence_decision,
                    $row->validation_status,
                    $row->ai_model_name,
                    $row->ai_model_version,
                    $row->severity_level,
                    $row->status,
                    $row->display_status,
                    trim(($row->user_first_name ?? '').' '.($row->user_last_name ?? '')),
                    $row->user_state,
                    $row->user_lga,
                    $row->collection_state,
                    $row->collection_lga,
                    $row->collection_community,
                    $row->collection_latitude,
                    $row->collection_longitude,
                    $row->collection_accuracy_meters,
                    $row->collection_capture_method,
                    $row->collection_verification_status,
                    $row->collection_collected_at
                        ? Carbon::parse($row->collection_collected_at)->timezone('Africa/Lagos')->format('Y-m-d H:i:s')
                        : null,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function headlineMetrics(): array
    {
        return [
            'today' => Diagnosis::whereDate('created_at', today())->count(),
            'week'  => Diagnosis::where('created_at', '>=', now()->subDays(6)->startOfDay())->count(),
            'month' => Diagnosis::where('created_at', '>=', now()->subDays(29)->startOfDay())->count(),
            'total' => Diagnosis::count(),
        ];
    }

    // dateRange(), applyNonGeoFilters(), displayStatusCaseSql() now live in
    // HasCeoScanFilters — shared with CEOController's Overview AI Analytics
    // section so both stay on one definition of "what a filter means".
}
