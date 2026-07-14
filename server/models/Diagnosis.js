const mongoose = require('mongoose');

const diagnosisSchema = new mongoose.Schema({
  user:        { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
  farm:        { type: mongoose.Schema.Types.ObjectId, ref: 'Farm' },
  type:        { type: String, enum: ['crop', 'livestock'], required: true },

  // ── For Crop Diagnoses ──
  cropType:    { type: String },  // maize, tomato, rice ...
  cropPart:    { type: String },  // leaf, stem, root, whole plant

  // ── For Livestock Diagnoses ──
  animal:      { type: mongoose.Schema.Types.ObjectId, ref: 'Animal' },
  animalType:  { type: String },
  assessmentType: { type: String, enum: ['fecal', 'visual', 'behavioral', 'comprehensive'] },

  // ── Uploaded Images ──
  images: [{
    url:       { type: String },
    angle:     { type: String }, // front, back, top, bottom, close-up
    uploadedAt:{ type: Date, default: Date.now },
  }],

  imageValidation: {
    accepted: Boolean,
    message: String,
    objectType: String,
    category: { type: String, enum: ['plant', 'animal', 'non_agricultural', 'unknown'] },
    reason: String,
    modelStatus: String,
    quality: {
      status: String,
      score: Number,
      issues: [{ type: String }],
    },
  },

  // ── AI Result ──
  aiResult: {
    primaryDiagnosis:    { type: String },
    primaryDiagnosisHa:  { type: String }, // Hausa translation
    confidence:          { type: Number }, // 0-100
    alternativeDiagnoses:[{ diagnosis: String, confidence: Number }],
    severity:            { type: String, enum: ['mild', 'moderate', 'severe', 'emergency'] },
    affectedAreas:       [{ type: String }],
    likelyCauses:        [{ type: String }],
    progressionRisk:     { type: String },
    contagionRisk:       { type: String, enum: ['low', 'medium', 'high', 'none'] },
    needsVetVisit:       { type: Boolean, default: false },
    needsExpertReview:   { type: Boolean, default: false },
    expertType:          { type: String, enum: ['vet', 'agronomist', 'none'], default: 'none' },
    processedBy:         { type: String, enum: ['on-device', 'cloud'], default: 'cloud' },
  },

  // ── Treatment Plan ──
  treatmentPlan: {
    immediateActions:   [{ action: String, actionHa: String }],
    organicRemedies:    [{ remedy: String, dosage: String, method: String, timing: String }],
    chemicalTreatments: [{ product: String, dosage: String, method: String, timing: String, cost: String }],
    dosageGuidance:      [{ guidance: String, guidanceHa: String }],
    prevention:         [{ measure: String }],
    consultation: {
      recommended: Boolean,
      expertType: { type: String, enum: ['vet', 'agronomist', 'none'] },
      message: String,
      callNumber: String,
      whatsapp: String,
    },
  },

  // ── Outcome Tracking ──
  followUpDate:  { type: Date },
  outcome:       { type: String, enum: ['resolved', 'improving', 'worsening', 'unchanged', 'pending'], default: 'pending' },
  userRating:    { type: Number, min: 1, max: 5 },
  wasHelpful:    { type: Boolean },
  expertReviewed:{ type: Boolean, default: false },
  expertNotes:   { type: String },

  // ── Status ──
  status:        { type: String, enum: ['pending', 'processed', 'failed'], default: 'pending' },
  isOfflineQueued:{ type: Boolean, default: false },
  offlineId:     { type: String, index: true },
  needsExpertReview: { type: Boolean, default: false },
  reviewReason:  { type: String },
}, { timestamps: true });

diagnosisSchema.index({ user: 1, offlineId: 1 }, { unique: true, sparse: true });

module.exports = mongoose.model('Diagnosis', diagnosisSchema);
