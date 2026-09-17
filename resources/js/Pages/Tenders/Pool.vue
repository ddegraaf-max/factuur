<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { t } from '@/i18n';
import { computed, ref } from 'vue';

const props = defineProps({
  packages: Array,
  subcontractors: Array,
});

const page = usePage();
const errors = computed(() => page.props.errors || {});

/* ---------- Werkpakketten ---------- */
const packageModal = ref(false);
const editingPackage = ref(null);
const packageForm = useForm({ name: '', description: '' });
const openPackage = (p = null) => {
  editingPackage.value = p;
  packageForm.name = p?.name || '';
  packageForm.description = p?.description || '';
  packageForm.clearErrors();
  packageModal.value = true;
};
const savePackage = () => {
  const opts = { preserveScroll: true, onSuccess: () => { packageModal.value = false; } };
  editingPackage.value
    ? packageForm.patch(route('tenders.packages.update', editingPackage.value.id), opts)
    : packageForm.post(route('tenders.packages.store'), opts);
};
const destroyPackage = (p) => {
  if (confirm(t('Werkpakket ":name" verwijderen?', { name: p.name }))) router.delete(route('tenders.packages.destroy', p.id), { preserveScroll: true });
};
const seed = () => router.post(route('tenders.packages.seed'), {}, { preserveScroll: true });

/* ---------- Bedrijven ---------- */
const subModal = ref(false);
const editingSub = ref(null);
const subForm = useForm({ name: '', contact_name: '', email: '', phone: '', city: '', website: '', notes: '', package_ids: [] });
const openSub = (s = null) => {
  editingSub.value = s;
  subForm.name = s?.name || '';
  subForm.contact_name = s?.contact_name || '';
  subForm.email = s?.email || '';
  subForm.phone = s?.phone || '';
  subForm.city = s?.city || '';
  subForm.website = s?.website || '';
  subForm.notes = s?.notes || '';
  subForm.package_ids = [...(s?.package_ids || [])];
  subForm.clearErrors();
  subModal.value = true;
};
const togglePackage = (id) => {
  const i = subForm.package_ids.indexOf(id);
  if (i >= 0) subForm.package_ids.splice(i, 1); else subForm.package_ids.push(id);
};
const saveSub = () => {
  const opts = { preserveScroll: true, onSuccess: () => { subModal.value = false; } };
  editingSub.value
    ? subForm.patch(route('tenders.subcontractors.update', editingSub.value.id), opts)
    : subForm.post(route('tenders.subcontractors.store'), opts);
};
const destroySub = (s) => {
  if (confirm(t(':name uit de pool verwijderen?', { name: s.name }))) router.delete(route('tenders.subcontractors.destroy', s.id), { preserveScroll: true });
};

/* ---------- Import ---------- */
const importModal = ref(false);
const importForm = useForm({ lines: '' });
const runImport = () => importForm.post(route('tenders.subcontractors.import'), { preserveScroll: true, onSuccess: () => { importModal.value = false; importForm.reset(); } });

const packageName = (id) => props.packages.find(p => p.id === id)?.name || '';
const hours = (h) => h === null ? '—' : (h < 48 ? t(':n uur', { n: h }) : t(':n dagen', { n: Math.round(h / 24) }));
</script>

<template>
  <Head :title="$t('Onderaannemers')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Inkoop') }} / <span class="breadcrumb-current">{{ $t('Onderaannemers') }}</span></template>
    <template #topbar-actions>
      <button class="btn btn-secondary btn-sm" @click="importModal = true">{{ $t('Meerdere bedrijven plakken') }}</button>
      <button class="btn btn-primary btn-sm" @click="openSub()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Bedrijf toevoegen') }}
      </button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Pool van onderaannemers') }}</h1>
        <p class="page-subtitle">{{ $t('Per werkpakket de bedrijven die je een prijs vraagt. De cijfers groeien mee: reactietijd, prijsniveau en gewonnen opdrachten.') }}</p>
      </div>
      <div class="page-actions">
        <Link :href="route('tenders.index')" class="btn btn-secondary btn-sm">{{ $t('Naar uitvragen') }}</Link>
      </div>
    </div>

    <div v-if="errors.package || errors.subcontractor" class="field-error" style="margin-bottom:12px;">{{ errors.package || errors.subcontractor }}</div>

    <!-- Werkpakketten -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Werkpakketten') }}</div>
          <div class="sub">{{ $t('De onderdelen van een project waarvoor je prijzen opvraagt. De omschrijving is de checklist die in de mail meegaat.') }}</div>
        </div>
        <div style="display:flex;gap:8px;">
          <button class="btn btn-secondary btn-sm" @click="seed">{{ $t('Standaardpakketten toevoegen') }}</button>
          <button class="btn btn-secondary btn-sm" @click="openPackage()">{{ $t('Werkpakket toevoegen') }}</button>
        </div>
      </div>
      <table v-if="packages.length" class="data-table">
        <thead><tr><th>{{ $t('Werkpakket') }}</th><th>{{ $t('Graag opgeven') }}</th><th class="right">{{ $t('Bedrijven') }}</th><th></th></tr></thead>
        <tbody>
          <tr v-for="p in packages" :key="p.id">
            <td class="cell-primary">{{ p.name }}</td>
            <td class="desc">{{ p.description || '—' }}</td>
            <td class="right num">{{ p.subcontractors_count }}</td>
            <td class="right actions">
              <button class="btn btn-ghost btn-sm" @click="openPackage(p)">{{ $t('Bewerken') }}</button>
              <button class="btn btn-ghost btn-sm" @click="destroyPackage(p)">✕</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="card-body sub">{{ $t('Nog geen werkpakketten. Begin met de standaardpakketten voor aan- en verbouw en pas ze aan.') }}</div>
    </div>

    <!-- Bedrijven -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Bedrijven') }} <span class="sub">({{ subcontractors.length }})</span></div>
          <div class="sub">{{ $t('Zonder e-mailadres kan een bedrijf niet worden aangeschreven.') }}</div>
        </div>
      </div>
      <table v-if="subcontractors.length" class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Bedrijf') }}</th>
            <th>{{ $t('Contact') }}</th>
            <th>{{ $t('Werkpakketten') }}</th>
            <th class="right">{{ $t('Uitvragen') }}</th>
            <th class="right">{{ $t('Reacties') }}</th>
            <th class="right">{{ $t('Reactietijd') }}</th>
            <th class="right">{{ $t('Prijsniveau') }}</th>
            <th class="right">{{ $t('Gewonnen') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in subcontractors" :key="s.id">
            <td class="cell-primary">
              {{ s.name }}
              <div class="sub">{{ s.city || '' }}<template v-if="s.source === 'suggested'"> · <span class="tagx">{{ $t('nog te controleren') }}</span></template></div>
            </td>
            <td>
              <div>{{ s.contact_name || '' }}</div>
              <div class="sub" :class="{ warn: !s.email }">{{ s.email || $t('geen e-mailadres') }}<template v-if="s.phone"> · {{ s.phone }}</template></div>
            </td>
            <td><span v-for="id in s.package_ids" :key="id" class="tagx">{{ packageName(id) }}</span><span v-if="!s.package_ids.length" class="sub">—</span></td>
            <td class="right num">{{ s.stats.requests }}</td>
            <td class="right num">{{ s.stats.responded }}<span v-if="s.stats.declined" class="sub"> · {{ $t(':n afgezegd', { n: s.stats.declined }) }}</span></td>
            <td class="right num">{{ hours(s.stats.avg_response_hours) }}</td>
            <td class="right num" :class="{ good: s.stats.price_index !== null && s.stats.price_index < 0, bad: s.stats.price_index !== null && s.stats.price_index > 0 }">
              {{ s.stats.price_index === null ? '—' : (s.stats.price_index > 0 ? '+' : '') + s.stats.price_index + '%' }}
            </td>
            <td class="right num">{{ s.stats.won }}</td>
            <td class="right actions">
              <button class="btn btn-ghost btn-sm" @click="openSub(s)">{{ $t('Bewerken') }}</button>
              <button class="btn btn-ghost btn-sm" @click="destroySub(s)">✕</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="card-body sub">{{ $t('Nog geen bedrijven. Voeg ze één voor één toe, of plak een lijst.') }}</div>
    </div>

    <!-- Modal: werkpakket -->
    <div v-if="packageModal" class="modal-overlay" @click.self="packageModal = false">
      <div class="modal">
        <div class="modal-header"><div class="modal-title">{{ editingPackage ? $t('Werkpakket bewerken') : $t('Werkpakket toevoegen') }}</div></div>
        <div class="modal-body">
          <div class="form-group">
            <label>{{ $t('Naam') }} *</label>
            <input type="text" v-model="packageForm.name" maxlength="120">
            <div v-if="packageForm.errors.name" class="field-error">{{ packageForm.errors.name }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Graag opgeven') }} <span class="label-hint">{{ $t('(wat een bedrijf nodig heeft om te prijzen; gaat mee in de mail)') }}</span></label>
            <textarea v-model="packageForm.description" rows="3" maxlength="2000"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="packageModal = false">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="packageForm.processing" @click="savePackage">{{ $t('Opslaan') }}</button>
        </div>
      </div>
    </div>

    <!-- Modal: bedrijf -->
    <div v-if="subModal" class="modal-overlay" @click.self="subModal = false">
      <div class="modal" style="max-width:640px;">
        <div class="modal-header"><div class="modal-title">{{ editingSub ? $t('Bedrijf bewerken') : $t('Bedrijf toevoegen') }}</div></div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Bedrijfsnaam') }} *</label>
              <input type="text" v-model="subForm.name" maxlength="160">
              <div v-if="subForm.errors.name" class="field-error">{{ subForm.errors.name }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Contactpersoon') }}</label>
              <input type="text" v-model="subForm.contact_name" maxlength="120">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('E-mail') }} <span class="label-hint">{{ $t('(nodig om aan te schrijven)') }}</span></label>
              <input type="email" v-model="subForm.email" maxlength="180">
              <div v-if="subForm.errors.email" class="field-error">{{ subForm.errors.email }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Telefoon') }}</label>
              <input type="text" v-model="subForm.phone" maxlength="40">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Plaats') }}</label>
              <input type="text" v-model="subForm.city" maxlength="120">
            </div>
            <div class="form-group">
              <label>{{ $t('Website') }}</label>
              <input type="text" v-model="subForm.website" maxlength="180">
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('Werkpakketten') }}</label>
            <div class="pk-list">
              <label v-for="p in packages" :key="p.id" class="pk-item" :class="{ on: subForm.package_ids.includes(p.id) }">
                <input type="checkbox" :checked="subForm.package_ids.includes(p.id)" @change="togglePackage(p.id)"> {{ p.name }}
              </label>
              <span v-if="!packages.length" class="sub">{{ $t('Maak eerst werkpakketten aan.') }}</span>
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('Notities') }} <span class="label-hint">{{ $t('(intern)') }}</span></label>
            <textarea v-model="subForm.notes" rows="2" maxlength="2000"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="subModal = false">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="subForm.processing" @click="saveSub">{{ $t('Opslaan') }}</button>
        </div>
      </div>
    </div>

    <!-- Modal: import -->
    <div v-if="importModal" class="modal-overlay" @click.self="importModal = false">
      <div class="modal" style="max-width:640px;">
        <div class="modal-header"><div class="modal-title">{{ $t('Meerdere bedrijven plakken') }}</div></div>
        <div class="modal-body">
          <p class="sub" style="margin:0 0 10px;line-height:1.6;">{{ $t('Eén bedrijf per regel, velden gescheiden door een puntkomma: naam; e-mail; telefoon; plaats; werkpakketten (door komma\'s gescheiden, op naam). Bestaande namen worden overgeslagen.') }}</p>
          <div class="form-group">
            <textarea v-model="importForm.lines" rows="8" placeholder="Jansen Funderingstechniek; info@jansen.nl; 035-1234567; Hilversum; Schroefpalen, Grondwerk"></textarea>
            <div v-if="importForm.errors.lines" class="field-error">{{ importForm.errors.lines }}</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="importModal = false">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="importForm.processing || !importForm.lines.trim()" @click="runImport">{{ $t('Toevoegen') }}</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.warn { color: var(--warning); }
.desc { font-size: 12.5px; color: var(--text-2); max-width: 420px; }
.actions { white-space: nowrap; }
.tagx { display: inline-block; font-size: 11px; padding: 2px 7px; border-radius: 999px; background: var(--surface-2, #F5F5F4); border: 1px solid var(--border); margin: 1px 3px 1px 0; color: var(--text-2); }
.good { color: var(--success); font-weight: 600; }
.bad { color: var(--warning); font-weight: 600; }
.pk-list { display: flex; flex-wrap: wrap; gap: 6px; }
.pk-item { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border: 1px solid var(--border); border-radius: 999px; font-size: 13px; cursor: pointer; }
.pk-item.on { border-color: var(--brand); color: var(--brand); font-weight: 600; }
</style>
