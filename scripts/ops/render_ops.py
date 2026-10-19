"""
Render API helper for incidents and releases. Reads RENDER_API_KEY from the
environment or from ~/.msas/render.env; never prints a secret (env var
values are shown only as a short SHA-256 fingerprint and length).

  py -I scripts/ops/render_ops.py services
  py -I scripts/ops/render_ops.py events  <service-name> [--since 2026-10-19T12:00:00Z]
  py -I scripts/ops/render_ops.py logs    <service-name> --since ... --until ... [--grep regex] [--limit 200]
  py -I scripts/ops/render_ops.py envfp   <service-name> KEY [KEY...]        # fingerprints only
  py -I scripts/ops/render_ops.py samekey <svc-a> KEY_A <svc-b> KEY_B        # equal or not
  py -I scripts/ops/render_ops.py rotate-shared <svc-a> KEY_A <svc-b> KEY_B  # new random value in both, then deploy both
  py -I scripts/ops/render_ops.py deploy  <service-name>
"""
import hashlib
import json
import os
import re
import secrets
import sys
import urllib.parse
import urllib.request

API = "https://api.render.com/v1"
MASKS = [
    (re.compile(r"sk-ant-[A-Za-z0-9_\-]{8,}"), "sk-ant-***"),
    (re.compile(r"\bBearer\s+[A-Za-z0-9._\-+/=]{6,}", re.I), "Bearer ***"),
    (re.compile(r"\b(sk|pk)_(live|test)_[A-Za-z0-9]{6,}"), r"\1_\2_***"),
    (re.compile(r"((?:api[_-]?key|key|token|secret|password)[\"']?\s*[:=]\s*[\"']?)[^\s\"'&,}]{4,}", re.I), r"\1***"),
    (re.compile(r"base64:[A-Za-z0-9+/]{20,}={0,2}"), "base64:***"),
]


def mask(text):
    for rx, rep in MASKS:
        text = rx.sub(rep, text)
    return text


def api_key():
    key = os.environ.get("RENDER_API_KEY")
    if not key:
        path = os.path.join(os.path.expanduser("~"), ".msas", "render.env")
        if os.path.exists(path):
            for line in open(path, encoding="utf-8"):
                if line.strip().startswith("RENDER_API_KEY="):
                    key = line.split("=", 1)[1].strip().strip('"').strip("'")
    if not key:
        sys.exit("RENDER_API_KEY not found (environment or ~/.msas/render.env)")
    return key


def call(method, path, body=None, query=None):
    url = API + path + ("?" + urllib.parse.urlencode(query, doseq=True) if query else "")
    req = urllib.request.Request(url, method=method, headers={
        "Authorization": f"Bearer {api_key()}", "Accept": "application/json", "Content-Type": "application/json"})
    data = json.dumps(body).encode() if body is not None else None
    try:
        with urllib.request.urlopen(req, data=data, timeout=60) as r:
            raw = r.read().decode()
            return json.loads(raw) if raw else {}
    except urllib.error.HTTPError as e:
        sys.exit(f"Render API {method} {path}: HTTP {e.code} {mask(e.read().decode()[:300])}")


def services():
    out = []
    cursor = None
    while True:
        q = {"limit": 100, **({"cursor": cursor} if cursor else {})}
        page = call("GET", "/services", query=q)
        if not page:
            break
        for item in page:
            out.append(item["service"])
            cursor = item.get("cursor")
        if len(page) < 100:
            break
    return out


def service(name):
    for s in services():
        if s["name"] == name:
            return s
    sys.exit(f"No service named {name}")


def env(name):
    s = service(name)
    out, cursor = {}, None
    while True:
        q = {"limit": 100, **({"cursor": cursor} if cursor else {})}
        page = call("GET", f"/services/{s['id']}/env-vars", query=q)
        for item in page:
            ev = item["envVar"]
            out[ev["key"]] = ev.get("value")
            cursor = item.get("cursor")
        if len(page) < 100:
            break
    return s, out


def fp(value):
    if value is None:
        return "(not set)"
    return f"sha256:{hashlib.sha256(value.encode()).hexdigest()[:10]} len={len(value)}"


def main(argv):
    if not argv:
        sys.exit(__doc__)
    cmd, args = argv[0], argv[1:]
    opts = {}
    while args and args[-2:-1] and args[-2].startswith("--"):
        opts[args[-2][2:]] = args[-1]
        args = args[:-2]
    if cmd == "services":
        for s in services():
            d = s.get("serviceDetails", {})
            print(f"{s['name']:28} {s['id']:24} {s['type']:18} {s.get('suspended')} {d.get('url', '')} owner={s['ownerId']}")
    elif cmd == "events":
        s = service(args[0])
        q = {"limit": int(opts.get("limit", 50))}
        if "since" in opts:
            q["startTime"] = opts["since"]
        for item in call("GET", f"/services/{s['id']}/events", query=q):
            e = item["event"]
            print(e.get("timestamp"), e.get("type"), mask(json.dumps(e.get("details", {}))[:300]))
    elif cmd == "logs":
        s = service(args[0])
        q = {"ownerId": s["ownerId"], "resource": [s["id"]], "limit": int(opts.get("limit", 200)), "direction": "backward"}
        if "since" in opts:
            q["startTime"] = opts["since"]
        if "until" in opts:
            q["endTime"] = opts["until"]
        if "grep" in opts:
            q["text"] = [opts["grep"]]
        res = call("GET", "/logs", query=q)
        for line in res.get("logs", []):
            print(line.get("timestamp"), mask(line.get("message", ""))[:600])
        if res.get("hasMore"):
            print(f"... more (next end time {res.get('nextEndTime')})")
    elif cmd == "envfp":
        s, vals = env(args[0])
        for k in args[1:]:
            print(f"{args[0]} {k}: {fp(vals.get(k))}")
    elif cmd == "samekey":
        _, a = env(args[0])
        _, b = env(args[2])
        va, vb = a.get(args[1]), b.get(args[3])
        print(f"{args[0]}.{args[1]} {fp(va)}")
        print(f"{args[2]}.{args[3]} {fp(vb)}")
        print("MATCH" if va is not None and va == vb else "DIFFERENT")
    elif cmd == "rotate-shared":
        new = secrets.token_urlsafe(48)
        for svc, key in ((args[0], args[1]), (args[2], args[3])):
            s = service(svc)
            call("PUT", f"/services/{s['id']}/env-vars/{key}", body={"value": new})
            print(f"set {svc}.{key} -> {fp(new)}")
        for svc in (args[0], args[2]):
            s = service(svc)
            d = call("POST", f"/services/{s['id']}/deploys", body={"clearCache": "do_not_clear"})
            print(f"deploy {svc}: {d.get('id')} {d.get('status')}")
    elif cmd == "deploy":
        s = service(args[0])
        d = call("POST", f"/services/{s['id']}/deploys", body={"clearCache": "do_not_clear"})
        print(f"deploy {args[0]}: {d.get('id')} {d.get('status')}")
    else:
        sys.exit(__doc__)


if __name__ == "__main__":
    main(sys.argv[1:])
