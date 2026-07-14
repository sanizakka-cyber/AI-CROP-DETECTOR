// Design Tokens — FarmAI Brand System
export const Colors = {
  primary:    '#1C6B38',   // Deep Forest Green
  primaryDark:'#0F4721',
  primaryLight:'#2E8B57',
  accent:     '#F4A32A',   // Golden Harvest
  accentDark: '#D4881A',
  surface:    '#FFFFFF',
  background: '#F5F7F5',
  card:       '#FFFFFF',
  border:     '#E0E8E2',
  textPrimary:'#1A2E1C',
  textSecondary:'#5A7060',
  textMuted:  '#9DB5A2',
  danger:     '#D94F3B',
  warning:    '#F4A32A',
  success:    '#2E8B57',
  info:       '#3B82F6',
  white:      '#FFFFFF',
  black:      '#000000',
  scarlet:    '#E53E3E',
  mild:       '#22C55E',
  moderate:   '#F59E0B',
  severe:     '#EF4444',
  emergency:  '#991B1B',

  // Dark-mode variants
  dark: {
    background: '#0D1F12',
    card:       '#142A1A',
    surface:    '#1C3524',
    textPrimary:'#E8F5EA',
    textSecondary:'#8DB89A',
    border:     '#2A4A32',
  },
};

export const Spacing = {
  xs: 4, sm: 8, md: 16, lg: 24, xl: 32, xxl: 48,
};

export const Radius = {
  sm: 8, md: 12, lg: 16, xl: 24, full: 9999,
};

export const Typography = {
  h1:    { fontSize: 28, fontWeight: '700', lineHeight: 36 },
  h2:    { fontSize: 22, fontWeight: '700', lineHeight: 30 },
  h3:    { fontSize: 18, fontWeight: '600', lineHeight: 26 },
  body:  { fontSize: 15, fontWeight: '400', lineHeight: 22 },
  small: { fontSize: 13, fontWeight: '400', lineHeight: 19 },
  tiny:  { fontSize: 11, fontWeight: '400', lineHeight: 16 },
  label: { fontSize: 13, fontWeight: '600', lineHeight: 18 },
};

export const Shadows = {
  sm: {
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.08, shadowRadius: 4, elevation: 2,
  },
  md: {
    shadowColor: '#000', shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.12, shadowRadius: 8, elevation: 5,
  },
  lg: {
    shadowColor: '#000', shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.18, shadowRadius: 16, elevation: 10,
  },
};
