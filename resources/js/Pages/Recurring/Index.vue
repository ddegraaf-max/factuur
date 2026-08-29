<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const brand = usePage().props.brand;

const props = defineProps({
  profiles: Array,
  frequencies: Object,
});

const eur = (n) => '€ ' + Number(n).toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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
  if (confirm(`Terugkerend profiel voor ${p.customer_name} verwijderen? Al gegenereerde facturen blijven bestaan.`)) {
    router.delete(route('recurring.destroy', p.id), { preserveScroll: true });
  }
};
</script>

<template>
  <Head title="Terugkerende facturen" />
  <AppLayout>
    <template #breadcrumb>Verkoop / <span class="breadcrumb-current">Terugkerend</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Terugkerende facturen</h1>
        <p class="page-subtitle">Automatisch periodiek factureren — ideaal voor abonnementen en vaste diensten</p>
      </div>
      <div class="page-actions">
        <Link :href="route('invoices.index')" class="btn btn-secondary btn-sm">Naar facturen</Link>
      </div>
    </div>

    <div class="rec-hint">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
      <div>
        <b>Zo werkt het:</b> open een bestaande factuur en kies <b>“Maak terugkerend”</b>.
        {{ brand.name }} maakt dan elke periode automatisch een nieuwe factuur aan — als concept om zelf
        te controleren, of direct verstuurd naar je klant.
      </div>
    </div>

    <div v-if="profiles.length === 0" class="card empty">
      <div style="font-family:var(--font-display);font-size:18px;font-weight:600;margin-bottom:6px;">Nog geen terugkerende facturen</div>
      <div style="color:var(--text-3);margin-bottom:18px;">Open een factuur en kies “Maak terugkerend” om te starten.</div>
      <Link :href="route('invoices.index')" class="btn btn-primary btn-sm" style="display:inline-flex;">Bekijk je facturen</Link>
    </div>

    <div v-else class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Profielen</div>
          <div class="card-subtitle">{{ profiles.length }} {{ profiles.length === 1 ? 'profiel' : 'profielen' }}</div>
        </div>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th>Klant</th>
            <th>Omschrijving</th>
            <th>Frequentie</th>
            <th>Volgende factuur</th>
            <th>Laatst gemaakt</th>
            <th class="right">Bedrag</th>
            <th>Wijze</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in profiles" :key="p.id" @click="openEdit(p)">
            <td class="cell-primary">{{ p.customer_name }}</td>
            <td data-label="Omschrijving">{{ p.description }}</td>
            <td data-label="Frequentie">{{ p.frequency_label }}</td>
            <td data-label="Volgende">{{ p.active ? p.next_run_label : '—' }}</td>
            <td data-label="Laatst">{{ p.last_run_label || 'Nog niet' }}<span v-if="p.invoices_generated" style="color:var(--text-4);"> · {{ p.invoices_generated }}×</span></td>
            <td class="num right" data-label="Bedrag">{{ eur(p.total) }}</td>
            <td data-label="Wijze"><span class="pill" :class="p.auto_send ? 'pill-sent' : 'pill-draft'">{{ p.auto_send ? 'Direct versturen' : 'Als concept' }}</span></td>
            <td data-label="Status"><span class="pill" :class="p.active ? 'pill-paid' : 'pill-cancelled'" style="text-decoration:none;">{{ p.active ? 'Actief' : 'Gepauzeerd' }}</span></td>
            <td @click.stop>
              <div style="display:flex;gap:4px;justify-content:flex-end;">
                <button class="btn btn-ghost btn-sm" @click="toggleActive(p)">{{ p.active ? 'Pauzeren' : 'Hervatten' }}</button>
                <button class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="destroy(p)">Verwijder</button>
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
          <div class="modal-title">Profiel bewerken — {{ editing.customer_name }}</div>
          <button class="icon-btn" @click="editing = null">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Frequentie</label>
              <select v-model="editForm.frequency">
                <option v-for="(label, value) in frequencies" :key="value" :value="value">{{ label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Volgende factuurdatum</label>
              <input type="date" v-model="editForm.next_run_on">
              <div v-if="editForm.errors.next_run_on" class="field-error">{{ editForm.errors.next_run_on }}</div>
            </div>
          </div>
          <div class="form-group">
            <label>Stopt automatisch na<span class="label-hint">(optioneel)</span></label>
            <input type="date" v-model="editForm.end_date">
            <div v-if="editForm.errors.end_date" class="field-error">{{ editForm.errors.end_date }}</div>
          </div>
          <div class="form-group">
            <label>Wijze</label>
            <select v-model="editForm.auto_send">
              <option :value="false">Als concept klaarzetten (zelf controleren en versturen)</option>
              <option :value="true">Direct versturen naar de klant</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <div></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary btn-sm" @click="editing = null">Annuleren</button>
            <button class="btn btn-primary btn-sm" @click="saveEdit" :disabled="editForm.processing">Opslaan</button>
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
