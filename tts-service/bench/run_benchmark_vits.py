"""
Benchmark the OpenBible VITS voices (multilingual-tts/VITS-OpenBible-<Lang>,
CC-BY-SA-4.0, Coqui TTS) the same way as run_benchmark.py: one call per
sentence, time to first audio, total time, real-time factor, peak memory,
failures.

Usage (from tts-service/, in an environment with coqui-tts):
  python bench/run_benchmark_vits.py --texts bench/texts.json --models C:/pyvits/models \
      --threads 2 --out bench/results-vits-laptop-2threads.json [--save-audio DIR]

Loading follows the model card: Synthesizer(checkpoint, config, speakers file),
restoring the SpeakerManager if Coqui does not.
"""
import argparse
import json
import os
import statistics
import sys
import time

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import numpy as np  # noqa: E402
import psutil  # noqa: E402

from run_benchmark import PeakRss, plausible, sentences  # noqa: E402

FOLDERS = {"ha": "Hausa", "yo": "Yoruba", "ig": "Igbo"}


def load(folder):
    from TTS.tts.utils.speakers import SpeakerManager
    from TTS.utils.synthesizer import Synthesizer
    syn = Synthesizer(tts_checkpoint=os.path.join(folder, "model_last.pth"),
                      tts_config_path=os.path.join(folder, "config.json"),
                      tts_speakers_file=os.path.join(folder, "speakers.pth"), use_cuda=False)
    # The card says "multispeaker", but these checkpoints have no speaker
    # embedding (config: use_speaker_embedding=false, no emb_g weights), so
    # there is one voice and Coqui must be called without a speaker name.
    if not syn.tts_config.model_args.get("use_speaker_embedding", False):
        syn.tts_model.speaker_manager = None
    elif syn.tts_model.speaker_manager is None:
        syn.tts_model.speaker_manager = SpeakerManager(speaker_id_file_path=os.path.join(folder, "speakers.pth"))
    return syn


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--texts", required=True)
    ap.add_argument("--models", required=True)
    ap.add_argument("--threads", type=int, default=2)
    ap.add_argument("--out", required=True)
    ap.add_argument("--save-audio", default="")
    args = ap.parse_args()

    import torch
    torch.set_num_threads(args.threads)
    texts = [t for t in json.load(open(args.texts, encoding="utf-8")) if t["lang"] in FOLDERS]
    report = {"engine": "VITS-OpenBible (Coqui TTS)", "threads": args.threads, "cpu": os.environ.get("BENCH_CPU", ""), "results": []}

    with PeakRss() as rss:
        for item in texts:
            lang = item["lang"]
            t0 = time.monotonic()
            syn = load(os.path.join(args.models, FOLDERS[lang]))
            sm = syn.tts_model.speaker_manager
            speakers = sorted(sm.speaker_names) if sm else []
            sr = syn.output_sample_rate
            row = {"id": item["id"], "lang": lang, "load_sec": round(time.monotonic() - t0, 1), "speakers": speakers,
                   "voice": speakers[0] if speakers else None, "per_sentence": [], "failures": 0}
            sents = sentences(item["text"])
            row["sentences"] = len(sents)
            started = time.monotonic()
            synth_total = audio_total = 0.0
            for i, s in enumerate(sents):
                t = time.monotonic()
                try:
                    wav = np.asarray(syn.tts(text=s, speaker_name=row["voice"], split_sentences=False), dtype=np.float32)
                    secs = len(wav) / sr
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
                row["per_sentence"].append({"chars": len(s), "sec": round(took, 2), "audio_sec": round(secs, 2), "ok": ok, **({"error": err} if err else {})})
                if args.save_audio and wav.size:
                    import soundfile as sf
                    os.makedirs(args.save_audio, exist_ok=True)
                    sf.write(os.path.join(args.save_audio, f"{item['id']}-vits-{i:02d}.wav"), wav, sr)
            row["total_sec"] = round(time.monotonic() - started, 2)
            row["audio_sec"] = round(audio_total, 2)
            row["rtf"] = round(synth_total / audio_total, 3) if audio_total else None
            row["failure_rate"] = round(row["failures"] / max(1, len(sents)), 2)
            report["results"].append(row)
            print(f"{item['id']} {row['voice']}: first {row['first_audio_sec']}s total {row['total_sec']}s audio {row['audio_sec']}s rtf {row['rtf']} failures {row['failures']}", flush=True)
            del syn

    report["peak_rss_mb"] = round(rss.peak / 2**20)
    json.dump(report, open(args.out, "w", encoding="utf-8"), indent=1, ensure_ascii=False)
    print("peak RSS MB", report["peak_rss_mb"])


if __name__ == "__main__":
    main()
