<script setup>
import { computed, ref } from 'vue';
import { router, useForm, Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { t } from '@/i18n';
import { eur } from '@/format.js';

const brand = usePage().props.brand;

const props = defineProps({
  year: Number,
  allYears: Array,
  period_type: String,
  periods: Array,
  totals: Object,
  settings: Object,
  mbz_url: String,
});

const setYear = (y) => router.get(route('vat.index'), { year: y }, { preserveState: false, preserveScroll: true });

// Bedragen: met minteken vóór het valutateken; 'whole' = hele euro's zoals op het aangifteformulier.
const amount = (n) => (n < 0 ? '− ' : '') + eur(Math.abs(n || 0));
const whole = (n) => (n < 0 ? '− ' : '') + eur(Math.abs(Math.round(n || 0)), { decimals: 0 });

const typeLabel = { quarter: t('kwartaal'), month: t('maand'), year: t('jaar') }[props.period_type] || t('kwartaal');
const gridClass = computed(() => ({ quarter: 'cols-2', month: 'cols-3', year: 'cols-1' }[props.period_type] || 'cols-2'));
const duePeriod = computed(() => props.periods.find((p) => p.declaration_due));
const rub = (p, key) => p.rubrieken.find((r) => r.key === key) || { base: 0, vat: 0 };

const shortLabels = {
  '1a': t('Hoog tarief · 21%'), '1b': t('Laag tarief · 9%'), '1c': t('Overige tarieven'), '1d': t('Privégebruik'),
  '1e': t('Nultarief / niet belast'), '2a': t('Btw naar u verlegd'), '3a': t('Uitvoer buiten de EU'),
  '3b': t('Binnen de EU (ICP)'), '3c': t('Afstandsverkopen EU'), '4a': t('Inkoop buiten de EU'), '4b': t('Inkoop binnen de EU'),
};
const isEmpty = (r) => (r.base === null || Math.abs(r.base) < 0.005) && Math.abs(r.vat) < 0.005;
// Op de kaart: 1a/1b/1e altijd, andere rubrieken alleen als er iets in staat.
const cardRows = (p) => p.rubrieken.filter((r) => ['1a', '1b', '1e'].includes(r.key) || (['auto', 'manual'].includes(r.source) && !isEmpty(r)));

const chip = (p) => {
  if (p.paid) return { label: t('Betaald'), cls: 'paid' };
  if (p.filed) return { label: t('Aangegeven'), cls: 'filed' };
  if (p.declaration_due) return { label: p.days_left <= 7 ? (p.days_left === 1 ? t('Nog 1 dag') : t('Nog :n dagen', { n: p.days_left })) : t('Aangifte doen'), cls: 'due' };
  if (p.status === 'current') return { label: t('Loopt nu'), cls: 'current' };
  if (p.status === 'future') return { label: t('Nog niet begonnen'), cls: 'future' };
  return { label: t('Niet gemarkeerd'), cls: 'unmarked' };
};

/* ---------- Aangifte-klaar (detail per tijdvak) ---------- */
const openKey = ref(null);
const detail = computed(() => props.periods.find((p) => p.key === openKey.value) || null);
const showCents = ref(false);
const showInvoices = ref(false);
const showPurchases = ref(false);
const manualForm = ref({});
const manualSaving = ref(false);

const filingRoute = (p) => route('vat.filing.update', { year: p.year, type: p.type, period: p.period });
const cloneManual = (p) => JSON.parse(JSON.stringify(p.manual || {}));

const open = (p) => {
  openKey.value = p.key;
  showInvoices.value = false;
  showPurchases.value = false;
  manualForm.value = cloneManual(p);
  refForm.reset();
  refForm.clearErrors();
};
const close = () => { openKey.value = null; };

const val = (r, which) => (showCents.value ? r[which] : r[which + '_rounded']);
const fmt = (v) => (v === null || v === undefined ? '—' : (showCents.value ? amount(v) : whole(v)));

// Kopiëren naar het klembord: hele euro's, precies zoals je ze invult.
const copied = ref(null);
const copy = async (text, id) => {
  try {
    await navigator.clipboard.writeText(String(text));
    copied.value = id;
    setTimeout(() => { if (copied.value === id) copied.value = null; }, 1500);
  } catch (e) { /* geen klembord (bijv. onbeveiligde context) — dan gewoon overtypen */ }
};
const copyValue = (r, which) => copy(Math.round(r[which + '_rounded'] ?? 0), `${r.key}-${which}`);

const manualDirty = computed(() => detail.value && JSON.stringify(manualForm.value) !== JSON.stringify(detail.value.manual));
const saveManual = () => {
  if (!detail.value) return;
  manualSaving.value = true;
  router.patch(filingRoute(detail.value), { manual: manualForm.value }, {
    preserveScroll: true, preserveState: true,
    onFinish: () => { manualSaving.value = false; },
  });
};

const toggle = (p, field, value) => router.patch(filingRoute(p), { [field]: value }, { preserveScroll: true, preserveState: true });

const refForm = useForm({ payment_reference: '' });
const saveReference = () => {
  if (!detail.value) return;
  refForm.patch(filingRoute(detail.value), { preserveScroll: true, preserveState: true, onSuccess: () => refForm.reset() });
};

/* ---------- Instellingen ---------- */
const showSettings = ref(false);
const settingsForm = useForm({
  vat_period: props.settings.vat_period,
  ob_number: '',
  ob_number_clear: false,
  vat_reminder_enabled: !!props.settings.vat_reminder_enabled,
});
const saveSettings = () => settingsForm.patch(route('vat.settings'), {
  preserveScroll: true, preserveState: false,
  onSuccess: () => { showSettings.value = false; },
});
</script>

<template>
  <Head :title="$t('Btw-aangifte')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Rapporten') }} / <span class="breadcrumb-current">{{ $t('Btw-aangifte') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Btw-aangifte') }}</h1>
        <p class="page-subtitle">{{ $t('Aangifte omzetbelasting per :type · alle rubrieken klaar om over te nemen in Mijn Belastingdienst Zakelijk', { type: typeLabel }) }}</p>
      </div>
      <div class="btw-header-actions">
        <div class="year-tabs">
          <div v-for="y in allYears" :key="y" class="tab" :class="{ active: year === y }" @click="setYear(y)">{{ y }}</div>
        </div>
        <button class="btn btn-secondary btn-sm" :title="$t('Tijdvak, omzetbelastingnummer en herinnering')" @click="showSettings = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          {{ $t('Instellingen') }}
        </button>
        <a :href="route('vat.pdf', { year })" class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          PDF
        </a>
      </div>
    </div>

    <!-- Actie nodig: aangiftetermijn loopt -->
    <div v-if="duePeriod" class="btw-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div class="btw-alert-text">
        <strong>{{ $t('Aangifte :period :year staat open', { period: duePeriod.label, year }) }}</strong> — {{ duePeriod.days_left === 1 ? $t('nog 1 dag') : $t('nog :n dagen', { n: duePeriod.days_left }) }}.
        <span v-if="duePeriod.balance_rounded > 0" v-html="$t('Per saldo <strong>:amount</strong> te betalen; aangifte én betaling vóór <strong>:deadline</strong>.', { amount: whole(duePeriod.balance_rounded), deadline: duePeriod.deadline_label })"></span>
        <span v-else-if="duePeriod.balance_rounded < 0" v-html="$t('Je krijgt <strong>:amount</strong> terug — dien de aangifte in vóór <strong>:deadline</strong>.', { amount: whole(-duePeriod.balance_rounded), deadline: duePeriod.deadline_label })"></span>
        <span v-else v-html="$t('Nihilaangifte — ook zonder omzet doe je aangifte, vóór <strong>:deadline</strong>.', { deadline: duePeriod.deadline_label })"></span>
      </div>
      <button class="btn btn-primary btn-sm" @click="open(duePeriod)">{{ $t('Aangifte voorbereiden →') }}</button>
    </div>

    <!-- Jaartotalen -->
    <div class="kpi-grid">
      <div class="kpi"><div class="lbl">{{ $t('Omzet excl. btw · :year', { year }) }}</div><div class="val">{{ amount(totals.base) }}</div><div class="meta">{{ $t(':n facturen', { n: totals.invoice_count }) }}<span v-if="totals.credit_count"> · {{ $t(":n creditnota's", { n: totals.credit_count }) }}</span></div></div>
      <div class="kpi"><div class="lbl">{{ $t('Btw over je omzet (5a)') }}</div><div class="val">{{ amount(totals.vat) }}</div><div class="meta">{{ $t('rubrieken 1 t/m 4') }}</div></div>
      <div class="kpi"><div class="lbl">{{ $t('Voorbelasting (5b)') }}</div><div class="val">{{ amount(totals.input_vat) }}</div><div class="meta">{{ $t('uit :n inkoopfacturen', { n: totals.purchase_count }) }}</div></div>
      <div class="kpi tint"><div class="lbl">{{ totals.balance < 0 ? $t('Terug te ontvangen') : $t('Per saldo te betalen') }} · {{ year }}</div><div class="val brand">{{ amount(totals.balance) }}</div><div class="meta">{{ $t('5a min 5b') }}</div></div>
    </div>

    <div v-if="totals.invoice_count === 0 && totals.credit_count === 0" class="btw-empty-note">
      {{ $t('Nog geen verstuurde facturen in :year — de tijdvakken hieronder staan op nul. Ook zonder omzet doe je overigens gewoon (nihil)aangifte.', { year }) }}
    </div>

    <!-- Tijdvakken -->
    <div class="btw-grid" :class="gridClass">
      <div v-for="p in periods" :key="p.key" class="btw-card" :class="{ current: p.status === 'current', future: p.status === 'future', due: p.declaration_due, done: p.filed }">
        <div class="btw-card-head">
          <div>
            <div class="btw-card-title">{{ p.label }}</div>
            <div class="btw-card-months">{{ p.months }}</div>
          </div>
          <span class="btw-chip" :class="chip(p).cls">{{ chip(p).label }}</span>
        </div>

        <div class="btw-card-amount">
          <div class="btw-card-amount-label">{{ p.balance_rounded < 0 ? $t('Terug te ontvangen') : $t('Per saldo te betalen') }}</div>
          <div class="btw-card-amount-value" :class="{ neg: p.balance_rounded < 0 }">{{ whole(p.balance_rounded) }} <small>{{ amount(p.balance) }}</small></div>
        </div>

        <table class="btw-table">
          <thead><tr><th>{{ $t('Rubriek') }}</th><th class="right">{{ $t('Grondslag') }}</th><th class="right">{{ $t('Btw') }}</th></tr></thead>
          <tbody>
            <tr v-for="r in cardRows(p)" :key="r.key" :class="{ dim: isEmpty(r) }">
              <td><span class="btw-rubriek">{{ r.key }}</span>{{ shortLabels[r.key] || $t(r.label) }}</td>
              <td class="right num" :class="{ neg: r.base < 0 }">{{ amount(r.base) }}</td>
              <td class="right num" :class="{ neg: r.vat < 0 }">{{ r.no_vat ? '—' : amount(r.vat) }}</td>
            </tr>
            <tr class="btw-subtotal-row"><td><span class="btw-rubriek">5a</span>{{ $t('Verschuldigde btw') }}</td><td></td><td class="right num">{{ amount(rub(p, '5a').vat) }}</td></tr>
            <tr><td><span class="btw-rubriek">5b</span>{{ $t('Voorbelasting') }}</td><td class="right muted-cell">{{ $t(':n inkoopfact.', { n: p.purchase_count }) }}</td><td class="right num vat-in">− {{ amount(rub(p, '5b').vat) }}</td></tr>
            <tr class="btw-total-row"><td><span class="btw-rubriek">5c</span>{{ p.balance < 0 ? $t('Terug te ontvangen') : $t('Te betalen') }}</td><td></td><td class="right num" :class="{ neg: p.balance < 0 }">{{ amount(p.balance) }}</td></tr>
          </tbody>
        </table>

        <div class="btw-card-foot">
          <span>{{ p.invoice_count }} {{ p.invoice_count === 1 ? $t('verkoopfactuur') : $t('verkoopfacturen') }}<span v-if="p.credit_count"> · {{ $t(":n creditnota's", { n: p.credit_count }) }}</span></span>
          <span v-if="p.status !== 'future'" class="btw-deadline" :class="{ urgent: p.declaration_due }">{{ $t('vóór :date', { date: p.deadline_label }) }}</span>
        </div>
        <div class="btw-card-actions">
          <button class="btn btn-sm" :class="p.declaration_due ? 'btn-primary' : 'btn-secondary'" @click="open(p)">{{ $t('Aangifte-klaar →') }}</button>
          <span v-if="p.filed_at_label" class="btw-mark">✓ {{ $t('Aangegeven :date', { date: p.filed_at_label }) }}<template v-if="p.paid_at_label"> · {{ $t('betaald :date', { date: p.paid_at_label }) }}</template></span>
        </div>
      </div>
    </div>

    <p class="btw-disclaimer">
      {{ $t("Berekend op factuurdatum (factuurstelsel) over alle verstuurde facturen en creditnota's. 0%-regels worden op klantland verdeeld over 1e (Nederland), 3b (EU) en 3a (buiten de EU).") }}
      {{ $t('De voorbelasting (5b) komt uit je') }} <Link :href="route('purchases.index')" style="color:var(--brand);font-weight:500;">{{ $t('ingeboekte inkoopfacturen') }}</Link> — {{ $t('dat cijfer is dus zo volledig als je inboekt.') }}
      {{ $t('Wat :brand niet kan weten (verlegde btw, inkoop uit het buitenland, privégebruik) vul je per tijdvak zelf aan. Controleer de cijfers altijd met je boekhouder.', { brand: brand.name }) }}
    </p>

    <!-- Aangifte-klaar: detail per tijdvak -->
    <div v-if="detail" class="modal-overlay" @click.self="close">
      <div class="modal modal-wide">
        <div class="modal-header">
          <div>
            <div class="modal-title">{{ $t('Aangifte :period :year', { period: detail.label, year: detail.year }) }}</div>
            <div class="modal-sub">{{ detail.months }} · {{ $t('aangifte en betaling vóór :date', { date: detail.deadline_label }) }}</div>
          </div>
          <div class="modal-header-actions">
            <label class="cents-toggle"><input type="checkbox" v-model="showCents"> {{ $t('Toon centen') }}</label>
            <button class="icon-btn" @click="close" :title="$t('Sluiten')">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
        </div>
        <div class="modal-body">
          <ol class="steps">
            <li class="done">{{ $t('Controleer de rubrieken hieronder — en vul aan wat :brand niet weet', { brand: brand.name }) }}</li>
            <li>{{ $t('Neem ze over in') }} <a :href="mbz_url" target="_blank" rel="noopener">Mijn Belastingdienst Zakelijk ↗</a> {{ $t('(klik op een bedrag om het te kopiëren)') }}</li>
            <li :class="{ done: detail.filed }">{{ $t('Markeer hieronder als aangegeven') }}</li>
            <li v-if="detail.payment.amount > 0" :class="{ done: detail.paid }">{{ $t('Betaal :amount vóór :date — de betaalgegevens staan klaar', { amount: whole(detail.payment.amount), date: detail.deadline_label }) }}</li>
          </ol>

          <div class="sect-title">{{ $t('Rubrieken') }} <span class="sect-hint">{{ showCents ? $t('exacte bedragen') : $t("hele euro's, afgerond in je voordeel") }}</span></div>
          <table class="rub-table">
            <thead>
              <tr><th></th><th>{{ $t('Omschrijving') }}</th><th class="right">{{ $t('Bedrag waarover btw wordt berekend') }}</th><th class="right">{{ $t('Btw') }}</th></tr>
            </thead>
            <tbody>
              <tr v-for="r in detail.rubrieken" :key="r.key" :class="['rub-' + r.source, { total: r.source === 'total', dim: r.source !== 'total' && r.key !== '5b' && isEmpty(r) }]">
                <td><span class="btw-rubriek">{{ r.key }}</span></td>
                <td>
                  {{ $t(r.label) }}
                  <span v-if="r.key === '3a' || r.key === '3b'" class="rub-note">{{ $t('op basis van het land van de klant') }}</span>
                  <span v-else-if="r.key === '5b'" class="rub-note">{{ $t(':amount uit inkoopfacturen', { amount: amount(r.auto) }) }}<template v-if="r.extra"> + {{ $t(':amount zelf aangevuld', { amount: amount(r.extra) }) }}</template></span>
                  <span v-else-if="r.source === 'manual'" class="rub-note">{{ $t('vul zelf in als van toepassing') }}</span>
                </td>
                <td class="right">
                  <input v-if="r.source === 'manual' && manualForm[r.key]" type="number" step="0.01" v-model.number="manualForm[r.key].base" class="rub-input" placeholder="0">
                  <button v-else-if="r.base !== null" type="button" class="copy-val" :title="$t('Kopieer :value', { value: whole(r.base_rounded) })" @click="copyValue(r, 'base')">
                    {{ fmt(val(r, 'base')) }}<span v-if="copied === r.key + '-base'" class="copied">{{ $t('gekopieerd') }}</span>
                  </button>
                  <span v-else class="muted">—</span>
                </td>
                <td class="right">
                  <span v-if="r.no_vat" class="muted">—</span>
                  <input v-else-if="r.source === 'manual' && manualForm[r.key]" type="number" step="0.01" v-model.number="manualForm[r.key].vat" class="rub-input" placeholder="0">
                  <button v-else type="button" class="copy-val" :class="{ strong: r.source === 'total' }" :title="$t('Kopieer :value', { value: whole(r.vat_rounded) })" @click="copyValue(r, 'vat')">
                    {{ fmt(val(r, 'vat')) }}<span v-if="copied === r.key + '-vat'" class="copied">{{ $t('gekopieerd') }}</span>
                  </button>
                </td>
              </tr>
              <tr class="rub-manual" v-if="manualForm['5b']">
                <td></td>
                <td>{{ $t('Extra voorbelasting buiten :brand', { brand: brand.name }) }} <span class="rub-note">{{ $t('bijv. bonnetjes die je niet hebt ingeboekt — telt op bij 5b') }}</span></td>
                <td></td>
                <td class="right"><input type="number" step="0.01" v-model.number="manualForm['5b'].vat" class="rub-input" placeholder="0"></td>
              </tr>
            </tbody>
          </table>
          <div class="rub-actions">
            <span class="rub-hint">{{ $t('Invulvelden bewaart :brand per tijdvak; de rest rekent hij zelf uit.', { brand: $page.props.brand.name }) }}</span>
            <button class="btn btn-sm" :class="manualDirty ? 'btn-primary' : 'btn-secondary'" :disabled="!manualDirty || manualSaving" @click="saveManual">{{ $t('Aanvullingen opslaan') }}</button>
          </div>

          <div class="sect-title" style="margin-top:22px;">{{ $t('Onderbouwing') }}</div>
          <div class="fold">
            <button type="button" class="fold-head" @click="showInvoices = !showInvoices">
              <span>{{ $t("Verkoopfacturen & creditnota's") }} <em>{{ detail.invoices.length }}</em></span><span>{{ showInvoices ? '−' : '+' }}</span>
            </button>
            <table v-if="showInvoices && detail.invoices.length" class="list-table">
              <tr v-for="i in detail.invoices" :key="i.id">
                <td><Link :href="route('invoices.show', i.id)" class="lnk">{{ i.number }}</Link><span v-if="i.is_credit" class="cc">{{ $t('credit') }}</span></td>
                <td class="grow">{{ i.customer_name }}<span v-if="i.country !== 'NL'" class="cc">{{ i.country }}</span></td>
                <td class="muted">{{ i.date_label }}</td>
                <td class="right num">{{ amount(i.base) }}</td>
                <td class="right num">{{ amount(i.vat) }}</td>
                <td class="tags"><span v-for="k in i.rubrieken" :key="k" class="btw-rubriek">{{ k }}</span></td>
              </tr>
            </table>
            <div v-else-if="showInvoices" class="fold-empty">{{ $t('Geen verkoopfacturen in dit tijdvak.') }}</div>
          </div>
          <div class="fold">
            <button type="button" class="fold-head" @click="showPurchases = !showPurchases">
              <span>{{ $t('Inkoopfacturen (voorbelasting)') }} <em>{{ detail.purchases.length }}</em></span><span>{{ showPurchases ? '−' : '+' }}</span>
            </button>
            <table v-if="showPurchases && detail.purchases.length" class="list-table">
              <tr v-for="i in detail.purchases" :key="i.id">
                <td class="grow"><Link :href="route('purchases.show', i.id)" class="lnk">{{ i.supplier_name }}</Link></td>
                <td class="muted">{{ i.date_label }}</td>
                <td class="right num muted">{{ amount(i.total) }} {{ $t('incl.') }}</td>
                <td class="right num vat-in">{{ amount(i.vat) }}</td>
              </tr>
            </table>
            <div v-else-if="showPurchases" class="fold-empty">{{ $t('Geen inkoopfacturen in dit tijdvak —') }} <Link :href="route('purchases.index')" class="lnk">{{ $t('boek ze in') }}</Link> {{ $t('om je voorbelasting terug te vragen.') }}</div>
          </div>

          <div class="sect-title" style="margin-top:22px;">{{ $t('Betalen') }}</div>
          <div v-if="detail.payment.amount > 0" class="pay-box">
            <div class="pay-row"><span class="pay-k">{{ $t('Bedrag') }}</span><button type="button" class="copy-val" @click="copy(detail.payment.amount, 'pay-amount')">{{ whole(detail.payment.amount) }}<span v-if="copied === 'pay-amount'" class="copied">{{ $t('gekopieerd') }}</span></button></div>
            <div class="pay-row"><span class="pay-k">IBAN</span><button type="button" class="copy-val mono" @click="copy(detail.payment.iban.replace(/ /g, ''), 'pay-iban')">{{ detail.payment.iban }}<span v-if="copied === 'pay-iban'" class="copied">{{ $t('gekopieerd') }}</span></button></div>
            <div class="pay-row"><span class="pay-k">{{ $t('Ten name van') }}</span><span class="pay-v">{{ detail.payment.beneficiary }}</span></div>
            <div class="pay-row">
              <span class="pay-k">{{ $t('Betalingskenmerk') }}</span>
              <template v-if="detail.payment.reference">
                <button type="button" class="copy-val mono" @click="copy(detail.payment.reference, 'pay-ref')">{{ detail.payment.reference_formatted }}<span v-if="copied === 'pay-ref'" class="copied">{{ $t('gekopieerd') }}</span></button>
                <span class="pay-src">{{ detail.payment.reference_source === 'auto' ? $t('berekend uit je omzetbelastingnummer') : $t('zelf ingevuld') }}</span>
              </template>
              <span v-else class="pay-v muted">{{ $t('nog niet bekend') }}</span>
            </div>
            <div class="pay-note">
              <span v-html="$t('Zet het kenmerk in het veld <b>Betalingskenmerk</b> van je overschrijving — zonder kenmerk kan de Belastingdienst je betaling niet verwerken.')"></span>
              <template v-if="detail.payment.reference_source === 'auto'"> {{ $t('Controleer het met het kenmerk bij je ingestuurde aangifte.') }}</template>
              <template v-if="!detail.payment.reference"> {{ $t('Je vindt het in Mijn Belastingdienst Zakelijk bij je ingestuurde aangifte — plak het hieronder. Of') }} <button type="button" class="link" @click="showSettings = true">{{ $t('stel je omzetbelastingnummer in') }}</button>{{ $t(', dan berekent :brand het voortaan zelf.', { brand: $page.props.brand.name }) }}</template>
            </div>
            <form class="ref-form" @submit.prevent="saveReference">
              <input type="text" v-model="refForm.payment_reference" maxlength="30" :placeholder="detail.payment.reference ? $t('Afwijkend kenmerk van de Belastingdienst? Plak het hier') : $t('Betalingskenmerk (16 cijfers)')">
              <button class="btn btn-secondary btn-sm" type="submit" :disabled="!refForm.payment_reference || refForm.processing">{{ $t('Opslaan') }}</button>
            </form>
            <div v-if="refForm.errors.payment_reference" class="field-error">{{ refForm.errors.payment_reference }}</div>
          </div>
          <div v-else-if="detail.balance_rounded < 0" class="pay-box neutral" v-html="$t('Je krijgt per saldo <b>:amount</b> terug. Dien de aangifte in; de Belastingdienst betaalt uit na verwerking.', { amount: whole(-detail.balance_rounded) })"></div>
          <div v-else class="pay-box neutral">{{ $t('Per saldo niets te betalen (nihilaangifte). Dien de aangifte wél in — ook bij :zero.', { zero: whole(0) }) }}</div>

          <div class="sect-title" style="margin-top:22px;">{{ $t('Status') }}</div>
          <div class="status-row">
            <label class="opt" :class="{ on: detail.filed }">
              <input type="checkbox" :checked="detail.filed" @change="toggle(detail, 'filed', $event.target.checked)">
              <div>
                <div class="opt-title">{{ $t('Aangifte ingediend') }}</div>
                <div class="opt-sub">{{ detail.filed_at_label ? $t('Gemarkeerd op :date', { date: detail.filed_at_label }) : $t('Vink aan zodra je de aangifte hebt verstuurd; de herinneringen stoppen dan.') }}</div>
              </div>
            </label>
            <label v-if="detail.payment.amount > 0" class="opt" :class="{ on: detail.paid }">
              <input type="checkbox" :checked="detail.paid" @change="toggle(detail, 'paid', $event.target.checked)">
              <div>
                <div class="opt-title">{{ $t('Betaald') }}</div>
                <div class="opt-sub">{{ detail.paid_at_label ? $t('Gemarkeerd op :date', { date: detail.paid_at_label }) : $t(':amount overgemaakt aan de Belastingdienst.', { amount: whole(detail.payment.amount) }) }}</div>
              </div>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <a :href="mbz_url" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">{{ $t('Open Mijn Belastingdienst Zakelijk ↗') }}</a>
          <button class="btn btn-primary btn-sm" @click="close">{{ $t('Sluiten') }}</button>
        </div>
      </div>
    </div>

    <!-- Instellingen -->
    <div v-if="showSettings" class="modal-overlay" @click.self="showSettings = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Btw-instellingen') }}</div>
          <button class="icon-btn" @click="showSettings = false" :title="$t('Sluiten')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>{{ $t('Aangiftetijdvak') }}</label>
            <select v-model="settingsForm.vat_period">
              <option value="quarter">{{ $t('Per kwartaal (meest gebruikelijk)') }}</option>
              <option value="month">{{ $t('Per maand') }}</option>
              <option value="year">{{ $t('Per jaar') }}</option>
            </select>
            <div class="hint">{{ $t('Zoals de Belastingdienst het aan je heeft toegewezen — staat in je brief en in Mijn Belastingdienst Zakelijk.') }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Omzetbelastingnummer') }} <span class="lbl-hint">{{ $t('(voor het betalingskenmerk)') }}</span></label>
            <div v-if="settings.has_ob_number && !settingsForm.ob_number_clear" class="ob-current">
              {{ $t('Ingesteld:') }} <b>{{ settings.ob_number_hint }}</b>
              <button type="button" class="link" @click="settingsForm.ob_number_clear = true">{{ $t('wissen') }}</button>
            </div>
            <input v-else type="text" v-model="settingsForm.ob_number" placeholder="123456789B01" maxlength="30">
            <div class="hint" v-html="$t('Staat bovenaan je aangiftebrief en in Mijn Belastingdienst Zakelijk. Bij een eenmanszaak is dit een <b>ander</b> nummer dan het btw-id op je facturen. Het wordt versleuteld opgeslagen en nooit getoond; :brand berekent er alleen het betalingskenmerk mee.', { brand: brand.name })"></div>
            <div v-if="settingsForm.errors.ob_number" class="field-error">{{ settingsForm.errors.ob_number }}</div>
          </div>
          <label class="opt" :class="{ on: settingsForm.vat_reminder_enabled }">
            <input type="checkbox" v-model="settingsForm.vat_reminder_enabled">
            <div>
              <div class="opt-title">{{ $t('Herinnering per e-mail') }}</div>
              <div class="opt-sub">{{ $t('Twee weken en drie dagen vóór de deadline naar :email — alleen zolang het tijdvak niet als aangegeven is gemarkeerd.', { email: settings.reminder_email }) }}</div>
            </div>
          </label>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showSettings = false">{{ $t('Annuleren') }}</button>
            <button class="btn btn-primary btn-sm" @click="saveSettings" :disabled="settingsForm.processing">{{ $t('Opslaan') }}</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.btw-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.year-tabs { display: flex; gap: 4px; background: var(--surface); border: 1px solid var(--border); padding: 4px; border-radius: 10px; }
.tab { padding: 8px 16px; font-size: 13px; font-weight: 500; color: var(--text-3); border-radius: 7px; cursor: pointer; }
.tab:hover { color: var(--text); }
.tab.active { background: var(--text); color: white; }

.btw-alert {
  display: flex; align-items: center; gap: 12px;
  background: var(--warning-bg); border: 1px solid var(--warning-border); color: var(--warning);
  border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; font-size: 13.5px; line-height: 1.6;
}
.btw-alert > svg { width: 19px; height: 19px; flex: none; }
.btw-alert-text { flex: 1; min-width: 0; }
.btw-alert strong { font-weight: 700; }
.btw-alert .btn { flex: none; }

.kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.kpi.tint { background: var(--brand-tint); border-color: var(--brand-border); }
.kpi .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.kpi .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; }
.kpi .val.brand { color: var(--brand-darker); }
.kpi .meta { font-size: 11px; color: var(--text-3); margin-top: 4px; }

.btw-empty-note { background: var(--surface-2); border: 1px dashed var(--border-strong); border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; font-size: 13px; color: var(--text-3); line-height: 1.6; }

.btw-grid { display: grid; gap: 16px; }
.btw-grid.cols-1 { grid-template-columns: minmax(0, 1fr); max-width: 640px; }
.btw-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.btw-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.btw-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 20px 22px; display: flex; flex-direction: column; }
.btw-card.current { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-tint); }
.btw-card.due { border-color: var(--warning-border); }
.btw-card.done { border-color: var(--success-border); }
.btw-card.future { opacity: 0.6; }

.btw-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
.btw-card-title { font-family: var(--font-display); font-weight: 700; font-size: 17px; letter-spacing: -0.01em; }
.btw-card-months { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }

.btw-chip { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 100px; border: 1px solid var(--border-strong); background: var(--surface-2); color: var(--text-2); white-space: nowrap; flex: none; }
.btw-chip.current { background: var(--info-bg); border-color: var(--info-border); color: var(--info); }
.btw-chip.filed, .btw-chip.paid { background: var(--success-bg); border-color: var(--success-border); color: var(--success); }
.btw-chip.due { background: var(--warning-bg); border-color: var(--warning-border); color: var(--warning); }
.btw-chip.unmarked { color: var(--text-4); }

.btw-card-amount { margin-bottom: 14px; }
.btw-card-amount-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; }
.btw-card-amount-value { font-family: var(--font-display); font-weight: 700; font-size: 26px; letter-spacing: -0.02em; margin-top: 2px; }
.btw-card-amount-value small { font-family: var(--font-mono); font-size: 12px; font-weight: 400; color: var(--text-4); margin-left: 6px; letter-spacing: 0; }

.btw-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.btw-table th { text-align: left; padding: 7px 8px; font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); border-bottom: 1px solid var(--border); background: var(--surface-2); }
.btw-table th:first-child { border-radius: 6px 0 0 6px; }
.btw-table th:last-child { border-radius: 0 6px 6px 0; }
.btw-table td { padding: 8px 8px; border-bottom: 1px solid var(--border); }
.btw-table .right { text-align: right; }
.btw-table .num { font-family: var(--font-mono); }
.btw-table tr.dim td { color: var(--text-4); }
.btw-rubriek { display: inline-flex; align-items: center; justify-content: center; min-width: 26px; padding: 1px 5px; margin-right: 7px; background: var(--surface-3); border-radius: 5px; font-size: 10.5px; font-weight: 700; color: var(--text-2); }
.btw-subtotal-row td { font-weight: 600; }
.muted-cell { color: var(--text-4); font-size: 11.5px; }
.vat-in { color: var(--success); font-weight: 600; }
.btw-total-row td { font-weight: 700; border-bottom: none; padding-top: 11px; }

.btw-card-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border); font-size: 12px; color: var(--text-3); }
.btw-deadline.urgent { color: var(--warning); font-weight: 600; }
.btw-card-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
.btw-mark { font-size: 12px; color: var(--success); font-weight: 600; }
.neg { color: var(--brand); }
.muted { color: var(--text-4); }
.btw-disclaimer { margin-top: 18px; font-size: 12px; color: var(--text-4); line-height: 1.6; max-width: 760px; }
@media (max-width: 900px) { .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .btw-grid.cols-2, .btw-grid.cols-3 { grid-template-columns: minmax(0, 1fr); } }

/* Modals */
.modal-overlay { position: fixed; inset: 0; background: rgba(28, 25, 23, 0.45); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 20px; }
.modal { background: var(--surface); border-radius: var(--r-lg); width: 100%; max-width: 520px; max-height: calc(100vh - 40px); overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
.modal.modal-wide { max-width: 880px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 24px; border-bottom: 1px solid var(--border); position: sticky; top: 0; background: var(--surface); z-index: 2; }
.modal-title { font-family: var(--font-display); font-weight: 600; font-size: 18px; }
.modal-sub { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }
.modal-header-actions { display: flex; align-items: center; gap: 12px; }
.cents-toggle { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-3); cursor: pointer; white-space: nowrap; }
.modal-body { padding: 22px 24px; }
.modal-footer { padding: 14px 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 10px; background: var(--surface-2); border-radius: 0 0 var(--r-lg) var(--r-lg); flex-wrap: wrap; }
.icon-btn { width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); background: none; border: none; cursor: pointer; }
.icon-btn:hover { background: var(--surface-2); }

.steps { margin: 0 0 20px; padding: 0 0 0 22px; font-size: 13px; color: var(--text-2); line-height: 1.9; }
.steps li.done { color: var(--text-4); text-decoration: line-through; }
.steps a { color: var(--brand); font-weight: 600; }

.sect-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 10px; display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
.sect-hint { font-family: var(--font-body); font-weight: 400; font-size: 12px; color: var(--text-4); }

.rub-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.rub-table th { text-align: left; padding: 7px 8px; font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); border-bottom: 1px solid var(--border); background: var(--surface-2); }
.rub-table th.right, .rub-table td.right { text-align: right; }
.rub-table td { padding: 7px 8px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.rub-table tr.dim td { color: var(--text-4); }
.rub-table tr.total td { font-weight: 700; background: var(--surface-2); }
.rub-table tr.rub-manual td { background: #FFFDF5; }
.rub-note { display: block; font-size: 11px; color: var(--text-4); font-weight: 400; }
.rub-input { width: 120px; text-align: right; font-family: var(--font-mono); font-size: 13px; padding: 5px 8px; }
.copy-val { background: none; border: 1px dashed transparent; border-radius: 6px; padding: 3px 8px; font-family: var(--font-mono); font-size: 13px; color: inherit; cursor: pointer; position: relative; }
.copy-val:hover { border-color: var(--border-strong); background: var(--surface-2); }
.copy-val.strong { font-weight: 700; }
.copy-val.mono { letter-spacing: 0.02em; }
.copied { position: absolute; right: 0; top: -20px; font-family: var(--font-body); font-size: 10.5px; font-weight: 600; color: var(--success); background: var(--success-bg); border: 1px solid var(--success-border); padding: 1px 7px; border-radius: 100px; white-space: nowrap; }
.rub-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
.rub-hint { font-size: 12px; color: var(--text-4); }

.fold { border: 1px solid var(--border); border-radius: 10px; margin-bottom: 8px; overflow: hidden; }
.fold-head { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--surface-2); border: none; font: inherit; font-size: 13px; font-weight: 600; color: var(--text-2); cursor: pointer; }
.fold-head em { font-style: normal; font-weight: 500; color: var(--text-4); margin-left: 6px; }
.fold-empty { padding: 12px 14px; font-size: 12.5px; color: var(--text-4); }
.list-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.list-table td { padding: 7px 12px; border-top: 1px solid var(--border); vertical-align: middle; white-space: nowrap; }
.list-table td.grow { white-space: normal; width: 100%; }
.list-table .right { text-align: right; }
.list-table .num { font-family: var(--font-mono); }
.list-table .tags .btw-rubriek { margin-right: 3px; }
.lnk { color: var(--brand); font-weight: 600; }
.cc { display: inline-block; margin-left: 6px; font-size: 10px; font-weight: 700; color: var(--text-4); background: var(--surface-3); border-radius: 4px; padding: 1px 5px; }

.pay-box { background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 12px 16px; }
.pay-box.neutral { font-size: 13px; color: var(--text-2); line-height: 1.6; }
.pay-row { display: flex; align-items: center; gap: 12px; padding: 6px 0; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
.pay-row:last-of-type { border-bottom: none; }
.pay-k { width: 150px; flex: none; font-size: 12.5px; color: var(--text-3); }
.pay-v { font-size: 13.5px; font-weight: 600; }
.pay-src { font-size: 11.5px; color: var(--text-4); }
.pay-note { font-size: 12px; color: var(--text-3); line-height: 1.6; margin: 10px 0 8px; }
.ref-form { display: flex; gap: 8px; align-items: center; }
.ref-form input { flex: 1; font-family: var(--font-mono); }
.link { background: none; border: none; padding: 0; font: inherit; color: var(--brand); text-decoration: underline; cursor: pointer; }

.status-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.opt { display: flex; gap: 12px; align-items: flex-start; border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; cursor: pointer; transition: border-color .15s, background .15s; }
.opt:hover { background: var(--surface-2); }
.opt.on { border-color: var(--success); background: var(--success-bg); }
.opt input { margin-top: 3px; width: 16px; height: 16px; accent-color: var(--success); flex: none; }
.opt-title { font-weight: 600; font-size: 13.5px; }
.opt-sub { font-size: 12px; color: var(--text-3); margin-top: 2px; line-height: 1.5; }
.hint { font-size: 12px; color: var(--text-3); line-height: 1.6; margin-top: 6px; }
.lbl-hint { font-weight: 400; color: var(--text-4); }
.ob-current { font-size: 13.5px; padding: 8px 0; display: flex; gap: 10px; align-items: center; }
@media (max-width: 640px) { .status-row { grid-template-columns: minmax(0, 1fr); } .pay-k { width: 100%; } }
</style>
