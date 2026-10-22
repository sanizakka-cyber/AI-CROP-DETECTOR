<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The engine's repeat analyses now vote on a condition label from a fixed
 * list (ai-engine/conditions.py) instead of on wording. Stored here: the
 * winning label, its category (which drives the farmer's health line and
 * "What to do now" steps) and the plain-language name the farmer sees.
 * All nullable: older scans and inconclusive results have none.
 */
return new class extends Migration
{
    private const COLUMNS = ['condition_label', 'condition_category', 'farmer_name'];

    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            if (!Schema::hasColumn('diagnoses', 'condition_label')) {
                $table->string('condition_label', 64)->nullable();
            }
            if (!Schema::hasColumn('diagnoses', 'condition_category')) {
                $table->string('condition_category', 32)->nullable();
            }
            if (!Schema::hasColumn('diagnoses', 'farmer_name')) {
                $table->string('farmer_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $col) {
            if (Schema::hasColumn('diagnoses', $col)) {
                Schema::table('diagnoses', fn (Blueprint $table) => $table->dropColumn($col));
            }
        }
    }
};
