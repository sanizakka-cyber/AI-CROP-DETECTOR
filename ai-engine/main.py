import os
import base64
from typing import List, Optional
from fastapi import FastAPI, UploadFile, File, Form, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
import anthropic
import uvicorn

app = FastAPI(title="MSAS FarmAI Inference Engine", version="3.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── Auth ──────────────────────────────────────────────────────────────────────

API_KEY       = os.environ.get("API_KEY", "")          # set in cPanel / Render env
ANTHROPIC_KEY = os.environ.get("ANTHROPIC_API_KEY", "")

def _check_auth(request: Request):
    if not API_KEY:
        return  # auth disabled if no key set
    auth = request.headers.get("Authorization", "")
    if auth != f"Bearer {API_KEY}":
        raise HTTPException(status_code=401, detail="Unauthorized.")

def _ai_client() -> anthropic.Anthropic:
    if not ANTHROPIC_KEY:
        raise HTTPException(status_code=503, detail="AI engine not configured (missing ANTHROPIC_API_KEY).")
    return anthropic.Anthropic(api_key=ANTHROPIC_KEY)

# ── Helpers ───────────────────────────────────────────────────────────────────

async def _read_images_b64(images: List[UploadFile]) -> List[dict]:
    """Return list of base64-encoded image dicts for Claude."""
    result = []
    for img in images:
        data = await img.read()
        media_type = img.content_type or "image/jpeg"
        result.append({
            "type": "image",
            "source": {
                "type": "base64",
                "media_type": media_type,
                "data": base64.standard_b64encode(data).decode("utf-8"),
            },
        })
    return result

def _parse_claude_crop(text: str, crop_type: str) -> dict:
    """
    Extract structured fields from Claude's text response.
    Claude is prompted to return a pipe-delimited block; this parses it.
    """
    lines = {
        k.strip().lower(): v.strip()
        for line in text.splitlines()
        if "|" in line
        for k, v in [line.split("|", 1)]
    }
    return {
        "disease":    lines.get("disease",    "Unknown"),
        "confidence": float(lines.get("confidence", "70").replace("%", "")),
        "cause":      lines.get("cause",      "Unknown cause"),
        "urgency":    lines.get("urgency",    "Medium"),
        "first_aid":  lines.get("first_aid",  "Isolate affected plants immediately."),
        "medication": lines.get("medication", "Consult an agronomist."),
        "referral":   lines.get("referral",   "Contact an agronomist if symptoms spread beyond 30% of crop."),
        "crop_type":  crop_type,
    }

def _parse_claude_soil(text: str) -> dict:
    lines = {
        k.strip().lower(): v.strip()
        for line in text.splitlines()
        if "|" in line
        for k, v in [line.split("|", 1)]
    }
    return {
        "condition":     lines.get("condition",      "Unknown"),
        "confidence":    float(lines.get("confidence", "70").replace("%", "")),
        "nutrients":     lines.get("nutrients",      "Assessment unavailable"),
        "ph_estimate":   lines.get("ph_estimate",    "Unknown"),
        "urgency":       lines.get("urgency",        "Medium"),
        "suitable_crops":lines.get("suitable_crops", "Consult an agronomist"),
        "recommendation":lines.get("recommendation", "Apply balanced NPK fertiliser."),
        "referral":      lines.get("referral",       "Consult an extension officer for a soil test."),
    }

def _parse_claude_livestock(text: str, animal_type: str) -> dict:
    lines = {
        k.strip().lower(): v.strip()
        for line in text.splitlines()
        if "|" in line
        for k, v in [line.split("|", 1)]
    }
    return {
        "disease":    lines.get("disease",    "Unknown"),
        "confidence": float(lines.get("confidence", "70").replace("%", "")),
        "cause":      lines.get("cause",      "Unknown cause"),
        "urgency":    lines.get("urgency",    "High"),
        "first_aid":  lines.get("first_aid",  "Isolate the animal immediately."),
        "medication": lines.get("medication", "Consult a veterinarian."),
        "referral":   lines.get("referral",   "Seek veterinary attention within 24 hours."),
        "animal_type": animal_type,
    }

# ── Routes ─────────────────────────────────────────────────────────────────────

@app.get("/")
async def root():
    return {
        "status": "online",
        "service": "MSAS FarmAI Inference Engine",
        "version": "3.0.0",
        "ai_backend": "Claude Vision" if ANTHROPIC_KEY else "not configured",
    }

@app.get("/health")
async def health():
    return {
        "status": "ok",
        "service": "MSAS FarmAI Inference Engine",
        "ai_ready": bool(ANTHROPIC_KEY),
    }

@app.post("/predict/crop")
async def predict_crop(
    request: Request,
    cropType: str = Form(...),
    cropPart: Optional[str] = Form("crop"),
    images: List[UploadFile] = File(...),
):
    _check_auth(request)
    client = _ai_client()

    image_blocks = await _read_images_b64(images)

    prompt = f"""You are an expert agricultural plant pathologist specialising in Nigerian and West African crops.

The farmer is scanning a {cropType} ({cropPart or 'whole plant'}) image for disease or nutrient issues.

Analyse the image(s) and respond ONLY in this exact pipe-delimited format (one field per line, no extra text):

disease | <disease or condition name, e.g. "Northern Leaf Blight">
confidence | <number 0-100, e.g. "87">
cause | <one-sentence cause, e.g. "Fungal infection by Exserohilum turcicum worsened by high humidity">
urgency | <one of: Low, Medium, High, Emergency>
first_aid | <2-3 immediate steps the farmer should take now>
medication | <specific product name and dosage, e.g. "Mancozeb 80WP — 2g per litre of water, spray every 7 days">
referral | <when to call an agronomist/extension officer>

If the image does not show a plant or crop, set disease to "Invalid image" and confidence to 0."""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = client.messages.create(
            model="claude-haiku-4-5-20251001",
            max_tokens=512,
            messages=[{"role": "user", "content": content}],
        )
        text = message.content[0].text
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    result = _parse_claude_crop(text, cropType)

    if result["disease"] == "Invalid image":
        raise HTTPException(status_code=422, detail={
            "accepted": False,
            "message": "Image does not appear to show a plant or crop. Please upload a clear photo of the affected plant part.",
        })

    return result

@app.post("/predict/livestock")
async def predict_livestock(
    request: Request,
    animalType: str = Form(...),
    assessmentType: str = Form(...),
    images: List[UploadFile] = File(default=[]),
):
    _check_auth(request)
    client = _ai_client()

    image_blocks = await _read_images_b64(images) if images else []

    prompt = f"""You are an expert veterinarian specialising in smallholder livestock in Nigeria and West Africa.

The farmer is reporting a health concern for a {animalType}. Assessment type: {assessmentType}.

{"Analyse the provided image(s) and " if image_blocks else "Based on the assessment type and "}respond ONLY in this exact pipe-delimited format (one field per line, no extra text):

disease | <condition name, e.g. "Foot and Mouth Disease" or "Internal Parasites">
confidence | <number 0-100>
cause | <one-sentence cause>
urgency | <one of: Low, Medium, High, Emergency>
first_aid | <2-3 immediate steps the farmer can take now before the vet arrives>
medication | <specific treatment name and dosage if applicable, or "Requires Vet prescription">
referral | <when and how urgently to contact a veterinarian>

If this is a behavioral-only assessment (no image), base your response on the most common {animalType} conditions for {assessmentType} presentations in West Africa."""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = client.messages.create(
            model="claude-haiku-4-5-20251001",
            max_tokens=512,
            messages=[{"role": "user", "content": content}],
        )
        text = message.content[0].text
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    return _parse_claude_livestock(text, animalType)

@app.post("/predict/soil")
async def predict_soil(
    request: Request,
    soilContext: Optional[str] = Form(None),
    images: List[UploadFile] = File(...),
):
    _check_auth(request)
    client = _ai_client()

    image_blocks = await _read_images_b64(images)

    context_note = f"\nAdditional context from the farmer: {soilContext}" if soilContext else ""

    prompt = f"""You are an expert soil scientist and agronomist specialising in Nigerian and West African farming soils.

The farmer has uploaded a photo of a soil sample for assessment.{context_note}

Analyse the image and respond ONLY in this exact pipe-delimited format (one field per line, no extra text):

condition | <overall soil condition, e.g. "Sandy loam — low organic matter">
confidence | <number 0-100>
nutrients | <apparent nutrient profile, e.g. "Low nitrogen, moderate phosphorus, likely adequate potassium">
ph_estimate | <estimated pH range, e.g. "6.0–6.5 (slightly acidic)">
urgency | <one of: Low, Medium, High, Emergency>
suitable_crops | <2-4 crops well-suited to this soil, e.g. "Cowpea, Sorghum, Groundnut">
recommendation | <specific amendment advice, e.g. "Apply 2 bags of NPK 15:15:15 per hectare, add compost to improve water retention">
referral | <when to get a laboratory soil test>

If the image does not clearly show soil, set condition to "Invalid image" and confidence to 0."""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = client.messages.create(
            model="claude-haiku-4-5-20251001",
            max_tokens=512,
            messages=[{"role": "user", "content": content}],
        )
        text = message.content[0].text
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    result = _parse_claude_soil(text)

    if result["condition"] == "Invalid image":
        raise HTTPException(status_code=422, detail={
            "accepted": False,
            "message": "Image does not appear to show a soil sample. Please upload a clear photo of the soil.",
        })

    return result

if __name__ == "__main__":
    port = int(os.environ.get("PORT", 8001))
    uvicorn.run(app, host="0.0.0.0", port=port)
