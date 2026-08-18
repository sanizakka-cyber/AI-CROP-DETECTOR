# MSAS FarmAI — Role, Permission & Dashboard Audit

Living document for the system-wide role-based dashboard synchronization effort. Updated at the end of each phase — do not treat any phase as complete until this file reflects it.

## Phase Tracker

| Phase | Scope | Status |
|---|---|---|
| 1 | Role/permission/dashboard/menu ground-truth audit (this document) | **Complete** — 2026-08-17 |
| 2 | Security: verify the two Phase 1 findings against actual exploitability | **Complete** — 2026-08-17, both downgraded, no code change (see §5) |
| 3 | Permission-system reconciliation (pick one authoritative system) | **Closed** 2026-08-17 — role-spelling/assignment-gap items resolved; the 3 systems aren't causing a live bug, left as documented debt per your call |
| 4 | Dead-button sweep + shared-component consolidation | **Complete** 2026-08-17 — all 7 dead nav links wired to real routes; the "second navigation system" was confirmed dead code and deleted rather than merged. Shared dashboard/scan component work deferred (see §9) |
| 5 | UI contrast / mobile responsiveness pass | **Complete (static-analysis scope)** 2026-08-17 — see §10. No browser/screenshot tool available this session; fixed everything verifiable from code, flagged what needs visual testing |
| 6 | Scope decision: expand scan/voice/report access beyond current 5 roles? | **Closed** 2026-08-17 — decided against expansion; access stays as-is |
| 7 | Final Implementation & QA round (code-verifiable scope only — see §11-13) | **Complete within code-verifiable scope** 2026-08-17, including error-handling infrastructure + full 22/22 dashboard rollout (§11.4). Explicitly does not include browser/device/OTP/payment/live-audio testing — no such tool exists in this environment; see §12-13 for the manual test checklist and honest evidence report |
| 8 | CEO Overview redesign: phase-by-phase executive summary of all 8 modules | **Complete** 2026-08-17 — see §14. Existing top nav preserved unchanged; Overview now also summarizes every module with Quick Nav, anchors, and "View Full Module" links. CEOController's ~27 data methods converted to the same honest-error pattern as Phase 7, benefiting all 9 CEO pages at once |
| 9 | Fresh re-verification of role-naming/assignment claims + authorization matrix + landing-page fix + live-test handoff instrument | **Complete for the code-level parts** 2026-08-18 — see §15. Live browser/device/database verification explicitly NOT performed — no such tool exists in this environment; §15.5 hands off the instrument for whoever runs it |

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

## 9. UI Contrast & Mobile Responsiveness (Phase 5)

**Important limitation, stated upfront:** this session has no browser or screenshot tool. The one confirmed real contrast bug this whole audit found (the CEO KPI-pill washing out — a pale mint-green on a near-transparent white background) was found because you sent a screenshot, not from reading code. Phase 5 is therefore a *static-analysis* sweep — real code patterns, not visual verification — across all 22 role dashboards plus the HR/Finance pages wired up in Phase 4. Anything below that would benefit from an actual look on a phone should still get one.

**Searched for:** the exact confirmed-bug pattern (low-opacity white/light backgrounds paired with light text) elsewhere; very light gray text classes (`text-gray-200/300`) outside dark containers; `<table>` elements with no horizontal-scroll wrapper (the classic mobile-breakage source); fixed pixel widths with no responsive variant.

**Result — the confirmed bug pattern doesn't exist anywhere else.** Every other `rgba(255,255,255,0.0x)` usage found sits on an opaque dark gradient card, not a light background, and uses no blur — the specific combination that caused the original bug. No fixed-width elements were found that actually break mobile flow (the few over 300px are decorative absolutely-positioned elements or modal `max-width`, not layout-breaking).

**Fixed — 3 tables missing horizontal-scroll wrappers** (would force the whole page to scroll sideways on narrow screens instead of just the table):
- `logistics/dashboard.blade.php:73` — 5-column recent-deliveries table, the most consequential of the three.
- `subscription/dashboard.blade.php:269` — subscription history table.
- `agribusiness-owner/dashboard.blade.php:71` — 3-column recent-orders table, lower risk but fixed for consistency.
- `data-analyst/dashboard.blade.php:141` — had `overflow-y-auto` for its scrollable height but no `overflow-x-auto`; added.

**Fixed — 3 low-contrast empty-state messages** using `text-gray-300` (very light) directly on white cards, with no dark container to justify it:
- `data-analyst/dashboard.blade.php:166` — "No activity yet" empty state.
- `monitoring-evaluation/dashboard.blade.php:166,202` — "No geographic data" and "No data available" empty states.

All three changed to `text-gray-400` — not an arbitrary pick: grepped the rest of the app first (`admin/wallets/index.blade.php:80`, `admin/wallets/withdrawals.blade.php:60,123`, `wallet/show.blade.php:67`) and confirmed `text-gray-400` is the established convention for this exact "no data yet" empty-state pattern everywhere else in the codebase. This matches these three to the existing design system rather than picking a new value.

**Not attempted in this phase:** interactive mobile behavior (dropdown/modal overlap, sidebar collapse behavior, notification panel positioning) — these are exactly the kind of thing that needs a real device or screenshot to verify, and guessing at a fix without seeing the actual failure risks the same mistake this audit corrected itself out of twice already (Phase 2's two downgraded findings). If you spot any of these on an actual phone, that's real, actionable evidence — send it and it goes straight to a fix, the same way the original CEO KPI-pill bug did.

---

## 10. Recommendations for Sequencing

Given the findings above, the original 29-section request breaks down into genuinely distinct pieces of work with different risk profiles:

- **Phase 2 (security verification) — complete.** Both findings verified against actual reachability/exploitability rather than fixed reflexively. Both downgraded to LOW once checked against what's really exposed (see §5) — no code changed, since the "fix" implied for each would have either broken a legitimate feature (VetController's outbreak-awareness page) or risked an unverifiable change to a live payment flow (mobile-callback). This is the correct outcome of a verify-before-fixing pass, not a skipped step.
- **Phase 3 (foundational) — closed.** Role-spelling and assignment-gap items verified and fixed where real (`financial-institution`); the `rider` "gap" was a mischaracterization, corrected. The 3-permission-system question isn't a live bug — closed as documented debt per your direction rather than forcing a broad `routes/web.php` migration with no test suite to catch regressions.
- **Phase 4 (consolidation) — complete.** The "merge two navigation systems" premise didn't survive investigation — one was confirmed dead code (deleted). All 7 dead links now point to real, previously-unwired features. The remaining consolidation item — a shared dashboard/scan component reused across roles — is real but large (see §7's implication note) and deferred rather than attempted opportunistically.
- **Phase 5 (visual/mobile) — complete within static-analysis scope.** Everything verifiable from code is fixed (see §9). Interactive/visual mobile behavior needs a real device or screenshot to responsibly act on.
- **Phase 6 (scope decision) — closed.** Decided against expanding scan/voice/report access beyond the current 5 roles (farmer, admin, ceo, vet, agronomist). Nothing was broken by the narrower scope — the other 19 roles simply have no scan UI, which stays as-is.

**All 6 phases complete as of 2026-08-17.** Of the original 29-section request: security findings were verified rather than reflexively fixed (2 downgraded, 0 required code changes), 1 real permission gap was fixed, 7 dead nav links were wired to already-working features, 1 orphaned file was deleted after being wrongly assumed to be a live second navigation system, and 6 mobile/contrast issues were fixed against a documented convention rather than guessed at. Several of Phase 1's initial findings were corrected on closer inspection rather than acted on as flagged — that self-correction is a feature of this process, not a gap in it.

---

## 11. Phase 7 — Final Implementation & QA (code-verifiable scope)

A follow-up "Final Implementation, Integration, QA & Production Readiness" request asked for 37 phases including full browser/device/OTP/payment/live-audio testing across all 24 roles. **No browser, device, or screenshot tool exists in this environment** — that testing is not something that can be executed here, and this section does not claim otherwise. What follows is everything achievable through direct code verification, scoped down from that request with the user's explicit sign-off to keep two Phase 3/6 decisions unreversed (permission-system consolidation stays as documented debt; scan/voice access stays at the current 5 roles).

### 11.1 TTS rewind/forward — verified honest, not decorative

Read `diagnostics/history.blade.php`'s TTS implementation in full rather than assuming. Confirmed already correctly built (from earlier work this session, not new):

- **Server-audio languages (Hausa, Yoruba, Igbo, French):** `ttsRewind`/`ttsFastForward` operate on `audio.currentTime` — real HTML5 `<audio>` seeking, genuine timeline scrubbing (`history.blade.php:958-984`).
- **Browser-fallback languages (English, Fulfulde):** no real audio file exists to seek within (speechSynthesis has no seek API), so rewind/forward re-synthesize from a recalculated character offset (`currentCharIdx` ± an estimated-chars-per-second jump) — the correct best-effort approach given the platform constraint, not a fake control that does nothing.
- **No silent English fallback (the explicit "10.2" requirement):** verified at `history.blade.php:827-842` — when no device voice matches the requested language, the code picks an intelligible English voice so *something* audible plays, but explicitly shows an on-screen warning naming the missing language and how to fix it ("Install a HA voice pack..."). It never silently swaps to English while claiming another language is active. Server-TTS-unavailable fallback (`:1159`) shows "Using device voice." — also not silent.

No code changes needed here — this item was already done correctly.

### 11.2 Dead-feature / fake-data sweep

Searched `app/` and `resources/views/` for `TODO`, `FIXME`, `@todo`, "Coming Soon", "Not Implemented", "Lorem ipsum", mock/dummy/fake data patterns, and remaining `href="#"`.

- **Zero** `TODO`/`FIXME`/`@todo` markers anywhere in the codebase.
- **Zero** Lorem ipsum or mock/fake/dummy-data patterns in `app/`.
- One dead (never-called) `showToast()` helper with a "Coming soon!" default message in `ceo/reports.blade.php:5-10` — harmless, since nothing invokes it, so no user ever sees the message. Low-priority cleanup, not a functional bug.
- `marketplace/index.blade.php`'s two remaining `href="#"` are legitimate — confirmed they're populated dynamically by JS when the contact modal opens for a specific product (`:264-265`), not dead links.
- `welcome.blade.php` — the **one real finding**: 8 genuinely placeholder `href="#"` links on the **public marketing landing page** (not a dashboard): social media icons, Blog/News/Downloads/FAQs/Training, and — more consequentially — Privacy Policy/Terms of Service/Data Protection footer links that go nowhere. **Not fixed here** — writing real legal/policy text is a business decision, not something to fabricate as code. Flagged for your call on whether/when to build these pages.

### 11.3 `scan.*` permission matrix — documented, not re-architected

Per your Phase 6 decision, this documents the *current* access reality rather than building new enforcement machinery (enforcement already happens correctly via existing route middleware + `DiagnosticController`/`NarrationController` ownership checks — adding a parallel permission-constants system that nothing actually reads would itself become the kind of dead code §11.2 looked for).

| Permission | Who has it today | Enforced by |
|---|---|---|
| `scan.create` | farmer | `role:farmer,admin,ceo,vet,agronomist` on `diagnostics.*` (`web.php:61`); farmer is the practical submitter |
| `scan.view_own` | farmer, vet, agronomist, admin, ceo | Same route gate + `DiagnosticController::downloadReport()` ownership check (`user_id` match) |
| `scan.view_assigned` | *(not a distinct concept — see §5 finding 1)* | VetController's queue/disease-alerts show aggregate, anonymized cross-user data by design (outbreak awareness), not assigned individual records |
| `scan.view_department` | — | Not implemented; no departmental scoping concept exists for diagnoses |
| `scan.view_all` | admin, ceo | `DiagnosticController::downloadReport()` explicit `role === 'ceo' \|\| 'admin'` bypass (added this session) |
| `scan.analytics` | ceo, admin | `CeoScanAnalyticsController`, `role:ceo,admin` |
| `scan.export` | ceo, admin | `CeoScanAnalyticsController::exportCsv()`, same gate |
| `scan.review` | vet, agronomist | Consultation model's `expert_id` assignment flow (`VetController::respond()`) — this is the real "review" concept, distinct from raw diagnosis records |
| `scan.narration` | farmer, vet, agronomist, admin, ceo (whoever owns the diagnosis) | `NarrationController.php:23`, ownership-checked |
| `scan.transcript` | same as narration | Same controller — transcript is served alongside narration, not a separate endpoint |

All 19 other roles: none of the above. This table is descriptive documentation of the current, deliberately-unexpanded state — not a new enforcement layer.

### 11.4 Error/loading state handling — infrastructure built, rolled out to 1 of 22 dashboards

Confirmed real (every dashboard controller method uses `try/catch` returning `0`/`collect()` on failure, so a broken query silently shows "0" instead of an error) and now has a real, working fix — but deliberately scoped to one complete example rather than a rushed pass across all 22, since that's ~100+ individual catch sites and this codebase has no test suite to catch a mistake made at that scale.

- **`DashboardController::safe(string $label, \Closure $query, mixed $fallback = 0)`** (`DashboardController.php:21-30`) — wraps a query exactly like the existing `try/catch` did (same fallback behavior, so a broken query still can't crash the page), but also appends the failed stat's label to a `private array $dashboardErrors` property.
- **`<x-dashboard-error-banner :errors="..." />`** (`resources/views/components/dashboard-error-banner.blade.php`) — new shared component, renders nothing when `$errors` is empty; when not empty, shows "Some dashboard data couldn't be loaded. Affected: X, Y. The figures below may be showing 0..." instead of leaving the 0s unexplained.
- **Rolled out to all 22 of 22 controller-owned dashboards** (215 `safe()` call sites total): every `DashboardController` dashboard method — `farmer`, `admin`, `vet`, `agronomist`, `dealer`, `hr`, `equipment-dealer`, `extension`, `finance`, `dataAnalyst`, `fieldOfficer`, `cooperative`, `ngo`, `government`, `researchInstitution`, `investor`, `financialInstitution`, `logistics`, `agribusiness`, `inputSupplier`, plus the 3 more involved ones (`operations`, `monitoringEvaluation`, `customerSupport`). Every `try/catch` converted to `safe()`, `$dashboardErrors` passed to each view, `<x-dashboard-error-banner>` wired in at the top of each page.
- **The 3 more involved methods** (`operations`: 1 multi-statement block; `monitoringEvaluation`: 5; `customerSupport`: 3) needed regular closures with explicit `use()` captures instead of the simpler `fn() =>` form, since their try blocks compute intermediate values (e.g. `$farmersWithScans`) before a final result (`$scanAdoptionRate`) that depends on a value from an *earlier* stat (`$totalFarmers`) — captured via `use($totalFarmers)` rather than relying on arrow-function auto-capture, since these are multi-statement bodies arrow functions can't express. Two spots (`customerSupport`'s ticket-category breakdown, `monitoringEvaluation`'s extension activity) originally computed multiple *sibling* final results in one try/catch (all succeed or fail together) — preserved that exact grouping via array destructuring (`[$a, $b] = $this->safe(...)`) rather than splitting into independent calls, which would have subtly changed the failure semantics from "these fail together" to "these fail independently."
- `rider()` delegates entirely to `RiderController::dashboard()` — out of scope for this controller; would need its own conversion inside that controller if wanted.
- Verified after every batch by brace/paren/bracket balance-checking the whole file (no PHP CLI available to lint directly) — consistently balanced across all edits, ending at 215 `safe()` calls. The only `try { ... } catch` left in the file is inside `safe()` itself (`DashboardController.php:21-30`) — every dashboard method now delegates to it instead of its own inline try/catch.

### 11.5 Shared component library — not built this pass

Real gap (documented in §7) — but building ~15-20 components (Header, Sidebar, Stat Card, Data Table, Scan Interface, Voice Player, etc.) and retrofitting 22 dashboards is large enough that it deserves its own plan and priority order, not an opportunistic partial build alongside everything else in this pass. The one component built this pass (`dashboard-error-banner`) is a real down payment on this list, not the whole thing.

### 11.5 What genuinely needs your own testing

No environment tool exists here for these — code review cannot substitute for actually running them:

- Browser/device rendering (Chrome, Edge, Android Chrome, iOS Safari), dropdown/modal overlap, responsive layout at real breakpoints.
- OTP/email delivery, expiry, resend, already-used rejection.
- Live payment flows (Paystack), live voice audio playback per language.
- Actual click-through per role (login → dashboard → menu → action → logout).

Section 12 gives a per-role checklist for exactly this, so results can come back as real pass/fail rather than being guessed at here.

---

## 12. Per-Role Manual Test Checklist

For each role, in order: log in → confirm correct dashboard loads → check sidebar shows only that role's real (now fully-wired) menu items → click every visible menu item once → confirm it loads a real page, not a 404/500/blank screen → try navigating to another role's dashboard URL directly (e.g. a farmer hitting `/ceo/overview`) → confirm it's blocked, not silently rendered → log out.

Roles with scan/voice access (farmer, vet, agronomist, admin, ceo) additionally: submit one scan → confirm result appears → open scan history → open one report → play voice narration in at least one server-audio language (Hausa/Yoruba/Igbo/French) and confirm real playback with working seek bar → try English or Fulfulde and confirm the "no native voice" warning appears if the test device lacks that voice pack, rather than silent English audio.

This is intentionally not filled in with results — it's the instrument for you (or whoever has access to a real browser and test accounts for each role) to run and report back. I'll act on whatever fails.

---

## 13. Evidence Report — Phase 7

Honest status, not "everything is fixed":

| Area | Status |
|---|---|
| Roles code-reviewed for correct routing/gating | 24/24 (Phase 1) |
| Roles click-tested in a real browser | 0/24 — no browser tool available; needs §12 |
| Dashboards code-reviewed | 22/22 (Phase 1) + CEO's 9-page suite |
| Dashboards visually/mobile tested | 0/22 — no device tool available |
| Dead nav links found → fixed | 7/7 |
| Dead code/placeholder sweep | Complete — 1 harmless dead function, 1 real finding (welcome.blade.php public-page links, not fixed — content/legal decision) |
| TTS controls (Play/Pause/Resume/Stop/Rewind/Forward) | Verified correct in code; **not** played back on a real device |
| TTS "no silent English fallback" requirement | Verified correct in code (§11.1) |
| Voice language mappings (en/ha/yo/ig/fr/ff) | Verified configured correctly (`TtsLanguages.php`); actual audio output not tested — needs a device with each language's voice pack, or a Hausa/Yoruba/Igbo/French speaker to confirm pronunciation quality |
| Transcript matches current scan/language | Verified in code (fetched per-diagnosis, per-language, ownership-checked) — not clicked through live |
| `scan.*` permission matrix | Documented (§11.3), matches current enforcement, not expanded |
| CEO analytics (date/state/LGA/filters/drill-down/export) | Built and code-reviewed (prior session); not click-tested |
| Security: cross-user report access | Verified blocked in code (ownership check); not penetration-tested against a live deployment |
| API status code coverage (200/401/403/etc.) | Not systematically tested — would need a real HTTP client against the live Render deployment, not available here |
| Error/loading/empty state handling | **Complete — 22/22 controller-owned dashboards** (215 safe() call sites). `rider()` delegates to a separate controller, out of scope (§11.4) |
| Shared component library | **1 component built** (error banner) — 14-19 more from the original list (§7) not built, scoped, deferred |
| Permission-system consolidation | **Not done** — closed as documented debt per your Phase 3 decision |
| Scan/voice access expansion | **Not done** — closed per your Phase 6 decision |

**Remaining issues, stated plainly:** error-state handling is complete for all 22 controller-owned dashboards; the rest of the shared component library and all interactive/visual/device/live-service testing are real, uncompleted work. Nothing above is claimed done that wasn't actually verified in code, and nothing requiring a browser or device is claimed done at all.

---

## 14. CEO Overview Redesign — Phase-by-Phase Executive Summary

Turned the CEO Overview page into a full executive command center: every module (Risk Center, Financial, AI Analytics, Marketplace, Operations, Geographic, Users & Subscriptions, System) now has a compact real-data summary directly on Overview, alongside the existing top navigation (unchanged) — giving both a scroll-through system summary and instant per-module navigation, as requested.

**What was built:**
- **Quick Navigation ("Jump to")** — 8 pill links at the top of Overview, each scrolling smoothly to its section via `#anchor` (`html { scroll-behavior: smooth }`, with `scroll-margin-top` on each section so the sticky nav bar doesn't cover the heading on arrival).
- **8 anchored phase sections** (`#risk-center`, `#financial`, `#ai-analytics`, `#marketplace`, `#operations`, `#geographic`, `#users-subscriptions`, `#system`), each a compact 3-4 KPI summary, not a reproduction of the full module page.
- **"View Full Module →" button** on every section, linking to the real existing route for that page (`ceo.risk-center`, `ceo.financial`, `ceo.ai-analytics`, etc.) — clicking the top-nav item still goes there directly too, satisfying "both ways" navigation.
- **New AI Analytics summary data** (`CEOController::aiAnalyticsSummaryMetrics()`): total/today/week/month scan counts, average confidence, pending-review count, failed-scan count, and top 5 states by scan volume — computed fresh, not reused from the legacy `aiStatsMetrics()` (which still carries the old crop/livestock split kept only for the pulse tile).
- **New Geographic coverage data** (`geographicSummaryMetrics()`): distinct states/LGAs with at least one user, plus top state by user count and by scan count from the existing `geoChartMetrics()`.
- **New System activity data** (`systemActivityMetrics()`): last 5 `AuditLog` entries (real model, `user_id`/`action`/`model`/`created_at`) with the acting user's name — nothing fabricated, and nothing beyond what a CEO/admin is already authorized to see.
- **Risk Center summary deliberately kept simple**: raw counts (disease alerts, failed payments, pending expert/verification approvals) rather than replicating the full page's derived "critical/warning" classification logic, which lives only in that page's view — duplicating it into Overview too would have created two places that could drift out of sync on the exact same severity thresholds.

**Honest data requirement (explicitly required):** every one of these new figures, plus every pre-existing Overview figure, now goes through the same `safe()`/`<x-dashboard-error-banner>` pattern built in Phase 7 — extracted into a reusable `App\Http\Controllers\Concerns\HasSafeDashboardQueries` trait so `DashboardController` and `CEOController` share one implementation instead of two copies. This meant converting **all ~27 of `CEOController`'s private data methods** (not just the new ones), which benefits all 9 CEO pages simultaneously since those methods are shared helpers — e.g. `orderStatsMetrics()` feeds Overview, Risk Center, and Marketplace, so fixing it once fixes honesty on all three. If a query fails, the CEO sees "Some dashboard data couldn't be loaded" naming which stat, never a silent 0 presented as real.

**Not duplicated:** confirmed each phase section shows only summary figures — no tables, no full charts, no forms reproduced from the actual module pages, matching the explicit "summary only" requirement.

**Not done / needs your testing (same limitation as every prior phase):** no browser tool exists here, so responsive behavior (mobile stacking, no horizontal overflow), the actual smooth-scroll animation, and clicking through every Quick Nav item / View Full Module button / top-nav item have not been visually verified — only code-reviewed. Recommended to spot-check on a phone and desktop before considering this fully done.

---

## 15. Fresh Re-verification, Authorization Matrix, Landing-Page Fix, Live-Test Handoff

A follow-up request specifically asked not to trust the Phase 3 "fixed" claim about role-naming and financial-institution/rider without re-checking, and asked for a live acceptance test across all 24 roles, every dashboard, TTS audio, payments, and OTP. The re-verification was done properly, with fresh evidence below. The live acceptance test was **not** performed and is not claimed to have been — no browser, device, database client, or ability to receive real email/SMS exists in this environment. That boundary hasn't changed since it was first stated in §11, and repeating a request for it doesn't create the capability. What follows is the honest split: what was re-verified, what was newly fixed, and the actual instrument for someone who can run the live tests.

### 15.1 Role-naming — re-verified with fresh evidence, not re-asserted

Grepped the entire codebase again from zero (not reading the earlier summary) for every spelling. Result, with line numbers: `m-e-officer` is the only value written by any code path — `TestAccountsSeeder.php:178`, `StaffAccountsSeeder.php:61`, `QAAccountsSeeder.php:106`, `admin/users.blade.php:180` (the actual role-assignment dropdown), `CEOController.php:705`, `CEO/StaffController.php:269`. `monitoring-evaluation` and `me-officer`/`me_officer` appear only in code that *reads/matches* a role value defensively — `RoleMiddleware.php:47-49`, route middleware alias lists (`web.php:146,200`), `User.php:113,144-146` (label lookups), `AnalyticsApiController.php:54,109,139` (accepted-value lists), `AuthApiController.php:144-145` (label lookups). This confirms the Phase 3 finding rather than merely repeating it.

**What this re-verification cannot do:** confirm the *live production database* has no row with `role = 'monitoring-evaluation'` or `role = 'me-officer'` — that requires a database client this environment doesn't have. If you want that closed with certainty, run:
```sql
SELECT id, email, role FROM users WHERE role IN ('monitoring-evaluation', 'me-officer', 'me_officer');
```
If it returns rows, those specific accounts need a manual `UPDATE users SET role = 'm-e-officer' WHERE id = ...` — the code-level fix (making `m-e-officer` the sole assignment target going forward) is already in place; only pre-existing bad data, if any, would need this.

### 15.2 financial-institution and rider — re-verified with fresh evidence

`financial-institution`: `CEOController.php:706` fresh-grepped and confirmed present in `$allRoles` (this was the Phase 3 fix — re-confirmed it's actually in the file, not just claimed). The dropdown feeds `updateUser()`'s validation rule (`CEOController.php:719`: `'role' => 'required|string'`, no `in:` whitelist), so any value the dropdown offers saves correctly — confirmed the fix is complete end-to-end, not just at the dropdown. Dashboard route exists and is view-backed: `web.php:92`, `resources/views/financial-institution/dashboard.blade.php`.

`rider`: re-confirmed this was never actually a gap. `Admin\RiderManagementController.php:82` sets `role => 'rider'` directly; the full CRUD route set (`web.php:309-314`: index/create/store/show/toggle/status) sits under `role:admin,ceo` (`web.php:299`) — correctly gated, reachable, and deliberately separate from the generic role editor because riders carry extra fields (`vehicle_type`, `rider_status`) that dedicated flow handles.

**What this re-verification cannot do:** click through the actual admin UI in a browser to confirm the dropdown renders correctly, the save button works, and the resulting dashboard actually loads — that's the live test in §15.5.

### 15.3 Authorization matrix

Traced each function's actual middleware chain fresh, not from memory:

| Function | Route middleware | Additional gate | Authoritative system |
|---|---|---|---|
| User suspend | `role:admin,ceo` (`web.php:271`) | `permission:user:suspend_account` (`web.php:274`) | RoleMiddleware (coarse) **+** legacy `role_permissions` table, falling back to StaffRole RBAC if the legacy table doesn't grant it (`PermissionMiddleware.php:65-88`) — an OR, not a conflict: either system granting is sufficient |
| User delete | `role:admin,ceo` | `permission:user:delete_other` (`web.php:275`) | Same as above |
| Settings | `role:admin,ceo` | `permission:admin:manage_settings` (`web.php:277`) | Same as above |
| Staff Roles | `role:ceo` only (`web.php:176`) | none | RoleMiddleware only — deliberately not layered with StaffRole RBAC, since the page that grants RBAC permissions shouldn't be gateable by RBAC itself |
| AI Scan | `role:farmer,admin,ceo,vet,agronomist` (`web.php:61`) | none | RoleMiddleware only |
| Diagnostic Report | Same route group | Ownership check in `DiagnosticController::downloadReport()` (owner, or `ceo`/`admin`) | RoleMiddleware (route access) **+** explicit per-record ownership check (controller) — two layers, not competing |
| Financial | `role:ceo,admin` (`CEOController::financial()`) | none | RoleMiddleware only |
| CEO Analytics | `role:ceo,admin` (`CeoScanAnalyticsController`) | none | RoleMiddleware only |

**No conflicting behavior found** — every function above has exactly one authoritative answer, even where two mechanisms are layered (they combine as AND for route+ownership, or OR for the two permission stores), not two systems disagreeing. The genuinely unresolved architectural point remains what Phase 3 already said: most of the app relies solely on RoleMiddleware, and the other two systems are real but narrow in scope — not a source of conflicting decisions for any function checked here, but not a single unified system either.

### 15.4 Landing-page links — fixed for real, not just relabeled

The Phase 7 finding (8 dead `href="#"` links on `welcome.blade.php`, including Privacy Policy/Terms/Data Protection) turned out to have an easy real fix rather than needing new pages: **six working public legal/support pages already existed** (`ComplianceController` + `legal.*` route names: `legal.privacy` → `/privacy-policy`, `legal.terms` → `/terms`, `legal.faq` → `/faq`, `legal.help` → `/help`, `legal.cookie` → `/cookie-policy`, `legal.refund` → `/refund-policy`) — the footer simply never linked to them. Confirmed `legal.privacy`'s actual content substantively covers NDPR data protection (Data Protection Officer contact, user rights under the Nigeria Data Protection Regulation — `resources/views/legal/privacy.blade.php:52-128`), so "Data Protection" honestly points to that same page rather than needing separate content.

Fixed: Privacy Policy, Terms & Conditions/Terms of Service (both footer instances), Data Protection, Help Center, FAQs, plus added Cookie Policy and Refund Policy links that existed but weren't surfaced anywhere. Removed rather than left dead (per your explicit instruction to remove until real content exists, not invent it): Blog, News & Events, Downloads, Training, the redundant "Contact Us" text link (real contact info already has its own footer column), and 5 social media icons (no real MSAS Agro social accounts to link to — inventing placeholder URLs would be worse than removing).

### 15.5 Live-test instrument — handoff, not a result

This is the actual acceptance-test matrix, structured exactly as requested, for whoever has a browser, test accounts for each role, and access to the live Render deployment to fill in. Every row starts blank/unknown — filling in "Pass" without running it would be exactly the fabrication this whole request is about avoiding.

| Test Area | Required | Passed | Failed | Evidence |
|---|---|---|---|---|
| Roles (login → dashboard → menu → logout, all 24) | 24 | — | — | — |
| Dashboards (22 role dashboards + CEO 9-page suite) | 31 | — | — | — |
| Role assignment (financial-institution, rider via their real flows) | 2 | — | — | — |
| AI Scan end-to-end (farmer, vet, agronomist, admin, ceo) | 5 | — | — | — |
| TTS languages (en, ha, yo, ig, fr, ff) | 6 | — | — | — |
| Voice controls (Play/Pause/Resume/Stop/Replay/Rewind/Forward) | 7 | — | — | — |
| Transcript (opens, correct scan, correct language) | 1 | — | — | — |
| CEO Analytics filters (date/state/LGA/crop/diagnosis/confidence/status/user) | 8 | — | — | — |
| CEO scan drill-down (state → LGA → scan → report) | 1 | — | — | — |
| CEO Overview (Quick Nav, 8 View Full Module links, top nav) | 17 | — | — | — |
| API status codes on critical endpoints | — | — | — | — |
| Direct URL security (limited-permission user hitting other-role URLs) | — | — | — | — |
| Registration → OTP → activation → login | 1 | — | — | — |
| Payment → Paystack → callback → verification | 1 | — | — | — |
| Mobile (phone) | 1 pass | — | — | — |
| Tablet | 1 pass | — | — | — |
| Desktop (Chrome, Edge) | 2 | — | — | — |
| Profile dropdown white-box regression check | 1 | — | — | — |

Report results back and they'll be acted on immediately — that's real, fast, high-value work once there's real evidence to act on. Until then, this system is **not** declared 100% complete, per the acceptance rule requested: code-verified and live-tested are being kept distinct, not blurred.
