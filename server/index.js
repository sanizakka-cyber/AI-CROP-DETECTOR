require('dotenv').config();
const express = require('express');
const cors = require('cors');
const mongoose = require('mongoose');
const rateLimit = require('express-rate-limit');

const app = express();
const PORT = process.env.PORT || 5000;
const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/farmai';

// Restrict CORS to known origins
const defaultOrigins = [
  'http://localhost:3000',
  'http://localhost:8081',
  'https://msasagro.com',
  'https://www.msasagro.com',
];
const allowedOrigins = (process.env.CORS_ORIGINS || defaultOrigins.join(',')).split(',');
app.use(cors({
  origin: (origin, cb) => {
    // Allow no-origin requests (mobile apps, curl) in dev only
    if (!origin || allowedOrigins.includes(origin) || process.env.NODE_ENV !== 'production') return cb(null, true);
    cb(new Error(`CORS: origin ${origin} not allowed`));
  },
  credentials: true,
}));

// General rate limit — 100 req / 15 min per IP
const generalLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100,
  standardHeaders: true,
  legacyHeaders: false,
  message: { success: false, message: 'Too many requests, please try again later.' },
});
// Strict rate limit on auth — 10 attempts / 15 min
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 10,
  standardHeaders: true,
  legacyHeaders: false,
  message: { success: false, message: 'Too many login attempts, please try again later.' },
});

app.use(generalLimiter);
app.use(express.json({ limit: '20mb' }));
app.use(express.urlencoded({ extended: true, limit: '20mb' }));

app.use('/api/auth',          authLimiter, require('./routes/auth'));
app.use('/api/users',         require('./routes/users'));
app.use('/api/farms',         require('./routes/farms'));
app.use('/api/animals',       require('./routes/animals'));
app.use('/api/crops',         require('./routes/crops'));
app.use('/api/diagnose',      require('./routes/diagnose'));
app.use('/api/treatments',    require('./routes/treatments'));
app.use('/api/alerts',        require('./routes/alerts'));
app.use('/api/marketplace',   require('./routes/marketplace'));
app.use('/api/vets',          require('./routes/vets'));
app.use('/api/analytics',     require('./routes/analytics'));
app.use('/api/consultations', require('./routes/consultations'));

app.get('/api/health', (req, res) => {
  res.json({
    status: 'ok',
    service: 'MSAS FarmAI Diagnostic API',
    version: '1.0.0',
    database: mongoose.connection.readyState === 1 ? 'connected' : 'offline',
    timestamp: new Date(),
  });
});

app.use((err, req, res, next) => {
  const isDev = process.env.NODE_ENV !== 'production';
  // Only log stack traces server-side; never expose them in responses
  if (isDev) console.error(err.stack);
  else console.error(`[ERROR] ${err.message}`);
  res.status(err.status || 500).json({
    success: false,
    message: err.message || 'Internal Server Error',
    ...(isDev ? { stack: err.stack } : {}),
  });
});

const listen = (mode) => {
  app.listen(PORT, () => console.log(`Server running on port ${PORT} (${mode})`));
};

const startServer = async () => {
  try {
    await mongoose.connect(MONGO_URI);
    console.log('MongoDB connected');
    listen('MongoDB connected');
  } catch (err) {
    console.error('MongoDB connection failed:', err.message);

    if (process.env.USE_MEMORY_DB !== 'true') {
      console.log('Starting API in DB-offline demo mode. Set USE_MEMORY_DB=true to try mongodb-memory-server.');
      listen('DB offline');
      return;
    }

    try {
      const { MongoMemoryServer } = require('mongodb-memory-server');
      const mongoServer = await MongoMemoryServer.create();
      const uri = mongoServer.getUri();

      await mongoose.connect(uri);
      console.log(`In-memory MongoDB connected: ${uri}`);

      const { seed } = require('./scripts/seed');
      await seed(uri);

      listen('in-memory DB');
    } catch (memErr) {
      console.error('Failed to start in-memory DB:', memErr.message);
      listen('DB offline');
    }
  }
};

startServer();
