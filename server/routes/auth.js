const express = require('express');
const router = express.Router();
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const User = require('../models/User');

const IS_DEV = process.env.NODE_ENV !== 'production';

// Roles a user may self-register as — admin/ceo must be created by an admin
const ALLOWED_SELF_REGISTER_ROLES = ['farmer', 'vet', 'agronomist', 'agro-dealer'];

const signToken = (id) =>
  jwt.sign({ id }, process.env.JWT_SECRET, { expiresIn: process.env.JWT_EXPIRES_IN || '7d' });

/**
 * Build complete user payload for all authenticated responses.
 * Ensures every user sees their own profile info regardless of role.
 */
const buildUserPayload = (user) => {
  const roleDisplayNames = {
    'farmer': 'Farmer',
    'vet': 'Veterinarian',
    'veterinarian': 'Veterinarian',
    'agronomist': 'Agronomist',
    'admin': 'Administrator',
    'agro-dealer': 'Agro Dealer',
    'extension-officer': 'Extension Worker',
    'extension-worker': 'Extension Worker',
    'researcher': 'Researcher',
  };

  return {
    id: user._id || user.id,
    name: user.name,
    phone: user.phone,
    email: user.email,
    role: user.role,
    roleDisplay: roleDisplayNames[user.role] || user.role,
    language: user.language,
    state: user.state,
    lga: user.lga,
    village: user.village,
    profilePic: user.profilePic || null,
    isPremium: user.isPremium || false,
    isVerified: user.isVerified || false,
    verificationStatus: user.verificationStatus,
    lastSeen: user.lastSeen,
    // Role-specific profiles
    ...(user.farmerProfile && { farmerProfile: user.farmerProfile }),
    ...(user.expertProfile && { expertProfile: user.expertProfile }),
    ...(user.dealerProfile && { dealerProfile: user.dealerProfile }),
  };
};

// POST /api/auth/register
router.post('/register', async (req, res) => {
  try {
    const {
      name,
      phone,
      email,
      password,
      language,
      role = 'farmer',
      state,
      lga,
      village,
      farmSize,
      farmSizeUnit,
      farmingExperienceYears,
      primaryActivity,
      cropsGrown,
      livestockCounts,
      licenseNumber,
      issuingAuthority,
      certification,
      specialization,
      yearsExperience,
      organization,
      practiceAddress,
      consultationFee,
      availabilitySchedule,
      expertLanguages,
      businessName,
      registrationNumber,
      storeAddress,
      productCategories,
      deliveryCoverage,
    } = req.body;
    if (!name || !phone || !password)
      return res.status(400).json({ success: false, message: 'Name, phone, and password are required' });
    if (password.length < 8)
      return res.status(400).json({ success: false, message: 'Password must be at least 8 characters long' });
    if (!ALLOWED_SELF_REGISTER_ROLES.includes(role))
      return res.status(400).json({ success: false, message: `Invalid role. Allowed: ${ALLOWED_SELF_REGISTER_ROLES.join(', ')}` });

    const exists = await User.findOne({ phone });
    if (exists) return res.status(409).json({ success: false, message: 'Phone number already registered' });

    const hashed = await bcrypt.hash(password, 12);
    const isExpert = ['vet', 'agronomist'].includes(role);
    const needsDealerReview = role === 'agro-dealer';
    const user = await User.create({
      name,
      phone,
      email,
      password: hashed,
      language,
      role,
      state,
      lga,
      village,
      isVerified: !isExpert && !needsDealerReview,
      verificationStatus: isExpert || needsDealerReview ? 'pending' : 'not_required',
      farmerProfile: role === 'farmer' ? {
        farmSize,
        farmSizeUnit,
        farmingExperienceYears,
        primaryActivity,
        cropsGrown,
        livestockCounts,
      } : undefined,
      expertProfile: isExpert ? {
        licenseNumber,
        issuingAuthority,
        certification,
        specialization,
        yearsExperience,
        organization,
        practiceAddress,
        consultationFee,
        availabilitySchedule,
        languages: expertLanguages || [language].filter(Boolean),
      } : undefined,
      dealerProfile: needsDealerReview ? {
        businessName,
        registrationNumber,
        storeAddress,
        productCategories,
        deliveryCoverage,
      } : undefined,
    });
    const token = signToken(user._id);

    res.status(201).json({
      success: true,
      token,
      user: buildUserPayload(user),
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// POST /api/auth/login
router.post('/login', async (req, res) => {
  try {
    const { phone, password } = req.body;
    if (!phone || !password)
      return res.status(400).json({ success: false, message: 'Phone and password required' });

    // ── Demo bypass — DEVELOPMENT / OFFLINE MODE ONLY ──────────────────────────
    if (IS_DEV) {
      const DEMO_LOGINS = [
        { phone: '08012345678', password: 'farmer123', user: { id: 'demo_user_id',       name: 'Aminu Yusuf',             phone: '08012345678', role: 'farmer',     language: 'ha', isPremium: true } },
        { phone: 'admin',       password: 'admin123',  user: { id: 'demo_admin_id',      name: 'Abdulkadir Isyaku',       phone: 'admin',       role: 'admin',      language: 'en', isPremium: true } },
        { phone: 'vet',         password: 'vet123',    user: { id: 'demo_vet_id',        name: 'Dr. Surajo Aminu',        phone: 'vet',         role: 'vet',        language: 'en', isPremium: true } },
        { phone: 'agronomist',  password: 'agro123',   user: { id: 'demo_agronomist_id', name: 'Rabi Shehu',              phone: 'agronomist',  role: 'agronomist', language: 'en', isPremium: true, verificationStatus: 'approved' } },
      ];
      const demo = DEMO_LOGINS.find(d => d.phone === phone && d.password === password);
      if (demo) {
        const token = jwt.sign({ id: demo.user.id }, process.env.JWT_SECRET, { expiresIn: '7d' });
        return res.json({ success: true, token, user: demo.user });
      }
    }
    // ────────────────────────────────────────────────────────────────────────────

    const user = await User.findOne({ phone }).select('+password');
    if (!user || !(await bcrypt.compare(password, user.password)))
      return res.status(401).json({ success: false, message: 'Invalid credentials' });

    user.lastSeen = new Date();
    await user.save({ validateBeforeSave: false });

    const token = signToken(user._id);
    res.json({ success: true, token, user: buildUserPayload(user) });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/auth/me
router.get('/me', require('../middleware/auth'), async (req, res) => {
  try {
    // For demo users, return their full payload directly
    if (req.user.id === 'demo_user_id' || req.user.id === 'demo_admin_id' || 
        req.user.id === 'demo_vet_id' || req.user.id === 'demo_agronomist_id') {
      return res.json({ success: true, user: req.user });
    }
    
    // For real users, fetch fresh data from database
    const user = await User.findById(req.user.id || req.user._id);
    if (!user) {
      return res.status(404).json({ success: false, message: 'User not found' });
    }
    
    res.json({ success: true, user: buildUserPayload(user) });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// PATCH /api/auth/profile — Update user profile (name, profilePic, etc.)
router.patch('/profile', require('../middleware/auth'), async (req, res) => {
  try {
    const { name, profilePic, language, state, lga, village } = req.body;
    const userId = req.user.id || req.user._id;
    
    const user = await User.findById(userId);
    if (!user) {
      return res.status(404).json({ success: false, message: 'User not found' });
    }

    // Update only provided fields
    if (name) user.name = name;
    if (profilePic) user.profilePic = profilePic;
    if (language) user.language = language;
    if (state) user.state = state;
    if (lga) user.lga = lga;
    if (village) user.village = village;

    await user.save();
    res.json({ success: true, user: buildUserPayload(user) });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
