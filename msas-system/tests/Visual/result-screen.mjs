// Screenshots of the web result card for the seeded banana scan, and a
// layout check: in the side panel no label's box may overlap its value's
// (the "DETECTEDstem (pseudostem)" bug). Used by result-screen-screenshots.yml.
//   node result-screen.mjs <base url> <out dir> <name> [--expect-no-overlap]
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const [base, outDir, name, flag] = process.argv.slice(2);
const strict = flag === '--expect-no-overlap';
fs.mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch();
const results = [];
for (const [label, width] of [['phone', 390], ['tablet', 768], ['desktop', 1280]]) {
  const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 2 });
  await page.goto(`${base}/login`);
  // Post the login form directly (the page's own submit goes through script);
  // the session cookie lands in this page's context.
  const token = await page.inputValue('input[name="_token"]');
  await page.request.post(`${base}/login`, { form: { _token: token, login: 'umar.demo@example.test', password: 'screenshot-only' } });
  await page.goto(`${base}/diagnostics/history`, { waitUntil: 'networkidle' });
  if (!page.url().includes('/diagnostics/history')) {
    throw new Error(`Not logged in: ended on ${page.url()}`);
  }

  // Label/value pairs in the side panel, on either version of the page.
  const pairs = await page.evaluate(() => {
    const out = [];
    const box = (el) => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; };
    const facts = document.querySelectorAll('[data-side-panel] .side-fact');
    if (facts.length) {
      facts.forEach(f => out.push({ label: f.querySelector('dt').innerText, value: f.querySelector('dd').innerText, a: box(f.querySelector('dt')), b: box(f.querySelector('dd')) }));
    } else {
      // main: <div class="flex justify-between"><span>LABEL</span><span>VALUE</span></div>
      document.querySelectorAll('span[data-i18n="Detected Part"], span[data-i18n="Health"], span[data-i18n="Severity"], span[data-i18n="Urgency"]').forEach(l => {
        const v = l.nextElementSibling;
        if (v) out.push({ label: l.innerText, value: v.innerText, a: box(l), b: box(v) });
      });
    }
    return out;
  });
  const overlaps = (p) => p.a.x < p.b.x + p.b.w && p.b.x < p.a.x + p.a.w && p.a.y < p.b.y + p.b.h && p.b.y < p.a.y + p.a.h;
  // Touching edge to edge with no gap is also the bug ("DETECTEDstem").
  const touching = (p) => Math.abs(p.a.y - p.b.y) < 2 && Math.abs((p.a.x + p.a.w) - p.b.x) < 2;
  for (const p of pairs) results.push({ width: label, ...p, bad: overlaps(p) || touching(p) });

  // The result card itself (other elements share its classes on small screens).
  const card = page.locator('div.bg-white.rounded-2xl.shadow-md', { has: page.locator('[data-i18n="Detected Part"]') }).first();
  await card.screenshot({ path: path.join(outDir, `${name}-${label}.png`) });
  // The card must get most of a phone's width (an open sidebar once left it ~86px).
  const cardWidth = (await card.boundingBox()).width;
  results.push({ width: label, label: 'card width', value: `${Math.round(cardWidth)}px`, a: {}, b: {}, bad: label === 'phone' && cardWidth < 300 });
  await page.close();
}
await browser.close();

fs.writeFileSync(path.join(outDir, `${name}-layout.json`), JSON.stringify(results, null, 1));
const bad = results.filter(r => r.bad);
console.log(`${name}: ${results.length} checks (label/value overlap, card width), ${bad.length} failed`);
bad.forEach(r => console.log(`  ${r.width}: "${r.label}" / "${r.value}"`));
if (strict && (bad.length || !results.length)) process.exit(1);
