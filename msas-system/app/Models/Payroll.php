<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'bonus'        => 'decimal:2',
        'deductions'   => 'decimal:2',
        'net_salary'   => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
