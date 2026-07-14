const mongoose = require('mongoose');

const consultationSchema = new mongoose.Schema({
  farmer: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
  expert: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
  diagnosis: { type: mongoose.Schema.Types.ObjectId, ref: 'Diagnosis' },
  caseType: { type: String, enum: ['crop', 'livestock'], required: true },
  consultationType: { type: String, enum: ['chat', 'voice', 'video', 'appointment'], default: 'chat' },
  urgency: { type: String, enum: ['routine', 'monitor', 'urgent', 'emergency'], default: 'monitor' },
  status: { type: String, enum: ['pending', 'matched', 'in-progress', 'completed', 'cancelled'], default: 'pending' },
  language: { type: String, enum: ['en', 'ha', 'ig', 'yo', 'ff', 'fr'], default: 'ha' },
  symptoms: [{ type: String }],
  farmerNotes: { type: String },
  expertNotes: { type: String },
  prescription: {
    summary: String,
    medications: [{
      name: String,
      dosage: String,
      route: String,
      duration: String,
      withdrawalPeriod: String,
    }],
    supportiveCare: [{ type: String }],
    followUpDate: Date,
    documentUrl: String,
  },
  fee: { type: Number, default: 0 },
  paymentStatus: { type: String, enum: ['unpaid', 'authorized', 'paid', 'released', 'refunded'], default: 'unpaid' },
  scheduledFor: Date,
  call: {
    provider: { type: String, default: 'manual' },
    joinUrl: String,
    phoneNumber: String,
  },
  rating: { type: Number, min: 1, max: 5 },
  feedback: String,
}, { timestamps: true });

consultationSchema.index({ farmer: 1, createdAt: -1 });
consultationSchema.index({ expert: 1, status: 1, urgency: 1 });

module.exports = mongoose.model('Consultation', consultationSchema);
