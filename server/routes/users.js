const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');

// Stub routes — auth protected  
router.get('/', auth, (req, res) => res.json({ success: true, data: [] }));
router.post('/', auth, (req, res) => res.status(201).json({ success: true }));
module.exports = router;
