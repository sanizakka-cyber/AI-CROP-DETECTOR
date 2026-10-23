// Screenshots of the web scan form's location capture, with the browser's
// GPS faked by Playwright (position, accuracy, permission): live accuracy
// improving, success with rating, coordinates, auto-filled State/LGA and the
// map, the pin moved on the map, permission denied, a low-accuracy reading
// with its tips, and manual entry. Fails if any of them does not appear.
//   node location-capture.mjs <base url> <out dir>
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const [base, outDir] = process.argv.slice(2);
fs.mkdirSync(outDir, { recursive: true });
const FUNTUA = { latitude: 11.523, longitude: 7.312 };
const failures = [];
const check = (ok, what) => { if (!ok) failures.push(what); console.log(`${ok ? 'ok  ' : 'FAIL'} ${what}`); };

const browser = await chromium.launch();

async function openScanPage({ geo = true, width = 390 } = {}) {
  const context = await browser.newContext({
    viewport: { width, height: 900 }, deviceScaleFactor: 2,
    ...(geo ? { geolocation: { ...FUNTUA, accuracy: 18 }, permissions: ['geolocation'] } : {}),
  });
  const page = await context.newPage();
  await page.goto(`${base}/login`);
  const token = await page.inputValue('input[name="_token"]');
  await page.request.post(`${base}/login`, { form: { _token: token, login: 'umar.demo@example.test', password: 'screenshot-only' } });
  await page.goto(`${base}/diagnostics/scan`, { waitUntil: 'networkidle' });
  return { context, page };
}
const section = (page) => page.locator('#locModeGps').locator('xpath=ancestor::div[contains(@class,"border-slate-200")][1]');
const shot = async (page, name) => section(page).screenshot({ path: path.join(outDir, `location-${name}.png`) });

// 1–3. Capture: ±18 m → ±7 m live, then ±4 m (target reached), names filled, map; then move the pin.
{
  const { context, page } = await openScanPage();
  await context.setGeolocation({ ...FUNTUA, accuracy: 18 }); // a fresh timestamp for the emulated fix
  await page.click('#locModeGps');
  await page.waitForSelector('#locProgressText:has-text("±18")');
  await context.setGeolocation({ ...FUNTUA, accuracy: 7 });
  await page.waitForSelector('#locProgressText:has-text("±18 m → ±7 m")', { timeout: 10000 }).catch(() => {});
  check((await page.textContent('#locProgressText')).includes('±7'), 'live accuracy shown while improving');
  await shot(page, '1-capturing-live-accuracy');

  await context.setGeolocation({ ...FUNTUA, accuracy: 4 });
  await page.waitForSelector('#locResult:not(.hidden)', { timeout: 15000 });
  await page.waitForFunction(() => document.getElementById('locState').value !== '', null, { timeout: 15000 }).catch(() => {});
  check(await page.getAttribute('#locRating', 'data-rating') === 'excellent', 'rating Excellent for ±4 m');
  check((await page.textContent('#locAccText')).trim() === '±4 m', 'reported accuracy shown as ±4 m');
  check(await page.inputValue('#locState') === 'Katsina' && await page.inputValue('#locLga') === 'Funtua', 'State and LGA filled from GPS');
  check(await page.locator('#locMap .leaflet-marker-icon').count() === 1, 'map preview with a pin');
  await page.waitForTimeout(1500); // tiles
  await shot(page, '2-success-rating-names-map');

  const pin = await page.locator('#locMap .leaflet-marker-icon').boundingBox();
  await page.mouse.move(pin.x + pin.width / 2, pin.y + pin.height - 2);
  await page.mouse.down();
  await page.mouse.move(pin.x + pin.width / 2 + 60, pin.y + pin.height - 30, { steps: 8 });
  await page.mouse.up();
  await page.waitForSelector('#locAdjusted:not(.hidden)', { timeout: 5000 }).catch(() => {});
  check(await page.inputValue('#locSource') === 'gps_adjusted' && await page.inputValue('#locOrigLatitude') !== '', 'moved pin saved as adjusted, device fix kept');
  await shot(page, '3-pin-adjusted');
  await context.close();
}

// 4. Permission denied: what to do, Try again, Enter manually instead.
{
  const { context, page } = await openScanPage({ geo: false });
  await page.click('#locModeGps');
  await page.waitForSelector('#locError:not(.hidden)', { timeout: 15000 });
  check(await page.getAttribute('#locError', 'data-loc-error') === 'denied', 'permission denied explained');
  await shot(page, '4-permission-denied');
  await context.close();
}

// 5. Low accuracy (laptop-like ±120 m): rating Low, tips, Try again.
{
  const { context, page } = await openScanPage({ width: 1280 });
  await context.setGeolocation({ ...FUNTUA, accuracy: 120 });
  await page.click('#locModeGps');
  await page.waitForSelector('#locStopNow:not(.hidden)', { timeout: 15000 });
  await page.click('#locStopNow');
  await page.waitForSelector('#locAdvice:not(.hidden)', { timeout: 15000 });
  check(await page.getAttribute('#locRating', 'data-rating') === 'low', 'rating Low for ±120 m');
  check(await page.isVisible('#locDesktopHint'), 'desktop hint to use the app or a phone');
  await shot(page, '5-low-accuracy-warning');
  await context.close();
}

// 6. Manual entry.
{
  const { context, page } = await openScanPage({ geo: false });
  await page.click('#locModeManual');
  await page.selectOption('#locState', 'Kano');
  await page.selectOption('#locLga', 'Dala');
  check(await page.inputValue('#locSource') === 'manual', 'manual entry recorded as manual');
  await shot(page, '6-manual-entry');
  await context.close();
}

await browser.close();
console.log(`location capture: ${failures.length} failed`);
if (failures.length) process.exit(1);
