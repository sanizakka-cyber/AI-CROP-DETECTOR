<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Animals ────────────────────────────────────────────────────────────
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tag_no')->unique();
            $table->string('type');        // cattle, goat, sheep, poultry
            $table->string('breed')->nullable();
            $table->integer('age_months')->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->string('sex')->nullable(); // male, female
            $table->string('health_status')->default('healthy'); // healthy, sick, recovering
            $table->date('purchase_date')->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Vaccinations ───────────────────────────────────────────────────────
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->onDelete('cascade');
            $table->string('vaccine_name');
            $table->date('given_date');
            $table->date('next_due')->nullable();
            $table->string('given_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Poultry Records ────────────────────────────────────────────────────
        Schema::create('poultry_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('record_date');
            $table->integer('layers')->default(0);
            $table->integer('pullets')->default(0);
            $table->integer('broilers')->default(0);
            $table->integer('mortality')->default(0);
            $table->decimal('feed_kg', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Egg Production ─────────────────────────────────────────────────────
        Schema::create('egg_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('production_date');
            $table->integer('quantity');
            $table->integer('broken')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_value', 12, 2)->storedAs('quantity * unit_price');
            $table->timestamps();
        });

        // ── Farm Records ───────────────────────────────────────────────────────
        Schema::create('farm_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('crop_type');
            $table->decimal('plot_size', 8, 2)->nullable();
            $table->date('planting_date')->nullable();
            $table->date('harvest_date')->nullable();
            $table->decimal('yield_kg', 10, 2)->nullable();
            $table->string('growth_stage')->default('planning');
            $table->text('inputs_used')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Sales ──────────────────────────────────────────────────────────────
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('item_type'); // animal, egg, crop, feed
            $table->string('item_name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('buyer_name')->nullable();
            $table->string('buyer_phone')->nullable();
            $table->string('payment_status')->default('paid'); // paid, pending
            $table->date('sale_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
        Schema::dropIfExists('farm_records');
        Schema::dropIfExists('egg_productions');
        Schema::dropIfExists('poultry_records');
        Schema::dropIfExists('vaccinations');
        Schema::dropIfExists('animals');
    }
};
