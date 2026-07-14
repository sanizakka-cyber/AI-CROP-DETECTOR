const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const Product = require('../models/Product');

// Escape special regex characters to prevent ReDoS
function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// GET /api/marketplace/products
router.get('/products', auth, async (req, res) => {
  try {
    const { category, search, page = 1, limit = 20 } = req.query;
    const safeLimit = Math.min(parseInt(limit, 10) || 20, 100);
    const filter = {};
    if (category) filter.category = category;
    if (search) {
      const escaped = escapeRegex(search.slice(0, 100)); // cap length too
      filter.$or = [
        { name:   { $regex: escaped, $options: 'i' } },
        { nameHa: { $regex: escaped, $options: 'i' } },
        { tags:   { $elemMatch: { $regex: escaped, $options: 'i' } } },
      ];
    }

    const products = await Product.find(filter).limit(safeLimit).skip((parseInt(page,10) - 1) * safeLimit);
    const total = await Product.countDocuments(filter);
    res.json({ success: true, products, total });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/marketplace/products/recommended?diagnosis=...
router.get('/products/recommended', auth, async (req, res) => {
  try {
    const { tags } = req.query; // comma-separated disease tags
    const tagList = tags ? tags.split(',') : [];
    const products = await Product.find({ tags: { $in: tagList }, stockQty: { $gt: 0 } }).limit(10);
    res.json({ success: true, products });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// POST /api/marketplace/products (admin only)
router.post('/products', auth, async (req, res) => {
  try {
    if (req.user.role !== 'admin') return res.status(403).json({ success: false, message: 'Admin only' });
    const product = await Product.create(req.body);
    res.status(201).json({ success: true, product });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
