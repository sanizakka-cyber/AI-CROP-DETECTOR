<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // ── Admin Dashboard ────────────────────────────────────────────
    public function admin()
    {
        try { $totalUsers = \App\Models\User::count(); } catch (\Exception $e) { $totalUsers = 0; }
        try { $activeUsers = \App\Models\User::where('is_active', true)->count(); } catch (\Exception $e) { $activeUsers = 0; }
        try { $pendingApprovals = \App\Models\User::where('is_verified', false)->whereIn('role', ['vet','agronomist','extension-officer'])->count(); } catch (\Exception $e) { $pendingApprovals = 0; }
        try { $recentUsers = \App\Models\User::latest()->take(10)->get(); } catch (\Exception $e) { $recentUsers = collect(); }
        try { $usersByRole = \App\Models\User::select('role', DB::raw('count(*) as count'))->groupBy('role')->pluck('count','role'); } catch (\Exception $e) { $usersByRole = collect(); }
        try { $newThisMonth = \App\Models\User::whereMonth('created_at', now()->month)->count(); } catch (\Exception $e) { $newThisMonth = 0; }
        try { $totalAnimals = \App\Models\Animal::count(); } catch (\Exception $e) { $totalAnimals = 0; }
        try { $totalConsults = \App\Models\Consultation::count(); } catch (\Exception $e) { $totalConsults = 0; }
        try {
            $monthlyGrowth = collect(range(5, 0))->map(fn($i) => [
                'label' => now()->subMonths($i)->format('M'),
                'users' => \App\Models\User::whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count(),
            ]);
        } catch (\Exception $e) { $monthlyGrowth = collect(); }

        return view('admin.dashboard', compact(
            'totalUsers','activeUsers','pendingApprovals','recentUsers',
            'usersByRole','newThisMonth','totalAnimals','totalConsults','monthlyGrowth'
        ));
    }

    // ── Farmer Dashboard ───────────────────────────────────────────
    public function farmer()
    {
        $user = auth()->user();
        try { $animalsCount       = \App\Models\Animal::where('user_id', $user->id)->count(); } catch (\Exception $e) { $animalsCount = 0; }
        try { $diagnosesCount     = \App\Models\Consultation::where('farmer_id', $user->id)->count(); } catch (\Exception $e) { $diagnosesCount = 0; }
        try { $recentScans        = \App\Models\Consultation::where('farmer_id', $user->id)->latest()->take(5)->get(); } catch (\Exception $e) { $recentScans = collect(); }
        try { $pendingVetConsults  = \App\Models\Consultation::where('farmer_id', $user->id)->where('status', 'pending')->count(); } catch (\Exception $e) { $pendingVetConsults = 0; }
        try { $recentAnimals      = \App\Models\Animal::where('user_id', $user->id)->latest()->take(5)->get(); } catch (\Exception $e) { $recentAnimals = collect(); }
        try { $recentConsults     = \App\Models\Consultation::where('farmer_id', $user->id)->latest()->take(4)->get(); } catch (\Exception $e) { $recentConsults = collect(); }
        try { $totalIncome        = \App\Models\Finance::where('user_id', $user->id)->where('type', 'Income')->sum('amount'); } catch (\Exception $e) { $totalIncome = 0; }
        try { $totalExpense       = \App\Models\Finance::where('user_id', $user->id)->where('type', 'Expense')->sum('amount'); } catch (\Exception $e) { $totalExpense = 0; }
        try { $recentFinances     = \App\Models\Finance::where('user_id', $user->id)->latest('transaction_date')->take(5)->get(); } catch (\Exception $e) { $recentFinances = collect(); }
        $netBalance = $totalIncome - $totalExpense;

        return view('farmer.dashboard', compact(
            'animalsCount', 'diagnosesCount', 'recentScans', 'pendingVetConsults',
            'recentAnimals', 'recentConsults', 'totalIncome', 'totalExpense',
            'recentFinances', 'netBalance'
        ));
    }

    // ── Vet Dashboard ──────────────────────────────────────────────
    public function vet()
    {
        try { $pendingConsultations = \App\Models\Consultation::where('status','pending')->count(); } catch (\Exception $e) { $pendingConsultations = 0; }
        try { $completedToday = \App\Models\Consultation::where('status','resolved')->whereDate('updated_at', today())->count(); } catch (\Exception $e) { $completedToday = 0; }
        try { $pendingQueue = \App\Models\Consultation::with('user')->where('status','pending')->latest()->take(8)->get(); } catch (\Exception $e) { $pendingQueue = collect(); }
        try { $totalFarmers = \App\Models\User::where('role','farmer')->count(); } catch (\Exception $e) { $totalFarmers = 0; }
        try { $totalHandled = \App\Models\Consultation::whereIn('status',['resolved','in_progress'])->count(); } catch (\Exception $e) { $totalHandled = 0; }

        return view('vet.dashboard', compact(
            'pendingConsultations','completedToday','pendingQueue','totalFarmers','totalHandled'
        ));
    }

    // ── Agronomist Dashboard ───────────────────────────────────────
    public function agronomist()
    {
        try { $pendingConsults = \App\Models\Consultation::where('status','pending')->count(); } catch (\Exception $e) { $pendingConsults = 0; }
        try { $reviewedDiagnoses = \App\Models\Consultation::whereIn('status',['resolved','in_progress'])->count(); } catch (\Exception $e) { $reviewedDiagnoses = 0; }
        try { $recentConsults = \App\Models\Consultation::with('user')->latest()->take(8)->get(); } catch (\Exception $e) { $recentConsults = collect(); }
        try { $totalFarmers = \App\Models\User::where('role','farmer')->count(); } catch (\Exception $e) { $totalFarmers = 0; }

        return view('agronomist.dashboard', compact(
            'pendingConsults','reviewedDiagnoses','recentConsults','totalFarmers'
        ));
    }

    // ── Dealer Dashboard ───────────────────────────────────────────
    public function dealer()
    {
        $user = auth()->user();
        try { $myListings    = \App\Models\Product::where('dealer_id', $user->id)->count(); } catch (\Exception $e) { $myListings = 0; }
        try { $activeListings = \App\Models\Product::where('dealer_id', $user->id)->where('status', 'active')->count(); } catch (\Exception $e) { $activeListings = 0; }
        try { $pendingOrders  = \App\Models\Order::where('dealer_id', $user->id)->where('status', 'pending')->count(); } catch (\Exception $e) { $pendingOrders = 0; }
        try { $recentItems    = \App\Models\Product::where('dealer_id', $user->id)->latest()->take(8)->get(); } catch (\Exception $e) { $recentItems = collect(); }
        try { $totalMarketItems = \App\Models\Product::where('status', 'active')->count(); } catch (\Exception $e) { $totalMarketItems = 0; }
        try { $revenue = \App\Models\Order::where('dealer_id', $user->id)->where('payment_status','paid')->sum('total'); } catch (\Exception $e) { $revenue = 0; }

        return view('dealer.dashboard', compact(
            'myListings','activeListings','pendingOrders','recentItems','totalMarketItems','revenue'
        ));
    }

    // ── Extension Officer Dashboard ────────────────────────────────
    public function extension()
    {
        try { $farmersAssigned = \App\Models\User::where('role','farmer')->where('state', auth()->user()->state)->count(); } catch (\Exception $e) { $farmersAssigned = 0; }
        try { $totalFarmers = \App\Models\User::where('role','farmer')->count(); } catch (\Exception $e) { $totalFarmers = 0; }
        try { $recentFarmers = \App\Models\User::where('role','farmer')->latest()->take(8)->get(); } catch (\Exception $e) { $recentFarmers = collect(); }
        try { $visitsThisMonth = DB::table('extension_visits')->where('officer_id', auth()->id())->whereMonth('visit_date', now()->month)->count(); } catch (\Exception $e) { $visitsThisMonth = 0; }
        try { $upcomingVisits = DB::table('extension_visits')->where('officer_id', auth()->id())->where('visit_date', '>=', today())->orderBy('visit_date')->take(5)->get(); } catch (\Exception $e) { $upcomingVisits = collect(); }

        return view('extension.dashboard', compact(
            'farmersAssigned','totalFarmers','recentFarmers','visitsThisMonth','upcomingVisits'
        ));
    }

    // ── Finance Dashboard ──────────────────────────────────────────
    public function finance()
    {
        try { $totalIncome = \App\Models\Finance::where('type','Income')->sum('amount'); } catch (\Exception $e) { $totalIncome = 0; }
        try { $totalExpenses = \App\Models\Finance::where('type','Expense')->sum('amount'); } catch (\Exception $e) { $totalExpenses = 0; }
        $netProfit = $totalIncome - $totalExpenses;
        try { $thisMonthIncome = \App\Models\Finance::where('type','Income')->whereMonth('transaction_date', now()->month)->sum('amount'); } catch (\Exception $e) { $thisMonthIncome = 0; }
        try { $thisMonthExpenses = \App\Models\Finance::where('type','Expense')->whereMonth('transaction_date', now()->month)->sum('amount'); } catch (\Exception $e) { $thisMonthExpenses = 0; }
        try { $recentTransactions = \App\Models\Finance::latest('transaction_date')->take(10)->get(); } catch (\Exception $e) { $recentTransactions = collect(); }
        try {
            $monthlyChart = collect(range(5, 0))->map(function ($i) {
                $month = now()->subMonths($i);
                return [
                    'month'   => $month->format('M'),
                    'income'  => \App\Models\Finance::where('type','Income')->whereMonth('transaction_date', $month->month)->whereYear('transaction_date', $month->year)->sum('amount'),
                    'expense' => \App\Models\Finance::where('type','Expense')->whereMonth('transaction_date', $month->month)->whereYear('transaction_date', $month->year)->sum('amount'),
                ];
            });
        } catch (\Exception $e) { $monthlyChart = collect(); }

        return view('finance.dashboard', compact(
            'totalIncome','totalExpenses','netProfit','thisMonthIncome',
            'thisMonthExpenses','recentTransactions','monthlyChart'
        ));
    }

    // ── Operations Dashboard ───────────────────────────────────────
    public function operations()
    {
        try { $totalUsers = \App\Models\User::count(); } catch (\Exception $e) { $totalUsers = 0; }
        try { $activeUsers = \App\Models\User::where('is_active', true)->count(); } catch (\Exception $e) { $activeUsers = 0; }
        try { $newThisWeek = \App\Models\User::where('created_at', '>=', now()->startOfWeek())->count(); } catch (\Exception $e) { $newThisWeek = 0; }
        try { $newThisMonth = \App\Models\User::whereMonth('created_at', now()->month)->count(); } catch (\Exception $e) { $newThisMonth = 0; }
        try { $totalConsultations = \App\Models\Consultation::count(); } catch (\Exception $e) { $totalConsultations = 0; }
        try { $totalAnimals = \App\Models\Animal::count(); } catch (\Exception $e) { $totalAnimals = 0; }
        try { $recentRegistrations = \App\Models\User::latest()->take(10)->get(); } catch (\Exception $e) { $recentRegistrations = collect(); }
        $systemUptime = '99.8%'; // stub

        return view('operations.dashboard', compact(
            'totalUsers','activeUsers','newThisWeek','newThisMonth',
            'totalConsultations','totalAnimals','recentRegistrations','systemUptime'
        ));
    }

    // ── Data Analyst Dashboard ────────────────────────────────────
    public function dataAnalyst()
    {
        try { $totalUsers = \App\Models\User::count(); } catch (\Exception $e) { $totalUsers = 0; }
        try { $totalConsults = \App\Models\Consultation::count(); } catch (\Exception $e) { $totalConsults = 0; }
        try { $totalAnimals = \App\Models\Animal::count(); } catch (\Exception $e) { $totalAnimals = 0; }
        try { $activeThisMonth = \App\Models\User::whereMonth('created_at', now()->month)->count(); } catch (\Exception $e) { $activeThisMonth = 0; }
        try { $usersByRole = \App\Models\User::select('role', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->groupBy('role')->pluck('count','role')->toArray(); } catch (\Exception $e) { $usersByRole = []; }
        try {
            $monthlyRegistrations = collect(range(5, 0))->map(function ($i) {
                $month = now()->subMonths($i);
                return ['month' => $month->format('M'), 'count' => \App\Models\User::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count()];
            });
        } catch (\Exception $e) { $monthlyRegistrations = collect(); }
        try { $recentConsults = \App\Models\Consultation::with('user')->latest()->take(10)->get(); } catch (\Exception $e) { $recentConsults = collect(); }

        return view('data-analyst.dashboard', compact(
            'totalUsers','totalConsults','totalAnimals','activeThisMonth',
            'usersByRole','monthlyRegistrations','recentConsults'
        ));
    }

    // ── M&E Officer Dashboard ─────────────────────────────────────
    public function monitoringEvaluation()
    {
        try { $totalFarmers = \App\Models\User::where('role','farmer')->count(); } catch (\Exception $e) { $totalFarmers = 0; }
        try { $totalAnimals = \App\Models\Animal::count(); } catch (\Exception $e) { $totalAnimals = 0; }
        try { $totalConsults = \App\Models\Consultation::count(); } catch (\Exception $e) { $totalConsults = 0; }
        try { $resolvedCases = \App\Models\Consultation::where('status','resolved')->count(); } catch (\Exception $e) { $resolvedCases = 0; }
        try { $stateActivity = \App\Models\User::select('state', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->whereNotNull('state')->groupBy('state')->orderByDesc('count')->take(8)->pluck('count','state')->toArray(); } catch (\Exception $e) { $stateActivity = []; }
        try {
            $monthlySummary = collect(range(5, 0))->map(function ($i) {
                $month = now()->subMonths($i);
                $total = \App\Models\Consultation::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
                $resolved = \App\Models\Consultation::where('status','resolved')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
                return [
                    'month'           => $month->format('M Y'),
                    'farmers'         => \App\Models\User::where('role','farmer')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count(),
                    'consults'        => $total,
                    'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100) : 0,
                ];
            })->toArray();
        } catch (\Exception $e) { $monthlySummary = []; }

        // Stub KPIs
        $scanAdoptionRate = 68;
        $vetResponseRate = 84;
        $farmerRetention = 72;
        $aiAccuracy = 91;
        $marketplaceUtilisation = 45;

        return view('monitoring-evaluation.dashboard', compact(
            'totalFarmers','totalAnimals','totalConsults','resolvedCases',
            'stateActivity','monthlySummary','scanAdoptionRate','vetResponseRate',
            'farmerRetention','aiAccuracy','marketplaceUtilisation'
        ));
    }

    // ── Field Officer Dashboard ───────────────────────────────────
    public function fieldOfficer()
    {
        $user = auth()->user();
        try { $assignedFarmers = \App\Models\User::where('role','farmer')->where('state', $user->state)->count(); } catch (\Exception $e) { $assignedFarmers = 0; }
        try { $recentFarmers = \App\Models\User::where('role','farmer')->latest()->take(8)->get(); } catch (\Exception $e) { $recentFarmers = collect(); }
        try { $visitsThisMonth = DB::table('extension_visits')->where('officer_id', $user->id)->whereMonth('visit_date', now()->month)->count(); } catch (\Exception $e) { $visitsThisMonth = 0; }
        try { $pendingFollowups = DB::table('extension_visits')->where('officer_id', $user->id)->where('visit_date', '>=', today())->count(); } catch (\Exception $e) { $pendingFollowups = 0; }
        try { $reportsSubmitted = DB::table('extension_advisory')->where('officer_id', $user->id)->count(); } catch (\Exception $e) { $reportsSubmitted = 0; }
        try { $farmersRegistered = \App\Models\User::where('role','farmer')->whereMonth('created_at', now()->month)->count(); } catch (\Exception $e) { $farmersRegistered = 0; }

        return view('field-officer.dashboard', compact(
            'assignedFarmers','recentFarmers','visitsThisMonth',
            'pendingFollowups','reportsSubmitted','farmersRegistered'
        ));
    }

    // ── Customer Support Dashboard ────────────────────────────────
    public function customerSupport()
    {
        try { $recentUsers = \App\Models\User::latest()->take(10)->get(); } catch (\Exception $e) { $recentUsers = collect(); }
        try { $totalUsers = \App\Models\User::count(); } catch (\Exception $e) { $totalUsers = 0; }

        // Stub support desk data
        $openTickets         = 0;
        $resolvedToday       = 0;
        $avgResponseTime     = 2;
        $satisfactionScore   = 94;
        $slaCompliance       = 88;
        $techIssues          = 0;
        $loginIssues         = 0;
        $marketplaceIssues   = 0;
        $aiQueryIssues       = 0;
        $generalIssues       = 0;

        return view('customer-support.dashboard', compact(
            'recentUsers','openTickets','resolvedToday','avgResponseTime',
            'satisfactionScore','slaCompliance','techIssues','loginIssues',
            'marketplaceIssues','aiQueryIssues','generalIssues'
        ));
    }

    // ── HR Dashboard ───────────────────────────────────────────────
    public function hr()
    {
        try { $staffCount = \App\Models\User::whereNotIn('role',['farmer','agro-dealer'])->count(); } catch (\Exception $e) { $staffCount = 0; }
        try { $presentToday = \App\Models\Attendance::whereDate('date', today())->where('status','present')->count(); } catch (\Exception $e) { $presentToday = 0; }
        try { $pendingLeaves = \App\Models\LeaveRequest::where('status','pending')->count(); } catch (\Exception $e) { $pendingLeaves = 0; }
        try { $recentStaff = \App\Models\User::whereNotIn('role',['farmer','agro-dealer'])->latest()->take(10)->get(); } catch (\Exception $e) { $recentStaff = collect(); }
        try { $absentToday = \App\Models\Attendance::whereDate('date', today())->where('status','absent')->count(); } catch (\Exception $e) { $absentToday = 0; }

        return view('hr.dashboard', compact(
            'staffCount','presentToday','pendingLeaves','recentStaff','absentToday'
        ));
    }
}
