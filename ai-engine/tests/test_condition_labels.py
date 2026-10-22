"""
Condition labels (conditions.py) and the vote on them (consensus.py).

The banana case is the real scan that prompted this: three analyses, three
wordings of the same finding, and the old wording vote called it
inconclusive.
"""
import os
import sys

import pytest

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import conditions  # noqa: E402
from consensus import LABEL, NAME, combine  # noqa: E402
from diagnosis_contract import build_prompt, finalize, schema_for  # noqa: E402

BANANA_WORDINGS = [
    "Natural leaf sheath senescence",
    "Natural drying and senescence of old leaf sheaths",
    "Natural drying and shedding of old outer leaf sheaths",
]


def _reply(**overrides):
    base = {k: "" for k in schema_for("crop")["properties"]}
    base.update({
        "assessment_outcome": "assessed", "subject_present": True, "image_quality": "good",
        "image_issues": [], "photo_tips": [], "alternative_findings": [], "symptoms_observed": [],
        "immediate_actions": ["Remove the dry outer sheaths"], "subject_name": "Banana",
        "scientific_name": "Musa spp.", "detected_part": "stem (pseudostem)",
        "health_status": "Healthy (normal ageing)", "primary_finding": "Natural leaf sheath senescence",
        "condition_label": "normal_ageing", "farmer_name": "Old outer layers drying naturally",
        "confidence": 80, "severity": "none", "urgency": "low",
    })
    base.update(overrides)
    return base


def _banana_passes(labels=("normal_ageing",) * 3, confidences=(78, 82, 80)):
    return [finalize("crop", _reply(primary_finding=w, condition_label=l, confidence=c))
            for w, l, c in zip(BANANA_WORDINGS, labels, confidences)]


# ── The vote ─────────────────────────────────────────────────────────────────

def test_three_wordings_of_one_condition_are_one_vote():
    out = combine(_banana_passes())
    assert out["assessment_outcome"] == "assessed"
    assert out["condition_label"] == "normal_ageing"
    assert out["condition_category"] == "normal_ageing"
    assert out["consensus"]["agreeing_passes"] == 3
    assert out["consensus"]["pass_labels"] == ["normal_ageing"] * 3
    assert out["confidence"] == 80
    assert out["alternative_findings"] == []  # the other two wordings are not "other possibilities"


def test_the_old_wording_vote_called_the_same_passes_inconclusive():
    out = combine(_banana_passes(), vote_on=NAME)
    assert out["assessment_outcome"] == "uncertain"
    assert out["consensus"]["agreeing_passes"] == 1


def test_three_different_conditions_are_inconclusive_and_unrated():
    out = combine(_banana_passes(labels=("normal_ageing", "banana_black_sigatoka", "banana_fusarium_wilt")))
    assert out["assessment_outcome"] == "uncertain"
    assert out["confidence"] is None
    # Nothing from one pass's answer survives: no severity, urgency, label, plain name or health line.
    for key in ("severity", "urgency", "condition_label", "condition_category", "farmer_name", "health_status"):
        assert out[key] is None, key
    assert len(out["alternative_findings"]) == 3


def test_majority_label_wins_over_one_outlier():
    out = combine(_banana_passes(labels=("normal_ageing", "banana_black_sigatoka", "normal_ageing")))
    assert out["assessment_outcome"] == "assessed"
    assert out["condition_label"] == "normal_ageing"
    assert out["consensus"]["agreeing_passes"] == 2
    assert [a["condition_label"] for a in out["alternative_findings"]] == ["banana_black_sigatoka"]


def test_unknown_labels_fall_back_to_wording():
    # An older engine or an off-list label: same rule as before.
    same = [finalize("crop", _reply(primary_finding=n, condition_label="unknown", confidence=70))
            for n in ("Late blight", "Tomato late blight", "Late blight")]
    assert combine(same)["assessment_outcome"] == "assessed"
    different = [finalize("crop", _reply(primary_finding=n, condition_label="", confidence=70))
                 for n in ("Late blight", "Early blight", "Septoria leaf spot")]
    assert combine(different)["assessment_outcome"] == "uncertain"


def test_inconclusive_by_majority_uncertain_passes_has_no_severity_or_urgency():
    passes = [finalize("crop", _reply(assessment_outcome=o)) for o in ("uncertain", "uncertain", "assessed")]
    out = combine(passes)
    assert out["assessment_outcome"] == "uncertain"
    assert out["severity"] is None and out["urgency"] is None


def test_alternatives_are_one_entry_per_condition():
    alt = {"name": "Black sigatoka", "farmer_name": "Black leaf streak", "condition_label": "banana_black_sigatoka",
           "why_considered": "Dark streaks"}
    alt_again = {**alt, "name": "Black leaf streak disease (Pseudocercospora fijiensis)"}
    passes = [finalize("crop", _reply(alternative_findings=[a])) for a in (alt, alt_again, alt)]
    out = combine(passes)
    assert [a["condition_label"] for a in out["alternative_findings"]] == ["banana_black_sigatoka"]
    assert out["alternative_findings"][0]["farmer_name"] == "Black leaf streak"


# ── Labels and the contract ──────────────────────────────────────────────────

@pytest.mark.parametrize("raw, expected", [
    ("normal_ageing", "normal_ageing"), ("Normal Ageing", "normal_ageing"), ("normal-ageing", "normal_ageing"),
    ("made_up_disease", "unknown"), ("", None), (None, None), (5, None),
])
def test_labels_are_checked_against_the_fixed_list(raw, expected):
    assert conditions.normalize("crop", raw) == expected


@pytest.mark.parametrize("mode", ["crop", "livestock", "soil", "pest"])
def test_every_mode_has_unknown_and_a_category_for_every_label(mode):
    labels = conditions.labels_for(mode)
    assert "unknown" in labels
    assert len(labels) == len(set(labels))
    for label in labels:
        assert conditions.category(mode, label) in conditions.CATEGORIES
        assert conditions.plain_name(mode, label)


def test_crop_and_livestock_lists_include_healthy_and_normal_states():
    assert {"healthy", "normal_ageing"} <= set(conditions.labels_for("crop"))
    assert {"healthy", "normal_variation"} <= set(conditions.labels_for("livestock"))


@pytest.mark.parametrize("mode", ["crop", "livestock", "soil", "pest"])
def test_prompt_lists_every_label_and_the_plain_words_rule(mode):
    prompt = build_prompt(mode)
    for label in conditions.labels_for(mode):
        assert f"  {label}:" in prompt
    assert "condition_label is exactly one label" in prompt
    assert 'not "pseudostem"' in prompt and 'not "senescence"' in prompt


def test_finalize_maps_the_label_category_and_plain_name():
    out = finalize("crop", _reply())
    assert out["condition_label"] == "normal_ageing"
    assert out["condition_category"] == "normal_ageing"
    assert out["farmer_name"] == "Old outer layers drying naturally"
    assert out["contract_version"] == "2026-10-condition-labels"


@pytest.mark.parametrize("outcome", ["poor_image", "unsupported_subject"])
def test_no_finding_outcomes_carry_no_label(outcome):
    out = finalize("crop", _reply(assessment_outcome=outcome))
    assert out["condition_label"] is None and out["condition_category"] is None and out["farmer_name"] is None


def test_schema_adds_label_fields_without_unions():
    props = schema_for("crop")["properties"]
    assert props["condition_label"] == {"type": "string"}
    item = props["alternative_findings"]["items"]
    assert set(item["required"]) == {"name", "farmer_name", "condition_label", "why_considered"}
