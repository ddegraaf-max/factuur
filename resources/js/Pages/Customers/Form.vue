<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
  customer: Object,
});

const isEdit = computed(() => !!props.customer);

const form = useForm({
  name: props.customer?.name ?? '',
  type: props.customer?.type ?? 'business',
  contact_name: props.customer?.contact_name ?? '',
  email: props.customer?.email ?? '',
  phone: props.customer?.phone ?? '',
  kvk_number: props.customer?.kvk_number ?? '',
  vat_number: props.customer?.vat_number ?? '',
  address_line: props.customer?.address_line ?? '',
  postal_code: props.customer?.postal_code ?? '',
  city: props.customer?.city ?? '',
  country: props.customer?.country ?? 'NL',
  payment_terms: props.customer?.payment_terms ?? null,
  notes: props.customer?.notes ?? '',
});

const submit = () => {
  if (isEdit.value) {
    form.put(route('customers.update', props.customer.id));
  } else {
    form.post(route('customers.store'));
  }
};

const remove = () => {
  if (confirm(`Klant "${props.customer.name}" verwijderen?`)) {
    router.delete(route('customers.destroy', props.customer.id));
  }
};
</script>

<template>
  <Head :title="isEdit ? `Klant ${customer.name}` : 'Nieuwe klant'" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        Verkoop / <Link :href="route('customers.index')" style="color:var(--text-3);">Klanten</Link> /
        <span class="breadcrumb-current">{{ isEdit ? customer.name : 'Nieuw' }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('customers.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          Terug
        </Link>
        <h1 class="page-title">{{ isEdit ? 'Klant bewerken' : 'Nieuwe klant' }}</h1>
      </div>
      <div class="page-actions">
        <button v-if="isEdit" class="btn btn-danger btn-sm" @click="remove">Verwijderen</button>
        <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">
          {{ isEdit ? 'Opslaan' : 'Aanmaken' }}
        </button>
      </div>
    </div>

    <div class="single-col">
      <div class="card">
        <div class="card-header"><div class="card-title">Algemeen</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Type *</label>
            <div class="type-toggle">
              <button type="button" :class="['type-opt', { active: form.type === 'business' }]" @click="form.type = 'business'">
                Zakelijk
              </button>
              <button type="button" :class="['type-opt', { active: form.type === 'consumer' }]" @click="form.type = 'consumer'">
                Particulier
              </button>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ form.type === 'business' ? 'Bedrijfsnaam' : 'Naam' }} *</label>
              <input type="text" v-model="form.name" required maxlength="255">
              <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
            </div>
            <div class="form-group" v-if="form.type === 'business'">
              <label>Contactpersoon</label>
              <input type="text" v-model="form.contact_name" maxlength="255">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>E-mail</label>
              <input type="email" v-model="form.email" maxlength="255">
              <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
            </div>
            <div class="form-group">
              <label>Telefoon</label>
              <input type="tel" v-model="form.phone" maxlength="50">
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;" v-if="form.type === 'business'">
        <div class="card-header"><div class="card-title">Zakelijk</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>KVK-nummer</label>
              <input type="text" v-model="form.kvk_number" maxlength="20" placeholder="12345678">
            </div>
            <div class="form-group">
              <label>BTW-nummer</label>
              <input type="text" v-model="form.vat_number" maxlength="20" placeholder="NL123456789B01">
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Adres</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Adres</label>
            <input type="text" v-model="form.address_line" maxlength="255" placeholder="Hoofdstraat 1">
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label>Postcode</label>
              <input type="text" v-model="form.postal_code" maxlength="20" placeholder="1234 AB">
            </div>
            <div class="form-group">
              <label>Plaats</label>
              <input type="text" v-model="form.city" maxlength="100">
            </div>
            <div class="form-group">
              <label>Land</label>
              <select v-model="form.country">
                <option value="NL">Nederland</option>
                <option value="BE">België</option>
                <option value="DE">Duitsland</option>
                <option value="FR">Frankrijk</option>
                <option value="LU">Luxemburg</option>
                <option value="PL">Polen</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Voorkeuren</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Betalingstermijn<span class="label-hint">(laat leeg voor standaard van bedrijf)</span></label>
            <input type="number" v-model="form.payment_terms" min="0" max="365" placeholder="Standaard">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Notities<span class="label-hint">(intern, niet zichtbaar op factuur)</span></label>
            <textarea v-model="form.notes" rows="3"></textarea>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.single-col { max-width: 760px; }
.type-toggle {
  display: inline-flex;
  background: var(--surface-2);
  border-radius: 8px;
  padding: 3px;
  gap: 2px;
}
.type-opt {
  padding: 7px 16px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-3);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
}
.type-opt:hover:not(.active) { color: var(--text); }
.type-opt.active { background: var(--surface); color: var(--text); box-shadow: var(--shadow-sm); }
</style>
