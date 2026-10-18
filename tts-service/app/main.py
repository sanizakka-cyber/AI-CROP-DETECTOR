"""
MSAS FarmAI speech service: self-hosted text-to-speech for "Listen to your result".

Laravel sends text that is already translated, normalised and split into
sentences (each with the pause that follows it). This service speaks each
sentence, joins them with the pauses, applies the speaking rate, and returns
one small mono audio file plus where each sentence starts and ends, so the
app can highlight the sentence being spoken.
"""
import base64
import logging
import os
import time

from fastapi import FastAPI, HTTPException, Request
from pydantic import BaseModel, Field

from app import audio
from app.engines import LICENSES, EngineError, enabled_engines

log = logging.getLogger("msas.tts")
app = FastAPI(title="MSAS FarmAI Speech Service", version="1.0.0")

MAX_SEGMENTS = 80
MAX_SEGMENT_CHARS = 300
_engines = None


def engines() -> dict:
    global _engines
    if _engines is None:
        _engines = enabled_engines()
    return _engines


def _check_auth(request: Request):
    key = os.environ.get("TTS_SERVICE_KEY", "")
    if key and request.headers.get("Authorization", "") != f"Bearer {key}":
        raise HTTPException(status_code=401, detail="Unauthorized.")


class Segment(BaseModel):
    text: str = Field(min_length=1, max_length=MAX_SEGMENT_CHARS)
    pause_ms: int = Field(default=450, ge=0, le=3000)


class SynthesizeRequest(BaseModel):
    engine: str
    lang: str
    voice: str
    rate: float = Field(default=0.9, ge=0.5, le=2.0)
    format: str = Field(default="mp3", pattern="^(mp3|opus)$")
    bitrate_kbps: int = Field(default=40, ge=24, le=64)
    segments: list[Segment] = Field(min_length=1, max_length=MAX_SEGMENTS)


@app.get("/health")
def health():
    return {"status": "ok", "engines": list(engines()), "ffmpeg": audio.ffmpeg_available()}


@app.get("/voices")
def voices(request: Request):
    _check_auth(request)
    return {name: {"voices": e.voices(), **LICENSES[name]} for name, e in engines().items()}


@app.post("/synthesize")
def synthesize(req: SynthesizeRequest, request: Request):
    _check_auth(request)
    engine = engines().get(req.engine)
    if engine is None:
        raise HTTPException(status_code=422, detail={"error": "engine_unavailable", "message": f"{req.engine} is not enabled here."})

    started = time.monotonic()
    clips, rate_hz = [], None
    try:
        for seg in req.segments:
            samples, sr = engine.synth(seg.text, req.lang, req.voice)
            if rate_hz is None:
                rate_hz = sr
            clips.append(audio.resample(samples, sr, rate_hz))
    except EngineError as e:
        raise HTTPException(status_code=422, detail={"error": "unsupported_voice", "message": str(e)})
    except Exception as e:  # model failure: Laravel retries and shows the text meanwhile
        log.exception("synthesis failed")
        raise HTTPException(status_code=503, detail={"error": "synthesis_failed", "message": str(e)[:300]})

    samples, timings = audio.assemble(clips, [s.pause_ms for s in req.segments], rate_hz)
    try:
        encoded = audio.encode(samples, rate_hz, req.rate, req.format, req.bitrate_kbps)
    except Exception as e:
        raise HTTPException(status_code=503, detail={"error": "encoding_failed", "message": str(e)[:300]})

    scale = 1.0 / req.rate  # atempo shortens/lengthens every timestamp equally
    return {
        "format": req.format,
        "mime": "audio/mpeg" if req.format == "mp3" else "audio/ogg",
        "audio_base64": base64.b64encode(encoded).decode(),
        "size_bytes": len(encoded),
        "duration_sec": round(len(samples) / rate_hz * scale, 2),
        "segments": [{"index": i, "start": round(a * scale, 2), "end": round(b * scale, 2)} for i, (a, b) in enumerate(timings)],
        "engine": req.engine,
        "voice": req.voice,
        "license": LICENSES[req.engine]["license"],
        "latency_ms": int((time.monotonic() - started) * 1000),
    }
