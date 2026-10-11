<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MobileNotification;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiderApiController extends Controller
{
    // This controller previously drove its own ad-hoc state machine on
    // Order::status ('pending' -> 'assigned' -> 'in_transit' -> 'delivered')
    // and let any rider self-assign an unassigned order. The real lifecycle
    // the rest of the app (web RiderController, admin dispatch, and
    // MarketplaceController::confirmDelivery()'s wallet-credit trigger) is
    // built on is Order::rider_status ('assigned' [set only by admin
    // dispatch] -> 'accepted' -> 'in_transit' -> 'awaiting_confirmation' ->
    // 'completed'), with rider_id set by an admin/CEO dispatcher, never by
    // the rider. Because this controller never touched rider_status, a
    // mobile-delivered order could never reach 'awaiting_confirmation', so
    // confirmDelivery()'s guard (abort_if rider_status !== 'awaiting_confirmation')
    // could never pass -- riders/dealers using the app were never paid. It
    // also let any rider grab any pending/confirmed order with a
    // check-then-act race and no admin dispatch step. Rewritten to mirror
    // RiderController's lifecycle exactly, closing both gaps at once.

    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $query = Order::where('rider_id', $user->id)
            ->with(['buyer:id,first_name,last_name,phone']);
        if ($request->filled('status')) {
            $query->where('rider_status', $request->status);
        }
        return response()->json($query->latest()->paginate(20));
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        if ($order->rider_id !== $user->id) return response()->json(['error' => 'Not assigned to you.'], 403);
        if ($order->rider_status !== 'assigned') {
            return response()->json(['error' => 'Order is not in assigned state.'], 422);
        }

        $order->update([
            'rider_status'      => 'accepted',
            'rider_accepted_at' => now(),
            'status'            => 'processing',
        ]);

        $vehicle = $user->vehicle_type ? ucfirst($user->vehicle_type) : 'Motorcycle';
        $acceptMsg = "Your order {$order->order_number} has been accepted by {$user->first_name} {$user->last_name} ({$vehicle}). Contact: {$user->phone}. Delivery in progress.";

        Notification::create([
            'user_id' => $order->buyer_id,
            'title'   => 'Rider On the Way!',
            'message' => $acceptMsg,
            'type'    => 'success',
            'link'    => '/marketplace/orders/' . $order->id,
        ]);
        MobileNotification::send($order->buyer_id, 'Rider On the Way! 🛵', $acceptMsg, 'order', [
            'order_id' => $order->id, 'order_number' => $order->order_number,
        ], '🛵');

        AuditLog::record('order.rider_accepted', 'Order', $order->id, ['rider_id' => $user->id]);

        return response()->json(['message' => 'Order accepted.', 'order' => $order->fresh()]);
    }

    public function decline(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        if ($order->rider_id !== $user->id) return response()->json(['error' => 'Not assigned to you.'], 403);
        if ($order->rider_status !== 'assigned') {
            return response()->json(['error' => 'Order is not in assigned state.'], 422);
        }

        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        $order->update([
            'rider_status'     => 'declined',
            'rejection_reason' => $data['reason'] ?? 'Declined by rider',
            'rider_id'         => null,
        ]);

        $user->update(['rider_status' => 'available']);

        $adminIds = User::whereIn('role', ['admin', 'ceo'])->pluck('id');
        foreach ($adminIds as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'title'   => 'Rider Declined Order',
                'message' => "Rider declined order {$order->order_number}. Please reassign.",
                'type'    => 'warning',
                'link'    => '/admin/orders/' . $order->id,
            ]);
        }

        AuditLog::record('order.rider_declined', 'Order', $order->id, [
            'rider_id' => $user->id,
            'reason'   => $data['reason'] ?? null,
        ]);

        return response()->json(['message' => 'Order returned to queue.']);
    }

    public function markInTransit(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        if ($order->rider_id !== $user->id) return response()->json(['error' => 'Not assigned to you.'], 403);
        if ($order->rider_status !== 'accepted') {
            return response()->json(['error' => 'Order must be accepted first.'], 422);
        }

        $order->update([
            'rider_status'  => 'in_transit',
            'in_transit_at' => now(),
            'status'        => 'shipped',
        ]);

        Notification::create([
            'user_id' => $order->buyer_id,
            'title'   => 'Out for Delivery',
            'message' => "Your order {$order->order_number} is out for delivery. Expect arrival soon!",
            'type'    => 'info',
            'link'    => '/marketplace/orders/' . $order->id,
        ]);

        AuditLog::record('order.in_transit', 'Order', $order->id, ['rider_id' => $user->id]);

        return response()->json(['message' => 'Order marked in transit.', 'order' => $order->fresh()]);
    }

    public function markDelivered(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'rider') return response()->json(['error' => 'Forbidden'], 403);
        if ($order->rider_id !== $user->id) return response()->json(['error' => 'Not assigned to you.'], 403);
        if ($order->rider_status !== 'in_transit') {
            return response()->json(['error' => 'Order must be in transit before marking delivered.'], 422);
        }

        $data = $request->validate(['proof' => 'nullable|mimes:jpeg,jpg,png,gif,webp|max:5120']);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proof_of_delivery', 'public');
        }

        $order->update([
            'rider_status'      => 'awaiting_confirmation',
            'status'            => 'delivered',
            'delivered_at'      => now(),
            'proof_of_delivery' => $proofPath,
        ]);

        $deliveredMsg = "Your order {$order->order_number} has arrived! Please confirm delivery or report an issue.";
        Notification::create([
            'user_id' => $order->buyer_id,
            'title'   => 'Order Delivered — Please Confirm',
            'message' => $deliveredMsg,
            'type'    => 'success',
            'link'    => '/marketplace/orders/' . $order->id,
        ]);
        MobileNotification::send($order->buyer_id, 'Order Delivered! ✅', $deliveredMsg, 'order', [
            'order_id' => $order->id, 'order_number' => $order->order_number,
        ], '📦');

        if ($order->dealer_id) {
            Notification::create([
                'user_id' => $order->dealer_id,
                'title'   => 'Order Delivered',
                'message' => "Order {$order->order_number} has been successfully delivered.",
                'type'    => 'success',
                'link'    => '/marketplace/sell/orders',
            ]);
        }

        AuditLog::record('order.delivered_by_rider', 'Order', $order->id, ['rider_id' => $user->id]);

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
            ->whereIn('rider_status', ['assigned', 'accepted', 'in_transit'])
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