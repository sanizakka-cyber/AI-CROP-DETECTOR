<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpeechReviewPack extends Model
{
    protected $fillable = ['lang', 'title', 'status', 'candidates', 'created_by'];

    protected $casts = ['candidates' => 'array'];

    public function items()
    {
        return $this->hasMany(SpeechReviewItem::class, 'pack_id')->orderBy('position');
    }
}
