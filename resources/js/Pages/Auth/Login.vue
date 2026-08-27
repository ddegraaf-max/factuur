<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Turnstile from '@/Components/Turnstile.vue';

const turnstileSitekey = import.meta.env.VITE_TURNSTILE_SITEKEY || '';

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
  <Head title="Inloggen" />
  <AuthLayout>
    <template #hero>
      <div class="auth-copy">
        <h1 class="auth-h1">Inloggen bij EasyInvoice</h1>
        <p>Welkom terug. Log in en ga verder waar je gebleven was: je facturen, offertes, klanten en het btw-overzicht staan klaar. EasyInvoice is het Nederlandse facturatieprogramma voor zzp'ers en mkb — facturatie zonder gedoe, vanaf € 12,10 per maand (incl. 21% btw) en maandelijks opzegbaar.</p>
        <div class="login-features">
          <div class="login-feature"><span class="check">✓</span> Btw automatisch per regel (21/9/0%) en de aangifte per kwartaal klaar</div>
          <div class="login-feature"><span class="check">✓</span> Offertes digitaal laten ondertekenen in het klantenportaal</div>
          <div class="login-feature"><span class="check">✓</span> Herinneringen en aanmaningen gaan automatisch de deur uit</div>
          <div class="login-feature"><span class="check">✓</span> iDEAL-betaallink op elke factuur, betalingen direct geboekt</div>
          <div class="login-feature"><span class="check">✓</span> Live overzicht van openstaand, achterstallig en je resultaat</div>
        </div>
        <h2>Problemen met inloggen?</h2>
        <p>Wachtwoord vergeten? Vraag een nieuw wachtwoord aan via de link onder het formulier; je ontvangt binnen een minuut een e-mail. Heb je tweestapsverificatie aanstaan, dan vragen we na je wachtwoord om de code uit je authenticator-app. Kom je er niet uit, stuur dan een bericht via de <a :href="route('contact')" style="color:#fff;">contactpagina</a> — we reageren meestal binnen een werkdag.</p>
        <h2>Nog geen account?</h2>
        <p>Probeer EasyInvoice 14 dagen gratis, zonder creditcard. Je maakt in één minuut een account aan en verstuurt direct je eerste factuur — of kijk eerst rond in de demo met voorbeeldgegevens. Ben je klant van een ondernemer die EasyInvoice gebruikt en zoek je je factuur of offerte? Die vind je in het klantenportaal; daar log je in met alleen je e-mailadres.</p>
        <ul class="auth-links">
          <li><a :href="route('register')">Gratis account aanmaken</a></li>
          <li><a :href="route('demo')">Demo bekijken</a></li>
          <li><a :href="route('gratis-factuur')">Gratis factuur maken</a></li>
          <li><a :href="route('helpcentrum')">Helpcentrum</a></li>
          <li><a :href="route('faq')">Veelgestelde vragen</a></li>
          <li><a :href="route('portal.login')">Klantenportaal</a></li>
        </ul>
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

        <div class="login-row" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
          <label class="remember-row">
            <input type="checkbox" v-model="form.remember" />
            <span>30 dagen onthouden</span>
          </label>
          <a :href="route('password.request')" style="font-size:13px;">Wachtwoord vergeten?</a>
        </div>

        <Turnstile :sitekey="turnstileSitekey"
                   @verified="t => form['cf-turnstile-response'] = t"
                   @expired="() => form['cf-turnstile-response'] = ''" />
        <div v-if="form.errors['cf-turnstile-response']" class="field-error">{{ form.errors['cf-turnstile-response'] }}</div>

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
