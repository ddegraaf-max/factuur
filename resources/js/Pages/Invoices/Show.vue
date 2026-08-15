<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { eur } from '@/format.js';
import { computed, ref } from 'vue';

const props = defineProps({
  invoice: Object,
  company: Object,
});

const showPaymentModal = ref(false);
const showRecurringModal = ref(false);
const showCreditModal = ref(false);

// Foutmeldingen die niet bij één invoerveld horen (incasso, creditnota, UBL…).
const page = usePage();
const pageError = computed(() => {
  const e = page.props.errors || {};
  return e.incasso || e.credit || e.ubl || e.status || e.delete || null;
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
  if (confirm('Creditnota definitief maken? Er wordt een definitief creditnotanummer toegekend.')) {
    router.post(route('invoices.credit.finalize', props.invoice.id));
  }
};

/* ---------- Incasso ---------- */
const phaseLabels = {
  minnelijk: 'Minnelijk traject',
  gerechtelijk: 'Gerechtelijke procedure',
  executie: 'Executie',
};

const canIncasso = computed(() =>
  !props.invoice.is_credit && ['sent', 'partial', 'overdue'].includes(props.invoice.status)
);

const sendToIncasso = () => {
  const msg = `Factuur ${props.invoice.number} overdragen aan de incassopartner?\n\n`
    + 'Het volledige dossier (factuur, betalingen en het herinneringsverloop) wordt per e-mail verstuurd. '
    + 'Dit kun je niet ongedaan maken.';
  if (confirm(msg)) {
    router.post(route('incasso.send', props.invoice.id), {}, { preserveScroll: true });
  }
};

/* ---------- Bijlagen ---------- */
const fileInput = ref(null);
const uploadForm = useForm({ files: [] });

const uploadFiles = (event) => {
  const files = Array.from(event.target.files || []);
  if (!files.length) return;

  uploadForm.files = files;
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
  if (confirm(`Bijlage "${att.filename}" verwijderen?`)) {
    router.delete(route('attachments.destroy', att.id), { preserveScroll: true });
  }
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
  amount: props.invoice.remaining,
  paid_on: new Date().toISOString().slice(0, 10),
  method: 'bank_transfer',
  reference: '',
  notes: '',
});

const recordPayment = () => {
  paymentForm.post(route('invoices.payments.store', props.invoice.id), {
    onSuccess: () => {
      showPaymentModal.value = false;
      paymentForm.reset();
    },
  });
};

const sendInvoice = () => {
  router.post(route('invoices.send', props.invoice.id));
};

const deleteInvoice = () => {
  if (confirm('Concept verwijderen?')) {
    router.delete(route('invoices.destroy', props.invoice.id));
  }
};
</script>

<template>
  <Head :title="`Factuur ${invoice.number || 'concept'}`" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        Verkoop / <Link :href="route('invoices.index')" style="color:var(--text-3);">Facturen</Link> /
        <span class="breadcrumb-current">{{ invoice.number || 'Concept' }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('invoices.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          Terug
        </Link>
        <h1 class="page-title">Factuur {{ invoice.number || '— concept —' }}</h1>
        <p class="page-subtitle">
          <template v-if="invoice.status === 'draft'">Concept · nog niet verstuurd</template>
          <template v-else-if="invoice.sent_at_label">Verstuurd op {{ invoice.sent_at_label }}</template>
        </p>
      </div>
      <div class="page-actions">
        <a :href="route('invoices.pdf', invoice.id)" target="_blank" class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          PDF
        </a>
        <a v-if="invoice.status !== 'draft'" :href="route('invoices.ubl', invoice.id)" class="btn btn-secondary btn-sm" title="Download als UBL 2.1 (e-factuur, NLCIUS)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
          UBL
        </a>
        <button v-if="!invoice.is_credit" class="btn btn-secondary btn-sm" title="Maak hier een terugkerende factuur van" @click="showRecurringModal = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          Maak terugkerend
        </button>
        <template v-if="invoice.status === 'draft'">
          <Link :href="route('invoices.edit', invoice.id)" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><polygon points="18.5 2.5 21.5 5.5 12 15 9 15 9 12 18.5 2.5"/></svg>
            Bewerken
          </Link>
          <button class="btn btn-danger btn-sm" @click="deleteInvoice">Verwijder</button>
          <button class="btn btn-primary btn-sm" @click="sendInvoice">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Versturen
          </button>
        </template>
        <button v-if="canCredit" class="btn btn-secondary btn-sm" title="Maak een creditnota op deze factuur" @click="showCreditModal = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg>
          Creditnota
        </button>
        <button v-if="canIncasso" class="btn btn-secondary btn-sm" title="Draag deze factuur over aan de incassopartner" @click="sendToIncasso">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
          Naar incasso
        </button>
        <button v-if="invoice.is_credit && invoice.status === 'draft'" class="btn btn-primary btn-sm" @click="finalizeCredit">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          Creditnota definitief maken
        </button>
        <button v-if="['sent','partial','overdue'].includes(invoice.status)" class="btn btn-primary btn-sm" @click="showPaymentModal = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          Betaling registreren
        </button>
      </div>
    </div>

    <div v-if="pageError" class="inv-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ pageError }}
    </div>

    <div class="inv-detail">
      <div class="inv-detail-header">
        <div class="inv-detail-top">
          <div>
            <div class="inv-number">{{ invoice.number || '— concept —' }}</div>
            <div style="margin-top:8px;">
              <StatusPill :status="invoice.status" :days-overdue="invoice.days_overdue" />
            </div>
          </div>
          <div style="text-align:right">
            <div class="inv-meta-label" style="margin-bottom:6px;">Totaal</div>
            <div style="font-family:var(--font-display);font-weight:700;font-size:28px;letter-spacing:-0.02em;">{{ eur(invoice.total) }}</div>
            <div v-if="invoice.paid_total > 0" style="font-size:12px;color:var(--success);margin-top:4px;">
              {{ eur(invoice.paid_total) }} betaald · {{ eur(invoice.remaining) }} open
            </div>
          </div>
        </div>
        <div class="inv-detail-meta">
          <div>
            <div class="inv-meta-label">Factuurdatum</div>
            <div class="inv-meta-value">{{ invoice.invoice_date_label }}</div>
          </div>
          <div>
            <div class="inv-meta-label">Vervaldatum</div>
            <div class="inv-meta-value">{{ invoice.due_date_label }}</div>
          </div>
          <div v-if="invoice.reference">
            <div class="inv-meta-label">Referentie</div>
            <div class="inv-meta-value mono">{{ invoice.reference }}</div>
          </div>
          <div>
            <div class="inv-meta-label">Betalingstermijn</div>
            <div class="inv-meta-value">{{ invoice.payment_terms }} dagen</div>
          </div>
        </div>
      </div>

      <div class="inv-body">
        <div class="inv-parties">
          <div>
            <div class="inv-party-label">Van</div>
            <div class="inv-party-name">{{ company.name }}</div>
            <div v-if="company.address_line" class="inv-party-line">{{ company.address_line }}</div>
            <div v-if="company.postal_code || company.city" class="inv-party-line">{{ company.postal_code }} {{ company.city }}</div>
            <div v-if="company.kvk_number || company.vat_number" class="inv-party-line">
              <span v-if="company.kvk_number">KVK {{ company.kvk_number }}</span>
              <span v-if="company.kvk_number && company.vat_number"> · </span>
              <span v-if="company.vat_number">BTW {{ company.vat_number }}</span>
            </div>
            <div v-if="company.iban" class="inv-party-line">IBAN {{ company.iban }}</div>
          </div>
          <div>
            <div class="inv-party-label">Aan</div>
            <div class="inv-party-name">{{ invoice.customer_name }}</div>
            <div v-if="invoice.customer_address_line" class="inv-party-line">{{ invoice.customer_address_line }}</div>
            <div v-if="invoice.customer_postal_code || invoice.customer_city" class="inv-party-line">
              {{ invoice.customer_postal_code }} {{ invoice.customer_city }}
            </div>
            <div v-if="invoice.customer_kvk_number || invoice.customer_vat_number" class="inv-party-line">
              <span v-if="invoice.customer_kvk_number">KVK {{ invoice.customer_kvk_number }}</span>
              <span v-if="invoice.customer_kvk_number && invoice.customer_vat_number"> · </span>
              <span v-if="invoice.customer_vat_number">BTW {{ invoice.customer_vat_number }}</span>
            </div>
            <div v-if="invoice.customer_email" class="inv-party-line">{{ invoice.customer_email }}</div>
          </div>
        </div>

        <table class="inv-lines stacked-table">
          <thead>
            <tr>
              <th style="width:55%">Omschrijving</th>
              <th style="text-align:right">Aantal</th>
              <th style="text-align:right">Prijs</th>
              <th style="text-align:center">BTW</th>
              <th style="text-align:right">Totaal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="line in invoice.lines" :key="line.id">
              <td class="cell-primary">
                <div style="font-weight:500;margin-bottom:2px;">{{ line.description }}</div>
                <div v-if="line.details" style="font-size:12px;color:var(--text-3);">{{ line.details }}</div>
              </td>
              <td class="mono" style="text-align:right" data-label="Aantal">{{ Number(line.quantity) }}</td>
              <td class="mono" style="text-align:right" data-label="Prijs">{{ eur(line.unit_price) }}</td>
              <td style="text-align:center" data-label="BTW">{{ Number(line.vat_rate) }}%</td>
              <td class="mono" style="text-align:right" data-label="Totaal">{{ eur(line.line_subtotal) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="inv-totals">
          <div class="inv-total-row">
            <span class="label">Subtotaal</span>
            <span class="value mono">{{ eur(invoice.subtotal) }}</span>
          </div>
          <div v-for="(amount, rate) in invoice.vat_breakdown" :key="rate" class="inv-total-row">
            <span class="label">BTW {{ Number(rate) }}%</span>
            <span class="value mono">{{ eur(amount) }}</span>
          </div>
          <div class="inv-total-row grand">
            <span class="label">Totaal</span>
            <span class="value mono">{{ eur(invoice.total) }}</span>
          </div>
        </div>

        <div v-if="invoice.notes" style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);font-size:13px;color:var(--text-3);">
          <div style="margin-bottom:8px;color:var(--text-2);font-weight:500;">Opmerking</div>
          {{ invoice.notes }}
        </div>

        <!-- Incasso-dossier -->
        <div v-if="invoice.status === 'incasso'" class="inc-panel">
          <div class="inc-head">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
            <div>
              <div class="inc-title">Overgedragen aan incasso</div>
              <div class="inc-sub">{{ invoice.incasso_handler }}</div>
            </div>
            <Link :href="route('incasso.index')" class="btn btn-secondary btn-sm">Alle dossiers</Link>
          </div>
          <div class="inc-meta">
            <div><span class="inv-meta-label">Dossiernummer</span><span class="mono">{{ invoice.incasso_reference }}</span></div>
            <div><span class="inv-meta-label">Overgedragen op</span><span>{{ invoice.incasso_sent_at_label || '—' }}</span></div>
            <div><span class="inv-meta-label">Fase</span><span>{{ phaseLabels[invoice.incasso_phase] || invoice.incasso_phase }}</span></div>
          </div>
        </div>

        <!-- Creditnota's op deze factuur -->
        <div v-if="invoice.credit_notes && invoice.credit_notes.length > 0" style="margin-top:28px;">
          <div class="sect-title">Creditnota's op deze factuur</div>
          <table class="payments-table stacked-table">
            <thead>
              <tr><th>Nummer</th><th>Datum</th><th>Status</th><th class="right">Bedrag</th></tr>
            </thead>
            <tbody>
              <tr v-for="c in invoice.credit_notes" :key="c.id" style="cursor:pointer" @click="router.get(route('invoices.show', c.id))">
                <td class="cell-primary mono">{{ c.number || 'Concept' }}</td>
                <td data-label="Datum">{{ c.invoice_date_label }}</td>
                <td data-label="Status"><StatusPill :status="c.status" /></td>
                <td class="num right" data-label="Bedrag">{{ eur(c.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Gecrediteerde factuur (bij een creditnota) -->
        <div v-if="invoice.is_credit && invoice.original_invoice" style="margin-top:28px;">
          <div class="sect-title">Hoort bij factuur</div>
          <Link :href="route('invoices.show', invoice.original_invoice.id)" class="btn btn-secondary btn-sm">
            {{ invoice.original_invoice.number }} bekijken →
          </Link>
        </div>

        <!-- Bijlagen -->
        <div style="margin-top:28px;">
          <div class="sect-head">
            <div class="sect-title" style="margin:0;">Bijlagen</div>
            <div>
              <input ref="fileInput" type="file" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="display:none" @change="uploadFiles">
              <button class="btn btn-secondary btn-sm" :disabled="uploadForm.processing" @click="fileInput?.click()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ uploadForm.processing ? 'Bezig met uploaden…' : 'Bestand toevoegen' }}
              </button>
            </div>
          </div>

          <div v-if="uploadForm.errors.files || uploadForm.errors['files.0']" class="field-error" style="margin-bottom:10px;">
            {{ uploadForm.errors.files || uploadForm.errors['files.0'] }}
          </div>

          <div v-if="!invoice.attachments || invoice.attachments.length === 0" class="att-empty">
            Nog geen bijlagen. Voeg bijvoorbeeld een opdrachtbevestiging, urenoverzicht of foto toe —
            deze gaan mee in het incassodossier. PDF, PNG, JPG of WEBP, max. 10 MB per bestand.
          </div>

          <div v-else class="att-list">
            <div v-for="att in invoice.attachments" :key="att.id" class="att-row">
              <span class="att-icon" :class="'att-' + att.kind">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              </span>
              <div class="att-info">
                <a :href="route('attachments.show', att.id)" target="_blank" class="att-name">{{ att.filename }}</a>
                <div class="att-meta">{{ att.size_formatted }} · toegevoegd op {{ att.uploaded_at_label }}</div>
              </div>
              <a :href="route('attachments.download', att.id)" class="btn btn-ghost btn-sm">Download</a>
              <button class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="removeAttachment(att)">Verwijder</button>
            </div>
          </div>
        </div>

        <!-- Payments -->
        <div v-if="invoice.payments && invoice.payments.length > 0" style="margin-top:28px;">
          <div style="font-family:var(--font-display);font-weight:600;font-size:16px;margin-bottom:12px;">Betalingen</div>
          <table class="payments-table stacked-table">
            <thead>
              <tr><th>Datum</th><th>Methode</th><th>Referentie</th><th class="right">Bedrag</th></tr>
            </thead>
            <tbody>
              <tr v-for="p in invoice.payments" :key="p.id">
                <td class="cell-primary">{{ p.paid_on }}</td>
                <td data-label="Methode">{{ p.method }}</td>
                <td data-label="Referentie">{{ p.reference || '—' }}</td>
                <td class="num right" data-label="Bedrag">{{ eur(p.amount) }}</td>
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
          <div class="modal-title">Betaling registreren</div>
          <button class="icon-btn" @click="showPaymentModal = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Bedrag *</label>
              <input type="number" v-model="paymentForm.amount" step="0.01" min="0.01" :max="invoice.remaining">
              <div v-if="paymentForm.errors.amount" class="field-error">{{ paymentForm.errors.amount }}</div>
            </div>
            <div class="form-group">
              <label>Betaaldatum *</label>
              <input type="date" v-model="paymentForm.paid_on">
            </div>
          </div>
          <div class="form-group">
            <label>Methode *</label>
            <select v-model="paymentForm.method">
              <option value="bank_transfer">Bankoverschrijving</option>
              <option value="ideal">iDEAL</option>
              <option value="cash">Contant</option>
              <option value="card">Pinpas / creditcard</option>
              <option value="other">Anders</option>
            </select>
          </div>
          <div class="form-group">
            <label>Referentie<span class="label-hint">(bijv. bankregel-omschrijving)</span></label>
            <input type="text" v-model="paymentForm.reference" maxlength="255">
          </div>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showPaymentModal = false">Annuleren</button>
            <button class="btn btn-primary btn-sm" @click="recordPayment" :disabled="paymentForm.processing">Registreren</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Creditnota modal -->
    <div v-if="showCreditModal" class="modal-overlay" @click.self="showCreditModal = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">Creditnota maken</div>
          <button class="icon-btn" @click="showCreditModal = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <p style="font-size:13px;color:var(--text-3);margin-bottom:16px;line-height:1.6;">
            Een creditnota corrigeert factuur <b>{{ invoice.number }}</b> van {{ invoice.customer_name }}.
            De oorspronkelijke factuur blijft ongewijzigd bestaan — zo blijft je administratie kloppen.
          </p>

          <label class="credit-opt" :class="{ on: creditForm.kind === 'full' }">
            <input type="radio" value="full" v-model="creditForm.kind">
            <div>
              <div class="credit-opt-title">Volledig crediteren</div>
              <div class="credit-opt-sub">Het hele bedrag van {{ eur(invoice.total) }} wordt teruggeboekt. De creditnota krijgt meteen een definitief nummer.</div>
            </div>
          </label>

          <label class="credit-opt" :class="{ on: creditForm.kind === 'partial' }">
            <input type="radio" value="partial" v-model="creditForm.kind">
            <div>
              <div class="credit-opt-title">Gedeeltelijk crediteren</div>
              <div class="credit-opt-sub">Je opent een concept waarin je zelf de regels en bedragen aanpast. Pas daarna maak je hem definitief.</div>
            </div>
          </label>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showCreditModal = false">Annuleren</button>
            <button class="btn btn-primary btn-sm" @click="createCredit" :disabled="creditForm.processing">Creditnota maken</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Recurring modal -->
    <div v-if="showRecurringModal" class="modal-overlay" @click.self="showRecurringModal = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">Maak terugkerend</div>
          <button class="icon-btn" @click="showRecurringModal = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <p style="font-size:13px;color:var(--text-3);margin-bottom:16px;line-height:1.6;">
            De regels van deze factuur worden elke periode automatisch opnieuw gefactureerd
            aan <b>{{ invoice.customer_name }}</b>. Beheren doe je via <b>Verkoop → Terugkerend</b>.
          </p>
          <div class="form-row">
            <div class="form-group">
              <label>Frequentie</label>
              <select v-model="recurringForm.frequency">
                <option value="weekly">Wekelijks</option>
                <option value="monthly">Maandelijks</option>
                <option value="quarterly">Per kwartaal</option>
                <option value="halfyearly">Per half jaar</option>
                <option value="yearly">Jaarlijks</option>
              </select>
            </div>
            <div class="form-group">
              <label>Eerste factuurdatum</label>
              <input type="date" v-model="recurringForm.next_run_on">
              <div v-if="recurringForm.errors.next_run_on" class="field-error">{{ recurringForm.errors.next_run_on }}</div>
            </div>
          </div>
          <div class="form-group">
            <label>Stopt automatisch na<span class="label-hint">(optioneel)</span></label>
            <input type="date" v-model="recurringForm.end_date">
            <div v-if="recurringForm.errors.end_date" class="field-error">{{ recurringForm.errors.end_date }}</div>
          </div>
          <div class="form-group">
            <label>Wijze</label>
            <select v-model="recurringForm.auto_send">
              <option :value="false">Als concept klaarzetten (zelf controleren en versturen)</option>
              <option :value="true">Direct versturen naar de klant</option>
            </select>
          </div>
          <div v-if="recurringForm.errors.recurring" class="field-error">{{ recurringForm.errors.recurring }}</div>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showRecurringModal = false">Annuleren</button>
            <button class="btn btn-primary btn-sm" @click="createRecurring" :disabled="recurringForm.processing">Profiel aanmaken</button>
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

/* Keuzeblokken in de creditnota-modal */
.credit-opt { display: flex; gap: 12px; align-items: flex-start; border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; margin-bottom: 10px; cursor: pointer; transition: border-color .15s, background .15s; }
.credit-opt:hover { background: var(--surface-2); }
.credit-opt.on { border-color: var(--brand); background: var(--brand-tint); }
.credit-opt input { margin-top: 3px; width: 16px; height: 16px; accent-color: var(--brand); flex: none; }
.credit-opt-title { font-weight: 600; font-size: 14px; }
.credit-opt-sub { font-size: 12.5px; color: var(--text-3); margin-top: 3px; line-height: 1.5; }

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
