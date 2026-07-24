<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminPayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::whereNotNull('payout_status')
            ->with(['dealer:id,first_name,last_name,email,phone', 'buyer:id,first_name,last_name'])
            ->orderByDesc('payout_requested_at');

        if ($status = $request->status) {
            $query->where('payout_status', $status);
        }

        $payouts = $query->paginate(25)->withQueryString();

        $stats = [
            'requested' => Order::where('payout_status', 'requested')->count(),
            'approved'  => Order::where('payout_status', 'approved')->count(),
            'paid'      => Order::where('payout_status', 'paid')->count(),
            'rejected'  => Order::where('payout_status', 'rejected')->count(),
            'total_due' => Order::whereIn('payout_status', ['requested', 'approved'])->sum('payout_amount'),
        ];

        return view('admin.payouts.index', compact('payouts', 'stats'));
    }

    public function approve(Order $order)
    {
        abort_if(!in_array($order->payout_status, ['requested']), 422, 'Invalid payout state.');

        $order->update(['payout_status' => 'approved']);

        AuditLog::record('payout.approved', 'Order', $order->id, [
            'payout_amount' => $order->payout_amount,
            'dealer_id'     => $order->dealer_id,
        ]);

        return back()->with('success', "Payout of ₦" . number_format($order->payout_amount, 2) . " approved for Order {$order->order_number}.");
    }

    public function reject(Request $request, Order $order)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        abort_if(!in_array($order->payout_status, ['requested', 'approved']), 422, 'Invalid payout state.');

        $order->update([
            'payout_status' => 'rejected',
            'payout_notes'  => $request->reason ?? 'Rejected by admin',
        ]);

        AuditLog::record('payout.rejected', 'Order', $order->id, [
            'reason'    => $request->reason,
            'dealer_id' => $order->dealer_id,
        ]);

        return back()->with('success', "Payout for Order {$order->order_number} rejected.");
    }

    public function markPaid(Request $request, Order $order)
    {
        $request->validate(['payout_reference' => 'required|string|max:100']);
        abort_if($order->payout_status !== 'approved', 422, 'Order must be approved before marking paid.');

        $order->update([
            'payout_status'    => 'paid',
            'payout_paid_at'   => now(),
            'payout_reference' => $request->payout_reference,
        ]);

        AuditLog::record('payout.paid', 'Order', $order->id, [
            'payout_amount'    => $order->payout_amount,
            'payout_reference' => $request->payout_reference,
            'dealer_id'        => $order->dealer_id,
        ]);

        return back()->with('success', "Payout of ₦" . number_format($order->payout_amount, 2) . " marked as paid. Ref: {$request->payout_reference}");
    }
}
