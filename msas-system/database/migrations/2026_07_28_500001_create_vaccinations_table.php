<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vaccinations')) {
            return;
        }

        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vet_id')->constrained('users')->cascadeOnDelete();
            $table->string('animal_type');
            $table->string('vaccine_name');
            $table->string('batch_number')->nullable();
            $table->date('administered_at');
            $table->date('next_due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vet_id', 'administered_at']);
            $table->index(['farmer_id', 'next_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
    }
};
