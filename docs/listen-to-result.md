# Listen to your result

After a crop, livestock, soil or pest scan, the farmer picks a language on the result screen and gets:
- the result shown in that language, in four sections: observations, explanation, recommendations and a safety note;
- in a language with audio, the same text read aloud, with the sentence being spoken highlighted.

It works on the web history page and in the Android app, for new scans and for saved scans.

**At launch (decisions of 2026-10-18):**

| Language | Text | Audio | Voice |
|---|---|---|---|
| English | live | on | Kokoro-82M `af_heart` (Apache-2.0), CPU |
| Hausa | after native-speaker review | on | OpenBible VITS (CC BY-SA 4.0), CPU |
| Yoruba | after native-speaker review | off: Listen is hidden, the text is shown | (OpenBible kept as the review candidate) |
| Igbo | after native-speaker review | off: Listen is hidden, the text is shown | (OpenBible kept as the review candidate) |

- YarnGPT2 is not used in production on CPU.
- More languages can be added from the admin screen without code changes.

## How it works

```
scan result (stored)
  │  ScanResultSource: reduce to {observations[], explanation, recommendations[], safety_note}
  ▼
TranslateScanResult job ──► AI engine POST /translate/scan-result (Claude, structured JSON,
  │                          per-language glossary, protected terms)
  │  TranslationIntegrity: every number, number+unit and product/scientific name must be unchanged,
  │                        no number added, no list item dropped → otherwise "rejected" (never shown or spoken)
  ▼
scan_result_translations (stored once per scan + language; re-done only if the result changes)
  │
  │  only if the language has audio (speech_languages.audio_enabled + a usable provider)
  ▼
GenerateScanAudio job
  │  SpeechPreprocessor: NFC, strip markdown/emoji (tone marks kept), pronunciation lexicon,
  │                      numbers/units/ranges/% in words, acronyms by letter, sentences + clauses ≤200 chars,
  │                      pauses 250 ms (comma) / 450 ms (full stop) / 700 ms (between sections)
  │  TtsProvider::synthesize(segments, lang, voice, rate)
  │     ├─ kokoro (English) + openbible (Hausa) → the CPU speech container (Dockerfile.openbible, TTS_CPU_URL)
  │     ├─ yarngpt → its own container (Dockerfile); GPU only, off unless SPEECH_YARNGPT_ENABLED
  │     ├─ farmerline → off until its licence is confirmed (SPEECH_FARMERLINE_CONFIRMED)
  │     └─ elevenlabs (optional paid cloud fallback per language)
  ▼
scan_result_audio: mono MP3 ~40 kbps on the speech disk, keyed by hash(text+lang+voice+provider+rate),
                   written sentence by sentence so playback can start before the end is generated
  ▼
signed, short-lived URL + per-sentence start/end times → player highlights the current sentence
```

**Source language.**
- The source is the scan's own language. New scans are in the language the farmer scanned in.
- English scans need no translation.
- Playback never translates: translations and audio are made once and cached.

**Pre-generation** (`SPEECH_PREGENERATE`, on):
- Right after a scan is saved, the result is prepared in the farmer's **preferred language only**, and only if that language is live and has audio (English or Hausa at launch).
- It is queued on the `speech` queue after the scan is committed, so the scan result is never slower.
- If the farmer opens the result before the audio exists, they see the text and "Audio is being prepared…".
- Text-only languages are translated when the farmer asks.

## Endpoints

The app uses `/api/...` with a bearer token. The web uses the same controller under `/speech/...` with the session.

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/speech/languages` | Languages for the picker (live ones; in-review ones too for reviewers), with `hasVoice` per language, the default (saved choice) and credits for the voices in use. |
| POST | `/api/scans/{id}/translate` `{lang, remember?, audio?}` | Start or return the translation. `remember` saves `users.preferred_language`. Returns 202 while working. |
| GET | `/api/scans/{id}/translation/{lang}` | Poll: `status` is pending, ready, rejected or failed. Returns `content`, plus `source` for fallback display. |
| POST | `/api/scans/{id}/audio` `{lang, voice?, rate?}` | Start or return the audio. A failed audio is retried. For a text-only language: `status: unavailable, textOnly: true`, and the voice service is never called. |
| GET | `/api/scans/{id}/audio/{lang}` | Poll: `status` is ready, pending, retrying, failed, unavailable or translating. Returns `url`, `downloadUrl`, `sizeBytes`, `durationSec`, `sentences[{text, section, item, start, end}]`, sentence `chunks` while generating, and `message`. |
| POST | `/api/tts-feedback` `{scan_id, lang, clear, comment?}` | "Was this audio clear?" Saved per language, voice and provider. |
| POST | `/api/scans/{id}/translation/{lang}/review` | Extension staff (and admins) mark a translation reviewed. |

**Access:**
- **Results:** the scan's owner (farmer, dealer or anyone who scanned), or a reviewer (`extension-officer`, `agronomist`, `admin`, `ceo`).
- **Languages, voices, the audio switch and the glossary:** `admin` and `ceo` only, at `/admin/speech`.

## Setup

1. **Migrations** run on deploy, through `php artisan migrate` in `docker/start.sh`.
   - They add `users.preferred_language`, `speech_languages` (seeded with en, ha, yo and ig), `scan_result_translations`, `scan_result_audio`, `glossary_terms`, `tts_feedback`, the review tables and `speech_languages.audio_enabled`.
   - The last of these sets the launch voices above.
2. **Queue.** The jobs run on the `speech` queue of the `database` connection. `docker/start.sh` starts a worker for that queue only. `QUEUE_CONNECTION` stays `sync` for everything else.
3. **AI engine.** Deploy `ai-engine/` with the `/translate/scan-result` endpoint. It uses the existing `AI_ENGINE_URL` and `AI_ENGINE_KEY`.
4. **Speech service.** Build `tts-service/Dockerfile.openbible`, the CPU image with English (Kokoro) and Hausa (OpenBible). See `docs/speech-hosting.md` for the instance size.
5. **Laravel environment:**

   | Variable | Value |
   |---|---|
   | `TTS_CPU_URL` | the CPU speech container, e.g. `http://msas-tts-cpu:8002` |
   | `TTS_CPU_KEY` | a long random string, the same value as `TTS_SERVICE_KEY` in that container |
   | `SPEECH_DISK` | defaults to `r2` when `R2_BUCKET` is set (served by 30-minute signed URLs), otherwise `local` (served by a signed Laravel route) |
   | `SPEECH_PREGENERATE` | `true` (the default) |
   | `SPEECH_YARNGPT_ENABLED` | `false`; `true` only where YarnGPT2 runs on a GPU (`TTS_SERVICE_URL`) |
   | `SPEECH_FARMERLINE_CONFIRMED` | `false` until Farmerline confirms the licence in writing |
   | `SPEECH_REVIEW_PACK_SIZE`, `SPEECH_GO_LIVE_*` | optional overrides of the review rule (step 7) |
   | `ELEVENLABS_API_KEY`, `ELEVENLABS_MODEL` | optional paid cloud fallback |

   `TTS_OPENBIBLE_URL` / `TTS_OPENBIBLE_KEY` are still read if `TTS_CPU_*` are not set.
6. **Admin, at `/admin/speech`:**
   - Check each language's provider, voice, rate and **Audio on** switch.
   - Add glossary terms.
   - Add protected product names to the "*" list.
7. **Native-speaker review.** For each of Hausa, Yoruba and Igbo:
   - The product owner sends names and emails. An admin creates the accounts (the app emails the invitation; no password is ever shared in chat) and assigns each one to a language at `/admin/speech/review`, as a native speaker or an expert (extension officer or vet).
   - Build a review pack: `php artisan speech:build-review-pack ha`.
     - **25 real scan results** per pack (`--size`): 7 crop, 6 livestock, 6 soil and 6 pest.
     - Anonymised: no farmer name, phone or location.
     - Every enabled voice for the language (or `--candidate=provider:voice`) speaks each item.
     - Each scan is translated by the production translator. Translations that fail the integrity checks are skipped, never fixed by hand.
     - The pack also covers numbers and units as they will be spoken, screen labels, the safety note and the pronunciations (MSAS FarmAI, GPS, LGA).
   - Reviewers hear voices as A/B/C in a different order per item. Nothing on the page names the provider or voice. The guide for them is `docs/review/reviewer-guide.md`.
   - They rate translation accuracy, and each voice's naturalness, pronunciation and speed. They mark errors by selecting text, and choose a voice.
   - Glossary and pronunciation fixes go into `/admin/speech`.
   - **Go-live rule** (`config/speech.php` → `go_live`):
     - **2 approved packs.** A pack is approved when every scan result in it has been reviewed by a native speaker **and** an expert, its average accuracy (and naturalness, for a language with audio) is 4/5 or more, and it has no dose or chemical error.
     - At least 50 reviewed results in all, with at least 5 of each scan type.
     - Average translation accuracy of 4/5 or more.
     - For a language with audio: average naturalness of its voice of 4/5 or more. A text-only language is judged on its translation alone.
     - No dose or chemical errors anywhere.

     Setting a language **live** is refused until it passes, and farmers do not see it until then. The results page shows each check, the unblinded voices and every marked error.
   - English is live from the start.

## Adding a language (no code change)

1. In `/admin/speech` → "Add a language", enter the code (e.g. `pcm`), the English name, the name in the language, and optionally a provider and voice. The language starts as **draft**, with audio off.
2. Add glossary terms. Add `unit` terms for how units are said (for example `kg` → `kilo`). Add `pronunciation` terms for names and acronyms.
3. Numbers stay as digits unless a number speller exists for the language (currently English, Hausa and Igbo). Unit and pronunciation terms work in every language.
4. Set the status to in review, get two packs approved, then set it to live. Switch **Audio on** only once its voice has been reviewed. Until then it is text only, and Listen is hidden.

## What is verified, and what is not

- **Verified by automated tests:**
  - **PHP (CI):**
    - the preprocessor, integrity checks, the full translate-then-audio job flow, caching, the integrity rejection, voice-service failure and fallback;
    - access rules, feedback, the review gate, admin and the web panel;
    - pre-generation, including preferred language only, audio languages only and never inside the scan request;
    - text-only languages, the Kokoro provider, YarnGPT2 off by default;
    - packs of 25 and the two-approved-packs rule.
  - **Python:** the AI-engine endpoint, and the speech service with fake engines (including Kokoro) plus real ffmpeg encoding.
  - **Jest:**
    - the app panel: picker, no auto-play, polling, highlighting, speed, download and offline play, fallback, feedback;
    - Listen hidden for text-only languages;
    - the Credits and licences screen.
- **Run for real on a laptop CPU (i5-8365U, results in `tts-service/bench/`):**

  | Engine | Language | Threads | First sentence ready | Compute per second of speech |
  |---|---|---|---|---|
  | YarnGPT2 | en | 2 | 55–71 s | 23–28× |
  | YarnGPT2 | ha / yo / ig | 2 | 3.6–5.5 min | 68–88× |
  | OpenBible VITS | ha | 1 / 2 / 4 | 20.5 / 10.3 / 7.3 s | 2.33 / 1.49 / 1.11× |
  | Kokoro-82M `af_heart` | en | 1 / 2 / 4 | 7.2 / 4.7 / 3.6 s | 2.29 / 1.91 / 1.36× |

  - The OpenBible and Kokoro rows come from the CPU image's own combination: both engines in one process, peak memory 2.05 GB (`bench/run_benchmark_cpu_image.py`).
  - Kokoro alone at 2 threads, 2 runs per voice (`bench/run_benchmark_kokoro.py`): `af_heart` 6.1–6.8 s first sentence, 2.0–2.1× real time; `bf_emma` 8.0–8.4 s, 2.4–2.9×. No failed sentences.
  - On CPU both voices are slower than real time at 1–2 threads, so on-demand playback may pause between sentences; pre-generation avoids this.
- **Not verified:**
  - How the speech sounds (quality, accent, pronunciation): native-speaker review.
  - Real translation quality in Hausa, Yoruba and Igbo: native-speaker review.
  - Speed on the production instance, and real 3G timings. Both are measured on staging once it exists.
- **Unreviewed wording**, written in the target language but not reviewed by native speakers (part of the native-speaker review):
  - the Hausa and Igbo number words and unit words;
  - the UI strings and section headings in Hausa, Yoruba and Igbo;
  - the new "Show the result" and "Text only in this language for now" strings, which are English in the Yoruba and Igbo files until reviewed.

## Decisions

### Made on 2026-10-18

1. **Voices and hosting.**
   - English: Kokoro-82M. Hausa: OpenBible, both on CPU in one container.
   - YarnGPT2 is not used in production on CPU.
   - Instance size and cost: `docs/speech-hosting.md`.
2. **Pre-generation** is on: preferred language only, English and Hausa only, never slowing the scan.
3. **Farmerline** stays disabled (`SPEECH_FARMERLINE_CONFIRMED=false`). An email asking Farmerline about the base model and commercial use is drafted in `docs/review/farmerline-email.md`.
4. **Yoruba and Igbo:**
   - Their text can go live after native-speaker review. Audio stays off and Listen is hidden.
   - A one-time GPU test of YarnGPT2 for these two is proposed in `docs/speech-hosting.md`, awaiting approval of its cost.
5. **Go-live rule:**
   - packs of 25;
   - 2 approved packs (50 results across all 4 scan types);
   - average accuracy and naturalness of 4/5 or more;
   - no dosage or chemical errors.
   - English is live from the start.
6. **Credits:** the OpenBible CC BY-SA 4.0 credit (and Kokoro's Apache-2.0 credit) is in the app under Profile → Credits and licences, and in About. It is built into the app so it shows offline.

### Still open (recommendations)

7. **English voice accent.** Kokoro has American and British voices only; there is no Nigerian English voice.
   - **Recommendation:** launch with `af_heart` (the best-graded voice) and ask the English reviewers to compare it with `bf_emma` in the first English pack.
8. **English as the single source.** Today the AI writes each result in the language the farmer scanned in.
   - **Recommendation:** keep it as it is for now. Switching every scan to English first would change the full report Hausa farmers see today.
   - Revisit after Hausa goes live, using reviewer feedback on the translated text.
9. **Old narration endpoints.** `/diagnostics/{id}/narration` and `/diagnostics/translate` are no longer used by the web page.
   - **Recommendation:** remove them in the release after this one ships, once the CEO dashboard's references to their route names are replaced.
10. **Retention of audio files.** Database rows are deleted with the scan. The files are shared between scans by content hash, so they stay until a cleanup removes files no row points to.
    - **Recommendation:** a weekly job that deletes audio files no row has pointed to for 30 days, plus audio not played for 180 days. It is not built yet.
11. **Reviewers.** Who reviews each language? Accounts are created only when the names and emails are sent.
