<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { t } from '@/i18n';
import { computed, watch } from 'vue';

/**
 * Uitvraag openen: werkpakket kiezen, bedrijven aanvinken, omschrijving en
 * deadline — vanuit een geaccepteerde offerte (met voorinvulling) of los.
 * Verkoopprijzen en het klantadres gaan bewust niet mee: alleen postcode en
 * plaats, zodat een bedrijf de reistijd kan inschatten.
 */
const props = defineProps({
  show: Boolean,
  packages: { type: Array, default: () => [] },
  quote: { type: Object, default: null },
});
const emit = defineEmits(['close']);

const page = usePage();
const pageError = computed(() => (page.props.errors || {}).tender ?? null);

const isoWeek = (date) => {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
  const day = d.getUTCDay() || 7;
  d.setUTCDate(d.getUTCDate() + 4 - day);
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
  const week = Math.ceil(((d - yearStart) / 86400000 + 1) / 7);
  return `${d.getUTCFullYear()}-W${String(week).padStart(2, '0')}`;
};
const plusDays = (n) => { const d = new Date(); d.setDate(d.getDate() + n); return d; };
const iso = (d) => d.toISOString().slice(0, 10);

const location = computed(() => props.quote
  ? [props.quote.customer_postal_code, props.quote.customer_city].filter(Boolean).join(' ')
  : '');

const form = useForm({
  work_package_id: '',
  subcontractor_ids: [],
  title: '',
  description: '',
  location: location.value,
  start_week: isoWeek(plusDays(28)),
  deadline: iso(plusDays(7)),
  budget: '',
});

const selectedPackage = computed(() => props.packages.find(p => p.id === Number(form.work_package_id)) || null);
const candidates = computed(() => selectedPackage.value?.subcontractors || []);
const mailable = computed(() => candidates.value.filter(c => c.has_email));

const prefill = () => {
  const pkg = selectedPackage.value;
  if (!pkg) return;
  form.title = pkg.name;
  // Standaard: de eerste vijf bedrijven mét e-mailadres.
  form.subcontractor_ids = mailable.value.slice(0, 5).map(c => c.id);
  const lines = [];
  if (location.value) lines.push(t('Project in :location.', { location: location.value }));
  lines.push(t('Onderdeel: :package', { package: pkg.name }));
  if (props.quote?.lines?.length) {
    lines.push('');
    lines.push(t('Werkzaamheden volgens de offerte:'));
    for (const l of props.quote.lines) lines.push('- ' + l.description);
  }
  if (pkg.description) {
    lines.push('');
    lines.push(t('Graag in uw prijs opnemen / aangeven: :items', { items: pkg.description }));
  }
  form.description = lines.join('\n');
};

watch(() => form.work_package_id, prefill);
watch(() => props.show, (open) => {
  if (open) {
    form.clearErrors();
    if (!form.work_package_id && props.packages.length === 1) form.work_package_id = props.packages[0].id;
  }
});

const toggle = (id) => {
  const i = form.subcontractor_ids.indexOf(id);
  if (i >= 0) form.subcontractor_ids.splice(i, 1); else form.subcontractor_ids.push(id);
};

const submit = () => {
  form
    .transform((data) => ({ ...data, budget: data.budget === '' ? null : String(data.budget).replace(',', '.') }))
    .post(props.quote ? route('tenders.from_quote', props.quote.id) : route('tenders.store'), {
      preserveScroll: true,
      onSuccess: () => { emit('close'); form.reset(); },
    });
};
</script>

<template>
  <div v-if="show" class="modal-overlay" @click.self="emit('close')">
    <div class="modal" style="max-width:720px;">
      <div class="modal-header">
        <div class="modal-title">{{ $t('Prijzen opvragen bij onderaannemers') }}</div>
        <button class="btn btn-ghost btn-sm" @click="emit('close')">✕</button>
      </div>
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-3);margin:0 0 14px;line-height:1.6;">
          {{ $t('Elk bedrijf krijgt een mail met een eigen link om prijs en beschikbaarheid door te geven. Je verkoopprijs en het adres van de klant gaan niet mee — alleen postcode en plaats.') }}
        </p>
        <div v-if="pageError" class="field-error" style="margin-bottom:12px;">{{ pageError }}</div>
        <div v-if="!packages.length" class="field-error" style="margin-bottom:12px;">
          {{ $t('Er zijn nog geen werkpakketten. Maak ze eerst aan bij Inkoop → Onderaannemers.') }}
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>{{ $t('Werkpakket') }} *</label>
            <select v-model="form.work_package_id">
              <option value="" disabled>{{ $t('Kies een onderdeel…') }}</option>
              <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }} ({{ p.subcontractors.length }})</option>
            </select>
            <div v-if="form.errors.work_package_id" class="field-error">{{ form.errors.work_package_id }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Titel') }}</label>
            <input type="text" v-model="form.title" maxlength="160" :placeholder="selectedPackage?.name || ''">
          </div>
        </div>

        <div v-if="selectedPackage" class="form-group">
          <label>{{ $t('Bedrijven') }} * <span class="label-hint">{{ $t(':n gekozen', { n: form.subcontractor_ids.length }) }}</span></label>
          <div v-if="!candidates.length" class="tm-empty">
            {{ $t('Nog geen bedrijven gekoppeld aan dit werkpakket. Voeg ze toe bij Inkoop → Onderaannemers.') }}
          </div>
          <div v-else class="tm-list">
            <label v-for="c in candidates" :key="c.id" class="tm-item" :class="{ off: !c.has_email }">
              <input type="checkbox" :checked="form.subcontractor_ids.includes(c.id)" :disabled="!c.has_email" @change="toggle(c.id)">
              <span class="tm-name">{{ c.name }}</span>
              <span class="tm-meta">{{ c.city || '' }}<template v-if="!c.has_email"> · {{ $t('geen e-mailadres') }}</template></span>
            </label>
          </div>
          <div v-if="form.errors.subcontractor_ids" class="field-error">{{ form.errors.subcontractor_ids }}</div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>{{ $t('Locatie') }} <span class="label-hint">{{ $t('(postcode en plaats)') }}</span></label>
            <input type="text" v-model="form.location" maxlength="160" placeholder="1402 AT Bussum">
          </div>
          <div class="form-group">
            <label>{{ $t('Gewenste startweek') }}</label>
            <input type="text" v-model="form.start_week" maxlength="12" placeholder="2026-W42">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>{{ $t('Reageren vóór') }} *</label>
            <input type="date" v-model="form.deadline">
            <div v-if="form.errors.deadline" class="field-error">{{ form.errors.deadline }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Jouw calculatie (excl. btw)') }} <span class="label-hint">{{ $t('(alleen ter vergelijking, gaat niet mee)') }}</span></label>
            <input type="text" v-model="form.budget" inputmode="decimal" placeholder="0,00">
            <div v-if="form.errors.budget" class="field-error">{{ form.errors.budget }}</div>
          </div>
        </div>
        <div class="form-group">
          <label>{{ $t('Omschrijving voor de bedrijven') }}</label>
          <textarea v-model="form.description" rows="7" maxlength="5000"></textarea>
          <div v-if="form.errors.description" class="field-error">{{ form.errors.description }}</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" @click="emit('close')">{{ $t('Annuleren') }}</button>
        <button class="btn btn-primary btn-sm" :disabled="form.processing || !selectedPackage || !form.subcontractor_ids.length" @click="submit">
          {{ $t('Versturen naar :n bedrijven', { n: form.subcontractor_ids.length }) }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tm-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 6px; }
.tm-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; font-size: 13.5px; }
.tm-item.off { opacity: 0.55; cursor: not-allowed; }
.tm-name { font-weight: 600; color: var(--text); }
.tm-meta { color: var(--text-3); font-size: 12px; margin-left: auto; white-space: nowrap; }
.tm-empty { font-size: 13px; color: var(--text-3); padding: 10px 12px; border: 1px dashed var(--border); border-radius: 8px; }
</style>
