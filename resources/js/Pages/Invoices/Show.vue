<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { eur, fmtDate, num } from '@/format.js';
import { t } from '@/i18n';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  invoice: Object,
  company: Object,
  peppol: { type: Object, default: null },
});

// Markt (nl/pl): betaalmethode-label, incassopartner en de Poolse windykacja/KSeF-blokken.
const market = usePage().props.market;

// Voor v-html-teksten met opmaak: dynamische waarden veilig invoegen.
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/* ---------- Peppol ---------- */
const sendPeppol = () => {
  if (confirm(t('Factuur via het Peppol-netwerk afleveren in het boekhoudpakket van de klant?'))) {
    router.post(route('invoices.peppol.send', props.invoice.id), {}, { preserveScroll: true });
  }
};

const showPaymentModal = ref(false);
const showRecurringModal = ref(false);
const showCreditModal = ref(false);

// Foutmeldingen die niet bij één invoerveld horen (incasso, creditnota, UBL…).
const page = usePage();
const pageError = computed(() => {
  const e = page.props.errors || {};
  return e.incasso || e.credit || e.reminder || e.thanks || e.ubl || e.status || e.delete || e.peppol || e.windykacja || e.ksef || null;
});

/* ---------- Creditnota ---------- */
const canCredit = computed(() =>
  !props.invoice.is_credit && ['sent', 'partial', 'overdue', 'paid', 'incasso'].includes(props.invoice.status)
);

const creditForm = useForm({ kind: 'full' });

const createCredit = () => {
  creditForm.post(route('invoices.credit.store', props.invoice.id), {
    onSuccess: () => { showCreditModal.value = false; },
  });
};

const finalizeCredit = () => {
  if (confirm(t('Creditnota definitief maken? Er wordt een definitief creditnotanummer toegekend.'))) {
    router.post(route('invoices.credit.finalize', props.invoice.id));
  }
};

/* ---------- Incasso ---------- */
const phaseLabels = {
  minnelijk: t('Minnelijk traject'),
  gerechtelijk: t('Gerechtelijke procedure'),
  executie: t('Executie'),
};

// Alleen in markten met een incassopartner (Polen niet: daar verkoop je de factuur).
const canIncasso = computed(() =>
  !!market.incasso_partner && !props.invoice.is_credit && ['sent', 'partial', 'overdue'].includes(props.invoice.status)
);

/* ---------- Handmatige herinnering ---------- */
const canRemind = computed(() =>
  !props.invoice.is_credit
  && ['sent', 'partial', 'overdue'].includes(props.invoice.status)
  && !!props.invoice.customer_email
);

const sendReminder = () => {
  if (confirm(t('Herinnering sturen naar :email?', { email: props.invoice.customer_email }))) {
    router.post(route('invoices.remind', props.invoice.id), {}, { preserveScroll: true });
  }
};

/* ---------- Bedankmail na betaling ---------- */
const canThank = computed(() =>
  !props.invoice.is_credit
  && props.invoice.status === 'paid'
  && !!props.invoice.customer_email
);

const sendThanks = () => {
  const again = props.invoice.thanks_sent_at_label
    ? t('Er is al een bedankmail verstuurd op :date.', { date: props.invoice.thanks_sent_at_label }) + '\n\n'
    : '';
  if (confirm(again + t('Bedankmail sturen naar :email? De factuur gaat mee als PDF met het stempel BETAALD.', { email: props.invoice.customer_email }))) {
    router.post(route('invoices.thank', props.invoice.id), {}, { preserveScroll: true });
  }
};

const sendToIncasso = () => {
  const msg = t('Factuur :number overdragen aan :partner?', { number: props.invoice.number, partner: market.incasso_partner }) + '\n\n'
    + t('Het volledige dossier (factuur, betalingen en het herinneringsverloop) wordt per e-mail verstuurd. Dit kun je niet ongedaan maken.');
  if (confirm(msg)) {
    router.post(route('incasso.send', props.invoice.id), {}, { preserveScroll: true });
  }
};

/* ---------- Bijlagen ---------- */
const fileInput = ref(null);
const uploadForm = useForm({ files: [], for_customer: false });

const uploadFiles = (event) => {
  const files = Array.from(event.target.files || []);
  if (!files.length) return;

  uploadForm.files = files;
  uploadForm.for_customer = uploadForCustomer.value;
  uploadForm.post(route('invoices.attachments.store', props.invoice.id), {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      uploadForm.reset();
      if (fileInput.value) fileInput.value.value = '';
    },
  });
};

const removeAttachment = (att) => {
  if (confirm(t('Bijlage ":name" verwijderen?', { name: att.filename }))) {
    router.delete(route('attachments.destroy', att.id), { preserveScroll: true });
  }
};

// Nieuwe uploads standaard intern; via het vinkje gaan ze naar de klant.
const uploadForCustomer = ref(false);

const toggleForCustomer = (att) => {
  router.patch(route('attachments.update', att.id), { for_customer: !att.for_customer }, { preserveScroll: true });
};

// Standaard eerstvolgende factuurdatum: één maand na de factuurdatum.
const suggestNextDate = () => {
  const base = new Date(props.invoice.invoice_date);
  const anchorDay = base.getDate();
  const next = new Date(base.getFullYear(), base.getMonth() + 1, 1);
  const daysInMonth = new Date(next.getFullYear(), next.getMonth() + 1, 0).getDate();
  next.setDate(Math.min(anchorDay, daysInMonth));
  const today = new Date();
  const pick = next > today ? next : today;
  return `${pick.getFullYear()}-${String(pick.getMonth() + 1).padStart(2, '0')}-${String(pick.getDate()).padStart(2, '0')}`;
};

const recurringForm = useForm({
  frequency: 'monthly',
  next_run_on: suggestNextDate(),
  end_date: '',
  auto_send: false,
});

const createRecurring = () => {
  recurringForm
    .transform((data) => ({ ...data, end_date: data.end_date || null }))
    .post(route('invoices.recurring.store', props.invoice.id), {
      onSuccess: () => { showRecurringModal.value = false; },
    });
};

const paymentForm = useForm({
  kind: 'payment',
  amount: props.invoice.remaining,
  paid_on: new Date().toISOString().slice(0, 10),
  method: 'bank_transfer',
  reference: '',
  notes: '',
  // Bedankmail: voorgevinkt als de instelling aanstaat (Instellingen → E-mailteksten).
  send_thanks: !!props.company?.thanks_mail_enabled && !!props.invoice.customer_email,
});

// Bij afboeken is het restbedrag vrijwel altijd wat je wilt wegboeken.
watch(() => paymentForm.kind, (kind) => {
  if (kind === 'write_off') paymentForm.amount = props.invoice.remaining;
});

// Een bedankje hoort pas bij een volledige betaling — niet bij een deelbetaling.
const isFullPayment = computed(() =>
  paymentForm.kind === 'payment' && Number(paymentForm.amount) >= Number(props.invoice.remaining) - 0.005
);

const recordPayment = () => {
  paymentForm.post(route('invoices.payments.store', props.invoice.id), {
    onSuccess: () => {
      showPaymentModal.value = false;
      paymentForm.reset();
    },
  });
};

const payMethodLabels = {
  bank_transfer: t('Bankoverschrijving'),
  ideal: market.online_payment_label,
  cash: t('Contant'),
  card: t('Pinpas / creditcard'),
  other: t('Anders'),
};

const sendInvoice = () => {
  router.post(route('invoices.send', props.invoice.id));
};

/* ---------- Voorvertoning (PDF) ---------- */
const viewMode = ref('regels');

/* ---------- Dupliceren ---------- */
const duplicateInvoice = () => {
  if (confirm(t('Kopie maken van deze factuur als nieuw concept?'))) {
    router.post(route('invoices.duplicate', props.invoice.id));
  }
};

/* ---------- Inplannen ---------- */
const showScheduleModal = ref(false);
const tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
const scheduleForm = useForm({ send_on: props.invoice.scheduled_send_on || tomorrow });

const scheduleInvoice = () => {
  scheduleForm.post(route('invoices.schedule', props.invoice.id), {
    preserveScroll: true,
    onSuccess: () => { showScheduleModal.value = false; },
  });
};

const unschedule = () => {
  if (confirm(t('Inplanning annuleren? De factuur blijft dan een concept.'))) {
    router.delete(route('invoices.unschedule', props.invoice.id), { preserveScroll: true });
  }
};

/* ---------- Interne notitie ---------- */
const noteForm = useForm({ internal_notes: props.invoice.internal_notes || '' });
const noteDirty = computed(() => noteForm.internal_notes !== (props.invoice.internal_notes || ''));
const saveNote = () => noteForm.patch(route('invoices.notes.update', props.invoice.id), { preserveScroll: true });

/* ---------- Historie ---------- */
const reminderCounts = computed(() => {
  const logs = props.invoice.reminder_logs || [];
  return {
    reminders: logs.filter(l => l.kind !== 'warning').length,
    warnings: logs.filter(l => l.kind === 'warning').length,
  };
});

/* ---------- Klantenportaal / inzagelog ---------- */
const viewEventLabels = { viewed: t('Factuur bekeken'), pdf: t('PDF gedownload'), attachment: t('Bijlage gedownload'), payment_started: t('Online betaling gestart') };
const showAllViews = ref(false);
const visibleViews = computed(() => {
  const views = props.invoice.views || [];
  return showAllViews.value ? views : views.slice(0, 5);
});

const linkCopied = ref(false);
const copyPortalLink = async () => {
  try {
    await navigator.clipboard.writeText(props.invoice.portal_url);
    linkCopied.value = true;
    setTimeout(() => { linkCopied.value = false; }, 2000);
  } catch {
    prompt(t('Kopieer de portaallink:'), props.invoice.portal_url);
  }
};

const deleteInvoice = () => {
  if (confirm(t('Concept verwijderen?'))) {
    router.delete(route('invoices.destroy', props.invoice.id));
  }
};

/* ---------- Windykacja (alleen markt 'pl') ---------- */
// Verstuurde, onbetaalde factuur (geen creditnota): formele aanmaning (wezwanie
// do zapłaty), verkoop van de vordering aan de partner en de berekening van
// wettelijke rente + rekompensata — lazy geladen bij het openen van het blok.
const canWindykacja = computed(() =>
  market.key === 'pl' && !props.invoice.is_credit && !['draft', 'paid', 'cancelled'].includes(props.invoice.status)
);
const windykacjaOpen = ref(false);
const claim = ref(null);
const claimLoading = ref(false);
const claimError = ref('');
const saleNote = ref('');

const loadClaim = async () => {
  if (claim.value || claimLoading.value) return;
  claimLoading.value = true;
  claimError.value = '';
  try {
    const { data } = await axios.get(route('windykacja.claim', props.invoice.id));
    claim.value = data;
  } catch (e) {
    claimError.value = e.response?.data?.message || t('De berekening kon niet worden geladen.');
  } finally {
    claimLoading.value = false;
  }
};

const toggleWindykacja = () => {
  windykacjaOpen.value = !windykacjaOpen.value;
  if (windykacjaOpen.value) loadClaim();
};

// De rente komt als fractie (0,14) of als percentage (14): toon altijd een percentage.
const claimRatePct = computed(() => {
  const r = Number(claim.value?.rate) || 0;
  return num(r <= 1 ? r * 100 : r, 2);
});

const requestSale = () => {
  if (confirm(t('Deze factuur ter verkoop aanbieden aan :partner? De partner neemt contact met je op over de voorwaarden.', { partner: market.wykup_partner }))) {
    router.post(route('windykacja.wykup', props.invoice.id), { note: saleNote.value }, {
      preserveScroll: true,
      onSuccess: () => { saleNote.value = ''; },
    });
  }
};

/* ---------- KSeF (alleen markt 'pl') ---------- */
const ksefForm = useForm({ ksef_number: props.invoice.ksef_number || '' });
const saveKsef = () => ksefForm.patch(route('ksef.number', props.invoice.id), { preserveScroll: true });
</script>

<template>
  <Head :title="$t('Factuur :number', { number: invoice.number || $t('concept') })" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        {{ $t('Verkoop') }} / <Link :href="route('invoices.index')" style="color:var(--text-3);">{{ $t('Facturen') }}</Link> /
        <span class="breadcrumb-current">{{ invoice.number || $t('Concept') }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('invoices.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          {{ $t('Terug') }}
        </Link>
        <h1 class="page-title">{{ $t('Factuur') }} {{ invoice.number || $t('— concept —') }}</h1>
        <p class="page-subtitle">
          <template v-if="invoice.status === 'draft'">{{ $t('Concept · nog niet verstuurd') }}</template>
          <template v-else-if="invoice.sent_at_label">{{ $t('Verstuurd op :date', { date: invoice.sent_at_label }) }}</template>
          <template v-if="invoice.brand_profile_name"> · {{ $t('als') }} <b>{{ invoice.brand_profile_name }}</b></template>
          <template v-if="invoice.language === 'en'"> · {{ $t('Engelstalig') }}</template>
        </p>
      </div>
      <div class="page-actions">
        <a :href="route('invoices.pdf', invoice.id)" target="_blank" class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          PDF
        </a>
        <a v-if="invoice.status !== 'draft'" :href="route('invoices.ubl', invoice.id)" class="btn btn-secondary btn-sm" :title="$t('Download als UBL 2.1 (e-factuur, NLCIUS)')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
          UBL
        </a>
        <button class="btn btn-secondary btn-sm" :title="$t('Maak een kopie als nieuw concept')" @click="duplicateInvoice">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          {{ $t('Dupliceren') }}
        </button>
        <button v-if="!invoice.is_credit" class="btn btn-secondary btn-sm" :title="$t('Maak hier een terugkerende factuur van')" @click="showRecurringModal = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          {{ $t('Maak terugkerend') }}
        </button>
        <template v-if="invoice.status === 'draft'">
          <Link :href="route('invoices.edit', invoice.id)" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><polygon points="18.5 2.5 21.5 5.5 12 15 9 15 9 12 18.5 2.5"/></svg>
            {{ $t('Bewerken') }}
          </Link>
          <button class="btn btn-danger btn-sm" @click="deleteInvoice">{{ $t('Verwijder') }}</button>
          <button v-if="!invoice.scheduled_send_on" class="btn btn-secondary btn-sm" :title="$t('Automatisch versturen op een datum die jij kiest')" @click="showScheduleModal = true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            {{ $t('Inplannen') }}
          </button>
          <button class="btn btn-primary btn-sm" @click="sendInvoice">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            {{ $t('Versturen') }}
          </button>
        </template>
        <button
          v-if="peppol?.available && peppol?.sending_enabled && invoice.status !== 'draft' && !peppol?.sent_at_label"
          class="btn btn-secondary btn-sm"
          :title="$t('Lever de e-factuur (UBL) rechtstreeks af in het boekhoudpakket van de klant')"
          @click="sendPeppol"
        >
          ⚡ {{ $t('Via Peppol afleveren') }}
        </button>
        <button v-if="canRemind" class="btn btn-secondary btn-sm" :title="$t('Stuur nu een herinnering naar de klant')" @click="sendReminder">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          {{ $t('Herinnering sturen') }}
        </button>
        <button
          v-if="canThank"
          class="btn btn-secondary btn-sm"
          :title="invoice.thanks_sent_at_label ? $t('Bedankmail verstuurd op :date — nogmaals sturen', { date: invoice.thanks_sent_at_label }) : $t('Stuur de klant een bedankje voor de betaling, met de factuur (stempel BETAALD) als PDF')"
          @click="sendThanks"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          {{ invoice.thanks_sent_at_label ? $t('Bedankmail opnieuw sturen') : $t('Bedankmail sturen') }}
        </button>
        <button v-if="canCredit" class="btn btn-secondary btn-sm" :title="$t('Maak een creditnota op deze factuur')" @click="showCreditModal = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg>
          {{ $t('Creditnota') }}
        </button>
        <button v-if="canIncasso" class="btn btn-secondary btn-sm" :title="$t('Draag deze factuur over aan :partner', { partner: $page.props.market.incasso_partner })" @click="sendToIncasso">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
          {{ $t('Naar incasso') }}
        </button>
        <button v-if="invoice.is_credit && invoice.status === 'draft'" class="btn btn-primary btn-sm" @click="finalizeCredit">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          {{ $t('Creditnota definitief maken') }}
        </button>
        <button v-if="['sent','partial','overdue'].includes(invoice.status)" class="btn btn-primary btn-sm" @click="showPaymentModal = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          {{ $t('Betaling registreren') }}
        </button>
      </div>
    </div>

    <div v-if="pageError" class="inv-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ pageError }}
    </div>
    <div v-if="$page.props.errors?.schedule" class="inv-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ $page.props.errors.schedule }}
    </div>

    <!-- Ingepland: wordt automatisch verstuurd -->
    <div v-if="invoice.scheduled_send_on_label && invoice.status === 'draft'" class="sched-banner">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <div v-html="$t('Deze factuur wordt <strong>automatisch verstuurd op :date</strong> (in de ochtend).', { date: esc(invoice.scheduled_send_on_label) })"></div>
      <button type="button" class="link-btn" style="margin-left:auto;flex:none;" @click="unschedule">{{ $t('Annuleren') }}</button>
    </div>

    <div class="inv-detail">
      <div class="inv-detail-header">
        <div class="inv-detail-top">
          <div>
            <div class="inv-number">{{ invoice.number || $t('— concept —') }}</div>
            <div style="margin-top:8px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <StatusPill :status="invoice.status" :days-overdue="invoice.days_overdue" />
              <span v-if="peppol?.sent_at_label" class="peppol-chip on" :title="$t('Afgeleverd via Peppol op :date', { date: peppol.sent_at_label })">
                ⚡ {{ $t('Via Peppol afgeleverd') }}
              </span>
              <span v-else-if="peppol?.available" class="peppol-chip" :title="$t('Deze klant is aangesloten op het Peppol-netwerk (:id)', { id: peppol.participant_id })">
                ⚡ {{ $t('Klant bereikbaar via Peppol') }}
              </span>
            </div>
          </div>
          <div style="text-align:right">
            <div class="inv-meta-label" style="margin-bottom:6px;">{{ $t('Totaal') }}</div>
            <div style="font-family:var(--font-display);font-weight:700;font-size:28px;letter-spacing:-0.02em;">{{ eur(invoice.total) }}</div>
            <div v-if="invoice.paid_total > 0" style="font-size:12px;color:var(--success);margin-top:4px;">
              {{ $t(':paid betaald · :open open', { paid: eur(invoice.paid_total), open: eur(invoice.remaining) }) }}
            </div>
          </div>
        </div>
        <div class="inv-detail-meta">
          <div>
            <div class="inv-meta-label">{{ $t('Factuurdatum') }}</div>
            <div class="inv-meta-value">{{ invoice.invoice_date_label }}</div>
          </div>
          <div>
            <div class="inv-meta-label">{{ $t('Vervaldatum') }}</div>
            <div class="inv-meta-value">{{ invoice.due_date_label }}</div>
          </div>
          <div v-if="invoice.reference">
            <div class="inv-meta-label">{{ $t('Referentie') }}</div>
            <div class="inv-meta-value mono">{{ invoice.reference }}</div>
          </div>
          <div>
            <div class="inv-meta-label">{{ $t('Betalingstermijn') }}</div>
            <div class="inv-meta-value">{{ $t(':n dagen', { n: invoice.payment_terms }) }}</div>
          </div>
        </div>
      </div>

      <!-- Wissel tussen de regels en een PDF-voorvertoning -->
      <div class="view-toggle-bar">
        <div class="view-toggle">
          <button type="button" :class="{ active: viewMode === 'regels' }" @click="viewMode = 'regels'">{{ $t('Factuurregels') }}</button>
          <button type="button" :class="{ active: viewMode === 'preview' }" @click="viewMode = 'preview'">{{ $t('Voorvertoning (PDF)') }}</button>
        </div>
      </div>

      <div class="inv-body">
        <div v-if="viewMode === 'preview'" class="inv-preview">
          <iframe :src="route('invoices.pdf', invoice.id)" :title="$t('Voorvertoning van de factuur-PDF')"></iframe>
        </div>

        <div v-show="viewMode === 'regels'">
        <div class="inv-parties">
          <div>
            <div class="inv-party-label">{{ $t('Van') }}</div>
            <div class="inv-party-name">{{ company.name }}</div>
            <div v-if="company.address_line" class="inv-party-line">{{ company.address_line }}</div>
            <div v-if="company.postal_code || company.city" class="inv-party-line">{{ company.postal_code }} {{ company.city }}</div>
            <div v-if="company.kvk_number || company.vat_number" class="inv-party-line">
              <span v-if="company.kvk_number">{{ $page.props.market.registry.short }} {{ company.kvk_number }}</span>
              <span v-if="company.kvk_number && company.vat_number"> · </span>
              <span v-if="company.vat_number">{{ $page.props.market.tax_id.short }} {{ company.vat_number }}</span>
            </div>
            <div v-if="company.iban" class="inv-party-line">IBAN {{ company.iban }}</div>
          </div>
          <div>
            <div class="inv-party-label">{{ $t('Aan') }}</div>
            <div class="inv-party-name">{{ invoice.customer_name }}</div>
            <div v-if="invoice.customer_address_line" class="inv-party-line">{{ invoice.customer_address_line }}</div>
            <div v-if="invoice.customer_postal_code || invoice.customer_city" class="inv-party-line">
              {{ invoice.customer_postal_code }} {{ invoice.customer_city }}
            </div>
            <div v-if="invoice.customer_kvk_number || invoice.customer_vat_number" class="inv-party-line">
              <span v-if="invoice.customer_kvk_number">{{ $page.props.market.registry.short }} {{ invoice.customer_kvk_number }}</span>
              <span v-if="invoice.customer_kvk_number && invoice.customer_vat_number"> · </span>
              <span v-if="invoice.customer_vat_number">{{ $page.props.market.tax_id.short }} {{ invoice.customer_vat_number }}</span>
            </div>
            <div v-if="invoice.customer_email" class="inv-party-line">{{ invoice.customer_email }}</div>
          </div>
        </div>

        <table class="inv-lines stacked-table">
          <thead>
            <tr>
              <th style="width:55%">{{ $t('Omschrijving') }}</th>
              <th style="text-align:right">{{ $t('Aantal') }}</th>
              <th style="text-align:right">{{ $t('Prijs') }}</th>
              <th style="text-align:center">{{ $t('BTW') }}</th>
              <th style="text-align:right">{{ $t('Totaal') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="line in invoice.lines" :key="line.id">
              <td class="cell-primary">
                <div style="font-weight:500;margin-bottom:2px;">{{ line.description }}</div>
                <div v-if="line.details" style="font-size:12px;color:var(--text-3);">{{ line.details }}</div>
              </td>
              <td class="mono" style="text-align:right" :data-label="$t('Aantal')">{{ Number(line.quantity) }}</td>
              <td class="mono" style="text-align:right" :data-label="$t('Prijs')">
                {{ eur(line.unit_price) }}
                <span v-if="Number(line.discount_pct) > 0" style="display:block;font-size:11px;color:var(--text-3);">−{{ Number(line.discount_pct) }}% {{ $t('korting') }}</span>
              </td>
              <td style="text-align:center" :data-label="$t('BTW')">{{ Number(line.vat_rate) }}%</td>
              <td class="mono" style="text-align:right" :data-label="$t('Totaal')">{{ eur(line.line_subtotal) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="inv-totals">
          <div class="inv-total-row">
            <span class="label">{{ $t('Subtotaal') }}</span>
            <span class="value mono">{{ eur(invoice.subtotal) }}</span>
          </div>
          <div v-for="(amount, rate) in invoice.vat_breakdown" :key="rate" class="inv-total-row">
            <span class="label">{{ $t('BTW') }} {{ Number(rate) }}%</span>
            <span class="value mono">{{ eur(amount) }}</span>
          </div>
          <div class="inv-total-row grand">
            <span class="label">{{ $t('Totaal') }}</span>
            <span class="value mono">{{ eur(invoice.total) }}</span>
          </div>
        </div>

        <div v-if="invoice.notes" style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);font-size:13px;color:var(--text-3);">
          <div style="margin-bottom:8px;color:var(--text-2);font-weight:500;">{{ $t('Opmerking') }}</div>
          {{ invoice.notes }}
        </div>
        </div><!-- /v-show regels -->

        <!-- Incasso-dossier -->
        <div v-if="invoice.status === 'incasso'" class="inc-panel">
          <div class="inc-head">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
            <div>
              <div class="inc-title">{{ $t('Overgedragen aan incasso') }}</div>
              <div class="inc-sub">{{ invoice.incasso_handler }}</div>
            </div>
            <Link :href="route('incasso.index')" class="btn btn-secondary btn-sm">{{ $t('Alle dossiers') }}</Link>
          </div>
          <div class="inc-meta">
            <div><span class="inv-meta-label">{{ $t('Dossiernummer') }}</span><span class="mono">{{ invoice.incasso_reference }}</span></div>
            <div><span class="inv-meta-label">{{ $t('Overgedragen op') }}</span><span>{{ invoice.incasso_sent_at_label || '—' }}</span></div>
            <div><span class="inv-meta-label">{{ $t('Fase') }}</span><span>{{ phaseLabels[invoice.incasso_phase] || invoice.incasso_phase }}</span></div>
          </div>
        </div>

        <!--
          De koppeling met VvEMaat. Staat er alleen bij een klant die een
          VvE-omgeving afneemt, en dan altijd — ook als alles goed gaat.

          Aan deze melding hangt of het bestuur van die vereniging zijn
          administratie kan blijven bijwerken. Gaat er iets mis, dan hoor je dat
          hier te zien in plaats van pas als er gebeld wordt.
        -->
        <div v-if="invoice.vvemaat" class="inc-panel">
          <div class="inc-head">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h.01M15 9h.01M9 13h.01M15 13h.01M11 21v-4h2v4"/></svg>
            <div>
              <div class="inc-title">VvEMaat</div>
              <div class="inc-sub">
                <a :href="invoice.vvemaat.url" target="_blank" rel="noopener noreferrer">{{ invoice.vvemaat.slug }}.vvemaat.nl</a>
              </div>
            </div>
          </div>
          <div class="inc-meta">
            <div>
              <span class="inv-meta-label">{{ $t('Periode') }}</span>
              <span>{{ invoice.vvemaat.period_label || $t('geen — er wordt niets doorgegeven') }}</span>
            </div>
            <div>
              <span class="inv-meta-label">{{ $t('Geeft toegang tot en met') }}</span>
              <span class="mono">{{ invoice.vvemaat.paid_through || '—' }}</span>
            </div>
            <div>
              <span class="inv-meta-label">{{ $t('Doorgegeven op') }}</span>
              <span>{{ invoice.vvemaat.notified_at_label || $t('nog niet') }}</span>
            </div>
          </div>
          <p v-if="invoice.vvemaat.waarschuwing" class="vv-warn">
            {{ invoice.vvemaat.waarschuwing }}
          </p>
        </div>

        <!-- Windykacja (alleen markt pl): aanmaning, rente + rekompensata, verkoop van de vordering -->
        <div v-if="canWindykacja" class="pl-panel">
          <div class="pl-head">
            <div>
              <div class="pl-title">{{ $t('Aanmaning en verkoop van de vordering') }}</div>
              <div class="pl-sub">{{ $t('Formele aanmaning met wettelijke rente en vergoeding van incassokosten — of verkoop de vordering aan :partner.', { partner: $page.props.market.wykup_partner }) }}</div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" @click="toggleWindykacja">
              {{ windykacjaOpen ? $t('Berekening verbergen') : $t('Berekening tonen') }}
            </button>
          </div>

          <div class="pl-actions" style="margin-top:12px;">
            <a :href="route('windykacja.wezwanie', invoice.id)" class="btn btn-secondary btn-sm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              {{ $t('Formele aanmaning (PDF)') }}
            </a>
            <span v-if="invoice.sale_requested_at" class="pl-chip" :title="invoice.sale_requested_at_label || fmtDate(invoice.sale_requested_at)">✓ {{ $t('Aangemeld voor verkoop') }}</span>
            <button v-else type="button" class="btn btn-secondary btn-sm" @click="requestSale">{{ $t('Factuur verkopen') }}</button>
          </div>
          <div v-if="!invoice.sale_requested_at" class="pl-sale">
            <input type="text" v-model="saleNote" maxlength="1000" :placeholder="$t('Toelichting bij de verkoop (optioneel)')">
          </div>

          <div v-if="windykacjaOpen" class="pl-claim">
            <div v-if="claimLoading" class="pl-note">{{ $t('Berekening laden…') }}</div>
            <div v-else-if="claimError" class="field-error">{{ claimError }}</div>
            <template v-else-if="claim">
              <div class="pl-claim-row"><span class="label">{{ $t('Hoofdsom') }}</span><span class="mono">{{ eur(claim.principal) }}</span></div>
              <div class="pl-claim-row"><span class="label">{{ $t('Dagen te laat') }}</span><span class="mono">{{ claim.days }}</span></div>
              <div class="pl-claim-row"><span class="label">{{ $t('Wettelijke rente') }} ({{ claimRatePct }}% {{ $t('per jaar') }})</span><span class="mono">{{ eur(claim.interest) }}</span></div>
              <div class="pl-claim-row"><span class="label">{{ $t('Vergoeding incassokosten') }} ({{ num(claim.compensation_eur, 0) }} EUR)</span><span class="mono">{{ eur(claim.compensation) }}</span></div>
              <div class="pl-claim-row grand"><span class="label">{{ $t('Totaal te vorderen') }}</span><span class="mono">{{ eur(claim.total) }}</span></div>
              <div v-if="(claim.interest_periods || []).length > 1" class="pl-note">{{ $t('Rente per periode:') }} {{ claim.interest_periods.map(p => num(p.rate * 100, 2) + '% × ' + p.days + ' ' + $t('dagen')).join(' · ') }}</div>
              <div v-if="claim.eur_pln" class="pl-note">{{ claim.eur_pln_source === 'nbp' ? $t('Vergoeding omgerekend tegen de NBP-koers van :date: :rate zł/EUR', { date: fmtDate(claim.eur_pln_date), rate: num(claim.eur_pln, 4) }) : $t('Vergoeding omgerekend tegen een indicatieve koers van :rate zł/EUR (NBP niet bereikbaar)', { rate: num(claim.eur_pln, 4) }) }}</div>
              <div class="pl-note">{{ $t('Uiterste betaaldatum in de aanmaning: :date', { date: fmtDate(claim.deadline) }) }}</div>
            </template>
          </div>
        </div>

        <!-- KSeF (alleen markt pl): FA-XML en het toegekende KSeF-nummer -->
        <div v-if="$page.props.market.key === 'pl' && invoice.status !== 'draft'" class="pl-panel">
          <div class="pl-head">
            <div>
              <div class="pl-title">KSeF</div>
              <div class="pl-sub">{{ $t('Krajowy System e-Faktur: download de XML en bewaar het toegekende KSeF-nummer bij deze factuur.') }}</div>
            </div>
            <a :href="route('ksef.xml', invoice.id)" class="btn btn-secondary btn-sm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
              {{ $t('KSeF-XML downloaden') }}
            </a>
          </div>
          <div v-if="invoice.ksef_number" class="pl-note" style="margin-top:10px;">{{ $t('KSeF-nummer') }}: <span class="mono">{{ invoice.ksef_number }}</span></div>
          <div class="ksef-row">
            <input type="text" v-model="ksefForm.ksef_number" maxlength="64" :placeholder="$t('Toegekend KSeF-nummer')">
            <button type="button" class="btn btn-primary btn-sm" :disabled="ksefForm.processing" @click="saveKsef">{{ $t('KSeF-nummer opslaan') }}</button>
          </div>
          <div v-if="ksefForm.errors.ksef_number" class="field-error" style="margin-top:6px;">{{ ksefForm.errors.ksef_number }}</div>
        </div>

        <!-- Creditnota's op deze factuur -->
        <div v-if="invoice.credit_notes && invoice.credit_notes.length > 0" style="margin-top:28px;">
          <div class="sect-title">{{ $t("Creditnota's op deze factuur") }}</div>
          <table class="payments-table stacked-table">
            <thead>
              <tr><th>{{ $t('Nummer') }}</th><th>{{ $t('Datum') }}</th><th>{{ $t('Status') }}</th><th class="right">{{ $t('Bedrag') }}</th></tr>
            </thead>
            <tbody>
              <tr v-for="c in invoice.credit_notes" :key="c.id" style="cursor:pointer" @click="router.get(route('invoices.show', c.id))">
                <td class="cell-primary mono">{{ c.number || $t('Concept') }}</td>
                <td :data-label="$t('Datum')">{{ c.invoice_date_label }}</td>
                <td :data-label="$t('Status')"><StatusPill :status="c.status" /></td>
                <td class="num right" :data-label="$t('Bedrag')">{{ eur(c.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Gecrediteerde factuur (bij een creditnota) -->
        <div v-if="invoice.is_credit && invoice.original_invoice" style="margin-top:28px;">
          <div class="sect-title">{{ $t('Hoort bij factuur') }}</div>
          <Link :href="route('invoices.show', invoice.original_invoice.id)" class="btn btn-secondary btn-sm">
            {{ $t(':number bekijken', { number: invoice.original_invoice.number }) }} →
          </Link>
        </div>

        <!-- Inzage door de klant (klantenportaal) -->
        <div v-if="invoice.status !== 'draft'" style="margin-top:28px;">
          <div class="sect-head">
            <div class="sect-title" style="margin:0;">{{ $t('Inzage door klant') }}</div>
            <button v-if="invoice.portal_url" class="btn btn-secondary btn-sm" @click="copyPortalLink">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              {{ linkCopied ? $t('Gekopieerd!') : $t('Kopieer portaallink') }}
            </button>
          </div>

          <div class="view-status" :class="invoice.first_viewed_at_label ? 'seen' : 'unseen'">
            <svg v-if="invoice.first_viewed_at_label" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            <div>
              <div class="view-status-title">
                <template v-if="invoice.first_viewed_at_label">{{ $t('Voor het eerst bekeken op :date', { date: invoice.first_viewed_at_label }) }}</template>
                <template v-else>{{ $t('Nog niet bekeken door de klant') }}</template>
              </div>
              <div class="view-status-sub">
                <template v-if="invoice.first_viewed_at_label">
                  {{ $t('De klant heeft de factuur :n× ingezien via het beveiligde portaal.', { n: (invoice.views || []).length }) }}
                </template>
                <template v-else>
                  {{ $t('Zodra de klant de factuur opent via de link in de e-mail, zie je dat hier direct terug.') }}
                </template>
              </div>
            </div>
          </div>

          <div v-if="visibleViews.length" class="rem-trail" style="margin-top:10px;">
            <div v-for="v in visibleViews" :key="v.id" class="rem-row">
              <span class="rem-dot" :class="v.event === 'pdf' ? '' : 'view'"></span>
              <div class="rem-info">
                <div class="rem-type">{{ viewEventLabels[v.event] || v.event }}</div>
                <div class="rem-meta">{{ v.viewed_at_label }}<template v-if="v.ip_address"> · IP {{ v.ip_address }}</template></div>
              </div>
            </div>
            <button
              v-if="(invoice.views || []).length > 5"
              class="link-btn"
              style="align-self:flex-start;margin-top:6px;"
              @click="showAllViews = !showAllViews"
            >
              {{ showAllViews ? $t('Toon minder') : $t('Toon alle :n inzagemomenten', { n: (invoice.views || []).length }) }}
            </button>
          </div>
        </div>

        <!-- Interne notitie -->
        <div style="margin-top:28px;">
          <div class="sect-head">
            <div class="sect-title" style="margin:0;display:flex;align-items:center;gap:10px;">
              {{ $t('Interne notitie') }}
              <span class="note-badge">{{ $t('niet zichtbaar voor de klant') }}</span>
            </div>
            <button v-if="noteDirty" class="btn btn-primary btn-sm" :disabled="noteForm.processing" @click="saveNote">
              {{ noteForm.processing ? $t('Opslaan…') : $t('Notitie opslaan') }}
            </button>
          </div>
          <textarea
            v-model="noteForm.internal_notes"
            class="note-area"
            rows="3"
            maxlength="5000"
            :placeholder="$t('Bijv. afspraken met de klant, status van het werk, waarom deze factuur afwijkt…')"
          ></textarea>
        </div>

        <!-- Historie -->
        <div v-if="invoice.history && invoice.history.length" style="margin-top:28px;">
          <div class="sect-head">
            <div class="sect-title" style="margin:0;">{{ $t('Historie') }}</div>
            <div class="hist-props">
              <span class="hist-chip" :class="{ on: !!invoice.sent_at_label }">{{ invoice.sent_at_label ? $t('1× verstuurd') : $t('Nog niet verstuurd') }}</span>
              <span v-if="reminderCounts.reminders" class="hist-chip on">{{ $t(':n× herinnering', { n: reminderCounts.reminders }) }}</span>
              <span v-if="reminderCounts.warnings" class="hist-chip warn">{{ $t(':n× aanmaning', { n: reminderCounts.warnings }) }}</span>
              <span v-if="invoice.thanks_sent_at_label" class="hist-chip thanks" :title="$t('Verstuurd op :date', { date: invoice.thanks_sent_at_label })">♥ {{ $t('Bedankmail verstuurd') }}</span>
            </div>
          </div>
          <div class="hist-trail">
            <div v-for="(e, i) in invoice.history" :key="i" class="hist-row">
              <span class="hist-icon">
                <svg v-if="e.icon === 'plus'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <svg v-else-if="e.icon === 'send'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <svg v-else-if="e.icon === 'eye'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else-if="e.icon === 'bell'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <svg v-else-if="e.icon === 'alert'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <svg v-else-if="e.icon === 'euro'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10h12"/><path d="M4 14h9"/><path d="M19 6a7.7 7.7 0 0 0-5.2-2A7.9 7.9 0 0 0 6 12c0 4.4 3.5 8 7.8 8 2 0 3.8-.8 5.2-2"/></svg>
                <svg v-else-if="e.icon === 'check'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <svg v-else-if="e.icon === 'gavel'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
                <svg v-else-if="e.icon === 'heart'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
              </span>
              <div class="hist-label">{{ e.label }}</div>
              <div class="hist-ts">{{ e.ts_label }}</div>
            </div>
          </div>
        </div>

        <!-- Herinneringsverloop -->
        <div v-if="invoice.reminder_logs && invoice.reminder_logs.length > 0" style="margin-top:28px;">
          <div class="sect-title">{{ $t('Verstuurde herinneringen') }}</div>
          <div class="rem-trail">
            <div v-for="r in invoice.reminder_logs" :key="r.id" class="rem-row">
              <span class="rem-dot" :class="r.kind === 'warning' ? 'warn' : ''"></span>
              <div class="rem-info">
                <div class="rem-type">{{ r.type }}</div>
                <div class="rem-meta">{{ r.sent_at_label }} · {{ $t('naar') }} {{ r.sent_to }}</div>
              </div>
              <div class="num rem-amt">{{ eur(r.amount_open) }}</div>
            </div>
          </div>
        </div>

        <!-- Bijlagen -->
        <div style="margin-top:28px;">
          <div class="sect-head">
            <div class="sect-title" style="margin:0;">{{ $t('Bijlagen') }}</div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
              <label class="att-upload-check" :title="$t('Aangevinkt: de bijlage is zichtbaar in het klantenportaal (en gaat mee als de factuur nog verstuurd wordt)')">
                <input type="checkbox" v-model="uploadForCustomer">
                {{ $t('Voor de klant') }}
              </label>
              <input ref="fileInput" type="file" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="display:none" @change="uploadFiles">
              <button class="btn btn-secondary btn-sm" :disabled="uploadForm.processing" @click="fileInput?.click()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ uploadForm.processing ? $t('Bezig met uploaden…') : $t('Bestand toevoegen') }}
              </button>
            </div>
          </div>

          <div v-if="uploadForm.errors.files || uploadForm.errors['files.0']" class="field-error" style="margin-bottom:10px;">
            {{ uploadForm.errors.files || uploadForm.errors['files.0'] }}
          </div>

          <div v-if="!invoice.attachments || invoice.attachments.length === 0" class="att-empty">
            {{ $t('Nog geen bijlagen. Voeg bijvoorbeeld een urenoverzicht of opdrachtbevestiging toe. Met het vinkje "Voor de klant" gaat een bijlage mee met de factuurmail en staat hij in het klantenportaal; interne bijlagen gaan alleen mee in het incassodossier. PDF, PNG, JPG of WEBP, max. 10 MB per bestand.') }}
          </div>

          <div v-else class="att-list">
            <div v-for="att in invoice.attachments" :key="att.id" class="att-row">
              <span class="att-icon" :class="'att-' + att.kind">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              </span>
              <div class="att-info">
                <a :href="route('attachments.show', att.id)" target="_blank" class="att-name">{{ att.filename }}</a>
                <div class="att-meta">{{ att.size_formatted }} · {{ $t('toegevoegd op :date', { date: att.uploaded_at_label }) }}</div>
              </div>
              <button
                class="att-customer-chip"
                :class="{ on: att.for_customer }"
                :title="att.for_customer ? $t('Zichtbaar voor de klant (factuurmail + portaal) — klik om intern te maken') : $t('Alleen intern — klik om zichtbaar te maken voor de klant')"
                @click="toggleForCustomer(att)"
              >
                <svg v-if="att.for_customer" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                {{ att.for_customer ? $t('Voor de klant') : $t('Intern') }}
              </button>
              <a :href="route('attachments.download', att.id)" class="btn btn-ghost btn-sm">{{ $t('Download') }}</a>
              <button class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="removeAttachment(att)">{{ $t('Verwijder') }}</button>
            </div>
          </div>
        </div>

        <!-- Payments -->
        <div v-if="invoice.payments && invoice.payments.length > 0" style="margin-top:28px;">
          <div style="font-family:var(--font-display);font-weight:600;font-size:16px;margin-bottom:12px;">{{ $t('Betalingen') }}</div>
          <table class="payments-table stacked-table">
            <thead>
              <tr><th>{{ $t('Datum') }}</th><th>{{ $t('Methode') }}</th><th>{{ $t('Referentie') }}</th><th class="right">{{ $t('Bedrag') }}</th></tr>
            </thead>
            <tbody>
              <tr v-for="p in invoice.payments" :key="p.id">
                <td class="cell-primary">{{ p.paid_on?.slice(0, 10) }}</td>
                <td :data-label="$t('Methode')">
                  <span v-if="p.kind === 'write_off'" class="writeoff-chip">{{ $t('Afboeking') }}</span>
                  <span v-else-if="p.kind === 'advance'" class="advance-chip">{{ $t('Doorgestort') }}</span>
                  <template v-else>{{ payMethodLabels[p.method] || p.method }}</template>
                </td>
                <td :data-label="$t('Referentie')">{{ p.reference || '—' }}</td>
                <td class="num right" :data-label="$t('Bedrag')">{{ eur(p.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Payment modal -->
    <div v-if="showPaymentModal" class="modal-overlay" @click.self="showPaymentModal = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Betaling registreren') }}</div>
          <button class="icon-btn" @click="showPaymentModal = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <label class="credit-opt" :class="{ on: paymentForm.kind === 'payment' }">
            <input type="radio" value="payment" v-model="paymentForm.kind">
            <div>
              <div class="credit-opt-title">{{ $t('Betaling ontvangen') }}</div>
              <div class="credit-opt-sub">{{ $t('Er is echt geld binnengekomen (bank, contant, pin…).') }}</div>
            </div>
          </label>
          <label class="credit-opt" :class="{ on: paymentForm.kind === 'advance' }">
            <input type="radio" value="advance" v-model="paymentForm.kind">
            <div>
              <div class="credit-opt-title">{{ $t('Verrekening · reeds doorgestort') }}</div>
              <div class="credit-opt-sub">{{ $t('Een al doorgestort bedrag dat op de factuur in mindering komt op "Te betalen". Verschijnt op de PDF; totaal en BTW blijven gelijk.') }}</div>
            </div>
          </label>
          <label class="credit-opt" :class="{ on: paymentForm.kind === 'write_off' }">
            <input type="radio" value="write_off" v-model="paymentForm.kind">
            <div>
              <div class="credit-opt-title">{{ $t('Afboeken (geen betaling)') }}</div>
              <div class="credit-opt-sub">{{ $t('Wikkel (een deel van) de factuur af zonder geld — bijv. een betalingsverschil, kwijtschelding of oninbaar bedrag.') }}</div>
            </div>
          </label>

          <div v-if="paymentForm.kind === 'advance'" class="writeoff-note" style="background:var(--info-bg);border-color:var(--info-border);color:var(--info);" v-html="$t('De verrekening verschijnt als aftrekpost op de factuur-PDF (\'reeds doorgestort\') en verlaagt het te betalen bedrag. Je omzet en BTW veranderen <b>niet</b>. Download de PDF opnieuw of stuur de factuur daarna — de regel staat er automatisch op.')"></div>

          <div v-if="paymentForm.kind === 'write_off'" class="writeoff-note" v-html="$t('Een afboeking verandert <b>niets</b> aan je omzet of BTW-aangifte — de factuur telt gewoon mee zoals hij is verstuurd. Wil je de BTW juist terugvragen (bijv. bij een oninbare factuur)? Maak dan een <b>creditnota</b> in plaats van een afboeking.')"></div>

          <div class="form-row" style="margin-top:14px;">
            <div class="form-group">
              <label>{{ $t('Bedrag') }} *</label>
              <input type="number" v-model="paymentForm.amount" step="0.01" min="0.01" :max="invoice.remaining">
              <div v-if="paymentForm.errors.amount" class="field-error">{{ paymentForm.errors.amount }}</div>
            </div>
            <div class="form-group">
              <label>{{ paymentForm.kind === 'payment' ? $t('Betaaldatum') : $t('Datum') }} *</label>
              <input type="date" v-model="paymentForm.paid_on">
            </div>
          </div>
          <div v-if="paymentForm.kind === 'payment'" class="form-group">
            <label>{{ $t('Methode') }} *</label>
            <select v-model="paymentForm.method">
              <option value="bank_transfer">{{ $t('Bankoverschrijving') }}</option>
              <option value="ideal">{{ $page.props.market.online_payment_label }}</option>
              <option value="cash">{{ $t('Contant') }}</option>
              <option value="card">{{ $t('Pinpas / creditcard') }}</option>
              <option value="other">{{ $t('Anders') }}</option>
            </select>
            <div v-if="paymentForm.errors.method" class="field-error">{{ paymentForm.errors.method }}</div>
          </div>
          <div class="form-group">
            <label>
              {{ paymentForm.kind === 'payment' ? $t('Referentie') : $t('Omschrijving') }}
              <span class="label-hint">{{ { payment: $t('(bijv. bankregel-omschrijving)'), advance: $t('(verschijnt op de PDF, bijv. "Reeds doorgestort 11-08")'), write_off: $t('(bijv. betalingsverschil, kwijtgescholden)') }[paymentForm.kind] }}</span>
            </label>
            <input type="text" v-model="paymentForm.reference" maxlength="255">
          </div>

          <!-- Bedankmail: alleen bij een echte, volledige betaling -->
          <label v-if="paymentForm.kind === 'payment'" class="credit-opt thanks-opt" :class="{ on: paymentForm.send_thanks && isFullPayment && invoice.customer_email, off: !isFullPayment || !invoice.customer_email }">
            <input type="checkbox" v-model="paymentForm.send_thanks" :disabled="!isFullPayment || !invoice.customer_email">
            <div>
              <div class="credit-opt-title">{{ $t('Klant bedanken per e-mail') }}</div>
              <div class="credit-opt-sub">
                <template v-if="!invoice.customer_email">{{ $t('Deze klant heeft geen e-mailadres — vul het aan bij de klantgegevens.') }}</template>
                <template v-else-if="!isFullPayment">{{ $t('Volgt pas bij volledige betaling; dit is een deelbetaling.') }}</template>
                <template v-else>{{ $t('Stuurt direct een bedankmail naar :email, met de factuur (stempel BETAALD) als PDF.', { email: invoice.customer_email }) }}</template>
              </div>
            </div>
          </label>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showPaymentModal = false">{{ $t('Annuleren') }}</button>
            <button class="btn btn-primary btn-sm" @click="recordPayment" :disabled="paymentForm.processing">
              {{ { payment: $t('Registreren'), advance: $t('Verrekenen'), write_off: $t('Afboeken') }[paymentForm.kind] }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Creditnota modal -->
    <div v-if="showCreditModal" class="modal-overlay" @click.self="showCreditModal = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Creditnota maken') }}</div>
          <button class="icon-btn" @click="showCreditModal = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <p style="font-size:13px;color:var(--text-3);margin-bottom:16px;line-height:1.6;" v-html="$t('Een creditnota corrigeert factuur <b>:number</b> van :customer. De oorspronkelijke factuur blijft ongewijzigd bestaan — zo blijft je administratie kloppen.', { number: esc(invoice.number), customer: esc(invoice.customer_name) })"></p>

          <label class="credit-opt" :class="{ on: creditForm.kind === 'full' }">
            <input type="radio" value="full" v-model="creditForm.kind">
            <div>
              <div class="credit-opt-title">{{ $t('Volledig crediteren') }}</div>
              <div class="credit-opt-sub">{{ $t('Het hele bedrag van :amount wordt teruggeboekt. De creditnota krijgt meteen een definitief nummer.', { amount: eur(invoice.total) }) }}</div>
            </div>
          </label>

          <label class="credit-opt" :class="{ on: creditForm.kind === 'partial' }">
            <input type="radio" value="partial" v-model="creditForm.kind">
            <div>
              <div class="credit-opt-title">{{ $t('Gedeeltelijk crediteren') }}</div>
              <div class="credit-opt-sub">{{ $t('Je opent een concept waarin je zelf de regels en bedragen aanpast. Pas daarna maak je hem definitief.') }}</div>
            </div>
          </label>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showCreditModal = false">{{ $t('Annuleren') }}</button>
            <button class="btn btn-primary btn-sm" @click="createCredit" :disabled="creditForm.processing">{{ $t('Creditnota maken') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Inplannen modal -->
    <div v-if="showScheduleModal" class="modal-overlay" @click.self="showScheduleModal = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Factuur inplannen') }}</div>
          <button class="icon-btn" @click="showScheduleModal = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <p style="font-size:13px;color:var(--text-3);margin-bottom:16px;line-height:1.6;" v-html="$t('Op de gekozen datum wordt deze factuur \'s ochtends automatisch definitief gemaakt en per e-mail verstuurd naar <b>:email</b>. Tot die tijd blijft het een concept dat je kunt aanpassen of annuleren.', { email: esc(invoice.customer_email || $t('de klant')) })"></p>
          <div class="form-group">
            <label>{{ $t('Versturen op') }} *</label>
            <input type="date" v-model="scheduleForm.send_on" :min="tomorrow">
            <div v-if="scheduleForm.errors.send_on" class="field-error">{{ scheduleForm.errors.send_on }}</div>
          </div>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showScheduleModal = false">{{ $t('Annuleren') }}</button>
            <button class="btn btn-primary btn-sm" :disabled="scheduleForm.processing" @click="scheduleInvoice">{{ $t('Inplannen') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Recurring modal -->
    <div v-if="showRecurringModal" class="modal-overlay" @click.self="showRecurringModal = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Maak terugkerend') }}</div>
          <button class="icon-btn" @click="showRecurringModal = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <p style="font-size:13px;color:var(--text-3);margin-bottom:16px;line-height:1.6;" v-html="$t('De regels van deze factuur worden elke periode automatisch opnieuw gefactureerd aan <b>:customer</b>. Beheren doe je via <b>Verkoop → Terugkerend</b>.', { customer: esc(invoice.customer_name) })"></p>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Frequentie') }}</label>
              <select v-model="recurringForm.frequency">
                <option value="weekly">{{ $t('Wekelijks') }}</option>
                <option value="monthly">{{ $t('Maandelijks') }}</option>
                <option value="quarterly">{{ $t('Per kwartaal') }}</option>
                <option value="halfyearly">{{ $t('Per half jaar') }}</option>
                <option value="yearly">{{ $t('Jaarlijks') }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>{{ $t('Eerste factuurdatum') }}</label>
              <input type="date" v-model="recurringForm.next_run_on">
              <div v-if="recurringForm.errors.next_run_on" class="field-error">{{ recurringForm.errors.next_run_on }}</div>
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('Stopt automatisch na') }}<span class="label-hint">{{ $t('(optioneel)') }}</span></label>
            <input type="date" v-model="recurringForm.end_date">
            <div v-if="recurringForm.errors.end_date" class="field-error">{{ recurringForm.errors.end_date }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Wijze') }}</label>
            <select v-model="recurringForm.auto_send">
              <option :value="false">{{ $t('Als concept klaarzetten (zelf controleren en versturen)') }}</option>
              <option :value="true">{{ $t('Direct versturen naar de klant') }}</option>
            </select>
          </div>
          <div v-if="recurringForm.errors.recurring" class="field-error">{{ recurringForm.errors.recurring }}</div>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showRecurringModal = false">{{ $t('Annuleren') }}</button>
            <button class="btn btn-primary btn-sm" @click="createRecurring" :disabled="recurringForm.processing">{{ $t('Profiel aanmaken') }}</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.inv-detail { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); overflow: hidden; }
.inv-detail-header {
  background: linear-gradient(180deg, var(--brand-tint) 0%, var(--surface) 100%);
  padding: 28px 32px;
  border-bottom: 1px solid var(--border);
}
.inv-detail-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
.inv-number { font-family: var(--font-display); font-weight: 600; font-size: 22px; letter-spacing: -0.01em; }
.inv-detail-meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
.inv-meta-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; margin-bottom: 4px; }
.inv-meta-value { font-size: 14px; color: var(--text); font-weight: 500; }
.inv-meta-value.mono { font-family: var(--font-mono); }
.inv-body { padding: 28px 32px; }
.inv-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; padding-bottom: 28px; margin-bottom: 28px; border-bottom: 1px solid var(--border); }
.inv-party-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; margin-bottom: 8px; }
.inv-party-name { font-weight: 600; font-size: 15px; margin-bottom: 4px; }
.inv-party-line { color: var(--text-2); font-size: 13px; line-height: 1.6; }
.inv-lines th { text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); border-bottom: 1px solid var(--border); }
.inv-lines td { padding: 14px 12px; border-bottom: 1px solid var(--border); vertical-align: top; }
.inv-totals { margin-top: 24px; margin-left: auto; width: 320px; }
.inv-total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
.inv-total-row .label { color: var(--text-2); }
.inv-total-row .value { font-weight: 500; }
.inv-total-row.grand { border-top: 2px solid var(--text); padding-top: 14px; margin-top: 8px; font-weight: 700; font-size: 18px; }
/* Foutmelding boven de factuur */
.inv-alert { display: flex; align-items: center; gap: 10px; background: var(--brand-tint); border: 1px solid var(--brand-border); color: var(--brand-darker); border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13.5px; }
.inv-alert svg { width: 18px; height: 18px; flex: none; }

/* Peppol */
.peppol-chip {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 100px;
  background: var(--info-bg); color: var(--info); border: 1px solid var(--info-border);
  cursor: help;
}
.peppol-chip.on { background: var(--success-bg); color: var(--success); border-color: var(--success-border); }

/* Afboeken */
.writeoff-note {
  margin-top: 4px;
  background: var(--warning-bg); border: 1px solid var(--warning-border); color: var(--warning);
  border-radius: 9px; padding: 11px 14px;
  font-size: 12.5px; line-height: 1.6;
}
.writeoff-chip {
  display: inline-flex; align-items: center;
  font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 100px;
  background: var(--warning-bg); color: var(--warning); border: 1px solid var(--warning-border);
}
.advance-chip {
  display: inline-flex; align-items: center;
  font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 100px;
  background: var(--info-bg); color: var(--info); border: 1px solid var(--info-border);
}

/* Keuzeblokken in de creditnota-modal */
.credit-opt { display: flex; gap: 12px; align-items: flex-start; border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; margin-bottom: 10px; cursor: pointer; transition: border-color .15s, background .15s; }
.credit-opt:hover { background: var(--surface-2); }
.credit-opt.on { border-color: var(--brand); background: var(--brand-tint); }
.credit-opt input { margin-top: 3px; width: 16px; height: 16px; accent-color: var(--brand); flex: none; }
.credit-opt-title { font-weight: 600; font-size: 14px; }
.credit-opt-sub { font-size: 12.5px; color: var(--text-3); margin-top: 3px; line-height: 1.5; }
.thanks-opt { margin-top: 4px; }
.thanks-opt.on { border-color: var(--success); background: var(--success-bg); }
.thanks-opt.off { cursor: default; opacity: .7; }
.thanks-opt.off:hover { background: transparent; }

/* Sectiekoppen binnen de factuur */
.sect-title { font-family: var(--font-display); font-weight: 600; font-size: 16px; margin-bottom: 12px; }
.sect-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }

/* Incasso-paneel */
.inc-panel { margin-top: 28px; background: #1F2937; color: #fff; border-radius: 12px; padding: 18px 20px; }
.inc-head { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
.inc-head svg { width: 26px; height: 26px; color: #FCD34D; flex: none; }
.inc-title { font-family: var(--font-display); font-weight: 700; font-size: 16px; }
.inc-sub { font-size: 13px; color: #D1D5DB; }
.inc-head .btn { margin-left: auto; }
.inc-meta { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
.inc-meta > div { display: flex; flex-direction: column; gap: 3px; }
.inc-meta .inv-meta-label { color: #9CA3AF; }
.inc-meta span:not(.inv-meta-label) { font-size: 14px; font-weight: 500; }
.inc-panel a { color: #93C5FD; text-decoration: underline; text-underline-offset: 2px; }
/* Een waarschuwing binnen het donkere paneel: dezelfde accentkleur als het
   pictogram, zodat het opvalt zonder een tweede stijltaal te introduceren. */
.vv-warn { margin: 14px 0 0; font-size: 13px; line-height: 1.55; color: #FCD34D; }

/* Windykacja & KSeF (markt pl) */
.pl-panel { margin-top: 28px; border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.pl-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.pl-title { font-family: var(--font-display); font-weight: 600; font-size: 16px; }
.pl-sub { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }
.pl-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.pl-claim { margin-top: 14px; border-top: 1px solid var(--border); padding-top: 14px; }
.pl-claim-row { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; font-size: 13.5px; }
.pl-claim-row .label { color: var(--text-2); }
.pl-claim-row.grand { border-top: 2px solid var(--text); margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 15px; }
.pl-note { font-size: 12px; color: var(--text-3); margin-top: 8px; }
.pl-sale { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
.pl-sale input { flex: 1; min-width: 200px; }
.pl-chip { display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 100px; background: var(--success-bg); color: var(--success); border: 1px solid var(--success-border); }
.ksef-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 12px; }
.ksef-row input { flex: 1; min-width: 220px; font-family: var(--font-mono); }

/* Ingepland-banner */
.sched-banner {
  display: flex; align-items: center; gap: 11px;
  background: var(--info-bg); border: 1px solid var(--info-border); color: var(--info);
  border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;
  font-size: 13.5px; line-height: 1.5;
}
.sched-banner svg { width: 18px; height: 18px; flex: none; }

/* Wissel factuurregels / PDF-voorvertoning */
.view-toggle-bar { display: flex; justify-content: center; padding: 14px 16px 0; }
.view-toggle {
  display: flex; gap: 3px;
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: 9px; padding: 3px;
}
.view-toggle button {
  font-size: 12.5px; font-weight: 600; color: var(--text-3);
  padding: 6px 16px; border-radius: 7px;
}
.view-toggle button.active { background: var(--surface); color: var(--text); box-shadow: var(--shadow-sm); }
.inv-preview {
  border: 1px solid var(--border); border-radius: 10px; overflow: hidden;
  background: var(--surface-2);
}
.inv-preview iframe { display: block; width: 100%; height: 860px; border: none; }

/* Interne notitie */
.note-badge {
  font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
  background: var(--warning-bg); color: var(--warning); border: 1px solid var(--warning-border);
  border-radius: 100px; padding: 2px 9px;
}
.note-area { width: 100%; font-size: 13.5px; line-height: 1.6; background: #FFFBEB; border-color: var(--warning-border); }
.note-area:focus { border-color: var(--warning); box-shadow: 0 0 0 3px var(--warning-bg); }

/* Historie */
.hist-props { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.hist-chip {
  font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 100px;
  background: var(--surface-2); color: var(--text-3); border: 1px solid var(--border-strong);
}
.hist-chip.on { background: var(--info-bg); color: var(--info); border-color: var(--info-border); }
.hist-chip.warn { background: var(--warning-bg); color: var(--warning); border-color: var(--warning-border); }
.hist-chip.thanks { background: var(--success-bg); color: var(--success); border-color: var(--success-border); }
.hist-trail { display: flex; flex-direction: column; }
.hist-row {
  display: flex; align-items: center; gap: 12px;
  padding: 9px 0; border-bottom: 1px solid var(--border);
  font-size: 13px;
}
.hist-row:last-child { border-bottom: none; }
.hist-icon {
  width: 28px; height: 28px; border-radius: 100px; flex: none;
  background: var(--surface-2); color: var(--text-3);
  display: inline-flex; align-items: center; justify-content: center;
}
.hist-icon svg { width: 14px; height: 14px; }
.hist-label { flex: 1; min-width: 0; color: var(--text-2); overflow-wrap: anywhere; }
.hist-ts { font-size: 12px; color: var(--text-4); white-space: nowrap; }

/* Inzage door klant */
.view-status { display: flex; align-items: flex-start; gap: 12px; border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; }
.view-status svg { width: 20px; height: 20px; flex: none; margin-top: 1px; }
.view-status.seen { background: var(--success-bg); border-color: var(--success-border); }
.view-status.seen svg, .view-status.seen .view-status-title { color: var(--success); }
.view-status.unseen { background: var(--surface-2); }
.view-status.unseen svg { color: var(--text-4); }
.view-status-title { font-weight: 600; font-size: 13.5px; }
.view-status-sub { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }
.rem-dot.view { background: var(--success); }

/* Herinneringsverloop */
.rem-trail { display: flex; flex-direction: column; gap: 2px; }
.rem-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); }
.rem-row:last-child { border-bottom: none; }
.rem-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--info); flex: none; }
.rem-dot.warn { background: var(--brand); }
.rem-info { flex: 1; min-width: 0; }
.rem-type { font-weight: 600; font-size: 13.5px; }
.rem-meta { font-size: 12px; color: var(--text-3); margin-top: 2px; overflow-wrap: anywhere; }
.rem-amt { font-size: 13px; color: var(--text-2); white-space: nowrap; }

/* Bijlagen */
.att-empty { color: var(--text-3); font-size: 13px; line-height: 1.6; background: var(--surface-2); border: 1px dashed var(--border-strong); border-radius: 10px; padding: 16px 18px; }
.att-list { display: flex; flex-direction: column; gap: 8px; }
.att-row { display: flex; align-items: center; gap: 12px; border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; }
.att-icon { width: 34px; height: 34px; border-radius: 8px; background: var(--surface-2); color: var(--text-3); display: inline-flex; align-items: center; justify-content: center; flex: none; }
.att-icon svg { width: 17px; height: 17px; }
.att-info { flex: 1; min-width: 0; }
.att-name { font-weight: 600; font-size: 13.5px; color: var(--text); word-break: break-word; }
.att-name:hover { color: var(--brand); }
.att-meta { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.att-upload-check { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--text-2); font-weight: 500; cursor: pointer; }
.att-upload-check input { width: 15px; height: 15px; accent-color: var(--brand); cursor: pointer; }
.att-customer-chip {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 600; white-space: nowrap;
  padding: 4px 10px; border-radius: 100px;
  background: var(--surface-2); color: var(--text-3);
  border: 1px solid var(--border-strong);
  flex: none; cursor: pointer;
}
.att-customer-chip.on { background: var(--success-bg); color: var(--success); border-color: var(--success-border); }

.payments-table { font-size: 13px; }
.payments-table th { background: var(--surface-2); padding: 8px 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-3); border-bottom: 1px solid var(--border); }
.payments-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
.payments-table .right { text-align: right; }
.payments-table .num { font-family: var(--font-mono); }

/* Modal */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(28,25,23,0.4);
  z-index: 100;
  display: flex; align-items: flex-start; justify-content: center;
  padding: 60px 20px; overflow-y: auto;
}
.modal {
  background: var(--surface); border-radius: var(--r-lg);
  box-shadow: var(--shadow-lg);
  width: 100%; max-width: 540px;
}
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border); }
.modal-title { font-family: var(--font-display); font-weight: 600; font-size: 18px; }
.modal-body { padding: 24px; }
.modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--surface-2); border-radius: 0 0 var(--r-lg) var(--r-lg); }
.icon-btn { width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.icon-btn:hover { background: var(--surface-2); }

@media (max-width: 760px) {
  .inv-detail-header, .inv-body { padding: 20px 16px; }
  .inv-detail-top { flex-direction: column; gap: 12px; }
  /* Vier meta-kolommen en twee adreskolommen passen niet naast elkaar. */
  .inv-detail-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
  .inv-parties { grid-template-columns: minmax(0, 1fr); gap: 22px; }
  .inv-party-line { overflow-wrap: anywhere; }
  /* Totalen vullen de breedte i.p.v. een vaste 320px-kolom rechts. */
  .inv-totals { width: 100%; margin-left: 0; }
  .inv-lines td:not([data-label]):not(.cell-primary) { display: none; }
}
@media (max-width: 400px) {
  .inv-detail-meta { grid-template-columns: minmax(0, 1fr); }
}
</style>
