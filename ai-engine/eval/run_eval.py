"""
MSAS FarmAI diagnosis accuracy evaluation.

Runs a labelled image set through the AI engine and reports, per scan
mode: accuracy on confident answers, how often the engine commits to an
answer, and whether the self-reported confidence means anything
(accuracy within each confidence band). That last table is what
calibration needs: until it exists, the percentage shown to farmers is
the model's own estimate, not a measured probability.

Sends images straight to the engine (not the Laravel API), so no farmer
records are created and no scan quota is used.

Manifest (CSV, UTF-8), one row per image, paths relative to the manifest:
    image,mode,expected,synonyms,expected_outcome
    maize/nclb_01.jpg,crop,Northern corn leaf blight,Exserohilum turcicum|turcicum leaf blight,
    blur/leaf_01.jpg,crop,,,poor_image
mode is crop|livestock|soil|pest. expected is the verified label (by an
agronomist or vet). synonyms are extra accepted names, "|"-separated.
expected_outcome (optional) checks the outcome instead of the label, for
images that SHOULD be rejected (poor_image, unsupported_subject).

Usage:
    set ENGINE_URL=https://msas-ai-engine.onrender.com
    set ENGINE_API_KEY=<the engine's API_KEY from Render>
    python eval/run_eval.py path/to/manifest.csv --out results.json
    python eval/run_eval.py path/to/manifest.csv --dry-run   # check the manifest only
"""
import argparse
import csv
import json
import os
import sys
import time
import urllib.error
import urllib.request
import uuid
from collections import defaultdict

MODES = {"crop", "livestock", "soil", "pest"}
OUTCOMES = {"assessed", "uncertain", "poor_image", "unsupported_subject"}
BANDS = [(0, 50), (50, 65), (65, 80), (80, 90), (90, 101)]


def load_manifest(path):
    base = os.path.dirname(os.path.abspath(path))
    rows, errors = [], []
    with open(path, encoding="utf-8-sig", newline="") as f:
        for n, r in enumerate(csv.DictReader(f), start=2):
            img = os.path.join(base, (r.get("image") or "").strip())
            mode = (r.get("mode") or "").strip().lower()
            expected = (r.get("expected") or "").strip()
            outcome = (r.get("expected_outcome") or "").strip().lower()
            if mode not in MODES:
                errors.append(f"line {n}: mode must be one of {sorted(MODES)}")
            if not os.path.isfile(img):
                errors.append(f"line {n}: image not found: {img}")
            if not expected and not outcome:
                errors.append(f"line {n}: give expected (a label) or expected_outcome")
            if outcome and outcome not in OUTCOMES:
                errors.append(f"line {n}: expected_outcome must be one of {sorted(OUTCOMES)}")
            names = [expected] + [s.strip() for s in (r.get("synonyms") or "").split("|") if s.strip()]
            rows.append({"line": n, "image": img, "mode": mode, "expected": expected,
                         "names": [x for x in names if x], "expected_outcome": outcome or None})
    return rows, errors


def label_matches(finding, names):
    """Accepted if any accepted name appears in the engine's finding (case-insensitive)."""
    f = (finding or "").lower()
    return any(n.lower() in f for n in names)


def call_engine(url, key, mode, image_path, timeout=180):
    boundary = uuid.uuid4().hex
    data = open(image_path, "rb").read()
    body = (f"--{boundary}\r\nContent-Disposition: form-data; name=\"images\"; filename=\"img.jpg\"\r\n"
            f"Content-Type: image/jpeg\r\n\r\n").encode() + data + f"\r\n--{boundary}--\r\n".encode()
    headers = {"Content-Type": f"multipart/form-data; boundary={boundary}", "Accept": "application/json"}
    if key:
        headers["Authorization"] = f"Bearer {key}"
    req = urllib.request.Request(f"{url.rstrip('/')}/predict/{mode}", data=body, headers=headers)
    t0 = time.time()
    try:
        with urllib.request.urlopen(req, timeout=timeout) as r:
            return r.status, json.load(r), time.time() - t0
    except urllib.error.HTTPError as e:
        try:
            payload = json.loads(e.read() or b"{}")
        except ValueError:
            payload = {}
        return e.code, payload, time.time() - t0


def score(rows, results):
    """Pure function: rows + engine results -> report dict (unit-tested)."""
    per_mode = defaultdict(lambda: {"images": 0, "errors": 0, "label_items": 0, "answered": 0, "correct": 0,
                                    "outcome_items": 0, "outcome_correct": 0, "bands": defaultdict(lambda: [0, 0]),
                                    "mistakes": []})
    for row, res in zip(rows, results):
        m = per_mode[row["mode"]]
        m["images"] += 1
        status, body = res["status"], res["body"] or {}
        if status != 200:
            m["errors"] += 1
            continue
        outcome = body.get("assessment_outcome")
        if row["expected_outcome"]:
            m["outcome_items"] += 1
            if outcome == row["expected_outcome"]:
                m["outcome_correct"] += 1
            else:
                m["mistakes"].append({"image": row["image"], "expected_outcome": row["expected_outcome"], "got": outcome})
            continue
        m["label_items"] += 1
        if outcome != "assessed":
            continue  # declined to answer: counted against coverage, not accuracy
        m["answered"] += 1
        finding = body.get("disease") or body.get("pest_name")
        ok = label_matches(finding, row["names"])
        m["correct"] += ok
        conf = body.get("confidence")
        if isinstance(conf, (int, float)):
            for lo, hi in BANDS:
                if lo <= conf < hi:
                    m["bands"][f"{lo}-{min(hi, 100)}"][0] += 1
                    m["bands"][f"{lo}-{min(hi, 100)}"][1] += ok
        if not ok:
            m["mistakes"].append({"image": row["image"], "expected": row["expected"], "got": finding, "confidence": conf})

    report = {}
    for mode, m in per_mode.items():
        report[mode] = {
            "images": m["images"],
            "engine_errors": m["errors"],
            "coverage": round(m["answered"] / m["label_items"], 3) if m["label_items"] else None,
            "accuracy_when_answered": round(m["correct"] / m["answered"], 3) if m["answered"] else None,
            "rejection_accuracy": round(m["outcome_correct"] / m["outcome_items"], 3) if m["outcome_items"] else None,
            "confidence_bands": {b: {"n": n, "accuracy": round(c / n, 3)} for b, (n, c) in sorted(m["bands"].items())},
            "mistakes": m["mistakes"],
        }
    return report


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("manifest")
    ap.add_argument("--out", default="eval_results.json")
    ap.add_argument("--dry-run", action="store_true")
    a = ap.parse_args(argv)

    rows, errors = load_manifest(a.manifest)
    if errors:
        print("Manifest problems:\n  " + "\n  ".join(errors))
        return 2
    print(f"{len(rows)} labelled images: " + ", ".join(f"{m}={sum(r['mode'] == m for r in rows)}" for m in sorted(MODES)))
    if a.dry_run:
        return 0

    url, key = os.environ.get("ENGINE_URL"), os.environ.get("ENGINE_API_KEY")
    if not url:
        print("Set ENGINE_URL (and ENGINE_API_KEY if the engine requires one).")
        return 2

    results = []
    for i, r in enumerate(rows, 1):
        status, body, secs = call_engine(url, key, r["mode"], r["image"])
        results.append({"status": status, "body": body, "seconds": round(secs, 1)})
        print(f"[{i}/{len(rows)}] {r['mode']:9} {status} {secs:5.1f}s {os.path.basename(r['image'])}: "
              f"{body.get('assessment_outcome')} / {body.get('disease') or body.get('pest_name')} / {body.get('confidence')}")

    report = score(rows, results)
    json.dump({"report": report, "raw": [{**r, **res} for r, res in zip(rows, results)]},
              open(a.out, "w", encoding="utf-8"), indent=1, default=str)
    print(json.dumps(report, indent=1, default=str))
    print(f"Saved {a.out}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
