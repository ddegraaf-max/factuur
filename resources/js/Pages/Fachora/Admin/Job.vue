<script setup>
/**
 * Eigenaar: één opdracht met alles erop en eraan. Klant, aanvraag, de
 * aanbiedingen, handmatig aanbieden aan geclaimde bedrijven (alleen met
 * toestemming), de bellijst van bedrijven uit het register in de buurt
 * (alleen bellen), het geschil met de beslissing, geld en het verloop.
 */
import { computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FachoraBadge from '@/Components/FachoraBadge.vue';
import { icon } from '@/fachora-icons.js';
import { money, num } from '@/format.js';
import { t } from '@/i18n';

const props = defineProps({
  job: Object,
  offers: { type: Array, default: () => [] },
  provider: { type: Object, default: null },
  files: { type: Array, default: () => [] },
  nearby: { type: Array, default: () => [] },
  claimable: { type: Array, default: () => [] },
  protect_enabled: { type: Boolean, default: false },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const jobPill = {
  pending: 'pill-draft', open: 'pill-sent', quoted: 'pill-partial', no_match: 'pill-overdue', agreed: 'pill-sent', scheduled: 'pill-sent',
  in_progress: 'pill-partial', done: 'pill-partial', accepted: 'pill-paid', disputed: 'pill-overdue', cancelled: 'pill-cancelled', expired: 'pill-cancelled',
};
const offerPill = { sent: 'pill-sent', responded: 'pill-partial', declined: 'pill-cancelled', chosen: 'pill-paid', rejected: 'pill-draft', expired: 'pill-cancelled' };
const statusLabel = { unclaimed: 'Niet geclaimd', claimed: 'Geclaimd', pending: 'In verificatie', verified: 'Verified', suspended: 'Geschorst', blocked: 'Geblokkeerd' };
const typeLabel = { fixed: 'Vaste prijs', hourly: 'Uurtarief', estimate: 'Richtprijs' };
const extraPill = { proposed: 'pill-partial', approved: 'pill-paid', rejected: 'pill-cancelled' };
const extraLabel = { proposed: 'Wacht op de klant', approved: 'Goedgekeurd', rejected: 'Afgewezen|meerwerk' };
const fileKindLabel = { photo: "Foto's van de klant", done: "Foto's bij de oplevering", dispute: "Foto's bij het geschil" };
const actorLabel = { customer: 'klant', provider: 'bedrijf', owner: 'Fachora', system: 'systeem', auto: 'automatisch' };
const stars = (rating) => '★'.repeat(Math.round(Number(rating) || 0)) + '☆'.repeat(5 - Math.round(Number(rating) || 0));

const job = computed(() => props.job);
const status = computed(() => props.job.status);
const title = computed(() => props.job.title_pl || props.job.title);
const canOffer = computed(() => ['open', 'quoted', 'no_match'].includes(status.value));
const canAccept = computed(() => ['done', 'in_progress', 'scheduled'].includes(status.value) && props.job.provider_id);
const canCancel = computed(() => !['accepted', 'cancelled', 'expired', 'done', 'disputed'].includes(status.value));
const canRetryPayout = computed(() => status.value === 'accepted' && props.job.paid && !props.job.has_transfer);
const fileGroups = computed(() => {
  const groups = {};
  for (const f of props.files) (groups[f.kind] ||= []).push(f);
  return Object.entries(groups).map(([kind, items]) => ({ kind, label: fileKindLabel[kind] || kind, items }));
});

/* ---------- handmatig aanbieden ---------- */
const offerForm = useForm({ provider_ids: [] });
const toggleProvider = (id) => { const i = offerForm.provider_ids.indexOf(id); if (i >= 0) offerForm.provider_ids.splice(i, 1); else offerForm.provider_ids.push(id); };
const offerTo = () => {
  if (!offerForm.provider_ids.length) return;
  if (!confirm(t('De opdracht per e-mail aanbieden aan :n bedrijven?', { n: offerForm.provider_ids.length }))) return;
  offerForm.post(route('fachora.admin.jobs.offer', job.value.id), { preserveScroll: true, onSuccess: () => offerForm.reset() });
};

/* ---------- geschil ---------- */
const resolveForm = useForm({ resolution: 'payout', payout_amount: '', note: '' });
const resolve = () => {
  const label = { payout: t('volledig uitbetalen aan het bedrijf'), refund: t('volledig terugbetalen aan de klant'), split: t('gedeeltelijk uitbetalen') }[resolveForm.resolution];
  if (!confirm(t('Geschil beslissen: :what? Beide partijen krijgen bericht; dit is definitief.', { what: label }))) return;
  resolveForm
    .transform((d) => ({ ...d, payout_amount: d.resolution === 'split' ? Number(String(d.payout_amount).replace(',', '.')) || 0 : null }))
    .post(route('fachora.admin.jobs.resolve', job.value.id), { preserveScroll: true });
};

/* ---------- overige acties ---------- */
const cancelForm = useForm({ reason: '' });
const cancelJob = () => {
  if (!confirm(t('Opdracht annuleren namens Fachora? Open aanbiedingen vervallen; een gereserveerd bedrag gaat terug naar de klant.'))) return;
  cancelForm.post(route('fachora.admin.jobs.cancel', job.value.id), { preserveScroll: true });
};
const accept = () => {
  if (!confirm(t('Goedkeuren namens de klant? Daarmee start de uitbetaling aan het bedrijf.'))) return;
  router.post(route('fachora.admin.jobs.accept', job.value.id), {}, { preserveScroll: true });
};
const retryPayout = () => {
  if (!confirm(t('Uitbetaling opnieuw proberen via Stripe?'))) return;
  router.post(route('fachora.admin.jobs.payout', job.value.id), {}, { preserveScroll: true });
};
</script>

<template>
  <Head :title="job.reference + ' · ' + title" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        {{ $t('Eigenaar') }} / <Link :href="route('fachora.admin.jobs')" style="color:var(--text-3);">{{ $t('Fachora: opdrachten') }}</Link> /
        <span class="breadcrumb-current">{{ job.reference }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('fachora.admin.jobs')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">‹ {{ $t('Terug') }}</Link>
        <h1 class="page-title">{{ title }} <span :class="['pill', jobPill[status]]" style="vertical-align:middle;margin-left:8px;">{{ job.status_label }}</span></h1>
        <p class="page-subtitle">
          <span class="num">{{ job.reference }}</span> · {{ job.category_name }}<template v-if="job.city"> · {{ job.postal_code }} {{ job.city }}</template> · {{ job.language_label }}
          <template v-if="job.created_at"> · {{ $t('aangevraagd :date', { date: job.created_at }) }}</template>
          <template v-if="job.waves"> · {{ $t('golf :n', { n: job.waves }) }}</template>
        </p>
      </div>
      <div class="page-actions">
        <button v-if="canAccept" class="btn btn-primary btn-sm" @click="accept">{{ $t('Goedkeuren namens de klant') }}</button>
        <button v-if="canRetryPayout" class="btn btn-primary btn-sm" @click="retryPayout">{{ $t('Uitbetaling opnieuw') }}</button>
        <Link v-if="job.invoice_id" :href="route('invoices.show', job.invoice_id)" class="btn btn-secondary btn-sm">{{ $t('Factuur') }}</Link>
      </div>
    </div>

    <div v-if="flash.error" class="fx-alert fx-alert--err">{{ flash.error }}</div>

    <div class="fx-grid">
      <div class="fx-main">
        <!-- Geschil -->
        <div v-if="status === 'disputed' || job.dispute_reason" class="card fx-card-warn">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Geschil') }}</div>
              <div class="card-subtitle"><template v-if="job.dispute_opened_at">{{ $t('Geopend door de klant op :date', { date: job.dispute_opened_at }) }} · </template>{{ $t('het bedrijf heeft 48 uur; zonder reactie wint de klant') }}</div>
            </div>
          </div>
          <div class="card-body">
            <div class="lbl">{{ $t('Bezwaar van de klant') }}</div>
            <p class="fx-text">{{ job.dispute_reason }}</p>
            <div class="lbl">{{ $t('Reactie van het bedrijf') }}</div>
            <p class="fx-text" :class="{ sub: !job.dispute_reply }">{{ job.dispute_reply || $t('(nog geen reactie)') }}</p>
            <template v-if="job.dispute_resolution">
              <div class="lbl">{{ $t('Beslissing') }}</div>
              <p class="fx-text">{{ $t({ payout: 'Uitbetaling aan het bedrijf', refund: 'Terugbetaling aan de klant', split: 'Gedeeltelijke uitbetaling' }[job.dispute_resolution] || job.dispute_resolution) }}<template v-if="job.dispute_note"> — {{ job.dispute_note }}</template></p>
            </template>
            <form v-else-if="status === 'disputed'" class="fx-resolve" @submit.prevent="resolve">
              <div class="lbl">{{ $t('Beslissing') }}</div>
              <label class="fx-radio"><input type="radio" value="payout" v-model="resolveForm.resolution"> {{ $t('Uitbetalen aan het bedrijf (klacht ongegrond)') }}</label>
              <label class="fx-radio"><input type="radio" value="refund" v-model="resolveForm.resolution"> {{ $t('Terugbetalen aan de klant (klacht gegrond)') }}</label>
              <label class="fx-radio"><input type="radio" value="split" v-model="resolveForm.resolution"> {{ $t('Gedeeltelijk uitbetalen') }}</label>
              <div v-if="resolveForm.resolution === 'split'" class="form-group" style="max-width:260px;">
                <label>{{ $t('Bedrag voor het bedrijf (zł, vóór fee)') }}</label>
                <input type="text" inputmode="decimal" v-model="resolveForm.payout_amount" :placeholder="job.total ? num(job.total) : '0,00'">
                <div v-if="resolveForm.errors.payout_amount" class="field-error">{{ resolveForm.errors.payout_amount }}</div>
              </div>
              <div class="form-group">
                <label>{{ $t('Toelichting (gaat naar beide partijen)') }}</label>
                <textarea v-model="resolveForm.note" rows="3" maxlength="3000"></textarea>
                <div v-if="resolveForm.errors.note" class="field-error">{{ resolveForm.errors.note }}</div>
              </div>
              <button type="submit" class="btn btn-primary" :disabled="resolveForm.processing">{{ $t('Geschil beslissen') }}</button>
            </form>
          </div>
        </div>

        <!-- Klant -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Klant') }}</div></div>
          <div class="card-body">
            <dl class="fx-facts">
              <dt>{{ $t('Naam') }}</dt><dd>{{ job.customer_name || '—' }}</dd>
              <dt>{{ $t('E-mail') }}</dt><dd><a v-if="job.customer_email" :href="'mailto:' + job.customer_email">{{ job.customer_email }}</a><span v-else>—</span></dd>
              <dt>{{ $t('Telefoon') }}</dt><dd><a v-if="job.customer_phone" :href="'tel:' + job.customer_phone">{{ job.customer_phone }}</a><span v-else>—</span></dd>
              <dt>{{ $t('Adres') }}</dt><dd>{{ job.address_line || '—' }}<template v-if="job.postal_code || job.city">, {{ job.postal_code }} {{ job.city }}</template></dd>
            </dl>
          </div>
        </div>

        <!-- Aanvraag -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Aanvraag') }}</div></div>
          <div class="card-body">
            <p v-if="job.description_pl" class="fx-text">{{ job.description_pl }}</p>
            <template v-if="job.description && job.description !== job.description_pl">
              <div class="lbl">{{ $t('Oorspronkelijke tekst van de klant') }} ({{ job.language_label }})</div>
              <p class="fx-text sub">{{ job.description }}</p>
            </template>
            <p v-if="job.summary_en" class="sub" style="margin:0 0 10px;">EN: {{ job.summary_en }}</p>
            <dl class="fx-facts">
              <dt>{{ $t('Wanneer') }}</dt><dd>{{ job.urgency_label }}<template v-if="job.time_window"> · {{ job.time_window }}</template></dd>
              <dt>{{ $t('Prijsindicatie') }}</dt><dd>{{ job.estimate_low !== null || job.estimate_high !== null ? money(job.estimate_low || 0) + ' – ' + money(job.estimate_high || 0) : '—' }}</dd>
              <dt>{{ $t('Aangeboden op|bedrijven') }}</dt><dd>{{ job.offered_at || '—' }}<template v-if="job.offers_deadline"> · {{ $t('reageren vóór :date', { date: job.offers_deadline }) }}</template></dd>
            </dl>
            <template v-if="job.questions_pl && job.questions_pl.length">
              <div class="lbl" style="margin-top:12px;">{{ $t('Vragen voor het bedrijf (AI)') }}</div>
              <ul class="fx-list"><li v-for="(q, i) in job.questions_pl" :key="i">{{ q }}</li></ul>
            </template>
            <div v-for="g in fileGroups" :key="g.kind" style="margin-top:12px;">
              <div class="lbl">{{ $t(g.label) }}</div>
              <div class="fx-photos">
                <a v-for="f in g.items" :key="f.id" :href="f.url" target="_blank" rel="noopener" class="fx-photo" :title="f.filename">
                  <img v-if="f.image" :src="f.url" :alt="f.filename" loading="lazy"><span v-else class="fx-file">{{ f.filename }}</span>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Aanbiedingen -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Aanbiedingen') }}</div>
              <div class="card-subtitle">{{ $t(':n aangeboden, :m met prijs', { n: offers.length, m: offers.filter((o) => o.price !== null && ['responded', 'chosen', 'rejected'].includes(o.status)).length }) }}</div>
            </div>
          </div>
          <div v-if="offers.length" class="table-scroll">
            <table class="data-table">
              <thead><tr><th>{{ $t('Bedrijf') }}</th><th>{{ $t('Status') }}</th><th class="right">{{ $t('Prijs') }}</th><th>{{ $t('Beschikbaar') }}</th><th>{{ $t('Gereageerd') }}</th></tr></thead>
              <tbody>
                <tr v-for="o in offers" :key="o.id">
                  <td class="cell-primary">
                    <Link v-if="o.provider" :href="route('fachora.admin.providers.show', o.provider.id)">{{ o.provider.name }}</Link><span v-else>—</span>
                    <div class="sub"><template v-if="o.provider">{{ o.provider.city }} · {{ $t(statusLabel[o.provider.status] || o.provider.status) }} · </template>{{ $t('golf :n', { n: o.wave }) }}<template v-if="o.sent_at"> · {{ o.sent_at }}</template></div>
                  </td>
                  <td :data-label="$t('Status')"><span :class="['pill', offerPill[o.status]]">{{ o.status_label }}</span><div v-if="o.decline_reason" class="sub">{{ o.decline_reason }}</div></td>
                  <td :data-label="$t('Prijs')" class="right num">{{ o.price !== null ? money(o.price) : '—' }}<div v-if="o.price !== null" class="sub">{{ $t(typeLabel[o.price_type] || 'Vaste prijs') }}</div></td>
                  <td :data-label="$t('Beschikbaar')">{{ o.available_label || '—' }}<div v-if="o.available_note" class="sub">{{ o.available_note }}</div></td>
                  <td :data-label="$t('Gereageerd')" class="sub">{{ o.responded_at || '—' }}<div v-if="o.remarks" class="sub">{{ o.remarks }}</div></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="card-body sub">{{ $t('Nog aan geen enkel bedrijf aangeboden.') }}</div>
        </div>

        <!-- Handmatig aanbieden -->
        <div v-if="canOffer" class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Opdracht aanbieden aan bedrijven') }}</div>
              <div class="card-subtitle">{{ $t('Geclaimde bedrijven in deze categorie die nog geen aanbieding kregen. Alleen bedrijven met toestemming krijgen e-mail; de rest staat grijs.') }}</div>
            </div>
          </div>
          <div class="card-body">
            <div v-if="claimable.length" class="fx-pick">
              <label v-for="c in claimable" :key="c.id" class="fx-pick-item" :class="{ off: !c.consent, on: offerForm.provider_ids.includes(c.id) }">
                <input type="checkbox" :disabled="!c.consent" :checked="offerForm.provider_ids.includes(c.id)" @change="toggleProvider(c.id)">
                <span class="fx-pick-name">{{ c.name }}</span>
                <span class="sub">{{ c.city }} · {{ $t(statusLabel[c.status] || c.status) }}<template v-if="!c.consent"> · <b>{{ $t('zonder toestemming') }}</b></template></span>
              </label>
            </div>
            <div v-else class="sub">{{ $t('Geen geclaimde bedrijven in deze categorie die nog geen aanbieding kregen.') }}</div>
            <div v-if="offerForm.errors.provider_ids" class="field-error">{{ offerForm.errors.provider_ids }}</div>
            <button v-if="claimable.length" class="btn btn-primary btn-sm" style="margin-top:12px;" :disabled="offerForm.processing || !offerForm.provider_ids.length" @click="offerTo">{{ $t('Aanbieden aan :n bedrijven', { n: offerForm.provider_ids.length }) }}</button>
          </div>
        </div>

        <!-- Bellijst -->
        <div v-if="canOffer || status === 'pending'" class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Bellijst: bedrijven uit het register in de buurt') }}</div>
              <div class="card-subtitle fx-call-note"><span v-html="icon('shield', 14)"></span> {{ $t('Alleen bellen – geen e-mail en geen sms (geen toestemming). Wie interesse heeft, claimt zijn profiel; daarna kunt u aanbieden.') }}</div>
            </div>
          </div>
          <div v-if="nearby.length" class="table-scroll">
            <table class="data-table">
              <thead><tr><th>{{ $t('Bedrijf') }}</th><th>{{ $t('Plaats') }}</th><th class="right">{{ $t('Afstand') }}</th><th>{{ $t('Telefoon') }}</th><th>{{ $t('Status') }}</th></tr></thead>
              <tbody>
                <tr v-for="n in nearby" :key="n.id">
                  <td class="cell-primary"><Link :href="n.url">{{ n.name }}</Link><div v-if="n.website" class="sub"><a :href="n.website.startsWith('http') ? n.website : 'https://' + n.website" target="_blank" rel="noopener">{{ n.website }}</a></div></td>
                  <td :data-label="$t('Plaats')">{{ n.city || '—' }}</td>
                  <td :data-label="$t('Afstand')" class="right num">{{ n.distance_km !== null && n.distance_km !== undefined ? num(n.distance_km, 0) + ' km' : '—' }}</td>
                  <td :data-label="$t('Telefoon')"><a v-if="n.phone" :href="'tel:' + n.phone" class="fx-tel">{{ n.phone }}</a><span v-else class="sub">{{ $t('geen nummer') }}</span></td>
                  <td :data-label="$t('Status')"><span class="pill pill-draft">{{ $t(statusLabel[n.status] || n.status) }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="card-body sub">{{ $t('Geen bedrijven uit het register in de buurt (of de opdracht is niet gegeocodeerd).') }}</div>
        </div>

        <!-- Meerwerk -->
        <div v-if="job.extras.length" class="card">
          <div class="card-header"><div class="card-title">{{ $t('Meerwerk') }}</div></div>
          <table class="data-table">
            <tbody>
              <tr v-for="e in job.extras" :key="e.id">
                <td class="cell-primary">{{ e.description }}</td>
                <td :data-label="$t('Bedrag')" class="right num">{{ money(e.amount) }}</td>
                <td :data-label="$t('Status')"><span :class="['pill', extraPill[e.status] || 'pill-draft']">{{ $t(extraLabel[e.status] || e.status) }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Annuleren -->
        <div v-if="canCancel" class="card">
          <div class="card-header"><div class="card-title">{{ $t('Annuleren namens Fachora') }}</div></div>
          <form class="card-body fx-inline-form" @submit.prevent="cancelJob">
            <div class="form-group" style="flex:1;min-width:240px;margin:0;">
              <input type="text" v-model="cancelForm.reason" maxlength="1000" :placeholder="$t('Reden (optioneel, gaat naar klant en bedrijf)')">
              <div v-if="cancelForm.errors.reason" class="field-error">{{ cancelForm.errors.reason }}</div>
            </div>
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" :disabled="cancelForm.processing">{{ $t('Opdracht annuleren') }}</button>
          </form>
        </div>
      </div>

      <!-- Rechterkolom -->
      <div class="fx-side">
        <!-- Bedrijf -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Bedrijf') }}</div></div>
          <div class="card-body">
            <template v-if="provider">
              <div style="font-weight:600;"><Link :href="route('fachora.admin.providers.show', provider.id)">{{ provider.name }}</Link></div>
              <div class="sub">{{ provider.city }} · <FachoraBadge :verified="provider.verified" small /></div>
              <div v-if="provider.rating_count" class="sub">{{ num(provider.rating, 1) }} ★ · {{ $t(':n beoordelingen', { n: provider.rating_count }) }}</div>
            </template>
            <div v-else class="sub">{{ $t('Nog geen bedrijf gekozen.') }}</div>
          </div>
        </div>

        <!-- Geld -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Geld') }}</div></div>
          <div class="card-body">
            <dl class="fx-facts fx-facts--narrow">
              <dt>{{ $t('Afgesproken') }}</dt><dd>{{ job.agreed_amount !== null ? money(job.agreed_amount) : '—' }}<span v-if="job.agreed_type" class="sub"> · {{ $t(typeLabel[job.agreed_type] || job.agreed_type) }}</span></dd>
              <dt>{{ $t('Totaal') }}</dt><dd><b>{{ job.total ? money(job.total) : '—' }}</b></dd>
              <dt>{{ $t('Fee') }}</dt><dd>{{ job.fee_amount !== null && job.fee_amount !== undefined ? money(job.fee_amount) : '—' }}<span v-if="job.fee_percent" class="sub"> · {{ num(job.fee_percent, 0) }}%</span></dd>
              <dt>{{ $t('Uitbetaling') }}</dt><dd>{{ job.payout_amount !== null && job.payout_amount !== undefined ? money(job.payout_amount) : '—' }}<span v-if="job.payout_at" class="sub"> · {{ job.payout_at }}</span></dd>
              <dt>{{ $t('Protect') }}</dt>
              <dd>
                <template v-if="job.protect">
                  <span v-if="job.refunded" class="fx-warn">{{ $t('terugbetaald') }}</span>
                  <span v-else-if="job.paid" class="fx-ok">{{ $t('gereserveerd') }}<span v-if="job.paid_at" class="sub"> · {{ job.paid_at }}</span></span>
                  <span v-else class="sub">{{ $t('nog niet betaald') }}</span>
                  <div class="sub">{{ job.has_payment_intent ? $t('betaling bekend bij Stripe') : $t('geen betaling bij Stripe') }} · {{ job.has_transfer ? $t('overboeking gedaan') : $t('nog geen overboeking') }}</div>
                </template>
                <span v-else class="sub">{{ $t('nee, rechtstreeks') }}</span>
              </dd>
              <dt>{{ $t('Factuur') }}</dt><dd><Link v-if="job.invoice_id" :href="route('invoices.show', job.invoice_id)">#{{ job.invoice_id }} →</Link><span v-else class="sub">—</span></dd>
              <dt v-if="job.warranty_months">{{ $t('Garantie') }}</dt><dd v-if="job.warranty_months">{{ $t(':n maanden', { n: job.warranty_months }) }}</dd>
            </dl>
            <div v-if="!protect_enabled" class="sub" style="margin-top:8px;">{{ $t('Fachora Protect staat op deze omgeving uit.') }}</div>
          </div>
        </div>

        <!-- Afspraak en oplevering -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Uitvoering') }}</div></div>
          <div class="card-body">
            <dl class="fx-facts fx-facts--narrow">
              <dt>{{ $t('Afspraak') }}</dt><dd>{{ job.scheduled_label || '—' }}<span v-if="job.scheduled_window" class="sub"> · {{ job.scheduled_window }}</span></dd>
              <dt>{{ $t('Gestart') }}</dt><dd>{{ job.started_at || '—' }}</dd>
              <dt>{{ $t('Klaar gemeld') }}</dt><dd>{{ job.done_at || '—' }}<div v-if="job.done_note" class="sub">{{ job.done_note }}</div><div v-if="job.auto_accept_on" class="sub">{{ $t('automatisch akkoord op :date', { date: job.auto_accept_on }) }}</div></dd>
              <dt>{{ $t('Goedgekeurd') }}</dt><dd>{{ job.accepted_at || '—' }}<span v-if="job.accepted_by" class="sub"> · {{ $t(actorLabel[job.accepted_by] || job.accepted_by) }}</span></dd>
              <dt v-if="job.cancelled_by">{{ $t('Geannuleerd|opdracht') }}</dt><dd v-if="job.cancelled_by">{{ $t(actorLabel[job.cancelled_by] || job.cancelled_by) }}<div v-if="job.cancel_reason" class="sub">{{ job.cancel_reason }}</div></dd>
            </dl>
            <template v-if="job.review">
              <div class="lbl" style="margin-top:10px;">{{ $t('Beoordeling') }}</div>
              <div><span class="stars">{{ stars(job.review.rating) }}</span> {{ job.review.text }}</div>
              <div v-if="job.review.reply" class="sub">{{ $t('Reactie van het bedrijf') }}: {{ job.review.reply }}</div>
            </template>
          </div>
        </div>

        <!-- Verloop -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Verloop') }}</div></div>
          <div class="card-body">
            <ol v-if="job.events.length" class="fx-timeline">
              <li v-for="(e, i) in job.events" :key="i">
                <div class="fx-tl-at">{{ e.at }}<span v-if="e.actor"> · {{ $t(actorLabel[e.actor] || e.actor) }}</span></div>
                <div class="fx-tl-text">{{ e.description }}</div>
              </li>
            </ol>
            <div v-else class="sub">{{ $t('Nog geen gebeurtenissen.') }}</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; margin: 0 0 4px; }
.num { font-family: var(--font-mono); }
.fx-alert { padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 14px; }
.fx-alert--err { background: var(--brand-tint-2); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.fx-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 16px; align-items: start; }
.fx-main, .fx-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.fx-main .card, .fx-side .card { margin: 0; }
@media (max-width: 900px) { .fx-grid { grid-template-columns: 1fr; } }
.fx-card-warn { border-color: var(--warning-border); }
.fx-facts { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 6px 16px; margin: 0; font-size: 13.5px; }
.fx-facts--narrow { grid-template-columns: 110px minmax(0, 1fr); }
.fx-facts dt { color: var(--text-3); font-weight: 500; }
.fx-facts dd { margin: 0; word-break: break-word; }
@media (max-width: 600px) { .fx-facts { grid-template-columns: 1fr; gap: 2px 0; } .fx-facts dd { margin-bottom: 8px; } }
.fx-text { margin: 0 0 10px; line-height: 1.6; white-space: pre-line; font-size: 13.5px; }
.fx-list { margin: 0; padding-left: 18px; line-height: 1.6; font-size: 13.5px; }
.fx-photos { display: flex; flex-wrap: wrap; gap: 8px; }
.fx-photo { display: block; width: 96px; height: 96px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); background: var(--surface-2); }
.fx-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.fx-file { display: flex; align-items: center; justify-content: center; height: 100%; padding: 8px; font-size: 11px; text-align: center; word-break: break-all; color: var(--text-2); }
.fx-resolve { margin-top: 10px; border-top: 1px solid var(--border); padding-top: 12px; }
.fx-radio { display: flex; gap: 8px; align-items: center; font-size: 13.5px; margin-bottom: 6px; cursor: pointer; }
.fx-radio input { width: 16px; height: 16px; }
.fx-pick { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 6px; }
.fx-pick-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; font-size: 13.5px; }
.fx-pick-item input { width: 17px; height: 17px; padding: 0; flex: none; }
.fx-pick-item.on { border-color: var(--brand); }
.fx-pick-item.off { opacity: 0.55; cursor: not-allowed; }
.fx-pick-name { font-weight: 600; }
.fx-pick-item .sub { margin-left: auto; text-align: right; }
.fx-call-note { display: flex; gap: 6px; align-items: center; color: var(--brand); font-weight: 600; }
.fx-tel { font-family: var(--font-mono); font-weight: 600; }
.fx-inline-form { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
.fx-ok { color: var(--success); font-weight: 600; }
.fx-warn { color: var(--warning); font-weight: 600; }
.stars { color: var(--accent, #F4781F); letter-spacing: 1px; }
.fx-timeline { list-style: none; margin: 0; padding: 0 0 0 14px; border-left: 2px solid var(--border); display: flex; flex-direction: column; gap: 10px; }
.fx-timeline li { position: relative; font-size: 13px; }
.fx-timeline li::before { content: ''; position: absolute; left: -19px; top: 5px; width: 8px; height: 8px; border-radius: 50%; background: var(--brand); }
.fx-tl-at { font-size: 11px; color: var(--text-3); font-family: var(--font-mono); }
.fx-tl-text { line-height: 1.5; }
</style>
