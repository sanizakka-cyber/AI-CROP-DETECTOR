// API Configuration — connects mobile to the Laravel backend
// EXPO_PUBLIC_API_URL is set per build profile in eas.json
// For local dev: set in mobile/.env
// For production: set EXPO_PUBLIC_API_URL=https://yourdomain.com/api in eas.json build.production.env
const BASE_URL = (process.env.EXPO_PUBLIC_API_URL || 'https://msas-api.onrender.com/api').replace(/\/$/, '');

import AsyncStorage from '@react-native-async-storage/async-storage';

const getToken = async () => AsyncStorage.getItem('token');

/** Central request helper with automatic 401 interception */
const request = async (path, options = {}) => {
  const token = await getToken();
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  };
  const res = await fetch(`${BASE_URL}${path}`, { ...options, headers });

  // Auto-logout on expired / invalid token
  if (res.status === 401) {
    await AsyncStorage.removeItem('token');
    // Dispatch a custom event that AuthContext listens to
    if (typeof globalThis.__onAuthExpired === 'function') globalThis.__onAuthExpired();
    throw new Error('Session expired. Please log in again.');
  }

  const data = await res.json();
  if (!res.ok) throw new Error(data.message || 'Request failed');
  return data;
};

// ── Auth ──────────────────────────────────────────────────────────────────────
export const authAPI = {
  register: (body) => request('/auth/register', { method: 'POST', body: JSON.stringify(body) }),
  login:    (body) => request('/auth/login',    { method: 'POST', body: JSON.stringify(body) }),
  me:       ()     => request('/auth/me'),
  updateProfile: (body) => request('/auth/profile', { method: 'PATCH', body: JSON.stringify(body) }),
};

// ── Farms ─────────────────────────────────────────────────────────────────────
export const farmsAPI = {
  list:   ()     => request('/farms'),
  get:    (id)   => request(`/farms/${id}`),
  create: (body) => request('/farms', { method: 'POST', body: JSON.stringify(body) }),
  update: (id, body) => request(`/farms/${id}`, { method: 'PATCH', body: JSON.stringify(body) }),
};

// ── Animals ───────────────────────────────────────────────────────────────────
export const animalsAPI = {
  list:   (params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(`/animals?${qs}`);
  },
  get:    (id)   => request(`/animals/${id}`),
  create: (body) => request('/animals', { method: 'POST', body: JSON.stringify(body) }),
  update: (id, body) => request(`/animals/${id}`, { method: 'PATCH', body: JSON.stringify(body) }),
  delete: (id)   => request(`/animals/${id}`, { method: 'DELETE' }),
};

// ── Diagnose ──────────────────────────────────────────────────────────────────
export const diagnoseAPI = {
  /**
   * Upload images and metadata for crop diagnosis.
   * Uses FormData (not JSON) for multipart upload.
   */
  crop: async ({ cropType, cropPart, farmId, images }) => {
    const token = await getToken();
    const form = new FormData();
    form.append('cropType', cropType || '');
    form.append('cropPart', cropPart || '');
    if (farmId) form.append('farmId', farmId);
    images.forEach((img, i) => {
      form.append('images', { uri: img.uri, name: `img_${i}.jpg`, type: 'image/jpeg' });
    });
    const res = await fetch(`${BASE_URL}/diagnose/crop`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: form,
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Upload failed');
    return data;
  },

  livestock: async ({ animalId, animalType, assessmentType, farmId, symptoms, behavioral, images }) => {
    const token = await getToken();
    const form = new FormData();
    form.append('animalType', animalType || '');
    form.append('assessmentType', assessmentType || 'comprehensive');
    if (animalId) form.append('animalId', animalId);
    if (farmId) form.append('farmId', farmId);
    if (symptoms) form.append('symptoms', JSON.stringify(symptoms));
    if (behavioral) form.append('behavioral', JSON.stringify(behavioral));
    images.forEach((img, i) => {
      form.append('images', { uri: img.uri, name: `img_${i}.jpg`, type: 'image/jpeg' });
    });
    const res = await fetch(`${BASE_URL}/diagnose/livestock`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: form,
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Upload failed');
    return data;
  },

  get:     (id) => request(`/diagnose/${id}`),
  history: (params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(`/diagnose?${qs}`);
  },
  feedback: (id, body) => request(`/diagnose/${id}/feedback`, { method: 'PATCH', body: JSON.stringify(body) }),
};

// ── Analytics ─────────────────────────────────────────────────────────────────
export const analyticsAPI = {
  summary:      () => request('/analytics/summary'),
  adminSummary: () => request('/analytics/admin-summary'),
  outbreaks:    () => request('/analytics/outbreaks'),
  outcomes:     () => request('/analytics/outcomes'),
  insurability: () => request('/analytics/insurability'),
};

// ── Marketplace ───────────────────────────────────────────────────────────────
export const marketplaceAPI = {
  products: (params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(`/marketplace/products?${qs}`);
  },
  product:     (id)    => request(`/marketplace/products/${id}`),
  categories:  ()      => request('/marketplace/products/categories'),
  recommended: (tags = []) => {
    const qs = tags.map(t => `tags[]=${encodeURIComponent(t)}`).join('&');
    return request(`/marketplace/products/recommended?${qs}`);
  },
  addReview: (id, body) => request(`/marketplace/products/${id}/reviews`, { method: 'POST', body: JSON.stringify(body) }),
};

// ── Cart ──────────────────────────────────────────────────────────────────────
export const cartAPI = {
  get:    ()              => request('/cart'),
  count:  async ()        => { try { const d = await request('/cart'); return d.count || 0; } catch { return 0; } },
  add:    (productId, qty = 1) => request('/cart', { method: 'POST', body: JSON.stringify({ product_id: productId, quantity: qty }) }),
  update: (itemId, qty)   => request(`/cart/${itemId}`, { method: 'PUT', body: JSON.stringify({ quantity: qty }) }),
  remove: (itemId)        => request(`/cart/${itemId}`, { method: 'DELETE' }),
  clear:  ()              => request('/cart', { method: 'DELETE' }),
};

// ── Orders ────────────────────────────────────────────────────────────────────
export const ordersAPI = {
  list:     (params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(`/orders?${qs}`);
  },
  get:      (id)          => request(`/orders/${id}`),
  checkout: (body)        => request('/orders/checkout', { method: 'POST', body: JSON.stringify(body) }),
  cancel:   (id)          => request(`/orders/${id}/cancel`, { method: 'POST' }),
};

// ── Connection health ─────────────────────────────────────────────────────────
export const healthAPI = {
  check: async () => {
    try {
      const res = await fetch(`${BASE_URL}/health`, {
        method: 'GET',
        headers: { Accept: 'application/json' },
        signal: AbortSignal.timeout(5000),
      });
      if (!res.ok) return { ok: false, status: res.status };
      const data = await res.json();
      return { ok: true, ...data, url: BASE_URL };
    } catch (e) {
      return { ok: false, error: e.message, url: BASE_URL };
    }
  },
};

// ── Dealer ────────────────────────────────────────────────────────────────────
export const dealerAPI = {
  products:      (params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(`/dealer/products?${qs}`);
  },
  createProduct: (body)   => request('/dealer/products', { method: 'POST', body: JSON.stringify(body) }),
  updateProduct: (id, body) => request(`/dealer/products/${id}`, { method: 'PUT', body: JSON.stringify(body) }),
  deleteProduct: (id)     => request(`/dealer/products/${id}`, { method: 'DELETE' }),
  adjustStock:   (id, adjustment, reason) => request(`/dealer/products/${id}/stock`, { method: 'PATCH', body: JSON.stringify({ adjustment, reason }) }),
  orders:        (params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(`/dealer/orders?${qs}`);
  },
  updateOrderStatus: (id, status) => request(`/dealer/orders/${id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) }),
  markPaid:      (id, ref) => request(`/dealer/orders/${id}/paid`, { method: 'PATCH', body: JSON.stringify({ payment_reference: ref }) }),
};
