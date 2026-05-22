<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineProps({
  status: String,
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Inloggen" />
  <AuthLayout>
    <template #hero>
      <h2>Welkom terug</h2>
      <p>Log in om verder te gaan met je administratie. Nederlandse facturatie zonder gedoe — voor maar €2,50 per maand.</p>
      <div class="login-features">
        <div class="login-feature"><span class="check">✓</span> BTW automatisch per regel (21/9/0%)</div>
        <div class="login-feature"><span class="check">✓</span> Eigen factuurnummerreeks per jaar</div>
        <div class="login-feature"><span class="check">✓</span> Live overzicht van openstaand en achterstallig</div>
      </div>
    </template>

    <div class="login-form-card">
      <div class="login-form-title">Inloggen</div>
      <div class="login-form-sub">Log in op je EasyInvoice-account</div>

      <div v-if="status" class="status-message">{{ status }}</div>

      <form @submit.prevent="submit">
        <div class="form-group">
          <label>E-mailadres</label>
          <input v-model="form.email" type="email" autocomplete="email" required autofocus />
          <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
        </div>

        <div class="form-group">
          <label>Wachtwoord</label>
          <input v-model="form.password" type="password" autocomplete="current-password" required />
          <div v-if="form.errors.password" class="field-error">{{ form.errors.password }}</div>
        </div>

        <label class="remember-row">
          <input type="checkbox" v-model="form.remember" />
          <span>30 dagen onthouden</span>
        </label>

        <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
          {{ form.processing ? 'Bezig…' : 'Inloggen' }}
        </button>
      </form>

      <div class="login-bottom">
        Nog geen account? <a :href="route('register')">Registreer hier</a>
      </div>
    </div>
  </AuthLayout>
</template>
