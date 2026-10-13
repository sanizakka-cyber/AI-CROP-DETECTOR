"""Tests for eval/run_eval.py (the labelled-dataset accuracy runner)."""
import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "eval"))
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
