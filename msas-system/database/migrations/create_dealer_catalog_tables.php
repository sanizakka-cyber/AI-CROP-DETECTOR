<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Product Catalog ─────────────────────────────────────────────────────
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('category');        // Livestock Feed, Veterinary Medicines, Vaccines, etc.
            $table->string('subcategory')->nullable(); // Poultry Feed → Starter Feed, etc.
            $table->string('brand')->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('description')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->text('dosage_instructions')->nullable();
            $table->text('storage_requirements')->nullable();
            $table->string('unit')->default('unit'); // kg, bag, bottle, sachet, head, etc.
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2);
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->date('expiry_date')->nullable();
            $table->string('image')->nullable();
            $table->json('tags')->nullable();  // for AI recommendation matching
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->string('status')->default('active'); // active, inactive, draft
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->index(['dealer_id', 'status']);
            $table->index('category');
        });

        // ── Product Reviews ─────────────────────────────────────────────────────
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('review')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'user_id']);
        });

        // ── Shopping Cart ────────────────────────────────────────────────────────
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        // ── Orders ───────────────────────────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // ORD-YYYYMMDD-XXXX
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('dealer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            // pending → confirmed → processing → shipped → delivered | cancelled
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, refunded
            $table->string('payment_method')->nullable(); // paystack, flutterwave, transfer, ussd, card, wallet
            $table->string('payment_channel')->nullable();
            $table->string('payment_reference')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('delivery_address')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['buyer_id', 'status']);
            $table->index(['dealer_id', 'status']);
        });

        // ── Order Items ───────────────────────────────────────────────────────────
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name'); // snapshot at order time
            $table->string('product_sku')->nullable();
            $table->string('unit')->default('unit');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('products');
    }
};
