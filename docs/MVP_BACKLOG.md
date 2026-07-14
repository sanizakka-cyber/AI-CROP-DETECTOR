# MVP Backlog

## Phase 0: Stabilize Existing Codebase

- Clean encoding artifacts in README, comments, and visible strings.
- Add API response contracts for crop and livestock diagnosis.
- Add health checks for `server` and `ai-engine`.
- Add smoke tests for auth, diagnosis submission, and diagnosis polling.
- Add environment examples for API, AI engine, and mobile.

## Phase 1: Offline Scan Queue

- Add mobile offline job store for crop and livestock diagnosis drafts.
- Generate `offlineId` for every offline-created diagnosis.
- Show queued/syncing/failed/synced states in mobile records.
- Add server idempotency handling for repeated `offlineId` submissions.
- Compress images before upload.

Acceptance criteria:
- A farmer can capture a crop scan offline.
- The scan remains visible after app restart.
- The scan uploads once when connectivity returns.
- Duplicate retries do not create duplicate diagnoses.

## Phase 2: Safer Diagnosis Contracts

- Standardize severity values: `routine`, `monitor`, `urgent`, `emergency`.
- Standardize confidence thresholds.
- Add `needsExpertReview`, `reviewReason`, and `farmerSafetyMessage`.
- Add differential diagnoses for livestock.
- Add treatment plan sections for dosage basis, withdrawal days, cost estimate, and local availability.

Acceptance criteria:
- Every diagnosis result can be rendered by mobile without conditional shape checks.
- Emergency and low-confidence results show expert escalation.

## Phase 3: Hausa Content

- Add translation keys for all scan workflow screens.
- Ensure treatment plan fields include Hausa equivalents.
- Add Hausa safety disclaimers for medication and emergency cases.
- Add a one-tap language switch that persists offline.

Acceptance criteria:
- A farmer can complete crop and livestock diagnosis workflows in Hausa.

## Phase 4: Expert Review Workflow

- Add consultation request model.
- Add API endpoints for creating, listing, and updating expert review cases.
- Auto-create review cases for low-confidence and emergency diagnoses.
- Add web/admin dashboard shell for expert queues.

Acceptance criteria:
- A diagnosis can be escalated to an expert.
- The expert can view submitted metadata, images, AI result, and farmer notes.

## Phase 5: Treatment Tracking

- Add treatment log model and endpoints.
- Add medication reminders in mobile.
- Add follow-up scan prompts.
- Add outcome feedback: improved, no change, worsened, died/lost, harvested.

Acceptance criteria:
- A farmer can record treatment given and update recovery status.
- Outcomes are linked to the original diagnosis for analytics and model feedback.

## Phase 6: Model Readiness

- Define dataset labeling schema.
- Add model metadata endpoint in `ai-engine`.
- Add per-model confidence calibration notes.
- Add test fixtures for supported crops and animal assessments.
- Add a human-review queue for cases below launch confidence.

Acceptance criteria:
- The system can distinguish mock, pilot, and production model outputs.
- Admins can identify which model produced a diagnosis.

## Phase 7: Marketplace Tie-In

- Map treatment recommendations to marketplace product categories.
- Add quantity calculators by farm size or animal weight.
- Add low-cost alternatives and organic options.
- Add supplier verification status.

Acceptance criteria:
- A diagnosis can recommend relevant products without hiding safety guidance.

## Immediate Engineering Tasks

1. Add idempotent `offlineId` support to diagnosis submissions.
2. Add diagnosis result schema normalization in `server/services/aiService.js`.
3. Add a mobile offline queue helper under `mobile/lib`.
4. Add an API health endpoint for the AI engine and server.
5. Clean visible encoding artifacts in existing mobile/server strings.
