"""
Benchmark YarnGPT2 the way Laravel uses it: one request per sentence.

For each result text it measures
  - time to first audio: first sentence synthesised and MP3-encoded,
  - total time: every sentence,
  - real-time factor (RTF): synthesis time / seconds of audio produced,
  - peak memory (RSS of this process),
  - failures: an exception, empty audio, or audio far longer/shorter than the
    text (the model "running away" until max_length, or stopping early).

Usage (from tts-service/):
  python bench/run_benchmark.py --texts bench/texts.json --threads 2 --out bench/results-cpu2.json \
      [--langs en,ha] [--voices ha=hausa_female1] [--limit-sentences 3] [--save-audio bench/audio]

Needs the model runtime (requirements-models.txt + outetts==0.2.3 --no-deps,
the yarngpt repo on PYTHONPATH, MODELS_DIR with the WavTokenizer files) and
ffmpeg on PATH.
"""
import argparse
import json
import os
import re
import statistics
import sys
import threading
import time

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import psutil  # noqa: E402

from app import audio  # noqa: E402
from app.engines import YARNGPT_VOICES, YarnGptEngine  # noqa: E402

SR = 24000


def sentences(text: str) -> list:
    parts = re.split(r"(?<=[.!?])\s+", text.strip())
    return [p.strip() for p in parts if p.strip()]


class PeakRss:
    def __init__(self):
        self.peak = 0
        self._stop = threading.Event()
        self._proc = psutil.Process()

    def __enter__(self):
        def run():
            while not self._stop.wait(0.2):
                self.peak = max(self.peak, self._proc.memory_info().rss)
        self._t = threading.Thread(target=run, daemon=True)
        self._t.start()
        return self

    def __exit__(self, *a):
        self._stop.set()
        self._t.join()


def plausible(seconds: float, text: str) -> bool:
    # Speech runs at very roughly 10-20 characters a second in all four
    # languages; outside 4-40 it is a runaway or a truncated generation.
    cps = len(text) / max(seconds, 0.01)
    return 4 <= cps <= 40


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--texts", required=True)
    ap.add_argument("--threads", type=int, default=2)
    ap.add_argument("--out", required=True)
    ap.add_argument("--langs", default="en,ha,yo,ig")
    ap.add_argument("--voices", default="", help="lang=voice,... (default: first voice per language)")
    ap.add_argument("--limit-sentences", type=int, default=0)
    ap.add_argument("--save-audio", default="")
    args = ap.parse_args()

    import torch
    torch.set_num_threads(args.threads)

    texts = json.load(open(args.texts, encoding="utf-8"))
    voices = dict(kv.split("=") for kv in args.voices.split(",") if kv)
    engine = YarnGptEngine()
    report = {"threads": args.threads, "torch": torch.__version__, "cpu": os.environ.get("BENCH_CPU", ""), "results": []}

    with PeakRss() as rss:
        t0 = time.monotonic()
        engine._load()
        report["load_sec"] = round(time.monotonic() - t0, 1)
        report["rss_after_load_mb"] = round(psutil.Process().memory_info().rss / 2**20)

        for item in texts:
            lang = item["lang"]
            if lang not in args.langs.split(","):
                continue
            voice = voices.get(lang, YARNGPT_VOICES[lang][0])
            sents = sentences(item["text"])
            if args.limit_sentences:
                sents = sents[: args.limit_sentences]
            row = {"id": item["id"], "lang": lang, "voice": voice, "words": len(" ".join(sents).split()),
                   "sentences": len(sents), "per_sentence": [], "failures": 0}
            started = time.monotonic()
            synth_total, audio_total = 0.0, 0.0
            for i, s in enumerate(sents):
                t = time.monotonic()
                try:
                    samples, sr = engine.synth(s, lang, voice)
                    data = audio.encode(samples, sr, rate=0.9, fmt="mp3", bitrate_kbps=40)
                    secs = len(samples) / sr / 0.9
                    ok = len(samples) > 0 and plausible(secs, s)
                    err = None
                except Exception as e:  # noqa: BLE001 - every failure counts
                    data, secs, ok, err = b"", 0.0, False, f"{type(e).__name__}: {e}"[:200]
                took = time.monotonic() - t
                synth_total += took
                audio_total += secs
                if i == 0:
                    row["first_audio_sec"] = round(time.monotonic() - started, 1)
                if not ok:
                    row["failures"] += 1
                row["per_sentence"].append({"chars": len(s), "sec": round(took, 1), "audio_sec": round(secs, 1), "ok": ok, **({"error": err} if err else {})})
                if args.save_audio and data:
                    os.makedirs(args.save_audio, exist_ok=True)
                    with open(os.path.join(args.save_audio, f"{item['id']}-{voice}-{i:02d}.mp3"), "wb") as f:
                        f.write(data)
                print(f"{item['id']} {voice} s{i + 1}/{len(sents)} {took:.1f}s audio {secs:.1f}s ok={ok}", flush=True)
            row["total_sec"] = round(time.monotonic() - started, 1)
            row["audio_sec"] = round(audio_total, 1)
            row["rtf"] = round(synth_total / audio_total, 2) if audio_total else None
            row["failure_rate"] = round(row["failures"] / max(1, len(sents)), 2)
            report["results"].append(row)
            json.dump(report, open(args.out, "w", encoding="utf-8"), indent=1, ensure_ascii=False)

    report["peak_rss_mb"] = round(rss.peak / 2**20)
    by_lang = {}
    for r in report["results"]:
        by_lang.setdefault(r["lang"], []).append(r)
    report["summary"] = {
        lang: {
            "results": len(rs),
            "first_audio_sec_median": statistics.median(r["first_audio_sec"] for r in rs),
            "total_sec_median": statistics.median(r["total_sec"] for r in rs),
            "rtf_median": statistics.median(r["rtf"] for r in rs if r["rtf"]),
            "failure_rate": round(sum(r["failures"] for r in rs) / max(1, sum(r["sentences"] for r in rs)), 2),
        } for lang, rs in by_lang.items()
    }
    json.dump(report, open(args.out, "w", encoding="utf-8"), indent=1, ensure_ascii=False)
    print(json.dumps(report["summary"], indent=1))


if __name__ == "__main__":
    main()
