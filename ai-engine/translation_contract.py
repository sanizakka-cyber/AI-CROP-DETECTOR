"""
"Listen to your result": translation of a finished scan result.

The scan result is translated as structured JSON, field by field, so the
same four sections (observations, explanation, recommendations, safety
note) exist in every language and can be checked against the source.

The target language is passed by name from Laravel's admin-managed language
table, so a new language needs no change here.
"""

CONTENT_KEYS = ("observations", "explanation", "recommendations", "safety_note")


def schema() -> dict:
    # No unions, no optional fields: well inside the structured-output limits
    # that caused the Phase 11 outage (16 unions / 24 optional per request).
    return {
        "type": "object",
        "properties": {
            "observations":    {"type": "array", "items": {"type": "string"}},
            "explanation":     {"type": "string"},
            "recommendations": {"type": "array", "items": {"type": "string"}},
            "safety_note":     {"type": "string"},
        },
        "required": list(CONTENT_KEYS),
        "additionalProperties": False,
    }


def build_prompt(source_name: str, target_name: str, content: dict, glossary: list, protected_terms: list) -> str:
    import json

    glossary_lines = "\n".join(
        f"- {g.get('english_term', '').strip()} => {g.get('local_term', '').strip()}"
        for g in glossary
        if g.get("english_term") and g.get("local_term")
    ) or "(none yet)"
    protected = "\n".join(f"- {t}" for t in protected_terms if t) or "(none)"

    return f"""Translate this crop/livestock scan result from {source_name} into {target_name} for a Nigerian smallholder farmer.

Rules:
- Translate every field. Keep the same number of items in each list, in the same order: item N of the translation must be the translation of item N of the source.
- Copy these EXACTLY as written in the source, character for character: product names, active ingredients, dosages, numbers, units (ml, L, kg, g, %, °C, etc.), and dates. Do not convert, round or spell out numbers.
- These protected terms must appear unchanged:
{protected}
- Use this agricultural glossary for {target_name}. When a source term appears, use the given local term:
{glossary_lines}
- Plain, everyday spoken wording. Short sentences. No idioms a farmer might misread.
- Do NOT add, remove or soften any advice, warning, diagnosis or quantity. Do not add greetings or explanations of your own.
- Write {target_name} with its correct special letters and tone marks (for example Hausa ɓ ɗ ƙ ƴ, Yoruba ẹ ọ ṣ with tones, Igbo ị ọ ụ ṅ). Never replace them with plain letters.
- If a term has no {target_name} word, keep the source term.

Source ({source_name}), as JSON:
{json.dumps(content, ensure_ascii=False, indent=2)}

Reply with the translated JSON only."""


def shape_ok(source: dict, translated: dict) -> str:
    """Return '' when the translation has the source's shape, else the problem."""
    for key in CONTENT_KEYS:
        if key not in translated:
            return f"missing {key}"
    for key in ("observations", "recommendations"):
        if len(translated[key]) != len(source.get(key) or []):
            return f"{key}: {len(translated[key])} items, source has {len(source.get(key) or [])}"
    for key in ("explanation", "safety_note"):
        if (source.get(key) or "").strip() and not str(translated[key]).strip():
            return f"{key} is empty"
    return ""
