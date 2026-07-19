<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'image_path',
        // Subject identification
        'subject_name',
        'scientific_name',
        'detected_part',
        'health_status',
        'severity_level',
        // Core diagnosis
        'disease_name',
        'confidence_score',
        'urgency_level',
        // Detailed findings
        'symptoms_identified',
        'cause',
        'environmental_factors',
        'nutrient_deficiencies',
        'pest_detection',
        // Treatment & prevention
        'first_aid_steps',
        'recommended_medication',
        'preventive_measures',
        'fertilizer_recommendation',
        'recovery_period',
        'best_practices',
        'vet_referral_advice',
        // Explainable AI
        'explanation',
        'status',
    ];

    protected $casts = [
        'confidence_score' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(\App\Models\DiagnosisFeedback::class);
    }

    public function myFeedback()
    {
        return $this->hasOne(\App\Models\DiagnosisFeedback::class)
            ->where('user_id', auth()->id());
    }
}
