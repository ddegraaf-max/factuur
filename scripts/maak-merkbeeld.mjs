/**
 * Maakt de rasterbestanden van het EasyBookkeeper-beeldmerk.
 *
 * ── Waarom dit er is ──────────────────────────────────────────────────────
 *
 * Een merk heeft PNG's nodig die geen enkele browser uit een SVG haalt: het
 * pictogram op het beginscherm van een iPhone, de favicon voor oudere
 * browsers, en de afbeelding die verschijnt als iemand een link deelt. Die
 * laatste is de belangrijkste — WhatsApp, LinkedIn en Signal tonen geen SVG,
 * en zonder raster blijft een gedeelde link kaal.
 *
 * Het beeldmerk bestaat uit rechthoeken en rechte lijnen, dus het is hier
 * rechtstreeks te tekenen. Een rasteriseerder binnenhalen om vier vormen om te
 * zetten is meer gewicht dan het waard is.
 *
 *   node scripts/maak-merkbeeld.mjs
 */

import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const HIER = path.dirname(fileURLToPath(import.meta.url));
const DOEL = path.join(HIER, '..', 'public', 'brand', 'easybookkeeper');

const GROEN = [0x14, 0x6b, 0x4f];
const DONKER = [0x0e, 0x4d, 0x39];
const WIT = [0xff, 0xff, 0xff];

// ─────────────────────────── tekenen ───────────────────────────

/** Een doek van breedte × hoogte, RGBA, volledig doorzichtig. */
const doek = (b, h) => ({ b, h, pix: new Uint8ClampedArray(b * h * 4) });

/** Eén pixel mengen; `dekking` tussen 0 en 1. */
function meng(d, x, y, kleur, dekking) {
  if (x < 0 || y < 0 || x >= d.b || y >= d.h || dekking <= 0) return;
  const i = (y * d.b + x) * 4;
  const a = Math.min(1, dekking);
  for (let k = 0; k < 3; k++) d.pix[i + k] = d.pix[i + k] * (1 - a) + kleur[k] * a;
  d.pix[i + 3] = Math.max(d.pix[i + 3], Math.round(a * 255));
}

/**
 * Een rechthoek met afgeronde hoeken.
 *
 * Per pixel wordt de afstand tot de vorm bepaald en daaruit de dekking; dat
 * geeft een gladde rand in plaats van een trapje. Zonder die tussenwaarden
 * ziet een pictogram van 32 pixels er hoekig en goedkoop uit.
 */
function rechthoek(d, x0, y0, br, ho, r, kleur, alfa = 1) {
  const x1 = x0 + br, y1 = y0 + ho;
  for (let y = Math.floor(y0) - 2; y < Math.ceil(y1) + 2; y++) {
    for (let x = Math.floor(x0) - 2; x < Math.ceil(x1) + 2; x++) {
      const px = x + 0.5, py = y + 0.5;
      // Afstand tot de rechthoek, met de hoekstraal eraf.
      const dx = Math.max(x0 + r - px, 0, px - (x1 - r));
      const dy = Math.max(y0 + r - py, 0, py - (y1 - r));
      const afstand = Math.hypot(dx, dy) - r;
      meng(d, x, y, kleur, Math.min(1, Math.max(0, 0.5 - afstand)) * alfa);
    }
  }
}

/** Een dikke lijn met ronde uiteinden. */
function lijn(d, ax, ay, bx, by, dikte, kleur, alfa = 1) {
  const r = dikte / 2;
  const minx = Math.floor(Math.min(ax, bx) - r - 2), maxx = Math.ceil(Math.max(ax, bx) + r + 2);
  const miny = Math.floor(Math.min(ay, by) - r - 2), maxy = Math.ceil(Math.max(ay, by) + r + 2);
  const vx = bx - ax, vy = by - ay;
  const lengte2 = vx * vx + vy * vy || 1;
  for (let y = miny; y < maxy; y++) {
    for (let x = minx; x < maxx; x++) {
      const px = x + 0.5, py = y + 0.5;
      let t = ((px - ax) * vx + (py - ay) * vy) / lengte2;
      t = Math.max(0, Math.min(1, t));
      const afstand = Math.hypot(px - (ax + t * vx), py - (ay + t * vy)) - r;
      meng(d, x, y, kleur, Math.min(1, Math.max(0, 0.5 - afstand)) * alfa);
    }
  }
}

/**
 * Het beeldmerk zelf, op een doek van 64 bij 64 dat naar `maat` is geschaald.
 * Twee kolommen van gelijke hoogte met een streep eronder: een grootboek dat
 * sluit.
 */
function merk(d, maat, kleur, verschuifX = 0, verschuifY = 0) {
  const s = maat / 64;
  const X = (w) => verschuifX + w * s;
  const Y = (w) => verschuifY + w * s;
  // Twee kolommen van gelijke hoogte op één streep: debet is credit. Drie
  // vormen, en daarom bij zestien pixels nog te herkennen.
  rechthoek(d, X(13), Y(18), 17 * s, 32 * s, 2.5 * s, kleur, 1);
  rechthoek(d, X(34), Y(18), 17 * s, 32 * s, 2.5 * s, kleur, 0.55);
  rechthoek(d, X(9), Y(54), 46 * s, 5 * s, 2.5 * s, kleur, 1);
}

// ─────────────────────────── PNG schrijven ───────────────────────────

const CRC = (() => {
  const t = new Int32Array(256);
  for (let n = 0; n < 256; n++) {
    let c = n;
    for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    t[n] = c;
  }
  return t;
})();

function crc32(buf) {
  let c = -1;
  for (const b of buf) c = CRC[(c ^ b) & 0xff] ^ (c >>> 8);
  return (c ^ -1) >>> 0;
}

function brok(soort, data) {
  const kop = Buffer.alloc(8);
  kop.writeUInt32BE(data.length, 0);
  kop.write(soort, 4, 'ascii');
  const staart = Buffer.alloc(4);
  staart.writeUInt32BE(crc32(Buffer.concat([kop.subarray(4), data])), 0);
  return Buffer.concat([kop, data, staart]);
}

/** kleurtype 6 = RGBA (pictogrammen), 2 = RGB (de deelafbeelding). */
function schrijfPng(d, kleurtype = 6) {
  const kanalen = kleurtype === 6 ? 4 : 3;
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(d.b, 0);
  ihdr.writeUInt32BE(d.h, 4);
  ihdr[8] = 8;
  ihdr[9] = kleurtype;
  const regel = d.b * kanalen;
  const rauw = Buffer.alloc((regel + 1) * d.h);
  for (let y = 0; y < d.h; y++) {
    rauw[y * (regel + 1)] = 0;
    for (let x = 0; x < d.b; x++) {
      const s = (y * d.b + x) * 4;
      const t = y * (regel + 1) + 1 + x * kanalen;
      rauw[t] = d.pix[s]; rauw[t + 1] = d.pix[s + 1]; rauw[t + 2] = d.pix[s + 2];
      if (kanalen === 4) rauw[t + 3] = d.pix[s + 3];
    }
  }
  return Buffer.concat([
    Buffer.from('89504e470d0a1a0a', 'hex'),
    brok('IHDR', ihdr),
    brok('IDAT', zlib.deflateSync(rauw, { level: 9 })),
    brok('IEND', Buffer.alloc(0)),
  ]);
}

// ─────────────────────────── maken ───────────────────────────

fs.mkdirSync(DOEL, { recursive: true });

/** Een pictogram: groene tegel met afgeronde hoeken, wit merk erop. */
function pictogram(maat) {
  const d = doek(maat, maat);
  rechthoek(d, 0, 0, maat, maat, maat * 0.22, GROEN, 1);
  const binnen = maat * 0.68;
  merk(d, binnen, WIT, (maat - binnen) / 2, (maat - binnen) / 2);
  return d;
}

for (const maat of [16, 32, 64, 180, 192, 512]) {
  const bestand = path.join(DOEL, `eb-icon-${maat}.png`);
  fs.writeFileSync(bestand, schrijfPng(pictogram(maat)));
  console.log(`  eb-icon-${maat}.png`);
}

/*
 * De deelafbeelding: 1200 bij 630. Die verhouding herkennen WhatsApp en
 * LinkedIn als "groot"; een vierkant wordt een duimnagel naast de tekst.
 * Geen tekst erin — er is niets in dit project dat letters kan tekenen, en de
 * naam komt sowieso als tekst naast de afbeelding te staan.
 */
{
  const B = 1200, H = 630;
  const d = doek(B, H);
  for (let y = 0; y < H; y++) {
    for (let x = 0; x < B; x++) {
      const t = (x / B) * 0.55 + (y / H) * 0.45;
      const i = (y * B + x) * 4;
      for (let k = 0; k < 3; k++) d.pix[i + k] = DONKER[k] + (GROEN[k] - DONKER[k]) * t;
      d.pix[i + 3] = 255;
    }
  }
  const maat = 300;
  merk(d, maat, WIT, (B - maat) / 2, (H - maat) / 2);
  const png = schrijfPng(d, 2);
  fs.writeFileSync(path.join(DOEL, 'og-easybookkeeper.png'), png);
  console.log(`  og-easybookkeeper.png — ${B}×${H}, ${(png.length / 1024).toFixed(0)} kB`);
}

/*
 * De favicon.ico. Oudere browsers en sommige feedlezers vragen nog steeds om
 * /favicon.ico en nemen geen SVG. Een ICO mag tegenwoordig een PNG bevatten,
 * dus dit is de kop eromheen en verder het bestand dat er al is.
 */
{
  const png = fs.readFileSync(path.join(DOEL, 'eb-icon-32.png'));
  const kop = Buffer.alloc(6);
  kop.writeUInt16LE(0, 0);      // gereserveerd
  kop.writeUInt16LE(1, 2);      // 1 = icoon
  kop.writeUInt16LE(1, 4);      // aantal afbeeldingen
  const ingang = Buffer.alloc(16);
  ingang[0] = 32;               // breedte
  ingang[1] = 32;               // hoogte
  ingang[2] = 0;                // aantal kleuren (0 = meer dan 256)
  ingang[3] = 0;                // gereserveerd
  ingang.writeUInt16LE(1, 4);   // kleurvlakken
  ingang.writeUInt16LE(32, 6);  // bits per pixel
  ingang.writeUInt32LE(png.length, 8);
  ingang.writeUInt32LE(22, 12); // de afbeelding begint na kop + ingang
  fs.writeFileSync(path.join(DOEL, 'favicon.ico'), Buffer.concat([kop, ingang, png]));
  console.log('  favicon.ico');
}
