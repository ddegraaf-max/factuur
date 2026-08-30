<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { t } from '@/i18n';
import { ref } from 'vue';

const brand = usePage().props.brand;

const props = defineProps({
  profiles: Array,
  frequencies: Object,
});

const editing = ref(null);
const editForm = useForm({
  frequency: 'monthly',
  next_run_on: '',
  end_date: '',
  auto_send: false,
});

const openEdit = (p) => {
  editing.value = p;
  editForm.frequency = p.frequency;
  editForm.next_run_on = p.next_run_on;
  editForm.end_date = p.end_date || '';
  editForm.auto_send = p.auto_send;
  editForm.clearErrors();
};

const saveEdit = () => {
  editForm
    .transform((data) => ({ ...data, end_date: data.end_date || null }))
    .patch(route('recurring.update', editing.value.id), {
      onSuccess: () => { editing.value = null; },
    });
};

const toggleActive = (p) => {
  router.patch(route('recurring.update', p.id), { active: !p.active }, { preserveScroll: true });
};

const destroy = (p) => {
  if (confirm(t('Terugkerend profiel voor :name verwijderen? Al gegenereerde facturen blijven bestaan.', { name: p.customer_name }))) {
    router.delete(route('recurring.destroy', p.id), { preserveScroll: true });
  }
};
</script>

<template>
  <Head :title="$t('Terugkerende facturen')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Verkoop') }} / <span class="breadcrumb-current">{{ $t('Terugkerend') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Terugkerende facturen') }}</h1>
        <p class="page-subtitle">{{ $t('Automatisch periodiek factureren — ideaal voor abonnementen en vaste diensten') }}</p>
      </div>
      <div class="page-actions">
        <Link :href="route('invoices.index')" class="btn btn-secondary btn-sm">{{ $t('Naar facturen') }}</Link>
      </div>
    </div>

    <div class="rec-hint">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
      <div v-html="$t('<b>Zo werkt het:</b> open een bestaande factuur en kies <b>“Maak terugkerend”</b>. :brand maakt dan elke periode automatisch een nieuwe factuur aan — als concept om zelf te controleren, of direct verstuurd naar je klant.', { brand: brand.name })"></div>
    </div>

    <div v-if="profiles.length === 0" class="card empty">
      <div style="font-family:var(--font-display);font-size:18px;font-weight:600;margin-bottom:6px;">{{ $t('Nog geen terugkerende facturen') }}</div>
      <div style="color:var(--text-3);margin-bottom:18px;">{{ $t('Open een factuur en kies “Maak terugkerend” om te starten.') }}</div>
      <Link :href="route('invoices.index')" class="btn btn-primary btn-sm" style="display:inline-flex;">{{ $t('Bekijk je facturen') }}</Link>
    </div>

    <div v-else class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Profielen') }}</div>
          <div class="card-subtitle">{{ profiles.length === 1 ? $t('1 profiel') : $t(':n profielen', { n: profiles.length }) }}</div>
        </div>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Klant') }}</th>
            <th>{{ $t('Omschrijving') }}</th>
            <th>{{ $t('Frequentie') }}</th>
            <th>{{ $t('Volgende factuur') }}</th>
            <th>{{ $t('Laatst gemaakt') }}</th>
            <th class="right">{{ $t('Bedrag') }}</th>
            <th>{{ $t('Wijze') }}</th>
            <th>{{ $t('Status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in profiles" :key="p.id" @click="openEdit(p)">
            <td class="cell-primary">{{ p.customer_name }}</td>
            <td :data-label="$t('Omschrijving')">{{ p.description }}</td>
            <td :data-label="$t('Frequentie')">{{ p.frequency_label }}</td>
            <td :data-label="$t('Volgende factuur')">{{ p.active ? p.next_run_label : '—' }}</td>
            <td :data-label="$t('Laatst gemaakt')">{{ p.last_run_label || $t('Nog niet') }}<span v-if="p.invoices_generated" style="color:var(--text-4);"> · {{ p.invoices_generated }}×</span></td>
            <td class="num right" :data-label="$t('Bedrag')">{{ eur(p.total) }}</td>
            <td :data-label="$t('Wijze')"><span class="pill" :class="p.auto_send ? 'pill-sent' : 'pill-draft'">{{ p.auto_send ? $t('Direct versturen') : $t('Als concept') }}</span></td>
            <td :data-label="$t('Status')"><span class="pill" :class="p.active ? 'pill-paid' : 'pill-cancelled'" style="text-decoration:none;">{{ p.active ? $t('Actief') : $t('Gepauzeerd') }}</span></td>
            <td @click.stop>
              <div style="display:flex;gap:4px;justify-content:flex-end;">
                <button class="btn btn-ghost btn-sm" @click="toggleActive(p)">{{ p.active ? $t('Pauzeren') : $t('Hervatten') }}</button>
                <button class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="destroy(p)">{{ $t('Verwijder') }}</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Edit modal -->
    <div v-if="editing" class="modal-overlay" @click.self="editing = null">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">{{ $t('Profiel bewerken') }} — {{ editing.customer_name }}</div>
          <button class="icon-btn" @click="editing = null">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Frequentie') }}</label>
              <select v-model="editForm.frequency">
                <option v-for="(label, value) in frequencies" :key="value" :value="value">{{ label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>{{ $t('Volgende factuurdatum') }}</label>
              <input type="date" v-model="editForm.next_run_on">
              <div v-if="editForm.errors.next_run_on" class="field-error">{{ editForm.errors.next_run_on }}</div>
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('Stopt automatisch na') }}<span class="label-hint">{{ $t('(optioneel)') }}</span></label>
            <input type="date" v-model="editForm.end_date">
            <div v-if="editForm.errors.end_date" class="field-error">{{ editForm.errors.end_date }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Wijze') }}</label>
            <select v-model="editForm.auto_send">
              <option :value="false">{{ $t('Als concept klaarzetten (zelf controleren en versturen)') }}</option>
              <option :value="true">{{ $t('Direct versturen naar de klant') }}</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="editing = null">{{ $t('Annuleren') }}</button>
            <button class="btn btn-primary btn-sm" @click="saveEdit" :disabled="editForm.processing">{{ $t('Opslaan') }}</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.rec-hint {
  display: flex; gap: 14px; align-items: flex-start;
  background: var(--brand-tint); border: 1px solid var(--brand-border);
  color: var(--brand-darker); border-radius: 12px;
  padding: 16px 20px; margin-bottom: 20px; font-size: 13px; line-height: 1.6;
}
.rec-hint svg { width: 22px; height: 22px; flex-shrink: 0; margin-top: 2px; }
.empty { padding: 80px 20px; text-align: center; }
</style>
