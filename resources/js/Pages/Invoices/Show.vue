<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { eur } from '@/format.js';
import { ref } from 'vue';

const props = defineProps({
  invoice: Object,
  company: Object,
});

const showPaymentModal = ref(false);

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
        <button v-if="['sent','partial','overdue'].includes(invoice.status)" class="btn btn-primary btn-sm" @click="showPaymentModal = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          Betaling registreren
        </button>
      </div>
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

        <table class="inv-lines">
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
              <td>
                <div style="font-weight:500;margin-bottom:2px;">{{ line.description }}</div>
                <div v-if="line.details" style="font-size:12px;color:var(--text-3);">{{ line.details }}</div>
              </td>
              <td class="mono" style="text-align:right">{{ Number(line.quantity) }}</td>
              <td class="mono" style="text-align:right">{{ eur(line.unit_price) }}</td>
              <td style="text-align:center">{{ Number(line.vat_rate) }}%</td>
              <td class="mono" style="text-align:right">{{ eur(line.line_subtotal) }}</td>
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

        <!-- Payments -->
        <div v-if="invoice.payments && invoice.payments.length > 0" style="margin-top:28px;">
          <div style="font-family:var(--font-display);font-weight:600;font-size:16px;margin-bottom:12px;">Betalingen</div>
          <table class="payments-table">
            <thead>
              <tr><th>Datum</th><th>Methode</th><th>Referentie</th><th class="right">Bedrag</th></tr>
            </thead>
            <tbody>
              <tr v-for="p in invoice.payments" :key="p.id">
                <td>{{ p.paid_on }}</td>
                <td>{{ p.method }}</td>
                <td>{{ p.reference || '—' }}</td>
                <td class="num right">{{ eur(p.amount) }}</td>
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
</style>
