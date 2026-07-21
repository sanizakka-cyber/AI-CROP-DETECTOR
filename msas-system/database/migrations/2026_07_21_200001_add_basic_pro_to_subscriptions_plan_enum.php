<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL stores Laravel enum() columns as VARCHAR with a named check constraint.
        // The constraint for subscriptions.plan is named "subscriptions_plan_check".
        // We drop and recreate it to include basic_pro.
        DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_plan_check');
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_plan_check CHECK (plan::text = ANY(ARRAY['basic'::text,'basic_pro'::text,'pro'::text,'premium'::text]))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_plan_check');
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_plan_check CHECK (plan::text = ANY(ARRAY['basic'::text,'pro'::text,'premium'::text]))");
    }
};
