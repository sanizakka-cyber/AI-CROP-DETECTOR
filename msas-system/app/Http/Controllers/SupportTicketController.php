<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MobileNotification;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    // Farmer: list own tickets
    public function index(Request $request)
    {
        $tickets = SupportTicket::where('user_id', auth()->id())
            ->latest()->paginate(10);
        return view('farmer.support.index', compact('tickets'));
    }

    // Farmer: create form
    public function create()
    {
        return view('farmer.support.create');
    }

    // Farmer: store new ticket
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string|min:10|max:3000',
            'category' => 'required|in:general,technical,billing,consultation,other',
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        $data['user_id']       = auth()->id();
        $data['ticket_number'] = SupportTicket::generateNumber();
        $ticket = SupportTicket::create($data);

        AuditLog::record('support.ticket.created', 'SupportTicket', $ticket->id, ['ticket_number' => $ticket->ticket_number]);

        return redirect()->route('support.show', $ticket)
            ->with('success', 'Ticket #'.$ticket->ticket_number.' submitted. Our team will respond within 24 hours.');
    }

    // Farmer: view own ticket + replies
    public function show(SupportTicket $ticket)
    {
        abort_if($ticket->user_id !== auth()->id(), 403);
        $ticket->load('replies.user');
        return view('farmer.support.show', compact('ticket'));
    }

    // Farmer: add reply to own ticket
    public function reply(Request $request, SupportTicket $ticket)
    {
        abort_if($ticket->user_id !== auth()->id(), 403);
        abort_if(in_array($ticket->status, ['resolved','closed']), 403, 'Ticket is closed.');

        $data = $request->validate(['message' => 'required|string|min:2|max:2000']);
        SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'message'   => $data['message'],
            'is_staff'  => false,
        ]);
        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'in_progress']);
        }
        return back()->with('success', 'Reply sent.');
    }

    // ── Staff-side ────────────────────────────────────────────────────────────

    public function adminIndex(Request $request)
    {
        $query = SupportTicket::with(['user:id,first_name,last_name,role', 'assignee:id,first_name,last_name'])
            ->latest();

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('priority')) $query->where('priority', $request->priority);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq->where('subject','ilike',"%$q%")
                                        ->orWhere('ticket_number','ilike',"%$q%"));
        }

        $tickets  = $query->paginate(20)->withQueryString();
        $openCount      = SupportTicket::where('status','open')->count();
        $inProgressCount= SupportTicket::where('status','in_progress')->count();
        $resolvedCount  = SupportTicket::where('status','resolved')->count();
        $urgentCount    = SupportTicket::where('priority','urgent')->whereIn('status',['open','in_progress'])->count();

        return view('ceo.support', compact('tickets','openCount','inProgressCount','resolvedCount','urgentCount'));
    }

    public function adminShow(SupportTicket $ticket)
    {
        $ticket->load('replies.user', 'user', 'assignee');
        $staff = User::whereIn('role',['ceo','admin','customer-support'])->orderBy('first_name')->get(['id','first_name','last_name','role']);
        return view('ceo.support-show', compact('ticket', 'staff'));
    }

    public function adminUpdate(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'status'      => 'required|in:open,in_progress,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'resolution'  => 'nullable|string|max:3000',
        ]);

        if ($data['status'] === 'resolved' && !$ticket->resolved_at) {
            $data['resolved_at'] = now();
            // Notify farmer
            MobileNotification::send(
                $ticket->user_id,
                'Support ticket resolved',
                'Your ticket #'.$ticket->ticket_number.' has been resolved.',
                'system',
                ['ticket_id' => $ticket->id]
            );
        }

        $ticket->update($data);
        AuditLog::record('support.ticket.updated', 'SupportTicket', $ticket->id, ['status' => $data['status']]);
        return back()->with('success', 'Ticket updated.');
    }

    public function adminReply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['message' => 'required|string|min:2|max:2000']);
        SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'message'   => $data['message'],
            'is_staff'  => true,
        ]);
        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }
        MobileNotification::send(
            $ticket->user_id,
            'New reply to your ticket',
            'Staff replied to #'.$ticket->ticket_number.': '.substr($data['message'],0,80),
            'system',
            ['ticket_id' => $ticket->id]
        );
        return back()->with('success', 'Reply sent.');
    }

    // ── Notification Centre ────────────────────────────────────────────────────

    public function notificationCentre(Request $request)
    {
        $notifications = \App\Models\MobileNotification::where('user_id', auth()->id())
            ->latest()->paginate(20);
        // Mark all as read
        \App\Models\MobileNotification::where('user_id', auth()->id())
            ->whereNull('read_at')->update(['read_at' => now()]);
        return view('farmer.notifications', compact('notifications'));
    }

    // ── Broadcast Tool (CEO/Admin) ─────────────────────────────────────────────

    public function broadcastForm()
    {
        return view('ceo.broadcast');
    }

    public function broadcastSend(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:100',
            'body'    => 'required|string|max:500',
            'segment' => 'required|in:all,farmers,vets,agro,pilots',
            'state'   => 'nullable|string|max:100',
            'icon'    => 'nullable|string|max:5',
        ]);

        $query = User::where('is_active', true);
        match($data['segment']) {
            'farmers' => $query->where('role', 'farmer'),
            'vets'    => $query->where('role', 'vet'),
            'agro'    => $query->where('role', 'agronomist'),
            'pilots'  => $query->where('is_pilot', true),
            default   => null,
        };
        if (!empty($data['state'])) $query->where('state', $data['state']);

        $count = 0;
        $query->select('id','expo_push_token')->chunk(200, function ($users) use ($data, &$count) {
            foreach ($users as $user) {
                MobileNotification::send(
                    $user->id,
                    $data['title'],
                    $data['body'],
                    'system',
                    [],
                    $data['icon'] ?? '📢'
                );
                $count++;
            }
        });

        AuditLog::record('broadcast.sent', 'User', null, [
            'segment' => $data['segment'],
            'count'   => $count,
            'title'   => $data['title'],
        ]);

        return back()->with('success', "Broadcast sent to {$count} users.");
    }
}