<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Order lifecycle + rider assignment fields ───────────────────────────
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete()->after('dealer_id');
            $table->unsignedBigInteger('assigned_by')->nullable()->after('rider_id'); // admin who assigned
            $table->string('rider_status', 30)->nullable()->after('assigned_by');
            // unassigned | assigned | accepted | declined | in_transit | delivered | completed | returned
            $table->timestamp('rider_assigned_at')->nullable()->after('rider_status');
            $table->timestamp('rider_accepted_at')->nullable()->after('rider_assigned_at');
            $table->timestamp('in_transit_at')->nullable()->after('rider_accepted_at');
            $table->timestamp('completed_at')->nullable()->after('in_transit_at');
            $table->timestamp('returned_at')->nullable()->after('completed_at');
            $table->timestamp('buyer_confirmed_at')->nullable()->after('returned_at');
            $table->text('rejection_reason')->nullable()->after('buyer_confirmed_at');
            $table->text('admin_notes')->nullable()->after('rejection_reason');

            $table->index(['rider_id', 'rider_status']);
        });

        // ── Rider profile fields on users ───────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->string('rider_status', 20)->nullable()->after('role');
            // available | busy | offline
            $table->string('vehicle_type', 50)->nullable()->after('rider_status');
            $table->decimal('rider_rating', 3, 2)->nullable()->after('vehicle_type');
            $table->integer('rider_deliveries')->default(0)->after('rider_rating');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['rider_id', 'rider_status']);
            $table->dropForeign(['rider_id']);
            $table->dropColumn([
                'rider_id', 'assigned_by', 'rider_status',
                'rider_assigned_at', 'rider_accepted_at', 'in_transit_at',
                'completed_at', 'returned_at', 'buyer_confirmed_at',
                'rejection_reason', 'admin_notes',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rider_status', 'vehicle_type', 'rider_rating', 'rider_deliveries']);
        });
    }
};
