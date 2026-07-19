<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Finance / Ledger ────────────────────────────────────────────────────
        Schema::create('finances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');         // Income, Expense
            $table->string('category');     // Eggs, Feed, Salary, Vet, Sales, Other
            $table->decimal('amount', 12, 2);
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        // ── Payroll ─────────────────────────────────────────────────────────────
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('month');        // e.g. "April 2026"
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->string('status')->default('pending'); // pending, paid
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        // ── Attendance ──────────────────────────────────────────────────────────
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status')->default('present'); // present, absent, late
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Leave Requests ──────────────────────────────────────────────────────
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');         // Annual, Sick, Casual, Movement
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // ── Consultations ───────────────────────────────────────────────────────
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('expert_id')->nullable()->constrained('users');
            $table->string('case_type');    // crop, livestock
            $table->string('animal_type')->nullable();
            $table->string('crop_type')->nullable();
            $table->string('priority')->default('low'); // low, medium, high, critical
            $table->text('symptoms');

            $table->string('photo')->nullable();
            $table->string('ai_diagnosis')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->string('status')->default('pending'); // pending, in-progress, completed
            $table->text('expert_response')->nullable();
            $table->string('consultation_type')->default('chat'); // chat, voice, video
            $table->string('channel')->default('in_app');
            $table->decimal('fee', 12, 2)->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_reference')->nullable();
            $table->integer('rating')->nullable(); // 1-5
            $table->text('feedback')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // ── Marketplace Items ───────────────────────────────────────────────────
        Schema::create('marketplace_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('category');    // Livestock, Eggs, Feed, Equipment, Crop
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->integer('quantity')->default(1);
            $table->string('unit')->default('unit'); // kg, bag, head, crate
            $table->string('image')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('active'); // active, sold, pending
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });

        // ── Audit Logs ──────────────────────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // ── Notifications ───────────────────────────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info'); // info, warning, success, danger
            $table->string('link')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // ── Feedback / Testimonials ─────────────────────────────────────────────
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->text('message');
            $table->integer('rating')->default(5); // 1-5
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('marketplace_items');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('finances');
    }
};
