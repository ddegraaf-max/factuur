<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { computed } from 'vue';

const props = defineProps({
  months: Array,       // [{ key, label, in_open, in_recurring, out_open, out_recurring, in, out, net, cumulative }]
  overdue: Object,     // { in, out } — al vervallen, direct opeisbaar
  later: Object,       // { in, out } — valt ná het venster
  totals: Object,      // { in, out, net }
  incasso_total: Number,
});

const maxFlow = computed(() => Math.max(
  ...props.months.map(m => Math.max(m.in, m.out)),
  props.overdue.in, props.overdue.out, 1,
));

const bar = (value) => Math.max(value / maxFlow.value * 100, value > 0 ? 2 : 0) + '%';
</script>

<template>
  <Head title="Cashflow" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Rapporten / <span class="breadcrumb-current">Cashflow</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Cashflow-prognose</h1>
        <p class="page-subtitle">Wat er de komende maanden naar verwachting binnenkomt en uitgaat — op basis van openstaande facturen, terugkerende facturen en vaste lasten.</p>
      </div>
    </div>

    <!-- KPI's -->
    <div class="kpi-grid">
      <div class="kpi">
        <div class="lbl">Te ontvangen</div>
        <div class="val">{{ eur(totals.in) }}</div>
        <div class="meta">waarvan {{ eur(overdue.in) }} al vervallen</div>
      </div>
      <div class="kpi">
        <div class="lbl">Te betalen</div>
        <div class="val">{{ eur(totals.out) }}</div>
        <div class="meta">waarvan {{ eur(overdue.out) }} al vervallen</div>
      </div>
      <div class="kpi" :class="totals.net >= 0 ? 'good' : 'alert'">
        <div class="lbl">Verwacht netto ({{ months.length }} mnd)</div>
        <div class="val">{{ eur(totals.net) }}</div>
        <div class="meta">ontvangsten − uitgaven</div>
      </div>
      <div class="kpi" :class="months.length && months[months.length - 1].cumulative < 0 ? 'alert' : ''">
        <div class="lbl">Verloop eind {{ months.length ? months[months.length - 1].label.split(' ')[0] : '' }}</div>
        <div class="val">{{ eur(months.length ? months[months.length - 1].cumulative : 0) }}</div>
        <div class="meta">cumulatief vanaf vandaag</div>
      </div>
    </div>

    <!-- Maandtabel met balkjes -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="cf-title">Per maand</div>
        <table class="data-table cf-table">
          <thead>
            <tr>
              <th>Periode</th>
              <th style="width:26%;"></th>
              <th class="right">Ontvangsten</th>
              <th class="right">Uitgaven</th>
              <th class="right">Netto</th>
              <th class="right">Cumulatief</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="overdue.in > 0 || overdue.out > 0" class="cf-overdue">
              <td><strong>Al vervallen</strong><div class="cf-sub">direct opeisbaar</div></td>
              <td>
                <div class="cf-track"><div class="cf-fill in" :style="{ width: bar(overdue.in) }"></div></div>
                <div class="cf-track"><div class="cf-fill out" :style="{ width: bar(overdue.out) }"></div></div>
              </td>
              <td class="num right">{{ eur(overdue.in) }}</td>
              <td class="num right">{{ overdue.out ? '− ' + eur(overdue.out) : '—' }}</td>
              <td class="num right" :class="overdue.in - overdue.out < 0 ? 'neg' : ''" style="font-weight:600;">{{ eur(overdue.in - overdue.out) }}</td>
              <td class="num right muted">—</td>
            </tr>
            <tr v-for="m in months" :key="m.key">
              <td>
                {{ m.label }}
                <div class="cf-sub">
                  <template v-if="m.in_recurring > 0.009">terugkerend {{ eur(m.in_recurring) }}</template>
                  <template v-if="m.in_recurring > 0.009 && m.out_recurring > 0.009"> · </template>
                  <template v-if="m.out_recurring > 0.009">vaste lasten {{ eur(m.out_recurring) }}</template>
                </div>
              </td>
              <td>
                <div class="cf-track"><div class="cf-fill in" :style="{ width: bar(m.in) }"></div></div>
                <div class="cf-track"><div class="cf-fill out" :style="{ width: bar(m.out) }"></div></div>
              </td>
              <td class="num right">{{ m.in ? eur(m.in) : '—' }}</td>
              <td class="num right">{{ m.out ? '− ' + eur(m.out) : '—' }}</td>
              <td class="num right" :class="m.net < 0 ? 'neg' : ''" style="font-weight:600;">{{ eur(m.net) }}</td>
              <td class="num right" :class="m.cumulative < 0 ? 'neg' : ''">{{ eur(m.cumulative) }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="later.in > 0.009 || later.out > 0.009" class="cf-later">
          Ná dit venster: nog {{ eur(later.in) }} te ontvangen en {{ eur(later.out) }} te betalen (vervaldatum verder weg).
        </div>
      </div>
    </div>

    <!-- Eerlijk kader -->
    <div class="cf-disclaimer">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div>
        <strong>Dit is een prognose, geen banksaldo.</strong>
        Ontvangsten staan op de vervaldatum van je facturen (terugkerende facturen: factuurdatum + betaaltermijn) en
        uitgaven op de vervaldatum van je inkoop en de boekingsdatum van je vaste lasten. In werkelijkheid betalen
        klanten soms later — de regel "al vervallen" laat zien wat er nu al opeisbaar is.
        <template v-if="incasso_total > 0.009">Facturen in het incassotraject ({{ eur(incasso_total) }}) tellen niet mee: die ontvangst is onzeker.</template>
        Privé-opnames, belastingen en loonkosten staan niet in EasyInvoice en zitten dus niet in dit beeld.
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

.cf-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 14px; }
.cf-sub { font-size: 11px; color: var(--text-3); margin-top: 2px; }
.cf-table td { vertical-align: middle; }
.cf-table .neg { color: var(--brand-dark); }
.cf-overdue td { background: var(--surface-2); }

.cf-track { height: 8px; background: var(--surface-2); border-radius: 4px; margin: 2px 0; }
.cf-fill { height: 100%; border-radius: 4px; }
.cf-fill.in { background: var(--success, #16A34A); }
.cf-fill.out { background: #D97706; }

.cf-later { margin-top: 12px; font-size: 12.5px; color: var(--text-3); }

.cf-disclaimer {
  display: flex; gap: 12px; align-items: flex-start;
  background: #FEF9EC; border: 1px solid #FDE68A; border-radius: 12px;
  padding: 14px 18px; margin-bottom: 16px; font-size: 13px; line-height: 1.65; color: #713F12;
}
.cf-disclaimer svg { flex: none; margin-top: 2px; color: #B45309; }

@media (max-width: 1000px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .cf-table th:nth-child(2), .cf-table td:nth-child(2) { display: none; }
}
</style>
