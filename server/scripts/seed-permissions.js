// server/scripts/seed-permissions.js
/**
 * Seed granular permissions based on RBAC Permissions Matrix
 * Run: node scripts/seed-permissions.js
 */

require('dotenv').config();
const mongoose = require('mongoose');
const Permission = require('../models/Permission');

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/farmai';

// All permissions from the RBAC matrix
const permissions = [
  // USER MANAGEMENT
  { name: 'user:read_own', category: 'user', description: 'View own profile', roles: ['farmer', 'vet', 'agronomist', 'agro-dealer', 'admin', 'extension-officer', 'ceo', 'researcher'] },
  { name: 'user:update_own', category: 'user', description: 'Update own profile', roles: ['farmer', 'vet', 'agronomist', 'agro-dealer', 'admin', 'extension-officer', 'ceo', 'researcher'], riskLevel: 'medium' },
  { name: 'user:change_password', category: 'user', description: 'Change own password', roles: ['farmer', 'vet', 'agronomist', 'agro-dealer', 'admin', 'extension-officer', 'ceo', 'researcher'], riskLevel: 'high' },
  { name: 'user:delete_own_account', category: 'user', description: 'Delete own account', roles: ['farmer', 'vet', 'agronomist', 'agro-dealer', 'admin', 'extension-officer', 'ceo', 'researcher'], riskLevel: 'critical' },
  { name: 'user:list_all', category: 'user', description: 'List all users', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'user:read_other', category: 'user', description: 'View other user profiles', roles: ['admin', 'ceo', 'vet', 'agronomist'], riskLevel: 'medium' },
  { name: 'user:update_other', category: 'user', description: 'Update other user profiles', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'user:delete_other', category: 'user', description: 'Delete other user accounts', roles: ['ceo'], riskLevel: 'critical' },
  { name: 'user:suspend_account', category: 'user', description: 'Suspend user account', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'user:change_role', category: 'user', description: 'Change user role', roles: ['ceo'], riskLevel: 'critical' },
  { name: 'user:view_analytics', category: 'user', description: 'View user analytics', roles: ['admin', 'ceo', 'vet', 'agronomist', 'agro-dealer'] },

  // FARM MANAGEMENT
  { name: 'farm:create', category: 'farm', description: 'Create new farm', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'medium' },
  { name: 'farm:read_own', category: 'farm', description: 'Read own farm data', roles: ['farmer', 'admin', 'ceo', 'extension-officer'], requiresOwnershipCheck: true },
  { name: 'farm:read_other', category: 'farm', description: 'Read other farm data', roles: ['admin', 'ceo', 'vet', 'agronomist', 'extension-officer'], riskLevel: 'medium', requiresOwnershipCheck: false },
  { name: 'farm:update_own', category: 'farm', description: 'Update own farm', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'medium', requiresOwnershipCheck: true },
  { name: 'farm:update_other', category: 'farm', description: 'Update other farm', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'farm:delete_own', category: 'farm', description: 'Delete own farm', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'high', requiresOwnershipCheck: true },
  { name: 'farm:delete_other', category: 'farm', description: 'Delete other farm', roles: ['admin', 'ceo'], riskLevel: 'critical' },
  { name: 'farm:list_own', category: 'farm', description: 'List own farms', roles: ['farmer', 'admin', 'ceo'] },
  { name: 'farm:list_all', category: 'farm', description: 'List all farms', roles: ['admin', 'ceo', 'extension-officer'], riskLevel: 'high' },
  { name: 'farm:grant_access', category: 'farm', description: 'Grant farm access to others', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'medium', requiresOwnershipCheck: true },
  { name: 'farm:revoke_access', category: 'farm', description: 'Revoke farm access', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'medium', requiresOwnershipCheck: true },

  // ANIMALS & CROPS
  { name: 'animal:create', category: 'animal', description: 'Create animal record', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'animal:read_own', category: 'animal', description: 'Read own animal records', roles: ['farmer', 'admin', 'ceo', 'vet', 'extension-officer'] },
  { name: 'animal:read_other', category: 'animal', description: 'Read other animal records', roles: ['admin', 'ceo', 'vet', 'extension-officer'], riskLevel: 'medium' },
  { name: 'animal:update_own', category: 'animal', description: 'Update own animal records', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'animal:delete_own', category: 'animal', description: 'Delete own animal records', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'crop:create', category: 'crop', description: 'Create crop record', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'crop:read_own', category: 'crop', description: 'Read own crop records', roles: ['farmer', 'admin', 'ceo', 'agronomist', 'extension-officer'] },
  { name: 'crop:read_other', category: 'crop', description: 'Read other crop records', roles: ['admin', 'ceo', 'agronomist', 'extension-officer'], riskLevel: 'medium' },
  { name: 'crop:update_own', category: 'crop', description: 'Update own crop records', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'crop:delete_own', category: 'crop', description: 'Delete own crop records', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'low' },

  // DIAGNOSTICS & CONSULTATION
  { name: 'diagnosis:create', category: 'diagnosis', description: 'Create diagnosis', roles: ['farmer', 'admin', 'ceo', 'extension-officer'], riskLevel: 'medium' },
  { name: 'diagnosis:read_own', category: 'diagnosis', description: 'Read own diagnoses', roles: ['farmer', 'admin', 'ceo', 'extension-officer'] },
  { name: 'diagnosis:read_other', category: 'diagnosis', description: 'Read other diagnoses', roles: ['admin', 'ceo', 'vet', 'agronomist', 'extension-officer'], riskLevel: 'medium' },
  { name: 'diagnosis:list_assigned', category: 'diagnosis', description: 'List assigned cases', roles: ['vet', 'agronomist', 'admin', 'ceo'] },
  { name: 'diagnosis:escalate', category: 'diagnosis', description: 'Escalate diagnosis for review', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo', 'extension-officer'], riskLevel: 'medium' },
  { name: 'diagnosis:mark_complete', category: 'diagnosis', description: 'Mark diagnosis complete', roles: ['vet', 'agronomist', 'admin', 'ceo'], riskLevel: 'medium' },
  { name: 'diagnosis:add_expert_notes', category: 'diagnosis', description: 'Add expert notes to diagnosis', roles: ['vet', 'agronomist', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'diagnosis:rate_result', category: 'diagnosis', description: 'Rate diagnosis result', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'diagnosis:request_consultation', category: 'diagnosis', description: 'Request expert consultation', roles: ['farmer', 'admin', 'ceo', 'extension-officer'], riskLevel: 'low' },
  { name: 'consultation:accept', category: 'consultation', description: 'Accept consultation request', roles: ['vet', 'agronomist', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'consultation:complete', category: 'consultation', description: 'Complete consultation', roles: ['vet', 'agronomist', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'consultation:cancel', category: 'consultation', description: 'Cancel consultation', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo'], riskLevel: 'low' },
  { name: 'consultation:write_prescription', category: 'consultation', description: 'Write prescription (vet)', roles: ['vet', 'admin', 'ceo'], riskLevel: 'high' },
  { name: 'consultation:write_recommendation', category: 'consultation', description: 'Write recommendation (agronomist)', roles: ['agronomist', 'admin', 'ceo'], riskLevel: 'high' },
  { name: 'consultation:rate_expert', category: 'consultation', description: 'Rate expert consultation', roles: ['farmer', 'admin', 'ceo'], riskLevel: 'low' },

  // TREATMENTS & MEDICATIONS
  { name: 'treatment:create', category: 'treatment', description: 'Create treatment record', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo', 'extension-officer'], riskLevel: 'low' },
  { name: 'treatment:read_own', category: 'treatment', description: 'Read own treatment records', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo', 'extension-officer'] },
  { name: 'treatment:read_other', category: 'treatment', description: 'Read other treatment records', roles: ['admin', 'ceo', 'vet', 'agronomist', 'extension-officer'], riskLevel: 'medium' },
  { name: 'treatment:log_application', category: 'treatment', description: 'Log treatment application', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo', 'extension-officer'], riskLevel: 'low' },
  { name: 'treatment:log_outcome', category: 'treatment', description: 'Log treatment outcome', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo', 'extension-officer'], riskLevel: 'low' },
  { name: 'medication:view_database', category: 'treatment', description: 'View medication database', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo', 'agro-dealer', 'extension-officer', 'researcher'] },
  { name: 'medication:edit_database', category: 'treatment', description: 'Edit medication database', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'medication:view_withdrawal_period', category: 'treatment', description: 'View medication withdrawal period', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo', 'agro-dealer', 'extension-officer', 'researcher'] },

  // MARKETPLACE
  { name: 'product:browse', category: 'marketplace', description: 'Browse marketplace products', roles: ['farmer', 'admin', 'ceo', 'extension-officer', 'agronomist', 'vet', 'agro-dealer'] },
  { name: 'product:search', category: 'marketplace', description: 'Search marketplace products', roles: ['farmer', 'admin', 'ceo', 'extension-officer', 'agronomist', 'vet', 'agro-dealer'] },
  { name: 'product:view_recommended', category: 'marketplace', description: 'View recommended products', roles: ['farmer', 'admin', 'ceo', 'agronomist', 'vet', 'agro-dealer', 'extension-officer'] },
  { name: 'product:add_to_cart', category: 'marketplace', description: 'Add product to cart', roles: ['farmer'] },
  { name: 'order:create', category: 'marketplace', description: 'Create order', roles: ['farmer'], riskLevel: 'medium' },
  { name: 'order:read_own', category: 'marketplace', description: 'Read own orders', roles: ['farmer', 'admin', 'ceo', 'agro-dealer'] },
  { name: 'order:read_other', category: 'marketplace', description: 'Read other orders', roles: ['admin', 'ceo', 'agro-dealer'], riskLevel: 'medium' },
  { name: 'order:cancel', category: 'marketplace', description: 'Cancel order', roles: ['farmer', 'admin', 'ceo', 'agro-dealer'], riskLevel: 'low' },
  { name: 'seller:create_product', category: 'marketplace', description: 'Create product listing', roles: ['admin', 'ceo', 'agro-dealer'], riskLevel: 'medium' },
  { name: 'seller:manage_inventory', category: 'marketplace', description: 'Manage product inventory', roles: ['admin', 'ceo', 'agro-dealer'], riskLevel: 'low' },
  { name: 'seller:view_orders', category: 'marketplace', description: 'View seller orders', roles: ['admin', 'ceo', 'agro-dealer'] },
  { name: 'seller:fulfill_order', category: 'marketplace', description: 'Fulfill order', roles: ['admin', 'ceo', 'agro-dealer'], riskLevel: 'low' },
  { name: 'seller:view_payout', category: 'marketplace', description: 'View payout information', roles: ['admin', 'ceo', 'agro-dealer'] },
  { name: 'seller:request_payout', category: 'marketplace', description: 'Request payout', roles: ['agro-dealer', 'admin', 'ceo'], riskLevel: 'medium' },
  { name: 'payment:process', category: 'marketplace', description: 'Process payment', roles: ['farmer'], riskLevel: 'high' },
  { name: 'counterfeit:report', category: 'marketplace', description: 'Report counterfeit product', roles: ['farmer', 'admin', 'ceo', 'extension-officer'], riskLevel: 'low' },
  { name: 'counterfeit:review', category: 'marketplace', description: 'Review counterfeit report', roles: ['admin', 'ceo'], riskLevel: 'high' },

  // EXPERT VERIFICATION
  { name: 'expert:apply', category: 'expert', description: 'Apply as expert', roles: ['vet', 'agronomist', 'admin', 'ceo'] },
  { name: 'expert:upload_credentials', category: 'expert', description: 'Upload expert credentials', roles: ['vet', 'agronomist', 'admin', 'ceo'], riskLevel: 'medium' },
  { name: 'expert:view_own_status', category: 'expert', description: 'View own verification status', roles: ['vet', 'agronomist', 'admin', 'ceo'] },
  { name: 'expert:list_pending', category: 'expert', description: 'List pending expert approvals', roles: ['admin', 'ceo'], riskLevel: 'medium' },
  { name: 'expert:approve', category: 'expert', description: 'Approve expert', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'expert:reject', category: 'expert', description: 'Reject expert application', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'expert:suspend', category: 'expert', description: 'Suspend expert account', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'expert:reactivate', category: 'expert', description: 'Reactivate expert account', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'expert:view_credentials', category: 'expert', description: 'View expert credentials', roles: ['admin', 'ceo'], riskLevel: 'medium' },

  // ANALYTICS & REPORTING
  { name: 'analytics:view_own_summary', category: 'analytics', description: 'View own summary', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo', 'agro-dealer'] },
  { name: 'analytics:view_own_performance', category: 'analytics', description: 'View own performance metrics', roles: ['vet', 'agronomist', 'admin', 'ceo', 'agro-dealer'] },
  { name: 'analytics:view_platform_summary', category: 'analytics', description: 'View platform summary', roles: ['admin', 'ceo'] },
  { name: 'analytics:view_user_metrics', category: 'analytics', description: 'View user metrics', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'analytics:view_diagnosis_metrics', category: 'analytics', description: 'View diagnosis metrics', roles: ['admin', 'ceo', 'vet', 'agronomist', 'extension-officer'] },
  { name: 'analytics:view_financial', category: 'analytics', description: 'View financial analytics', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'report:generate_custom', category: 'analytics', description: 'Generate custom report', roles: ['admin', 'ceo', 'agro-dealer'], riskLevel: 'low' },
  { name: 'report:export_pdf', category: 'analytics', description: 'Export report as PDF', roles: ['farmer', 'vet', 'agronomist', 'admin', 'ceo', 'agro-dealer', 'extension-officer'] },
  { name: 'report:export_excel', category: 'analytics', description: 'Export report as Excel', roles: ['admin', 'ceo', 'agro-dealer'], riskLevel: 'low' },
  { name: 'audit:view_log', category: 'analytics', description: 'View audit log', roles: ['admin', 'ceo'], riskLevel: 'high' },

  // ADMIN
  { name: 'admin:view_dashboard', category: 'admin', description: 'View admin dashboard', roles: ['admin', 'ceo'] },
  { name: 'admin:manage_users', category: 'admin', description: 'Manage users', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'admin:manage_content', category: 'admin', description: 'Manage platform content', roles: ['admin', 'ceo'], riskLevel: 'high' },
  { name: 'admin:manage_settings', category: 'admin', description: 'Manage platform settings', roles: ['ceo'], riskLevel: 'critical' },
  { name: 'admin:manage_features', category: 'admin', description: 'Manage feature flags', roles: ['ceo'], riskLevel: 'critical' },
  { name: 'admin:view_system_health', category: 'admin', description: 'View system health', roles: ['admin', 'ceo'], riskLevel: 'medium' },
  { name: 'admin:manage_payment', category: 'admin', description: 'Manage payment settings', roles: ['ceo'], riskLevel: 'critical' },
  { name: 'admin:financial_controls', category: 'admin', description: 'Financial controls', roles: ['ceo'], riskLevel: 'critical' },
  { name: 'admin:emergency_controls', category: 'admin', description: 'Emergency system controls', roles: ['ceo'], riskLevel: 'critical' },
];

const seedPermissions = async () => {
  try {
    await mongoose.connect(MONGO_URI);
    console.log('Connected to MongoDB');

    // Clear existing permissions
    await Permission.deleteMany({});
    console.log('Cleared existing permissions');

    // Insert all permissions
    const created = await Permission.insertMany(permissions);
    console.log(`✅ Seeded ${created.length} permissions`);

    // Display summary
    const byCategory = {};
    created.forEach((p) => {
      byCategory[p.category] = (byCategory[p.category] || 0) + 1;
    });

    console.log('\nPermissions by category:');
    Object.entries(byCategory)
      .sort()
      .forEach(([cat, count]) => {
        console.log(`  ${cat}: ${count}`);
      });

    const byRisk = {};
    created.forEach((p) => {
      byRisk[p.riskLevel] = (byRisk[p.riskLevel] || 0) + 1;
    });

    console.log('\nPermissions by risk level:');
    ['critical', 'high', 'medium', 'low'].forEach((level) => {
      if (byRisk[level]) console.log(`  ${level}: ${byRisk[level]}`);
    });

    process.exit(0);
  } catch (err) {
    console.error('❌ Seed failed:', err);
    process.exit(1);
  }
};

seedPermissions();
