<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConsultationApiController extends Controller
{
    private const CHANNEL_FEES = [
        'in_app'     => 1500,
        'whatsapp'   => 2500,
        'phone_call' => 3500,
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Consultation::where('farmer_id', $user->id)
            ->with('expert:id,first_name,last_name,role')
            ->latest();

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('case_type')) $query->where('case_type', $request->case_type);

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, Consultation $consultation): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $consultation->farmer_id === $user->id ||
            $consultation->expert_id === $user->id ||
            in_array($user->role, ['admin','ceo']),
            403
        );
        $consultation->load(['farmer:id,first_name,last_name,phone', 'expert:id,first_name,last_name,role']);
        return response()->json($consultation);
    }

    public function storeVet(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'farmer') {
            return response()->json(['error' => 'Only farmers can request vet consultations.'], 403);
        }
        $data = $request->validate([
            'animal_type' => 'required|string|max:100',
            'symptoms'    => 'required|string|max:2000',
            'priority'    => 'required|in:low,medium,high,critical',
            'channel'     => 'required|in:in_app,whatsapp,phone_call',
        ]);
        $fee = self::CHANNEL_FEES[$data['channel']];
        $ref = 'MSAS-CONSULT-' . strtoupper(Str::random(10));
        $consultation = Consultation::create([
            'farmer_id'         => $user->id,
            'case_type'         => 'livestock',
            'animal_type'       => $data['animal_type'],
            'symptoms'          => $data['symptoms'],
            'priority'          => $data['priority'],
            'channel'           => $data['channel'],
            'fee'               => $fee,
            'status'            => 'awaiting_payment',
            'payment_status'    => 'unpaid',
            'payment_reference' => $ref,
        ]);
        return response()->json(['consultation' => $consultation, 'payment_ref' => $ref, 'fee' => $fee,
            'message' => 'Consultation created. Proceed to payment to activate.'], 201);
    }

    public function storeAgro(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'farmer') {
            return response()->json(['error' => 'Only farmers can request agronomist consultations.'], 403);
        }
        $data = $request->validate([
            'crop_type' => 'required|string|max:100',
            'symptoms'  => 'required|string|max:2000',
            'priority'  => 'required|in:low,medium,high,critical',
            'channel'   => 'required|in:in_app,whatsapp,phone_call',
        ]);
        $fee = self::CHANNEL_FEES[$data['channel']];
        $ref = 'MSAS-CONSULT-' . strtoupper(Str::random(10));
        $consultation = Consultation::create([
            'farmer_id'         => $user->id,
            'case_type'         => 'crop',
            'crop_type'         => $data['crop_type'],
            'symptoms'          => $data['symptoms'],
            'priority'          => $data['priority'],
            'channel'           => $data['channel'],
            'fee'               => $fee,
            'status'            => 'awaiting_payment',
            'payment_status'    => 'unpaid',
            'payment_reference' => $ref,
        ]);
        return response()->json(['consultation' => $consultation, 'payment_ref' => $ref, 'fee' => $fee,
            'message' => 'Consultation created. Proceed to payment to activate.'], 201);
    }

    public function vetQueue(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! in_array($user->role, ['vet', 'agronomist'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if (! $user->is_verified) {
            return response()->json(['error' => 'Account not yet verified.'], 403);
        }
        $query = Consultation::where('status', 'open')
            ->where(fn($q) => $q->whereNull('expert_id')->orWhere('expert_id', $user->id));
        if ($user->role === 'agronomist') $query->where('case_type', 'crop');
        elseif ($user->role === 'vet')     $query->where('case_type', 'livestock');
        $consultations = $query->with(['farmer:id,first_name,last_name,phone'])->latest()->get();
        return response()->json(['queue' => $consultations]);
    }

    public function vetRespond(Request $request, Consultation $consultation): JsonResponse
    {
        $user = $request->user();
        if (! in_array($user->role, ['vet', 'agronomist'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if (! $user->is_verified) {
            return response()->json(['error' => 'Account not yet verified.'], 403);
        }
        if ($consultation->expert_id && $consultation->expert_id !== $user->id) {
            return response()->json(['error' => 'Assigned to another expert.'], 403);
        }
        if ($consultation->status === 'resolved') {
            return response()->json(['error' => 'Already resolved.'], 422);
        }
        $data = $request->validate(['expert_response' => 'required|string|min:10']);
        $consultation->update([
            'expert_id'       => $user->id,
            'expert_response' => $data['expert_response'],
            'status'          => 'resolved',
            'completed_at'    => now(),
        ]);
        Notification::create([
            'user_id' => $consultation->farmer_id,
            'title'   => 'Expert Response Received',
            'message' => 'Your consultation has been answered by an expert.',
            'type'    => 'success',
            'link'    => '/farmer/vet-consult/' . $consultation->id,
        ]);
        return response()->json(['message' => 'Consultation resolved.', 'consultation' => $consultation]);
    }

    public function vetStartVideo(Request $request, Consultation $consultation): JsonResponse
    {
        $user = $request->user();
        if (! in_array($user->role, ['vet', 'agronomist'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if ($consultation->expert_id && $consultation->expert_id !== $user->id) {
            return response()->json(['error' => 'Assigned to another expert.'], 403);
        }
        if (! $consultation->video_room_id) {
            $consultation->update([
                'video_room_id' => Str::uuid()->toString(),
                'expert_id'     => $user->id,
            ]);
        }
        return response()->json([
            'video_room_id' => $consultation->video_room_id,
            'jitsi_url'     => 'https://meet.jit.si/msas-' . $consultation->video_room_id,
        ]);
    }
}