<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasSafeDashboardQueries;
use App\Models\User;
use App\Models\Animal;
use App\Models\Feedback;
use App\Models\Finance;
use App\Models\Consultation;
use App\Models\InviteCode;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Diagnosis;
use App\Models\EggProduction;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Subscription;
use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CEOController extends Controller
{
    use HasSafeDashboardQueries;

    // ── Dashboard Pages ──────────────────────────────────────────────
    // Each method below renders one routed CEO dashboard page, calling only
    // the data helpers that page's view actually needs. This replaces the
    // former single index() that computed all ~40 variables for every page
    // load regardless of which section the CEO wanted to see.

    public function overview()
    {
        $kpi    = $this->kpiMetrics();
        $rev    = $this->revenueMetrics();
        $health = $this->platformHealthMetrics($kpi['totalDiagnoses'], $kpi['totalUsers'], $kpi['activeUsers']);

        $data = array_merge(
            $kpi, $rev, $health,
            $this->payRevenueMetrics(),
            $this->aiStatsMetrics(),
            $this->subStatsMetrics(),
            $this->orderStatsMetrics(),
            $this->mrrChurnMetrics(),
            $this->userRegistrationMetrics($kpi['totalUsers']),
            // Phase-by-phase module summaries — compact figures only, the full
            // page for each module is one click away via "View Full Module".
            $this->diseaseAlertsMetrics(),
            $this->riskMetrics($kpi['pendingExperts']),
            $this->walletStatsMetrics(),
            $this->aiAnalyticsSummaryMetrics(),
            $this->topProductsMetrics(),
            $this->logisticsStatsMetrics(),
            $this->consultStatsMetrics($kpi['pendingConsults']),
            $this->geoChartMetrics(),
            $this->geographicSummaryMetrics(),
            $this->usersByRoleMetrics(),
            $this->marketplaceStatsMetrics(),
            $this->systemActivityMetrics()
        );

        $data['dashboardErrors'] = $this->dashboardErrors;

        return view('ceo.pages.overview', $data);
    }

    public function riskCenter()
    {
        $kpi = $this->kpiMetrics();

        $data = array_merge(
            ['pendingExperts' => $kpi['pendingExperts']],
            $this->diseaseAlertsMetrics(),
            $this->riskMetrics($kpi['pendingExperts']),
            $this->orderStatsMetrics(),
            $this->mrrChurnMetrics(),
            $this->walletStatsMetrics()
        );
        $data['dashboardErrors'] = $this->dashboardErrors;

        return view('ceo.pages.risk-center', $data);
    }

    public function financial()
    {
        $data = array_merge(
            $this->payRevenueMetrics(),
            $this->mrrChurnMetrics(),
            $this->revTimeSeriesMetrics(),
            $this->walletStatsMetrics(),
            $this->subStatsMetrics()
        );
        $data['dashboardErrors'] = $this->dashboardErrors;

        return view('ceo.pages.financial', $data);
    }

    public function marketplace()
    {
        $data = array_merge(
            $this->orderStatsMetrics(),
            $this->topProductsMetrics()
        );
        $data['dashboardErrors'] = $this->dashboardErrors;

        return view('ceo.pages.marketplace', $data);
    }

    public function operations()
    {
        $kpi = $this->kpiMetrics();

        $data = array_merge(
            ['pendingExperts' => $kpi['pendingExperts']],
            $this->logisticsStatsMetrics(),
            $this->consultStatsMetrics($kpi['pendingConsults']),
            $this->attendanceMetrics(),
            $this->pendingLeavesMetrics(),
            $this->marketplaceStatsMetrics()
        );
        $data['dashboardErrors'] = $this->dashboardErrors;

        return view('ceo.pages.operations', $data);
    }

    public function geographic()
    {
        $data = array_merge(
            $this->geoChartMetrics(),
            $this->stateActivityMetrics()
        );
        $data['dashboardErrors'] = $this->dashboardErrors;

        return view('ceo.pages.geographic', $data);
    }

    public function usersSubs()
    {
        $kpi = $this->kpiMetrics();

        $data = array_merge(
            $this->userRegistrationMetrics($kpi['totalUsers']),
            ['totalUsers' => $kpi['totalUsers']],
            $this->monthlyGrowthMetrics(),
            $this->usersByRoleMetrics(),
            $this->subStatsMetrics()
        );
        $data['dashboardErrors'] = $this->dashboardErrors;

        return view('ceo.pages.users-subs', $data);
    }

    public function system()
    {
        $kpi    = $this->kpiMetrics();
        $health = $this->platformHealthMetrics($kpi['totalDiagnoses'], $kpi['totalUsers'], $kpi['activeUsers']);

        $data = array_merge(
            ['pendingExperts' => $kpi['pendingExperts']],
            $health,
            $this->marketplaceStatsMetrics(),
            $this->recentUsersMetrics(),
            $this->diseaseAlertsMetrics()
        );
        $data['dashboardErrors'] = $this->dashboardErrors;

        return view('ceo.pages.system', $data);
    }

    // ── Dashboard Data Helpers ───────────────────────────────────────
    // Each helper below is the exact query block that used to live inline
    // in the old index() method, unchanged — just extracted so the route
    // methods above can call only what their page needs.

    private function kpiMetrics(): array
    {
        [$totalUsers, $activeUsers, $pendingExperts] = $this->safe('kpi user counts', function () {
            return [
                User::count(),
                User::where('is_active', true)->count(),
                User::whereIn('role', ['vet','agronomist'])->where('is_verified', false)->count(),
            ];
        }, [0, 0, 0]);

        $totalAnimals = $this->safe('total livestock', fn() => Animal::count());

        [$totalDiagnoses, $pendingConsults] = $this->safe('consultation counts', fn() => [
            Consultation::count(),
            Consultation::where('status','pending')->count(),
        ], [0, 0]);

        return compact('totalUsers','activeUsers','pendingExperts','totalAnimals','totalDiagnoses','pendingConsults');
    }

    private function revenueMetrics(): array
    {
        [$totalRevenue, $totalExpenses, $thisMonthRevenue, $lastMonthRevenue] = $this->safe('revenue totals', fn() => [
            Finance::where('type','Income')->sum('amount'),
            Finance::where('type','Expense')->sum('amount'),
            Finance::where('type','Income')->whereMonth('transaction_date', now()->month)->sum('amount'),
            Finance::where('type','Income')->whereMonth('transaction_date', now()->subMonth()->month)->sum('amount'),
        ], [0, 0, 0, 0]);
        $netProfit     = $totalRevenue - $totalExpenses;
        $revenueGrowth = $lastMonthRevenue > 0
                           ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
                           : 0;

        return compact('totalRevenue','totalExpenses','thisMonthRevenue','lastMonthRevenue','netProfit','revenueGrowth');
    }

    private function usersByRoleMetrics(): array
    {
        $usersByRole = $this->safe('users by role', fn() => User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role'), collect());

        return compact('usersByRole');
    }

    private function monthlyGrowthMetrics(): array
    {
        $monthlyGrowth = $this->safe('monthly growth', fn() => Cache::remember('ceo:monthly_growth', 300, function () {
            return collect(range(5, 0))->map(function ($i) {
                $month = now()->subMonths($i);
                $rows  = User::selectRaw("role, COUNT(*) as count")
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->groupBy('role')
                    ->pluck('count', 'role');
                return [
                    'label'   => $month->format('M'),
                    'farmers' => $rows['farmer'] ?? 0,
                    'experts' => ($rows['vet'] ?? 0) + ($rows['agronomist'] ?? 0),
                    'total'   => $rows->sum(),
                ];
            });
        }), collect(range(5, 0))->map(fn($i) => [
            'label' => now()->subMonths($i)->format('M'), 'farmers' => 0, 'experts' => 0, 'total' => 0,
        ]));

        return compact('monthlyGrowth');
    }

    private function stateActivityMetrics(): array
    {
        $stateActivity = $this->safe('state activity', fn() => User::select('state', DB::raw('count(*) as count'))
            ->whereNotNull('state')->groupBy('state')
            ->orderByDesc('count')->take(6)->pluck('count','state')->toArray(), []);

        return compact('stateActivity');
    }

    private function platformHealthMetrics(int $totalDiagnoses, int $totalUsers, int $activeUsers): array
    {
        $resolvedCases = $this->safe('resolved cases', fn() => Consultation::where('status','resolved')->count());
        $resolutionRate = $totalDiagnoses > 0 ? round(($resolvedCases / $totalDiagnoses) * 100) : 0;
        $activePct      = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;
        $platformHealth = (int) round(($resolutionRate * 0.4) + ($activePct * 0.4) + 20);
        $platformHealth = min(100, max(0, $platformHealth));

        return compact('resolutionRate','activePct','platformHealth');
    }

    private function recentUsersMetrics(): array
    {
        $recentUsers = $this->safe('recent users', fn() => User::latest()->take(8)->get(), collect());

        return compact('recentUsers');
    }

    private function attendanceMetrics(): array
    {
        [$presentToday, $staffCount] = $this->safe('attendance', fn() => [
            Attendance::whereDate('date', today())->where('status','present')->count(),
            User::whereNotIn('role', ['farmer','agro-dealer'])->count(),
        ], [0, 0]);

        return compact('presentToday','staffCount');
    }

    private function pendingLeavesMetrics(): array
    {
        $pendingLeaves = $this->safe('pending leaves', fn() => LeaveRequest::where('status','pending')->count());

        return compact('pendingLeaves');
    }

    private function marketplaceStatsMetrics(): array
    {
        [$marketItems, $pendingListings] = $this->safe('marketplace stats', fn() => [
            Product::where('status','active')->where('is_approved', true)->count(),
            Product::where('is_approved', false)->count(),
        ], [0, 0]);

        return compact('marketItems','pendingListings');
    }

    private function diseaseAlertsMetrics(): array
    {
        $diseaseAlerts = $this->safe('disease alerts', fn() => Diagnosis::select('disease_name', 'type', DB::raw('count(*) as cases'))
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('status', ['pending','reviewed'])
            ->whereNotNull('disease_name')
            ->where('disease_name', '!=', 'Pending Expert Review')
            ->groupBy('disease_name', 'type')
            ->orderByDesc('cases')
            ->take(5)
            ->get()
            ->map(fn($d) => [
                'disease'  => $d->disease_name,
                'cases'    => $d->cases,
                'severity' => $d->cases >= 5 ? 'high' : ($d->cases >= 2 ? 'medium' : 'low'),
                'type'     => $d->type,
            ])
            ->toArray(), []);

        return compact('diseaseAlerts');
    }

    private function subStatsMetrics(): array
    {
        $subStats = $this->safe('subscription stats', function () {
            $planKeys = array_keys(config('subscription.plans', []));
            return Cache::remember('ceo:sub_stats', 120, function () use ($planKeys) {
                $all = Subscription::all();
                $active  = $all->whereIn('status', ['active', 'trial']);
                $byPlan  = $active->groupBy('plan')
                                  ->map(fn($g) => $g->count());
                $revenue = Subscription::where('status', 'active')
                    ->selectRaw('plan, SUM(amount_paid) as total')
                    ->groupBy('plan')
                    ->pluck('total', 'plan');

                $thisMonth = now()->format('Y-m');
                $lastMonth = now()->subMonth()->format('Y-m');
                $newThisMonth = Subscription::where('status', 'active')
                    ->whereRaw("TO_CHAR(created_at, 'YYYY-MM') = ?", [$thisMonth])
                    ->count();
                $newLastMonth = Subscription::where('status', 'active')
                    ->whereRaw("TO_CHAR(created_at, 'YYYY-MM') = ?", [$lastMonth])
                    ->count();

                return [
                    'total'         => $all->count(),
                    'active'        => $all->where('status', 'active')->count(),
                    'trial'         => $all->where('status', 'trial')->count(),
                    'expired'       => $all->where('status', 'expired')->count(),
                    'cancelled'     => $all->where('status', 'cancelled')->count(),
                    'suspended'     => $all->where('status', 'suspended')->count(),
                    'by_plan'       => $byPlan->toArray(),
                    'revenue_total' => $all->sum('amount_paid'),
                    'revenue_month' => Subscription::whereRaw("TO_CHAR(created_at, 'YYYY-MM') = ?", [$thisMonth])->sum('amount_paid'),
                    'revenue_by_plan' => $revenue->toArray(),
                    'new_this_month'  => $newThisMonth,
                    'growth_pct'    => $newLastMonth > 0 ? round((($newThisMonth - $newLastMonth) / $newLastMonth) * 100, 1) : 0,
                ];
            });
        }, [
            'total' => 0, 'active' => 0, 'trial' => 0, 'expired' => 0,
            'cancelled' => 0, 'suspended' => 0, 'by_plan' => [],
            'revenue_total' => 0, 'revenue_month' => 0, 'revenue_by_plan' => [],
            'new_this_month' => 0, 'growth_pct' => 0,
        ]);

        return compact('subStats');
    }

    private function orderStatsMetrics(): array
    {
        $orderStats = $this->safe('order stats', fn() => Cache::remember('ceo:order_stats', 120, function () {
            return [
                'total'      => Order::count(),
                'pending'    => Order::where('status', 'pending')->count(),
                'processing' => Order::where('status', 'processing')->count(),
                'shipped'    => Order::where('status', 'shipped')->count(),
                'delivered'  => Order::where('status', 'delivered')->count(),
                'cancelled'  => Order::where('status', 'cancelled')->count(),
                'gmv'        => Order::where('payment_status', 'paid')->sum('total'),
                'gmv_month'  => Order::where('payment_status', 'paid')
                                    ->whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->sum('total'),
            ];
        }), ['total'=>0,'pending'=>0,'processing'=>0,'shipped'=>0,'delivered'=>0,'cancelled'=>0,'gmv'=>0,'gmv_month'=>0]);

        return compact('orderStats');
    }

    private function topProductsMetrics(): array
    {
        $topProducts = $this->safe('top products', fn() => Product::select('id', 'name', 'selling_price', 'category')
            ->selectRaw('(SELECT COUNT(*) FROM order_items WHERE order_items.product_id = products.id) as order_count')
            ->where('status', 'active')
            ->orderByDesc('order_count')
            ->take(5)
            ->get(), collect());

        return compact('topProducts');
    }

    private function aiStatsMetrics(): array
    {
        $aiStats = $this->safe('ai stats', fn() => Cache::remember('ceo:ai_stats', 120, function () {
            return [
                'today'       => Diagnosis::whereDate('created_at', today())->count(),
                'this_month'  => Diagnosis::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'total'       => Diagnosis::count(),
                'avg_conf'    => round((float) Diagnosis::whereNotNull('confidence_score')->avg('confidence_score'), 1),
                'crop_total'  => Diagnosis::where('type', 'crop')->count(),
                'live_total'  => Diagnosis::where('type', 'livestock')->count(),
                'soil_total'  => Diagnosis::where('type', 'soil')->count(),
                'top_diseases'=> Diagnosis::select('disease_name', DB::raw('count(*) as cnt'))
                                    ->whereNotNull('disease_name')
                                    ->where('disease_name', '!=', 'Pending Expert Review')
                                    ->where('created_at', '>=', now()->subDays(30))
                                    ->groupBy('disease_name')
                                    ->orderByDesc('cnt')
                                    ->take(6)
                                    ->get(),
            ];
        }), ['today'=>0,'this_month'=>0,'total'=>0,'avg_conf'=>0,'crop_total'=>0,'live_total'=>0,'soil_total'=>0,'top_diseases'=>collect()]);

        return compact('aiStats');
    }

    private function consultStatsMetrics(int $pendingConsults): array
    {
        $consultStats = $this->safe('consultation stats', fn() => [
            'pending'     => Consultation::where('status', 'pending')->count(),
            'in_progress' => Consultation::where('status', 'in-progress')->count(),
            'completed'   => Consultation::where('status', 'completed')->count(),
            'avg_hours'   => round((float) Consultation::whereNotNull('completed_at')
                                ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at)) / 3600) as avg_h')
                                ->value('avg_h'), 1),
        ], ['pending'=>$pendingConsults,'in_progress'=>0,'completed'=>0,'avg_hours'=>0]);

        return compact('consultStats');
    }

    private function logisticsStatsMetrics(): array
    {
        $logisticsStats = $this->safe('logistics stats', fn() => [
            'pending_dispatch' => Order::whereIn('status', ['confirmed','processing'])->whereNull('rider_id')->count(),
            'riders_available' => User::where('role', 'rider')->where('rider_status', 'available')->count(),
            'riders_busy'      => User::where('role', 'rider')->where('rider_status', 'busy')->count(),
            'in_transit'       => Order::where('rider_status', 'in_transit')->count(),
            'delivered'        => Order::where('status', 'delivered')->count(),
        ], ['pending_dispatch'=>0,'riders_available'=>0,'riders_busy'=>0,'in_transit'=>0,'delivered'=>0]);

        return compact('logisticsStats');
    }

    private function payRevenueMetrics(): array
    {
        $payRevenue = $this->safe('payment revenue', fn() => Cache::remember('ceo:pay_revenue', 120, function () {
            return [
                'today' => Payment::successful()->whereDate('paid_at', today())->sum('amount'),
                'week'  => Payment::successful()->where('paid_at', '>=', now()->startOfWeek())->sum('amount'),
                'month' => Payment::successful()->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount'),
                'year'  => Payment::successful()->whereYear('paid_at', now()->year)->sum('amount'),
                'total' => Payment::successful()->sum('amount'),
            ];
        }), ['today'=>0,'week'=>0,'month'=>0,'year'=>0,'total'=>0]);

        return compact('payRevenue');
    }

    private function revTimeSeriesMetrics(): array
    {
        $revTimeSeries = $this->safe('revenue time series', fn() => Cache::remember('ceo:rev_timeseries', 120, function () {
            // Daily: last 14 days
            $dailyRaw = Payment::successful()
                ->where('paid_at', '>=', now()->subDays(13)->startOfDay())
                ->selectRaw("DATE(paid_at) as d, SUM(amount) as total")
                ->groupBy(DB::raw('DATE(paid_at)'))
                ->pluck('total', 'd');
            $daily = collect(range(13, 0))->map(fn($i) => [
                'label' => now()->subDays($i)->format('M d'),
                'value' => (float) ($dailyRaw[now()->subDays($i)->format('Y-m-d')] ?? 0),
            ]);

            // Weekly: last 8 weeks
            $weekly = collect(range(7, 0))->map(function ($i) {
                $start = now()->startOfWeek()->subWeeks($i);
                $end   = (clone $start)->endOfWeek();
                return [
                    'label' => $start->format('M d'),
                    'value' => (float) Payment::successful()->whereBetween('paid_at', [$start, $end])->sum('amount'),
                ];
            });

            // Monthly: last 12 months
            $monthly = collect(range(11, 0))->map(function ($i) {
                $m = now()->subMonths($i);
                return [
                    'label' => $m->format('M Y'),
                    'value' => (float) Payment::successful()->whereYear('paid_at', $m->year)->whereMonth('paid_at', $m->month)->sum('amount'),
                ];
            });

            return compact('daily', 'weekly', 'monthly');
        }), [
            'daily'   => collect(range(13,0))->map(fn($i) => ['label' => now()->subDays($i)->format('M d'), 'value' => 0]),
            'weekly'  => collect(range(7,0))->map(fn($i) => ['label' => now()->subWeeks($i)->format('M d'), 'value' => 0]),
            'monthly' => collect(range(11,0))->map(fn($i) => ['label' => now()->subMonths($i)->format('M Y'), 'value' => 0]),
        ]);

        return compact('revTimeSeries');
    }

    private function geoChartMetrics(): array
    {
        $geoChart = $this->safe('geo chart', fn() => Cache::remember('ceo:geo_chart', 300, function () {
            $states = User::whereNotNull('state')
                ->select('state', DB::raw('count(*) as users'))
                ->groupBy('state')
                ->orderByDesc('users')
                ->take(8)
                ->pluck('users', 'state');

            $diseaseCounts = Diagnosis::join('users', 'diagnoses.user_id', '=', 'users.id')
                ->whereIn('users.state', $states->keys()->toArray())
                ->select('users.state', DB::raw('count(*) as diagnoses'))
                ->groupBy('users.state')
                ->pluck('diagnoses', 'users.state');

            return $states->mapWithKeys(fn($cnt, $state) => [
                $state => ['users' => $cnt, 'diagnoses' => (int)($diseaseCounts[$state] ?? 0)],
            ]);
        }), collect());

        return compact('geoChart');
    }

    private function mrrChurnMetrics(): array
    {
        [$mrr, $arr, $churnRate, $conversionRate] = $this->safe('MRR/churn', function () {
            $plans = config('subscription.plans', []);
            $activeSubsByPlan = Subscription::where('status', 'active')
                ->select('plan', DB::raw('count(*) as cnt'))
                ->groupBy('plan')
                ->pluck('cnt', 'plan');

            $mrr = 0;
            foreach ($plans as $key => $plan) {
                $mrr += (($plan['price']['monthly'] ?? 0) * ($activeSubsByPlan[$key] ?? 0));
            }
            $arr = $mrr * 12;

            $expiredThisMonth    = Subscription::whereIn('status', ['expired','cancelled'])
                ->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();
            $activeStartOfMonth  = Subscription::where('status', 'active')
                ->where('created_at', '<', now()->startOfMonth())->count();
            $churnRate           = $activeStartOfMonth > 0
                ? round(($expiredThisMonth / $activeStartOfMonth) * 100, 1) : 0;

            $totalSubs           = Subscription::whereIn('status', ['active','trial','expired','cancelled'])->count();
            $activeSubs          = Subscription::where('status', 'active')->count();
            $conversionRate      = $totalSubs > 0 ? round(($activeSubs / $totalSubs) * 100, 1) : 0;

            return [$mrr, $arr, $churnRate, $conversionRate];
        }, [0, 0, 0, 0]);

        return compact('mrr','arr','churnRate','conversionRate');
    }

    private function userRegistrationMetrics(int $totalUsers): array
    {
        [$newUsersToday, $newUsersWeek, $verifiedUsers, $verifyRate] = $this->safe('user registration', function () use ($totalUsers) {
            $newUsersToday = User::whereDate('created_at', today())->count();
            $newUsersWeek  = User::where('created_at', '>=', now()->startOfWeek())->count();
            $verifiedUsers = User::where('is_verified', true)->count();
            $verifyRate    = $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100) : 0;
            return [$newUsersToday, $newUsersWeek, $verifiedUsers, $verifyRate];
        }, [0, 0, 0, 0]);

        return compact('newUsersToday','newUsersWeek','verifiedUsers','verifyRate');
    }

    private function walletStatsMetrics(): array
    {
        $walletStats = $this->safe('wallet stats', fn() => [
            'total_balance'       => \App\Models\Wallet::sum('balance'),
            'pending_withdrawals' => DB::table('wallet_transactions')
                                        ->where('type', 'withdrawal')
                                        ->where('status', 'pending')
                                        ->count(),
            'withdrawals_value'   => DB::table('wallet_transactions')
                                        ->where('type', 'withdrawal')
                                        ->where('status', 'pending')
                                        ->sum('amount'),
        ], ['total_balance' => 0, 'pending_withdrawals' => 0, 'withdrawals_value' => 0]);

        return compact('walletStats');
    }

    private function riskMetrics(int $pendingExperts): array
    {
        [$failedPaymentsToday, $pendingVerifications] = $this->safe('risk metrics', function () {
            $failedPaymentsToday = Payment::where('status', 'failed')
                ->whereDate('created_at', today())
                ->count();
            $pendingVerifications = User::whereIn('role', [
                'vet','agronomist','agro-dealer','equipment-dealer','agribusiness-owner',
                'cooperative','government-agency','ngo','research-institution',
                'input-supplier','logistics-provider','investor',
            ])->where('application_status', 'pending')->count();
            return [$failedPaymentsToday, $pendingVerifications];
        }, [0, $pendingExperts]);

        return compact('failedPaymentsToday','pendingVerifications');
    }

    // Overview-page-only summary metrics — compact figures for the phase-by-phase
    // executive summary, distinct from the full CeoScanAnalyticsController data
    // (which has filters/drill-down) and from aiStatsMetrics() above (which still
    // carries the legacy crop/livestock split kept only for the old pulse tile).
    private function aiAnalyticsSummaryMetrics(): array
    {
        $aiSummary = $this->safe('ai analytics summary', fn() => [
            'total'          => Diagnosis::count(),
            'today'          => Diagnosis::whereDate('created_at', today())->count(),
            'week'           => Diagnosis::where('created_at', '>=', now()->subDays(6)->startOfDay())->count(),
            'month'          => Diagnosis::where('created_at', '>=', now()->subDays(29)->startOfDay())->count(),
            'avg_confidence' => round((float) Diagnosis::whereNotNull('confidence_score')->avg('confidence_score'), 1),
            'pending_review' => Diagnosis::where('status', 'pending')->count(),
            'failed'         => Diagnosis::where('status', 'needs_review')->count(),
        ], ['total'=>0,'today'=>0,'week'=>0,'month'=>0,'avg_confidence'=>0,'pending_review'=>0,'failed'=>0]);

        $aiTopStates = $this->safe('ai analytics top states', fn() => Diagnosis::join('users', 'diagnoses.user_id', '=', 'users.id')
            ->whereNotNull('users.state')
            ->select('users.state', DB::raw('count(*) as cnt'))
            ->groupBy('users.state')
            ->orderByDesc('cnt')
            ->take(5)
            ->get(), collect());

        return compact('aiSummary', 'aiTopStates');
    }

    private function geographicSummaryMetrics(): array
    {
        [$statesCovered, $lgasCovered] = $this->safe('geographic coverage', fn() => [
            User::whereNotNull('state')->distinct('state')->count('state'),
            User::whereNotNull('lga')->distinct('lga')->count('lga'),
        ], [0, 0]);

        return compact('statesCovered', 'lgasCovered');
    }

    private function systemActivityMetrics(): array
    {
        $recentAuditLogs = $this->safe('recent audit logs', fn() => \App\Models\AuditLog::with('user:id,first_name,last_name')
            ->latest()
            ->take(5)
            ->get(), collect());

        return compact('recentAuditLogs');
    }

    // ── User Management ────────────────────────────────────────────
    public function users()
    {
        $query = User::latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name',  'ilike', "%{$search}%")
                  ->orWhere('last_name',  'ilike', "%{$search}%")
                  ->orWhere('email',      'ilike', "%{$search}%")
                  ->orWhere('phone',      'ilike', "%{$search}%");
            });
        }
        if ($role = request('role')) {
            $query->where('role', $role);
        }
        if (request('status') !== null && request('status') !== '') {
            $query->where('is_active', (bool) request('status'));
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = User::select('role')->distinct()->orderBy('role')->pluck('role');

        return view('ceo.users', compact('users', 'roles'));
    }

    public function showUser(User $user)
    {
        return view('ceo.users.show', compact('user'));
    }

    public function editUser(User $user)
    {
        $allRoles = [
            'farmer','vet','agronomist','agro-dealer','equipment-dealer','logistics-provider',
            'agribusiness-owner','input-supplier','cooperative','government-agency','ngo',
            'research-institution','investor','extension-officer','field-officer',
            'data-analyst','m-e-officer','customer-support','hr','finance','operations',
            'general-user','admin','ceo','financial-institution',
        ];
        return view('ceo.users.edit', compact('user', 'allRoles'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'middle_name'        => 'nullable|string|max:100',
            'email'              => 'required|email|max:200|unique:users,email,'.$user->id,
            'phone'              => 'nullable|string|max:30',
            'role'               => 'required|string',
            'state'              => 'nullable|string|max:100',
            'lga'                => 'nullable|string|max:100',
            'is_active'          => 'boolean',
            'is_verified'        => 'boolean',
            'application_status' => 'nullable|in:pending,approved,rejected',
        ]);

        // Prevent demoting the only CEO
        if ($user->role === 'ceo' && $request->role !== 'ceo') {
            $ceoCount = User::where('role', 'ceo')->count();
            if ($ceoCount <= 1) {
                return back()->with('error', 'Cannot change role: this is the only CEO account.');
            }
        }

        $user->update($request->only([
            'first_name','last_name','middle_name','email','phone',
            'role','state','lga','is_active','is_verified','application_status',
        ]));

        return redirect()->route('ceo.users.show', $user)
            ->with('success', "Profile for {$user->name} updated successfully.");
    }

    public function toggleUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot suspend your own account.');
        }
        $user->update(['is_active' => !$user->is_active]);
        $state = $user->is_active ? 'activated' : 'suspended';
        return back()->with('success', "{$user->name} has been {$state}.");
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->role === 'ceo') {
            $ceoCount = User::where('role', 'ceo')->count();
            if ($ceoCount <= 1) {
                return back()->with('error', 'Cannot delete the only CEO account.');
            }
        }
        $name = $user->name;
        $user->delete();
        return redirect()->route('ceo.users')->with('success', "User \"{$name}\" has been permanently deleted.");
    }

    public function approveExpert(User $user)
    {
        $user->update(['is_verified' => true]);
        return back()->with('success', "{$user->name} approved as {$user->role}.");
    }

    // ── System Audit ───────────────────────────────────────────────
    public function audit()
    {
        $checks = [];

        // 1. Database tables
        $tables = [
            'users', 'animals', 'consultations', 'diagnoses', 'finance',
            'products', 'subscriptions', 'subscription_usages', 'notifications',
            'attendances', 'leave_requests', 'egg_productions',
        ];
        $tableGroup = [];
        foreach ($tables as $t) {
            $exists = \Illuminate\Support\Facades\Schema::hasTable($t);
            $tableGroup[] = ['name' => $t, 'ok' => $exists, 'detail' => $exists ? 'exists' : 'MISSING'];
        }
        $checks['Database Tables'] = $tableGroup;

        // 2. Subscription plans
        $plans = config('subscription.plans', []);
        $planGroup = [];
        $requiredPlans = ['basic', 'basic_pro', 'premium', 'enterprise', 'enterprise_plus'];
        foreach ($requiredPlans as $p) {
            $found = isset($plans[$p]);
            $price = $found ? '₦' . number_format($plans[$p]['price']['monthly'] ?? 0) . '/mo' : '—';
            $planGroup[] = ['name' => $p, 'ok' => $found, 'detail' => $found ? $price : 'MISSING from config'];
        }
        $checks['Subscription Plans'] = $planGroup;

        // 3. Language files
        $langGroup = [];
        $locales = ['en' => 'English', 'ha' => 'Hausa', 'yo' => 'Yorùbá', 'ig' => 'Igbo', 'fr' => 'Français'];
        $enKeys  = [];
        foreach ($locales as $code => $name) {
            $path   = lang_path($code . '.json');
            $exists = file_exists($path);
            $count  = 0;
            if ($exists) {
                $data  = json_decode(file_get_contents($path), true) ?: [];
                $count = count($data);
                if ($code === 'en') $enKeys = array_keys($data);
            }
            $missing = ($code !== 'en' && $enKeys) ? count(array_diff($enKeys, array_keys(json_decode(file_exists($path) ? file_get_contents($path) : '{}', true) ?: []))) : 0;
            $ok      = $exists && $count > 0 && $missing === 0;
            $detail  = $exists ? "{$count} keys" . ($missing > 0 ? ", {$missing} missing" : '') : 'FILE MISSING';
            $langGroup[] = ['name' => $name . " ({$code})", 'ok' => $ok, 'detail' => $detail];
        }
        $checks['Language Files'] = $langGroup;

        // 4. Key routes
        $routeGroup = [];
        $namedRoutes = [
            'ceo.dashboard', 'ceo.users', 'ceo.audit', 'ceo.reports',
            'farmer.dashboard', 'subscription.plans', 'subscription.dashboard',
            'diagnostics.history', 'diagnostics.translate',
            'locale.set', 'login', 'register',
        ];
        foreach ($namedRoutes as $r) {
            $ok = \Illuminate\Support\Facades\Route::has($r);
            $routeGroup[] = ['name' => $r, 'ok' => $ok, 'detail' => $ok ? 'registered' : 'MISSING'];
        }
        $checks['Named Routes'] = $routeGroup;

        // 5. App environment
        $envGroup = [];
        $envChecks = [
            'APP_KEY'      => config('app.key') !== null && config('app.key') !== '',
            'APP_URL'      => config('app.url') !== 'http://localhost',
            'DB_HOST'      => config('database.connections.pgsql.host') !== null,
            'PAYSTACK_KEY' => config('services.paystack.secret_key') && !str_contains(config('services.paystack.secret_key', ''), 'REPLACE'),
        ];
        foreach ($envChecks as $key => $ok) {
            $envGroup[] = ['name' => $key, 'ok' => $ok, 'detail' => $ok ? 'set' : 'not configured'];
        }
        $checks['Environment'] = $envGroup;

        // 6. Recent errors from log (last 20 lines)
        $logPath   = storage_path('logs/laravel.log');
        $logErrors = [];
        if (file_exists($logPath)) {
            $lines = array_slice(file($logPath), -80);
            foreach ($lines as $line) {
                if (str_contains($line, '.ERROR') || str_contains($line, '.CRITICAL')) {
                    $logErrors[] = trim($line);
                    if (count($logErrors) >= 10) break;
                }
            }
        }

        // 7. User stats
        $userStats = [
            'total'    => \App\Models\User::count(),
            'active'   => \App\Models\User::where('is_active', true)->count(),
            'trial'    => \App\Models\User::whereHas('subscriptions', fn($q) => $q->where('status','trial'))->count(),
            'paid'     => \App\Models\User::whereHas('subscriptions', fn($q) => $q->where('status','active'))->count(),
        ];

        $auditAt = now()->format('d M Y H:i:s T');

        return view('ceo.audit', compact('checks', 'logErrors', 'userStats', 'auditAt'));
    }

    // ── Reports ────────────────────────────────────────────────────
    public function reports()
    {
        return view('ceo.reports');
    }

    public function generateReport($type)
    {
        $data = match($type) {
            'financial' => [
                'title'    => 'Financial Summary Report',
                'columns'  => ['Description', 'Type', 'Amount', 'Date'],
                'income'   => Finance::where('type','Income')->sum('amount'),
                'expenses' => Finance::where('type','Expense')->sum('amount'),
                'records'  => Finance::latest()->take(50)->get(),
                'row_keys' => ['description', 'type', 'amount', 'transaction_date'],
            ],
            'users' => [
                'title'   => 'User Activity Report',
                'columns' => ['Name', 'Role', 'Email', 'State', 'Active', 'Joined'],
                'records' => User::latest()->get(),
                'row_keys'=> ['name', 'role', 'email', 'state', 'is_active', 'created_at'],
            ],
            'farmers' => [
                'title'   => 'Farmer Registration Report',
                'columns' => ['Name', 'Email', 'State', 'LGA', 'Phone', 'Verified', 'Joined'],
                'records' => User::where('role','farmer')->latest()->get(),
                'row_keys'=> ['name', 'email', 'state', 'lga', 'phone', 'is_verified', 'created_at'],
            ],
            'animals', 'livestock' => [
                'title'   => 'Livestock Report',
                'columns' => ['Name', 'Species', 'Breed', 'Owner', 'Health Status', 'Registered'],
                'records' => Animal::with('user')->latest()->get(),
                'row_keys'=> ['name', 'species', 'breed', 'user.name', 'health_status', 'created_at'],
            ],
            'diseases' => [
                'title'   => 'Disease Incidence & Expert Interventions Report',
                'columns' => ['Farmer', 'Case Type', 'Status', 'Submitted', 'Updated'],
                'records' => Consultation::with('farmer')->latest()->get(),
                'row_keys'=> ['farmer.name', 'case_type', 'status', 'created_at', 'updated_at'],
            ],
            'geographic' => [
                'title'   => 'Geographic Distribution Report',
                'columns' => ['State', 'Total Users'],
                'records' => User::select('state', DB::raw('count(*) as count'))->groupBy('state')->orderByDesc('count')->get(),
                'row_keys'=> ['state', 'count'],
            ],
            default => abort(404),
        };

        return view('ceo.report-preview', compact('data','type'));
    }

    // ── CSV Export ─────────────────────────────────────────────────
    public function exportCsv(string $type)
    {
        $data = match($type) {
            'financial' => [
                'filename' => 'financial-report',
                'columns'  => ['Description', 'Type', 'Amount', 'Date'],
                'records'  => Finance::latest()->get(),
                'row'      => fn($r) => [$r->description, $r->type, $r->amount, $r->transaction_date],
            ],
            'users' => [
                'filename' => 'users-report',
                'columns'  => ['Name', 'Role', 'Email', 'Phone', 'State', 'Active', 'Joined'],
                'records'  => User::latest()->get(),
                'row'      => fn($r) => [$r->first_name.' '.$r->last_name, $r->role, $r->email, $r->phone, $r->state, $r->is_active ? 'Yes' : 'No', $r->created_at->toDateString()],
            ],
            'farmers' => [
                'filename' => 'farmers-report',
                'columns'  => ['Name', 'Email', 'Phone', 'State', 'LGA', 'Verified', 'Joined'],
                'records'  => User::where('role', 'farmer')->latest()->get(),
                'row'      => fn($r) => [$r->first_name.' '.$r->last_name, $r->email, $r->phone, $r->state, $r->lga, $r->is_verified ? 'Yes' : 'No', $r->created_at->toDateString()],
            ],
            'diseases' => [
                'filename' => 'disease-report',
                'columns'  => ['Farmer', 'Case Type', 'Status', 'Submitted', 'Completed'],
                'records'  => Consultation::with('farmer')->latest()->get(),
                'row'      => fn($r) => [$r->farmer?->first_name.' '.$r->farmer?->last_name, $r->case_type, $r->status, $r->created_at->toDateString(), $r->completed_at?->toDateString() ?? '-'],
            ],
            'geographic' => [
                'filename' => 'geographic-report',
                'columns'  => ['State', 'Total Users'],
                'records'  => User::select('state', DB::raw('count(*) as count'))->groupBy('state')->orderByDesc('count')->get(),
                'row'      => fn($r) => [$r->state ?? 'Unknown', $r->count],
            ],
            default => abort(404),
        };

        $filename = $data['filename'] . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $data['columns']);
            foreach ($data['records'] as $record) {
                fputcsv($handle, ($data['row'])($record));
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function aiStatus()
    {
        $baseUrl = rtrim(config('services.ai_engine.url', ''), '/');
        $aiKey   = config('services.ai_engine.key', '');

        // ── Health check (no auth required) ───────────────────────────────────
        $health     = null;
        $latency    = null;
        $error      = null;
        $rawBody    = null;
        $httpStatus = null;

        // ── Auth test (POST to /predict/crop with 1×1 JPEG, checks Bearer key) ─
        $authStatus  = null;
        $authBody    = null;
        $authError   = null;
        $authLatency = null;

        if ($baseUrl) {
            $guzzle = new GuzzleClient(['connect_timeout' => 10, 'timeout' => 30, 'http_errors' => false]);
            $authHeaders = [];
            if ($aiKey && $aiKey !== 'REPLACE_WITH_AI_ENGINE_KEY') {
                $authHeaders['Authorization'] = "Bearer {$aiKey}";
            }

            // 1. Health check
            try {
                $t0      = microtime(true);
                $resp    = $guzzle->get("{$baseUrl}/health", ['headers' => $authHeaders]);
                $latency = round((microtime(true) - $t0) * 1000);

                $httpStatus = $resp->getStatusCode();
                $rawBody    = (string) $resp->getBody();
                $health     = json_decode($rawBody, true);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }

            // 2. Auth test — POST a small but valid green-leaf JPEG to /predict/crop.
            // This verifies the Bearer key is accepted AND that Claude can process an image.
            // 8×8 solid green JPEG — small enough to be fast, large enough for Claude.
            $minimalJpeg = base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoH'
                . 'BwYIDAoMCwsKCwsNCxAQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQME'
                . 'BAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU'
                . 'FBQUFBQUFBT/wAARCAAIAAgDASIAAhEBAxEB/8QAFgABAQEAAAAAAAAAAAAAAAAA'
                . 'CAUD/8QAIhAAAQQCAgMBAAAAAAAAAAAAAQIDBBESITFBUWH/xAAUAQEAAAAAAAAA'
                . 'AAAAAAAAAAD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCt2tpVW'
                . 'ZqJibLXHGxrGNDWtaMAD2AHoiICIiAiIgIiIP/9k='
            );

            try {
                $boundary = '----MSASAuthTest' . bin2hex(random_bytes(8));
                $body     = "--{$boundary}\r\n"
                    . "Content-Disposition: form-data; name=\"images\"; filename=\"test.jpg\"\r\n"
                    . "Content-Type: image/jpeg\r\n\r\n"
                    . $minimalJpeg . "\r\n"
                    . "--{$boundary}--\r\n";

                $t0 = microtime(true);
                $resp = $guzzle->post("{$baseUrl}/predict/crop", [
                    'headers' => array_merge($authHeaders, [
                        'Content-Type' => "multipart/form-data; boundary={$boundary}",
                    ]),
                    'body' => $body,
                ]);
                $authLatency = round((microtime(true) - $t0) * 1000);
                $authStatus  = $resp->getStatusCode();
                $authBody    = (string) $resp->getBody();
            } catch (\Throwable $e) {
                $authError = $e->getMessage();
            }
        }

        $recentFailed = \App\Models\Diagnosis::where('status', 'needs_review')
            ->whereNull('subject_name')
            ->latest()
            ->take(5)
            ->get();

        return view('ceo.ai-status', compact(
            'baseUrl', 'aiKey', 'health', 'latency', 'error',
            'rawBody', 'httpStatus',
            'authStatus', 'authBody', 'authError', 'authLatency',
            'recentFailed'
        ));
    }

    // ── Order Oversight ──────────────────────────────────────────────────────────

    public function orders(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\Order::with([
            'buyer:id,first_name,last_name,phone',
            'dealer:id,first_name,last_name',
            'rider:id,first_name,last_name,phone',
            'assignedBy:id,first_name,last_name',
        ])->latest();

        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($riderStatus = $request->rider_status) {
            $query->where('rider_status', $riderStatus);
        }
        if ($search = $request->search) {
            $query->where('order_number', 'ilike', "%{$search}%");
        }

        $orders = $query->paginate(25)->withQueryString();

        $stats = [
            'total'      => \App\Models\Order::count(),
            'pending'    => \App\Models\Order::where('status', 'pending')->count(),
            'unassigned' => \App\Models\Order::whereIn('status', ['confirmed','processing'])->whereNull('rider_id')->count(),
            'in_transit' => \App\Models\Order::where('rider_status', 'in_transit')->count(),
            'delivered'  => \App\Models\Order::where('status', 'delivered')->count(),
            'cancelled'  => \App\Models\Order::where('status', 'cancelled')->count(),
            'revenue'    => \App\Models\Order::where('payment_status', 'paid')->sum('total'),
        ];

        $riders = \App\Models\User::where('role', 'rider')->where('is_active', true)
            ->orderBy('first_name')->get(['id','first_name','last_name','rider_status']);

        return view('ceo.orders', compact('orders', 'stats', 'riders'));
    }

    public function assignOrderRider(\Illuminate\Http\Request $request, \App\Models\Order $order)
    {
        $request->validate(['rider_id' => 'required|exists:users,id']);
        $rider = \App\Models\User::findOrFail($request->rider_id);

        if ($order->rider_id && $order->rider_id !== $rider->id) {
            optional($order->rider)->update(['rider_status' => 'available']);
        }

        $order->update([
            'rider_id'          => $rider->id,
            'assigned_by'       => auth()->id(),
            'rider_status'      => 'assigned',
            'rider_assigned_at' => now(),
            'status'            => 'processing',
        ]);

        $rider->update(['rider_status' => 'busy']);

        \App\Models\Notification::create([
            'user_id' => $rider->id,
            'title'   => 'CEO Assignment',
            'message' => "Order {$order->order_number} has been assigned to you by the CEO.",
            'type'    => 'info',
            'link'    => '/rider/orders/' . $order->id,
        ]);

        \App\Models\AuditLog::record('order.rider_assigned_by_ceo', 'Order', $order->id, [
            'rider_id' => $rider->id, 'ceo_id' => auth()->id(),
        ]);

        return back()->with('success', "Order {$order->order_number} assigned to {$rider->first_name} {$rider->last_name}.");
    }

    // ── Phase 2: Pilot Program ─────────────────────────────────────────────────

    public function pilot()
    {
        $pilots = User::where('role', 'farmer')
            ->where(fn($q) => $q->where('is_pilot', true)->orWhere('created_at', '>=', now()->subDays(30)))
            ->with(['subscriptions' => fn($q) => $q->latest()])
            ->withCount(['diagnoses as scan_count', 'consultations as consult_count'])
            ->latest()
            ->paginate(25);

        $pilotCount = User::where('is_pilot', true)->count();
        $newThisWeek = User::where('role', 'farmer')->where('created_at', '>=', now()->subDays(7))->count();

        return view('ceo.pilot', compact('pilots', 'pilotCount', 'newThisWeek'));
    }

    public function flagPilot(Request $request, User $user)
    {
        $user->update(['is_pilot' => ! $user->is_pilot]);
        $action = $user->fresh()->is_pilot ? 'flagged as pilot' : 'removed from pilot';
        return back()->with('success', "{$user->first_name} {$user->last_name} has been {$action}.");
    }

    public function feedback(Request $request)
    {
        $query = Feedback::with('user:id,first_name,last_name,role')->latest();
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('type'))   $query->where('type', $request->type);
        $feedbacks = $query->paginate(30);
        $counts = [
            'total'    => Feedback::count(),
            'new'      => Feedback::where('status', 'new')->count(),
            'reviewed' => Feedback::where('status', 'reviewed')->count(),
            'resolved' => Feedback::where('status', 'resolved')->count(),
        ];
        return view('ceo.feedback', compact('feedbacks', 'counts'));
    }

    public function updateFeedback(Request $request, Feedback $feedback)
    {
        $data = $request->validate([
            'status'      => 'required|in:new,reviewed,resolved',
            'admin_notes' => 'nullable|string|max:1000',
        ]);
        $feedback->update($data);
        return back()->with('success', 'Feedback updated.');
    }

    public function inviteCodes()
    {
        $codes = InviteCode::with('creator:id,first_name,last_name')
            ->latest()
            ->paginate(30);
        $plans = config('subscription.plans', []);
        return view('ceo.invite-codes', compact('codes', 'plans'));
    }

    public function storeInviteCode(Request $request)
    {
        $validPlans = implode(',', array_keys(config('subscription.plans', [])));
        $data = $request->validate([
            'plan'       => "required|in:{$validPlans}",
            'max_uses'   => 'required|integer|min:1|max:500',
            'expires_at' => 'nullable|date|after:today',
        ]);

        InviteCode::create([
            'code'       => InviteCode::generate(),
            'created_by' => auth()->id(),
            'plan'       => $data['plan'],
            'max_uses'   => $data['max_uses'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Invite code created successfully.');
    }

    public function deleteInviteCode(InviteCode $code)
    {
        $code->delete();
        return back()->with('success', 'Invite code deleted.');
    }
}
