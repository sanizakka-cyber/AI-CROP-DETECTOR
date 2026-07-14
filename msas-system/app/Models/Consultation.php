<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = [
        'farmer_id',
        'expert_id',
        'case_type',
        'animal_type',
        'crop_type',
        'symptoms',
        'photo',
        'ai_diagnosis',
        'ai_confidence',
        'status',
        'expert_response',
        'consultation_type',
        'fee',
        'payment_status',
        'rating',
        'feedback',
        'completed_at',
        'priority'
    ];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    // Alias so ->with('user') and ->user work alongside ->with('farmer')
    public function user()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
}

