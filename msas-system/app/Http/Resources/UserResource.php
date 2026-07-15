<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'role'        => $this->role,
            'role_label'  => $this->roleLabel ?? $this->role,
            'state'       => $this->state,
            'lga'         => $this->lga,
            'language'    => $this->language,
            'avatar_url'  => $this->avatarUrl ?? null,
            'is_verified' => (bool) $this->is_verified,
            'is_active'   => (bool) $this->is_active,
            'last_seen'   => $this->last_seen?->toIso8601String(),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
