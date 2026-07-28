<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    // GET /messages — inbox: one entry per conversation partner, most recent first
    public function index(): View
    {
        $userId = auth()->id();

        $allMessages = Message::where('from_user_id', $userId)
            ->orWhere('to_user_id', $userId)
            ->with(['fromUser', 'toUser'])
            ->orderByDesc('created_at')
            ->get();

        // Group by partner, keep only the latest message per partner
        $conversations = $allMessages
            ->groupBy(fn($msg) => $msg->from_user_id === $userId ? $msg->to_user_id : $msg->from_user_id)
            ->map(fn($msgs) => $msgs->first())
            ->values();

        $unreadTotal = Message::where('to_user_id', $userId)->whereNull('read_at')->count();

        return view('messages.index', compact('conversations', 'unreadTotal'));
    }

    // GET /messages/{user} — full thread with one user
    public function show(User $user): View
    {
        abort_if($user->id === auth()->id(), 400, 'Cannot message yourself.');

        $userId = auth()->id();

        // Mark all incoming messages in this thread as read
        Message::where('from_user_id', $user->id)
            ->where('to_user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::where(function ($q) use ($userId, $user) {
                $q->where('from_user_id', $userId)->where('to_user_id', $user->id);
            })
            ->orWhere(function ($q) use ($userId, $user) {
                $q->where('from_user_id', $user->id)->where('to_user_id', $userId);
            })
            ->with('fromUser')
            ->orderBy('created_at')
            ->get();

        return view('messages.show', compact('user', 'messages'));
    }

    // POST /messages/{user} — send a message
    public function send(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 400, 'Cannot message yourself.');

        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        Message::create([
            'from_user_id' => auth()->id(),
            'to_user_id'   => $user->id,
            'body'         => $data['body'],
        ]);

        return redirect()->route('messages.show', $user)->with('success', 'Message sent.');
    }
}
