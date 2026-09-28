/**
 * Weken in de uitvraagtool: je kiest een dag in de kalender, opgeslagen wordt de
 * ISO-week ('2026-W44'), en getoond wordt het weeknummer met de dagen erbij.
 */
import { fmtDate } from '@/format.js';

/** ISO-week van een datum: '2026-W44'. */
export function isoWeek(date) {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
  const day = d.getUTCDay() || 7;
  d.setUTCDate(d.getUTCDate() + 4 - day);
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
  const week = Math.ceil(((d - yearStart) / 86400000 + 1) / 7);
  return `${d.getUTCFullYear()}-W${String(week).padStart(2, '0')}`;
}

/** Een datum uit een datumveld ('2026-10-26') als lokale dag, of null. */
export function parseDay(value) {
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));
  return m ? new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])) : null;
}

/** 'YYYY-MM-DD' voor een datumveld. */
export function dayValue(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

/** Maandag van '2026-W44', of null als de tekst geen ISO-week is. */
export function weekMonday(week) {
  const m = /^(\d{4})-W(\d{1,2})$/i.exec(String(week || '').trim());
  if (!m || Number(m[2]) < 1 || Number(m[2]) > 53) return null;
  // 4 januari valt altijd in week 1.
  const jan4 = new Date(Number(m[1]), 0, 4);
  const monday = new Date(jan4);
  monday.setDate(jan4.getDate() - ((jan4.getDay() || 7) - 1) + (Number(m[2]) - 1) * 7);
  return monday;
}

/** Voor achter het woord 'week': "44 (26 okt – 1 nov 2026)"; onbekende notatie blijft staan. */
export function weekLabel(week) {
  const monday = weekMonday(week);
  if (!monday) return String(week || '').trim();
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);
  const number = Number(isoWeek(monday).split('-W')[1]);
  return `${number} (${fmtDate(monday, { day: 'numeric', month: 'short' })} – ${fmtDate(sunday, { day: 'numeric', month: 'short', year: 'numeric' })})`;
}
