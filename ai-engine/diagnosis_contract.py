"""
Canonical diagnosis contract (Phase 11) shared by /predict/crop, /livestock,
/soil and /pest.

Why this exists. Before Phase 11 each endpoint asked Claude for a
pipe-delimited text reply ("subject_name | Maize") and parsed it line by
line. Three things went wrong with that, all visible in production:

1. Any field the parser could not find was silently replaced with a
   default ("Unknown", "Uncertain", "Moderate", "Medium", "Consult an
   agronomist"), so a truncated or oddly formatted reply looked like a real
   but vague diagnosis.
2. The prompt required every treatment field, so an image the model could
   not read still came back with a medication / fertilizer / recovery
   period, which the apps then displayed as a treatment plan.
3. There was no field saying *what kind* of result this was. "Too blurry to
   tell", "this is not a plant" and "probable late blight" all arrived in
   the same shape, and the backend turned "not a plant" into an AI-outage
   record.

Here the model must return JSON matching a strict schema (structured
outputs), every optional finding is explicitly nullable, and an
`assessment_outcome` field states which of the recoverable states the
result is in. Nothing in this module invents a value: a missing or invalid
value stays None.
"""
from typing import Optional

# ── Outcomes ─────────────────────────────────────────────────────────────────
ASSESSED = "assessed"                        # a supported finding, with treatment where justified
UNCERTAIN = "uncertain"                      # subject visible but evidence insufficient for one finding
POOR_IMAGE = "poor_image"                    # image quality prevents assessment (blur, dark, too far, ...)
UNSUPPORTED_SUBJECT = "unsupported_subject"  # image does not show what this scan mode needs
OUTCOMES = [ASSESSED, UNCERTAIN, POOR_IMAGE, UNSUPPORTED_SUBJECT]

IMAGE_QUALITY = ["good", "acceptable", "poor"]
IMAGE_ISSUES = [
    "blurred", "too_dark", "overexposed_or_glare", "too_far", "too_close",
    "subject_partly_visible", "subject_not_visible", "low_resolution",
    "colour_distorted", "screen_or_printed_photo", "multiple_subjects",
]


def _nullable(schema: dict) -> dict:
    return {"anyOf": [schema, {"type": "null"}]}


S = {"type": "string"}
NS = _nullable(S)
STR_LIST = {"type": "array", "items": S}

# Fields every scan mode returns. Enum values are lowercase snake_case so the
# backend and both apps can branch on them without string guessing.
COMMON_PROPS = {
    "assessment_outcome": {"type": "string", "enum": OUTCOMES},
    "subject_present": {"type": "boolean"},
    "image_quality": {"type": "string", "enum": IMAGE_QUALITY},
    "image_issues": {"type": "array", "items": {"type": "string", "enum": IMAGE_ISSUES}},
    "photo_tips": STR_LIST,
    "subject_name": NS,
    "scientific_name": NS,
    "detected_part": NS,
    "health_status": NS,
    "primary_finding": NS,
    "alternative_findings": {
        "type": "array",
        "items": {
            "type": "object",
            "properties": {"name": S, "why_considered": S},
            "required": ["name", "why_considered"],
            "additionalProperties": False,
        },
    },
    # Integer 0-100 or null. Range is checked in code (finalize), because
    # numeric bounds are not part of the structured-output schema subset.
    "confidence": _nullable({"type": "integer"}),
    "severity": _nullable({"type": "string", "enum": ["none", "mild", "moderate", "severe", "critical"]}),
    "urgency": _nullable({"type": "string", "enum": ["low", "medium", "high", "emergency"]}),
    "symptoms_observed": STR_LIST,
    "cause": NS,
    "contributing_factors": NS,
    "what_cannot_be_confirmed": NS,
    "explanation": NS,
    "immediate_actions": STR_LIST,
    "organic_options": NS,
    "treatment": NS,
    "prevention": NS,
    "monitoring_and_recovery": NS,
    "when_to_seek_expert": NS,
}

MODE_PROPS = {
    "crop": {
        "nutrient_deficiencies": NS,
        "pest_detection": NS,
        "fertilizer_guidance": NS,
    },
    "livestock": {
        "breed": NS,
        "vet_recommendation": NS,
    },
    "soil": {
        "soil_texture": NS,
        "visual_ph_indication": NS,
        "visual_nutrient_indication": NS,
        "suitable_crops": NS,
        "amendment_guidance": NS,
        "lab_test_advice": NS,
    },
    "pest": {
        "pest_type": NS,
        "damage_observed": NS,
        "host_crops": NS,
        "spread_risk": _nullable({"type": "string", "enum": ["low", "medium", "high"]}),
        "biological_control": NS,
        "cultural_control": NS,
        "economic_threshold": NS,
    },
}


def schema_for(mode: str) -> dict:
    props = {**COMMON_PROPS, **MODE_PROPS[mode]}
    return {
        "type": "object",
        "properties": props,
        "required": list(props.keys()),
        "additionalProperties": False,
    }


# ── Prompts ──────────────────────────────────────────────────────────────────
_SUBJECT = {
    "crop": "a crop or plant (leaf, stem, fruit, root, flower, seed or whole plant)",
    "livestock": "a farm animal (cattle, goat, sheep, pig, poultry, camel, donkey, horse, rabbit) or its droppings, wound or lesion",
    "soil": "a soil sample or exposed soil",
    "pest": "a crop pest, weed, or the damage a pest or pathogen causes on a plant",
}

_ROLE = {
    "crop": "an experienced plant pathologist and agronomist working with smallholder farmers in Nigeria and West Africa",
    "livestock": "an experienced livestock veterinarian working with smallholder farmers in Nigeria and West Africa",
    "soil": "an experienced soil scientist and agronomist working with smallholder farmers in Nigeria and West Africa",
    "pest": "an experienced entomologist and plant-health specialist working with smallholder farmers in Nigeria and West Africa",
}

_MODE_RULES = {
    "crop": (
        "- primary_finding names the specific disease, deficiency, pest damage or 'Healthy' state that the visible evidence best supports.\n"
        "- treatment: name the active ingredient or product type and say to apply it at the rate on the product label. Do not invent dosages.\n"
        "- fertilizer_guidance only when the finding is a nutrient problem; otherwise null."
    ),
    "livestock": (
        "- An image alone cannot establish a definitive veterinary diagnosis. Describe what is visible and the most likely condition, and say what a veterinarian would need to confirm it.\n"
        "- treatment: name the class of treatment a vet would consider. Never give drug doses, routes or withdrawal periods.\n"
        "- when_to_seek_expert must always be filled for livestock, with urgency proportionate to the signs."
    ),
    "soil": (
        "- A photo cannot measure pH, nutrient concentrations or soil chemistry. visual_ph_indication and visual_nutrient_indication describe only what colour, texture and structure suggest, and must say a laboratory test is needed to confirm.\n"
        "- amendment_guidance is general soil-management advice, not a measured prescription. Do not give kg per hectare figures.\n"
        "- lab_test_advice explains which test to request and why."
    ),
    "pest": (
        "- Distinguish the pest itself from the damage. If only damage is visible, primary_finding names the most likely cause and what_cannot_be_confirmed says the pest itself was not seen.\n"
        "- Do not name a species when the image does not show enough to identify it; name the group instead (for example 'caterpillar, likely a moth larva').\n"
        "- treatment: name the active ingredient or product type and say to follow the label rate. Do not invent dosages."
    ),
}


def build_prompt(mode: str, hint: str = "", extra_rules: str = "") -> str:
    return f"""You are {_ROLE[mode]}. Examine the uploaded image(s) for a {mode} scan. This scan mode needs {_SUBJECT[mode]}.{hint}

Decide assessment_outcome first, honestly:
- "unsupported_subject": the image does not show {_SUBJECT[mode]}. Set subject_present to false.
- "poor_image": the subject is there but blur, darkness, glare, distance or framing prevents a meaningful assessment.
- "uncertain": the subject is visible and assessable, but the evidence does not support one finding over others. Put the candidates in alternative_findings and say in what_cannot_be_confirmed what is missing.
- "assessed": the visible evidence supports a specific finding.

Rules:
- Fill only what the image supports. Use null for any field you cannot support from the evidence, and an empty list for list fields with nothing to report. Never guess a value to fill a field.
- For "unsupported_subject" and "poor_image", leave every finding and treatment field null or empty, and give 2 to 4 specific photo_tips for retaking the photo.
- For "uncertain", give observations, candidates, safe general immediate_actions and photo_tips, but leave treatment null.
- confidence is your own estimate from 0 to 100 that primary_finding is correct, given image quality and how specific the signs are. Use null when there is no primary_finding.
- image_issues lists every quality problem you notice, even when you could still assess the image.
- Recommend organic or non-chemical options only where evidence shows they work for this finding.
- explanation says which visible features led to your conclusion.
{_MODE_RULES[mode]}{extra_rules}

Write all text values in plain language a smallholder farmer understands: no Markdown, no emojis."""


# ── Result normalisation ─────────────────────────────────────────────────────

def _clean_conf(raw) -> dict:
    """Self-reported certainty, never fabricated. Out-of-range or non-integer
    values are reported invalid (and dropped) instead of clamped or zeroed."""
    if raw is None or isinstance(raw, bool):
        return {"value": None, "valid": raw is None, "raw": raw}
    if isinstance(raw, (int, float)) and 0 <= raw <= 100:
        return {"value": float(raw), "valid": True, "raw": raw}
    return {"value": None, "valid": False, "raw": raw}


def _join(items) -> Optional[str]:
    items = [i.strip() for i in (items or []) if isinstance(i, str) and i.strip()]
    return ", ".join(items) if items else None


def _numbered(items) -> Optional[str]:
    items = [i.strip() for i in (items or []) if isinstance(i, str) and i.strip()]
    return " ".join(f"{n}) {t}" for n, t in enumerate(items, 1)) if items else None


def _title(value: Optional[str]) -> Optional[str]:
    return value.capitalize() if isinstance(value, str) and value else None


def finalize(mode: str, data: dict) -> dict:
    """Map the validated structured reply onto the response keys the Laravel
    DiagnosisResultMapper already reads (backward compatible), plus the new
    Phase 11 keys. Consistency rules are enforced here, not trusted to the
    model: an unsupported or poor image never carries a finding, confidence
    or treatment."""
    outcome = data.get("assessment_outcome")
    if outcome not in OUTCOMES:
        outcome = UNCERTAIN
    if data.get("subject_present") is False:
        outcome = UNSUPPORTED_SUBJECT

    no_finding = outcome in (UNSUPPORTED_SUBJECT, POOR_IMAGE)
    no_treatment = no_finding or outcome == UNCERTAIN

    def f(key):  # finding field, suppressed when there is no assessable subject
        return None if no_finding else data.get(key)

    def t(key):  # treatment field, only for a supported assessment
        return None if no_treatment else data.get(key)

    conf = _clean_conf(None if no_finding else data.get("confidence"))

    out = {
        # Phase 11 contract fields
        "contract_version": "2026-10-phase11",
        "assessment_outcome": outcome,
        "subject_present": outcome != UNSUPPORTED_SUBJECT,
        "image_quality": data.get("image_quality") if data.get("image_quality") in IMAGE_QUALITY else None,
        "image_issues": [i for i in (data.get("image_issues") or []) if i in IMAGE_ISSUES],
        "photo_tips": [p for p in (data.get("photo_tips") or []) if isinstance(p, str) and p.strip()],
        "alternative_findings": [] if no_finding else [
            a for a in (data.get("alternative_findings") or [])
            if isinstance(a, dict) and a.get("name")
        ],
        "what_cannot_be_confirmed": data.get("what_cannot_be_confirmed"),
        # Keys the existing mapper reads
        "subject_name": f("subject_name"),
        "scientific_name": f("scientific_name"),
        "detected_part": f("detected_part"),
        "health_status": f("health_status"),
        "disease": f("primary_finding"),
        "confidence": conf["value"],
        "confidence_valid": conf["valid"],
        "severity": _title(f("severity")),
        "urgency": _title(f("urgency")),
        "symptoms_identified": None if no_finding else _join(data.get("symptoms_observed")),
        "cause": f("cause"),
        "environmental_factors": f("contributing_factors"),
        "first_aid": None if no_finding else _numbered(data.get("immediate_actions")),
        "organic_treatment": t("organic_options"),
        "preventive_measures": t("prevention"),
        "recovery_period": t("monitoring_and_recovery"),
        "referral": data.get("when_to_seek_expert"),
        "explanation": data.get("explanation"),
        "scan_type": mode,
    }

    if mode == "crop":
        out.update({
            "medication": t("treatment"),
            "nutrient_deficiencies": f("nutrient_deficiencies"),
            "pest_detection": f("pest_detection"),
            "fertilizer_recommendation": t("fertilizer_guidance"),
        })
    elif mode == "livestock":
        out.update({
            "medication": t("treatment"),
            "breed": f("breed"),
            "vet_recommendation": f("vet_recommendation"),
        })
    elif mode == "soil":
        out.update({
            # Soil keys the mapper already understands, now qualitative only.
            "ph_estimate": f("visual_ph_indication"),
            "nutrients": f("visual_nutrient_indication"),
            "suitable_crops": f("suitable_crops"),
            "amendment_recommendation": t("amendment_guidance"),
            "referral": data.get("lab_test_advice") or data.get("when_to_seek_expert"),
            "subject_name": f("subject_name") or f("soil_texture"),
        })
    elif mode == "pest":
        out.update({
            "pest_name": f("primary_finding"),
            "pest_type": f("pest_type"),
            "damage_type": f("damage_observed"),
            "host_crops": f("host_crops"),
            "spread_risk": _title(f("spread_risk")),
            "chemical_control": t("treatment"),
            "biological_control": t("biological_control"),
            "cultural_control": t("cultural_control"),
            "economic_threshold": t("economic_threshold"),
            "immediate_action": None if no_finding else _numbered(data.get("immediate_actions")),
        })
    return out
