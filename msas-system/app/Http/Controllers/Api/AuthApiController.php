<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account is suspended. Please contact support.'], 403);
        }

        $user->update(['last_seen' => now()]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {

        $parts     = explode(' ', trim($request->name), 2);
        $firstName = $parts[0];
        $lastName  = $parts[1] ?? $parts[0];

        $user = User::create([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $request->phone,
            'email'      => $request->email ?? null,
            'password'   => Hash::make($request->password),
            'role'       => $request->role ?? 'farmer',
            'language'   => $request->language ?? 'en',
            'state'      => $request->state ?? null,
            'is_active'  => true,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->update(['api_token' => null, 'expo_push_token' => null]);
        return response()->json(['message' => 'Logged out']);
    }

    /** PATCH /auth/profile */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'first_name'  => 'sometimes|string|max:100',
            'last_name'   => 'sometimes|string|max:100',
            'email'       => 'sometimes|nullable|email|unique:users,email,' . $request->user()->id,
            'state'       => 'sometimes|nullable|string|max:100',
            'lga'         => 'sometimes|nullable|string|max:100',
            'village'     => 'sometimes|nullable|string|max:100',
            'language'    => 'sometimes|nullable|string|max:10',
        ]);

        $request->user()->update($request->only([
            'first_name', 'last_name', 'email', 'state', 'lga', 'village', 'language',
        ]));

        return response()->json(['user' => $this->userPayload($request->user()->fresh())]);
    }

    /** POST /auth/fcm-token */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'expo_push_token' => 'nullable|string|max:255',
            'fcm_token'       => 'nullable|string|max:255',
        ]);

        $request->user()->update(array_filter([
            'expo_push_token' => $request->expo_push_token,
            'fcm_token'       => $request->fcm_token,
        ], fn($v) => $v !== null));

        return response()->json(['message' => 'Push token updated.']);
    }

    private function userPayload(User $user): array
    {
        $roleDisplayNames = [
            'farmer' => 'Farmer',
            'vet' => 'Veterinarian',
            'veterinarian' => 'Veterinarian',
            'agronomist' => 'Agronomist',
            'admin' => 'Administrator',
            'agro-dealer' => 'Agro Dealer',
            'agro_dealer' => 'Agro Dealer',
            'extension-officer' => 'Extension Worker',
            'extension_officer' => 'Extension Worker',
            'extension-worker' => 'Extension Worker',
            'extension_worker' => 'Extension Worker',
            'researcher' => 'Researcher',
            'hr' => 'Human Resources',
            'finance' => 'Finance Officer',
            'operations' => 'Operations Manager',
            'data-analyst' => 'Data Analyst',
            'data_analyst' => 'Data Analyst',
            'field-officer' => 'Field Officer',
            'field_officer' => 'Field Officer',
            'me-officer' => 'Monitoring & Evaluation Officer',
            'me_officer' => 'Monitoring & Evaluation Officer',
            'customer-support' => 'Customer Support',
            'customer_support' => 'Customer Support',
        ];

        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'display_first_name' => $user->displayFirstName,
            'first_name'         => $user->first_name,
            'last_name'          => $user->last_name,
            'phone'              => $user->phone,
            'email'              => $user->email,
            'role'               => $user->role,
            'role_label'         => $user->roleLabel,
            'role_display'       => $user->roleLabel,
            'language'           => $user->language,
            'state'              => $user->state,
            'lga'                => $user->lga,
            'village'            => $user->village,
            'avatar_url'         => $user->avatarUrl,
            'profile_photo'      => $user->profile_photo,
            'is_verified'        => (bool) $user->is_verified,
            'is_active'          => (bool) $user->is_active,
            'last_seen'          => $user->last_seen,
            'department'         => $user->department ?? null,
            'job_title'          => $user->job_title ?? null,
            'created_at'         => $user->created_at,
        ];
    }
}
