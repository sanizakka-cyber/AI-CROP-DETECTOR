<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RiderManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'rider');

        if ($status = $request->status) {
            $query->where('rider_status', $status);
        }
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name',  'ilike', "%{$search}%")
                  ->orWhere('phone',      'ilike', "%{$search}%");
            });
        }

        $riders = $query->orderBy('first_name')->paginate(20)->withQueryString();

        $stats = [
            'total'     => User::where('role', 'rider')->count(),
            'available' => User::where('role', 'rider')->where('rider_status', 'available')->count(),
            'busy'      => User::where('role', 'rider')->where('rider_status', 'busy')->count(),
            'offline'   => User::where('role', 'rider')->where('rider_status', 'offline')->orWhereNull('rider_status')->where('role','rider')->count(),
            'suspended' => User::where('role', 'rider')->where('is_active', false)->count(),
        ];

        return view('admin.riders.index', compact('riders', 'stats'));
    }

    public function show(User $user)
    {
        abort_if($user->role !== 'rider', 404);

        $activeOrders    = Order::where('rider_id', $user->id)->whereIn('rider_status', ['assigned','accepted','in_transit'])->count();
        $deliveredOrders = Order::where('rider_id', $user->id)->where('rider_status', 'completed')->count();
        $declinedOrders  = Order::where('rider_id', $user->id)->where('rider_status', 'declined')->count();
        $totalAssigned   = Order::where('rider_id', $user->id)->count();
        $successRate     = $totalAssigned > 0 ? round(($deliveredOrders / $totalAssigned) * 100) : 0;
        $recentOrders    = Order::where('rider_id', $user->id)->with('buyer:id,first_name,last_name')->latest('rider_assigned_at')->take(20)->get();
        $rider           = $user;

        return view('admin.riders.show', compact('rider', 'activeOrders', 'deliveredOrders', 'declinedOrders', 'totalAssigned', 'successRate', 'recentOrders'));
    }

    public function create()
    {
        return view('admin.riders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'phone'        => 'required|string|unique:users,phone',
            'email'        => 'nullable|email|unique:users,email',
            'vehicle_type' => 'nullable|string|max:50',
        ]);

        $tempPassword = Str::random(10);

        $rider = User::create([
            'first_name'           => $request->first_name,
            'last_name'            => $request->last_name,
            'phone'                => $request->phone,
            'email'                => $request->email,
            'password'             => Hash::make($tempPassword),
            'role'                 => 'rider',
            'is_active'            => true,
            'is_verified'          => true,
            'rider_status'         => 'available',
            'vehicle_type'         => $request->vehicle_type,
            'force_password_reset' => true,
        ]);

        AuditLog::record('rider.created', 'User', $rider->id, ['created_by' => auth()->id()]);

        return redirect()->route('admin.riders.show', $rider)
            ->with('success', "Rider {$rider->first_name} {$rider->last_name} created. Temp password: {$tempPassword} — share via secure channel.");
    }

    public function toggleStatus(User $user)
    {
        abort_if($user->role !== 'rider', 403);

        $user->update(['is_active' => !$user->is_active]);

        if (!$user->is_active) {
            $user->update(['rider_status' => 'offline']);
        }

        AuditLog::record('rider.' . ($user->is_active ? 'activated' : 'suspended'), 'User', $user->id, ['by' => auth()->id()]);

        return back()->with('success', "Rider " . ($user->is_active ? 'activated' : 'suspended') . ".");
    }

    public function updateRiderStatus(Request $request, User $user)
    {
        abort_if($user->role !== 'rider', 403);
        $request->validate(['rider_status' => 'required|in:available,busy,offline']);
        $user->update(['rider_status' => $request->rider_status]);
        return back()->with('success', 'Rider status updated.');
    }
}
