const mongoose = require('mongoose');

const farmSchema = new mongoose.Schema({
  owner:       { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
  name:        { type: String, required: true },
  location: {
    state:     { type: String, default: 'Katsina' },
    lga:       { type: String },
    village:   { type: String },
    lat:       { type: Number },
    lng:       { type: Number },
  },
  sizeHectares:   { type: Number },
  cropPlots: [{
    cropType:  { type: String },  // maize, rice, sorghum, millet...
    plotSize:  { type: Number },  // in hectares
    plantedDate: { type: Date },
    growthStage: { type: String, enum: ['seedling', 'vegetative', 'flowering', 'harvest', 'fallow'] },
  }],
  livestockCounts: {
    cattle:  { type: Number, default: 0 },
    goats:   { type: Number, default: 0 },
    sheep:   { type: Number, default: 0 },
    poultry: { type: Number, default: 0 },
    pigs:    { type: Number, default: 0 },
  },
}, { timestamps: true });

module.exports = mongoose.model('Farm', farmSchema);
