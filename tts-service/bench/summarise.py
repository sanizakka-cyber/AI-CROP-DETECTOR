"""Summarise benchmark JSON into one table (per language, per engine).

YarnGPT2 runs covered the first N sentences only, so its full-result time is
projected: real-time factor x the audio length of the whole text, estimated
from seconds of audio per character in the sentences that were run.
A sentence is excluded when the machine was asleep (wall time > 20x the
median for that language)."""
import json, statistics, sys

texts = {t["id"]: t["text"] for t in json.load(open("bench/texts.json", encoding="utf-8"))}
rows = []
for path in sys.argv[1:]:
    rep = json.load(open(path, encoding="utf-8"))
    engine = rep.get("engine", "YarnGPT2")
    for r in rep["results"]:
        ps = r["per_sentence"]
        med = statistics.median(p["sec"] for p in ps)
        good = [p for p in ps if p["sec"] <= 20 * med and p["ok"]]
        excluded = len(ps) - len(good)
        synth = sum(p["sec"] for p in good)
        audio = sum(p["audio_sec"] for p in good)
        rtf = synth / audio
        sec_per_char = audio / sum(p["chars"] for p in good)
        full_audio = sec_per_char * len(texts[r["id"]])
        rows.append({
            "engine": engine, "lang": r["lang"], "voice": r.get("voice"),
            "sentences_run": len(ps), "excluded": excluded,
            "first_audio_sec": round(statistics.median(p["sec"] for p in good), 1),
            "rtf": round(rtf, 2),
            "full_result_audio_sec": round(full_audio),
            "full_result_synth_sec": round(rtf * full_audio),
            "failures": sum(1 for p in ps if not p["ok"]),
            "peak_rss_mb": rep.get("peak_rss_mb"),
        })
print(json.dumps(rows, indent=1, ensure_ascii=False))
