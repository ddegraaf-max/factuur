<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  // Ingelogd e-mailadres (alleen op pagina's achter de verificatie)
  email: { type: String, default: null },
});

const page = usePage();
const brand = computed(() => page.props.brand);
const flash = computed(() => page.props.flash?.flash || null);
const flashError = computed(() => page.props.flash?.error || null);

const logout = () => {
  router.post(route('portal.logout'));
};
</script>

<template>
  <div class="portal-shell">
    <header class="portal-topbar">
      <div class="portal-topbar-inner">
        <a :href="brand.url" target="_blank" rel="noopener" class="portal-brand">
          <img :src="brand.mark" class="portal-logo" :alt="brand.name">
          <div>
            <div class="portal-brand-name">{{ brand.name }}</div>
            <div class="portal-brand-sub"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Klantenportaal · beveiligde omgeving</div>
          </div>
        </a>
        <div v-if="email" class="portal-user">
          <span class="portal-user-email">{{ email }}</span>
          <button type="button" class="portal-logout" @click="logout">Uitloggen</button>
        </div>
      </div>
    </header>

    <main class="portal-main">
      <div v-if="flash" class="portal-flash">{{ flash }}</div>
      <div v-if="flashError" class="portal-flash portal-flash-error">{{ flashError }}</div>
      <slot />
    </main>

    <footer class="portal-footer">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      <span>Beveiligd portaal · mogelijk gemaakt door <a :href="brand.url" target="_blank" rel="noopener">{{ brand.name }}</a></span>
    </footer>
  </div>
</template>

<style>
/* Niet scoped: ook StatusPill en de portaalpagina's gebruiken deze stijlen. */
.portal-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background:
    radial-gradient(1000px 400px at 50% -200px, var(--brand-tint) 0%, transparent 60%),
    var(--bg);
}
.portal-topbar {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
}
.portal-topbar-inner {
  max-width: 920px;
  margin: 0 auto;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}
.portal-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
.portal-logo {
  width: 40px; height: 40px; border-radius: 11px; display: block; flex: none;
  box-shadow: 0 1px 2px rgba(28,25,23,0.18);
}
.portal-brand-name { font-family: var(--font-display); font-weight: 800; font-size: 17px; letter-spacing: -0.02em; line-height: 1.15; color: var(--text); }
.portal-brand-sub { display: flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--text-3); margin-top: 2px; }
.portal-brand-sub svg { width: 12px; height: 12px; color: var(--success); }
.portal-user { display: flex; align-items: center; gap: 12px; min-width: 0; }
.portal-user-email { font-size: 13px; color: var(--text-2); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.portal-logout {
  font-size: 13px; font-weight: 500; color: var(--text-3);
  border: 1px solid var(--border); border-radius: var(--r-sm);
  padding: 6px 12px; background: var(--surface); flex: none;
}
.portal-logout:hover { color: var(--text); border-color: var(--border-strong); background: var(--surface-2); }

.portal-main {
  flex: 1;
  width: 100%;
  max-width: 920px;
  margin: 0 auto;
  padding: 28px 20px 48px;
}
.portal-flash-error {
  background: var(--danger-bg, #FEF2F2) !important;
  color: var(--danger, #B91C1C) !important;
  border-color: var(--danger-border, #FECACA) !important;
}
.portal-flash {
  background: var(--success-bg);
  color: var(--success);
  border: 1px solid var(--success-border);
  border-radius: var(--r-sm);
  padding: 10px 14px;
  margin-bottom: 16px;
  font-size: 13px;
  font-weight: 500;
}
.portal-footer {
  display: flex; align-items: center; justify-content: center; gap: 7px;
  padding: 18px 20px 26px;
  font-size: 12px; color: var(--text-4);
}
.portal-footer svg { width: 13px; height: 13px; }
.portal-footer a { color: var(--text-3); font-weight: 500; }
.portal-footer a:hover { text-decoration: underline; }

/* Gedeelde kaartstijl voor alle portaalpagina's */
.portal-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: 32px 36px;
  width: 100%;
  box-shadow: var(--shadow);
}
.portal-card-title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 24px;
  letter-spacing: -0.015em;
  color: var(--text);
  margin-bottom: 8px;
}
.portal-card-sub {
  font-size: 14px;
  color: var(--text-3);
  line-height: 1.6;
  margin-bottom: 22px;
}
@media (max-width: 560px) {
  .portal-card { padding: 24px 18px; }
}

/* Statuspills (kopie van AppLayout — het portaal laadt die layout niet) */
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
.pill-incasso { color: #FBBF24; background: #1F2937; border-color: #374151; }
.pill-cancelled { color: var(--text-3); background: var(--surface-3); border-color: var(--border-strong); text-decoration: line-through; }

@media (max-width: 560px) {
  .portal-user-email { display: none; }
  .portal-main { padding: 20px 14px 40px; }
}
</style>
