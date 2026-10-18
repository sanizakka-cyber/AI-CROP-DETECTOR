# Livestock evaluation set (lumpy skin disease, ringworm, mange, healthy)

This is for measuring livestock accuracy **per condition**, before and after any change, without tuning to the one photo that failed live (lumpy skin disease answered as ringworm or mange at 55% on 2026-10-13).

## What is needed

The set needs at least 20 real cattle photos for each condition: lumpy skin disease, ringworm (dermatophytosis), mange, and healthy skin.
- **Label:** each photo is labelled by a vet, not by the AI.
- **Format:** phone-camera photos, not dataset thumbnails, so they resemble what farmers upload.
- **Held out:** keep a few photos per condition aside, and don't look at them while changing prompts.

## Candidate sources (licence must be confirmed before use)

| Source | What it has | Notes |
|---|---|---|
| [Mendeley "Lumpy Skin Images Dataset"](https://data.mendeley.com/datasets/w36hpf86j2) | 324 lumpy skin, 700 normal | Already resized to 256×256 PNG, which is lower quality than a phone photo; licence not yet checked |
| [Roboflow Universe, cattle ringworm](https://universe.roboflow.com/cattle-diseasee-computer-vision-dataset/ringworm-w4wsj) | ringworm | CC BY 4.0; no description, so count and labelling are unknown |
| [CDC PHIL](https://phil.cdc.gov/Details.aspx?pid=26679) | a few calf dermatophytosis photos | public domain |
| Mange | none found openly | needs field photos from a vet or extension partner |

## Manifest and running it

Copy `manifest.livestock.example.csv` and point each row at a real image.

```bash
# Before a change: 3 runs per photo, which also measures consistency
ENGINE_URL=... ENGINE_API_KEY=... python eval/run_eval.py eval/livestock/manifest.csv --repeats 3 --out before.json
# After the change
ENGINE_URL=... ENGINE_API_KEY=... python eval/run_eval.py eval/livestock/manifest.csv --repeats 3 --out after.json
python eval/run_eval.py --compare before.json after.json
```

The report gives, for each condition:
- accuracy, and accuracy counting only the photos the AI answered;
- how many results the app would show as "Not certain" (below 65%);
- the most common wrong answers.

Across repeated runs it also gives:
- the share of photos that got the same answer every time;
- the average confidence spread.

The repeats matter. On 2026-10-16, the same tomato-leaf JPEG scanned 3 times gave Early blight at 68%, then Late blight at 78%, then Late blight at 78%. Single runs therefore can't show whether a change helped.
