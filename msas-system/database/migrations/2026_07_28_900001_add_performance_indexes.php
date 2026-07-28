<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // diagnoses — most queried table; every farmer page load hits this
        if (Schema::hasTable('diagnoses')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                if (!$this->hasIndex('diagnoses', 'diagnoses_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at'], 'diagnoses_user_id_created_at_index');
                }
                if (!$this->hasIndex('diagnoses', 'diagnoses_status_index')) {
                    $table->index('status', 'diagnoses_status_index');
                }
                if (!$this->hasIndex('diagnoses', 'diagnoses_type_index')) {
                    $table->index('type', 'diagnoses_type_index');
                }
            });
        }

        // consultations — filtered by status, farmer, expert on every queue page
        if (Schema::hasTable('consultations')) {
            Schema::table('consultations', function (Blueprint $table) {
                if (!$this->hasIndex('consultations', 'consultations_farmer_id_status_index')) {
                    $table->index(['farmer_id', 'status'], 'consultations_farmer_id_status_index');
                }
                if (!$this->hasIndex('consultations', 'consultations_expert_id_status_index')) {
                    $table->index(['expert_id', 'status'], 'consultations_expert_id_status_index');
                }
                if (!$this->hasIndex('consultations', 'consultations_status_index')) {
                    $table->index('status', 'consultations_status_index');
                }
            });
        }

        // notifications — WHERE user_id=? AND is_read=false on every page load
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!$this->hasIndex('notifications', 'notifications_user_id_is_read_index')) {
                    $table->index(['user_id', 'is_read'], 'notifications_user_id_is_read_index');
                }
            });
        }

        // subscriptions — activeSubscription() called on nearly every request
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!$this->hasIndex('subscriptions', 'subscriptions_user_id_status_index')) {
                    $table->index(['user_id', 'status'], 'subscriptions_user_id_status_index');
                }
            });
        }

        // audit_logs — CEO audit dashboard queries by action and date
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!$this->hasIndex('audit_logs', 'audit_logs_action_created_at_index')) {
                    $table->index(['action', 'created_at'], 'audit_logs_action_created_at_index');
                }
            });
        }

        // animals — listed per-user with health_status filter
        if (Schema::hasTable('animals')) {
            Schema::table('animals', function (Blueprint $table) {
                if (!$this->hasIndex('animals', 'animals_user_id_health_status_index')) {
                    $table->index(['user_id', 'health_status'], 'animals_user_id_health_status_index');
                }
            });
        }

        // login_histories — security page loads last 20 per user
        if (Schema::hasTable('login_histories')) {
            Schema::table('login_histories', function (Blueprint $table) {
                if (!$this->hasIndex('login_histories', 'login_histories_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at'], 'login_histories_user_id_created_at_index');
                }
            });
        }

        // payments — history page, receipt lookup, duplicate-reference check
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!$this->hasIndex('payments', 'payments_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at'], 'payments_user_id_created_at_index');
                }
                if (!$this->hasIndex('payments', 'payments_reference_index')) {
                    $table->index('reference', 'payments_reference_index');
                }
            });
        }
    }

    public function down(): void
    {
        $drops = [
            'diagnoses'       => ['diagnoses_user_id_created_at_index', 'diagnoses_status_index', 'diagnoses_type_index'],
            'consultations'   => ['consultations_farmer_id_status_index', 'consultations_expert_id_status_index', 'consultations_status_index'],
            'notifications'   => ['notifications_user_id_is_read_index'],
            'subscriptions'   => ['subscriptions_user_id_status_index'],
            'audit_logs'      => ['audit_logs_action_created_at_index'],
            'animals'         => ['animals_user_id_health_status_index'],
            'login_histories' => ['login_histories_user_id_created_at_index'],
            'payments'        => ['payments_user_id_created_at_index', 'payments_reference_index'],
        ];

        foreach ($drops as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($indexes) {
                    foreach ($indexes as $index) {
                        try { $t->dropIndex($index); } catch (\Throwable) {}
                    }
                });
            }
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = \Illuminate\Support\Facades\DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return !empty($indexes);
        } catch (\Throwable) {
            return false;
        }
    }
};
