<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageApiController extends Controller
{
    // GET /api/messages — inbox (one entry per conversation partner)
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $allMessages = Message::where('from_user_id', $userId)
            ->orWhere('to_user_id', $userId)
            ->with(['fromUser:id,first_name,last_name,role,profile_photo',
                    'toUser:id,first_name,last_name,role,profile_photo'])
            ->orderByDesc('created_at')
            ->get();

        $conversations = $allMessages
            ->groupBy(fn($msg) => $msg->from_user_id === $userId ? $msg->to_user_id : $msg->from_user_id)
            ->map(function ($msgs) use ($userId) {
                $latest  = $msgs->first();
                $partner = $latest->from_user_id === $userId ? $latest->toUser : $latest->fromUser;
                $unread  = $msgs->filter(fn($m) => $m->to_user_id === $userId && is_null($m->read_at))->count();
                return [
                    'partner'    => $partner,
                    'last_body'  => $latest->body,
                    'last_at'    => $latest->created_at->toISOString(),
                    'unread'     => $unread,
                    'is_mine'    => $latest->from_user_id === $userId,
                ];
            })
            ->values();

        $unreadTotal = Message::where('to_user_id', $userId)->whereNull('read_at')->count();

        return response()->json([
            'conversations' => $conversations,
            'unread_total'  => $unreadTotal,
        ]);
    }

    // GET /api/messages/{user} — full thread with one user
    public function show(Request $request, User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot message yourself.'], 400);
        }

        $userId = auth()->id();

        // Mark incoming as read
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
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => [
                'id'      => $m->id,
                'body'    => $m->body,
                'is_mine' => $m->from_user_id === $userId,
                'read_at' => $m->read_at?->toISOString(),
                'sent_at' => $m->created_at->toISOString(),
            ]);

        return response()->json([
            'partner'  => [
                'id'            => $user->id,
                'name'          => trim($user->first_name . ' ' . $user->last_name),
                'role'          => $user->role,
                'profile_photo' => $user->profile_photo,
            ],
            'messages' => $messages,
        ]);
    }

    // POST /api/messages/{user} — send a message
    public function send(Request $request, User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot message yourself.'], 400);
        }

        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'from_user_id' => auth()->id(),
            'to_user_id'   => $user->id,
            'body'         => $data['body'],
        ]);

        return response()->json([
            'message' => [
                'id'      => $message->id,
                'body'    => $message->body,
                'is_mine' => true,
                'read_at' => null,
                'sent_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }
}
