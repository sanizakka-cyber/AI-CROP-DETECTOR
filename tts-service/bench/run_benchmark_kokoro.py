"""
Benchmark Kokoro-82M (hexgrad/Kokoro-82M, Apache-2.0) for English the same
way as run_benchmark.py and run_benchmark_vits.py: one call per sentence,
time to first audio, total time, real-time factor, peak memory, failures.

Usage (from tts-service/, in an environment with kokoro==0.9.4, Python 3.10-3.12):
  python bench/run_benchmark_kokoro.py --texts bench/texts.json --threads 2 \
      --voices af_heart bf_emma --out bench/results-kokoro-laptop-2threads.json [--save-audio DIR]
"""
import argparse
import json
import os
import sys
import time

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import numpy as np  # noqa: E402

from run_benchmark import PeakRss, plausible, sentences  # noqa: E402

SR = 24000


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--texts", required=True)
    ap.add_argument("--threads", type=int, default=2)
    ap.add_argument("--voices", nargs="+", default=["af_heart"])
    ap.add_argument("--repeats", type=int, default=2, help="runs per text and voice (the first includes warm-up)")
    ap.add_argument("--out", required=True)
    ap.add_argument("--save-audio", default="")
    args = ap.parse_args()

    import torch
    torch.set_num_threads(args.threads)
    from kokoro import KPipeline

    texts = [t for t in json.load(open(args.texts, encoding="utf-8")) if t["lang"] == "en"]
    report = {"engine": "Kokoro-82M", "threads": args.threads, "cpu": os.environ.get("BENCH_CPU", ""), "results": []}

    with PeakRss() as rss:
        pipes = {}
        for voice in args.voices:
            lang_code = voice[0]  # a = American English, b = British English
            if lang_code not in pipes:
                t0 = time.monotonic()
                pipes[lang_code] = KPipeline(lang_code=lang_code, repo_id="hexgrad/Kokoro-82M")
                report[f"load_sec_{lang_code}"] = round(time.monotonic() - t0, 1)
            pipe = pipes[lang_code]
            for rep in range(args.repeats):
                for item in texts:
                    sents = sentences(item["text"])
                    row = {"id": item["id"], "lang": "en", "voice": voice, "run": rep + 1, "sentences": len(sents),
                           "per_sentence": [], "failures": 0}
                    started = time.monotonic()
                    synth_total = audio_total = 0.0
                    for i, s in enumerate(sents):
                        t = time.monotonic()
                        try:
                            # split_pattern=None: the service already sends one sentence per call.
                            chunks = [np.asarray(r.audio, dtype=np.float32) for r in pipe(s, voice=voice, speed=1.0, split_pattern=None)
                                      if r.audio is not None]
                            wav = np.concatenate(chunks) if chunks else np.zeros(0, np.float32)
                            secs = len(wav) / SR
                            ok, err = len(wav) > 0 and plausible(secs, s), None
                        except Exception as e:  # noqa: BLE001
                            wav, secs, ok, err = np.zeros(0, np.float32), 0.0, False, f"{type(e).__name__}: {e}"[:200]
                        took = time.monotonic() - t
                        synth_total += took
                        audio_total += secs
                        if i == 0:
                            row["first_audio_sec"] = round(time.monotonic() - started, 2)
                        if not ok:
                            row["failures"] += 1
                        row["per_sentence"].append({"chars": len(s), "sec": round(took, 2), "audio_sec": round(secs, 2), "ok": ok,
                                                    **({"error": err} if err else {})})
                        if args.save_audio and wav.size and rep == 0:
                            import soundfile as sf
                            os.makedirs(args.save_audio, exist_ok=True)
                            sf.write(os.path.join(args.save_audio, f"{item['id']}-{voice}-{i:02d}.wav"), wav, SR)
                    row["total_sec"] = round(time.monotonic() - started, 2)
                    row["audio_sec"] = round(audio_total, 2)
                    row["rtf"] = round(synth_total / audio_total, 3) if audio_total else None
                    row["failure_rate"] = round(row["failures"] / max(1, len(sents)), 2)
                    report["results"].append(row)
                    print(f"{item['id']} {voice} run {rep + 1}: first {row['first_audio_sec']}s total {row['total_sec']}s "
                          f"audio {row['audio_sec']}s rtf {row['rtf']} failures {row['failures']}", flush=True)

    report["peak_rss_mb"] = round(rss.peak / 2**20)
    json.dump(report, open(args.out, "w", encoding="utf-8"), indent=1, ensure_ascii=False)
    print("peak RSS MB", report["peak_rss_mb"])


if __name__ == "__main__":
    main()
