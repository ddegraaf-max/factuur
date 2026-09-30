<script setup>
/*
 * De grootboekkaart van één rekening.
 *
 * Met het beginsaldo erboven en een doorlopend saldo per regel. Zonder dat
 * beginsaldo is een grootboekkaart een lijstje mutaties waar je zelf mee moet
 * gaan rekenen om te zien of het klopt met de proefbalans — en dan klopt het
 * meestal ergens niet.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
  account: Object,
  opening: Number,
  closing: Number,
  rows: Array,
  filters: Object,
  years: Array,
  accounts: Array,
});

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const gekozen = ref(props.account.id);

const geld = (n) => new Intl.NumberFormat('nl-NL',
  { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);

const toon = () => router.get(route('ledger.card', gekozen.value),
  { from: from.value, to: to.value }, { preserveState: true, preserveScroll: true });

const jaar = (j) => router.get(route('ledger.card', props.account.id), { year: j }, { preserveState: true });
</script>

<template>
  <Head :title="account.number + ' ' + account.name" />
  <AppLayout>
    <template #breadcrumb>
      {{ $t('Grootboek') }} /
      <Link :href="route('ledger.accounts')">{{ $t('Rekeningschema') }}</Link> /
      <span class="breadcrumb-current">{{ account.number }}</span>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title"><span class="nr">{{ account.number }}</span> {{ account.name }}</h1>
        <p class="page-subtitle">
          {{ account.statement === 'balans' ? $t('Balansrekening') : $t('Resultaatrekening') }}
          <template v-if="account.rgs_code"> · RGS {{ account.rgs_code }}</template>
          <template v-if="account.statement === 'balans'">
            · {{ $t('het saldo loopt door over de jaren') }}
          </template>
          <template v-else>
            · {{ $t('het saldo begint elk boekjaar bij nul') }}
          </template>
        </p>
      </div>
      <div class="header-actions">
        <Link :href="route('ledger.trial', { year: filters.year })" class="btn btn-secondary btn-sm">{{ $t('Proefbalans') }}</Link>
      </div>
    </div>

    <div class="filter-bar">
      <select v-model="gekozen" @change="toon" class="rekeningkeuze">
        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.number }} — {{ a.name }}</option>
      </select>
      <span class="scheiding"></span>
      <button v-for="j in years" :key="j" class="filter-chip"
              :class="{ active: filters.year === j }" @click="jaar(j)">{{ j }}</button>
      <span class="scheiding"></span>
      <input type="date" v-model="from" @change="toon">
      <input type="date" v-model="to" @change="toon">
    </div>

    <div class="saldi">
      <div class="saldo">
        <span class="label">{{ $t('Beginsaldo') }}</span>
        <span class="bedrag">€ {{ geld(opening) }}</span>
      </div>
      <div class="saldo">
        <span class="label">{{ $t('Eindsaldo') }}</span>
        <span class="bedrag">€ {{ geld(closing) }}</span>
      </div>
      <div class="saldo">
        <span class="label">{{ $t('Aantal boekingen') }}</span>
        <span class="bedrag">{{ rows.length }}</span>
      </div>
    </div>

    <div class="card">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:100px;">{{ $t('Datum') }}</th>
            <th style="width:120px;">{{ $t('Boekstuk') }}</th>
            <th>{{ $t('Omschrijving') }}</th>
            <th style="width:160px;">{{ $t('Relatie') }}</th>
            <th style="width:120px;" class="right">{{ $t('Debet') }}</th>
            <th style="width:120px;" class="right">{{ $t('Credit') }}</th>
            <th style="width:130px;" class="right">{{ $t('Saldo') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr class="begin">
            <td></td><td></td>
            <td class="cell-primary">{{ $t('Beginsaldo') }}</td>
            <td></td><td></td><td></td>
            <td class="right num">{{ geld(opening) }}</td>
          </tr>
          <tr v-for="r in rows" :key="r.id">
            <td>{{ r.date }}</td>
            <td class="num">{{ r.entry_number }}</td>
            <td class="cell-primary">{{ r.description }}</td>
            <td>{{ r.relation }}</td>
            <td class="right num">{{ r.debit ? geld(r.debit) : '' }}</td>
            <td class="right num">{{ r.credit ? geld(r.credit) : '' }}</td>
            <td class="right num">{{ geld(r.running) }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="6">{{ $t('Eindsaldo') }}</td>
            <td class="right num">{{ geld(closing) }}</td>
          </tr>
        </tfoot>
      </table>

      <div v-if="!rows.length" class="card-empty">
        {{ $t('In deze periode is er op deze rekening niets geboekt.') }}
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.header-actions { display: flex; gap: 8px; }
.scheiding { width: 1px; align-self: stretch; background: var(--border); margin: 0 4px; }
.nr { font-family: var(--font-mono); color: var(--text-3); margin-right: 8px; }
.rekeningkeuze { max-width: 320px; }

.saldi { display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.saldo { background: var(--surface); border: 1px solid var(--border); border-radius: 10px;
  padding: 12px 16px; min-width: 150px; }
.saldo .label { display: block; font-size: 12px; color: var(--text-3); }
.saldo .bedrag { font-size: 20px; font-weight: 700; font-variant-numeric: tabular-nums; }

.begin td { background: var(--surface-2); color: var(--text-3); }
tfoot td { font-weight: 700; border-top: 2px solid var(--border); }
</style>
