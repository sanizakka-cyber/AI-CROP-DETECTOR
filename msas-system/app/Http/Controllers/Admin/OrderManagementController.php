<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['buyer:id,first_name,last_name,phone', 'dealer:id,first_name,last_name', 'rider:id,first_name,last_name,phone'])
            ->latest();

        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($riderStatus = $request->rider_status) {
            if ($riderStatus === 'unassigned') {
                $query->whereNull('rider_id');
            } else {
                $query->where('rider_status', $riderStatus);
            }
        }
        if ($search = $request->search) {
            $query->where('order_number', 'ilike', "%{$search}%");
        }

        $orders = $query->paginate(20)->withQueryString();

        $stats = [
            'total'           => Order::count(),
            'pending'         => Order::where('status', 'pending')->count(),
            'unassigned'      => Order::whereIn('status', ['confirmed','processing'])->whereNull('rider_id')->count(),
            'in_transit'      => Order::where('rider_status', 'in_transit')->count(),
            'delivered'       => Order::where('status', 'delivered')->count(),
            'cancelled'       => Order::where('status', 'cancelled')->count(),
            'today_revenue'   => Order::where('payment_status', 'paid')->whereDate('created_at', today())->sum('total'),
            'total_revenue'   => Order::where('payment_status', 'paid')->sum('total'),
        ];

        $riders = User::where('role', 'rider')->where('is_active', true)->orderBy('first_name')->get(['id','first_name','last_name','phone','rider_status']);

        return view('admin.orders.index', compact('orders', 'stats', 'riders'));
    }

    public function show(Order $order)
    {
        $order->load(['buyer', 'dealer', 'rider', 'assignedBy', 'items.product']);
        $riders = User::where('role', 'rider')->where('is_active', true)->orderBy('first_name')->get(['id','first_name','last_name','phone','rider_status','vehicle_type']);
        return view('admin.orders.show', compact('order', 'riders'));
    }

    public function assignRider(Request $request, Order $order)
    {
        $request->validate(['rider_id' => 'required|exists:users,id']);

        $rider = User::findOrFail($request->rider_id);

        $order->update([
            'rider_id'          => $rider->id,
            'assigned_by'       => auth()->id(),
            'rider_status'      => 'assigned',
            'rider_assigned_at' => now(),
            'status'            => 'processing',
        ]);

        // Mark rider as busy
        $rider->update(['rider_status' => 'busy']);

        // Notify rider
        Notification::create([
            'user_id' => $rider->id,
            'title'   => 'New Delivery Assignment',
            'message' => "Order {$order->order_number} has been assigned to you. Please accept or decline.",
            'type'    => 'info',
            'link'    => '/rider/orders/' . $order->id,
        ]);

        // Notify buyer
        Notification::create([
            'user_id' => $order->buyer_id,
            'title'   => 'Rider Assigned',
            'message' => "A rider has been assigned to deliver your order {$order->order_number}.",
            'type'    => 'success',
            'link'    => '/marketplace/orders/' . $order->id,
        ]);

        AuditLog::record('order.rider_assigned', 'Order', $order->id, [
            'rider_id'   => $rider->id,
            'rider_name' => $rider->first_name . ' ' . $rider->last_name,
            'assigned_by'=> auth()->id(),
        ]);

        return back()->with('success', "Order {$order->order_number} assigned to {$rider->first_name} {$rider->last_name}.");
    }

    public function reassignRider(Request $request, Order $order)
    {
        $request->validate(['rider_id' => 'required|exists:users,id', 'reason' => 'nullable|string|max:500']);

        $oldRider = $order->rider;
        $newRider = User::findOrFail($request->rider_id);

        // Free old rider
        if ($oldRider) {
            $oldRider->update(['rider_status' => 'available']);
        }

        $order->update([
            'rider_id'          => $newRider->id,
            'assigned_by'       => auth()->id(),
            'rider_status'      => 'assigned',
            'rider_assigned_at' => now(),
            'rider_accepted_at' => null,
            'admin_notes'       => $request->reason,
        ]);

        $newRider->update(['rider_status' => 'busy']);

        Notification::create([
            'user_id' => $newRider->id,
            'title'   => 'Delivery Assignment',
            'message' => "Order {$order->order_number} has been reassigned to you.",
            'type'    => 'info',
            'link'    => '/rider/orders/' . $order->id,
        ]);

        AuditLog::record('order.rider_reassigned', 'Order', $order->id, [
            'old_rider_id' => $oldRider?->id,
            'new_rider_id' => $newRider->id,
        ]);

        return back()->with('success', "Order reassigned to {$newRider->first_name} {$newRider->last_name}.");
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status'     => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,returned',
            'admin_notes'=> 'nullable|string|max:500',
        ]);

        $old = $order->status;
        $order->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes ?? $order->admin_notes,
            'delivered_at'=> $request->status === 'delivered' ? now() : $order->delivered_at,
            'completed_at'=> $request->status === 'delivered' ? now() : $order->completed_at,
            'returned_at' => $request->status === 'returned'  ? now() : $order->returned_at,
        ]);

        // Notify buyer of status change
        $messages = [
            'confirmed'  => "Your order {$order->order_number} has been confirmed.",
            'shipped'    => "Your order {$order->order_number} is on its way!",
            'delivered'  => "Your order {$order->order_number} has been delivered.",
            'cancelled'  => "Your order {$order->order_number} has been cancelled.",
            'returned'   => "Your order {$order->order_number} has been marked as returned.",
        ];

        if (isset($messages[$request->status])) {
            Notification::create([
                'user_id' => $order->buyer_id,
                'title'   => 'Order Update',
                'message' => $messages[$request->status],
                'type'    => in_array($request->status, ['cancelled','returned']) ? 'warning' : 'success',
                'link'    => '/marketplace/orders/' . $order->id,
            ]);
        }

        AuditLog::record('order.status_overridden', 'Order', $order->id, [
            'from' => $old, 'to' => $request->status, 'by' => auth()->id(),
        ]);

        return back()->with('success', "Order status updated to " . ucfirst($request->status) . ".");
    }

    public function addNote(Request $request, Order $order)
    {
        $request->validate(['admin_notes' => 'required|string|max:1000']);
        $order->update(['admin_notes' => $request->admin_notes]);
        return back()->with('success', 'Note saved.');
    }
}
