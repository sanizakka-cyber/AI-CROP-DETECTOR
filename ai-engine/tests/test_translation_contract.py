"""
"Listen to your result": /translate/scan-result.

A fake Anthropic client is used, so these prove the request/response
handling only, not translation quality (that needs native-speaker review).
"""
import json
import os
import sys
from types import SimpleNamespace

import pytest

os.environ.setdefault("ANTHROPIC_API_KEY", "test-key")
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import anthropic  # noqa: E402
import main  # noqa: E402
import translation_contract as tc  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402

SOURCE = {
    "observations": ["Brown spots on the leaves", "White mould under the leaf"],
    "explanation": "This looks like late blight.",
    "recommendations": ["Spray Mancozeb 80% WP at 2.5 g per litre", "Remove infected leaves"],
    "safety_note": "This is AI guidance and may be wrong.",
}
HAUSA = {
    "observations": ["Ɗigo-ɗigo masu ruwan ƙasa a kan ganye", "Farin fumfuna a ƙarƙashin ganye"],
    "explanation": "Wannan yana kama da cutar late blight.",
    "recommendations": ["Fesa Mancozeb 80% WP a 2.5 g kowace lita", "Cire ganyen da suka kamu"],
    "safety_note": "Wannan shawarar AI ce kuma tana iya yin kuskure.",
}


class FakeMessages:
    def __init__(self, text="", stop_reason="end_turn", raise_exc=None):
        self.text, self.stop_reason, self.raise_exc = text, stop_reason, raise_exc
        self.calls = []

    async def create(self, **kwargs):
        self.calls.append(kwargs)
        if self.raise_exc:
            raise self.raise_exc
        return SimpleNamespace(stop_reason=self.stop_reason,
                               content=[SimpleNamespace(type="text", text=self.text)])


@pytest.fixture
def engine(monkeypatch):
    def make(**kw):
        msgs = FakeMessages(**kw)
        monkeypatch.setattr(main, "_get_client", lambda: SimpleNamespace(messages=msgs))
        monkeypatch.setattr(main, "API_KEY", "")
        return TestClient(main.app), msgs
    return make


def body(**over):
    b = {"source_language_name": "English", "target_language_name": "Hausa", "content": SOURCE,
         "glossary": [{"english_term": "leaf", "local_term": "ganye"}],
         "protected_terms": ["Mancozeb 80% WP", "2.5 g"]}
    b.update(over)
    return b


def test_schema_has_no_unions_or_optional_fields():
    s = tc.schema()
    assert set(s["required"]) == set(s["properties"]) == set(tc.CONTENT_KEYS)
    assert "anyOf" not in json.dumps(s) and "null" not in json.dumps(s)


def test_translates_and_keeps_special_letters(engine):
    client, msgs = engine(text=json.dumps(HAUSA, ensure_ascii=False))
    r = client.post("/translate/scan-result", json=body())
    assert r.status_code == 200
    assert r.json()["translated"] == HAUSA
    assert "ƙ" in r.json()["translated"]["observations"][0] + r.json()["translated"]["observations"][1]
    sent = msgs.calls[0]
    assert sent["output_config"]["format"]["schema"] == tc.schema()
    prompt = sent["messages"][0]["content"]
    assert "leaf => ganye" in prompt and "- Mancozeb 80% WP" in prompt and "into Hausa" in prompt


def test_new_language_needs_only_a_name(engine):
    client, msgs = engine(text=json.dumps(HAUSA, ensure_ascii=False))
    assert client.post("/translate/scan-result", json=body(target_language_name="Nigerian Pidgin")).status_code == 200
    assert "into Nigerian Pidgin" in msgs.calls[0]["messages"][0]["content"]


def test_dropped_list_item_is_rejected_not_stored(engine):
    short = dict(HAUSA, recommendations=HAUSA["recommendations"][:1])
    client, _ = engine(text=json.dumps(short, ensure_ascii=False))
    r = client.post("/translate/scan-result", json=body())
    assert r.status_code == 502
    assert r.json()["detail"]["error"] == "shape_mismatch"


@pytest.mark.parametrize("stop", ["max_tokens", "refusal"])
def test_incomplete_reply_is_an_error(engine, stop):
    client, _ = engine(text="{", stop_reason=stop)
    assert client.post("/translate/scan-result", json=body()).status_code == 502


def test_provider_failure_is_503(engine):
    err = anthropic.APIConnectionError(request=SimpleNamespace(method="POST", url="x"))
    client, _ = engine(raise_exc=err)
    assert client.post("/translate/scan-result", json=body()).status_code == 503


def test_missing_section_is_rejected_before_calling_the_model(engine):
    client, msgs = engine(text="{}")
    content = {k: v for k, v in SOURCE.items() if k != "safety_note"}
    assert client.post("/translate/scan-result", json=body(content=content)).status_code == 422
    assert msgs.calls == []
