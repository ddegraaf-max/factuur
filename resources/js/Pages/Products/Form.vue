<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { t } from '@/i18n';

const props = defineProps({
  product: Object,
  vat_rates: Array,
});

const page = usePage();
const market = computed(() => page.props.market || {});

const isEdit = computed(() => !!props.product);

// Btw-tarieven van de markt (nl: 21/9/0, pl: 23/8/5/0). Ontbreekt de markt,
// dan de vaste lijst van de server.
const vatOptions = computed(() => {
  const rates = market.value.vat_rates;
  if (!Array.isArray(rates) || !rates.length) return props.vat_rates || [];
  const highest = Math.max(...rates.map(Number));
  return rates.map((r) => {
    const rate = Number(r);
    let label;
    if (rate === 0) label = t('0% / vrijgesteld');
    else if (rate === highest) label = t(':rate% (hoog tarief)', { rate });
    else label = t(':rate% (laag tarief)', { rate });
    return { value: rate, label };
  });
});

const form = useForm({
  name: props.product?.name ?? '',
  description: props.product?.description ?? '',
  sku: props.product?.sku ?? '',
  unit: props.product?.unit ?? 'stuk',
  price: props.product?.price ?? 0,
  vat_rate: props.product?.vat_rate ?? (page.props.market?.default_vat ?? 21),
  is_active: props.product?.is_active ?? true,
});

const submit = () => {
  if (isEdit.value) form.put(route('products.update', props.product.id));
  else form.post(route('products.store'));
};

const remove = () => {
  if (confirm(t('Product ":name" verwijderen?', { name: props.product.name }))) {
    router.delete(route('products.destroy', props.product.id));
  }
};
</script>

<template>
  <Head :title="isEdit ? $t('Product :name', { name: product.name }) : $t('Nieuw product')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        {{ $t('Verkoop') }} / <Link :href="route('products.index')" style="color:var(--text-3);">{{ $t('Producten') }}</Link> /
        <span class="breadcrumb-current">{{ isEdit ? product.name : $t('Nieuw') }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('products.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          {{ $t('Terug') }}
        </Link>
        <h1 class="page-title">{{ isEdit ? $t('Product bewerken') : $t('Nieuw product') }}</h1>
      </div>
      <div class="page-actions">
        <button v-if="isEdit" class="btn btn-danger btn-sm" @click="remove">{{ $t('Verwijderen') }}</button>
        <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">{{ isEdit ? $t('Opslaan') : $t('Aanmaken') }}</button>
      </div>
    </div>

    <div class="single-col">
      <div class="card">
        <div class="card-header"><div class="card-title">{{ $t('Product / dienst') }}</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>{{ $t('Naam') }} *</label>
            <input type="text" v-model="form.name" required maxlength="255">
            <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Omschrijving') }}<span class="label-hint">{{ $t('(verschijnt onder de regel op de factuur)') }}</span></label>
            <textarea v-model="form.description" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('SKU / Artikelcode') }}</label>
              <input type="text" v-model="form.sku" maxlength="50" class="mono">
            </div>
            <div class="form-group">
              <label>{{ $t('Eenheid') }} *</label>
              <select v-model="form.unit">
                <option value="stuk">{{ $t('stuk') }}</option>
                <option value="uur">{{ $t('uur') }}</option>
                <option value="dag">{{ $t('dag') }}</option>
                <option value="maand">{{ $t('maand') }}</option>
                <option value="km">km</option>
                <option value="kg">kg</option>
                <option value="m">m</option>
                <option value="m2">m²</option>
                <option value="m3">m³</option>
                <option value="set">{{ $t('set') }}</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Prijs (excl. BTW)') }} *</label>
              <input type="number" v-model.number="form.price" min="0" step="0.01" required class="mono">
              <div v-if="form.errors.price" class="field-error">{{ form.errors.price }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('BTW-tarief') }} *</label>
              <select v-model.number="form.vat_rate">
                <option v-for="r in vatOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
              </select>
            </div>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.is_active">
              {{ $t('Actief') }}
              <span class="label-hint">{{ $t('(inactieve producten verschijnen niet bij factuur maken)') }}</span>
            </label>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.checkbox-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: var(--text);
  cursor: pointer;
}
.checkbox-label input[type="checkbox"] {
  width: 16px; height: 16px;
  accent-color: var(--brand);
  cursor: pointer;
}
</style>
