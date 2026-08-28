<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { reactive, ref } from 'vue';

const props = defineProps({
  transactions: Object,
  tab: String,
  counts: Object,
  open_invoices: Array,
  open_purchases: Array,
});

/* ---------- Upload (klik of slepen) ---------- */
const fileInput = ref(null);
const dragging = ref(false);
const uploadForm = useForm({ file: null });

const uploadFile = (file) => {
  if (!file) return;
  uploadForm.file = file;
  uploadForm.post(route('bank.upload'), {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      uploadForm.reset();
      if (fileInput.value) fileInput.value.value = '';
    },
  });
};

const onDrop = (e) => {
  dragging.value = false;
  uploadFile(e.dataTransfer?.files?.[0]);
};

/* ---------- Tabs ---------- */
const setTab = (t) => router.get(route('bank.index'), { tab: t }, { preserveState: false });

/* ---------- Koppelen ---------- */
// Per transactie de gekozen factuur/inkoopfactuur (default: de suggestie).
const chosen = reactive({});
const chosenFor = (tx) => chosen[tx.id] ?? tx.suggestion?.id ?? '';

const match = (tx) => {
  const id = chosenFor(tx);
  if (!id) return;
  if (tx.amount >= 0) {
    router.post(route('bank.match.invoice', tx.id), { invoice_id: id }, { preserveScroll: true });
  } else {
    router.post(route('bank.match.purchase', tx.id), { purchase_id: id }, { preserveScroll: true });
  }
};

const ignore = (tx) => router.post(route('bank.ignore', tx.id), {}, { preserveScroll: true });

const restore = (tx) => {
  const msg = tx.status === 'matched'
    ? 'Koppeling ongedaan maken? De geboekte betaling wordt teruggedraaid.'
    : 'Transactie weer openzetten?';
  if (confirm(msg)) router.post(route('bank.restore', tx.id), {}, { preserveScroll: true });
};

const reasonLabels = { factuurnummer: 'factuurnr.', bedrag: 'bedrag', naam: 'naam', leverancier: 'leverancier', kenmerk: 'kenmerk' };
</script>

<template>
  <Head title="Bank & transacties" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Bank / <span class="breadcrumb-current">Transacties</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Bank &amp; transacties</h1>
        <p class="page-subtitle">Importeer je bankafschrift en koppel transacties aan facturen en inkoopfacturen — betalingen worden automatisch geboekt.</p>
      </div>
    </div>

    <!-- Import + automatische koppeling -->
    <div class="bank-top">
      <div
        class="bank-drop"
        :class="{ dragging }"
        @click="fileInput?.click()"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="onDrop"
      >
        <input ref="fileInput" type="file" accept=".xml,.sta,.940,.mt940,.txt,.dat" style="display:none" @change="e => uploadFile(e.target.files?.[0])">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        <div class="bank-drop-title">{{ uploadForm.processing ? 'Bezig met importeren…' : 'Sleep je bankafschrift hierheen of klik om te kiezen' }}</div>
        <div class="bank-drop-sub">Ondersteund: CAMT.053 (XML) of MT940 — te downloaden bij alle Nederlandse banken. Dubbele transacties worden automatisch overgeslagen.</div>
        <div v-if="uploadForm.errors.file" class="field-error" style="margin-top:8px;">{{ uploadForm.errors.file }}</div>
      </div>

    </div>

    <!-- Tabs -->
    <div class="filter-bar">
      <button :class="['filter-chip', { active: tab === 'open' }]" @click="setTab('open')">Open <span class="count">{{ counts.open }}</span></button>
      <button :class="['filter-chip', { active: tab === 'matched' }]" @click="setTab('matched')">Verwerkt <span class="count">{{ counts.matched }}</span></button>
      <button :class="['filter-chip', { active: tab === 'ignored' }]" @click="setTab('ignored')">Genegeerd <span class="count">{{ counts.ignored }}</span></button>
    </div>

    <!-- Transacties -->
    <div v-if="transactions.data.length > 0" class="bank-list">
      <div v-for="tx in transactions.data" :key="tx.id" class="bank-row">
        <div class="bank-row-main">
          <div class="bank-row-top">
            <span class="bank-date">{{ tx.booking_date_label }}</span>
            <span class="bank-party">{{ tx.counterparty_name || 'Onbekende tegenpartij' }}</span>
            <span v-if="tx.counterparty_iban" class="bank-iban">{{ tx.counterparty_iban }}</span>
          </div>
          <div class="bank-desc" :title="tx.description">{{ tx.description || '—' }}</div>

          <!-- Suggestie -->
          <div v-if="tx.status === 'open' && tx.suggestion" class="bank-suggestion">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 0 1 4 12.7V17a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1v-2.3A7 7 0 0 1 12 2z"/><line x1="9" y1="21" x2="15" y2="21"/></svg>
            Suggestie: <b>{{ tx.suggestion.label }}</b>
            <span class="bank-reasons">({{ tx.suggestion.reasons.map(r => reasonLabels[r] || r).join(' + ') }})</span>
          </div>

          <!-- Gekoppeld aan -->
          <div v-if="tx.status === 'matched'" class="bank-matched">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <template v-if="tx.matched_invoice">
              Gekoppeld aan factuur
              <Link :href="route('invoices.show', tx.matched_invoice.id)" style="color:var(--brand);font-weight:600;">{{ tx.matched_invoice.number }}</Link>
              — betaling geboekt
            </template>
            <template v-else-if="tx.matched_purchase">
              Gekoppeld aan inkoopfactuur van
              <Link :href="route('purchases.show', tx.matched_purchase.id)" style="color:var(--brand);font-weight:600;">{{ tx.matched_purchase.supplier_name }}</Link>
            </template>
          </div>
        </div>

        <div class="bank-row-side">
          <div class="bank-amount" :class="tx.amount >= 0 ? 'pos' : 'neg'">
            {{ tx.amount >= 0 ? '+' : '−' }} {{ eur(Math.abs(tx.amount)) }}
          </div>

          <template v-if="tx.status === 'open'">
            <select
              class="bank-select"
              :value="chosenFor(tx)"
              @change="chosen[tx.id] = Number($event.target.value) || ''"
            >
              <option value="">{{ tx.amount >= 0 ? '— Kies factuur —' : '— Kies inkoopfactuur —' }}</option>
              <option v-for="opt in (tx.amount >= 0 ? open_invoices : open_purchases)" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
            </select>
            <div class="bank-actions">
              <button class="btn btn-primary btn-sm" :disabled="!chosenFor(tx)" @click="match(tx)">Koppelen</button>
              <button class="btn btn-secondary btn-sm" @click="ignore(tx)">Negeren</button>
            </div>
          </template>
          <template v-else>
            <button class="btn btn-secondary btn-sm" @click="restore(tx)">
              {{ tx.status === 'matched' ? 'Ontkoppelen' : 'Herstellen' }}
            </button>
          </template>
        </div>
      </div>

      <div class="pagination" v-if="transactions.last_page > 1">
        <Link v-for="link in transactions.links" :key="link.label"
          :href="link.url || '#'"
          v-html="link.label"
          :class="['page-link', { active: link.active, disabled: !link.url }]"
        />
      </div>
    </div>

    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">
        {{ tab === 'open' ? 'Geen open transacties' : tab === 'matched' ? 'Nog niets verwerkt' : 'Niets genegeerd' }}
      </div>
      <div v-if="tab === 'open'">
        Importeer een bankafschrift hierboven — daarna koppel je hier elke transactie met één klik aan de juiste factuur.
      </div>
      <div v-else>Verwerkte en genegeerde transacties verschijnen hier.</div>
    </div>
  </AppLayout>
</template>

<style scoped>
.bank-top {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 14px;
  margin-bottom: 18px;
}
.bank-drop {
  border: 2px dashed var(--border-strong);
  border-radius: var(--r-lg);
  background: var(--surface);
  padding: 30px 26px;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.bank-drop:hover, .bank-drop.dragging { border-color: var(--brand); background: var(--brand-tint); }
.bank-drop svg { width: 30px; height: 30px; color: var(--text-3); margin-bottom: 10px; }
.bank-drop-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 6px; }
.bank-drop-sub { font-size: 12.5px; color: var(--text-3); line-height: 1.6; max-width: 460px; margin: 0 auto; }

.bank-list { display: flex; flex-direction: column; gap: 10px; }
.bank-row {
  display: flex; justify-content: space-between; gap: 18px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 14px 18px;
}
.bank-row-main { flex: 1; min-width: 0; }
.bank-row-top { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
.bank-date { font-size: 12px; color: var(--text-3); white-space: nowrap; }
.bank-party { font-weight: 600; font-size: 14px; }
.bank-iban { font-family: var(--font-mono); font-size: 11.5px; color: var(--text-4); }
.bank-desc {
  font-size: 12.5px; color: var(--text-3); margin-top: 3px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 640px;
}
.bank-suggestion {
  display: flex; align-items: center; gap: 7px; flex-wrap: wrap;
  margin-top: 8px; font-size: 12.5px; color: var(--text-2);
  background: var(--warning-bg); border: 1px solid var(--warning-border);
  border-radius: 8px; padding: 6px 11px; width: fit-content; max-width: 100%;
}
.bank-suggestion svg { width: 14px; height: 14px; color: var(--warning); flex: none; }
.bank-reasons { color: var(--text-3); }
.bank-matched { display: flex; align-items: center; gap: 7px; margin-top: 8px; font-size: 12.5px; color: var(--text-2); flex-wrap: wrap; }
.bank-matched svg { width: 14px; height: 14px; color: var(--success); flex: none; }

.bank-row-side { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex: none; max-width: 330px; }
.bank-amount { font-family: var(--font-mono); font-weight: 700; font-size: 15.5px; white-space: nowrap; }
.bank-amount.pos { color: var(--success); }
.bank-amount.neg { color: var(--brand-dark); }
.bank-select { width: 310px; max-width: 100%; height: 36px; font-size: 12.5px; }
.bank-actions { display: flex; gap: 8px; }

@media (max-width: 960px) {
  .bank-top { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 700px) {
  .bank-row { flex-direction: column; }
  .bank-row-side { align-items: stretch; max-width: none; }
  .bank-amount { text-align: left; }
  .bank-select { width: 100%; }
}
</style>
