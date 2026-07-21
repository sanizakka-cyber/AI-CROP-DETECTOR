<?php

namespace App\Http\Controllers;

use App\Models\Finance;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', new Middleware('role:finance,admin,ceo')];
    }

    public function transactions(Request $request)
    {
        $query = Finance::query();
        if ($request->type)     $query->where('type', $request->type);
        if ($request->category) $query->where('category', $request->category);
        if ($request->from)     $query->whereDate('transaction_date', '>=', $request->from);
        if ($request->to)       $query->whereDate('transaction_date', '<=', $request->to);

        $transactions  = $query->latest('transaction_date')->paginate(25)->withQueryString();
        $totalIncome   = Finance::where('type', 'Income')->sum('amount');
        $totalExpenses = Finance::where('type', 'Expense')->sum('amount');
        $netBalance    = $totalIncome - $totalExpenses;
        $categories    = Finance::distinct()->pluck('category')->sort()->values();

        return view('finance.transactions', compact(
            'transactions', 'totalIncome', 'totalExpenses', 'netBalance', 'categories'
        ));
    }

    public function storeTransaction(Request $request)
    {
        $request->validate([
            'type'             => 'required|in:Income,Expense',
            'category'         => 'required|string|max:100',
            'amount'           => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description'      => 'nullable|string|max:500',
            'reference'        => 'nullable|string|max:100',
        ]);

        Finance::create([
            'user_id'          => auth()->id(),
            'type'             => $request->type,
            'category'         => $request->category,
            'amount'           => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description'      => $request->description,
            'reference'        => $request->reference,
        ]);

        return back()->with('success', "{$request->type} of ₦" . number_format($request->amount) . ' recorded.');
    }

    public function deleteTransaction(Finance $finance)
    {
        $finance->delete();
        return back()->with('success', 'Transaction deleted.');
    }

    public function payroll(Request $request)
    {
        $query = Payroll::with('user');
        if ($request->month)  $query->where('month', 'like', "%{$request->month}%");
        if ($request->status) $query->where('status', $request->status);

        $payrolls     = $query->latest()->paginate(20)->withQueryString();
        $totalPayroll = Payroll::sum('net_salary');
        $totalPaid    = Payroll::where('status', 'paid')->sum('net_salary');
        $totalPending = Payroll::where('status', 'pending')->sum('net_salary');

        return view('finance.payroll', compact('payrolls', 'totalPayroll', 'totalPaid', 'totalPending'));
    }

    public function reports(Request $request)
    {
        $year = $request->year ?? now()->year;

        try { $annualIncome   = Finance::where('type', 'Income')->whereYear('transaction_date', $year)->sum('amount'); } catch (\Exception $e) { $annualIncome = 0; }
        try { $annualExpenses = Finance::where('type', 'Expense')->whereYear('transaction_date', $year)->sum('amount'); } catch (\Exception $e) { $annualExpenses = 0; }
        try { $annualPayroll  = Payroll::whereYear('created_at', $year)->sum('net_salary'); } catch (\Exception $e) { $annualPayroll = 0; }

        try {
            $monthlyData = collect(range(1, 12))->map(function ($m) use ($year) {
                $income   = Finance::where('type', 'Income')->whereYear('transaction_date', $year)->whereMonth('transaction_date', $m)->sum('amount');
                $expenses = Finance::where('type', 'Expense')->whereYear('transaction_date', $year)->whereMonth('transaction_date', $m)->sum('amount');
                return [
                    'month'    => \Carbon\Carbon::create($year, $m, 1)->format('M Y'),
                    'income'   => $income,
                    'expenses' => $expenses,
                ];
            });
        } catch (\Exception $e) { $monthlyData = collect(); }

        try {
            $categoryData = Finance::whereYear('transaction_date', $year)
                ->select('category', 'type', DB::raw('SUM(amount) as total'))
                ->groupBy('category', 'type')
                ->orderByDesc('total')
                ->get();
        } catch (\Exception $e) { $categoryData = collect(); }

        // Use single-quoted string literals (PostgreSQL-compatible CASE WHEN)
        try {
            $payrollByMonth = Payroll::select(
                'month',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(net_salary) as total'),
                DB::raw("SUM(CASE WHEN status = 'paid'    THEN net_salary ELSE 0 END) as paid"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN net_salary ELSE 0 END) as pending")
            )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        } catch (\Exception $e) { $payrollByMonth = collect(); }

        return view('finance.reports', compact(
            'annualIncome', 'annualExpenses', 'annualPayroll',
            'monthlyData', 'categoryData', 'payrollByMonth', 'year'
        ));
    }
}
