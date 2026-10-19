// Prints the app's farmer-facing strings as JSON, without React Native:
//   node dump_app_strings.mjs i18n    <path to mobile/lib/i18n.js>
//   node dump_app_strings.mjs scan    <path to mobile/lib/scanErrors.js>
// Output: { "<lang>": { "<key>": "<text>" } } (nested keys flattened with ".").
import fs from 'fs';

const [kind, file] = process.argv.slice(2);
let src = fs.readFileSync(file, 'utf8').replace(/^import .*$/gm, '');

function flatten(obj, prefix = '', out = {}) {
  for (const [k, v] of Object.entries(obj || {})) {
    const key = prefix ? `${prefix}.${k}` : k;
    if (v && typeof v === 'object') flatten(v, key, out);
    else if (typeof v === 'string') out[key] = v;
  }
  return out;
}

let result = {};
if (kind === 'i18n') {
  // Keep only the language objects: cut at the i18next setup (older files
  // have no `export const resources` line), drop any other export.
  const cuts = [src.indexOf('export const resources'), src.search(/^i18n\b/m), src.indexOf('export default')].filter((i) => i >= 0);
  src = src.slice(0, Math.min(...cuts)).replace(/\bexport\s+/g, '');
  const names = ['en', 'ha', 'ig', 'yo', 'ff', 'fr'];
  // eslint-disable-next-line no-new-func
  const langs = new Function(`${src}; return { ${names.map((n) => `${n}: typeof ${n} === 'undefined' ? undefined : ${n}`).join(', ')} };`)();
  for (const [lang, v] of Object.entries(langs)) if (v) result[lang] = flatten(v.translation || v);
} else if (kind === 'scan') {
  src = src.replace(/\bexport\s+/g, '');
  // eslint-disable-next-line no-new-func
  const text = new Function('Alert', 'Share', `${src}; return SCAN_ERROR_TEXT;`)({}, {});
  for (const [lang, v] of Object.entries(text)) result[lang] = flatten(v);
} else {
  throw new Error('kind must be i18n or scan');
}
process.stdout.write(JSON.stringify(result));
