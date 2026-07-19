<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('diagnoses')) {
            return;
        }

        Schema::table('diagnoses', function (Blueprint $table) {
            if (!Schema::hasColumn('diagnoses', 'subject_name')) {
                $table->string('subject_name')->nullable()->after('image_path');
            }
            if (!Schema::hasColumn('diagnoses', 'scientific_name')) {
                $table->string('scientific_name')->nullable()->after('subject_name');
            }
            if (!Schema::hasColumn('diagnoses', 'detected_part')) {
                $table->string('detected_part')->nullable()->after('scientific_name');
            }
            if (!Schema::hasColumn('diagnoses', 'health_status')) {
                $table->string('health_status')->nullable()->after('detected_part');
            }
            if (!Schema::hasColumn('diagnoses', 'severity_level')) {
                $table->string('severity_level')->nullable()->after('health_status');
            }
            if (!Schema::hasColumn('diagnoses', 'symptoms_identified')) {
                $table->text('symptoms_identified')->nullable()->after('confidence_score');
            }
            if (!Schema::hasColumn('diagnoses', 'environmental_factors')) {
                $table->text('environmental_factors')->nullable()->after('cause');
            }
            if (!Schema::hasColumn('diagnoses', 'nutrient_deficiencies')) {
                $table->text('nutrient_deficiencies')->nullable()->after('environmental_factors');
            }
            if (!Schema::hasColumn('diagnoses', 'pest_detection')) {
                $table->text('pest_detection')->nullable()->after('nutrient_deficiencies');
            }
            if (!Schema::hasColumn('diagnoses', 'preventive_measures')) {
                $table->text('preventive_measures')->nullable()->after('recommended_medication');
            }
            if (!Schema::hasColumn('diagnoses', 'fertilizer_recommendation')) {
                $table->text('fertilizer_recommendation')->nullable()->after('preventive_measures');
            }
            if (!Schema::hasColumn('diagnoses', 'recovery_period')) {
                $table->string('recovery_period')->nullable()->after('fertilizer_recommendation');
            }
            if (!Schema::hasColumn('diagnoses', 'best_practices')) {
                $table->text('best_practices')->nullable()->after('recovery_period');
            }
            if (!Schema::hasColumn('diagnoses', 'explanation')) {
                $table->text('explanation')->nullable()->after('vet_referral_advice');
            }
        });

        // Drop NOT NULL constraints using raw SQL (avoids doctrine/dbal dependency).
        // Safe to run multiple times — DROP NOT NULL on a nullable column is a no-op.
        $cols = ['cause', 'first_aid_steps', 'recommended_medication', 'vet_referral_advice'];
        foreach ($cols as $col) {
            if (Schema::hasColumn('diagnoses', $col)) {
                DB::statement("ALTER TABLE diagnoses ALTER COLUMN \"{$col}\" DROP NOT NULL");
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('diagnoses')) {
            return;
        }

        Schema::table('diagnoses', function (Blueprint $table) {
            $columns = [
                'subject_name', 'scientific_name', 'detected_part', 'health_status',
                'severity_level', 'symptoms_identified', 'environmental_factors',
                'nutrient_deficiencies', 'pest_detection', 'preventive_measures',
                'fertilizer_recommendation', 'recovery_period', 'best_practices', 'explanation',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('diagnoses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
