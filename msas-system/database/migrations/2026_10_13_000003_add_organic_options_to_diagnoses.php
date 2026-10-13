<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The engine has always returned organic/non-chemical options, but there
 * was no column for them, so they were folded into best_practices and the
 * app's "Organic" tab (which reads its own field) was always empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('diagnoses', 'organic_options')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->text('organic_options')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('diagnoses', 'organic_options')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->dropColumn('organic_options');
            });
        }
    }
};
