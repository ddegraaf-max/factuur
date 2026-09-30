<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  project: Object,
  customers: Array,
  preset_customer_id: { type: [String, Number], default: null },
});

const isEdit = computed(() => !!props.project);
const form = useForm({
  name: props.project?.name ?? '',
  customer_id: props.project?.customer_id ?? props.preset_customer_id ?? null,
  status: props.project?.status ?? 'open',
  location: props.project?.location ?? '',
  starts_on: props.project?.starts_on ?? '',
  ends_on: props.project?.ends_on ?? '',
  description: props.project?.description ?? '',
  agreed_price: props.project?.agreed_price ?? '',
});

const submit = () => {
  if (isEdit.value) form.patch(route('projects.update', props.project.id));
  else form.post(route('projects.store'));
};
</script>

<template>
  <Head :title="isEdit ? $t('Project bewerken') : $t('Nieuw project')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        {{ $t('Verkoop') }} / <Link :href="route('projects.index')" style="color:var(--text-3);">{{ $t('Projecten') }}</Link> /
        <span class="breadcrumb-current">{{ isEdit ? project.number : $t('Nieuw') }}</span>
      </div>
    </template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">{{ form.processing ? $t('Bezig…') : $t('Opslaan') }}</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isEdit ? $t('Project bewerken') : $t('Nieuw project') }}</h1>
        <p class="page-subtitle">{{ $t('Een project bundelt de offerte, de facturen, de inkoop, de uren en de uitvragen van één klus.') }}</p>
      </div>
    </div>

    <div class="single-col">
      <div class="card">
        <div class="card-body">
          <div class="form-row">
            <div class="form-group grow">
              <label>{{ $t('Naam') }} *</label>
              <input type="text" v-model="form.name" maxlength="160" :placeholder="$t('Bijv. Uitbouw Dorpsstraat 12, Woerden')">
              <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Klant') }}</label>
              <select v-model="form.customer_id">
                <option :value="null">{{ $t('— Geen klant —') }}</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Locatie') }}<span class="label-hint">{{ $t('(optioneel)') }}</span></label>
              <input type="text" v-model="form.location" maxlength="160" :placeholder="$t('Plaats of adres van het werk')">
            </div>
            <div class="form-group">
              <label>{{ $t('Afgesproken prijs (excl. btw)') }}<span class="label-hint">{{ $t('(optioneel)') }}</span></label>
              <input type="number" step="0.01" min="0" v-model="form.agreed_price" :placeholder="$t('Leeg = de geaccepteerde offertes')">
              <div v-if="form.errors.agreed_price" class="field-error">{{ form.errors.agreed_price }}</div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Start') }}</label>
              <input type="date" v-model="form.starts_on">
            </div>
            <div class="form-group">
              <label>{{ $t('Einde') }}</label>
              <input type="date" v-model="form.ends_on">
              <div v-if="form.errors.ends_on" class="field-error">{{ form.errors.ends_on }}</div>
            </div>
            <div v-if="isEdit" class="form-group">
              <label>{{ $t('Status') }}</label>
              <select v-model="form.status">
                <option value="open">{{ $t('Open') }}</option>
                <option value="closed">{{ $t('Gesloten') }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('Omschrijving') }}<span class="label-hint">{{ $t('(optioneel)') }}</span></label>
            <textarea v-model="form.description" rows="4" maxlength="5000" :placeholder="$t('Wat er gemaakt wordt, afspraken, bijzonderheden')"></textarea>
          </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;">
          <Link :href="isEdit ? route('projects.show', project.id) : route('projects.index')" class="btn btn-secondary btn-sm">{{ $t('Annuleren') }}</Link>
          <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">{{ form.processing ? $t('Bezig…') : $t('Opslaan') }}</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.grow { flex: 2; }
.card-footer { padding: 14px 20px; border-top: 1px solid var(--border); }
</style>
