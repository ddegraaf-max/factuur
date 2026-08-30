<script setup>
import { useForm, Head, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Turnstile from '@/Components/Turnstile.vue';
import LopraAuthHero from '@/Components/LopraAuthHero.vue';

const turnstileSitekey = import.meta.env.VITE_TURNSTILE_SITEKEY || '';
const brand = usePage().props.brand;

defineProps({
  status: String,
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
  'cf-turnstile-response': '',
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head :title="$t('Inloggen')" />
  <AuthLayout>
    <template #hero>
      <!-- Lopra heeft een eigen, korte hero met visual; EasyInvoice houdt de uitgebreide SEO-tekst. -->
      <LopraAuthHero v-if="brand.key === 'lopra' || brand.key === 'lopra_pl'" mode="login" />
      <div v-else class="auth-copy">
        <h1 class="auth-h1">{{ $t('Inloggen bij :brand', { brand: brand.name }) }}</h1>
        <p>{{ $t("Welkom terug. Log in en ga verder waar je gebleven was: je facturen, offertes, klanten en het btw-overzicht staan klaar. :brand is het Nederlandse facturatieprogramma voor zzp'ers en mkb — facturatie zonder gedoe, vanaf € 12,10 per maand (incl. 21% btw) en maandelijks opzegbaar.", { brand: brand.name }) }}</p>
        <div class="login-features">
          <div class="login-feature"><span class="check">✓</span> {{ $t('Btw automatisch per regel (21/9/0%) en de aangifte per kwartaal klaar') }}</div>
          <div class="login-feature"><span class="check">✓</span> {{ $t('Offertes digitaal laten ondertekenen in het klantenportaal') }}</div>
          <div class="login-feature"><span class="check">✓</span> {{ $t('Herinneringen en aanmaningen gaan automatisch de deur uit') }}</div>
          <div class="login-feature"><span class="check">✓</span> {{ $t('iDEAL-betaallink op elke factuur, betalingen direct geboekt') }}</div>
          <div class="login-feature"><span class="check">✓</span> {{ $t('Live overzicht van openstaand, achterstallig en je resultaat') }}</div>
        </div>
        <h2>{{ $t('Problemen met inloggen?') }}</h2>
        <p>{{ $t('Wachtwoord vergeten? Vraag een nieuw wachtwoord aan via de link onder het formulier; je ontvangt binnen een minuut een e-mail. Heb je tweestapsverificatie aanstaan, dan vragen we na je wachtwoord om de code uit je authenticator-app. Kom je er niet uit, stuur dan een bericht via de') }} <a :href="route('contact')" style="color:#fff;">{{ $t('contactpagina') }}</a> {{ $t('— we reageren meestal binnen een werkdag.') }}</p>
        <h2>{{ $t('Nog geen account?') }}</h2>
        <p>{{ $t('Probeer :brand 14 dagen gratis, zonder creditcard. Je maakt in één minuut een account aan en verstuurt direct je eerste factuur — of kijk eerst rond in de demo met voorbeeldgegevens. Ben je klant van een ondernemer die :brand gebruikt en zoek je je factuur of offerte? Die vind je in het klantenportaal; daar log je in met alleen je e-mailadres.', { brand: brand.name }) }}</p>
        <ul class="auth-links">
          <li><a :href="route('register')">{{ $t('Gratis account aanmaken') }}</a></li>
          <li><a :href="route('demo')">{{ $t('Demo bekijken') }}</a></li>
          <li><a :href="route('gratis-factuur')">{{ $t('Gratis factuur maken') }}</a></li>
          <li><a :href="route('helpcentrum')">{{ $t('Helpcentrum') }}</a></li>
          <li><a :href="route('faq')">{{ $t('Veelgestelde vragen') }}</a></li>
          <li><a :href="route('portal.login')">{{ $t('Klantenportaal') }}</a></li>
        </ul>
      </div>
    </template>

    <div class="login-form-card">
      <div class="login-form-title">{{ $t('Inloggen') }}</div>
      <div class="login-form-sub">{{ $t('Log in op je :brand-account', { brand: brand.name }) }}</div>

      <div v-if="status" class="status-message">{{ status }}</div>

      <form @submit.prevent="submit">
        <div class="form-group">
          <label>{{ $t('E-mailadres') }}</label>
          <input v-model="form.email" type="email" autocomplete="email" required autofocus />
          <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
        </div>

        <div class="form-group">
          <label>{{ $t('Wachtwoord') }}</label>
          <input v-model="form.password" type="password" autocomplete="current-password" required />
          <div v-if="form.errors.password" class="field-error">{{ form.errors.password }}</div>
        </div>

        <div class="login-row" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
          <label class="remember-row">
            <input type="checkbox" v-model="form.remember" />
            <span>{{ $t('30 dagen onthouden') }}</span>
          </label>
          <a :href="route('password.request')" style="font-size:13px;">{{ $t('Wachtwoord vergeten?') }}</a>
        </div>

        <Turnstile :sitekey="turnstileSitekey"
                   @verified="t => form['cf-turnstile-response'] = t"
                   @expired="() => form['cf-turnstile-response'] = ''" />
        <div v-if="form.errors['cf-turnstile-response']" class="field-error">{{ form.errors['cf-turnstile-response'] }}</div>

        <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
          {{ form.processing ? $t('Bezig…') : $t('Inloggen') }}
        </button>
      </form>

      <div class="login-bottom">
        {{ $t('Nog geen account?') }} <a :href="route('register')">{{ $t('Registreer hier') }}</a>
      </div>
    </div>
  </AuthLayout>
</template>
