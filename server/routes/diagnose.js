const express = require('express');
const router = express.Router();
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const auth = require('../middleware/auth');
const Diagnosis = require('../models/Diagnosis');
const aiService = require('../services/aiService');

function getReviewReason(aiResult = {}) {
  if (Number(aiResult.confidence) < 70) return 'low_confidence';
  if (['severe', 'emergency'].includes(aiResult.severity)) return 'high_severity';
  if (aiResult.contagionRisk === 'high') return 'high_contagion_risk';
  if (aiResult.needsVetVisit) return 'vet_visit_recommended';
  if (aiResult.needsExpertReview) return 'expert_review_recommended';
  return null;
}

// Configure local storage for MVP
const uploadDir = path.join(__dirname, '..', 'uploads');
if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir, { recursive: true });

// Allowed MIME types — reject anything that isn't a real image
const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, uploadDir),
  // Sanitise filename to prevent path traversal
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase().replace(/[^.a-z0-9]/g, '');
    cb(null, `${Date.now()}-${Math.random().toString(36).slice(2)}${ext}`);
  },
});

const fileFilter = (req, file, cb) => {
  if (ALLOWED_MIME.includes(file.mimetype)) {
    cb(null, true);
  } else {
    cb(new Error(`Invalid file type: ${file.mimetype}. Only images are allowed.`), false);
  }
};

const upload = multer({ storage, fileFilter, limits: { fileSize: 10 * 1024 * 1024 } }); // 10MB

// Safe JSON parse helper
function safeJsonParse(str, fallback) {
  try { return JSON.parse(str); } catch { return fallback; }
}

// ─── POST /api/diagnose/crop ──────────────────────────────────────────────────
router.post('/crop', auth, upload.array('images', 5), async (req, res) => {
  try {
    const { cropType, cropPart, farmId, offlineId } = req.body;
    const imagePaths = req.files.map(f => f.path);
    const validation = await aiService.validateCrop({ cropType, cropPart, imagePaths });
    if (!validation.accepted) {
      return res.status(422).json({
        success: false,
        message: aiService.INVALID_IMAGE_MESSAGE,
        validation,
      });
    }

    if (offlineId) {
      const existing = await Diagnosis.findOne({ user: req.user.id, offlineId });
      if (existing) {
        return res.status(200).json({
          success: true,
          diagnosisId: existing._id,
          status: existing.status,
          duplicate: true,
        });
      }
    }

    const imageUrls = req.files.map(f => `/uploads/${f.filename}`);

    // Create pending diagnosis record
    const diag = await Diagnosis.create({
      user: req.user.id,
      farm: farmId,
      type: 'crop',
      cropType,
      cropPart,
      images: imageUrls.map(url => ({ url })),
      imageValidation: validation,
      isOfflineQueued: !!offlineId,
      offlineId,
      status: 'pending',
    });

    // Run AI inference (async, non-blocking)
    aiService.analyzeCrop({ diagnosisId: diag._id, cropType, imagePaths })
      .then(async (result) => {
        const reviewReason = getReviewReason(result.aiResult);
        diag.aiResult = result.aiResult;
        diag.treatmentPlan = result.treatmentPlan;
        diag.status = 'processed';
        diag.needsExpertReview = !!reviewReason;
        diag.reviewReason = reviewReason;
        diag.followUpDate = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000); // 7 days
        await diag.save();
      })
      .catch(async (err) => {
        diag.status = 'failed';
        await diag.save();
      });

    res.status(202).json({ success: true, diagnosisId: diag._id, message: 'Analysis started', status: 'pending' });
  } catch (err) {
    if (err.statusCode === 422) {
      return res.status(422).json({ success: false, message: err.message, validation: err.validation });
    }
    res.status(500).json({ success: false, message: err.message });
  }
});

// ─── POST /api/diagnose/livestock ────────────────────────────────────────────
router.post('/livestock', auth, upload.array('images', 8), async (req, res) => {
  try {
    const { animalId, animalType, assessmentType, farmId, symptoms, behavioral, offlineId } = req.body;
    const imagePaths = req.files.map(f => f.path);
    const validation = await aiService.validateLivestock({ animalType, assessmentType, imagePaths });
    if (!validation.accepted) {
      return res.status(422).json({
        success: false,
        message: aiService.INVALID_IMAGE_MESSAGE,
        validation,
      });
    }

    if (offlineId) {
      const existing = await Diagnosis.findOne({ user: req.user.id, offlineId });
      if (existing) {
        return res.status(200).json({
          success: true,
          diagnosisId: existing._id,
          status: existing.status,
          duplicate: true,
        });
      }
    }

    const imageUrls = req.files.map(f => `/uploads/${f.filename}`);

    const diag = await Diagnosis.create({
      user: req.user.id,
      farm: farmId,
      type: 'livestock',
      animal: animalId,
      animalType,
      assessmentType,
      images: imageUrls.map(url => ({ url })),
      imageValidation: validation,
      isOfflineQueued: !!offlineId,
      offlineId,
      status: 'pending',
    });

    aiService.analyzeLivestock({
      diagnosisId: diag._id,
      animalType,
      assessmentType,
      imagePaths,
      symptoms: symptoms ? safeJsonParse(symptoms, []) : [],
      behavioral: behavioral ? safeJsonParse(behavioral, {}) : {},
    }).then(async (result) => {
        const reviewReason = getReviewReason(result.aiResult);
        diag.aiResult = result.aiResult;
        diag.treatmentPlan = result.treatmentPlan;
        diag.status = 'processed';
        diag.needsExpertReview = !!reviewReason;
        diag.reviewReason = reviewReason;
        diag.followUpDate = new Date(Date.now() + 3 * 24 * 60 * 60 * 1000); // 3 days
        await diag.save();
      })
      .catch(async () => { diag.status = 'failed'; await diag.save(); });

    res.status(202).json({ success: true, diagnosisId: diag._id, status: 'pending' });
  } catch (err) {
    if (err.statusCode === 422) {
      return res.status(422).json({ success: false, message: err.message, validation: err.validation });
    }
    res.status(500).json({ success: false, message: err.message });
  }
});

// ─── GET /api/diagnose/:id ───────────────────────────────────────────
router.get('/:id', auth, async (req, res) => {
  try {
    const diag = await Diagnosis.findById(req.params.id).populate('animal farm');
    if (!diag) return res.status(404).json({ success: false, message: 'Diagnosis not found' });
    // Ownership check — only owner or admin/vet can view
    const canView = ['admin', 'ceo', 'vet', 'agronomist'].includes(req.user.role) ||
                    String(diag.user) === String(req.user.id);
    if (!canView) return res.status(403).json({ success: false, message: 'Access denied.' });
    res.json({ success: true, diagnosis: diag });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// ─── GET /api/diagnose (user history) ────────────────────────────────────
router.get('/', auth, async (req, res) => {
  try {
    const { type, page = 1, limit = 20 } = req.query;
    // Cap limit to prevent bulk data extraction
    const safeLimit = Math.min(parseInt(limit, 10) || 20, 100);
    const safePage  = Math.max(parseInt(page, 10)  || 1, 1);
    const filter = { user: req.user.id };
    if (type) filter.type = type;

    const diagnoses = await Diagnosis.find(filter)
      .sort({ createdAt: -1 })
      .limit(safeLimit)
      .skip((safePage - 1) * safeLimit)
      .populate('animal', 'name type');

    const total = await Diagnosis.countDocuments(filter);
    res.json({ success: true, diagnoses, total, pages: Math.ceil(total / safeLimit) });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// ─── PATCH /api/diagnose/:id/feedback ────────────────────────────────────
router.patch('/:id/feedback', auth, async (req, res) => {
  try {
    const diag = await Diagnosis.findById(req.params.id);
    if (!diag) return res.status(404).json({ success: false, message: 'Diagnosis not found' });
    // Only the owner can submit feedback
    if (String(diag.user) !== String(req.user.id))
      return res.status(403).json({ success: false, message: 'Access denied.' });
    const { wasHelpful, userRating, outcome } = req.body;
    diag.wasHelpful = wasHelpful;
    diag.userRating = userRating;
    diag.outcome = outcome || diag.outcome;
    await diag.save();
    res.json({ success: true, diagnosis: diag });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
