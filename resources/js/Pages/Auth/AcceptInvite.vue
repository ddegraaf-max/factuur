<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const brand = usePage().props.brand;

const props = defineProps({
  valid: Boolean,
  token: String,
  existing: Boolean, // er bestaat al een account op dit e-mailadres
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
  <Head :title="$t('Uitnodiging accepteren')" />
  <AuthLayout>
    <template #hero>
      <h2>{{ $t('Je bent uitgenodigd') }}</h2>
      <p v-if="valid">
        {{ $t(':who nodigt je uit om mee te werken in de :brand-omgeving van :company.', { who: invitedBy || $t('Een beheerder'), brand: brand.name, company }) }}
      </p>
      <p v-else>{{ $t('Deze uitnodigingslink is helaas niet (meer) geldig.') }}</p>
      <ul v-if="valid" class="hero-bullets">
        <li>{{ $t('Geen eigen abonnement nodig') }}</li>
        <li>{{ $t('Je kiest zelf een wachtwoord') }}</li>
        <li>{{ $t('Je rol: :role', { role: roleLabel }) }}</li>
      </ul>
    </template>

    <div class="login-form-card">
      <template v-if="valid && existing">
        <div class="login-form-title">{{ $t('Administratie koppelen') }}</div>
        <div class="login-form-sub">
          {{ $t('Je hebt al een :brand-account op', { brand: brand.name }) }} <strong>{{ email }}</strong>.
          {{ $t('Koppel') }} <strong>{{ company }}</strong> ({{ roleLabel }}) {{ $t('aan die inlog — je wisselt daarna moeiteloos tussen je administraties.') }}
        </div>
        <form @submit.prevent="submit">
          <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
            {{ form.processing ? $t('Bezig…') : $t('Koppel aan mijn account') }}
          </button>
          <div v-if="form.errors.name" class="field-error" style="margin-top:8px;">{{ form.errors.name }}</div>
        </form>
      </template>
      <template v-else-if="valid">
        <div class="login-form-title">{{ $t('Account aanmaken') }}</div>
        <div class="login-form-sub">
          {{ $t('Voor') }} <strong>{{ email }}</strong> · {{ roleLabel }} {{ $t('bij') }} <strong>{{ company }}</strong>
        </div>

        <form @submit.prevent="submit">
          <div class="form-group">
            <label>{{ $t('Je naam') }} *</label>
            <input type="text" v-model="form.name" autocomplete="name" maxlength="120" autofocus>
            <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Wachtwoord') }} *<span class="muted" style="margin-left:6px;">{{ $t('(minimaal 8 tekens)') }}</span></label>
            <input type="password" v-model="form.password" autocomplete="new-password">
            <div v-if="form.errors.password" class="field-error">{{ form.errors.password }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Herhaal wachtwoord') }} *</label>
            <input type="password" v-model="form.password_confirmation" autocomplete="new-password">
          </div>

          <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
            {{ form.processing ? $t('Bezig…') : $t('Account aanmaken en starten') }}
          </button>
        </form>
      </template>

      <template v-else>
        <div class="login-form-title">{{ $t('Uitnodiging niet geldig') }}</div>
        <div class="login-form-sub">
          {{ $t('Deze link is verlopen, ingetrokken of al gebruikt. Vraag de beheerder van de omgeving om een nieuwe uitnodiging te sturen.') }}
        </div>
        <a href="/login" class="btn btn-secondary btn-block">{{ $t('Naar inloggen') }}</a>
      </template>
    </div>
  </AuthLayout>
</template>
