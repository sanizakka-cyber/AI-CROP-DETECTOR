<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Allow any role (not just dealers) to list products
        Schema::table('products', function (Blueprint $table) {
            $table->string('seller_type')->default('dealer')->after('dealer_id');
            // seller_type: dealer, farmer, agribusiness, input-supplier, equipment-dealer
        });

        // Add logistics_provider_id and delivery tracking to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('logistics_provider_id')->nullable()->after('dealer_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('shipped_at')->nullable()->after('confirmed_at');
            $table->string('tracking_ref')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['logistics_provider_id']);
            $table->dropColumn(['logistics_provider_id', 'shipped_at', 'tracking_ref']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('seller_type');
        });
    }
};
