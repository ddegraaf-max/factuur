<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  cases: Array,
  stats: Object,
  handler: Object,
});

const eur = (n) => '€ ' + Number(n).toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatDate = (s) => s ? new Date(s).toLocaleDateString('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';

const phaseLabels = {
  minnelijk: 'Minnelijk traject',
  gerechtelijk: 'Gerechtelijke procedure',
  executie: 'Executie',
};
</script>

<template>
  <Head title="Incasso" />
  <AppLayout>
    <template #breadcrumb>Verkoop / <span class="breadcrumb-current">Incasso</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Incasso</h1>
        <p class="page-subtitle">Facturen die zijn overgedragen aan de deurwaarder</p>
      </div>
    </div>

    <div class="armaere-card">
      <div class="armaere-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8" /><path d="m16 16 6-6" /><path d="m8 8 6-6" /><path d="m9 7 8 8" /><path d="m21 11-8-8" />
        </svg>
      </div>
      <div>
        <div class="eyebrow">Incassopartner</div>
        <div class="name">{{ handler.name }}</div>
        <div class="sub">{{ handler.tagline }}</div>
      </div>
      <div class="contacts">
        <div>📞 <b>{{ handler.phone }}</b></div>
        <div>✉ {{ handler.email }}</div>
        <div>🌐 {{ handler.portal }}</div>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="lbl">Actieve dossiers</div>
        <div class="val">{{ stats.count }}</div>
      </div>
      <div class="stat-card">
        <div class="lbl">Totaal in incasso</div>
        <div class="val">{{ eur(stats.total_open) }}</div>
      </div>
      <div class="stat-card">
        <div class="lbl">Langst lopende dossier</div>
        <div class="val">{{ stats.oldest_days > 0 ? stats.oldest_days + ' dagen' : '—' }}</div>
      </div>
    </div>

    <div v-if="cases.length === 0" class="card empty">
      <div style="font-family:var(--font-display);font-size:18px;font-weight:600;margin-bottom:6px;">Geen actieve dossiers</div>
      <div style="color:var(--text-3);">Achterstallige facturen kun je via de detailpagina overdragen aan Armaere.</div>
    </div>

    <div v-else class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Actieve dossiers</div>
          <div class="card-subtitle">{{ cases.length }} {{ cases.length === 1 ? 'factuur' : 'facturen' }} in behandeling bij {{ handler.name }}</div>
        </div>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th>Dossier</th>
            <th>Factuur</th>
            <th>Klant</th>
            <th>Overdracht</th>
            <th>Looptijd</th>
            <th>Fase</th>
            <th class="right">Openstaand</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in cases" :key="c.id">
            <td class="mono">{{ c.incasso_reference }}</td>
            <td class="mono"><Link :href="route('invoices.show', c.id)">{{ c.number }}</Link></td>
            <td>{{ c.customer_name }}</td>
            <td>{{ formatDate(c.incasso_sent_at) }}</td>
            <td>{{ c.days_at_armaere }} dagen</td>
            <td><span class="pill pill-incasso">{{ phaseLabels[c.incasso_phase] || c.incasso_phase }}</span></td>
            <td class="right num">{{ eur(c.remaining) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<style scoped>
.armaere-card { background: linear-gradient(135deg, #1F2937 0%, #111827 100%); color: white; border-radius: 12px; padding: 24px 28px; margin-bottom: 20px; display: grid; grid-template-columns: auto 1fr auto; gap: 20px; align-items: center; }
.armaere-icon { width: 56px; height: 56px; background: rgba(252,211,77,.15); color: #FCD34D; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; }
.armaere-icon svg { width: 28px; height: 28px; }
.eyebrow { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #FCD34D; font-weight: 700; margin-bottom: 4px; }
.name { font-family: var(--font-display); font-weight: 700; font-size: 22px; }
.sub { font-size: 13px; color: #D1D5DB; }
.contacts { display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #D1D5DB; }
.contacts b { color: white; }
.stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
.stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.stat-card .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.stat-card .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; }
.empty { padding: 80px 20px; text-align: center; }
.pill-incasso { color: #FBBF24; background: #1F2937; border: 1px solid #374151; padding: 3px 9px; border-radius: 100px; font-size: 11px; font-weight: 600; }
</style>
