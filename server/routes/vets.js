const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const { requireAdmin } = require('../middleware/rbac');
const User = require('../models/User');

router.get('/', auth, async (req, res) => {
  try {
    const experts = await User.find({
      role: { $in: ['vet', 'agronomist'] },
      verificationStatus: 'approved',
    })
      .select('name phone role state lga expertProfile verificationStatus isVerified')
      .sort({ 'expertProfile.rating': -1, createdAt: -1 });

    res.json({ success: true, experts });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

router.get('/pending', auth, requireAdmin, async (req, res) => {
  try {
    const applicants = await User.find({
      role: { $in: ['vet', 'agronomist', 'agro-dealer'] },
      verificationStatus: 'pending',
    })
      .select('name phone email role state lga expertProfile dealerProfile createdAt')
      .sort({ createdAt: -1 });

    res.json({ success: true, applicants });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

router.patch('/:id/verification', auth, requireAdmin, async (req, res) => {
  try {
    const { status, notes } = req.body;
    if (!['approved', 'rejected', 'pending'].includes(status)) {
      return res.status(400).json({ success: false, message: 'Invalid verification status' });
    }

    const user = await User.findByIdAndUpdate(
      req.params.id,
      {
        verificationStatus: status,
        verificationNotes: notes,
        isVerified: status === 'approved',
      },
      { new: true },
    ).select('name phone role verificationStatus isVerified verificationNotes');

    if (!user) return res.status(404).json({ success: false, message: 'Applicant not found' });
    res.json({ success: true, user });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
