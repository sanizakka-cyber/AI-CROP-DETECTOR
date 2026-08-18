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

    // Never fabricate a diagnosis/confidence when the real AI service is
    // unreachable — surface an honest failure instead.
    const failure = new Error('AI engine temporarily unavailable. Please try again shortly.');
    failure.statusCode = 503;
    failure.aiUnavailable = true;
    throw failure;
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

    // Never fabricate a diagnosis/confidence when the real AI service is
    // unreachable — surface an honest failure instead.
    const failure = new Error('AI engine temporarily unavailable. Please try again shortly.');
    failure.statusCode = 503;
    failure.aiUnavailable = true;
    throw failure;
  }
}

module.exports = { analyzeCrop, analyzeLivestock, validateCrop, validateLivestock, INVALID_IMAGE_MESSAGE };
