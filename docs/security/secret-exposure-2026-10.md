# Secret exposure review (Phase 12.2, 2026-10-18)

The repository `sanizakka-cyber/AI-CROP-DETECTOR` is **public**. Anything ever committed to it should be treated as published.

Nothing below has been rotated, purged or rewritten. Every action that changes keys or history waits for the owner's written approval.

## 1. The 14 July 2026 commit (`2504e18`)

### What it contains

`2504e18` ("Prepare deployment-ready project", 2026-07-14) added `mobile/KEYSTORE_CREDENTIALS.txt`. It holds:

| Field | Value | Secret? |
|---|---|---|
| Keystore file | `android/app/msas-release.keystore` (a path only) | no |
| Key alias | `msas` | no |
| Store password | 21 characters | **yes, exposed** |
| Key password | "same as store password" | **yes, exposed** |
| Certificate | `CN=MSAS FarmAI, OU=Mobile, O=MSAS Livestock and Agro Services, L=Katsina, ST=Katsina State, C=NG`, RSA 2048, valid 10,000 days | no |

Commit `ac8f49c` (2026-09-08) stopped tracking the file, but it is still in history.

### Was the keystore file itself committed?

**No.** Three checks found no keystore file anywhere in history:
- No file named `*.keystore`, `*.jks`, `*.p12` or `*.pfx` was ever committed. The only path matching "keystore" is the `.txt` above.
- All 5,208 git objects were scanned by file signature (JKS `FEEDFEED`, JCEKS `CECECECE`, PKCS#12): no keystore content.
- The file is not on this machine either. Only Android's public `debug.keystore` is in `mobile/android/app/`.

So the **passwords leaked, the key did not**. A password is useless without the key file.

### Is it the key that signs the app today?

**No.** Signing certificates read from every APK on disk:

| Builds | Signed by | Certificate SHA-256 |
|---|---|---|
| v1.0 release only (2026-06-21) | **the leaked-password key** (`CN=MSAS FarmAI…`) | `03:58:21:8A:B6:6C:59:F2:B1:59:AA:E6:48:96:B8:5D:F6:26:C9:1D:5E:68:22:B7:5C:6C:2F:DE:9A:36:16:A7` |
| v1.1.1 build 2 to v1.4.3 build 19 | Android's public **debug** key | `FA:C6:17:45:DC:09:03:78:…` |
| v1.4.4 build 20 to v1.4.8 build 24 | **the EAS-managed key** (created 2026-07-17, stored by Expo, never in git) | `92:0E:E4:7F:7A:C3:74:4C:E3:14:FE:54:5F:C1:6E:7E:EE:6E:D7:76:34:35:DA:3B:9C:EF:9E:2B:88:41:8A:40` |

`mobile/eas.json` sets no `credentialsSource`, so EAS signs with its remote keystore, `92:0E:E4:7F…`. The leaked passwords belong to a key that has not signed anything since v1.0.

**Still to confirm (owner): the Google Play upload key.** I cannot see Play Console. Open Play Console → the app → **Test and release → App integrity → App signing**, and compare the **Upload key certificate** SHA-256 with the table:
- `92:0E:E4:7F…` (EAS): the leaked key plays no part in Play. Nothing to reset.
- `03:58:21:8A…`: the leaked key is the Play upload key, and option B below is recommended.
- The app is not on Play: nothing to reset.

### Risk

- **Today's releases: low.** The signing key in use was never exposed.
- **The leaked key: low.** To misuse it, someone would need the keystore file too, and that file was never committed.
- **If it is the Play upload key: medium.** Rotate it anyway: Play cannot know whether the file ever left your machines.
- **Related, from the table:** builds 2–19 were signed with Android's public debug key, which anyone can use. Phones still on those builds should uninstall them and install the current release. An update signed with a different key cannot replace them anyway.

### Options needing your approval

| | Action | When | Cost / effect |
|---|---|---|---|
| A | **Retire the leaked key.** Never sign with `msas-release.keystore` again. If you still have the file, store it offline (or delete it if Play does not use it). | always (recommended) | free; no app impact |
| A2 | **Change the keystore passwords:** `keytool -storepasswd -keystore msas-release.keystore`, then `keytool -keypasswd -alias msas -keystore msas-release.keystore`, in a JDK. | only if you keep using that file | free; the new passwords go into the password manager, nowhere else |
| B | **Play upload-key reset:** Play Console → App integrity → **Request upload key reset**. Generate a new upload key with `eas credentials -p android` → Keystore → set up a new keystore, export its certificate (`.pem`) and upload it. Google takes about 2 days. | only if Play's upload key is `03:58:21:8A…` | free; app users unaffected (Play re-signs with the app signing key) |
| C | **Purge the file from git history** with `git filter-repo` (steps below). | optional, after #147 and #157 are merged | rewrites every commit hash; every clone and open PR must be re-made; forks and caches keep the old copy |

Purging (C) does not undo three months of public exposure. The keys and passwords must be rotated whether or not history is rewritten. Purging only stops new clones from carrying them.

## 2. Everything else in history

`gitleaks` with the repo rules (`.gitleaks.toml`) found 26 historical findings. All are recorded by fingerprint (no values) in `.gitleaksignore`.

| What | Where (first commit) | Status | Action |
|---|---|---|---|
| Android keystore passwords | `mobile/KEYSTORE_CREDENTIALS.txt` (2504e18) | see §1 | A / B |
| QA account emails + passwords (7 accounts per file) | `msas-system/storage/qa-credentials-2026061{6,7}-*.txt`, `QA_CREDENTIALS_HANDOVER_GUIDE.md` (2504e18) | untracked 2026-09-08 | **change those passwords, or deactivate those accounts in production** (they are test accounts) |
| Laravel `APP_KEY` | `render.yaml` (592f284, b91c152; 2026-07-14) | removed 2026-08-18 (e96dc91), comment says rotate | confirm it was rotated in Render; if not, rotate it and put the old value in `APP_PREVIOUS_KEYS` so existing sessions survive |
| AI engine `API_KEY` / Laravel `AI_ENGINE_KEY` | `render.yaml` (b1e7493, facddb3; 2026-07-15) | removed 2026-08-18 | confirm rotated; rotate both together if not |
| `JWT_SECRET` of the old Node `server/` | `server/.env` and `render.yaml` (2504e18) | dev-only config (in-memory database); `server/` is not deployed | none, unless `server/` is ever deployed |

I cannot see Render's settings, so I cannot tell whether the `render.yaml` values were rotated after 2026-08-18. Render shows when each environment variable was last changed.

## 3. Prevention, now in place

- `.gitleaks.toml`: gitleaks' default rules plus the shapes that actually leaked here (keystore and QA passwords in `.txt` files, `APP_KEY` and `*_KEY` values in YAML, env-file secrets, and keystore/key files by path). The default rules alone found 2 of the 26 findings.
- `.githooks/pre-commit` blocks a commit that stages a secret. It uses gitleaks when it is installed, and a built-in check otherwise. Enable it once per clone: `git config core.hooksPath .githooks`. It is enabled on this machine.
- `.github/workflows/secret-scan.yml` scans the whole history on every push and pull request, with a checksum-verified gitleaks 8.21.2, and fails on any finding not in `.gitleaksignore`.
- Tested: both the hook and the scanner block a fake `APP_KEY` and a `.jks` file.

## 4. Prepared history purge (option C). Not run.

Run only after written approval, and after #147 and #157 are merged (it changes every commit hash, so open PRs would need re-creating).

```bash
# 0. Safety copy of the whole repository, kept offline
git clone --mirror https://github.com/sanizakka-cyber/AI-CROP-DETECTOR.git msas-backup-before-purge.git

# 1. Fresh mirror to rewrite
git clone --mirror https://github.com/sanizakka-cyber/AI-CROP-DETECTOR.git msas-purge.git
cd msas-purge.git
pip install git-filter-repo==2.45.0

# 2. Remove the credential files from every commit
git filter-repo --invert-paths \
  --path mobile/KEYSTORE_CREDENTIALS.txt \
  --path msas-system/storage/qa-credentials-20260616-125622.txt \
  --path msas-system/storage/qa-credentials-20260617-075931.txt \
  --path QA_CREDENTIALS_HANDOVER_GUIDE.md \
  --path server/.env

# 3. Blank the old values inside render.yaml. Make replacements.txt LOCALLY,
#    outside the repo, one line per old secret value:  <old value>==>***REMOVED***
#    Never commit it, and delete it afterwards.
git filter-repo --replace-text ../replacements.txt

# 4. Check, then publish (this force-pushes every branch and tag)
gitleaks git . --config <path-to>/.gitleaks.toml --redact   # expect no findings
git push --force --mirror

# 5. Afterwards
#  - Ask GitHub Support to remove cached views and pull-request refs
#    (refs/pull/*) that still point at the old commits.
#  - Everyone deletes their clone and clones again. EAS and Render are unaffected.
#  - Empty .gitleaksignore: its fingerprints name the old commits.
```
