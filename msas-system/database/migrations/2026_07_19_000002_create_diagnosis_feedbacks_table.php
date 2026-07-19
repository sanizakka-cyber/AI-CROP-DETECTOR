<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('diagnosis_feedbacks')) {
            return;
        }

        Schema::create('diagnosis_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rating');           // 'thumbs_up' | 'thumbs_down'
            $table->string('correct_disease')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['diagnosis_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_feedbacks');
    }
};
