<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { computed } from 'vue';

const props = defineProps({
  rows: Array,          // per klant: { name, current, b30, b60, b90, b90plus, total, oldest_days, count, incasso }
  totals: Object,       // zelfde emmers, alles bij elkaar
  overdue_total: Number,
  oldest: Array,        // oudste vervallen facturen
});

const pct = computed(() => props.totals.total > 0
  ? Math.round(props.overdue_total / props.totals.total * 100)
  : 0);

const riskClass = (row) => {
  if (row.b90plus > 0.009) return 'risk-high';
  if (row.b60 > 0.009 || row.b90 > 0.009) return 'risk-mid';
  return '';
};
</script>

<template>
  <Head title="Debiteuren" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Rapporten / <span class="breadcrumb-current">Debiteuren</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Ouderdomsanalyse debiteuren</h1>
        <p class="page-subtitle">Wie staat er hoe lang open — zodat je precies weet waar je achteraan moet.</p>
      </div>
    </div>

    <!-- KPI's -->
    <div class="kpi-grid">
      <div class="kpi">
        <div class="lbl">Totaal openstaand</div>
        <div class="val">{{ eur(totals.total) }}</div>
        <div class="meta">{{ totals.count }} facturen</div>
      </div>
      <div class="kpi" :class="overdue_total > 0.009 ? 'alert' : 'good'">
        <div class="lbl">Waarvan vervallen</div>
        <div class="val">{{ eur(overdue_total) }}</div>
        <div class="meta">{{ pct }}% van het openstaande bedrag</div>
      </div>
      <div class="kpi" :class="totals.b90plus > 0.009 ? 'alert' : ''">
        <div class="lbl">Ouder dan 90 dagen</div>
        <div class="val">{{ eur(totals.b90plus) }}</div>
        <div class="meta">hoogste risico op wanbetaling</div>
      </div>
      <div class="kpi">
        <div class="lbl">Nog niet vervallen</div>
        <div class="val">{{ eur(totals.current) }}</div>
        <div class="meta">binnen de betaaltermijn</div>
      </div>
    </div>

    <!-- Per klant -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="ag-title">Per klant</div>
        <table v-if="rows.length" class="data-table ag-table">
          <thead>
            <tr>
              <th>Klant</th>
              <th class="right">Niet vervallen</th>
              <th class="right">1–30 dgn</th>
              <th class="right">31–60 dgn</th>
              <th class="right">61–90 dgn</th>
              <th class="right">90+ dgn</th>
              <th class="right">Totaal open</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.name" :class="riskClass(row)">
              <td>
                <Link v-if="row.customer_id" :href="route('customers.show', row.customer_id)" class="ag-name">{{ row.name }}</Link>
                <span v-else class="ag-name">{{ row.name }}</span>
                <div class="ag-sub">
                  {{ row.count }} {{ row.count === 1 ? 'factuur' : 'facturen' }}
                  <template v-if="row.oldest_days > 0"> · oudste {{ row.oldest_days }} dgn te laat</template>
                  <template v-if="row.incasso > 0"> · {{ row.incasso }} in incasso</template>
                </div>
              </td>
              <td class="num right">{{ row.current ? eur(row.current) : '—' }}</td>
              <td class="num right">{{ row.b30 ? eur(row.b30) : '—' }}</td>
              <td class="num right">{{ row.b60 ? eur(row.b60) : '—' }}</td>
              <td class="num right">{{ row.b90 ? eur(row.b90) : '—' }}</td>
              <td class="num right" :style="row.b90plus > 0.009 ? 'color:var(--brand-dark);font-weight:600;' : ''">{{ row.b90plus ? eur(row.b90plus) : '—' }}</td>
              <td class="num right" style="font-weight:600;">{{ eur(row.total) }}</td>
            </tr>
            <tr class="ag-total">
              <td>Totaal</td>
              <td class="num right">{{ eur(totals.current) }}</td>
              <td class="num right">{{ eur(totals.b30) }}</td>
              <td class="num right">{{ eur(totals.b60) }}</td>
              <td class="num right">{{ eur(totals.b90) }}</td>
              <td class="num right">{{ eur(totals.b90plus) }}</td>
              <td class="num right" style="font-weight:700;">{{ eur(totals.total) }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else class="muted" style="font-size:13px;">Er staat momenteel niets open — mooi zo.</div>
      </div>
    </div>

    <!-- Oudste vervallen facturen -->
    <div v-if="oldest.length" class="card">
      <div class="card-body">
        <div class="ag-title">Langst vervallen facturen</div>
        <table class="data-table">
          <thead>
            <tr><th>Factuur</th><th>Klant</th><th>Vervaldatum</th><th class="right">Dagen te laat</th><th class="right">Openstaand</th></tr>
          </thead>
          <tbody>
            <tr v-for="inv in oldest" :key="inv.id">
              <td><Link :href="route('invoices.show', inv.id)" class="ag-name">{{ inv.number }}</Link>
                <span v-if="inv.status === 'incasso'" class="pill pill-muted" style="margin-left:6px;">incasso</span>
              </td>
              <td>{{ inv.customer_name }}</td>
              <td>{{ inv.due_label }}</td>
              <td class="num right" :style="inv.days > 90 ? 'color:var(--brand-dark);font-weight:600;' : ''">{{ inv.days }}</td>
              <td class="num right" style="font-weight:600;">{{ eur(inv.open) }}</td>
            </tr>
          </tbody>
        </table>
        <div class="ag-hint">
          Tip: herinneringen en aanmaningen gaan automatisch (Instellingen → Herinneringen);
          voor hardnekkige gevallen is er het incassotraject.
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

.ag-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 14px; }
.ag-name { font-weight: 600; color: var(--text); }
.ag-name:hover { color: var(--brand); }
.ag-sub { font-size: 11.5px; color: var(--text-3); margin-top: 2px; }
.ag-table .ag-total td { border-top: 2px solid var(--text); font-weight: 600; }
.ag-table tr.risk-high td { background: var(--brand-tint); }
.ag-table tr.risk-mid td { background: #FEF9EC; }
.ag-hint { margin-top: 12px; font-size: 12.5px; color: var(--text-3); }
.pill-muted { background: var(--surface-2); color: var(--text-3); }

@media (max-width: 1000px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .ag-table th:nth-child(2), .ag-table td:nth-child(2) { display: none; }
}
</style>
