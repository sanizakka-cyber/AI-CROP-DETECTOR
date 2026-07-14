<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Support tickets
        if (!Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('subject');
                $table->string('category')->default('General');
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
                $table->text('description');
                $table->string('reference')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        // Ticket replies
        if (!Schema::hasTable('ticket_replies')) {
            Schema::create('ticket_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('message');
                $table->timestamps();
            });
        }

        // Operations tasks
        if (!Schema::hasTable('operations_tasks')) {
            Schema::create('operations_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
                $table->date('due_date')->nullable();
                $table->timestamps();
            });
        }

        // Extension advisory
        if (!Schema::hasTable('extension_advisory')) {
            Schema::create('extension_advisory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('farmer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('category')->default('General');
                $table->string('subject');
                $table->text('advice');
                $table->timestamps();
            });
        }

        // Extension farm visits
        if (!Schema::hasTable('extension_visits')) {
            Schema::create('extension_visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('farmer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('visit_date');
                $table->string('purpose');
                $table->string('outcome')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('operations_tasks');
        Schema::dropIfExists('extension_advisory');
        Schema::dropIfExists('extension_visits');
    }
};
