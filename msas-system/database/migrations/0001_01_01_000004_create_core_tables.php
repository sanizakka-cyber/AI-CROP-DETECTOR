<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Each table guarded so this migration is a no-op on existing databases
        // that already ran the old create_core_tables.php filename.

        if (!Schema::hasTable('animals')) {
            Schema::create('animals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('tag_number');
                $table->string('species');        // cattle, goat, sheep, poultry
                $table->string('breed')->nullable();
                $table->string('gender')->nullable(); // male, female
                $table->date('date_of_birth')->nullable();
                $table->decimal('weight_kg', 8, 2)->nullable();
                $table->string('health_status')->default('healthy'); // healthy, sick, recovering
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2026_07_28_500001 creates vaccinations with the authoritative schema;
        // skip here to avoid "table already exists" on fresh installs.
        if (!Schema::hasTable('vaccinations')) {
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
        }

        if (!Schema::hasTable('poultry_records')) {
            Schema::create('poultry_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('batch_number');
                $table->string('bird_type');
                $table->integer('quantity')->default(0);
                $table->date('date_acquired')->nullable();
                $table->integer('mortality')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('egg_productions')) {
            Schema::create('egg_productions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('poultry_record_id')->nullable()->constrained('poultry_records')->onDelete('set null');
                $table->date('production_date');
                $table->integer('quantity');
                $table->integer('broken')->default(0);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('total_value', 12, 2)->storedAs('quantity * unit_price');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('farm_records')) {
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
        }

        if (!Schema::hasTable('sales')) {
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
