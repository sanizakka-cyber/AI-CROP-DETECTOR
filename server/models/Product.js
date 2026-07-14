const mongoose = require('mongoose');

const productSchema = new mongoose.Schema({
  name:        { type: String, required: true },
  nameHa:      { type: String },
  category:    { type: String, enum: ['veterinary', 'pesticide', 'fungicide', 'herbicide', 'fertilizer', 'supplement', 'equipment'] },
  description: { type: String },
  brand:       { type: String },
  genericName: { type: String },
  priceNGN:    { type: Number },
  unit:        { type: String }, // kg, litre, sachet
  stockQty:    { type: Number, default: 0 },
  supplier:    { type: String },
  images:      [{ type: String }],
  isAvailableKatsina: { type: Boolean, default: true },
  withdrawalPeriodDays: { type: Number }, // for livestock meds
  activeIngredient: { type: String },
  tags:        [{ type: String }], // conditions it treats
}, { timestamps: true });

module.exports = mongoose.model('Product', productSchema);
