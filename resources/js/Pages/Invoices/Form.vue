<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur, parseDutchNumber } from '@/format.js';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  invoice: Object,
  customers: Array,
  products: Array,
  vat_rates: Array,
  preselect_customer_id: { type: [String, Number], default: null },
  price_mode: { type: String, default: 'excl' },
});

const isEdit = computed(() => !!props.invoice);

// 'incl' = de ondernemer typt de prijs die de klant betaalt (bruto).
// De opslag blijft altijd netto; de server rekent dat terug.
const inclMode = computed(() => props.price_mode === 'incl');
const priceLabel = computed(() => inclMode.value ? 'Prijs incl. btw' : 'Prijs');

/** Toon de prijs zoals de gebruiker hem invoert: bruto in incl-modus. */
const displayPrice = (line) => {
  const qty = Number(line.quantity) || 0;
  if (props.price_mode === 'incl') {
    // Bestaande regel: leid de brutoprijs af uit het regeltotaal, dan zie je
    // exact terug wat er ooit is ingetypt (ook bij meerdere stuks).
    if (line.line_total != null && qty > 0) {
      return Math.round((Number(line.line_total) / qty) * 100) / 100;
    }
    return Math.round(Number(line.unit_price) * (1 + Number(line.vat_rate) / 100) * 100) / 100;
  }
  return Number(line.unit_price);
};

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
  customer_id: props.invoice?.customer_id ?? props.preselect_customer_id ?? (props.customers[0]?.id || ''),
  invoice_date: props.invoice?.invoice_date ?? today,
  payment_terms: props.invoice?.payment_terms ?? 30,
  reference: props.invoice?.reference ?? '',
  notes: props.invoice?.notes ?? '',
  lines: props.invoice?.lines?.length > 0
    ? props.invoice.lines.map(l => ({
        product_id: l.product_id,
        description: l.description,
        details: l.details ?? '',
        quantity: Number(l.quantity),
        unit: l.unit,
        unit_price: displayPrice(l),
        vat_rate: Number(l.vat_rate),
      }))
    : [{
        product_id: null,
        description: '',
        details: '',
        quantity: 1,
        unit: 'stuk',
        unit_price: 0,
        vat_rate: 21,
      }],
  action: 'draft',
});

const r2 = (n) => Math.round(n * 100) / 100;

/**
 * Rekent één regel door — dezelfde volgorde als VatCalculator op de server,
 * zodat het scherm en de opgeslagen factuur tot op de cent gelijk zijn.
 */
const calcLine = (line) => {
  const qty = parseDutchNumber(line.quantity);
  const price = parseDutchNumber(line.unit_price);
  const rate = Number(line.vat_rate) || 0;

  if (inclMode.value) {
    const total = r2(qty * price);
    const sub = r2(total / (1 + rate / 100));
    return { rate, subtotal: sub, vat: r2(total - sub), total };
  }

  const sub = r2(qty * price);
  const vat = r2(sub * (rate / 100));
  return { rate, subtotal: sub, vat, total: r2(sub + vat) };
};

const totals = computed(() => {
  let subtotal = 0;
  let vatTotal = 0;
  const breakdown = {};

  for (const line of form.lines) {
    const c = calcLine(line);
    subtotal += c.subtotal;
    vatTotal += c.vat;

    const key = c.rate.toFixed(2);
    if (!breakdown[key]) breakdown[key] = { rate: c.rate, subtotal: 0, vat: 0 };
    breakdown[key].subtotal += c.subtotal;
    breakdown[key].vat += c.vat;
  }

  for (const k in breakdown) {
    breakdown[k].vat = r2(breakdown[k].vat);
    breakdown[k].subtotal = r2(breakdown[k].subtotal);
  }

  return {
    subtotal: r2(subtotal),
    vat_total: r2(vatTotal),
    total: r2(subtotal + vatTotal),
    breakdown: Object.values(breakdown).filter(b => b.subtotal !== 0),
  };
});

const lineTotal = (line) => calcLine(line).total;

const addLine = () => {
  form.lines.push({
    product_id: null,
    description: '',
    details: '',
    quantity: 1,
    unit: 'stuk',
    unit_price: 0,
    vat_rate: 21,
  });
};

const removeLine = (i) => {
  if (form.lines.length > 1) form.lines.splice(i, 1);
};

const applyProduct = (line, productId) => {
  if (!productId) return;
  const p = props.products.find(p => p.id === Number(productId));
  if (p) {
    line.description = p.name;
    line.details = p.description ?? '';
    line.unit = p.unit;
    line.vat_rate = Number(p.vat_rate);
    // Productprijzen staan netto opgeslagen; in incl-modus tonen we ze bruto.
    line.unit_price = inclMode.value
      ? Math.round(Number(p.price) * (1 + Number(p.vat_rate) / 100) * 100) / 100
      : Number(p.price);
  }
};

const selectedCustomer = computed(() => {
  return props.customers.find(c => c.id === Number(form.customer_id));
});

// When customer changes, auto-update payment terms if not edited
watch(() => form.customer_id, (id) => {
  const c = props.customers.find(c => c.id === Number(id));
  if (c?.payment_terms) form.payment_terms = c.payment_terms;
});

/* ---------- Bijlagen (meesturen met de factuurmail + klantenportaal) ---------- */
const fileInput = ref(null);
const files = ref([]); // { file, name, size, forCustomer }

const addFiles = (event) => {
  for (const file of Array.from(event.target.files || [])) {
    files.value.push({ file, name: file.name, size: file.size, forCustomer: true });
  }
  event.target.value = '';
};

const removeFile = (i) => files.value.splice(i, 1);

const formatSize = (b) => b < 1024 * 1024 ? (b / 1024).toFixed(0) + ' KB' : (b / 1024 / 1024).toFixed(1) + ' MB';

const fileError = computed(() => {
  const key = Object.keys(form.errors).find(k => k === 'files' || k.startsWith('files'));
  return key ? form.errors[key] : null;
});

const submit = (action) => {
  form.action = action;

  // Met bijlagen moet het verzoek als multipart-formulier (met method-spoofing
  // voor bewerken); zonder bijlagen blijft alles zoals het was.
  if (files.value.length > 0) {
    form
      .transform((data) => ({
        ...data,
        files: files.value.map(f => f.file),
        files_for_customer: files.value.map(f => (f.forCustomer ? '1' : '0')),
        ...(isEdit.value ? { _method: 'put' } : {}),
      }))
      .post(isEdit.value ? route('invoices.update', props.invoice.id) : route('invoices.store'), {
        forceFormData: true,
      });
  } else if (isEdit.value) {
    form.put(route('invoices.update', props.invoice.id));
  } else {
    form.post(route('invoices.store'));
  }
};
</script>

<template>
  <Head :title="isEdit ? 'Factuur bewerken' : 'Nieuwe factuur'" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        Verkoop / <Link :href="route('invoices.index')" style="color:var(--text-3);">Facturen</Link> /
        <span class="breadcrumb-current">{{ isEdit ? 'Bewerken' : 'Nieuw' }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('invoices.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          Terug
        </Link>
        <h1 class="page-title">{{ isEdit ? 'Factuur bewerken' : 'Nieuwe factuur' }}</h1>
      </div>
      <div class="page-actions">
        <button class="btn btn-secondary btn-sm" :disabled="form.processing" @click="submit('draft')">
          Opslaan als concept
        </button>
        <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit('send')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          Versturen
        </button>
      </div>
    </div>

    <div class="form-layout">
      <div class="form-main">
        <div class="card">
          <div class="card-header"><div class="card-title">Klant &amp; details</div></div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group">
                <label>Klant *</label>
                <select v-model="form.customer_id" required>
                  <option v-if="customers.length === 0" value="">Geen klanten — eerst toevoegen</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <div v-if="form.errors.customer_id" class="field-error">{{ form.errors.customer_id }}</div>
                <Link v-if="customers.length === 0" :href="route('customers.create')" style="color:var(--brand);font-size:13px;font-weight:500;display:inline-block;margin-top:6px;">
                  + Nieuwe klant aanmaken
                </Link>
              </div>
              <div class="form-group">
                <label>Referentie<span class="label-hint">(optioneel)</span></label>
                <input type="text" v-model="form.reference" placeholder="PROJ-2026-001" maxlength="255">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Factuurdatum *</label>
                <input type="date" v-model="form.invoice_date" required>
                <div v-if="form.errors.invoice_date" class="field-error">{{ form.errors.invoice_date }}</div>
              </div>
              <div class="form-group">
                <label>Betalingstermijn (dagen) *</label>
                <input type="number" v-model="form.payment_terms" min="0" max="365" required>
              </div>
            </div>
          </div>
        </div>

        <!-- Lines -->
        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <div>
              <div class="card-title">Factuurregels</div>
              <div class="card-subtitle">
                {{ inclMode ? 'Je typt prijzen inclusief btw' : 'Je typt prijzen exclusief btw' }}
                · aan te passen bij Instellingen → Bedrijfsgegevens
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="lines-grid">
              <div class="lines-header">
                <div>Omschrijving</div>
                <div style="text-align:right;">Aantal</div>
                <div style="text-align:right;">{{ priceLabel }}</div>
                <div>BTW</div>
                <div style="text-align:right;">Totaal</div>
                <div></div>
              </div>

              <!-- De .line-field-wrappers zijn op desktop 'display: contents', dus
                   de velden blijven gewoon kolommen van .line-row. Op mobiel worden
                   het blokken met een eigen label (uit data-label). -->
              <div v-for="(line, i) in form.lines" :key="i" class="line-row">
                <div class="line-desc">
                  <div class="line-desc-row">
                    <select v-if="products.length > 0" v-model="line.product_id" @change="applyProduct(line, $event.target.value)" class="product-select" title="Kies product">
                      <option :value="null">— Eigen regel —</option>
                      <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <input type="text" v-model="line.description" placeholder="Omschrijving" required>
                  </div>
                  <textarea v-model="line.details" placeholder="Toelichting (optioneel)" rows="1" class="line-details"></textarea>
                </div>
                <div class="line-field" data-label="Aantal">
                  <input type="number" v-model.number="line.quantity" min="0" step="0.001" class="num right">
                </div>
                <div class="line-field" :data-label="priceLabel">
                  <input type="number" v-model.number="line.unit_price" min="0" step="0.01" class="num right">
                </div>
                <div class="line-field" data-label="BTW">
                  <select v-model.number="line.vat_rate">
                    <option v-for="r in vat_rates" :key="r.value" :value="r.value">{{ r.value }}%</option>
                  </select>
                </div>
                <div class="line-field line-total-field" data-label="Totaal">
                  <div class="num line-total">{{ eur(lineTotal(line)) }}</div>
                </div>
                <button class="li-remove" @click="removeLine(i)" :disabled="form.lines.length === 1" type="button">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>
            </div>

            <button class="add-line-btn" @click="addLine" type="button">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Regel toevoegen
            </button>
          </div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-header"><div class="card-title">Opmerking voor klant</div></div>
          <div class="card-body">
            <div class="form-group" style="margin:0;">
              <textarea v-model="form.notes" placeholder="Optioneel — verschijnt onderaan de factuur" rows="3"></textarea>
            </div>
          </div>
        </div>

        <!-- Bijlagen -->
        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <div>
              <div class="card-title">Bijlagen</div>
              <div class="card-subtitle">Bijv. een urenoverzicht of specificatie — gaat mee met de factuurmail en staat in het klantenportaal</div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" @click="fileInput?.click()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Bestand toevoegen
            </button>
          </div>
          <div class="card-body">
            <input ref="fileInput" type="file" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="display:none" @change="addFiles">

            <div v-if="fileError" class="field-error" style="margin-bottom:10px;">{{ fileError }}</div>

            <div v-if="files.length === 0" class="fa-empty">
              Nog geen bijlagen. PDF, PNG, JPG of WEBP · max. 10 MB per bestand, 10 bestanden per factuur.
            </div>

            <div v-for="(f, i) in files" :key="i" class="fa-row">
              <span class="fa-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              </span>
              <div class="fa-info">
                <div class="fa-name">{{ f.name }}</div>
                <div class="fa-meta">{{ formatSize(f.size) }}</div>
              </div>
              <label class="fa-check" :title="f.forCustomer ? 'Gaat mee met de factuurmail en is zichtbaar in het portaal' : 'Alleen intern — de klant ziet dit bestand niet'">
                <input type="checkbox" v-model="f.forCustomer">
                Meesturen naar klant
              </label>
              <button type="button" class="li-remove" @click="removeFile(i)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <p v-if="isEdit" class="fa-note">Eerder toegevoegde bijlagen bekijk en beheer je op de factuurpagina.</p>
          </div>
        </div>
      </div>

      <!-- Sidebar with totals -->
      <aside class="form-sidebar">
        <div class="card totals-card">
          <div class="card-header"><div class="card-title">Overzicht</div></div>
          <div class="card-body">
            <div class="total-row" v-for="b in totals.breakdown" :key="b.rate">
              <span>Excl. BTW ({{ b.rate }}%)</span>
              <span class="mono">{{ eur(b.subtotal) }}</span>
            </div>
            <div class="total-row sep"><span>Subtotaal</span><span class="mono">{{ eur(totals.subtotal) }}</span></div>
            <div class="total-row" v-for="b in totals.breakdown" :key="'vat-' + b.rate">
              <span>BTW {{ b.rate }}%</span>
              <span class="mono">{{ eur(b.vat) }}</span>
            </div>
            <div class="total-row grand"><span>Totaal</span><span class="mono">{{ eur(totals.total) }}</span></div>
          </div>
        </div>

        <div v-if="selectedCustomer" class="card" style="margin-top:12px;">
          <div class="card-header"><div class="card-title">Klantgegevens</div></div>
          <div class="card-body" style="font-size:13px;line-height:1.7;">
            <div style="font-weight:600;">{{ selectedCustomer.name }}</div>
            <div v-if="selectedCustomer.address_line" style="color:var(--text-3);">{{ selectedCustomer.address_line }}</div>
            <div v-if="selectedCustomer.postal_code || selectedCustomer.city" style="color:var(--text-3);">
              {{ selectedCustomer.postal_code }} {{ selectedCustomer.city }}
            </div>
            <div v-if="selectedCustomer.kvk_number" style="color:var(--text-3);font-family:var(--font-mono);font-size:12px;margin-top:4px;">KVK {{ selectedCustomer.kvk_number }}</div>
            <div v-if="selectedCustomer.vat_number" style="color:var(--text-3);font-family:var(--font-mono);font-size:12px;">BTW {{ selectedCustomer.vat_number }}</div>
          </div>
        </div>
      </aside>
    </div>
  </AppLayout>
</template>

<style src="../document-form.css"></style>

<style scoped>
/* Bijlagen bij het opstellen */
.fa-empty {
  color: var(--text-3); font-size: 12.5px; line-height: 1.6;
  background: var(--surface-2); border: 1px dashed var(--border-strong);
  border-radius: 9px; padding: 12px 15px;
}
.fa-row {
  display: flex; align-items: center; gap: 11px;
  border: 1px solid var(--border); border-radius: 9px;
  padding: 9px 12px; margin-bottom: 8px;
}
.fa-icon {
  width: 32px; height: 32px; border-radius: 7px; flex: none;
  background: var(--surface-2); color: var(--text-3);
  display: inline-flex; align-items: center; justify-content: center;
}
.fa-icon svg { width: 16px; height: 16px; }
.fa-info { flex: 1; min-width: 0; }
.fa-name { font-weight: 600; font-size: 13px; word-break: break-word; }
.fa-meta { font-size: 11.5px; color: var(--text-3); margin-top: 1px; }
.fa-check {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 12.5px; color: var(--text-2); font-weight: 500;
  cursor: pointer; white-space: nowrap; flex: none;
}
.fa-check input { width: 15px; height: 15px; accent-color: var(--brand); cursor: pointer; }
.fa-note { font-size: 12px; color: var(--text-4); margin-top: 8px; }

@media (max-width: 560px) {
  .fa-row { flex-wrap: wrap; }
  .fa-check { white-space: normal; }
}
</style>
