<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TtsFeedback extends Model
{
    protected $table = 'tts_feedback';

    protected $fillable = ['user_id', 'diagnosis_id', 'lang', 'voice', 'provider', 'clear', 'comment'];

    protected $casts = ['clear' => 'boolean'];
}
