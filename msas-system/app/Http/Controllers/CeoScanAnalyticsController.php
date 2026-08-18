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
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (diagnoses.updated_at - diagnoses.created_at)) / 60) as m')
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
            ->select(
                'diagnoses.*',
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                'users.state as user_state',
                'users.lga as user_lga'
            )
            ->orderByDesc('diagnoses.created_at')
            ->paginate(25)
            ->withQueryString();

        return view('ceo.pages.ai-analytics', [
            'summary'            => $summary,
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

    public function exportCsv(Request $request)
    {
        $query = $this->applyNonGeoFilters(
            Diagnosis::query()->join('users', 'diagnoses.user_id', '=', 'users.id'),
            $request
        )
            ->when($request->filled('state'), fn (Builder $q) => $q->where('users.state', $request->state))
            ->when($request->filled('lga'), fn (Builder $q) => $q->where('users.lga', $request->lga))
            ->select(
                'diagnoses.id', 'diagnoses.scan_ref', 'diagnoses.created_at', 'diagnoses.type', 'diagnoses.subject_name',
                'diagnoses.disease_name', 'diagnoses.confidence_score', 'diagnoses.severity_level',
                'diagnoses.status', DB::raw($this->displayStatusCaseSql().' as display_status'),
                'users.first_name as user_first_name', 'users.last_name as user_last_name',
                'users.state as user_state', 'users.lga as user_lga'
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
                'Confidence %', 'Severity', 'Raw Status', 'Display Status', 'User', 'State', 'LGA',
            ]);
            foreach ($query->cursor() as $row) {
                fputcsv($handle, [
                    $row->scan_ref ?? $row->id,
                    optional($row->created_at)->timezone('Africa/Lagos')->format('Y-m-d H:i:s'),
                    $row->type,
                    $row->subject_name,
                    $row->disease_name,
                    $row->confidence_score,
                    $row->severity_level,
                    $row->status,
                    $row->display_status,
                    trim(($row->user_first_name ?? '').' '.($row->user_last_name ?? '')),
                    $row->user_state,
                    $row->user_lga,
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
