const mongoose = require('mongoose');

const animalSchema = new mongoose.Schema({
  farm:        { type: mongoose.Schema.Types.ObjectId, ref: 'Farm', required: true },
  owner:       { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
  type:        { type: String, enum: ['cattle', 'goat', 'sheep', 'poultry', 'pig'], required: true },
  name:        { type: String },
  tagId:       { type: String }, // ear tag or chip ID
  breed:       { type: String },
  sex:         { type: String, enum: ['male', 'female', 'unknown'] },
  dob:         { type: Date },
  weightKg:    { type: Number },
  bodyConditionScore: { type: Number, min: 1, max: 5 },
  status:      { type: String, enum: ['healthy', 'sick', 'recovering', 'deceased'], default: 'healthy' },
  vaccinationHistory: [{
    vaccine:   { type: String },
    date:      { type: Date },
    nextDue:   { type: Date },
    vet:       { type: String },
  }],
  dewormingHistory: [{
    drug:      { type: String },
    dose:      { type: String },
    date:      { type: Date },
    nextDue:   { type: Date },
  }],
  notes:       { type: String },
}, { timestamps: true });

module.exports = mongoose.model('Animal', animalSchema);
