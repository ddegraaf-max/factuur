<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const isAuthed = computed(() => !!page.props.auth?.user);

const menuOpen = ref(false);
const year = 2026;

const productLinks = [
  { name: 'Functies', route: 'functies' },
  { name: 'Prijzen', route: 'prijzen' },
  { name: 'Reviews', route: 'reviews' },
  { name: 'Wat is nieuw', route: 'changelog' },
  { name: 'Roadmap', route: 'roadmap' },
];
const companyLinks = [
  { name: 'Over ons', route: 'over' },
  { name: 'Contact', route: 'contact' },
  { name: 'Pers', route: 'pers' },
  { name: 'Vacatures', route: 'vacatures' },
];
const helpLinks = [
  { name: 'Helpcentrum', route: 'helpcentrum' },
  { name: 'Veelgestelde vragen', route: 'faq' },
  { name: 'E-mailondersteuning', route: 'support' },
  { name: 'Status', route: 'status' },
];
</script>

<template>
  <div class="m-root">
    <header class="m-header">
      <div class="m-wrap m-header-inner">
        <Link :href="route('home')" class="m-brand">
          <span class="m-logo">
            <svg viewBox="0 0 32 32" width="20" height="20" fill="none">
              <circle cx="16" cy="14" r="11" stroke="currentColor" stroke-width="2.5"/>
              <path d="M11 10h10v3h-7v2h6v3h-6v2h7v3H11z" fill="currentColor"/>
            </svg>
          </span>
          <span class="m-brand-name">EasyInvoice</span>
        </Link>

        <nav class="m-nav" :class="{ open: menuOpen }">
          <Link :href="route('functies')" class="m-nav-link" @click="menuOpen = false">Functies</Link>
          <Link :href="route('prijzen')" class="m-nav-link" @click="menuOpen = false">Prijzen</Link>
          <Link :href="route('reviews')" class="m-nav-link" @click="menuOpen = false">Reviews</Link>
          <Link :href="route('helpcentrum')" class="m-nav-link" @click="menuOpen = false">Helpcentrum</Link>

          <div class="m-nav-cta">
            <Link v-if="isAuthed" :href="route('dashboard')" class="m-btn m-btn-primary">Naar dashboard</Link>
            <template v-else>
              <Link :href="route('login')" class="m-btn m-btn-ghost">Inloggen</Link>
              <Link :href="route('register')" class="m-btn m-btn-primary">Gratis starten</Link>
            </template>
          </div>
        </nav>

        <button class="m-burger" :aria-expanded="menuOpen" aria-label="Menu" @click="menuOpen = !menuOpen">
          <svg v-if="!menuOpen" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          <svg v-else viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
        </button>
      </div>
    </header>

    <main class="m-main">
      <slot />
    </main>

    <footer class="m-footer">
      <div class="m-wrap m-footer-grid">
        <div class="m-footer-brand">
          <Link :href="route('home')" class="m-brand m-brand-light">
            <span class="m-logo"><svg viewBox="0 0 32 32" width="20" height="20" fill="none"><circle cx="16" cy="14" r="11" stroke="currentColor" stroke-width="2.5"/><path d="M11 10h10v3h-7v2h6v3h-6v2h7v3H11z" fill="currentColor"/></svg></span>
            <span class="m-brand-name">EasyInvoice</span>
          </Link>
          <p class="m-footer-blurb">Nederlandse facturatie zonder gedoe. Facturen, btw, herinneringen en incasso — alles op één plek, voor maar €2,50 per maand.</p>
          <Link :href="route('status')" class="m-status-chip">
            <span class="m-status-dot"></span> Alle systemen operationeel
          </Link>
        </div>

        <div class="m-footer-col">
          <div class="m-footer-head">Product</div>
          <Link v-for="l in productLinks" :key="l.route" :href="route(l.route)" class="m-footer-link">{{ l.name }}</Link>
        </div>
        <div class="m-footer-col">
          <div class="m-footer-head">Bedrijf</div>
          <Link v-for="l in companyLinks" :key="l.route" :href="route(l.route)" class="m-footer-link">{{ l.name }}</Link>
        </div>
        <div class="m-footer-col">
          <div class="m-footer-head">Help</div>
          <Link v-for="l in helpLinks" :key="l.route" :href="route(l.route)" class="m-footer-link">{{ l.name }}</Link>
        </div>
      </div>

      <div class="m-wrap m-footer-bottom">
        <span>© {{ year }} EasyInvoice · KvK 87654321 · Gemaakt in Nederland 🇳🇱</span>
        <div class="m-footer-bottom-links">
          <Link :href="route('contact')">Contact</Link>
          <Link :href="route('faq')">FAQ</Link>
          <Link :href="route('status')">Status</Link>
        </div>
      </div>
    </footer>
  </div>
</template>

<style>
@import url('https://fonts.bunny.net/css?family=bricolage-grotesque:500,600,700,800|dm-sans:400,500,600,700&display=swap');

:root {
  --brand: #E8231F;
  --brand-dark: #B81814;
  --brand-darker: #7F1310;
  --brand-tint: #FEF2F2;
  --brand-tint-2: #FEE2E2;
  --brand-border: #FECACA;

  --bg: #FAFAF9;
  --surface: #FFFFFF;
  --surface-2: #F5F5F4;
  --surface-3: #EFEEEC;
  --border: #E7E5E4;
  --border-strong: #D6D3D1;

  --ink: #1C1917;
  --ink-2: #44403C;
  --ink-3: #78716C;
  --ink-4: #A8A29E;

  --success: #15803D;
  --success-bg: #DCFCE7;
  --success-border: #86EFAC;

  --r-sm: 8px;
  --r: 12px;
  --r-lg: 18px;
  --r-xl: 24px;

  --font-display: 'Bricolage Grotesque', system-ui, sans-serif;
  --font-body: 'DM Sans', system-ui, sans-serif;

  --shadow-sm: 0 1px 2px rgba(28,25,23,0.05);
  --shadow: 0 4px 14px rgba(28,25,23,0.07);
  --shadow-lg: 0 18px 50px rgba(28,25,23,0.12);
}

* { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--font-body);
  color: var(--ink);
  background: var(--bg);
  -webkit-font-smoothing: antialiased;
  line-height: 1.55;
}
a { color: inherit; text-decoration: none; }
img { max-width: 100%; display: block; }

/* ---------- LAYOUT ---------- */
.m-root { display: flex; flex-direction: column; min-height: 100vh; }
.m-wrap { width: 100%; max-width: 1140px; margin: 0 auto; padding-left: 24px; padding-right: 24px; }
.m-main { flex: 1; }

/* ---------- HEADER ---------- */
.m-header {
  position: sticky; top: 0; z-index: 50;
  background: rgba(255,255,255,0.85);
  backdrop-filter: saturate(180%) blur(12px);
  border-bottom: 1px solid var(--border);
}
.m-header-inner { display: flex; align-items: center; justify-content: space-between; height: 68px; }
.m-brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 700; }
.m-logo {
  width: 34px; height: 34px; border-radius: 9px;
  background: var(--brand); color: #fff;
  display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.m-brand-name { font-family: var(--font-display); font-weight: 700; font-size: 17px; letter-spacing: -0.01em; }
.m-nav { display: flex; align-items: center; gap: 28px; }
.m-nav-link { color: var(--ink-2); font-weight: 500; font-size: 15px; transition: color .15s; }
.m-nav-link:hover { color: var(--ink); }
.m-nav-cta { display: flex; align-items: center; gap: 10px; margin-left: 8px; }
.m-burger { display: none; color: var(--ink); padding: 6px; background: none; border: none; cursor: pointer; }

/* ---------- BUTTONS ---------- */
.m-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  height: 42px; padding: 0 20px; border-radius: var(--r-sm);
  font-weight: 600; font-size: 15px; cursor: pointer; border: 1px solid transparent;
  transition: all .15s; white-space: nowrap;
}
.m-btn-lg { height: 50px; padding: 0 28px; font-size: 16px; border-radius: var(--r); }
.m-btn-primary { background: var(--brand); color: #fff; box-shadow: var(--shadow-sm); }
.m-btn-primary:hover { background: var(--brand-dark); transform: translateY(-1px); box-shadow: var(--shadow); }
.m-btn-ghost { color: var(--ink-2); }
.m-btn-ghost:hover { background: var(--surface-2); color: var(--ink); }
.m-btn-secondary { background: var(--surface); color: var(--ink); border-color: var(--border-strong); }
.m-btn-secondary:hover { background: var(--surface-2); transform: translateY(-1px); }
.m-btn-white { background: #fff; color: var(--brand-dark); }
.m-btn-white:hover { background: var(--brand-tint); transform: translateY(-1px); }

/* ---------- GENERIC SECTION HELPERS ---------- */
.m-section { padding: 80px 0; }
.m-section-tight { padding: 56px 0; }
.m-eyebrow {
  display: inline-block; font-weight: 700; font-size: 13px; letter-spacing: 0.06em;
  text-transform: uppercase; color: var(--brand); margin-bottom: 14px;
}
.m-h1 { font-family: var(--font-display); font-weight: 800; font-size: clamp(34px, 5vw, 56px); line-height: 1.05; letter-spacing: -0.02em; }
.m-h2 { font-family: var(--font-display); font-weight: 700; font-size: clamp(26px, 3.4vw, 38px); line-height: 1.12; letter-spacing: -0.02em; }
.m-h3 { font-family: var(--font-display); font-weight: 700; font-size: 20px; letter-spacing: -0.01em; }
.m-lead { font-size: clamp(17px, 2vw, 20px); color: var(--ink-3); line-height: 1.6; }
.m-muted { color: var(--ink-3); }
.m-center { text-align: center; }
.m-section-head { max-width: 680px; margin: 0 auto 48px; text-align: center; }
.m-section-head .m-lead { margin-top: 14px; }

/* ---------- HERO ---------- */
.m-hero {
  position: relative; overflow: hidden;
  padding: 96px 0 80px;
  background:
    radial-gradient(900px 420px at 80% -10%, var(--brand-tint) 0%, transparent 60%),
    radial-gradient(700px 380px at 0% 0%, #FFF7ED 0%, transparent 55%);
}
.m-hero-inner { max-width: 760px; }
.m-hero .m-lead { margin-top: 20px; max-width: 600px; }
.m-hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
.m-hero-note { margin-top: 16px; font-size: 14px; color: var(--ink-3); display: flex; gap: 8px; align-items: center; }

/* ---------- CARDS / GRID ---------- */
.m-grid { display: grid; gap: 20px; }
.m-grid-2 { grid-template-columns: repeat(2, 1fr); }
.m-grid-3 { grid-template-columns: repeat(3, 1fr); }
.m-grid-4 { grid-template-columns: repeat(4, 1fr); }
.m-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--r-lg); padding: 28px;
}
.m-card-hover { transition: transform .15s, box-shadow .15s, border-color .15s; }
.m-card-hover:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--border-strong); }
.m-card h3, .m-card .m-h3 { margin-bottom: 8px; }
.m-card p { color: var(--ink-3); }
.m-ficon {
  width: 46px; height: 46px; border-radius: 12px; margin-bottom: 18px;
  background: var(--brand-tint); color: var(--brand);
  display: inline-flex; align-items: center; justify-content: center;
}
.m-ficon svg { width: 24px; height: 24px; }

/* ---------- CHECK LIST ---------- */
.m-checks { display: grid; gap: 12px; margin-top: 8px; }
.m-check { display: flex; gap: 12px; align-items: flex-start; }
.m-check .tick {
  flex-shrink: 0; width: 22px; height: 22px; border-radius: 100px; margin-top: 1px;
  background: var(--success-bg); color: var(--success);
  display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;
}

/* ---------- BADGE / PILL ---------- */
.m-pill {
  display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 100px;
  font-size: 13px; font-weight: 600; background: var(--surface-2); color: var(--ink-2); border: 1px solid var(--border);
}
.m-pill-brand { background: var(--brand-tint); color: var(--brand-darker); border-color: var(--brand-border); }
.m-pill-green { background: var(--success-bg); color: var(--success); border-color: var(--success-border); }

/* ---------- CTA BAND ---------- */
.m-cta-band {
  background: linear-gradient(135deg, var(--brand) 0%, var(--brand-darker) 100%);
  color: #fff; border-radius: var(--r-xl); padding: 56px; text-align: center;
}
.m-cta-band .m-h2 { color: #fff; }
.m-cta-band p { color: rgba(255,255,255,0.85); margin-top: 12px; font-size: 17px; }
.m-cta-band .m-hero-actions { justify-content: center; }

/* ---------- FOOTER ---------- */
.m-footer { background: var(--ink); color: #D6D3D1; margin-top: 40px; }
.m-footer-grid {
  display: grid; grid-template-columns: 1.6fr 1fr 1fr 1fr; gap: 40px;
  padding-top: 56px; padding-bottom: 40px;
}
.m-brand-light { color: #fff; }
.m-brand-light .m-logo { background: var(--brand); }
.m-footer-blurb { color: #A8A29E; margin: 16px 0 18px; max-width: 320px; font-size: 14px; }
.m-status-chip {
  display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #D6D3D1;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
  padding: 6px 12px; border-radius: 100px;
}
.m-status-dot { width: 8px; height: 8px; border-radius: 100px; background: #4ADE80; box-shadow: 0 0 0 3px rgba(74,222,128,0.2); }
.m-footer-head { color: #fff; font-weight: 700; font-size: 14px; margin-bottom: 16px; }
.m-footer-col { display: flex; flex-direction: column; gap: 11px; }
.m-footer-link { color: #A8A29E; font-size: 14px; transition: color .15s; }
.m-footer-link:hover { color: #fff; }
.m-footer-bottom {
  border-top: 1px solid rgba(255,255,255,0.1);
  padding-top: 22px; padding-bottom: 32px;
  display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
  font-size: 13px; color: #78716C;
}
.m-footer-bottom-links { display: flex; gap: 22px; }
.m-footer-bottom-links a { color: #A8A29E; }
.m-footer-bottom-links a:hover { color: #fff; }

/* ---------- RESPONSIVE ---------- */
@media (max-width: 900px) {
  .m-grid-3, .m-grid-4 { grid-template-columns: repeat(2, 1fr); }
  .m-footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
  .m-footer-brand { grid-column: 1 / -1; }
}
@media (max-width: 760px) {
  .m-burger { display: inline-flex; }
  .m-nav {
    position: absolute; top: 68px; left: 0; right: 0;
    flex-direction: column; align-items: stretch; gap: 4px;
    background: var(--surface); border-bottom: 1px solid var(--border);
    padding: 12px 24px 20px; box-shadow: var(--shadow-lg);
    display: none;
  }
  .m-nav.open { display: flex; }
  .m-nav-link { padding: 12px 4px; border-bottom: 1px solid var(--border); }
  .m-nav-cta { flex-direction: column; align-items: stretch; margin: 12px 0 0; gap: 8px; }
  .m-nav-cta .m-btn { width: 100%; }
  .m-section { padding: 56px 0; }
  .m-hero { padding: 64px 0 56px; }
  .m-grid-2, .m-grid-3, .m-grid-4 { grid-template-columns: 1fr; }
  .m-cta-band { padding: 40px 24px; }
}
</style>
