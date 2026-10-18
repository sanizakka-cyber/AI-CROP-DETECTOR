#!/bin/sh
# Downloads model files into $MODELS_DIR (a volume), once.
#  - yarngpt: the WavTokenizer files from the YarnGPT2 model card (the model
#    itself is fetched by transformers into $HF_HOME on first use).
#  - openbible: the VITS-OpenBible checkpoints for Hausa, Yoruba and Igbo.
set -e
MODELS_DIR="${MODELS_DIR:-/models}"
mkdir -p "$MODELS_DIR"
case ",${TTS_ENGINES:-yarngpt}," in *,yarngpt,*)
  YAML="$MODELS_DIR/wavtokenizer_mediumdata_frame75_3s_nq1_code4096_dim512_kmeans200_attn.yaml"
  CKPT="$MODELS_DIR/wavtokenizer_large_speech_320_24k.ckpt"
  [ -s "$YAML" ] || wget -q -O "$YAML" "https://huggingface.co/novateur/WavTokenizer-medium-speech-75token/resolve/main/wavtokenizer_mediumdata_frame75_3s_nq1_code4096_dim512_kmeans200_attn.yaml"
  [ -s "$CKPT" ] || gdown 1-ASeEkrn4HY49yZWHTASgfGFNXdVnLTt -O "$CKPT"
  ;;
esac
case ",${TTS_ENGINES:-yarngpt}," in *,openbible,*)
  # Each checkpoint is about 950 MB; at launch only Hausa has audio.
  # Set OPENBIBLE_LANGS="Hausa Yoruba Igbo" for review packs in all three.
  for LANG_DIR in ${OPENBIBLE_LANGS:-Hausa}; do
    DIR="$MODELS_DIR/openbible/$LANG_DIR"
    mkdir -p "$DIR"
    for F in config.json speakers.pth model_last.pth; do
      [ -s "$DIR/$F" ] || wget -q -c -O "$DIR/$F" "https://huggingface.co/multilingual-tts/VITS-OpenBible-$LANG_DIR/resolve/main/$F"
    done
  done
  ;;
esac
case ",${TTS_ENGINES:-yarngpt}," in *,kokoro,*)
  # Kokoro-82M weights and the English voices the service offers, into
  # $HF_HOME (on the /models volume), so no request waits on a download.
  python -c "from huggingface_hub import hf_hub_download as d; r='hexgrad/Kokoro-82M'; [d(r, f) for f in ['config.json', 'kokoro-v1_0.pth', 'voices/af_heart.pt', 'voices/af_bella.pt', 'voices/bf_emma.pt']]"
  ;;
esac
exec "$@"
