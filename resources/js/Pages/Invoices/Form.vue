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
});

const isEdit = computed(() => !!props.invoice);

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
        unit_price: Number(l.unit_price),
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

const totals = computed(() => {
  let subtotal = 0;
  const breakdown = {};
  for (const line of form.lines) {
    const qty = parseDutchNumber(line.quantity);
    const price = parseDutchNumber(line.unit_price);
    const rate = Number(line.vat_rate) || 0;
    const lineSubtotal = qty * price;
    subtotal += lineSubtotal;
    const key = rate.toFixed(2);
    if (!breakdown[key]) breakdown[key] = { rate, subtotal: 0, vat: 0 };
    breakdown[key].subtotal += lineSubtotal;
    breakdown[key].vat += lineSubtotal * (rate / 100);
  }
  let vatTotal = 0;
  for (const k in breakdown) {
    breakdown[k].vat = Math.round(breakdown[k].vat * 100) / 100;
    breakdown[k].subtotal = Math.round(breakdown[k].subtotal * 100) / 100;
    vatTotal += breakdown[k].vat;
  }
  return {
    subtotal: Math.round(subtotal * 100) / 100,
    vat_total: Math.round(vatTotal * 100) / 100,
    total: Math.round((subtotal + vatTotal) * 100) / 100,
    breakdown: Object.values(breakdown).filter(b => b.subtotal > 0),
  };
});

const lineTotal = (line) => {
  const qty = parseDutchNumber(line.quantity);
  const price = parseDutchNumber(line.unit_price);
  const rate = Number(line.vat_rate) || 0;
  return qty * price * (1 + rate / 100);
};

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
    line.unit_price = Number(p.price);
    line.vat_rate = Number(p.vat_rate);
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

const submit = (action) => {
  form.action = action;
  if (isEdit.value) {
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
          <div class="card-header"><div class="card-title">Factuurregels</div></div>
          <div class="card-body">
            <div class="lines-grid">
              <div class="lines-header">
                <div>Omschrijving</div>
                <div style="text-align:right;">Aantal</div>
                <div style="text-align:right;">Prijs</div>
                <div>BTW</div>
                <div style="text-align:right;">Totaal</div>
                <div></div>
              </div>

              <div v-for="(line, i) in form.lines" :key="i" class="line-row">
                <div>
                  <div class="line-desc-row">
                    <select v-if="products.length > 0" v-model="line.product_id" @change="applyProduct(line, $event.target.value)" class="product-select" title="Kies product">
                      <option :value="null">— Eigen regel —</option>
                      <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <input type="text" v-model="line.description" placeholder="Omschrijving" required>
                  </div>
                  <textarea v-model="line.details" placeholder="Toelichting (optioneel)" rows="1" class="line-details"></textarea>
                </div>
                <input type="number" v-model.number="line.quantity" min="0" step="0.001" class="num right">
                <input type="number" v-model.number="line.unit_price" min="0" step="0.01" class="num right">
                <select v-model.number="line.vat_rate">
                  <option v-for="r in vat_rates" :key="r.value" :value="r.value">{{ r.value }}%</option>
                </select>
                <div class="num line-total">{{ eur(lineTotal(line)) }}</div>
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

<style>
.form-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 20px;
}
@media (max-width: 1100px) {
  .form-layout { grid-template-columns: 1fr; }
}

.lines-grid { width: 100%; }
.lines-header {
  display: grid;
  grid-template-columns: 1fr 80px 110px 90px 110px 32px;
  gap: 8px;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-3);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0 4px 8px;
  border-bottom: 1px solid var(--border);
}
.line-row {
  display: grid;
  grid-template-columns: 1fr 80px 110px 90px 110px 32px;
  gap: 8px;
  align-items: start;
  padding: 12px 4px;
  border-bottom: 1px solid var(--border);
}
.line-row:last-child { border-bottom: none; }
.line-desc-row { display: flex; gap: 6px; }
.line-desc-row select.product-select {
  flex: 0 0 130px;
  height: 34px;
  padding: 0 8px;
  font-size: 12px;
  border: 1px solid var(--border);
  border-radius: 5px;
  color: var(--text-3);
}
.line-desc-row input {
  flex: 1;
  height: 34px;
  padding: 0 10px;
  font-size: 13px;
  border: 1px solid var(--border);
  border-radius: 5px;
}
.line-details {
  margin-top: 6px;
  width: 100%;
  padding: 6px 10px;
  font-size: 12px;
  color: var(--text-3);
  border: 1px solid var(--border);
  border-radius: 5px;
  resize: vertical;
  min-height: 28px;
  font-family: inherit;
}
.line-row input.num {
  height: 34px;
  padding: 0 8px;
  border: 1px solid var(--border);
  border-radius: 5px;
  text-align: right;
  font-family: var(--font-mono);
  font-size: 13px;
}
.line-row select {
  height: 34px;
  padding: 0 8px;
  border: 1px solid var(--border);
  border-radius: 5px;
  font-size: 13px;
}
.line-row input:focus, .line-row select:focus, .line-details:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 2px var(--brand-tint);
}
.line-total {
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 8px;
  font-weight: 500;
  font-size: 13px;
  font-variant-numeric: tabular-nums;
}
.li-remove {
  height: 34px;
  width: 32px;
  color: var(--text-4);
  border-radius: 5px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.li-remove:hover:not(:disabled) { color: var(--brand); background: var(--brand-tint); }
.li-remove:disabled { opacity: 0.3; cursor: not-allowed; }

.add-line-btn {
  margin-top: 12px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--brand);
  font-size: 13px;
  font-weight: 500;
  padding: 8px 12px;
  border-radius: 6px;
}
.add-line-btn:hover { background: var(--brand-tint); }

/* Sidebar totals */
.totals-card { position: sticky; top: 88px; }
.total-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  font-size: 13px;
  color: var(--text-2);
}
.total-row.sep {
  border-top: 1px solid var(--border);
  margin-top: 8px;
  padding-top: 12px;
  font-weight: 500;
}
.total-row.grand {
  border-top: 2px solid var(--text);
  margin-top: 12px;
  padding-top: 14px;
  font-weight: 700;
  font-size: 16px;
  color: var(--text);
}
</style>
