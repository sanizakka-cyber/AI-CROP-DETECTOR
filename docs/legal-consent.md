# Terms agreement, minimum age 18 and the no-refund acknowledgement

## What this adds

| Where | Change |
|---|---|
| Web registration | "Age" field (numbers only) and a required "I confirm I am 18 years or older" tick on the Details step; the Terms/Privacy checkbox on the last step is now a real, required field and the Create button stays disabled until it is ticked. |
| App registration (`mobile/app/(auth)/register.jsx`) | Same age field and 18+ tick; the passive "By creating an account…" text is replaced by a required checkbox with links to the Terms and Privacy pages; the Register button stays disabled until it is ticked. |
| Server (web and `POST /api/auth/register`) | `terms_accepted` must be accepted, `age` must be 18–120, `age_confirmed` must be accepted. Under 18 on the web: nothing is stored and the entered details are not kept in the session. |
| Proof | `users.terms_accepted_at`, `terms_version`, `privacy_version`, `age_declared`, `age_declared_at`, plus one row per acceptance in `legal_acceptances` (document, version, context, IP, user agent, app version, payment reference). |
| Existing users (web) | `EnsureCurrentLegalAcceptance` sends signed-in users who have not accepted the current versions to `/legal/accept` once. Only full-page GET navigations are redirected; forms, JSON calls, payment callbacks and webhooks are never interrupted. |
| Subscription payment (web plans page) | Yellow "Subscription payments are non-refundable" notice with a required tick; every subscribe form sends `refund_acknowledged`; the server rejects a payment without it and records the acknowledgement with the payment reference before Paystack is opened. The refund policy version is also sent in the Paystack metadata. |
| Subscription payment (app) | The confirmation before Paystack shows the no-refund notice; the pay button reads "I understand – Pay", and the app sends `refund_acknowledged: true`. |
| Legal pages | Refund Policy rewritten to "non-refundable after activation" with the four exceptions; Terms §1/§3/§5 updated (acceptance record, 18+, refunds); Privacy Policy now cites the NDPA 2023 and GAID 2025, names the real processors (Anthropic, OpenAI/Spitch, Paystack, Render, email/SMS, map/weather), explains international transfers, location privacy and children. Each page shows its version. **These texts are drafts for legal review.** |

## Settings (`config/legal.php`, all overridable in Render env vars)

| Variable | Default | Meaning |
|---|---|---|
| `LEGAL_TERMS_VERSION`, `LEGAL_PRIVACY_VERSION`, `LEGAL_REFUND_VERSION` | `2026-11-v2` | Bump after a legal change; users then re-accept at next web login. |
| `LEGAL_REQUIRE_API_CONSENT` | `true` | App builds before v1.4.11 do not send age/terms, so registration from those builds is refused with a clear message. Set to `false` **only temporarily** if old builds must keep registering until the new build is published. An under-18 age is refused either way. |
| `LEGAL_REQUIRE_API_REFUND_ACK` | `true` | Same, for subscribing from old app builds. |
| `LEGAL_ENFORCE_REACCEPTANCE` | `true` | Web re-acceptance page for existing users (off in tests and the screenshot job). |

## Rollout order

1. Merge → Render runs the migration (additive only: 5 nullable columns and one new table).
2. On the first web visit, every existing user (including staff) sees the accept page once.
3. Until the next app build (with these changes) is published, either keep the defaults (old app builds can't register or subscribe and are told why) or set `LEGAL_REQUIRE_API_CONSENT=false` and `LEGAL_REQUIRE_API_REFUND_ACK=false` for that short period.
4. Build and publish the next APK, then make sure both settings are back to `true`.

## Still open (not done in code)

- The app does not yet ask existing app-only users to re-accept new versions (web does).
- Translations of the new web text (Hausa, Yoruba, Igbo, French, Arabic) need native review: `docs/review/translation-review-2026-11-legal.csv`. Fulfulde falls back to English. The app strings are English and Hausa only, like the rest of the registration screen.
- The legal page texts must be reviewed by a Nigerian data-protection lawyer or licensed DPCO before relying on them.

## Tests

- `msas-system/tests/Feature/LegalConsentTest.php` (14 tests) and `LegalPagesRenderTest.php` (3 tests).
- `mobile/app/(auth)/__tests__/register.consent.test.js` (4 tests).
- Existing registration tests now send the new fields.
