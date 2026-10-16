# MSAS FarmAI speech service (YarnGPT2, optional Farmerline Hausa).
# CPU image. Weights are downloaded on first start into the /models volume,
# not baked into the image.
FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1 \
    MODELS_DIR=/models HF_HOME=/models/hf TTS_ENGINES=yarngpt

RUN apt-get update \
 && apt-get install -y --no-install-recommends ffmpeg git wget ca-certificates \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /srv
COPY requirements.txt requirements-models.txt ./
RUN pip install --no-cache-dir torch torchaudio --index-url https://download.pytorch.org/whl/cpu \
 && pip install --no-cache-dir -r requirements.txt -r requirements-models.txt

# YarnGPT's AudioTokenizerV2 lives in its GitHub repo (model card: "git clone").
# Pin YARNGPT_REF to a reviewed commit for reproducible builds.
ARG YARNGPT_REF=main
RUN git clone https://github.com/saheedniyi02/yarngpt.git /opt/yarngpt \
 && git -C /opt/yarngpt checkout "$YARNGPT_REF"
ENV PYTHONPATH=/opt/yarngpt

COPY app ./app
COPY fetch_models.sh /usr/local/bin/fetch_models.sh
RUN chmod +x /usr/local/bin/fetch_models.sh \
 && useradd --create-home tts && mkdir -p /models && chown tts /models
USER tts

VOLUME ["/models"]
EXPOSE 8002
HEALTHCHECK --interval=30s --timeout=5s --start-period=120s CMD python -c "import urllib.request;urllib.request.urlopen('http://127.0.0.1:8002/health')"
ENTRYPOINT ["fetch_models.sh"]
# One worker: each engine keeps one model in memory and generates one sentence at a time.
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8002", "--workers", "1"]
