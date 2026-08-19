<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { computed, ref } from 'vue';

const props = defineProps({
  profiles: Array,   // vaste-lastenprofielen
  suppliers: Array,  // leveranciersnamen (autocomplete)
  categories: Array,
});

const FREQUENCIES = {
  weekly: 'Wekelijks', monthly: 'Maandelijks', quarterly: 'Per kwartaal',
  halfyearly: 'Per half jaar', yearly: 'Jaarlijks',
};

/* ---------- Formulier (nieuw / bewerken) ---------- */
const showForm = ref(false);
const editingId = ref(null);
const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

const form = useForm({
  supplier_name: '',
  category: '',
  frequency: 'monthly',
  next_run_on: new Date().toISOString().slice(0, 10),
  end_date: '',
  rows: [{ amount: null, rate: 21, vat: 0 }],
  auto_paid: true,
  payment_method: 'direct_debit',
  notes: '',
});

const recalcVat = (row) => {
  row.vat = round2((Number(row.amount) || 0) * (Number(row.rate) || 0) / 100);
};
const addRow = () => form.rows.push({ amount: null, rate: 21, vat: 0 });
const removeRow = (idx) => form.rows.splice(idx, 1);

const totals = computed(() => {
  let base = 0, vat = 0;
  for (const row of form.rows) {
    base += Number(row.amount) || 0;
    vat += Number(row.vat) || 0;
  }
  return { base: round2(base), vat: round2(vat), total: round2(base + vat) };
});

const startAdd = () => {
  editingId.value = null;
  form.reset();
  form.clearErrors();
  showForm.value = true;
};

const startEdit = (p) => {
  editingId.value = p.id;
  form.clearErrors();
  form.supplier_name = p.supplier_name;
  form.category = p.category || '';
  form.frequency = p.frequency;
  form.next_run_on = p.next_run_on;
  form.end_date = p.end_date || '';
  form.rows = (p.vat_lines || []).map(l => ({ amount: Number(l.base), rate: Number(l.rate), vat: Number(l.vat) }));
  form.auto_paid = p.auto_paid;
  form.payment_method = p.payment_method || 'direct_debit';
  form.notes = p.notes || '';
  showForm.value = true;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submit = () => {
  form
    .transform((data) => ({
      supplier_name: data.supplier_name,
      category: data.category || null,
      frequency: data.frequency,
      next_run_on: data.next_run_on,
      end_date: data.end_date || null,
      vat_lines: data.rows.map(r => ({ base: Number(r.amount) || 0, rate: Number(r.rate) || 0, vat: Number(r.vat) || 0 })),
      auto_paid: data.auto_paid,
      payment_method: data.payment_method,
      notes: data.notes || null,
    }))
    .submit(
      editingId.value ? 'patch' : 'post',
      editingId.value ? route('purchases.recurring.update', editingId.value) : route('purchases.recurring.store'),
      {
        preserveScroll: true,
        onSuccess: () => { showForm.value = false; editingId.value = null; form.reset(); },
      }
    );
};

const toggleActive = (p) => {
  router.patch(route('purchases.recurring.update', p.id), { active: !p.active }, { preserveScroll: true });
};

const removeProfile = (p) => {
  if (confirm(`Vaste last "${p.supplier_name}" verwijderen?\n\nAl ingeboekte inkoopfacturen blijven gewoon bewaard.`)) {
    router.delete(route('purchases.recurring.destroy', p.id), { preserveScroll: true });
  }
};

const lineError = computed(() => {
  const key = Object.keys(form.errors).find(k => k === 'vat_lines' || k.startsWith('vat_lines.'));
  return key ? form.errors[key] : null;
});
</script>

<template>
  <Head title="Vaste lasten" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Inkoop / <span class="breadcrumb-current">Vaste lasten</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Vaste lasten</h1>
        <p class="page-subtitle">
          Huur, software, verzekeringen — terugkerende kosten worden automatisch als inkoopfactuur ingeboekt,
          zodat de BTW vanzelf meetelt als voorbelasting.
        </p>
      </div>
      <button v-if="!showForm" type="button" class="btn btn-primary" @click="startAdd">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nieuwe vaste last
      </button>
    </div>

    <!-- Formulier -->
    <div v-if="showForm" class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="rp-title">{{ editingId ? 'Vaste last bewerken' : 'Nieuwe vaste last' }}</div>
        <form @submit.prevent="submit">
          <div class="form-row">
            <div class="form-group">
              <label>Leverancier *</label>
              <input type="text" v-model="form.supplier_name" list="rp-suppliers" maxlength="180" placeholder="Bijv. KPN Zakelijk">
              <datalist id="rp-suppliers"><option v-for="s in suppliers" :key="s" :value="s" /></datalist>
              <div v-if="form.errors.supplier_name" class="field-error">{{ form.errors.supplier_name }}</div>
            </div>
            <div class="form-group">
              <label>Categorie</label>
              <select v-model="form.category">
                <option value="">— Kies een categorie —</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label>Frequentie *</label>
              <select v-model="form.frequency">
                <option v-for="(label, key) in FREQUENCIES" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Volgende inboeking *</label>
              <input type="date" v-model="form.next_run_on">
              <div v-if="form.errors.next_run_on" class="field-error">{{ form.errors.next_run_on }}</div>
            </div>
            <div class="form-group">
              <label>Stopt op<span class="label-hint">(optioneel)</span></label>
              <input type="date" v-model="form.end_date">
              <div v-if="form.errors.end_date" class="field-error">{{ form.errors.end_date }}</div>
            </div>
          </div>

          <label style="display:block;font-size:12.5px;font-weight:600;margin-bottom:8px;">Bedrag per periode (excl. BTW)</label>
          <div class="rp-rows">
            <div v-for="(row, idx) in form.rows" :key="idx" class="rp-row">
              <input type="number" step="0.01" v-model="row.amount" placeholder="0,00" @input="recalcVat(row)">
              <select v-model="row.rate" @change="recalcVat(row)">
                <option :value="21">21%</option>
                <option :value="9">9%</option>
                <option :value="0">0% / vrijgesteld</option>
              </select>
              <input type="number" step="0.01" v-model="row.vat" title="BTW-bedrag — pas aan als de factuur anders afrondt">
              <button type="button" class="icon-btn" :disabled="form.rows.length === 1" @click="removeRow(idx)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" style="margin-top:8px;" @click="addRow">+ Tarief toevoegen</button>
          <div v-if="lineError" class="field-error" style="margin-top:6px;">{{ lineError }}</div>
          <div class="rp-total">Per periode: <b>{{ eur(totals.total) }}</b> incl. BTW ({{ eur(totals.vat) }} voorbelasting)</div>

          <div class="form-row" style="margin-top:14px;">
            <div class="form-group">
              <label class="checkbox-row" style="margin:0 0 8px;">
                <input type="checkbox" v-model="form.auto_paid">
                <span>Direct op betaald zetten (bijv. automatische incasso)</span>
              </label>
              <select v-if="form.auto_paid" v-model="form.payment_method" style="max-width:260px;">
                <option value="direct_debit">Automatische incasso</option>
                <option value="bank_transfer">Bankoverschrijving</option>
                <option value="card">Pinpas / creditcard</option>
                <option value="ideal">iDEAL</option>
                <option value="other">Anders</option>
              </select>
            </div>
            <div class="form-group">
              <label>Notities<span class="label-hint">(komt op elke inboeking)</span></label>
              <input type="text" v-model="form.notes" maxlength="2000" placeholder="Bijv. contractnummer">
            </div>
          </div>

          <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" class="btn btn-secondary" @click="showForm = false">Annuleren</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Bezig…' : (editingId ? 'Wijzigingen opslaan' : 'Vaste last aanmaken') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Lijst -->
    <div class="card" v-if="profiles.length > 0">
      <table class="data-table">
        <thead>
          <tr>
            <th>Leverancier</th>
            <th>Categorie</th>
            <th>Frequentie</th>
            <th class="right">Per periode</th>
            <th>Volgende inboeking</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in profiles" :key="p.id">
            <td class="cell-primary">
              {{ p.supplier_name }}
              <div v-if="p.last_run_label" class="rp-sub">Laatst: {{ p.last_run_label }} · {{ p.purchases_generated }}× ingeboekt</div>
            </td>
            <td data-label="Categorie">{{ p.category || '—' }}</td>
            <td data-label="Frequentie">{{ p.frequency_label }}</td>
            <td class="num right" data-label="Per periode">{{ eur(p.total) }}</td>
            <td data-label="Volgende">
              {{ p.next_run_label }}
              <div v-if="p.end_date_label" class="rp-sub">stopt {{ p.end_date_label }}</div>
            </td>
            <td data-label="Status">
              <span v-if="p.active" class="pill pill-paid">Actief</span>
              <span v-else class="pill pill-muted">Gepauzeerd</span>
              <span v-if="p.auto_paid" class="pill pill-sent" style="margin-left:4px;" title="Wordt direct als betaald ingeboekt">Incasso</span>
            </td>
            <td class="rp-actions">
              <button type="button" class="icon-btn" :title="p.active ? 'Pauzeren' : 'Hervatten'" @click="toggleActive(p)">
                <svg v-if="p.active" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              </button>
              <button type="button" class="icon-btn" title="Bewerken" @click="startEdit(p)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
              </button>
              <button type="button" class="icon-btn" title="Verwijderen" @click="removeProfile(p)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else-if="!showForm" class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">Nog geen vaste lasten</div>
      <div style="margin-bottom:20px;">
        Zet je terugkerende kosten hier eenmalig klaar — Easy boekt ze voortaan automatisch in,
        inclusief de BTW als voorbelasting. Tip: op een bestaande inkoopfactuur staat ook een knop "Maak terugkerend".
      </div>
      <button type="button" class="btn btn-primary btn-sm" style="display:inline-flex;" @click="startAdd">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Eerste vaste last aanmaken
      </button>
    </div>
  </AppLayout>
</template>

<style scoped>
.rp-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 14px; }
.label-hint { color: var(--text-4); font-weight: 400; font-size: 11.5px; margin-left: 6px; }
.form-row-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }

.rp-rows { display: flex; flex-direction: column; gap: 8px; }
.rp-row { display: grid; grid-template-columns: minmax(0, 1fr) 160px 130px 32px; gap: 8px; align-items: center; max-width: 620px; }
.rp-total { margin-top: 10px; font-size: 13px; color: var(--text-2); }

.rp-sub { font-size: 11.5px; color: var(--text-3); font-weight: 400; margin-top: 2px; }
.rp-actions { white-space: nowrap; text-align: right; }
.pill-muted { background: var(--surface-2); color: var(--text-3); }
.icon-btn { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.icon-btn:hover:not(:disabled) { background: var(--surface-2); color: var(--brand-dark); }
.icon-btn:disabled { opacity: 0.35; }

@media (max-width: 760px) {
  .form-row-3 { grid-template-columns: minmax(0, 1fr); }
  .rp-row { grid-template-columns: minmax(0, 1fr) 90px 90px 28px; }
}
</style>
