<script setup>
/*
 * Het journaal: alle boekingen, en het formulier voor een handmatige.
 *
 * Het formulier rekent de twee kanten voor en zegt wat er nog ontbreekt, maar
 * het is niet de scheidsrechter — dat is de database. Sluit een boeking niet,
 * dan komt hij er niet in, ook niet als het scherm een keer iets anders denkt.
 */
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
  entries: Object,
  journals: Array,
  accounts: Array,
  filters: Object,
  years: Array,
});

const geld = (n) => new Intl.NumberFormat('nl-NL',
  { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);

const zoek = ref(props.filters.q || '');
const open = ref(null);
const nieuwOpen = ref(false);

const vandaag = new Date().toISOString().slice(0, 10);

const leegRegel = () => ({ account_id: '', debit: '', credit: '', description: '' });

const boeking = useForm({
  date: vandaag,
  journal: 'MEM',
  description: '',
  lines: [leegRegel(), leegRegel()],
});

const totaal = computed(() => boeking.lines.reduce((t, r) => ({
  debit: t.debit + (parseFloat(r.debit) || 0),
  credit: t.credit + (parseFloat(r.credit) || 0),
}), { debit: 0, credit: 0 }));

const verschil = computed(() => Math.round((totaal.value.debit - totaal.value.credit) * 100) / 100);

const klaar = computed(() =>
  verschil.value === 0
  && totaal.value.debit > 0
  && boeking.description.trim().length >= 2
  && boeking.lines.filter(r => r.account_id && ((parseFloat(r.debit) || 0) > 0 || (parseFloat(r.credit) || 0) > 0)).length >= 2
);

const voegRegelToe = () => boeking.lines.push(leegRegel());
const haalRegelWeg = (i) => { if (boeking.lines.length > 2) boeking.lines.splice(i, 1); };

/*
 * Het restant op de laatste regel zetten. Dit is de handeling die een
 * boekhouder honderd keer per dag doet: bedrag links, tegenrekening kiezen,
 * klaar. Zonder dit knopje moet iemand zelf gaan aftrekken, en dan staat er af
 * en toe een cent verkeerd.
 */
const vulAan = (i) => {
  const rest = verschil.value;
  if (rest === 0) return;
  if (rest > 0) {
    boeking.lines[i].credit = String(rest.toFixed(2));
    boeking.lines[i].debit = '';
  } else {
    boeking.lines[i].debit = String(Math.abs(rest).toFixed(2));
    boeking.lines[i].credit = '';
  }
};

const bewaar = () => boeking.post(route('ledger.entries.store'), {
  preserveScroll: true,
  onSuccess: () => {
    boeking.reset();
    boeking.date = vandaag;
    boeking.lines = [leegRegel(), leegRegel()];
    nieuwOpen.value = false;
  },
});

/*
 * Een boeking wordt niet gewist maar tegengeboekt. Een gat in de nummering is
 * precies waar een accountant naar gaat zoeken; een tegenboeking laat zien wát
 * er is teruggedraaid en wanneer. Dat staat ook in de vraag, zodat niemand
 * verrast is door de extra regel in het journaal.
 */
const terugdraaien = (entry) => {
  const vraag = `Boeking ${entry.number} terugdraaien? Er komt een tegenboeking bij; `
    + 'de oorspronkelijke boeking blijft staan.';
  if (!window.confirm(vraag)) return;
  useForm({ reason: '' }).post(route('ledger.entries.reverse', entry.id), { preserveScroll: true });
};

const filter = (extra) => router.get(route('ledger.entries'),
  { ...props.filters, ...extra }, { preserveState: true, preserveScroll: true });

const bron = {
  invoice: 'Verkoopfactuur', purchase_invoice: 'Inkoopfactuur', payment: 'Ontvangst',
  purchase_payment: 'Betaling inkoop', opening: 'Beginbalans', close: 'Jaarafsluiting',
  manual: 'Handmatig', reversal: 'Tegenboeking', bank_transaction: 'Bankmutatie',
};
</script>

<template>
  <Head :title="$t('Journaal')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Grootboek') }} / <span class="breadcrumb-current">{{ $t('Journaal') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Journaal') }}</h1>
        <p class="page-subtitle">{{ entries.total }} {{ $t('boekingen in deze periode.') }}</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary btn-sm" @click="nieuwOpen = !nieuwOpen">{{ $t('Handmatige boeking') }}</button>
        <Link :href="route('ledger.trial', { year: filters.year })" class="btn btn-secondary btn-sm">{{ $t('Proefbalans') }}</Link>
      </div>
    </div>

    <div v-if="nieuwOpen" class="card" style="margin-bottom:16px;">
      <div class="card-header"><div class="card-title">{{ $t('Handmatige boeking') }}</div></div>
      <div class="card-body">
        <p class="hint">
          {{ $t('Voor alles wat geen factuur is: een afschrijving, een privé-opname, een correctie. Debet moet gelijk zijn aan credit; anders neemt het grootboek de boeking niet aan.') }}
        </p>

        <div class="form-row">
          <div class="form-group">
            <label>{{ $t('Datum') }}</label>
            <input type="date" v-model="boeking.date">
          </div>
          <div class="form-group">
            <label>{{ $t('Dagboek') }}</label>
            <select v-model="boeking.journal">
              <option v-for="j in journals" :key="j.id" :value="j.code">{{ j.code }} — {{ j.name }}</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>{{ $t('Omschrijving') }}</label>
          <input type="text" v-model="boeking.description" :placeholder="$t('Bijvoorbeeld: afschrijving bestelauto 2026')">
        </div>

        <table class="data-table regels">
          <thead>
            <tr>
              <th>{{ $t('Rekening') }}</th>
              <th style="width:200px;">{{ $t('Omschrijving') }}</th>
              <th style="width:120px;" class="right">{{ $t('Debet') }}</th>
              <th style="width:120px;" class="right">{{ $t('Credit') }}</th>
              <th style="width:90px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in boeking.lines" :key="i">
              <td>
                <select v-model="r.account_id">
                  <option value="">{{ $t('— kies een rekening —') }}</option>
                  <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.number }} — {{ a.name }}</option>
                </select>
              </td>
              <td><input type="text" v-model="r.description"></td>
              <td><input type="number" step="0.01" min="0" class="bedrag" v-model="r.debit"
                         @input="r.debit && (r.credit = '')"></td>
              <td><input type="number" step="0.01" min="0" class="bedrag" v-model="r.credit"
                         @input="r.credit && (r.debit = '')"></td>
              <td class="right">
                <button v-if="verschil !== 0" class="btn-plain" @click="vulAan(i)">{{ $t('Rest') }}</button>
                <button v-if="boeking.lines.length > 2" class="btn-plain" @click="haalRegelWeg(i)">×</button>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2">
                <button class="btn-plain" @click="voegRegelToe">+ {{ $t('regel toevoegen') }}</button>
              </td>
              <td class="right num">{{ geld(totaal.debit) }}</td>
              <td class="right num">{{ geld(totaal.credit) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>

        <div class="stand" :class="verschil === 0 ? 'goed' : 'nog-niet'">
          <template v-if="verschil === 0 && totaal.debit > 0">{{ $t('In balans.') }}</template>
          <template v-else-if="verschil === 0">{{ $t('Vul de bedragen in.') }}</template>
          <template v-else>
            {{ $t('Nog niet in balans: er staat') }} € {{ geld(Math.abs(verschil)) }}
            {{ verschil > 0 ? $t('te veel debet') : $t('te veel credit') }}.
          </template>
        </div>

        <div v-if="boeking.errors.lines" class="fout">{{ boeking.errors.lines }}</div>

        <button class="btn btn-primary" :disabled="!klaar || boeking.processing" @click="bewaar">
          {{ boeking.processing ? $t('Bezig…') : $t('Boeking vastleggen') }}
        </button>
      </div>
    </div>

    <div class="filter-bar">
      <input type="search" class="filter-search" v-model="zoek"
             :placeholder="$t('Zoek op boekstuk of omschrijving')" @keyup.enter="filter({ q: zoek })">
      <span class="scheiding"></span>
      <button v-for="j in years" :key="j" class="filter-chip"
              :class="{ active: filters.year === j }" @click="filter({ year: j, from: null, to: null })">{{ j }}</button>
      <span class="scheiding"></span>
      <button class="filter-chip" :class="{ active: !filters.journal }" @click="filter({ journal: null })">{{ $t('Alle') }}</button>
      <button v-for="j in journals" :key="j.id" class="filter-chip"
              :class="{ active: filters.journal === j.code }" @click="filter({ journal: j.code })">{{ j.code }}</button>
    </div>

    <div class="card">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:100px;">{{ $t('Datum') }}</th>
            <th style="width:130px;">{{ $t('Boekstuk') }}</th>
            <th>{{ $t('Omschrijving') }}</th>
            <th style="width:140px;">{{ $t('Herkomst') }}</th>
            <th style="width:120px;" class="right">{{ $t('Bedrag') }}</th>
            <th style="width:120px;"></th>
          </tr>
        </thead>
        <tbody>
          <template v-for="e in entries.data" :key="e.id">
            <tr @click="open = open === e.id ? null : e.id">
              <td>{{ e.date }}</td>
              <td class="num">{{ e.number }}</td>
              <td class="cell-primary">{{ e.description }}</td>
              <td class="herkomst">{{ bron[e.source_type] || e.source_type || '—' }}</td>
              <td class="right num">{{ geld(e.total) }}</td>
              <td class="right">
                <button class="btn-plain">{{ open === e.id ? $t('Sluiten') : $t('Regels') }}</button>
                <button class="btn-plain" @click.stop="terugdraaien(e)">{{ $t('Terugdraaien') }}</button>
              </td>
            </tr>
            <tr v-if="open === e.id" class="regels-rij">
              <td colspan="6">
                <table class="data-table binnen">
                  <tbody>
                    <tr v-for="(l, i) in e.lines" :key="i">
                      <td style="width:120px;" class="num">{{ l.number }}</td>
                      <td>{{ l.name }}</td>
                      <td>{{ l.description }}</td>
                      <td style="width:120px;" class="right num">{{ l.debit ? geld(l.debit) : '' }}</td>
                      <td style="width:120px;" class="right num">{{ l.credit ? geld(l.credit) : '' }}</td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </template>
        </tbody>
      </table>

      <div v-if="!entries.data.length" class="card-empty">
        {{ $t('In deze periode is er nog niets geboekt.') }}
      </div>

      <div v-if="entries.links.length > 3" class="pagination">
        <Link v-for="(link, i) in entries.links" :key="i" :href="link.url || ''"
              class="page-link" :class="{ active: link.active, disabled: !link.url }"
              v-html="link.label" />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.header-actions { display: flex; gap: 8px; }
.scheiding { width: 1px; align-self: stretch; background: var(--border); margin: 0 4px; }
.hint { font-size: 13px; color: var(--text-3); margin: 0 0 14px; line-height: 1.55; max-width: 75ch; }

.regels tbody tr { cursor: default; }
.regels select, .regels input { width: 100%; }
.bedrag { text-align: right; font-variant-numeric: tabular-nums; }
tfoot td { font-weight: 700; border-top: 2px solid var(--border); }

.stand { margin: 12px 0; font-size: 13.5px; }
.stand.goed { color: var(--success, #1E8E5A); font-weight: 600; }
.stand.nog-niet { color: var(--text-3); }
.fout { color: var(--danger, #C0392B); font-size: 13px; margin-bottom: 12px; }

.herkomst { font-size: 12px; color: var(--text-3); }
.regels-rij td { background: var(--surface-2); padding: 0; }
.binnen { font-size: 12.5px; }
.binnen tbody tr { cursor: default; }
.binnen tbody tr:hover { background: transparent; }

.btn-plain {
  background: none; border: 0; padding: 2px 6px; font-size: 12px;
  color: var(--text-3); cursor: pointer; text-decoration: underline;
}
.btn-plain:hover { color: var(--brand); }
</style>
