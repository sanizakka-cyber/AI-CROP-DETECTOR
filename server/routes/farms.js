const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const Farm = require('../models/Farm');

// POST /api/farms
router.post('/', auth, async (req, res) => {
  try {
    const farm = await Farm.create({ ...req.body, owner: req.user.id });
    res.status(201).json({ success: true, farm });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/farms (user's farms)
router.get('/', auth, async (req, res) => {
  try {
    const farms = await Farm.find({ owner: req.user.id });
    res.json({ success: true, farms });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/farms/:id
router.get('/:id', auth, async (req, res) => {
  try {
    const farm = await Farm.findById(req.params.id);
    if (!farm) return res.status(404).json({ success: false, message: 'Farm not found' });
    if (String(farm.owner) !== String(req.user.id) && !['admin', 'ceo'].includes(req.user.role))
      return res.status(403).json({ success: false, message: 'Access denied.' });
    res.json({ success: true, farm });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// PATCH /api/farms/:id
router.patch('/:id', auth, async (req, res) => {
  try {
    const farm = await Farm.findById(req.params.id);
    if (!farm) return res.status(404).json({ success: false, message: 'Farm not found' });
    if (String(farm.owner) !== String(req.user.id) && !['admin', 'ceo'].includes(req.user.role))
      return res.status(403).json({ success: false, message: 'Access denied.' });
    const updated = await Farm.findByIdAndUpdate(req.params.id, req.body, { new: true, runValidators: true });
    res.json({ success: true, farm: updated });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
