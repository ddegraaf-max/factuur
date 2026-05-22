/**
 * Format a number as Dutch Euros: 1234.5 → "€ 1.234,50"
 */
export function eur(value) {
  const n = Number(value) || 0;
  return new Intl.NumberFormat('nl-NL', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(n);
}

/**
 * Format a number with thousand separators, no currency symbol
 */
export function num(value, decimals = 2) {
  const n = Number(value) || 0;
  return new Intl.NumberFormat('nl-NL', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(n);
}

/**
 * Parse a user-entered Dutch number string back to a float.
 * Accepts "1.234,56" or "1234.56" or "1234,56".
 */
export function parseDutchNumber(input) {
  if (input === null || input === undefined || input === '') return 0;
  if (typeof input === 'number') return input;
  let s = String(input).trim();
  // If both . and , are present, assume . is thousand separator
  if (s.includes('.') && s.includes(',')) {
    s = s.replace(/\./g, '').replace(',', '.');
  } else if (s.includes(',')) {
    s = s.replace(',', '.');
  }
  const n = parseFloat(s);
  return isNaN(n) ? 0 : n;
}
