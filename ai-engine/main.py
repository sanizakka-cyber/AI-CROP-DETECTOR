import time
import random
from typing import List
from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from pydantic import BaseModel
import uvicorn

app = FastAPI(title="FarmAI Inference Engine", version="2.0.0")

# --- AI Models Mock/Stub ---
# In a real environment, you'd load .tflite models here:
# interpreter = tf.lite.Interpreter(model_path="models/maize_model.tflite")

class DiagnosisResult(BaseModel):
    primaryDiagnosis: str
    confidence: float
    severity: str
    likelyCauses: List[str]
    treatmentPlan: dict

INVALID_IMAGE_MESSAGE = "Invalid image detected. Please upload a clear image of a plant, livestock animal, or agricultural sample only."

VALID_PLANT_INPUTS = {
    "leaf", "leaf top", "leaf bottom", "stem", "root", "fruit", "flower",
    "seed", "crop", "soil", "soil symptom", "whole plant"
}

VALID_ANIMAL_INPUTS = {
    "goat", "ram", "cow", "cattle", "poultry", "chicken", "sheep",
    "animal stool", "feces", "stool", "saliva", "skin infection", "hoof",
    "mouth", "eye", "visual", "fecal", "behavioral", "comprehensive"
}

INVALID_HINTS = {
    "selfie", "human", "person", "shoe", "shoes", "car", "vehicle",
    "building", "phone", "laptop", "random", "object"
}

def _image_quality(images: List[UploadFile]):
    if not images:
        return {"status": "poor", "score": 0, "issues": ["No image was uploaded"]}
    issues = []
    score = 92
    for image in images:
        content_type = image.content_type or ""
        filename = (image.filename or "").lower()
        if not content_type.startswith("image/"):
            issues.append(f"{image.filename or 'file'} is not an image")
            score -= 40
        if any(hint in filename for hint in INVALID_HINTS):
            issues.append("Filename suggests a non-agricultural object")
            score -= 45
    score = max(0, min(score, 100))
    return {
        "status": "good" if score >= 75 else "needs_retake" if score >= 45 else "poor",
        "score": score,
        "issues": issues,
    }

def _classification_response(accepted, object_type, category, quality, reason=None):
    return {
        "accepted": accepted,
        "message": None if accepted else INVALID_IMAGE_MESSAGE,
        "objectType": object_type,
        "category": category,
        "quality": quality,
        "reason": reason,
        "modelStatus": "heuristic-validation-stub",
    }

async def validate_crop_image(crop_type: str, crop_part: str, images: List[UploadFile]):
    quality = _image_quality(images)
    crop = (crop_type or "").lower().strip()
    part = (crop_part or "crop").lower().strip()
    filenames = " ".join((image.filename or "").lower() for image in images)
    if any(hint in crop or hint in part or hint in filenames for hint in INVALID_HINTS):
      return _classification_response(False, "invalid", "non_agricultural", quality, "Non-agricultural object detected")
    if crop not in DISEASE_DB:
      return _classification_response(False, "unknown", "unsupported_crop", quality, "Unsupported or missing crop type")
    if not any(valid in part for valid in VALID_PLANT_INPUTS):
      return _classification_response(False, "unknown", "unsupported_plant_part", quality, "Unsupported plant input")
    if quality["score"] < 45:
      return _classification_response(False, "unknown", "poor_quality", quality, "Image quality is too low")
    return _classification_response(True, part, "plant", quality)

async def validate_livestock_image(animal_type: str, assessment_type: str, images: List[UploadFile]):
    quality = _image_quality(images)
    animal = (animal_type or "").lower().strip()
    assessment = (assessment_type or "").lower().strip()
    filenames = " ".join((image.filename or "").lower() for image in images)
    if any(hint in animal or hint in assessment or hint in filenames for hint in INVALID_HINTS):
      return _classification_response(False, "invalid", "non_agricultural", quality, "Non-agricultural object detected")
    if animal not in {"cattle", "cow", "goat", "ram", "sheep", "poultry", "chicken"}:
      return _classification_response(False, "unknown", "unsupported_animal", quality, "Unsupported or missing livestock type")
    if assessment not in VALID_ANIMAL_INPUTS:
      return _classification_response(False, "unknown", "unsupported_sample", quality, "Unsupported animal sample or symptom type")
    if assessment != "behavioral" and quality["score"] < 45:
      return _classification_response(False, "unknown", "poor_quality", quality, "Image quality is too low")
    return _classification_response(True, assessment, "animal", quality)

# Mock Database for Phase 2 (10+ Crops)
DISEASE_DB = {
    "maize": [
        {"name": "Northern Leaf Blight", "conf": 0.88, "sev": "moderate", "causes": ["Fungus: Exserohilum turcicum"]},
        {"name": "Nitrogen Deficiency", "conf": 0.82, "sev": "mild", "causes": ["Low soil nutrients"]},
        {"name": "Maize Streak Virus", "conf": 0.94, "sev": "severe", "causes": ["Leafhopper transmission"]}
    ],
    "tomato": [
        {"name": "Late Blight", "conf": 0.91, "sev": "severe", "causes": ["Phytophthora infestans"]},
        {"name": "Bacterial Wilt", "conf": 0.85, "sev": "emergency", "causes": ["Ralstonia solanacearum"]},
        {"name": "Tomato Leaf Miner", "conf": 0.89, "sev": "moderate", "causes": ["Tuta absoluta larvae"]}
    ],
    "rice": [
        {"name": "Rice Blast", "conf": 0.87, "sev": "severe", "causes": ["Magnaporthe oryzae"]},
        {"name": "Brown Spot", "conf": 0.83, "sev": "moderate", "causes": ["Cochliobolus miyabeanus"]}
    ],
    "cassava": [
        {"name": "Cassava Mosaic Disease", "conf": 0.92, "sev": "severe", "causes": ["Begomovirus"]},
        {"name": "Cassava Brown Streak", "conf": 0.88, "sev": "emergency", "causes": ["Ipomovirus"]}
    ],
    "yam": [
        {"name": "Yam Anthracnose", "conf": 0.86, "sev": "moderate", "causes": ["Colletotrichum gloeosporioides"]},
        {"name": "Yam Mosaic Virus", "conf": 0.84, "sev": "mild", "causes": ["Potyvirus"]}
    ],
    "beans": [
        {"name": "Bean Rust", "conf": 0.89, "sev": "moderate", "causes": ["Uromyces appendiculatus"]},
        {"name": "Common Blight", "conf": 0.91, "sev": "severe", "causes": ["Xanthomonas axonopodis"]}
    ],
    "soybeans": [
        {"name": "Soybean Rust", "conf": 0.93, "sev": "severe", "causes": ["Phakopsora pachyrhizi"]}
    ],
    "onions": [
        {"name": "Purple Blotch", "conf": 0.85, "sev": "moderate", "causes": ["Alternaria porri"]}
    ],
    "pepper": [
        {"name": "Pepper Veinal Mottle", "conf": 0.88, "sev": "moderate", "causes": ["Potyvirus"]}
    ],
    "millet": [
        {"name": "Downy Mildew", "conf": 0.90, "sev": "severe", "causes": ["Sclerospora graminicola"]}
    ]
}

@app.get("/")
async def root():
    return {"status": "online", "model_version": "v2.0-alpha", "supported_crops": list(DISEASE_DB.keys())}

@app.get("/health")
async def health():
    return {
        "status": "ok",
        "service": "MSAS FarmAI Inference Engine",
        "model_version": "v2.0-alpha",
        "mode": "mock",
        "supported_crops": list(DISEASE_DB.keys()),
        "supported_livestock": ["cattle", "goat", "sheep", "chicken"],
    }

@app.get("/models")
async def models():
    return {
        "crop": {
            "status": "mock",
            "target_accuracy": 0.85,
            "supported_inputs": ["leaf", "stem", "whole_plant", "gallery_upload"],
            "architecture_target": "MobileNetV3 or EfficientNet Lite",
        },
        "livestock": {
            "status": "mock",
            "target_accuracy": 0.80,
            "supported_inputs": ["fecal_image", "visual_symptom_image", "behavioral_questionnaire"],
            "architecture_target": "Multi-modal mobile-first classifier",
        },
    }

@app.post("/predict/crop")
async def predict_crop(
    cropType: str = Form(...),
    images: List[UploadFile] = File(...)
):
    validation = await validate_crop_image(cropType, "crop", images)
    if not validation["accepted"]:
        raise HTTPException(status_code=422, detail=validation)

    # Simulate inference delay
    time.sleep(1.2)
    
    crop = cropType.lower()
    if crop not in DISEASE_DB:
        crop = "maize" # Fallback
        
    # In reality, you'd run:
    # img = preprocess(images[0])
    # prediction = model.predict(img)
    
    # Mocking the AI output for Phase 2 demonstration
    result = random.choice(DISEASE_DB[crop])
    
    return {
        "aiResult": {
            "primaryDiagnosis": result["name"],
            "confidence": round(result["conf"] * 100 + random.uniform(-5, 5), 1),
            "severity": result["sev"],
            "likelyCauses": result["causes"],
            "contagionRisk": "high" if result["sev"] in ["severe", "emergency"] else "medium",
            "needsVetVisit": False,
            "needsExpertReview": result["sev"] in ["severe", "emergency"],
            "expertType": "agronomist",
            "validation": validation,
        },
        "treatmentPlan": {
            "immediateActions": [
                {"action": f"Isolate affected {crop} plants", "actionHa": "Ware amfanin gona da abin ya shafa"},
                {"action": "Check irrigation drainage", "actionHa": "Duba magudanar ruwa"}
            ],
            "organicRemedies": [
                {"remedy": "Neem oil spray", "dosage": "5ml/L", "method": "Foliar", "timing": "Weekly"}
            ],
            "chemicalTreatments": [
                {"product": "Standard Fungicide", "dosage": "2g/L", "method": "Spray", "timing": "Every 10 days", "cost": "N2,500"}
            ],
            "prevention": [
                {"measure": "Rotate crops annually"},
                {"measure": "Use healthy seedlings and monitor affected plots every 3 days"}
            ],
            "consultation": {
                "recommended": result["sev"] in ["severe", "emergency"],
                "expertType": "agronomist",
                "message": "Professional consultation is recommended." if result["sev"] in ["severe", "emergency"] else "Monitor for 7 days and consult an agronomist if symptoms spread.",
                "callNumber": "08129582957",
                "whatsapp": "https://wa.me/2348129582957"
            }
        }
    }

@app.post("/predict/livestock")
async def predict_livestock(
    animalType: str = Form(...),
    assessmentType: str = Form(...),
    images: List[UploadFile] = File(...)
):
    validation = await validate_livestock_image(animalType, assessmentType, images)
    if not validation["accepted"]:
        raise HTTPException(status_code=422, detail=validation)

    time.sleep(1.5)
    
    # Simple logic for Phase 2 demo
    diagnosis = "Foot Rot" if assessmentType == "visual" else "Internal Parasites"
    severity = "severe" if "cattle" in animalType.lower() else "moderate"
    
    return {
        "aiResult": {
            "primaryDiagnosis": diagnosis,
            "confidence": 92.5,
            "severity": severity,
            "likelyCauses": ["Bacterial infection", "Environmental dampness"],
            "contagionRisk": "high",
            "needsVetVisit": True,
            "needsExpertReview": True,
            "expertType": "vet",
            "validation": validation,
        },
        "treatmentPlan": {
            "immediateActions": [{"action": "Isolate the animal", "actionHa": "Ware dabba"}],
            "organicRemedies": [],
            "chemicalTreatments": [{"product": "Antibiotic Spray", "dosage": "As directed by vet", "method": "Topical", "timing": "Twice daily", "cost": "N4,000"}],
            "prevention": [{"measure": "Keep pens dry and clean"}, {"measure": "Deworm regularly and disinfect housing"}],
            "dosageGuidance": [
                {"guidance": "Medication dosage must be calculated by animal weight"},
                {"guidance": "Repeat deworming after 14 days only if symptoms persist or vet confirms"}
            ],
            "consultation": {
                "recommended": True,
                "expertType": "vet",
                "message": "Professional consultation is recommended.",
                "callNumber": "08129582957",
                "whatsapp": "https://wa.me/2348129582957"
            }
        }
    }

@app.post("/validate/crop")
async def validate_crop_endpoint(
    cropType: str = Form(...),
    cropPart: str = Form("crop"),
    images: List[UploadFile] = File(...)
):
    result = await validate_crop_image(cropType, cropPart, images)
    if not result["accepted"]:
        raise HTTPException(status_code=422, detail=result)
    return {"validation": result}

@app.post("/validate/livestock")
async def validate_livestock_endpoint(
    animalType: str = Form(...),
    assessmentType: str = Form(...),
    images: List[UploadFile] = File(...)
):
    result = await validate_livestock_image(animalType, assessmentType, images)
    if not result["accepted"]:
        raise HTTPException(status_code=422, detail=result)
    return {"validation": result}

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8001)
