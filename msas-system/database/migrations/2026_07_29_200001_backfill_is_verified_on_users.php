<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mark existing users as verified if they already have a verified email or phone.
        // Going forward, OtpVerificationController sets is_verified = true on first verification.
        DB::table('users')
            ->where('is_verified', false)
            ->orWhereNull('is_verified')
            ->where(function ($q) {
                $q->whereNotNull('email_verified_at')
                  ->orWhereNotNull('phone_verified_at');
            })
            ->update(['is_verified' => true]);
    }

    public function down(): void
    {
        // Non-destructive — leave is_verified as set
    }
};
