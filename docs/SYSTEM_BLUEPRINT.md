# MSAS FarmAI System Blueprint

## Product Goal

Build a mobile-first, offline-first agricultural health platform for smallholder farmers in Northwestern Nigeria. The system should help farmers identify crop and livestock health issues, understand urgency, apply safe first actions, contact experts, and track outcomes.

## Core Architecture

```text
Mobile App (Expo React Native)
  - camera capture
  - offline queue
  - Hausa/English UI
  - diagnosis results
  - treatment tracking
  - records and marketplace

Web App (Next.js)
  - public landing page
  - future admin/expert dashboard
  - support/education content

API Server (Node.js + Express)
  - auth and roles
  - farm, animal, crop records
  - diagnosis submission and polling
  - marketplace and treatment data
  - analytics and alerts
  - expert escalation

AI Engine (FastAPI)
  - crop image inference
  - livestock fecal/visual inference
  - model metadata and confidence thresholds
  - fallback/rule-based responses while models mature

Data Stores
  - MongoDB for current app records and media metadata
  - future PostgreSQL for relational operational data
  - object storage for encrypted images
  - SQLite/AsyncStorage on mobile for offline cache
```

## Diagnostic Flow

1. Farmer selects crop or animal assessment.
2. App gives guided capture instructions.
3. App validates required metadata and image quality.
4. If offline, diagnosis is saved to a queue with local status.
5. When online, API stores images and creates a pending diagnosis.
6. AI engine returns diagnosis, confidence, severity, likely causes, and treatment plan.
7. API saves the result and schedules follow-up.
8. App displays result with urgency, confidence, treatment tabs, and expert escalation.
9. Farmer logs treatment and outcome.
10. Expert or admin can review uncertain cases for model improvement.

## AI Modules

### Crop Health

Initial scope:
- Maize
- Tomato
- Rice
- Sorghum
- Millet

Detection categories:
- Fungal disease
- Bacterial disease
- Viral disease
- Nutrient deficiency
- Pest damage
- Severity level

Output contract:

```json
{
  "aiResult": {
    "primaryDiagnosis": "Northern Leaf Blight",
    "primaryDiagnosisHa": "Cuta ta ganyen masara",
    "confidence": 88,
    "severity": "moderate",
    "likelyCauses": ["Fungus", "High humidity"],
    "contagionRisk": "medium",
    "needsExpertReview": false
  },
  "treatmentPlan": {
    "immediateActions": [],
    "organicRemedies": [],
    "chemicalTreatments": [],
    "prevention": [],
    "whenToCallExpert": []
  }
}
```

### Livestock Health

Initial scope:
- Cattle
- Goats
- Sheep

Assessment types:
- Fecal analysis
- Visual symptoms
- Behavioral questionnaire
- Comprehensive check

Output must include:
- Primary diagnosis
- Differential diagnoses
- Urgency
- Contagion risk
- On-farm first aid
- Medication guidance with dosage basis
- Withdrawal periods
- Expert review trigger

## Offline-First Requirements

Mobile should support:
- Create farm, animal, and crop records offline.
- Capture images and metadata offline.
- Store queued diagnosis jobs locally.
- Show queued, syncing, failed, and completed states.
- Retry sync safely without duplicate server records.
- Cache treatment library and previous diagnoses.

Recommended local entities:
- `offline_jobs`
- `diagnosis_drafts`
- `cached_treatments`
- `sync_conflicts`

## Language and Accessibility

Required:
- English and Hausa content for all farmer-facing diagnosis and treatment text.
- Simple action labels.
- Visual severity cues.
- Large tap targets.
- Voice and video content as Phase 2 enhancements.

## Safety and Escalation Rules

Trigger expert review when:
- Confidence is below 70%.
- Severity is `severe` or `emergency`.
- Contagion risk is high.
- Medication requires injection, antibiotic use, or withdrawal-period explanation.
- Farmer reports no improvement after 48 to 72 hours.

Farmer-facing copy should clearly say when a condition may require a veterinarian or agronomist.

## Data and Model Improvement Loop

Capture:
- Submitted images
- Metadata
- AI result
- Expert correction
- Farmer feedback
- Treatment outcome
- Follow-up image

Use reviewed cases to build a validated local dataset for Katsina and neighboring states.

## Non-Goals for MVP

- Fully automated prescription authority.
- Real-time video diagnosis.
- Insurance and loan integrations.
- IoT sensor support.
- Multi-state outbreak prediction.
- Apple App Store launch before Android pilot proof.
