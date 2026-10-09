<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { t } from '@/i18n';
import { computed, ref } from 'vue';

const props = defineProps({
  round: Object,
  requests: Array,
  candidates: { type: Array, default: () => [] },
  rejectDefault: { type: String, default: '' },
  sms: { type: Object, default: () => ({ available: false }) },
  ai: { type: Object, default: () => ({ available: false, auto: false }) },
});

const page = usePage();
const pageError = computed(() => (page.props.errors || {}).tender ?? null);
const smsError = computed(() => (page.props.errors || {}).sms ?? null);

const pillClass = { open: 'pill-sent', awarded: 'pill-paid', closed: 'pill-cancelled' };
const pillLabel = { open: 'Open', awarded: 'Gegund', closed: 'Gesloten' };
const reqClass = { sent: 'pill-sent', responded: 'pill-partial', declined: 'pill-cancelled', awarded: 'pill-paid', rejected: 'pill-draft' };
const reqLabel = { sent: 'Aangeschreven', responded: 'Prijs ontvangen', declined: 'Afgezegd', awarded: 'Gegund', rejected: 'Niet gegund' };

// De laagste prijs van wie nog meedoet; een afgewezen offerte telt niet mee.
const lowestId = computed(() => {
  const priced = props.requests.filter(r => r.price !== null && r.status === 'responded');
  if (!priced.length) return null;
  return priced.reduce((a, b) => (b.price < a.price ? b : a)).id;
});

const award = (r) => {
  if (confirm(t('Opdracht gunnen aan :name voor :price? De opdracht wordt gemaild; de andere bedrijven die een prijs gaven krijgen een nette afwijzing.', { name: r.name, price: eur(r.price) }))) {
    router.post(route('tenders.award', [props.round.id, r.id]), {}, { preserveScroll: true });
  }
};
// Een herinnering is een mail: altijd eerst vragen, zodat een misklik niets verstuurt.
const remind = (r) => {
  const question = r.email ? t('Herinnering mailen naar :name?', { name: r.name }) : t('Herinnering per sms sturen naar :name?', { name: r.name });
  if (confirm(question)) {
    router.post(route('tenders.remind', [props.round.id, r.id]), {}, { preserveScroll: true });
  }
};

/* ---------- Sms met de link naar de aanvraag ---------- */
const texting = ref(null);
const smsForm = useForm({ text: '' });
const openSms = (r) => { smsForm.clearErrors(); smsForm.text = r.sms_text || ''; texting.value = r; };
const sendSms = () => smsForm.post(route('tenders.sms', [props.round.id, texting.value.id]), {
  preserveScroll: true,
  onSuccess: () => { texting.value = null; },
});
// Boven 160 tekens gaat het bericht in delen van 153; een paar tekens tellen dubbel.
const smsLength = computed(() => [...smsForm.text].reduce((n, c) => n + ('^{}\\[~]|€'.includes(c) ? 2 : 1), 0));
const smsParts = computed(() => (smsLength.value <= 160 ? 1 : Math.ceil(smsLength.value / 153)));
const smsHasLink = computed(() => !texting.value?.sms_link || smsForm.text.includes(texting.value.sms_link));

/* ---------- Offerte afwijzen met een bericht ---------- */
const rejecting = ref(null);
const rejectForm = useForm({ message: '' });
const openReject = (r) => { rejectForm.clearErrors(); rejectForm.message = props.rejectDefault; rejecting.value = r; };
const reject = () => rejectForm.post(route('tenders.requests.reject', [props.round.id, rejecting.value.id]), {
  preserveScroll: true,
  onSuccess: () => { rejecting.value = null; },
});

/* ---------- Offertecheck: het advies van de AI per prijsopgave ---------- */
const verdictClass = { reasonable: 'pill-paid', high: 'pill-partial', low: 'pill-overdue', unclear: 'pill-draft' };
const openAdvice = ref({}); // id → true als het paneel open is
const toggleAdvice = (r) => { openAdvice.value = { ...openAdvice.value, [r.id]: !openAdvice.value[r.id] }; };
const reviewing = ref(null);
const reviewNow = (r) => {
  reviewing.value = r.id;
  router.post(route('tenders.requests.review', [props.round.id, r.id]), {}, { preserveScroll: true, onFinish: () => { reviewing.value = null; openAdvice.value = { ...openAdvice.value, [r.id]: true }; } });
};
const pct = (v) => (v === null || v === undefined ? null : (v > 0 ? '+' : '') + v.toLocaleString('nl-NL', { maximumFractionDigits: 1 }) + '%');
// Vragen mailen: de vragen uit de check staan klaar, de ondernemer past ze aan.
const asking = ref(null);
const questionForm = useForm({ questions: [] });
const openQuestions = (r) => { questionForm.clearErrors(); questionForm.questions = [...(r.review?.questions || []), ''].slice(0, 5); asking.value = r; };
const sendQuestions = () => questionForm
  .transform((d) => ({ questions: d.questions.map((q) => q.trim()).filter(Boolean) }))
  .post(route('tenders.requests.questions', [props.round.id, asking.value.id]), { preserveScroll: true, onSuccess: () => { asking.value = null; } });

/* ---------- Gunning intrekken ---------- */
const revoking = ref(false);
const revokeForm = useForm({ message: '', reopen: true });
const openRevoke = () => { revokeForm.clearErrors(); revokeForm.message = ''; revokeForm.reopen = true; revoking.value = true; };
const revoke = () => revokeForm.post(route('tenders.revoke', props.round.id), { preserveScroll: true, onSuccess: () => { revoking.value = false; } });

/* ---------- Afgezegd of per vergissing aangeschreven ---------- */
const markDeclined = (r) => {
  const reason = prompt(t(':name heeft afgezegd. Reden (mag leeg blijven):', { name: r.name }), t('Geen tijd'));
  if (reason === null) return;
  router.post(route('tenders.requests.decline', [props.round.id, r.id]), { reason }, { preserveScroll: true });
};
const removeRequest = (r) => {
  if (confirm(t(':name uit deze uitvraag halen? Het bedrijf krijgt geen bericht; een ingestuurde prijs gaat verloren.', { name: r.name }))) {
    router.delete(route('tenders.requests.destroy', [props.round.id, r.id]), { preserveScroll: true });
  }
};

/* ---------- Extra bedrijven aanschrijven ---------- */
const showInvite = ref(false);
const inviteForm = useForm({ subcontractor_ids: [] });
const openInvite = () => { inviteForm.clearErrors(); inviteForm.subcontractor_ids = []; showInvite.value = true; };
const toggleInvite = (id) => {
  const i = inviteForm.subcontractor_ids.indexOf(id);
  if (i >= 0) inviteForm.subcontractor_ids.splice(i, 1); else inviteForm.subcontractor_ids.push(id);
};
const invite = () => inviteForm.post(route('tenders.requests.store', props.round.id), {
  preserveScroll: true,
  onSuccess: () => { showInvite.value = false; },
});
const close = () => {
  if (confirm(t('Uitvraag sluiten zonder te gunnen? De bedrijven krijgen geen bericht.'))) {
    router.post(route('tenders.close', props.round.id), {}, { preserveScroll: true });
  }
};
/* ---------- Bijlagen bij de uitvraag ---------- */
const fileInput = ref(null);
const uploadForm = useForm({ files: [] });
const uploadErrors = computed(() => Object.entries(uploadForm.errors)
  .filter(([key]) => key === 'files' || key.startsWith('files.'))
  .map(([, message]) => message));
const uploadFiles = (event) => {
  const files = Array.from(event.target.files || []);
  if (!files.length) return;
  uploadForm.files = files;
  uploadForm.post(route('tenders.attachments.store', props.round.id), {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      uploadForm.reset();
      if (fileInput.value) fileInput.value.value = '';
    },
  });
};
const removeAttachment = (file) => {
  if (confirm(t('Bijlage ":name" verwijderen?', { name: file.filename }))) {
    router.delete(route('attachments.destroy', file.id), { preserveScroll: true });
  }
};

const copy = async (url) => { try { await navigator.clipboard.writeText(url); } catch (e) { /* stil */ } };
</script>

<template>
  <Head :title="$t('Uitvraag :title', { title: round.title })" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        {{ $t('Inkoop') }} / <Link :href="route('tenders.index')" style="color:var(--text-3);">{{ $t('Uitvragen') }}</Link> /
        <span class="breadcrumb-current">{{ round.title }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('tenders.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">‹ {{ $t('Terug') }}</Link>
        <h1 class="page-title">{{ round.title }} <span :class="['pill', pillClass[round.status]]" style="vertical-align:middle;margin-left:8px;">{{ $t(pillLabel[round.status]) }}</span></h1>
        <p class="page-subtitle">
          {{ round.package }}
          <template v-if="round.quote_number"> · <Link :href="route('quotes.show', round.quote_id)">{{ $t('Offerte :number', { number: round.quote_number }) }}</Link> · {{ round.customer_name }}</template>
          <template v-if="round.location"> · {{ round.location }}</template>
          <template v-if="round.start_week"> · {{ $t('start week :week', { week: round.start_week }) }}</template>
          · {{ $t('reageren vóór :date', { date: round.deadline_label }) }}
          <template v-if="round.awarded_to"> · {{ $t('gegund aan :name op :date', { name: round.awarded_to, date: round.awarded_at_label }) }}</template>
        </p>
      </div>
      <div class="page-actions">
        <button v-if="round.status === 'open'" class="btn btn-secondary btn-sm" @click="close">{{ $t('Sluiten zonder gunning') }}</button>
        <button v-if="round.status === 'awarded'" class="btn btn-secondary btn-sm" @click="openRevoke">{{ $t('Gunning intrekken') }}</button>
      </div>
    </div>

    <div v-if="pageError" class="field-error" style="margin-bottom:12px;">{{ pageError }}</div>

    <div class="kpis">
      <div class="kpi"><div class="lbl">{{ $t('Aangeschreven') }}</div><div class="val">{{ round.requested }}</div></div>
      <div class="kpi"><div class="lbl">{{ $t('Prijzen binnen') }}</div><div class="val">{{ round.responded }}<span v-if="round.declined" class="sub"> · {{ $t(':n afgezegd', { n: round.declined }) }}</span></div></div>
      <div class="kpi"><div class="lbl">{{ $t('Laagste prijs') }}</div><div class="val">{{ round.lowest !== null ? eur(round.lowest) : '—' }}</div></div>
      <div class="kpi"><div class="lbl">{{ $t('Gemiddeld') }}</div><div class="val">{{ round.average !== null ? eur(round.average) : '—' }}</div></div>
      <div class="kpi"><div class="lbl">{{ $t('Jouw calculatie') }}</div><div class="val">{{ round.budget !== null ? eur(round.budget) : '—' }}</div></div>
    </div>

    <div class="card">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="card-title">{{ $t('Vergelijking') }}</div>
        <button v-if="round.status === 'open' && candidates.length" class="btn btn-secondary btn-sm" @click="openInvite">
          {{ $t('Bedrijven toevoegen') }}
        </button>
      </div>
      <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Bedrijf') }}</th>
            <th>{{ $t('Status') }}</th>
            <th class="right">{{ $t('Prijs excl. btw') }}</th>
            <th class="right">{{ $t('t.o.v. calculatie') }}</th>
            <th>{{ $t('Beschikbaar') }}</th>
            <th>{{ $t('Geldig tot') }}</th>
            <th>{{ $t('Opmerkingen') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in requests" :key="r.id" :class="{ best: r.id === lowestId && round.status === 'open' }">
            <td class="cell-primary">
              {{ r.name }}
              <div class="sub">{{ [r.contact_name, r.city].filter(Boolean).join(' · ') }}</div>
              <div class="sub">{{ [r.email, r.phone].filter(Boolean).join(' · ') }}</div>
            </td>
            <td>
              <span :class="['pill', reqClass[r.status]]">{{ $t(reqLabel[r.status]) }}</span>
              <div class="sub">
                <template v-if="r.status === 'rejected' && r.rejected_at_label">{{ $t('afgewezen :date', { date: r.rejected_at_label }) }}</template>
                <template v-else-if="r.responded_at_label">{{ $t('gereageerd :date', { date: r.responded_at_label }) }}</template>
                <template v-else-if="r.opened_at_label">{{ $t('geopend :date', { date: r.opened_at_label }) }}</template>
                <template v-else-if="r.sent_at_label && r.email">{{ $t('gemaild :date', { date: r.sent_at_label }) }}</template>
                <template v-else-if="r.sent_at_label">{{ $t('aangeschreven :date', { date: r.sent_at_label }) }}</template>
                <template v-else-if="r.email">{{ $t('mail niet verstuurd') }}</template>
                <template v-else>{{ $t('sms niet verstuurd') }}</template>
                <template v-if="r.reminded_at_label"> · {{ $t('herinnerd :date', { date: r.reminded_at_label }) }}</template>
                <template v-if="r.sms_at_label"> · {{ $t('sms :date', { date: r.sms_at_label }) }}<span v-if="r.sms_delivery_label" :class="{ good: r.sms_delivery === 'delivered', bad: r.sms_delivery === 'failed' }"> · {{ r.sms_delivery_label }}</span></template>
              </div>
            </td>
            <td class="right num">
              {{ r.price !== null ? eur(r.price) : '—' }}
              <div v-if="r.review" class="advice-pill">
                <button type="button" :class="['pill', verdictClass[r.review.verdict]]" :title="r.review.headline" @click="toggleAdvice(r)">{{ r.review.verdict_label }}</button>
              </div>
              <div v-else-if="r.price !== null && ai.available && ['responded', 'awarded'].includes(r.status)" class="sub">
                <button type="button" class="lnk-btn" :disabled="reviewing === r.id" @click="reviewNow(r)">{{ reviewing === r.id ? $t('Bezig…') : $t('Offertecheck') }}</button>
                <span v-if="ai.auto && !r.review_error"> · {{ $t('komt vanzelf') }}</span>
              </div>
            </td>
            <td class="right num" :class="{ good: r.delta !== null && r.delta <= 0, bad: r.delta !== null && r.delta > 0 }">
              {{ r.delta !== null ? (r.delta > 0 ? '+' : '') + eur(r.delta) : '—' }}
            </td>
            <td>{{ r.available_week ? $t('week :week', { week: r.available_week }) : '—' }}</td>
            <td>{{ r.valid_until_label || '—' }}</td>
            <td class="remarks">
              <template v-if="r.status === 'declined'">{{ r.decline_reason || $t('Geen reden opgegeven') }}</template>
              <template v-else>{{ r.remarks || '—' }}</template>
              <div v-if="r.attachment_url"><a :href="r.attachment_url" target="_blank" class="lnk">📎 {{ r.attachment_name }}</a></div>
              <div v-if="r.status === 'rejected' && r.reject_message" class="sent-note"><b>{{ $t('Jouw bericht bij de afwijzing:') }}</b> {{ r.reject_message }}</div>
            </td>
            <td class="right actions">
              <button v-if="round.status === 'open' && r.status === 'responded'" class="btn btn-primary btn-sm" @click="award(r)">{{ $t('Gunnen') }}</button>
              <button v-if="round.status === 'open' && r.status === 'responded'" class="btn btn-secondary btn-sm" :title="$t('De offerte afwijzen en het bedrijf een vriendelijk bericht mailen. De uitvraag blijft open.')" @click="openReject(r)">{{ $t('Afwijzen') }}</button>
              <button v-if="round.status === 'open' && r.status === 'sent'" class="btn btn-secondary btn-sm" @click="remind(r)">{{ $t('Herinneren') }}</button>
              <button v-if="sms.available && r.sms_text" class="btn btn-secondary btn-sm" :title="$t('Een sms met de link naar de aanvraag, naar :number', { number: r.mobile })" @click="openSms(r)">{{ $t('Sms') }}</button>
              <button v-if="round.status === 'open' && ['sent', 'responded'].includes(r.status)" class="btn btn-ghost btn-sm" :title="$t('Het bedrijf heeft afgezegd, bijvoorbeeld per telefoon of mail. Er gaat geen bericht uit.')" @click="markDeclined(r)">{{ $t('Afgezegd') }}</button>
              <button v-if="round.status === 'open' && r.status !== 'awarded'" class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" :title="$t('Bedrijf uit deze uitvraag halen. Er gaat geen bericht uit.')" @click="removeRequest(r)">{{ $t('Verwijder') }}</button>
              <button class="btn btn-ghost btn-sm" :title="$t('Link naar het reactieformulier kopiëren (voor als je zelf belt)')" @click="copy(r.response_url)">🔗</button>
            </td>
          </tr>
          <!-- Het advies van de offertecheck, uitklapbaar onder de rij -->
          <template v-for="r in requests" :key="'advice-' + r.id">
            <tr v-if="r.review && openAdvice[r.id]" class="advice-row">
              <td colspan="8">
                <div class="advice">
                  <div class="advice-head">
                    <span :class="['pill', verdictClass[r.review.verdict]]">{{ r.review.verdict_label }}</span>
                    <b>{{ r.review.headline }}</b>
                    <span class="sub">{{ $t('zekerheid: :c', { c: $t(r.review.confidence === 'high' ? 'hoog' : r.review.confidence === 'medium' ? 'gemiddeld' : 'laag') }) }} · {{ r.review.reviewed_at_label }}</span>
                    <button type="button" class="btn btn-ghost btn-sm" style="margin-left:auto;" @click="toggleAdvice(r)">✕</button>
                  </div>
                  <p class="advice-text">{{ r.review.summary }}</p>
                  <div class="advice-grid">
                    <div>
                      <div class="advice-label">{{ $t('Cijfers') }}</div>
                      <ul class="advice-list">
                        <li v-if="r.review.facts?.budget !== null && r.review.facts?.budget !== undefined">{{ $t('t.o.v. jouw calculatie: :pct', { pct: pct(r.review.facts.vs_budget_pct) }) }}</li>
                        <li v-if="r.review.facts?.of > 1">{{ $t('positie :rank van :of', { rank: r.review.facts.rank, of: r.review.facts.of }) }}<template v-if="r.review.facts.vs_lowest_pct"> · {{ $t(':pct t.o.v. de laagste', { pct: pct(r.review.facts.vs_lowest_pct) }) }}</template></li>
                        <li v-if="r.review.facts?.history">{{ $t('eerdere prijzen: mediaan :m (:n keer) · :pct', { m: eur(r.review.facts.history.median), n: r.review.facts.history.n, pct: pct(r.review.facts.vs_history_pct) }) }}</li>
                        <li v-if="r.review.market_estimate?.low && r.review.market_estimate?.high">{{ $t('marktindicatie :low – :high', { low: eur(r.review.market_estimate.low), high: eur(r.review.market_estimate.high) }) }}</li>
                        <li v-if="r.review.comparison">{{ r.review.comparison }}</li>
                      </ul>
                      <div v-if="r.review.price_basis" class="sub" style="margin-top:6px;"><b>{{ $t('Opbouw') }}:</b> {{ r.review.price_basis }}</div>
                    </div>
                    <div v-if="r.review.included?.length || r.review.excluded?.length">
                      <div v-if="r.review.included?.length" class="advice-label">{{ $t('Inbegrepen') }}</div>
                      <ul v-if="r.review.included?.length" class="advice-list"><li v-for="(x, i) in r.review.included" :key="'i' + i">{{ x }}</li></ul>
                      <div v-if="r.review.excluded?.length" class="advice-label">{{ $t('Niet inbegrepen / voorbehoud') }}</div>
                      <ul v-if="r.review.excluded?.length" class="advice-list warn"><li v-for="(x, i) in r.review.excluded" :key="'e' + i">{{ x }}</li></ul>
                    </div>
                    <div v-if="r.review.scope_gaps?.length || r.review.questions?.length">
                      <div v-if="r.review.scope_gaps?.length" class="advice-label">{{ $t('Niet gedekt uit de aanvraag') }}</div>
                      <ul v-if="r.review.scope_gaps?.length" class="advice-list warn"><li v-for="(x, i) in r.review.scope_gaps" :key="'g' + i">{{ x }}</li></ul>
                      <div v-if="r.review.questions?.length" class="advice-label">{{ $t('Vragen aan het bedrijf') }}</div>
                      <ol v-if="r.review.questions?.length" class="advice-list"><li v-for="(x, i) in r.review.questions" :key="'q' + i">{{ x }}</li></ol>
                      <div v-if="r.review.questions_sent" class="sub">{{ $t('vragen gemaild op :date', { date: r.review.questions_sent.at }) }}</div>
                    </div>
                  </div>
                  <div class="advice-box"><b>{{ $t('Advies') }}:</b> {{ r.review.advice }}</div>
                  <div v-if="r.review.market_estimate?.basis" class="sub" style="margin-top:6px;">{{ r.review.market_estimate.basis }}</div>
                  <div class="advice-actions">
                    <button v-if="round.status === 'open' && r.status === 'responded' && r.email" class="btn btn-secondary btn-sm" @click="openQuestions(r)">{{ $t('Vragen mailen') }}</button>
                    <button v-if="ai.available" class="btn btn-ghost btn-sm" :disabled="reviewing === r.id" @click="reviewNow(r)">{{ reviewing === r.id ? $t('Bezig…') : $t('Opnieuw beoordelen') }}</button>
                    <span class="sub">{{ $t('Een beoordeling door AI: een tweede paar ogen, jij beslist.') }}</span>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
      </div>
      <div v-if="sms.available && sms.free" class="card-body att-note" style="margin-top:0;">
        {{ $t('Sms: een bedrijf met een mobiel nummer kun je een sms sturen met de link naar de aanvraag. Afzender :sender; deze maand nog :n te versturen.', { sender: sms.sender, n: sms.remaining }) }}
      </div>
      <div v-else-if="sms.available" class="card-body att-note" style="margin-top:0;">
        {{ $t('Sms: een bedrijf met een mobiel nummer kun je een sms sturen met de link naar de aanvraag. Afzender :sender; tegoed: :n sms\'en.', { sender: sms.sender, n: sms.remaining }) }}
        <Link v-if="sms.buy_url" :href="sms.buy_url" class="lnk">{{ $t('Tegoed kopen') }}</Link>
      </div>
      <div v-else-if="sms.enabled" class="card-body att-note" style="margin-top:0;">
        {{ $t('Sms: je hebt geen tegoed. Met tegoed stuur je een bedrijf met een mobiel nummer een sms met de link naar de aanvraag.') }}
        <Link v-if="sms.buy_url" :href="sms.buy_url" class="lnk">{{ $t('Tegoed kopen') }}</Link>
      </div>
      <div v-else-if="sms.missing" class="card-body att-note" style="margin-top:0;">
        {{ $t('Sms staat nog uit: in de omgeving ontbreekt :name.', { name: sms.missing }) }}
      </div>
    </div>

    <div class="card" style="margin-top:16px;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="card-title">{{ $t('Bijlagen voor de bedrijven') }}</div>
        <template v-if="round.status === 'open'">
          <input ref="fileInput" type="file" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="display:none" @change="uploadFiles">
          <button class="btn btn-secondary btn-sm" :disabled="uploadForm.processing" @click="fileInput?.click()">
            {{ uploadForm.processing ? $t('Bezig met uploaden…') : $t('Bestand toevoegen') }}
          </button>
        </template>
      </div>
      <div class="card-body">
        <div v-for="(message, i) in uploadErrors" :key="i" class="field-error" style="margin-bottom:8px;">{{ message }}</div>
        <div v-if="!round.attachments.length" class="att-none">
          {{ $t('Geen bijlagen. Voeg een tekening, bestek of foto’s toe zodat de bedrijven precies weten wat er gemaakt moet worden.') }}
        </div>
        <div v-for="file in round.attachments" :key="file.id" class="att-line">
          <a :href="route('attachments.show', file.id)" target="_blank" class="lnk">📎 {{ file.filename }}</a>
          <span class="sub">{{ file.size_formatted }}</span>
          <span class="att-actions">
            <a :href="route('attachments.download', file.id)" class="btn btn-ghost btn-sm">{{ $t('Download') }}</a>
            <button v-if="round.status === 'open'" class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="removeAttachment(file)">{{ $t('Verwijder') }}</button>
          </span>
        </div>
        <div v-if="round.status === 'open'" class="att-note">
          {{ $t('Een bijlage die je nu toevoegt staat meteen op de reactiepagina van elk bedrijf en gaat mee met herinneringen. Er gaat geen nieuwe mail uit.') }}
        </div>
      </div>
    </div>

    <div v-if="round.description" class="card" style="margin-top:16px;">
      <div class="card-header"><div class="card-title">{{ $t('Omschrijving in de mail') }}</div></div>
      <div class="card-body" style="white-space:pre-wrap;font-size:13.5px;line-height:1.6;color:var(--text-2);">{{ round.description }}</div>
      <div v-if="round.signature" class="card-body sig-note">
        {{ $t('De ondertekening die in je tekst stond, staat onderaan de mail en niet meer in de omschrijving. Dat hoeft niet: zonder eigen ondertekening sluit de mail af met je bedrijfsgegevens.') }}
      </div>
    </div>

    <!-- Offerte afwijzen met een bericht -->
    <!-- Gunning intrekken -->
    <div v-if="revoking" class="modal-overlay" @click.self="revoking = false">
      <div class="modal" style="max-width:560px;">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Gunning aan :name intrekken', { name: round.awarded_to }) }}</div>
          <button class="btn btn-ghost btn-sm" @click="revoking = false">✕</button>
        </div>
        <div class="modal-body">
          <p class="sub" style="margin:0 0 12px;line-height:1.6;">{{ $t('Het bedrijf krijgt een nette mail dat de opdracht is ingetrokken, met je toelichting erbij. De uitvraag gaat weer open: je kunt een ander bedrijf gunnen, bedrijven toevoegen of sluiten.') }}</p>
          <div class="form-group">
            <label>{{ $t('Toelichting') }} <span class="sub">{{ $t('(optioneel, gaat mee in de mail)') }}</span></label>
            <textarea v-model="revokeForm.message" rows="4" maxlength="2000" :placeholder="$t('Bijv. de opdrachtgever heeft het werk uitgesteld')"></textarea>
            <div v-if="revokeForm.errors.message" class="field-error">{{ revokeForm.errors.message }}</div>
          </div>
          <label class="check" style="display:flex;gap:8px;align-items:flex-start;font-size:13px;line-height:1.5;">
            <input type="checkbox" v-model="revokeForm.reopen" style="margin-top:3px;width:16px;height:16px;padding:0;">
            <span>{{ $t('Bedrijven die bij deze gunning een afwijzing kregen, doen weer mee. Zij krijgen nu geen bericht; bij een nieuwe gunning gewoon de opdrachtmail.') }}</span>
          </label>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="revoking = false">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="revokeForm.processing" @click="revoke">{{ $t('Intrekken en mailen') }}</button>
        </div>
      </div>
    </div>

    <!-- Vragen mailen aan het bedrijf -->
    <div v-if="asking" class="modal-overlay" @click.self="asking = null">
      <div class="modal" style="max-width:600px;">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Vragen mailen aan :name', { name: asking.name }) }}</div>
          <button class="btn btn-ghost btn-sm" @click="asking = null">✕</button>
        </div>
        <div class="modal-body">
          <p class="sub" style="margin:0 0 12px;line-height:1.6;">{{ $t('Het bedrijf krijgt de vragen per mail, met een knop om zijn prijsopgave aan te vullen. Pas de vragen aan of haal ze weg; lege regels gaan niet mee.') }}</p>
          <div v-for="(q, i) in questionForm.questions" :key="i" class="form-group" style="display:flex;gap:8px;align-items:flex-start;">
            <span class="sub" style="padding-top:9px;width:18px;">{{ i + 1 }}.</span>
            <textarea v-model="questionForm.questions[i]" rows="2" maxlength="500" style="flex:1;"></textarea>
          </div>
          <button v-if="questionForm.questions.length < 5" type="button" class="lnk-btn" @click="questionForm.questions.push('')">+ {{ $t('Vraag toevoegen') }}</button>
          <div v-if="questionForm.errors.questions" class="field-error">{{ questionForm.errors.questions }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="asking = null">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="questionForm.processing || !questionForm.questions.some((q) => q.trim())" @click="sendQuestions">{{ $t('Vragen mailen') }}</button>
        </div>
      </div>
    </div>

    <div v-if="rejecting" class="modal-overlay" @click.self="rejecting = null">
      <div class="modal" style="max-width:560px;">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Offerte van :name afwijzen', { name: rejecting.name }) }}</div>
          <button class="btn btn-ghost btn-sm" @click="rejecting = null">✕</button>
        </div>
        <div class="modal-body">
          <p style="font-size:13px;color:var(--text-3);margin:0 0 14px;line-height:1.6;">
            {{ $t(':name krijgt dit bericht per mail. De aanhef en de afsluiting met je gegevens staan er al omheen. De uitvraag blijft open voor de andere bedrijven.', { name: rejecting.name }) }}
          </p>
          <div v-if="pageError" class="field-error" style="margin-bottom:12px;">{{ pageError }}</div>
          <div class="form-group">
            <label>{{ $t('Bericht') }}</label>
            <textarea v-model="rejectForm.message" rows="7" maxlength="2000"></textarea>
            <div v-if="rejectForm.errors.message" class="field-error">{{ rejectForm.errors.message }}</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="rejecting = null">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="rejectForm.processing" @click="reject">
            {{ rejectForm.processing ? $t('Bezig met versturen…') : $t('Afwijzen en mailen') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Sms met de link naar de aanvraag -->
    <div v-if="texting" class="modal-overlay" @click.self="texting = null">
      <div class="modal" style="max-width:560px;">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Sms naar :name', { name: texting.name }) }}</div>
          <button class="btn btn-ghost btn-sm" @click="texting = null">✕</button>
        </div>
        <div class="modal-body">
          <p style="font-size:13px;color:var(--text-3);margin:0 0 14px;line-height:1.6;">
            {{ $t('Naar :number, met als afzender :sender. De link leidt naar de aanvraag; het bedrijf geeft daar zijn prijs door.', { number: texting.mobile, sender: sms.sender }) }}
          </p>
          <div v-if="smsError" class="field-error" style="margin-bottom:12px;">{{ smsError }}</div>
          <div class="form-group">
            <label>{{ $t('Bericht') }}</label>
            <textarea v-model="smsForm.text" rows="5" maxlength="500"></textarea>
            <div v-if="smsForm.errors.text" class="field-error">{{ smsForm.errors.text }}</div>
            <div class="sub" style="margin-top:6px;">
              {{ $t(':n tekens, :parts sms', { n: smsLength, parts: smsParts }) }}
              <template v-if="smsParts > sms.max_segments"> · <span class="bad">{{ $t('te lang, maak het korter') }}</span></template>
              <template v-if="!smsHasLink"> · <span class="bad">{{ $t('de link ontbreekt') }}</span></template>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="texting = null">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="smsForm.processing || !smsForm.text.trim() || !smsHasLink || smsParts > sms.max_segments" @click="sendSms">
            {{ smsForm.processing ? $t('Bezig met versturen…') : $t('Sms versturen') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Extra bedrijven aanschrijven -->
    <div v-if="showInvite" class="modal-overlay" @click.self="showInvite = false">
      <div class="modal" style="max-width:640px;">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Bedrijven toevoegen aan deze uitvraag') }}</div>
          <button class="btn btn-ghost btn-sm" @click="showInvite = false">✕</button>
        </div>
        <div class="modal-body">
          <p style="font-size:13px;color:var(--text-3);margin:0 0 14px;line-height:1.6;">
            {{ $t('Deze bedrijven staan in de pool van :package en zijn nog niet aangeschreven. Ze krijgen dezelfde aanvraag met dezelfde bijlagen.', { package: round.package }) }}
          </p>
          <div v-if="pageError" class="field-error" style="margin-bottom:12px;">{{ pageError }}</div>
          <div class="inv-list">
            <label v-for="c in candidates" :key="c.id" class="inv-item" :class="{ off: !c.has_email && !c.by_sms }">
              <input type="checkbox" :checked="inviteForm.subcontractor_ids.includes(c.id)" :disabled="!c.has_email && !c.by_sms" @change="toggleInvite(c.id)">
              <span class="inv-name">{{ c.name }}</span>
              <span class="sub">{{ c.city || '' }}<template v-if="c.by_sms"> · {{ $t('per sms') }}</template><template v-else-if="!c.has_email"> · {{ $t('geen e-mailadres') }}</template></span>
            </label>
          </div>
          <div v-if="inviteForm.errors.subcontractor_ids" class="field-error">{{ inviteForm.errors.subcontractor_ids }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="showInvite = false">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="inviteForm.processing || !inviteForm.subcontractor_ids.length" @click="invite">
            {{ $t('Versturen naar :n bedrijven', { n: inviteForm.subcontractor_ids.length }) }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; }
.kpi .lbl { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; }
.kpi .val { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin-top: 4px; }
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.best td { background: var(--success-bg, rgba(22,163,74,0.06)); }
.good { color: var(--success); font-weight: 600; }
.bad { color: var(--warning); font-weight: 600; }
.remarks { max-width: 280px; font-size: 13px; white-space: pre-wrap; }
.sent-note { margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border); font-size: 12.5px; color: var(--text-3); }
/* De knoppen mogen over twee regels; met nowrap liep de tabel op een laptop
   uit de kaart en vielen "Afgezegd" en "Verwijder" buiten beeld. */
.actions { white-space: normal; min-width: 200px; }
.actions .btn { margin: 2px 0 2px 4px; }
/* En als het dan nóg niet past: zijwaarts scrollen in plaats van afsnijden. */
.table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.lnk { color: var(--brand); }
.lnk-btn { background: none; border: 0; padding: 0; cursor: pointer; font: inherit; font-size: 12px; color: var(--brand); font-weight: 600; }
.lnk-btn:disabled { color: var(--text-3); cursor: default; }
.advice-pill { margin-top: 4px; }
.advice-pill .pill { cursor: pointer; border: 0; font: inherit; font-size: 11px; white-space: nowrap; }
.advice-row td { background: var(--surface-2); padding: 0 14px 14px; }
.advice { border: 1px solid var(--border); border-radius: 10px; background: var(--surface); padding: 12px 14px; font-size: 13px; }
.advice-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
.advice-text { margin: 0 0 10px; line-height: 1.55; color: var(--text-2); white-space: normal; }
.advice-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
.advice-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; margin: 6px 0 4px; }
.advice-list { margin: 0; padding-left: 18px; line-height: 1.5; }
.advice-list.warn li { color: var(--warning); }
.advice-box { margin-top: 12px; padding: 10px 12px; background: var(--brand-tint); border-left: 3px solid var(--brand); border-radius: 6px; line-height: 1.55; }
.advice-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
.sig-note { font-size: 12.5px; color: var(--text-3); line-height: 1.6; border-top: 1px solid var(--border); }
.inv-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 6px; }
.inv-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; font-size: 13.5px; }
.inv-item input { width: 17px; height: 17px; padding: 0; flex: none; }
.inv-item .sub { margin-left: auto; white-space: nowrap; }
.inv-item.off { opacity: 0.55; cursor: not-allowed; }
.inv-name { font-weight: 600; color: var(--text); }
.att-line { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 13.5px; }
.att-line:last-of-type { border-bottom: none; }
.att-actions { margin-left: auto; white-space: nowrap; }
.att-none, .att-note { font-size: 13px; color: var(--text-3); line-height: 1.6; }
.att-note { margin-top: 10px; }
</style>
