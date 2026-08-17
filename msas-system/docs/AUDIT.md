# MSAS FarmAI — Role, Permission & Dashboard Audit

Living document for the system-wide role-based dashboard synchronization effort. Updated at the end of each phase — do not treat any phase as complete until this file reflects it.

## Phase Tracker

| Phase | Scope | Status |
|---|---|---|
| 1 | Role/permission/dashboard/menu ground-truth audit (this document) | **Complete** — 2026-08-17 |
| 2 | Security: verify the two Phase 1 findings against actual exploitability | **Complete** — 2026-08-17, both downgraded, no code change (see §5) |
| 3 | Permission-system reconciliation (pick one authoritative system) | **Closed** 2026-08-17 — role-spelling/assignment-gap items resolved; the 3 systems aren't causing a live bug, left as documented debt per your call |
| 4 | Dead-button sweep + shared-component consolidation | **Complete** 2026-08-17 — all 7 dead nav links wired to real routes; the "second navigation system" was confirmed dead code and deleted rather than merged. Shared dashboard/scan component work deferred (see §9) |
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
| financial-institution | ❌ | — | ✅ **fixed Phase 3** — added to `CEOController::$allRoles` (was previously only in QA seeder test data, no real admin assignment path) |
| rider | ❌ | — | ✅ has its own dedicated flow — `Admin\RiderManagementController::store()` creates riders with `role => 'rider'` directly, plus a full `admin.riders.*` management UI. Not a gap; correctly excluded from the generic `$allRoles` editor since riders carry extra fields (`vehicle_type`, `rider_status`) that dedicated flow handles. **Phase 1 mischaracterized this as a gap** — corrected here. |
| student | ❌ | — | ⚠️ Referenced in `RoleMiddleware`'s redirect map but not in any assignable-role list found |

**Findings:**
- `government`/`government-agency`, `researcher`/`research-institution`, and `student`/`general-user` are synonym pairs mapped to the same dashboard — inconsistent naming, not a functional bug today, but a trap for future code that checks one spelling and not the other.
- **Corrected in Phase 3** — the "three spellings" of `monitoring-evaluation` flagged in the original Phase 1 pass is not a live bug. Every actual *assignment* site (`TestAccountsSeeder`, `StaffAccountsSeeder`, `QAAccountsSeeder`, `admin/users.blade.php`'s role dropdown, `CEOController::$allRoles`, `CEO\StaffController`) uses only `m-e-officer` — that is the sole value ever written to `users.role` for this role. `monitoring-evaluation` and `me-officer`/`me_officer` appear only as defensive extra aliases inside `role:` middleware lists and label-lookup arrays (`RoleMiddleware.php:47-49`, `User.php:113,144-146`) — harmless belt-and-suspenders code, not a source of silent lockouts today. Worth normalizing eventually so a future `role:` check copied from the wrong list doesn't miss `m-e-officer`, but it is not urgent.
- **Fixed in Phase 3** — `financial-institution` had a working dashboard but no real admin assignment path (only QA seeder test data ever set it). Added to `CEOController::$allRoles` (`CEOController.php:687-693`).
- **Corrected in Phase 3** — `rider` was mischaracterized as having the same gap. It doesn't: `Admin\RiderManagementController::store()` (`RiderManagementController.php:82`) creates riders directly with a full `admin.riders.*` management UI, correctly kept separate from the generic role editor since riders carry extra fields (`vehicle_type`, `rider_status`) that flow handles.
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

**Corrected in Phase 4 — there is only one live navigation system, not two.** The Phase 1 pass found a second file, `resources/views/layouts/navigation.blade.php`, with its own independent role-gated menu logic, and treated it as a second live implementation that could drift out of sync with the first. Investigation before attempting a "merge" found `layouts/navigation.blade.php` is **referenced nowhere in the codebase** — no `@include`, no Blade component tag, no `view()` call, in `app/`, `resources/`, `routes/`, or `tests/`. It was Laravel Breeze scaffolding, superseded by the custom sidebar below, and never removed — git history shows it was still being edited during unrelated passes (e.g. an emoji-removal sweep) purely because nobody realized it was dead. **Deleted in Phase 4** rather than merged, since there was nothing live to merge into.

**`resources/views/layouts/app.blade.php`** is the one real navigation system — the sidebar used by `<x-app-layout>` (148 views, essentially the whole authenticated app). Role gating is a long chain of `@if($role === 'x')` / `in_array($role, [...])` blocks (`app.blade.php:156` onward), not a config array or policy:
- AI Smart Scan link: `farmer, admin, ceo, vet, agronomist` (`app.blade.php:147`) — matches the `diagnostics.*` route gate exactly.
- CEO/Admin Management section (`159`): Users & Staff; CEO-only (`165`): Staff Management, Staff Roles, Monitoring, BI, Pilot, Feedback, Invite Codes, Support, Broadcast, Audit Log, Referrals, NPS. Shared ceo+admin: Reports, Applications, Subscriptions.
- Per-role sections: farmer (`244`), vet (`275`), agronomist (`292`), finance (`305`), hr (`322`), agro-dealer (`335`), equipment-dealer (`348`), logistics-provider (`361`), agribusiness-owner (`378`), input-supplier (`387`).
- "My Plan" (subscription) link shown to everyone except `ceo, admin, general-user` (`396`) — matches `RequireSubscription`'s bypass list exactly (see section 6).
- "General" section (Marketplace, My Profile) shown to all roles unconditionally (`416`).

**Dead links — fixed in Phase 4.** All 7 turned out to be real, fully-built features (`HRController`, `FinanceController`, and the shared `VetController` queue/disease-alerts, which agronomists are already permission-gated for via `role:vet,agronomist`) that were simply never wired into the sidebar — not missing functionality:
- Agronomist section (`app.blade.php:292`): "Crop Requests" → now `route('vet.queue')` (already server-side filtered to `case_type=crop` for agronomists in `VetController::queue()`). "Soil Reports" → **removed**, no backing feature exists anywhere in the app; added "Disease Alerts" → `route('vet.disease-alerts')` instead, since agronomists had zero sidebar path to it despite already being permitted.
- Finance section (`app.blade.php:305`): "Income & Expenses" → `route('finance.transactions')`, "Payroll" → `route('finance.payroll')`, "Financial Reports" → `route('finance.reports')` — all three map directly to existing, complete `FinanceController` methods with working views.
- HR section (`app.blade.php:322`): "Staff Records" → `route('hr.staff')`, "Attendance & Leave" split into "Attendance" → `route('hr.attendance')` and "Leave Requests" → `route('hr.leaves')`, plus added "Payroll" → `route('hr.payroll')` (HR has its own payroll flow, separate from Finance's — previously unreachable from any nav).

No new features were built — every link above points to a controller method and view that already existed and worked, just wasn't linked.

---

## 4. Permission Systems — three overlapping mechanisms

No permission package is installed (`composer.json` has no `spatie/laravel-permission` or equivalent). Three systems coexist and can disagree for the same user:

**(a) `RoleMiddleware` — the primary mechanism, used in 51 routes.** Coarse string comparison: `role:x,y,z` middleware checks `in_array($user->role, $roles)` (`RoleMiddleware.php` full logic). No wildcards, no hierarchy, no inheritance. On failure, redirects to a per-role fallback dashboard route (a ~29-entry map) or aborts 403.

**(b) Legacy `role_permissions` table + `PermissionMiddleware`.** A `role→permission` table (`2026_06_16_create_permissions_tables.php`), seeded by `RolePermissionSeeder`, checked via `permission:action_name` middleware, cached 1h. **Used in only 3 routes** (`web.php:274,275,277` — admin user suspend/delete/settings). `ceo` bypasses this system entirely.

**(c) Granular RBAC (`StaffRole`) — the most modern system, also the least used.** Permissions stored as JSON `{module: [abilities]}` on a `StaffRole` model, assigned to users via a `staff_role_assignments` pivot, checked via `User::hasRbacPermission($module, $ability)`. Fully built out (CRUD UI, audit logging via `RbacAuditLog`) but wired into almost nothing outside its own management routes (`ceo`-only `staff-roles.*`/`staff.*`).

**Net finding:** access control in this app is **primarily the blunt `role:` string middleware**. The other two systems are real, functional, but nearly unused — meaning most of the codebase has no path to fine-grained permissions at all today. Building "the central permission system" the request asks for (section 20 of the original prompt) is not a small addition — it means picking one of these three as authoritative and either migrating or removing the other two, which touches every `role:` middleware call in `routes/web.php`. **This is proposed as its own phase (Phase 3), not something to fold into a smaller fix.**

---

## 5. Access Control / Security Findings

Ranked by severity. Items 1 and 2 were flagged HIGH/MEDIUM-HIGH in the Phase 1 pass based on reading the controller code alone; Phase 2 verified both against what's actually reachable/exploitable and downgraded them. Corrected assessment below — **no code changed for either.**

1. **🟡 LOW (downgraded from HIGH) — VetController diagnosis query bypasses the assignment model, but exposes no private data.** `VetController.php:176`'s query does read across all users' diagnoses without an ownership/assignment filter, and `VetController.php:156` similarly aggregates across all users. But `vet/disease-alerts.blade.php` — the only view consuming this data — renders exclusively `disease_name`, `type`, `status`, and a relative timestamp: no farmer identity, no image, no confidence score, and no link to the individual report. There's no path from this page to a private record, and a vet directly guessing a `/diagnostics/{id}/report` URL is already blocked by the ownership check added to `DiagnosticController::downloadReport()` (owner, `ceo`, or `admin` only). Functionally this is closer to an anonymized epidemiological trend dashboard than a data leak — and restricting it to "assigned cases only" would break the actual point of an outbreak early-warning feature, which needs visibility beyond the vet's own cases. **No fix applied.** If tightened later, it should be for architectural consistency (routing through the same assignment concept Consultations use), not because private data is exposed today.
2. **🟢 LOW (downgraded from MEDIUM-HIGH) — Payment mobile-callback lookup is unauthenticated by necessity, and the reference isn't practically guessable.** `GET /payment/mobile-callback` (`routes/api.php:289-291`, `PaymentApiController::mobileCallback`) has no auth middleware — it's the Paystack redirect target (`PaymentApiController.php:34` sets it as `callback_url`) for an in-app-browser flow, so the caller genuinely has no identity to check "ownership" against; requiring auth here would break the redirect. The reference itself is `Payment::generateReference()` → `Str::random(12)` over a 62-character alphabet (~3×10²¹ possibilities), combined with `throttle:20,1` — brute-forcing a valid reference is infeasible, so real exploitability requires already knowing a specific other user's reference through some other channel (not this endpoint). **No fix applied** — trimming the response payload was considered but the mobile app's consumption of this endpoint's JSON body couldn't be located in `mobile/` to confirm a field-removal wouldn't break live payment confirmation; changing an unverifiable payment-flow response was judged riskier than the (low) exposure it would reduce.
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

- **Phase 2 (security verification) — complete.** Both findings verified against actual reachability/exploitability rather than fixed reflexively. Both downgraded to LOW once checked against what's really exposed (see §5) — no code changed, since the "fix" implied for each would have either broken a legitimate feature (VetController's outbreak-awareness page) or risked an unverifiable change to a live payment flow (mobile-callback). This is the correct outcome of a verify-before-fixing pass, not a skipped step.
- **Phase 3 (foundational, higher risk) — in progress.** Reconcile the three permission systems into one authoritative source, fix the role-spelling inconsistencies, decide the `financial-institution`/`rider` assignability gap. Touches `routes/web.php` broadly — needs careful regression testing per role since there's no test suite and no local PHP available to lint/run this app.
- **Phase 4 (consolidation) — complete.** The "merge two navigation systems" premise didn't survive investigation — one was confirmed dead code (deleted). All 7 dead links now point to real, previously-unwired features. The remaining consolidation item — a shared dashboard/scan component reused across roles — is real but large (see §7's implication note) and deferred rather than attempted opportunistically.
- **Phase 5 (visual/mobile):** Contrast, dead-button sweep beyond navigation, mobile responsiveness — best done role-by-role once Phase 4's shared components exist, so a fix lands once instead of 22 times.
- **Phase 6 (scope decision required):** Whether to expand scan/voice/report access to roles beyond the current 5 is a product decision, not implied by anything currently broken — flagged for explicit sign-off before any code changes.

No code has been changed through Phase 2. Phase 3 is next.
