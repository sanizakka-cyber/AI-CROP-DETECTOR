<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Consultation;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', new Middleware('role:operations,admin,ceo')];
    }

    public function tasks(Request $request)
    {
        try {
            $query = DB::table('operations_tasks')->orderByDesc('created_at');
            if ($request->status)   $query->where('status', $request->status);
            if ($request->priority) $query->where('priority', $request->priority);
            $tasks = $query->paginate(20)->withQueryString();
            $stats = [
                'pending'     => DB::table('operations_tasks')->where('status','pending')->count(),
                'in_progress' => DB::table('operations_tasks')->where('status','in_progress')->count(),
                'completed'   => DB::table('operations_tasks')->where('status','completed')->count(),
                'total'       => DB::table('operations_tasks')->count(),
            ];
        } catch (\Exception $e) {
            $tasks = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            $stats = ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'total' => 0];
        }

        $staff = User::whereNotIn('role', ['farmer', 'agro-dealer'])->get();

        return view('operations.tasks', compact('tasks', 'stats', 'staff'));
    }

    public function storeTask(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'priority'    => 'required|in:low,medium,high',
            'due_date'    => 'nullable|date',
        ]);

        try {
            DB::table('operations_tasks')->insert([
                'title'       => $request->title,
                'description' => $request->description,
                'assigned_to' => $request->assigned_to,
                'created_by'  => auth()->id(),
                'priority'    => $request->priority,
                'due_date'    => $request->due_date,
                'status'      => 'pending',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not save task. Table may need migration.');
        }

        return back()->with('success', 'Task created successfully.');
    }

    public function updateTaskStatus(Request $request, $task)
    {
        $request->validate(['status' => 'required|in:pending,in_progress,completed,cancelled']);
        try {
            DB::table('operations_tasks')->where('id', $task)->update(['status' => $request->status, 'updated_at' => now()]);
        } catch (\Exception $e) {}
        return back()->with('success', 'Task status updated.');
    }

    public function users()
    {
        $totalUsers   = User::count();
        $activeUsers  = User::where('is_active', true)->count();
        $newThisMonth = User::whereMonth('created_at', now()->month)->count();
        $byRole       = User::select('role', DB::raw('count(*) as cnt'))->groupBy('role')->orderByDesc('cnt')->get();
        $recentUsers  = User::latest()->paginate(20);

        return view('operations.users', compact('totalUsers', 'activeUsers', 'newThisMonth', 'byRole', 'recentUsers'));
    }
}
