<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisFeedback extends Model
{
    protected $fillable = [
        'diagnosis_id',
        'user_id',
        'rating',
        'correct_disease',
        'notes',
    ];

    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
