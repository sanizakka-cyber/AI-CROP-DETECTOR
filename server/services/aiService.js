const axios = require('axios');
const FormData = require('form-data');
const fs = require('fs');

const AI_SERVICE_URL = process.env.AI_SERVICE_URL || 'http://localhost:8000';
const INVALID_IMAGE_MESSAGE = 'Invalid image detected. Please upload a clear image of a plant, livestock animal, or agricultural sample only.';

function buildForm(fields, imagePaths) {
  const form = new FormData();
  Object.entries(fields).forEach(([key, value]) => form.append(key, value || ''));
  imagePaths.forEach((path) => {
    form.append('images', fs.createReadStream(path));
  });
  return form;
}

function normalizeValidationError(err) {
  const detail = err.response?.data?.detail;
  if (detail?.message) {
    const error = new Error(detail.message);
    error.statusCode = 422;
    error.validation = detail;
    return error;
  }
  return err;
}

async function validateCrop({ cropType, cropPart, imagePaths }) {
  try {
    const form = buildForm({ cropType: cropType || 'maize', cropPart: cropPart || 'crop' }, imagePaths);
    const response = await axios.post(`${AI_SERVICE_URL}/validate/crop`, form, {
      headers: { ...form.getHeaders() },
      timeout: 10000,
    });
    return response.data.validation;
  } catch (err) {
    const normalized = normalizeValidationError(err);
    if (normalized.statusCode === 422) throw normalized;
    return {
      accepted: true,
      message: null,
      objectType: cropPart || 'crop',
      category: 'plant',
      quality: { status: 'unknown', score: 70, issues: ['AI validation service unavailable; fallback accepted'] },
      modelStatus: 'server-fallback',
    };
  }
}

async function validateLivestock({ animalType, assessmentType, imagePaths }) {
  try {
    const form = buildForm({ animalType: animalType || 'cattle', assessmentType: assessmentType || 'visual' }, imagePaths);
    const response = await axios.post(`${AI_SERVICE_URL}/validate/livestock`, form, {
      headers: { ...form.getHeaders() },
      timeout: 10000,
    });
    return response.data.validation;
  } catch (err) {
    const normalized = normalizeValidationError(err);
    if (normalized.statusCode === 422) throw normalized;
    return {
      accepted: true,
      message: null,
      objectType: assessmentType || 'visual',
      category: 'animal',
      quality: { status: 'unknown', score: 70, issues: ['AI validation service unavailable; fallback accepted'] },
      modelStatus: 'server-fallback',
    };
  }
}

// ─── CROP DISEASE DATABASE (Expanded for Phase 2: 10+ Crops) ─────────────────
const CROP_DISEASE_DB = {
  maize: [
    {
      name: 'Northern Leaf Blight',
      nameHa: 'Cuta ta ganyen masara (arewa)',
      confidence: 88,
      severity: 'moderate',
      causes: ['Fungus: Exserohilum turcicum', 'High humidity', 'Temperature 18-27°C'],
      immediateActions: ['Remove and destroy infected leaves', 'Improve air circulation by thinning plants'],
      organicRemedies: [{ remedy: 'Neem oil spray', dosage: '5ml/litre water', method: 'Foliar spray', timing: 'Early morning, every 5 days' }],
      chemicalTreatments: [{ product: 'Mancozeb 80% WP', dosage: '2g/litre water', method: 'Foliar spray', timing: 'Every 7-10 days', cost: '₦1,500/pack' }],
      prevention: ['Use resistant varieties', 'Crop rotation every 2 seasons', 'Avoid overhead irrigation'],
    },
    {
      name: 'Nitrogen Deficiency',
      nameHa: 'Karancin nitrogen a cikin masara',
      confidence: 82,
      severity: 'mild',
      causes: ['Low soil nitrogen', 'Poor organic matter', 'Waterlogging'],
      immediateActions: ['Apply urea fertilizer immediately', 'Ensure adequate drainage'],
      organicRemedies: [{ remedy: 'Compost tea', dosage: '1 litre per plant', method: 'Soil drench', timing: 'Weekly' }],
      chemicalTreatments: [{ product: 'Urea (46-0-0)', dosage: '50kg/hectare', method: 'Broadcast and water in', timing: 'Immediately and repeat in 3 weeks', cost: '₦22,000/50kg bag' }],
      prevention: ['Apply basal fertilizer at planting', 'Annual soil testing', 'Use legume cover crops'],
    },
  ],
  tomato: [
    {
      name: 'Late Blight',
      nameHa: 'Cuta ta latti a cikin tumatir',
      confidence: 91,
      severity: 'severe',
      causes: ['Fungus: Phytophthora infestans', 'Cool wet weather', 'Poor drainage'],
      immediateActions: ['Quarantine infected plants immediately', 'Remove all visibly infected leaves', 'Stop overhead irrigation'],
      organicRemedies: [{ remedy: 'Copper sulphate + lime (Bordeaux mixture)', dosage: '10g/litre', method: 'Foliar spray', timing: 'Every 5 days' }],
      chemicalTreatments: [{ product: 'Ridomil Gold (Metalaxyl + Mancozeb)', dosage: '2.5g/litre', method: 'Foliar spray covering undersides', timing: 'Every 7 days, stop 14 days before harvest', cost: '₦3,500/pack' }],
      prevention: ['Plant resistant varieties (e.g. UC82)', 'Stake plants for better airflow', 'Rotate with non-solanaceous crops'],
    },
  ],
  rice: [
    {
      name: 'Rice Blast',
      nameHa: 'Cuta ta Rice Blast',
      confidence: 87,
      severity: 'severe',
      causes: ['Magnaporthe oryzae', 'High nitrogen fertilization', 'Drought stress'],
      immediateActions: ['Reduce nitrogen application', 'Maintain proper water levels'],
      organicRemedies: [{ remedy: 'Potassium salt of fatty acids', dosage: '10ml/L', method: 'Spray', timing: 'Morning' }],
      chemicalTreatments: [{ product: 'Tricyclazole', dosage: '0.6g/L', method: 'Foliar', timing: 'Once', cost: '₦2,800' }],
      prevention: ['Plant resistant varieties', 'Treat seeds with fungicide'],
    }
  ],
  cassava: [
    {
      name: 'Cassava Mosaic Disease',
      nameHa: 'Cutar Mosaic a Rogo',
      confidence: 92,
      severity: 'severe',
      causes: ['Whiteflies', 'Infected cuttings'],
      immediateActions: ['Uproot and burn infected plants', 'Control whiteflies'],
      organicRemedies: [{ remedy: 'Ash dusting', dosage: 'Dusting', method: 'Topical', timing: 'Weekly' }],
      chemicalTreatments: [{ product: 'Insecticide (for whiteflies)', dosage: '2ml/L', method: 'Spray', timing: 'Twice a month', cost: '₦3,000' }],
      prevention: ['Use healthy cuttings', 'Plant resistant varieties'],
    }
  ],
  // ... (Other crops: Sorghum, Yam, Beans, Soybeans, Onions, Pepper, Millet)
};

const LIVESTOCK_DISEASE_DB = {
  goat: {
    fecal: [
      {
        name: 'Coccidiosis',
        nameHa: 'Coccidiosis a cikin awaki',
        confidence: 80,
        severity: 'moderate',
        contagionRisk: 'high',
        needsVetVisit: false,
        immediateActions: ['Isolate affected animal', 'Provide clean water with electrolytes (ORS)', 'Clean and disinfect pen'],
        treatment: {
          drug: 'Amprolium (Amprolsol)',
          dosage: '10mg/kg body weight',
          route: 'Oral – mix in water',
          duration: '5 days',
          withdrawalDays: 0,
          cost: '₦1,800/bottle',
        },
        prevention: ['Regular pen sanitation', 'Avoid overcrowding', 'Prophylactic amprolium during high-risk periods'],
      },
    ],
  },
  cattle: {
    fecal: [
      {
        name: 'Gastrointestinal Worms (Haemonchosis)',
        nameHa: 'Tsutsotsi a ciki (Haemonchosis)',
        confidence: 78,
        severity: 'moderate',
        contagionRisk: 'medium',
        needsVetVisit: false,
        immediateActions: ['Isolate animal', 'Weigh animal for accurate dosing', 'Provide high-quality hay'],
        treatment: {
          drug: 'Ivermectin 1% injectable',
          dosage: '0.2mg/kg body weight (= 1ml/50kg)',
          route: 'Subcutaneous injection',
          duration: 'Single dose, repeat in 14 days',
          withdrawalDays: 28,
          cost: '₦2,500/50ml vial',
        },
        prevention: ['Strategic deworming (dry season onset + peak rainy season)', 'Pasture rotation', 'FAMACHA scoring'],
      },
    ],
    visual: [
      {
        name: 'Tick Fever (Theileriosis)',
        nameHa: 'Zazzabin ɓari (Tick Fever)',
        confidence: 75,
        severity: 'severe',
        contagionRisk: 'low',
        needsVetVisit: true,
        immediateActions: ['Shade and rest animal', 'Force fluids', 'Remove ticks manually with acaricide dip'],
        treatment: {
          drug: 'Buparvaquone (Butalex)',
          dosage: '2.5mg/kg body weight IM',
          route: 'Intramuscular injection',
          duration: 'Single dose',
          withdrawalDays: 30,
          cost: '₦8,500/20ml vial',
        },
        prevention: ['Regular tick control (acaricide dipping every 2 weeks)', 'Stable vaccination programme', 'Avoid grazing tick-infested pastures'],
      },
    ],
  },
};

/**
 * AI Crop Analysis
 * Phase 2: Call Python FastAPI microservice running TFLite model
 */
async function analyzeCrop({ diagnosisId, cropType, imagePaths }) {
  try {
    const form = buildForm({ cropType: cropType || 'maize' }, imagePaths);

    const response = await axios.post(`${AI_SERVICE_URL}/predict/crop`, form, {
      headers: { ...form.getHeaders() },
      timeout: 10000,
    });

    return response.data;
  } catch (err) {
    const normalized = normalizeValidationError(err);
    if (normalized.statusCode === 422) throw normalized;
    console.error('AI Service Error (Crop):', err.message);
    console.log('Using local rule-based fallback...');
    
    // FALLBACK TO MOCK
    const crop = cropType?.toLowerCase() || 'maize';
    const diseases = CROP_DISEASE_DB[crop] || CROP_DISEASE_DB['maize'];
    const picked = diseases[Math.floor(Math.random() * diseases.length)];

    return {
      aiResult: {
        primaryDiagnosis: picked.name,
        primaryDiagnosisHa: picked.nameHa,
        confidence: picked.confidence,
        severity: picked.severity,
        likelyCauses: picked.causes,
        contagionRisk: 'medium',
        needsVetVisit: false,
        needsExpertReview: picked.severity === 'severe' || picked.confidence < 70,
        expertType: 'agronomist',
      },
      treatmentPlan: {
        immediateActions: picked.immediateActions.map(a => ({ action: a, actionHa: a })),
        organicRemedies: picked.organicRemedies,
        chemicalTreatments: picked.chemicalTreatments,
        prevention: picked.prevention.map(p => ({ measure: p })),
        consultation: {
          recommended: picked.severity === 'severe' || picked.confidence < 70,
          expertType: 'agronomist',
          message: picked.severity === 'severe' || picked.confidence < 70
            ? 'Professional consultation is recommended.'
            : 'Monitor for 7 days and consult an agronomist if symptoms spread.',
          callNumber: '08129582957',
          whatsapp: 'https://wa.me/2348129582957',
        },
      },
    };
  }
}

/**
 * AI Livestock Analysis
 * Phase 2: Call Python FastAPI microservice
 */
async function analyzeLivestock({ diagnosisId, animalType, assessmentType, imagePaths, symptoms, behavioral }) {
  try {
    const form = buildForm({ animalType: animalType || 'cattle', assessmentType: assessmentType || 'visual' }, imagePaths);

    const response = await axios.post(`${AI_SERVICE_URL}/predict/livestock`, form, {
      headers: { ...form.getHeaders() },
      timeout: 12000,
    });

    return response.data;
  } catch (err) {
    const normalized = normalizeValidationError(err);
    if (normalized.statusCode === 422) throw normalized;
    console.error('AI Service Error (Livestock):', err.message);
    console.log('Using local rule-based fallback...');

    // FALLBACK TO MOCK
    const animal = animalType?.toLowerCase() || 'cattle';
    const db = LIVESTOCK_DISEASE_DB[animal] || LIVESTOCK_DISEASE_DB['cattle'];
    const modality = assessmentType === 'fecal' ? 'fecal' : 'visual';
    const list = db[modality] || db['fecal'] || [];
    const picked = list[0] || {
      name: 'General Health Issue',
      nameHa: 'Matsalar lafiya',
      confidence: 60,
      severity: 'mild',
      needsVetVisit: true,
      immediateActions: ['Monitor animal closely'],
      treatment: { drug: 'Consult veterinarian' },
      prevention: ['Regular monitoring'],
    };

    return {
      aiResult: {
        primaryDiagnosis: picked.name,
        primaryDiagnosisHa: picked.nameHa,
        confidence: picked.confidence,
        severity: picked.severity,
        likelyCauses: ['Environmental stress', 'Parasitic load'],
        contagionRisk: picked.contagionRisk || 'low',
        needsVetVisit: picked.needsVetVisit,
        needsExpertReview: picked.needsVetVisit || picked.severity === 'severe' || picked.confidence < 70,
        expertType: 'vet',
      },
      treatmentPlan: {
        immediateActions: picked.immediateActions.map(a => ({ action: a, actionHa: a })),
        organicRemedies: [],
        chemicalTreatments: [{
          product: picked.treatment.drug,
          dosage: picked.treatment.dosage,
          method: picked.treatment.route,
          timing: picked.treatment.duration,
          cost: picked.treatment.cost,
        }],
        dosageGuidance: [
          { guidance: 'Administer medication based on animal weight and species.' },
          { guidance: 'Observe withdrawal periods before selling milk or meat.' },
        ],
        prevention: picked.prevention.map(p => ({ measure: p })),
        consultation: {
          recommended: picked.needsVetVisit || picked.severity === 'severe' || picked.confidence < 70,
          expertType: 'vet',
          message: 'Professional consultation is recommended.',
          callNumber: '08129582957',
          whatsapp: 'https://wa.me/2348129582957',
        },
      },
    };
  }
}

module.exports = { analyzeCrop, analyzeLivestock, validateCrop, validateLivestock, INVALID_IMAGE_MESSAGE };
