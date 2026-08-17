<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // ── Dashboard query error tracking ──────────────────────────────
    // Dashboard queries have historically swallowed failures and shown 0/empty
    // instead of surfacing them (confirmed across all 22 dashboards during the
    // role/permission audit). safe() preserves that same fallback behavior so
    // a broken query still can't crash the page, but also records which stat
    // failed so the view can show a real error banner instead of a silent 0.
    private array $dashboardErrors = [];

    private function safe(string $label, \Closure $query, mixed $fallback = 0): mixed
    {
        try {
            return $query();
        } catch (\Throwable $e) {
            Log::error("[Dashboard:{$label}] " . $e->getMessage());
            $this->dashboardErrors[] = $label;
            return $fallback;
        }
    }

    // ── Role-Based Dashboard Router ────────────────────────────────
    public function dispatch()
    {
        $user = auth()->user();
        $route = match($user->role ?? '') {
            'ceo'                   => 'ceo.dashboard',
            'admin'                 => 'admin.dashboard',
            'farmer'                => 'farmer.dashboard',
            'vet'                   => 'vet.dashboard',
            'agronomist'            => 'agronomist.dashboard',
            'agro-dealer'           => 'dealer.dashboard',
            'equipment-dealer'      => 'equipment-dealer.dashboard',
            'cooperative'           => 'cooperative.dashboard',
            'ngo'                   => 'ngo.dashboard',
            'government',
            'government-agency'     => 'government.dashboard',
            'research-institution'  => 'research-institution.dashboard',
            'investor'              => 'investor.dashboard',
            'financial-institution' => 'financial-institution.dashboard',
            'logistics-provider'    => 'logistics.dashboard',
            'agribusiness-owner'    => 'agribusiness.dashboard',
            'input-supplier'        => 'input-supplier.dashboard',
            'extension-officer'     => 'extension.dashboard',
            'finance'               => 'finance.dashboard',
            'hr'                    => 'hr.dashboard',
            'operations'            => 'operations.dashboard',
            'data-analyst'          => 'data-analyst.dashboard',
            'monitoring-evaluation',
            'm-e-officer',
            'me-officer'            => 'monitoring-evaluation.dashboard',
            'field-officer'         => 'field-officer.dashboard',
            'customer-support'      => 'customer-support.dashboard',
            'rider'                 => 'rider.dashboard',
            'researcher'            => 'research-institution.dashboard',
            'student'               => 'farmer.dashboard',
            'general-user'          => 'farmer.dashboard',
            default                 => null,
        };

        return $route ? redirect()->route($route) : view('dashboard');
    }

    // ── Admin Dashboard ────────────────────────────────────────────
    public function admin()
    {
        $totalUsers = $this->safe('total users', fn() => \App\Models\User::count());
        $activeUsers = $this->safe('active users', fn() => \App\Models\User::where('is_active', true)->count());
        $pendingApprovals = $this->safe('pending approvals', fn() => \App\Models\User::where('application_status', 'pending')->whereNotIn('role', ['farmer','general-user','ceo','admin'])->count());
        $recentUsers = $this->safe('recent users', fn() => \App\Models\User::latest()->take(10)->get(), collect());
        $usersByRole = $this->safe('users by role', fn() => \App\Models\User::select('role', DB::raw('count(*) as count'))->groupBy('role')->pluck('count','role'), collect());
        $newThisMonth = $this->safe('new users this month', fn() => \App\Models\User::whereMonth('created_at', now()->month)->count());
        $totalAnimals = $this->safe('total livestock', fn() => \App\Models\Animal::count());
        $totalConsults = $this->safe('total consultations', fn() => \App\Models\Consultation::count());
        $monthlyGrowth = $this->safe('monthly user growth', fn() => collect(range(5, 0))->map(fn($i) => [
            'label' => now()->subMonths($i)->format('M'),
            'users' => \App\Models\User::whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count(),
        ]), collect());

        // ── Order Operations Stats ──────────────────────────────────────────────
        $ordersToday       = $this->safe('orders today', fn() => \App\Models\Order::whereDate('created_at', today())->count());
        $ordersUnassigned  = $this->safe('unassigned orders', fn() => \App\Models\Order::whereIn('status', ['confirmed','processing'])->whereNull('rider_id')->count());
        $ordersInTransit   = $this->safe('orders in transit', fn() => \App\Models\Order::where('rider_status', 'in_transit')->count());
        $ordersPending     = $this->safe('pending orders', fn() => \App\Models\Order::where('status', 'pending')->count());
        $ordersDelivered   = $this->safe('delivered orders', fn() => \App\Models\Order::where('status', 'delivered')->count());
        $ridersAvailable   = $this->safe('available riders', fn() => \App\Models\User::where('role', 'rider')->where('rider_status', 'available')->count());
        $ridersBusy        = $this->safe('busy riders', fn() => \App\Models\User::where('role', 'rider')->where('rider_status', 'busy')->count());
        $revenueToday      = $this->safe('revenue today', fn() => \App\Models\Order::whereDate('created_at', today())->where('payment_status','paid')->sum('total'));
        $recentOrders = $this->safe('recent orders', fn() => \App\Models\Order::with(['buyer:id,first_name,last_name','dealer:id,first_name,last_name','rider:id,first_name,last_name'])
            ->latest()->take(6)->get(), collect());

        // ── Consultation Stats ──────────────────────────────────────────────────
        $consultsOpen        = $this->safe('open consultations', fn() => \App\Models\Consultation::where('status','open')->count());
        $consultsUnassigned  = $this->safe('unassigned consultations', fn() => \App\Models\Consultation::whereNull('expert_id')->where('status','open')->count());
        $consultsResolved    = $this->safe('resolved consultations', fn() => \App\Models\Consultation::where('status','resolved')->count());
        $recentConsults = $this->safe('recent consultations', fn() => \App\Models\Consultation::with(['farmer:id,first_name,last_name','expert:id,first_name,last_name,role'])
            ->latest()->take(6)->get(), collect());

        // ── Application / Verification Stats ───────────────────────────────────
        // Must match ApplicationController::index() query exactly so the counter = the page count
        $pendingVerifications = $this->safe('pending verifications', fn() => \App\Models\User::where('application_status', 'pending')->whereNotIn('role', ['farmer','general-user','ceo','admin'])->count());

        // ── Wallet Stats ────────────────────────────────────────────────────────
        $pendingWithdrawals = $this->safe('pending withdrawals', fn() => \App\Models\WalletTransaction::where('type','hold')->where('status','pending')->count());

        $dashboardErrors = $this->dashboardErrors;

        return view('admin.dashboard', compact(
            'totalUsers','activeUsers','pendingApprovals','recentUsers',
            'usersByRole','newThisMonth','totalAnimals','totalConsults','monthlyGrowth',
            'ordersToday','ordersUnassigned','ordersInTransit','ordersPending',
            'ordersDelivered','ridersAvailable','ridersBusy','revenueToday','recentOrders',
            'consultsOpen','consultsUnassigned','consultsResolved','recentConsults',
            'pendingVerifications','pendingWithdrawals','dashboardErrors'
        ));
    }

    public function rider()
    {
        return app(RiderController::class)->dashboard();
    }

    // ── Farmer Dashboard ───────────────────────────────────────────
    public function farmer()
    {
        $user = auth()->user();
        $animalsCount        = $this->safe('livestock count', fn() => \App\Models\Animal::where('user_id', $user->id)->count());
        $poultryCount        = $this->safe('poultry count', fn() => \App\Models\PoultryRecord::where('user_id', $user->id)->count());
        $diagnosesCount      = $this->safe('scan count', fn() => \App\Models\Diagnosis::where('user_id', $user->id)->count());
        $recentScans         = $this->safe('recent scans', fn() => \App\Models\Diagnosis::where('user_id', $user->id)->latest()->take(5)->get(), collect());
        $pendingVetConsults  = $this->safe('pending consultations', fn() => \App\Models\Consultation::where('farmer_id', $user->id)->where('status', 'pending')->count());
        $recentAnimals       = $this->safe('recent livestock', fn() => \App\Models\Animal::where('user_id', $user->id)->latest()->take(5)->get(), collect());
        $recentFlocks        = $this->safe('recent poultry', fn() => \App\Models\PoultryRecord::where('user_id', $user->id)->latest()->take(3)->get(), collect());
        $recentConsults      = $this->safe('recent consultations', fn() => \App\Models\Consultation::where('farmer_id', $user->id)->latest()->take(4)->get(), collect());
        $totalIncome         = $this->safe('income total', fn() => \App\Models\Finance::where('user_id', $user->id)->where('type', 'Income')->sum('amount'));
        $totalExpense        = $this->safe('expense total', fn() => \App\Models\Finance::where('user_id', $user->id)->where('type', 'Expense')->sum('amount'));
        $recentFinances      = $this->safe('recent finances', fn() => \App\Models\Finance::where('user_id', $user->id)->latest('transaction_date')->take(5)->get(), collect());
        $netBalance = $totalIncome - $totalExpense;
        $scanCheck = $this->safe(
            'scan limit check',
            fn() => app(\App\Services\SubscriptionLimitService::class)->canScan($user),
            ['allowed' => true, 'used' => 0, 'limit' => -1, 'remaining' => -1, 'plan' => 'free']
        );

        $onboardingSteps     = \App\Http\Controllers\FarmerController::onboardingSteps($user);
        $onboardingDone      = collect($onboardingSteps)->every(fn($s) => $s['done']);
        $showOnboarding      = ! $onboardingDone && ! $user->onboarding_dismissed_at;

        $dashboardErrors = $this->dashboardErrors;

        return view('farmer.dashboard', compact(
            'animalsCount', 'poultryCount', 'diagnosesCount', 'recentScans', 'pendingVetConsults',
            'recentAnimals', 'recentFlocks', 'recentConsults', 'totalIncome', 'totalExpense',
            'recentFinances', 'netBalance', 'scanCheck',
            'onboardingSteps', 'showOnboarding', 'dashboardErrors'
        ));
    }

    // ── Vet Dashboard ──────────────────────────────────────────────
    public function vet()
    {
        $pendingConsultations = $this->safe('pending consultations', fn() => \App\Models\Consultation::where('status','pending')->count());
        $completedToday = $this->safe('completed today', fn() => \App\Models\Consultation::where('status','resolved')->whereDate('updated_at', today())->count());
        $pendingQueue = $this->safe('pending queue', fn() => \App\Models\Consultation::with('user')->where('status','pending')->latest()->take(8)->get(), collect());
        $totalFarmers = $this->safe('total farmers', fn() => \App\Models\User::where('role','farmer')->count());
        $totalHandled = $this->safe('total handled', fn() => \App\Models\Consultation::whereIn('status',['resolved','in_progress'])->count());

        $dashboardErrors = $this->dashboardErrors;

        return view('vet.dashboard', compact(
            'pendingConsultations','completedToday','pendingQueue','totalFarmers','totalHandled','dashboardErrors'
        ));
    }

    // ── Agronomist Dashboard ───────────────────────────────────────
    public function agronomist()
    {
        $pendingConsults = $this->safe('pending consultations', fn() => \App\Models\Consultation::where('status','pending')->count());
        $reviewedDiagnoses = $this->safe('reviewed diagnoses', fn() => \App\Models\Consultation::whereIn('status',['resolved','in_progress'])->count());
        $recentConsults = $this->safe('recent consultations', fn() => \App\Models\Consultation::with('user')->latest()->take(8)->get(), collect());
        $totalFarmers = $this->safe('total farmers', fn() => \App\Models\User::where('role','farmer')->count());

        $dashboardErrors = $this->dashboardErrors;

        return view('agronomist.dashboard', compact(
            'pendingConsults','reviewedDiagnoses','recentConsults','totalFarmers','dashboardErrors'
        ));
    }

    // ── Dealer Dashboard ───────────────────────────────────────────
    public function dealer()
    {
        $user = auth()->user();
        $myListings    = $this->safe('my listings', fn() => \App\Models\Product::where('dealer_id', $user->id)->count());
        $activeListings = $this->safe('active listings', fn() => \App\Models\Product::where('dealer_id', $user->id)->where('status', 'active')->count());
        $pendingOrders  = $this->safe('pending orders', fn() => \App\Models\Order::where('dealer_id', $user->id)->where('status', 'pending')->count());
        $recentItems    = $this->safe('recent items', fn() => \App\Models\Product::where('dealer_id', $user->id)->latest()->take(8)->get(), collect());
        $totalMarketItems = $this->safe('total market items', fn() => \App\Models\Product::where('status', 'active')->count());
        $revenue = $this->safe('revenue', fn() => \App\Models\Order::where('dealer_id', $user->id)->where('payment_status','paid')->sum('total'));

        $dashboardErrors = $this->dashboardErrors;

        return view('dealer.dashboard', compact(
            'myListings','activeListings','pendingOrders','recentItems','totalMarketItems','revenue','dashboardErrors'
        ));
    }

    // ── Extension Officer Dashboard ────────────────────────────────
    public function extension()
    {
        $farmersAssigned = $this->safe('farmers assigned', fn() => \App\Models\User::where('role','farmer')->where('state', auth()->user()->state)->count());
        $totalFarmers = $this->safe('total farmers', fn() => \App\Models\User::where('role','farmer')->count());
        $recentFarmers = $this->safe('recent farmers', fn() => \App\Models\User::where('role','farmer')->latest()->take(8)->get(), collect());
        $visitsThisMonth = $this->safe('visits this month', fn() => DB::table('extension_visits')->where('officer_id', auth()->id())->whereMonth('visit_date', now()->month)->count());
        $upcomingVisits = $this->safe('upcoming visits', fn() => DB::table('extension_visits')->where('officer_id', auth()->id())->where('visit_date', '>=', today())->orderBy('visit_date')->take(5)->get(), collect());

        $dashboardErrors = $this->dashboardErrors;

        return view('extension.dashboard', compact(
            'farmersAssigned','totalFarmers','recentFarmers','visitsThisMonth','upcomingVisits','dashboardErrors'
        ));
    }

    // ── Finance Dashboard ──────────────────────────────────────────
    public function finance()
    {
        $totalIncome = $this->safe('total income', fn() => \App\Models\Finance::where('type','Income')->sum('amount'));
        $totalExpenses = $this->safe('total expenses', fn() => \App\Models\Finance::where('type','Expense')->sum('amount'));
        $netProfit = $totalIncome - $totalExpenses;
        $thisMonthIncome = $this->safe('this month income', fn() => \App\Models\Finance::where('type','Income')->whereMonth('transaction_date', now()->month)->sum('amount'));
        $thisMonthExpenses = $this->safe('this month expenses', fn() => \App\Models\Finance::where('type','Expense')->whereMonth('transaction_date', now()->month)->sum('amount'));
        $recentTransactions = $this->safe('recent transactions', fn() => \App\Models\Finance::latest('transaction_date')->take(10)->get(), collect());
        $monthlyChart = $this->safe('monthly chart', fn() => collect(range(5, 0))->map(function ($i) {
            $month = now()->subMonths($i);
            return [
                'month'   => $month->format('M'),
                'income'  => \App\Models\Finance::where('type','Income')->whereMonth('transaction_date', $month->month)->whereYear('transaction_date', $month->year)->sum('amount'),
                'expense' => \App\Models\Finance::where('type','Expense')->whereMonth('transaction_date', $month->month)->whereYear('transaction_date', $month->year)->sum('amount'),
            ];
        }), collect());

        $dashboardErrors = $this->dashboardErrors;

        return view('finance.dashboard', compact(
            'totalIncome','totalExpenses','netProfit','thisMonthIncome',
            'thisMonthExpenses','recentTransactions','monthlyChart','dashboardErrors'
        ));
    }

    // ── Operations Dashboard ───────────────────────────────────────
    public function operations()
    {
        $totalUsers = $this->safe('total users', fn() => \App\Models\User::count());
        $activeUsers = $this->safe('active users', fn() => \App\Models\User::where('is_active', true)->count());
        $newThisWeek = $this->safe('new users this week', fn() => \App\Models\User::where('created_at', '>=', now()->startOfWeek())->count());
        $newThisMonth = $this->safe('new users this month', fn() => \App\Models\User::whereMonth('created_at', now()->month)->count());
        $totalConsultations = $this->safe('total consultations', fn() => \App\Models\Consultation::count());
        $totalAnimals = $this->safe('total livestock', fn() => \App\Models\Animal::count());
        $recentRegistrations = $this->safe('recent registrations', fn() => \App\Models\User::latest()->take(10)->get(), collect());
        // Uptime = (total users who logged in last 30 days / total active users) as platform health proxy
        $systemUptime = $this->safe('system uptime', function () use ($activeUsers) {
            $activeUsers30d = \App\Models\User::where('is_active', true)
                ->where('last_seen', '>=', now()->subDays(30))->count();
            return $activeUsers > 0 ? round(($activeUsers30d / $activeUsers) * 100, 1) . '%' : 'N/A';
        }, 'N/A');

        $dashboardErrors = $this->dashboardErrors;

        return view('operations.dashboard', compact(
            'totalUsers','activeUsers','newThisWeek','newThisMonth',
            'totalConsultations','totalAnimals','recentRegistrations','systemUptime','dashboardErrors'
        ));
    }

    // ── Data Analyst Dashboard ────────────────────────────────────
    public function dataAnalyst()
    {
        $totalUsers = $this->safe('total users', fn() => \App\Models\User::count());
        $totalConsults = $this->safe('total consultations', fn() => \App\Models\Consultation::count());
        $totalAnimals = $this->safe('total livestock', fn() => \App\Models\Animal::count());
        $activeThisMonth = $this->safe('active this month', fn() => \App\Models\User::whereMonth('created_at', now()->month)->count());
        $usersByRole = $this->safe('users by role', fn() => \App\Models\User::select('role', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->groupBy('role')->pluck('count','role')->toArray(), []);
        $monthlyRegistrations = $this->safe('monthly registrations', fn() => collect(range(5, 0))->map(function ($i) {
            $month = now()->subMonths($i);
            return ['month' => $month->format('M'), 'count' => \App\Models\User::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count()];
        }), collect());
        $recentConsults = $this->safe('recent consultations', fn() => \App\Models\Consultation::with('user')->latest()->take(10)->get(), collect());

        $dashboardErrors = $this->dashboardErrors;

        return view('data-analyst.dashboard', compact(
            'totalUsers','totalConsults','totalAnimals','activeThisMonth',
            'usersByRole','monthlyRegistrations','recentConsults','dashboardErrors'
        ));
    }

    // ── M&E Officer Dashboard ─────────────────────────────────────
    public function monitoringEvaluation()
    {
        $totalFarmers = $this->safe('total farmers', fn() => \App\Models\User::where('role','farmer')->count());
        $totalAnimals = $this->safe('total livestock', fn() => \App\Models\Animal::count());
        $totalConsults = $this->safe('total consultations', fn() => \App\Models\Consultation::count());
        $resolvedCases = $this->safe('resolved cases', fn() => \App\Models\Consultation::where('status','resolved')->count());
        $stateActivity = $this->safe('state activity', fn() => \App\Models\User::select('state', DB::raw('count(*) as count'))->whereNotNull('state')->groupBy('state')->orderByDesc('count')->take(8)->pluck('count','state')->toArray(), []);
        $monthlySummary = $this->safe('monthly summary', fn() => collect(range(5, 0))->map(function ($i) {
            $month = now()->subMonths($i);
            $total = \App\Models\Consultation::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
            $resolved = \App\Models\Consultation::where('status','resolved')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
            return [
                'month'           => $month->format('M Y'),
                'farmers'         => \App\Models\User::where('role','farmer')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count(),
                'consults'        => $total,
                'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100) : 0,
            ];
        })->toArray(), []);

        // ── Live KPIs (replacing all hardcoded values) ─────────────
        $scanAdoptionRate = $this->safe('scan adoption rate', function () use ($totalFarmers) {
            $farmersWithScans = \App\Models\Diagnosis::distinct('user_id')->count('user_id');
            return $totalFarmers > 0 ? round(($farmersWithScans / $totalFarmers) * 100) : 0;
        });

        $vetResponseRate = $this->safe('vet response rate', function () use ($totalConsults) {
            $consultsWithResponse = \App\Models\Consultation::whereNotNull('expert_response')->count();
            return $totalConsults > 0 ? round(($consultsWithResponse / $totalConsults) * 100) : 0;
        });

        $farmerRetention = $this->safe('farmer retention', function () use ($totalFarmers) {
            $activeRecentFarmers = \App\Models\User::where('role','farmer')
                ->where('last_seen', '>=', now()->subDays(30))->count();
            return $totalFarmers > 0 ? round(($activeRecentFarmers / $totalFarmers) * 100) : 0;
        });

        $aiAccuracy = $this->safe('AI accuracy', function () {
            $totalDiag = \App\Models\Diagnosis::count();
            $confirmedDiag = \App\Models\Diagnosis::where('status','reviewed')->count();
            return $totalDiag > 0 ? round(($confirmedDiag / $totalDiag) * 100) : 0;
        });

        $marketplaceUtilisation = $this->safe('marketplace utilisation', function () use ($totalFarmers) {
            $farmersWhoOrdered = \App\Models\Order::whereHas('buyer', fn($q) => $q->where('role','farmer'))
                ->distinct('buyer_id')->count('buyer_id');
            return $totalFarmers > 0 ? round(($farmersWhoOrdered / $totalFarmers) * 100) : 0;
        });

        [$extensionVisitsThisMonth, $extensionAdvisories] = $this->safe('extension activity', function () {
            return [
                DB::table('extension_visits')->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year)->count(),
                DB::table('extension_advisory')->whereMonth('created_at', now()->month)->count(),
            ];
        }, [0, 0]);

        $dashboardErrors = $this->dashboardErrors;

        return view('monitoring-evaluation.dashboard', compact(
            'totalFarmers','totalAnimals','totalConsults','resolvedCases',
            'stateActivity','monthlySummary','scanAdoptionRate','vetResponseRate',
            'farmerRetention','aiAccuracy','marketplaceUtilisation',
            'extensionVisitsThisMonth','extensionAdvisories','dashboardErrors'
        ));
    }

    // ── Field Officer Dashboard ───────────────────────────────────
    public function fieldOfficer()
    {
        $user = auth()->user();
        $assignedFarmers = $this->safe('assigned farmers', fn() => \App\Models\User::where('role','farmer')->where('state', $user->state)->count());
        $recentFarmers = $this->safe('recent farmers', fn() => \App\Models\User::where('role','farmer')->latest()->take(8)->get(), collect());
        $visitsThisMonth = $this->safe('visits this month', fn() => DB::table('extension_visits')->where('officer_id', $user->id)->whereMonth('visit_date', now()->month)->count());
        $pendingFollowups = $this->safe('pending followups', fn() => DB::table('extension_visits')->where('officer_id', $user->id)->where('visit_date', '>=', today())->count());
        $reportsSubmitted = $this->safe('reports submitted', fn() => DB::table('extension_advisory')->where('officer_id', $user->id)->count());
        $farmersRegistered = $this->safe('farmers registered', fn() => \App\Models\User::where('role','farmer')->whereMonth('created_at', now()->month)->count());

        $dashboardErrors = $this->dashboardErrors;

        return view('field-officer.dashboard', compact(
            'assignedFarmers','recentFarmers','visitsThisMonth',
            'pendingFollowups','reportsSubmitted','farmersRegistered','dashboardErrors'
        ));
    }

    // ── Customer Support Dashboard ────────────────────────────────
    public function customerSupport()
    {
        $recentUsers = $this->safe('recent users', fn() => \App\Models\User::latest()->take(10)->get(), collect());
        $totalUsers = $this->safe('total users', fn() => \App\Models\User::count());

        // ── Live ticket stats from support_tickets table ───────────
        $openTickets = $this->safe('open tickets', fn() => DB::table('support_tickets')->where('status','open')->count());
        $resolvedToday = $this->safe('resolved today', fn() => DB::table('support_tickets')->where('status','resolved')->whereDate('updated_at', today())->count());
        $pendingTickets = $this->safe('pending tickets', fn() => DB::table('support_tickets')->where('status','in_progress')->count());
        $totalTickets = $this->safe('total tickets', fn() => DB::table('support_tickets')->count());
        $recentTickets = $this->safe('recent tickets', fn() => DB::table('support_tickets')->orderByDesc('created_at')->take(10)->get(), collect());

        // Category breakdown
        [$techIssues, $loginIssues, $marketplaceIssues, $aiQueryIssues, $generalIssues] = $this->safe('ticket categories', function () {
            return [
                DB::table('support_tickets')->where('category','technical')->count(),
                DB::table('support_tickets')->where('category','login')->count(),
                DB::table('support_tickets')->where('category','marketplace')->count(),
                DB::table('support_tickets')->where('category','ai-query')->count(),
                DB::table('support_tickets')->where('category','general')->count(),
            ];
        }, [0, 0, 0, 0, 0]);

        // Average first-reply time in hours (PostgreSQL-compatible)
        $avgResponseTime = $this->safe('avg response time', fn() => DB::table('ticket_replies as r')
            ->join('support_tickets as t', 't.id', '=', 'r.ticket_id')
            ->whereRaw('r.created_at = (SELECT MIN(r2.created_at) FROM ticket_replies r2 WHERE r2.ticket_id = t.id)')
            ->whereColumn('r.user_id', '!=', 't.user_id')
            ->selectRaw('ROUND(AVG(EXTRACT(EPOCH FROM (r.created_at::timestamp - t.created_at::timestamp)) / 3600)::numeric, 1) as avg_hours')
            ->value('avg_hours') ?? 0);

        // SLA compliance: tickets resolved within 24 h / all resolved tickets (PostgreSQL-compatible)
        $slaCompliance = $this->safe('SLA compliance', function () {
            $resolvedTotal = DB::table('support_tickets')->where('status','resolved')->count();
            $withinSla     = DB::table('support_tickets')->where('status','resolved')
                ->whereRaw('EXTRACT(EPOCH FROM (updated_at::timestamp - created_at::timestamp)) / 3600 <= 24')->count();
            return $resolvedTotal > 0 ? round(($withinSla / $resolvedTotal) * 100) : 100;
        });

        // Satisfaction from diagnoses feedback as a proxy (no dedicated feedback table yet)
        $satisfactionScore = $this->safe('satisfaction score', function () {
            $positiveReviews = \App\Models\Diagnosis::where('status','reviewed')->count();
            $totalReviewed   = \App\Models\Diagnosis::count();
            return $totalReviewed > 0 ? round(($positiveReviews / $totalReviewed) * 100) : 0;
        });

        $dashboardErrors = $this->dashboardErrors;

        return view('customer-support.dashboard', compact(
            'recentUsers','totalUsers','openTickets','resolvedToday','pendingTickets',
            'totalTickets','recentTickets','avgResponseTime','satisfactionScore',
            'slaCompliance','techIssues','loginIssues','marketplaceIssues',
            'aiQueryIssues','generalIssues','dashboardErrors'
        ));
    }

    // ── HR Dashboard ───────────────────────────────────────────────
    public function hr()
    {
        $staffCount = $this->safe('staff count', fn() => \App\Models\User::whereNotIn('role',['farmer','agro-dealer'])->count());
        $presentToday = $this->safe('present today', fn() => \App\Models\Attendance::whereDate('date', today())->where('status','present')->count());
        $pendingLeaves = $this->safe('pending leaves', fn() => \App\Models\LeaveRequest::where('status','pending')->count());
        $recentStaff = $this->safe('recent staff', fn() => \App\Models\User::whereNotIn('role',['farmer','agro-dealer'])->latest()->take(10)->get(), collect());
        $absentToday = $this->safe('absent today', fn() => \App\Models\Attendance::whereDate('date', today())->where('status','absent')->count());

        $dashboardErrors = $this->dashboardErrors;

        return view('hr.dashboard', compact(
            'staffCount','presentToday','pendingLeaves','recentStaff','absentToday','dashboardErrors'
        ));
    }

    // ── Equipment Dealer Dashboard ─────────────────────────────────
    public function equipmentDealer()
    {
        $user = auth()->user();
        $totalProducts  = $this->safe('total products', fn() => DB::table('products')->where('dealer_id', $user->id)->count());
        $totalOrders    = $this->safe('total orders', fn() => DB::table('orders')->where('dealer_id', $user->id)->count());
        $pendingOrders  = $this->safe('pending orders', fn() => DB::table('orders')->where('dealer_id', $user->id)->where('status','pending')->count());
        $totalRevenue   = $this->safe('total revenue', fn() => DB::table('orders')->where('dealer_id', $user->id)->where('status','confirmed')->sum('total'));
        $recentOrders   = $this->safe('recent orders', fn() => DB::table('orders')->where('dealer_id', $user->id)->orderByDesc('created_at')->take(5)->get(), collect());

        $dashboardErrors = $this->dashboardErrors;

        return view('equipment-dealer.dashboard', compact('totalProducts','totalOrders','pendingOrders','totalRevenue','recentOrders','dashboardErrors'));
    }

    // ── Cooperative Dashboard ──────────────────────────────────────
    public function cooperative()
    {
        $user = auth()->user();
        $state = $user->state;
        $totalFarmers     = $this->safe('total farmers', fn() => \App\Models\User::where('role','farmer')->where('state', $state)->count());
        $totalConsults    = $this->safe('total consultations', fn() => \App\Models\Consultation::count());
        $resolvedConsults = $this->safe('resolved consultations', fn() => \App\Models\Consultation::where('status','resolved')->count());
        $recentFarmers    = $this->safe('recent farmers', fn() => \App\Models\User::where('role','farmer')->where('state', $state)->latest()->take(8)->get(), collect());
        $totalDiagnoses   = $this->safe('total diagnoses', fn() => \App\Models\Diagnosis::count());
        $walletBalance    = $this->safe('wallet balance', fn() => $user->wallet?->available_balance ?? 0);
        $lgaBreakdown     = $this->safe('lga breakdown', fn() => \App\Models\User::where('role','farmer')->where('state', $state)->whereNotNull('lga')->select('lga', DB::raw('count(*) as count'))->groupBy('lga')->orderByDesc('count')->take(6)->get(), collect());
        $diseaseAlerts    = $this->safe('disease alerts', fn() => \App\Models\Diagnosis::whereNotNull('disease_name')->latest()->take(5)->get(), collect());
        $agroDealsInState = $this->safe('agro-dealers in state', fn() => \App\Models\User::whereIn('role',['agro-dealer','equipment-dealer'])->where('state', $state)->count());
        $monthlyFarmers   = $this->safe('monthly farmers', fn() => collect(range(5,0))->map(fn($i) => ['label' => now()->subMonths($i)->format('M'), 'count' => \App\Models\User::where('role','farmer')->where('state',$state)->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count()]), collect());
        $activeProductsInState = $this->safe('active products in state', fn() => \App\Models\Product::where('status','active')->where('is_approved',true)->whereHas('dealer', fn($q) => $q->where('state', $state))->count());
        $resolutionRate   = $this->safe('resolution rate', fn() => $totalConsults > 0 ? round(($resolvedConsults / $totalConsults) * 100) : 0);

        $dashboardErrors = $this->dashboardErrors;

        return view('cooperative.dashboard', compact('totalFarmers','totalConsults','resolvedConsults','recentFarmers','totalDiagnoses','walletBalance','lgaBreakdown','diseaseAlerts','agroDealsInState','monthlyFarmers','activeProductsInState','resolutionRate','dashboardErrors'));
    }

    // ── NGO Dashboard ──────────────────────────────────────────────
    public function ngo()
    {
        $totalBeneficiaries = $this->safe('total beneficiaries', fn() => \App\Models\User::where('role','farmer')->count());
        $totalDiagnoses     = $this->safe('total diagnoses', fn() => \App\Models\Diagnosis::count());
        $totalConsults      = $this->safe('total consultations', fn() => \App\Models\Consultation::count());
        $resolvedConsults   = $this->safe('resolved consultations', fn() => \App\Models\Consultation::where('status','resolved')->count());
        $recentActivity     = $this->safe('recent activity', fn() => \App\Models\User::where('role','farmer')->latest()->take(8)->get(), collect());
        $resolutionRate     = $this->safe('resolution rate', fn() => $totalConsults > 0 ? round(($resolvedConsults / $totalConsults) * 100) : 0);
        $stateBreakdown     = $this->safe('state breakdown', fn() => \App\Models\User::where('role','farmer')->whereNotNull('state')->select('state', DB::raw('count(*) as count'))->groupBy('state')->orderByDesc('count')->take(8)->get(), collect());
        $monthlyRegistrations = $this->safe('monthly registrations', fn() => collect(range(5,0))->map(fn($i) => ['label' => now()->subMonths($i)->format('M'), 'count' => \App\Models\User::where('role','farmer')->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count()]), collect());
        $diseaseBreakdown   = $this->safe('disease breakdown', fn() => \App\Models\Diagnosis::whereNotNull('disease_name')->select('disease_name', DB::raw('count(*) as count'))->groupBy('disease_name')->orderByDesc('count')->take(6)->get(), collect());
        $farmersWithScans   = $this->safe('farmers with scans', fn() => \App\Models\Diagnosis::distinct('user_id')->count('user_id'));
        $adoptionRate       = $this->safe('adoption rate', fn() => $totalBeneficiaries > 0 ? round(($farmersWithScans / $totalBeneficiaries) * 100) : 0);

        $dashboardErrors = $this->dashboardErrors;

        return view('ngo.dashboard', compact('totalBeneficiaries','totalDiagnoses','totalConsults','resolvedConsults','recentActivity','resolutionRate','stateBreakdown','monthlyRegistrations','diseaseBreakdown','farmersWithScans','adoptionRate','dashboardErrors'));
    }

    // ── Government Dashboard ───────────────────────────────────────
    public function government()
    {
        $totalFarmers        = $this->safe('total farmers', fn() => \App\Models\User::where('role','farmer')->count());
        $totalAnimals        = $this->safe('total livestock', fn() => \App\Models\Animal::count());
        $totalDiagnoses      = $this->safe('total diagnoses', fn() => \App\Models\Diagnosis::count());
        $totalConsults       = $this->safe('total consultations', fn() => \App\Models\Consultation::count());
        $resolvedConsults    = $this->safe('resolved consultations', fn() => \App\Models\Consultation::where('status','resolved')->count());
        $resolutionRate      = $this->safe('resolution rate', fn() => $totalConsults > 0 ? round(($resolvedConsults / $totalConsults) * 100) : 0);
        $verifiedFarmers     = $this->safe('verified farmers', fn() => \App\Models\User::where('role','farmer')->where('is_active',true)->count());
        $marketGMV           = $this->safe('market GMV', fn() => \App\Models\Order::where('payment_status','paid')->sum('total'));
        $diseaseAlerts       = $this->safe('disease alerts', fn() => \App\Models\Diagnosis::whereNotNull('disease_name')->latest()->take(8)->get(), collect());
        $stateBreakdown      = $this->safe('state breakdown', fn() => \App\Models\User::where('role','farmer')->whereNotNull('state')->select('state', DB::raw('count(*) as count'))->groupBy('state')->orderByDesc('count')->take(10)->get(), collect());
        $diseaseFrequency    = $this->safe('disease frequency', fn() => \App\Models\Diagnosis::whereNotNull('disease_name')->select('disease_name', DB::raw('count(*) as count'))->groupBy('disease_name')->orderByDesc('count')->take(8)->get(), collect());
        $totalAgroDealers    = $this->safe('total agro-dealers', fn() => \App\Models\User::whereIn('role',['agro-dealer','equipment-dealer'])->count());
        $totalVets           = $this->safe('total vets', fn() => \App\Models\User::where('role','vet')->count());
        $totalAgronomists    = $this->safe('total agronomists', fn() => \App\Models\User::where('role','agronomist')->count());
        $monthlyFarmers      = $this->safe('monthly farmers', fn() => collect(range(5,0))->map(fn($i) => ['label' => now()->subMonths($i)->format('M'), 'count' => \App\Models\User::where('role','farmer')->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count()]), collect());
        $monthlyDiagnoses    = $this->safe('monthly diagnoses', fn() => collect(range(5,0))->map(fn($i) => ['label' => now()->subMonths($i)->format('M'), 'count' => \App\Models\Diagnosis::whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count()]), collect());
        $lgaBreakdown        = $this->safe('lga breakdown', fn() => \App\Models\User::where('role','farmer')->whereNotNull('lga')->select('lga', DB::raw('count(*) as count'))->groupBy('lga')->orderByDesc('count')->take(8)->get(), collect());
        $recentFarmers       = $this->safe('recent farmers', fn() => \App\Models\User::where('role','farmer')->latest()->take(8)->get(), collect());

        $dashboardErrors = $this->dashboardErrors;

        return view('government.dashboard', compact(
            'totalFarmers','totalAnimals','totalDiagnoses','totalConsults','resolvedConsults','resolutionRate',
            'verifiedFarmers','marketGMV','diseaseAlerts','stateBreakdown','diseaseFrequency',
            'totalAgroDealers','totalVets','totalAgronomists','monthlyFarmers','monthlyDiagnoses',
            'lgaBreakdown','recentFarmers','dashboardErrors'
        ));
    }

    // ── Research Institution Dashboard ─────────────────────────────
    public function researchInstitution()
    {
        $totalDiagnoses   = $this->safe('total diagnoses', fn() => \App\Models\Diagnosis::count());
        $totalAnimals     = $this->safe('total livestock', fn() => \App\Models\Animal::count());
        $totalFarmers     = $this->safe('total farmers', fn() => \App\Models\User::where('role','farmer')->count());
        $recentDiagnoses  = $this->safe('recent diagnoses', fn() => \App\Models\Diagnosis::latest()->take(10)->get(), collect());
        $diseaseFrequency = $this->safe('disease frequency', fn() => \App\Models\Diagnosis::whereNotNull('disease_name')->select('disease_name', DB::raw('count(*) as count'))->groupBy('disease_name')->orderByDesc('count')->take(8)->get(), collect());
        $scanTypeBreakdown = $this->safe('scan type breakdown', fn() => \App\Models\Diagnosis::select('type', DB::raw('count(*) as count'))->groupBy('type')->get()->pluck('count','type')->toArray(), []);
        $monthlyScans     = $this->safe('monthly scans', fn() => collect(range(5,0))->map(fn($i) => ['label' => now()->subMonths($i)->format('M'), 'count' => \App\Models\Diagnosis::whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count()]), collect());
        $stateHeatmap     = $this->safe('state heatmap', fn() => \App\Models\User::where('role','farmer')->whereNotNull('state')->select('state', DB::raw('count(*) as count'))->groupBy('state')->orderByDesc('count')->take(10)->get(), collect());
        $totalConsults    = $this->safe('total consultations', fn() => \App\Models\Consultation::count());
        $resolvedConsults = $this->safe('resolved consultations', fn() => \App\Models\Consultation::where('status','resolved')->count());
        $aiAccuracy       = $this->safe('AI accuracy', fn() => $totalDiagnoses > 0 ? round((\App\Models\Diagnosis::where('status','reviewed')->count() / $totalDiagnoses) * 100) : 0);

        $dashboardErrors = $this->dashboardErrors;

        return view('research-institution.dashboard', compact('totalDiagnoses','totalAnimals','totalFarmers','recentDiagnoses','diseaseFrequency','scanTypeBreakdown','monthlyScans','stateHeatmap','totalConsults','resolvedConsults','aiAccuracy','dashboardErrors'));
    }

    // ── Investor Dashboard ─────────────────────────────────────────
    public function investor()
    {
        $totalFarmers     = $this->safe('total farmers', fn() => \App\Models\User::where('role','farmer')->count());
        $totalUsers       = $this->safe('total users', fn() => \App\Models\User::count());
        $totalRevenue     = $this->safe('total revenue', fn() => \App\Models\Payment::where('status','success')->sum('amount'));
        $totalTransacts   = $this->safe('total transactions', fn() => \App\Models\Payment::where('status','success')->count());
        $marketProducts   = $this->safe('market products', fn() => DB::table('products')->where('is_approved', true)->count());
        $marketGMV        = $this->safe('market GMV', fn() => \App\Models\Order::where('payment_status','paid')->sum('total'));
        $activeUsers30d   = $this->safe('active users (30d)', fn() => \App\Models\User::where('last_seen', '>=', now()->subDays(30))->count());
        $totalConsults    = $this->safe('total consultations', fn() => \App\Models\Consultation::count());
        $totalScans       = $this->safe('total scans', fn() => \App\Models\Diagnosis::count());
        $monthlyRevenue   = $this->safe('monthly revenue', fn() => collect(range(5,0))->map(fn($i) => [
            'label'   => now()->subMonths($i)->format('M'),
            'amount'  => \App\Models\Payment::where('status','success')->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->sum('amount'),
        ]), collect());
        $monthlyGMV       = $this->safe('monthly GMV', fn() => collect(range(5,0))->map(fn($i) => [
            'label'  => now()->subMonths($i)->format('M'),
            'amount' => \App\Models\Order::where('payment_status','paid')->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->sum('total'),
        ]), collect());
        $userGrowth       = $this->safe('user growth', fn() => collect(range(5,0))->map(fn($i) => [
            'label' => now()->subMonths($i)->format('M'),
            'count' => \App\Models\User::whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count(),
        ]), collect());
        $subscriptionRevenue = $this->safe('subscription revenue', fn() => \App\Models\Payment::where('status','success')->where('description','like','%subscription%')->sum('amount'));
        $retentionRate    = $this->safe('retention rate', fn() => $totalUsers > 0 ? round(($activeUsers30d / $totalUsers) * 100) : 0);

        $dashboardErrors = $this->dashboardErrors;

        return view('investor.dashboard', compact('totalFarmers','totalUsers','totalRevenue','totalTransacts','marketProducts','marketGMV','activeUsers30d','totalConsults','totalScans','monthlyRevenue','monthlyGMV','userGrowth','subscriptionRevenue','retentionRate','dashboardErrors'));
    }

    // ── Financial Institution Dashboard ────────────────────────────
    public function financialInstitution()
    {
        $totalFarmers     = $this->safe('total farmers', fn() => \App\Models\User::where('role','farmer')->count());
        $totalAnimals     = $this->safe('total livestock', fn() => \App\Models\Animal::count());
        $verifiedFarmers  = $this->safe('verified farmers', fn() => \App\Models\User::where('role','farmer')->where('is_active',true)->count());
        $recentFarmers    = $this->safe('recent farmers', fn() => \App\Models\User::where('role','farmer')->latest()->take(10)->get(), collect());
        $stateBreakdown   = $this->safe('state breakdown', fn() => \App\Models\User::where('role','farmer')->whereNotNull('state')->select('state', DB::raw('count(*) as count'))->groupBy('state')->orderByDesc('count')->take(8)->get(), collect());
        $farmersWithOrders= $this->safe('farmers with orders', fn() => \App\Models\Order::whereHas('buyer', fn($q) => $q->where('role','farmer'))->distinct('buyer_id')->count('buyer_id'));
        $farmersWithScans = $this->safe('farmers with scans', fn() => \App\Models\Diagnosis::distinct('user_id')->count('user_id'));
        $farmersWithConsults = $this->safe('farmers with consultations', fn() => \App\Models\Consultation::distinct('farmer_id')->count('farmer_id'));
        $monthlyNewFarmers = $this->safe('monthly new farmers', fn() => collect(range(5,0))->map(fn($i) => ['label' => now()->subMonths($i)->format('M'), 'count' => \App\Models\User::where('role','farmer')->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count()]), collect());
        $totalLivestockValue = $totalAnimals * 150000; // Est. ₦150k avg per animal — pure arithmetic, can't throw
        $marketplaceActivity = $this->safe('marketplace activity', fn() => \App\Models\Order::where('payment_status','paid')->sum('total'));

        $dashboardErrors = $this->dashboardErrors;

        return view('financial-institution.dashboard', compact('totalFarmers','totalAnimals','verifiedFarmers','recentFarmers','stateBreakdown','farmersWithOrders','farmersWithScans','farmersWithConsults','monthlyNewFarmers','totalLivestockValue','marketplaceActivity','dashboardErrors'));
    }

    // ── Logistics Provider Dashboard ───────────────────────────────
    public function logistics()
    {
        $user = auth()->user();
        $totalVehicles   = $this->safe('total vehicles', fn() => \App\Models\LogisticsVehicle::where('user_id', $user->id)->count());
        $activeVehicles  = $this->safe('active vehicles', fn() => \App\Models\LogisticsVehicle::where('user_id', $user->id)->where('status','active')->count());
        $totalDrivers    = $this->safe('total drivers', fn() => \App\Models\LogisticsDriver::where('user_id', $user->id)->count());
        $availableDrivers= $this->safe('available drivers', fn() => \App\Models\LogisticsDriver::where('user_id', $user->id)->where('status','available')->count());
        $pendingDeliveries  = $this->safe('pending deliveries', fn() => \App\Models\DeliveryRequest::where('logistics_provider_id', $user->id)->where('status','pending')->count());
        $inTransit          = $this->safe('in transit', fn() => \App\Models\DeliveryRequest::where('logistics_provider_id', $user->id)->whereIn('status',['assigned','picked_up','in_transit'])->count());
        $completedToday     = $this->safe('completed today', fn() => \App\Models\DeliveryRequest::where('logistics_provider_id', $user->id)->where('status','delivered')->whereDate('delivered_at', today())->count());
        $totalRevenue       = $this->safe('total revenue', fn() => \App\Models\DeliveryRequest::where('logistics_provider_id', $user->id)->where('status','delivered')->sum('delivery_fee'));
        $recentDeliveries   = $this->safe('recent deliveries', fn() => \App\Models\DeliveryRequest::where('logistics_provider_id', $user->id)->with(['vehicle','driver'])->orderByDesc('created_at')->take(8)->get(), collect());

        $dashboardErrors = $this->dashboardErrors;

        return view('logistics.dashboard', compact('totalVehicles','activeVehicles','totalDrivers','availableDrivers','pendingDeliveries','inTransit','completedToday','totalRevenue','recentDeliveries','dashboardErrors'));
    }

    // ── Agribusiness Owner Dashboard ───────────────────────────────
    public function agribusiness()
    {
        $user = auth()->user();
        $myListings      = $this->safe('my listings', fn() => DB::table('products')->where('dealer_id', $user->id)->count());
        $activeListings  = $this->safe('active listings', fn() => DB::table('products')->where('dealer_id', $user->id)->where('status','active')->count());
        $totalOrders     = $this->safe('total orders', fn() => DB::table('orders')->where('dealer_id', $user->id)->count());
        $pendingOrders   = $this->safe('pending orders', fn() => DB::table('orders')->where('dealer_id', $user->id)->where('status','pending')->count());
        $totalRevenue    = $this->safe('total revenue', fn() => DB::table('orders')->where('dealer_id', $user->id)->where('payment_status','paid')->sum('total'));
        $farmersInState  = $this->safe('farmers in state', fn() => \App\Models\User::where('role','farmer')->where('state', $user->state)->count());
        $recentOrders    = $this->safe('recent orders', fn() => DB::table('orders')->where('dealer_id', $user->id)->orderByDesc('created_at')->take(8)->get(), collect());

        $dashboardErrors = $this->dashboardErrors;

        return view('agribusiness-owner.dashboard', compact('myListings','activeListings','totalOrders','pendingOrders','totalRevenue','farmersInState','recentOrders','dashboardErrors'));
    }

    // ── Input Supplier Dashboard ───────────────────────────────────
    public function inputSupplier()
    {
        $user = auth()->user();
        $myListings     = $this->safe('my listings', fn() => DB::table('products')->where('dealer_id', $user->id)->count());
        $activeListings = $this->safe('active listings', fn() => DB::table('products')->where('dealer_id', $user->id)->where('status','active')->count());
        $totalOrders    = $this->safe('total orders', fn() => DB::table('orders')->where('dealer_id', $user->id)->count());
        $pendingOrders  = $this->safe('pending orders', fn() => DB::table('orders')->where('dealer_id', $user->id)->where('status','pending')->count());
        $totalRevenue   = $this->safe('total revenue', fn() => DB::table('orders')->where('dealer_id', $user->id)->where('payment_status','paid')->sum('total'));
        $topCategories  = $this->safe('top categories', fn() => DB::table('products')->where('dealer_id', $user->id)->select('category', DB::raw('count(*) as count'))->groupBy('category')->orderByDesc('count')->take(5)->get(), collect());
        $recentOrders   = $this->safe('recent orders', fn() => DB::table('orders')->where('dealer_id', $user->id)->orderByDesc('created_at')->take(8)->get(), collect());

        $dashboardErrors = $this->dashboardErrors;

        return view('input-supplier.dashboard', compact('myListings','activeListings','totalOrders','pendingOrders','totalRevenue','topCategories','recentOrders','dashboardErrors'));
    }
}
