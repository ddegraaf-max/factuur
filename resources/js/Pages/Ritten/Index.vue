<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur, num, marketLocale } from '@/format.js';
import { computed, ref } from 'vue';
import { t } from '@/i18n';

const props = defineProps({
  trips: Object,               // paginator
  filters: Object,             // { period, status, customer_id }
  stats: Object,               // { month_km, year_km, open_amount }
  billable_by_customer: Array, // openstaande ritten per klant
  customers: Array,            // { id, name }
  default_km_rate: Number,     // standaard kilometervergoeding van het bedrijf
});

// Markt (nl/pl): valutasymbool voor de tarief-hint en de wettelijke
// kilometervergoeding als het bedrijf zelf geen standaardtarief heeft.
const market = computed(() => usePage().props.market || {});
const symbol = computed(() => market.value.symbol || '€');
const defaultRate = computed(() => props.default_km_rate ?? market.value.km_rate ?? 0.23);

const km = (n) => Number(n).toLocaleString(marketLocale, { maximumFractionDigits: 1 });

/* ---------- Rit registreren / bewerken ---------- */
const today = new Date().toISOString().slice(0, 10);
const editingId = ref(null);

const form = useForm({
  customer_id: props.filters.customer_id || null,
  trip_date: today,
  from_location: '',
  to_location: '',
  round_trip: false,
  description: '',
  distance: '',      // enkele reis, zoals ingevoerd
  rate: null,
  billable: true,
});

const parseNumber = (v) => {
  const n = parseFloat(String(v ?? '').trim().replace(',', '.'));
  return isNaN(n) ? 0 : n;
};

// Totaal dat wordt geregistreerd (retour = heen én terug).
const totalKm = computed(() => {
  const single = parseNumber(form.distance);
  return Math.round(single * (form.round_trip ? 2 : 1) * 10) / 10;
});

const ratePlaceholder = computed(() => t(':rate (standaard)', { rate: num(defaultRate.value) }));

const submit = () => {
  form
    .transform((data) => ({
      customer_id: data.customer_id || null,
      trip_date: data.trip_date,
      from_location: data.from_location,
      to_location: data.to_location,
      round_trip: data.round_trip,
      description: data.description || null,
      kilometers: totalKm.value,
      rate: data.rate === '' || data.rate === null ? null : parseNumber(data.rate),
      billable: data.billable,
    }))
    .submit(
      editingId.value ? 'patch' : 'post',
      editingId.value ? route('trips.update', editingId.value) : route('trips.store'),
      {
        preserveScroll: true,
        onSuccess: () => {
          editingId.value = null;
          form.reset('from_location', 'to_location', 'round_trip', 'description', 'distance', 'rate');
          form.billable = true;
        },
      }
    );
};

const startEdit = (t) => {
  editingId.value = t.id;
  form.customer_id = t.customer_id;
  form.trip_date = t.trip_date;
  form.from_location = t.from_location;
  form.to_location = t.to_location;
  form.round_trip = t.round_trip;
  form.description = t.description || '';
  form.distance = String(t.round_trip ? Math.round(t.kilometers / 2 * 10) / 10 : t.kilometers).replace('.', ',');
  form.rate = t.rate;
  form.billable = t.billable;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEdit = () => {
  editingId.value = null;
  form.reset();
  form.trip_date = today;
};

const removeTrip = (trip) => {
  if (confirm(t('Deze rit verwijderen?'))) {
    router.delete(route('trips.destroy', trip.id), { preserveScroll: true });
  }
};

/* ---------- Factureren ---------- */
const invoiceCustomer = (row) => {
  if (confirm(t('Conceptfactuur maken voor :customer met :km km (:amount)?', { customer: row.customer_name, km: km(row.kilometers), amount: eur(row.amount) }))) {
    router.post(route('trips.invoice'), { customer_id: row.customer_id });
  }
};

/* ---------- Filters ---------- */
const applyFilters = (overrides = {}) => {
  router.get(route('trips.index'), {
    period: props.filters.period,
    status: props.filters.status,
    customer_id: props.filters.customer_id || undefined,
    ...overrides,
  }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
  <Head :title="$t('Ritten')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">{{ $t('Verkoop') }} / <span class="breadcrumb-current">{{ $t('Ritten') }}</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Kilometerregistratie') }}</h1>
        <p class="page-subtitle">{{ $t('Houd zakelijke ritten bij — belast ze door aan je klant of bewaar ze als kilometeradministratie voor je aangifte.') }}</p>
      </div>
    </div>

    <!-- Statistieken -->
    <div class="kpi-grid">
      <div class="kpi"><div class="lbl">{{ $t('Deze maand') }}</div><div class="val">{{ km(stats.month_km) }} km</div><div class="meta">{{ $t('zakelijk gereden') }}</div></div>
      <div class="kpi"><div class="lbl">{{ $t('Dit jaar') }}</div><div class="val">{{ km(stats.year_km) }} km</div><div class="meta">{{ $t('zakelijk gereden') }}</div></div>
      <div class="kpi" :class="{ alert: stats.open_amount > 0 }">
        <div class="lbl">{{ $t('Nog te factureren') }}</div>
        <div class="val">{{ eur(stats.open_amount) }}</div>
        <div class="meta">{{ $t('aan open reiskosten') }}</div>
      </div>
    </div>

    <!-- Rit registreren / bewerken -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="trip-form-title">
          {{ editingId ? $t('Rit bewerken') : $t('Rit registreren') }}
          <button v-if="editingId" type="button" class="btn btn-secondary btn-sm" @click="cancelEdit">{{ $t('Annuleren') }}</button>
        </div>
        <form @submit.prevent="submit" class="trip-form">
          <div class="form-group">
            <label>{{ $t('Datum') }} *</label>
            <input type="date" v-model="form.trip_date">
            <div v-if="form.errors.trip_date" class="field-error">{{ form.errors.trip_date }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Klant') }}<span class="label-hint">{{ $t('(voor doorbelasten)') }}</span></label>
            <select v-model="form.customer_id">
              <option :value="null">{{ $t('— Geen klant —') }}</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>{{ $t('Van') }} *</label>
            <input type="text" v-model="form.from_location" maxlength="190" :placeholder="$t('Bijv. Bussum')">
            <div v-if="form.errors.from_location" class="field-error">{{ form.errors.from_location }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Naar') }} *</label>
            <input type="text" v-model="form.to_location" maxlength="190" :placeholder="$t('Bijv. Amsterdam')">
            <div v-if="form.errors.to_location" class="field-error">{{ form.errors.to_location }}</div>
          </div>
          <div class="form-group narrow">
            <label>{{ $t('Afstand (km)') }} *</label>
            <input type="text" v-model="form.distance" :placeholder="$t('Bijv. 42,5')" inputmode="decimal">
            <label class="checkbox-row" style="margin-top:7px;">
              <input type="checkbox" v-model="form.round_trip">
              <span>{{ $t('Retour') }}<template v-if="totalKm > 0"> — {{ $t('totaal :km km', { km: km(totalKm) }) }}</template></span>
            </label>
            <div v-if="form.errors.kilometers" class="field-error">{{ form.errors.kilometers }}</div>
          </div>
          <div class="form-group narrow">
            <label>{{ $t('Tarief per km') }}<span class="label-hint">{{ symbol }}</span></label>
            <input type="text" v-model="form.rate" :placeholder="ratePlaceholder" inputmode="decimal">
            <div v-if="form.errors.rate" class="field-error">{{ form.errors.rate }}</div>
          </div>
          <div class="form-group grow">
            <label>{{ $t('Doel van de rit') }}<span class="label-hint">{{ $t('(optioneel, komt als detail op de factuur)') }}</span></label>
            <input type="text" v-model="form.description" maxlength="500" :placeholder="$t('Bijv. bespreking nieuwe huisstijl')">
          </div>
          <div class="trip-form-actions">
            <label class="checkbox-row" style="margin:0;">
              <input type="checkbox" v-model="form.billable">
              <span>{{ $t('Doorbelasten aan klant') }}</span>
            </label>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? $t('Bezig…') : (editingId ? $t('Opslaan') : $t('Rit registreren')) }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Met één klik factureren -->
    <div v-if="billable_by_customer.length" class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="trip-form-title" style="margin-bottom:4px;">{{ $t('Klaar om te factureren') }}</div>
        <p class="bill-hint">{{ $t('Alle openstaande ritten van een klant worden gebundeld op één conceptfactuur — per rit een reiskostenregel met datum en afstand.') }}</p>
        <div v-for="row in billable_by_customer" :key="row.customer_id" class="bill-row">
          <span class="bill-name">{{ row.customer_name }}</span>
          <span class="bill-meta">{{ km(row.kilometers) }} km · {{ row.trips === 1 ? $t(':n rit', { n: row.trips }) : $t(':n ritten', { n: row.trips }) }}</span>
          <span class="bill-amount num">{{ eur(row.amount) }}</span>
          <button type="button" class="btn btn-primary btn-sm" @click="invoiceCustomer(row)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            {{ $t('Maak factuur') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <button :class="['filter-chip', { active: filters.status === 'open' }]" @click="applyFilters({ status: 'open' })">{{ $t('Open') }}</button>
      <button :class="['filter-chip', { active: filters.status === 'invoiced' }]" @click="applyFilters({ status: 'invoiced' })">{{ $t('Gefactureerd') }}</button>
      <button :class="['filter-chip', { active: filters.status === 'all' }]" @click="applyFilters({ status: 'all' })">{{ $t('Alles') }}</button>
      <span class="filter-sep"></span>
      <select class="filter-select" :value="filters.period" @change="applyFilters({ period: $event.target.value })">
        <option value="week">{{ $t('Deze week') }}</option>
        <option value="month">{{ $t('Deze maand') }}</option>
        <option value="year">{{ $t('Dit jaar') }}</option>
        <option value="all">{{ $t('Alle periodes') }}</option>
      </select>
      <select class="filter-select" :value="filters.customer_id ?? ''" @change="applyFilters({ customer_id: $event.target.value || undefined })">
        <option value="">{{ $t('Alle klanten') }}</option>
        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>

    <!-- Rittenlijst -->
    <div class="card" v-if="trips.data.length > 0">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Datum') }}</th>
            <th>{{ $t('Rit') }}</th>
            <th>{{ $t('Klant') }}</th>
            <th class="right">{{ $t('Afstand') }}</th>
            <th class="right">{{ $t('Tarief') }}</th>
            <th class="right">{{ $t('Bedrag') }}</th>
            <th>{{ $t('Status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in trips.data" :key="t.id">
            <td :data-label="$t('Datum')">{{ t.trip_date_label }}</td>
            <td class="cell-primary" :data-label="$t('Rit')">
              {{ t.from_location }} – {{ t.to_location }}<template v-if="t.round_trip"> <span class="muted">{{ $t('(retour)') }}</span></template>
              <div v-if="t.description" class="trip-desc">{{ t.description }}</div>
            </td>
            <td :data-label="$t('Klant')">{{ t.customer_name || '—' }}</td>
            <td class="num right" :data-label="$t('Afstand')">{{ km(t.kilometers) }} km</td>
            <td class="num right" :data-label="$t('Tarief')">{{ eur(t.effective_rate) }}</td>
            <td class="num right" :data-label="$t('Bedrag')">{{ t.amount != null ? eur(t.amount) : '—' }}</td>
            <td :data-label="$t('Status')">
              <Link v-if="t.invoice_id" :href="route('invoices.show', t.invoice_id)" class="pill pill-paid" style="text-decoration:none;">
                {{ t.invoice_number || $t('Conceptfactuur') }}
              </Link>
              <span v-else-if="!t.billable" class="pill pill-muted">{{ $t('Eigen administratie') }}</span>
              <span v-else class="pill pill-sent">{{ $t('Open') }}</span>
            </td>
            <td class="row-actions">
              <template v-if="!t.invoice_id">
                <button type="button" class="icon-btn" :title="$t('Bewerken')" @click="startEdit(t)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                </button>
                <button type="button" class="icon-btn" :title="$t('Verwijderen')" @click="removeTrip(t)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="pagination" v-if="trips.last_page > 1">
        <Link v-for="link in trips.links" :key="link.label"
          :href="link.url || '#'"
          v-html="link.label"
          :class="['page-link', { active: link.active, disabled: !link.url }]"
          preserve-state
        />
      </div>
    </div>
    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">{{ $t('Nog geen ritten in deze periode') }}</div>
      <div>{{ $t('Registreer hierboven je eerste zakelijke rit — doorbelasten aan je klant of gewoon voor je eigen kilometeradministratie.') }}</div>
    </div>
  </AppLayout>
</template>

<style scoped>
.kpi-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.kpi.alert { background: var(--brand-tint); border-color: var(--brand-border); }
.kpi.alert .val { color: var(--brand-darker); }
.kpi .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.kpi .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; }
.kpi .meta { font-size: 11px; color: var(--text-3); margin-top: 4px; }

.trip-form-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.trip-form { display: grid; grid-template-columns: 150px 190px minmax(0, 1fr) minmax(0, 1fr) 150px 120px; gap: 12px; align-items: start; }
.trip-form .grow { grid-column: 1 / -1; }
.trip-form-actions { grid-column: 1 / -1; display: flex; align-items: center; justify-content: flex-end; gap: 14px; }
.label-hint { color: var(--text-4); font-weight: 400; font-size: 11.5px; margin-left: 5px; }

.bill-hint { font-size: 12.5px; color: var(--text-3); margin-bottom: 12px; line-height: 1.6; }
.bill-row { display: flex; align-items: center; gap: 14px; padding: 10px 0; border-top: 1px solid var(--border); flex-wrap: wrap; }
.bill-name { font-weight: 600; font-size: 13.5px; flex: 1; min-width: 140px; }
.bill-meta { font-size: 12.5px; color: var(--text-3); }
.bill-amount { font-family: var(--font-mono); font-weight: 600; font-size: 13.5px; min-width: 90px; text-align: right; }

.filter-sep { flex: 0 0 8px; }
.filter-select {
  font-size: 13px; padding: 6px 10px; border: 1px solid var(--border); border-radius: 8px;
  background: var(--surface); color: var(--text-2); max-width: 200px;
}

.trip-desc { font-size: 11.5px; color: var(--text-3); font-weight: 400; margin-top: 2px; }
.pill-muted { background: var(--surface-2); color: var(--text-3); }
.row-actions { white-space: nowrap; text-align: right; }
.icon-btn { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.icon-btn:hover { background: var(--surface-2); color: var(--brand-dark); }

@media (max-width: 1100px) {
  .trip-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 760px) {
  .kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
  .kpi { padding: 14px; }
  .kpi .val { font-size: 18px; }
  .trip-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .bill-row .btn { margin-left: auto; }
}
@media (max-width: 400px) {
  .trip-form { grid-template-columns: minmax(0, 1fr); }
}
</style>
