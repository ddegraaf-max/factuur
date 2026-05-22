<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  company: Object,
});

const form = useForm({
  name: props.company.name ?? '',
  trading_name: props.company.trading_name ?? '',
  kvk_number: props.company.kvk_number ?? '',
  vat_number: props.company.vat_number ?? '',
  iban: props.company.iban ?? '',
  email: props.company.email ?? '',
  phone: props.company.phone ?? '',
  website: props.company.website ?? '',
  address_line: props.company.address_line ?? '',
  postal_code: props.company.postal_code ?? '',
  city: props.company.city ?? '',
  country: props.company.country ?? 'NL',
  brand_color: props.company.brand_color ?? '#E8231F',
  default_payment_terms: props.company.default_payment_terms ?? 30,
  invoice_footer: props.company.invoice_footer ?? '',
  invoice_number_format: props.company.invoice_number_format ?? '{year}-{sequence:4}',
});

const submit = () => form.patch(route('settings.company.update'));
</script>

<template>
  <Head title="Bedrijfsgegevens" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Instellingen / <span class="breadcrumb-current">Bedrijfsgegevens</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Bedrijfsgegevens</h1>
        <p class="page-subtitle">Deze gegevens verschijnen op al je facturen</p>
      </div>
      <div class="page-actions">
        <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">Opslaan</button>
      </div>
    </div>

    <div class="single-col">
      <div class="card">
        <div class="card-header"><div class="card-title">Bedrijf</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>Bedrijfsnaam *</label>
              <input type="text" v-model="form.name" required maxlength="255">
              <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
            </div>
            <div class="form-group">
              <label>Handelsnaam<span class="label-hint">(optioneel)</span></label>
              <input type="text" v-model="form.trading_name" maxlength="255">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>KVK-nummer</label>
              <input type="text" v-model="form.kvk_number" maxlength="20">
            </div>
            <div class="form-group">
              <label>BTW-nummer</label>
              <input type="text" v-model="form.vat_number" maxlength="20">
            </div>
          </div>
          <div class="form-group">
            <label>IBAN<span class="label-hint">(verschijnt op facturen)</span></label>
            <input type="text" v-model="form.iban" maxlength="34" class="mono">
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Contact</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>E-mail</label>
              <input type="email" v-model="form.email" maxlength="255">
            </div>
            <div class="form-group">
              <label>Telefoon</label>
              <input type="tel" v-model="form.phone" maxlength="50">
            </div>
          </div>
          <div class="form-group" style="margin:0;">
            <label>Website</label>
            <input type="url" v-model="form.website" maxlength="255" placeholder="https://...">
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Adres</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Adres</label>
            <input type="text" v-model="form.address_line" maxlength="255">
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label>Postcode</label>
              <input type="text" v-model="form.postal_code" maxlength="20">
            </div>
            <div class="form-group">
              <label>Plaats</label>
              <input type="text" v-model="form.city" maxlength="100">
            </div>
            <div class="form-group">
              <label>Land *</label>
              <select v-model="form.country">
                <option value="NL">Nederland</option>
                <option value="BE">België</option>
                <option value="DE">Duitsland</option>
                <option value="PL">Polen</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Factuurinstellingen</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>Standaard betalingstermijn (dagen) *</label>
              <input type="number" v-model.number="form.default_payment_terms" min="0" max="365" required>
            </div>
            <div class="form-group">
              <label>Factuurnummer-formaat *</label>
              <input type="text" v-model="form.invoice_number_format" required maxlength="50" class="mono">
              <div style="font-size:11px;color:var(--text-4);margin-top:4px;">
                Gebruik <code style="font-family:var(--font-mono);">{year}</code> en <code style="font-family:var(--font-mono);">{sequence:4}</code>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Huisstijlkleur</label>
            <div style="display:flex;gap:10px;align-items:center;">
              <input type="color" v-model="form.brand_color" style="width:48px;height:42px;padding:2px;cursor:pointer;">
              <input type="text" v-model="form.brand_color" maxlength="7" class="mono" style="width:120px;">
            </div>
          </div>
          <div class="form-group" style="margin:0;">
            <label>Standaard voetnoot<span class="label-hint">(onderaan elke factuur)</span></label>
            <textarea v-model="form.invoice_footer" rows="3" placeholder="Bijv. betalingsvoorwaarden, BTW-mededelingen..."></textarea>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
