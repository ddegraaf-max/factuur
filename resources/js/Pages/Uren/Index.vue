<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur, num } from '@/format.js';
import { computed, onBeforeUnmount, ref } from 'vue';
import { t } from '@/i18n';

const props = defineProps({
  entries: Object,             // paginator
  filters: Object,             // { period, status, customer_id }
  stats: Object,               // { week_minutes, month_minutes, open_minutes }
  billable_by_customer: Array, // openstaande uren per klant
  timer: Object,               // lopende timer van deze gebruiker (of null)
  customers: Array,            // { id, name, hourly_rate }
  projects: Array,             // eerder gebruikte projectnamen
  default_hourly_rate: Number, // standaardtarief van het bedrijf (of null)
  time_cards: { type: Array, default: () => [] }, // strippenkaarten (tegoeden per klant)
});

// Valutasymbool van de markt (€ / zł) voor de hints bij tarief en bundelprijs.
const symbol = computed(() => usePage().props.market?.symbol || '€');

/* ---------- Duur: minuten <-> invoer ---------- */
const dur = (m) => `${Math.floor(m / 60)}:${String(m % 60).padStart(2, '0')}`;

// Accepteert "1:30" (uren:minuten) én "1,5" of "1.5" (decimale uren).
const parseDuration = (value) => {
  const v = String(value ?? '').trim().replace(',', '.');
  if (!v) return 0;
  if (v.includes(':')) {
    const [h, m] = v.split(':');
    return (parseInt(h, 10) || 0) * 60 + (parseInt(m, 10) || 0);
  }
  const hours = parseFloat(v);
  return isNaN(hours) ? 0 : Math.round(hours * 60);
};

/* ---------- Uren schrijven / bewerken ---------- */
const today = new Date().toISOString().slice(0, 10);
const editingId = ref(null);

const form = useForm({
  customer_id: props.filters.customer_id || null,
  project: '',
  description: '',
  work_date: today,
  duration: '',
  hourly_rate: null,
  billable: true,
});

// Placeholder toont het tarief dat gaat gelden als je niets invult.
const ratePlaceholder = computed(() => {
  const customer = props.customers.find(c => c.id === form.customer_id);
  const rate = customer?.hourly_rate ?? props.default_hourly_rate;
  return rate != null ? t(':rate (standaard)', { rate: num(rate) }) : t('bijv. 75,00');
});

const submit = () => {
  form
    .transform((data) => ({
      customer_id: data.customer_id || null,
      project: data.project || null,
      description: data.description,
      work_date: data.work_date,
      minutes: parseDuration(data.duration),
      hourly_rate: data.hourly_rate === '' || data.hourly_rate === null ? null : Number(String(data.hourly_rate).replace(',', '.')),
      billable: data.billable,
    }))
    .submit(
      editingId.value ? 'patch' : 'post',
      editingId.value ? route('hours.update', editingId.value) : route('hours.store'),
      {
        preserveScroll: true,
        onSuccess: () => {
          editingId.value = null;
          form.reset('project', 'description', 'duration', 'hourly_rate');
          form.billable = true;
        },
      }
    );
};

const startEdit = (e) => {
  editingId.value = e.id;
  form.customer_id = e.customer_id;
  form.project = e.project || '';
  form.description = e.description;
  form.work_date = e.work_date;
  form.duration = dur(e.minutes);
  form.hourly_rate = e.hourly_rate;
  form.billable = e.billable;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEdit = () => {
  editingId.value = null;
  form.reset();
  form.work_date = today;
};

const removeEntry = (e) => {
  if (confirm(t('Deze urenregel verwijderen?'))) {
    router.delete(route('hours.destroy', e.id), { preserveScroll: true });
  }
};

/* ---------- Timer ---------- */
const now = ref(Date.now());
const tick = setInterval(() => { now.value = Date.now(); }, 1000);
onBeforeUnmount(() => clearInterval(tick));

const elapsed = computed(() => {
  if (!props.timer) return '';
  const secs = Math.max(0, Math.floor((now.value - new Date(props.timer.started_at).getTime()) / 1000));
  const h = Math.floor(secs / 3600);
  const m = Math.floor((secs % 3600) / 60);
  const s = secs % 60;
  return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

const startTimer = () => {
  router.post(route('hours.timer.start'), {
    customer_id: form.customer_id || null,
    project: form.project || null,
    description: form.description || null,
  }, { preserveScroll: true });
};

const stopTimer = () => {
  router.post(route('hours.timer.stop'), {}, { preserveScroll: true });
};

/* ---------- Strippenkaarten ---------- */
const showCardForm = ref(false);
const cardForm = useForm({
  customer_id: null,
  hours: '',
  price: '',
  name: '',
  valid_until: '',
});

const submitCard = () => {
  cardForm
    .transform((data) => ({
      customer_id: data.customer_id,
      hours: parseFloat(String(data.hours).replace(',', '.')) || 0,
      price: parseFloat(String(data.price).replace(',', '.')) || 0,
      name: data.name || null,
      valid_until: data.valid_until || null,
    }))
    .post(route('timecards.store'), {
      preserveScroll: true,
      onSuccess: () => { showCardForm.value = false; cardForm.reset(); },
    });
};

const invoiceCard = (card) => {
  if (confirm(t('Conceptfactuur maken voor ":name" (:amount)?', { name: card.name, amount: eur(card.price) }))) {
    router.post(route('timecards.invoice', card.id));
  }
};

const removeCard = (card) => {
  if (confirm(t('Strippenkaart ":name" verwijderen?', { name: card.name }) + '\n\n' + t('De gedekte uren komen dan weer als factureerbare uren in de lijst.'))) {
    router.delete(route('timecards.destroy', card.id), { preserveScroll: true });
  }
};

// Hint bij het urenschrijven: wordt dit afgeschreven van een strippenkaart?
const activeCardForForm = computed(() => {
  if (!form.customer_id) return null;
  return props.time_cards.find(c =>
    c.customer_id === form.customer_id && !c.expired && c.remaining_minutes > 0
  ) || null;
});

/* ---------- Factureren ---------- */
const invoiceCustomer = (row) => {
  const amountLabel = row.amount != null ? ` (${eur(row.amount)})` : '';
  if (confirm(t('Conceptfactuur maken voor :customer met :hours uur:amount?', { customer: row.customer_name, hours: dur(row.minutes), amount: amountLabel }))) {
    router.post(route('hours.invoice'), { customer_id: row.customer_id });
  }
};

/* ---------- Filters ---------- */
const applyFilters = (overrides = {}) => {
  router.get(route('hours.index'), {
    period: props.filters.period,
    status: props.filters.status,
    customer_id: props.filters.customer_id || undefined,
    ...overrides,
  }, { preserveState: true, preserveScroll: true });
};

const totalOpenAmount = computed(() => {
  if (props.billable_by_customer.some(r => r.amount == null)) return null;
  return props.billable_by_customer.reduce((sum, r) => sum + r.amount, 0);
});
</script>

<template>
  <Head :title="$t('Uren')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">{{ $t('Verkoop') }} / <span class="breadcrumb-current">{{ $t('Uren') }}</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Urenregistratie') }}</h1>
        <p class="page-subtitle">{{ $t('Houd gewerkte uren bij per klant of project — en zet ze met één klik op een conceptfactuur.') }}</p>
      </div>
    </div>

    <!-- Lopende timer -->
    <div v-if="timer" class="timer-bar">
      <span class="timer-dot"></span>
      <span class="timer-time">{{ elapsed }}</span>
      <span class="timer-info">
        {{ timer.description }}<template v-if="timer.customer_name"> · {{ timer.customer_name }}</template>
      </span>
      <button type="button" class="btn btn-primary btn-sm" @click="stopTimer">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
        {{ $t('Stop timer') }}
      </button>
    </div>

    <!-- Statistieken -->
    <div class="kpi-grid">
      <div class="kpi"><div class="lbl">{{ $t('Deze week') }}</div><div class="val">{{ dur(stats.week_minutes) }}</div><div class="meta">{{ $t('uur geschreven') }}</div></div>
      <div class="kpi"><div class="lbl">{{ $t('Deze maand') }}</div><div class="val">{{ dur(stats.month_minutes) }}</div><div class="meta">{{ $t('uur geschreven') }}</div></div>
      <div class="kpi" :class="{ alert: stats.open_minutes > 0 }">
        <div class="lbl">{{ $t('Nog te factureren') }}</div>
        <div class="val">{{ dur(stats.open_minutes) }}</div>
        <div class="meta">{{ totalOpenAmount != null ? $t(':amount aan open uren', { amount: eur(totalOpenAmount) }) : $t('uur factureerbaar') }}</div>
      </div>
    </div>

    <!-- Uren schrijven / bewerken -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="entry-form-title">
          {{ editingId ? $t('Urenregel bewerken') : $t('Uren schrijven') }}
          <button v-if="editingId" type="button" class="btn btn-secondary btn-sm" @click="cancelEdit">{{ $t('Annuleren') }}</button>
        </div>
        <form @submit.prevent="submit" class="entry-form">
          <div class="form-group">
            <label>{{ $t('Datum') }} *</label>
            <input type="date" v-model="form.work_date">
            <div v-if="form.errors.work_date" class="field-error">{{ form.errors.work_date }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Klant') }}</label>
            <select v-model="form.customer_id">
              <option :value="null">{{ $t('— Geen klant —') }}</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>{{ $t('Project') }}<span class="label-hint">{{ $t('(optioneel)') }}</span></label>
            <input type="text" v-model="form.project" list="project-list" maxlength="100" :placeholder="$t('Bijv. Website')">
            <datalist id="project-list"><option v-for="p in projects" :key="p" :value="p" /></datalist>
          </div>
          <div class="form-group grow">
            <label>{{ $t('Omschrijving') }} *</label>
            <input type="text" v-model="form.description" maxlength="500" :placeholder="$t('Wat heb je gedaan? (komt op de factuur)')">
            <div v-if="form.errors.description" class="field-error">{{ form.errors.description }}</div>
          </div>
          <div class="form-group narrow">
            <label>{{ $t('Duur') }} *</label>
            <input type="text" v-model="form.duration" :placeholder="$t('1:30 of 1,5')" inputmode="decimal">
            <div v-if="form.errors.minutes" class="field-error">{{ form.errors.minutes }}</div>
          </div>
          <div class="form-group narrow">
            <label>{{ $t('Uurtarief') }}<span class="label-hint">{{ symbol }}</span></label>
            <input type="text" v-model="form.hourly_rate" :placeholder="ratePlaceholder" inputmode="decimal">
            <div v-if="form.errors.hourly_rate" class="field-error">{{ form.errors.hourly_rate }}</div>
          </div>
          <div class="entry-form-actions">
            <span v-if="activeCardForForm && form.billable" class="card-hint">
              {{ $t('Wordt afgeschreven van') }} <b>{{ activeCardForForm.name }}</b> ({{ $t('nog :hours tegoed', { hours: dur(activeCardForForm.remaining_minutes) }) }})
            </span>
            <label class="checkbox-row" style="margin:0;">
              <input type="checkbox" v-model="form.billable">
              <span>{{ $t('Factureerbaar') }}</span>
            </label>
            <button v-if="!editingId && !timer" type="button" class="btn btn-secondary" @click="startTimer" :title="$t('Start een timer met de ingevulde klant en omschrijving')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"/></svg>
              {{ $t('Start timer') }}
            </button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? $t('Bezig…') : (editingId ? $t('Opslaan') : $t('Uren schrijven')) }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Strippenkaarten (vooraf betaalde urenbundels) -->
    <div v-if="time_cards.length || showCardForm" class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="entry-form-title">
          {{ $t('Strippenkaarten') }}
          <button v-if="!showCardForm" type="button" class="btn btn-secondary btn-sm" @click="showCardForm = true">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ $t('Nieuwe strippenkaart') }}
          </button>
        </div>

        <!-- Nieuwe kaart -->
        <form v-if="showCardForm" @submit.prevent="submitCard" class="tc-form">
          <div class="form-group">
            <label>{{ $t('Klant') }} *</label>
            <select v-model="cardForm.customer_id">
              <option :value="null">{{ $t('— Kies een klant —') }}</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <div v-if="cardForm.errors.customer_id" class="field-error">{{ cardForm.errors.customer_id }}</div>
          </div>
          <div class="form-group narrow">
            <label>{{ $t('Aantal uur') }} *</label>
            <input type="text" v-model="cardForm.hours" :placeholder="$t('Bijv. 10')" inputmode="decimal">
            <div v-if="cardForm.errors.hours" class="field-error">{{ cardForm.errors.hours }}</div>
          </div>
          <div class="form-group narrow">
            <label>{{ $t('Bundelprijs') }} *<span class="label-hint">{{ $t(':symbol excl.', { symbol }) }}</span></label>
            <input type="text" v-model="cardForm.price" :placeholder="$t('Bijv. 850')" inputmode="decimal">
            <div v-if="cardForm.errors.price" class="field-error">{{ cardForm.errors.price }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Naam') }}<span class="label-hint">{{ $t('(op de factuur)') }}</span></label>
            <input type="text" v-model="cardForm.name" maxlength="190" :placeholder="$t('Strippenkaart 10 uur')">
          </div>
          <div class="form-group narrow">
            <label>{{ $t('Geldig tot') }}<span class="label-hint">{{ $t('(optioneel)') }}</span></label>
            <input type="date" v-model="cardForm.valid_until">
            <div v-if="cardForm.errors.valid_until" class="field-error">{{ cardForm.errors.valid_until }}</div>
          </div>
          <div class="tc-form-actions">
            <button type="button" class="btn btn-secondary" @click="showCardForm = false">{{ $t('Annuleren') }}</button>
            <button type="submit" class="btn btn-primary" :disabled="cardForm.processing">
              {{ cardForm.processing ? $t('Bezig…') : $t('Strippenkaart aanmaken') }}
            </button>
          </div>
        </form>

        <!-- Kaarten -->
        <div v-for="c in time_cards" :key="c.id" class="tc-row">
          <div class="tc-info">
            <div class="tc-name">
              {{ c.name }} <span class="muted">· {{ c.customer_name }}</span>
              <span v-if="c.expired" class="pill pill-overdue" style="margin-left:6px;">{{ $t('Verlopen') }}</span>
              <span v-else-if="c.remaining_minutes === 0" class="pill pill-muted" style="margin-left:6px;">{{ $t('Op') }}</span>
            </div>
            <div class="tc-bar"><div class="tc-fill" :style="{ width: Math.min(100, c.used_minutes / c.total_minutes * 100) + '%' }"></div></div>
            <div class="tc-meta">
              {{ $t(':used gebruikt van :total uur', { used: dur(c.used_minutes), total: dur(c.total_minutes) }) }} — <b>{{ $t('nog :hours tegoed', { hours: dur(c.remaining_minutes) }) }}</b>
              <template v-if="c.valid_until_label"> · {{ $t('geldig tot :date', { date: c.valid_until_label }) }}</template>
              <template v-if="c.invoice_id"> · <Link :href="route('invoices.show', c.invoice_id)" style="color:var(--brand-dark);">{{ c.invoice_number || $t('conceptfactuur') }}</Link></template>
            </div>
          </div>
          <div class="tc-actions">
            <button v-if="!c.invoice_id" type="button" class="btn btn-primary btn-sm" @click="invoiceCard(c)">{{ $t('Factureer :amount', { amount: eur(c.price) }) }}</button>
            <button type="button" class="icon-btn" :title="$t('Verwijderen')" @click="removeCard(c)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-else style="margin-bottom:16px;text-align:right;">
      <button type="button" class="btn btn-secondary btn-sm" @click="showCardForm = true">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Strippenkaart verkopen (vooraf betaalde uren)') }}
      </button>
    </div>

    <!-- Met één klik factureren -->
    <div v-if="billable_by_customer.length" class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="entry-form-title" style="margin-bottom:4px;">{{ $t('Klaar om te factureren') }}</div>
        <p class="bill-hint">{{ $t('Alle openstaande factureerbare uren van een klant worden gebundeld op één conceptfactuur — die je daarna gewoon controleert en verstuurt.') }}</p>
        <div v-for="row in billable_by_customer" :key="row.customer_id" class="bill-row">
          <span class="bill-name">{{ row.customer_name }}</span>
          <span class="bill-meta">{{ $t(':hours uur', { hours: dur(row.minutes) }) }} · {{ row.entries === 1 ? $t(':n regel', { n: row.entries }) : $t(':n regels', { n: row.entries }) }}</span>
          <span class="bill-amount num">{{ row.amount != null ? eur(row.amount) : $t('— stel een tarief in —') }}</span>
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

    <!-- Urenlijst -->
    <div class="card" v-if="entries.data.length > 0">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Datum') }}</th>
            <th>{{ $t('Klant') }}</th>
            <th>{{ $t('Omschrijving') }}</th>
            <th class="right">{{ $t('Duur') }}</th>
            <th class="right">{{ $t('Tarief') }}</th>
            <th class="right">{{ $t('Bedrag') }}</th>
            <th>{{ $t('Status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="e in entries.data" :key="e.id">
            <td :data-label="$t('Datum')">{{ e.work_date_label }}</td>
            <td :data-label="$t('Klant')">{{ e.customer_name || '—' }}</td>
            <td class="cell-primary" :data-label="$t('Omschrijving')">
              {{ e.description }}
              <span v-if="e.project" class="muted"> · {{ e.project }}</span>
            </td>
            <td class="num right" :data-label="$t('Duur')">{{ dur(e.minutes) }}</td>
            <td class="num right" :data-label="$t('Tarief')">{{ e.billable && e.effective_rate != null ? eur(e.effective_rate) : '—' }}</td>
            <td class="num right" :data-label="$t('Bedrag')">{{ e.amount != null ? eur(e.amount) : '—' }}</td>
            <td :data-label="$t('Status')">
              <Link v-if="e.invoice_id" :href="route('invoices.show', e.invoice_id)" class="pill pill-paid" style="text-decoration:none;">
                {{ e.invoice_number || $t('Conceptfactuur') }}
              </Link>
              <span v-else-if="e.time_card_id" class="pill pill-card" :title="$t('Afgeschreven van een strippenkaart — al betaald')">{{ $t('Strippenkaart') }}</span>
              <span v-else-if="!e.billable" class="pill pill-muted">{{ $t('Niet-factureerbaar') }}</span>
              <span v-else class="pill pill-sent">{{ $t('Open') }}</span>
            </td>
            <td class="row-actions">
              <template v-if="!e.invoice_id">
                <button type="button" class="icon-btn" :title="$t('Bewerken')" @click="startEdit(e)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                </button>
                <button type="button" class="icon-btn" :title="$t('Verwijderen')" @click="removeEntry(e)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="pagination" v-if="entries.last_page > 1">
        <Link v-for="link in entries.links" :key="link.label"
          :href="link.url || '#'"
          v-html="link.label"
          :class="['page-link', { active: link.active, disabled: !link.url }]"
          preserve-state
        />
      </div>
    </div>
    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">{{ $t('Nog geen uren in deze periode') }}</div>
      <div>{{ $t('Schrijf hierboven je eerste uren, of start de timer en ga aan het werk.') }}</div>
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

.timer-bar {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 12px;
  padding: 12px 16px; margin-bottom: 16px;
}
.timer-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--brand); animation: timer-pulse 1.2s ease-in-out infinite; }
@keyframes timer-pulse { 50% { opacity: 0.3; } }
.timer-time { font-family: var(--font-mono); font-weight: 700; font-size: 17px; color: var(--brand-darker); min-width: 84px; }
.timer-info { flex: 1; font-size: 13px; color: var(--text-2); min-width: 120px; }

.entry-form-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.entry-form { display: grid; grid-template-columns: 150px 190px 160px minmax(0, 1fr) 110px 130px; gap: 12px; align-items: start; }
.entry-form .grow { grid-column: auto; }
.entry-form-actions { grid-column: 1 / -1; display: flex; align-items: center; justify-content: flex-end; gap: 14px; }
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

.pill-muted { background: var(--surface-2); color: var(--text-3); }
.pill-card { background: #EDE9FE; color: #6D28D9; }

.card-hint { font-size: 12px; color: #6D28D9; background: #EDE9FE; border-radius: 7px; padding: 5px 10px; }

.tc-form { display: grid; grid-template-columns: minmax(0, 1fr) 110px 130px minmax(0, 1fr) 150px; gap: 12px; align-items: start; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px dashed var(--border); }
.tc-form-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 10px; }
.tc-row { display: flex; align-items: center; gap: 16px; padding: 11px 0; border-top: 1px solid var(--border); }
.tc-row:first-of-type { border-top: none; }
.tc-info { flex: 1; min-width: 0; }
.tc-name { font-weight: 600; font-size: 13.5px; }
.tc-bar { height: 8px; background: var(--surface-2); border-radius: 5px; margin: 7px 0 5px; max-width: 420px; }
.tc-fill { height: 100%; background: linear-gradient(90deg, #8B5CF6, #6D28D9); border-radius: 5px; }
.tc-meta { font-size: 12px; color: var(--text-3); }
.tc-actions { display: flex; align-items: center; gap: 6px; flex: none; }

@media (max-width: 900px) {
  .tc-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .tc-row { flex-wrap: wrap; }
}
.row-actions { white-space: nowrap; text-align: right; }
.icon-btn { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.icon-btn:hover { background: var(--surface-2); color: var(--brand-dark); }

@media (max-width: 1100px) {
  .entry-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 760px) {
  .kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
  .kpi { padding: 14px; }
  .kpi .val { font-size: 18px; }
  .entry-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .bill-row .btn { margin-left: auto; }
}
@media (max-width: 400px) {
  .entry-form { grid-template-columns: minmax(0, 1fr); }
}
</style>
