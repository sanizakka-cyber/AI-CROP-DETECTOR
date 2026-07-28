<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BiController extends Controller
{
    public function index()
    {
        // Disease heatmap: top states x top diseases (last 90 days)
        $rawDiseases = Diagnosis::select(
                'users.state',
                'diagnoses.disease_name',
                DB::raw('COUNT(*) as count')
            )
            ->join('users', 'diagnoses.user_id', '=', 'users.id')
            ->whereNotNull('users.state')->where('users.state', '!=', '')
            ->whereNotNull('diagnoses.disease_name')
            ->where('diagnoses.disease_name', '!=', 'Pending Expert Review')
            ->where('diagnoses.created_at', '>=', now()->subDays(90))
            ->groupBy('users.state', 'diagnoses.disease_name')
            ->orderByDesc('count')
            ->limit(200)
            ->get();

        $topStates   = $rawDiseases->groupBy('state')
            ->map(fn($r) => $r->sum('count'))
            ->sortDesc()->keys()->take(10);

        $topDiseases = $rawDiseases->groupBy('disease_name')
            ->map(fn($r) => $r->sum('count'))
            ->sortDesc()->keys()->take(5);

        $heatmap = [];
        foreach ($topStates as $state) {
            $heatmap[$state] = [];
            foreach ($topDiseases as $disease) {
                $heatmap[$state][$disease] = $rawDiseases
                    ->where('state', $state)
                    ->where('disease_name', $disease)
                    ->first()?->count ?? 0;
            }
        }
        $heatmapMax = $rawDiseases->max('count') ?: 1;

        // Revenue by month (last 12)
        $months12 = collect(range(11, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));
        $rawRevenue = Payment::select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy(DB::raw("TO_CHAR(created_at, 'YYYY-MM')"))
            ->orderBy('month')
            ->get()->pluck('total', 'month');

        $revenueByMonth = $months12->mapWithKeys(
            fn($m) => [$m => (float) ($rawRevenue->get($m, 0))]
        );

        // Top diseases last 30 days
        $topDiseasesData = Diagnosis::select('disease_name', DB::raw('COUNT(*) as count'))
            ->whereNotNull('disease_name')
            ->where('disease_name', '!=', 'Pending Expert Review')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('disease_name')
            ->orderByDesc('count')
            ->take(10)->get();

        // Top experts by consultations responded
        $topExperts = User::select(
                'users.id', 'users.first_name', 'users.last_name',
                'users.role', 'users.state',
                DB::raw('COUNT(consultations.id) as consult_count'),
                DB::raw('AVG(consultations.rating) as avg_rating')
            )
            ->join('consultations', 'consultations.expert_id', '=', 'users.id')
            ->whereIn('users.role', ['vet', 'agronomist'])
            ->where('consultations.status', 'responded')
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.role', 'users.state')
            ->orderByDesc('consult_count')
            ->take(10)->get();

        // Subscription funnel
        $totalFarmers = User::where('role', 'farmer')->count();
        $hadAnySub    = Subscription::distinct('user_id')->count('user_id');
        $activeSub    = Subscription::whereIn('status', ['active', 'trial'])
            ->distinct('user_id')->count('user_id');
        $paidSub      = Subscription::where('status', 'active')
            ->where('amount_paid', '>', 0)
            ->distinct('user_id')->count('user_id');

        // Scan type breakdown
        $scanTypes = Diagnosis::select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')->get()->pluck('count', 'type');

        $totalScans      = $scanTypes->sum();
        $avgScansPerUser = $totalFarmers > 0 ? round($totalScans / $totalFarmers, 1) : 0;

        // Plan distribution
        $planDist = Subscription::select('plan', DB::raw('COUNT(*) as count'))
            ->whereIn('status', ['active', 'trial'])
            ->groupBy('plan')->orderByDesc('count')->get();

        // Crop type breakdown from consultations
        $cropTypes = Consultation::select('crop_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('crop_type')->where('crop_type', '!=', '')
            ->groupBy('crop_type')->orderByDesc('count')->take(8)->get();

        // Monthly new user trend (last 6 months)
        $usersByMonth = User::select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy(DB::raw("TO_CHAR(created_at, 'YYYY-MM')"))
            ->orderBy('month')->get()->pluck('count', 'month');

        $months6 = collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));
        $usersByMonth = $months6->mapWithKeys(fn($m) => [$m => (int) ($usersByMonth->get($m, 0))]);

        return view('ceo.bi', compact(
            'heatmap', 'topStates', 'topDiseases', 'heatmapMax',
            'revenueByMonth', 'months12',
            'topDiseasesData',
            'topExperts',
            'totalFarmers', 'hadAnySub', 'activeSub', 'paidSub',
            'scanTypes', 'totalScans', 'avgScansPerUser',
            'planDist', 'cropTypes',
            'usersByMonth', 'months6'
        ));
    }
}