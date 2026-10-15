<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { ref } from 'vue';

const props = defineProps({
  month: String,        // 'JJJJ-MM'
  month_label: String,
  months: Array,        // [{ value, label }]
  enabled: Boolean,     // Mollie-koppeling aanwezig
  last_checked: String, // laatst bij Mollie gekeken
  groups: Array,        // per uitbetaling: { settlement_id, reference, status, settled_label, total, gross, difference, count, rows: [...] }
  totals: Object,       // { received, count, settled, unsettled, payouts }
});

const errors = usePage().props.errors || {};
const refreshing = ref(false);

const setMonth = (month) => router.get(route('mollie.index'), { month }, { preserveState: true });

const refresh = () => {
  refreshing.value = true;
  router.post(route('mollie.refresh'), {}, { preserveScroll: true, onFinish: () => { refreshing.value = false; } });
};

const statusLabel = (group) => {
  if (!group.settlement_id) return null;
  if (group.status === 'paidout') return { text: 'Uitbetaald', cls: 'ok' };
  if (group.status === 'failed') return { text: 'Mislukt', cls: 'bad' };
  return { text: 'Onderweg', cls: 'wait' };
};
</script>

<template>
  <Head :title="$t('Mollie-betalingen')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">{{ $t('Rapporten') }} / <span class="breadcrumb-current">{{ $t('Mollie-betalingen') }}</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Mollie-betalingen :month', { month: month_label }) }}</h1>
        <p class="page-subtitle">{{ $t('Welke facturen zijn online betaald, en in welke uitbetaling van Mollie zit dat geld. Voor de boekhouder: het bedrag op het bankafschrift, met de facturen erachter.') }}</p>
      </div>
      <div class="mo-actions">
        <select class="mo-select" :value="month" @change="setMonth($event.target.value)">
          <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
        <a :href="route('mollie.pdf', { month })" class="btn btn-secondary">{{ $t('Download PDF') }}</a>
        <a :href="route('mollie.csv', { month })" class="btn btn-ghost">CSV</a>
        <button v-if="enabled" type="button" class="btn btn-primary" :disabled="refreshing" @click="refresh">
          {{ refreshing ? $t('Bezig…') : $t('Ophalen bij Mollie') }}
        </button>
      </div>
    </div>

    <div v-if="errors.mollie" class="mo-error">{{ errors.mollie }}</div>

    <div v-if="!enabled" class="mo-note">
      {{ $t('Er is nog geen Mollie-koppeling. Vul je Mollie API-key in bij Instellingen → Bedrijfsgegevens; daarna krijgt elke factuur een betaallink en verschijnen de betalingen hier.') }}
      <Link :href="route('settings.company')" class="mo-link">{{ $t('Naar Bedrijfsgegevens') }} →</Link>
    </div>

    <div class="kpi-grid">
      <div class="kpi">
        <div class="lbl">{{ $t('Online ontvangen') }}</div>
        <div class="val">{{ eur(totals.received) }}</div>
        <div class="meta">{{ $t(':n betalingen', { n: totals.count }) }}</div>
      </div>
      <div class="kpi">
        <div class="lbl">{{ $t('In een uitbetaling') }}</div>
        <div class="val">{{ eur(totals.settled) }}</div>
        <div class="meta">{{ $t(':n uitbetalingen', { n: totals.payouts }) }}</div>
      </div>
      <div class="kpi">
        <div class="lbl">{{ $t('Nog niet uitbetaald') }}</div>
        <div class="val">{{ eur(totals.unsettled) }}</div>
        <div class="meta">{{ last_checked ? $t('Laatst gekeken bij Mollie: :time', { time: last_checked }) : $t('Nog niet bij Mollie gekeken') }}</div>
      </div>
    </div>

    <div v-if="groups.length === 0" class="card mo-empty">
      {{ $t('Geen online betalingen in deze maand.') }}
    </div>

    <div v-for="group in groups" :key="group.settlement_id || 'open'" class="card mo-group">
      <div class="mo-group-head">
        <div>
          <h2 class="mo-group-title">
            <template v-if="group.settlement_id">{{ $t('Uitbetaling :reference', { reference: group.reference || group.settlement_id }) }}</template>
            <template v-else>{{ $t('Nog niet uitbetaald') }}</template>
            <span v-if="statusLabel(group)" class="mo-pill" :class="statusLabel(group).cls">{{ $t(statusLabel(group).text) }}</span>
          </h2>
          <p class="mo-group-sub">
            <template v-if="group.settlement_id">
              <template v-if="group.settled_label">{{ $t('Overgemaakt op :date', { date: group.settled_label }) }}</template>
              <template v-else>{{ $t('Nog niet overgemaakt door Mollie') }}</template>
              <template v-if="group.total !== null"> · {{ $t('Bedrag op bankafschrift: :amount', { amount: eur(group.total) }) }}</template>
              · <span class="mo-mono">{{ group.settlement_id }}</span>
            </template>
            <template v-else>{{ $t('Binnen bij Mollie; het geld volgt in een volgende uitbetaling. Klik op "Ophalen bij Mollie" om te zien of er al een uitbetaling bekend is.') }}</template>
          </p>
        </div>
        <a v-if="group.settlement_id" :href="route('mollie.payout.pdf', group.settlement_id)" class="btn btn-ghost">{{ $t('PDF van deze uitbetaling') }}</a>
      </div>

      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th>{{ $t('Betaald op') }}</th>
              <th>{{ $t('Factuur') }}</th>
              <th>{{ $t('Klant') }}</th>
              <th>{{ $t('Methode') }}</th>
              <th>{{ $t('Mollie-betaling') }}</th>
              <th class="right">{{ $t('Bedrag') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in group.rows" :key="row.id">
              <td :data-label="$t('Betaald op')">{{ row.paid_label }}</td>
              <td :data-label="$t('Factuur')"><Link v-if="row.invoice_id" :href="route('invoices.show', row.invoice_id)" class="mo-link">{{ row.number }}</Link><span v-else>{{ row.number }}</span></td>
              <td :data-label="$t('Klant')">{{ row.customer }}</td>
              <td :data-label="$t('Methode')">{{ row.method }}</td>
              <td :data-label="$t('Mollie-betaling')" class="mo-mono mo-muted">{{ row.mollie_id }}</td>
              <td :data-label="$t('Bedrag')" class="right mo-num">{{ eur(row.amount) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="5">{{ $t('Door klanten betaald (:n)', { n: group.count }) }}</td>
              <td class="right mo-num"><b>{{ eur(group.gross) }}</b></td>
            </tr>
            <tr v-if="group.total !== null">
              <td colspan="5" class="mo-muted">
                <template v-if="group.difference < 0">{{ $t('Kosten Mollie (verschil met de uitbetaling)') }}</template>
                <template v-else-if="group.difference > 0">{{ $t('Andere ontvangsten in deze uitbetaling (niet via :brand)', { brand: $page.props.brand?.name || 'EasyInvoice' }) }}</template>
                <template v-else>{{ $t('Verschil met de uitbetaling') }}</template>
              </td>
              <td class="right mo-num mo-muted">{{ group.difference < 0 ? '-' : '' }}{{ eur(Math.abs(group.difference)) }}</td>
            </tr>
            <tr v-if="group.total !== null">
              <td colspan="5">{{ $t('Overgemaakt door Mollie') }}</td>
              <td class="right mo-num"><b>{{ eur(group.total) }}</b></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <p class="mo-foot">{{ $t('Mollie maakt betalingen gebundeld over, minus zijn kosten. Het bedrag op het bankafschrift is daardoor lager dan het totaal van de facturen; het verschil boekt de boekhouder als betaalkosten. Elke nacht wordt bij Mollie gekeken welke betalingen in een uitbetaling zitten.') }}</p>
  </AppLayout>
</template>

<style scoped>
.mo-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.mo-select { font-size: 14px; font-weight: 600; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text); }
.mo-error { background: #FEF2F2; border: 1px solid #FECACA; color: #9C1411; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 14px; }
.mo-note { background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; font-size: 14px; color: var(--text-2); }
.mo-link { color: var(--brand); font-weight: 600; }
.mo-empty { padding: 28px; text-align: center; color: var(--text-3); }
.mo-group { margin-top: 16px; padding: 0; overflow: hidden; }
.mo-group-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 18px 20px 10px; flex-wrap: wrap; }
.mo-group-title { font-size: 17px; margin: 0; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.mo-group-sub { margin: 4px 0 0; color: var(--text-3); font-size: 13.5px; }
.mo-pill { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 3px 8px; border-radius: 999px; }
.mo-pill.ok { background: #DCFCE7; color: #166534; }
.mo-pill.wait { background: #FEF3C7; color: #92400E; }
.mo-pill.bad { background: #FEE2E2; color: #991B1B; }
.mo-mono { font-family: 'JetBrains Mono', monospace; font-size: 12.5px; }
.mo-muted { color: var(--text-3); }
.mo-num { font-variant-numeric: tabular-nums; white-space: nowrap; }
.right { text-align: right; }
tfoot td { border-top: 1px solid var(--border); padding-top: 10px; }
.mo-foot { margin-top: 20px; color: var(--text-3); font-size: 13px; max-width: 820px; }
</style>
