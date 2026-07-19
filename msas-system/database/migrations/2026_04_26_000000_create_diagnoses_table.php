<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'plant' or 'animal'
            $table->string('image_path')->nullable();
            
            // Subject identification (auto-detected by AI)
            $table->string('subject_name')->nullable();
            $table->string('scientific_name')->nullable();
            $table->string('detected_part')->nullable();
            $table->string('health_status')->nullable();
            $table->string('severity_level')->nullable();

            // Core diagnosis
            $table->string('disease_name');
            $table->decimal('confidence_score', 5, 2);
            $table->string('urgency_level')->default('Medium');

            // Detailed findings
            $table->text('symptoms_identified')->nullable();
            $table->text('cause')->nullable();
            $table->text('environmental_factors')->nullable();
            $table->text('nutrient_deficiencies')->nullable();
            $table->text('pest_detection')->nullable();

            // Treatment & prevention
            $table->text('first_aid_steps')->nullable();
            $table->text('recommended_medication')->nullable();
            $table->text('preventive_measures')->nullable();
            $table->text('fertilizer_recommendation')->nullable();
            $table->string('recovery_period')->nullable();
            $table->text('best_practices')->nullable();
            $table->text('vet_referral_advice')->nullable();

            // Explainable AI
            $table->text('explanation')->nullable();

            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
