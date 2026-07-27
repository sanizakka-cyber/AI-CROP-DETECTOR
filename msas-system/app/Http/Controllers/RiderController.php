<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MobileNotification;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Http\Request;

class RiderController extends Controller
{
    public function dashboard()
    {
        $rider = auth()->user();

        $activeOrders = Order::where('rider_id', $rider->id)
            ->whereIn('rider_status', ['assigned', 'accepted', 'in_transit'])
            ->with(['buyer:id,first_name,last_name,phone', 'dealer:id,first_name,last_name'])
            ->latest()
            ->get();

        $stats = [
            'assigned'  => Order::where('rider_id', $rider->id)->where('rider_status', 'assigned')->count(),
            'in_transit'=> Order::where('rider_id', $rider->id)->where('rider_status', 'in_transit')->count(),
            'completed' => Order::where('rider_id', $rider->id)->where('rider_status', 'completed')->count(),
            'declined'  => Order::where('rider_id', $rider->id)->where('rider_status', 'declined')->count(),
            'earnings'  => Order::where('rider_id', $rider->id)->where('rider_status', 'completed')->sum('total') * 0.05,
        ];

        return view('rider.dashboard', compact('activeOrders', 'stats', 'rider'));
    }

    public function orders(Request $request)
    {
        $rider = auth()->user();

        $query = Order::where('rider_id', $rider->id)
            ->with(['buyer:id,first_name,last_name,phone', 'dealer:id,first_name,last_name,phone'])
            ->latest();

        if ($status = $request->status) {
            $query->where('rider_status', $status);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('rider.orders', compact('orders', 'rider'));
    }

    public function showOrder(Order $order)
    {
        abort_if($order->rider_id !== auth()->id(), 403);
        $order->load(['buyer', 'dealer', 'items']);
        return view('rider.order-detail', compact('order'));
    }

    public function accept(Order $order)
    {
        abort_if($order->rider_id !== auth()->id(), 403);
        abort_if($order->rider_status !== 'assigned', 422, 'Order is not in assigned state.');

        $order->update([
            'rider_status'      => 'accepted',
            'rider_accepted_at' => now(),
            'status'            => 'processing',
        ]);

        $rider = auth()->user();
        $vehicle = $rider->vehicle_type ? ucfirst($rider->vehicle_type) : 'Motorcycle';

        $acceptMsg = "Your order {$order->order_number} has been accepted by {$rider->first_name} {$rider->last_name} ({$vehicle}). Contact: {$rider->phone}. Delivery in progress.";

        Notification::create([
            'user_id' => $order->buyer_id,
            'title'   => 'Rider On the Way!',
            'message' => $acceptMsg,
            'type'    => 'success',
            'link'    => '/marketplace/orders/' . $order->id,
        ]);

        MobileNotification::send($order->buyer_id, 'Rider On the Way! 🛵', $acceptMsg, 'order', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
        ], '🛵');

        AuditLog::record('order.rider_accepted', 'Order', $order->id, ['rider_id' => auth()->id()]);

        return back()->with('success', 'Order accepted. You can now start delivery.');
    }

    public function decline(Request $request, Order $order)
    {
        abort_if($order->rider_id !== auth()->id(), 403);
        abort_if($order->rider_status !== 'assigned', 422, 'Order is not in assigned state.');

        $request->validate(['reason' => 'nullable|string|max:500']);

        $order->update([
            'rider_status'     => 'declined',
            'rejection_reason' => $request->reason ?? 'Declined by rider',
            'rider_id'         => null,
        ]);

        // Free rider
        auth()->user()->update(['rider_status' => 'available']);

        // Notify admin
        $adminUsers = \App\Models\User::whereIn('role', ['admin', 'ceo'])->pluck('id');
        foreach ($adminUsers as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'title'   => 'Rider Declined Order',
                'message' => "Rider declined order {$order->order_number}. Please reassign.",
                'type'    => 'warning',
                'link'    => '/admin/orders/' . $order->id,
            ]);
        }

        AuditLog::record('order.rider_declined', 'Order', $order->id, [
            'rider_id' => auth()->id(),
            'reason'   => $request->reason,
        ]);

        return redirect()->route('rider.dashboard')->with('info', 'Order declined. Admin has been notified to reassign.');
    }

    public function markInTransit(Order $order)
    {
        abort_if($order->rider_id !== auth()->id(), 403);
        abort_if($order->rider_status !== 'accepted', 422);

        $order->update([
            'rider_status' => 'in_transit',
            'in_transit_at'=> now(),
            'status'       => 'shipped',
        ]);

        Notification::create([
            'user_id' => $order->buyer_id,
            'title'   => 'Out for Delivery',
            'message' => "Your order {$order->order_number} is out for delivery. Expect arrival soon!",
            'type'    => 'info',
            'link'    => '/marketplace/orders/' . $order->id,
        ]);

        AuditLog::record('order.in_transit', 'Order', $order->id, ['rider_id' => auth()->id()]);

        return back()->with('success', 'Order marked as in transit.');
    }

    public function markDelivered(Order $order)
    {
        abort_if($order->rider_id !== auth()->id(), 403);
        abort_if($order->rider_status !== 'in_transit', 422);

        $order->update([
            'rider_status' => 'awaiting_confirmation',
            'status'       => 'delivered',
            'delivered_at' => now(),
        ]);

        // Notify buyer to confirm
        $deliveredMsg = "Your order {$order->order_number} has arrived! Please confirm delivery or report an issue.";

        Notification::create([
            'user_id' => $order->buyer_id,
            'title'   => 'Order Delivered — Please Confirm',
            'message' => $deliveredMsg,
            'type'    => 'success',
            'link'    => '/marketplace/orders/' . $order->id,
        ]);

        MobileNotification::send($order->buyer_id, 'Order Delivered! ✅', $deliveredMsg, 'order', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
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

        AuditLog::record('order.delivered_by_rider', 'Order', $order->id, ['rider_id' => auth()->id()]);

        return back()->with('success', 'Delivery confirmed. Well done!');
    }

    public function updateStatus(Request $request)
    {
        $rider = auth()->user();
        $request->validate(['rider_status' => 'required|in:available,offline']);
        $rider->update(['rider_status' => $request->rider_status]);
        return back()->with('success', 'Your status has been updated.');
    }
}
