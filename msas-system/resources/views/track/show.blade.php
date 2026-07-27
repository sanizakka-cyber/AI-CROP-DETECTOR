<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Order {{ $order->order_number }} — MSAS FarmAI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700,800|poppins:700,800&display=swap" rel="stylesheet"/>
    <style>
        *,*::before,*::after{box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;margin:0;min-height:100vh;}
        h1,h2,h3{font-family:'Poppins',sans-serif;}
        .container{max-width:640px;margin:0 auto;padding:24px 16px;}
        .logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;}
        .logo-mark{width:40px;height:40px;background:linear-gradient(135deg,#0F6B3E,#1FA84A);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;}
        .logo-text{font-family:'Poppins',sans-serif;font-weight:800;font-size:18px;color:#0B2447;}
        .card{background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.05);}
        .order-num{font-size:24px;font-weight:800;color:#0f172a;}
        .order-date{font-size:12px;color:#94a3b8;margin-top:2px;}
        .badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;}
        .badge-amber{background:#fef3c7;color:#92400e;}
        .badge-green{background:#dcfce7;color:#15803d;}
        .badge-blue{background:#dbeafe;color:#1d4ed8;}
        .badge-red{background:#fee2e2;color:#b91c1c;}
        .badge-slate{background:#f1f5f9;color:#475569;}
        .timeline{display:flex;justify-content:space-between;gap:4px;}
        .timeline-step{display:flex;flex-direction:column;align-items:center;flex:1;position:relative;}
        .timeline-step:not(:last-child)::after{content:'';position:absolute;top:16px;left:50%;width:100%;height:2px;background:#e2e8f0;z-index:0;}
        .timeline-step.done:not(:last-child)::after{background:#0F6B3E;}
        .timeline-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;position:relative;z-index:1;}
        .timeline-dot.done{background:#0F6B3E;color:#fff;}
        .timeline-dot.pending{background:#f1f5f9;color:#94a3b8;}
        .timeline-label{font-size:10px;font-weight:600;margin-top:6px;text-align:center;color:#94a3b8;}
        .timeline-label.done{color:#0F6B3E;}
        .section-title{font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;}
        .item-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f8fafc;}
        .item-row:last-child{border:none;}
        .total-row{display:flex;justify-content:space-between;padding:12px 0 0;font-size:16px;font-weight:800;color:#0F6B3E;}
        .rider-card{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
        .rider-name{font-weight:800;color:#0f172a;}
        .rider-sub{font-size:12px;color:#64748b;margin-top:2px;}
        .footer{text-align:center;font-size:12px;color:#94a3b8;margin-top:24px;padding-bottom:24px;}
        .footer a{color:#0F6B3E;text-decoration:none;font-weight:600;}
    </style>
</head>
<body>
<div class="container">

    <div class="logo">
        <div class="logo-mark">🌿</div>
        <span class="logo-text">MSAS FarmAI</span>
    </div>

    <div class="card">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <div>
                <div class="order-num">{{ $order->order_number }}</div>
                <div class="order-date">Placed {{ $order->created_at->format('M d, Y \a\t g:i A') }}</div>
            </div>
            @php
                $statusBadge = match($order->rider_status) {
                    'awaiting_confirmation' => ['class'=>'badge-amber','label'=>'Awaiting Your Confirmation'],
                    'completed'             => ['class'=>'badge-green','label'=>'Delivered & Confirmed'],
                    'in_transit'            => ['class'=>'badge-blue','label'=>'Out for Delivery'],
                    'accepted'              => ['class'=>'badge-blue','label'=>'Rider Accepted'],
                    default                 => match($order->status) {
                        'cancelled' => ['class'=>'badge-red','label'=>'Cancelled'],
                        'delivered' => ['class'=>'badge-amber','label'=>'Delivered'],
                        'shipped'   => ['class'=>'badge-blue','label'=>'Shipped'],
                        default     => ['class'=>'badge-slate','label'=>ucfirst($order->status)],
                    }
                };
            @endphp
            <span class="badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
        </div>
    </div>

    {{-- Timeline --}}
    @php $timeline = $order->orderTimeline(); @endphp
    @if($order->status !== 'cancelled')
    <div class="card">
        <div class="section-title">Delivery Progress</div>
        <div class="timeline">
            @foreach($timeline as $i => $step)
            <div class="timeline-step {{ $step['done'] ? 'done' : '' }}">
                <div class="timeline-dot {{ $step['done'] ? 'done' : 'pending' }}">
                    {{ $step['done'] ? '✓' : ($i+1) }}
                </div>
                <div class="timeline-label {{ $step['done'] ? 'done' : '' }}">{{ $step['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="card" style="background:#fee2e2;border-color:#fca5a5;">
        <p style="color:#b91c1c;font-weight:600;margin:0;">This order has been cancelled.</p>
    </div>
    @endif

    {{-- Rider --}}
    @if($order->rider && in_array($order->rider_status, ['accepted','in_transit','awaiting_confirmation','completed']))
    <div class="card">
        <div class="section-title">Your Rider</div>
        <div class="rider-card">
            <div>
                <div class="rider-name">{{ $order->rider->first_name }} {{ $order->rider->last_name }}</div>
                <div class="rider-sub">{{ ucfirst($order->rider->vehicle_type ?? 'Motorcycle') }}</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Proof of Delivery --}}
    @if($order->proof_of_delivery)
    <div class="card">
        <div class="section-title">Proof of Delivery</div>
        <img src="{{ asset('storage/' . $order->proof_of_delivery) }}"
             alt="Proof of delivery"
             style="width:100%;border-radius:10px;object-fit:cover;max-height:300px;">
    </div>
    @endif

    {{-- Items --}}
    <div class="card">
        <div class="section-title">Order Items</div>
        @foreach($order->items as $item)
        <div class="item-row">
            <div>
                <div style="font-size:13px;font-weight:600;color:#0f172a;">{{ $item->product_name }}</div>
                <div style="font-size:11px;color:#94a3b8;">{{ $item->quantity }} × ₦{{ number_format($item->unit_price, 2) }}</div>
            </div>
            <div style="font-size:13px;font-weight:700;color:#0f172a;">₦{{ number_format($item->total, 2) }}</div>
        </div>
        @endforeach
        <div class="total-row">
            <span>Total</span>
            <span>₦{{ number_format($order->total, 2) }}</span>
        </div>
    </div>

    <div class="footer">
        Order tracking by <a href="https://ai.msasagro.com">MSAS FarmAI</a><br>
        For help, contact support at <a href="mailto:support@msasagro.com">support@msasagro.com</a>
    </div>

</div>
</body>
</html>
