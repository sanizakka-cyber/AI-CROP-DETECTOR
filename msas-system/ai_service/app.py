from flask import Flask, request, jsonify
import base64
import os
import requests
from PIL import Image
import io

app = Flask(__name__)

# ──────────────────────────────────────────────────────────────────────────────
# CONFIG
# ──────────────────────────────────────────────────────────────────────────────
GOOGLE_VISION_API_KEY = os.environ.get("GOOGLE_VISION_API_KEY", "")
HF_API_KEY = os.environ.get("HF_API_KEY", "")  # Hugging Face free token

# Hugging Face plant disease model (free public endpoint)
PLANT_DISEASE_MODEL = "linkanjarad/mobilenet_v2_1.0_224-plant-disease-identification"
HF_INFERENCE_URL = f"https://api-inference.huggingface.co/models/{PLANT_DISEASE_MODEL}"

# Valid agricultural object labels from Google Vision
VALID_PLANT_LABELS = {
    "leaf", "plant", "tree", "flower", "fruit", "vegetable", "crop",
    "grass", "agriculture", "root", "stem", "seed", "soil", "mango",
    "tomato", "maize", "corn", "cassava", "rice", "wheat", "citrus",
    "sugarcane", "soybean", "groundnut", "pepper", "yam", "cocoa",
    "potato", "spinach", "lettuce", "fungi", "moss", "shrub",
    "branch", "petal", "blossom", "herb"
}

VALID_ANIMAL_LABELS = {
    "cattle", "cow", "goat", "sheep", "ram", "poultry", "chicken",
    "bird", "pig", "horse", "donkey", "rabbit", "livestock",
    "animal", "stool", "feces", "skin", "hoof", "snout", "fur",
    "feather", "beak", "wildlife", "mammal"
}

CONFIDENCE_THRESHOLD = 0.50  # 50% minimum AI confidence
MIN_IMAGE_WIDTH = 100
MIN_IMAGE_HEIGHT = 100


# ──────────────────────────────────────────────────────────────────────────────
# HEALTH CHECK
# ──────────────────────────────────────────────────────────────────────────────
@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "MSAS AI Engine Online", "version": "2.0"})


# ──────────────────────────────────────────────────────────────────────────────
# MAIN SCAN ENDPOINT
# ──────────────────────────────────────────────────────────────────────────────
@app.route("/scan", methods=["POST"])
def scan():
    if "image" not in request.files:
        return jsonify({"success": False, "error": "No image file provided."}), 400

    scan_type = request.form.get("scan_type", "plant")  # 'plant' or 'animal'
    image_file = request.files["image"]

    # ── STEP 1: Image Quality Validation ──────────────────────────────────────
    try:
        img_bytes = image_file.read()
        img = Image.open(io.BytesIO(img_bytes))
        width, height = img.size

        if width < MIN_IMAGE_WIDTH or height < MIN_IMAGE_HEIGHT:
            return jsonify({
                "success": False,
                "rejected": True,
                "error": "Image resolution too low. Please upload a clearer, closer image."
            }), 422

    except Exception:
        return jsonify({
            "success": False,
            "rejected": True,
            "error": "Unable to process image. Please upload a valid JPG or PNG file."
        }), 422

    # ── STEP 2: Object Classification via Google Cloud Vision ─────────────────
    if GOOGLE_VISION_API_KEY:
        classification_result = classify_with_google_vision(img_bytes)
        if classification_result["error"]:
            return jsonify({"success": False, "error": classification_result["error"]}), 500

        detected_labels = classification_result["labels"]
        detected_label_names = {l.lower() for l in detected_labels}

        is_plant = bool(VALID_PLANT_LABELS & detected_label_names)
        is_animal = bool(VALID_ANIMAL_LABELS & detected_label_names)

        if scan_type == "plant" and not is_plant:
            top_label = detected_labels[0] if detected_labels else "Non-agricultural object"
            return jsonify({
                "success": False,
                "rejected": True,
                "detected_object": top_label,
                "error": f"Invalid image. Detected '{top_label}'. Please upload a clear image of a plant leaf, stem, root, fruit, or crop field only."
            }), 422

        if scan_type == "animal" and not is_animal:
            top_label = detected_labels[0] if detected_labels else "Non-agricultural object"
            return jsonify({
                "success": False,
                "rejected": True,
                "detected_object": top_label,
                "error": f"Invalid image. Detected '{top_label}'. Please upload an image of an animal, livestock, or animal symptom."
            }), 422

        top_detected = detected_labels[0] if detected_labels else "Agricultural Object"
    else:
        # Fallback: skip Vision API if key not configured
        top_detected = "Agricultural Object (unverified)"

    # ── STEP 3: Disease Detection via Hugging Face ────────────────────────────
    if scan_type == "plant":
        diagnosis = classify_plant_disease(img_bytes)
    else:
        diagnosis = classify_animal_symptom(img_bytes)

    if diagnosis.get("error"):
        return jsonify({"success": False, "error": diagnosis["error"]}), 500

    # ── STEP 4: Apply Confidence Threshold ────────────────────────────────────
    confidence = diagnosis.get("confidence_score", 0)
    if confidence < CONFIDENCE_THRESHOLD:
        return jsonify({
            "success": False,
            "rejected": False,
            "uncertain": True,
            "detected_object": top_detected,
            "confidence_score": round(confidence * 100, 1),
            "error": "Unable to confirm diagnosis with sufficient confidence. Please retake the image from a closer angle in good lighting, or consult a field expert."
        }), 200

    return jsonify({
        "success": True,
        "rejected": False,
        "detected_object": top_detected,
        "scan_type": scan_type,
        "disease_name": diagnosis["disease_name"],
        "confidence_score": round(confidence * 100, 1),
        "cause": diagnosis["cause"],
        "urgency_level": diagnosis["urgency_level"],
        "first_aid_steps": diagnosis["first_aid_steps"],
        "recommended_medication": diagnosis["recommended_medication"],
        "vet_referral_advice": diagnosis["vet_referral_advice"],
        "disclaimer": "This AI result is advisory only. Always confirm diagnosis with a certified Agronomist or Veterinary Doctor before applying treatments."
    })


# ──────────────────────────────────────────────────────────────────────────────
# GOOGLE VISION OBJECT CLASSIFICATION
# ──────────────────────────────────────────────────────────────────────────────
def classify_with_google_vision(img_bytes):
    try:
        encoded = base64.b64encode(img_bytes).decode("utf-8")
        url = f"https://vision.googleapis.com/v1/images:annotate?key={GOOGLE_VISION_API_KEY}"
        payload = {
            "requests": [{
                "image": {"content": encoded},
                "features": [
                    {"type": "LABEL_DETECTION", "maxResults": 15},
                    {"type": "SAFE_SEARCH_DETECTION"}
                ]
            }]
        }
        resp = requests.post(url, json=payload, timeout=10)
        data = resp.json()
        labels = [a["description"] for a in data["responses"][0].get("labelAnnotations", [])]
        return {"labels": labels, "error": None}
    except Exception as e:
        return {"labels": [], "error": str(e)}


# ──────────────────────────────────────────────────────────────────────────────
# PLANT DISEASE VIA HUGGING FACE
# ──────────────────────────────────────────────────────────────────────────────
def classify_plant_disease(img_bytes):
    try:
        headers = {}
        if HF_API_KEY:
            headers["Authorization"] = f"Bearer {HF_API_KEY}"

        response = requests.post(
            HF_INFERENCE_URL,
            headers=headers,
            data=img_bytes,
            timeout=30
        )

        if response.status_code != 200:
            return {"error": f"AI model returned status {response.status_code}. Model may be loading, try again in 30 seconds."}

        results = response.json()
        if not results or not isinstance(results, list):
            return {"error": "AI model returned unexpected format."}

        top_result = results[0]
        raw_label = top_result.get("label", "unknown")
        confidence = top_result.get("score", 0)

        return parse_plant_label(raw_label, confidence)

    except requests.exceptions.Timeout:
        return {"error": "AI service timed out. Please try again."}
    except Exception as e:
        return {"error": str(e)}


def parse_plant_label(raw_label, confidence):
    """
    PlantVillage model labels follow the format:
    'Tomato___Late_blight' or 'Apple___healthy'
    """
    label = raw_label.replace("___", " - ").replace("_", " ")
    parts = raw_label.split("___")
    plant_name = parts[0].replace("_", " ") if len(parts) > 0 else "Unknown Plant"
    condition = parts[1].replace("_", " ") if len(parts) > 1 else raw_label

    is_healthy = "healthy" in condition.lower()

    if is_healthy:
        return {
            "disease_name": f"{plant_name} — Healthy",
            "confidence_score": confidence,
            "cause": "No disease detected.",
            "urgency_level": "None",
            "first_aid_steps": "No action needed. Continue regular farming practices.",
            "recommended_medication": "No treatment required.",
            "vet_referral_advice": "Monitor regularly and maintain good crop hygiene."
        }

    treatment_map = {
        "late blight": ("Copper-based fungicide or Metalaxyl spray", "High"),
        "early blight": ("Chlorothalonil or Mancozeb fungicide", "Medium"),
        "leaf scorch": ("Remove infected leaves; apply potassium fertilizer", "Medium"),
        "bacterial spot": ("Copper hydroxide spray; avoid overhead irrigation", "Medium"),
        "mosaic virus": ("Remove infected plants; control aphids with insecticide", "High"),
        "powdery mildew": ("Sulfur-based fungicide or Neem oil", "Medium"),
        "rust": ("Propiconazole or Tebuconazole fungicide", "Medium"),
        "anthracnose": ("Copper fungicide; remove infected fruits", "Medium"),
        "black rot": ("Captan fungicide; improve drainage", "High"),
        "cercospora": ("Chlorothalonil fungicide", "Low"),
        "leaf mold": ("Ventilation improvement; Chlorothalonil spray", "Medium"),
        "target spot": ("Azoxystrobin fungicide", "Medium"),
        "spider mites": ("Miticide spray; increase humidity", "Medium"),
        "nutrient deficiency": ("Apply balanced NPK fertilizer; soil test recommended", "Low"),
    }

    matched_treatment = "Consult a certified Agronomist for specific treatment."
    matched_urgency = "Medium"
    for key, (treat, urgency) in treatment_map.items():
        if key in condition.lower():
            matched_treatment = treat
            matched_urgency = urgency
            break

    return {
        "disease_name": label,
        "confidence_score": confidence,
        "cause": f"Detected symptoms consistent with {condition} in {plant_name}.",
        "urgency_level": matched_urgency,
        "first_aid_steps": "Isolate affected plants immediately. Remove and dispose of infected leaves/fruit away from field.",
        "recommended_medication": matched_treatment,
        "vet_referral_advice": "If spread exceeds 30% of crop, contact a certified Agronomist for field inspection."
    }


# ──────────────────────────────────────────────────────────────────────────────
# ANIMAL SYMPTOM DETECTION (rule-based on Vision labels when no animal model)
# ──────────────────────────────────────────────────────────────────────────────
def classify_animal_symptom(img_bytes):
    """
    For animal diagnosis, we use Google Vision label context + symptom rules.
    A dedicated livestock disease model can be plugged in here when available.
    """
    if not GOOGLE_VISION_API_KEY:
        return {
            "disease_name": "Unverified Animal Symptom",
            "confidence_score": 0.0,
            "error": "Google Vision API key required for animal diagnosis."
        }

    result = classify_with_google_vision(img_bytes)
    if result["error"]:
        return {"error": result["error"]}

    labels = [l.lower() for l in result["labels"]]
    label_str = " ".join(labels)

    animal_rules = [
        (["stool", "feces", "diarrhea", "manure"], {
            "disease_name": "Suspected Intestinal Parasites / Diarrhea",
            "cause": "Abnormal stool color or consistency detected — possible worm infestation or bacterial infection.",
            "urgency_level": "High",
            "first_aid_steps": "Isolate the animal. Provide clean drinking water only. Withhold solid feed temporarily.",
            "recommended_medication": "Broad-spectrum dewormer (Albendazole or Ivermectin). Oral rehydration solution.",
            "vet_referral_advice": "Stool sample analysis by a Vet is strongly recommended within 24 hours.",
            "confidence_score": 0.78
        }),
        (["skin", "lesion", "wound", "sore", "rash", "spot", "scab"], {
            "disease_name": "Suspected Skin Infection / Dermatitis",
            "cause": "Visible skin lesion or wound detected — possible bacterial or fungal skin infection.",
            "urgency_level": "Medium",
            "first_aid_steps": "Clean wound with antiseptic solution. Keep animal in dry, clean housing.",
            "recommended_medication": "Topical antiseptic (Betadine). Consult Vet for oral antibiotics if infected.",
            "vet_referral_advice": "If lesions spread or animal shows fever, seek Veterinary attention immediately.",
            "confidence_score": 0.72
        }),
        (["hoof", "foot", "leg", "lameness"], {
            "disease_name": "Suspected Foot Rot (Infectious Pododermatitis)",
            "cause": "Hoof or lower limb abnormality detected — possible bacterial foot rot from wet/muddy conditions.",
            "urgency_level": "High",
            "first_aid_steps": "Move animal to dry ground. Clean hoof with warm water and antiseptic.",
            "recommended_medication": "Penicillin or Oxytetracycline (consult Vet for dosage). Hoof-bath with Zinc Sulfate.",
            "vet_referral_advice": "Immediate Veterinary inspection required if lameness persists over 24 hours.",
            "confidence_score": 0.81
        }),
        (["eye", "discharge", "mucus", "nose", "mouth"], {
            "disease_name": "Suspected Respiratory Infection / Pinkeye",
            "cause": "Eye, nose or mouth discharge detected — possible viral or bacterial respiratory infection.",
            "urgency_level": "Medium",
            "first_aid_steps": "Isolate animal from herd. Keep in shaded, ventilated area away from dust.",
            "recommended_medication": "Oxytetracycline eye spray or oral antibiotic as prescribed by Vet.",
            "vet_referral_advice": "Veterinary diagnosis needed within 48 hours to prevent herd spread.",
            "confidence_score": 0.69
        }),
    ]

    for keywords, diagnosis in animal_rules:
        if any(kw in label_str for kw in keywords):
            return diagnosis

    # No specific symptom matched
    return {
        "disease_name": "No Specific Symptom Identified",
        "confidence_score": 0.0,
        "error": "Could not identify specific animal symptoms. Please upload a clearer image focused on the affected body part (stool, skin, hoof, eye, or mouth)."
    }


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5001))
    app.run(host="0.0.0.0", port=port, debug=False)
