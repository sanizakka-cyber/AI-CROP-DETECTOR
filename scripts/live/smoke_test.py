"""
Post-deploy smoke test (Phase 12.2): login, all 4 scan types, history, PDF
report, the low-confidence screen rule, the severity rule, and the payment
checkout start. Run against staging first, then production after a deploy.

Credentials come only from the environment and are never printed:
    SMOKE_URL        e.g. https://msas-farmai-staging.onrender.com
    SMOKE_EMAIL      the account to test with
    SMOKE_PASSWORD
Photos: --photos crop=leaf.jpg livestock=cow.jpg soil=soil.jpg pest=pest.jpg

Each scan uses one scan from that account's monthly quota and calls the AI
engine (3 analyses per scan). Production needs --production, on purpose.
Payments: on staging the checkout is started with Paystack TEST keys; on
production the script only checks that a checkout URL is issued, it never pays.

    py -I scripts/live/smoke_test.py --photos crop=leaf.jpg livestock=cow.jpg soil=soil.jpg pest=larva.jpg
"""
import argparse
import json
import os
import sys
import time
import uuid

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from parity_check import PROD_HOSTS, Client, _multipart, get  # noqa: E402

TYPES = ["crop", "livestock", "soil", "pest"]


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--photos", nargs="+", default=[], help="type=path for each scan type to test")
    ap.add_argument("--production", action="store_true", help="required to run against production")
    ap.add_argument("--out", default="smoke.json")
    a = ap.parse_args(argv)

    base = os.environ.get("SMOKE_URL", "").rstrip("/")
    email, password = os.environ.get("SMOKE_EMAIL"), os.environ.get("SMOKE_PASSWORD")
    if not base or not email or not password:
        print("Set SMOKE_URL, SMOKE_EMAIL and SMOKE_PASSWORD in the environment.")
        return 2
    is_prod = any(h in base for h in PROD_HOSTS)
    if is_prod and not a.production:
        print("This is production: add --production to confirm.")
        return 2
    photos = dict(p.split("=", 1) for p in a.photos)

    results = []

    def check(name, ok, detail=""):
        results.append({"check": name, "ok": bool(ok), "detail": detail})
        print(f"  {'PASS' if ok else 'FAIL'}  {name}{('  ' + detail) if detail else ''}")
        return ok

    c = Client(base)
    s, body = c.req("GET", "/api/health")
    check("health", s == 200, f"HTTP {s} {body[:80]}")

    s, body = c.req("POST", "/api/auth/login", json.dumps({"phone": email, "password": password}).encode(),
                    {"Content-Type": "application/json", "Accept": "application/json"})
    if not check("app login", s == 200, f"HTTP {s}"):
        return 1
    auth = {"Authorization": f"Bearer {json.loads(body)['token']}", "Accept": "application/json"}

    scanned = []
    for t in TYPES:
        if t not in photos:
            check(f"scan {t}", False, "no photo given (--photos {t}=...)")
            continue
        form, ctype = _multipart({"client_scan_id": str(uuid.uuid4())}, [("images[]", photos[t])])
        t0 = time.time()
        s, body = c.req("POST", f"/api/diagnose/{t}", form, {**auth, "Content-Type": ctype})
        secs = time.time() - t0
        d = json.loads(body).get("diagnosis", {}) if s in (200, 201) else {}
        ok = s in (200, 201) and d.get("assessment", {}).get("outcome") is not None
        check(f"scan {t}", ok, f"HTTP {s} {secs:.0f}s outcome={get(d, 'assessment.outcome')} "
              f"finding={get(d, 'aiResult.primaryDiagnosis')!r} conf={get(d, 'aiResult.confidence')} "
              f"passes={get(d, 'assessment.consensus.passes_completed')}")
        if d:
            scanned.append(d)
            # One severity value: the API's severity is the lowercase severityLevel, never the urgency.
            sev, lvl = get(d, "aiResult.severity"), get(d, "aiResult.severityLevel")
            check(f"severity rule ({t})", sev == (lvl.lower() if lvl else None), f"severity={sev} severityLevel={lvl} urgency={get(d, 'aiResult.urgency')}")
            conf = get(d, "aiResult.confidence")
            a_ = d.get("assessment", {})
            if isinstance(conf, (int, float)) and a_.get("outcome") == "assessed":
                want_nc = conf < a_.get("minConfidence", 65)
                want_lk = not want_nc and conf < a_.get("confirmBelow", 75)
                check(f"low-confidence rule ({t})", a_.get("notCertain") == want_nc and a_.get("needsConfirmation") == want_lk,
                      f"conf={conf} notCertain={a_.get('notCertain')} needsConfirmation={a_.get('needsConfirmation')}")

    s, body = c.req("GET", "/api/diagnose", headers=auth)
    rows = json.loads(body).get("diagnoses", []) if s == 200 else []
    ids = {r.get("id") for r in rows}
    check("history lists the new scans", s == 200 and all(d.get("diagnosisId") in ids for d in scanned), f"HTTP {s}, {len(rows)} rows")

    # Web: session login, history page and the PDF report of the first new scan.
    import re
    import urllib.parse
    w = Client(base)
    s, page = w.req("GET", "/login")
    m = re.search(r'name="_token"\s+value="([^"]+)"', page)
    if m:
        w.req("POST", "/login", urllib.parse.urlencode({"_token": m.group(1), "login": email, "password": password}).encode(),
              {"Content-Type": "application/x-www-form-urlencoded"})
    s, hist = w.req("GET", "/diagnostics/history")
    check("web history page", s == 200 and "data-ai-disclaimer" in hist, f"HTTP {s}")
    if scanned:
        did = scanned[0]["diagnosisId"]
        s, rep = w.req("GET", f"/diagnostics/{did}/report")
        check("PDF report page", s == 200 and "diag-name" in rep, f"HTTP {s} (diagnosis {did})")

    # Payments: start a checkout and stop there. Never completes a payment.
    s, page = w.req("GET", "/subscription/plans")
    check("subscription plans page", s == 200, f"HTTP {s}")

    ok = all(r["ok"] for r in results)
    json.dump({"base": base, "production": is_prod, "results": results, "scans": scanned},
              open(a.out, "w", encoding="utf-8"), indent=1, default=str)
    print(("SMOKE TEST PASSED" if ok else "SMOKE TEST FAILED") + f"  (saved {a.out})")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
