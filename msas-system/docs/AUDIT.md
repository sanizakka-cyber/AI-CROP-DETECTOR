# MSAS FarmAI — Role, Permission & Dashboard Audit

Living document for the system-wide role-based dashboard synchronization effort. Updated at the end of each phase — do not treat any phase as complete until this file reflects it.

## Phase Tracker

| Phase | Scope | Status |
|---|---|---|
| 1 | Role/permission/dashboard/menu ground-truth audit (this document) | **Complete** — 2026-08-17 |
| 2 | Security: fix confirmed cross-user diagnosis leak, add per-record authorization | Not started |
| 3 | Permission-system reconciliation (pick one authoritative system) | Not started |
| 4 | Dead-button sweep + shared-component consolidation | Not started |
| 5 | UI contrast / mobile responsiveness pass | Not started |
| 6 | CEO analytics / location dropdown / voice-narration reach expansion (if approved) | Not started |

No code changes have been made as part of Phase 1. Everything below is factual inventory, gathered by reading the codebase directly (file:line references throughout), not a proposal.

---

## 1. All Roles Discovered

The `users.role` column is a plain `string` (`0001_01_01_000003_add_role_to_users.php:8`), default `'farmer'` — there is no DB-level enum, so nothing prevents an arbitrary string being stored.

| Role | Self-registerable | Needs manual approval after registering | Assignable internally (`CEOController::$allRoles` / `StaffController`) |
|---|---|---|---|
| farmer | ✅ | No (auto-active) | ✅ |
| general-user | ✅ | No (auto-active) | ✅ |
| vet | ✅ | Yes | ✅ |
| agronomist | ✅ | Yes | ✅ |
| agro-dealer | ✅ | Yes | ✅ |
| equipment-dealer | ✅ | Yes | ✅ |
| agribusiness-owner | ✅ | Yes | ✅ |
| cooperative | ✅ | Yes | ✅ |
| government-agency (alias: `government`) | ✅ | Yes | ✅ |
| ngo | ✅ | Yes | ✅ |
| research-institution (alias: `researcher`) | ✅ | Yes | ✅ |
| input-supplier | ✅ | Yes | ✅ |
| logistics-provider | ✅ | Yes | ✅ |
| investor | ✅ | Yes | ✅ |
| extension-officer | ❌ | — | ✅ |
| field-officer | ❌ | — | ✅ |
| data-analyst | ❌ | — | ✅ |
| monitoring-evaluation (aliases: `m-e-officer`, `me-officer`) | ❌ | — | ✅ (only `m-e-officer` spelling listed) |
| customer-support | ❌ | — | ✅ |
| hr | ❌ | — | ✅ |
| finance | ❌ | — | ✅ |
| operations | ❌ | — | ✅ |
| admin | ❌ | — | ✅ |
| ceo | ❌ | — | ✅ |
| financial-institution | ❌ | — | ⚠️ **gap** — has a dashboard but is in neither the self-register list nor `CEOController::$allRoles` |
| rider | ❌ | — | ⚠️ **gap** — same issue, only settable by direct role-string edit |
| student | ❌ | — | ⚠️ Referenced in `RoleMiddleware`'s redirect map but not in any assignable-role list found |

**Findings:**
- `government`/`government-agency`, `researcher`/`research-institution`, and `student`/`general-user` are synonym pairs mapped to the same dashboard — inconsistent naming, not a functional bug today, but a trap for future code that checks one spelling and not the other.
- `monitoring-evaluation` has **three** spellings in circulation (`monitoring-evaluation`, `m-e-officer`, `me-officer`) across `RoleMiddleware.php` vs. `CEOController::$allRoles` — a role assigned under one spelling could fail a `role:` check written with another.
- `financial-institution` and `rider` have working dashboards and route middleware but no first-class way to assign the role through the CEO UI — currently only fixable by a direct database edit.
- All 12 non-farmer/general-user self-registerable roles go through `application_status = pending` manual approval (`RegisteredUserController.php:95`) before activation — this is intentional gatekeeping, not a bug.

---

## 2. Dashboard Inventory

`DashboardController::dispatch()` (`DashboardController.php:13-52`) is the single entry point (`route('dashboard')`) that routes every logged-in user to their role's dashboard via `match($user->role)`. Any role not in the match list falls through to a generic `view('dashboard')`.

| Role(s) | Method | View | View size |
|---|---|---|---|
| farmer, student, general-user | `farmer()` | `farmer/dashboard.blade.php` | 554 lines |
| vet | `vet()` | `vet/dashboard.blade.php` | 106 |
| agronomist | `agronomist()` | `agronomist/dashboard.blade.php` | 104 |
| agro-dealer | `dealer()` | `dealer/dashboard.blade.php` | 110 |
| equipment-dealer | `equipmentDealer()` | `equipment-dealer/dashboard.blade.php` | 67 |
| cooperative | `cooperative()` | `cooperative/dashboard.blade.php` | 121 |
| ngo | `ngo()` | `ngo/dashboard.blade.php` | 113 |
| government, government-agency | `government()` | `government/dashboard.blade.php` | 193 |
| research-institution, researcher | `researchInstitution()` | `research-institution/dashboard.blade.php` | 146 |
| investor | `investor()` | `investor/dashboard.blade.php` | 135 |
| financial-institution | `financialInstitution()` | `financial-institution/dashboard.blade.php` | 132 |
| logistics-provider | `logistics()` | `logistics/dashboard.blade.php` | 111 |
| agribusiness-owner | `agribusiness()` | `agribusiness-owner/dashboard.blade.php` | 97 |
| input-supplier | `inputSupplier()` | `input-supplier/dashboard.blade.php` | 112 |
| extension-officer | `extension()` | `extension/dashboard.blade.php` | 101 |
| finance | `finance()` | `finance/dashboard.blade.php` | 118 |
| hr | `hr()` | `hr/dashboard.blade.php` | 102 |
| operations | `operations()` | `operations/dashboard.blade.php` | 123 |
| data-analyst | `dataAnalyst()` | `data-analyst/dashboard.blade.php` | 212 |
| monitoring-evaluation, m-e-officer, me-officer | `monitoringEvaluation()` | `monitoring-evaluation/dashboard.blade.php` | 243 |
| field-officer | `fieldOfficer()` | `field-officer/dashboard.blade.php` | 155 |
| customer-support | `customerSupport()` | `customer-support/dashboard.blade.php` | 167 |
| admin | `admin()` | `admin/dashboard.blade.php` | 220 |
| rider | `rider()` → delegates to `RiderController::dashboard()` | `rider/dashboard.blade.php` | not measured |
| ceo | *(not in DashboardController — `/ceo` redirects to `ceo/overview`)* | `ceo/pages/overview.blade.php` + 7 sibling pages | see prior CEO dashboard split work |

**Findings:**
- Every dashboard method wraps its stat queries in `try/catch` returning `0`/`collect()` on failure — consistent defensive pattern, but it means a broken query silently shows zero instead of surfacing an error. Relevant to Phase 4/5 ("no empty white page on API failure" / "distinguish loading, empty, error").
- Each dashboard's stat cards and markup are hand-written per view — no shared "stat card" or "recent activity" component exists (confirmed in section 8), so the same visual bug (e.g. the CEO KPI-pill contrast issue fixed earlier this session) could independently exist in some subset of these 22 views without being connected to each other.
- CEO is architecturally different from the other 22 — a multi-page BI suite, not a single `dashboard()` method — already addressed in the prior CEO dashboard split.

---

## 3. Menu Availability Per Role

**Two independent navigation implementations exist and can drift out of sync:**

1. **`resources/views/layouts/app.blade.php`** — the sidebar used by `<x-app-layout>` (148 views, essentially the whole authenticated app). Role gating is a long chain of `@if($role === 'x')` / `in_array($role, [...])` blocks (`app.blade.php:156` onward), not a config array or policy:
   - AI Smart Scan link: `farmer, admin, ceo, vet, agronomist` (`app.blade.php:147`) — matches the `diagnostics.*` route gate exactly.
   - CEO/Admin Management section (`159`): Users & Staff; CEO-only (`165`): Staff Management, Staff Roles, Monitoring, BI, Pilot, Feedback, Invite Codes, Support, Broadcast, Audit Log, Referrals, NPS. Shared ceo+admin: Reports, Applications, Subscriptions.
   - Per-role sections: farmer (`244`), vet (`275`), agronomist (`292`), finance (`305`), hr (`322`), agro-dealer (`335`), equipment-dealer (`348`), logistics-provider (`361`), agribusiness-owner (`378`), input-supplier (`387`).
   - "My Plan" (subscription) link shown to everyone except `ceo, admin, general-user` (`396`) — matches `RequireSubscription`'s bypass list exactly (see section 6).
   - "General" section (Marketplace, My Profile) shown to all roles unconditionally (`416`).

2. **`resources/views/layouts/navigation.blade.php`** — a *separate* top-nav used on non-`x-app-layout` pages, with its **own independent** role→dashboard `match()` (`navigation.blade.php:30-63`) and its own set of role-gated dropdowns: ceo (`73`), admin (`152`), finance (`199`), rider (`205`), farmer (`212`), vet/agronomist (`228`), hr — visible to `hr, admin, ceo` (`235`). Wallet, AI Scan, Marketplace, and Notifications are shown to all roles.

**Finding — flagged for Phase 4:** because these two files maintain independent role-to-menu-item mappings, a role or menu item added to one is not guaranteed to be reflected in the other. This is the single biggest "shared component" risk found in the whole audit — it directly matches the request's concern about the same bug appearing in one dashboard after being fixed in another, except here it's two *navigation systems*, not two dashboards.

**Dead links found during this pass** (present in `app.blade.php`, `href="#"`, not wired to any route):
- Agronomist section (`app.blade.php:292`): "Crop Requests", "Soil Reports"
- Finance section (`app.blade.php:305`): "Income & Expenses", "Payroll", "Financial Reports"
- HR section (`app.blade.php:322`): "Staff Records", "Attendance & Leave"

These are exactly the kind of "decorative buttons that appear functional but do nothing" flagged in the request. Not fixed in this phase (documentation only) — carried into Phase 4.

---

## 4. Permission Systems — three overlapping mechanisms

No permission package is installed (`composer.json` has no `spatie/laravel-permission` or equivalent). Three systems coexist and can disagree for the same user:

**(a) `RoleMiddleware` — the primary mechanism, used in 51 routes.** Coarse string comparison: `role:x,y,z` middleware checks `in_array($user->role, $roles)` (`RoleMiddleware.php` full logic). No wildcards, no hierarchy, no inheritance. On failure, redirects to a per-role fallback dashboard route (a ~29-entry map) or aborts 403.

**(b) Legacy `role_permissions` table + `PermissionMiddleware`.** A `role→permission` table (`2026_06_16_create_permissions_tables.php`), seeded by `RolePermissionSeeder`, checked via `permission:action_name` middleware, cached 1h. **Used in only 3 routes** (`web.php:274,275,277` — admin user suspend/delete/settings). `ceo` bypasses this system entirely.

**(c) Granular RBAC (`StaffRole`) — the most modern system, also the least used.** Permissions stored as JSON `{module: [abilities]}` on a `StaffRole` model, assigned to users via a `staff_role_assignments` pivot, checked via `User::hasRbacPermission($module, $ability)`. Fully built out (CRUD UI, audit logging via `RbacAuditLog`) but wired into almost nothing outside its own management routes (`ceo`-only `staff-roles.*`/`staff.*`).

**Net finding:** access control in this app is **primarily the blunt `role:` string middleware**. The other two systems are real, functional, but nearly unused — meaning most of the codebase has no path to fine-grained permissions at all today. Building "the central permission system" the request asks for (section 20 of the original prompt) is not a small addition — it means picking one of these three as authoritative and either migrating or removing the other two, which touches every `role:` middleware call in `routes/web.php`. **This is proposed as its own phase (Phase 3), not something to fold into a smaller fix.**

---

## 5. Access Control / Security Findings

These are confirmed by reading the code, not yet fixed. Ranked by severity.

1. **🔴 HIGH — Cross-user diagnosis data exposure.** `VetController.php:176`: any user with role `vet` or `agronomist` can browse **all** farmers' diagnoses, filtered only by scan type (`plant` vs `animal`), not by assignment or authorization. There is no `DiagnosisPolicy` anywhere in the app — the ownership check added to `DiagnosticController::downloadReport()` earlier this session is currently the *only* per-record diagnosis authorization check that exists. `VetController.php:156` similarly runs an unfiltered outbreak aggregation across all users' diagnoses (aggregate-only, lower severity, but still worth reviewing for what it exposes). **Proposed for Phase 2.**
2. **🟠 MEDIUM-HIGH — Unauthenticated payment lookup.** `GET /payment/mobile-callback` (`routes/api.php:289-291`, `PaymentApiController::mobileCallback` at line 120) has no auth middleware — only `throttle:20,1` — and returns full payment details to anyone who supplies a valid Paystack `reference`/`trxref` query parameter, with no check that the caller is the payment's owner. References are not typically guessable, but this is still an ownership-check gap on sensitive financial data. **Proposed for Phase 2.**
3. **🟡 LOW — Role-string typos/aliases create silent authorization gaps**, not leaks: e.g. a user assigned `me-officer` when a `role:` check was written for `m-e-officer` would simply be locked out of their own dashboard, not exposed to someone else's data. Documented in section 1; fix belongs in Phase 3 (permission reconciliation), since it's really a symptom of there being no canonical role list.
4. **✅ Notifications correctly scoped.** Both notification stacks (`NotificationController` web, `NotificationApiController` mobile) filter every read/write by `user_id` — no leak found, no action needed.
5. **✅ Narration/TTS correctly scoped.** `NarrationController.php:23` enforces `$diagnosis->user_id === auth()->id()` — matches the ownership pattern, no action needed.
6. **Marketplace/dealer API ownership checks are pushed into controllers, not route middleware** (e.g. `ProductApiController.php:94,108,148,186,219` manually checking `$product->dealer_id !== $user->id`) — functional today, confirmed by reading each check, but inconsistent with the request's stated preference for backend enforcement that doesn't rely on scattered manual checks. Not a confirmed bug, but a pattern worth consolidating into policies during Phase 3.

---

## 6. Marketplace, Subscription & API Access Matrix

**Marketplace:**
- Browse/buy: any authenticated role, no role restriction (`routes/web.php:375-390`, `MarketplaceController`).
- Sell: any authenticated + **subscribed** role (`web.php:465`, `auth + subscription` middleware, `MarketplaceSellController`).
- Dealer-specific catalogs: `equipment-dealer` (`web.php:98`) and `agro-dealer` (`web.php:111`) only, both also require an active subscription.
- Order oversight: `ceo` (`web.php:336-338`), `admin,ceo` (`web.php:299-312`), payout approval `admin,ceo,finance` (`web.php:484`).

**Subscription:** `RequireSubscription` middleware (`RequireSubscription.php:22`) hard-codes a bypass list — `ceo, admin, general-user` never need a plan. Every other role hitting a `subscription`-gated route is redirected to the plans page (or gets a 402) if no active subscription exists. Farmer routes are *not* gated at the route level; instead `SubscriptionLimitService` enforces per-feature usage caps in-controller (scan count, consultation count).

**API (`routes/api.php`):** mobile app backend, Sanctum-authenticated (`auth.api`) for the bulk of routes (109-286: notifications, diagnose, farms, animals, marketplace, orders, subscriptions, messages, consultations, rider, wallet, payments). No further role middleware inside that block — every ownership/role check happens inside the controller. `GET /api/health` is intentionally public but gates detailed diagnostics behind a bearer token restricted to `ceo, admin, operations, finance, data-analyst`. `GET /payment/mobile-callback` and `GET /track/{orderNumber}` are intentionally public (order tracking, payment callback) — the former's missing ownership check is flagged in section 5.

---

## 7. Shared Components Inventory

- `resources/views/layouts/app.blade.php` — the shared authenticated shell, used by all 148 role-facing views via `<x-app-layout>`. Every dashboard, including all 9 CEO pages, shares this one layout.
- `resources/views/layouts/navigation.blade.php` — a **second, independent** nav implementation (see section 3 finding).
- `resources/views/components/*` — generic UI atoms only (buttons, modal, dropdown, inputs, nav-link). **No dashboard-specific shared component exists** — no shared stat-card, no shared "recent scans" list, no shared diagnostic-report or voice-player partial reused across roles. Each of the 22 dashboards hand-duplicates its own version of these.
- Diagnostics/voice narration UI (`diagnostics/scan.blade.php`, `diagnostics/history.blade.php`) is not componentized — it exists as one page reachable only by the 5 roles in the `diagnostics.*` route group, not as a reusable Blade component other dashboards could opt into.

**Implication for later phases:** the request's item 3 ("use shared components wherever possible... Shared AI Scan Component → Farmer/Expert/Staff/CEO/Admin Dashboard") describes an architecture that **does not exist yet** for anything except the outer layout. Building it is a real engineering task (extracting a Blade component, deciding its data contract, retrofitting the 5 roles that currently have inline scan/report markup), not a find-and-replace. Proposed as part of Phase 4, scoped to whichever roles are confirmed to need scan access beyond the current 5.

---

## 8. Voice Narration / TTS Reach

Confirmed fully isolated to one page: `diagnostics/history.blade.php`, reachable only by `farmer, admin, ceo, vet, agronomist` (same route group as `diagnostics.scan`/`diagnostics.history`, `routes/web.php:61`). `DiagnosisNarration`/`TtsService` are referenced nowhere else. This means 18 of the 24 roles have no voice narration to "correct" today — there's simply no UI surface for it. If broader access is wanted, that's a scope decision (which roles should see scan reports at all) rather than a bug fix.

---

## 9. Recommendations for Sequencing

Given the findings above, the original 29-section request breaks down into genuinely distinct pieces of work with different risk profiles:

- **Phase 2 (security, no UI risk):** Fix the VetController diagnosis leak with a proper ownership/assignment check; add ownership check to the payment mobile-callback endpoint. Small, contained, high value, low risk of breaking other roles.
- **Phase 3 (foundational, higher risk):** Reconcile the three permission systems into one authoritative source, fix the role-spelling inconsistencies, decide the `financial-institution`/`rider` assignability gap. Touches `routes/web.php` broadly — needs careful regression testing per role since there's no test suite and no local PHP available to lint/run this app.
- **Phase 4 (consolidation):** Merge the two navigation implementations into one, remove the confirmed dead links, build the first real shared dashboard component (starting with whichever is cheapest — likely a stat-card partial — before attempting a shared scan/report component).
- **Phase 5 (visual/mobile):** Contrast, dead-button sweep beyond navigation, mobile responsiveness — best done role-by-role once Phase 4's shared components exist, so a fix lands once instead of 22 times.
- **Phase 6 (scope decision required):** Whether to expand scan/voice/report access to roles beyond the current 5 is a product decision, not implied by anything currently broken — flagged for explicit sign-off before any code changes.

No code has been changed in this phase. Next step is agreeing which phase to execute first.
