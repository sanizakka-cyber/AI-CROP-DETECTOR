# MSAS FarmAI

AI-powered crop and livestock health diagnostics for smallholder farmers in Katsina State and Northwestern Nigeria.

The platform combines mobile-first farm management, image-based crop diagnosis, livestock health assessment, Hausa/English guidance, expert escalation, and a treatment/input marketplace.

## Repository Structure

```text
AI CROP DETECTOR/
  ai-engine/    FastAPI inference service stub for crop and livestock AI
  server/       Node.js + Express API, MongoDB models, diagnosis workflow
  mobile/       Expo React Native app for farmers and field use
  web/          Next.js landing page and web dashboard shell
  laravel/      Laravel backend experiments
  msas-system/  Existing MSAS system assets/vendor code
```

## Current Capabilities

- Mobile app shell with authentication, tabs, scan flows, diagnosis results, records, market, and profile screens.
- Express API for auth, farms, animals, crop/livestock diagnosis, treatments, marketplace, alerts, vets, analytics, and users.
- AI service adapter with Python FastAPI integration and local rule-based fallback.
- Hausa and English diagnosis fields in the mock knowledge base.
- Next.js landing page for MSAS Livestock & Agro Services.

## Target Product

MSAS FarmAI is designed as an offline-first agricultural health platform:

- Crop disease, pest, and nutrient deficiency diagnosis from photos.
- Livestock fecal, visual symptom, and behavior-based diagnosis.
- Actionable treatment plans with immediate actions, organic remedies, chemical treatments, prevention, cost, dosage, and withdrawal guidance.
- Hausa language support, low-literacy visual cues, and future voice support.
- Expert consultation when confidence is low, severity is high, or the farmer requests review.
- Marketplace recommendations tied to the diagnosis and farm scale.
- Farm records, treatment tracking, reminders, outcomes, and model feedback loops.

## Quick Start

### Backend API

```bash
cd server
npm install
npm run seed
npm run dev
```

The API defaults to port `5000`.

### AI Engine

```bash
cd ai-engine
pip install -r requirements.txt
python main.py
```

The inference service defaults to port `8000`.

### Mobile App

```bash
cd mobile
npm install
npm start
```

Use Expo Go, Android emulator, iOS simulator, or the web target.

### Web App

```bash
cd web
npm install
npm run dev
```

The landing page runs on `http://localhost:3000`.

## MVP Scope

Phase 1 should focus on shipping a trustworthy field pilot rather than a broad AI promise:

- Android-first Expo app.
- Offline scan capture and queued sync.
- Crop diagnosis for maize, tomato, rice, sorghum, and millet.
- Livestock diagnosis for cattle, goats, and sheep using fecal and guided visual assessment.
- Treatment plans reviewed by a veterinarian and agronomist.
- Hausa/English content for all farmer-facing diagnosis and treatment steps.
- Expert escalation for emergency, low-confidence, or unresolved cases.
- Admin review workflow for uncertain diagnoses and training data collection.

## Documentation

- [System Blueprint](docs/SYSTEM_BLUEPRINT.md)
- [MVP Backlog](docs/MVP_BACKLOG.md)

## Safety Note

AI diagnosis must be treated as decision support, not a replacement for licensed veterinary or agronomic judgment. High-severity, low-confidence, contagious, or medication-sensitive cases should always trigger expert review.
