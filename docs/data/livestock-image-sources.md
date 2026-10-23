# Livestock image set: proposed sources (for approval)

**Purpose:** a labelled livestock photo set to measure accuracy (`ai-engine/eval/run_eval.py`) and consistency. The target is at least 20 photos per condition. It is not for training the model: the engine uses Claude and does not train on these photos.

**Rules:**
- No photo is used without a licence or written consent.
- No scraping.
- Licences that forbid commercial use (CC BY-NC) are excluded, because MSAS FarmAI is a commercial service.
- Every photo goes through the admin page (`/admin/training-images`), which records its source, its licence or consent reference, and who confirmed the label.

## 1. Public datasets

| # | Dataset | What it has | Licence | Verified | Use for | Caveats |
|---|---|---|---|---|---|---|
| 1 | **Lumpy Skin Images Dataset.** Kumar & Shastri, University of Jammu, Mendeley Data, 2022. DOI [10.17632/w36hpf86j2.1](https://data.mendeley.com/datasets/w36hpf86j2/1) | 324 lumpy skin + 700 normal skin cattle images, 256×256 PNG | **CC BY 4.0** (attribution required) | yes: licence read on the dataset page, 2026-10-18 | LSD vs healthy skin: the miss seen live on 2026-10-13 | Small 256 px images. The source of the photos is not stated, so some may come from the web. Indian cattle breeds. Use for eval only, and spot-check before use. |
| 2 | **Machine Learning Dataset for Poultry Diseases Diagnostics: PCR annotated.** Machuve et al., NM-AIST Tanzania, [Zenodo 5801834](https://zenodo.org/records/5801834) | 1,255 chicken droppings photos (coccidiosis 373, healthy 347, salmonella 349, Newcastle 186), confirmed by **PCR**, from farms in Arusha and Kilimanjaro | **CC BY 4.0** | yes: licence read on the Zenodo record, 2026-10-18 | poultry droppings scans (`livestock` mode) | Laboratory-confirmed labels and East African smallholder farms: the closest match to Katsina of anything found. |
| 3 | **Roboflow Universe "CowSense"** (VisionVet). [Link](https://universe.roboflow.com/visionvet/cowsense) | about 7,900 images: foot disease, lumpy skin, mouth disease, healthy | listed as **CC BY 4.0** in search results | **no**: the page refused an automated check | FMD and LSD | Roboflow projects are often collected from the web. The licence the uploader chose does not prove they held the rights. Only after opening the page yourself, confirming the licence, and checking a sample for watermarks or stock photos. |
| 4 | Other Roboflow LSD sets ("Cattle lumpy disease", 51 images; "Lumpy_skin_disease"; "lumpy_folder") | small LSD sets | listed as CC BY 4.0 | no | — | **Not recommended**: small, unknown sources, and they probably repeat the larger sets. |

Not found with an open licence: labelled photo sets for **PPR, orf, mange or ringworm** in goats and sheep. The PPR leaflets in India's ICAR repository are copyrighted extension material, not a dataset. For small ruminants, field photos (section 2) are the only clean source.

**Attribution** for 1–3, kept in the manifest and in `docs/data/ATTRIBUTION.md` once approved:
- dataset title;
- authors;
- DOI or link;
- "CC BY 4.0";
- "images not modified" (or the changes made).

## 2. Field photos from vets and extension officers in Katsina

The best source for the conditions farmers actually photograph in Katsina: PPR, orf, mange, ringworm, dermatophilosis, LSD, FMD, and Newcastle disease in village chickens.

**Who:**
- State Ministry of Agriculture and Natural Resources veterinary officers;
- vets at the Katsina State veterinary clinics;
- NAERLS / ADP extension officers;
- private vets who already work with MSAS.

**How:**
1. **Written consent, every time.** A one-page form signed by the **animal owner**, and by the **photographer** if different. In Hausa and English. It covers:
   - "photos may be used by MSAS to test and improve its AI and in reports";
   - no faces, no names published;
   - consent can be withdrawn.
   The form number is entered on upload, and the scanned form can be attached.
2. **Label confirmed by the vet**, with how sure: lab-confirmed, clinical diagnosis by a vet, or suspected. Only lab-confirmed or vet-diagnosed photos go into the accuracy set.
3. **Photo rules:**
   - the phone's normal camera, in daylight;
   - the lesion filling about a third of the frame, plus one wide shot of the whole animal;
   - no people's faces or ear-tag numbers in the frame.
4. **Privacy:**
   - photos are stored privately, never public;
   - the page removes GPS and other EXIF data on upload;
   - only the state and LGA are kept.
5. **Thanks, not payment** (unless you decide otherwise): credit in reports, and early access to results. Paying per photo would be a spending decision.

**Suggested first target:** 20 photos for each of LSD, FMD, PPR, orf, mange, ringworm, dermatophilosis, and healthy cattle, goats and sheep. Plus the poultry set from dataset 2.

## 3. Decisions for you

1. Approve datasets **1 and 2** (verified CC BY 4.0) for the evaluation set?
2. Dataset **3**: open the page and confirm, or skip?
3. Approve the field-photo programme and the consent form wording (I will draft the form in Hausa and English, for a native-speaker check).
4. Which partner organisations to approach first.
