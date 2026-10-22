"""Tests for eval/run_eval.py (the labelled-dataset accuracy runner)."""
import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "eval"))
import run_eval  # noqa: E402
from run_eval import label_matches, load_manifest, score  # noqa: E402


def _row(mode="crop", expected="Late blight", names=None, outcome=None):
    return {"line": 2, "image": "x.jpg", "mode": mode, "expected": expected,
            "names": names or ([expected] if expected else []), "expected_outcome": outcome}


def _res(outcome="assessed", disease="Late blight (Phytophthora infestans)", conf=80, status=200):
    return {"status": status, "body": {"assessment_outcome": outcome, "disease": disease, "confidence": conf}}


def test_label_match_accepts_synonyms_case_insensitively():
    assert label_matches("Northern Corn Leaf Blight (Exserohilum turcicum)", ["northern corn leaf blight"])
    assert label_matches("Turcicum leaf blight", ["NCLB", "turcicum"])
    assert not label_matches("Early blight", ["Late blight"])
    assert not label_matches(None, ["Late blight"])


def test_accuracy_counts_only_answered_items_and_coverage_counts_declines():
    rows = [_row(), _row(), _row(), _row()]
    results = [_res(), _res(disease="Early blight"), _res(outcome="uncertain"), _res(status=503)]
    m = score(rows, results)["crop"]
    assert m["images"] == 4 and m["engine_errors"] == 1
    assert m["coverage"] == round(2 / 3, 3)          # 2 of 3 non-error label items answered
    assert m["accuracy_when_answered"] == 0.5
    assert m["mistakes"][0]["got"] == "Early blight"


def test_confidence_bands_show_accuracy_per_band():
    rows = [_row()] * 3
    results = [_res(conf=95), _res(conf=92, disease="Early blight"), _res(conf=55)]
    bands = score(rows, results)["crop"]["confidence_bands"]
    assert bands["90-100"] == {"n": 2, "accuracy": 0.5}
    assert bands["50-65"] == {"n": 1, "accuracy": 1.0}


def test_expected_outcome_rows_check_rejections_not_labels():
    rows = [_row(expected="", outcome="poor_image"), _row(expected="", outcome="unsupported_subject")]
    results = [_res(outcome="poor_image", disease=None, conf=None), _res(outcome="assessed")]
    m = score(rows, results)["crop"]
    assert m["rejection_accuracy"] == 0.5
    assert m["accuracy_when_answered"] is None


def test_manifest_validation_reports_problems(tmp_path):
    (tmp_path / "a.jpg").write_bytes(b"x")
    (tmp_path / "m.csv").write_text(
        "image,mode,expected,synonyms,expected_outcome\n"
        "a.jpg,crop,Late blight,Phytophthora,\n"
        "missing.jpg,crop,Late blight,,\n"
        "a.jpg,fish,Late blight,,\n"
        "a.jpg,crop,,,\n", encoding="utf-8")
    rows, errors = load_manifest(str(tmp_path / "m.csv"))
    assert rows[0]["names"] == ["Late blight", "Phytophthora"]
    assert any("image not found" in e for e in errors)
    assert any("mode must be" in e for e in errors)
    assert any("give expected" in e for e in errors)


# ── Phase 12.1: per-condition accuracy, consistency across runs, before/after ──
from run_eval import compare, consistency  # noqa: E402


def _lv(expected, names=None):
    return _row(mode="livestock", expected=expected, names=names or [expected])


def test_accuracy_is_reported_per_condition_with_confusions_and_not_certain_counts():
    rows = [_lv("Lumpy skin disease", ["lumpy skin", "LSD"])] * 3 + [_lv("Ringworm", ["ringworm", "dermatophytosis"])] * 2
    results = [
        _res(disease="Lumpy skin disease", conf=82),
        _res(disease="Ringworm (dermatophytosis)", conf=55),   # the miss seen live on 2026-10-13
        _res(disease="Mange", conf=60),
        _res(disease="Ringworm", conf=70),
        _res(disease="Ringworm", conf=50),
    ]
    c = score(rows, results)["livestock"]["conditions"]
    assert c["Lumpy skin disease"]["n"] == 3 and c["Lumpy skin disease"]["accuracy"] == round(1 / 3, 3)
    assert c["Lumpy skin disease"]["shown_as_not_certain"] == 2   # both misses were under 65%
    assert ("Ringworm (dermatophytosis)", 1) in c["Lumpy skin disease"]["top_confusions"]
    assert c["Ringworm"]["accuracy"] == 1.0 and c["Ringworm"]["shown_as_not_certain"] == 1


def test_consistency_measures_whether_the_same_photo_gets_the_same_answer():
    rows = [_row(expected="Late blight"), _row(expected="Early blight")]
    run1 = [_res(disease="Late blight", conf=78), _res(disease="Early blight", conf=70)]
    run2 = [_res(disease="Early blight", conf=68), _res(disease="Early blight", conf=72)]
    cons = consistency(rows, [run1, run2])
    assert cons["Late blight"] == {"images": 1, "same_finding_rate": 0.0, "mean_confidence_spread": 10.0}
    assert cons["Early blight"]["same_finding_rate"] == 1.0


def test_compare_prints_per_condition_change():
    before = {"livestock": {"conditions": {"Lumpy skin disease": {"accuracy": 0.4}}}}
    after = {"livestock": {"conditions": {"Lumpy skin disease": {"accuracy": 0.65}}}}
    out = compare(before, after)
    assert "livestock/Lumpy skin disease" in out and "+0.250" in out


# ── Phase 12.2: per-photo spread, the farmer's screen, before/after ──────────
from run_eval import compare_photos, per_photo, photo_table  # noqa: E402


def test_per_photo_reports_spread_and_whether_the_farmer_saw_the_same_screen():
    rows = [_row(expected="Late blight")]
    runs = [[_res(disease="Late blight", conf=c)] for c in (65, 78, 70, 64, 72)]
    p = per_photo(rows, runs, min_confidence=65, confirm_below=75)[0]
    assert p["same_top_finding"] is True
    assert p["spread"] == 14
    assert p["shown_as"] == ["likely_confirm", "diagnosis", "likely_confirm", "not_certain", "likely_confirm"]
    assert p["same_screen"] is False
    assert "FAIL" in photo_table([p]) and "0/1 photos meet the target" in photo_table([p])


def test_compare_photos_shows_before_and_after_per_photo():
    before = [{"image": "a.jpg", "same_top_finding": True, "spread": 13.0}]
    after = [{"image": "a.jpg", "same_top_finding": True, "spread": 2.0}]
    out = compare_photos(before, after)
    assert "True / 13.0" in out and "True / 2.0" in out
    assert "after: 1/1 photos meet the target" in out and "before: 0/1" in out


def test_unlabelled_photos_are_allowed_for_consistency_runs(tmp_path):
    (tmp_path / "a.jpg").write_bytes(b"x")
    (tmp_path / "m.csv").write_text("image,mode,expected,synonyms,expected_outcome\na.jpg,crop,,,\n", encoding="utf-8")
    _, errors = load_manifest(str(tmp_path / "m.csv"), labels_required=False)
    assert errors == []


def _labelled_response(outcomes, findings, labels, confs):
    return {"assessment_outcome": "x", "consensus": {"pass_outcomes": outcomes, "pass_findings": findings,
                                                     "pass_labels": labels, "pass_confidences": confs}}


def test_vote_change_replays_both_votes_on_the_same_passes():
    banana = _labelled_response(["assessed"] * 3,
                                ["Natural leaf sheath senescence", "Natural drying and senescence of old leaf sheaths",
                                 "Natural drying and shedding of old outer leaf sheaths"],
                                ["normal_ageing"] * 3, [78, 82, 80])
    split = _labelled_response(["assessed"] * 3, ["Late blight", "Early blight", "Septoria leaf spot"],
                               ["tomato_late_blight", "tomato_early_blight", "tomato_septoria_leaf_spot"], [70, 70, 70])
    rows = [{"image": "a/banana.jpg", "expected": "Normal ageing", "names": ["natural", "ageing"]},
            {"image": "a/tomato.jpg", "expected": "", "names": []}]
    runs = [[{"status": 200, "body": banana}, {"status": 200, "body": split}]]
    v = run_eval.vote_change(rows, runs)
    assert v["results"] == 2
    assert v["inconclusive_before"] == 2 and v["inconclusive_after"] == 1
    assert v["inconclusive_to_clear"] == 1 and v["clear_to_inconclusive"] == 0
    assert v["changed_and_labelled_correct"] == 1
    assert v["changes"][0]["image"] == "banana.jpg" and v["changes"][0]["new_label"] == "normal_ageing"
    assert "Inconclusive -> clear finding: 1" in run_eval.vote_table(v)


def test_vote_change_skips_responses_without_per_pass_labels():
    old_engine = {"assessment_outcome": "assessed", "consensus": {"pass_findings": ["Late blight"] * 3}}
    v = run_eval.vote_change([{"image": "x.jpg", "expected": "", "names": []}], [[{"status": 200, "body": old_engine}]])
    assert v["results"] == 0
