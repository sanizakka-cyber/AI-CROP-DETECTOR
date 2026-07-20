<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('logistics_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reg_number')->unique();
            $table->string('make');
            $table->string('model')->nullable();
            $table->string('year', 4)->nullable();
            $table->string('vehicle_type')->default('truck'); // truck, van, motorcycle, pickup, refrigerated
            $table->decimal('capacity_kg', 10, 2)->nullable();
            $table->string('status')->default('active'); // active, maintenance, retired
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('logistics_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('license_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('available'); // available, on_trip, off_duty
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('delivery_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref_number')->unique();
            $table->foreignId('logistics_provider_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('logistics_vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('logistics_drivers')->nullOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, assigned, picked_up, in_transit, delivered, failed
            $table->text('pickup_address')->nullable();
            $table->text('delivery_address');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->decimal('cargo_weight_kg', 10, 2)->nullable();
            $table->text('cargo_description')->nullable();
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['logistics_provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_requests');
        Schema::dropIfExists('logistics_drivers');
        Schema::dropIfExists('logistics_vehicles');
    }
};
