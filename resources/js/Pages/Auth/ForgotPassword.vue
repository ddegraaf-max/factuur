<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineProps({
  status: String,
});

const form = useForm({
  email: '',
});

const submit = () => form.post(route('password.email'));
</script>

<template>
  <Head title="Wachtwoord vergeten" />
  <AuthLayout>
    <template #hero>
      <h2>Wachtwoord vergeten?</h2>
      <p>Geen probleem. Vul je e-mailadres in en we sturen je een link om een nieuw wachtwoord in te stellen.</p>
    </template>

    <div class="login-form-card">
      <div class="login-form-title">Wachtwoord vergeten</div>
      <div class="login-form-sub">We mailen je een herstel-link</div>

      <div v-if="status" class="status-message">{{ status }}</div>

      <form @submit.prevent="submit">
        <div class="form-group">
          <label>E-mailadres</label>
          <input v-model="form.email" type="email" autocomplete="email" required autofocus />
          <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
        </div>

        <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
          {{ form.processing ? 'Bezig…' : 'Stuur herstel-link' }}
        </button>
      </form>

      <div class="login-bottom">
        <a :href="route('login')">Terug naar inloggen</a>
      </div>
    </div>
  </AuthLayout>
</template>
