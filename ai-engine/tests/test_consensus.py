"""
Repeat-pass consensus (consensus.py) and its wiring into /predict/*.

Uses a fake Anthropic client: these prove the combining rules, not the
model's real variability (ai-engine/eval/run_eval.py --repeats measures that).
"""
import io
import json
import os
import sys
from types import SimpleNamespace

import pytest
from PIL import Image

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import main  # noqa: E402
from consensus import combine, same_finding  # noqa: E402
from diagnosis_contract import finalize, schema_for  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402


def _reply(**overrides):
    base = {k: None for k in schema_for("crop")["properties"]}
    base.update({
        "assessment_outcome": "assessed", "subject_present": True, "image_quality": "good",
        "image_issues": [], "photo_tips": [], "alternative_findings": [],
        "symptoms_observed": ["lesions"], "immediate_actions": ["Remove infected leaves"],
        "subject_name": "Tomato", "health_status": "Diseased", "primary_finding": "Late blight",
        "confidence": 70, "severity": "moderate", "urgency": "high",
        "treatment": "Copper-based fungicide, at the label rate", "explanation": "Water-soaked lesions.",
    })
    base.update(overrides)
    return base


def _fin(**overrides):
    return finalize("crop", _reply(**overrides))


# ── Combining rules ──────────────────────────────────────────────────────────

def test_median_confidence_of_agreeing_passes():
    out = combine([_fin(confidence=65), _fin(confidence=78), _fin(confidence=71)])
    assert out["disease"] == "Late blight"
    assert out["confidence"] == 71
    assert out["assessment_outcome"] == "assessed"
    assert out["consensus"]["agreeing_passes"] == 3
    assert out["consensus"]["confidence_spread"] == 13


def test_one_outlier_pass_cannot_move_the_answer():
    out = combine([_fin(confidence=72), _fin(primary_finding="Early blight", confidence=90), _fin(confidence=74)])
    assert out["disease"] == "Late blight"
    assert out["confidence"] == 73          # median of 72 and 74, the 90 is a different finding
    assert out["consensus"]["agreeing_passes"] == 2
    assert "Early blight" in [a["name"] for a in out["alternative_findings"]]


def test_text_fields_come_from_one_real_pass():
    out = combine([_fin(confidence=60, explanation="A"), _fin(confidence=70, explanation="B"),
                   _fin(confidence=80, explanation="C")])
    assert out["explanation"] == "B"  # the pass whose confidence is the median


def test_no_majority_is_uncertain_with_every_candidate_and_no_treatment():
    out = combine([_fin(primary_finding="Late blight"), _fin(primary_finding="Septoria leaf spot"),
                   _fin(primary_finding="Bacterial spot")])
    assert out["assessment_outcome"] == "uncertain"
    assert out["confidence"] is None
    assert out["medication"] is None
    names = [a["name"] for a in out["alternative_findings"]]
    assert {"Septoria leaf spot", "Bacterial spot"} <= set(names)


def test_majority_poor_image_wins_over_one_guess():
    poor = _fin(assessment_outcome="poor_image", image_quality="poor", image_issues=["blurred"])
    out = combine([poor, poor, _fin()])
    assert out["assessment_outcome"] == "poor_image"
    assert out["disease"] is None and out["confidence"] is None


def test_agreeing_on_a_finding_but_mostly_uncertain_stays_uncertain():
    out = combine([_fin(assessment_outcome="uncertain", confidence=40), _fin(assessment_outcome="uncertain", confidence=45),
                   _fin(confidence=70)])
    assert out["assessment_outcome"] == "uncertain"
    assert out["medication"] is None


def test_single_pass_is_unchanged_apart_from_the_consensus_block():
    single = _fin(confidence=66)
    out = combine([single])
    assert {k: v for k, v in out.items() if k != "consensus"} == single
    assert out["consensus"]["method"] == "single pass"


@pytest.mark.parametrize("a,b,same", [
    ("Late blight", "Tomato late blight", True),
    ("Late blight", "Late blight (Phytophthora infestans)", True),
    ("Late blight", "late-blight", True),
    ("Late blight", "Early blight", False),
    ("Lumpy skin disease", "Lumpy skin", True),
    ("Ringworm", "Mange", False),
])
def test_finding_names_are_grouped_conservatively(a, b, same):
    assert same_finding({"disease": a}, {"disease": b}) is same


# ── Wiring into the endpoint ─────────────────────────────────────────────────

class _Msgs:
    def __init__(self, replies):
        self.replies, self.calls = list(replies), []

    async def create(self, **kwargs):
        self.calls.append(kwargs)
        step = self.replies.pop(0)
        if isinstance(step, Exception):
            raise step
        return SimpleNamespace(stop_reason="end_turn", content=[SimpleNamespace(type="text", text=json.dumps(step))])


def _install(monkeypatch, replies, passes):
    msgs = _Msgs(replies)
    monkeypatch.setattr(main, "_get_client", lambda: SimpleNamespace(messages=msgs))
    monkeypatch.setattr(main, "ANTHROPIC_KEY", "test-key")
    monkeypatch.setattr(main, "API_KEY", "")
    monkeypatch.setenv("SCAN_PASSES", str(passes))
    return msgs


def _post():
    buf = io.BytesIO()
    Image.new("RGB", (320, 240), (90, 140, 60)).save(buf, format="JPEG")
    return TestClient(main.app).post("/predict/crop", files=[("images", ("a.jpg", buf.getvalue(), "image/jpeg"))])


def test_endpoint_sends_identical_requests_and_returns_the_median(monkeypatch):
    msgs = _install(monkeypatch, [_reply(confidence=65), _reply(confidence=78), _reply(confidence=70)], passes=3)
    r = _post()
    assert r.status_code == 200
    assert len(msgs.calls) == 3
    assert all(c == msgs.calls[0] for c in msgs.calls)  # same prompt, image and schema every pass
    body = r.json()
    assert body["confidence"] == 70
    assert body["consensus"]["passes_completed"] == 3


def test_one_failed_pass_is_tolerated(monkeypatch):
    import anthropic
    import httpx2
    err = anthropic.APIConnectionError(request=httpx2.Request("POST", "https://api.anthropic.com/v1/messages"))
    _install(monkeypatch, [_reply(confidence=70), err, _reply(confidence=74)], passes=3)
    body = _post().json()
    assert body["confidence"] == 72
    assert body["consensus"]["passes_requested"] == 3 and body["consensus"]["passes_completed"] == 2


def test_scan_passes_is_bounded(monkeypatch):
    for raw, want in [("0", 1), ("9", 5), ("x", 3), ("3", 3)]:
        monkeypatch.setenv("SCAN_PASSES", raw)
        assert main._scan_passes() == want
