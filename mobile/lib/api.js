// API Configuration — connects mobile to the Laravel backend
// EXPO_PUBLIC_API_URL is set per build profile in eas.json
// For local dev: set in mobile/.env
// For production: set EXPO_PUBLIC_API_URL=https://yourdomain.com/api in eas.json build.production.env
//
// MUST be the "www" host: msasagro.com (bare) 301-redirects to
// www.msasagro.com, and that redirect strips the Authorization header
// (standard cross-host-redirect behavior in every HTTP client) — every
// authenticated request was silently losing its token on the redirect,
// which is what caused "login succeeds, then immediately logged out".
// Confirmed by testing both hosts directly against production.
const BASE_URL = (process.env.EXPO_PUBLIC_API_URL || 'https://www.msasagro.com/api').replace(/\/$/, '');

import AsyncStorage from '@react-native-async-storage/async-storage';

const getToken = async () => AsyncStorage.getItem('token');

/**
 * ApiError carries enough structure for screens to show either a generic
 * toast (err.message, already a good human-readable string) or highlight a
 * specific field (err.fieldErrors.state, etc — Laravel's 422 `errors` shape).
 * err.kind lets callers tell "you typed something wrong" apart from
 * "the network/server failed", which a bare Error can't distinguish.
 */
export class ApiError extends Error {
  constructor(message, { kind = 'server', status = null, fieldErrors = null } = {}) {
    super(message);
    this.kind = kind;               // 'network' | 'timeout' | 'validation' | 'auth' | 'server'
    this.status = status;
    this.fieldErrors = fieldErrors; // Laravel's { field: [messages] } from a 422, or null
  }
}

/**
 * First message out of a { field: [msg, ...] } validation error bag.
 * This backend's global exception renderer (bootstrap/app.php) returns
 * { error, details } for every API error, not Laravel's textbook-default
 * { message, errors } — 'details' is checked first since that's what this
 * app actually sends; 'errors' stays as a fallback in case some endpoint
 * doesn't go through that renderer.
 */
function firstFieldError(data) {
  const errors = data?.details || data?.errors;
  if (!errors || typeof errors !== 'object') return null;
  const first = Object.values(errors)[0];
  return Array.isArray(first) ? first[0] : null;
}

// Status-code-specific fallback messages, used when the backend didn't send
// its own error body for that response. Real backend messages (data.error /
// data.details) always take priority over these — see below.
const STATUS_FALLBACKS = {
  400: 'MSAS could not process that request. Please try again.',
  401: 'Email/phone or password is incorrect.',
  403: "You don't have permission to do that.",
  404: 'That MSAS resource could not be found.',
  409: 'This conflicts with existing data. Please check and try again.',
  422: 'Some of your information is invalid. Please check the highlighted fields.',
  429: 'Too many attempts. Please wait a moment and try again.',
  500: 'MSAS server error. Please try again later.',
  502: 'MSAS is temporarily unavailable (bad gateway). Please try again shortly.',
  503: 'MSAS is temporarily unavailable. Please try again shortly.',
};

function kindForStatus(status) {
  if (status === 401 || status === 403) return 'auth';
  if (status === 422 || status === 400 || status === 409) return 'validation';
  if (status === 429) return 'rate_limit';
  if (status >= 500) return 'server';
  return 'validation';
}

/** Feature-detect AbortSignal.timeout — don't let an unsupported API masquerade as "no internet." */
function timeoutSignal(ms) {
  try {
    if (typeof AbortSignal !== 'undefined' && typeof AbortSignal.timeout === 'function') {
      return AbortSignal.timeout(ms);
    }
  } catch {}
  return undefined;
}

/** Central request helper with automatic 401 interception */
const request = async (path, options = {}) => {
  const token = await getToken();
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  };

  const url = `${BASE_URL}${path}`;
  let res;
  try {
    res = await fetch(url, { ...options, headers, signal: options.signal ?? timeoutSignal(20000) });
  } catch (e) {
    // React Native's fetch throws a specific "TypeError: Network request
    // failed" for real connectivity/DNS/TLS failures — that's the only case
    // that should say "check your internet connection." Anything else (a
    // bug in this function, an unsupported API, a thrown non-Error value)
    // gets its real message surfaced instead of being relabeled as a
    // network problem, which was hiding the actual cause.
    if (e?.name === 'TimeoutError' || e?.name === 'AbortError') {
      throw new ApiError('The MSAS server took too long to respond. Please try again.', { kind: 'timeout' });
    }
    if (e?.message === 'Network request failed' || e?.name === 'TypeError') {
      throw new ApiError('MSAS could not be reached. Please check your internet connection and try again.', { kind: 'network' });
    }
    throw new ApiError(`Unexpected error before reaching MSAS: ${e?.message || String(e)}`, { kind: 'client' });
  }

  // Auto-logout on expired / invalid token — but not for `background` calls
  // (push-token registration, best-effort fetches the caller already wraps
  // in .catch(() => {})). Those are written to fail silently and shouldn't
  // be able to tear down an otherwise perfectly valid session just because
  // that one particular endpoint rejected the request for its own reasons.
  if (res.status === 401 && token && !options.background) {
    // Which endpoint actually triggered the session wipe — the single most
    // useful line in `adb logcat` for tracing "login succeeded, then
    // immediately logged out" back to its actual cause.
    console.log(`[AUTH DEBUG] 401 on ${options.method || 'GET'} ${path} with a token present -> clearing session`);
    await AsyncStorage.removeItem('token');
    if (typeof globalThis.__onAuthExpired === 'function') globalThis.__onAuthExpired();
    throw new ApiError('Session expired. Please log in again.', { kind: 'auth', status: 401 });
  }

  let data = {};
  try {
    const text = await res.text();
    data = text ? JSON.parse(text) : {};
  } catch {
    throw new ApiError(
      res.ok
        ? 'The server returned an unexpected response. Please try again.'
        : (STATUS_FALLBACKS[res.status] || `MSAS returned an unexpected error (${res.status}).`),
      { kind: kindForStatus(res.status), status: res.status }
    );
  }

  if (!res.ok) {
    // This backend's validation failures carry { error: 'Validation
    // failed.', details: { field: [...] } } — the top-level 'error' is
    // always the generic string, so prefer the first field-specific
    // message, then the backend's own top-level message, then a
    // status-specific fallback — never a one-size-fits-all string.
    const fieldMessage = firstFieldError(data);
    const message = fieldMessage || data.error || data.message || STATUS_FALLBACKS[res.status] || `Request failed (${res.status}).`;
    throw new ApiError(message, { kind: kindForStatus(res.status), status: res.status, fieldErrors: data.details || data.errors || null });
  }

  return data;
};

// ── Auth ──────────────────────────────────────────────────────────────────────
export const authAPI = {
  register:   (body) => request('/auth/register',    { method: 'POST', body: JSON.stringify(body) }),
  login:      (body) => request('/auth/login',        { method: 'POST', body: JSON.stringify(body) }),
  verifyOtp:  (identifier, code) => request('/auth/verify-otp', { method: 'POST', body: JSON.stringify({ identifier, code }) }),
  resendOtp:  (identifier)       => request('/auth/resend-otp', { method: 'POST', body: JSON.stringify({ identifier }) }),
  me:       ()     => request('/auth/me'),
  updateProfile: (body) => request('/auth/profile', { method: 'PATCH', body: JSON.stringify(body) }),
};

// ── Locations (states/LGAs/countries for registration & profile forms) ─────────
export const locationsAPI = {
  list: () => request('/locations'),
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
    // Both are optional hints — omit entirely when unset so the AI engine
    // identifies the crop/plant part on its own, matching the web scan form.
    if (cropType) form.append('cropType', cropType);
    if (cropPart) form.append('cropPart', cropPart);
    if (farmId) form.append('farmId', farmId);
    images.forEach((img, i) => {
      // 'images[]' — PHP/Laravel only collects a multipart field into a
      // real array when the field name carries the [] suffix; multiple
      // parts all named plain 'images' collapse to a single value instead
      // of an array, which is exactly what produced "The images field
      // must be an array." on every scan attempt.
      form.append('images[]', { uri: img.uri, name: `img_${i}.jpg`, type: 'image/jpeg' });
    });
    const res = await fetch(`${BASE_URL}/diagnose/crop`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: form,
    });
    const data = await res.json();
    if (!res.ok) throw new Error(firstFieldError(data) || data.error || data.message || 'Upload failed');
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
      // 'images[]' — PHP/Laravel only collects a multipart field into a
      // real array when the field name carries the [] suffix; multiple
      // parts all named plain 'images' collapse to a single value instead
      // of an array, which is exactly what produced "The images field
      // must be an array." on every scan attempt.
      form.append('images[]', { uri: img.uri, name: `img_${i}.jpg`, type: 'image/jpeg' });
    });
    const res = await fetch(`${BASE_URL}/diagnose/livestock`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: form,
    });
    const data = await res.json();
    if (!res.ok) throw new Error(firstFieldError(data) || data.error || data.message || 'Upload failed');
    return data;
  },

  soil: async ({ soilContext, images }) => {
    const token = await getToken();
    const form = new FormData();
    if (soilContext) form.append('soilContext', soilContext);
    images.forEach((img, i) => {
      form.append('images[]', { uri: img.uri, name: `img_${i}.jpg`, type: 'image/jpeg' });
    });
    const res = await fetch(`${BASE_URL}/diagnose/soil`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: form,
    });
    const data = await res.json();
    if (!res.ok) throw new Error(firstFieldError(data) || data.error || data.message || 'Upload failed');
    return data;
  },

  pest: async ({ cropType, location, images }) => {
    const token = await getToken();
    const form = new FormData();
    if (cropType) form.append('cropType', cropType);
    if (location) form.append('location', location);
    images.forEach((img, i) => {
      form.append('images[]', { uri: img.uri, name: `img_${i}.jpg`, type: 'image/jpeg' });
    });
    const res = await fetch(`${BASE_URL}/diagnose/pest`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: form,
    });
    const data = await res.json();
    if (!res.ok) throw new Error(firstFieldError(data) || data.error || data.message || 'Upload failed');
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

// ── Profile ───────────────────────────────────────────────────────────────────
export const profileAPI = {
  update:       (body)  => request('/auth/profile',    { method: 'PATCH', body: JSON.stringify(body) }),
  updateFcmToken:(body) => request('/auth/fcm-token',  { method: 'POST',  body: JSON.stringify(body), background: true }),
};

// ── Subscription ──────────────────────────────────────────────────────────────
export const subscriptionAPI = {
  status:    () => request('/subscription/status'),
  subscribe: (plan, cycle) => request('/subscription/subscribe', { method: 'POST', body: JSON.stringify({ plan, cycle }) }),
  cancel:    (reason) => request('/subscription/cancel', { method: 'POST', body: JSON.stringify({ reason }) }),
};

// ── Weather ───────────────────────────────────────────────────────────────────
export const weatherAPI = {
  current: (params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(`/weather${qs ? '?' + qs : ''}`);
  },
};

// ── Notifications ─────────────────────────────────────────────────────────────
export const notificationsAPI = {
  list:       (params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(`/notifications${qs ? '?' + qs : ''}`);
  },
  markRead:   (id)  => request(`/notifications/${id}/read`, { method: 'PATCH' }),
  markAllRead:()    => request('/notifications/read-all',   { method: 'POST' }),
  delete:     (id)  => request(`/notifications/${id}`,      { method: 'DELETE' }),
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
