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
});

const page = usePage();
const pageError = computed(() => (page.props.errors || {}).tender ?? null);

const pillClass = { open: 'pill-sent', awarded: 'pill-paid', closed: 'pill-cancelled' };
const pillLabel = { open: 'Open', awarded: 'Gegund', closed: 'Gesloten' };
const reqClass = { sent: 'pill-sent', responded: 'pill-partial', declined: 'pill-cancelled', awarded: 'pill-paid', rejected: 'pill-draft' };
const reqLabel = { sent: 'Aangeschreven', responded: 'Prijs ontvangen', declined: 'Afgezegd', awarded: 'Gegund', rejected: 'Niet gegund' };

const lowestId = computed(() => {
  const priced = props.requests.filter(r => r.price !== null);
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
  if (confirm(t('Herinnering mailen naar :name?', { name: r.name }))) {
    router.post(route('tenders.remind', [props.round.id, r.id]), {}, { preserveScroll: true });
  }
};

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
              <div class="sub">{{ r.email }}<template v-if="r.phone"> · {{ r.phone }}</template></div>
            </td>
            <td>
              <span :class="['pill', reqClass[r.status]]">{{ $t(reqLabel[r.status]) }}</span>
              <div class="sub">
                <template v-if="r.responded_at_label">{{ $t('gereageerd :date', { date: r.responded_at_label }) }}</template>
                <template v-else-if="r.opened_at_label">{{ $t('geopend :date', { date: r.opened_at_label }) }}</template>
                <template v-else-if="r.sent_at_label">{{ $t('gemaild :date', { date: r.sent_at_label }) }}</template>
                <template v-else>{{ $t('mail niet verstuurd') }}</template>
                <template v-if="r.reminded_at_label"> · {{ $t('herinnerd :date', { date: r.reminded_at_label }) }}</template>
              </div>
            </td>
            <td class="right num">{{ r.price !== null ? eur(r.price) : '—' }}</td>
            <td class="right num" :class="{ good: r.delta !== null && r.delta <= 0, bad: r.delta !== null && r.delta > 0 }">
              {{ r.delta !== null ? (r.delta > 0 ? '+' : '') + eur(r.delta) : '—' }}
            </td>
            <td>{{ r.available_week ? $t('week :week', { week: r.available_week }) : '—' }}</td>
            <td>{{ r.valid_until_label || '—' }}</td>
            <td class="remarks">
              <template v-if="r.status === 'declined'">{{ r.decline_reason || $t('Geen reden opgegeven') }}</template>
              <template v-else>{{ r.remarks || '—' }}</template>
              <div v-if="r.attachment_url"><a :href="r.attachment_url" target="_blank" class="lnk">📎 {{ r.attachment_name }}</a></div>
            </td>
            <td class="right actions">
              <button v-if="round.status === 'open' && r.status === 'responded'" class="btn btn-primary btn-sm" @click="award(r)">{{ $t('Gunnen') }}</button>
              <button v-if="round.status === 'open' && r.status === 'sent'" class="btn btn-secondary btn-sm" @click="remind(r)">{{ $t('Herinneren') }}</button>
              <button v-if="round.status === 'open' && ['sent', 'responded'].includes(r.status)" class="btn btn-ghost btn-sm" :title="$t('Het bedrijf heeft afgezegd, bijvoorbeeld per telefoon of mail. Er gaat geen bericht uit.')" @click="markDeclined(r)">{{ $t('Afgezegd') }}</button>
              <button v-if="round.status === 'open' && r.status !== 'awarded'" class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" :title="$t('Bedrijf uit deze uitvraag halen. Er gaat geen bericht uit.')" @click="removeRequest(r)">{{ $t('Verwijder') }}</button>
              <button class="btn btn-ghost btn-sm" :title="$t('Link naar het reactieformulier kopiëren (voor als je zelf belt)')" @click="copy(r.response_url)">🔗</button>
            </td>
          </tr>
        </tbody>
      </table>
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
            <label v-for="c in candidates" :key="c.id" class="inv-item" :class="{ off: !c.has_email }">
              <input type="checkbox" :checked="inviteForm.subcontractor_ids.includes(c.id)" :disabled="!c.has_email" @change="toggleInvite(c.id)">
              <span class="inv-name">{{ c.name }}</span>
              <span class="sub">{{ c.city || '' }}<template v-if="!c.has_email"> · {{ $t('geen e-mailadres') }}</template></span>
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
.actions { white-space: nowrap; }
.lnk { color: var(--brand); }
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
