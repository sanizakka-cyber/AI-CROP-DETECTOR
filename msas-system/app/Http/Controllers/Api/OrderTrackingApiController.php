<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderTrackingApiController extends Controller
{
    public function track(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['rider:id,first_name,last_name,phone', 'buyer:id,first_name,last_name'])
            ->first();
        if (! $order) {
            return response()->json(['error' => 'Order not found. Please check the order number.'], 404);
        }
        $statusLabels = [
            'pending'    => 'Order Placed',
            'confirmed'  => 'Confirmed',
            'assigned'   => 'Rider Assigned',
            'in_transit' => 'Out for Delivery',
            'delivered'  => 'Delivered',
            'cancelled'  => 'Cancelled',
        ];
        $steps        = ['pending', 'confirmed', 'assigned', 'in_transit', 'delivered'];
        $currentIndex = array_search($order->status, $steps);
        $timeline     = array_values(array_map(fn($step, $idx) => [
            'status'    => $step,
            'label'     => $statusLabels[$step] ?? $step,
            'completed' => $currentIndex !== false && $idx <= $currentIndex,
            'current'   => $step === $order->status,
        ], $steps, array_keys($steps)));
        return response()->json([
            'order_number' => $order->order_number,
            'status'       => $order->status,
            'status_label' => $statusLabels[$order->status] ?? $order->status,
            'timeline'     => $timeline,
            'rider'        => $order->rider ? [
                'name'  => $order->rider->first_name . ' ' . $order->rider->last_name,
                'phone' => $order->rider->phone,
            ] : null,
            'delivered_at' => $order->delivered_at ?? null,
            'created_at'   => $order->created_at,
        ]);
    }
}