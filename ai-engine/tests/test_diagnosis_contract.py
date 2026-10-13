"""
Phase 11: the structured diagnosis contract shared by /predict/crop,
/livestock, /soil and /pest.

These tests use a fake Anthropic client, so they prove the software
behaviour (schema, outcome handling, no invented values, error mapping).
They say nothing about real-world diagnostic accuracy -- that needs a
labelled image set run against the real model.

Run: pip install -r requirements.txt -r requirements-dev.txt && pytest tests/ -v
"""
import io
import json
import os
import sys
from types import SimpleNamespace

import pytest
from PIL import Image

os.environ.setdefault("ANTHROPIC_API_KEY", "test-key")
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import main  # noqa: E402
from diagnosis_contract import (  # noqa: E402
    MODE_PROPS, OUTCOMES, finalize, schema_for, build_prompt,
)
from fastapi.testclient import TestClient  # noqa: E402

MODES = ["crop", "livestock", "soil", "pest"]


def _reply(mode, **overrides):
    """A complete, schema-shaped reply for `mode` (every key present)."""
    base = {k: None for k in schema_for(mode)["properties"]}
    base.update({
        "assessment_outcome": "assessed", "subject_present": True, "image_quality": "good",
        "image_issues": [], "photo_tips": [], "alternative_findings": [],
        "symptoms_observed": ["dark water-soaked lesions", "white sporulation"],
        "immediate_actions": ["Remove infected leaves", "Avoid overhead watering"],
        "subject_name": "Tomato", "scientific_name": "Solanum lycopersicum",
        "health_status": "Diseased", "primary_finding": "Late blight", "confidence": 78,
        "severity": "moderate", "urgency": "high", "treatment": "Copper-based fungicide, at the label rate",
        "organic_options": "Remove and destroy infected plants", "prevention": "Rotate crops",
        "explanation": "Water-soaked lesions with white sporulation on the leaf underside.",
    })
    base.update(overrides)
    return base


class FakeMessages:
    """`script` (optional) is a list of per-call steps: an Exception to raise
    or a text reply to return, consumed in order."""
    def __init__(self, text="{}", stop_reason="end_turn", raise_exc=None, script=None):
        self.text, self.stop_reason, self.raise_exc = text, stop_reason, raise_exc
        self.script = list(script or [])
        self.calls = []

    async def create(self, **kwargs):
        self.calls.append(kwargs)
        if self.script:
            step = self.script.pop(0)
            if isinstance(step, Exception):
                raise step
            return SimpleNamespace(stop_reason="end_turn",
                                   content=[SimpleNamespace(type="text", text=step)])
        if self.raise_exc:
            raise self.raise_exc
        return SimpleNamespace(
            stop_reason=self.stop_reason,
            content=[SimpleNamespace(type="thinking", thinking=""), SimpleNamespace(type="text", text=self.text)],
        )


@pytest.fixture
def fake(monkeypatch):
    holder = {}

    def install(**kw):
        msgs = FakeMessages(**kw)
        monkeypatch.setattr(main, "_get_client", lambda: SimpleNamespace(messages=msgs))
        monkeypatch.setattr(main, "ANTHROPIC_KEY", "test-key")
        monkeypatch.setattr(main, "API_KEY", "")
        holder["msgs"] = msgs
        return msgs
    return install


def _jpeg():
    buf = io.BytesIO()
    Image.new("RGB", (320, 240), (90, 140, 60)).save(buf, format="JPEG")
    return buf.getvalue()


def _post(mode, **form):
    client = TestClient(main.app)
    return client.post(f"/predict/{mode}", data=form, files=[("images", ("a.jpg", _jpeg(), "image/jpeg"))])


# ── Schema ───────────────────────────────────────────────────────────────────

@pytest.mark.parametrize("mode", MODES)
def test_schema_is_strict_and_complete(mode):
    schema = schema_for(mode)
    assert schema["additionalProperties"] is False
    assert set(schema["required"]) == set(schema["properties"])
    assert schema["properties"]["assessment_outcome"]["enum"] == OUTCOMES
    for key in MODE_PROPS[mode]:
        assert key in schema["properties"]


@pytest.mark.parametrize("mode", MODES)
def test_endpoint_requests_structured_output_with_its_schema(fake, mode):
    msgs = fake(text=json.dumps(_reply(mode)))
    r = _post(mode)
    assert r.status_code == 200
    call = msgs.calls[0]
    assert call["output_config"]["format"]["type"] == "json_schema"
    assert call["output_config"]["format"]["schema"] == schema_for(mode)
    assert call["max_tokens"] >= 4000  # room for adaptive thinking + the full JSON reply


def _walk(node):
    yield node
    if isinstance(node, dict):
        for v in node.values():
            yield from _walk(v)
    elif isinstance(node, list):
        for v in node:
            yield from _walk(v)


@pytest.mark.parametrize("mode", MODES)
def test_schema_stays_inside_structured_output_limits(mode):
    # platform.claude.com structured outputs: at most 16 union-typed and 24
    # optional parameters per request. The first deploy of this contract
    # had ~30 nullable (anyOf) fields and production rejected every scan
    # with HTTP 400. This contract uses none of either.
    schema = schema_for(mode)
    unions = [n for n in _walk(schema) if isinstance(n, dict) and ("anyOf" in n or isinstance(n.get("type"), list))]
    assert unions == []
    optional = 0
    for n in _walk(schema):
        if isinstance(n, dict) and n.get("type") == "object" and "properties" in n:
            optional += len(set(n["properties"]) - set(n.get("required", [])))
            assert n.get("additionalProperties") is False
    assert optional == 0
    for n in _walk(schema):
        if isinstance(n, dict):
            assert not ({"minimum", "maximum", "minLength", "maxLength", "multipleOf"} & set(n)), n


# ── Outcomes ─────────────────────────────────────────────────────────────────

def test_assessed_crop_maps_onto_legacy_keys(fake):
    fake(text=json.dumps(_reply("crop")))
    body = _post("crop").json()
    assert body["assessment_outcome"] == "assessed"
    assert body["subject_name"] == "Tomato"
    assert body["scientific_name"] == "Solanum lycopersicum"
    assert body["disease"] == "Late blight"
    assert body["confidence"] == 78 and body["confidence_valid"] is True
    assert body["medication"].startswith("Copper")
    assert body["first_aid"] == "1) Remove infected leaves 2) Avoid overhead watering"
    assert body["contract_version"]
    assert body["ai_model"] == main.AI_MODEL


@pytest.mark.parametrize("mode", MODES)
def test_unsupported_subject_is_a_200_result_not_an_error(fake, mode):
    fake(text=json.dumps(_reply(mode, assessment_outcome="unsupported_subject", subject_present=False,
                                photo_tips=["Photograph the affected leaf"])))
    r = _post(mode)
    assert r.status_code == 200
    body = r.json()
    assert body["assessment_outcome"] == "unsupported_subject"
    assert body["subject_present"] is False
    assert body["photo_tips"] == ["Photograph the affected leaf"]


@pytest.mark.parametrize("outcome", ["unsupported_subject", "poor_image"])
def test_no_finding_outcomes_never_carry_diagnosis_confidence_or_treatment(fake, outcome):
    # Even if the model fills findings anyway, they are dropped.
    fake(text=json.dumps(_reply("crop", assessment_outcome=outcome, image_quality="poor", image_issues=["blurred"])))
    body = _post("crop").json()
    for key in ["subject_name", "scientific_name", "disease", "confidence", "medication",
                "first_aid", "preventive_measures", "recovery_period", "organic_treatment"]:
        assert body[key] is None, key
    assert body["confidence_valid"] is True  # absent, not malformed
    assert body["image_issues"] == ["blurred"]


def test_uncertain_keeps_observations_and_candidates_but_no_treatment(fake):
    fake(text=json.dumps(_reply("crop", assessment_outcome="uncertain", confidence=35,
                                alternative_findings=[{"name": "Early blight", "why_considered": "Similar spots"}],
                                what_cannot_be_confirmed="Spore type not visible")))
    body = _post("crop").json()
    assert body["assessment_outcome"] == "uncertain"
    assert body["disease"] == "Late blight" and body["confidence"] == 35
    assert body["alternative_findings"][0]["name"] == "Early blight"
    assert body["what_cannot_be_confirmed"] == "Spore type not visible"
    assert body["first_aid"]  # safe general actions kept
    assert body["medication"] is None and body["fertilizer_recommendation"] is None


def test_subject_present_false_overrides_an_inconsistent_outcome():
    out = finalize("crop", _reply("crop", assessment_outcome="assessed", subject_present=False))
    assert out["assessment_outcome"] == "unsupported_subject"
    assert out["disease"] is None


# ── No invented values ──────────────────────────────────────────────────────

def test_missing_values_stay_null_never_defaulted():
    out = finalize("crop", _reply("crop", subject_name=None, scientific_name=None, severity=None,
                                  urgency=None, health_status=None))
    assert out["subject_name"] is None and out["scientific_name"] is None
    assert out["severity"] is None and out["urgency"] is None and out["health_status"] is None


@pytest.mark.parametrize("raw,value,valid", [(None, None, True), (78, 78.0, True), (0, 0.0, True),
                                             (140, None, False), (-5, None, False), ("80", None, False)])
def test_confidence_is_never_fabricated_or_clamped(raw, value, valid):
    out = finalize("crop", _reply("crop", confidence=raw))
    assert out["confidence"] == value
    assert out["confidence_valid"] is valid


def test_soil_never_returns_fertilizer_figures_keys_from_old_contract():
    out = finalize("soil", _reply("soil", visual_ph_indication="Reddish colour can suggest acidity; needs a lab test",
                                  amendment_guidance="Add compost", lab_test_advice="Request pH and NPK test"))
    assert "fertilizer_recommendation" not in out
    assert out["ph_estimate"].endswith("lab test")
    assert out["referral"] == "Request pH and NPK test"


def test_pest_maps_its_own_keys():
    out = finalize("pest", _reply("pest", primary_finding="Fall armyworm", pest_type="Insect",
                                  damage_observed="Window-pane feeding", spread_risk="high"))
    assert out["pest_name"] == "Fall armyworm" and out["damage_type"] == "Window-pane feeding"
    assert out["spread_risk"] == "High" and out["chemical_control"].startswith("Copper")


def test_empty_strings_and_not_applicable_mean_absent():
    out = finalize("crop", _reply("crop", subject_name="", scientific_name="  ", severity="not_applicable",
                                  urgency="not_applicable", treatment=""))
    assert out["subject_name"] is None and out["scientific_name"] is None
    assert out["severity"] is None and out["urgency"] is None and out["medication"] is None


def test_confidence_is_dropped_when_there_is_no_finding():
    out = finalize("crop", _reply("crop", assessment_outcome="uncertain", primary_finding="", confidence=0))
    assert out["confidence"] is None and out["disease"] is None


LEGACY_PIPE_REPLY = chr(10).join([
    "subject_name | Tomato", "scientific_name | Solanum lycopersicum", "detected_part | Leaf",
    "health_status | Diseased", "disease | Late blight", "confidence | 77", "severity | Moderate",
])


def test_schema_rejection_falls_back_to_the_legacy_prompt_instead_of_failing(fake):
    import anthropic
    import httpx2
    req = httpx2.Request("POST", "https://api.anthropic.com/v1/messages")
    rejected = anthropic.BadRequestError("Schema is too complex for compilation.",
                                         response=httpx2.Response(400, request=req), body=None)
    msgs = fake(script=[rejected, LEGACY_PIPE_REPLY])
    r = _post("crop")
    assert r.status_code == 200
    body = r.json()
    assert body["contract_version"] == "legacy-fallback"
    assert body["disease"] == "Late blight" and body["confidence"] == 77
    assert len(msgs.calls) == 2
    assert "output_config" in msgs.calls[0] and "output_config" not in msgs.calls[1]
    # The legacy call re-read the uploaded image after the first attempt consumed it.
    assert msgs.calls[1]["messages"][0]["content"][0]["type"] == "image"


# ── Failures are failures, not diagnoses ────────────────────────────────────

@pytest.mark.parametrize("stop", ["max_tokens", "refusal"])
def test_truncated_or_refused_reply_is_502(fake, stop):
    fake(text=json.dumps(_reply("crop"))[:200], stop_reason=stop)
    r = _post("crop")
    assert r.status_code == 502
    assert r.json()["detail"]["error"] == "malformed_response"


def test_non_json_reply_is_502(fake):
    fake(text="subject_name | Maize")
    r = _post("crop")
    assert r.status_code == 502


def test_provider_error_is_503(fake):
    import anthropic
    import httpx2
    err = anthropic.APIConnectionError(request=httpx2.Request("POST", "https://api.anthropic.com/v1/messages"))
    fake(raise_exc=err)
    r = _post("crop")
    assert r.status_code == 503
    assert r.json()["detail"]["error"] == "provider_unavailable"


def test_livestock_without_a_photo_does_not_call_the_model(fake):
    msgs = fake(text="{}")
    r = TestClient(main.app).post("/predict/livestock", data={"animalType": "Goat"})
    assert r.status_code == 200
    assert r.json()["assessment_outcome"] == "poor_image"
    assert msgs.calls == []


# ── Prompts ─────────────────────────────────────────────────────────────────

def test_prompts_forbid_unsupported_dosages_and_lab_claims():
    assert "Never give drug doses" in build_prompt("livestock")
    assert "cannot measure pH" in build_prompt("soil")
    assert "Do not invent dosages" in build_prompt("crop")
    assert "Do not name a species" in build_prompt("pest")
