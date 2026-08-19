<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  customer: Object,
  kvk_enabled: { type: Boolean, default: false },
});

const isEdit = computed(() => !!props.customer);

/* ---------- KvK-register zoeken ---------- */
const kvkQuery = ref('');
const kvkResults = ref([]);
const kvkSearching = ref(false);
const kvkFilling = ref(null);
const kvkError = ref(null);
const kvkSearched = ref(false);

const typeLabels = { hoofdvestiging: 'Hoofdvestiging', nevenvestiging: 'Nevenvestiging', rechtspersoon: 'Rechtspersoon' };

const kvkSearch = async () => {
  const q = kvkQuery.value.trim();
  if (q.length < 2 || kvkSearching.value) return;
  kvkSearching.value = true;
  kvkError.value = null;
  try {
    const { data } = await axios.get(route('kvk.search'), { params: { q } });
    kvkResults.value = data.results || [];
    kvkError.value = data.error || null;
    kvkSearched.value = true;
  } catch {
    kvkError.value = 'Zoeken mislukt — probeer het zo opnieuw.';
  } finally {
    kvkSearching.value = false;
  }
};

const kvkPick = async (result) => {
  kvkFilling.value = result.kvk_number;
  try {
    // Basisprofiel voor het volledige adres; lukt dat niet, dan gebruiken we
    // wat het zoekresultaat al weet (naam, straat, plaats).
    const { data } = await axios.get(route('kvk.profile', result.kvk_number));
    const p = data.result;

    form.name = (p?.name || result.name) ?? form.name;
    form.kvk_number = (p?.kvk_number || result.kvk_number) ?? form.kvk_number;
    form.address_line = p?.address_line ?? result.street ?? form.address_line;
    form.postal_code = p?.postal_code ?? form.postal_code;
    form.city = p?.city ?? result.city ?? form.city;
    form.country = 'NL';
    form.type = 'business';

    kvkResults.value = [];
    kvkQuery.value = '';
    kvkSearched.value = false;
  } catch {
    kvkError.value = 'Gegevens ophalen mislukt — vul het formulier handmatig in.';
  } finally {
    kvkFilling.value = null;
  }
};

const form = useForm({
  name: props.customer?.name ?? '',
  type: props.customer?.type ?? 'business',
  contact_name: props.customer?.contact_name ?? '',
  email: props.customer?.email ?? '',
  phone: props.customer?.phone ?? '',
  kvk_number: props.customer?.kvk_number ?? '',
  vat_number: props.customer?.vat_number ?? '',
  peppol_id: props.customer?.peppol_id ?? '',
  address_line: props.customer?.address_line ?? '',
  postal_code: props.customer?.postal_code ?? '',
  city: props.customer?.city ?? '',
  country: props.customer?.country ?? 'NL',
  payment_terms: props.customer?.payment_terms ?? null,
  hourly_rate: props.customer?.hourly_rate ?? null,
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
      <!-- KvK-register zoeken -->
      <div v-if="kvk_enabled" class="card kvk-card">
        <div class="card-body">
          <div class="kvk-head">
            <span class="kvk-logo">KvK</span>
            <div>
              <div class="kvk-title">Zoek in het Handelsregister</div>
              <div class="kvk-sub">Typ een bedrijfsnaam of KvK-nummer — kies het bedrijf en de gegevens worden ingevuld.</div>
            </div>
          </div>
          <div class="kvk-search">
            <input
              type="text"
              v-model="kvkQuery"
              placeholder="Bijv. 'Bakkerij Janssen' of 68750110"
              maxlength="100"
              @keydown.enter.prevent="kvkSearch"
            >
            <button type="button" class="btn btn-primary" :disabled="kvkSearching || kvkQuery.trim().length < 2" @click="kvkSearch">
              {{ kvkSearching ? 'Zoeken…' : 'Zoeken' }}
            </button>
          </div>
          <div v-if="kvkError" class="field-error" style="margin-top:8px;">{{ kvkError }}</div>

          <div v-if="kvkResults.length" class="kvk-results">
            <button
              v-for="r in kvkResults"
              :key="r.kvk_number + (r.name || '')"
              type="button"
              class="kvk-result"
              :disabled="kvkFilling !== null"
              @click="kvkPick(r)"
            >
              <div class="kvk-result-main">
                <div class="kvk-result-name">{{ r.name }}</div>
                <div class="kvk-result-meta">
                  KvK {{ r.kvk_number }}<template v-if="r.city"> · {{ r.street ? r.street + ', ' : '' }}{{ r.city }}</template>
                </div>
              </div>
              <span class="kvk-type">{{ kvkFilling === r.kvk_number ? 'Ophalen…' : (typeLabels[r.type] || r.type) }}</span>
            </button>
          </div>
          <div v-else-if="kvkSearched && !kvkSearching && !kvkError" class="kvk-empty">
            Geen bedrijven gevonden — controleer de spelling of vul het formulier handmatig in.
          </div>
        </div>
      </div>

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
          <div class="form-row">
            <div class="form-group">
              <label>Peppol-ID<span class="muted" style="margin-left:6px;">(optioneel — standaard afgeleid van het KvK-nummer)</span></label>
              <input type="text" v-model="form.peppol_id" maxlength="50" placeholder="0106:12345678">
              <div v-if="form.errors.peppol_id" class="field-error">{{ form.errors.peppol_id }}</div>
            </div>
            <div class="form-group" v-if="isEdit" style="display:flex;align-items:flex-end;">
              <button type="button" class="btn btn-secondary" style="width:100%;" @click="router.post(route('customers.peppol.check', customer.id), {}, { preserveScroll: true })">
                ⚡ Check Peppol-bereikbaarheid
              </button>
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
          <div class="form-group">
            <label>Uurtarief<span class="label-hint">(voor de urenregistratie — laat leeg voor standaard van bedrijf)</span></label>
            <input type="number" v-model="form.hourly_rate" min="0" step="0.01" placeholder="Standaard">
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

/* KvK-zoeker */
.kvk-card { margin-bottom: 16px; border-color: var(--info-border); }
.kvk-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
.kvk-logo {
  width: 40px; height: 40px; border-radius: 9px; flex: none;
  background: #21145F; color: #fff;
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-display); font-weight: 700; font-size: 13px;
}
.kvk-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; }
.kvk-sub { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }
.kvk-search { display: flex; gap: 8px; }
.kvk-search input { flex: 1; }
.kvk-results { margin-top: 10px; display: flex; flex-direction: column; gap: 6px; }
.kvk-result {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  width: 100%; text-align: left;
  border: 1px solid var(--border); border-radius: 9px;
  padding: 10px 14px; background: var(--surface);
  transition: border-color 0.15s, background 0.15s;
}
.kvk-result:hover:not(:disabled) { border-color: var(--brand); background: var(--brand-tint); }
.kvk-result:disabled { opacity: 0.6; cursor: wait; }
.kvk-result-name { font-weight: 600; font-size: 13.5px; }
.kvk-result-meta { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.kvk-type {
  flex: none; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
  background: var(--surface-2); color: var(--text-3);
  border: 1px solid var(--border-strong); border-radius: 100px; padding: 3px 9px;
}
.kvk-empty { margin-top: 10px; font-size: 12.5px; color: var(--text-3); }
</style>
