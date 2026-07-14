<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| RolePermissionSeeder
|--------------------------------------------------------------------------
| Seeds all 100 granular permissions and maps them to each role exactly
| as specified in RBAC_PERMISSIONS_MATRIX.md.
|
| Run:  php artisan db:seed --class=RolePermissionSeeder
|
| IMPORTANT: CEO role is NOT seeded here.  The PermissionMiddleware
| grants CEOs full access automatically, so no rows are needed.
|
| Conditional permissions (marked ⚠️ in the matrix) are granted at this
| level; the scope restriction (e.g. "own cases only") is enforced inside
| the relevant controller, not here.
|--------------------------------------------------------------------------
*/

class RolePermissionSeeder extends Seeder
{
    // ── Full permission catalogue ─────────────────────────────────────────────

    private const PERMISSIONS = [

        // USER MANAGEMENT
        'user:read_own'           => ['category' => 'user',         'description' => 'View own profile and account details'],
        'user:update_own'         => ['category' => 'user',         'description' => 'Edit own profile fields'],
        'user:change_password'    => ['category' => 'user',         'description' => 'Change own account password'],
        'user:delete_own_account' => ['category' => 'user',         'description' => 'Request deletion of own account'],
        'user:list_all'           => ['category' => 'user',         'description' => 'List every user on the platform'],
        'user:read_other'         => ['category' => 'user',         'description' => 'View another user\'s profile (role-scoped)'],
        'user:update_other'       => ['category' => 'user',         'description' => 'Edit another user\'s profile fields'],
        'user:delete_other'       => ['category' => 'user',         'description' => 'Delete another user\'s account (CEO only)'],
        'user:suspend_account'    => ['category' => 'user',         'description' => 'Suspend or reactivate any account'],
        'user:change_role'        => ['category' => 'user',         'description' => 'Reassign a user\'s role (CEO only)'],
        'user:view_analytics'     => ['category' => 'user',         'description' => 'View user analytics (scoped by role)'],

        // FARM MANAGEMENT
        'farm:create'        => ['category' => 'farm', 'description' => 'Register a new farm'],
        'farm:read_own'      => ['category' => 'farm', 'description' => 'View own farm records'],
        'farm:read_other'    => ['category' => 'farm', 'description' => 'View another user\'s farm (role-scoped)'],
        'farm:update_own'    => ['category' => 'farm', 'description' => 'Edit own farm details'],
        'farm:update_other'  => ['category' => 'farm', 'description' => 'Edit another user\'s farm details'],
        'farm:delete_own'    => ['category' => 'farm', 'description' => 'Delete own farm record'],
        'farm:delete_other'  => ['category' => 'farm', 'description' => 'Delete another user\'s farm record'],
        'farm:list_own'      => ['category' => 'farm', 'description' => 'List own farms'],
        'farm:list_all'      => ['category' => 'farm', 'description' => 'List all farms on the platform'],
        'farm:grant_access'  => ['category' => 'farm', 'description' => 'Grant a third party time-limited access to own farm'],
        'farm:revoke_access' => ['category' => 'farm', 'description' => 'Revoke third-party access to own farm'],

        // ANIMALS
        'animal:create'     => ['category' => 'animal', 'description' => 'Register a new animal'],
        'animal:read_own'   => ['category' => 'animal', 'description' => 'View own animals'],
        'animal:read_other' => ['category' => 'animal', 'description' => 'View another user\'s animals (vet: with active consultation)'],
        'animal:update_own' => ['category' => 'animal', 'description' => 'Edit own animal record'],
        'animal:delete_own' => ['category' => 'animal', 'description' => 'Delete own animal record'],

        // CROPS
        'crop:create'     => ['category' => 'crop', 'description' => 'Register a new crop / field'],
        'crop:read_own'   => ['category' => 'crop', 'description' => 'View own crop records'],
        'crop:read_other' => ['category' => 'crop', 'description' => 'View another user\'s crops (agronomist: with active consultation)'],
        'crop:update_own' => ['category' => 'crop', 'description' => 'Edit own crop record'],
        'crop:delete_own' => ['category' => 'crop', 'description' => 'Delete own crop record'],

        // DIAGNOSTICS
        'diagnosis:create'               => ['category' => 'diagnosis', 'description' => 'Submit a new AI scan / diagnosis'],
        'diagnosis:read_own'             => ['category' => 'diagnosis', 'description' => 'View own diagnosis history'],
        'diagnosis:read_other'           => ['category' => 'diagnosis', 'description' => 'View another user\'s diagnosis (expert: assigned cases only)'],
        'diagnosis:list_assigned'        => ['category' => 'diagnosis', 'description' => 'List cases assigned to the current expert'],
        'diagnosis:escalate'             => ['category' => 'diagnosis', 'description' => 'Escalate a case to expert or higher review'],
        'diagnosis:mark_complete'        => ['category' => 'diagnosis', 'description' => 'Mark a case as resolved'],
        'diagnosis:add_expert_notes'     => ['category' => 'diagnosis', 'description' => 'Attach expert notes or findings to a case'],
        'diagnosis:rate_result'          => ['category' => 'diagnosis', 'description' => 'Rate the quality of a diagnosis result'],
        'diagnosis:request_consultation' => ['category' => 'diagnosis', 'description' => 'Request a paid expert consultation on a case'],

        // CONSULTATION
        'consultation:accept'               => ['category' => 'consultation', 'description' => 'Accept an incoming consultation request'],
        'consultation:complete'             => ['category' => 'consultation', 'description' => 'Mark a consultation as completed'],
        'consultation:cancel'               => ['category' => 'consultation', 'description' => 'Cancel an open consultation request'],
        'consultation:write_prescription'   => ['category' => 'consultation', 'description' => 'Write a veterinary prescription (vet only)'],
        'consultation:write_recommendation' => ['category' => 'consultation', 'description' => 'Write a crop advisory recommendation (agronomist only)'],
        'consultation:rate_expert'          => ['category' => 'consultation', 'description' => 'Rate an expert after consultation (farmer only)'],

        // TREATMENTS & MEDICATIONS
        'treatment:create'                  => ['category' => 'treatment',  'description' => 'Log a new treatment plan or application'],
        'treatment:read_own'                => ['category' => 'treatment',  'description' => 'View own treatment records'],
        'treatment:read_other'              => ['category' => 'treatment',  'description' => 'View another user\'s treatment records (expert scope)'],
        'treatment:log_application'         => ['category' => 'treatment',  'description' => 'Record that a treatment was applied'],
        'treatment:log_outcome'             => ['category' => 'treatment',  'description' => 'Record the outcome of a treatment'],
        'medication:view_database'          => ['category' => 'medication', 'description' => 'Browse the medication reference database'],
        'medication:edit_database'          => ['category' => 'medication', 'description' => 'Add or edit entries in the medication database'],
        'medication:view_withdrawal_period' => ['category' => 'medication', 'description' => 'View drug withdrawal periods for livestock products'],

        // MARKETPLACE
        'product:browse'           => ['category' => 'marketplace', 'description' => 'Browse the product catalogue'],
        'product:search'           => ['category' => 'marketplace', 'description' => 'Search products by keyword or tag'],
        'product:view_recommended' => ['category' => 'marketplace', 'description' => 'View AI-recommended products based on diagnosis'],
        'product:add_to_cart'      => ['category' => 'marketplace', 'description' => 'Add products to a shopping cart'],
        'order:create'             => ['category' => 'marketplace', 'description' => 'Place a new order'],
        'order:read_own'           => ['category' => 'marketplace', 'description' => 'View own order history'],
        'order:read_other'         => ['category' => 'marketplace', 'description' => 'View any order (admin / seller own orders)'],
        'order:cancel'             => ['category' => 'marketplace', 'description' => 'Cancel an order'],
        'seller:create_product'    => ['category' => 'marketplace', 'description' => 'List a new product for sale'],
        'seller:manage_inventory'  => ['category' => 'marketplace', 'description' => 'Update stock levels and product details'],
        'seller:view_orders'       => ['category' => 'marketplace', 'description' => 'View incoming customer orders'],
        'seller:fulfill_order'     => ['category' => 'marketplace', 'description' => 'Mark an order as shipped / fulfilled'],
        'seller:view_payout'       => ['category' => 'marketplace', 'description' => 'View payout history and pending balance'],
        'seller:request_payout'    => ['category' => 'marketplace', 'description' => 'Request a payout to bank account'],
        'payment:process'          => ['category' => 'marketplace', 'description' => 'Complete a payment at checkout'],
        'counterfeit:report'       => ['category' => 'marketplace', 'description' => 'Report a product as counterfeit'],
        'counterfeit:review'       => ['category' => 'marketplace', 'description' => 'Review and action counterfeit reports'],

        // EXPERT VERIFICATION & MANAGEMENT
        'expert:apply'              => ['category' => 'expert', 'description' => 'Submit an application for expert status'],
        'expert:upload_credentials' => ['category' => 'expert', 'description' => 'Upload professional credentials for review'],
        'expert:view_own_status'    => ['category' => 'expert', 'description' => 'Check own application / verification status'],
        'expert:list_pending'       => ['category' => 'expert', 'description' => 'List all pending expert applications'],
        'expert:approve'            => ['category' => 'expert', 'description' => 'Approve an expert application'],
        'expert:reject'             => ['category' => 'expert', 'description' => 'Reject an expert application'],
        'expert:suspend'            => ['category' => 'expert', 'description' => 'Suspend a verified expert'],
        'expert:reactivate'         => ['category' => 'expert', 'description' => 'Reactivate a suspended expert'],
        'expert:view_credentials'   => ['category' => 'expert', 'description' => 'View uploaded credential documents'],

        // ANALYTICS & REPORTING
        'analytics:view_own_summary'       => ['category' => 'analytics', 'description' => 'View own activity summary card'],
        'analytics:view_own_performance'   => ['category' => 'analytics', 'description' => 'View own performance metrics (cases closed, ratings)'],
        'analytics:view_platform_summary'  => ['category' => 'analytics', 'description' => 'View platform-wide KPIs'],
        'analytics:view_user_metrics'      => ['category' => 'analytics', 'description' => 'View user growth and activity breakdown'],
        'analytics:view_diagnosis_metrics' => ['category' => 'analytics', 'description' => 'View diagnosis accuracy and outcome trends (role-scoped)'],
        'analytics:view_financial'         => ['category' => 'analytics', 'description' => 'View revenue, expenses, and GMV analytics'],
        'report:generate_custom'           => ['category' => 'analytics', 'description' => 'Build and run custom reports with filters'],
        'report:export_pdf'                => ['category' => 'analytics', 'description' => 'Download reports as PDF'],
        'report:export_excel'              => ['category' => 'analytics', 'description' => 'Download reports as Excel / CSV'],
        'audit:view_log'                   => ['category' => 'analytics', 'description' => 'Browse the system audit trail'],

        // PLATFORM ADMINISTRATION
        'admin:view_dashboard'     => ['category' => 'admin', 'description' => 'Access the admin management dashboard'],
        'admin:manage_users'       => ['category' => 'admin', 'description' => 'Full user management (create, edit, suspend, delete)'],
        'admin:manage_content'     => ['category' => 'admin', 'description' => 'Manage platform content (articles, notifications, alerts)'],
        'admin:manage_settings'    => ['category' => 'admin', 'description' => 'Change system-level platform settings (CEO only)'],
        'admin:manage_features'    => ['category' => 'admin', 'description' => 'Enable or disable platform features (CEO only)'],
        'admin:view_system_health' => ['category' => 'admin', 'description' => 'View service health, uptime, and error rates'],
        'admin:manage_payment'     => ['category' => 'admin', 'description' => 'Configure payment gateways and rates (CEO only)'],
        'admin:financial_controls' => ['category' => 'admin', 'description' => 'Approve payouts and access financial ledger (CEO only)'],
        'admin:emergency_controls' => ['category' => 'admin', 'description' => 'Emergency platform shutdown or maintenance mode (CEO only)'],
    ];

    // ── Role → permission mapping (source: RBAC_PERMISSIONS_MATRIX.md) ────────

    private const ROLE_PERMISSIONS = [

        // ── FARMER (end user) ─────────────────────────────────────────────────
        'farmer' => [
            'user:read_own', 'user:update_own', 'user:change_password', 'user:delete_own_account',
            'farm:create', 'farm:read_own', 'farm:update_own', 'farm:delete_own',
            'farm:list_own', 'farm:grant_access', 'farm:revoke_access',
            'animal:create', 'animal:read_own', 'animal:update_own', 'animal:delete_own',
            'crop:create', 'crop:read_own', 'crop:update_own', 'crop:delete_own',
            'diagnosis:create', 'diagnosis:read_own', 'diagnosis:escalate',
            'diagnosis:rate_result', 'diagnosis:request_consultation',
            'consultation:cancel', 'consultation:rate_expert',
            'treatment:create', 'treatment:read_own', 'treatment:log_application', 'treatment:log_outcome',
            'medication:view_withdrawal_period',
            'product:browse', 'product:search', 'product:view_recommended', 'product:add_to_cart',
            'order:create', 'order:read_own', 'order:cancel',
            'payment:process',
            'analytics:view_own_summary',
            'report:export_pdf',
            'counterfeit:report',
        ],

        // ── VETERINARY DOCTOR (livestock expert) ──────────────────────────────
        // Handles livestock cases only; no crop permissions.
        // diagnosis:read_other and animal:read_other are granted here;
        // controllers MUST scope queries to assigned consultations only.
        'vet' => [
            'user:read_own', 'user:update_own', 'user:change_password', 'user:delete_own_account',
            'user:view_analytics',
            'animal:read_own', 'animal:read_other',
            'farm:read_other',
            'diagnosis:read_other', 'diagnosis:list_assigned', 'diagnosis:escalate',
            'diagnosis:mark_complete', 'diagnosis:add_expert_notes',
            'consultation:accept', 'consultation:complete', 'consultation:cancel',
            'consultation:write_prescription',
            'treatment:create', 'treatment:read_own', 'treatment:read_other',
            'treatment:log_application', 'treatment:log_outcome',
            'medication:view_database', 'medication:view_withdrawal_period',
            'analytics:view_own_summary', 'analytics:view_own_performance', 'analytics:view_diagnosis_metrics',
            'report:export_pdf',
            'expert:apply', 'expert:upload_credentials', 'expert:view_own_status',
        ],

        // ── AGRONOMIST (crop expert) ──────────────────────────────────────────
        // Handles crop cases only; no livestock permissions.
        // diagnosis:read_other and crop:read_other are scoped to assigned cases in controllers.
        'agronomist' => [
            'user:read_own', 'user:update_own', 'user:change_password', 'user:delete_own_account',
            'user:view_analytics',
            'crop:read_own', 'crop:read_other',
            'farm:read_other',
            'diagnosis:read_other', 'diagnosis:list_assigned', 'diagnosis:escalate',
            'diagnosis:mark_complete', 'diagnosis:add_expert_notes',
            'consultation:accept', 'consultation:complete', 'consultation:cancel',
            'consultation:write_recommendation',
            'treatment:create', 'treatment:read_own', 'treatment:read_other',
            'treatment:log_application', 'treatment:log_outcome',
            'medication:view_database', 'medication:view_withdrawal_period',
            'analytics:view_own_summary', 'analytics:view_own_performance', 'analytics:view_diagnosis_metrics',
            'report:export_pdf',
            'expert:apply', 'expert:upload_credentials', 'expert:view_own_status',
        ],

        // ── ADMIN / PLATFORM MANAGER ──────────────────────────────────────────
        // Full management access; no financial controls, no system settings.
        'admin' => [
            'user:read_own', 'user:update_own', 'user:change_password', 'user:delete_own_account',
            'user:list_all', 'user:read_other', 'user:update_other', 'user:suspend_account', 'user:view_analytics',
            'farm:create', 'farm:read_own', 'farm:read_other', 'farm:update_own', 'farm:update_other',
            'farm:delete_own', 'farm:delete_other', 'farm:list_own', 'farm:list_all',
            'animal:create', 'animal:read_own', 'animal:read_other', 'animal:update_own', 'animal:delete_own',
            'crop:create', 'crop:read_own', 'crop:read_other', 'crop:update_own', 'crop:delete_own',
            'diagnosis:create', 'diagnosis:read_own', 'diagnosis:read_other', 'diagnosis:list_assigned',
            'diagnosis:escalate', 'diagnosis:mark_complete', 'diagnosis:add_expert_notes',
            'diagnosis:rate_result', 'diagnosis:request_consultation',
            'consultation:accept', 'consultation:complete', 'consultation:cancel',
            'consultation:write_prescription', 'consultation:write_recommendation', 'consultation:rate_expert',
            'treatment:create', 'treatment:read_own', 'treatment:read_other',
            'treatment:log_application', 'treatment:log_outcome',
            'medication:view_database', 'medication:edit_database', 'medication:view_withdrawal_period',
            'product:browse', 'product:search', 'product:view_recommended',
            'order:read_own', 'order:read_other', 'order:cancel',
            'seller:create_product', 'seller:manage_inventory', 'seller:view_orders',
            'seller:fulfill_order', 'seller:view_payout', 'seller:request_payout',
            'counterfeit:report', 'counterfeit:review',
            'expert:apply', 'expert:upload_credentials', 'expert:view_own_status',
            'expert:list_pending', 'expert:approve', 'expert:reject',
            'expert:suspend', 'expert:reactivate', 'expert:view_credentials',
            'analytics:view_own_summary', 'analytics:view_own_performance',
            'analytics:view_platform_summary', 'analytics:view_user_metrics', 'analytics:view_diagnosis_metrics',
            'report:generate_custom', 'report:export_pdf', 'report:export_excel',
            'audit:view_log',
            'admin:view_dashboard', 'admin:manage_users', 'admin:manage_content', 'admin:view_system_health',
        ],

        // ── AGRO-DEALER / SUPPLIER ────────────────────────────────────────────
        // Manages own product inventory and order fulfilment only.
        'agro-dealer' => [
            'user:read_own', 'user:update_own', 'user:change_password', 'user:delete_own_account',
            'user:view_analytics',
            'product:browse', 'product:search', 'product:view_recommended',
            'order:read_own', 'order:read_other', 'order:cancel',
            'seller:create_product', 'seller:manage_inventory', 'seller:view_orders',
            'seller:fulfill_order', 'seller:view_payout', 'seller:request_payout',
            'medication:view_database', 'medication:view_withdrawal_period',
            'analytics:view_own_summary', 'analytics:view_own_performance',
            'report:generate_custom', 'report:export_pdf', 'report:export_excel',
            'expert:apply', 'expert:upload_credentials', 'expert:view_own_status',
            'counterfeit:report',
        ],

        // ── EXTENSION OFFICER (field support) ─────────────────────────────────
        // Supervised field work; scoped to assigned coverage area in controllers.
        'extension-officer' => [
            'user:read_own', 'user:update_own', 'user:change_password', 'user:delete_own_account',
            'user:read_other',
            'farm:read_own', 'farm:read_other', 'farm:list_all',
            'animal:read_own', 'animal:read_other',
            'crop:read_own', 'crop:read_other',
            'diagnosis:create', 'diagnosis:read_own', 'diagnosis:read_other',
            'diagnosis:escalate', 'diagnosis:request_consultation',
            'consultation:cancel',
            'treatment:create', 'treatment:read_own', 'treatment:read_other',
            'treatment:log_application', 'treatment:log_outcome',
            'medication:view_withdrawal_period',
            'product:browse', 'product:search', 'product:view_recommended',
            'counterfeit:report',
            'analytics:view_diagnosis_metrics',
            'report:export_pdf',
        ],

        // CEO is handled by PermissionMiddleware directly (all-pass); no rows needed.
    ];

    // ── Seeder execution ──────────────────────────────────────────────────────

    public function run(): void
    {
        // 1. Upsert permission catalogue.
        $this->command->info('Seeding permissions catalogue…');
        foreach (self::PERMISSIONS as $name => $meta) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['category' => $meta['category'], 'description' => $meta['description']]
            );
        }

        // Build name → id map once.
        $permissionMap = Permission::pluck('id', 'name');

        // 2. Seed role → permission rows.
        $this->command->info('Mapping permissions to roles…');
        $rows = [];
        $now  = now();

        foreach (self::ROLE_PERMISSIONS as $role => $permNames) {
            foreach ($permNames as $permName) {
                if (!isset($permissionMap[$permName])) {
                    $this->command->warn("  ⚠  Unknown permission '{$permName}' for role '{$role}' — skipped.");
                    continue;
                }
                $rows[] = [
                    'role'          => $role,
                    'permission_id' => $permissionMap[$permName],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        // Upsert to support re-running the seeder safely.
        foreach (array_chunk($rows, 100) as $chunk) {
            RolePermission::upsert(
                $chunk,
                ['role', 'permission_id'],
                ['updated_at']
            );
        }

        // 3. Flush permission cache so live requests pick up new mappings.
        foreach (array_keys(self::ROLE_PERMISSIONS) as $role) {
            Cache::forget("msas:permissions:{$role}");
        }

        // 4. Summary.
        $total = array_sum(array_map('count', self::ROLE_PERMISSIONS));
        $this->command->info("✅ Seeded " . count(self::PERMISSIONS) . " permissions, {$total} role-permission mappings.");
        $this->command->table(
            ['Role', 'Permission count'],
            array_map(
                fn($role, $perms) => [$role, count($perms)],
                array_keys(self::ROLE_PERMISSIONS),
                self::ROLE_PERMISSIONS
            )
        );
        $this->command->info('   CEO: all permissions (handled by middleware, no DB rows needed).');
    }
}
