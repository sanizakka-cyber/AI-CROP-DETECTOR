// Phase 9 §B9: starter scan-upload coverage. validateImageForScanning()
// is the on-device gate before a scan is ever sent to the AI engine --
// pure, no native modules, so it's testable the same way locationCapture
// already is. Covers the real rejection/warning thresholds as written,
// not assumed values.
import { validateImageForScanning, qualityLabel } from '../imageValidator';

function asset(overrides = {}) {
  return { width: 1920, height: 1080, fileSize: 500_000, exif: null, ...overrides };
}

describe('validateImageForScanning', () => {
  test('rejects an image with unreadable dimensions', async () => {
    const result = await validateImageForScanning(asset({ width: undefined, height: undefined }));
    expect(result.valid).toBe(false);
    expect(result.qualityScore).toBe(0);
  });

  test('rejects resolution below the 640x480 floor', async () => {
    const result = await validateImageForScanning(asset({ width: 320, height: 240 }));
    expect(result.valid).toBe(false);
    expect(result.error).toMatch(/Resolution too low/);
  });

  test('warns but still accepts resolution between the floor and 1280x720', async () => {
    const result = await validateImageForScanning(asset({ width: 800, height: 600 }));
    expect(result.valid).toBe(true);
    expect(result.warnings.some((w) => /Low resolution/.test(w))).toBe(true);
  });

  test('rejects a file under 30KB as a likely thumbnail/placeholder', async () => {
    const result = await validateImageForScanning(asset({ fileSize: 10_000 }));
    expect(result.valid).toBe(false);
    expect(result.error).toMatch(/too small/);
  });

  test('rejects a photo with an EXIF timestamp older than 48 hours', async () => {
    const old = new Date(Date.now() - 72 * 60 * 60 * 1000);
    const exifStr = `${old.getFullYear()}:${String(old.getMonth() + 1).padStart(2, '0')}:${String(old.getDate()).padStart(2, '0')} ${String(old.getHours()).padStart(2, '0')}:${String(old.getMinutes()).padStart(2, '0')}:${String(old.getSeconds()).padStart(2, '0')}`;
    const result = await validateImageForScanning(asset({ exif: { DateTimeOriginal: exifStr } }));
    expect(result.valid).toBe(false);
    expect(result.error).toMatch(/hours ago/);
  });

  test('accepts a fresh, well-formed photo with no warnings', async () => {
    const result = await validateImageForScanning(asset());
    expect(result.valid).toBe(true);
    expect(result.warnings).toEqual([]);
    expect(result.qualityScore).toBe(100);
  });

  test('applies a trust penalty and warning for gallery-sourced images', async () => {
    const result = await validateImageForScanning(asset({ _source: 'gallery' }));
    expect(result.valid).toBe(true);
    expect(result.warnings.some((w) => /Gallery uploads/.test(w))).toBe(true);
    expect(result.qualityScore).toBeLessThan(100);
  });
});

describe('qualityLabel', () => {
  test.each([
    [95, 'Excellent'],
    [65, 'Good'],
    [45, 'Fair'],
    [10, 'Poor'],
  ])('score %i maps to %s', (score, label) => {
    expect(qualityLabel(score).label).toBe(label);
  });
});
