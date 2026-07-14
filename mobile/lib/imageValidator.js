/**
 * imageValidator.js
 * Real-time image quality validation before AI scanning.
 * Runs entirely on-device (no network) for instant feedback.
 */

/**
 * Analyse an image asset from expo-image-picker and return a validation result.
 * @param {object} asset  - An asset object from ImagePicker (has .uri, .width, .height, .exif, .fileSize)
 * @param {'crop'|'livestock'} scanType
 * @returns {{ valid: boolean, error?: string, warnings: string[], qualityScore: number }}
 */
export async function validateImageForScanning(asset, scanType = 'crop') {
  const warnings = [];
  let qualityScore = 100;

  // ── 1. Resolution check ─────────────────────────────────────────────────────
  const minWidth  = 640;
  const minHeight = 480;
  if (!asset.width || !asset.height) {
    return { valid: false, error: '❌ Unable to read image dimensions. Please retake the photo.', warnings, qualityScore: 0 };
  }
  if (asset.width < minWidth || asset.height < minHeight) {
    return {
      valid: false,
      error: `❌ Resolution too low (${asset.width}×${asset.height}). Minimum is ${minWidth}×${minHeight}. Use your main camera and get closer to the subject.`,
      warnings,
      qualityScore: 10,
    };
  }
  if (asset.width < 1280 || asset.height < 720) {
    warnings.push('⚠️ Low resolution — results may be less accurate. Try a higher quality setting.');
    qualityScore -= 15;
  }

  // ── 2. File size check (too small = likely a thumbnail / stock icon) ─────────
  if (asset.fileSize && asset.fileSize < 30_000) { // < 30 KB
    return {
      valid: false,
      error: '❌ Image file is too small. This may be a thumbnail or placeholder. Please capture a real photo.',
      warnings,
      qualityScore: 5,
    };
  }

  // ── 3. EXIF timestamp check (reject images older than 48 hours) ─────────────
  if (asset.exif) {
    const exifDate = asset.exif.DateTime || asset.exif.DateTimeOriginal;
    if (exifDate) {
      const taken = parseExifDate(exifDate);
      if (taken) {
        const ageHours = (Date.now() - taken.getTime()) / (1000 * 60 * 60);
        if (ageHours > 48) {
          return {
            valid: false,
            error: `❌ This image was taken ${Math.round(ageHours)} hours ago. Please capture a fresh photo (within 48 hours) for accurate results.`,
            warnings,
            qualityScore: 0,
          };
        }
        if (ageHours > 24) {
          warnings.push('⚠️ Image is over 24 hours old. A fresher photo will give better accuracy.');
          qualityScore -= 10;
        }
      }
    }
  }

  // ── 4. Aspect ratio sanity check (detect screenshots / non-photo formats) ────
  const ratio = asset.width / asset.height;
  if (ratio > 3.0 || ratio < 0.3) {
    warnings.push('⚠️ Unusual image dimensions detected. This may not be a camera photo.');
    qualityScore -= 20;
  }

  // ── 5. Source preference: camera > gallery ───────────────────────────────────
  // expo-image-picker .type === 'image' for both; we track source externally.
  // If source is 'gallery' we apply a trust penalty and extra warning.
  if (asset._source === 'gallery') {
    warnings.push('⚠️ Gallery uploads may not reflect the current condition. Camera capture is recommended for best accuracy.');
    qualityScore -= 10;
  }

  // ── 6. Minimum dimension for subject coverage (get close to subject) ─────────
  // For crop scans the leaf should fill a good portion of the frame.
  // We can't do real subject detection on-device without ML, so we use size as proxy.
  if (asset.width < 1000 && asset.height < 750 && qualityScore > 50) {
    warnings.push('ℹ️ For best results, get close enough to the subject to fill the frame.');
  }

  // ── 7. Final score decision ──────────────────────────────────────────────────
  qualityScore = Math.max(0, qualityScore);

  if (qualityScore < 30) {
    return { valid: false, error: '❌ Image quality too low for reliable diagnosis. Please retake with better lighting and focus.', warnings, qualityScore };
  }

  return { valid: true, warnings, qualityScore };
}

/**
 * Parse an EXIF date string like "2026:05:23 14:30:00" into a Date object.
 */
function parseExifDate(str) {
  try {
    // EXIF format: "YYYY:MM:DD HH:MM:SS"
    const [datePart, timePart] = str.split(' ');
    const [y, m, d] = datePart.split(':');
    const [hh, mm, ss] = (timePart || '00:00:00').split(':');
    return new Date(+y, +m - 1, +d, +hh, +mm, +ss);
  } catch {
    return null;
  }
}

/**
 * Returns a colour and label for a quality score.
 */
export function qualityLabel(score) {
  if (score >= 80) return { label: 'Excellent', color: '#16A34A' };
  if (score >= 60) return { label: 'Good',      color: '#65A30D' };
  if (score >= 40) return { label: 'Fair',       color: '#D97706' };
  return                  { label: 'Poor',       color: '#DC2626' };
}
