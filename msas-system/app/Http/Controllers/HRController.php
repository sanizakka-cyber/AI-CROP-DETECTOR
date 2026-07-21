<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class HRController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', new Middleware('role:hr,admin,ceo')];
    }

    public function dashboard()
    {
        try { $staffCount   = User::whereNotIn('role', ['farmer', 'agro-dealer'])->count(); } catch (\Exception $e) { $staffCount = 0; }
        try { $presentToday = Attendance::whereDate('date', today())->where('status', 'present')->count(); } catch (\Exception $e) { $presentToday = 0; }
        try { $absentToday  = Attendance::whereDate('date', today())->where('status', 'absent')->count(); } catch (\Exception $e) { $absentToday = 0; }
        try { $pendingLeaves = LeaveRequest::where('status', 'pending')->count(); } catch (\Exception $e) { $pendingLeaves = 0; }
        try { $recentStaff  = User::whereNotIn('role', ['farmer', 'agro-dealer'])->latest()->take(10)->get(); } catch (\Exception $e) { $recentStaff = collect(); }

        return view('hr.dashboard', compact('staffCount', 'presentToday', 'absentToday', 'pendingLeaves', 'recentStaff'));
    }

    public function staff(Request $request)
    {
        $query = User::whereNotIn('role', ['farmer', 'agro-dealer']);
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'ilike', "%{$request->search}%")
                  ->orWhere('last_name',  'ilike', "%{$request->search}%")
                  ->orWhere('email',      'ilike', "%{$request->search}%");
            });
        }
        if ($request->role) $query->where('role', $request->role);
        if ($request->state) $query->where('state', $request->state);

        $staff = $query->latest()->paginate(20)->withQueryString();
        $roles = User::whereNotIn('role', ['farmer', 'agro-dealer'])->distinct()->pluck('role');
        $totalStaff = User::whereNotIn('role', ['farmer', 'agro-dealer'])->count();
        $activeStaff = User::whereNotIn('role', ['farmer', 'agro-dealer'])->where('is_active', true)->count();

        return view('hr.staff', compact('staff', 'roles', 'totalStaff', 'activeStaff'));
    }

    public function toggleStaffStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "{$user->name} has been {$status}.");
    }

    public function attendance(Request $request)
    {
        $date  = $request->date ? \Carbon\Carbon::parse($request->date) : today();
        $query = Attendance::with('user')->whereDate('date', $date);
        if ($request->status) $query->where('status', $request->status);

        $records = $query->latest()->paginate(30)->withQueryString();
        $presentCount = Attendance::whereDate('date', $date)->where('status', 'present')->count();
        $absentCount  = Attendance::whereDate('date', $date)->where('status', 'absent')->count();
        $lateCount    = Attendance::whereDate('date', $date)->where('status', 'late')->count();
        $staffList    = User::whereNotIn('role', ['farmer', 'agro-dealer'])->where('is_active', true)->get();

        return view('hr.attendance', compact('records', 'date', 'presentCount', 'absentCount', 'lateCount', 'staffList'));
    }

    public function markAttendance(Request $request)
    {
        $request->validate([
            'user_id'   => 'required|exists:users,id',
            'date'      => 'required|date',
            'status'    => 'required|in:present,absent,late',
            'check_in'  => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'notes'     => 'nullable|string|max:255',
        ]);

        Attendance::updateOrCreate(
            ['user_id' => $request->user_id, 'date' => $request->date],
            [
                'status'    => $request->status,
                'check_in'  => $request->check_in,
                'check_out' => $request->check_out,
                'notes'     => $request->notes,
            ]
        );

        return back()->with('success', 'Attendance recorded successfully.');
    }

    public function bulkAttendance(Request $request)
    {
        $request->validate([
            'date'    => 'required|date',
            'records' => 'required|array',
            'records.*.user_id' => 'required|exists:users,id',
            'records.*.status'  => 'required|in:present,absent,late',
        ]);

        foreach ($request->records as $rec) {
            Attendance::updateOrCreate(
                ['user_id' => $rec['user_id'], 'date' => $request->date],
                ['status' => $rec['status'], 'check_in' => $rec['check_in'] ?? null]
            );
        }

        return back()->with('success', 'Bulk attendance saved for ' . $request->date . '.');
    }

    public function leaves(Request $request)
    {
        $query = LeaveRequest::with('user');
        if ($request->status) $query->where('status', $request->status);
        if ($request->type)   $query->where('type', $request->type);

        $leaves       = $query->latest()->paginate(20)->withQueryString();
        $pendingCount = LeaveRequest::where('status', 'pending')->count();
        $approvedThisMonth = LeaveRequest::where('status', 'approved')->whereMonth('created_at', now()->month)->count();

        return view('hr.leaves', compact('leaves', 'pendingCount', 'approvedThisMonth'));
    }

    public function approveLeave(LeaveRequest $leave)
    {
        $leave->update(['status' => 'approved', 'reviewed_by' => auth()->id()]);
        return back()->with('success', 'Leave request approved.');
    }

    public function rejectLeave(Request $request, LeaveRequest $leave)
    {
        $request->validate(['admin_note' => 'nullable|string|max:500']);
        $leave->update(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'admin_note' => $request->admin_note]);
        return back()->with('success', 'Leave request rejected.');
    }

    public function payroll(Request $request)
    {
        $query = Payroll::with('user');
        if ($request->month)  $query->where('month', 'like', "%{$request->month}%");
        if ($request->status) $query->where('status', $request->status);

        $payrolls     = $query->latest()->paginate(20)->withQueryString();
        $totalPaid    = Payroll::where('status', 'paid')->sum('net_salary');
        $pendingPay   = Payroll::where('status', 'pending')->sum('net_salary');
        $staffList    = User::whereNotIn('role', ['farmer', 'agro-dealer'])->where('is_active', true)->get();

        return view('hr.payroll', compact('payrolls', 'totalPaid', 'pendingPay', 'staffList'));
    }

    public function storePayroll(Request $request)
    {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'month'        => 'required|string|max:20',
            'basic_salary' => 'required|numeric|min:0',
            'bonus'        => 'nullable|numeric|min:0',
            'deductions'   => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
        ]);

        $basic      = $request->basic_salary;
        $bonus      = $request->bonus ?? 0;
        $deductions = $request->deductions ?? 0;
        $net        = $basic + $bonus - $deductions;

        Payroll::updateOrCreate(
            ['user_id' => $request->user_id, 'month' => $request->month],
            [
                'basic_salary'  => $basic,
                'bonus'         => $bonus,
                'deductions'    => $deductions,
                'net_salary'    => $net,
                'status'        => $request->payment_date ? 'paid' : 'pending',
                'payment_date'  => $request->payment_date,
            ]
        );

        return back()->with('success', 'Payroll record saved for ' . $request->month . '.');
    }

    public function markPayrollPaid(Payroll $payroll)
    {
        $payroll->update(['status' => 'paid', 'payment_date' => today()]);
        return back()->with('success', 'Payroll marked as paid.');
    }
}
