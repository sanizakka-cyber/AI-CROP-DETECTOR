# MSAS FarmAI speech service

Self-hosted text-to-speech for "Listen to your result". Laravel sends text that is already translated, cleaned and split into sentences. This service speaks each sentence, joins them with pauses, applies the speaking rate, and returns one small mono MP3 plus each sentence's start and end time. The app uses those times to highlight the sentence being spoken.

The feature as a whole is described in [`docs/listen-to-result.md`](../docs/listen-to-result.md).

## Voices

| Engine | Languages | License | Source |
|---|---|---|---|
| `kokoro` (CPU image, **production English**) | English (American/British; voices `af_heart`, `af_bella`, `bf_emma`) | Apache-2.0 | [hexgrad/Kokoro-82M](https://huggingface.co/hexgrad/Kokoro-82M) |
| `openbible` (CPU image, **production Hausa**) | Hausa, Yoruba, Igbo (one voice each) | CC-BY-SA-4.0, credit "Voices: OpenBibleTTS (McGill-NLP, Mila)" | [multilingual-tts/VITS-OpenBible-*](https://huggingface.co/multilingual-tts) |
| `yarngpt` (own image, GPU only) | English (Nigerian accent), Hausa, Yoruba, Igbo | Apache-2.0 | [saheedniyi/YarnGPT2](https://huggingface.co/saheedniyi/YarnGPT2) |
| `farmerline` (off) | Hausa | CC-BY-4.0, credit "Hausa voice: FarmerlineML" | [FarmerlineML/main_hausa_TTS](https://huggingface.co/FarmerlineML/main_hausa_TTS) |

Voices follow the YarnGPT2 model card's order, best first. The card spells two of them `hausa_feamle1` and `yoruba_feamle1`, but the speaker files the model loads are `hausa_female1` and `yoruba_female1`, so the file names are used.

- `farmerline` is not enabled by default. Its model card does not say which model it was fine-tuned from. It has the size and architecture of Meta's MMS Hausa model, which is CC-BY-NC (non-commercial). Confirm its provenance before enabling it.
- **Production is the CPU image `Dockerfile.openbible`:** OpenBible (Hausa) and Kokoro (English) together, `TTS_ENGINES=openbible,kokoro`. Point Laravel's `TTS_CPU_URL` / `TTS_CPU_KEY` at it. Sizing and cost: `docs/speech-hosting.md`.
  - It is separate from YarnGPT2's image because coqui-tts needs transformers 4.56 or later and YarnGPT2 needs 4.47.1.
  - It fetches only the Hausa OpenBible checkpoint (about 950 MB) unless `OPENBIBLE_LANGS="Hausa Yoruba Igbo"`.
  - Kokoro's weights, voices and spaCy's English model are installed at build or first start, never at request time.
- `openbible` voices: the model cards say "multispeaker", but the checkpoints have no speaker embedding, so there is one voice per language. They were trained on Bible readings, so the speaking style is formal.
- Non-commercial models are refused unless `ALLOW_NON_COMMERCIAL_MODELS=1`. Never set that in production.

## Run

```bash
docker build -t msas-tts tts-service
docker run -d --name msas-tts -p 8002:8002 \
  -e TTS_SERVICE_KEY=change-me \
  -e TTS_ENGINES=yarngpt \
  -v msas-tts-models:/models \
  msas-tts
curl http://localhost:8002/health
```

- **First start** downloads the YarnGPT2 model (about 0.4B parameters) and the WavTokenizer files into the `/models` volume, using the URLs on the model card. Later starts reuse them.
- **Hardware:** a GPU makes synthesis many times faster. The image is CPU-only; for a GPU, swap the torch wheel index in the Dockerfile.
- **Measured speed (2026-10-17, `bench/`):** on a 2-thread laptop CPU (i5-8365U), YarnGPT2 took 23 to 88 seconds of compute per second of speech. The first Hausa sentence took about 5 minutes, and a full Hausa result about an hour. OpenBible VITS ran at 1.2 to 1.4 times real time, with the first sentence in 6 to 9 seconds. YarnGPT2 on CPU is too slow for live use. Re-run `bench/run_benchmark.py` on the target host.
- **CPU image (2026-10-18, `bench/run_benchmark_cpu_image.py`):** both engines in one process used 2.05 GB at peak. At 1, 2 and 4 threads:
  - Hausa (OpenBible): first sentence 20.5, 10.3 and 7.3 s; 2.33, 1.49 and 1.11 times real time.
  - English (Kokoro `af_heart`): first sentence 7.2, 4.7 and 3.6 s; 2.29, 1.91 and 1.36 times real time.
- **Pinning:** pin `YARNGPT_REF` (a build argument) to a reviewed commit of `github.com/saheedniyi02/yarngpt` for reproducible builds.
- **Workers:** the service runs one uvicorn worker, since each engine keeps one model in memory and generates one sentence at a time. Scale with more containers, not more workers.

## API

All endpoints except `/health` require `Authorization: Bearer $TTS_SERVICE_KEY` when that key is set.

- `GET /health` lists the enabled engines and whether ffmpeg is present.
- `GET /voices` lists the voices and licence for each engine.
- `POST /synthesize`:

  ```json
  {"engine": "yarngpt", "lang": "ha", "voice": "hausa_female1", "rate": 0.9,
   "format": "mp3", "bitrate_kbps": 40,
   "segments": [{"text": "Ganyen sun yi ruwan ƙasa,", "pause_ms": 250}, {"text": "kuma suna bushewa.", "pause_ms": 450}]}
  ```

  It returns `audio_base64`, `mime`, `size_bytes`, `duration_sec`, `segments[{index, start, end}]`, `engine`, `voice`, `license` and `latency_ms`.

  - **Limits:** up to 80 segments of up to 300 characters each.
  - **Errors:** an unknown voice or language returns 422, and Laravel does not retry it. A model failure returns 503, and Laravel retries it.

## Tests

```bash
pip install -r requirements.txt -r requirements-dev.txt
pytest tests/ -v
```

The tests use a fake engine, so no model weights are needed. The real MP3 encoding test runs when ffmpeg is installed, which it is in CI (`.github/workflows/tts-service-tests.yml`).
