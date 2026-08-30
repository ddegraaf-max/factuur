<script setup>
import { useForm, Head, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const brand = usePage().props.brand;

const props = defineProps({
  email: String,
  token: String,
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => form.post(route('password.store'), {
  onFinish: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
  <Head :title="$t('Nieuw wachtwoord')" />
  <AuthLayout>
    <template #hero>
      <h2>{{ $t('Nieuw wachtwoord instellen') }}</h2>
      <p>{{ $t('Kies een sterk, nieuw wachtwoord voor je :brand-account (minimaal 8 tekens).', { brand: brand.name }) }}</p>
    </template>

    <div class="login-form-card">
      <div class="login-form-title">{{ $t('Nieuw wachtwoord') }}</div>
      <div class="login-form-sub">{{ $t('Voor :email', { email: form.email }) }}</div>

      <form @submit.prevent="submit">
        <div class="form-group">
          <label>{{ $t('E-mailadres') }}</label>
          <input v-model="form.email" type="email" autocomplete="email" required />
          <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
        </div>

        <div class="form-group">
          <label>{{ $t('Nieuw wachtwoord') }}</label>
          <input v-model="form.password" type="password" autocomplete="new-password" required autofocus />
          <div v-if="form.errors.password" class="field-error">{{ form.errors.password }}</div>
        </div>

        <div class="form-group">
          <label>{{ $t('Herhaal wachtwoord') }}</label>
          <input v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
        </div>

        <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
          {{ form.processing ? $t('Bezig…') : $t('Wachtwoord opslaan') }}
        </button>
      </form>

      <div class="login-bottom">
        <a :href="route('login')">{{ $t('Terug naar inloggen') }}</a>
      </div>
    </div>
  </AuthLayout>
</template>
