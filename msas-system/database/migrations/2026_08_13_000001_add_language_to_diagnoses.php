<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('diagnoses') || Schema::hasColumn('diagnoses', 'language')) {
            return;
        }

        Schema::table('diagnoses', function (Blueprint $table) {
            // Locale the diagnosis was generated in (matches app locale at scan time),
            // so report/history views know whether to translate on display.
            $table->string('language', 5)->default('en')->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('diagnoses') && Schema::hasColumn('diagnoses', 'language')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->dropColumn('language');
            });
        }
    }
};
