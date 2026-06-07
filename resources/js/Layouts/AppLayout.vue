<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import EasyAgent from '@/Components/EasyAgent.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const company = computed(() => page.props.auth.company);

const initials = computed(() => {
  const parts = (user.value?.name ?? '').trim().split(/\s+/);
  const first = parts[0]?.[0] ?? '';
  const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
  return (first + last).toUpperCase();
});

const nav = [
  {
    title: 'Overzicht',
    items: [
      { name: 'Dashboard', route: 'dashboard', icon: 'dashboard' },
    ],
  },
  {
    title: 'Verkoop',
    items: [
      { name: 'Facturen', route: 'invoices.index', icon: 'invoice' },
      { name: 'Klanten', route: 'customers.index', icon: 'users' },
      { name: 'Producten', route: 'products.index', icon: 'box' },
      { name: 'Incasso', route: 'incasso.index', icon: 'gavel' },
    ],
  },
  {
    title: 'Rapporten',
    items: [
      { name: 'Klantomzet', route: 'stats.index', icon: 'chart' },
    ],
  },
  {
    title: 'Instellingen',
    items: [
      { name: 'Bedrijfsgegevens', route: 'settings.company', icon: 'settings' },
      { name: 'Huisstijl', route: 'settings.brand', icon: 'palette' },
      { name: 'Nummering', route: 'settings.numbering', icon: 'hash' },
      { name: 'Herinneringen', route: 'settings.reminders', icon: 'bell' },
      { name: 'Beveiliging', route: 'settings.security', icon: 'shield' },
    ],
  },
];

const isActive = (routeName) => {
  return route().current(routeName) || route().current(routeName.replace('.index', '.*'));
};

const flash = computed(() => page.props.flash || {});

const userMenuOpen = ref(false);

const logout = () => {
  router.post(route('logout'));
};
</script>

<template>
  <div class="app">
    <aside class="sidebar">
      <Link :href="route('dashboard')" class="sidebar-brand">
        <span class="logo-mark">E</span>
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
          >
            <!-- Icons -->
            <svg v-if="item.icon === 'dashboard'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
            <svg v-else-if="item.icon === 'invoice'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg>
            <svg v-else-if="item.icon === 'users'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
            <svg v-else-if="item.icon === 'box'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            <svg v-else-if="item.icon === 'settings'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <svg v-else-if="item.icon === 'gavel'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
            <svg v-else-if="item.icon === 'chart'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="20" x2="21" y2="20"/></svg>
            <svg v-else-if="item.icon === 'palette'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
            <svg v-else-if="item.icon === 'hash'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>
            <svg v-else-if="item.icon === 'bell'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <svg v-else-if="item.icon === 'shield'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            {{ item.name }}
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
          <Link :href="route('settings.security')" class="user-menu-item">Beveiliging</Link>
          <button class="user-menu-item" @click.stop="logout">Uitloggen</button>
        </div>
      </div>
    </aside>

    <div class="main">
      <header class="topbar">
        <div class="topbar-left">
          <slot name="breadcrumb"></slot>
        </div>
        <div class="topbar-right">
          <slot name="topbar-actions"></slot>
        </div>
      </header>

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
  grid-template-columns: 248px 1fr;
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
  background: var(--brand);
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  font-family: system-ui, sans-serif;
  font-weight: 700;
  font-size: 20px;
  line-height: 1;
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

/* MAIN */
.main { background: var(--bg); }
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
@media (max-width: 1100px) {
  .form-row, .form-row-3 { grid-template-columns: 1fr; }
}
@media (max-width: 760px) {
  .app { grid-template-columns: 1fr; }
  .sidebar { display: none; }
  .content { padding: 16px; }
  .topbar { padding: 0 16px; }
}
</style>
