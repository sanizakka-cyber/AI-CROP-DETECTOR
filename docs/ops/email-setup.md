# Transactional email for msasagro.com

**Today no MSAS email reaches anyone.**
- `MAIL_USERNAME` in Render is the placeholder `REPLACE_WITH_GMAIL`, so the app cannot send at all.
- Even when it can, msasagro.com has **no SPF record and no DKIM**, while its DMARC record (GoDaddy's default) says `p=quarantine`. Every message "from" `noreply@msasagro.com` would therefore fail authentication and be junked or quarantined.
- DNS checked on 2026-10-19: DNS is at GoDaddy (ns17/ns18.domaincontrol.com). There are no MX or TXT records on the root. `_dmarc` is `v=DMARC1; p=quarantine; adkim=r; aspf=r; rua=mailto:dmarc_rua@onsecureserver.net;`.

## Recommendation: Brevo (free plan, SMTP)

| | Brevo | Resend | Amazon SES |
|---|---|---|---|
| Free | 300 emails a day, permanently | 3,000 a month, 100 a day | credits only in the first months |
| Paid | from about USD 9 a month | USD 20 a month for 50,000 | USD 0.10 per 1,000 |
| Setup | SMTP, no code change | SMTP or API | SMTP, plus an AWS account and a "production access" request |
| Fits MSAS because | OTPs, password resets, receipts and alerts stay well under 300 a day today; account in minutes; dashboard shows every delivery and bounce | — | cheapest at scale, but the most setup |

**Pick Brevo now; move to SES** if sending regularly passes about 250 a day. These are sources' figures as of 2026, so check the pricing pages ([Brevo](https://costbench.com/software/email-api/brevo-transactional/), [comparison](https://dupple.com/learn/best-transactional-email-services)) before upgrading.

## What you do (about 20 minutes)

1. Create a free account at brevo.com with a company email address.
2. **Senders, Domains & Dedicated IPs → Domains → Add a domain**: `msasagro.com`. Choose **manual** setup. Brevo then shows the exact records for your account.
3. In **GoDaddy → My Products → msasagro.com → DNS → Add record**, add these. The values marked ⟨from Brevo⟩ are copied from the Brevo screen.

   | Type | Name | Value | Why |
   |---|---|---|---|
   | TXT | `@` | `brevo-code:⟨from Brevo⟩` | proves you own the domain |
   | CNAME | `brevo1._domainkey` | `⟨from Brevo⟩` (e.g. `b1.msasagro-com.dkim.brevo.com`) | DKIM signature, key 1 |
   | CNAME | `brevo2._domainkey` | `⟨from Brevo⟩` (e.g. `b2.msasagro-com.dkim.brevo.com`) | DKIM signature, key 2 |
   | TXT | `@` | `v=spf1 include:spf.brevo.com ~all` | SPF: Brevo may send for msasagro.com (there is no SPF record today, so this is the only one) |
   | TXT | `_dmarc` (**edit the existing one**) | `v=DMARC1; p=none; rua=mailto:⟨your email⟩; adkim=r; aspf=r` | report-only for 2 weeks while we confirm everything passes; then change `p=none` to `p=quarantine` |

   If Brevo shows different record names (it changes them now and then), use Brevo's. Do not add a second SPF (`v=spf1`) record later; combine them into one.
4. Back in Brevo, click **Authenticate this email domain**. DNS can take up to an hour.
5. **SMTP & API → SMTP → Generate a new SMTP key**, named `msas-production`.
6. Tell me "Brevo is ready". Then either:
   - paste the SMTP login (it is not secret, it looks like `xxxx@smtp-brevo.com`) and set the SMTP key yourself in Render → `msas-farmai` → Environment → `MAIL_PASSWORD`; or
   - put both in `~/.msas/mail.env` and I set them through the Render API without printing them.

## What I then set in Render (msas-farmai)

| Variable | Value |
|---|---|
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `smtp-relay.brevo.com` |
| `MAIL_PORT` | `587` |
| `MAIL_SCHEME` | `tls` (already set) |
| `MAIL_USERNAME` | the Brevo SMTP login |
| `MAIL_PASSWORD` | the Brevo SMTP key (secret) |
| `MAIL_FROM_ADDRESS` | `noreply@msasagro.com` (already set) |
| `MAIL_FROM_NAME` | `MSAS FarmAI` (already set) |
| `SCAN_ERROR_ALERT_EMAILS` | the address you choose for scan-failure alerts |

## Tests after setup

1. **Password reset (OTP)**: request one for a test account at https://www.msasagro.com/forgot-password, and check it arrives in the inbox, not spam. In Gmail, "Show original" should say SPF, DKIM and DMARC all **PASS**.
2. **Scan-failure alert**: sent from a Render shell (`php artisan tinker`) with a test `ScanFailureAlertMail`, never by causing real failures.
3. **Payment receipt**: the app has a printable receipt page (`/payment/receipt/{id}`) but no receipt email today. Subscription reminder and expiry emails exist and use the same mail settings. A receipt email is a small addition if you want one.
4. `php artisan mail:test <address>` (`TestMailCommand`) sends a sample OTP email.

## Also worth knowing

- **No MX record:** nobody can reply to `noreply@msasagro.com`, and bounces from replies are lost. That is fine for a no-reply sender. For support@ you need a mailbox (for example Zoho Mail free, or Google Workspace), which adds MX records, plus a merged SPF such as `v=spf1 include:spf.brevo.com include:zoho.com ~all`.
- **The nightly `db:backup --email`** emails `MAIL_FROM_ADDRESS` (`noreply@`), so it goes nowhere even after this. That is a separate fix; database dumps should not go by email anyway.
