<script setup>
/*
 * De proefbalans.
 *
 * Het stuk waar een boekhouder als eerste naar kijkt: telt links op tot
 * hetzelfde als rechts? Zo ja, dan is er in elk geval niets half geboekt. Zo
 * nee, dan is er buiten het grootboek om iets gewijzigd — en dan zegt dit
 * scherm dat, met het verschil erbij, in plaats van de regel weg te laten.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
  rows: Array,
  totals: Object,
  filters: Object,
  years: Array,
});

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const soort = ref('alles');

const geld = (n) => (n === null || n === undefined ? '' :
  new Intl.NumberFormat('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n));

const zichtbaar = computed(() => {
  if (soort.value === 'alles') return props.rows;
  return props.rows.filter(r => r.statement === soort.value);
});

const subtotaal = computed(() => zichtbaar.value.reduce((t, r) => ({
  debit: t.debit + r.debit,
  credit: t.credit + r.credit,
}), { debit: 0, credit: 0 }));

const toon = () => router.get(route('ledger.trial'), { from: from.value, to: to.value },
  { preserveState: true, preserveScroll: true });

const jaar = (j) => router.get(route('ledger.trial'), { year: j }, { preserveState: true });

const kwartaal = (k) => {
  const j = props.filters.year;
  from.value = `${j}-${String(k * 3 - 2).padStart(2, '0')}-01`;
  to.value = new Date(j, k * 3, 0).toISOString().slice(0, 10);
  toon();
};
</script>

<template>
  <Head :title="$t('Proefbalans')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Grootboek') }} / <span class="breadcrumb-current">{{ $t('Proefbalans') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Proefbalans') }}</h1>
        <p class="page-subtitle">{{ $t('Debet en credit per rekening over de gekozen periode.') }}</p>
      </div>
      <div class="header-actions">
        <Link :href="route('ledger.sheet', { year: filters.year })" class="btn btn-secondary btn-sm">{{ $t('Balans') }}</Link>
        <Link :href="route('ledger.entries', { year: filters.year })" class="btn btn-secondary btn-sm">{{ $t('Journaal') }}</Link>
      </div>
    </div>

    <!--
      De uitkomst staat bovenaan en niet onderaan. Wie hier komt heeft één vraag,
      en die hoort niet onder een lijst van honderd regels te staan.
    -->
    <div class="card melding" :class="totals.balanced ? 'goed' : 'fout'">
      <div class="card-body">
        <template v-if="totals.balanced">
          <strong>{{ $t('De proefbalans sluit.') }}</strong>
          {{ $t('Debet en credit zijn samen') }} € {{ geld(totals.debit) }}.
        </template>
        <template v-else>
          <strong>{{ $t('De proefbalans sluit niet.') }}</strong>
          {{ $t('Debet') }} € {{ geld(totals.debit) }}, {{ $t('credit') }} € {{ geld(totals.credit) }} —
          {{ $t('een verschil van') }} € {{ geld(Math.abs(totals.difference)) }}.
          {{ $t('Dat hoort niet te kunnen. Neem contact op voordat u iets vaststelt of aangifte doet.') }}
        </template>
      </div>
    </div>

    <div class="filter-bar">
      <button v-for="j in years" :key="j" class="filter-chip"
              :class="{ active: filters.year === j }" @click="jaar(j)">{{ j }}</button>
      <span class="scheiding"></span>
      <button v-for="k in [1,2,3,4]" :key="'k'+k" class="filter-chip" @click="kwartaal(k)">
        {{ $t('K') }}{{ k }}
      </button>
      <span class="scheiding"></span>
      <input type="date" v-model="from" @change="toon">
      <input type="date" v-model="to" @change="toon">
      <span class="scheiding"></span>
      <button class="filter-chip" :class="{ active: soort === 'alles' }" @click="soort = 'alles'">{{ $t('Alles') }}</button>
      <button class="filter-chip" :class="{ active: soort === 'balans' }" @click="soort = 'balans'">{{ $t('Balans') }}</button>
      <button class="filter-chip" :class="{ active: soort === 'resultaat' }" @click="soort = 'resultaat'">{{ $t('Resultaat') }}</button>
    </div>

    <div class="card">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:120px;">{{ $t('Nummer') }}</th>
            <th>{{ $t('Rekening') }}</th>
            <th style="width:110px;">{{ $t('RGS') }}</th>
            <th style="width:130px;" class="right">{{ $t('Debet') }}</th>
            <th style="width:130px;" class="right">{{ $t('Credit') }}</th>
            <th style="width:130px;" class="right">{{ $t('Saldo') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in zichtbaar" :key="r.id" @click="router.get(route('ledger.card', { account: r.id, from: filters.from, to: filters.to }))">
            <td class="num">{{ r.number }}</td>
            <td class="cell-primary">{{ r.name }}</td>
            <td class="rgs">{{ r.rgs_code }}</td>
            <td class="right num">{{ r.debit ? geld(r.debit) : '' }}</td>
            <td class="right num">{{ r.credit ? geld(r.credit) : '' }}</td>
            <td class="right num" :class="{ negatief: r.balance < 0 }">{{ geld(r.balance) }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3">{{ soort === 'alles' ? $t('Totaal') : $t('Subtotaal') }}</td>
            <td class="right num">{{ geld(subtotaal.debit) }}</td>
            <td class="right num">{{ geld(subtotaal.credit) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>

      <div v-if="!zichtbaar.length" class="card-empty">
        {{ $t('In deze periode is er nog niets geboekt.') }}
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.header-actions { display: flex; gap: 8px; }
.scheiding { width: 1px; align-self: stretch; background: var(--border); margin: 0 4px; }
.rgs { font-family: var(--font-mono); font-size: 11.5px; color: var(--text-3); }
.negatief { color: var(--danger, #C0392B); }

.melding { margin-bottom: 16px; border-left: 3px solid var(--border); }
.melding .card-body { font-size: 13.5px; line-height: 1.6; }
.melding.goed { border-left-color: var(--success, #1E8E5A); }
.melding.fout { border-left-color: var(--danger, #C0392B); background: var(--surface-2); }

tfoot td { font-weight: 700; border-top: 2px solid var(--border-strong, var(--border)); }
</style>
