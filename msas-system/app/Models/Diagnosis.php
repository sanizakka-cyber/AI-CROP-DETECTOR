<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type', // 'plant' or 'animal'
        'image_path',
        'disease_name',
        'confidence_score',
        'cause',
        'urgency_level',
        'first_aid_steps',
        'recommended_medication',
        'vet_referral_advice',
        'status', // 'pending', 'reviewed', 'resolved'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
