<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const components = [
  ['Webapplicatie', 'operational'],
  ['Facturen & PDF-generatie', 'operational'],
  ['E-mail versturen', 'operational'],
  ['Herinneringen & incasso', 'operational'],
  ['Inloggen & 2FA', 'operational'],
  ['Database', 'operational'],
];

const history = [
  ['7 juni 2026', 'Geen incidenten — alles werkte normaal.'],
  ['6 juni 2026', 'Geen incidenten — alles werkte normaal.'],
  ['5 juni 2026', 'Gepland onderhoud (02:00–02:20). Geen merkbare onderbreking.'],
  ['4 juni 2026', 'Geen incidenten — alles werkte normaal.'],
  ['3 juni 2026', 'Geen incidenten — alles werkte normaal.'],
];

const label = (s) => s === 'operational' ? 'Operationeel' : s === 'degraded' ? 'Verminderd' : 'Storing';
</script>

<template>
  <Head title="Status" />
  <PublicLayout>
    <section class="m-section" style="padding-top:56px;">
      <div class="m-wrap" style="max-width:760px;">
        <div class="status-banner">
          <span class="status-big-dot"></span>
          <div>
            <h1 class="m-h2" style="font-size:26px;">Alle systemen operationeel</h1>
            <p class="m-muted" style="margin-top:2px;">Laatst bijgewerkt: 7 juni 2026, 09:00 uur</p>
          </div>
        </div>

        <div class="m-card" style="margin-top:24px;padding:0;overflow:hidden;">
          <div v-for="(c, i) in components" :key="c[0]" class="status-row" :class="{ first: i === 0 }">
            <span style="font-weight:500;">{{ c[0] }}</span>
            <span class="status-tag" :class="`st-${c[1]}`"><span class="st-dot"></span> {{ label(c[1]) }}</span>
          </div>
        </div>

        <div style="display:flex;align-items:baseline;justify-content:space-between;margin:36px 0 14px;">
          <h2 class="m-h3">Uptime laatste 90 dagen</h2>
          <span style="font-weight:700;color:var(--success);">99,98%</span>
        </div>
        <div class="uptime-bar">
          <span v-for="n in 90" :key="n" class="uptime-tick" :class="{ down: n === 30 }"></span>
        </div>

        <h2 class="m-h3" style="margin:36px 0 14px;">Recente geschiedenis</h2>
        <div style="display:grid;gap:10px;">
          <div v-for="h in history" :key="h[0]" class="m-card" style="display:flex;gap:16px;align-items:baseline;padding:16px 20px;">
            <span class="m-muted" style="font-size:13px;white-space:nowrap;min-width:96px;">{{ h[0] }}</span>
            <span style="font-size:14px;">{{ h[1] }}</span>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.status-banner {
  display: flex; align-items: center; gap: 16px;
  background: var(--success-bg); border: 1px solid var(--success-border);
  border-radius: var(--r-lg); padding: 24px 28px;
}
.status-big-dot { width: 16px; height: 16px; border-radius: 100px; background: var(--success); box-shadow: 0 0 0 5px rgba(21,128,61,0.15); flex-shrink: 0; }
.status-row { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-top: 1px solid var(--border); }
.status-row.first { border-top: none; }
.status-tag { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; }
.st-dot { width: 8px; height: 8px; border-radius: 100px; background: currentColor; }
.st-operational { color: var(--success); }
.st-degraded { color: #B45309; }
.st-down { color: var(--brand); }
.uptime-bar { display: flex; gap: 2px; align-items: flex-end; }
.uptime-tick { flex: 1; height: 32px; border-radius: 2px; background: var(--success); opacity: 0.85; }
.uptime-tick.down { background: var(--warning, #B45309); }
</style>
