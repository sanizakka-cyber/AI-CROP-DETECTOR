<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    /** GET /notifications — paginated list for current user */
    public function index(Request $request): JsonResponse
    {
        $notifications = MobileNotification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(30);

        $unread = MobileNotification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'data'         => $notifications->map(fn($n) => $this->shape($n)),
            'unread_count' => $unread,
            'total'        => $notifications->total(),
            'current_page' => $notifications->currentPage(),
            'last_page'    => $notifications->lastPage(),
        ]);
    }

    /** PATCH /notifications/{id}/read */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notif = MobileNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notif->markRead();

        return response()->json(['message' => 'Marked as read.']);
    }

    /** POST /notifications/read-all */
    public function markAllRead(Request $request): JsonResponse
    {
        MobileNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /** DELETE /notifications/{id} */
    public function destroy(Request $request, int $id): JsonResponse
    {
        MobileNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

    private function shape(MobileNotification $n): array
    {
        return [
            'id'         => $n->id,
            'title'      => $n->title,
            'body'       => $n->body,
            'type'       => $n->type,
            'icon'       => $n->icon ?? '🔔',
            'data'       => $n->data ?? [],
            'read'       => (bool) $n->read_at,
            'read_at'    => $n->read_at?->toISOString(),
            'created_at' => $n->created_at->toISOString(),
            'time_ago'   => $n->created_at->diffForHumans(),
        ];
    }
}
