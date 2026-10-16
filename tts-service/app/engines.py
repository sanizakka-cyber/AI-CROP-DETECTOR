"""
Speech engines behind one interface: synth(text, lang, voice) -> (samples, sample_rate).

Models load lazily on first use, so the service starts (and its tests run)
without the weights. Usage follows each model card verbatim; see README for
the sources.
"""
import os
import threading

import numpy as np

# License of every engine this service can run. Laravel mirrors this in
# config/speech.php. Non-commercial models are refused unless explicitly
# allowed for local experiments (never in production).
LICENSES = {
    "yarngpt":    {"license": "Apache-2.0", "commercial_use": True,
                   "source": "https://huggingface.co/saheedniyi/YarnGPT2"},
    "farmerline": {"license": "CC-BY-4.0", "commercial_use": True,
                   "credit": "Hausa voice: FarmerlineML",
                   "source": "https://huggingface.co/FarmerlineML/main_hausa_TTS",
                   "note": "Base model not stated on the model card; confirm it is not a fine-tune of a CC-BY-NC model before production use."},
}

# Voice names exactly as listed on the YarnGPT2 model card, including its
# spelling ("feamle"). They are passed to the model as-is.
YARNGPT_VOICES = {
    "en": ["idera", "chinenye", "jude", "emma", "umar", "joke", "zainab", "osagie", "remi", "tayo"],
    "yo": ["yoruba_male2", "yoruba_female2", "yoruba_feamle1"],
    "ig": ["igbo_female2", "igbo_male2", "igbo_female1"],
    "ha": ["hausa_feamle1", "hausa_female2", "hausa_male2", "hausa_male1"],
}
YARNGPT_LANG = {"en": "english", "yo": "yoruba", "ig": "igbo", "ha": "hausa"}


class EngineError(Exception):
    pass


class YarnGptEngine:
    name = "yarngpt"

    def __init__(self):
        self._lock = threading.Lock()
        self._model = None
        self._tok = None

    def voices(self) -> dict:
        return YARNGPT_VOICES

    def _load(self):
        if self._model is not None:
            return
        from transformers import AutoModelForCausalLM
        from yarngpt.audiotokenizer import AudioTokenizerV2

        models = os.environ.get("MODELS_DIR", "/models")
        self._tok = AudioTokenizerV2(
            "saheedniyi/YarnGPT2",
            os.path.join(models, "wavtokenizer_large_speech_320_24k.ckpt"),
            os.path.join(models, "wavtokenizer_mediumdata_frame75_3s_nq1_code4096_dim512_kmeans200_attn.yaml"),
        )
        self._model = AutoModelForCausalLM.from_pretrained("saheedniyi/YarnGPT2", torch_dtype="auto").to(self._tok.device)

    def synth(self, text: str, lang: str, voice: str):
        if lang not in YARNGPT_LANG:
            raise EngineError(f"yarngpt has no {lang} voice")
        if voice not in YARNGPT_VOICES[lang]:
            raise EngineError(f"unknown yarngpt voice {voice} for {lang}")
        with self._lock:  # one generation at a time: the model is not shared-state safe
            self._load()
            prompt = self._tok.create_prompt(text, lang=YARNGPT_LANG[lang], speaker_name=voice)
            input_ids = self._tok.tokenize_prompt(prompt)
            kwargs = dict(input_ids=input_ids, temperature=0.1, repetition_penalty=1.1, max_length=4000)
            if lang != "en":
                kwargs["num_beams"] = 5  # model card: beams help the local languages, not English
            output = self._model.generate(**kwargs)
            audio = self._tok.get_audio(self._tok.get_codes(output))
        return np.asarray(audio.squeeze().detach().cpu().numpy(), dtype=np.float32), 24000


class FarmerlineHausaEngine:
    name = "farmerline"

    def __init__(self):
        self._lock = threading.Lock()
        self._model = None
        self._tok = None

    def voices(self) -> dict:
        return {"ha": ["farmerline_hausa"]}

    def synth(self, text: str, lang: str, voice: str):
        if lang != "ha":
            raise EngineError("farmerline is Hausa only")
        with self._lock:
            if self._model is None:
                from transformers import AutoTokenizer, VitsModel
                self._tok = AutoTokenizer.from_pretrained("FarmerlineML/main_hausa_TTS")
                self._model = VitsModel.from_pretrained("FarmerlineML/main_hausa_TTS")
            import torch
            inputs = self._tok(text, return_tensors="pt")
            with torch.no_grad():
                wave = self._model(**inputs).waveform
        return np.asarray(wave.squeeze().cpu().numpy(), dtype=np.float32), int(self._model.config.sampling_rate)


def enabled_engines() -> dict:
    """Engines named in TTS_ENGINES (default: yarngpt), minus non-commercial ones."""
    allow_nc = os.environ.get("ALLOW_NON_COMMERCIAL_MODELS") == "1"
    wanted = [e.strip() for e in os.environ.get("TTS_ENGINES", "yarngpt").split(",") if e.strip()]
    available = {"yarngpt": YarnGptEngine, "farmerline": FarmerlineHausaEngine}
    out = {}
    for name in wanted:
        if name not in available:
            continue
        if not LICENSES[name]["commercial_use"] and not allow_nc:
            continue
        out[name] = available[name]()
    return out
