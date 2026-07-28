<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    protected $fillable = [
        'farmer_id', 'vet_id', 'animal_type', 'vaccine_name',
        'batch_number', 'administered_at', 'next_due_at', 'notes',
    ];

    protected $casts = [
        'administered_at' => 'date',
        'next_due_at'     => 'date',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function vet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vet_id');
    }

    public function isDueWithin(int $days = 30): bool
    {
        return $this->next_due_at && $this->next_due_at->between(now(), now()->addDays($days));
    }
}
