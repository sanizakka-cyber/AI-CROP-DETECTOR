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
  for LANG_DIR in Hausa Yoruba Igbo; do
    DIR="$MODELS_DIR/openbible/$LANG_DIR"
    mkdir -p "$DIR"
    for F in config.json speakers.pth model_last.pth; do
      [ -s "$DIR/$F" ] || wget -q -c -O "$DIR/$F" "https://huggingface.co/multilingual-tts/VITS-OpenBible-$LANG_DIR/resolve/main/$F"
    done
  done
  ;;
esac
exec "$@"
