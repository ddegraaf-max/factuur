<script setup>
import { computed } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';

const props = defineProps({
  year: Number,
  allYears: Array,
  quarters: Array,
  totals: Object,
});

const setYear = (y) => router.get(route('vat.index'), { year: y }, { preserveState: false, preserveScroll: true });

// Bedrag met minteken vóór het euroteken (creditsaldo)
const amount = (n) => (n < 0 ? '− ' : '') + eur(Math.abs(n));

const statusMeta = {
  closed: { label: 'Afgerond', cls: 'closed' },
  current: { label: 'Loopt nu', cls: 'current' },
  future: { label: 'Nog niet begonnen', cls: 'future' },
};

const dueQuarter = computed(() => props.quarters.find(q => q.declaration_due));

const rateRows = [
  { key: '21', rubriek: '1a', label: 'Hoog tarief · 21%' },
  { key: '9', rubriek: '1b', label: 'Laag tarief · 9%' },
  { key: '0', rubriek: '1e', label: 'Nultarief · 0%' },
];
</script>

<template>
  <Head title="BTW-aangifte" />
  <AppLayout>
    <template #breadcrumb>Rapporten / <span class="breadcrumb-current">BTW-aangifte</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">BTW-aangifte</h1>
        <p class="page-subtitle">Omzetbelasting per kwartaal · grondslag en BTW per tarief (rubriek 1a, 1b en 1e)</p>
      </div>
      <div class="btw-header-actions">
        <div class="year-tabs">
          <div v-for="y in allYears" :key="y" class="tab" :class="{ active: year === y }" @click="setYear(y)">{{ y }}</div>
        </div>
        <a :href="route('vat.pdf', { year })" class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download PDF
        </a>
      </div>
    </div>

    <!-- Actie nodig: aangiftetermijn loopt -->
    <div v-if="dueQuarter" class="btw-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div>
        <strong>Aangifte {{ dueQuarter.label }} {{ year }} staat open.</strong>
        <template v-if="dueQuarter.balance >= 0">
          Dien de aangifte in en betaal per saldo <strong>{{ amount(dueQuarter.balance) }}</strong>
          uiterlijk <strong>{{ dueQuarter.deadline_label }}</strong> bij de Belastingdienst.
        </template>
        <template v-else>
          Dien de aangifte in vóór <strong>{{ dueQuarter.deadline_label }}</strong> —
          je krijgt per saldo <strong>{{ amount(dueQuarter.balance) }}</strong> terug.
        </template>
      </div>
    </div>

    <!-- Jaartotalen -->
    <div class="kpi-grid">
      <div class="kpi"><div class="lbl">Omzet excl. BTW · {{ year }}</div><div class="val">{{ amount(totals.base) }}</div><div class="meta">{{ totals.invoice_count }} facturen<span v-if="totals.credit_count"> · {{ totals.credit_count }} creditnota's</span></div></div>
      <div class="kpi"><div class="lbl">BTW over je omzet</div><div class="val">{{ amount(totals.vat) }}</div><div class="meta">rubriek 1a + 1b</div></div>
      <div class="kpi"><div class="lbl">Voorbelasting (5b)</div><div class="val">{{ amount(totals.input_vat) }}</div><div class="meta">uit {{ totals.purchase_count }} ingeboekte inkoopfacturen</div></div>
      <div class="kpi tint"><div class="lbl">{{ totals.balance < 0 ? 'Terug te ontvangen' : 'Per saldo te betalen' }} · {{ year }}</div><div class="val brand">{{ amount(totals.balance) }}</div><div class="meta">BTW omzet minus voorbelasting</div></div>
    </div>

    <div v-if="totals.invoice_count === 0 && totals.credit_count === 0" class="btw-empty-note">
      Nog geen verstuurde facturen in {{ year }} — de kwartalen hieronder staan op nul.
      Ook zonder omzet doe je overigens gewoon (nihil)aangifte.
    </div>

    <!-- Kwartalen -->
    <div class="btw-grid">
      <div
        v-for="q in quarters"
        :key="q.quarter"
        class="btw-card"
        :class="{ current: q.status === 'current', future: q.status === 'future', due: q.declaration_due }"
      >
        <div class="btw-card-head">
          <div>
            <div class="btw-card-title">{{ q.label }}</div>
            <div class="btw-card-months">{{ q.months }} {{ year }}</div>
          </div>
          <span v-if="q.declaration_due" class="btw-chip due">Aangifte doen</span>
          <span v-else class="btw-chip" :class="statusMeta[q.status].cls">{{ statusMeta[q.status].label }}</span>
        </div>

        <div class="btw-card-amount">
          <div class="btw-card-amount-label">{{ q.balance < 0 ? 'Terug te ontvangen' : 'Per saldo te betalen' }}</div>
          <div class="btw-card-amount-value" :class="{ neg: q.balance < 0 }">{{ amount(q.balance) }}</div>
        </div>

        <table class="btw-table">
          <thead>
            <tr>
              <th>Rubriek</th>
              <th class="right">Grondslag</th>
              <th class="right">BTW</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rateRows" :key="row.key" :class="{ dim: q.rates[row.key].base === 0 && q.rates[row.key].vat === 0 }">
              <td>
                <span class="btw-rubriek">{{ row.rubriek }}</span>
                {{ row.label }}
              </td>
              <td class="right num" :class="{ neg: q.rates[row.key].base < 0 }">{{ amount(q.rates[row.key].base) }}</td>
              <td class="right num" :class="{ neg: q.rates[row.key].vat < 0 }">
                <template v-if="row.key === '0'">—</template>
                <template v-else>{{ amount(q.rates[row.key].vat) }}</template>
              </td>
            </tr>
            <tr class="btw-subtotal-row">
              <td>BTW over omzet</td>
              <td class="right num" :class="{ neg: q.base < 0 }">{{ amount(q.base) }}</td>
              <td class="right num" :class="{ neg: q.vat < 0 }">{{ amount(q.vat) }}</td>
            </tr>
            <tr>
              <td><span class="btw-rubriek">5b</span> Voorbelasting (inkoop)</td>
              <td class="right num muted-cell">{{ q.purchase_count }} {{ q.purchase_count === 1 ? 'factuur' : 'facturen' }}</td>
              <td class="right num vat-in">− {{ amount(q.input_vat) }}</td>
            </tr>
            <tr class="btw-total-row">
              <td>{{ q.balance < 0 ? 'Terug te ontvangen' : 'Per saldo te betalen' }}</td>
              <td></td>
              <td class="right num" :class="{ neg: q.balance < 0 }">{{ amount(q.balance) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="btw-card-foot">
          <span>
            {{ q.invoice_count }} {{ q.invoice_count === 1 ? 'verkoopfactuur' : 'verkoopfacturen' }}<span v-if="q.credit_count"> · {{ q.credit_count }} creditnota's</span>
          </span>
          <span v-if="q.status !== 'future'" class="btw-deadline" :class="{ urgent: q.declaration_due }">
            Aangifte vóór {{ q.deadline_label }}
          </span>
        </div>
      </div>
    </div>

    <p class="btw-disclaimer">
      Berekend op factuurdatum (factuurstelsel) over alle verstuurde facturen en creditnota's.
      De voorbelasting (rubriek 5b) komt uit je
      <Link :href="route('purchases.index')" style="color:var(--brand);font-weight:500;">ingeboekte inkoopfacturen</Link>
      — dat cijfer is dus zo volledig als je inboekt. Controleer de cijfers altijd samen met je boekhouder.
    </p>
  </AppLayout>
</template>

<style scoped>
.btw-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.year-tabs { display: flex; gap: 4px; background: var(--surface); border: 1px solid var(--border); padding: 4px; border-radius: 10px; }
.tab { padding: 8px 16px; font-size: 13px; font-weight: 500; color: var(--text-3); border-radius: 7px; cursor: pointer; }
.tab:hover { color: var(--text); }
.tab.active { background: var(--text); color: white; }

.btw-alert {
  display: flex; align-items: flex-start; gap: 12px;
  background: var(--warning-bg); border: 1px solid var(--warning-border); color: var(--warning);
  border-radius: 10px; padding: 14px 16px; margin-bottom: 18px;
  font-size: 13.5px; line-height: 1.6;
}
.btw-alert svg { width: 19px; height: 19px; flex: none; margin-top: 2px; }
.btw-alert strong { font-weight: 700; }

.kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.kpi.tint { background: var(--brand-tint); border-color: var(--brand-border); }
.kpi .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.kpi .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; }
.kpi .val.brand { color: var(--brand-darker); }
.kpi .meta { font-size: 11px; color: var(--text-3); margin-top: 4px; }

.btw-empty-note {
  background: var(--surface-2); border: 1px dashed var(--border-strong); border-radius: 10px;
  padding: 12px 16px; margin-bottom: 18px; font-size: 13px; color: var(--text-3); line-height: 1.6;
}

.btw-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.btw-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: 20px 22px;
}
.btw-card.current { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-tint); }
.btw-card.due { border-color: var(--warning-border); }
.btw-card.future { opacity: 0.65; }

.btw-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
.btw-card-title { font-family: var(--font-display); font-weight: 700; font-size: 17px; letter-spacing: -0.01em; }
.btw-card-months { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }

.btw-chip {
  font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 100px;
  border: 1px solid var(--border-strong); background: var(--surface-2); color: var(--text-2);
  white-space: nowrap; flex: none;
}
.btw-chip.current { background: var(--info-bg); border-color: var(--info-border); color: var(--info); }
.btw-chip.closed { background: var(--success-bg); border-color: var(--success-border); color: var(--success); }
.btw-chip.due { background: var(--warning-bg); border-color: var(--warning-border); color: var(--warning); }

.btw-card-amount { margin-bottom: 14px; }
.btw-card-amount-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; }
.btw-card-amount-value { font-family: var(--font-display); font-weight: 700; font-size: 26px; letter-spacing: -0.02em; margin-top: 2px; }

.btw-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.btw-table th {
  text-align: left; padding: 7px 8px; font-size: 10.5px; font-weight: 600;
  text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3);
  border-bottom: 1px solid var(--border); background: var(--surface-2);
}
.btw-table th:first-child { border-radius: 6px 0 0 6px; }
.btw-table th:last-child { border-radius: 0 6px 6px 0; }
.btw-table td { padding: 9px 8px; border-bottom: 1px solid var(--border); }
.btw-table .right { text-align: right; }
.btw-table .num { font-family: var(--font-mono); }
.btw-table tr.dim td { color: var(--text-4); }
.btw-rubriek {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 26px; padding: 1px 5px; margin-right: 7px;
  background: var(--surface-3); border-radius: 5px;
  font-size: 10.5px; font-weight: 700; color: var(--text-2);
}
.btw-subtotal-row td { font-weight: 600; }
.muted-cell { color: var(--text-4); font-size: 11.5px; font-family: var(--font-body); }
.vat-in { color: var(--success); font-weight: 600; }
.btw-total-row td { font-weight: 700; border-bottom: none; padding-top: 11px; }

.btw-card-foot {
  display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
  margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border);
  font-size: 12px; color: var(--text-3);
}
.btw-deadline.urgent { color: var(--warning); font-weight: 600; }

.neg { color: var(--brand); }

.btw-disclaimer {
  margin-top: 18px;
  font-size: 12px;
  color: var(--text-4);
  line-height: 1.6;
  max-width: 720px;
}

@media (max-width: 860px) {
  .btw-grid { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 760px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .kpi { padding: 14px; }
  .kpi .val { font-size: 18px; }
  .year-tabs { overflow-x: auto; }
  .tab { padding: 8px 12px; white-space: nowrap; }
}
@media (max-width: 400px) {
  .kpi-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
