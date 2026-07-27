<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Wallet;

class UserObserver
{
    public function created(User $user): void
    {
        Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'locked_balance' => 0, 'currency' => 'NGN', 'is_active' => true]
        );
    }
}
