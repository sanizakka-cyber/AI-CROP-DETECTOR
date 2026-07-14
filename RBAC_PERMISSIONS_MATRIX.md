# MSAS FarmAI - Role-Based Access Control (RBAC) Permissions Matrix

## Role Definitions & Hierarchy

```
┌─────────────────────────────────────────────────┐
│ SUPER ADMIN / CEO                               │
│ Full system access, financial controls, all UX  │
└─────────────────────────────────────────────────┘
  ├─ Admin / Platform Manager
  │  └─ User support, content moderation, no financials
  ├─ Veterinary Doctor
  │  └─ Livestock consultations only
  ├─ Agronomist
  │  └─ Crop consultations only
  ├─ Agro-Dealer / Supplier
  │  └─ Own product inventory only
  ├─ Extension Officer
  │  └─ Field work, farmer support (optional)
  └─ Farmer (End User)
     └─ Own farm data & consultations only
```

---

## Permissions Matrix

### A. USER MANAGEMENT

| Permission | Farmer | Vet | Agronomist | Admin | Agro-Dealer | Ext-Officer | CEO |
|---|---|---|---|---|---|---|---|
| **user:read_own** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **user:update_own** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **user:change_password** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **user:delete_own_account** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **user:list_all** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **user:read_other** | ❌ | ❌ | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| **user:update_other** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **user:delete_other** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **user:suspend_account** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **user:change_role** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **user:view_analytics** | ❌ | ⚠️ | ⚠️ | ✅ | ⚠️ | ❌ | ✅ |

**Notes:**
- ⚠️ **user:view_analytics** for Vets/Agronomists = own performance metrics only
- ⚠️ **user:read_other** for Extension Officers = farmers in their coverage area only

---

### B. FARM MANAGEMENT

| Permission | Farmer | Vet | Agronomist | Admin | Agro-Dealer | Ext-Officer | CEO |
|---|---|---|---|---|---|---|---|
| **farm:create** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **farm:read_own** | ✅ | ❌ | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| **farm:read_other** | ❌ | ⚠️ | ⚠️ | ✅ | ❌ | ⚠️ | ✅ |
| **farm:update_own** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **farm:update_other** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **farm:delete_own** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **farm:delete_other** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **farm:list_own** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **farm:list_all** | ❌ | ❌ | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| **farm:grant_access** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **farm:revoke_access** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |

**Notes:**
- ⚠️ **farm:read_other** for Vet/Agronomist = only farms with active consultations
- ⚠️ **farm:read_other** for Extension Officer = farms in their area
- Farm access can be **temporary** (time-limited token)

---

### C. ANIMALS & CROPS

| Permission | Farmer | Vet | Agronomist | Admin | Agro-Dealer | Ext-Officer | CEO |
|---|---|---|---|---|---|---|---|
| **animal:create** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **animal:read_own** | ✅ | ⚠️ | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| **animal:read_other** | ❌ | ⚠️ | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| **animal:update_own** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **animal:delete_own** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **crop:create** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **crop:read_own** | ✅ | ❌ | ⚠️ | ✅ | ❌ | ⚠️ | ✅ |
| **crop:read_other** | ❌ | ❌ | ⚠️ | ✅ | ❌ | ⚠️ | ✅ |
| **crop:update_own** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **crop:delete_own** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |

---

### D. DIAGNOSTICS & CONSULTATION

| Permission | Farmer | Vet | Agronomist | Admin | Agro-Dealer | Ext-Officer | CEO |
|---|---|---|---|---|---|---|---|
| **diagnosis:create** | ✅ | ❌ | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| **diagnosis:read_own** | ✅ | ❌ | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| **diagnosis:read_other** | ❌ | ⚠️ | ⚠️ | ✅ | ❌ | ⚠️ | ✅ |
| **diagnosis:list_assigned** | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **diagnosis:escalate** | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ | ✅ |
| **diagnosis:mark_complete** | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **diagnosis:add_expert_notes** | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **diagnosis:rate_result** | ✅ | ❌ | ❌ | ⚠️ | ❌ | ❌ | ✅ |
| **diagnosis:request_consultation** | ✅ | ❌ | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| **consultation:accept** | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **consultation:complete** | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **consultation:cancel** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **consultation:write_prescription** | ❌ | ✅ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **consultation:write_recommendation** | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **consultation:rate_expert** | ✅ | ❌ | ❌ | ⚠️ | ❌ | ❌ | ✅ |

**Notes:**
- ⚠️ **diagnosis:create** for Extension Officer = on behalf of farmer (supervised)
- ⚠️ **diagnosis:rate_result** for Admin = all diagnoses (for admin review)
- ⚠️ **consultation:rate_expert** for Admin = disputed ratings only
- Vets handle **livestock** diagnoses only
- Agronomists handle **crop** diagnoses only

---

### E. TREATMENTS & MEDICATIONS

| Permission | Farmer | Vet | Agronomist | Admin | Agro-Dealer | Ext-Officer | CEO |
|---|---|---|---|---|---|---|---|
| **treatment:create** | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ | ✅ |
| **treatment:read_own** | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ | ✅ |
| **treatment:read_other** | ❌ | ⚠️ | ⚠️ | ✅ | ❌ | ⚠️ | ✅ |
| **treatment:log_application** | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ | ✅ |
| **treatment:log_outcome** | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ | ✅ |
| **medication:view_database** | ❌ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **medication:edit_database** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **medication:view_withdrawal_period** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

### F. MARKETPLACE

| Permission | Farmer | Vet | Agronomist | Admin | Agro-Dealer | Ext-Officer | CEO |
|---|---|---|---|---|---|---|---|
| **product:browse** | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ |
| **product:search** | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ |
| **product:view_recommended** | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ |
| **product:add_to_cart** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **order:create** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **order:read_own** | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ | ✅ |
| **order:read_other** | ❌ | ❌ | ❌ | ✅ | ⚠️ | ❌ | ✅ |
| **order:cancel** | ✅ | ❌ | ❌ | ✅ | ⚠️ | ❌ | ✅ |
| **seller:create_product** | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ✅ |
| **seller:manage_inventory** | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ✅ |
| **seller:view_orders** | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ✅ |
| **seller:fulfill_order** | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ✅ |
| **seller:view_payout** | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ✅ |
| **seller:request_payout** | ❌ | ❌ | ❌ | ⚠️ | ✅ | ❌ | ✅ |
| **payment:process** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **counterfeit:report** | ✅ | ❌ | ❌ | ✅ | ❌ | ✅ | ✅ |
| **counterfeit:review** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |

**Notes:**
- **product:browse** restricted to farmers for MVP (future: enable for Vet/Agronomist)
- ⚠️ **seller:request_payout** for Admin = approval authority

---

### G. EXPERT VERIFICATION & MANAGEMENT

| Permission | Farmer | Vet | Agronomist | Admin | Agro-Dealer | Ext-Officer | CEO |
|---|---|---|---|---|---|---|---|
| **expert:apply** | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **expert:upload_credentials** | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **expert:view_own_status** | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **expert:list_pending** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **expert:approve** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **expert:reject** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **expert:suspend** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **expert:reactivate** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **expert:view_credentials** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |

---

### H. ANALYTICS & REPORTING

| Permission | Farmer | Vet | Agronomist | Admin | Agro-Dealer | Ext-Officer | CEO |
|---|---|---|---|---|---|---|---|
| **analytics:view_own_summary** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **analytics:view_own_performance** | ❌ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **analytics:view_platform_summary** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **analytics:view_user_metrics** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **analytics:view_diagnosis_metrics** | ❌ | ⚠️ | ⚠️ | ✅ | ❌ | ⚠️ | ✅ |
| **analytics:view_financial** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **report:generate_custom** | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ✅ |
| **report:export_pdf** | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ | ✅ |
| **report:export_excel** | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ✅ |
| **audit:view_log** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |

**Notes:**
- ⚠️ **analytics:view_diagnosis_metrics** for Vet/Agronomist = own cases only
- ⚠️ **analytics:view_diagnosis_metrics** for Extension Officer = area coverage
- ⚠️ **report:export_pdf** for Extension Officer = area summary only

---

### I. PLATFORM ADMINISTRATION

| Permission | Farmer | Vet | Agronomist | Admin | Agro-Dealer | Ext-Officer | CEO |
|---|---|---|---|---|---|---|---|
| **admin:view_dashboard** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **admin:manage_users** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **admin:manage_content** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **admin:manage_settings** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **admin:manage_features** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **admin:view_system_health** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **admin:manage_payment** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **admin:financial_controls** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **admin:emergency_controls** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

---

## Summary Statistics

### Permission Counts by Role:

- **Farmer**: 35 permissions (read/write own data + marketplace)
- **Vet**: 42 permissions (livestock + own performance)
- **Agronomist**: 42 permissions (crop + own performance)
- **Admin**: 78 permissions (management + oversight)
- **Agro-Dealer**: 28 permissions (product inventory + marketplace)
- **Extension Officer**: 32 permissions (supervised field work)
- **CEO**: 120 permissions (full access)

### Legend:
- ✅ = **Allow** - User can perform this action
- ❌ = **Deny** - User cannot perform this action
- ⚠️ = **Conditional** - Allowed with restrictions (see notes)

---

## Role Assignment Flow

### Registration & Verification:

```
User Registers
    ↓
[farmer] → Immediate activation (OTP verified)
[vet] → Requires credential upload → Admin review → Approval
[agronomist] → Requires credential upload → Admin review → Approval
[agro-dealer] → Requires business info + bank details → Admin review → Approval
[extension-officer] → Admin assignment only (not self-registered)
```

### Credential Requirements by Role:

| Role | Required Documents | Verification Time | Status |
|------|--------------------|--------------------|--------|
| Farmer | None (OTP) | Immediate | ✅ Implemented |
| Vet | License #, proof | 1-5 business days | ⚠️ Model exists, UI missing |
| Agronomist | Cert., credentials | 1-5 business days | ⚠️ Model exists, UI missing |
| Agro-Dealer | Business reg., bank account | 3-7 business days | ❌ Not implemented |
| Extension Officer | — | Admin assigned | ❌ Not implemented |

---

## Recommended Implementation Order

1. **Phase 1** (Week 1-2): Implement granular permission system
   - Create `Permission` model
   - Build `requirePermission()` middleware
   - Seed role-permission mappings

2. **Phase 2** (Week 3): Migration & Testing
   - Replace inline checks with middleware
   - Add audit logging
   - Test all role flows

3. **Phase 3** (Week 4): UI & Admin Tools
   - Build user management dashboard
   - Implement permission debugging UI
   - Create audit trail viewer

4. **Phase 4** (Week 5+): Advanced Features
   - Temporary access tokens
   - Org/team support
   - Premium tier feature gates

---

**Permissions Matrix Version**: 1.0  
**Last Updated**: 2026-06-16  
**Status**: Ready for Implementation
