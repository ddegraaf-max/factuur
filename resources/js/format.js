/**
 * Getallen, geld en datums in de schrijfwijze van de markt (zie config/markets.php).
 * De markt komt van de server via data-attributen op <html>: data-locale ('nl'/'pl')
 * en data-currency ('EUR'/'PLN'). Eén build draait zo voor Nederland én Polen.
 */
const root = typeof document !== 'undefined' ? document.documentElement.dataset : {};
const LOCALE_TAGS = { nl: 'nl-NL', pl: 'pl-PL', en: 'en-GB' };

// Interfacetaal (data-locale: nl/pl/en) bepaalt maand- en dagnamen; de markttaal
// (data-market-locale) bepaalt getallen, geld en cijferdatums — ook in de Engelse UI in Polen.
export const uiLocale = LOCALE_TAGS[root.locale] || 'nl-NL';
export const marketLocale = LOCALE_TAGS[root.marketLocale || root.locale] || 'nl-NL';
export const marketCurrency = root.currency || 'EUR';

/**
 * Geldbedrag: nl → "€ 1.234,50", pl → "1 234,50 zł". De naam `eur` is historisch;
 * `money` is het synoniem voor nieuwe code.
 */
export function money(value, options = {}) {
  const n = Number(value) || 0;
  return new Intl.NumberFormat(marketLocale, {
    style: 'currency',
    currency: marketCurrency,
    minimumFractionDigits: options.decimals ?? 2,
    maximumFractionDigits: options.decimals ?? 2,
  }).format(n);
}

export const eur = money;

/** Getal met duizendtallen, zonder valutasymbool. */
export function num(value, decimals = 2) {
  const n = Number(value) || 0;
  return new Intl.NumberFormat(marketLocale, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(n);
}

/** Datum in de markt-schrijfwijze: nl "12-09-2026", pl "12.09.2026". */
export function fmtDate(value, options) {
  if (!value) return '';
  const d = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(d.getTime())) return String(value);
  // Met maand- of dagnamen in de interfacetaal; kale cijferdatums in de markt-schrijfwijze.
  const named = options && (['long', 'short', 'narrow'].includes(options.month) || options.weekday);
  return d.toLocaleDateString(named ? uiLocale : marketLocale, options || { day: '2-digit', month: '2-digit', year: 'numeric' });
}

/** Datum met maandnaam: nl "12 september 2026", pl "12 września 2026". */
export function fmtDateLong(value) {
  return fmtDate(value, { day: 'numeric', month: 'long', year: 'numeric' });
}

/**
 * Een door de gebruiker ingevoerd getal ("1.234,56", "1 234,56", "1234.56")
 * terug naar een float. Komma én punt worden begrepen.
 */
export function parseDutchNumber(input) {
  if (input === null || input === undefined || input === '') return 0;
  if (typeof input === 'number') return input;
  let s = String(input).trim().replace(/\s| /g, '');
  // If both . and , are present, assume . is thousand separator
  if (s.includes('.') && s.includes(',')) {
    s = s.replace(/\./g, '').replace(',', '.');
  } else if (s.includes(',')) {
    s = s.replace(',', '.');
  }
  const n = parseFloat(s);
  return isNaN(n) ? 0 : n;
}

export const parseNumber = parseDutchNumber;
