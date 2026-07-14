# MSAS FarmAI - QA Credentials Secure Handover Guide

**CONFIDENTIAL - FOR QA TEAM ONLY**

---

## Overview

This document explains how to securely generate, distribute, and manage QA test account credentials for the MSAS FarmAI system audit and testing.

---

## Credential Generation

### Step 1: Generate QA Accounts
```bash
cd server
npm run seed:qa-accounts
```

**Output**: Will display 7 QA accounts (one per role) with:
- Username (phone number)
- Email address
- **Strong random password** (16+ characters with mixed case, numbers, symbols)

**Example Output Format:**
```
ROLE                PHONE               PASSWORD
farmer              +234801000001       aB3$xK9!mL2#pQ7@vW5%
vet                 +234701000002       Xq8!rT4$nE2@bJ6#mK9%
agronomist          +234701000003       wP5$hG1!dY8@jL3#xM6%
admin               +234801000004       fN7!vB2$kQ9@lR4#oS8%
agro-dealer         +234801000005       cM3$tY6!uH9@sA2#wD5%
extension-officer   +234801000006       zE4!pL7$gK5@fJ2#iO8%
ceo                 +234801000007       yR9!mW3$nV6@tX1#jZ4%
```

### Step 2: Copy to Secure Password Manager

**NEVER** store credentials in:
- ❌ Plaintext files
- ❌ Email messages
- ❌ Slack/Teams chat
- ❌ Version control (Git)
- ❌ Screenshots

**DO** use:
- ✅ 1Password
- ✅ LastPass
- ✅ Bitwarden
- ✅ KeePass (local only)
- ✅ Azure Key Vault (for enterprise)

---

## Credential Distribution

### For QA Team Members

**Via Secure Channel Only:**
1. **Option A**: 1Password shared vault (recommended)
   - Create shared collection: "MSAS QA Testing"
   - Add each credential with full access info
   - Grant access to QA team members

2. **Option B**: Secure password sharing link
   - Generate one-time link in 1Password / LastPass
   - Share link (not credentials) via encrypted email
   - Link expires after first use or time limit

3. **Option C**: Encrypted email
   - Use PGP encryption or system like ProtonMail
   - Share only the link/reference, not plaintext passwords
   - Confirm receipt and successful login

**NEVER distribute credentials:**
- In Slack/Teams
- In JIRA tickets
- In meeting notes
- Via SMS/WhatsApp
- In unencrypted email

---

## QA Account Access Levels

### By Role (for testing matrix):

| Role | Features to Test | Login Method | Status |
|------|------------------|--------------|--------|
| **Farmer** | Scan upload, diagnostics, marketplace | Phone + password | ✅ Immediate |
| **Vet** | Livestock cases, consultations, prescriptions | Email + password | ⏳ Needs admin approval |
| **Agronomist** | Crop cases, consultations, recommendations | Email + password | ⏳ Needs admin approval |
| **Admin** | User management, approvals, analytics | Email + password | ✅ Immediate |
| **Agro-Dealer** | Product inventory, orders, payouts | Email + password | ⏳ Needs admin approval |
| **Extension Officer** | Farmer support, area coverage | Email + password | ✅ Immediate |
| **CEO** | Full system access, emergency controls | Email + password | ✅ Immediate |

### Initial Testing Sequence:

1. **Day 1**: Test Farmer → Admin → CEO flows
2. **Day 2**: Get Vet & Agronomist approved by Admin
3. **Day 3**: Test expert consultation workflows
4. **Day 4**: Test Agro-Dealer marketplace features
5. **Day 5**: Test Extension Officer field workflows

---

## Testing Checklist

### Authentication Tests
- [ ] Each role can log in
- [ ] Incorrect password rejects login
- [ ] Session persists across page reload
- [ ] Logout clears session everywhere
- [ ] Can't access protected routes without auth

### Permission Tests (per role)
- [ ] Farmer: Can create farm, scan, view results
- [ ] Vet: Can see livestock cases, write prescriptions
- [ ] Agronomist: Can see crop cases, write recommendations
- [ ] Admin: Can approve experts, view analytics
- [ ] Agro-Dealer: Can list products, fulfill orders
- [ ] Extension Officer: Can view assigned farms
- [ ] CEO: Can access admin dashboard, all features

### Data Isolation Tests
- [ ] Farmer A can't see Farmer B's data
- [ ] Vet sees only livestock cases (not crops)
- [ ] Agronomist sees only crop cases (not livestock)
- [ ] Non-Admin can't modify users

### Feature-Specific Tests
- [ ] Expert case escalation routes correctly
- [ ] Marketplace product search works
- [ ] Offline queue syncs when online
- [ ] Image quality validation works
- [ ] Language switching (EN/Hausa) works

---

## Credential Lifecycle

### Pre-Production (Testing Phase)
```
Generate QA Accounts
    ↓
Distribute to QA Team
    ↓
Run Comprehensive Tests (5-7 days)
    ↓
Document Issues & Gaps
    ↓
Fix Issues
    ↓
Re-test Fixed Items
```

### Production Preparation
```
Before Go-Live:
    ↓
[ ] All QA accounts deleted from database
[ ] No test data in live system
[ ] No demo bypass code in production
[ ] Audit log clean of test entries
[ ] Credentials securely destroyed
    ↓
System Ready for Live Users
```

---

## Credential Rotation & Cleanup

### Daily (During Testing)
- ✅ Test each role at least once
- ✅ Document any permission issues
- ✅ Clear browser cache if needed

### After Testing Complete
1. **Export audit logs** showing all test actions
2. **Delete QA accounts** from database:
   ```bash
   # NOT YET - but will do before production
   npm run delete:qa-accounts
   ```
3. **Purge credentials** from password manager
4. **Delete this document** (after archiving reference copy)

### Before Production Launch
- ❌ **NO** test accounts in live database
- ❌ **NO** hardcoded demo credentials
- ❌ **NO** demo bypass middleware active
- ✅ All production safety checks enabled

---

## Emergency Access

If a QA team member's password needs reset:

1. **Ask Admin** (qa-admin@msas.test) to reset
2. **Admin logs in** → Users section → Find account
3. **Click "Reset Password"** → Generates new temporary password
4. **Admin sends** new password via 1Password/secure channel only
5. **Team member changes** password on first login

**Avoid sharing credentials verbally** - always use secure channel.

---

## Troubleshooting

### "Login Failed" - Could be:
- ❌ Wrong phone/email format
- ❌ Password with special characters (copy carefully)
- ❌ Account not yet approved (Vet/Agronomist)
- ❌ Backend service down (check API health)

**Test backend health:**
```bash
curl http://localhost:5000/api/health
# Expected: {"status":"ok","database":"connected"}
```

### "Permission Denied" - Could be:
- ❌ Feature restricted to admin only (use CEO account)
- ❌ Data ownership check failed
- ❌ Role not yet implemented
- 📝 This is a **bug to report!**

### "Session Expired" - Normal:
- JWT tokens expire after 7 days
- During testing: Re-login (accounts persist)
- In production: Users directed to login screen

---

## Reporting Issues Found

### Format for Bug Reports:

```
ROLE TESTED: [farmer/vet/admin/etc]
ACTION: [What were you trying to do?]
EXPECTED: [What should happen?]
ACTUAL: [What actually happened?]
STEPS TO REPRODUCE:
  1. Log in as [role]
  2. Navigate to [page]
  3. Click [button]
  4. Observe [error/unexpected behavior]
ENVIRONMENT: [localhost/staging]
SCREENSHOT: [if applicable]
```

### Report to:
- Slack: #qa-testing
- Jira: Create ticket under "MSAS-TESTING"
- Email: qa-lead@msas.local

---

## Data Privacy & Compliance

**Remember**: QA accounts should **NOT** contain:
- Real personal data
- Real phone numbers (use test +234 series)
- Real bank account details
- Real medical information

**All QA data is:**
- ✅ Isolated to test environment
- ✅ Will be deleted before production
- ✅ Not used for any real transactions
- ✅ Only for system validation

---

## Contact & Support

- **QA Lead**: [qa-lead@msas.local]
- **Dev Team**: [dev-team@msas.local]
- **Admin Contact**: [admin@msas.local]
- **Emergency**: [admin-on-call]

---

## Sign-Off

- Generated: 2026-06-16
- QA Team Lead Signature: _______________
- Date Approved: _______________
- Date QA Credentials Destroyed: _______________

---

**CONFIDENTIAL - FOR QA TEAM ONLY**  
**Destroy this document after QA phase is complete.**
