# Listen to your result

After a crop, livestock, soil or pest scan, the farmer picks a language on the result screen and gets two things:
- the result shown in that language, in four sections: observations, explanation, recommendations and a safety note;
- the same text read aloud, with the sentence being spoken highlighted.

It works on the web history page and in the Android app, for new scans and for saved scans.

Launch languages are English, Hausa, Yoruba and Igbo. More can be added from the admin screen without code changes.

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
GenerateScanAudio job
  │  SpeechPreprocessor: NFC, strip markdown/emoji (tone marks kept), pronunciation lexicon,
  │                      numbers/units/ranges/% in words, acronyms by letter, sentences + clauses ≤200 chars,
  │                      pauses 250 ms (comma) / 450 ms (full stop) / 700 ms (between sections)
  │  TtsProvider::synthesize(segments, lang, voice, rate)
  │     ├─ yarngpt / farmerline → tts-service (self-hosted, FastAPI, Docker)
  │     ├─ openbible → tts-service built from Dockerfile.openbible (its own container, TTS_OPENBIBLE_URL)
  │     └─ elevenlabs (optional cloud fallback per language)
  ▼
scan_result_audio: mono MP3 ~40 kbps on the speech disk, keyed by hash(text+lang+voice+provider+rate)
  ▼
signed, short-lived URL + per-sentence start/end times → player highlights the current sentence
```

The source is the scan's own language. New scans are in the language the farmer scanned in; English scans need no translation. Playback never translates: translations and audio are made once and cached.

## Endpoints

The app uses `/api/...` with a bearer token. The web uses the same controller under `/speech/...` with the session.

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/speech/languages` | Languages for the picker (live ones; in-review ones too for reviewers), the default (saved choice), and voice credits. |
| POST | `/api/scans/{id}/translate` `{lang, remember?, audio?}` | Start or return the translation. `remember` saves `users.preferred_language`. Returns 202 while working. |
| GET | `/api/scans/{id}/translation/{lang}` | Poll: `status` is pending, ready, rejected or failed. Returns `content`, plus `source` for fallback display. |
| POST | `/api/scans/{id}/audio` `{lang, voice?, rate?}` | Start or return the audio. A failed audio is retried. |
| GET | `/api/scans/{id}/audio/{lang}` | Poll: `status` is ready, pending, retrying, failed, unavailable or translating. Returns `url`, `downloadUrl`, `sizeBytes`, `durationSec`, `sentences[{text, section, item, start, end}]` and `message`. |
| POST | `/api/tts-feedback` `{scan_id, lang, clear, comment?}` | "Was this audio clear?" Saved per language, voice and provider. |
| POST | `/api/scans/{id}/translation/{lang}/review` | Extension staff (and admins) mark a translation reviewed. |

Access: the scan's owner (farmer, dealer or anyone who scanned), or a reviewer (`extension-officer`, `agronomist`, `admin`, `ceo`). Language, voice and glossary admin is `admin` and `ceo` only, at `/admin/speech`.

## Setup

1. **Migrations** run on deploy, through `php artisan migrate` in `docker/start.sh`. They add `users.preferred_language`, `speech_languages` (seeded with en, ha, yo and ig), `scan_result_translations`, `scan_result_audio`, `glossary_terms` and `tts_feedback`.
2. **Queue.** The jobs run on the `speech` queue of the `database` connection. `docker/start.sh` now starts a worker for that queue only. `QUEUE_CONNECTION` stays `sync` for everything else, so nothing else changes behaviour.
3. **AI engine.** Deploy `ai-engine/` with the new `/translate/scan-result` endpoint. It uses the existing `AI_ENGINE_URL` and `AI_ENGINE_KEY`.
4. **Speech service.** Build and run `tts-service/` (see its README) somewhere Laravel can reach.
5. **Laravel environment:**

   | Variable | Value |
   |---|---|
   | `TTS_SERVICE_URL` | e.g. `http://msas-tts:8002` |
   | `TTS_SERVICE_KEY` | a long random string, the same value as in the speech service |
   | `SPEECH_DISK` | defaults to `r2` when `R2_BUCKET` is set (served by 30-minute signed URLs), otherwise `local` (served by a signed Laravel route) |
   | `TTS_OPENBIBLE_URL`, `TTS_OPENBIBLE_KEY` | optional, the OpenBible VITS container (Hausa, Yoruba, Igbo) |
   | `SPEECH_FARMERLINE_CONFIRMED` | leave `false` until Farmerline confirms the licence in writing (Decision 3) |
   | `SPEECH_GO_LIVE_*` | optional overrides of the go-live rule (step 7) |
   | `SPEECH_PREGENERATE` | optional, see Decisions |
   | `ELEVENLABS_API_KEY`, `ELEVENLABS_MODEL` | optional cloud fallback |

6. **Admin, at `/admin/speech`:**
   - Check each language's provider, voice and rate.
   - Add glossary terms.
   - Add protected product names to the "*" list.
7. **Native-speaker review.** For each of Hausa, Yoruba and Igbo:
   - The product owner creates reviewer accounts. An admin assigns each one to a language as a native speaker or an expert (extension officer or vet) at `/admin/speech/review`.
   - Build a review pack: `php artisan speech:build-review-pack ha --candidate=yarngpt:hausa_female1 --candidate=openbible:openbible_hausa`.
     - The pack holds real scans of every type (crop, livestock, soil, pest), anonymised: no farmer name, phone or location.
     - It takes `--per-type` scans of each type, 5 by default, so 20 per pack. Use `--per-type=13` or several packs to reach 50.
     - Each scan is translated by the production translator. Translations that fail the integrity checks are skipped, never fixed by hand.
     - Every candidate voice speaks each item. The pack also covers numbers and units as they will be spoken, screen labels, the safety note and the pronunciation lexicon.
   - Reviewers hear voices as A/B/C in a different order per item. Nothing on the page names the provider or voice.
   - They rate translation accuracy, and each voice's naturalness, pronunciation and speed. They mark errors by selecting text, and choose a voice.
   - Glossary and pronunciation fixes go into `/admin/speech`.
   - **Go-live rule** (`config/speech.php` → `go_live`; overridable with `SPEECH_GO_LIVE_*`):
     - at least 50 scan results reviewed by both a native speaker and an expert, with at least 5 of each scan type;
     - average accuracy of 4 or more, and average naturalness of the chosen voice of 4 or more;
     - no dose or chemical errors.

     Setting a language **live** is refused until it passes, and farmers do not see it until then. The results page shows each check, the unblinded voices and every marked error.
   - English is live from the start.

## Adding a language (no code change)

1. In `/admin/speech` → "Add a language", enter the code (e.g. `pcm`), the English name, the name in the language, and optionally a provider and voice. The language starts as **draft**.
2. Add glossary terms. Add `unit` terms for how units are said (for example `kg` → `kilo`). Add `pronunciation` terms for names and acronyms.
3. Numbers stay as digits unless a number speller exists for the language (currently English, Hausa and Igbo). Unit and pronunciation terms work in every language.
4. Set the status to in review, get 50 results reviewed, then set it to live. A language with no voice provider is still useful, as text only.

## What is verified, and what is not

- **Verified by automated tests:**
  - **PHP (CI):** the preprocessor, integrity checks, the full translate-then-audio job flow, caching, the integrity rejection, voice-service failure and fallback, access rules, feedback, the review gate, admin, the web panel and pre-generation.
  - **Python:** the AI-engine endpoint, and the speech service with a fake engine plus real ffmpeg encoding.
  - **Jest:** the app panel (picker, no auto-play, polling, highlighting, speed, download and offline play, fallback, feedback).
- **Run for real (2026-10-17, laptop CPU, results in `tts-service/bench/`):**
  - YarnGPT2 and OpenBible VITS both produced speech in every launch language with no failed sentences. One Igbo test sentence was excluded from the YarnGPT2 run.
  - That first run found four start-up bugs: wrong speaker file names, the import path, an unpinned `outetts`, and a build failure from `pesq`. All four are fixed, and CI now imports the YarnGPT2 runtime.
  - **Speed, 2 CPU threads (i5-8365U):**

    | Engine | Language | First sentence ready | Full test result | Compute per second of speech |
    |---|---|---|---|---|
    | YarnGPT2 | en | 71 s (55 s on mains power) | 18 min | 28× |
    | YarnGPT2 | ha | 5.5 min | 61 min | 68× |
    | YarnGPT2 | yo | 4 min | 55 min | 88× |
    | YarnGPT2 | ig | 3.6 min | 57 min | 80× |
    | OpenBible VITS | ha / yo / ig | 6–9 s | 1.3–2 min | 1.2–1.4× |

- **Not verified:**
  - How the speech sounds: quality and pronunciation in every language. Only native speakers can judge this, in the review packs.
  - Real translation quality in Hausa, Yoruba and Igbo. That is also what the native-speaker review is for.
  - Speed on the production host or on a GPU. The "~10 seconds on 3G" target is out of reach for YarnGPT2 on CPU (see Decisions).
- **Unreviewed wording.** These are written in the target language but not reviewed by native speakers:
  - the Hausa and Igbo number words and unit words;
  - the UI strings and section headings in Hausa, Yoruba and Igbo.

  They are part of the native-speaker review.

## Decisions for the product owner

1. **Where the speech service runs, and the 10-second target.**
   - YarnGPT2 is a 0.4B-parameter model that generates audio step by step. The model card recommends 5 beams for Hausa, Yoruba and Igbo, which multiplies the work.
   - On a CPU server, a full result will take well over 10 seconds; expect minutes for long results. Render has no GPUs.
   - Measured: on a laptop CPU, YarnGPT2 took 4 to 5.5 minutes for the first Hausa, Yoruba or Igbo sentence. OpenBible VITS took 6 to 9 seconds (see the table above).
   - Options:
     - **(a)** a GPU host for `tts-service`, e.g. a GPU cloud VM or a serverless GPU platform;
     - **(a2)** OpenBible VITS on CPU for Hausa, Yoruba and Igbo, if reviewers accept its voices. It has a Bible-reading style, one voice per language, and a CC-BY-SA licence that requires attribution;
     - **(b)** `SPEECH_PREGENERATE=true`, which prepares text and audio in the farmer's saved language right after every scan, so it is ready when they open the result;
     - **(c)** both.
   - Text appears within seconds either way, and cached replays are instant.
2. **Pre-generation cost.** Option (b) adds one Claude translation and one synthesis to every scan, including scans the farmer never listens to.
3. **Farmerline Hausa licence.** Its card says CC-BY-4.0 but does not state the base model, and it matches the size of Meta's MMS Hausa model, which is CC-BY-NC. It is disabled until the provenance is confirmed with Farmerline.
4. **YarnGPT2 model card inconsistency.** The card lists Yoruba, Igbo and Hausa voices but says in its "out of scope" section that it is unsuitable for languages other than English. Native-speaker review must decide whether its Hausa, Yoruba and Igbo voices are good enough. If not, choose a cloud provider for those languages, which costs money per use.
5. **Default voices.** The first-listed (most stable) voice per language: en `idera`, ha `hausa_female1`, yo `yoruba_male2`, ig `igbo_female2`. Choose male or female per language after listening.
6. **English as the single source.** The spec asks for English as the source of truth. Today the AI writes each result in the language the farmer scanned in, and that is kept: switching every scan to English first would change what Hausa-speaking farmers see on the full report today. Translation works from either. Decide whether to switch scans to English-only. That is a one-line change to the scan request, but it changes the full report screen.
7. **Reviewers.** Who are the native-speaker and expert reviewers for each language? Are the go-live thresholds right: 50 results, 5 per scan type, average scores of 4 out of 5, and no dose errors?
8. **Old narration endpoints.** `/diagnostics/{id}/narration` (Spitch, whose base URL was never verified) and `/diagnostics/translate` are no longer used by the web page, which used to send result text to a free third-party translation service. Remove them after this ships.
9. **Retention.** How long to keep generated audio files. Database rows are deleted with the scan, but the files are not: they are shared between scans by content hash, so they stay until a cleanup job removes files no row points to. That job is not built yet.
