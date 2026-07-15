<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $guarded = [];

    public static function record(string $action, string $model, $modelId = null, array $details = []): void
    {
        try {
            static::create([
                'user_id'    => auth()->id(),
                'action'     => $action,
                'model'      => $model,
                'model_id'   => $modelId,
                'details'    => json_encode($details),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable) {}
    }
}
