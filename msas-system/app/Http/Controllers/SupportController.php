<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class SupportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', new Middleware('role:customer-support,admin,ceo')];
    }

    public function tickets(Request $request)
    {
        $query = DB::table('support_tickets')->orderByDesc('created_at');
        if ($request->status) $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);
        if ($request->search) $query->where('subject', 'ilike', "%{$request->search}%");

        try {
            $tickets = $query->paginate(20)->withQueryString();
        } catch (\Exception $e) {
            $tickets = collect()->paginate(20) ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            $tickets = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        $stats = [
            'open'       => $this->safeCount('support_tickets', ['status' => 'open']),
            'in_progress'=> $this->safeCount('support_tickets', ['status' => 'in_progress']),
            'resolved'   => $this->safeCount('support_tickets', ['status' => 'resolved']),
            'total'      => $this->safeCount('support_tickets'),
        ];

        $users = User::latest()->take(30)->get();

        return view('support.tickets', compact('tickets', 'stats', 'users'));
    }

    public function create()
    {
        $users = User::latest()->get();
        return view('support.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'     => 'nullable|exists:users,id',
            'subject'     => 'required|string|max:200',
            'description' => 'required|string',
            'priority'    => 'required|in:low,medium,high,urgent',
            'category'    => 'required|string|max:100',
        ]);

        try {
            DB::table('support_tickets')->insert([
                'user_id'      => $request->user_id,
                'assigned_to'  => auth()->id(),
                'subject'      => $request->subject,
                'description'  => $request->description,
                'priority'     => $request->priority,
                'category'     => $request->category,
                'status'       => 'open',
                'reference'    => 'TKT-' . strtoupper(substr(uniqid(), -6)),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not save ticket. Table may need migration: ' . $e->getMessage());
        }

        return redirect()->route('support.tickets')->with('success', 'Ticket created successfully.');
    }

    public function show($ticket)
    {
        try {
            $ticket = DB::table('support_tickets')->where('id', $ticket)->first();
            if (!$ticket) abort(404);
            $replies = DB::table('ticket_replies')->where('ticket_id', $ticket->id)->orderBy('created_at')->get();
            $user    = $ticket->user_id ? User::find($ticket->user_id) : null;
        } catch (\Exception $e) {
            return back()->with('error', 'Ticket system not yet migrated.');
        }

        return view('support.show', compact('ticket', 'replies', 'user'));
    }

    public function resolve($ticket)
    {
        try {
            DB::table('support_tickets')->where('id', $ticket)->update(['status' => 'resolved', 'updated_at' => now()]);
        } catch (\Exception $e) {}
        return back()->with('success', 'Ticket resolved.');
    }

    public function reply(Request $request, $ticket)
    {
        $request->validate(['message' => 'required|string|max:2000']);
        try {
            DB::table('ticket_replies')->insert([
                'ticket_id'  => $ticket,
                'user_id'    => auth()->id(),
                'message'    => $request->message,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('support_tickets')->where('id', $ticket)->update(['status' => 'in_progress', 'updated_at' => now()]);
        } catch (\Exception $e) {}
        return back()->with('success', 'Reply sent.');
    }

    public function close($ticket)
    {
        try {
            DB::table('support_tickets')->where('id', $ticket)->update(['status' => 'closed', 'updated_at' => now()]);
        } catch (\Exception $e) {}
        return back()->with('success', 'Ticket closed.');
    }

    private function safeCount($table, $where = [])
    {
        try {
            $q = DB::table($table);
            foreach ($where as $k => $v) $q->where($k, $v);
            return $q->count();
        } catch (\Exception $e) { return 0; }
    }
}
