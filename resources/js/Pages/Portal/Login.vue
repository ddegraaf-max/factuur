<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Turnstile from '@/Components/Turnstile.vue';

const turnstileSitekey = import.meta.env.VITE_TURNSTILE_SITEKEY || '';

const form = useForm({
  email: '',
  'cf-turnstile-response': '',
});

const submit = () => {
  form.post(route('portal.code.request'));
};
</script>

<template>
  <Head title="Facturenportaal" />
  <PortalLayout>
    <div class="portal-center">
      <div class="portal-card portal-login-card">
        <div class="portal-lock-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h1 class="portal-card-title">Bekijk je facturen</h1>
        <p class="portal-card-sub">
          Vul het e-mailadres in waarop je facturen ontvangt.
          We sturen je een eenmalige toegangscode — een wachtwoord is niet nodig.
        </p>

        <form @submit.prevent="submit">
          <div class="form-group">
            <label for="portal-email">E-mailadres</label>
            <input
              id="portal-email"
              v-model="form.email"
              type="email"
              inputmode="email"
              autocomplete="email"
              placeholder="naam@bedrijf.nl"
              required
              autofocus
            />
            <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
          </div>

          <Turnstile
            :sitekey="turnstileSitekey"
            @verified="t => form['cf-turnstile-response'] = t"
            @expired="() => form['cf-turnstile-response'] = ''"
          />
          <div v-if="form.errors['cf-turnstile-response']" class="field-error" style="margin-bottom:10px;">
            {{ form.errors['cf-turnstile-response'] }}
          </div>

          <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
            {{ form.processing ? 'Bezig…' : 'Stuur toegangscode' }}
          </button>
        </form>

        <div class="portal-trust">
          <div class="portal-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Toegang alleen met een code die naar jouw e-mailadres wordt gestuurd
          </div>
          <div class="portal-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            De code is 10 minuten geldig en werkt maar één keer
          </div>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<style scoped>
.portal-center {
  display: flex;
  justify-content: center;
  padding-top: 24px;
}
.portal-login-card { max-width: 440px; }
.portal-lock-icon {
  width: 46px; height: 46px;
  border-radius: 12px;
  background: var(--brand-tint);
  color: var(--brand-dark);
  display: inline-flex; align-items: center; justify-content: center;
  margin-bottom: 18px;
}
.portal-lock-icon svg { width: 22px; height: 22px; }
.portal-trust {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.portal-trust-item {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  font-size: 12.5px;
  color: var(--text-3);
  line-height: 1.5;
}
.portal-trust-item svg { width: 15px; height: 15px; flex: none; margin-top: 1px; color: var(--success); }
</style>
