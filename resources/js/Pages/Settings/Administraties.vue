<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

defineProps({
  administrations: Array, // { id, name, kvk_number, role, role_label, is_active, subscription }
});

const showForm = ref(false);

const form = useForm({
  name: '',
  kvk_number: '',
  vat_number: '',
});

const submit = () => {
  form.post(route('administrations.store'), {
    onSuccess: () => { showForm.value = false; form.reset(); },
  });
};

const switchTo = (a) => {
  if (!a.is_active) {
    router.post(route('administrations.switch', a.id));
  }
};

const subscriptionLabel = (s) => {
  if (!s) return '';
  if (s.status === 'active') return 'Abonnement actief';
  if (s.status === 'trialing') return `Proefperiode — nog ${s.days_left} ${s.days_left === 1 ? 'dag' : 'dagen'}`;
  return 'Proefperiode verlopen';
};
</script>

<template>
  <Head title="Administraties" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Account / <span class="breadcrumb-current">Administraties</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Administraties</h1>
        <p class="page-subtitle">
          Beheer meerdere bedrijven onder één inlog — elk met een eigen KvK, klanten, facturen,
          nummering en abonnement. Wisselen kan altijd via het menu linksonder.
        </p>
      </div>
      <button v-if="!showForm" type="button" class="btn btn-primary" @click="showForm = true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nieuwe administratie
      </button>
    </div>

    <!-- Nieuwe administratie -->
    <div v-if="showForm" class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="adm-form-title">Nieuwe administratie starten</div>
        <p class="adm-hint">
          De nieuwe administratie begint met een eigen gratis proefperiode van 14 dagen en een eigen
          abonnement. Jij wordt automatisch beheerder; collega's nodig je daarna uit via Instellingen → Team.
        </p>
        <form @submit.prevent="submit">
          <div class="form-row">
            <div class="form-group">
              <label>Bedrijfsnaam *</label>
              <input type="text" v-model="form.name" maxlength="255" placeholder="Bijv. Vries Fotografie">
              <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
            </div>
            <div class="form-group">
              <label>KvK-nummer *</label>
              <input type="text" v-model="form.kvk_number" maxlength="8" inputmode="numeric" placeholder="8 cijfers">
              <div v-if="form.errors.kvk_number" class="field-error">{{ form.errors.kvk_number }}</div>
            </div>
          </div>
          <div class="form-group">
            <label>BTW-nummer<span class="label-hint">(optioneel)</span></label>
            <input type="text" v-model="form.vat_number" maxlength="20" placeholder="NL123456789B01" style="max-width:260px;">
            <div v-if="form.errors.vat_number" class="field-error">{{ form.errors.vat_number }}</div>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" class="btn btn-secondary" @click="showForm = false">Annuleren</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Bezig…' : 'Administratie aanmaken' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Lijst -->
    <div class="adm-grid">
      <div v-for="a in administrations" :key="a.id" class="card adm-card" :class="{ active: a.is_active }">
        <div class="card-body">
          <div class="adm-head">
            <div class="adm-name">{{ a.name }}</div>
            <span v-if="a.is_active" class="pill pill-paid">Actief</span>
          </div>
          <div class="adm-meta">
            <span v-if="a.kvk_number">KvK {{ a.kvk_number }} · </span>{{ a.role_label }}
          </div>
          <div class="adm-meta" style="margin-top:2px;">{{ subscriptionLabel(a.subscription) }}</div>
          <button v-if="!a.is_active" type="button" class="btn btn-secondary btn-sm" style="margin-top:12px;" @click="switchTo(a)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            Wisselen naar deze administratie
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.adm-form-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 6px; }
.adm-hint { font-size: 12.5px; color: var(--text-3); line-height: 1.6; margin-bottom: 14px; }
.label-hint { color: var(--text-4); font-weight: 400; font-size: 11.5px; margin-left: 6px; }

.adm-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.adm-card.active { border-color: var(--brand-border); background: var(--brand-tint); }
.adm-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.adm-name { font-weight: 700; font-size: 15.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.adm-meta { font-size: 12.5px; color: var(--text-3); margin-top: 4px; }

@media (max-width: 760px) {
  .adm-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
