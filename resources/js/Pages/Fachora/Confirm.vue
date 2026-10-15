<script setup>
import { useForm } from '@inertiajs/vue3';
import FachoraShell from '@/Components/FachoraShell.vue';
import { icon } from '@/fachora-icons.js';

/**
 * De bevestigingspagina uit de mail (/zlecenie/{token}/potwierdz): de klant
 * ziet zijn aanvraag nog één keer en bevestigt met één knop. Pas daarna gaat
 * de klus naar bedrijven.
 */
const props = defineProps({
  job: { type: Object, required: true },
  token: { type: String, required: true },
});

const form = useForm({});
const confirm = () => form.post(route('fachora.job.confirm', props.token));
</script>

<template>
  <FachoraShell :title="$t('Bevestig je aanvraag')" narrow noindex>
    <div class="fa-card">
      <span class="fa-eyebrow">{{ $t('Aanvraag') }} <span class="fa-ref">{{ job.reference }}</span></span>
      <h1 class="fa-h1">{{ $t('Bevestig je aanvraag') }}</h1>
      <p class="fa-lead">{{ $t('Bedankt, je e-mailadres klopt. Controleer hieronder wat je hebt ingevuld en bevestig. Pas na die klik bieden we de klus aan bij bedrijven.') }}</p>

      <dl class="fa-kv cf-kv">
        <dt>{{ $t('Categorie') }}</dt><dd><strong>{{ job.category_name }}</strong></dd>
        <dt>{{ $t('Plaats') }}</dt><dd>{{ [job.postal_code, job.city].filter(Boolean).join(' ') }}</dd>
        <dt>{{ $t('Wanneer') }}</dt><dd>{{ job.urgency_label }}<template v-if="job.time_window"> · {{ job.time_window }}</template></dd>
        <dt>{{ $t('Taal') }}</dt><dd>{{ job.language_label }}</dd>
        <dt v-if="job.title">{{ $t('Titel') }}</dt><dd v-if="job.title">{{ job.title }}</dd>
        <dt>{{ $t('Omschrijving van de klus') }}</dt><dd class="fa-pre">{{ job.description }}</dd>
      </dl>

      <div class="fa-note cf-next">
        <strong>{{ $t('Wat er na je bevestiging gebeurt') }}</strong>
        <ul class="fa-ticks">
          <li>{{ $t('We bieden de klus aan bij maximaal vijf Fachora Verified-bedrijven in de buurt die jouw taal spreken.') }}</li>
          <li>{{ $t('Zij hebben 48 uur om een prijs en een moment door te geven. Je krijgt een e-mail bij elke reactie.') }}</li>
          <li>{{ $t('Je vergelijkt de prijzen op je eigen pagina en kiest zelf. Niemand belt je ongevraagd.') }}</li>
        </ul>
      </div>

      <form @submit.prevent="confirm" class="fa-actions cf-actions">
        <button type="submit" class="fa-btn fa-btn--block" :disabled="form.processing">
          <span class="ic" v-html="icon('check', 20)"></span>{{ $t('Ik bevestig de aanvraag') }}
        </button>
      </form>
      <p class="fa-small fa-muted cf-foot">{{ $t('Klopt er iets niet? Bevestig dan niet; de aanvraag verdwijnt vanzelf na 7 dagen. Je kunt een nieuwe aanvraag doen.') }} <a :href="route('fachora.intake')">{{ $t('Nieuwe aanvraag beginnen') }}</a></p>
    </div>
  </FachoraShell>
</template>

<style scoped>
.cf-kv { margin: 18px 0; }
.cf-next { margin-top: 18px; }
.cf-next strong { display: block; margin-bottom: 8px; }
.cf-actions { margin-top: 20px; }
.cf-foot { margin-top: 14px; }
</style>
