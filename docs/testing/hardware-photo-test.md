# Phone photo test: HEIF/HEIC on a real Android phone and an iPhone

**About 30 minutes. Uses 6 scans from your account.** Use your farmer test account. Take screenshots where it says 📸.

**You need:**
- the Android phone with the MSAS FarmAI app, **v1.4.8 build 24** (Profile → bottom of the page shows the version);
- an iPhone with Safari;
- one healthy-looking leaf and one diseased leaf (or a printed photo of one);
- daylight.

## A. Android phone, with HEIF capture on

1. Open the phone's **Camera** app → **Settings** (⚙️) → **Picture formats** (on Samsung: **Advanced picture options**) → turn **High efficiency pictures (HEIF)** **ON**.
   - On other phones it may be called "Save as HEIF" or "HEIC".
   - If there is no such option, write "no HEIF option" and the phone model, and skip to step 9.
2. Take one photo of the diseased leaf with the Camera app (not the MSAS app).
   - 📸 Open it in Gallery → **Details** (ⓘ), which shows the file name ending in `.heic` or `.heif`.
3. Open **MSAS FarmAI** → **Scan** → **Crop Scan**.
4. Press **Take Picture**, photograph the diseased leaf, and confirm.
   - 📸 The preview, before you submit.
5. Submit. Watch the upload. 📸 The upload percentage while it shows.
6. 📸 The **result screen** (top), then scroll and 📸 the **Diagnostic Details** card.
   - Check that the **Severity** in the coloured badge at the top matches **Severity** in the details. Write down both.
7. Go back → **Crop Scan** → **Select Picture** → choose the `.heic` photo from step 2 → submit.
   - 📸 The result screen.
8. Write down for steps 6 and 7:
   - the diagnosis name;
   - the confidence %;
   - whether it says "Not certain";
   - any error message, word for word.
9. Turn HEIF **OFF** again in the Camera settings, take one normal photo, and scan it with **Select Picture**. 📸 The result.

## B. iPhone, Safari, web upload

10. On the iPhone: **Settings** → **Camera** → **Formats** → **High Efficiency**. That is HEIC, the iPhone default.
11. In **Safari**, open **https://www.msasagro.com**, sign in, then go to **AI Scan** (the scan page).
12. Choose **Crop / Plant**, tap the photo box → **Take Photo**, photograph the diseased leaf → **Use Photo** → **Analyze**.
    - 📸 The page while it uploads, then 📸 the result in **History** (top of the newest card).
13. Tap the photo box again → **Photo Library** → choose a photo you took earlier with the iPhone Camera app (it is HEIC) → **Analyze**.
    - 📸 The result.
14. On the newest result, tap **Download Report** (PDF). 📸 The top of the report.
    - Check the severity there matches the History card.
15. Write down for steps 12 and 13: the diagnosis, confidence %, "Not certain" or not, and any error, word for word.

## What counts as a pass

- Every upload finishes. No "unsupported file", "invalid image" or blank result.
- The result is a diagnosis, "Not certain", or a clear "retake the photo" message. Never an error page.
- The **same photo** gives the **same kind of answer** on Android and iPhone.
- Severity is the **same** in the badge, the details, History and the PDF.

## Where to send the results

1. Copy every screenshot and your notes into this folder on the office PC: `release_v1.4.8_build24\evidence\hardware\`. Name the files by step number, for example `A6-result-top.png` or `B13-library.png`.
2. Add a file `notes.txt` with:
   - the phone models;
   - Android and iOS versions;
   - the step-8 and step-15 notes.
3. Then tell me "hardware results are in the folder", and I will check them against the web, the app and the PDF.

Do not post the screenshots in a public GitHub issue: they show your account.
