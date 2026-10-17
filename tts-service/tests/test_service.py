"""Speech service behaviour with a fake engine (no model weights needed)."""
import base64
import os
import sys

import numpy as np
import pytest

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from fastapi.testclient import TestClient  # noqa: E402

from app import audio, engines, main  # noqa: E402

SR = 1000  # tiny sample rate keeps the arithmetic obvious


class FakeEngine:
    name = "yarngpt"

    def __init__(self, fail_on=None):
        self.calls, self.fail_on = [], fail_on

    def voices(self):
        return {"ha": ["hausa_female2"]}

    def synth(self, text, lang, voice):
        self.calls.append((text, lang, voice))
        if self.fail_on and self.fail_on in text:
            raise RuntimeError("model crashed")
        if voice != "hausa_female2":
            raise engines.EngineError("unknown voice")
        return np.full(len(text) * 10, 0.1, dtype=np.float32), SR  # 10 samples per char


@pytest.fixture
def client(monkeypatch):
    def make(engine=None, key=""):
        engine = engine or FakeEngine()
        monkeypatch.setattr(main, "_engines", {"yarngpt": engine})
        monkeypatch.setenv("TTS_SERVICE_KEY", key)
        monkeypatch.setattr(audio, "encode", lambda s, sr, rate, fmt, kbps: b"ID3fake" + bytes(8))
        return TestClient(main.app), engine
    return make


def req(**over):
    body = {"engine": "yarngpt", "lang": "ha", "voice": "hausa_female2", "rate": 1.0,
            "segments": [{"text": "Ganye ya ƙone.", "pause_ms": 450}, {"text": "Cire shi.", "pause_ms": 0}]}
    body.update(over)
    return body


def test_assemble_inserts_pauses_and_reports_sentence_times():
    samples, timings = audio.assemble([np.ones(100), np.ones(50)], [250, 450], SR)
    assert len(samples) == 100 + 250 + 50 + 450
    assert timings == [(0.0, 0.1), (0.35, 0.4)]
    assert samples[100:350].max() == 0  # the pause is silence


def test_assemble_never_clips():
    samples, _ = audio.assemble([np.array([3.0, -1.5])], [0], SR)
    assert np.max(np.abs(samples)) <= 1.0


def test_resample_changes_length_only():
    out = audio.resample(np.ones(160, dtype=np.float32), 16000, 24000)
    assert len(out) == 240 and np.allclose(out, 1.0)


def test_synthesize_returns_audio_and_highlight_timings(client):
    c, engine = client()
    r = c.post("/synthesize", json=req())
    assert r.status_code == 200
    body = r.json()
    assert base64.b64decode(body["audio_base64"]).startswith(b"ID3")
    assert body["size_bytes"] == len(base64.b64decode(body["audio_base64"]))
    # "Ganye ya ƙone." = 14 chars -> 0.14 s, then 0.45 s pause, "Cire shi." = 9 chars -> 0.09 s
    assert body["segments"] == [{"index": 0, "start": 0.0, "end": 0.14}, {"index": 1, "start": 0.59, "end": 0.68}]
    assert body["license"] == "Apache-2.0"
    assert engine.calls[0] == ("Ganye ya ƙone.", "ha", "hausa_female2")  # special letters reach the model intact


def test_slower_rate_stretches_timings(client):
    c, _ = client()
    body = c.post("/synthesize", json=req(rate=0.5)).json()
    assert body["segments"][1]["end"] == pytest.approx(1.36)


def test_unknown_voice_is_422_and_model_crash_is_503(client):
    c, _ = client()
    assert c.post("/synthesize", json=req(voice="nobody")).status_code == 422
    c, _ = client(FakeEngine(fail_on="Cire"))
    r = c.post("/synthesize", json=req())
    assert r.status_code == 503 and r.json()["detail"]["error"] == "synthesis_failed"


def test_disabled_engine_and_oversized_input_are_rejected(client):
    c, _ = client()
    assert c.post("/synthesize", json=req(engine="farmerline")).status_code == 422
    assert c.post("/synthesize", json=req(segments=[{"text": "a" * 301}])).status_code == 422
    assert c.post("/synthesize", json=req(segments=[])).status_code == 422


def test_requires_key_when_configured(client):
    c, _ = client(key="s3cret")
    assert c.post("/synthesize", json=req()).status_code == 401
    assert c.post("/synthesize", json=req(), headers={"Authorization": "Bearer s3cret"}).status_code == 200


def test_non_commercial_engines_are_never_enabled(monkeypatch):
    monkeypatch.setitem(engines.LICENSES, "farmerline", {**engines.LICENSES["farmerline"], "commercial_use": False})
    monkeypatch.setenv("TTS_ENGINES", "yarngpt,farmerline")
    monkeypatch.delenv("ALLOW_NON_COMMERCIAL_MODELS", raising=False)
    assert list(engines.enabled_engines()) == ["yarngpt"]


def test_voice_names_match_the_speaker_files():
    # The model loads default_speakers_local/<voice>.json. The card's
    # "hausa_feamle1" spelling has no file and failed every Hausa request.
    assert "hausa_female1" in engines.YARNGPT_VOICES["ha"]
    assert "yoruba_female1" in engines.YARNGPT_VOICES["yo"]
    assert not any("feamle" in v for vs in engines.YARNGPT_VOICES.values() for v in vs)


@pytest.mark.skipif(not audio.ffmpeg_available(), reason="ffmpeg not installed")
def test_real_encoding_is_small_mono_mp3():
    tone = (0.3 * np.sin(np.linspace(0, 2 * np.pi * 440 * 3, 24000 * 3))).astype(np.float32)
    data = audio.encode(tone, 24000, rate=0.9, fmt="mp3", bitrate_kbps=40)
    assert data[:3] == b"ID3" or data[0] == 0xFF
    assert len(data) < 3.4 * 40_000 / 8 * 1.3  # ~40 kbps for ~3.3 s, with container overhead


@pytest.mark.skipif(not audio.ffmpeg_available(), reason="ffmpeg not installed")
def test_sentence_files_join_into_one_playable_mp3():
    # Laravel concatenates sentence files byte for byte. Each must start with
    # an MPEG frame (no ID3 tag) and carry no Xing/Info header, or players
    # would read the joined file's length from the first sentence only.
    import subprocess
    one = (0.3 * np.sin(np.linspace(0, 2 * np.pi * 220 * 2, 24000 * 2))).astype(np.float32)
    a = audio.encode(one, 24000, rate=1.0, fmt="mp3", bitrate_kbps=40)
    b = audio.encode(one, 24000, rate=1.0, fmt="mp3", bitrate_kbps=40)
    for chunk in (a, b):
        assert chunk[0] == 0xFF and (chunk[1] & 0xE0) == 0xE0
        assert b"Xing" not in chunk[:400] and b"Info" not in chunk[:400]
    proc = subprocess.run(["ffmpeg", "-hide_banner", "-loglevel", "error", "-f", "mp3", "-i", "pipe:0",
                           "-f", "f32le", "-ac", "1", "-ar", "24000", "pipe:1"], input=a + b, capture_output=True, timeout=60)
    assert proc.returncode == 0
    seconds = len(proc.stdout) / 4 / 24000
    assert 3.8 < seconds < 4.4  # two 2 s sentences (plus encoder padding)
