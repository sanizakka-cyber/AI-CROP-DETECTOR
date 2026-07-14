const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const { requireAdmin } = require('../middleware/rbac');
const Diagnosis = require('../models/Diagnosis');
const User = require('../models/User');
const Consultation = require('../models/Consultation');

const IS_DEV = process.env.NODE_ENV !== 'production';

// GET /api/analytics/summary — farm-level health summary
router.get('/summary', auth, async (req, res) => {
  try {
    const userId = req.user.id;
    const [total, processed, crop, livestock] = await Promise.all([
      Diagnosis.countDocuments({ user: userId }),
      Diagnosis.countDocuments({ user: userId, status: 'processed' }),
      Diagnosis.countDocuments({ user: userId, type: 'crop' }),
      Diagnosis.countDocuments({ user: userId, type: 'livestock' }),
    ]);

    const recent = await Diagnosis.find({ user: userId, status: 'processed' })
      .sort({ createdAt: -1 }).limit(5)
      .select('type aiResult.primaryDiagnosis aiResult.severity createdAt');

    const severityCounts = await Diagnosis.aggregate([
      { $match: { user: userId, status: 'processed' } },
      { $group: { _id: '$aiResult.severity', count: { $sum: 1 } } },
    ]);

    res.json({ success: true, summary: { total, processed, crop, livestock, recent, severityCounts } });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/analytics/admin-summary - CEO platform overview
router.get('/admin-summary', auth, requireAdmin, async (req, res) => {
  try {
    const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
    const sevenDaysAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);

    const [
      totalUsers,
      farmers,
      vets,
      agronomists,
      pendingExperts,
      activeMonthly,
      totalScans,
      processedScans,
      expertReviews,
      consultations,
      completedConsultations,
      treatmentOutcomes,
      severityCounts,
      userGrowth,
    ] = await Promise.all([
      User.countDocuments(),
      User.countDocuments({ role: 'farmer' }),
      User.countDocuments({ role: 'vet' }),
      User.countDocuments({ role: 'agronomist' }),
      User.countDocuments({ role: { $in: ['vet', 'agronomist', 'agro-dealer'] }, verificationStatus: 'pending' }),
      User.countDocuments({ lastSeen: { $gte: thirtyDaysAgo } }),
      Diagnosis.countDocuments(),
      Diagnosis.countDocuments({ status: 'processed' }),
      Diagnosis.countDocuments({ needsExpertReview: true }),
      Consultation.countDocuments(),
      Consultation.countDocuments({ status: 'completed' }),
      Diagnosis.aggregate([
        { $match: { status: 'processed' } },
        { $group: { _id: '$outcome', count: { $sum: 1 } } },
      ]),
      Diagnosis.aggregate([
        { $match: { status: 'processed' } },
        { $group: { _id: '$aiResult.severity', count: { $sum: 1 } } },
      ]),
      User.aggregate([
        { $match: { createdAt: { $gte: thirtyDaysAgo } } },
        {
          $group: {
            _id: { $dateToString: { format: '%Y-%m-%d', date: '$createdAt' } },
            count: { $sum: 1 },
          },
        },
        { $sort: { _id: 1 } },
      ]),
    ]);

    const successOutcomes = treatmentOutcomes
      .filter((item) => ['resolved', 'improving'].includes(item._id))
      .reduce((sum, item) => sum + item.count, 0);
    const outcomeTotal = treatmentOutcomes.reduce((sum, item) => sum + item.count, 0);

    res.json({
      success: true,
      summary: {
        users: {
          total: totalUsers,
          farmers,
          vets,
          agronomists,
          pendingExperts,
          activeMonthly,
          activeWeeklyWindowStart: sevenDaysAgo,
        },
        scans: {
          total: totalScans,
          processed: processedScans,
          expertReviews,
          processingRate: totalScans ? Math.round((processedScans / totalScans) * 100) : 0,
        },
        treatment: {
          successRate: outcomeTotal ? Math.round((successOutcomes / outcomeTotal) * 100) : 0,
          outcomes: treatmentOutcomes,
        },
        consultations: {
          total: consultations,
          completed: completedConsultations,
          completionRate: consultations ? Math.round((completedConsultations / consultations) * 100) : 0,
        },
        severityCounts,
        userGrowth,
      },
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/analytics/outbreaks — real regional disease aggregation and predictive logic
router.get('/outbreaks', auth, async (req, res) => {
  try {
    const sevenDaysAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
    
    // Aggregate recent diagnoses by LGA and disease
    const data = await Diagnosis.aggregate([
      { $match: { createdAt: { $gte: sevenDaysAgo }, status: 'processed' } },
      {
        $lookup: {
          from: 'farms',
          localField: 'farm',
          foreignField: '_id',
          as: 'farmInfo'
        }
      },
      { $unwind: '$farmInfo' },
      {
        $group: {
          _id: {
            lga: '$farmInfo.location.lga',
            disease: '$aiResult.primaryDiagnosis',
            type: '$type'
          },
          count: { $sum: 1 },
          avgConfidence: { $avg: '$aiResult.confidence' },
          lastReported: { $max: '$createdAt' }
        }
      },
      { $sort: { count: -1 } }
    ]);

    const outbreaks = data.map(o => ({
      disease: o._id.disease,
      region: o._id.lga || 'Katsina Region',
      severity: o.count > 5 ? 'critical' : o.count > 2 ? 'high' : 'medium',
      count: o.count,
      type: o._id.type,
      reportedAt: o.lastReported,
      forecast: o.count > 3 ? 'Spreading' : 'Stable'
    }));

    // Only show hardcoded demo stubs in development/offline mode
    if (outbreaks.length === 0 && IS_DEV) {
      outbreaks.push(
        { disease: 'Fall Armyworm', region: 'Funtua', severity: 'high', count: 12, type: 'crop', reportedAt: new Date(), forecast: 'Spreading' },
        { disease: 'Foot & Mouth',  region: 'Daura',  severity: 'medium', count: 4, type: 'livestock', reportedAt: new Date(), forecast: 'Stable' }
      );
    }

    res.json({ success: true, outbreaks });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/analytics/iot-data — IoT sensor simulation (Phase 3)
router.get('/iot-data', auth, async (req, res) => {
  try {
    const sensors = {
      soil: {
        moisture: (45 + Math.random() * 15).toFixed(1) + '%',
        ph: (6.5 + Math.random() * 1.0).toFixed(1),
        temp: (28 + Math.random() * 5).toFixed(1) + '°C',
        status: 'Optimal'
      },
      weather: {
        humidity: (60 + Math.random() * 20).toFixed(0) + '%',
        rainfall_forecast: '30% chance of showers',
        wind_speed: '12 km/h'
      },
      livestock_trackers: {
        active: 14,
        health_alerts: 0,
        grazing_area: 'Zone B'
      }
    };
    res.json({ success: true, sensors });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/analytics/insurability (Phase 3)
router.get('/insurability', auth, async (req, res) => {
  try {
    const diagnoses = await Diagnosis.find({ user: req.user.id });
    const resolvedCount = diagnoses.filter(d => d.outcome === 'resolved').length;
    const totalCount = diagnoses.length;
    
    let score = 50; // Base score
    if (totalCount > 0) {
      score = Math.min(100, 50 + (resolvedCount / totalCount * 40) + (totalCount * 2));
    }

    res.json({ 
      success: true, 
      creditScore: score.toFixed(0),
      tier: score > 80 ? 'Gold' : score > 60 ? 'Silver' : 'Bronze',
      eligibleForInsurance: score > 65
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
