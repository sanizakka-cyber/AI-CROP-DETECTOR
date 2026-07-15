<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsApiController extends Controller
{
    // GET /analytics/summary — caller's own scan + consultation history
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $total      = Diagnosis::where('user_id', $user->id)->count();
        $processed  = Diagnosis::where('user_id', $user->id)->where('status', 'confirmed')->count();
        $crop       = Diagnosis::where('user_id', $user->id)->where('type', 'plant')->count();
        $livestock  = Diagnosis::where('user_id', $user->id)->where('type', 'animal')->count();
        $recent     = Diagnosis::where('user_id', $user->id)->latest()->take(5)->get(['id','disease_name','type','status','created_at']);

        return response()->json([
            'summary' => [
                'total'     => $total,
                'processed' => $processed,
                'crop'      => $crop,
                'livestock' => $livestock,
                'recent'    => $recent,
            ],
        ]);
    }

    // GET /analytics/admin-summary — platform-wide overview (admin/CEO only)
    public function adminSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! in_array($user->role, ['ceo','admin','data-analyst','monitoring-evaluation'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $totalUsers    = User::count();
        $farmers       = User::where('role', 'farmer')->count();
        $vets          = User::where('role', 'vet')->count();
        $agronomists   = User::where('role', 'agronomist')->count();
        $pendingExperts= User::whereIn('role', ['vet','agronomist'])->where('is_verified', false)->count();
        $activeMonthly = User::where('last_seen', '>=', now()->subDays(30))->count();

        $totalScans      = Diagnosis::count();
        $processedScans  = Diagnosis::where('status', 'confirmed')->count();
        $expertReviews   = Diagnosis::where('status', 'needs_review')->count();
        $processingRate  = $totalScans > 0 ? round(($processedScans / $totalScans) * 100, 1) : 0;

        $totalConsults    = Consultation::count();
        $completedConsults= Consultation::where('status', 'resolved')->count();
        $completionRate   = $totalConsults > 0 ? round(($completedConsults / $totalConsults) * 100, 1) : 0;

        $confirmedDx = Diagnosis::where('status', 'confirmed')->count();
        $successRate = $totalScans > 0 ? round(($confirmedDx / $totalScans) * 100, 1) : 0;

        return response()->json([
            'summary' => [
                'users' => [
                    'total'          => $totalUsers,
                    'farmers'        => $farmers,
                    'vets'           => $vets,
                    'agronomists'    => $agronomists,
                    'pendingExperts' => $pendingExperts,
                    'activeMonthly'  => $activeMonthly,
                ],
                'scans' => [
                    'total'          => $totalScans,
                    'processed'      => $processedScans,
                    'expertReviews'  => $expertReviews,
                    'processingRate' => $processingRate,
                ],
                'consultations' => [
                    'total'          => $totalConsults,
                    'completed'      => $completedConsults,
                    'completionRate' => $completionRate,
                ],
                'treatment' => [
                    'successRate' => $successRate,
                ],
            ],
        ]);
    }

    // GET /analytics/outbreaks — disease outbreak trends (last 30 days)
    public function outbreaks(Request $request): JsonResponse
    {
        try {
            $outbreaks = Diagnosis::select('disease_name', 'type', DB::raw('count(*) as cases'))
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('disease_name')
                ->where('disease_name', '!=', 'Pending Expert Review')
                ->groupBy('disease_name', 'type')
                ->orderByDesc('cases')
                ->take(10)
                ->get()
                ->map(fn($d) => [
                    'disease'  => $d->disease_name,
                    'type'     => $d->type,
                    'cases'    => $d->cases,
                    'severity' => $d->cases >= 10 ? 'high' : ($d->cases >= 4 ? 'medium' : 'low'),
                ]);
        } catch (\Exception $e) {
            $outbreaks = collect([]);
        }

        return response()->json(['outbreaks' => $outbreaks]);
    }

    // GET /analytics/outcomes — treatment success breakdown
    public function outcomes(Request $request): JsonResponse
    {
        try {
            $total     = Diagnosis::count();
            $confirmed = Diagnosis::where('status', 'confirmed')->count();
            $review    = Diagnosis::where('status', 'needs_review')->count();
            $pending   = Diagnosis::where('status', 'pending')->count();

            $byType = Diagnosis::select('type', 'status', DB::raw('count(*) as count'))
                ->groupBy('type', 'status')
                ->get()
                ->groupBy('type');

            $outcomes = [
                'total'        => $total,
                'confirmed'    => $confirmed,
                'needs_review' => $review,
                'pending'      => $pending,
                'success_rate' => $total > 0 ? round(($confirmed / $total) * 100, 1) : 0,
                'by_type'      => $byType,
            ];
        } catch (\Exception $e) {
            $outcomes = ['total' => 0, 'confirmed' => 0, 'needs_review' => 0, 'pending' => 0, 'success_rate' => 0, 'by_type' => []];
        }

        return response()->json(['outcomes' => $outcomes]);
    }

    // GET /analytics/insurability — farmer credit/insurability score
    public function insurability(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'farmer') {
            return response()->json(['creditScore' => null, 'tier' => null, 'reason' => 'Only available for farmers.']);
        }

        try {
            $animalCount  = Animal::where('user_id', $user->id)->count();
            $scanCount    = Diagnosis::where('user_id', $user->id)->count();
            $consultCount = Consultation::where('farmer_id', $user->id)->count();
            $accountAgeDays = $user->created_at->diffInDays(now());

            // Simple composite score: 0–100
            $score = min(100, (int) round(
                ($animalCount  * 5)  +
                ($scanCount    * 3)  +
                ($consultCount * 4)  +
                (min($accountAgeDays, 365) / 365 * 30)
            ));

            $tier = match(true) {
                $score >= 80 => 'platinum',
                $score >= 60 => 'gold',
                $score >= 40 => 'silver',
                default      => 'bronze',
            };

        } catch (\Exception $e) {
            $score = null;
            $tier  = null;
        }

        return response()->json([
            'creditScore' => $score,
            'tier'        => $tier,
        ]);
    }
}
