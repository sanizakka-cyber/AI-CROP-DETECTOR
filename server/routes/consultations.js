const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const Consultation = require('../models/Consultation');
const Diagnosis = require('../models/Diagnosis');
const User = require('../models/User');

const EXPERT_ROLES = {
  crop: 'agronomist',
  livestock: 'vet',
};

function requireRole(roles) {
  return (req, res, next) => {
    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ success: false, message: 'You do not have permission for this action' });
    }
    next();
  };
}

function mapUrgency(diagnosis) {
  const severity = diagnosis?.aiResult?.severity;
  if (severity === 'emergency') return 'emergency';
  if (severity === 'severe') return 'urgent';
  if (diagnosis?.needsExpertReview) return 'urgent';
  return 'monitor';
}

router.get('/available-experts', auth, async (req, res) => {
  try {
    const { caseType = 'livestock', language } = req.query;
    const role = EXPERT_ROLES[caseType] || 'vet';
    const experts = await User.find({
      role,
      verificationStatus: 'approved',
      isVerified: true,
      isActive: { $ne: false },
    })
      .select('name phone role state lga expertProfile')
      .sort({ 'expertProfile.rating': -1, 'expertProfile.averageResponseMinutes': 1 })
      .limit(5);

    const data = experts.map((expert) => ({
      id: expert._id,
      name: expert.name,
      phone: expert.phone,
      role: expert.role,
      specialization: expert.expertProfile?.specialization,
      fee: expert.expertProfile?.consultationFee || 0,
      rating: expert.expertProfile?.rating || 0,
      responseMinutes: expert.expertProfile?.averageResponseMinutes || 30,
      languageMatch: language ? expert.expertProfile?.languages?.includes(language) : true,
      location: [expert.state, expert.lga].filter(Boolean).join(', '),
    }));

    res.json({ success: true, experts: data });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

router.post('/', auth, async (req, res) => {
  try {
    const {
      diagnosisId,
      consultationType = 'chat',
      expertId,
      farmerNotes,
      scheduledFor,
      language,
    } = req.body;

    const diagnosis = diagnosisId ? await Diagnosis.findById(diagnosisId) : null;
    if (diagnosis && String(diagnosis.user) !== String(req.user.id)) {
      return res.status(403).json({ success: false, message: 'You can only consult on your own diagnosis' });
    }

    const caseType = diagnosis?.type || req.body.caseType;
    if (!['crop', 'livestock'].includes(caseType)) {
      return res.status(400).json({ success: false, message: 'caseType must be crop or livestock' });
    }

    // Validate expert exists and is verified, if provided
    let resolvedExpert = expertId;
    if (expertId) {
      const expert = await User.findOne({ _id: expertId, verificationStatus: 'approved', isVerified: true });
      if (!expert) return res.status(400).json({ success: false, message: 'Selected expert not found or not verified.' });
      resolvedExpert = expert._id;
    }

    const consultation = await Consultation.create({
      farmer: req.user.id,
      expert: resolvedExpert,
      diagnosis: diagnosisId,
      caseType,
      consultationType,
      urgency: req.body.urgency || mapUrgency(diagnosis),
      status: resolvedExpert ? 'matched' : 'pending',
      language: language || req.user.language || 'ha',
      farmerNotes,
      scheduledFor,
    });

    res.status(201).json({ success: true, consultation });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

router.get('/queue', auth, requireRole(['vet', 'agronomist', 'admin']), async (req, res) => {
  try {
    const filter = {};
    if (req.user.role === 'vet') filter.caseType = 'livestock';
    if (req.user.role === 'agronomist') filter.caseType = 'crop';
    if (req.user.role !== 'admin') {
      filter.$or = [{ expert: req.user.id }, { expert: null }];
    }

    const consultations = await Consultation.find(filter)
      .populate('farmer', 'name phone state lga village language')
      .populate('expert', 'name phone role expertProfile.specialization')
      .populate('diagnosis', 'type cropType animalType assessmentType aiResult status createdAt')
      .sort({ urgency: 1, createdAt: -1 })
      .limit(50);

    res.json({ success: true, consultations });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

router.patch('/:id/complete', auth, requireRole(['vet', 'agronomist', 'admin']), async (req, res) => {
  try {
    const consultation = await Consultation.findById(req.params.id);
    if (!consultation) return res.status(404).json({ success: false, message: 'Consultation not found' });

    // Non-admin experts can only complete consultations assigned to them
    if (req.user.role !== 'admin' && consultation.expert &&
        String(consultation.expert) !== String(req.user.id)) {
      return res.status(403).json({ success: false, message: 'You can only complete consultations assigned to you.' });
    }

    consultation.status        = 'completed';
    consultation.expert        = req.user.role === 'admin' ? req.body.expertId : req.user.id;
    consultation.expertNotes   = req.body.expertNotes;
    consultation.prescription  = req.body.prescription;
    consultation.paymentStatus = req.body.paymentStatus || 'released';
    await consultation.save();

    res.json({ success: true, consultation });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
