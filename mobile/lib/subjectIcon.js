/**
 * Picks an icon that actually reflects a diagnosis record's detected
 * subject, instead of a single hardcoded icon for every crop scan (🌽) and
 * every livestock scan (🐄) regardless of what was actually scanned —
 * confirmed as a real bug (a tomato/leaf scan always rendered as maize).
 *
 * This is a targeted fix for that specific defect, not the broader
 * "replace all emojis with a professional SVG icon system" redesign
 * (tracked separately) — it keeps the existing emoji-based visual
 * language but picks the emoji from the actual record.
 */
const CROP_KEYWORDS = [
  { match: /maize|corn/i,           icon: '🌽' },
  { match: /rice/i,                 icon: '🌾' },
  { match: /tomato/i,               icon: '🍅' },
  { match: /cassava/i,              icon: '🥔' },
  { match: /yam/i,                  icon: '🍠' },
  { match: /bean/i,                 icon: '🫘' },
  { match: /pepper|chilli|chili/i,  icon: '🌶️' },
  { match: /onion/i,                icon: '🧅' },
  { match: /banana|plantain/i,      icon: '🍌' },
  { match: /cocoa/i,                icon: '🍫' },
  { match: /cotton/i,               icon: '🌱' },
  { match: /wheat|sorghum|millet/i, icon: '🌾' },
  { match: /soil/i,                 icon: '🟤' },
  { match: /pest|insect|weed/i,     icon: '🐛' },
];

const LIVESTOCK_KEYWORDS = [
  { match: /cattle|cow|bull|calf/i,        icon: '🐄' },
  { match: /goat/i,                        icon: '🐐' },
  { match: /sheep|ram|ewe/i,               icon: '🐑' },
  { match: /poultry|chicken|hen|rooster/i, icon: '🐔' },
  { match: /pig|swine|hog/i,               icon: '🐖' },
  { match: /duck/i,                        icon: '🦆' },
  { match: /rabbit/i,                      icon: '🐇' },
];

/**
 * @param {{ type?: string, subject_name?: string, subjectName?: string, disease_name?: string, aiResult?: { primaryDiagnosis?: string } }} record
 */
export function subjectIcon(record) {
  const isCrop = record?.type === 'crop';
  const text = [
    record?.subject_name,
    record?.subjectName,
    record?.disease_name,
    record?.aiResult?.primaryDiagnosis,
  ].filter(Boolean).join(' ');

  const table = isCrop ? CROP_KEYWORDS : LIVESTOCK_KEYWORDS;
  const found = table.find(({ match }) => match.test(text));
  if (found) return found.icon;

  // Genuinely unknown subject — a neutral category icon, never a specific
  // crop/animal guessed at random.
  return isCrop ? '🌱' : '🐾';
}
