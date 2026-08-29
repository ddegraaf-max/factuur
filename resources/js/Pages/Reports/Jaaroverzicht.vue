<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { computed } from 'vue';

const brand = usePage().props.brand;

const props = defineProps({
  year: Number,
  allYears: Array,
  totals: Object,   // { revenue, costs, km, km_amount, result, invoice_count, purchase_count, trip_count }
  previous: Object, // zelfde vorm, voor jaar − 1
  quarters: Array,  // [{ label, revenue, costs, km_amount, result }]
  categories: Array,// [{ name, amount, count }]
});

const setYear = (year) => router.get(route('yearreport.index'), { year }, { preserveState: true });

const delta = (nu, vorig) => {
  if (!vorig) return null;
  return Math.round((nu - vorig) / Math.abs(vorig) * 100);
};

const revenueDelta = computed(() => delta(props.totals.revenue, props.previous.revenue));
const resultDelta = computed(() => delta(props.totals.result, props.previous.result));

const maxCategory = computed(() => Math.max(...props.categories.map(c => c.amount), 1));
</script>

<template>
  <Head title="Jaaroverzicht" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Rapporten / <span class="breadcrumb-current">Jaaroverzicht</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Jaaroverzicht {{ year }}</h1>
        <p class="page-subtitle">Omzet, kosten en resultaat uit je facturatie — de basis voor je aangifte of voor je boekhouder.</p>
      </div>
      <div style="display:flex;gap:10px;align-items:center;">
        <select class="yr-select" :value="year" @change="setYear($event.target.value)">
          <option v-for="y in allYears" :key="y" :value="y">{{ y }}</option>
        </select>
        <a :href="route('yearreport.pdf', { year })" class="btn btn-secondary">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download PDF
        </a>
      </div>
    </div>

    <!-- Totalen -->
    <div class="kpi-grid">
      <div class="kpi">
        <div class="lbl">Omzet (excl. btw)</div>
        <div class="val">{{ eur(totals.revenue) }}</div>
        <div class="meta">
          {{ totals.invoice_count }} facturen
          <template v-if="revenueDelta !== null"> · {{ revenueDelta >= 0 ? '+' : '' }}{{ revenueDelta }}% t.o.v. {{ year - 1 }}</template>
        </div>
      </div>
      <div class="kpi">
        <div class="lbl">Kosten (excl. btw)</div>
        <div class="val">{{ eur(totals.costs) }}</div>
        <div class="meta">{{ totals.purchase_count }} inkoopfacturen</div>
      </div>
      <div class="kpi">
        <div class="lbl">Kilometeraftrek</div>
        <div class="val">{{ eur(totals.km_amount) }}</div>
        <div class="meta">{{ totals.km.toLocaleString('nl-NL') }} zakelijke km</div>
      </div>
      <div class="kpi" :class="totals.result >= 0 ? 'good' : 'alert'">
        <div class="lbl">Resultaat uit facturatie</div>
        <div class="val">{{ eur(totals.result) }}</div>
        <div class="meta">
          <template v-if="resultDelta !== null">{{ resultDelta >= 0 ? '+' : '' }}{{ resultDelta }}% t.o.v. {{ year - 1 }}</template>
          <template v-else>omzet − kosten − kilometers</template>
        </div>
      </div>
    </div>

    <!-- Eerlijk kader: wat dit wél en niet is -->
    <div class="yr-disclaimer">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div>
        <strong>Dit is de basis, nog geen complete fiscale winst-en-verliesrekening.</strong>
        Dit overzicht bevat alles wat je in {{ brand.name }} registreert: omzet (factuurdatum, excl. btw, creditnota's negatief),
        ingeboekte kosten en de kilometeraftrek (€ 0,23/km voor privévervoermiddelen). Wat je boekhouder nog toevoegt vóór de
        aangifte inkomstenbelasting: <b>afschrijvingen</b> (bijv. een bus of apparatuur), eventuele <b>loonkosten</b>,
        <b>bijtelling</b> bij een zakelijke auto, en de <b>ondernemersaftrekken</b> (zelfstandigenaftrek, startersaftrek,
        MKB-winstvrijstelling). Stuur de PDF mee — dan heeft je boekhouder alles uit je facturatie in één keer.
      </div>
    </div>

    <div class="yr-grid">
      <!-- Per kwartaal -->
      <div class="card">
        <div class="card-body">
          <div class="yr-title">Per kwartaal</div>
          <table class="data-table yr-table">
            <thead>
              <tr><th>Kwartaal</th><th class="right">Omzet</th><th class="right">Kosten</th><th class="right">Kilometers</th><th class="right">Resultaat</th></tr>
            </thead>
            <tbody>
              <tr v-for="q in quarters" :key="q.label">
                <td>{{ q.label }} {{ year }}</td>
                <td class="num right">{{ eur(q.revenue) }}</td>
                <td class="num right">{{ q.costs ? '− ' + eur(q.costs) : '—' }}</td>
                <td class="num right">{{ q.km_amount ? '− ' + eur(q.km_amount) : '—' }}</td>
                <td class="num right" :style="{ color: q.result < 0 ? 'var(--brand-dark)' : 'inherit', fontWeight: 600 }">{{ eur(q.result) }}</td>
              </tr>
              <tr class="yr-total">
                <td>Totaal {{ year }}</td>
                <td class="num right">{{ eur(totals.revenue) }}</td>
                <td class="num right">− {{ eur(totals.costs) }}</td>
                <td class="num right">− {{ eur(totals.km_amount) }}</td>
                <td class="num right" style="font-weight:700;">{{ eur(totals.result) }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="previous.revenue || previous.costs" class="yr-prev">
            Ter vergelijking {{ year - 1 }}: omzet {{ eur(previous.revenue) }}, kosten {{ eur(previous.costs) }},
            resultaat {{ eur(previous.result) }}.
          </div>
        </div>
      </div>

      <!-- Kosten per categorie -->
      <div class="card">
        <div class="card-body">
          <div class="yr-title">Kosten per categorie</div>
          <template v-if="categories.length">
            <div v-for="c in categories" :key="c.name" class="yr-cat">
              <div class="yr-cat-name">{{ c.name }} <span class="muted">· {{ c.count }}×</span></div>
              <div class="yr-cat-track"><div class="yr-cat-fill" :style="{ width: Math.max(c.amount / maxCategory * 100, 2) + '%' }"></div></div>
              <div class="yr-cat-amount num">{{ eur(c.amount) }}</div>
            </div>
          </template>
          <div v-else class="muted" style="font-size:13px;">Nog geen inkoopfacturen in {{ year }}.</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.kpi.good { background: var(--success-bg); border-color: var(--success-border); }
.kpi.good .val { color: var(--success); }
.kpi.alert { background: var(--brand-tint); border-color: var(--brand-border); }
.kpi.alert .val { color: var(--brand-darker); }
.kpi .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.kpi .val { font-family: var(--font-display); font-weight: 700; font-size: 22px; letter-spacing: -0.01em; }
.kpi .meta { font-size: 11px; color: var(--text-3); margin-top: 4px; }

.yr-select { font-size: 14px; font-weight: 600; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface); }

.yr-disclaimer {
  display: flex; gap: 12px; align-items: flex-start;
  background: #FEF9EC; border: 1px solid #FDE68A; border-radius: 12px;
  padding: 14px 18px; margin-bottom: 16px; font-size: 13px; line-height: 1.65; color: #713F12;
}
.yr-disclaimer svg { flex: none; margin-top: 2px; color: #B45309; }

.yr-grid { display: grid; grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); gap: 16px; align-items: start; }
.yr-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 14px; }
.yr-table .yr-total td { border-top: 2px solid var(--text); font-weight: 600; }
.yr-prev { margin-top: 12px; font-size: 12.5px; color: var(--text-3); }

.yr-cat { display: grid; grid-template-columns: 170px minmax(0, 1fr) 110px; gap: 12px; align-items: center; padding: 7px 0; }
.yr-cat-name { font-size: 13px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.yr-cat-track { height: 16px; background: var(--surface-2); border-radius: 5px; }
.yr-cat-fill { height: 100%; background: linear-gradient(90deg, var(--warning, #D97706), #B45309); border-radius: 5px; }
.yr-cat-amount { text-align: right; font-weight: 600; font-size: 13px; }

@media (max-width: 1000px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .yr-grid { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 500px) {
  .yr-cat { grid-template-columns: minmax(0, 1fr) 90px; }
  .yr-cat-track { display: none; }
}
</style>
