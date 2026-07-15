<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'type'                   => $this->type,
            'disease_name'           => $this->disease_name,
            'confidence_score'       => $this->confidence_score,
            'cause'                  => $this->cause,
            'urgency_level'          => $this->urgency_level,
            'first_aid_steps'        => $this->first_aid_steps,
            'recommended_medication' => $this->recommended_medication,
            'vet_referral_advice'    => $this->vet_referral_advice,
            'status'                 => $this->status,
            'image_url'              => $this->image_path
                ? url('storage/' . $this->image_path)
                : null,
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }
}
