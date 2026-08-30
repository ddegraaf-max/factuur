<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { t } from '@/i18n';
import { eur } from '@/format';

const props = defineProps({ blockers: Array, creditor: Object, earliest_date: String, collectable: Array, batches: Array, mandates: Number });

const selected = ref(props.collectable.map(i => i.id));
const toggle = (id) => { selected.value = selected.value.includes(id) ? selected.value.filter(x => x !== id) : [...selected.value, id]; };
const all = computed(() => selected.value.length === props.collectable.length && props.collectable.length > 0);
const toggleAll = () => { selected.value = all.value ? [] : props.collectable.map(i => i.id); };
const total = computed(() => props.collectable.filter(i => selected.value.includes(i.id)).reduce((s, i) => s + i.remaining, 0));

const form = useForm({ invoice_ids: [], collection_date: props.earliest_date });
const create = () => {
  form.invoice_ids = selected.value;
  form.post(route('direct-debit.store'), { preserveScroll: true, onSuccess: () => { selected.value = []; } });
};
const cancelBatch = (b) => {
  if (confirm(t('Batch :reference annuleren? Alleen doen als je het bestand niet bij de bank hebt ingediend.', { reference: b.reference }))) router.delete(route('direct-debit.destroy', b.id), { preserveScroll: true });
};
const open = ref(null);
</script>

<template>
  <Head :title="$t('Automatische incasso')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Verkoop') }} / <span class="breadcrumb-current">{{ $t('Automatische incasso') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Automatische incasso') }}</h1>
        <p class="page-subtitle">{{ $t('Open facturen van klanten met een machtiging bundel je in één incassobestand (SEPA pain.008) dat je uploadt bij je bank — Rabobank, ING, ABN AMRO, bunq, Knab, Triodos en SNS lezen het direct in.') }}</p>
      </div>
    </div>

    <div v-if="blockers.length" class="card" style="margin-bottom:16px;">
      <div class="card-body dd-blocked">
        <b>{{ $t('Nog even inrichten:') }}</b> {{ $t('vul :fields in bij', { fields: blockers.map(b => $t(b)).join(' ' + $t('en') + ' ') }) }}
        <Link :href="route('settings.company')" style="color:var(--brand);font-weight:600;">{{ $t('Bedrijfsgegevens') }}</Link>.
        {{ $t('Je Incassant-ID vraag je aan bij je bank (samen met een incassocontract); dat duurt meestal een paar werkdagen.') }}
      </div>
    </div>

    <div class="dd-grid">
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">{{ $t('Te incasseren') }}</div>
            <div class="card-subtitle">{{ collectable.length === 1 ? $t('1 open factuur') : $t(':n open facturen', { n: collectable.length }) }} {{ mandates === 1 ? $t('bij 1 klant met machtiging') : $t('bij :n klanten met machtiging', { n: mandates }) }}</div>
          </div>
        </div>
        <div class="card-body-flush" v-if="collectable.length">
          <table class="data-table">
            <thead><tr><th style="width:32px;"><input type="checkbox" :checked="all" @change="toggleAll"></th><th>{{ $t('Factuur') }}</th><th>{{ $t('Klant') }}</th><th>IBAN</th><th>{{ $t('Soort') }}</th><th>{{ $t('Vervalt') }}</th><th class="right">{{ $t('Bedrag') }}</th></tr></thead>
            <tbody>
              <tr v-for="i in collectable" :key="i.id" @click="toggle(i.id)" style="cursor:pointer;">
                <td><input type="checkbox" :checked="selected.includes(i.id)" @click.stop="toggle(i.id)"></td>
                <td><b>{{ i.number }}</b></td>
                <td>{{ i.customer }}</td>
                <td class="mono-sm">{{ i.iban }}</td>
                <td><span class="pill pill-draft">{{ i.scheme }} · {{ i.sequence === 'FRST' ? $t('eerste') : $t('vervolg') }}</span></td>
                <td class="muted-sm">{{ i.due_label }}</td>
                <td class="right num">{{ eur(i.remaining) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="card-empty" v-html="$t('Niets te incasseren. Zet bij een klant (Klanten → bewerken → <b>Automatische incasso</b>) het IBAN van de machtiging; open facturen van die klant verschijnen dan hier.')"></div>
        <div v-if="collectable.length" class="dd-footer">
          <div>
            <label class="dd-date">{{ $t('Incassodatum') }} <input type="date" v-model="form.collection_date" :min="earliest_date"></label>
            <div v-if="form.errors.collection_date" class="field-error">{{ form.errors.collection_date }}</div>
            <div class="muted-sm">{{ $t('Minimaal drie werkdagen vooruit; de bank verwerkt het bestand op die dag.') }}</div>
          </div>
          <div class="dd-total">
            <div class="muted-sm">{{ $t(':n geselecteerd', { n: selected.length }) }}</div>
            <div class="dd-sum">{{ eur(total) }}</div>
            <button class="btn btn-primary" :disabled="!selected.length || blockers.length || form.processing" @click="create">{{ form.processing ? $t('Bezig…') : $t('Batch aanmaken') }}</button>
          </div>
        </div>
      </div>

      <div>
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Incassobestanden') }}</div></div>
          <div class="card-body-flush" v-if="batches.length">
            <table class="data-table">
              <thead><tr><th>{{ $t('Batch') }}</th><th>{{ $t('Incassodatum') }}</th><th class="right">{{ $t('Facturen') }}</th><th class="right">{{ $t('Totaal') }}</th><th></th></tr></thead>
              <tbody>
                <template v-for="b in batches" :key="b.id">
                  <tr>
                    <td><b>{{ b.reference }}</b><div class="muted-sm">{{ b.created_label }}<span v-if="b.downloaded"> · {{ $t('gedownload') }}</span></div></td>
                    <td>{{ b.collection_label }}</td>
                    <td class="right num">{{ b.count }}</td>
                    <td class="right num">{{ eur(b.total) }}</td>
                    <td class="right dd-actions">
                      <a :href="route('direct-debit.download', b.id)" class="btn btn-secondary btn-sm">{{ $t('Download XML') }}</a>
                      <button type="button" class="link-btn" @click="open = open === b.id ? null : b.id">{{ open === b.id ? $t('verberg') : $t('details') }}</button>
                      <button type="button" class="link-btn danger" @click="cancelBatch(b)">{{ $t('annuleer') }}</button>
                    </td>
                  </tr>
                  <tr v-if="open === b.id"><td colspan="5" class="dd-lines">
                    <div v-for="l in b.lines" :key="l.invoice_id" class="dd-line"><span>{{ l.number }} · {{ l.customer_name }}</span><span class="mono-sm">{{ l.iban }}</span><span class="num">{{ eur(l.amount) }}</span></div>
                  </td></tr>
                </template>
              </tbody>
            </table>
          </div>
          <div v-else class="card-empty">{{ $t('Nog geen batches. Na het aanmaken download je hier het bestand voor je bank.') }}</div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-header"><div class="card-title">{{ $t('Zo werkt het') }}</div></div>
          <div class="card-body dd-help">
            <ol>
              <li v-html="$t('<b>Incassocontract</b> bij je bank afsluiten; je krijgt een Incassant-ID (bijv. NL12ZZZ123456780000). Zet dat bij Bedrijfsgegevens.')"></li>
              <li v-html="$t('<b>Machtiging</b> laten tekenen door je klant (doorlopende SEPA-machtiging) en het IBAN bij de klant invullen. Bewaar de getekende machtiging.')"></li>
              <li v-html="$t('<b>Batch aanmaken</b> van de open facturen en het XML-bestand uploaden in internetbankieren (Rabobank: Betalen → Bestand uploaden; ING: Zakelijk → Incasso-opdrachten; ABN: Batchverwerking).')"></li>
              <li v-html="$t('<b>Bijschrijving</b> koppel je daarna via Bank → Transacties aan de facturen, of boek je handmatig als betaald. Een CORE-incasso kan 8 weken worden gestorneerd; B2B niet.')"></li>
            </ol>
            <div class="muted-sm">{{ $t('Incassant:') }} {{ creditor.creditor_id || $t('— nog niet ingesteld') }} · IBAN {{ creditor.iban || '—' }}</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.dd-grid { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); gap: 20px; align-items: start; }
.dd-blocked { font-size: 13.5px; line-height: 1.6; background: var(--warning-bg); color: var(--warning); border-radius: var(--r); }
.dd-footer { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; padding: 14px 18px; border-top: 1px solid var(--border); flex-wrap: wrap; }
.dd-date { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; }
.dd-date input { max-width: 170px; }
.dd-total { text-align: right; }
.dd-sum { font-family: var(--font-display); font-weight: 700; font-size: 20px; margin: 2px 0 8px; }
.dd-actions { white-space: nowrap; }
.dd-actions .link-btn { margin-left: 8px; }
.link-btn { background: none; border: none; padding: 0; font-size: 12px; color: var(--brand); text-decoration: underline; cursor: pointer; }
.link-btn.danger { color: #B91C1C; }
.dd-lines { background: var(--surface-2); font-size: 12.5px; }
.dd-line { display: flex; justify-content: space-between; gap: 12px; padding: 3px 0; }
.dd-help { font-size: 13px; line-height: 1.65; color: var(--text-2); }
.dd-help ol { margin: 0 0 10px; padding-left: 18px; }
.dd-help li { margin-bottom: 6px; }
.mono-sm { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; }
.muted-sm { font-size: 12px; color: var(--text-4); }
@media (max-width: 1000px) { .dd-grid { grid-template-columns: minmax(0, 1fr); } }
</style>
