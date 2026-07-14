const jwt = require('jsonwebtoken');
const User = require('../models/User');

if (!process.env.JWT_SECRET) {
  throw new Error('FATAL: JWT_SECRET environment variable is not set. Server cannot start safely.');
}

const IS_DEV = process.env.NODE_ENV !== 'production';

// Demo accounts — ONLY active in development/offline mode, never in production.
const DEMO_USERS = {
  demo_user_id:       { id: 'demo_user_id',       name: 'Aminu Yusuf',             phone: '08012345678', role: 'farmer',     language: 'ha', profilePic: null, isPremium: true, isVerified: true },
  demo_admin_id:      { id: 'demo_admin_id',      name: 'Abdulkadir Isyaku',       phone: 'admin',       role: 'admin',      language: 'en', profilePic: null, isPremium: true, isVerified: true },
  demo_vet_id:        { id: 'demo_vet_id',        name: 'Dr. Surajo Aminu',        phone: 'vet',         role: 'vet',        language: 'en', profilePic: null, isPremium: true, isVerified: true },
  demo_agronomist_id: { id: 'demo_agronomist_id', name: 'Rabi Shehu',              phone: 'agronomist',  role: 'agronomist', language: 'en', profilePic: null, isPremium: true, isVerified: true, verificationStatus: 'approved' },
};

module.exports = async (req, res, next) => {
  try {
    let token;
    if (req.headers.authorization && req.headers.authorization.startsWith('Bearer '))
      token = req.headers.authorization.split(' ')[1];

    if (!token) return res.status(401).json({ success: false, message: 'Not authenticated. Please log in.' });

    const decoded = jwt.verify(token, process.env.JWT_SECRET);

    // Demo bypass — development/offline only
    if (IS_DEV && DEMO_USERS[decoded.id]) {
      req.user = DEMO_USERS[decoded.id];
      return next();
    }

    const user = await User.findById(decoded.id);
    if (!user) return res.status(401).json({ success: false, message: 'User no longer exists.' });

    req.user = user;
    next();
  } catch (err) {
    res.status(401).json({ success: false, message: 'Invalid or expired token.' });
  }
};
