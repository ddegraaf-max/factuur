<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur, marketLocale } from '@/format.js';
import { t } from '@/i18n';
import { computed, ref } from 'vue';

const props = defineProps({
  quote: Object,
  company: Object,
});

// Voor v-html-teksten met opmaak: dynamische waarden veilig invoegen.
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const page = usePage();
const pageError = computed(() => (page.props.errors || {}).quote ?? null);

const isOpen = computed(() => ['sent', 'expired'].includes(props.quote.status));
const canEdit = computed(() => ['draft', 'sent'].includes(props.quote.status));

const pillClass = computed(() => ({
  draft: 'pill-draft',
  sent: 'pill-sent',
  accepted: 'pill-paid',
  rejected: 'pill-overdue',
  expired: 'pill-partial',
}[props.quote.status] ?? 'pill-draft'));

const send = () => {
  const label = props.quote.status === 'sent' ? t('Offerte opnieuw versturen?') : t('Offerte versturen naar de klant?');
  if (confirm(label)) {
    router.post(route('quotes.send', props.quote.id), {}, { preserveScroll: true });
  }
};

/* ---------- Geaccepteerd markeren (+ bevestiging naar de klant) ---------- */
const showAcceptModal = ref(false);
const acceptForm = useForm({
  // Voorgevinkt als de bevestigingsmail aanstaat (Instellingen → E-mailteksten).
  send_confirmation: !!props.company?.quote_accept_mail_enabled && !!props.quote.customer_email,
});
const accept = () => {
  acceptForm.post(route('quotes.accept', props.quote.id), {
    preserveScroll: true,
    onSuccess: () => { showAcceptModal.value = false; },
  });
};
const sendConfirmation = () => {
  const again = props.quote.accept_mail_sent_at_label ? t('Er is al een bevestiging gemaild op :date.', { date: props.quote.accept_mail_sent_at_label }) + '\n\n' : '';
  if (confirm(again + t('Bevestiging van het akkoord mailen naar :email? De offerte gaat mee als PDF.', { email: props.quote.customer_email }))) {
    router.post(route('quotes.confirm', props.quote.id), {}, { preserveScroll: true });
  }
};
const reject = () => {
  if (confirm(t('Offerte markeren als afgewezen?'))) {
    router.post(route('quotes.reject', props.quote.id), {}, { preserveScroll: true });
  }
};

const convert = () => {
  if (confirm(t('Van deze offerte een concept-factuur maken? De offerte blijft bewaard.'))) {
    router.post(route('quotes.convert', props.quote.id));
  }
};

/* ---------- Ondertekenlink kopiëren ---------- */
const signLinkCopied = ref(false);
const copySignLink = async () => {
  try {
    await navigator.clipboard.writeText(props.quote.portal_url);
    signLinkCopied.value = true;
    setTimeout(() => { signLinkCopied.value = false; }, 2500);
  } catch (e) {
    prompt(t('Kopieer de link handmatig:'), props.quote.portal_url);
  }
};

const destroy = () => {
  if (confirm(t('Concept verwijderen?'))) {
    router.delete(route('quotes.destroy', props.quote.id));
  }
};

/* ---------- Bijlagen ---------- */
const attFileInput = ref(null);
const attUploading = ref(false);

const uploadAttachments = (event) => {
  const files = Array.from(event.target.files || []);
  if (!files.length) return;
  attUploading.value = true;
  router.post(route('quotes.attachments.store', props.quote.id), { files }, {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      attUploading.value = false;
      if (attFileInput.value) attFileInput.value.value = '';
    },
  });
};

const removeAttachment = (att) => {
  if (confirm(t('Bijlage ":name" verwijderen?', { name: att.filename }))) {
    router.delete(route('attachments.destroy', att.id), { preserveScroll: true });
  }
};

const toggleAttachmentVisibility = (att) => {
  router.patch(route('attachments.update', att.id), { for_customer: !att.for_customer }, { preserveScroll: true });
};

/* ---------- Termijnfacturen ---------- */
const installmentsError = computed(() => (page.props.errors || {}).installments ?? null);
const showPlanner = ref(false);
const planRows = ref([
  { description: t('Termijn 1: bij opdracht'), percentage: 30 },
  { description: t('Termijn 2: bij oplevering'), percentage: 70 },
]);

const applyPreset = (parts) => {
  const labels = {
    2: [t('bij opdracht'), t('bij oplevering')],
    3: [t('bij opdracht'), t('tussentijds'), t('bij oplevering')],
  };
  planRows.value = parts.map((pct, i) => ({
    description: `${t('Termijn :n', { n: i + 1 })}: ${(labels[parts.length] || [])[i] || ''}`.trim(),
    percentage: pct,
  }));
};

const addPlanRow = () => planRows.value.push({ description: t('Termijn :n', { n: planRows.value.length + 1 }), percentage: 0 });
const removePlanRow = (i) => { if (planRows.value.length > 2) planRows.value.splice(i, 1); };

const planSum = computed(() => Math.round(planRows.value.reduce((s, r) => s + (Number(r.percentage) || 0), 0) * 100) / 100);

const savePlan = () => {
  router.post(route('quotes.installments.store', props.quote.id), { installments: planRows.value }, {
    preserveScroll: true,
    onSuccess: () => { showPlanner.value = false; },
  });
};

const deletePlan = () => {
  if (confirm(t('Termijnplan verwijderen? De offerte kan daarna weer in één keer worden gefactureerd.'))) {
    router.delete(route('quotes.installments.destroy', props.quote.id), { preserveScroll: true });
  }
};

const invoiceInstallment = (inst) => {
  if (confirm(t('Conceptfactuur maken voor ":description" (:amount)?', { description: inst.description, amount: eur(inst.amount) }))) {
    router.post(route('quotes.installments.invoice', [props.quote.id, inst.id]));
  }
};

const invoicedCount = computed(() => (props.quote.installments || []).filter(i => i.invoice).length);
</script>

<template>
  <Head :title="$t('Offerte :number', { number: quote.number || $t('concept') })" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        {{ $t('Verkoop') }} / <Link :href="route('quotes.index')" style="color:var(--text-3);">{{ $t('Offertes') }}</Link> /
        <span class="breadcrumb-current">{{ quote.number || $t('Concept') }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('quotes.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          {{ $t('Terug') }}
        </Link>
        <h1 class="page-title">{{ $t('Offerte') }} {{ quote.number || $t('— concept —') }}</h1>
        <p class="page-subtitle">
          <template v-if="quote.status === 'draft'">{{ $t('Concept · nog niet verstuurd') }}</template>
          <template v-else-if="quote.status === 'accepted'">{{ $t('Geaccepteerd op :date', { date: quote.accepted_at_label }) }}<template v-if="quote.accept_mail_sent_at_label"> · {{ $t('bevestiging gemaild :date', { date: quote.accept_mail_sent_at_label }) }}</template></template>
          <template v-else-if="quote.status === 'rejected'">{{ $t('Afgewezen op :date', { date: quote.rejected_at_label }) }}</template>
          <template v-else-if="quote.sent_at_label">{{ $t('Verstuurd op :date', { date: quote.sent_at_label }) }}</template>
          <template v-if="quote.brand_profile_name"> · {{ $t('als') }} <b>{{ quote.brand_profile_name }}</b></template>
          <template v-if="quote.language === 'en'"> · {{ $t('Engelstalig') }}</template>
        </p>
      </div>
      <div class="page-actions">
        <a :href="route('quotes.pdf', quote.id)" target="_blank" class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          PDF
        </a>
        <button
          v-if="quote.status === 'accepted' && quote.customer_email"
          class="btn btn-secondary btn-sm"
          :title="quote.accept_mail_sent_at_label ? $t('Bevestiging gemaild op :date — nogmaals sturen', { date: quote.accept_mail_sent_at_label }) : $t('Stuur de klant een bevestiging van het akkoord, met de offerte als PDF')"
          @click="sendConfirmation"
        >
          {{ quote.accept_mail_sent_at_label ? $t('Bevestiging opnieuw mailen') : $t('Bevestiging mailen') }}
        </button>
        <Link v-if="canEdit" :href="route('quotes.edit', quote.id)" class="btn btn-secondary btn-sm">{{ $t('Bewerken') }}</Link>
        <button v-if="quote.status === 'draft'" class="btn btn-danger btn-sm" @click="destroy">{{ $t('Verwijder') }}</button>
        <button v-if="canEdit" class="btn btn-primary btn-sm" @click="send">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          {{ quote.status === 'sent' ? $t('Opnieuw versturen') : $t('Versturen') }}
        </button>
      </div>
    </div>

    <div v-if="pageError" class="q-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ pageError }}
    </div>

    <!-- Beslisbalk: wat is er met dit voorstel gebeurd? -->
    <div v-if="isOpen" class="decide">
      <div class="decide-text">
        <strong>{{ $t('Wat heeft de klant besloten?') }}</strong>
        <span v-if="quote.status === 'expired'">{{ $t('Deze offerte is verlopen op :date, maar je kunt hem alsnog afronden.', { date: quote.valid_until_label }) }}</span>
        <span v-else-if="quote.days_left > 0">{{ quote.days_left === 1 ? $t('Nog 1 dag geldig.') : $t('Nog :n dagen geldig.', { n: quote.days_left }) }}</span>
      </div>
      <div class="decide-actions">
        <button v-if="quote.can_installments && !(quote.installments || []).length" class="btn btn-secondary btn-sm" @click="showPlanner = !showPlanner">{{ $t('In termijnen') }}</button>
        <button class="btn btn-secondary btn-sm" @click="reject">{{ $t('Afgewezen') }}</button>
        <button class="btn btn-primary btn-sm" @click="showAcceptModal = true">{{ $t('Geaccepteerd') }}</button>
      </div>
    </div>

    <!-- Geaccepteerd markeren -->
    <div v-if="showAcceptModal" class="modal-overlay" @click.self="showAcceptModal = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Offerte markeren als geaccepteerd') }}</div>
          <button class="icon-btn" @click="showAcceptModal = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <p class="modal-text" v-html="$t('De klant is akkoord (bijv. per telefoon of e-mail). De offerte krijgt de status <b>Geaccepteerd</b>; daarna kun je hem omzetten naar een factuur.')"></p>
          <label class="opt" :class="{ on: acceptForm.send_confirmation && quote.customer_email, off: !quote.customer_email }">
            <input type="checkbox" v-model="acceptForm.send_confirmation" :disabled="!quote.customer_email">
            <div>
              <div class="opt-title">{{ $t('Bevestiging mailen naar de klant') }}</div>
              <div class="opt-sub">
                <template v-if="!quote.customer_email">{{ $t('Deze klant heeft geen e-mailadres.') }}</template>
                <template v-else>{{ $t('Stuurt direct een bevestiging van het akkoord naar :email, met de offerte als PDF.', { email: quote.customer_email }) }}</template>
              </div>
            </div>
          </label>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showAcceptModal = false">{{ $t('Annuleren') }}</button>
            <button class="btn btn-primary btn-sm" @click="accept" :disabled="acceptForm.processing">{{ $t('Markeren als geaccepteerd') }}</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="quote.status === 'accepted' && !quote.invoice && !(quote.installments || []).length" class="decide accepted">
      <div class="decide-text">
        <strong>{{ $t('De klant is akkoord.') }}</strong>
        <span>{{ $t('Zet de offerte om in een factuur — of factureer in termijnen (bijv. 30% vooraf).') }}</span>
      </div>
      <div class="decide-actions">
        <button v-if="quote.can_installments" class="btn btn-secondary btn-sm" @click="showPlanner = !showPlanner">{{ $t('In termijnen') }}</button>
        <button class="btn btn-primary btn-sm" @click="convert">{{ $t('Omzetten naar factuur') }}</button>
      </div>
    </div>

    <!-- Digitale handtekening: het bewijsdossier -->
    <div v-if="quote.signed_at_label" class="sig-card">
      <div class="sig-info">
        <div class="sig-title">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
          {{ $t('Digitaal ondertekend') }}
        </div>
        <div class="sig-line" v-html="$t('Door <strong>:name</strong> op :date', { name: esc(quote.signed_name), date: esc(quote.signed_at_label) })"></div>
        <div class="sig-meta">{{ $t('Geverifieerd e-mailadres') }}: {{ quote.signed_email }} · IP: {{ quote.signed_ip }}</div>
        <div class="sig-meta" v-if="quote.accept_mail_sent_at_label">✓ {{ $t('Bevestiging gemaild naar :email op :date', { email: quote.accept_mail_sent_to, date: quote.accept_mail_sent_at_label }) }}</div>
      </div>
      <img v-if="quote.signature_data" :src="quote.signature_data" :alt="$t('Handtekening')" class="sig-img">
    </div>

    <!-- Ondertekenlink delen (bijv. via WhatsApp) -->
    <div v-if="quote.portal_url && ['sent', 'expired'].includes(quote.status)" class="sig-share">
      <span v-html="$t('Je klant kan de offerte online bekijken en <b>digitaal ondertekenen</b> via de beveiligde link uit de mail.')"></span>
      <button type="button" class="btn btn-secondary btn-sm" @click="copySignLink">
        {{ signLinkCopied ? $t('Gekopieerd ✓') : $t('Kopieer ondertekenlink') }}
      </button>
    </div>

    <div v-if="quote.invoice" class="decide accepted">
      <div class="decide-text">
        <strong>{{ $t('Gefactureerd.') }}</strong>
        <span>{{ $t('Deze offerte is omgezet in factuur :number.', { number: quote.invoice.number || $t('(concept)') }) }}</span>
      </div>
      <div class="decide-actions">
        <Link :href="route('invoices.show', quote.invoice.id)" class="btn btn-secondary btn-sm">{{ $t('Bekijk factuur') }}</Link>
      </div>
    </div>

    <div v-if="installmentsError" class="q-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ installmentsError }}
    </div>

    <!-- Termijnplan: bestaat er al één, toon de voortgang -->
    <div v-if="(quote.installments || []).length" class="card term-card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Termijnfacturen') }}</div>
          <div class="card-subtitle">{{ $t(':done van :total termijnen gefactureerd · samen :amount', { done: invoicedCount, total: quote.installments.length, amount: eur(quote.total) }) }}</div>
        </div>
        <button v-if="!quote.installments_locked" type="button" class="btn btn-secondary btn-sm" @click="deletePlan">{{ $t('Plan verwijderen') }}</button>
      </div>
      <div class="card-body" style="padding-top:6px;">
        <div v-for="(inst, i) in quote.installments" :key="inst.id" class="term-row">
          <div class="term-num" :class="{ done: inst.invoice }">{{ i + 1 }}</div>
          <div class="term-info">
            <div class="term-desc">{{ inst.description }}</div>
            <div class="term-sub">{{ Number(inst.percentage).toLocaleString(marketLocale) }}% · {{ eur(inst.amount) }} {{ $t('incl. btw') }}</div>
          </div>
          <div class="term-action">
            <Link v-if="inst.invoice" :href="route('invoices.show', inst.invoice.id)" class="pill pill-paid" style="text-decoration:none;">
              {{ inst.invoice.number || $t('Concept') }}
            </Link>
            <button v-else-if="inst.is_next" type="button" class="btn btn-primary btn-sm" @click="invoiceInstallment(inst)">{{ $t('Maak factuur') }}</button>
            <span v-else class="pill pill-draft">{{ $t('Wacht op eerdere termijn') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Termijnplan opstellen -->
    <div v-else-if="showPlanner && quote.can_installments" class="card term-card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('In termijnen factureren') }}</div>
          <div class="card-subtitle">{{ $t('Verdeel :amount over termijnen — de laatste termijn wordt automatisch het restant', { amount: eur(quote.total) }) }}</div>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button type="button" class="btn btn-secondary btn-sm" @click="applyPreset([30, 70])">30 / 70</button>
          <button type="button" class="btn btn-secondary btn-sm" @click="applyPreset([50, 50])">50 / 50</button>
          <button type="button" class="btn btn-secondary btn-sm" @click="applyPreset([33.33, 33.33, 33.34])">3 × ⅓</button>
        </div>
      </div>
      <div class="card-body">
        <div v-for="(row, i) in planRows" :key="i" class="term-edit-row">
          <input type="text" v-model="row.description" maxlength="200" :placeholder="$t('Omschrijving (komt op de factuur)')">
          <input type="number" v-model.number="row.percentage" min="0.01" max="100" step="0.01" class="num right" style="width:90px;">
          <span class="term-pct">%</span>
          <button type="button" class="li-remove-sm" :disabled="planRows.length <= 2" @click="removePlanRow(i)" :title="$t('Termijn verwijderen')">✕</button>
        </div>
        <div class="term-foot">
          <button type="button" class="btn btn-ghost btn-sm" @click="addPlanRow">+ {{ $t('Termijn toevoegen') }}</button>
          <div :style="{ color: Math.abs(planSum - 100) > 0.01 ? 'var(--brand-dark)' : 'var(--success)', fontWeight: 600, fontSize: '13px' }">
            {{ $t('Totaal') }}: {{ planSum.toLocaleString(marketLocale) }}%
          </div>
          <button type="button" class="btn btn-primary btn-sm" :disabled="Math.abs(planSum - 100) > 0.01" @click="savePlan">{{ $t('Plan opslaan') }}</button>
        </div>
      </div>
    </div>

    <div class="inv-detail">
      <div class="inv-detail-header">
        <div class="inv-detail-top">
          <div>
            <div class="inv-number">{{ quote.number || $t('— concept —') }}</div>
            <div style="margin-top:8px;">
              <span class="pill" :class="pillClass">{{ quote.status_label }}</span>
            </div>
          </div>
          <div style="text-align:right">
            <div class="inv-meta-label" style="margin-bottom:6px;">{{ $t('Totaal') }}</div>
            <div style="font-family:var(--font-display);font-weight:700;font-size:28px;letter-spacing:-0.02em;">{{ eur(quote.total) }}</div>
          </div>
        </div>
        <div class="inv-detail-meta">
          <div>
            <div class="inv-meta-label">{{ $t('Offertedatum') }}</div>
            <div class="inv-meta-value">{{ quote.quote_date_label }}</div>
          </div>
          <div>
            <div class="inv-meta-label">{{ $t('Geldig tot') }}</div>
            <div class="inv-meta-value">{{ quote.valid_until_label }}</div>
          </div>
          <div v-if="quote.reference">
            <div class="inv-meta-label">{{ $t('Referentie') }}</div>
            <div class="inv-meta-value mono">{{ quote.reference }}</div>
          </div>
        </div>
      </div>

      <div class="inv-body">
        <div class="inv-parties">
          <div>
            <div class="inv-party-label">{{ $t('Van') }}</div>
            <div class="inv-party-name">{{ company.name }}</div>
            <div v-if="company.address_line" class="inv-party-line">{{ company.address_line }}</div>
            <div v-if="company.postal_code || company.city" class="inv-party-line">{{ company.postal_code }} {{ company.city }}</div>
          </div>
          <div>
            <div class="inv-party-label">{{ $t('Voor') }}</div>
            <div class="inv-party-name">{{ quote.customer_name }}</div>
            <div v-if="quote.customer_address_line" class="inv-party-line">{{ quote.customer_address_line }}</div>
            <div v-if="quote.customer_postal_code || quote.customer_city" class="inv-party-line">
              {{ quote.customer_postal_code }} {{ quote.customer_city }}
            </div>
            <div v-if="quote.customer_email" class="inv-party-line">{{ quote.customer_email }}</div>
          </div>
        </div>

        <div v-if="quote.intro" style="margin-bottom:24px;font-size:14px;color:var(--text-2);line-height:1.65;white-space:pre-line;">{{ quote.intro }}</div>

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
            <tr v-for="line in quote.lines" :key="line.id">
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
            <span class="value mono">{{ eur(quote.subtotal) }}</span>
          </div>
          <div v-for="(amount, rate) in quote.vat_breakdown" :key="rate" class="inv-total-row">
            <span class="label">{{ $t('BTW') }} {{ Number(rate) }}%</span>
            <span class="value mono">{{ eur(amount) }}</span>
          </div>
          <div class="inv-total-row grand">
            <span class="label">{{ $t('Totaal') }}</span>
            <span class="value mono">{{ eur(quote.total) }}</span>
          </div>
        </div>

        <div v-if="quote.notes" style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);font-size:13px;color:var(--text-3);">
          <div style="margin-bottom:8px;color:var(--text-2);font-weight:500;">{{ $t('Opmerking') }}</div>
          {{ quote.notes }}
        </div>
      </div>
    </div>

    <!-- Bijlagen: gaan mee met de offertemail (voor de klant) of blijven intern -->
    <div class="card" style="margin-top:16px;">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Bijlagen') }}</div>
          <div class="card-subtitle">{{ $t('Bijv. een specificatie of plan van aanpak — gaat mee met de offertemail naar de klant') }}</div>
        </div>
        <div>
          <input ref="attFileInput" type="file" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="display:none" @change="uploadAttachments">
          <button class="btn btn-secondary btn-sm" :disabled="attUploading" @click="attFileInput?.click()">
            {{ attUploading ? $t('Uploaden…') : $t('Bijlage toevoegen') }}
          </button>
        </div>
      </div>
      <div class="card-body">
        <div v-if="!quote.attachments || quote.attachments.length === 0" class="qa-empty">
          {{ $t('Nog geen bijlagen. PDF, PNG, JPG of WEBP · max. 10 MB per bestand.') }}
        </div>
        <div v-else>
          <div v-for="att in quote.attachments" :key="att.id" class="qa-row">
            <span class="qa-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </span>
            <div class="qa-info">
              <a :href="route('attachments.show', att.id)" target="_blank" class="qa-name">{{ att.filename }}</a>
              <div class="qa-meta">{{ att.size_formatted }} · {{ att.uploaded_at_label }}</div>
            </div>
            <button
              class="btn btn-ghost btn-sm"
              :title="att.for_customer ? $t('Gaat mee met de offertemail — klik om alleen intern te maken') : $t('Alleen intern — klik om mee te sturen naar de klant')"
              @click="toggleAttachmentVisibility(att)"
            >
              {{ att.for_customer ? $t('Voor de klant ✓') : $t('Alleen intern') }}
            </button>
            <a :href="route('attachments.download', att.id)" class="btn btn-ghost btn-sm">{{ $t('Download') }}</a>
            <button class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="removeAttachment(att)">{{ $t('Verwijder') }}</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Modal "markeren als geaccepteerd" */
.modal-overlay { position: fixed; inset: 0; background: rgba(28, 25, 23, 0.45); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 20px; }
.modal { background: var(--surface); border-radius: var(--r-lg); width: 100%; max-width: 520px; max-height: calc(100vh - 40px); overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
.modal-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 24px; border-bottom: 1px solid var(--border); }
.modal-title { font-family: var(--font-display); font-weight: 600; font-size: 18px; }
.modal-body { padding: 22px 24px; }
.modal-text { margin: 0 0 14px; font-size: 13.5px; line-height: 1.6; color: var(--text-2); }
.modal-footer { padding: 14px 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 10px; background: var(--surface-2); border-radius: 0 0 var(--r-lg) var(--r-lg); }
.icon-btn { width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); background: none; border: none; cursor: pointer; }
.icon-btn:hover { background: var(--surface-2); }
.opt { display: flex; gap: 12px; align-items: flex-start; border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; cursor: pointer; transition: border-color .15s, background .15s; }
.opt:hover { background: var(--surface-2); }
.opt.on { border-color: var(--success); background: var(--success-bg); }
.opt.off { cursor: default; opacity: .7; }
.opt input { margin-top: 3px; width: 16px; height: 16px; accent-color: var(--success); flex: none; }
.opt-title { font-weight: 600; font-size: 13.5px; }
.opt-sub { font-size: 12px; color: var(--text-3); margin-top: 2px; line-height: 1.5; }

/* Bijlagen */
.qa-empty { color: var(--text-3); font-size: 12.5px; background: var(--surface-2); border: 1px dashed var(--border-strong); border-radius: 9px; padding: 13px 15px; }
.qa-row { display: flex; align-items: center; gap: 11px; border: 1px solid var(--border); border-radius: 9px; padding: 9px 12px; margin-bottom: 8px; flex-wrap: wrap; }
.qa-icon { width: 32px; height: 32px; border-radius: 7px; background: var(--surface-2); color: var(--text-3); display: inline-flex; align-items: center; justify-content: center; flex: none; }
.qa-icon svg { width: 16px; height: 16px; }
.qa-info { flex: 1; min-width: 160px; }
.qa-name { font-weight: 600; font-size: 13px; color: var(--text); word-break: break-word; }
.qa-name:hover { color: var(--brand); }
.qa-meta { font-size: 11.5px; color: var(--text-3); margin-top: 1px; }

/* Documentweergave — zelfde vormgeving als de factuurpagina. Lokaal
   gedefinieerd zodat de offerte er ook goed uitziet wanneer je er direct
   op binnenkomt zonder eerst een factuur te openen. */
.inv-detail { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); overflow: hidden; }
.inv-detail-header { background: linear-gradient(180deg, var(--brand-tint) 0%, var(--surface) 100%); padding: 28px 32px; border-bottom: 1px solid var(--border); }
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

.sig-card {
  display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap;
  background: var(--success-bg); border: 1px solid var(--success-border); border-radius: 12px;
  padding: 14px 18px; margin-bottom: 16px;
}
.sig-title { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 13.5px; color: var(--success); }
.sig-line { font-size: 13px; color: var(--text-2); margin-top: 4px; }
.sig-meta { font-size: 11.5px; color: var(--text-3); margin-top: 2px; }
.sig-img { max-height: 54px; max-width: 200px; background: #fff; border: 1px solid var(--success-border); border-radius: 8px; padding: 4px 10px; }

.sig-share {
  display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
  background: var(--surface); border: 1px dashed var(--border-strong, #D6D3D1); border-radius: 12px;
  padding: 12px 18px; margin-bottom: 16px; font-size: 13px; color: var(--text-2);
}

/* Termijnfacturen */
.term-card { margin-bottom: 16px; }
.term-row { display: flex; align-items: center; gap: 14px; padding: 10px 0; border-bottom: 1px solid var(--border); }
.term-row:last-child { border-bottom: none; }
.term-num {
  flex: none; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  background: var(--surface-2); color: var(--text-3); font-weight: 700; font-size: 12.5px;
}
.term-num.done { background: var(--success-bg); color: var(--success); border: 1px solid var(--success-border); }
.term-info { flex: 1; min-width: 0; }
.term-desc { font-weight: 600; font-size: 13.5px; }
.term-sub { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.term-action { flex: none; }

.term-edit-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.term-edit-row input[type="text"] { flex: 1; min-width: 0; height: 34px; padding: 0 10px; font-size: 13px; border: 1px solid var(--border); border-radius: 6px; }
.term-edit-row input.num { height: 34px; padding: 0 8px; text-align: right; font-family: var(--font-mono); font-size: 13px; border: 1px solid var(--border); border-radius: 6px; }
.term-pct { color: var(--text-3); font-size: 13px; }
.li-remove-sm { width: 28px; height: 28px; border-radius: 6px; color: var(--text-4); }
.li-remove-sm:hover:not(:disabled) { color: var(--brand); background: var(--brand-tint); }
.li-remove-sm:disabled { opacity: 0.3; cursor: not-allowed; }
.term-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 10px; flex-wrap: wrap; }

@media (max-width: 760px) {
  .inv-detail-header, .inv-body { padding: 20px 16px; }
  .inv-detail-top { flex-direction: column; gap: 12px; }
  .inv-detail-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
  .inv-parties { grid-template-columns: minmax(0, 1fr); gap: 22px; }
  .inv-party-line { overflow-wrap: anywhere; }
  .inv-totals { width: 100%; margin-left: 0; }
  .inv-lines td:not([data-label]):not(.cell-primary) { display: none; }
}

.q-alert { display: flex; align-items: center; gap: 10px; background: var(--brand-tint); border: 1px solid var(--brand-border); color: var(--brand-darker); border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13.5px; }
.q-alert svg { width: 18px; height: 18px; flex: none; }

.decide {
  display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
  background: var(--surface); border: 1px solid var(--border-strong);
  border-radius: 12px; padding: 16px 20px; margin-bottom: 16px;
}
.decide.accepted { background: var(--success-bg); border-color: var(--success-border); }
.decide-text { flex: 1; min-width: 220px; font-size: 13.5px; color: var(--text-2); line-height: 1.6; }
.decide-text strong { display: block; color: var(--text); font-size: 14px; margin-bottom: 2px; }
.decide-actions { display: flex; gap: 8px; }

@media (max-width: 640px) {
  .decide-actions { width: 100%; }
  .decide-actions .btn { flex: 1; }
}
</style>
