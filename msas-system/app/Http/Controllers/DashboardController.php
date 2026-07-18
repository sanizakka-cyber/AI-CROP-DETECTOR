<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
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
        try { $poultryCount       = \App\Models\PoultryRecord::where('user_id', $user->id)->count(); } catch (\Exception $e) { $poultryCount = 0; }
        try { $diagnosesCount     = \App\Models\Diagnosis::where('user_id', $user->id)->count(); } catch (\Exception $e) { $diagnosesCount = 0; }
        try { $recentScans        = \App\Models\Diagnosis::where('user_id', $user->id)->latest()->take(5)->get(); } catch (\Exception $e) { $recentScans = collect(); }
        try { $pendingVetConsults  = \App\Models\Consultation::where('farmer_id', $user->id)->where('status', 'pending')->count(); } catch (\Exception $e) { $pendingVetConsults = 0; }
        try { $recentAnimals      = \App\Models\Animal::where('user_id', $user->id)->latest()->take(5)->get(); } catch (\Exception $e) { $recentAnimals = collect(); }
        try { $recentFlocks       = \App\Models\PoultryRecord::where('user_id', $user->id)->latest()->take(3)->get(); } catch (\Exception $e) { $recentFlocks = collect(); }
        try { $recentConsults     = \App\Models\Consultation::where('farmer_id', $user->id)->latest()->take(4)->get(); } catch (\Exception $e) { $recentConsults = collect(); }
        try { $totalIncome        = \App\Models\Finance::where('user_id', $user->id)->where('type', 'Income')->sum('amount'); } catch (\Exception $e) { $totalIncome = 0; }
        try { $totalExpense       = \App\Models\Finance::where('user_id', $user->id)->where('type', 'Expense')->sum('amount'); } catch (\Exception $e) { $totalExpense = 0; }
        try { $recentFinances     = \App\Models\Finance::where('user_id', $user->id)->latest('transaction_date')->take(5)->get(); } catch (\Exception $e) { $recentFinances = collect(); }
        $netBalance = $totalIncome - $totalExpense;

        return view('farmer.dashboard', compact(
            'animalsCount', 'poultryCount', 'diagnosesCount', 'recentScans', 'pendingVetConsults',
            'recentAnimals', 'recentFlocks', 'recentConsults', 'totalIncome', 'totalExpense',
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
        // Uptime = (total users who logged in last 30 days / total active users) as platform health proxy
        try {
            $activeUsers30d = \App\Models\User::where('is_active', true)
                ->where('last_seen', '>=', now()->subDays(30))->count();
            $systemUptime = $activeUsers > 0 ? round(($activeUsers30d / $activeUsers) * 100, 1) . '%' : 'N/A';
        } catch (\Exception $e) { $systemUptime = 'N/A'; }

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
        try { $stateActivity = \App\Models\User::select('state', DB::raw('count(*) as count'))->whereNotNull('state')->groupBy('state')->orderByDesc('count')->take(8)->pluck('count','state')->toArray(); } catch (\Exception $e) { $stateActivity = []; }
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

        // ── Live KPIs (replacing all hardcoded values) ─────────────
        try {
            $farmersWithScans = \App\Models\Diagnosis::distinct('user_id')->count('user_id');
            $scanAdoptionRate = $totalFarmers > 0 ? round(($farmersWithScans / $totalFarmers) * 100) : 0;
        } catch (\Exception $e) { $scanAdoptionRate = 0; }

        try {
            $consultsWithResponse = \App\Models\Consultation::whereNotNull('expert_response')->count();
            $vetResponseRate = $totalConsults > 0 ? round(($consultsWithResponse / $totalConsults) * 100) : 0;
        } catch (\Exception $e) { $vetResponseRate = 0; }

        try {
            $activeRecentFarmers = \App\Models\User::where('role','farmer')
                ->where('last_seen', '>=', now()->subDays(30))->count();
            $farmerRetention = $totalFarmers > 0 ? round(($activeRecentFarmers / $totalFarmers) * 100) : 0;
        } catch (\Exception $e) { $farmerRetention = 0; }

        try {
            $totalDiag = \App\Models\Diagnosis::count();
            $confirmedDiag = \App\Models\Diagnosis::where('status','reviewed')->count();
            $aiAccuracy = $totalDiag > 0 ? round(($confirmedDiag / $totalDiag) * 100) : 0;
        } catch (\Exception $e) { $aiAccuracy = 0; }

        try {
            $farmersWhoOrdered = \App\Models\Order::whereHas('buyer', fn($q) => $q->where('role','farmer'))
                ->distinct('buyer_id')->count('buyer_id');
            $marketplaceUtilisation = $totalFarmers > 0 ? round(($farmersWhoOrdered / $totalFarmers) * 100) : 0;
        } catch (\Exception $e) { $marketplaceUtilisation = 0; }

        try {
            $extensionVisitsThisMonth = DB::table('extension_visits')
                ->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year)->count();
            $extensionAdvisories = DB::table('extension_advisory')
                ->whereMonth('created_at', now()->month)->count();
        } catch (\Exception $e) { $extensionVisitsThisMonth = 0; $extensionAdvisories = 0; }

        return view('monitoring-evaluation.dashboard', compact(
            'totalFarmers','totalAnimals','totalConsults','resolvedCases',
            'stateActivity','monthlySummary','scanAdoptionRate','vetResponseRate',
            'farmerRetention','aiAccuracy','marketplaceUtilisation',
            'extensionVisitsThisMonth','extensionAdvisories'
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

        // ── Live ticket stats from support_tickets table ───────────
        try { $openTickets = DB::table('support_tickets')->where('status','open')->count(); } catch (\Exception $e) { $openTickets = 0; }
        try { $resolvedToday = DB::table('support_tickets')->where('status','resolved')->whereDate('updated_at', today())->count(); } catch (\Exception $e) { $resolvedToday = 0; }
        try { $pendingTickets = DB::table('support_tickets')->where('status','pending')->count(); } catch (\Exception $e) { $pendingTickets = 0; }
        try { $totalTickets = DB::table('support_tickets')->count(); } catch (\Exception $e) { $totalTickets = 0; }
        try { $recentTickets = DB::table('support_tickets')->orderByDesc('created_at')->take(10)->get(); } catch (\Exception $e) { $recentTickets = collect(); }

        // Category breakdown
        try {
            $techIssues        = DB::table('support_tickets')->where('category','technical')->count();
            $loginIssues       = DB::table('support_tickets')->where('category','login')->count();
            $marketplaceIssues = DB::table('support_tickets')->where('category','marketplace')->count();
            $aiQueryIssues     = DB::table('support_tickets')->where('category','ai-query')->count();
            $generalIssues     = DB::table('support_tickets')->where('category','general')->count();
        } catch (\Exception $e) {
            $techIssues = $loginIssues = $marketplaceIssues = $aiQueryIssues = $generalIssues = 0;
        }

        // Average first-reply time in hours (created_at → first reply created_at)
        try {
            $avgResponseTime = DB::table('ticket_replies as r')
                ->join('support_tickets as t', 't.id', '=', 'r.ticket_id')
                ->whereRaw('r.created_at = (SELECT MIN(r2.created_at) FROM ticket_replies r2 WHERE r2.ticket_id = t.id)')
                ->whereColumn('r.user_id', '!=', 't.user_id')
                ->selectRaw('ROUND(AVG(TIMESTAMPDIFF(HOUR, t.created_at, r.created_at)), 1) as avg_hours')
                ->value('avg_hours') ?? 0;
        } catch (\Exception $e) { $avgResponseTime = 0; }

        // SLA compliance: tickets resolved within 24 h / all resolved tickets
        try {
            $resolvedTotal = DB::table('support_tickets')->where('status','resolved')->count();
            $withinSla     = DB::table('support_tickets')->where('status','resolved')
                ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 24')->count();
            $slaCompliance = $resolvedTotal > 0 ? round(($withinSla / $resolvedTotal) * 100) : 100;
        } catch (\Exception $e) { $slaCompliance = 0; }

        // Satisfaction from diagnoses feedback as a proxy (no dedicated feedback table yet)
        try {
            $positiveReviews = \App\Models\Diagnosis::where('status','reviewed')->count();
            $totalReviewed   = \App\Models\Diagnosis::count();
            $satisfactionScore = $totalReviewed > 0 ? round(($positiveReviews / $totalReviewed) * 100) : 0;
        } catch (\Exception $e) { $satisfactionScore = 0; }

        return view('customer-support.dashboard', compact(
            'recentUsers','totalUsers','openTickets','resolvedToday','pendingTickets',
            'totalTickets','recentTickets','avgResponseTime','satisfactionScore',
            'slaCompliance','techIssues','loginIssues','marketplaceIssues',
            'aiQueryIssues','generalIssues'
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
