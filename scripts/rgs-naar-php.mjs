/**
 * Zet de officiële RGS-werkmap om in resources/data/rgs33.php.
 *
 * ── Waarom dit script bestaat ─────────────────────────────────────────────
 *
 * Het Referentie GrootboekSchema wordt door de RGS-beheerders als Excel
 * gepubliceerd. Onze grootboekrekeningen dragen die codes, en ze gaan als
 * leadCode mee in de auditfile: klopt er een niet, dan koppelt de accountant
 * onze rekening aan de verkeerde referentie. Dat is stil verkeerd — het bestand
 * wordt niet geweigerd, het betekent alleen iets anders dan wij bedoelen.
 *
 * Daarom komt de lijst uit de bron en niet uit ons hoofd, en daarom staat het
 * omzetten in een script dat opnieuw te draaien is in plaats van in een
 * handmatige actie die niemand kan nakijken.
 *
 * ── Gebruik ───────────────────────────────────────────────────────────────
 *
 *   node scripts/rgs-naar-php.mjs <pad naar de RGS-werkmap.xlsx>
 *
 * De werkmap staat op referentiegrootboekschema.nl (blad "RGS3.3def"); een kopie
 * van 3.3 ligt in de VvEMaat-repo onder schemas/RGS-3.3-definitief.xlsx. Hij
 * staat hier zelf niet in: 1,3 MB binair in de repo, voor een bestand dat één
 * keer per RGS-versie wordt gelezen, is niet de moeite. De uitvoer
 * (resources/data/rgs33.php) commit je wél mee.
 *
 * ── Geen bibliotheken ─────────────────────────────────────────────────────
 *
 * Een xlsx is een ZIP met XML erin en Node heeft zlib aan boord. Een pakket
 * binnenhalen voor één bestand dat één keer per RGS-versie wordt gelezen is
 * meer gewicht dan het waard is — zeker omdat het dan ook in de productiebuild
 * meekomt, waar het niets te zoeken heeft.
 */

import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const hier = path.dirname(fileURLToPath(import.meta.url));

// ═══════════════════════════ de werkmap lezen ═══════════════════════════

/**
 * De bestanden uit een ZIP halen, via de centrale map achteraan. Niet door van
 * voren te lopen: dat is hoe het formaat bedoeld is, en de enige manier die
 * klopt als er iets tussen de items staat.
 */
function zipInhoud(buf) {
  let eocd = -1;
  for (let i = buf.length - 22; i >= Math.max(0, buf.length - 66000); i--) {
    if (buf.readUInt32LE(i) === 0x06054b50) { eocd = i; break; }
  }
  if (eocd === -1) throw new Error('geen ZIP: einde van de centrale map niet gevonden');

  const aantal = buf.readUInt16LE(eocd + 10);
  let p = buf.readUInt32LE(eocd + 16);

  const uit = new Map();
  for (let n = 0; n < aantal; n++) {
    if (buf.readUInt32LE(p) !== 0x02014b50) break;
    const methode = buf.readUInt16LE(p + 10);
    const gepakt = buf.readUInt32LE(p + 20);
    const naamLengte = buf.readUInt16LE(p + 28);
    const extraLengte = buf.readUInt16LE(p + 30);
    const commentaarLengte = buf.readUInt16LE(p + 32);
    const begin = buf.readUInt32LE(p + 42);
    const naam = buf.slice(p + 46, p + 46 + naamLengte).toString('utf8');

    // Bij het item zelf staat de kop opnieuw, met eigen lengtes voor naam en
    // extra veld; die van de centrale map kloppen daar niet voor.
    const nl = buf.readUInt16LE(begin + 26);
    const el = buf.readUInt16LE(begin + 28);
    const data = buf.slice(begin + 30 + nl + el, begin + 30 + nl + el + gepakt);

    uit.set(naam, methode === 0 ? data : zlib.inflateRawSync(data));
    p += 46 + naamLengte + extraLengte + commentaarLengte;
  }
  return uit;
}

const ONTSNAPT = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'" };
const terug = (s) => s.replace(/&(amp|lt|gt|quot|apos|#x?[0-9a-fA-F]+);/g, (h, k) => {
  if (k[0] !== '#') return ONTSNAPT[k];
  return String.fromCodePoint(k[1] === 'x' ? parseInt(k.slice(2), 16) : Number(k.slice(1)));
});

/** Alle tekst binnen een element, met de opmaakstukjes aan elkaar geplakt. */
const tekstVan = (xml) => [...xml.matchAll(/<t[^>]*>([\s\S]*?)<\/t>/g)]
  .map((m) => terug(m[1])).join('');

/**
 * De gedeelde teksten. Excel zet elke tekst één keer in een aparte lijst en
 * verwijst er vanuit de cellen naar; zonder die lijst zijn de meeste cellen
 * niet meer dan een nummer.
 */
function gedeeldeTeksten(zip) {
  const ruw = zip.get('xl/sharedStrings.xml');
  if (!ruw) return [];
  const xml = ruw.toString('utf8');
  return [...xml.matchAll(/<si>([\s\S]*?)<\/si>/g)].map((m) => tekstVan(m[1]));
}

/** Kolomletters naar een nulgebaseerd nummer: A → 0, Z → 25, AA → 26. */
function kolomVan(verwijzing) {
  const letters = (verwijzing.match(/^[A-Z]+/) || [''])[0];
  let n = 0;
  for (const teken of letters) n = n * 26 + (teken.charCodeAt(0) - 64);
  return n - 1;
}

/**
 * Eén blad als rijen van tekst.
 *
 * Lege cellen worden door Excel overgeslagen; die vullen we aan, anders
 * schuiven de kolommen op en gaat de lijst er heel geloofwaardig maar verkeerd
 * uitzien.
 */
function bladRijen(zip, bestand, teksten) {
  const ruw = zip.get(bestand);
  if (!ruw) throw new Error(`blad ${bestand} zit niet in de werkmap`);
  const xml = ruw.toString('utf8');

  const rijen = [];
  for (const rij of xml.matchAll(/<row[^>]*>([\s\S]*?)<\/row>/g)) {
    const cellen = [];
    for (const cel of rij[1].matchAll(/<c\s([^>]*)>([\s\S]*?)<\/c>|<c\s([^>]*)\/>/g)) {
      const kop = cel[1] || cel[3] || '';
      const inhoud = cel[2] || '';
      const verwijzing = (kop.match(/r="([A-Z]+\d+)"/) || [])[1] || '';
      const soort = (kop.match(/t="(\w+)"/) || [])[1] || 'n';

      let waarde = '';
      if (soort === 's') {
        const i = Number((inhoud.match(/<v>(\d+)<\/v>/) || [])[1]);
        waarde = teksten[i] !== undefined ? teksten[i] : '';
      } else if (soort === 'inlineStr') {
        waarde = tekstVan(inhoud);
      } else {
        waarde = terug((inhoud.match(/<v>([\s\S]*?)<\/v>/) || [])[1] || '');
      }

      const k = verwijzing ? kolomVan(verwijzing) : cellen.length;
      while (cellen.length < k) cellen.push('');
      cellen[k] = String(waarde).trim();
    }
    rijen.push(cellen);
  }
  return rijen;
}

/** De bladen van de werkmap, op naam. */
function bladen(zip) {
  const wb = (zip.get('xl/workbook.xml') || Buffer.alloc(0)).toString('utf8');
  const rels = (zip.get('xl/_rels/workbook.xml.rels') || Buffer.alloc(0)).toString('utf8');

  const doelen = new Map();
  for (const m of rels.matchAll(/Id="([^"]+)"[^>]*Target="([^"]+)"/g)) {
    doelen.set(m[1], m[2].replace(/^\/?xl\//, '').replace(/^\.\//, ''));
  }

  const uit = [];
  for (const m of wb.matchAll(/<sheet\b([^>]*)\/?>/g)) {
    const naam = terug((m[1].match(/name="([^"]*)"/) || [])[1] || '');
    const id = (m[1].match(/r:id="([^"]*)"/) || [])[1];
    const doel = doelen.get(id);
    if (naam && doel) uit.push({ naam, bestand: 'xl/' + doel });
  }
  return uit;
}

function leesWerkmap(pad) {
  const zip = zipInhoud(fs.readFileSync(pad));
  const teksten = gedeeldeTeksten(zip);
  const lijst = bladen(zip);

  return {
    bladen: lijst.map((b) => b.naam),
    rijen(naam) {
      const b = lijst.find((x) => x.naam === naam) || lijst[0];
      if (!b) throw new Error('de werkmap bevat geen bladen');
      return bladRijen(zip, b.bestand, teksten);
    },
  };
}

// ═══════════════════════════ omzetten ═══════════════════════════

const BLAD = 'RGS3.3def';
const K = { code: 0, nummer: 3, kort: 4, lang: 5, dc: 6, nivo: 7, basis: 9, zzp: 12 };

/**
 * Het referentienummer staat in de werkmap soms zonder voorloopnul, omdat Excel
 * de cel als getal heeft bewaard: "107015" hoort "0107015" te zijn. Een nummer
 * op niveau 3 of hoger is zeven cijfers, bij niveau 5 met een punt en twee
 * cijfers erachter. Niveau 1 en 2 zijn rubrieken van één of twee tekens.
 */
function nummerRecht(ruw, nivo) {
  const s = String(ruw || '').trim();
  if (!s) return '';
  if (nivo <= 2) return s;
  const [hoofd, staart] = s.split('.');
  const recht = hoofd.length < 7 ? hoofd.padStart(7, '0') : hoofd;
  return staart ? `${recht}.${staart}` : recht;
}

/**
 * De voorouders van een RGS-code. Een code is opgebouwd uit blokken: één letter
 * voor balans of winst-en-verlies, daarna per niveau drie letters. BVorDebHad is
 * dus B › BVor › BVorDeb › BVorDebHad. Daarmee is de boom af te leiden uit de
 * code zelf en hoeft er geen aparte kolom bij.
 */
function voorouders(code) {
  const uit = [];
  for (let len = 1; len < code.length; len = len === 1 ? 4 : len + 3) uit.push(code.slice(0, len));
  return uit;
}

function php(s) {
  return "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";
}

function main() {
  const werkmap = process.argv[2];
  if (!werkmap) {
    console.error('Gebruik: node scripts/rgs-naar-php.mjs <RGS-werkmap.xlsx>');
    process.exit(2);
  }

  const wm = leesWerkmap(werkmap);
  if (!wm.bladen.includes(BLAD)) {
    console.error(`het blad "${BLAD}" zit niet in deze werkmap; gevonden: ${wm.bladen.join(', ')}`);
    process.exit(1);
  }
  const rijen = wm.rijen(BLAD);

  // ---- alles inlezen, op code
  const alles = new Map();
  for (const rij of rijen.slice(2)) {
    const code = (rij[K.code] || '').trim();
    if (!code || alles.has(code)) continue;
    const nivo = parseInt((rij[K.nivo] || '0').trim(), 10) || 1;
    alles.set(code, {
      code,
      nummer: nummerRecht(rij[K.nummer], nivo),
      naam: ((rij[K.kort] || '').trim() || (rij[K.lang] || '').trim()).replace(/\s+/g, ' '),
      dc: (rij[K.dc] || '').trim(),
      nivo,
      basis: (rij[K.basis] || '').trim() === '1',
      zzp: (rij[K.zzp] || '').trim() === '1',
    });
  }

  // ---- de selectie: basis of zzp, plus elke voorouder daarvan
  const kies = new Set();
  for (const r of alles.values()) {
    if (!r.basis && !r.zzp) continue;
    kies.add(r.code);
    for (const v of voorouders(r.code)) if (alles.has(v)) kies.add(v);
  }

  // Een rubriek die zelf niet in de basis staat maar wel een basiskind heeft,
  // hoort tóch in het startschema: anders kan de balans het kind niet indelen.
  for (const code of kies) {
    if (!alles.get(code).basis) continue;
    for (const v of voorouders(code)) if (alles.has(v)) alles.get(v).basis = true;
  }

  /*
   * Sorteren op de boom en niet op het nummer: een rekening hoort onder zijn
   * rubriek te staan, de rubrieken in de volgorde van de balans (B) en daarna
   * de winst-en-verliesrekening (W), en binnen één rubriek op nummer.
   */
  const sleutel = (code) => [...voorouders(code), code]
    .filter((c) => alles.has(c))
    .map((c) => {
      const r = alles.get(c);
      // Niveau 2 draagt een rubrieknummer van twee cijfers ("01", "80"); dat
      // sorteert niet tussen zevencijferige nummers. Vul aan tot dezelfde lengte.
      const n = r.nivo <= 2 ? r.nummer.padEnd(7, '0') : r.nummer;
      return (n || 'zzzzzzz').padEnd(10, ' ');
    })
    .join('|');

  const regels = [...kies].map((c) => alles.get(c)).filter((r) => r.nivo >= 2);
  regels.sort((a, b) => {
    if (a.code[0] !== b.code[0]) return a.code[0] === 'B' ? -1 : 1;
    return sleutel(a.code).localeCompare(sleutel(b.code));
  });

  const aantalBasis = regels.filter((r) => r.basis).length;

  const kop = `<?php

/*
 * Referentie GrootboekSchema (RGS) 3.3 — de officiële lijst.
 *
 * NIET met de hand bijwerken. Dit bestand is gegenereerd uit de definitieve
 * werkmap van het RGS-beheer (blad "${BLAD}", kolommen Referentiecode,
 * Referentienummer, Omschrijving (verkort), D/C, Nivo, Basis, ZZP) met
 * scripts/rgs-naar-php.mjs. Komt er een RGS 3.4, draai dat script opnieuw.
 *
 * Waarom de codes hier uit een bron komen en niet uit ons hoofd: een RGS-code
 * is een afspraak tussen pakketten, accountants en de Belastingdienst. Een zelf
 * bedachte code ziet er precies zo uit als een echte, en valt dus pas op
 * wanneer de accountant de auditfile niet kan inlezen.
 *
 * Meegenomen zijn de rekeningen die RGS aanmerkt als:
 *   basis = true   het startschema (${aantalBasis} regels; dit wordt aangelegd bij een nieuwe
 *                  administratie)
 *   basis = false  geldig voor zzp en eenmanszaak, maar niet standaard; hieruit
 *                  kan een gebruiker bijkiezen
 * Woningcorporaties, banken en agro zijn weggelaten.
 *
 * De regels staan in de volgorde waarin een jaarrekening gelezen wordt: eerst
 * de balans (codes met een B), dan de winst-en-verliesrekening (W), en binnen
 * elke rubriek op referentienummer.
 *
 * Velden per regel:
 *   code    Referentiecode, bijv. BVorDebHad. Deze gaat in de auditfile.
 *   nummer  Referentienummer, bijv. 1101010. Dit is het rekeningnummer.
 *   naam    de verkorte omschrijving van RGS.
 *   dc      D of C: de kant waar het saldo normaal op staat.
 *   nivo    2 = hoofdrubriek, 3 = rubriek, 4 = rekening, 5 = subrekening.
 *           Op 2 en 3 wordt niet geboekt; die dragen de indeling van de balans
 *           en de winst-en-verliesrekening.
 *   basis   hoort in het startschema.
 *
 * Gegenereerd: ${new Date().toISOString().slice(0, 10)} · ${regels.length} regels.
 */

return [
`;

  const lijnen = regels.map((r) =>
    `    [${php(r.code)}, ${php(r.nummer)}, ${php(r.naam)}, ${php(r.dc)}, ${r.nivo}, ${r.basis ? 'true' : 'false'}],`);

  const uit = path.join(hier, '..', 'resources', 'data', 'rgs33.php');
  fs.mkdirSync(path.dirname(uit), { recursive: true });
  fs.writeFileSync(uit, kop + lijnen.join('\n') + '\n];\n', 'utf8');

  console.log(`${regels.length} regels weggeschreven naar resources/data/rgs33.php `
    + `(${aantalBasis} in het startschema, ${(fs.statSync(uit).size / 1024).toFixed(0)} kB)`);
}

main();
