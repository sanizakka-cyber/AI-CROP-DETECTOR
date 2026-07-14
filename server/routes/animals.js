const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const Animal = require('../models/Animal');

router.post('/', auth, async (req, res) => {
  try {
    const animal = await Animal.create({ ...req.body, owner: req.user.id });
    res.status(201).json({ success: true, animal });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

router.get('/', auth, async (req, res) => {
  try {
    const { farmId, type, status } = req.query;
    const filter = { owner: req.user.id };
    if (farmId) filter.farm = farmId;
    if (type) filter.type = type;
    if (status) filter.status = status;
    const animals = await Animal.find(filter).populate('farm', 'name');
    res.json({ success: true, animals });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

router.get('/:id', auth, async (req, res) => {
  try {
    const animal = await Animal.findById(req.params.id).populate('farm');
    if (!animal) return res.status(404).json({ success: false, message: 'Animal not found' });
    // Ownership check
    if (String(animal.owner) !== String(req.user.id) && !['admin', 'ceo'].includes(req.user.role))
      return res.status(403).json({ success: false, message: 'Access denied.' });
    res.json({ success: true, animal });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

router.patch('/:id', auth, async (req, res) => {
  try {
    const animal = await Animal.findOne({ _id: req.params.id, owner: req.user.id });
    if (!animal && !['admin', 'ceo'].includes(req.user.role))
      return res.status(403).json({ success: false, message: 'Access denied or animal not found.' });
    const updated = await Animal.findByIdAndUpdate(req.params.id, req.body, { new: true, runValidators: true });
    res.json({ success: true, animal: updated });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

router.delete('/:id', auth, async (req, res) => {
  try {
    const animal = await Animal.findById(req.params.id);
    if (!animal) return res.status(404).json({ success: false, message: 'Animal not found' });
    if (String(animal.owner) !== String(req.user.id) && !['admin', 'ceo'].includes(req.user.role))
      return res.status(403).json({ success: false, message: 'Access denied.' });
    await animal.deleteOne();
    res.json({ success: true, message: 'Animal record deleted' });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
