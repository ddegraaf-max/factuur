<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import EasyAgent from '@/Components/EasyAgent.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const company = computed(() => page.props.auth.company);
const version = computed(() => page.props.version);
const sidebarOpen = ref(false);

const initials = computed(() => {
  const parts = (user.value?.name ?? '').trim().split(/\s+/);
  const first = parts[0]?.[0] ?? '';
  const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
  return (first + last).toUpperCase();
});

// Per item bepaalt 'can' welke rol het ziet (zie auth.can in HandleInertiaRequests).
// De routes dwingen dezelfde regels server-side af — dit is alleen de zichtbaarheid.
const rawNav = [
  {
    title: 'Overzicht',
    items: [
      { name: 'Dashboard', route: 'dashboard', icon: 'dashboard' },
    ],
  },
  {
    title: 'Verkoop',
    items: [
      { name: 'Offertes', route: 'quotes.index', icon: 'quote' },
      { name: 'Facturen', route: 'invoices.index', icon: 'invoice' },
      { name: 'Uren', route: 'hours.index', icon: 'clock' },
      { name: 'Ritten', route: 'trips.index', icon: 'car' },
      { name: 'Terugkerend', route: 'recurring.index', icon: 'repeat' },
      { name: 'Klanten', route: 'customers.index', icon: 'users' },
      { name: 'Producten', route: 'products.index', icon: 'box' },
      { name: 'Incasso', route: 'incasso.index', icon: 'gavel' },
    ],
  },
  {
    title: 'Inkoop',
    items: [
      { name: 'Inkoopfacturen', route: 'purchases.index', icon: 'receipt' },
      { name: 'Postvak IN', route: 'purchases.inbox.index', icon: 'inbox', badge: 'Nieuw' },
      { name: 'Vaste lasten', route: 'purchases.recurring.index', icon: 'repeat' },
    ],
  },
  {
    title: 'Bank',
    items: [
      { name: 'Transacties', route: 'bank.index', icon: 'bank', badge: 'Nieuw' },
    ],
  },
  {
    title: 'Rapporten',
    items: [
      { name: 'Klantomzet', route: 'stats.index', icon: 'chart', can: 'reports' },
      { name: 'BTW-aangifte', route: 'vat.index', icon: 'percent', can: 'reports' },
      { name: 'Jaaroverzicht', route: 'yearreport.index', icon: 'chart', can: 'reports' },
      { name: 'Cashflow', route: 'cashflow.index', icon: 'chart', can: 'reports' },
      { name: 'Debiteuren', route: 'aging.index', icon: 'clock', can: 'reports', badge: 'Nieuw' },
      { name: 'Export boekhouder', route: 'export.index', icon: 'download', can: 'reports' },
    ],
  },
  {
    title: 'Instellingen',
    items: [
      { name: 'Bedrijfsgegevens', route: 'settings.company', icon: 'settings', can: 'settings' },
      { name: 'Huisstijl', route: 'settings.brand', icon: 'palette', can: 'settings' },
      { name: 'Handelsnamen', route: 'settings.brands', icon: 'tag', can: 'settings' },
      { name: 'Nummering', route: 'settings.numbering', icon: 'hash', can: 'settings' },
      { name: 'Herinneringen', route: 'settings.reminders', icon: 'bell', can: 'settings' },
      { name: 'E-mailteksten', route: 'settings.emails', icon: 'mail', can: 'settings', badge: 'Nieuw' },
      { name: 'Team', route: 'settings.team', icon: 'users', can: 'team' },
      { name: 'Beveiliging', route: 'settings.security', icon: 'shield' },
      { name: 'Abonnement', route: 'billing.show', icon: 'card', can: 'billing' },
    ],
  },
];

const can = computed(() => page.props.auth.can || {});
const nav = computed(() =>
  rawNav
    .map(section => ({
      ...section,
      items: section.items.filter(item => !item.can || can.value[item.can]),
    }))
    .filter(section => section.items.length > 0)
);

const subscription = computed(() => page.props.subscription || {});
const isDemo = computed(() => !!page.props.demo);
// In de demo is de proefperiode-balk alleen ruis; de demobalk staat er al.
const showTrialBanner = computed(() => !isDemo.value && subscription.value.status === 'trialing');

// De demo verlaten gaat via een gewoon formulier (dus een volledige
// paginanavigatie): we landen op de marketingsite of het registratieformulier,
// en dat zijn geen Inertia-pagina's.
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const isActive = (routeName) => {
  if (route().current(routeName)) return true;
  if (!route().current(routeName.replace('.index', '.*'))) return false;
  // Wildcard-match ('purchases.*' dekt ook 'purchases.inbox.*'): alleen actief
  // als er geen specifieker menu-item is dat óók matcht — anders lichten
  // Inkoopfacturen en Postvak IN allebei op.
  const prefix = routeName.replace(/\.index$/, '.');
  return !rawNav.some(section => section.items.some(item =>
    item.route !== routeName
    && item.route.startsWith(prefix)
    && (route().current(item.route) || route().current(item.route.replace('.index', '.*')))
  ));
};

const flash = computed(() => page.props.flash || {});

const userMenuOpen = ref(false);

/* ---------- Administraties (wisselaar) ---------- */
const administrations = computed(() => page.props.auth.administrations || []);
const hasMultipleAdministrations = computed(() => administrations.value.length > 1);

const switchAdministration = (a) => {
  if (a.id === company.value?.id) { userMenuOpen.value = false; return; }
  router.post(route('administrations.switch', a.id));
};

const logout = () => {
  router.post(route('logout'));
};
</script>

<template>
  <div class="app">
    <aside class="sidebar" :class="{ open: sidebarOpen }">
      <Link :href="route('dashboard')" class="sidebar-brand">
        <img src="/images/easyinvoice-favicon-180.png" class="logo-mark" alt="EasyInvoice" />
        <span class="brand-name">EasyInvoice</span>
      </Link>

      <nav class="sidebar-nav">
        <div v-for="section in nav" :key="section.title" class="nav-section">
          <div class="nav-section-title">{{ section.title }}</div>
          <Link
            v-for="item in section.items"
            :key="item.route"
            :href="route(item.route)"
            :class="['nav-item', { active: isActive(item.route) }]"
            @click="sidebarOpen = false"
          >
            <!-- Icons -->
            <svg v-if="item.icon === 'dashboard'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
            <svg v-else-if="item.icon === 'invoice'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg>
            <svg v-else-if="item.icon === 'users'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
            <svg v-else-if="item.icon === 'box'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            <svg v-else-if="item.icon === 'settings'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <svg v-else-if="item.icon === 'gavel'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
            <svg v-else-if="item.icon === 'chart'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="20" x2="21" y2="20"/></svg>
            <svg v-else-if="item.icon === 'percent'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
            <svg v-else-if="item.icon === 'receipt'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1.5L8 22l2-1.5L12 22l2-1.5L16 22l2-1.5L20 22V2l-2 1.5L16 2l-2 1.5L12 2l-2 1.5L8 2 6 3.5 4 2z"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="13" y2="16"/></svg>
            <svg v-else-if="item.icon === 'bank'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="22" x2="21" y2="22"/><line x1="6" y1="18" x2="6" y2="11"/><line x1="10" y1="18" x2="10" y2="11"/><line x1="14" y1="18" x2="14" y2="11"/><line x1="18" y1="18" x2="18" y2="11"/><polygon points="12 2 20 7 4 7"/></svg>
            <svg v-else-if="item.icon === 'palette'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
            <svg v-else-if="item.icon === 'hash'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>
            <svg v-else-if="item.icon === 'clock'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <svg v-else-if="item.icon === 'tag'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            <svg v-else-if="item.icon === 'car'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
            <svg v-else-if="item.icon === 'inbox'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
            <svg v-else-if="item.icon === 'bell'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <svg v-else-if="item.icon === 'mail'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <svg v-else-if="item.icon === 'shield'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <svg v-else-if="item.icon === 'card'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <svg v-else-if="item.icon === 'quote'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
            <svg v-else-if="item.icon === 'repeat'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            <svg v-else-if="item.icon === 'download'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            {{ item.name }}
            <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
          </Link>
        </div>
      </nav>

      <div class="sidebar-user" @click="userMenuOpen = !userMenuOpen">
        <div class="avatar">{{ initials }}</div>
        <div class="user-info">
          <div class="user-name">{{ user?.name }}</div>
          <div class="user-co">{{ company?.name }}</div>
        </div>
        <div v-if="userMenuOpen" class="user-menu">
          <template v-if="hasMultipleAdministrations">
            <div class="user-menu-label">Administraties</div>
            <button
              v-for="a in administrations"
              :key="a.id"
              class="user-menu-item"
              :class="{ 'is-active-admin': a.id === company?.id }"
              @click.stop="switchAdministration(a)"
            >
              <svg v-if="a.id === company?.id" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex:none;"><polyline points="20 6 9 17 4 12"/></svg>
              <span v-else style="width:12px;flex:none;"></span>
              <span class="admin-name">{{ a.name }}</span>
            </button>
            <div class="user-menu-sep"></div>
          </template>
          <Link :href="route('administrations.index')" class="user-menu-item">Administraties beheren</Link>
          <Link :href="route('settings.security')" class="user-menu-item">Beveiliging</Link>
          <button class="user-menu-item" @click.stop="logout">Uitloggen</button>
        </div>
      </div>

      <div class="sidebar-version" :title="'Softwareversie ' + version">{{ version }}</div>
    </aside>
    <div class="sidebar-overlay" v-if="sidebarOpen" @click="sidebarOpen = false"></div>

    <div class="main">
      <header class="topbar">
        <div class="topbar-left">
          <button class="topbar-toggle" @click="sidebarOpen = true" aria-label="Menu openen">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          </button>
          <slot name="breadcrumb"></slot>
        </div>
        <div class="topbar-right">
          <!-- Boekhouder-rol is alleen-lezen: geen aanmaak-/opslaknoppen tonen. -->
          <span v-if="can.write === false" class="readonly-badge" title="Je kunt alles inzien en exporteren, maar niets wijzigen.">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Alleen inzien
          </span>
          <slot v-else name="topbar-actions"></slot>
        </div>
      </header>

      <div v-if="isDemo" class="demo-banner">
        <span class="demo-chip">Demo</span>
        <span class="demo-text">
          Je bekijkt de <strong>echte EasyInvoice</strong> met voorbeeldgegevens. Klik gerust overal op —
          er wordt niets verstuurd naar echte klanten.
        </span>
        <div class="demo-actions">
          <form method="POST" :action="route('demo.stop')">
            <input type="hidden" name="_token" :value="csrfToken">
            <button type="submit" class="demo-leave">Demo verlaten</button>
          </form>
          <form method="POST" :action="route('demo.stop')">
            <input type="hidden" name="_token" :value="csrfToken">
            <input type="hidden" name="to" value="register">
            <button type="submit" class="demo-cta">Start 14 dagen gratis</button>
          </form>
        </div>
      </div>

      <div v-if="showTrialBanner" class="trial-banner">
        <span class="trial-banner-text">
          🎁 Nog <strong>{{ subscription.days_left }}</strong>
          {{ subscription.days_left === 1 ? 'dag' : 'dagen' }} in je gratis proefperiode.
        </span>
        <Link :href="route('billing.show')" class="trial-banner-btn">Abonnement afsluiten</Link>
      </div>

      <div class="content">
        <slot />
      </div>
    </div>

    <Toast :message="flash.flash" type="success" />
    <EasyAgent />
  </div>
</template>

<style>
@import url('https://fonts.bunny.net/css?family=bricolage-grotesque:500,600,700|dm-sans:400,500,600,700|jetbrains-mono:400,500,600&display=swap');

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

  --text: #1C1917;
  --text-2: #44403C;
  --text-3: #78716C;
  --text-4: #A8A29E;

  --success: #15803D;
  --success-bg: #DCFCE7;
  --success-border: #86EFAC;
  --warning: #B45309;
  --warning-bg: #FEF3C7;
  --warning-border: #FCD34D;
  --info: #1E40AF;
  --info-bg: #DBEAFE;
  --info-border: #93C5FD;

  --shadow-sm: 0 1px 2px rgba(28,25,23,0.04);
  --shadow: 0 1px 3px rgba(28,25,23,0.06), 0 1px 2px rgba(28,25,23,0.04);
  --shadow-lg: 0 10px 24px rgba(28,25,23,0.08), 0 4px 8px rgba(28,25,23,0.04);

  --font-display: 'Bricolage Grotesque', system-ui, sans-serif;
  --font-body: 'DM Sans', system-ui, sans-serif;
  --font-mono: 'JetBrains Mono', ui-monospace, monospace;

  --r-sm: 6px;
  --r: 10px;
  --r-lg: 14px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
body {
  font-family: var(--font-body);
  font-size: 14px;
  line-height: 1.5;
  color: var(--text);
  background: var(--bg);
  -webkit-font-smoothing: antialiased;
}
button { font-family: inherit; cursor: pointer; border: none; background: none; color: inherit; }
input, select, textarea { font-family: inherit; font-size: inherit; color: inherit; }
a { color: inherit; text-decoration: none; }
table { border-collapse: collapse; width: 100%; }
.mono { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }

/* ============ APP SHELL ============ */
.app {
  display: grid;
  /* minmax(0, 1fr) i.p.v. 1fr: anders krijgt de kolom de min-content-breedte van
     de inhoud (brede tabellen) en wordt de hele pagina horizontaal scrollbaar. */
  grid-template-columns: 248px minmax(0, 1fr);
  min-height: 100vh;
}
.sidebar {
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
}
.sidebar-brand {
  padding: 20px 20px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--border);
}
.sidebar-brand .logo-mark {
  width: 36px; height: 36px;
  border-radius: 8px;
  flex-shrink: 0;
  object-fit: cover;
  display: block;
}
.sidebar-brand .brand-name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 16px;
  letter-spacing: -0.01em;
}
.sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
.nav-section { margin-bottom: 20px; }
.nav-section-title {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-4);
  padding: 0 12px;
  margin-bottom: 6px;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: var(--r-sm);
  color: var(--text-2);
  font-size: 14px;
  font-weight: 500;
  transition: background 0.15s, color 0.15s;
  cursor: pointer;
  position: relative;
}
.nav-item:hover { background: var(--surface-2); color: var(--text); }
.nav-item.active { background: var(--brand-tint); color: var(--brand-darker); }
.nav-item.active::before {
  content: '';
  position: absolute;
  left: -12px;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 18px;
  background: var(--brand);
  border-radius: 0 2px 2px 0;
}
.nav-item svg { width: 18px; height: 18px; stroke-width: 1.75; }
.nav-badge {
  margin-left: auto;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #fff;
  background: var(--brand);
  padding: 2px 7px;
  border-radius: 100px;
  line-height: 1.5;
  flex-shrink: 0;
}

/* ============ MODALS (globaal — gebruikt op meerdere pagina's) ============ */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(28,25,23,0.4);
  z-index: 100;
  display: flex; align-items: flex-start; justify-content: center;
  padding: 60px 20px; overflow-y: auto;
}
.modal {
  background: var(--surface); border-radius: var(--r-lg);
  box-shadow: var(--shadow-lg);
  width: 100%; max-width: 540px;
}
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border); }
.modal-title { font-family: var(--font-display); font-weight: 600; font-size: 18px; }
.modal-body { padding: 24px; }
.modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--surface-2); border-radius: 0 0 var(--r-lg) var(--r-lg); }
.icon-btn { width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.icon-btn:hover { background: var(--surface-2); }

.sidebar-user {
  border-top: 1px solid var(--border);
  padding: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  position: relative;
}
.sidebar-user:hover { background: var(--surface-2); }
.avatar {
  width: 32px; height: 32px;
  border-radius: 100px;
  background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
  color: white;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  flex-shrink: 0;
}
.user-info { flex: 1; min-width: 0; }
.user-name { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-co { font-size: 12px; color: var(--text-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-version { padding: 8px 16px 4px; font-size: 11px; color: var(--text-4); font-family: 'JetBrains Mono', monospace; letter-spacing: 0.02em; }
.user-menu {
  position: absolute;
  bottom: calc(100% + 4px);
  left: 14px;
  right: 14px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}
.user-menu-item {
  display: block;
  width: 100%;
  text-align: left;
  padding: 10px 14px;
  font-size: 13px;
  cursor: pointer;
}
.user-menu-item:hover { background: var(--surface-2); }
.user-menu-label {
  padding: 8px 14px 4px; font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em;
  text-transform: uppercase; color: var(--text-4);
}
.user-menu-sep { height: 1px; background: var(--border); margin: 4px 0; }
.user-menu-item.is-active-admin { font-weight: 600; color: var(--brand-darker); }
.user-menu-item .admin-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.user-menu-item svg, .user-menu-item .admin-name { vertical-align: middle; }
.user-menu-item.is-active-admin, .user-menu button.user-menu-item { display: flex; align-items: center; gap: 8px; }

/* MAIN */
.main { background: var(--bg); min-width: 0; }
.topbar {
  height: 64px;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 10;
}
.topbar-left { display: flex; align-items: center; gap: 16px; flex: 1; }
.topbar-right { display: flex; align-items: center; gap: 8px; }
.breadcrumb { font-size: 13px; color: var(--text-3); }
.breadcrumb-current { color: var(--text); font-weight: 500; }
.content { padding: 32px; max-width: 1400px; margin: 0 auto; }

/* DEMO BANNER */
.demo-banner {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  padding: 11px 24px;
  background: linear-gradient(100deg, var(--brand-tint) 0%, var(--surface) 70%);
  border-bottom: 1px solid var(--brand-border);
  color: var(--brand-darker);
  font-size: 13.5px;
}
.demo-chip {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #fff;
  background: var(--brand);
  padding: 3px 9px;
  border-radius: 100px;
  flex-shrink: 0;
}
.demo-text { flex: 1; min-width: 200px; }
.demo-text strong { font-weight: 700; }
.demo-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.demo-leave {
  height: 30px;
  padding: 0 12px;
  border-radius: var(--r-sm);
  border: 1px solid var(--brand-border);
  background: var(--surface);
  color: var(--brand-dark);
  font-size: 13px;
  font-weight: 600;
}
.demo-leave:hover { background: var(--brand-tint); }
.demo-cta {
  display: inline-flex;
  align-items: center;
  height: 30px;
  padding: 0 14px;
  border-radius: var(--r-sm);
  background: var(--brand);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
}
.demo-cta:hover { background: var(--brand-dark); }
@media (max-width: 760px) {
  .demo-banner { padding: 10px 12px; gap: 10px; }
  .demo-actions { margin-left: 0; width: 100%; }
  .demo-actions form { flex: 1; }
  .demo-cta, .demo-leave { width: 100%; justify-content: center; }
}

/* TRIAL BANNER */
.trial-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
  padding: 10px 24px;
  background: var(--brand-tint);
  border-bottom: 1px solid var(--brand-border);
  color: var(--brand-darker);
  font-size: 14px;
}
.trial-banner-text strong { font-weight: 700; }
.trial-banner-btn {
  display: inline-flex;
  align-items: center;
  height: 30px;
  padding: 0 14px;
  border-radius: var(--r-sm);
  background: var(--brand);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
}
.trial-banner-btn:hover { background: var(--brand-dark); }

/* HEADERS */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 28px;
  gap: 20px;
  flex-wrap: wrap;
}
.page-title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 28px;
  letter-spacing: -0.015em;
  line-height: 1.1;
}
.page-subtitle { color: var(--text-3); margin-top: 6px; font-size: 14px; }
.page-actions { display: flex; gap: 8px; align-items: center; }

/* BUTTONS */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 40px;
  padding: 0 16px;
  border-radius: var(--r-sm);
  font-weight: 500;
  font-size: 14px;
  transition: all 0.15s;
  white-space: nowrap;
  cursor: pointer;
}
.btn-primary { background: var(--brand); color: white; }
.btn-primary:hover { background: var(--brand-dark); }
.btn-secondary { background: var(--surface); color: var(--text); border: 1px solid var(--border); }
.btn-secondary:hover { background: var(--surface-2); border-color: var(--border-strong); }
.btn-ghost { color: var(--text-2); }
.btn-ghost:hover { background: var(--surface-2); color: var(--text); }
.btn-danger { background: white; color: var(--brand-dark); border: 1px solid var(--brand-border); }
.btn-danger:hover { background: var(--brand-tint); }
.btn-block { width: 100%; }
.btn-sm { height: 32px; padding: 0 12px; font-size: 13px; }
.btn-icon { width: 36px; padding: 0; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* CARDS */
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  overflow: hidden;
}
.card-header {
  padding: 18px 20px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border);
}
.card-title { font-family: var(--font-display); font-weight: 600; font-size: 16px; }
.card-subtitle { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.card-body { padding: 20px; }
.card-body-flush { padding: 0; }
.card-empty {
  padding: 60px 20px;
  text-align: center;
  color: var(--text-3);
}

/* FORMS */
.form-group { margin-bottom: 16px; }
.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-2);
  margin-bottom: 6px;
}
.form-group .label-hint {
  font-size: 12px;
  color: var(--text-4);
  font-weight: 400;
  margin-left: 4px;
}
.form-group input, .form-group select, .form-group textarea {
  width: 100%;
  height: 42px;
  padding: 0 14px;
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  background: var(--surface);
  transition: border-color 0.15s, box-shadow 0.15s;
}
.form-group textarea { height: auto; padding: 10px 14px; min-height: 80px; resize: vertical; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 3px var(--brand-tint);
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.field-error { color: var(--brand); font-size: 12px; margin-top: 4px; }

/* TABLES */
.data-table { font-size: 13px; }
.data-table th {
  background: var(--surface-2);
  text-align: left;
  padding: 10px 16px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-3);
  border-bottom: 1px solid var(--border);
}
.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.data-table tbody tr { transition: background 0.1s; cursor: pointer; }
.data-table tbody tr:hover { background: var(--surface-2); }
.data-table tbody tr:last-child td { border-bottom: none; }
.data-table .num { font-family: var(--font-mono); font-weight: 500; font-variant-numeric: tabular-nums; }
.data-table .right { text-align: right; }

/* Op mobiel wordt elke tabelrij een kaartje: label links, waarde rechts.
   Zo past alles binnen het scherm en scrol je alleen naar beneden.
   De labels komen uit het data-label-attribuut op elke <td>; de cel met
   .cell-primary wordt de titel van het kaartje.
   Geldt voor .data-table en voor elke tabel met .stacked-table. */
@media (max-width: 760px) {
  .data-table, .stacked-table { display: block; font-size: 14px; }
  .data-table thead, .stacked-table thead { display: none; }
  .data-table tbody, .data-table tr, .data-table td,
  .stacked-table tbody, .stacked-table tr, .stacked-table td { display: block; }
  .data-table tr, .stacked-table tr {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
  }
  .data-table td, .stacked-table td {
    padding: 0;
    border: none;
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 14px;
    min-height: 24px;
    text-align: left;
  }
  .data-table td + td, .stacked-table td + td { margin-top: 6px; }
  .data-table td::before, .stacked-table td::before {
    content: attr(data-label);
    flex: 0 0 auto;
    white-space: nowrap;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-3);
  }
  /* De eerste cel is de titel van het kaartje: volle breedte, zonder label. */
  .data-table td.cell-primary, .stacked-table td.cell-primary {
    display: block;
    font-weight: 600;
    font-size: 15px;
    margin-bottom: 10px;
  }
  .data-table td.cell-primary::before, .stacked-table td.cell-primary::before { content: none; }
  /* Cel zonder label en zonder titelrol (bijv. het chevron-pijltje) valt weg. */
  .data-table td:not([data-label]):not(.cell-primary),
  .stacked-table td:not([data-label]):not(.cell-primary) { display: none; }
  /* De waarde mag afbreken; het label niet. */
  .data-table td > *, .stacked-table td > * { min-width: 0; }
}

/* Alleen-lezen-badge (boekhouder) */
.readonly-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 600;
  color: var(--text-3);
  background: var(--surface-2);
  border: 1px solid var(--border-strong);
  border-radius: 100px;
  padding: 5px 12px;
  cursor: help;
}

/* PILLS */
.pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 100px;
  font-size: 11px;
  font-weight: 600;
  border: 1px solid transparent;
}
.pill::before {
  content: '';
  width: 6px; height: 6px;
  border-radius: 100px;
  background: currentColor;
}
.pill-paid    { color: var(--success); background: var(--success-bg); border-color: var(--success-border); }
.pill-sent    { color: var(--info); background: var(--info-bg); border-color: var(--info-border); }
.pill-overdue { color: var(--brand-darker); background: var(--brand-tint-2); border-color: var(--brand-border); }
.pill-draft   { color: var(--text-2); background: var(--surface-3); border-color: var(--border-strong); }
.pill-partial { color: var(--warning); background: var(--warning-bg); border-color: var(--warning-border); }
.pill-cancelled { color: var(--text-3); background: var(--surface-3); border-color: var(--border-strong); text-decoration: line-through; }

/* FILTERS */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.filter-search { position: relative; flex: 1; max-width: 320px; }
.filter-search input {
  width: 100%;
  height: 36px;
  padding: 0 14px 0 36px;
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  background: var(--surface);
  font-size: 13px;
}
.filter-search input:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 3px var(--brand-tint);
}
.filter-search svg {
  position: absolute;
  left: 11px; top: 50%;
  transform: translateY(-50%);
  width: 16px; height: 16px;
  color: var(--text-4);
}
.filter-chip {
  padding: 7px 12px;
  border-radius: 100px;
  background: var(--surface);
  border: 1px solid var(--border);
  font-size: 12px;
  font-weight: 500;
  color: var(--text-2);
  cursor: pointer;
  transition: all 0.15s;
}
.filter-chip:hover { background: var(--surface-2); }
.filter-chip.active { background: var(--text); color: white; border-color: var(--text); }
.filter-chip .count { font-family: var(--font-mono); margin-left: 4px; opacity: 0.7; }

/* RESPONSIVE */
.topbar-toggle { display: none; align-items: center; justify-content: center; width: 40px; height: 40px; margin-left: -8px; border-radius: 8px; color: var(--text-2); cursor: pointer; flex-shrink: 0; }
.topbar-toggle svg { width: 22px; height: 22px; }
.topbar-toggle:hover { background: var(--surface-2); color: var(--text); }
.sidebar-overlay { display: none; }

@media (max-width: 1100px) {
  .form-row, .form-row-3 { grid-template-columns: 1fr; }
}
@media (max-width: 760px) {
  .app { grid-template-columns: minmax(0, 1fr); }
  .sidebar {
    position: fixed; top: 0; left: 0; z-index: 100;
    width: 268px; max-width: 84vw; height: 100vh;
    transform: translateX(-100%);
    transition: transform 0.25s ease;
    box-shadow: 0 10px 40px rgba(28, 25, 23, 0.18);
  }
  .sidebar.open { transform: translateX(0); }
  .sidebar-overlay { display: block; position: fixed; inset: 0; z-index: 90; background: rgba(28, 25, 23, 0.45); }
  .topbar-toggle { display: inline-flex; }
  .content { padding: 16px; }
  .topbar { padding: 0 12px; }
  /* Zoekveld op een eigen regel; naast de filterknoppen bleef er "Zoe..." over. */
  .filter-search { flex: 1 0 100%; max-width: none; }
}
</style>
