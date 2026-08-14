import os
import json
import base64
from typing import List, Optional
from fastapi import FastAPI, UploadFile, File, Form, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
import anthropic
import uvicorn

app = FastAPI(title="MSAS FarmAI Inference Engine", version="5.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── Config ────────────────────────────────────────────────────────────────────

API_KEY        = os.environ.get("API_KEY", "")
ANTHROPIC_KEY  = os.environ.get("ANTHROPIC_API_KEY", "")
AI_MODEL       = os.environ.get("AI_MODEL", "claude-sonnet-5")          # vision + standard text
AI_MODEL_PRO   = os.environ.get("AI_MODEL_PRO", "claude-opus-4-8")      # high-accuracy text tasks

_async_client: Optional[anthropic.AsyncAnthropic] = None

def _get_client() -> anthropic.AsyncAnthropic:
    global _async_client
    if not ANTHROPIC_KEY:
        raise HTTPException(status_code=503, detail="AI engine not configured (missing ANTHROPIC_API_KEY).")
    if _async_client is None:
        _async_client = anthropic.AsyncAnthropic(api_key=ANTHROPIC_KEY)
    return _async_client

def _pick_model(quality: str = "standard") -> str:
    return AI_MODEL_PRO if quality == "high" else AI_MODEL

# ── Auth ──────────────────────────────────────────────────────────────────────

def _check_auth(request: Request):
    if not API_KEY:
        return
    auth = request.headers.get("Authorization", "")
    if auth != f"Bearer {API_KEY}":
        raise HTTPException(status_code=401, detail="Unauthorized.")

# ── Language map ──────────────────────────────────────────────────────────────

LANGUAGES = {
    "en": "English", "ha": "Hausa", "yo": "Yoruba",
    "ig": "Igbo",    "fr": "French", "ar": "Arabic", "sw": "Swahili",
    "ff": "Fulfulde",
}

def _lang_instruction(language: str) -> str:
    lang = LANGUAGES.get(language.lower(), "")
    if not lang or language.lower() == "en":
        return ""
    return f"\n\nIMPORTANT: Respond entirely in {lang}. Use simple, everyday language that Nigerian smallholder farmers would easily understand."

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

def _extract_text(message) -> str:
    """Return the first text block from a Claude response.

    Extended-thinking models (claude-sonnet-5 and similar) can emit a
    ThinkingBlock as content[0] before the actual TextBlock answer,
    especially for complex tasks like image analysis — blindly indexing
    content[0].text then throws AttributeError. Skip any non-text blocks
    (thinking, redacted_thinking, etc.) and return the first real one.
    """
    for block in message.content:
        if getattr(block, "type", None) == "text":
            return block.text
    raise HTTPException(status_code=503, detail="AI service error: model returned no text content")

# ── Status ────────────────────────────────────────────────────────────────────

@app.get("/")
async def root():
    return {
        "status": "online",
        "service": "MSAS FarmAI Inference Engine",
        "version": "5.0.0",
        "model": AI_MODEL,
        "model_pro": AI_MODEL_PRO,
        "endpoints": [
            "/predict/crop", "/predict/livestock", "/predict/soil",
            "/predict/pest", "/predict/weather", "/predict/market",
            "/chat", "/voice", "/translate",
        ],
        "ai_backend": "Claude Vision + Claude Opus" if ANTHROPIC_KEY else "not configured",
    }

@app.get("/health")
async def health():
    return {
        "status": "ok",
        "service": "MSAS FarmAI Inference Engine",
        "ai_ready": bool(ANTHROPIC_KEY),
        "model": AI_MODEL,
        "model_pro": AI_MODEL_PRO,
    }

# ─── /predict/crop ────────────────────────────────────────────────────────────

@app.post("/predict/crop")
async def predict_crop(
    request: Request,
    cropType: Optional[str] = Form(None),
    cropPart: Optional[str] = Form(None),
    language: Optional[str] = Form("en"),
    images: List[UploadFile] = File(...),
):
    _check_auth(request)
    client = _get_client()
    image_blocks = await _read_images_b64(images)

    hint = ""
    if cropType: hint += f" The farmer reports this is a {cropType}."
    if cropPart: hint += f" The photographed area is the {cropPart}."
    if hint:     hint = f"\n\nFarmer hint:{hint}"

    lang_note = _lang_instruction(language or "en")

    prompt = f"""You are a world-class agricultural scientist, plant pathologist, and agronomist with deep expertise in Nigerian, West African, and tropical farming systems.

Carefully examine the uploaded image of a crop or plant and provide a thorough, accurate diagnosis.{hint}

IMPORTANT GUIDELINES:
- Auto-identify the plant species and the specific plant part visible in the image
- Report your ACTUAL confidence based on image quality and symptom clarity — never inflate scores
- If the image is unclear or low-quality, lower your confidence accordingly
- If the plant appears healthy, say so clearly
- Be specific: name diseases, deficiencies, and pests precisely

Respond ONLY in this exact pipe-delimited format (one field per line, no extra text before or after):

subject_name | <common crop name, e.g. "Cassava", "Maize", "Tomato", "Pepper", or "Unknown">
scientific_name | <scientific name, e.g. "Manihot esculenta", or "Unknown">
detected_part | <Leaf, Stem, Fruit, Root, Flower, Seed, Whole Plant, or Unknown>
health_status | <Healthy, Diseased, Nutrient-Deficient, Pest-Affected, Water-Stressed, Multi-Issue, or Uncertain>
disease | <precise disease name; "Healthy — No disease detected" if healthy>
confidence | <integer 0–100>
severity | <None, Mild, Moderate, Severe, Critical>
symptoms_identified | <2–4 specific visual symptoms, comma-separated>
cause | <one sentence: root pathological or physiological cause>
environmental_factors | <temperature, humidity, drainage, or management factors>
nutrient_deficiencies | <specific nutrient(s) and visible signs, or "None detected">
pest_detection | <pest name and evidence, or "No pest detected">
urgency | <Low, Medium, High, Emergency>
first_aid | <3 immediate actionable steps numbered: 1) ... 2) ... 3) ...>
medication | <specific product, active ingredient, concentration, dosage, application method>
preventive_measures | <2–3 specific prevention strategies>
fertilizer_recommendation | <specific fertilizer, NPK ratio, kg per hectare, timing>
recovery_period | <realistic time to recovery, e.g. "2–4 weeks">
best_practices | <1–2 long-term management tips>
referral | <specific threshold or signs to escalate to agronomist>
explanation | <2–3 sentences explaining WHICH visual features led to this diagnosis>

If the image does not show a plant, set:
subject_name | Not a plant
disease | Invalid image — please upload a clear photo of the affected plant or crop part
confidence | 0
severity | None{lang_note}"""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = await client.messages.create(
            model=AI_MODEL, max_tokens=1536,
            messages=[{"role": "user", "content": content}],
        )
        text = _extract_text(message)
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    fields = _parse_pipe(text)

    if fields.get("subject_name", "").lower() == "not a plant":
        raise HTTPException(status_code=422, detail={
            "accepted": False,
            "message": "Image does not appear to show a plant or crop. Please upload a clear photo of the affected plant part.",
        })

    return {
        "subject_name":              fields.get("subject_name",             "Unknown"),
        "scientific_name":           fields.get("scientific_name",          "Unknown"),
        "detected_part":             fields.get("detected_part",            "Unknown"),
        "health_status":             fields.get("health_status",            "Uncertain"),
        "disease":                   fields.get("disease",                  "Unknown"),
        "confidence":                _safe_float(fields.get("confidence",   "0")),
        "severity":                  fields.get("severity",                 "Moderate"),
        "symptoms_identified":       fields.get("symptoms_identified",      ""),
        "cause":                     fields.get("cause",                    ""),
        "environmental_factors":     fields.get("environmental_factors",    ""),
        "nutrient_deficiencies":     fields.get("nutrient_deficiencies",    "None detected"),
        "pest_detection":            fields.get("pest_detection",           "No pest detected"),
        "urgency":                   fields.get("urgency",                  "Medium"),
        "first_aid":                 fields.get("first_aid",                ""),
        "medication":                fields.get("medication",               "Consult an agronomist."),
        "preventive_measures":       fields.get("preventive_measures",      ""),
        "fertilizer_recommendation": fields.get("fertilizer_recommendation",""),
        "recovery_period":           fields.get("recovery_period",          ""),
        "best_practices":            fields.get("best_practices",           ""),
        "referral":                  fields.get("referral",                 "Contact an agronomist if symptoms persist."),
        "explanation":               fields.get("explanation",              ""),
        "scan_type":                 "crop",
    }

# ─── /predict/livestock ───────────────────────────────────────────────────────

@app.post("/predict/livestock")
async def predict_livestock(
    request: Request,
    animalType: Optional[str] = Form(None),
    assessmentType: Optional[str] = Form(None),
    language: Optional[str] = Form("en"),
    images: List[UploadFile] = File(default=[]),
):
    _check_auth(request)
    client = _get_client()
    image_blocks = await _read_images_b64(images) if images else []

    hint = ""
    if animalType:     hint += f" The farmer reports this is a {animalType}."
    if assessmentType: hint += f" Assessment area: {assessmentType}."
    if hint:           hint = f"\n\nFarmer hint:{hint}"

    has_image = bool(image_blocks)
    image_instruction = (
        "Carefully examine the uploaded image and provide a thorough visual diagnosis."
        if has_image else
        "No image was provided. Base your assessment on common conditions in West African smallholder livestock."
    )

    lang_note = _lang_instruction(language or "en")

    prompt = f"""You are a world-class veterinarian and livestock health expert with deep expertise in Nigerian and West African smallholder farming systems.

{image_instruction}{hint}

IMPORTANT GUIDELINES:
- Auto-identify the animal species and breed if visible
- Report your ACTUAL confidence based on visible signs — never inflate scores
- If no image is provided, lower confidence significantly and state this limitation

Respond ONLY in this exact pipe-delimited format (one field per line, no extra text):

subject_name | <animal species, e.g. "Cattle", "Goat", "Broiler Chicken", "Pig", or "Unknown">
scientific_name | <scientific name, e.g. "Bos taurus", or "N/A">
breed | <breed if identifiable, or "Unknown">
detected_part | <Skin/Coat, Eyes, Hooves/Feet, Droppings, Wound/Lesion, Whole Body, or Unknown>
health_status | <Healthy, Diseased, Injured, Malnourished, Parasite-Infested, Stressed, or Uncertain>
disease | <condition name; or "Healthy — No condition detected">
confidence | <integer 0–100>
severity | <None, Mild, Moderate, Severe, Critical>
symptoms_identified | <2–4 specific visual or reported symptoms, comma-separated>
cause | <one sentence: root pathological or physiological cause>
environmental_factors | <housing, feed, management, or climate factors>
urgency | <Low, Medium, High, Emergency>
first_aid | <3 immediate steps numbered: 1) ... 2) ... 3) ...>
medication | <specific veterinary product, dosage, route, duration>
preventive_measures | <2–3 specific prevention strategies>
vet_recommendation | <vaccinations due, tests needed, referral urgency>
recovery_period | <realistic recovery time with treatment>
best_practices | <1–2 long-term herd/flock management tips>
referral | <specific signs or timeframe requiring emergency vet>
explanation | <2–3 sentences on WHICH specific visual signs led to this diagnosis>

If the image does not show livestock, set subject_name to "Not livestock" and disease to "Invalid image".
If no image was provided, set confidence no higher than 30.{lang_note}"""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = await client.messages.create(
            model=AI_MODEL, max_tokens=1536,
            messages=[{"role": "user", "content": content}],
        )
        text = _extract_text(message)
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
    language: Optional[str] = Form("en"),
    images: List[UploadFile] = File(...),
):
    _check_auth(request)
    client = _get_client()
    image_blocks = await _read_images_b64(images)

    context_note = f"\nFarmer context: {soilContext}" if soilContext else ""
    lang_note    = _lang_instruction(language or "en")

    prompt = f"""You are a world-class soil scientist, pedologist, and agronomist with deep expertise in Nigerian and West African farming soils.

Carefully examine the uploaded soil sample image and provide a thorough visual assessment.{context_note}

IMPORTANT GUIDELINES:
- Assess visual soil properties: texture, structure, colour, organic matter, moisture
- Confidence should reflect the limitation of visual-only assessment — lab test is always more definitive

Respond ONLY in this exact pipe-delimited format (one field per line, no extra text):

subject_name | <soil type, e.g. "Sandy Loam", "Clay", "Laterite", "Loamy Sand">
health_status | <Good, Fair, Poor, Degraded, Compacted, Waterlogged>
confidence | <integer 0–100>
ph_estimate | <estimated pH range, e.g. "6.0–6.5 (slightly acidic)"; "Unknown" if cannot determine>
nutrients | <apparent nutrient profile based on visual cues>
nutrient_deficiencies | <specific deficiencies suspected, or "None visually apparent">
urgency | <Low, Medium, High>
suitable_crops | <3–5 crops best suited to this soil type and condition>
fertilizer_recommendation | <specific fertilizer, NPK ratio, kg per hectare, timing>
amendment_recommendation | <organic matter, lime, gypsum, or other amendments with quantities>
pest_detection | <soil pests or nematode damage if visible, or "None detected">
recovery_period | <time to improve soil health with recommended amendments>
best_practices | <2–3 long-term soil health management tips>
referral | <specific reason and timing for a laboratory soil test>
explanation | <2–3 sentences on WHICH visual properties informed this assessment>

If the image does not clearly show soil, set:
subject_name | Invalid image
confidence | 0{lang_note}"""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = await client.messages.create(
            model=AI_MODEL, max_tokens=1024,
            messages=[{"role": "user", "content": content}],
        )
        text = _extract_text(message)
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
        "condition":                 fields.get("subject_name",              "Unknown"),
        "recommendation":            fields.get("fertilizer_recommendation", "Apply balanced NPK fertiliser."),
        "scan_type":                 "soil",
    }

# ─── /predict/pest ────────────────────────────────────────────────────────────

@app.post("/predict/pest")
async def predict_pest(
    request: Request,
    cropType: Optional[str] = Form(None),
    location: Optional[str] = Form(None),
    language: Optional[str] = Form("en"),
    images: List[UploadFile] = File(...),
):
    """Standalone pest identification — insect, weed, or pathogen from an image."""
    _check_auth(request)
    client = _get_client()
    image_blocks = await _read_images_b64(images)

    context = ""
    if cropType:  context += f" Crop affected: {cropType}."
    if location:  context += f" Location: {location}."
    if context:   context = f"\n\nFarmer context:{context}"

    lang_note = _lang_instruction(language or "en")

    prompt = f"""You are a world-class entomologist, plant pathologist, and agronomist specialising in Nigerian and West African pest management.

Carefully examine the uploaded image and identify the pest, weed, or pathogen present.{context}

Respond ONLY in this exact pipe-delimited format:

pest_name | <common name, e.g. "Fall Armyworm", "Striga Weed", "Aphid Colony", "Cassava Mealybug">
scientific_name | <scientific name, or "Unknown">
pest_type | <Insect, Weed, Fungal Pathogen, Bacterial Pathogen, Nematode, Rodent, Bird, or Unknown>
host_crops | <crops this pest commonly attacks, comma-separated>
confidence | <integer 0–100>
severity | <None, Mild, Moderate, Severe, Critical>
damage_type | <what this pest does to the crop, e.g. "leaf mining, stem boring, root damage">
spread_risk | <Low, Medium, High — likelihood of spreading to neighbouring farms>
urgency | <Low, Medium, High, Emergency>
immediate_action | <3 steps to take right now numbered: 1) ... 2) ... 3) ...>
chemical_control | <specific pesticide, active ingredient, dilution rate, application method>
biological_control | <natural predators, biopesticides, or organic methods>
cultural_control | <crop rotation, intercropping, sanitation, or mechanical methods>
preventive_measures | <2–3 strategies to prevent future infestation>
economic_threshold | <at what infestation level should chemical control begin>
recovery_period | <estimated time to recovery with treatment>
referral | <when to escalate to an extension officer or plant health clinic>
explanation | <2–3 sentences on WHICH specific visual features identified this pest>

If the image does not show a pest, weed, or pathogen clearly, set:
pest_name | Not identified
confidence | 0{lang_note}"""

    content = image_blocks + [{"type": "text", "text": prompt}]

    try:
        message = await client.messages.create(
            model=AI_MODEL, max_tokens=1024,
            messages=[{"role": "user", "content": content}],
        )
        text = _extract_text(message)
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    fields = _parse_pipe(text)

    if fields.get("pest_name", "").lower() == "not identified":
        raise HTTPException(status_code=422, detail={
            "accepted": False,
            "message": "Could not identify a pest from this image. Please upload a clearer, closer photo.",
        })

    return {
        "pest_name":           fields.get("pest_name",           "Unknown"),
        "scientific_name":     fields.get("scientific_name",     "Unknown"),
        "pest_type":           fields.get("pest_type",           "Unknown"),
        "host_crops":          fields.get("host_crops",          ""),
        "confidence":          _safe_float(fields.get("confidence", "0")),
        "severity":            fields.get("severity",            "Moderate"),
        "damage_type":         fields.get("damage_type",         ""),
        "spread_risk":         fields.get("spread_risk",         "Medium"),
        "urgency":             fields.get("urgency",             "High"),
        "immediate_action":    fields.get("immediate_action",    ""),
        "chemical_control":    fields.get("chemical_control",    "Consult an agronomist."),
        "biological_control":  fields.get("biological_control",  ""),
        "cultural_control":    fields.get("cultural_control",    ""),
        "preventive_measures": fields.get("preventive_measures", ""),
        "economic_threshold":  fields.get("economic_threshold",  ""),
        "recovery_period":     fields.get("recovery_period",     ""),
        "referral":            fields.get("referral",            "Contact extension officer if infestation spreads."),
        "explanation":         fields.get("explanation",         ""),
        "scan_type":           "pest",
    }

# ─── /predict/weather ─────────────────────────────────────────────────────────

@app.post("/predict/weather")
async def predict_weather(
    request: Request,
    location: str = Form(...),
    season: Optional[str] = Form(None),
    crop_type: Optional[str] = Form(None),
    question: Optional[str] = Form(None),
    language: Optional[str] = Form("en"),
    quality: Optional[str] = Form("standard"),
):
    """Weather-based farming calendar and agronomic advice for a given location and season."""
    _check_auth(request)
    client = _get_client()
    model  = _pick_model(quality or "standard")

    context = f"Location: {location}."
    if season:     context += f" Season: {season}."
    if crop_type:  context += f" Crop: {crop_type}."
    if question:   context += f" Farmer question: {question}"

    lang_note = _lang_instruction(language or "en")

    prompt = f"""You are a senior agrometeorology expert and farming calendar specialist with deep knowledge of Nigerian and West African climate zones, rainfall patterns, and seasonal farming cycles.

Farmer context: {context}

Provide practical, location-specific weather and seasonal farming advice.

Respond ONLY in this exact pipe-delimited format:

season | <current or approaching season: Dry Season, Early Rains, Peak Rains, Late Rains, Harmattan>
climate_zone | <Sudan Savanna, Guinea Savanna, Forest Zone, Sahel, Coastal, or the specific Nigerian zone>
rainfall_outlook | <expected rainfall pattern for this period in this location>
temperature_range | <expected temperature range, e.g. "28–36°C">
humidity_level | <Low, Moderate, High>
planting_window | <optimal planting dates or weeks for this location and season>
recommended_crops | <5–7 crops best suited to plant NOW in this location and season>
crops_to_avoid | <crops NOT suitable for this season/location and why>
irrigation_advice | <specific irrigation guidance: frequency, method, water stress signs>
soil_preparation | <how to prepare soil for the upcoming season>
pest_season_alert | <pests and diseases to watch for this season in this region>
harvest_calendar | <what was planted earlier and is ready to harvest now>
storage_advice | <how to store harvested produce given current weather conditions>
farm_tasks | <5 priority farming tasks for this week/period numbered: 1) ... 2) ... 3) ... 4) ... 5) ...>
weather_risks | <flood risk, drought risk, harmattan damage — and how to mitigate>
market_timing | <when to sell based on seasonal price patterns for recommended crops>
answer | <direct answer to the farmer's specific question, if one was asked>{lang_note}"""

    try:
        message = await client.messages.create(
            model=model, max_tokens=1536,
            messages=[{"role": "user", "content": prompt}],
        )
        text = _extract_text(message)
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    fields = _parse_pipe(text)

    return {
        "season":               fields.get("season",              ""),
        "climate_zone":         fields.get("climate_zone",        ""),
        "rainfall_outlook":     fields.get("rainfall_outlook",    ""),
        "temperature_range":    fields.get("temperature_range",   ""),
        "humidity_level":       fields.get("humidity_level",      ""),
        "planting_window":      fields.get("planting_window",     ""),
        "recommended_crops":    fields.get("recommended_crops",   ""),
        "crops_to_avoid":       fields.get("crops_to_avoid",      ""),
        "irrigation_advice":    fields.get("irrigation_advice",   ""),
        "soil_preparation":     fields.get("soil_preparation",    ""),
        "pest_season_alert":    fields.get("pest_season_alert",   ""),
        "harvest_calendar":     fields.get("harvest_calendar",    ""),
        "storage_advice":       fields.get("storage_advice",      ""),
        "farm_tasks":           fields.get("farm_tasks",          ""),
        "weather_risks":        fields.get("weather_risks",       ""),
        "market_timing":        fields.get("market_timing",       ""),
        "answer":               fields.get("answer",              ""),
        "location":             location,
        "scan_type":            "weather",
    }

# ─── /predict/market ──────────────────────────────────────────────────────────

@app.post("/predict/market")
async def predict_market(
    request: Request,
    crop: str = Form(...),
    location: str = Form(...),
    quantity_kg: Optional[str] = Form(None),
    quality_grade: Optional[str] = Form(None),
    season: Optional[str] = Form(None),
    language: Optional[str] = Form("en"),
    quality: Optional[str] = Form("standard"),
):
    """Market price estimation, trading advice, and value chain guidance for Nigerian farmers."""
    _check_auth(request)
    client = _get_client()
    model  = _pick_model(quality or "standard")

    context = f"Crop: {crop}. Location: {location}."
    if quantity_kg:    context += f" Quantity: {quantity_kg} kg."
    if quality_grade:  context += f" Quality grade: {quality_grade}."
    if season:         context += f" Season: {season}."

    lang_note = _lang_instruction(language or "en")

    prompt = f"""You are a senior agricultural economist and market analyst with deep expertise in Nigerian commodity markets, including the Lagos Mile 12 market, Kano Dawanau grain market, and major produce markets across all Nigerian states.

Farmer context: {context}

Provide accurate, practical market intelligence and trading advice.

Respond ONLY in this exact pipe-delimited format:

crop | <standardised crop name>
price_range_naira | <realistic current farm-gate price range per kg or bag, e.g. "₦350–₦420/kg" or "₦18,000–₦22,000/100kg bag">
wholesale_price | <wholesale market price per kg or standard unit>
retail_price | <retail market price per kg or standard unit>
best_markets | <3–5 specific markets in or near {location} where this crop sells well>
market_days | <when are the best market days or periods to sell>
price_trend | <Rising, Stable, Falling — current price direction and why>
seasonal_pattern | <how price changes across the year: peak months, low months>
best_time_to_sell | <optimal month or season to sell for maximum price>
storage_potential | <can this crop be stored to wait for better prices? How long? What conditions?>
value_addition | <processing options that increase value, e.g. drying, milling, packaging>
buyers | <who to sell to: aggregators, processors, exporters, or direct retailers>
export_potential | <is there export demand? Which countries? Requirements?>
estimated_revenue | <estimated total revenue if selling {quantity_kg or "100"} kg at current prices>
price_risks | <what could cause price to fall: oversupply, imports, policy changes>
negotiation_tips | <2–3 practical tips to negotiate a better price>
cooperatives | <benefit of selling through a cooperative vs. individually in this market>
answer | <specific advice directly addressing the farmer's situation>{lang_note}"""

    try:
        message = await client.messages.create(
            model=model, max_tokens=1536,
            messages=[{"role": "user", "content": prompt}],
        )
        text = _extract_text(message)
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    fields = _parse_pipe(text)

    return {
        "crop":               fields.get("crop",               crop),
        "price_range_naira":  fields.get("price_range_naira",  "Price data unavailable for this location."),
        "wholesale_price":    fields.get("wholesale_price",    ""),
        "retail_price":       fields.get("retail_price",       ""),
        "best_markets":       fields.get("best_markets",       ""),
        "market_days":        fields.get("market_days",        ""),
        "price_trend":        fields.get("price_trend",        "Stable"),
        "seasonal_pattern":   fields.get("seasonal_pattern",   ""),
        "best_time_to_sell":  fields.get("best_time_to_sell",  ""),
        "storage_potential":  fields.get("storage_potential",  ""),
        "value_addition":     fields.get("value_addition",     ""),
        "buyers":             fields.get("buyers",             ""),
        "export_potential":   fields.get("export_potential",   ""),
        "estimated_revenue":  fields.get("estimated_revenue",  ""),
        "price_risks":        fields.get("price_risks",        ""),
        "negotiation_tips":   fields.get("negotiation_tips",   ""),
        "cooperatives":       fields.get("cooperatives",       ""),
        "answer":             fields.get("answer",             ""),
        "location":           location,
        "scan_type":          "market",
    }

# ─── /chat ────────────────────────────────────────────────────────────────────

@app.post("/chat")
async def chat(
    request: Request,
    message: str = Form(...),
    context: Optional[str] = Form(None),
    history: Optional[str] = Form(None),
    language: Optional[str] = Form("en"),
    quality: Optional[str] = Form("standard"),
):
    """Multi-turn conversational farming assistant for Nigerian smallholder farmers."""
    _check_auth(request)
    client = _get_client()
    model  = _pick_model(quality or "standard")

    lang_note = _lang_instruction(language or "en")

    system_prompt = f"""You are MSAS FarmAI, a knowledgeable, friendly, and practical farming assistant for Nigerian smallholder farmers. You have deep expertise in:

- Crop farming: maize, cassava, yam, tomatoes, peppers, cowpea, sorghum, millet, rice, cocoa, groundnut, sesame
- Livestock: cattle, goats, sheep, poultry (broilers and layers), pigs, fish farming
- Soil health, fertilizers, irrigation, and agrochemicals available in Nigeria
- Nigerian agricultural extension practices, state ADPs, and government programmes
- Market prices, value chains, cooperatives, and commodity trading
- Weather patterns and seasonal farming calendars across Nigeria's climate zones
- Pest and disease management with products available in Nigerian agro-dealer shops

COMMUNICATION RULES:
- Be concise and practical — farmers want actionable advice, not lectures
- Use simple language; avoid jargon unless you explain it
- Give specific product names, dosages, and local market prices where possible
- Always reference Nigerian conditions, markets, and locally available inputs
- If a farmer describes a problem, ask clarifying questions if needed before diagnosing
- Never make up information — if you are unsure, say so and suggest consulting an extension officer
{lang_note}"""

    # Parse conversation history
    messages_list = []
    if history:
        try:
            parsed = json.loads(history)
            if isinstance(parsed, list):
                messages_list = parsed
        except (json.JSONDecodeError, ValueError):
            pass

    # Add context as a system note if provided
    user_message = message
    if context:
        user_message = f"[Context: {context}]\n\n{message}"

    messages_list.append({"role": "user", "content": user_message})

    try:
        response = await client.messages.create(
            model=model,
            max_tokens=1024,
            system=system_prompt,
            messages=messages_list,
        )
        reply = _extract_text(response).strip()
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    # Build updated history for client to store and send back next turn
    messages_list.append({"role": "assistant", "content": reply})

    return {
        "reply":   reply,
        "history": messages_list[-20:],  # Keep last 20 turns to avoid context overflow
        "model":   model,
    }

# ─── /voice ───────────────────────────────────────────────────────────────────

@app.post("/voice")
async def voice_query(
    request: Request,
    text: str = Form(...),
    detected_language: Optional[str] = Form("en"),
    context: Optional[str] = Form(None),
    quality: Optional[str] = Form("standard"),
):
    """
    Voice query router — accepts transcribed voice text from the mobile app
    and returns a structured farming response. The mobile app handles
    recording + speech-to-text using device capabilities; this endpoint
    interprets the intent and routes to the appropriate AI response.
    """
    _check_auth(request)
    client = _get_client()
    model  = _pick_model(quality or "standard")

    lang_note = _lang_instruction(detected_language or "en")

    system_prompt = f"""You are MSAS FarmAI, a voice-activated farming assistant for Nigerian smallholder farmers. The farmer has spoken a question or described a problem.

Your job:
1. Understand what the farmer is asking (crop disease, livestock health, market price, weather, general farming advice)
2. Identify the INTENT from the voice transcription (even if the text is informal, incomplete, or has transcription errors)
3. Provide a clear, spoken-style answer — short paragraphs, no bullet points, conversational tone
4. End with one follow-up question to help the farmer get more specific advice

You have deep expertise in Nigerian crop farming, livestock, soil, market prices, and seasonal farming calendars.

If the transcription is too unclear to understand, politely ask the farmer to repeat their question.{lang_note}"""

    context_note = f"\n\nAdditional context: {context}" if context else ""

    try:
        response = await client.messages.create(
            model=model,
            max_tokens=512,
            system=system_prompt,
            messages=[{
                "role": "user",
                "content": f"Farmer said (voice transcription): \"{text}\"{context_note}"
            }],
        )
        reply = _extract_text(response).strip()
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"AI service error: {str(e)}")

    # Detect likely scan type from input text for client routing
    text_lower = text.lower()
    if any(w in text_lower for w in ["price", "market", "sell", "buy", "naira", "kg", "bag"]):
        intent = "market"
    elif any(w in text_lower for w in ["rain", "weather", "plant", "season", "when to", "harmattan"]):
        intent = "weather"
    elif any(w in text_lower for w in ["sick", "disease", "leaves", "crop", "farm", "cassava", "maize", "yam", "tomato"]):
        intent = "crop"
    elif any(w in text_lower for w in ["goat", "cow", "cattle", "chicken", "poultry", "animal", "livestock"]):
        intent = "livestock"
    else:
        intent = "chat"

    return {
        "reply":             reply,
        "detected_intent":   intent,
        "transcribed_text":  text,
        "language":          detected_language or "en",
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
    client = _get_client()

    lang_name = LANGUAGES.get(target_language.lower(), target_language)

    if target_language.lower() == "en":
        return {"translated_text": text, "language": "English"}

    # Callers translating several report fields at once send a JSON object
    # (field name -> text) as `text` instead of a plain string, so we can
    # translate them all in a single request. Detect that and use a prompt
    # that preserves keys exactly, so the caller can safely map the response
    # back onto the same fields.
    is_json_payload = False
    try:
        parsed = json.loads(text)
        is_json_payload = isinstance(parsed, dict)
    except (ValueError, TypeError):
        pass

    if is_json_payload:
        prompt = f"""Translate the VALUES of this JSON object into {lang_name}. Do NOT translate, rename, or reorder the JSON keys — keep them byte-for-byte identical to the input. Keep technical/agricultural terms accurate; for Hausa, Yoruba, and Igbo use natural everyday language a Nigerian farmer would understand.

JSON:
{text}

Respond with ONLY a valid JSON object with the exact same keys and translated values. No markdown, no code fences, no commentary."""
    else:
        prompt = f"""Translate the following agricultural diagnosis report into {lang_name}.

Keep all technical terms accurate. For Hausa, Yoruba, and Igbo, use natural everyday language that farmers in Nigeria would understand. Keep the same structure and numbered lists.

Text to translate:
{text}

Provide ONLY the translated text, no explanations or meta-commentary."""

    try:
        message = await client.messages.create(
            model=AI_MODEL, max_tokens=2048,
            messages=[{"role": "user", "content": prompt}],
        )
        translated = _extract_text(message).strip()
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail=f"Translation error: {str(e)}")

    return {"translated_text": translated, "language": lang_name}


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 8001))
    uvicorn.run(app, host="0.0.0.0", port=port)
