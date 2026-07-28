<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiderApiController extends Controller
{
    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $query = Order::where(fn($q) =>
            $q->where('rider_id', $user->id)->orWhereNull('rider_id')
        )->with(['buyer:id,first_name,last_name,phone']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return response()->json($query->latest()->paginate(20));
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        if ($order->rider_id && $order->rider_id !== $user->id) {
            return response()->json(['error' => 'Already assigned to another rider.'], 409);
        }
        if (! in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json(['error' => 'Order cannot be accepted in its current status.'], 422);
        }
        $order->update(['rider_id' => $user->id, 'status' => 'assigned']);
        return response()->json(['message' => 'Order accepted.', 'order' => $order->fresh()]);
    }

    public function decline(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        if ($order->rider_id !== $user->id) return response()->json(['error' => 'Not assigned to you.'], 403);
        $order->update(['rider_id' => null, 'status' => 'confirmed']);
        return response()->json(['message' => 'Order returned to queue.']);
    }

    public function markInTransit(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        if ($order->rider_id !== $user->id) return response()->json(['error' => 'Not assigned to you.'], 403);
        if ($order->status !== 'assigned') {
            return response()->json(['error' => 'Order must be in "assigned" status first.'], 422);
        }
        $order->update(['status' => 'in_transit']);
        return response()->json(['message' => 'Order marked in transit.', 'order' => $order->fresh()]);
    }

    public function markDelivered(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        if ($order->rider_id !== $user->id) return response()->json(['error' => 'Not assigned to you.'], 403);
        if ($order->status !== 'in_transit') {
            return response()->json(['error' => 'Order must be in transit before marking delivered.'], 422);
        }
        $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        return response()->json(['message' => 'Order marked as delivered.', 'order' => $order->fresh()]);
    }

    public function updateAvailability(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        $data = $request->validate(['rider_status' => 'required|in:available,busy,offline']);
        $user->update(['rider_status' => $data['rider_status']]);
        return response()->json(['message' => 'Availability updated.', 'rider_status' => $user->fresh()->rider_status]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        $activeOrder = Order::where('rider_id', $user->id)
            ->whereIn('status', ['assigned', 'in_transit'])
            ->with(['buyer:id,first_name,last_name,phone'])
            ->first();
        return response()->json([
            'rider' => array_merge(
                $user->only(['id','first_name','last_name','phone']),
                ['rider_status' => $user->rider_status]
            ),
            'active_order' => $activeOrder,
        ]);
    }
}