<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const form = useForm({
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  password_confirmation: '',
  companyName: '',
  companyType: 'eenmanszaak',
  kvkNumber: '',
  vatNumber: '',
  acceptTerms: false,
  newsletter: true,
});

const companyTypes = [
  { value: 'eenmanszaak', label: 'ZZP / Eenmanszaak' },
  { value: 'bv', label: 'Besloten Vennootschap (BV)' },
  { value: 'vof', label: 'Vennootschap Onder Firma (VOF)' },
  { value: 'maatschap', label: 'Maatschap' },
  { value: 'stichting', label: 'Stichting' },
  { value: 'vereniging', label: 'Vereniging' },
  { value: 'other', label: 'Anders' },
];

const pwStrength = computed(() => {
  const pw = form.password;
  if (!pw) return { score: 0, label: '', color: 'var(--text-4)', pct: 0 };
  let score = 0;
  if (pw.length >= 8) score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
  if (/\d/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  score = Math.min(score, 4);
  const levels = [
    { label: 'Te kort', color: '#DC2626', pct: 15 },
    { label: 'Zwak',    color: '#DC2626', pct: 30 },
    { label: 'Matig',   color: '#F59E0B', pct: 55 },
    { label: 'Goed',    color: '#10B981', pct: 80 },
    { label: 'Sterk',   color: '#059669', pct: 100 },
  ];
  return { score, ...levels[score] };
});

const submit = () => form.post(route('register'));
</script>

<template>
  <Head title="Registreren" />
  <AuthLayout>
    <template #hero>
      <h2>Begin met je administratie</h2>
      <p>Maak een gratis EasyInvoice-account aan en stuur binnen vijf minuten je eerste factuur.</p>
      <ul class="hero-bullets">
        <li>Onbeperkt facturen versturen</li>
        <li>Automatische BTW per regel</li>
        <li>14 dagen gratis — geen creditcard</li>
      </ul>
    </template>

    <div class="register-card">
      <div class="login-form-title">Maak een account</div>
      <div class="login-form-sub">
        Heb je al een account? <a :href="route('login')">Inloggen</a>
      </div>

      <form @submit.prevent="submit">
        <div class="register-section">
          <div class="register-section-title"><span class="num">1</span> Jouw gegevens</div>
          <div class="form-row">
            <div class="form-group">
              <label>Voornaam *</label>
              <input v-model="form.firstName" type="text" required />
              <div v-if="form.errors.firstName" class="field-error">{{ form.errors.firstName }}</div>
            </div>
            <div class="form-group">
              <label>Achternaam *</label>
              <input v-model="form.lastName" type="text" required />
              <div v-if="form.errors.lastName" class="field-error">{{ form.errors.lastName }}</div>
            </div>
          </div>
          <div class="form-group">
            <label>E-mailadres *</label>
            <input v-model="form.email" type="email" required />
            <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Wachtwoord *</label>
              <input v-model="form.password" type="password" required />
              <div v-if="form.password" class="password-strength">
                <div class="bar"><div class="fill" :style="{ width: pwStrength.pct + '%', background: pwStrength.color }"></div></div>
                <span class="label" :style="{ color: pwStrength.color }">{{ pwStrength.label }}</span>
              </div>
              <div v-if="form.errors.password" class="field-error">{{ form.errors.password }}</div>
            </div>
            <div class="form-group">
              <label>Bevestigen *</label>
              <input v-model="form.password_confirmation" type="password" required />
            </div>
          </div>
        </div>

        <div class="register-section">
          <div class="register-section-title"><span class="num">2</span> Je bedrijf</div>
          <div class="form-group">
            <label>Bedrijfsnaam *</label>
            <input v-model="form.companyName" type="text" required />
            <div v-if="form.errors.companyName" class="field-error">{{ form.errors.companyName }}</div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Bedrijfsvorm</label>
              <select v-model="form.companyType">
                <option v-for="t in companyTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>KVK-nummer *</label>
              <input v-model="form.kvkNumber" type="text" inputmode="numeric" maxlength="8" required />
              <div v-if="form.errors.kvkNumber" class="field-error">{{ form.errors.kvkNumber }}</div>
            </div>
          </div>
          <div class="form-group">
            <label>BTW-nummer <span class="muted">(optioneel)</span></label>
            <input v-model="form.vatNumber" type="text" placeholder="NL123456789B01" />
            <div v-if="form.errors.vatNumber" class="field-error">{{ form.errors.vatNumber }}</div>
          </div>
        </div>

        <label class="checkbox-row">
          <input type="checkbox" v-model="form.acceptTerms" />
          <span>Ik ga akkoord met de algemene voorwaarden en het privacybeleid. *</span>
        </label>
        <div v-if="form.errors.acceptTerms" class="field-error">{{ form.errors.acceptTerms }}</div>

        <label class="checkbox-row">
          <input type="checkbox" v-model="form.newsletter" />
          <span>Stuur me tips, productupdates en nieuws.</span>
        </label>

        <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
          {{ form.processing ? 'Bezig…' : 'Account aanmaken' }}
        </button>
      </form>
    </div>
  </AuthLayout>
</template>
