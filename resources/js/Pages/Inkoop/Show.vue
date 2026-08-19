<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { computed, ref } from 'vue';

const props = defineProps({
  purchase: Object,
});

const methodLabels = {
  bank_transfer: 'Bankoverschrijving',
  ideal: 'iDEAL',
  direct_debit: 'Automatische incasso',
  card: 'Pinpas / creditcard',
  cash: 'Contant',
  other: 'Anders',
};

const rateLabel = (r) => Number(r) === 0 ? '0% / vrijgesteld' : `${Number(r)}%`;

/* ---------- Betaald markeren ---------- */
const showPaidForm = ref(false);
const paidForm = useForm({
  paid_at: new Date().toISOString().slice(0, 10),
  payment_method: 'bank_transfer',
});

const markPaid = () => {
  paidForm.post(route('purchases.paid', props.purchase.id), {
    preserveScroll: true,
    onSuccess: () => { showPaidForm.value = false; },
  });
};

const reopen = () => {
  if (confirm('Deze factuur weer op "open" zetten?')) {
    router.post(route('purchases.reopen', props.purchase.id), {}, { preserveScroll: true });
  }
};

const destroy = () => {
  if (confirm('Inkoopfactuur én bijbehorende bijlagen definitief verwijderen?')) {
    router.delete(route('purchases.destroy', props.purchase.id));
  }
};

const makeRecurring = () => {
  if (confirm(`"${props.purchase.supplier_name}" voortaan automatisch maandelijks inboeken als vaste last?\n\nDe frequentie kun je daarna nog aanpassen.`)) {
    router.post(route('purchases.recurring.from', props.purchase.id));
  }
};

/* ---------- Bijlagen ---------- */
const fileInput = ref(null);
const uploadForm = useForm({ files: [] });

const uploadFiles = (event) => {
  const files = Array.from(event.target.files || []);
  if (!files.length) return;
  uploadForm.files = files;
  uploadForm.post(route('purchases.attachments.store', props.purchase.id), {
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

// Eerste afbeelding of PDF groot tonen naast de gegevens.
const previewAttachment = computed(() =>
  (props.purchase.attachments || []).find(a => a.kind === 'image')
  || (props.purchase.attachments || []).find(a => a.kind === 'pdf')
  || null
);
</script>

<template>
  <Head :title="`Inkoop · ${purchase.supplier_name}`" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        Inkoop / <Link :href="route('purchases.index')" style="color:var(--text-3);">Inkoopfacturen</Link> /
        <span class="breadcrumb-current">{{ purchase.supplier_name }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('purchases.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          Terug
        </Link>
        <h1 class="page-title">{{ purchase.supplier_name }}</h1>
        <p class="page-subtitle">
          Inkoopfactuur<template v-if="purchase.supplier_reference"> {{ purchase.supplier_reference }}</template>
          · {{ purchase.invoice_date_label }}
        </p>
      </div>
      <div class="page-actions">
        <button v-if="purchase.status === 'open'" class="btn btn-primary btn-sm" @click="showPaidForm = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          Markeer als betaald
        </button>
        <button v-else class="btn btn-secondary btn-sm" @click="reopen">Heropen</button>
        <Link :href="route('purchases.edit', purchase.id)" class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><polygon points="18.5 2.5 21.5 5.5 12 15 9 15 9 12 18.5 2.5"/></svg>
          Bewerken
        </Link>
        <button class="btn btn-secondary btn-sm" title="Boek deze kosten voortaan automatisch periodiek in" @click="makeRecurring">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          Maak terugkerend
        </button>
        <button class="btn btn-danger btn-sm" @click="destroy">Verwijder</button>
      </div>
    </div>

    <div class="pu-grid" :class="{ 'has-preview': previewAttachment }">
      <div>
        <!-- Kerngegevens -->
        <div class="card">
          <div class="pu-head">
            <div>
              <span v-if="purchase.status === 'paid'" class="pill pill-paid">Betaald</span>
              <span v-else-if="purchase.is_overdue" class="pill pill-overdue">{{ purchase.days_overdue }} d. over tijd</span>
              <span v-else class="pill pill-sent">Open</span>
            </div>
            <div class="pu-total">
              <div class="pu-total-label">Totaal incl. BTW</div>
              <div class="pu-total-value">{{ eur(purchase.total) }}</div>
            </div>
          </div>

          <div class="pu-meta">
            <div>
              <div class="pu-meta-label">Factuurdatum</div>
              <div class="pu-meta-value">{{ purchase.invoice_date_label }}</div>
            </div>
            <div>
              <div class="pu-meta-label">Vervaldatum</div>
              <div class="pu-meta-value">{{ purchase.due_date_label || '—' }}</div>
            </div>
            <div>
              <div class="pu-meta-label">Categorie</div>
              <div class="pu-meta-value">{{ purchase.category || '—' }}</div>
            </div>
            <div v-if="purchase.status === 'paid'">
              <div class="pu-meta-label">Betaald op</div>
              <div class="pu-meta-value">
                {{ purchase.paid_at_label }}
                <span v-if="purchase.payment_method" class="muted"> · {{ methodLabels[purchase.payment_method] }}</span>
              </div>
            </div>
          </div>

          <div class="pu-body">
            <table class="pu-table">
              <thead>
                <tr>
                  <th>BTW-tarief</th>
                  <th class="right">Grondslag excl.</th>
                  <th class="right">BTW</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(line, idx) in purchase.vat_lines" :key="idx">
                  <td>{{ rateLabel(line.rate) }}</td>
                  <td class="right num">{{ eur(line.base) }}</td>
                  <td class="right num">{{ eur(line.vat) }}</td>
                </tr>
                <tr class="total">
                  <td>Totaal</td>
                  <td class="right num">{{ eur(purchase.subtotal) }}</td>
                  <td class="right num">{{ eur(purchase.vat_total) }}</td>
                </tr>
              </tbody>
            </table>

            <div class="pu-vat-note">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
              {{ eur(purchase.vat_total) }} voorbelasting — telt automatisch mee in rubriek 5b van je
              <Link :href="route('vat.index')" style="color:var(--brand);font-weight:500;">BTW-overzicht</Link>.
            </div>

            <div v-if="purchase.notes" class="pu-notes">
              <div class="pu-meta-label" style="margin-bottom:6px;">Notities</div>
              {{ purchase.notes }}
            </div>

            <!-- Bijlagen -->
            <div class="sect-head" style="margin-top:22px;">
              <div class="pu-meta-label" style="margin:0;font-size:12px;">Bijlagen</div>
              <div>
                <input ref="fileInput" type="file" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="display:none" @change="uploadFiles">
                <button class="btn btn-secondary btn-sm" :disabled="uploadForm.processing" @click="fileInput?.click()">
                  {{ uploadForm.processing ? 'Uploaden…' : 'Bijlage toevoegen' }}
                </button>
              </div>
            </div>
            <div v-if="!purchase.attachments || purchase.attachments.length === 0" class="pu-att-empty">
              Nog geen bon of factuur geüpload. Voeg een foto of PDF toe zodat je administratie compleet is.
            </div>
            <div v-else class="pu-att-list">
              <div v-for="att in purchase.attachments" :key="att.id" class="pu-att-row">
                <span class="pu-att-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </span>
                <div class="pu-att-info">
                  <a :href="route('attachments.show', att.id)" target="_blank" class="pu-att-name">{{ att.filename }}</a>
                  <div class="pu-att-meta">{{ att.size_formatted }} · {{ att.uploaded_at_label }}</div>
                </div>
                <a :href="route('attachments.download', att.id)" class="btn btn-ghost btn-sm">Download</a>
                <button class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="removeAttachment(att)">Verwijder</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Grote preview van de bon -->
      <div v-if="previewAttachment" class="card pu-preview-card">
        <div class="pu-preview-head">
          <span>{{ previewAttachment.filename }}</span>
          <a :href="route('attachments.show', previewAttachment.id)" target="_blank" class="link-btn">Open groot</a>
        </div>
        <img
          v-if="previewAttachment.kind === 'image'"
          :src="route('attachments.show', previewAttachment.id)"
          class="pu-preview-img"
          alt="Bon"
        >
        <iframe
          v-else
          :src="route('attachments.show', previewAttachment.id)"
          class="pu-preview-pdf"
          title="Factuur-PDF"
        ></iframe>
      </div>
    </div>

    <!-- Betaald-modal -->
    <div v-if="showPaidForm" class="modal-overlay" @click.self="showPaidForm = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">Markeer als betaald</div>
          <button class="icon-btn" @click="showPaidForm = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Betaald op *</label>
              <input type="date" v-model="paidForm.paid_at">
              <div v-if="paidForm.errors.paid_at" class="field-error">{{ paidForm.errors.paid_at }}</div>
            </div>
            <div class="form-group">
              <label>Betaalwijze</label>
              <select v-model="paidForm.payment_method">
                <option value="bank_transfer">Bankoverschrijving</option>
                <option value="ideal">iDEAL</option>
                <option value="direct_debit">Automatische incasso</option>
                <option value="card">Pinpas / creditcard</option>
                <option value="cash">Contant</option>
                <option value="other">Anders</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="showPaidForm = false">Annuleren</button>
            <button class="btn btn-primary btn-sm" :disabled="paidForm.processing" @click="markPaid">Opslaan</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.pu-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; align-items: start; }
.pu-grid.has-preview { grid-template-columns: minmax(0, 1fr) minmax(280px, 420px); }

.pu-head {
  display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
  padding: 22px 24px 0;
}
.pu-total { text-align: right; }
.pu-total-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; }
.pu-total-value { font-family: var(--font-display); font-weight: 700; font-size: 26px; letter-spacing: -0.02em; margin-top: 2px; }

.pu-meta {
  display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px;
  padding: 18px 24px; border-bottom: 1px solid var(--border); margin-top: 14px;
}
.pu-meta-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; margin-bottom: 3px; }
.pu-meta-value { font-size: 13.5px; font-weight: 500; }

.pu-body { padding: 20px 24px 24px; }

.pu-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.pu-table th {
  text-align: left; padding: 8px 10px; font-size: 11px; font-weight: 600;
  text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3);
  background: var(--surface-2); border-bottom: 1px solid var(--border);
}
.pu-table td { padding: 10px; border-bottom: 1px solid var(--border); }
.pu-table .right { text-align: right; }
.pu-table .num { font-family: var(--font-mono); }
.pu-table .total td { font-weight: 700; border-bottom: none; }

.pu-vat-note {
  display: flex; align-items: center; gap: 9px;
  margin-top: 14px; padding: 11px 14px;
  background: var(--success-bg); border: 1px solid var(--success-border); border-radius: 9px;
  font-size: 12.5px; color: var(--success);
}
.pu-vat-note svg { width: 15px; height: 15px; flex: none; }

.pu-notes { margin-top: 18px; font-size: 13px; color: var(--text-2); line-height: 1.6; white-space: pre-wrap; }

.sect-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
.pu-att-empty { color: var(--text-3); font-size: 12.5px; background: var(--surface-2); border: 1px dashed var(--border-strong); border-radius: 9px; padding: 13px 15px; }
.pu-att-list { display: flex; flex-direction: column; gap: 8px; }
.pu-att-row { display: flex; align-items: center; gap: 11px; border: 1px solid var(--border); border-radius: 9px; padding: 9px 12px; }
.pu-att-icon { width: 32px; height: 32px; border-radius: 7px; background: var(--surface-2); color: var(--text-3); display: inline-flex; align-items: center; justify-content: center; flex: none; }
.pu-att-icon svg { width: 16px; height: 16px; }
.pu-att-info { flex: 1; min-width: 0; }
.pu-att-name { font-weight: 600; font-size: 13px; color: var(--text); word-break: break-word; }
.pu-att-name:hover { color: var(--brand); }
.pu-att-meta { font-size: 11.5px; color: var(--text-3); margin-top: 1px; }

.pu-preview-card { overflow: hidden; }
.pu-preview-head {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 12px 16px; border-bottom: 1px solid var(--border);
  font-size: 12.5px; font-weight: 600;
}
.pu-preview-head span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pu-preview-img { display: block; width: 100%; max-height: 640px; object-fit: contain; background: var(--surface-2); }
.pu-preview-pdf { display: block; width: 100%; height: 640px; border: none; background: var(--surface-2); }

.modal-overlay { position: fixed; inset: 0; background: rgba(28,25,23,0.4); z-index: 100; display: flex; align-items: flex-start; justify-content: center; padding: 60px 20px; overflow-y: auto; }
.modal { background: var(--surface); border-radius: var(--r-lg); box-shadow: var(--shadow-lg); width: 100%; max-width: 480px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 1px solid var(--border); }
.modal-title { font-family: var(--font-display); font-weight: 600; font-size: 17px; }
.modal-body { padding: 22px; }
.modal-footer { padding: 14px 22px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--surface-2); border-radius: 0 0 var(--r-lg) var(--r-lg); }
.icon-btn { width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.icon-btn:hover { background: var(--surface-2); }

@media (max-width: 960px) {
  .pu-grid.has-preview { grid-template-columns: minmax(0, 1fr); }
  .pu-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
