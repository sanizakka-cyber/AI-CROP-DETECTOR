#!/bin/sh
# Downloads the WavTokenizer files YarnGPT2 needs into $MODELS_DIR (a volume),
# once. URLs are the ones on the YarnGPT2 model card. The model itself is
# fetched by transformers into $HF_HOME on first use.
set -e
MODELS_DIR="${MODELS_DIR:-/models}"
mkdir -p "$MODELS_DIR"
YAML="$MODELS_DIR/wavtokenizer_mediumdata_frame75_3s_nq1_code4096_dim512_kmeans200_attn.yaml"
CKPT="$MODELS_DIR/wavtokenizer_large_speech_320_24k.ckpt"
case ",${TTS_ENGINES:-yarngpt}," in *,yarngpt,*)
  [ -s "$YAML" ] || wget -q -O "$YAML" "https://huggingface.co/novateur/WavTokenizer-medium-speech-75token/resolve/main/wavtokenizer_mediumdata_frame75_3s_nq1_code4096_dim512_kmeans200_attn.yaml"
  [ -s "$CKPT" ] || gdown 1-ASeEkrn4HY49yZWHTASgfGFNXdVnLTt -O "$CKPT"
  ;;
esac
exec "$@"
