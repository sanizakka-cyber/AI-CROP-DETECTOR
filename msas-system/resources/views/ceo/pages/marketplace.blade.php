<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    @include('ceo.partials.styles')

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    <div class="space-y-4">
        <div class="bi-section-eyebrow">Marketplace Intelligence</div>

        <div class="bi-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
                <div class="bi-card-title" style="margin-bottom:0;">
                    <span class="bi-dot" style="background:#4f46e5;"></span>Order Pipeline
                </div>
                <a href="{{ route('ceo.orders') }}" style="font-size:11px;font-weight:700;color:#4f46e5;text-decoration:none;">Manage Orders →</a>
            </div>

            {{-- Order status funnel --}}
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-5">
            @php
            $orderPills = [
                ['Total',      $orderStats['total'],     '#0f172a','#f8fafc'],
                ['Pending',    $orderStats['pending'],   '#d97706','#fffbeb'],
                ['Processing', $orderStats['processing'],'#2563eb','#eff6ff'],
                ['Shipped',    $orderStats['shipped'],   '#7c3aed','#f5f3ff'],
                ['Delivered',  $orderStats['delivered'], '#16a34a','#f0fdf4'],
                ['Cancelled',  $orderStats['cancelled'], '#dc2626','#fef2f2'],
            ];
            @endphp
            @foreach($orderPills as [$ol,$ov,$oc,$obg])
            <div style="background:{{ $obg }};border-radius:10px;padding:12px 8px;text-align:center;border-top:3px solid {{ $oc }};">
                <div style="font-size:22px;font-weight:900;color:{{ $oc }};">{{ $ov }}</div>
                <div style="font-size:9px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:2px;">{{ $ol }}</div>
            </div>
            @endforeach
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- GMV --}}
                <div>
                    <div class="bi-eyebrow" style="color:#4f46e5;margin-bottom:8px;">Gross Merchandise Value</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div style="background:#f0fdf4;border-radius:10px;padding:14px;text-align:center;">
                            <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">GMV This Month</div>
                            <div style="font-size:19px;font-weight:900;color:#16a34a;">₦{{ number_format($orderStats['gmv_month']) }}</div>
                        </div>
                        <div style="background:#f8fafc;border-radius:10px;padding:14px;text-align:center;">
                            <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Total GMV</div>
                            <div style="font-size:19px;font-weight:900;color:#0f172a;">₦{{ number_format($orderStats['gmv']) }}</div>
                        </div>
                    </div>
                </div>
                {{-- Top products --}}
                <div>
                    <div class="bi-eyebrow" style="color:#4f46e5;margin-bottom:8px;">Top Products by Orders</div>
                    @forelse($topProducts as $prod)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;':'' }}">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:18px;height:18px;border-radius:50%;background:#eff6ff;font-size:9px;font-weight:800;color:#4f46e5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $loop->iteration }}</span>
                            <span style="font-size:12px;font-weight:600;color:#374151;">{{ Str::limit($prod->name, 28) }}</span>
                        </div>
                        <span style="font-size:11px;font-weight:800;color:#4f46e5;background:#eff6ff;padding:2px 8px;border-radius:99px;">{{ $prod->order_count }}</span>
                    </div>
                    @empty
                    <div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">No orders yet</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    </div>
</x-app-layout>
