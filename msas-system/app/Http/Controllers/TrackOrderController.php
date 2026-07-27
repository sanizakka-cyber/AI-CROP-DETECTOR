<?php

namespace App\Http\Controllers;

use App\Models\Order;

class TrackOrderController extends Controller
{
    public function show(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with([
                'buyer:id,first_name,last_name',
                'rider:id,first_name,last_name,vehicle_type',
                'items:id,order_id,product_name,quantity,unit_price,total',
            ])
            ->first();

        if (!$order) {
            return view('track.not-found', ['orderNumber' => $orderNumber]);
        }

        return view('track.show', compact('order'));
    }
}
