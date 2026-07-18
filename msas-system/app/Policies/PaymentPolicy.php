<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id
            || in_array($user->role, ['admin', 'ceo', 'finance']);
    }
}
