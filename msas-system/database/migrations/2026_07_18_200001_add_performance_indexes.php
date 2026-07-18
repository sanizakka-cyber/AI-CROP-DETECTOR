<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Index definitions: [table => [[columns], indexName]]
    // Using Schema::getIndexListing() for DB-agnostic existence checks (works on both MySQL and PostgreSQL)
    private array $indexes = [
        'users' => [
            [['role'],       'users_role_index'],
            [['is_active'],  'users_is_active_index'],
            [['is_verified'],'users_is_verified_index'],
            [['state'],      'users_state_index'],
            [['created_at'], 'users_created_at_index'],
        ],
        'consultations' => [
            [['status'],     'consultations_status_index'],
            [['case_type'],  'consultations_case_type_index'],
            [['created_at'], 'consultations_created_at_index'],
        ],
        'finances' => [
            [['type', 'transaction_date'], 'finances_type_transaction_date_index'],
        ],
        'diagnoses' => [
            [['status'],     'diagnoses_status_index'],
            [['created_at'], 'diagnoses_created_at_index'],
        ],
        'attendances' => [
            [['date', 'status'], 'attendances_date_status_index'],
        ],
        'support_tickets' => [
            [['status'], 'support_tickets_status_index'],
        ],
        'leave_requests' => [
            [['status'], 'leave_requests_status_index'],
        ],
        'extension_visits' => [
            [['visit_date'], 'extension_visits_visit_date_index'],
        ],
        'products' => [
            [['is_approved'], 'products_is_approved_index'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $defs) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = Schema::getIndexListing($table);

            Schema::table($table, function (Blueprint $blueprint) use ($defs, $existing) {
                foreach ($defs as [$columns, $name]) {
                    if (! in_array($name, $existing)) {
                        $blueprint->index($columns, $name);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $defs) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = Schema::getIndexListing($table);

            Schema::table($table, function (Blueprint $blueprint) use ($defs, $existing) {
                foreach ($defs as [$columns, $name]) {
                    if (in_array($name, $existing)) {
                        $blueprint->dropIndex($name);
                    }
                }
            });
        }
    }
};
