import os
import base64
from typing import List, Optional
from fastapi import FastAPI, UploadFile, File, Form, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
import anthropic
import uvicorn

app = FastAPI(title="MSAS FarmAI Inference Engine", version="4.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── Config ────────────────────────────────────────────────────────────────────

API_KEY       = os.environ.get("API_KEY", "")
ANTHROPIC_KEY = os.environ.get("ANTHROPIC_API_KEY", "")
AI_MODEL      = os.environ.get("AI_MODEL", "claude-sonnet-5")

# ── Auth ──────────────────────────────────────────────────────────────────────

def _check_auth(request: Request):
    if not API_KEY:
        return
    auth = request.headers.get("Authorization", "")
    if auth != f"Bearer {API_KEY}":
        raise HTTPException(status_code=401, detail="Unauthorized.")

def _ai_client() -> anthropic.Anthropic:
    if not ANTHROPIC_KEY:
        raise HTTPException(status_code=503, detail="AI engine not configured (missing ANTHROPIC_API_KEY).")
    return anthropic.Anthropic(api_key=ANTHROPIC_KEY)

# ── Helpers ───────────────────────────────────────────────────────────────────

async def _read_images_b64(images: List[UploadFile]) -> List[dict]:
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

def _parse_pipe(text: str) -> dict:
    """Parse Claude's pipe-delimited response into a dict."""
    result = {}
    for line in text.splitlines():
        if "|" in line:
            key, _, value = line.partition("|")
            result[key.strip().lower()] = value.strip()
    return result

def _safe_float(val: str, default: float = 0.0) -> float:
    try:
        return float(str(val).replace("%", "").strip())
    except (ValueError, TypeError):
        return default

# ── Routes ────────────────────────────────────────────────────────────────────

@app.get("/")
async def root():
    return {
        "status": "online",
        "service": "MSAS FarmAI Inference Engine",
        "version": "4.0.0",
        "model": AI_MODEL,
        "ai_backend": "Claude Vision" if ANTHROPIC_KEY else "not configured",
    }

@app.get("/health")
async def health():
    return {
        "status": "ok",
        "service": "MSAS FarmAI Inference Engine",
        "ai_ready": bool(ANTHROPIC_KEY),
        "model": AI_MODEL,
    }

# ─── /predict/crop ────────────────────────────────────────────────────────────

@app.post("/predict/crop")
async def predict_crop(
    request: Request,
    cropType: Optional[str] = Form(None),   # optional hint — AI auto-detects
    cropPart: Optional[str] = Form(None),   # optional hint — AI auto-detects
    images: List[UploadFile] = File(...),
):
    _check_auth(request)
    client = _ai_client()
    image_blocks = await _read_images_b64(images)

    hint = ""
    if cropType:
        hint += f" The farmer reports this is a {cropType}."
    if cropPart:
        hint += f" The photographed area is the {cropPart}."
    if hint:
        hint = f"\n\nFarmer hint:{hint}"

    prompt = f"""You are a world-class agricultural scientist, plant pathologist, and agronomist with deep expertise in Nigerian, West African, and tropical farming systems.

Carefully examine the uploaded image of a crop or plant and provide a thorough, accurate diagnosis.{hint}

IMPORTANT GUIDELINES:
- Auto-identify the plant species and the specific plant part visible in the image — do not rely solely on the farmer's hint
- Report your ACTUAL confidence based on image quality and symptom clarity — never inflate scores
- If the image is unclear or low-quality, lower your confidence accordingly
- If the plant appears healthy, say so clearly
- Be specific: name diseases, deficiencies, and pests precisely

Respond ONLY in this exact pipe-delimited format (one field per line, no extra text before or after):

subject_name | <common crop name, e.g. "Cassava", "Maize", "Tomato", "Pepper", or "Unknown" if unidentifiable>
scientific_name | <scientific name, e.g. "Manihot esculenta", or "Unknown">
detected_part | <specific plant organ visible: Leaf, Stem, Fruit, Root, Flower, Seed, Whole Plant, or Unknown>
health_status | <one of: Healthy, Diseased, Nutrient-Deficient, Pest-Affected, Water-Stressed, Multi-Issue, or Uncertain>
disease | <precise disease or condition name; "Healthy — No disease detected" if healthy>
confidence | <integer 0–100 — your honest certainty given image quality and symptom evidence>
severity | <one of: None, Mild, Moderate, Severe, Critical>
symptoms_identified | <2–4 specific visual symptoms you observed in the image, comma-separated>
cause | <one sentence: the root pathological or physiological cause>
environmental_factors | <temperature, humidity, drainage, or management factors likely contributing>
nutrient_deficiencies | <specific nutrient(s) deficient and visible signs, or "None detected">
pest_detection | <pest name and evidence observed, or "No pest detected">
urgency | <one of: Low, Medium, High, Emergency>
first_aid | <3 immediate, actionable steps numbered: 1) ... 2) ... 3) ...>
medication | <specific product name, active ingredient, concentration, dosage, and application method>
preventive_measures | <2–3 specific prevention strategies for this exact issue>
fertilizer_recommendation | <specific fertilizer, NPK ratio, application rate per hectare, and timing>
recovery_period | <realistic estimated time to recovery with proper treatment, e.g. "2–4 weeks">
best_practices | <1–2 long-term crop management tips to prevent recurrence>
referral | <specific threshold or signs that should trigger escalation to an agronomist or extension officer>
explanation | <2–3 sentences explaining exactly WHICH visual features in the image led to this diagnosis — be specific about colours, patterns, textures you observed>{hint}

If the image does not show a plant or crop (e.g. it shows a person, animal, or unrelated object), set:
subject_name | Not a plant
disease | Invalid image — please upload a clear photo of the affected plant or crop part
confidence | 0
severity | None"""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = client.messages.create(
            model=AI_MODEL,
            max_tokens=1536,
            temperature=0.2,
            messages=[{"role": "user", "content": content}],
        )
        text = message.content[0].text
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    fields = _parse_pipe(text)

    if fields.get("subject_name", "").lower() == "not a plant":
        raise HTTPException(status_code=422, detail={
            "accepted": False,
            "message": "Image does not appear to show a plant or crop. Please upload a clear photo of the affected plant part.",
        })

    return {
        "subject_name":             fields.get("subject_name",           "Unknown"),
        "scientific_name":          fields.get("scientific_name",         "Unknown"),
        "detected_part":            fields.get("detected_part",           "Unknown"),
        "health_status":            fields.get("health_status",           "Uncertain"),
        "disease":                  fields.get("disease",                 "Unknown"),
        "confidence":               _safe_float(fields.get("confidence",  "0")),
        "severity":                 fields.get("severity",                "Moderate"),
        "symptoms_identified":      fields.get("symptoms_identified",     ""),
        "cause":                    fields.get("cause",                   ""),
        "environmental_factors":    fields.get("environmental_factors",   ""),
        "nutrient_deficiencies":    fields.get("nutrient_deficiencies",   "None detected"),
        "pest_detection":           fields.get("pest_detection",          "No pest detected"),
        "urgency":                  fields.get("urgency",                 "Medium"),
        "first_aid":                fields.get("first_aid",               ""),
        "medication":               fields.get("medication",              "Consult an agronomist."),
        "preventive_measures":      fields.get("preventive_measures",     ""),
        "fertilizer_recommendation":fields.get("fertilizer_recommendation",""),
        "recovery_period":          fields.get("recovery_period",         ""),
        "best_practices":           fields.get("best_practices",          ""),
        "referral":                 fields.get("referral",                "Contact an agronomist if symptoms persist."),
        "explanation":              fields.get("explanation",             ""),
        "scan_type":                "crop",
    }

# ─── /predict/livestock ───────────────────────────────────────────────────────

@app.post("/predict/livestock")
async def predict_livestock(
    request: Request,
    animalType: Optional[str] = Form(None),      # optional hint — AI auto-detects
    assessmentType: Optional[str] = Form(None),  # optional hint
    images: List[UploadFile] = File(default=[]),
):
    _check_auth(request)
    client = _ai_client()
    image_blocks = await _read_images_b64(images) if images else []

    hint = ""
    if animalType:
        hint += f" The farmer reports this is a {animalType}."
    if assessmentType:
        hint += f" Assessment area: {assessmentType}."
    if hint:
        hint = f"\n\nFarmer hint:{hint}"

    has_image = bool(image_blocks)
    image_instruction = (
        "Carefully examine the uploaded image and provide a thorough visual diagnosis."
        if has_image else
        "No image was provided. Base your assessment on common conditions in West African smallholder livestock."
    )

    prompt = f"""You are a world-class veterinarian and livestock health expert with deep expertise in Nigerian and West African smallholder farming systems.

{image_instruction}{hint}

IMPORTANT GUIDELINES:
- Auto-identify the animal species and breed if visible — do not rely solely on the farmer's hint
- Report your ACTUAL confidence based on visible signs — never inflate scores
- If no image is provided, lower confidence significantly and state this limitation
- Be specific: name exact conditions, parasites, diseases

Respond ONLY in this exact pipe-delimited format (one field per line, no extra text):

subject_name | <animal species, e.g. "Cattle", "Goat", "Broiler Chicken", "Pig", or "Unknown">
scientific_name | <scientific name if relevant, e.g. "Bos taurus", or "N/A">
breed | <breed if identifiable, e.g. "White Leghorn", "Sokoto Gudali", or "Unknown">
detected_part | <body area examined: Skin/Coat, Eyes, Hooves/Feet, Droppings, Wound/Lesion, Whole Body, or Unknown>
health_status | <one of: Healthy, Diseased, Injured, Malnourished, Parasite-Infested, Stressed, or Uncertain>
disease | <condition name, e.g. "Newcastle Disease", "Foot Rot", "Mange"; or "Healthy — No condition detected">
confidence | <integer 0–100 — your honest certainty>
severity | <one of: None, Mild, Moderate, Severe, Critical>
symptoms_identified | <2–4 specific visual or reported symptoms, comma-separated>
cause | <one sentence: root pathological or physiological cause>
environmental_factors | <housing, feed, management, or climate factors contributing>
urgency | <one of: Low, Medium, High, Emergency>
first_aid | <3 immediate steps numbered: 1) ... 2) ... 3) ...>
medication | <specific veterinary product, dosage, route of administration, and duration>
preventive_measures | <2–3 specific prevention strategies>
vet_recommendation | <specific veterinary actions: vaccinations due, tests needed, referral urgency>
recovery_period | <realistic estimated recovery time with treatment>
best_practices | <1–2 long-term herd/flock management tips>
referral | <specific signs or timeframe that require an emergency vet call>
explanation | <2–3 sentences explaining WHICH specific visual signs led to this diagnosis>{hint}

If the image does not show a livestock animal, set subject_name to "Not livestock" and disease to "Invalid image".
If no image was provided, set confidence no higher than 30 and note the limitation in the explanation."""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = client.messages.create(
            model=AI_MODEL,
            max_tokens=1536,
            temperature=0.2,
            messages=[{"role": "user", "content": content}],
        )
        text = message.content[0].text
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    fields = _parse_pipe(text)

    if fields.get("subject_name", "").lower() == "not livestock":
        raise HTTPException(status_code=422, detail={
            "accepted": False,
            "message": "Image does not appear to show livestock. Please upload a clear photo of the affected animal.",
        })

    return {
        "subject_name":         fields.get("subject_name",         "Unknown"),
        "scientific_name":      fields.get("scientific_name",       "N/A"),
        "breed":                fields.get("breed",                 "Unknown"),
        "detected_part":        fields.get("detected_part",         "Unknown"),
        "health_status":        fields.get("health_status",         "Uncertain"),
        "disease":              fields.get("disease",               "Unknown"),
        "confidence":           _safe_float(fields.get("confidence","0")),
        "severity":             fields.get("severity",              "Moderate"),
        "symptoms_identified":  fields.get("symptoms_identified",   ""),
        "cause":                fields.get("cause",                 ""),
        "environmental_factors":fields.get("environmental_factors", ""),
        "urgency":              fields.get("urgency",               "High"),
        "first_aid":            fields.get("first_aid",             "Isolate the animal immediately."),
        "medication":           fields.get("medication",            "Requires veterinary prescription."),
        "preventive_measures":  fields.get("preventive_measures",   ""),
        "vet_recommendation":   fields.get("vet_recommendation",    ""),
        "recovery_period":      fields.get("recovery_period",       ""),
        "best_practices":       fields.get("best_practices",        ""),
        "referral":             fields.get("referral",              "Seek veterinary attention within 24 hours."),
        "explanation":          fields.get("explanation",           ""),
        "scan_type":            "livestock",
    }

# ─── /predict/soil ────────────────────────────────────────────────────────────

@app.post("/predict/soil")
async def predict_soil(
    request: Request,
    soilContext: Optional[str] = Form(None),
    images: List[UploadFile] = File(...),
):
    _check_auth(request)
    client = _ai_client()
    image_blocks = await _read_images_b64(images)

    context_note = f"\nFarmer context: {soilContext}" if soilContext else ""

    prompt = f"""You are a world-class soil scientist, pedologist, and agronomist with deep expertise in Nigerian and West African farming soils.

Carefully examine the uploaded soil sample image and provide a thorough visual assessment.{context_note}

IMPORTANT GUIDELINES:
- Assess visual soil properties: texture, structure, colour (Munsell if possible), organic matter, moisture
- Be specific about what you can and cannot determine from a visual assessment alone
- Confidence should reflect the limitation of visual-only assessment — a lab test is always more definitive

Respond ONLY in this exact pipe-delimited format (one field per line, no extra text):

subject_name | <soil type description, e.g. "Sandy Loam", "Clay", "Laterite", "Loamy Sand">
health_status | <one of: Good, Fair, Poor, Degraded, Compacted, Waterlogged>
confidence | <integer 0–100 — visual assessment only; lab test needed for certainty>
ph_estimate | <estimated pH range, e.g. "6.0–6.5 (slightly acidic)"; "Unknown" if cannot determine>
nutrients | <apparent nutrient profile based on visual cues, e.g. "Likely low nitrogen, moderate phosphorus">
nutrient_deficiencies | <specific nutrient deficiencies suspected, or "None visually apparent">
urgency | <one of: Low, Medium, High>
suitable_crops | <3–5 crops best suited to this soil type and condition>
fertilizer_recommendation | <specific fertilizer, NPK ratio, kg per hectare, and application timing>
amendment_recommendation | <organic matter, lime, gypsum, or other amendments with quantities>
pest_detection | <soil pests or signs of nematode damage if visible, or "None detected">
recovery_period | <time to improve soil health with recommended amendments>
best_practices | <2–3 long-term soil health management tips>
referral | <specific reason and timing for seeking a laboratory soil test>
explanation | <2–3 sentences explaining WHICH visual properties (colour, texture, structure) informed this assessment>

If the image does not clearly show soil, set:
subject_name | Invalid image
confidence | 0"""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = client.messages.create(
            model=AI_MODEL,
            max_tokens=1024,
            temperature=0.2,
            messages=[{"role": "user", "content": content}],
        )
        text = message.content[0].text
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    fields = _parse_pipe(text)

    if fields.get("subject_name", "").lower() == "invalid image":
        raise HTTPException(status_code=422, detail={
            "accepted": False,
            "message": "Image does not appear to show a soil sample. Please upload a clear photo of the soil.",
        })

    return {
        "subject_name":              fields.get("subject_name",              "Unknown soil type"),
        "health_status":             fields.get("health_status",             "Unknown"),
        "confidence":                _safe_float(fields.get("confidence",    "0")),
        "ph_estimate":               fields.get("ph_estimate",               "Unknown"),
        "nutrients":                 fields.get("nutrients",                  "Assessment unavailable"),
        "nutrient_deficiencies":     fields.get("nutrient_deficiencies",     "None visually apparent"),
        "urgency":                   fields.get("urgency",                   "Medium"),
        "suitable_crops":            fields.get("suitable_crops",            "Consult an agronomist"),
        "fertilizer_recommendation": fields.get("fertilizer_recommendation", ""),
        "amendment_recommendation":  fields.get("amendment_recommendation",  ""),
        "pest_detection":            fields.get("pest_detection",            "None detected"),
        "recovery_period":           fields.get("recovery_period",           ""),
        "best_practices":            fields.get("best_practices",            ""),
        "referral":                  fields.get("referral",                  "Consult an extension officer for a soil test."),
        "explanation":               fields.get("explanation",               ""),
        "condition":                 fields.get("subject_name",              "Unknown"),  # backward compat
        "recommendation":            fields.get("fertilizer_recommendation", "Apply balanced NPK fertiliser."),
        "scan_type":                 "soil",
    }

# ─── /translate ───────────────────────────────────────────────────────────────

@app.post("/translate")
async def translate_text(
    request: Request,
    text: str = Form(...),
    target_language: str = Form(...),
):
    """Translate a diagnosis report text into the target language."""
    _check_auth(request)
    client = _ai_client()

    language_map = {
        "ha": "Hausa",
        "fr": "French",
        "yo": "Yoruba",
        "ig": "Igbo",
        "ar": "Arabic",
        "sw": "Swahili",
        "en": "English",
    }
    lang_name = language_map.get(target_language.lower(), target_language)

    if target_language.lower() == "en":
        return {"translated_text": text, "language": "English"}

    prompt = f"""Translate the following agricultural diagnosis report into {lang_name}.

Keep all technical terms accurate. For Hausa, Yoruba, and Igbo, use natural everyday language that farmers in Nigeria would understand. Keep the same structure and numbered lists.

Text to translate:
{text}

Provide ONLY the translated text, no explanations or meta-commentary."""

    try:
        message = client.messages.create(
            model=AI_MODEL,
            max_tokens=1024,
            messages=[{"role": "user", "content": prompt}],
        )
        translated = message.content[0].text.strip()
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"Translation error: {str(e)}")

    return {"translated_text": translated, "language": lang_name}


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 8001))
    uvicorn.run(app, host="0.0.0.0", port=port)
