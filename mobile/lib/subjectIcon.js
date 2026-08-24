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

const PEST_KEYWORDS = [
  { match: /weed|striga/i,                       icon: '🌿' },
  { match: /fungal|fungus|pathogen|bacteria/i,    icon: '🦠' },
  { match: /nematode/i,                           icon: '🪱' },
  { match: /rodent|rat|mouse/i,                   icon: '🐀' },
  { match: /bird/i,                               icon: '🐦' },
];

/**
 * @param {{ type?: string, subject_name?: string, subjectName?: string, disease_name?: string, aiResult?: { primaryDiagnosis?: string } }} record
 */
export function subjectIcon(record) {
  const type = record?.type;
  const text = [
    record?.subject_name,
    record?.subjectName,
    record?.disease_name,
    record?.aiResult?.primaryDiagnosis,
  ].filter(Boolean).join(' ');

  if (type === 'soil') return '🟤';
  if (type === 'pest') {
    const found = PEST_KEYWORDS.find(({ match }) => match.test(text));
    return found ? found.icon : '🐛';
  }

  const isCrop = type === 'crop';
  const table = isCrop ? CROP_KEYWORDS : LIVESTOCK_KEYWORDS;
  const found = table.find(({ match }) => match.test(text));
  if (found) return found.icon;

  // Genuinely unknown subject — a neutral category icon, never a specific
  // crop/animal guessed at random.
  return isCrop ? '🌱' : '🐾';
}

/** Human-readable label for a diagnosis record's scan type — covers all
 * four scan types, not just crop/livestock (a soil or pest scan was
 * previously mislabeled "Livestock" by a two-way ternary). */
export function typeLabel(type) {
  switch (type) {
    case 'crop':      return 'Crop';
    case 'livestock': return 'Livestock';
    case 'soil':      return 'Soil';
    case 'pest':      return 'Pest';
    default:          return type ? type.charAt(0).toUpperCase() + type.slice(1) : 'Scan';
  }
}
