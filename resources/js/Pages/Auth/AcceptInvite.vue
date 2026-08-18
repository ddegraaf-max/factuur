<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const props = defineProps({
  valid: Boolean,
  token: String,
  email: String,
  company: String,
  roleLabel: String,
  invitedBy: String,
});

const form = useForm({
  name: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('invitation.accept', props.token));
};
</script>

<template>
  <Head title="Uitnodiging accepteren" />
  <AuthLayout>
    <template #hero>
      <h2>Je bent uitgenodigd</h2>
      <p v-if="valid">
        {{ invitedBy || 'Een beheerder' }} nodigt je uit om mee te werken in de
        EasyInvoice-omgeving van {{ company }}.
      </p>
      <p v-else>Deze uitnodigingslink is helaas niet (meer) geldig.</p>
      <ul v-if="valid" class="hero-bullets">
        <li>Geen eigen abonnement nodig</li>
        <li>Je kiest zelf een wachtwoord</li>
        <li>Je rol: {{ roleLabel }}</li>
      </ul>
    </template>

    <div class="login-form-card">
      <template v-if="valid">
        <div class="login-form-title">Account aanmaken</div>
        <div class="login-form-sub">
          Voor <strong>{{ email }}</strong> · {{ roleLabel }} bij <strong>{{ company }}</strong>
        </div>

        <form @submit.prevent="submit">
          <div class="form-group">
            <label>Je naam *</label>
            <input type="text" v-model="form.name" autocomplete="name" maxlength="120" autofocus>
            <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
          </div>
          <div class="form-group">
            <label>Wachtwoord *<span class="muted" style="margin-left:6px;">(minimaal 8 tekens)</span></label>
            <input type="password" v-model="form.password" autocomplete="new-password">
            <div v-if="form.errors.password" class="field-error">{{ form.errors.password }}</div>
          </div>
          <div class="form-group">
            <label>Herhaal wachtwoord *</label>
            <input type="password" v-model="form.password_confirmation" autocomplete="new-password">
          </div>

          <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
            {{ form.processing ? 'Bezig…' : 'Account aanmaken en starten' }}
          </button>
        </form>
      </template>

      <template v-else>
        <div class="login-form-title">Uitnodiging niet geldig</div>
        <div class="login-form-sub">
          Deze link is verlopen, ingetrokken of al gebruikt.
          Vraag de beheerder van de omgeving om een nieuwe uitnodiging te sturen.
        </div>
        <a href="/login" class="btn btn-secondary btn-block">Naar inloggen</a>
      </template>
    </div>
  </AuthLayout>
</template>
