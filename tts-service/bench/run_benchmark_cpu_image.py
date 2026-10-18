"""
Sizing benchmark for the CPU speech image (Dockerfile.openbible): OpenBible
Hausa and Kokoro English loaded in ONE process, as the container runs them,
at several thread counts (a stand-in for the vCPUs of a hosting plan).
Reports per engine and thread count: first-sentence time, median sentence
time, real-time factor, and the process's peak memory with both loaded.

Usage (from tts-service/, Python 3.11 env with coqui-tts and kokoro):
  python bench/run_benchmark_cpu_image.py --texts bench/texts.json --models C:/pyvits/models \
      --threads 1 2 4 --sentences 6 --out bench/results-cpu-image-laptop.json
"""
import argparse
import json
import os
import statistics
import sys
import time

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import numpy as np  # noqa: E402

from run_benchmark import PeakRss, sentences  # noqa: E402
from run_benchmark_vits import load as load_vits  # noqa: E402


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--texts", required=True)
    ap.add_argument("--models", required=True)
    ap.add_argument("--threads", type=int, nargs="+", default=[1, 2, 4])
    ap.add_argument("--sentences", type=int, default=6)
    ap.add_argument("--out", required=True)
    a = ap.parse_args()

    import torch
    from kokoro import KPipeline
    texts = {t["lang"]: t["text"] for t in json.load(open(a.texts, encoding="utf-8")) if t["lang"] in ("en", "ha")}
    report = {"cpu": os.environ.get("BENCH_CPU", ""), "sentences_per_run": a.sentences, "runs": []}

    with PeakRss() as rss:
        t0 = time.monotonic()
        vits = load_vits(os.path.join(a.models, "Hausa"))
        kok = KPipeline(lang_code="a", repo_id="hexgrad/Kokoro-82M")
        report["load_both_sec"] = round(time.monotonic() - t0, 1)

        def say_ha(s):
            w = np.asarray(vits.tts(text=s, speaker_name=None, split_sentences=False), dtype=np.float32)
            return len(w) / vits.output_sample_rate

        def say_en(s):
            return sum(len(r.audio) for r in kok(s, voice="af_heart", split_pattern=None) if r.audio is not None) / 24000

        say_ha("Sannu."), say_en("Hello.")  # warm-up, not measured
        for n in a.threads:
            torch.set_num_threads(n)
            for lang, fn in (("ha", say_ha), ("en", say_en)):
                secs, audio = [], 0.0
                for s in sentences(texts[lang])[: a.sentences]:
                    t = time.monotonic()
                    audio += fn(s)
                    secs.append(time.monotonic() - t)
                row = {"threads": n, "lang": lang, "engine": "openbible" if lang == "ha" else "kokoro",
                       "first_sentence_sec": round(secs[0], 2), "median_sentence_sec": round(statistics.median(secs), 2),
                       "synth_sec": round(sum(secs), 1), "audio_sec": round(audio, 1), "rtf": round(sum(secs) / audio, 2)}
                report["runs"].append(row)
                print(row, flush=True)
    report["peak_rss_mb_both_loaded"] = round(rss.peak / 2**20)
    print("peak RSS MB (both engines loaded):", report["peak_rss_mb_both_loaded"])
    json.dump(report, open(a.out, "w", encoding="utf-8"), indent=1)


if __name__ == "__main__":
    main()
