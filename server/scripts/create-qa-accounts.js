// server/scripts/create-qa-accounts.js
/**
 * Create QA demo accounts for all 7 roles
 * Generates strong passwords (stored hashed in DB)
 * Output: prints credentials for secure handover
 * 
 * Run: node scripts/create-qa-accounts.js
 */

require('dotenv').config();
const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');
const User = require('../models/User');

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/farmai';

// Generate strong random password
function generatePassword(length = 16) {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
  let password = '';
  for (let i = 0; i < length; i++) {
    password += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return password;
}

// QA Account definitions
const qaAccounts = [
  {
    role: 'farmer',
    displayName: 'QA Farmer Test',
    phone: '+234801000001',
    email: 'qa-farmer@msas.test',
    password: generatePassword(),
  },
  {
    role: 'vet',
    displayName: 'QA Vet Doctor',
    phone: '+234701000002',
    email: 'qa-vet@msas.test',
    password: generatePassword(),
    expertProfile: {
      licenseNumber: 'VET-QA-001',
      specialization: 'Cattle Health',
      yearsExperience: 10,
    },
  },
  {
    role: 'agronomist',
    displayName: 'QA Agronomist',
    phone: '+234701000003',
    email: 'qa-agronomist@msas.test',
    password: generatePassword(),
    expertProfile: {
      licenseNumber: 'AGR-QA-001',
      specialization: 'Crop Diseases',
      yearsExperience: 8,
    },
  },
  {
    role: 'admin',
    displayName: 'QA Admin',
    phone: '+234801000004',
    email: 'qa-admin@msas.test',
    password: generatePassword(),
    isVerified: true,
  },
  {
    role: 'agro-dealer',
    displayName: 'QA Agro Dealer',
    phone: '+234801000005',
    email: 'qa-dealer@msas.test',
    password: generatePassword(),
    dealerProfile: {
      businessName: 'QA Agro Supplies',
      storeAddress: 'QA District, Nigeria',
      productCategories: ['pesticide', 'fertilizer', 'veterinary'],
    },
  },
  {
    role: 'extension-officer',
    displayName: 'QA Extension Officer',
    phone: '+234801000006',
    email: 'qa-officer@msas.test',
    password: generatePassword(),
  },
  {
    role: 'ceo',
    displayName: 'QA CEO / Super Admin',
    phone: '+234801000007',
    email: 'qa-ceo@msas.test',
    password: generatePassword(),
    isVerified: true,
  },
];

const createQAAccounts = async () => {
  try {
    await mongoose.connect(MONGO_URI);
    console.log('Connected to MongoDB');

    // Clear existing QA accounts
    const deleted = await User.deleteMany({ email: { $regex: '@msas.test$' } });
    console.log(`Cleared ${deleted.deletedCount} existing QA accounts\n`);

    const credentials = [];

    // Create accounts
    for (const account of qaAccounts) {
      const hashedPassword = await bcrypt.hash(account.password, 10);

      const user = new User({
        name: account.displayName,
        phone: account.phone,
        email: account.email,
        password: hashedPassword,
        role: account.role,
        language: 'en',
        isVerified: account.isVerified || false,
        verificationStatus: account.expertProfile ? 'pending' : 'not_required',
        ...(account.expertProfile && { expertProfile: account.expertProfile }),
        ...(account.dealerProfile && { dealerProfile: account.dealerProfile }),
        isPremium: true, // QA accounts have all features
      });

      await user.save();

      credentials.push({
        role: account.role,
        username: account.phone,
        email: account.email,
        password: account.password, // Plain text for handover
        status: account.expertProfile ? 'Pending Approval' : account.isVerified ? 'Verified' : 'Active',
      });

      console.log(`✅ Created ${account.role}: ${account.displayName}`);
    }

    console.log('\n' + '='.repeat(70));
    console.log('QA CREDENTIALS FOR SECURE HANDOVER');
    console.log('='.repeat(70));
    console.log('\n⚠️  IMPORTANT: Store these credentials securely (password manager)');
    console.log('⚠️  DO NOT commit this output to version control\n');

    // Display table
    console.log('ROLE\t\tPHONE\t\t\tPASSWORD');
    console.log('-'.repeat(70));
    credentials.forEach((cred) => {
      console.log(
        `${cred.role.padEnd(15)}\t${cred.username.padEnd(18)}\t${cred.password}`
      );
    });

    console.log('\n' + '-'.repeat(70));
    console.log('EMAILS (for password reset testing):');
    console.log('-'.repeat(70));
    credentials.forEach((cred) => {
      console.log(`${cred.role}: ${cred.email}`);
    });

    console.log('\n' + '='.repeat(70));
    console.log('TESTING MATRIX');
    console.log('='.repeat(70));
    console.log(`
farmer:
  - Can create farms and scans
  - Can request vet/agronomist consultations
  - Can access marketplace

vet (qa-vet@msas.test):
  - Can view livestock diagnostics
  - Can respond to consultation requests
  - Requires approval before active (currently: ${credentials[1].status})

agronomist (qa-agronomist@msas.test):
  - Can view crop diagnostics
  - Can respond to consultation requests
  - Requires approval before active (currently: ${credentials[2].status})

admin (qa-admin@msas.test):
  - Full access to user management
  - Can approve/reject experts
  - Can view analytics and reports
  - Can manage platform settings

agro-dealer (qa-dealer@msas.test):
  - Can list and manage products
  - Can view orders and payouts
  - Can see marketplace analytics

extension-officer (qa-officer@msas.test):
  - Can support farmers in assigned areas
  - Can access farm records and diagnostics
  - Limited management capabilities

ceo (qa-ceo@msas.test):
  - FULL SYSTEM ACCESS
  - Financial controls
  - Emergency override capabilities
    `);

    console.log('='.repeat(70));
    console.log('NEXT STEPS:');
    console.log('='.repeat(70));
    console.log(`
1. Copy credentials to secure password manager (1Password, LastPass, etc.)
2. Distribute passwords to QA team ONLY through encrypted channel
3. BEFORE PRODUCTION: Delete these QA accounts (they are flagged as test)
4. Test each role to verify permissions work correctly
5. Document any permission gaps found

Credentials generated: ${new Date().toISOString()}
    `);

    process.exit(0);
  } catch (err) {
    console.error('❌ Failed to create QA accounts:', err.message);
    process.exit(1);
  }
};

createQAAccounts();
