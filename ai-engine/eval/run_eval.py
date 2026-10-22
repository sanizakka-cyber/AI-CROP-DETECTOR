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

Consistency (Phase 12.2): run every photo 5 times and print each photo's
findings, confidences and spread; the target is the same top finding every
run and a spread of 3 points or less. Unlabelled photos are allowed with
--consistency-only. Compare two saved runs (e.g. SCAN_PASSES=1 vs 3):
    python eval/run_eval.py manifest.csv --repeats 5 --consistency-only --out before.json
    python eval/run_eval.py --compare before.json after.json

Condition-label vote: every response from an engine with condition labels
carries each pass's outcome, label, wording and confidence
(consensus.pass_*). The runner replays both votes on those same passes, the
old one on wording and the new one on labels, so the difference is the vote
alone, not model randomness. It prints how many results move from
inconclusive to a clear finding (and the reverse). A saved run can be
re-scored without calling the engine:
    python eval/run_eval.py --vote-compare results.json
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
from collections import Counter, defaultdict

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from consensus import LABEL, NAME, combine  # noqa: E402

MODES = {"crop", "livestock", "soil", "pest"}
OUTCOMES = {"assessed", "uncertain", "poor_image", "unsupported_subject"}
BANDS = [(0, 50), (50, 65), (65, 80), (80, 90), (90, 101)]


def load_manifest(path, labels_required=True):
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
            if labels_required and not expected and not outcome:
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


def score(rows, results, min_confidence=65):
    """Pure function: rows + engine results -> report dict (unit-tested)."""
    per_mode = defaultdict(lambda: {"images": 0, "errors": 0, "label_items": 0, "answered": 0, "correct": 0,
                                    "outcome_items": 0, "outcome_correct": 0, "bands": defaultdict(lambda: [0, 0]),
                                    "mistakes": [], "conditions": defaultdict(lambda: {
                                        "n": 0, "answered": 0, "correct": 0, "not_certain": 0, "confusions": Counter()})})
    for row, res in zip(rows, results):
        m = per_mode[row["mode"]]
        c = m["conditions"][row["expected"] or f"outcome:{row['expected_outcome']}"]
        c["n"] += 1
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
        c["answered"] += 1
        finding = body.get("disease") or body.get("pest_name")
        ok = label_matches(finding, row["names"])
        m["correct"] += ok
        c["correct"] += ok
        conf = body.get("confidence")
        if isinstance(conf, (int, float)) and conf < min_confidence:
            c["not_certain"] += 1  # the app/web would show "Not certain", not this finding
        if not ok:
            c["confusions"][(finding or "(none)").strip()] += 1
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
            "conditions": {
                name: {
                    "n": c["n"],
                    "accuracy": round(c["correct"] / c["n"], 3) if c["n"] else None,
                    "accuracy_when_answered": round(c["correct"] / c["answered"], 3) if c["answered"] else None,
                    "shown_as_not_certain": c["not_certain"],
                    "top_confusions": c["confusions"].most_common(3),
                } for name, c in sorted(m["conditions"].items())
            },
        }
    return report


def consistency(rows, runs):
    """Same photo, several runs: how often does the finding stay the same? (pure, unit-tested)"""
    out = defaultdict(lambda: {"images": 0, "same_finding": 0, "confidence_spread": []})
    for i, row in enumerate(rows):
        bodies = [r[i]["body"] or {} for r in runs if r[i]["status"] == 200]
        if len(bodies) < 2:
            continue
        o = out[row["expected"] or row["mode"]]
        o["images"] += 1
        findings = {(b.get("disease") or b.get("pest_name") or b.get("assessment_outcome") or "").strip().lower() for b in bodies}
        o["same_finding"] += len(findings) == 1
        confs = [b.get("confidence") for b in bodies if isinstance(b.get("confidence"), (int, float))]
        if len(confs) >= 2:
            o["confidence_spread"].append(max(confs) - min(confs))
    return {k: {"images": v["images"], "same_finding_rate": round(v["same_finding"] / v["images"], 3) if v["images"] else None,
                "mean_confidence_spread": round(sum(v["confidence_spread"]) / len(v["confidence_spread"]), 1) if v["confidence_spread"] else None}
            for k, v in sorted(out.items())}


def _top(body):
    return (body.get("disease") or body.get("pest_name") or body.get("assessment_outcome") or "").strip()


def _shown_as(body, min_confidence, confirm_below):
    """What the farmer sees for this result (same bands as Laravel's Diagnosis model)."""
    conf = body.get("confidence")
    if body.get("assessment_outcome") != "assessed" or not isinstance(conf, (int, float)):
        return body.get("assessment_outcome") or "error"
    if conf < min_confidence:
        return "not_certain"
    return "likely_confirm" if conf < confirm_below else "diagnosis"


def per_photo(rows, runs, min_confidence=65, confirm_below=75):
    """Pure: one line per photo with every run's finding and confidence, the
    spread, and whether the farmer would have seen the same thing each time."""
    out = []
    for i, row in enumerate(rows):
        bodies = [r[i]["body"] or {} for r in runs if r[i]["status"] == 200]
        tops = [_top(b) for b in bodies]
        confs = [b.get("confidence") for b in bodies]
        nums = [c for c in confs if isinstance(c, (int, float))]
        shown = [_shown_as(b, min_confidence, confirm_below) for b in bodies]
        out.append({
            "image": os.path.basename(row["image"]), "mode": row["mode"], "expected": row["expected"] or None,
            "runs": len(bodies), "errors": len(runs) - len(bodies),
            "findings": tops, "confidences": confs,
            "same_top_finding": len({t.lower() for t in tops}) == 1 if tops else None,
            "spread": round(max(nums) - min(nums), 1) if len(nums) >= 2 else (0.0 if nums else None),
            "shown_as": shown, "same_screen": len(set(shown)) == 1 if shown else None,
        })
    return out


def photo_table(photos, target_spread=3):
    lines = [f"{'photo':34} {'runs':>4} {'same top':>8} {'spread':>6} {'same screen':>11}  findings / confidences"]
    for p in photos:
        ok = p["same_top_finding"] and p["spread"] is not None and p["spread"] <= target_spread
        lines.append(f"{p['image'][:34]:34} {p['runs']:>4} {str(p['same_top_finding']):>8} {str(p['spread']):>6} "
                     f"{str(p['same_screen']):>11}  {'PASS' if ok else 'FAIL'}  "
                     + "; ".join(f"{f} {c}" for f, c in zip(p["findings"], p["confidences"])))
    n = len(photos)
    passed = sum(1 for p in photos if p["same_top_finding"] and p["spread"] is not None and p["spread"] <= target_spread)
    lines.append(f"{passed}/{n} photos meet the target (same top finding every run, spread <= {target_spread} points)")
    return "\n".join(lines)


def compare_photos(before, after, target_spread=3):
    """Per-photo spread before vs after (two saved runs' per_photo lists)."""
    a_by = {p["image"]: p for p in after}
    lines = [f"{'photo':34} {'before same/spread':>19} {'after same/spread':>18}"]
    for b in before:
        a = a_by.get(b["image"], {})
        lines.append(f"{b['image'][:34]:34} {str(b['same_top_finding']) + ' / ' + str(b['spread']):>19} "
                     f"{str(a.get('same_top_finding')) + ' / ' + str(a.get('spread')):>18}")
    for label, ps in (("before", before), ("after", after)):
        spreads = [p["spread"] for p in ps if p["spread"] is not None]
        passed = sum(1 for p in ps if p["same_top_finding"] and p["spread"] is not None and p["spread"] <= target_spread)
        lines.append(f"{label}: {passed}/{len(ps)} photos meet the target; max spread "
                     f"{max(spreads) if spreads else '-'}, mean {round(sum(spreads) / len(spreads), 1) if spreads else '-'}")
    return "\n".join(lines)


def _replayed(body, vote_on):
    """Combine the response's own passes again with the given vote rule."""
    c = body.get("consensus") or {}
    outs, labels = c.get("pass_outcomes"), c.get("pass_labels")
    finds, confs = c.get("pass_findings") or [], c.get("pass_confidences") or []
    if not outs or labels is None or len(outs) < 2:
        return None
    passes = [{"assessment_outcome": o, "disease": f if o in ("assessed", "uncertain") else None,
               "condition_label": l, "confidence": cf, "alternative_findings": []}
              for o, f, l, cf in zip(outs, finds, labels, confs)]
    return combine(passes, vote_on=vote_on)


CLEAR = ("diagnosis", "likely_confirm")


def vote_change(rows, runs, min_confidence=65, confirm_below=75):
    """Pure: per photo and run, the screen under the old (wording) and new
    (label) vote on identical passes, and the counts that matter."""
    items = []
    for run in runs:
        for row, res in zip(rows, run):
            body = res.get("body") or {}
            if res.get("status") != 200:
                continue
            old, new = _replayed(body, NAME), _replayed(body, LABEL)
            if old is None:
                continue
            o, n = _shown_as(old, min_confidence, confirm_below), _shown_as(new, min_confidence, confirm_below)
            correct = label_matches(_top(new), row.get("names") or []) if row.get("expected") and n in CLEAR else None
            items.append({"image": os.path.basename(row["image"]), "expected": row.get("expected") or None,
                          "old": o, "new": n, "old_finding": _top(old), "new_finding": _top(new),
                          "new_label": new.get("condition_label"), "new_correct": correct})
    to_clear = [i for i in items if i["old"] == "uncertain" and i["new"] in CLEAR]
    to_unclear = [i for i in items if i["old"] in CLEAR and i["new"] == "uncertain"]
    return {"results": len(items),
            "inconclusive_before": sum(i["old"] == "uncertain" for i in items),
            "inconclusive_after": sum(i["new"] == "uncertain" for i in items),
            "inconclusive_to_clear": len(to_clear),
            "clear_to_inconclusive": len(to_unclear),
            "changed_and_labelled_correct": sum(i["new_correct"] is True for i in to_clear),
            "changed_and_labelled_wrong": sum(i["new_correct"] is False for i in to_clear),
            "changes": to_clear + to_unclear}


def vote_table(v):
    lines = [f"Results scored: {v['results']} (photos x runs, engine responses with per-pass labels)",
             f"Inconclusive with the old wording vote: {v['inconclusive_before']}",
             f"Inconclusive with the label vote:      {v['inconclusive_after']}",
             f"Inconclusive -> clear finding: {v['inconclusive_to_clear']} "
             f"(of the labelled ones: {v['changed_and_labelled_correct']} correct, {v['changed_and_labelled_wrong']} wrong)",
             f"Clear finding -> inconclusive: {v['clear_to_inconclusive']}"]
    for c in v["changes"]:
        lines.append(f"  {c['image'][:34]:34} {c['old']:>14} -> {c['new']:<14} {c['new_finding']} [{c['new_label']}]"
                     + (f"  expected {c['expected']}: {'ok' if c['new_correct'] else 'WRONG'}" if c["new_correct"] is not None else ""))
    return "\n".join(lines)


def compare(before, after):
    """Per-condition accuracy before vs after a change (two saved reports)."""
    lines = [f"{'mode/condition':45} {'before':>8} {'after':>8} {'change':>8}"]
    for mode in sorted(set(before) | set(after)):
        conds = set(before.get(mode, {}).get("conditions", {})) | set(after.get(mode, {}).get("conditions", {}))
        for c in sorted(conds):
            b = before.get(mode, {}).get("conditions", {}).get(c, {}).get("accuracy")
            a = after.get(mode, {}).get("conditions", {}).get(c, {}).get("accuracy")
            d = f"{a - b:+.3f}" if a is not None and b is not None else "n/a"
            lines.append(f"{(mode + '/' + c)[:45]:45} {b if b is not None else '-':>8} {a if a is not None else '-':>8} {d:>8}")
    return "\n".join(lines)


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("manifest", nargs="?")
    ap.add_argument("--out", default="eval_results.json")
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--repeats", type=int, default=1, help="run each image N times and report consistency")
    ap.add_argument("--min-confidence", type=float, default=65, help="the app's Not-certain threshold")
    ap.add_argument("--confirm-below", type=float, default=75, help="the app's 'Likely, please confirm' band upper edge")
    ap.add_argument("--target-spread", type=float, default=3, help="consistency target, in confidence points")
    ap.add_argument("--consistency-only", action="store_true",
                    help="allow photos without a label (consistency needs no label; accuracy is skipped for them)")
    ap.add_argument("--compare", nargs=2, metavar=("BEFORE_JSON", "AFTER_JSON"), help="compare two saved runs and exit")
    ap.add_argument("--vote-compare", metavar="RESULTS_JSON", help="re-score a saved run under both votes and exit")
    a = ap.parse_args(argv)

    if a.vote_compare:
        saved = json.load(open(a.vote_compare, encoding="utf-8"))
        raw = saved.get("raw") or []
        rows = [{k: r.get(k) for k in ("image", "mode", "expected", "names")} for r in (raw[0] if raw else [])]
        print(vote_table(vote_change(rows, raw, a.min_confidence, a.confirm_below)))
        return 0

    if a.compare:
        before, after = (json.load(open(f, encoding="utf-8")) for f in a.compare)
        print(compare(before["report"], after["report"]))
        if before.get("per_photo") and after.get("per_photo"):
            print()
            print(compare_photos(before["per_photo"], after["per_photo"], a.target_spread))
        return 0
    if not a.manifest:
        ap.error("manifest is required (except with --compare)")

    rows, errors = load_manifest(a.manifest, labels_required=not a.consistency_only)
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

    runs = []
    for rep in range(max(1, a.repeats)):
        results = []
        for i, r in enumerate(rows, 1):
            status, body, secs = call_engine(url, key, r["mode"], r["image"])
            results.append({"status": status, "body": body, "seconds": round(secs, 1)})
            print(f"[run {rep + 1}, {i}/{len(rows)}] {r['mode']:9} {status} {secs:5.1f}s {os.path.basename(r['image'])}: "
                  f"{body.get('assessment_outcome')} / {body.get('disease') or body.get('pest_name')} / {body.get('confidence')}")
        runs.append(results)

    labelled = [i for i, r in enumerate(rows) if r["expected"] or r["expected_outcome"]]
    report = score([rows[i] for i in labelled], [runs[0][i] for i in labelled], a.min_confidence)
    cons = consistency(rows, runs) if len(runs) > 1 else None
    photos = per_photo(rows, runs, a.min_confidence, a.confirm_below) if len(runs) > 1 else None
    votes = vote_change(rows, runs, a.min_confidence, a.confirm_below)
    json.dump({"report": report, "consistency": cons, "per_photo": photos, "vote_change": votes,
               "raw": [[{**r, **res} for r, res in zip(rows, run)] for run in runs]},
              open(a.out, "w", encoding="utf-8"), indent=1, default=str)
    print(json.dumps({"report": report, "consistency": cons}, indent=1, default=str))
    if photos:
        print(photo_table(photos, a.target_spread))
    if votes["results"]:
        print(vote_table(votes))
    print(f"Saved {a.out}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
