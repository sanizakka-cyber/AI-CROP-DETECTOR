<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users — role, is_active, is_verified, state, created_at are all used in WHERE clauses
        // across every dashboard but have no index → full table scan on every dashboard load
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->hasIndex('users', 'users_role_index')) {
                    $table->index('role');
                }
                if (!$this->hasIndex('users', 'users_is_active_index')) {
                    $table->index('is_active');
                }
                if (!$this->hasIndex('users', 'users_is_verified_index')) {
                    $table->index('is_verified');
                }
                if (!$this->hasIndex('users', 'users_state_index')) {
                    $table->index('state');
                }
                if (!$this->hasIndex('users', 'users_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }

        // consultations — status and case_type filtered on every vet/agronomist/CEO query
        if (Schema::hasTable('consultations')) {
            Schema::table('consultations', function (Blueprint $table) {
                if (!$this->hasIndex('consultations', 'consultations_status_index')) {
                    $table->index('status');
                }
                if (!$this->hasIndex('consultations', 'consultations_case_type_index')) {
                    $table->index('case_type');
                }
                if (!$this->hasIndex('consultations', 'consultations_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }

        // finances — type (Income/Expense) and transaction_date used in every finance/CEO query
        if (Schema::hasTable('finances')) {
            Schema::table('finances', function (Blueprint $table) {
                if (!$this->hasIndex('finances', 'finances_type_transaction_date_index')) {
                    $table->index(['type', 'transaction_date']);
                }
            });
        }

        // diagnoses — status and type filtered on M&E, CEO, customer-support dashboards
        if (Schema::hasTable('diagnoses')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                if (!$this->hasIndex('diagnoses', 'diagnoses_status_index')) {
                    $table->index('status');
                }
                if (!$this->hasIndex('diagnoses', 'diagnoses_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }

        // attendances — date+status queried on HR and CEO dashboards
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!$this->hasIndex('attendances', 'attendances_date_status_index')) {
                    $table->index(['date', 'status']);
                }
            });
        }

        // support_tickets — status filtered 8 times in customer-support dashboard
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                if (!$this->hasIndex('support_tickets', 'support_tickets_status_index')) {
                    $table->index('status');
                }
            });
        }

        // leave_requests — status filtered in HR and CEO dashboards
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (!$this->hasIndex('leave_requests', 'leave_requests_status_index')) {
                    $table->index('status');
                }
            });
        }

        // extension_visits — visit_date used in whereMonth and where(>=today) queries
        if (Schema::hasTable('extension_visits')) {
            Schema::table('extension_visits', function (Blueprint $table) {
                if (!$this->hasIndex('extension_visits', 'extension_visits_visit_date_index')) {
                    $table->index('visit_date');
                }
            });
        }

        // products — is_approved used in marketplace WHERE clause
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!$this->hasIndex('products', 'products_is_approved_index')) {
                    $table->index('is_approved');
                }
            });
        }
    }

    public function down(): void
    {
        $drops = [
            'users'            => ['role', 'is_active', 'is_verified', 'state', 'created_at'],
            'consultations'    => ['status', 'case_type', 'created_at'],
            'finances'         => [['type', 'transaction_date']],
            'diagnoses'        => ['status', 'created_at'],
            'attendances'      => [['date', 'status']],
            'support_tickets'  => ['status'],
            'leave_requests'   => ['status'],
            'extension_visits' => ['visit_date'],
            'products'         => ['is_approved'],
        ];

        foreach ($drops as $table => $columns) {
            if (!Schema::hasTable($table)) continue;
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $col) {
                    try {
                        is_array($col) ? $blueprint->dropIndex($col) : $blueprint->dropIndex([$col]);
                    } catch (\Exception $e) {}
                }
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = \Illuminate\Support\Facades\DB::select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            );
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};
