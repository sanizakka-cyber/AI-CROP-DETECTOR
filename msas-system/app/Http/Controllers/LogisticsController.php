<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRequest;
use App\Models\LogisticsDriver;
use App\Models\LogisticsVehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogisticsController extends Controller
{
    private function provider()
    {
        return auth()->user();
    }

    // ── Vehicles ─────────────────────────────────────────────────────────────

    public function vehicles(): View
    {
        $vehicles = LogisticsVehicle::where('user_id', $this->provider()->id)
            ->withCount(['deliveries as active_deliveries' => fn($q) => $q->whereNotIn('status', ['delivered', 'failed'])])
            ->orderByDesc('created_at')
            ->get();

        return view('logistics.vehicles', compact('vehicles'));
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reg_number'  => 'required|string|max:20|unique:logistics_vehicles,reg_number',
            'make'        => 'required|string|max:100',
            'model'       => 'nullable|string|max:100',
            'year'        => 'nullable|digits:4|integer|min:1990|max:' . (date('Y') + 1),
            'vehicle_type'=> 'required|in:truck,van,motorcycle,pickup,refrigerated',
            'capacity_kg' => 'nullable|numeric|min:0',
            'status'      => 'required|in:active,maintenance,retired',
            'notes'       => 'nullable|string|max:500',
        ]);

        LogisticsVehicle::create(['user_id' => $this->provider()->id] + $data);

        return back()->with('success', 'Vehicle registered successfully.');
    }

    public function updateVehicle(Request $request, LogisticsVehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== $this->provider()->id, 403);

        $data = $request->validate([
            'reg_number'  => 'required|string|max:20|unique:logistics_vehicles,reg_number,' . $vehicle->id,
            'make'        => 'required|string|max:100',
            'model'       => 'nullable|string|max:100',
            'year'        => 'nullable|digits:4|integer|min:1990|max:' . (date('Y') + 1),
            'vehicle_type'=> 'required|in:truck,van,motorcycle,pickup,refrigerated',
            'capacity_kg' => 'nullable|numeric|min:0',
            'status'      => 'required|in:active,maintenance,retired',
            'notes'       => 'nullable|string|max:500',
        ]);

        $vehicle->update($data);

        return back()->with('success', 'Vehicle updated.');
    }

    public function deleteVehicle(LogisticsVehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== $this->provider()->id, 403);
        $vehicle->delete();
        return back()->with('success', 'Vehicle removed.');
    }

    // ── Drivers ─────────────────────────────────────────────────────────────

    public function drivers(): View
    {
        $drivers = LogisticsDriver::where('user_id', $this->provider()->id)
            ->withCount(['deliveries as active_deliveries' => fn($q) => $q->whereNotIn('status', ['delivered', 'failed'])])
            ->orderByDesc('created_at')
            ->get();

        return view('logistics.drivers', compact('drivers'));
    }

    public function storeDriver(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'license_number' => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:20',
            'status'         => 'required|in:available,on_trip,off_duty',
            'notes'          => 'nullable|string|max:500',
        ]);

        LogisticsDriver::create(['user_id' => $this->provider()->id] + $data);

        return back()->with('success', 'Driver added successfully.');
    }

    public function updateDriver(Request $request, LogisticsDriver $driver): RedirectResponse
    {
        abort_if($driver->user_id !== $this->provider()->id, 403);

        $data = $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'license_number' => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:20',
            'status'         => 'required|in:available,on_trip,off_duty',
            'notes'          => 'nullable|string|max:500',
        ]);

        $driver->update($data);

        return back()->with('success', 'Driver updated.');
    }

    public function deleteDriver(LogisticsDriver $driver): RedirectResponse
    {
        abort_if($driver->user_id !== $this->provider()->id, 403);
        $driver->delete();
        return back()->with('success', 'Driver removed.');
    }

    // ── Deliveries ───────────────────────────────────────────────────────────

    public function deliveries(Request $request): View
    {
        $status    = $request->get('status', 'all');
        $query     = DeliveryRequest::where('logistics_provider_id', $this->provider()->id)
            ->with(['vehicle', 'driver', 'requester'])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $deliveries = $query->paginate(20);

        $vehicles = LogisticsVehicle::where('user_id', $this->provider()->id)
            ->where('status', 'active')->get();

        $drivers = LogisticsDriver::where('user_id', $this->provider()->id)
            ->where('status', 'available')->get();

        $counts = DeliveryRequest::where('logistics_provider_id', $this->provider()->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('logistics.deliveries', compact('deliveries', 'status', 'vehicles', 'drivers', 'counts'));
    }

    public function storeDelivery(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'delivery_address'   => 'required|string|max:500',
            'pickup_address'     => 'nullable|string|max:500',
            'contact_name'       => 'nullable|string|max:100',
            'contact_phone'      => 'nullable|string|max:20',
            'cargo_description'  => 'nullable|string|max:500',
            'cargo_weight_kg'    => 'nullable|numeric|min:0',
            'delivery_fee'       => 'nullable|numeric|min:0',
            'vehicle_id'         => 'nullable|exists:logistics_vehicles,id',
            'driver_id'          => 'nullable|exists:logistics_drivers,id',
            'notes'              => 'nullable|string|max:500',
        ]);

        $data['ref_number']             = DeliveryRequest::generateRef();
        $data['logistics_provider_id']  = $this->provider()->id;
        $data['status']                 = ($data['vehicle_id'] && $data['driver_id']) ? 'assigned' : 'pending';
        $data['assigned_at']            = ($data['status'] === 'assigned') ? now() : null;

        DeliveryRequest::create($data);

        return back()->with('success', 'Delivery request created.');
    }

    public function updateDeliveryStatus(Request $request, DeliveryRequest $delivery): RedirectResponse
    {
        abort_if($delivery->logistics_provider_id !== $this->provider()->id, 403);

        $request->validate([
            'status'     => 'required|in:pending,assigned,picked_up,in_transit,delivered,failed',
            'vehicle_id' => 'nullable|exists:logistics_vehicles,id',
            'driver_id'  => 'nullable|exists:logistics_drivers,id',
            'notes'      => 'nullable|string|max:500',
        ]);

        $updates = ['status' => $request->status];

        if ($request->vehicle_id) $updates['vehicle_id'] = $request->vehicle_id;
        if ($request->driver_id)  $updates['driver_id']  = $request->driver_id;
        if ($request->notes)      $updates['notes']       = $request->notes;

        if ($request->status === 'assigned' && ! $delivery->assigned_at)  $updates['assigned_at']  = now();
        if ($request->status === 'picked_up' && ! $delivery->picked_up_at) $updates['picked_up_at'] = now();
        if ($request->status === 'delivered' && ! $delivery->delivered_at) $updates['delivered_at'] = now();

        $delivery->update($updates);

        return back()->with('success', 'Delivery status updated.');
    }
}
