"""
Repeat-pass consensus for scan diagnoses (Phase 12.2).

Why. The same photo scored 65-78% across runs, so one photo could flip
between a diagnosis and "Not certain" (threshold 65%). Nothing in this
service is random: images are oriented and re-encoded deterministically,
and there is no test-time augmentation. The variation is the model's own
sampling. The scan model (claude-sonnet-5) rejects temperature/top_p/top_k
with HTTP 400, and the API has no seed, so a single call cannot be made
deterministic from here.

What this does instead. The engine asks the same question N times in
parallel (SCAN_PASSES, default 3) and combines the finalized results:

- The outcome and the top finding are decided by vote. Two names count as
  the same finding when they match after normalisation or one contains the
  other ("Late blight" / "Tomato late blight").
- The reported confidence is the median confidence of the passes that
  agree with the winning finding, so one outlier pass cannot move it.
- The text fields come from one real pass (the agreeing pass whose
  confidence is closest to the median), never stitched from several
  replies, so explanation and treatment stay coherent.
- If no finding has a majority (for example three passes, three different
  answers), the result is "uncertain" with every candidate listed, and no
  treatment. That is the honest answer when the model disagrees with itself.

Everything here is pure (no I/O) and unit-tested.
"""
import re
import statistics
from typing import List, Optional

ASSESSED, UNCERTAIN = "assessed", "uncertain"
NO_FINDING = ("poor_image", "unsupported_subject")
# Keys that only make sense for a supported assessment (see finalize()).
TREATMENT_KEYS = ("medication", "organic_treatment", "preventive_measures", "recovery_period",
                  "fertilizer_recommendation", "amendment_recommendation", "chemical_control",
                  "biological_control", "cultural_control", "economic_threshold")

_WORD = re.compile(r"[a-z0-9]+")
# Words that do not distinguish one finding from another.
_STOP = {"the", "a", "an", "of", "on", "in", "disease", "infection", "likely", "probable", "possible", "suspected"}


def _norm(name: Optional[str]) -> str:
    if not name:
        return ""
    name = re.sub(r"\(.*?\)", " ", name.lower())
    return " ".join(w for w in _WORD.findall(name) if w not in _STOP)


def _finding(r: dict) -> Optional[str]:
    return r.get("disease") or r.get("pest_name")


def same_finding(a: dict, b: dict) -> bool:
    # scientific_name is the subject's (the crop's or animal's), not the
    # finding's, so it cannot tell two findings apart and is not used here.
    fa, fb = _norm(_finding(a)), _norm(_finding(b))
    return bool(fa and fb and (fa == fb or f" {fa} " in f" {fb} " or f" {fb} " in f" {fa} "))


def _groups(results: List[dict]) -> List[List[dict]]:
    groups: List[List[dict]] = []
    for r in results:
        for g in groups:
            if same_finding(g[0], r):
                g.append(r)
                break
        else:
            groups.append([r])
    return groups


def _conf(r: dict) -> Optional[float]:
    c = r.get("confidence")
    return float(c) if isinstance(c, (int, float)) and not isinstance(c, bool) else None


def _median_pick(group: List[dict]) -> tuple:
    confs = [c for c in (_conf(r) for r in group) if c is not None]
    med = statistics.median(confs) if confs else None
    if med is None:
        return group[0], None
    rep = min(group, key=lambda r: abs((_conf(r) if _conf(r) is not None else 1e9) - med))
    return rep, med


def combine(results: List[dict], attempted: Optional[int] = None) -> dict:
    """Combine N finalized results (finalize() output) into one result with
    a `consensus` block. A single result is returned unchanged apart from
    that block."""
    if not results:
        raise ValueError("no results to combine")
    attempted = attempted or len(results)
    n = len(results)
    findings = [r for r in results if r.get("assessment_outcome") in (ASSESSED, UNCERTAIN) and _finding(r)]
    no_finding = [r for r in results if r.get("assessment_outcome") in NO_FINDING]

    groups = sorted(_groups(findings), key=lambda g: (len(g), statistics.median([_conf(r) or 0 for r in g])), reverse=True)
    top = groups[0] if groups else []

    if len(no_finding) > n / 2:
        # Most passes could not assess the photo: say so, using the most common outcome.
        outcome = max(NO_FINDING, key=lambda o: sum(r["assessment_outcome"] == o for r in no_finding))
        out = dict(next(r for r in no_finding if r["assessment_outcome"] == outcome))
        agreeing, median = sum(r["assessment_outcome"] == outcome for r in results), None
    elif top and len(top) > n / 2:
        rep, median = _median_pick(top)
        out = dict(rep)
        out["confidence"] = round(median, 1) if median is not None else rep.get("confidence")
        # Assessed only when most agreeing passes said assessed.
        assessed_votes = sum(r.get("assessment_outcome") == ASSESSED for r in top)
        if assessed_votes <= n / 2 and out.get("assessment_outcome") == ASSESSED:
            out = _as_uncertain(out)
        agreeing = len(top)
        out["alternative_findings"] = _alternatives(out, groups[1:], results)
    else:
        # No majority: the model disagreed with itself. Never pick one.
        base = top[0] if top else results[0]
        out = _as_uncertain(dict(base))
        out["confidence"] = None
        out["confidence_valid"] = True
        agreeing, median = (len(top) if top else 0), None
        out["alternative_findings"] = _alternatives(None, groups, results)

    confs = [c for c in (_conf(r) for r in top) if c is not None] if top else []
    out["consensus"] = {
        "passes_requested": attempted,
        "passes_completed": n,
        "agreeing_passes": agreeing,
        "method": "majority finding, median confidence" if n > 1 else "single pass",
        "pass_findings": [_finding(r) or r.get("assessment_outcome") for r in results],
        "pass_confidences": [_conf(r) for r in results],
        "confidence_spread": round(max(confs) - min(confs), 1) if len(confs) >= 2 else 0.0 if confs else None,
    }
    return out


def _as_uncertain(r: dict) -> dict:
    r["assessment_outcome"] = UNCERTAIN
    for k in TREATMENT_KEYS:
        if k in r:
            r[k] = None
    return r


def _alternatives(chosen: Optional[dict], other_groups: List[List[dict]], results: List[dict]) -> List[dict]:
    """The chosen pass's own alternatives, plus the findings other passes
    arrived at, without duplicates."""
    alts, seen = [], set()
    if chosen is not None:
        seen.add(_norm(_finding(chosen)))
    sources = list((chosen or {}).get("alternative_findings") or [])
    for g in other_groups:
        sources.append({"name": _finding(g[0]), "why_considered": f"Named by {len(g)} of {len(results)} repeat analyses."})
    for r in results:
        sources.extend(r.get("alternative_findings") or [])
    for a in sources:
        key = _norm(a.get("name"))
        if key and key not in seen:
            seen.add(key)
            alts.append({"name": a["name"], "why_considered": a.get("why_considered") or ""})
    return alts
