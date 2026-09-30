<script setup>
/*
 * De boekjaren, en de beginbalans waarmee een overstapper begint.
 *
 * Vaststellen is niet zomaar een knop: daarna kan er in dat jaar niets meer
 * geboekt worden, ook niet door ons. Daarom staat er eerst of de stukken van dat
 * jaar sluiten, en kan de knop pas als dat zo is.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
  years: Array,
  opening: Object,
  accounts: Array,
  suggested: Array,
});

const geld = (n) => new Intl.NumberFormat('nl-NL',
  { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);

// ---- vaststellen en heropenen

const stelVast = (jaar) => {
  const vraag = `Boekjaar ${jaar} vaststellen?\n\n`
    + 'Daarna kan er in dit jaar niets meer worden geboekt — ook niet door ons. '
    + 'Het resultaat gaat naar het ondernemingsvermogen. '
    + 'Een correctie hoort daarna in het lopende jaar.';
  if (!window.confirm(vraag)) return;
  useForm({}).post(route('ledger.years.close', jaar), { preserveScroll: true });
};

const heropen = (jaar) => {
  const vraag = `Boekjaar ${jaar} weer openen?\n\n`
    + 'De afsluitboeking wordt verwijderd. Hebt u de jaarrekening of de aangifte '
    + 'al ingediend, dan wijken de cijfers daarna mogelijk af van wat u hebt opgestuurd.';
  if (!window.confirm(vraag)) return;
  useForm({}).post(route('ledger.years.reopen', jaar), { preserveScroll: true });
};

// ---- beginbalans

const openFormulier = ref(false);

/*
 * Het formulier begint met de rekeningen waar een beginbalans bijna altijd uit
 * bestaat: bank, kas, debiteuren, crediteuren, btw en het kapitaal. Iemand die
 * overstapt kan zijn oude balans dan regel voor regel overtypen zonder eerst
 * rekeningen te moeten opzoeken.
 */
const maakRegels = () => {
  if (props.opening.rows.length) {
    return props.opening.rows.map(r => ({
      account_id: r.account_id,
      debit: r.debit ? String(r.debit) : '',
      credit: r.credit ? String(r.credit) : '',
    }));
  }
  return props.suggested.map(a => ({ account_id: a.id, debit: '', credit: '' }));
};

const begin = useForm({
  year: props.opening.year,
  rows: maakRegels(),
});

const totaal = computed(() => begin.rows.reduce((t, r) => ({
  debit: t.debit + (parseFloat(r.debit) || 0),
  credit: t.credit + (parseFloat(r.credit) || 0),
}), { debit: 0, credit: 0 }));

const verschil = computed(() => Math.round((totaal.value.debit - totaal.value.credit) * 100) / 100);

const voegToe = () => begin.rows.push({ account_id: '', debit: '', credit: '' });
const haalWeg = (i) => begin.rows.splice(i, 1);

const bewaar = () => begin.post(route('ledger.opening.store'), { preserveScroll: true });
</script>

<template>
  <Head :title="$t('Boekjaren')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Grootboek') }} / <span class="breadcrumb-current">{{ $t('Boekjaren') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Boekjaren') }}</h1>
        <p class="page-subtitle">{{ $t('Een vastgesteld jaar zit dicht. Dat is het verschil tussen een jaarrekening en een momentopname.') }}</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary btn-sm" @click="openFormulier = !openFormulier">{{ $t('Beginbalans') }}</button>
        <Link :href="route('ledger.sheet')" class="btn btn-secondary btn-sm">{{ $t('Balans') }}</Link>
      </div>
    </div>

    <div class="card">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:80px;">{{ $t('Jaar') }}</th>
            <th style="width:110px;">{{ $t('Stand') }}</th>
            <th style="width:100px;" class="right">{{ $t('Boekingen') }}</th>
            <th style="width:130px;" class="right">{{ $t('Omgezet') }}</th>
            <th style="width:130px;" class="right">{{ $t('Resultaat') }}</th>
            <th>{{ $t('Controle') }}</th>
            <th style="width:150px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="j in years" :key="j.year">
            <td class="num jaar">{{ j.year }}</td>
            <td>
              <span class="merkje" :class="j.status === 'closed' ? 'dicht' : 'open'">
                {{ j.status === 'closed' ? $t('Vastgesteld') : $t('Open') }}
              </span>
            </td>
            <td class="right num">{{ j.entries }}</td>
            <td class="right num">{{ geld(j.turnover) }}</td>
            <td class="right num" :class="{ negatief: j.result < 0 }">{{ geld(j.result) }}</td>
            <td class="controle">
              <template v-if="j.trial_balanced && j.sheet_balanced">
                {{ $t('Proefbalans en balans sluiten.') }}
              </template>
              <template v-else>
                <span class="waarschuwing">
                  {{ !j.trial_balanced ? $t('De proefbalans sluit niet.') : $t('De balans sluit niet.') }}
                  {{ $t('Zoek dit eerst uit.') }}
                </span>
              </template>
            </td>
            <td class="right">
              <Link :href="route('ledger.sheet', { year: j.year })" class="btn-plain">{{ $t('Balans') }}</Link>
              <button v-if="j.status === 'open'" class="btn-plain"
                      :disabled="!j.trial_balanced || !j.sheet_balanced || j.entries === 0"
                      @click="stelVast(j.year)">{{ $t('Vaststellen') }}</button>
              <button v-else class="btn-plain" @click="heropen(j.year)">{{ $t('Heropenen') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="openFormulier" class="card" style="margin-top:16px;">
      <div class="card-header">
        <div class="card-title">{{ $t('Beginbalans') }} {{ begin.year }}</div>
      </div>
      <div class="card-body">
        <p class="hint">
          {{ $t('Komt u van een ander pakket, dan begint uw administratie hier niet bij nul. Neem de eindbalans van vorig jaar over: het banksaldo, wat klanten nog moeten betalen, wat u nog aan leveranciers moet, en de btw-stand. Sluit het niet helemaal, dan komt het verschil zichtbaar op een eigen regel bij het kapitaal te staan — dan weet u dat er nog iets uitgezocht moet worden.') }}
        </p>

        <p v-if="opening.number" class="bestaat">
          {{ $t('Er staat al een beginbalans') }} ({{ opening.number }}).
          {{ $t('Opnieuw opslaan vervangt die.') }}
        </p>

        <div class="form-group" style="max-width:200px;">
          <label>{{ $t('Boekjaar') }}</label>
          <input type="number" v-model="begin.year" min="2000" max="2100">
        </div>

        <table class="data-table regels">
          <thead>
            <tr>
              <th>{{ $t('Rekening') }}</th>
              <th style="width:130px;" class="right">{{ $t('Debet') }}</th>
              <th style="width:130px;" class="right">{{ $t('Credit') }}</th>
              <th style="width:50px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in begin.rows" :key="i">
              <td>
                <select v-model="r.account_id">
                  <option value="">{{ $t('— kies een rekening —') }}</option>
                  <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.number }} — {{ a.name }}</option>
                </select>
              </td>
              <td><input type="number" step="0.01" min="0" class="bedrag" v-model="r.debit"
                         @input="r.debit && (r.credit = '')"></td>
              <td><input type="number" step="0.01" min="0" class="bedrag" v-model="r.credit"
                         @input="r.credit && (r.debit = '')"></td>
              <td class="right"><button class="btn-plain" @click="haalWeg(i)">×</button></td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td><button class="btn-plain" @click="voegToe">+ {{ $t('regel toevoegen') }}</button></td>
              <td class="right num">{{ geld(totaal.debit) }}</td>
              <td class="right num">{{ geld(totaal.credit) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>

        <div class="stand" :class="verschil === 0 ? 'goed' : 'nog-niet'">
          <template v-if="verschil === 0 && totaal.debit > 0">{{ $t('De beginbalans sluit.') }}</template>
          <template v-else-if="verschil === 0">{{ $t('Vul de bedragen in.') }}</template>
          <template v-else>
            {{ $t('Er staat') }} € {{ geld(Math.abs(verschil)) }}
            {{ verschil > 0 ? $t('meer debet dan credit') : $t('meer credit dan debet') }}.
            {{ $t('Dat verschil komt op het kapitaal te staan, met de aantekening dat het nog uitgezocht moet worden.') }}
          </template>
        </div>

        <div v-if="begin.errors.rows" class="fout">{{ begin.errors.rows }}</div>

        <button class="btn btn-primary" :disabled="begin.processing || totaal.debit + totaal.credit === 0"
                @click="bewaar">
          {{ begin.processing ? $t('Bezig…') : $t('Beginbalans vastleggen') }}
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.header-actions { display: flex; gap: 8px; }
.hint { font-size: 13px; color: var(--text-3); margin: 0 0 14px; line-height: 1.6; max-width: 80ch; }
.bestaat { font-size: 13px; color: var(--text-2); background: var(--surface-2);
  padding: 8px 12px; border-radius: 8px; margin-bottom: 14px; }

.jaar { font-weight: 700; }
.merkje { display: inline-block; font-size: 11.5px; font-weight: 600; padding: 3px 9px;
  border-radius: 100px; border: 1px solid var(--border); }
.merkje.open { color: var(--text-2); }
.merkje.dicht { background: var(--surface-3, var(--surface-2)); color: var(--text-2); }

.controle { font-size: 12.5px; color: var(--text-3); }
.waarschuwing { color: var(--danger, #C0392B); font-weight: 600; }
.negatief { color: var(--danger, #C0392B); }

.regels tbody tr { cursor: default; }
.regels select, .regels input { width: 100%; }
.bedrag { text-align: right; font-variant-numeric: tabular-nums; }
tfoot td { font-weight: 700; border-top: 2px solid var(--border); }

.stand { margin: 12px 0; font-size: 13.5px; line-height: 1.6; max-width: 80ch; }
.stand.goed { color: var(--success, #1E8E5A); font-weight: 600; }
.stand.nog-niet { color: var(--text-3); }
.fout { color: var(--danger, #C0392B); font-size: 13px; margin-bottom: 12px; }

.btn-plain {
  background: none; border: 0; padding: 2px 6px; font-size: 12px;
  color: var(--text-3); cursor: pointer; text-decoration: underline;
}
.btn-plain:hover:not(:disabled) { color: var(--brand); }
.btn-plain:disabled { opacity: 0.4; cursor: not-allowed; text-decoration: none; }
</style>
