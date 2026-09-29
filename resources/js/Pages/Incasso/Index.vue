<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { t } from '@/i18n';
import { eur, fmtDate } from '@/format';
import { computed } from 'vue';

const props = defineProps({
  demands: { type: Array, default: () => [] },
  cases: Array,
  stats: Object,
  handler: Object,
});

// Markt (nl/pl): de incassopartner komt van de server, met de marktinstelling als vangnet.
const market = usePage().props.market || {};
const partnerName = computed(() => props.handler?.name || market.incasso_partner || '');

// De fase bepaalt hoe ver het traject is: eerst minnelijk (schikken), dan via
// de rechter, en ten slotte executie (beslag).
const changePhase = (invoice, phase) => {
  if (!phase || phase === invoice.incasso_phase) return;
  router.patch(route('incasso.phase', invoice.id), { phase }, { preserveScroll: true });
};

// Online aanmaning: termijn voorbij en niet betaald, dan gaat het dossier met één klik over.
const transfer = (d) => {
  const msg = t('Factuur :number overdragen aan :partner?', { number: d.number, partner: partnerName.value }) + '\n\n'
    + t('De factuur, de aanmaning, het logboek en de berekening van rente en incassokosten gaan per e-mail mee. Dit kun je niet ongedaan maken.');
  if (confirm(msg)) {
    router.post(route('demands.transfer', [d.invoice_id, d.id]), {}, { preserveScroll: true });
  }
};
const demandError = computed(() => (usePage().props.errors || {}).demand ?? null);

const formatDate = (s) => s ? fmtDate(s, { day: 'numeric', month: 'short', year: 'numeric' }) : '—';

const phaseLabels = {
  minnelijk: t('Minnelijk traject'),
  gerechtelijk: t('Gerechtelijke procedure'),
  executie: t('Executie'),
};

</script>

<template>
  <Head :title="$t('Incasso')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Verkoop') }} / <span class="breadcrumb-current">{{ $t('Incasso') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Incasso') }}</h1>
        <p class="page-subtitle">{{ $t('Facturen die zijn overgedragen aan de deurwaarder') }}</p>
      </div>
    </div>

    <div class="armaere-card">
      <div class="armaere-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8" /><path d="m16 16 6-6" /><path d="m8 8 6-6" /><path d="m9 7 8 8" /><path d="m21 11-8-8" />
        </svg>
      </div>
      <div>
        <div class="eyebrow">{{ $t('Incassopartner') }}</div>
        <div class="name">{{ partnerName }}</div>
        <div class="sub">{{ $t(handler.tagline) }}</div>
      </div>
      <div class="contacts">
        <div>✉ <b>{{ handler.email }}</b></div>
        <div style="opacity:.7;font-size:12px;margin-top:4px;">{{ $t('Nieuwe dossiers worden automatisch per e-mail aangeleverd.') }}</div>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="lbl">{{ $t('Actieve dossiers') }}</div>
        <div class="val">{{ stats.count }}</div>
      </div>
      <div class="stat-card">
        <div class="lbl">{{ $t('Totaal in incasso') }}</div>
        <div class="val">{{ eur(stats.total_open) }}</div>
      </div>
      <div class="stat-card">
        <div class="lbl">{{ $t('Langst lopende dossier') }}</div>
        <div class="val">{{ stats.oldest_days > 0 ? $t(':n dagen', { n: stats.oldest_days }) : '—' }}</div>
      </div>
    </div>

    <div v-if="demandError" class="field-error" style="margin-bottom:12px;">{{ demandError }}</div>

    <!-- Lopende online aanmaningen: de stap vóór de overdracht -->
    <div v-if="demands.length" class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Laatste aanmaningen') }}</div>
          <div class="card-subtitle">{{ $t('Is de termijn voorbij en is er niet betaald, dan draag je het dossier met één klik over.') }}</div>
        </div>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Factuur') }}</th>
            <th>{{ $t('Klant') }}</th>
            <th>{{ $t('Verstuurd') }}</th>
            <th>{{ $t('Termijn tot en met') }}</th>
            <th>{{ $t('Reactie') }}</th>
            <th class="right">{{ $t('Te betalen vandaag') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in demands" :key="d.id">
            <td class="mono cell-primary"><Link :href="route('invoices.show', d.invoice_id)">{{ d.number }}</Link></td>
            <td :data-label="$t('Klant')">{{ d.customer_name }}</td>
            <td :data-label="$t('Verstuurd')">{{ formatDate(d.sent_at) }}</td>
            <td :data-label="$t('Termijn tot en met')">
              {{ formatDate(d.deadline) }}
              <span v-if="d.due" class="due-chip">{{ $t('Termijn verstreken') }}</span>
            </td>
            <td :data-label="$t('Reactie')">{{ d.response_label || (d.opened ? $t('Geopend, geen reactie') : $t('Nog niet geopend')) }}</td>
            <td class="right num" :data-label="$t('Te betalen vandaag')">{{ eur(d.total) }}</td>
            <td class="right">
              <button v-if="d.due && !d.paused" class="btn btn-primary btn-sm" @click="transfer(d)">{{ $t('Overdragen') }}</button>
              <span v-else-if="d.paused" class="sub">{{ $t('Op pauze') }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="cases.length === 0" class="card empty">
      <div style="font-family:var(--font-display);font-size:18px;font-weight:600;margin-bottom:6px;">{{ $t('Geen actieve dossiers') }}</div>
      <div style="color:var(--text-3);margin-bottom:18px;">
        {{ $t('Open een achterstallige factuur en klik daar op') }} <b>{{ $t('“Naar incasso”') }}</b> {{ $t('om het dossier over te dragen aan :partner.', { partner: partnerName }) }}
        {{ $t('Eerst nog één kans geven? Stuur vanaf de factuur een laatste aanmaning.') }}
      </div>
      <Link :href="route('invoices.index', { status: 'overdue' })" class="btn btn-primary btn-sm" style="display:inline-flex;">
        {{ $t('Bekijk verlopen facturen') }}
      </Link>
    </div>

    <div v-else class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Actieve dossiers') }}</div>
          <div class="card-subtitle">{{ cases.length }} {{ cases.length === 1 ? $t('factuur') : $t('facturen') }} {{ $t('in behandeling bij :partner', { partner: partnerName }) }}</div>
        </div>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Dossier') }}</th>
            <th>{{ $t('Factuur') }}</th>
            <th>{{ $t('Klant') }}</th>
            <th>{{ $t('Overdracht') }}</th>
            <th>{{ $t('Looptijd') }}</th>
            <th>{{ $t('Fase') }}</th>
            <th class="right">{{ $t('Openstaand') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in cases" :key="c.id">
            <td class="mono cell-primary">{{ c.incasso_reference }}</td>
            <td class="mono" :data-label="$t('Factuur')"><Link :href="route('invoices.show', c.id)">{{ c.number }}</Link></td>
            <td :data-label="$t('Klant')">{{ c.customer_name }}</td>
            <td :data-label="$t('Overdracht')">{{ formatDate(c.incasso_sent_at) }}</td>
            <td :data-label="$t('Looptijd')">{{ $t(':n dagen', { n: c.days_at_armaere }) }}</td>
            <td :data-label="$t('Fase')">
              <select class="phase-select" :value="c.incasso_phase" @change="changePhase(c, $event.target.value)">
                <option v-for="(label, value) in phaseLabels" :key="value" :value="value">{{ label }}</option>
              </select>
            </td>
            <td class="right num" :data-label="$t('Openstaand')">{{ eur(c.remaining) }}</td>
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
.stat-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
.stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.stat-card .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.stat-card .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; }
.empty { padding: 80px 20px; text-align: center; }
.due-chip { display: inline-block; margin-left: 6px; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 100px; color: #92400E; background: #FEF3C7; border: 1px solid #FCD34D; white-space: nowrap; }
.sub { font-size: 12px; color: var(--text-3); }
.pill-incasso { color: #FBBF24; background: #1F2937; border: 1px solid #374151; padding: 3px 9px; border-radius: 100px; font-size: 11px; font-weight: 600; }
.phase-select {
  height: 32px;
  padding: 0 8px;
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  background: var(--surface);
  font-size: 12.5px;
  font-weight: 500;
  color: var(--text);
  cursor: pointer;
  max-width: 100%;
}
.phase-select:hover { border-color: var(--border-strong); }
.phase-select:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-tint); }

@media (max-width: 760px) {
  /* Icoon, naam en contactgegevens onder elkaar i.p.v. drie kolommen. */
  .armaere-card { grid-template-columns: minmax(0, 1fr); gap: 14px; padding: 20px; }
  .name { font-size: 19px; }
  /* Lange e-mailadressen mogen afbreken, anders duwen ze de pagina breder. */
  .contacts { overflow-wrap: anywhere; }
  .stat-grid { grid-template-columns: minmax(0, 1fr); gap: 10px; }
}
</style>
