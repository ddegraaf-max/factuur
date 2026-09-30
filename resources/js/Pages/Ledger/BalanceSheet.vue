<script setup>
/*
 * De balans en de winst-en-verliesrekening.
 *
 * Twee soorten tijd op één pagina, en dat is met opzet. De balans is een foto op
 * de peildatum: wat er ís. De winst-en-verliesrekening is de film van het
 * boekjaar: wat er gebeurde. Het resultaat uit die film staat als losse regel
 * bij het eigen vermogen op de foto — daar sluit de balans op.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
  assets: Array,
  liabilities: Array,
  income: Array,
  expenses: Array,
  result: Number,
  totals: Object,
  filters: Object,
  years: Array,
});

const geld = (n) => new Intl.NumberFormat('nl-NL',
  { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);

/** Regels onder hun rubriek, zodat de balans leest als een jaarrekening. */
const perGroep = (regels) => {
  const groepen = new Map();
  (regels || []).forEach(r => {
    if (!groepen.has(r.group)) groepen.set(r.group, { naam: r.group, regels: [], totaal: 0 });
    const g = groepen.get(r.group);
    g.regels.push(r);
    g.totaal += r.amount;
  });
  return [...groepen.values()];
};

const bezittingen = computed(() => perGroep(props.assets));
const schulden = computed(() => perGroep(props.liabilities));
const opbrengsten = computed(() => perGroep(props.income));
const kosten = computed(() => perGroep(props.expenses));

const totaalOpbrengsten = computed(() => (props.income || []).reduce((t, r) => t + r.amount, 0));
const totaalKosten = computed(() => (props.expenses || []).reduce((t, r) => t + r.amount, 0));

const jaar = (j) => router.get(route('ledger.sheet'), { year: j }, { preserveState: true });
</script>

<template>
  <Head :title="$t('Balans en resultaat')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Grootboek') }} / <span class="breadcrumb-current">{{ $t('Balans en resultaat') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Balans en resultaat') }} {{ filters.year }}</h1>
        <p class="page-subtitle">{{ $t('Stand op') }} {{ filters.to }}.</p>
      </div>
      <div class="header-actions">
        <Link :href="route('ledger.trial', { year: filters.year })" class="btn btn-secondary btn-sm">{{ $t('Proefbalans') }}</Link>
        <Link :href="route('ledger.years')" class="btn btn-secondary btn-sm">{{ $t('Boekjaren') }}</Link>
      </div>
    </div>

    <div class="filter-bar">
      <button v-for="j in years" :key="j" class="filter-chip"
              :class="{ active: filters.year === j }" @click="jaar(j)">{{ j }}</button>
    </div>

    <div v-if="!totals.balanced" class="card melding fout">
      <div class="card-body">
        <strong>{{ $t('De balans sluit niet.') }}</strong>
        {{ $t('Bezittingen') }} € {{ geld(totals.assets) }}, {{ $t('schulden en vermogen') }} € {{ geld(totals.liabilities) }}.
        {{ $t('Dat hoort niet te kunnen; kijk eerst in de proefbalans wat er scheef staat.') }}
      </div>
    </div>

    <h2 class="blok-kop">{{ $t('Balans') }}</h2>
    <div class="twee">
      <div class="card">
        <div class="card-header"><div class="card-title">{{ $t('Bezittingen') }}</div></div>
        <table class="data-table">
          <tbody>
            <template v-for="g in bezittingen" :key="'a'+g.naam">
              <tr class="groep"><td colspan="2">{{ g.naam }}</td></tr>
              <tr v-for="r in g.regels" :key="'a'+r.id+r.name">
                <td class="cell-primary">
                  <Link v-if="r.id" :href="route('ledger.card', { account: r.id, year: filters.year })">
                    <span class="nr">{{ r.number }}</span> {{ r.name }}
                  </Link>
                  <span v-else><span class="nr">{{ r.number }}</span> {{ r.name }}</span>
                </td>
                <td class="right num">{{ geld(r.amount) }}</td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr><td>{{ $t('Totaal bezittingen') }}</td><td class="right num">{{ geld(totals.assets) }}</td></tr>
          </tfoot>
        </table>
        <div v-if="!assets.length" class="card-empty">{{ $t('Nog niets geboekt.') }}</div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title">{{ $t('Schulden en eigen vermogen') }}</div></div>
        <table class="data-table">
          <tbody>
            <template v-for="g in schulden" :key="'p'+g.naam">
              <tr class="groep"><td colspan="2">{{ g.naam }}</td></tr>
              <tr v-for="r in g.regels" :key="'p'+r.id+r.name">
                <td class="cell-primary">
                  <Link v-if="r.id" :href="route('ledger.card', { account: r.id, year: filters.year })">
                    <span class="nr">{{ r.number }}</span> {{ r.name }}
                  </Link>
                  <span v-else><span class="nr">{{ r.number }}</span> {{ r.name }}</span>
                </td>
                <td class="right num">{{ geld(r.amount) }}</td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr><td>{{ $t('Totaal schulden en vermogen') }}</td><td class="right num">{{ geld(totals.liabilities) }}</td></tr>
          </tfoot>
        </table>
        <div v-if="!liabilities.length" class="card-empty">{{ $t('Nog niets geboekt.') }}</div>
      </div>
    </div>

    <h2 class="blok-kop">{{ $t('Winst- en verliesrekening') }} {{ filters.year }}</h2>
    <div class="twee">
      <div class="card">
        <div class="card-header"><div class="card-title">{{ $t('Opbrengsten') }}</div></div>
        <table class="data-table">
          <tbody>
            <template v-for="g in opbrengsten" :key="'i'+g.naam">
              <tr class="groep"><td colspan="2">{{ g.naam }}</td></tr>
              <tr v-for="r in g.regels" :key="'i'+r.id">
                <td class="cell-primary">
                  <Link :href="route('ledger.card', { account: r.id, year: filters.year })">
                    <span class="nr">{{ r.number }}</span> {{ r.name }}
                  </Link>
                </td>
                <td class="right num">{{ geld(r.amount) }}</td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr><td>{{ $t('Totaal opbrengsten') }}</td><td class="right num">{{ geld(totaalOpbrengsten) }}</td></tr>
          </tfoot>
        </table>
        <div v-if="!income.length" class="card-empty">{{ $t('Nog geen opbrengsten geboekt.') }}</div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title">{{ $t('Kosten') }}</div></div>
        <table class="data-table">
          <tbody>
            <template v-for="g in kosten" :key="'e'+g.naam">
              <tr class="groep"><td colspan="2">{{ g.naam }}</td></tr>
              <tr v-for="r in g.regels" :key="'e'+r.id">
                <td class="cell-primary">
                  <Link :href="route('ledger.card', { account: r.id, year: filters.year })">
                    <span class="nr">{{ r.number }}</span> {{ r.name }}
                  </Link>
                </td>
                <td class="right num">{{ geld(r.amount) }}</td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr><td>{{ $t('Totaal kosten') }}</td><td class="right num">{{ geld(totaalKosten) }}</td></tr>
          </tfoot>
        </table>
        <div v-if="!expenses.length" class="card-empty">{{ $t('Nog geen kosten geboekt.') }}</div>
      </div>
    </div>

    <div class="card uitkomst" :class="result >= 0 ? 'winst' : 'verlies'">
      <div class="card-body">
        <span class="label">{{ result >= 0 ? $t('Winst') : $t('Verlies') }} {{ filters.year }}</span>
        <span class="bedrag">€ {{ geld(Math.abs(result)) }}</span>
        <p class="toelichting">
          {{ $t('Dit bedrag staat ook op de balans, bij het eigen vermogen. Zolang het boekjaar niet is vastgesteld blijft het daar als aparte regel staan; bij het vaststellen gaat het naar het ondernemingsvermogen.') }}
        </p>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.header-actions { display: flex; gap: 8px; }
.blok-kop { font-size: 15px; font-weight: 700; margin: 24px 0 10px; }
.twee { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
@media (max-width: 900px) { .twee { grid-template-columns: 1fr; } }

.groep td { background: var(--surface-2); font-weight: 600; font-size: 12px;
  text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-3); }
.nr { font-family: var(--font-mono); font-size: 11.5px; color: var(--text-3); margin-right: 6px; }
tfoot td { font-weight: 700; border-top: 2px solid var(--border); }

.melding { margin-bottom: 16px; border-left: 3px solid var(--danger, #C0392B); background: var(--surface-2); }
.melding .card-body { font-size: 13.5px; line-height: 1.6; }

.uitkomst { margin-top: 16px; border-left: 3px solid var(--border); }
.uitkomst.winst { border-left-color: var(--success, #1E8E5A); }
.uitkomst.verlies { border-left-color: var(--danger, #C0392B); }
.uitkomst .label { font-size: 13px; color: var(--text-3); display: block; }
.uitkomst .bedrag { font-size: 26px; font-weight: 700; font-variant-numeric: tabular-nums; }
.uitkomst .toelichting { font-size: 12.5px; color: var(--text-3); margin: 8px 0 0; line-height: 1.6; max-width: 70ch; }
</style>
