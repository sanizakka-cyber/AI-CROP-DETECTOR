const mongoose = require('mongoose');

const userSchema = new mongoose.Schema({
  name:        { type: String, required: true, trim: true },
  phone:       { type: String, required: true, unique: true },
  email:       { type: String, trim: true, lowercase: true },
  password:    { type: String, required: true, select: false },
  language:    { type: String, enum: ['en', 'ha', 'ig', 'yo', 'ff', 'fr'], default: 'ha' },
  role:        { type: String, enum: ['farmer', 'vet', 'agronomist', 'admin', 'agro-dealer', 'extension-officer', 'researcher'], default: 'farmer' },
  state:       { type: String, default: 'Katsina' },
  lga:         { type: String },
  village:     { type: String },
  profilePic:  { type: String },
  isVerified:  { type: Boolean, default: false },
  verificationStatus: { type: String, enum: ['not_required', 'pending', 'approved', 'rejected'], default: 'not_required' },
  verificationNotes: { type: String },
  isPremium:   { type: Boolean, default: false },
  lastSeen:    { type: Date },
  fcmToken:    { type: String }, // for push notifications
  farmerProfile: {
    farmSize: Number,
    farmSizeUnit: { type: String, default: 'hectares' },
    farmingExperienceYears: Number,
    primaryActivity: { type: String, enum: ['livestock', 'crops', 'mixed', 'other'], default: 'mixed' },
    cropsGrown: [{ type: String }],
    livestockCounts: {
      cattle: { type: Number, default: 0 },
      goats: { type: Number, default: 0 },
      sheep: { type: Number, default: 0 },
      poultry: { type: Number, default: 0 },
    },
  },
  expertProfile: {
    licenseNumber: String,
    issuingAuthority: String,
    certification: String,
    specialization: String,
    yearsExperience: Number,
    organization: String,
    practiceAddress: String,
    consultationFee: Number,
    availabilitySchedule: String,
    languages: [{ type: String }],
    documents: [{
      kind: { type: String },
      url: { type: String },
      uploadedAt: { type: Date, default: Date.now },
    }],
    rating: { type: Number, default: 0 },
    totalConsultations: { type: Number, default: 0 },
    averageResponseMinutes: { type: Number, default: 0 },
    bankAccount: {
      bankName: String,
      accountName: String,
      accountNumberLast4: String,
    },
  },
  dealerProfile: {
    businessName: String,
    registrationNumber: String,
    storeAddress: String,
    productCategories: [{ type: String }],
    deliveryCoverage: [{ type: String }],
  },
}, { timestamps: true });

module.exports = mongoose.model('User', userSchema);
