/**
 * Seed script — populates the database with demo data for testing
 * Run: node server/scripts/seed.js
 */
require('dotenv').config({ path: require('path').join(__dirname, '..', '.env') });
const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');

// Models
const User    = require('../models/User');
const Farm    = require('../models/Farm');
const Animal  = require('../models/Animal');
const Product = require('../models/Product');

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/farmai';

const PRODUCTS = [
  { name: 'Ivermectin 1%', nameHa: 'Maganin tsutsotsi', category: 'veterinary', priceNGN: 2500, unit: '50ml', brand: 'Agverm', tags: ['worms', 'parasites', 'cattle', 'goat'], stockQty: 100, isAvailableKatsina: true, withdrawalPeriodDays: 28, activeIngredient: 'Ivermectin' },
  { name: 'Albendazole 10%', nameHa: 'Maganin ƙwari na ciki', category: 'veterinary', priceNGN: 1200, unit: '100ml', brand: 'Valbazen', tags: ['worms', 'parasites', 'sheep', 'goat'], stockQty: 80, isAvailableKatsina: true, withdrawalPeriodDays: 14, activeIngredient: 'Albendazole' },
  { name: 'Amprolium (Amprolsol)', nameHa: 'Maganin Coccidiosis', category: 'veterinary', priceNGN: 1800, unit: '100ml', brand: 'Vetmediq', tags: ['coccidiosis', 'goat', 'poultry'], stockQty: 60, isAvailableKatsina: true, withdrawalPeriodDays: 0, activeIngredient: 'Amprolium' },
  { name: 'ORS Animal Electrolyte', nameHa: 'Ruwan Electrolyte don Dabbobi', category: 'veterinary', priceNGN: 350, unit: 'sachet (1L)', brand: 'FarmCare', tags: ['diarrhea', 'dehydration', 'cattle', 'goat', 'scours'], stockQty: 200, isAvailableKatsina: true, withdrawalPeriodDays: 0, activeIngredient: 'Sodium Chloride + Glucose' },
  { name: 'Mancozeb 80% WP', nameHa: 'Magungunan Naman Gwari (Mancozeb)', category: 'fungicide', priceNGN: 1500, unit: '200g pack', brand: 'Dithane M-45', tags: ['blight', 'late blight', 'tomato', 'maize', 'fungal'], stockQty: 150, isAvailableKatsina: true, activeIngredient: 'Mancozeb' },
  { name: 'Urea Fertilizer 46-0-0', nameHa: 'Taki na Urea', category: 'fertilizer', priceNGN: 22000, unit: '50kg bag', brand: 'NOTORE', tags: ['nitrogen', 'yellow leaves', 'nitrogen deficiency', 'maize'], stockQty: 50, isAvailableKatsina: true, activeIngredient: 'Urea (nitrogen 46%)' },
  { name: 'NPK 15-15-15 Compound', nameHa: 'Taki na NPK', category: 'fertilizer', priceNGN: 26000, unit: '50kg bag', brand: 'Yara', tags: ['npk', 'deficiency', 'all crops'], stockQty: 40, isAvailableKatsina: true },
  { name: 'Emamectin Benzoate (Coragen)', nameHa: 'Maganin Fall Armyworm', category: 'pesticide', priceNGN: 4200, unit: '100ml', brand: 'FMC', tags: ['fall armyworm', 'caterpillar', 'maize', 'sorghum'], stockQty: 30, isAvailableKatsina: true, activeIngredient: 'Emamectin Benzoate 5%' },
  { name: 'Neem Oil Organic Spray', nameHa: 'Maganin Neem (Na Halitta)', category: 'pesticide', priceNGN: 800, unit: '500ml', brand: 'GreenVet', tags: ['organic', 'pests', 'aphids', 'mites'], stockQty: 120, isAvailableKatsina: true, activeIngredient: 'Neem oil 3000ppm' },
  { name: 'Copper Sulphate (Bordeaux)', nameHa: 'Maganin Copper', category: 'fungicide', priceNGN: 2000, unit: '1kg', brand: 'AgriCopper', tags: ['late blight', 'tomato', 'organic fungicide'], stockQty: 60, isAvailableKatsina: true, activeIngredient: 'Copper Sulphate' },
];

async function seed(uri) {
  if (mongoose.connection.readyState !== 1) {
    await mongoose.connect(uri || MONGO_URI);
    console.log('✅ Connected to MongoDB');
  }

  // Clear collections
  await Promise.all([User.deleteMany(), Farm.deleteMany(), Animal.deleteMany(), Product.deleteMany()]);
  console.log('🗑 Cleared existing data');

  // Create demo farmer
  const hashed = await bcrypt.hash('farmer123', 12);
  const farmer = await User.create({
    name: 'Aminu Yusuf Katsina',
    phone: '08012345678',
    password: hashed,
    language: 'ha',
    role: 'farmer',
    state: 'Katsina',
    lga: 'Katsina Central',
    village: 'Kaura',
  });
  console.log(`👤 Created farmer: ${farmer.name} | phone: 08012345678 | password: farmer123`);

  // Create demo farm
  const farm = await Farm.create({
    owner: farmer._id,
    name: "Aminu's Farm",
    location: { state: 'Katsina', lga: 'Katsina Central', village: 'Kaura', lat: 12.9906, lng: 7.6017 },
    sizeHectares: 4.5,
    cropPlots: [
      { cropType: 'maize', plotSize: 2, plantedDate: new Date('2026-03-01'), growthStage: 'vegetative' },
      { cropType: 'sorghum', plotSize: 1.5, plantedDate: new Date('2026-03-10'), growthStage: 'vegetative' },
      { cropType: 'tomato', plotSize: 1, plantedDate: new Date('2026-03-15'), growthStage: 'flowering' },
    ],
    livestockCounts: { cattle: 8, goats: 24, sheep: 12, poultry: 50 },
  });
  console.log(`🌾 Created farm: ${farm.name}`);

  // Create demo animals
  const animals = await Animal.insertMany([
    { farm: farm._id, owner: farmer._id, type: 'cattle', name: 'Fulani Bull 1', tagId: 'CT-001', breed: 'Fulani', sex: 'male', weightKg: 320, status: 'healthy' },
    { farm: farm._id, owner: farmer._id, type: 'cattle', name: 'Cow Maje', tagId: 'CT-002', breed: 'Bunaji', sex: 'female', weightKg: 260, status: 'sick' },
    { farm: farm._id, owner: farmer._id, type: 'goat', name: 'Kid Alfa', breed: 'Red Sokoto', sex: 'male', weightKg: 18, status: 'recovering' },
    { farm: farm._id, owner: farmer._id, type: 'sheep', name: 'Ram Bala', breed: 'Uda', sex: 'male', weightKg: 35, status: 'healthy' },
    { farm: farm._id, owner: farmer._id, type: 'poultry', name: 'Hen A1', breed: 'Noiler', sex: 'female', weightKg: 1.8, status: 'healthy' },
  ]);
  console.log(`🐄 Created ${animals.length} animals`);

  // Create vet user
  await User.create({
    name: 'Dr. Ibrahim Sule',
    phone: '07011111111',
    password: await bcrypt.hash('vet123', 12),
    language: 'en',
    role: 'vet',
    state: 'Katsina',
  });
  console.log('🩺 Created vet: Dr. Ibrahim Sule | phone: 07011111111 | password: vet123');

  // Seed products
  await Product.insertMany(PRODUCTS);
  console.log(`🛒 Created ${PRODUCTS.length} marketplace products`);

  console.log('\n✅ Seed complete!');
  console.log('─────────────────────────────────────────');
  console.log('Demo Farmer → phone: 08012345678 | password: farmer123');
  console.log('Demo Vet    → phone: 07011111111 | password: vet123');
  console.log('─────────────────────────────────────────');
  
  if (require.main === module) {
    await mongoose.disconnect();
  }
}

if (require.main === module) {
  seed().catch(err => { console.error(err); process.exit(1); });
} else {
  module.exports = { seed };
}
