/* Service worker (EasyInvoice én Lopra) — maakt de app installeerbaar (PWA) en houdt de
 * statische bestanden (JS/CSS/afbeeldingen) in een cache. Pagina's en gegevens
 * komen altijd van het netwerk; alleen bij geen verbinding tonen we een
 * eenvoudige offline-melding. */
const VERSION = 'app-v2';
const OFFLINE_HTML = '<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title><style>body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;background:#FAFAF9;color:#1C1917;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}div{max-width:360px;text-align:center;padding:24px}h1{font-size:20px;margin:0 0 8px}p{color:#57534E;line-height:1.6}a{color:#E8231F;font-weight:600}</style></head><body><div><h1>Geen verbinding</h1><p>Deze app heeft internet nodig om je administratie te laden. Controleer je verbinding en <a href="javascript:location.reload()">probeer opnieuw</a>.</p></div></body></html>';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Statische bestanden met hash in de naam: cache-first.
  if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/') || url.pathname.startsWith('/fonts/')) {
    event.respondWith(caches.open(VERSION).then(async (cache) => {
      const hit = await cache.match(req);
      if (hit) return hit;
      const res = await fetch(req);
      if (res.ok) cache.put(req, res.clone());
      return res;
    }));
    return;
  }

  // Navigaties: netwerk, met offline-pagina als vangnet.
  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => new Response(OFFLINE_HTML, { headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
  }
});
