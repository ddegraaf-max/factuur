<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur, parseDutchNumber } from '@/format.js';
import { computed } from 'vue';

const props = defineProps({
  quote: Object,
  customers: Array,
  products: Array,
  vat_rates: Array,
  price_mode: { type: String, default: 'excl' },
  default_valid_days: { type: Number, default: 30 },
  preselect_customer_id: { type: [String, Number], default: null },
  brand_profiles: { type: Array, default: () => [] }, // handelsnamen (leeg = geen keuze tonen)
});

const isEdit = computed(() => !!props.quote);
const inclMode = computed(() => props.price_mode === 'incl');
const priceLabel = computed(() => inclMode.value ? 'Prijs incl. btw' : 'Prijs');

const today = new Date().toISOString().slice(0, 10);

/** Toon de prijs zoals hij is ingevoerd: bruto in incl-modus, vóór korting. */
const displayPrice = (line) => {
  const qty = Number(line.quantity) || 0;
  const factor = 1 - (Number(line.discount_pct) || 0) / 100;
  if (props.price_mode === 'incl') {
    if (line.line_total != null && qty > 0 && factor > 0) {
      return Math.round((Number(line.line_total) / qty / factor) * 100) / 100;
    }
    return Math.round(Number(line.unit_price) * (1 + Number(line.vat_rate) / 100) * 100) / 100;
  }
  return Number(line.unit_price);
};

const daysBetween = (from, to) => {
  const ms = new Date(to).getTime() - new Date(from).getTime();
  return Math.max(1, Math.round(ms / 86400000));
};

const form = useForm({
  customer_id: props.quote?.customer_id ?? props.preselect_customer_id ?? (props.customers[0]?.id || ''),
  brand_profile_id: props.quote?.brand_profile_id ?? null,
  quote_date: props.quote?.quote_date?.slice(0, 10) ?? today,
  valid_days: props.quote
    ? daysBetween(props.quote.quote_date, props.quote.valid_until)
    : props.default_valid_days,
  reference: props.quote?.reference ?? '',
  intro: props.quote?.intro ?? '',
  notes: props.quote?.notes ?? '',
  lines: props.quote?.lines?.length > 0
    ? props.quote.lines.map(l => ({
        product_id: l.product_id,
        description: l.description,
        details: l.details ?? '',
        quantity: Number(l.quantity),
        unit: l.unit,
        unit_price: displayPrice(l),
        vat_rate: Number(l.vat_rate),
        discount_pct: Number(l.discount_pct) || 0,
      }))
    : [{ product_id: null, description: '', details: '', quantity: 1, unit: 'stuk', unit_price: 0, vat_rate: 21, discount_pct: 0 }],
  action: 'draft',
});

const r2 = (n) => Math.round(n * 100) / 100;

/** Zelfde volgorde als VatCalculator op de server. */
const calcLine = (line) => {
  const qty = parseDutchNumber(line.quantity);
  const price = parseDutchNumber(line.unit_price);
  const rate = Number(line.vat_rate) || 0;
  const factor = 1 - Math.min(100, Math.max(0, Number(line.discount_pct) || 0)) / 100;

  if (inclMode.value) {
    const total = r2(qty * price * factor);
    const sub = r2(total / (1 + rate / 100));
    return { rate, subtotal: sub, vat: r2(total - sub), total };
  }

  const sub = r2(qty * price * factor);
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

const validUntilLabel = computed(() => {
  const d = new Date(form.quote_date);
  d.setDate(d.getDate() + Number(form.valid_days || 0));
  return d.toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' });
});

const addLine = () => {
  form.lines.push({ product_id: null, description: '', details: '', quantity: 1, unit: 'stuk', unit_price: 0, vat_rate: 21, discount_pct: 0 });
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
    line.unit_price = inclMode.value
      ? r2(Number(p.price) * (1 + Number(p.vat_rate) / 100))
      : Number(p.price);
  }
};

/* ---------- Zichtbare foutmeldingen bij het opslaan ---------- */
const hasErrors = computed(() => Object.keys(form.errors).length > 0);

// Fouten per offerteregel ("lines.0.description" → regel 1), zodat een
// afgekeurde regel nooit onzichtbaar blijft.
const lineErrorList = computed(() =>
  form.lines
    .map((_, i) => ({
      line: i + 1,
      msgs: Object.entries(form.errors)
        .filter(([k]) => k.startsWith(`lines.${i}.`))
        .map(([, m]) => m),
    }))
    .filter(e => e.msgs.length > 0)
);

const submit = (action) => {
  form.action = action;
  if (isEdit.value) {
    form.put(route('quotes.update', props.quote.id));
  } else {
    form.post(route('quotes.store'));
  }
};
</script>

<template>
  <Head :title="isEdit ? 'Offerte bewerken' : 'Nieuwe offerte'" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        Verkoop / <Link :href="route('quotes.index')" style="color:var(--text-3);">Offertes</Link> /
        <span class="breadcrumb-current">{{ isEdit ? 'Bewerken' : 'Nieuw' }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('quotes.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          Terug
        </Link>
        <h1 class="page-title">{{ isEdit ? 'Offerte bewerken' : 'Nieuwe offerte' }}</h1>
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

    <div v-if="hasErrors" class="form-error-banner">
      Opslaan is niet gelukt — controleer de gemarkeerde velden hieronder.
    </div>

    <div class="form-layout">
      <div class="form-main">
        <div class="card">
          <div class="card-header"><div class="card-title">Klant &amp; geldigheid</div></div>
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
                <input type="text" v-model="form.reference" placeholder="Bijv. Verbouwing kantoor" maxlength="255">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Offertedatum *</label>
                <input type="date" v-model="form.quote_date" required>
                <div v-if="form.errors.quote_date" class="field-error">{{ form.errors.quote_date }}</div>
              </div>
              <div class="form-group">
                <label>Geldig gedurende (dagen) *</label>
                <input type="number" v-model.number="form.valid_days" min="1" max="365" required>
                <div style="font-size:11px;color:var(--text-4);margin-top:4px;">Geldig tot en met {{ validUntilLabel }}</div>
                <div v-if="form.errors.valid_days" class="field-error">{{ form.errors.valid_days }}</div>
              </div>
            </div>
            <div v-if="brand_profiles.length" class="form-row">
              <div class="form-group">
                <label>Offerte als<span class="label-hint">(handelsnaam op de offerte)</span></label>
                <select v-model="form.brand_profile_id">
                  <option :value="null">Standaard huisstijl</option>
                  <option v-for="bp in brand_profiles" :key="bp.id" :value="bp.id">{{ bp.name }}</option>
                </select>
              </div>
              <div class="form-group"></div>
            </div>
            <div class="form-group" style="margin:0;">
              <label>Begeleidende tekst<span class="label-hint">(bovenaan de offerte)</span></label>
              <textarea v-model="form.intro" rows="3" maxlength="2000" placeholder="Bijv. Naar aanleiding van ons gesprek doen wij je graag het volgende voorstel…"></textarea>
            </div>
          </div>
        </div>

        <!-- Regels -->
        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <div>
              <div class="card-title">Offerteregels</div>
              <div class="card-subtitle">{{ inclMode ? 'Je typt prijzen inclusief btw' : 'Je typt prijzen exclusief btw' }}</div>
            </div>
          </div>
          <div class="card-body">
            <div class="lines-grid">
              <div class="lines-header">
                <div>Omschrijving</div>
                <div style="text-align:right;">Aantal</div>
                <div style="text-align:right;">{{ priceLabel }}</div>
                <div style="text-align:right;">Korting</div>
                <div>BTW</div>
                <div style="text-align:right;">Totaal</div>
                <div></div>
              </div>

              <div v-for="(line, i) in form.lines" :key="i" class="line-row">
                <div class="line-desc">
                  <div class="line-desc-row">
                    <select v-if="products.length > 0" v-model="line.product_id" @change="applyProduct(line, $event.target.value)" class="product-select" title="Kies product">
                      <option :value="null">— Eigen regel —</option>
                      <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <input type="text" v-model="line.description" placeholder="Omschrijving" maxlength="500">
                  </div>
                  <textarea v-model="line.details" class="line-details" rows="1" placeholder="Toelichting (optioneel)"></textarea>
                </div>
                <div class="line-field" data-label="Aantal">
                  <input type="number" v-model.number="line.quantity" min="0" step="0.001" class="num right">
                </div>
                <div class="line-field" :data-label="priceLabel">
                  <input type="number" v-model.number="line.unit_price" min="0" step="0.01" class="num right">
                </div>
                <div class="line-field" data-label="Korting %">
                  <input type="number" v-model.number="line.discount_pct" min="0" max="100" step="0.01" class="num right" placeholder="0" title="Korting in procenten op deze regel">
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

            <div v-if="form.errors.lines" class="field-error" style="margin-top:10px;">{{ form.errors.lines }}</div>
            <div v-for="e in lineErrorList" :key="'err-' + e.line" class="field-error" style="margin-top:6px;">
              Regel {{ e.line }}: {{ e.msgs.join(' ') }}
            </div>

            <button class="add-line-btn" @click="addLine" type="button">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Regel toevoegen
            </button>
          </div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-header"><div class="card-title">Opmerking</div></div>
          <div class="card-body">
            <div class="form-group" style="margin:0;">
              <textarea v-model="form.notes" rows="3" placeholder="Bijv. voorwaarden, planning of aannames bij dit voorstel"></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="form-sidebar">
        <div class="card totals-card">
          <div class="card-header"><div class="card-title">Totaal</div></div>
          <div class="card-body">
            <div class="total-row"><span>Subtotaal</span><span class="mono">{{ eur(totals.subtotal) }}</span></div>
            <div v-for="b in totals.breakdown" :key="b.rate" class="total-row">
              <span>BTW {{ b.rate }}%</span>
              <span class="mono">{{ eur(b.vat) }}</span>
            </div>
            <div class="total-row grand"><span>Totaal</span><span class="mono">{{ eur(totals.total) }}</span></div>

            <div style="margin-top:18px;font-size:12px;color:var(--text-3);line-height:1.6;">
              De offerte krijgt pas een definitief nummer zodra je hem verstuurt.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style src="../document-form.css"></style>
