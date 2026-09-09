<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: drop the old CHECK constraint and recreate with all plan keys.
        // Keeps 'pro' for backward-compat with any legacy subscriptions.
        // Production runs pgsql exclusively; SQLite (used for tests) has no
        // CHECK-constraint DDL of this shape and doesn't enforce this at the
        // DB level anyway, so it's a no-op there rather than a crash.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }
        DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_plan_check');
        DB::statement("
            ALTER TABLE subscriptions
            ADD CONSTRAINT subscriptions_plan_check
            CHECK (plan::text = ANY(ARRAY[
                'basic'::text,
                'basic_pro'::text,
                'premium'::text,
                'enterprise'::text,
                'enterprise_plus'::text,
                'pro'::text,
                'professional_starter'::text,
                'professional_business'::text
            ]))
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }
        DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_plan_check');
        DB::statement("
            ALTER TABLE subscriptions
            ADD CONSTRAINT subscriptions_plan_check
            CHECK (plan::text = ANY(ARRAY[
                'basic'::text,
                'basic_pro'::text,
                'pro'::text,
                'premium'::text
            ]))
        ");
    }
};
