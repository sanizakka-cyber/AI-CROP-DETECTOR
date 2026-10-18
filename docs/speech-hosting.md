# Speech service hosting plan (for approval; nothing bought yet)

**Scope:** English (Kokoro-82M) and Hausa (OpenBible VITS) on CPU, in one container built from `tts-service/Dockerfile.openbible`.

All figures below come from a laptop CPU (Intel i5-8365U), so treat them as estimates. Re-run `tts-service/bench/run_benchmark_cpu_image.py` on the chosen instance before launch.

## What the service needs (measured)

- **Memory:** 2.05 GB peak with both engines loaded. A 2 GB instance would run out of memory, so 4 GB is the minimum.
- **Disk for models:** OpenBible Hausa 952 MB + Kokoro 314 MB, about 1.3 GB.
  - `fetch_models.sh` fetches only Hausa unless `OPENBIBLE_LANGS` says otherwise.
  - Yoruba and Igbo would add about 950 MB each, only when needed for review packs.
- **Speed** (time to first sentence; compute seconds per second of speech):

  | CPU threads (≈ vCPUs) | Hausa first sentence | Hausa | English first sentence | English |
  |---|---|---|---|---|
  | 1 | 20.5 s | 2.33× | 7.2 s | 2.29× |
  | 2 | 10.3 s | 1.49× | 4.7 s | 1.91× |
  | 4 | 7.3 s | 1.11× | 3.6 s | 1.36× |

- **Length of a typical long result:** about 90 s of speech in Hausa and 46 s in English (`bench/texts.json`).

## Options

| | Instance | Monthly cost | Hausa first sentence | Pre-generated results per day (70% busy) | Notes |
|---|---|---|---|---|---|
| **A (recommended)** | **Render Pro**: 2 CPU, 4 GB, + 5 GB disk | **about USD 85 + 1.25** | about 10 s | about 440 Hausa, or about 690 English | Same dashboard and private network as Laravel; automatic restarts; no public endpoint |
| B | Render Pro Plus: 4 CPU, 8 GB, + 5 GB disk | about USD 175 + 1.25 | about 7 s | about 600 Hausa, or about 960 English | Choose if scan volume or on-demand speed outgrows A |
| C (cheapest) | Hetzner Cloud CX33: 4 vCPU (shared), 8 GB, Germany | about EUR 7–9 (price after Hetzner's June 2026 increase not confirmed) | about 7 s (shared vCPU may vary) | about 600 Hausa | About a tenth of A's cost, but you run the server: OS updates, HTTPS (Caddy), firewall, monitoring. Laravel reaches it over the internet with the bearer key. |
| — | Render Standard: 1 CPU, 2 GB | USD 25 | — | — | **Not suitable:** too little memory |

**Sources for prices:**
- Render instance specs: [render.com/docs/compute-plans](https://render.com/docs/compute-plans).
- Render prices (Standard $25, Pro $85, Pro Plus $175) are from mid-2026 third-party reviews ([jwatte.com](https://jwatte.com/blog/render-com-platform-review/)), because Render's own pricing page could not be read automatically.
- Hetzner's June 2026 repricing: [privatedevops.com](https://privatedevops.com/news/hetzner-june-2026-cloud-price-increase-what-to-do).
- **Check both providers' pricing pages before buying.**

**How the throughput is estimated:**
- Results per day = 0.7 × 86,400 s ÷ (speech length × compute factor).
- Example for Hausa on Pro: 90 s × 1.49 = about 134 s per result.
- A mix of English and Hausa falls between the two figures.
- The Claude translation runs on the AI engine, not here, so it does not use this capacity.

**Recommendation: A, Render Pro.** Pre-generation prepares audio before the farmer opens the result, so the on-demand first-sentence time matters less than reliability and simplicity. Move to B if there are more than about 400 English and Hausa scans a day. Choose C only if cost matters more than having nothing new to run.

## Expected time to first sound for the farmer (estimates; measure on staging)

| | English | Hausa |
|---|---|---|
| **Pre-generated** (opened after the audio was made) | about 2–3 s: one API call plus the first sentence file (about 20 KB at 40 kbps) over 3G | same |
| **On demand** (opened before, or not the saved language) | about 7–10 s: synthesis of the first sentence plus fetch | about 15–30 s: Claude translation (not measured yet), about 10 s for the first sentence on option A, plus fetch |
| Text on screen | at once (English) | when the translation is ready (pre-generated: at once) |

These are not measurements. The 3G figures will come from staging (`docs/staging.md`), with the emulator throttled to UMTS as in Phase 12.1.

## One-time GPU test: YarnGPT2 for Yoruba and Igbo (needs your approval of the amount)

**Why:** on CPU, YarnGPT2 takes about 4 minutes to the first Yoruba or Igbo sentence. Its voices may still be the best for these two languages. A GPU test shows whether a GPU makes it usable, and what it would cost.

- **Provider:** RunPod, pay as you go, prepaid credit. You top up a fixed amount, so the spend cannot go over it.
- **Instance:** 1 × RTX 4090 (24 GB). Listed at about USD 0.34/hour on Community Cloud and about USD 0.69/hour on Secure Cloud ([runpod.io/gpu-cloud/pricing](https://www.runpod.io/gpu-cloud/pricing), checked 2026-10-18).
- **Time:** 6 hours at most, covering:
  - building the image and downloading the models: about 1 h;
  - the benchmark: about 1 h;
  - generating the Yoruba and Igbo review-pack audio (25 results × 3 voices each, plus the extra items): about 2 h;
  - margin: about 2 h.
- **Cost:** 6 h × USD 0.69 = USD 4.14 at most on Secure Cloud, plus about USD 0.50 for a temporary 50 GB volume.
- **Cap for your approval: USD 10, about ₦15,000** (at about ₦1,500 to the dollar; check the day's rate). Top up exactly that; the pod is deleted when the test ends.
- **What I will measure** (same scripts as the CPU runs, `bench/run_benchmark.py`):
  - time to the first sentence, the compute factor and failures, per language and voice (Yoruba 3 voices, Igbo 3 voices);
  - GPU memory;
  - cost per full result, and per 1,000 results per month on an always-on GPU versus a pay-per-second serverless GPU;
  - audio for the Yoruba and Igbo review packs, so native speakers can compare YarnGPT2 with OpenBible blind.
- **Not started.** It needs your approval of the USD 10 cap, a RunPod account in MSAS's name, and the review packs, which come from staging.
