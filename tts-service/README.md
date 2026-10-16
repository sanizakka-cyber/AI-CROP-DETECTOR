# MSAS FarmAI speech service

Self-hosted text-to-speech for "Listen to your result". Laravel sends text that is already translated, cleaned and split into sentences. This service speaks each sentence, joins them with pauses, applies the speaking rate, and returns one small mono MP3 plus each sentence's start and end time. The app uses those times to highlight the sentence being spoken.

The feature as a whole is described in [`docs/listen-to-result.md`](../docs/listen-to-result.md).

## Voices

| Engine | Languages | License | Source |
|---|---|---|---|
| `yarngpt` (default) | English (Nigerian accent), Hausa, Yoruba, Igbo | Apache-2.0 | [saheedniyi/YarnGPT2](https://huggingface.co/saheedniyi/YarnGPT2) |
| `farmerline` (off) | Hausa | CC-BY-4.0, credit "Hausa voice: FarmerlineML" | [FarmerlineML/main_hausa_TTS](https://huggingface.co/FarmerlineML/main_hausa_TTS) |

Voice names are copied exactly from the YarnGPT2 model card, including its spelling (`hausa_feamle1`, `yoruba_feamle1`). The card lists them in order of quality, best first.

- `farmerline` is not enabled by default. Its model card does not say which model it was fine-tuned from. It has the size and architecture of Meta's MMS Hausa model, which is CC-BY-NC (non-commercial). Confirm its provenance before enabling it.
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
- **Pinning:** pin `YARNGPT_REF` (a build argument) to a reviewed commit of `github.com/saheedniyi02/yarngpt` for reproducible builds.
- **Workers:** the service runs one uvicorn worker, since each engine keeps one model in memory and generates one sentence at a time. Scale with more containers, not more workers.

## API

All endpoints except `/health` require `Authorization: Bearer $TTS_SERVICE_KEY` when that key is set.

- `GET /health` lists the enabled engines and whether ffmpeg is present.
- `GET /voices` lists the voices and licence for each engine.
- `POST /synthesize`:

  ```json
  {"engine": "yarngpt", "lang": "ha", "voice": "hausa_feamle1", "rate": 0.9,
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
