<script setup>
import { computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur, marketLocale } from '@/format';

const props = defineProps({
  year: Number,
  allYears: Array,
  list: Array,
  totals: Object,
});

const maxBar = computed(() => Math.max(1, ...props.list.map(c => Math.abs(c.ex_vat))));

const setYear = (y) => router.get(route('stats.index'), { year: y }, { preserveState: false, preserveScroll: true });
</script>

<template>
  <Head :title="$t('Klantomzet')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Rapporten') }} / <span class="breadcrumb-current">{{ $t('Klantomzet') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Klantomzet') }}</h1>
        <p class="page-subtitle">{{ $t('Omzet per klant per jaar · met bestede uren en het effectieve uurtarief') }}</p>
      </div>
      <div class="year-tabs">
        <div v-for="y in allYears" :key="y" class="tab" :class="{ active: year === y }" @click="setYear(y)">{{ y }}</div>
      </div>
    </div>

    <div v-if="list.length === 0" class="card empty">
      <div style="font-size:18px;font-weight:600;margin-bottom:6px;">{{ $t('Geen omzet in :year', { year }) }}</div>
      <div style="color:var(--text-3);">{{ $t('Er zijn nog geen verstuurde facturen in dit jaar.') }}</div>
    </div>

    <template v-else>
      <div class="kpi-grid">
        <div class="kpi"><div class="lbl">{{ $t('Aantal klanten') }}</div><div class="val">{{ totals.customers }}</div><div class="meta">{{ $t(':n facturen', { n: totals.invoice_count }) }}<span v-if="totals.credit_count">· {{ $t(":n creditnota's", { n: totals.credit_count }) }}</span></div></div>
        <div class="kpi"><div class="lbl">{{ $t('Omzet excl. BTW') }}</div><div class="val">{{ eur(totals.ex_vat) }}</div><div class="meta">{{ $t('Belastbaar bedrag') }}</div></div>
        <div class="kpi"><div class="lbl">{{ $t('BTW totaal') }}</div><div class="val">{{ eur(totals.vat) }}</div><div class="meta">{{ $t('Af te dragen') }}</div></div>
        <div class="kpi tint"><div class="lbl">{{ $t('Omzet incl. BTW') }}</div><div class="val brand">{{ eur(totals.inc_vat) }}</div><div class="meta">{{ $t('Totaal gefactureerd') }}</div></div>
        <div v-if="totals.hours > 0" class="kpi"><div class="lbl">{{ $t('Effectief uurtarief') }}</div><div class="val">{{ eur(totals.effective_rate) }}</div><div class="meta">{{ $t('omzet excl. BTW ÷ :hours geschreven uur', { hours: totals.hours.toLocaleString(marketLocale) }) }}</div></div>
      </div>

      <div v-if="list.length >= 2" class="card">
        <div class="card-header"><div class="card-title">{{ $t('Top :n klanten', { n: Math.min(list.length, 10) }) }} <span class="muted">{{ $t('naar omzet excl. BTW') }}</span></div></div>
        <div class="card-body">
          <div v-for="(c, i) in list.slice(0, 10)" :key="c.customer_id" class="bar-row">
            <div class="bar-label">
              <span class="rank" :class="['rank-' + (i+1)]">{{ i + 1 }}</span>
              {{ c.customer_name }}
            </div>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: Math.max(Math.abs(c.ex_vat) / maxBar * 100, 1) + '%' }"></div>
            </div>
            <div class="bar-amount">{{ eur(Math.abs(c.ex_vat)) }}</div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:14px;">
        <div class="card-header">
          <div>
            <div class="card-title">{{ $t('Volledig overzicht') }}</div>
            <div class="card-subtitle">{{ $t(':n klanten met activiteit in :year', { n: list.length, year }) }}</div>
          </div>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>{{ $t('Klant') }}</th>
              <th class="right">{{ $t('Facturen') }}</th>
              <th class="right">{{ $t('Uren') }}</th>
              <th class="right">{{ $t('Effectief/u') }}</th>
              <th class="right">{{ $t('Excl. BTW') }}</th>
              <th class="right">{{ $t('BTW') }}</th>
              <th class="right">{{ $t('Incl. BTW') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(c, i) in list" :key="c.customer_id">
              <td class="num rank-cell">#{{ i + 1 }}</td>
              <td class="cell-primary">{{ c.customer_name }}<span v-if="c.customer_city" class="city"> · {{ c.customer_city }}</span></td>
              <td class="right num" :data-label="$t('Facturen')">{{ c.invoice_count }}<span v-if="c.credit_count" class="muted-red">−{{ c.credit_count }}</span></td>
              <td class="right num" :data-label="$t('Uren')">
                <template v-if="c.hours > 0">{{ c.hours.toLocaleString(marketLocale) }}<span v-if="c.km > 0" class="city"> · {{ c.km.toLocaleString(marketLocale) }} km</span></template>
                <span v-else class="city">—</span>
              </td>
              <td class="right num" :data-label="$t('Effectief/u')">
                <template v-if="c.effective_rate !== null">{{ eur(c.effective_rate) }}</template>
                <span v-else class="city">—</span>
              </td>
              <td class="right num" :data-label="$t('Excl. BTW')" :class="{ neg: c.ex_vat < 0 }">{{ c.ex_vat < 0 ? '−' : '' }}{{ eur(Math.abs(c.ex_vat)) }}</td>
              <td class="right num" :data-label="$t('BTW')" :class="{ neg: c.vat < 0 }">{{ c.vat < 0 ? '−' : '' }}{{ eur(Math.abs(c.vat)) }}</td>
              <td class="right num bold" :data-label="$t('Incl. BTW')" :class="{ neg: c.inc_vat < 0 }">{{ c.inc_vat < 0 ? '−' : '' }}{{ eur(Math.abs(c.inc_vat)) }}</td>
            </tr>
            <tr class="total-row">
              <td class="rank-cell"></td>
              <td class="cell-primary">{{ $t('Totaal') }}</td>
              <td class="right num" :data-label="$t('Facturen')">{{ totals.invoice_count + totals.credit_count }}</td>
              <td class="right num" :data-label="$t('Uren')">{{ totals.hours > 0 ? totals.hours.toLocaleString(marketLocale) : '—' }}</td>
              <td class="right num" :data-label="$t('Effectief/u')">{{ totals.effective_rate !== null ? eur(totals.effective_rate) : '—' }}</td>
              <td class="right num" :data-label="$t('Excl. BTW')">{{ eur(totals.ex_vat) }}</td>
              <td class="right num" :data-label="$t('BTW')">{{ eur(totals.vat) }}</td>
              <td class="right num bold" :data-label="$t('Incl. BTW')">{{ eur(totals.inc_vat) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </AppLayout>
</template>

<style scoped>
.year-tabs { display: flex; gap: 4px; background: var(--surface); border: 1px solid var(--border); padding: 4px; border-radius: 10px; }
.tab { padding: 8px 16px; font-size: 13px; font-weight: 500; color: var(--text-3); border-radius: 7px; cursor: pointer; }
.tab:hover { color: var(--text); }
.tab.active { background: var(--text); color: white; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 20px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.kpi.tint { background: var(--brand-tint); border-color: var(--brand-border); }
.kpi .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.kpi .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; }
.kpi .val.brand { color: var(--brand-darker); }
.kpi .meta { font-size: 11px; color: var(--text-3); margin-top: 4px; }
.bar-row { display: grid; grid-template-columns: 180px 1fr 140px; gap: 14px; align-items: center; padding: 10px 0; }
.bar-label { font-size: 13px; font-weight: 500; }
.rank { display: inline-flex; width: 22px; height: 22px; background: var(--surface-2); color: var(--text-3); border-radius: 6px; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; margin-right: 8px; }
.rank-1 { background: linear-gradient(135deg, #FCD34D, #F59E0B); color: white; }
.rank-2 { background: linear-gradient(135deg, #D1D5DB, #9CA3AF); color: white; }
.rank-3 { background: linear-gradient(135deg, #F59E0B, #D97706); color: white; }
.bar-track { height: 26px; background: var(--surface-2); border-radius: 6px; }
.bar-fill { height: 100%; background: linear-gradient(90deg, var(--brand), var(--brand-dark)); border-radius: 6px; }
.bar-amount { text-align: right; font-family: var(--font-mono); font-weight: 600; font-size: 14px; }
.city { font-size: 11px; color: var(--text-3); }
.neg { color: var(--brand); }
.muted-red { color: var(--brand); font-size: 11px; margin-left: 4px; }
.total-row { background: var(--surface-2); font-weight: 600; }
.bold { font-weight: 600; }

@media (max-width: 760px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .kpi { padding: 14px; }
  .kpi .val { font-size: 18px; }
  /* Naam boven, balk + bedrag eronder: 180px + 140px past niet naast elkaar. */
  .bar-row {
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 6px 10px;
    padding: 12px 0;
  }
  .bar-label { grid-column: 1 / -1; }
  .bar-amount { font-size: 13px; white-space: nowrap; }
  .year-tabs { width: 100%; overflow-x: auto; }
  .tab { padding: 8px 12px; white-space: nowrap; }
}
@media (max-width: 400px) {
  .kpi-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
