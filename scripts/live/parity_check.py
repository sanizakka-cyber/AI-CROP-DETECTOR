"""
Live web-vs-app parity check against STAGING (Phase 12.2).

Scans the same photo twice through the real services, once the way the
Android app (build 24) does it (POST /api/diagnose/<type>, bearer token,
images[] multipart) and once the way the web scan page does it (session
login, POST /diagnostics/analyze), then reads both records back through
GET /api/diagnose/<id> (what the app renders) and the web history page, and
compares them field by field.

Credentials come only from the environment, never from arguments, and are
never printed:
    QA_EMAIL, QA_PASSWORD   a staging QA account (Render secrets)
    STAGING_URL             default https://msas-farmai-staging.onrender.com

Refuses to run against the production domain.

Usage (PowerShell):
    $env:QA_EMAIL = "qa-farmer@staging.msasagro.com"
    $env:QA_PASSWORD = (Get-Clipboard)   # pasted from Render, not typed into a command
    py -I scripts/live/parity_check.py --type crop --photo path\\to\\leaf.jpg --out parity.json
"""
import argparse
import http.cookiejar
import json
import mimetypes
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid

PROD_HOSTS = ("msasagro.com", "www.msasagro.com", "msas-farmai.onrender.com")
API_PATH = {"crop": "crop", "livestock": "livestock", "soil": "soil", "pest": "pest"}
WEB_TYPE = {"crop": "plant", "livestock": "animal", "soil": "soil", "pest": "pest"}
# Fields both clients must agree on for the same scan.
COMPARE = [
    ("assessment.outcome", "outcome"),
    ("aiResult.primaryDiagnosis", "top finding"),
    ("aiResult.severity", "severity"),
    ("aiResult.urgency", "urgency"),
    ("assessment.notCertain", "not certain"),
    ("assessment.needsConfirmation", "likely, please confirm"),
    ("aiResult.confidence", "confidence"),
]


def _multipart(fields, files):
    b = uuid.uuid4().hex
    out = []
    for k, v in fields.items():
        out.append(f'--{b}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
    for k, path in files:
        ctype = mimetypes.guess_type(path)[0] or "image/jpeg"
        out.append(f'--{b}\r\nContent-Disposition: form-data; name="{k}"; filename="{os.path.basename(path)}"\r\n'
                   f"Content-Type: {ctype}\r\n\r\n".encode() + open(path, "rb").read() + b"\r\n")
    out.append(f"--{b}--\r\n".encode())
    return b"".join(out), f"multipart/form-data; boundary={b}"


class Client:
    def __init__(self, base):
        self.base = base.rstrip("/")
        self.jar = http.cookiejar.CookieJar()
        self.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def req(self, method, path, body=None, headers=None, timeout=240):
        r = urllib.request.Request(self.base + path, data=body, method=method, headers=headers or {})
        try:
            with self.op.open(r, timeout=timeout) as resp:
                return resp.status, resp.read().decode("utf-8", "replace")
        except urllib.error.HTTPError as e:
            return e.code, e.read().decode("utf-8", "replace")


def get(d, dotted):
    for part in dotted.split("."):
        d = d.get(part) if isinstance(d, dict) else None
    return d


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--type", choices=sorted(API_PATH), required=True)
    ap.add_argument("--photo", required=True)
    ap.add_argument("--out", default="parity.json")
    ap.add_argument("--confidence-tolerance", type=float, default=3.0)
    a = ap.parse_args(argv)

    base = os.environ.get("STAGING_URL", "https://msas-farmai-staging.onrender.com")
    if any(h in base for h in PROD_HOSTS):
        print("Refusing to run against production. Set STAGING_URL to the staging service.")
        return 2
    email, password = os.environ.get("QA_EMAIL"), os.environ.get("QA_PASSWORD")
    if not email or not password:
        print("Set QA_EMAIL and QA_PASSWORD in the environment (from the Render secrets).")
        return 2

    # ── App path (what build 24 sends) ────────────────────────────────────────
    app = Client(base)
    s, body = app.req("POST", "/api/auth/login", json.dumps({"phone": email, "password": password}).encode(),
                      {"Content-Type": "application/json", "Accept": "application/json"})
    if s != 200:
        print(f"App login failed: HTTP {s}")
        return 1
    token = json.loads(body).get("token")
    auth = {"Authorization": f"Bearer {token}", "Accept": "application/json"}
    form, ctype = _multipart({"client_scan_id": str(uuid.uuid4())}, [("images[]", a.photo)])
    t0 = time.time()
    s, body = app.req("POST", f"/api/diagnose/{API_PATH[a.type]}", form, {**auth, "Content-Type": ctype})
    app_secs = time.time() - t0
    if s not in (200, 201):
        print(f"App scan failed: HTTP {s} {body[:300]}")
        return 1
    app_resp = json.loads(body)
    app_id = get(app_resp, "diagnosis.diagnosisId") or get(app_resp, "diagnosis.id") or app_resp.get("diagnosisId")

    # ── Web path (what the scan page sends) ───────────────────────────────────
    web = Client(base)
    s, page = web.req("GET", "/login")
    m = re.search(r'name="_token"\s+value="([^"]+)"', page) or re.search(r'name="csrf-token"\s+content="([^"]+)"', page)
    if not m:
        print("Could not read the web login form")
        return 1
    data = urllib.parse.urlencode({"_token": m.group(1), "login": email, "password": password}).encode()
    s, _ = web.req("POST", "/login", data, {"Content-Type": "application/x-www-form-urlencoded"})
    s, page = web.req("GET", "/diagnostics/scan")
    m = re.search(r'name="csrf-token"\s+content="([^"]+)"', page)
    if s != 200 or not m:
        print(f"Web login failed (scan page HTTP {s})")
        return 1
    form, ctype = _multipart({"_token": m.group(1), "scan_type": WEB_TYPE[a.type]}, [("image", a.photo)])
    t0 = time.time()
    s, body = web.req("POST", "/diagnostics/analyze", form,
                      {"Content-Type": ctype, "Accept": "application/json", "X-Requested-With": "XMLHttpRequest"})
    web_secs = time.time() - t0
    if s != 200:
        print(f"Web scan failed: HTTP {s} {body[:300]}")
        return 1
    web_id = json.loads(body).get("diagnosis_id")

    # ── Read both back the way the app renders them ───────────────────────────
    recs = {}
    for name, did in (("app", app_id), ("web", web_id)):
        s, body = app.req("GET", f"/api/diagnose/{did}", headers=auth)
        recs[name] = json.loads(body).get("diagnosis", {}) if s == 200 else {}
    s, hist = web.req("GET", "/diagnostics/history")
    web_page_sev = sorted(set(re.findall(r'data-severity="([^"]*)"', hist)))

    rows, ok = [], True
    for path, label in COMPARE:
        av, wv = get(recs["app"], path), get(recs["web"], path)
        if label == "confidence":
            same = av is not None and wv is not None and abs(float(av) - float(wv)) <= a.confidence_tolerance
        elif label == "top finding":
            same = (av or "").strip().lower() == (wv or "").strip().lower()
        else:
            same = av == wv
        ok &= bool(same)
        rows.append({"field": label, "app": av, "web": wv, "same": bool(same)})

    print(f"Staging {base}  photo {os.path.basename(a.photo)}  type {a.type}")
    print(f"app scan #{app_id} in {app_secs:.0f}s, web scan #{web_id} in {web_secs:.0f}s")
    for r in rows:
        print(f"  {'OK ' if r['same'] else 'DIFF'} {r['field']:24} app={r['app']!r:40} web={r['web']!r}")
    print(f"  severities on the web history page: {web_page_sev}")
    for name in ("app", "web"):
        print(f"  {name} consensus: {get(recs[name], 'assessment.consensus')}")
    json.dump({"base": base, "photo": a.photo, "type": a.type, "app_id": app_id, "web_id": web_id,
               "rows": rows, "records": recs, "web_history_severities": web_page_sev, "parity": ok},
              open(a.out, "w", encoding="utf-8"), indent=1, default=str)
    print(("PARITY OK" if ok else "PARITY DIFFERENCES FOUND") + f"  (saved {a.out})")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
