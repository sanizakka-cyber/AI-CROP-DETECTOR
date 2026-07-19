<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->text('cause')->nullable()->change();
            $table->text('first_aid_steps')->nullable()->change();
            $table->text('recommended_medication')->nullable()->change();
            $table->text('vet_referral_advice')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->text('cause')->nullable(false)->change();
            $table->text('first_aid_steps')->nullable(false)->change();
            $table->text('recommended_medication')->nullable(false)->change();
            $table->text('vet_referral_advice')->nullable(false)->change();
        });
    }
};
