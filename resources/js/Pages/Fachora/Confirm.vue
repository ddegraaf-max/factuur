<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import FachoraShell from '@/Components/FachoraShell.vue';
import { icon } from '@/fachora-icons.js';
import { t } from '@/i18n';

/**
 * De bevestigingspagina uit de mail (/zlecenie/{token}/potwierdz): de klant
 * ziet zijn aanvraag nog één keer, krijgt advies (taal, omschrijving, bereik)
 * en kan terug om aan te passen. Pas na de bevestiging gaat de klus verder.
 */
const props = defineProps({
  job: { type: Object, required: true },
  token: { type: String, required: true },
  advice: { type: Object, default: () => ({ radius: 50, towns: [], tips: [] }) },
  customerChoice: { type: Boolean, default: false },
  categories: { type: Array, default: () => [] },
  languages: { type: Array, default: () => [] },
});

const form = useForm({});
const confirm = () => form.post(route('fachora.job.confirm', props.token));

const urgencies = [
  { key: 'now', label: 'Zo snel mogelijk' },
  { key: 'week', label: 'Deze week|termijn' },
  { key: 'month', label: 'Binnen een maand' },
  { key: 'date', label: 'Op een datum' },
];

const editing = ref(false);
const edit = useForm({
  category: props.job.category,
  title: props.job.title || '',
  description: props.job.description || '',
  urgency: props.job.urgency,
  wanted_date: props.job.wanted_date_value || '',
  time_window: props.job.time_window || '',
  language: props.job.language,
  postal_code: props.job.postal_code || '',
  city: props.job.city || '',
});
const save = () => edit
  .transform((d) => ({ ...d, postal_code: d.postal_code.trim(), wanted_date: d.urgency === 'date' ? d.wanted_date : '' }))
  .post(route('fachora.job.confirm.update', props.token), { preserveScroll: true, onSuccess: () => { editing.value = false; } });
const openEdit = (focus) => {
  editing.value = true;
  setTimeout(() => document.getElementById('cf-' + (focus || 'description'))?.focus(), 50);
};
const fieldFor = { language: 'language', description: 'description', postal_code: 'postal', location: 'city' };
const townsLabel = () => props.advice.towns.map((x) => t(':name (:km km)', { name: x.name, km: x.km })).join(', ');
</script>

<template>
  <FachoraShell :title="$t('Bevestig je aanvraag')" narrow noindex>
    <div class="fa-card">
      <span class="fa-eyebrow">{{ $t('Aanvraag') }} <span class="fa-ref">{{ job.reference }}</span></span>
      <h1 class="fa-h1">{{ $t('Bevestig je aanvraag') }}</h1>
      <p class="fa-lead">{{ $t('Bedankt, je e-mailadres klopt. Controleer hieronder wat je hebt ingevuld en bevestig. Pas na die klik bieden we de klus aan bij bedrijven.') }}</p>

      <!-- ===== Advies ===== -->
      <div v-if="advice.tips.length" class="fa-note cf-advice">
        <strong>{{ $t('Ons advies voor je bevestigt') }}</strong>
        <ul class="cf-tips">
          <li v-for="tip in advice.tips" :key="tip.key">
            {{ tip.text }}
            <button type="button" class="cf-link" @click="openEdit(fieldFor[tip.key])">{{ $t('Aanpassen') }}</button>
          </li>
        </ul>
      </div>

      <!-- ===== Overzicht ===== -->
      <template v-if="!editing">
        <dl class="fa-kv cf-kv">
          <dt>{{ $t('Categorie') }}</dt><dd><strong>{{ job.category_name }}</strong></dd>
          <dt>{{ $t('Plaats') }}</dt><dd>{{ [job.postal_code, job.city].filter(Boolean).join(' ') }}</dd>
          <dt>{{ $t('Wanneer') }}</dt><dd>{{ job.urgency_label }}<template v-if="job.time_window"> · {{ job.time_window }}</template></dd>
          <dt>{{ $t('Taal') }}</dt><dd>{{ job.language_label }}</dd>
          <dt v-if="job.title">{{ $t('Titel') }}</dt><dd v-if="job.title">{{ job.title }}</dd>
          <dt>{{ $t('Omschrijving van de klus') }}</dt><dd class="fa-pre">{{ job.description }}</dd>
        </dl>
        <button type="button" class="fa-btn fa-btn--secondary cf-editbtn" @click="openEdit()">
          <span class="ic" v-html="icon('tools', 18)"></span>{{ $t('Terug naar je aanvraag om iets aan te passen') }}
        </button>
      </template>

      <!-- ===== Aanpassen ===== -->
      <form v-else class="cf-edit" @submit.prevent="save">
        <h2 class="fa-h2">{{ $t('Aanvraag aanpassen') }}</h2>
        <div class="fa-field">
          <label for="cf-category">{{ $t('Categorie') }}</label>
          <select id="cf-category" v-model="edit.category">
            <option v-for="c in categories" :key="c.key" :value="c.key">{{ c.name }}</option>
          </select>
          <div v-if="edit.errors.category" class="fa-err">{{ edit.errors.category }}</div>
        </div>
        <div class="cf-row">
          <div class="fa-field cf-postal">
            <label for="cf-postal">{{ $t('Postcode') }}</label>
            <input id="cf-postal" type="text" v-model="edit.postal_code" inputmode="numeric" placeholder="00-000" maxlength="6">
            <div v-if="edit.errors.postal_code" class="fa-err">{{ edit.errors.postal_code }}</div>
          </div>
          <div class="fa-field cf-city">
            <label for="cf-city">{{ $t('Plaats') }} *</label>
            <input id="cf-city" type="text" v-model="edit.city" maxlength="120">
            <div v-if="edit.errors.city" class="fa-err">{{ edit.errors.city }}</div>
          </div>
        </div>
        <div class="fa-field">
          <label for="cf-urgency">{{ $t('Wanneer') }}</label>
          <select id="cf-urgency" v-model="edit.urgency">
            <option v-for="u in urgencies" :key="u.key" :value="u.key">{{ $t(u.label) }}</option>
          </select>
        </div>
        <div v-if="edit.urgency === 'date'" class="fa-field">
          <label for="cf-date">{{ $t('Datum') }}</label>
          <input id="cf-date" type="date" v-model="edit.wanted_date">
          <div v-if="edit.errors.wanted_date" class="fa-err">{{ edit.errors.wanted_date }}</div>
        </div>
        <div class="fa-field">
          <label for="cf-language">{{ $t('Taal') }}</label>
          <select id="cf-language" v-model="edit.language">
            <option v-for="l in languages" :key="l.code" :value="l.code">{{ l.flag }} {{ l.name }}</option>
          </select>
          <p v-if="edit.language !== 'pl'" class="fa-small fa-muted">{{ $t('Tip: met Pools heb je de meeste keus. Je omschrijving vertalen we toch automatisch.') }}</p>
        </div>
        <div class="fa-field">
          <label for="cf-title">{{ $t('Titel') }}</label>
          <input id="cf-title" type="text" v-model="edit.title" maxlength="160">
        </div>
        <div class="fa-field">
          <label for="cf-description">{{ $t('Omschrijving van de klus') }} *</label>
          <textarea id="cf-description" v-model="edit.description" rows="6" maxlength="3000"></textarea>
          <div v-if="edit.errors.description" class="fa-err">{{ edit.errors.description }}</div>
        </div>
        <div class="fa-actions cf-editactions">
          <button type="submit" class="fa-btn" :disabled="edit.processing">{{ $t('Wijzigingen opslaan') }}</button>
          <button type="button" class="fa-btn fa-btn--secondary" @click="editing = false; edit.clearErrors()">{{ $t('Annuleren') }}</button>
        </div>
      </form>

      <div class="fa-note cf-next">
        <strong>{{ $t('Wat er na je bevestiging gebeurt') }}</strong>
        <ul class="fa-ticks" v-if="customerChoice">
          <li>{{ $t('We zoeken bedrijven binnen :km km van :city.', { km: advice.radius, city: job.city }) }}<template v-if="advice.towns.length"> {{ $t('Daar vallen ook :towns onder.', { towns: townsLabel() }) }}</template></li>
          <li>{{ $t('Je kiest zelf maximaal vijf bedrijven. Fachora Verified-bedrijven krijgen je aanvraag van ons; bij andere bedrijven zie je hun openbare gegevens en neem je zelf contact op, met een kant-en-klare Poolse e-mail.') }}</li>
          <li>{{ $t('Je omschrijving vertalen we automatisch naar het Pools. Niemand belt je ongevraagd.') }}</li>
        </ul>
        <ul class="fa-ticks" v-else>
          <li>{{ $t('We bieden de klus aan bij maximaal vijf Fachora Verified-bedrijven in de buurt die jouw taal spreken.') }}</li>
          <li>{{ $t('Zij hebben 48 uur om een prijs en een moment door te geven. Je krijgt een e-mail bij elke reactie.') }}</li>
          <li>{{ $t('Je vergelijkt de prijzen op je eigen pagina en kiest zelf. Niemand belt je ongevraagd.') }}</li>
        </ul>
      </div>

      <form v-if="!editing" @submit.prevent="confirm" class="fa-actions cf-actions">
        <button type="submit" class="fa-btn fa-btn--block" :disabled="form.processing">
          <span class="ic" v-html="icon('check', 20)"></span>{{ $t('Ik bevestig de aanvraag') }}
        </button>
      </form>
      <p class="fa-small fa-muted cf-foot">{{ $t('Bevestig je niet, dan verdwijnt de aanvraag vanzelf na 7 dagen.') }}</p>
    </div>
  </FachoraShell>
</template>

<style scoped>
.cf-kv { margin: 18px 0; }
.cf-advice { margin-top: 18px; background: #FFF6EC; border-color: #F6C48F; }
.cf-advice strong, .cf-next strong { display: block; margin-bottom: 8px; }
.cf-tips { margin: 0; padding-left: 18px; display: grid; gap: 8px; }
.cf-link { background: none; border: 0; padding: 0; margin-left: 4px; color: inherit; font-weight: 700; text-decoration: underline; cursor: pointer; font: inherit; }
.cf-editbtn { margin-bottom: 6px; }
.cf-edit { margin: 18px 0; display: grid; gap: 12px; }
.cf-edit select, .cf-edit input, .cf-edit textarea { width: 100%; }
.cf-row { display: grid; grid-template-columns: 140px 1fr; gap: 12px; }
@media (max-width: 520px) { .cf-row { grid-template-columns: 1fr; } }
.cf-editactions { display: flex; gap: 10px; flex-wrap: wrap; }
.cf-next { margin-top: 18px; }
.cf-actions { margin-top: 20px; }
.cf-foot { margin-top: 14px; }
</style>
