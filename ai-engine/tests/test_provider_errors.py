"""
Scan error codes: every Claude API failure leaves the engine as a labelled
503 (detail.error), so the website can tell the farmer the right thing and
staff the real cause. Undecodable uploads are labelled unreadable_image.
Uses a fake Anthropic client.
"""
import io
import os
import sys
from types import SimpleNamespace

import pytest
from PIL import Image

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import anthropic  # noqa: E402
import httpx2  # noqa: E402
import main  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402

REQ = httpx2.Request("POST", "https://api.anthropic.com/v1/messages")


def _status_error(cls, status, message):
    return cls(message, response=httpx2.Response(status, request=REQ), body={"error": {"message": message}})


CASES = [
    ("auth", lambda: _status_error(anthropic.AuthenticationError, 401, "invalid x-api-key"), "provider_auth_failed"),
    ("forbidden", lambda: _status_error(anthropic.PermissionDeniedError, 403, "permission denied"), "provider_auth_failed"),
    ("no credit (400)", lambda: _status_error(anthropic.BadRequestError, 400,
                                              "Your credit balance is too low to access the Anthropic API."), "provider_no_credit"),
    ("spend limit", lambda: _status_error(anthropic.BadRequestError, 400,
                                          "You have reached your specified API usage limits."), "provider_no_credit"),
    ("rate limit", lambda: _status_error(anthropic.RateLimitError, 429, "rate_limit_error"), "provider_rate_limit"),
    ("server error", lambda: _status_error(anthropic.InternalServerError, 500, "internal"), "provider_error"),
    ("overloaded", lambda: _status_error(anthropic.APIStatusError, 529, "overloaded_error"), "provider_error"),
    ("connection", lambda: anthropic.APIConnectionError(request=REQ), "provider_error"),
]


class _Msgs:
    def __init__(self, exc):
        self.exc, self.calls = exc, 0

    async def create(self, **kw):
        self.calls += 1
        raise self.exc


def _post(mode="crop"):
    buf = io.BytesIO()
    Image.new("RGB", (64, 64), (90, 140, 60)).save(buf, format="JPEG")
    return TestClient(main.app).post(f"/predict/{mode}", files=[("images", ("a.jpg", buf.getvalue(), "image/jpeg"))])


@pytest.mark.parametrize("name,make,code", CASES, ids=[c[0] for c in CASES])
def test_claude_failures_are_labelled(monkeypatch, name, make, code):
    msgs = _Msgs(make())
    monkeypatch.setattr(main, "_get_client", lambda: SimpleNamespace(messages=msgs))
    monkeypatch.setattr(main, "ANTHROPIC_KEY", "test-key")
    monkeypatch.setattr(main, "API_KEY", "")
    monkeypatch.setenv("SCAN_PASSES", "1")
    r = _post()
    assert r.status_code == 503
    assert r.json()["detail"]["error"] == code
    # No credit is not a schema problem: the legacy prompt is not tried as well.
    if code == "provider_no_credit":
        assert msgs.calls == 1


def test_missing_key_is_labelled_not_configured(monkeypatch):
    monkeypatch.setattr(main, "ANTHROPIC_KEY", "")
    monkeypatch.setattr(main, "API_KEY", "")
    r = _post()
    assert r.status_code == 503
    assert r.json()["detail"]["error"] == "provider_not_configured"


def test_engine_key_mismatch_is_401_not_a_photo_problem(monkeypatch):
    monkeypatch.setattr(main, "API_KEY", "right-key")
    r = TestClient(main.app).post("/predict/crop", headers={"Authorization": "Bearer wrong"},
                                  files=[("images", ("a.jpg", b"x", "image/jpeg"))])
    assert r.status_code == 401


def test_an_undecodable_upload_is_labelled_unreadable_image(monkeypatch):
    monkeypatch.setattr(main, "ANTHROPIC_KEY", "test-key")
    monkeypatch.setattr(main, "API_KEY", "")
    monkeypatch.setattr(main, "_get_client", lambda: SimpleNamespace(messages=_Msgs(RuntimeError("not called"))))
    r = TestClient(main.app).post("/predict/crop", files=[("images", ("a.jpg", b"not an image at all", "image/jpeg"))])
    assert r.status_code == 422
    assert r.json()["detail"]["error"] == "unreadable_image"
