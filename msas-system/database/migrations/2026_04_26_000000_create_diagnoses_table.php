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
            
            // AI Analysis Results
            $table->string('disease_name');
            $table->decimal('confidence_score', 5, 2);
            $table->text('cause');
            $table->string('urgency_level'); // 'High', 'Medium', 'Low'
            $table->text('first_aid_steps');
            $table->text('recommended_medication');
            $table->text('vet_referral_advice');
            
            $table->string('status')->default('pending'); // 'pending', 'reviewed'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
