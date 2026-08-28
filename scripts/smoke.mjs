#!/usr/bin/env node
// Bugscan laag 2: rookproef van de LIVE site via de demo-sandbox.
// Gebruik: node scripts/smoke.mjs [--wait-for-version 1.37.0]
// Faalt (exit 1) zodra een pagina geen 200 geeft of een serverfout toont.
const BASE = (process.env.SMOKE_BASE_URL || 'https://easyinvoice.nl').replace(/\/$/, '');
const args = process.argv.slice(2);
const waitVersion = args.includes('--wait-for-version') ? args[args.indexOf('--wait-for-version') + 1] : null;

const jar = new Map();
const cookieHeader = () => [...jar.entries()].map(([k, v]) => `${k}=${v}`).join('; ');
const storeCookies = (res) => {
  for (const c of res.headers.getSetCookie?.() ?? []) {
    const [pair] = c.split(';');
    const i = pair.indexOf('=');
    if (i > 0) jar.set(pair.slice(0, i).trim(), pair.slice(i + 1).trim());
  }
};
const xsrf = () => decodeURIComponent(jar.get('XSRF-TOKEN') || '');

async function req(path, opts = {}) {
  const res = await fetch(BASE + path, {
    redirect: 'manual',
    ...opts,
    headers: { 'User-Agent': 'EasyInvoice-smoke/1.0', Cookie: cookieHeader(), ...(opts.headers || {}) },
  });
  storeCookies(res);
  return res;
}

const failures = [];
const ok = (label) => console.log(`  OK  ${label}`);
const fail = (label, detail) => { failures.push(`${label}: ${detail}`); console.log(`  FOUT ${label}: ${detail}`); };

async function expectPage(path, label = path) {
  const res = await req(path);
  const body = await res.text();
  if (res.status !== 200) { fail(label, `status ${res.status}`); return null; }
  if (/Server Error|Whoops|ErrorException|SQLSTATE/.test(body)) { fail(label, 'foutpagina in de HTML'); return null; }
  ok(label);
  return body;
}

function pageProps(html) {
  const m = html.match(/data-page="([^"]+)"/);
  if (!m) return null;
  const json = m[1].replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
  try { return JSON.parse(json).props; } catch { return null; }
}

async function waitForVersion(version) {
  const deadline = Date.now() + 15 * 60 * 1000;
  process.stdout.write(`Wachten tot Easy ${version} live is `);
  while (Date.now() < deadline) {
    try {
      const html = await (await fetch(BASE + '/')).text();
      if (html.includes(`Easy ${version}`)) { console.log('- live.'); return; }
    } catch { /* nog niet bereikbaar */ }
    process.stdout.write('.');
    await new Promise((r) => setTimeout(r, 30000));
  }
  console.log('');
  fail('deploy', `versie ${version} na 15 minuten nog niet live`);
}

(async () => {
  if (waitVersion) await waitForVersion(waitVersion);

  console.log('Openbare pagina\'s');
  for (const p of ['/', '/status', '/login', '/wat-is-nieuw', '/helpcentrum', '/kennisbank', '/gratis-factuur-maken', '/demo', '/portaal']) {
    await expectPage(p);
  }

  console.log('Gezondheid');
  try {
    const res = await fetch(BASE + '/health');
    const health = await res.json();
    if (res.status === 200 && health.status === 'ok') ok(`/health (planner ${health.checks?.scheduler?.age_minutes ?? '?'} min geleden, ${health.version})`);
    else fail('/health', `status ${res.status}: ${JSON.stringify(health.checks)}`);
  } catch (e) {
    fail('/health', e.message);
  }

  console.log('Demo-sandbox');
  const demoHtml = await expectPage('/demo', 'GET /demo');
  const token = demoHtml ? demoHtml.match(/name="_token"\s+value="([^"]+)"/)?.[1] : null;
  if (!token) {
    fail('demo', 'geen CSRF-token gevonden');
  } else {
    const start = await req('/demo', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ _token: token }).toString(),
    });
    if (start.status !== 302) fail('POST /demo', `status ${start.status}`); else ok('demo gestart');

    const pages = ['/dashboard', '/invoices', '/invoices/create', '/offertes', '/offertes/create', '/customers', '/customers/create',
      '/btw', '/inkoop', '/uren', '/bank', '/stats', '/jaaroverzicht', '/cashflow', '/debiteuren',
      '/settings/company', '/settings/brand', '/settings/emailteksten', '/settings/reminders',
      '/settings/emailteksten/voorbeeld-bedankmail', '/settings/emailteksten/voorbeeld-akkoord'];
    const htmlByPage = {};
    for (const p of pages) htmlByPage[p] = await expectPage(p);

    // Detailpagina's op basis van echte records uit de demo.
    const inv = pageProps(htmlByPage['/invoices'] || '')?.invoices?.data?.[0];
    if (inv) await expectPage(`/invoices/${inv.id}`, 'factuurpagina'); else fail('facturen', 'geen factuur in demo-props');
    const q = pageProps(htmlByPage['/offertes'] || '')?.quotes?.data?.[0];
    if (q) await expectPage(`/offertes/${q.id}`, 'offertepagina'); else fail('offertes', 'geen offerte in demo-props');
    const c = pageProps(htmlByPage['/customers'] || '')?.customers?.data?.[0];
    if (c) await expectPage(`/customers/${c.id}`, 'klantpagina'); else fail('klanten', 'geen klant in demo-props');

    const stop = await req('/demo/verlaten', { method: 'POST', headers: { 'X-XSRF-TOKEN': xsrf() } });
    if (stop.status === 302) ok('demo opgeruimd'); else fail('POST /demo/verlaten', `status ${stop.status}`);
  }

  console.log('');
  if (failures.length) {
    console.log(`MISLUKT - ${failures.length} probleem/problemen:\n- ${failures.join('\n- ')}`);
    process.exit(1);
  }
  console.log('Alles in orde.');
})().catch((e) => { console.error('Onverwachte fout:', e); process.exit(1); });
