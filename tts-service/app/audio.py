"""Joining sentence audio with pauses, tempo, and low-bitrate mono encoding."""
import shutil
import subprocess

import numpy as np


def assemble(clips: list, pauses_ms: list, sample_rate: int):
    """Concatenate per-sentence clips with silence after each.

    Returns (samples, timings) where timings[i] = (start_sec, end_sec) of
    sentence i's speech, before any tempo change.
    """
    parts, timings, cursor = [], [], 0
    for clip, pause in zip(clips, pauses_ms):
        clip = np.asarray(clip, dtype=np.float32).reshape(-1)
        timings.append((cursor / sample_rate, (cursor + len(clip)) / sample_rate))
        parts.append(clip)
        cursor += len(clip)
        gap = int(sample_rate * max(0, pause) / 1000)
        if gap:
            parts.append(np.zeros(gap, dtype=np.float32))
            cursor += gap
    samples = np.concatenate(parts) if parts else np.zeros(0, dtype=np.float32)
    peak = float(np.max(np.abs(samples))) if samples.size else 0.0
    if peak > 1.0:
        samples = samples / peak
    return samples, timings


def resample(samples, from_rate: int, to_rate: int):
    if from_rate == to_rate or samples.size == 0:
        return samples
    n = int(round(len(samples) * to_rate / from_rate))
    return np.interp(np.linspace(0, len(samples) - 1, n), np.arange(len(samples)), samples).astype(np.float32)


def ffmpeg_available() -> bool:
    return shutil.which("ffmpeg") is not None


def encode(samples, sample_rate: int, rate: float = 1.0, fmt: str = "mp3", bitrate_kbps: int = 40) -> bytes:
    """Encode mono float samples as MP3 or Opus, applying the speaking rate.

    `rate` < 1 slows speech (0.9 = calm) without changing pitch.
    """
    rate = min(2.0, max(0.5, float(rate)))
    codec = {"mp3": ["-c:a", "libmp3lame", "-f", "mp3"],
             "opus": ["-c:a", "libopus", "-f", "ogg"]}[fmt]
    cmd = ["ffmpeg", "-hide_banner", "-loglevel", "error",
           "-f", "f32le", "-ar", str(sample_rate), "-ac", "1", "-i", "pipe:0",
           "-filter:a", f"atempo={rate}", "-ac", "1", "-b:a", f"{bitrate_kbps}k", *codec, "pipe:1"]
    proc = subprocess.run(cmd, input=np.asarray(samples, dtype="<f4").tobytes(),
                          capture_output=True, timeout=120)
    if proc.returncode != 0 or not proc.stdout:
        raise RuntimeError("ffmpeg failed: " + proc.stderr.decode(errors="replace")[:300])
    return proc.stdout
