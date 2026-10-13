import os
import json
import base64
import io
from typing import List, Optional
from fastapi import FastAPI, UploadFile, File, Form, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from PIL import Image, ImageOps, UnidentifiedImageError
import anthropic
import uvicorn

from diagnosis_contract import build_prompt, finalize, schema_for

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

# ── /chat history validation ─────────────────────────────────────────────────
# The client-supplied 'history' field was previously parsed with only an
# isinstance(parsed, list) check -- no validation of what's actually inside
# each entry -- before being forwarded verbatim into messages=[...] on a real
# Anthropic API call. Malformed JSON was silently swallowed into an empty
# history rather than rejected. None of this let a client literally replace
# the system prompt (system=system_prompt is a separate parameter, never
# derived from these entries), but restricting roles to exactly
# {"user","assistant"} here makes that guarantee explicit and defended in
# this service too, not just incidentally true of how the Anthropic API
# happens to be called today.
_CHAT_VALID_ROLES = {"user", "assistant"}
_CHAT_MAX_HISTORY_ENTRIES = 20   # matches the existing messages_list[-20:] trim already applied to outgoing history
_CHAT_MAX_CONTENT_CHARS   = 4000  # generous for one real chat turn; not a token budget, just a sanity ceiling

def _validate_chat_history(raw: str) -> list:
    try:
        parsed = json.loads(raw)
    except (json.JSONDecodeError, ValueError):
        raise HTTPException(status_code=422, detail="history must be valid JSON.")

    if not isinstance(parsed, list):
        raise HTTPException(status_code=422, detail="history must be a JSON array.")
    if len(parsed) > _CHAT_MAX_HISTORY_ENTRIES:
        raise HTTPException(status_code=422, detail=f"history cannot exceed {_CHAT_MAX_HISTORY_ENTRIES} entries.")

    validated = []
    for entry in parsed:
        if not isinstance(entry, dict):
            raise HTTPException(status_code=422, detail="each history entry must be an object with role and content.")
        role = entry.get("role")
        content = entry.get("content")
        if role not in _CHAT_VALID_ROLES:
            raise HTTPException(status_code=422, detail=f"unsupported role in history: {role!r}.")
        if not isinstance(content, str) or not content.strip():
            raise HTTPException(status_code=422, detail="history entry content must be a non-empty string.")
        if len(content) > _CHAT_MAX_CONTENT_CHARS:
            raise HTTPException(status_code=422, detail=f"history entry content exceeds {_CHAT_MAX_CONTENT_CHARS} characters.")
        validated.append({"role": role, "content": content})
    return validated

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

# Phase 7 fix. Root cause of a real production failure found during the
# Phase 6 live-scan test: Laravel's own upload validation allows images up
# to 10MB, but nothing between the farmer's camera and Claude ever
# guaranteed the final base64 payload stayed under Anthropic's real
# per-image limit (10MB base64-encoded on the direct API -- confirmed
# against platform.claude.com/docs/en/build-with-claude/vision, 2026-10-09).
# Base64 inflates raw bytes by ~4/3, so a image anywhere close to Laravel's
# 10MB ceiling could still be rejected by Anthropic, confusingly, after
# already passing every earlier validation layer. Every image is now
# normalized here -- resized to a sane long-edge cap and recompressed as
# JPEG -- regardless of what was uploaded, so the payload sent to Claude is
# always small and predictable. This also improves latency/cost (smaller
# base64 payloads) independent of the size-limit fix.
_MAX_LONG_EDGE = 2000          # px -- Anthropic's own stated safe ceiling
_TARGET_JPEG_QUALITY = 85
_MAX_FORWARD_BYTES = 4 * 1024 * 1024   # 4MB raw -> ~5.3MB base64, safely under Anthropic's 10MB cap

def _normalize_image_bytes(data: bytes) -> bytes:
    """Resize/recompress raw image bytes to a Claude-safe JPEG. Raises
    HTTPException(422) if the bytes can't be decoded as an image at all --
    never silently forwards undecodable data and never fabricates a result
    from it."""
    try:
        with Image.open(io.BytesIO(data)) as im:
            im.load()  # force a full decode now, not lazily later, so a truncated/corrupt file fails here
            # Phone cameras store rotation as an EXIF tag rather than
            # rotating the pixel data itself. Pillow does not apply that
            # tag on open/resize/save -- without this, a portrait photo's
            # pixels stay landscape-oriented and the EXIF tag that would
            # have told a viewer to rotate it is then dropped on re-save,
            # so Claude would see a sideways image with no orientation hint.
            im = ImageOps.exif_transpose(im)
            if im.mode not in ("RGB", "L"):
                im = im.convert("RGB")

            long_edge = max(im.size)
            if long_edge > _MAX_LONG_EDGE:
                scale = _MAX_LONG_EDGE / long_edge
                new_size = (max(1, round(im.width * scale)), max(1, round(im.height * scale)))
                im = im.resize(new_size, Image.LANCZOS)

            quality = _TARGET_JPEG_QUALITY
            while True:
                buf = io.BytesIO()
                im.save(buf, format="JPEG", quality=quality, optimize=True)
                encoded = buf.getvalue()
                if len(encoded) <= _MAX_FORWARD_BYTES or quality <= 40:
                    return encoded
                quality -= 15  # still too large after resizing (dense/noisy photo) -- step quality down and retry
    except UnidentifiedImageError:
        raise HTTPException(status_code=422, detail={
            "accepted": False,
            "message": "This file could not be read as an image. Please upload a clear JPEG, PNG, or WebP photo.",
        })
    except HTTPException:
        raise
    except Exception as e:
        # Any other Pillow failure (truncated file, unsupported mode, etc.)
        # is still an honest "can't process this" -- never fall through to
        # forwarding the raw, possibly-broken bytes to Claude.
        raise HTTPException(status_code=422, detail={
            "accepted": False,
            "message": "This image could not be processed. Please try a different photo.",
        }) from e


async def _read_images_b64(images: List[UploadFile]) -> List[dict]:
    result = []
    for img in images:
        data = await img.read()
        normalized = _normalize_image_bytes(data)
        result.append({
            "type": "image",
            "source": {
                "type": "base64",
                "media_type": "image/jpeg",  # _normalize_image_bytes always re-encodes as JPEG
                "data": base64.standard_b64encode(normalized).decode("utf-8"),
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

def _parse_confidence(raw_val: Optional[str]) -> dict:
    """
    Parses the model's self-reported confidence number and says whether it
    was actually parseable and in range — callers must NOT treat a missing
    or malformed value as a genuine 0%. `_safe_float` alone can't make that
    distinction (it silently returns 0.0 for both "the model wrote 0" and
    "the model wrote nothing parseable"), which is exactly the kind of
    fabricated-looking-real number this audit flags elsewhere.

    IMPORTANT CONTEXT for callers: this number is the vision-language
    model's own self-reported statement, written into a pipe-delimited text
    reply because it was asked to. It is NOT a softmax/logit probability
    from a trained classifier — there is no trained classifier in this
    service. Treat it as a directional, uncalibrated self-assessment only.
    """
    if raw_val is None or str(raw_val).strip() == "":
        return {"value": None, "valid": False, "raw": raw_val or ""}

    try:
        parsed = float(str(raw_val).replace("%", "").strip())
    except (ValueError, TypeError):
        return {"value": None, "valid": False, "raw": str(raw_val)}

    if parsed < 0 or parsed > 100:
        # Out-of-range is a malformed response, not a real extreme score —
        # clamp for display but mark invalid so storage can flag it instead
        # of silently presenting e.g. "140% confidence".
        return {"value": max(0.0, min(100.0, parsed)), "valid": False, "raw": str(raw_val)}

    return {"value": parsed, "valid": True, "raw": str(raw_val)}


def _model_meta(model: str, raw_text: str) -> dict:
    """Confidence provenance fields attached to every /predict/* response (spec 4.5)."""
    return {
        "ai_model": model,
        # The full Claude model identifier already encodes the version
        # (e.g. "claude-sonnet-5") — there is no separate internal version
        # number to report beyond the model id itself.
        "ai_model_version": model,
        "raw_model_output": raw_text[:4000],
        "confidence_interpretation": (
            "Self-reported certainty from a vision-language model's text "
            "response, not a calibrated statistical probability from a "
            "trained classifier. Treat as directional guidance only."
        ),
        "validation_status": "unvalidated",
    }

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

# ─── Structured diagnosis (Phase 11) ──────────────────────────────────────────
# All four scan endpoints share one strict JSON contract (diagnosis_contract.py)
# delivered through structured outputs, replacing the pipe-delimited replies
# whose missing fields were silently back-filled with invented defaults.

_SCAN_MAX_TOKENS = 8000  # was 1024-1536: adaptive thinking shares this budget, so long replies were being cut short


class _MalformedReply(Exception):
    pass


async def _run_structured(mode: str, image_blocks: List[dict], prompt: str) -> tuple:
    client = _get_client()
    try:
        message = await client.messages.create(
            model=AI_MODEL,
            max_tokens=_SCAN_MAX_TOKENS,
            messages=[{"role": "user", "content": image_blocks + [{"type": "text", "text": prompt}]}],
            output_config={"format": {"type": "json_schema", "schema": schema_for(mode)}},
        )
    except anthropic.APIError as e:
        raise HTTPException(status_code=503, detail={"error": "provider_unavailable", "message": f"AI service error: {str(e)}"})

    # A refusal or a reply cut off at max_tokens is a technical failure, never
    # a diagnosis -- report it as such instead of parsing a partial answer.
    if message.stop_reason in ("refusal", "max_tokens"):
        raise HTTPException(status_code=502, detail={"error": "malformed_response", "message": f"AI reply incomplete ({message.stop_reason})."})
    text = _extract_text(message)
    try:
        data = json.loads(text)
        if not isinstance(data, dict):
            raise _MalformedReply()
    except (json.JSONDecodeError, _MalformedReply):
        raise HTTPException(status_code=502, detail={"error": "malformed_response", "message": "AI reply was not valid JSON."})
    return data, text


def _structured_result(mode: str, data: dict, raw_text: str) -> dict:
    return {**_model_meta(AI_MODEL, raw_text), **finalize(mode, data)}


def _hint(**pairs) -> str:
    parts = [f" {label}: {value}." for label, value in pairs.items() if value]
    return ("\n\nFarmer-provided context (a hint, not proof):" + "".join(parts)) if parts else ""


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
    _get_client()
    image_blocks = await _read_images_b64(images)
    prompt = build_prompt("crop", _hint(**{"Reported crop": cropType, "Photographed part": cropPart})) + _lang_instruction(language or "en")
    data, text = await _run_structured("crop", image_blocks, prompt)
    return _structured_result("crop", data, text)

# ─── /predict/livestock ───────────────────────────────────────────────────────

@app.post("/predict/livestock")
async def predict_livestock(
    request: Request,
    animalType: Optional[str] = Form(None),
    assessmentType: Optional[str] = Form(None),
    symptoms: Optional[str] = Form(None),
    language: Optional[str] = Form("en"),
    images: List[UploadFile] = File(default=[]),
):
    _check_auth(request)
    _get_client()
    image_blocks = await _read_images_b64(images) if images else []
    if not image_blocks:
        # No photo: there is nothing to assess visually. Previously the model
        # was told to answer "based on common conditions", i.e. to guess.
        return {
            **finalize("livestock", {
                "assessment_outcome": "poor_image", "subject_present": True, "image_quality": "poor",
                "image_issues": ["subject_not_visible"],
                "photo_tips": ["Add at least one clear photo of the animal or the affected area."],
                "when_to_seek_expert": "If the animal is weak, not eating, bleeding or breathing with difficulty, call a veterinarian now.",
            }),
            "ai_model": None, "ai_model_version": None, "raw_model_output": None,
            "confidence_interpretation": None, "validation_status": "unvalidated",
        }
    prompt = build_prompt("livestock", _hint(**{"Reported animal": animalType, "Assessment area": assessmentType, "Reported symptoms": symptoms})) + _lang_instruction(language or "en")
    data, text = await _run_structured("livestock", image_blocks, prompt)
    return _structured_result("livestock", data, text)

# ─── /predict/soil ────────────────────────────────────────────────────────────

@app.post("/predict/soil")
async def predict_soil(
    request: Request,
    soilContext: Optional[str] = Form(None),
    language: Optional[str] = Form("en"),
    images: List[UploadFile] = File(...),
):
    _check_auth(request)
    _get_client()
    image_blocks = await _read_images_b64(images)
    prompt = build_prompt("soil", _hint(**{"Farmer context": soilContext})) + _lang_instruction(language or "en")
    data, text = await _run_structured("soil", image_blocks, prompt)
    return _structured_result("soil", data, text)

# ─── /predict/pest ────────────────────────────────────────────────────────────

@app.post("/predict/pest")
async def predict_pest(
    request: Request,
    cropType: Optional[str] = Form(None),
    location: Optional[str] = Form(None),
    language: Optional[str] = Form("en"),
    images: List[UploadFile] = File(...),
):
    """Pest identification -- insect, weed, or pathogen, kept distinct from the damage it causes."""
    _check_auth(request)
    _get_client()
    image_blocks = await _read_images_b64(images)
    prompt = build_prompt("pest", _hint(**{"Affected crop": cropType, "Location": location})) + _lang_instruction(language or "en")
    data, text = await _run_structured("pest", image_blocks, prompt)
    return _structured_result("pest", data, text)

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
- A farmer may name a crop, animal, disease, or pest by its common name (maize),
  local/Hausa name, or botanical/scientific name (Zea mays) — treat these as the
  same subject regardless of which form they use, and answer accordingly without
  asking them to rephrase
- Write in plain text only. Do not use Markdown syntax (no #, ##, **, *, backtick
  code fences, or --- separators). Use plain paragraphs, and where a list is the
  clearest way to present steps or items, write each on its own line starting
  with a plain hyphen and a space
- Do not use em-dashes or en-dashes (the "--" long-dash characters). Where you
  would reach for one, use a comma, the word "or", parentheses, or a colon
- Do not decorate the answer with emojis. Use plain section labels
  ("Diagnosis", "Treatment", "Warning"), not emoji-prefixed ones
{lang_note}"""

    # Parse + validate conversation history -- malformed input is now
    # rejected with a clear 422 rather than silently treated as no history.
    messages_list = _validate_chat_history(history) if history else []

    # Also enforce the same content-length ceiling on the live 'message'
    # field itself, not just entries already round-tripped through 'history'.
    if len(message) > _CHAT_MAX_CONTENT_CHARS:
        raise HTTPException(status_code=422, detail=f"message exceeds {_CHAT_MAX_CONTENT_CHARS} characters.")

    # Add context as a system note if provided
    user_message = message
    if context:
        user_message = f"[Context: {context}]\n\n{message}"

    messages_list.append({"role": "user", "content": user_message})

    try:
        response = await client.messages.create(
            model=model,
            max_tokens=2048,
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

If the transcription is too unclear to understand, politely ask the farmer to repeat their question.

Write in plain text only: no Markdown symbols (#, **, *, backticks), no emojis, and
no em-dashes or en-dashes (use a comma, "or", or a colon instead).{lang_note}"""

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
