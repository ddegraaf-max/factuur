<script setup>
import { useForm, Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Turnstile from '@/Components/Turnstile.vue';
import LopraAuthHero from '@/Components/LopraAuthHero.vue';
import { t } from '@/i18n';

const turnstileSitekey = import.meta.env.VITE_TURNSTILE_SITEKEY || '';
const brand = usePage().props.brand;

// Markt (nl/pl): labels, placeholders en rechtsvormen van KvK/REGON en btw-nummer/NIP
// komen van de server — zie config/markets.php en Market::forClient().
const market = usePage().props.market || {};
const registry = market.registry || { label: 'KvK-nummer', placeholder: '12345678', required: true, digits: [8, 8] };
const taxId = market.tax_id || { label: 'Btw-nummer', placeholder: 'NL123456789B01', required: false, maxlength: 14 };
const companyTypes = Object.entries(market.company_types || { eenmanszaak: 'ZZP / Eenmanszaak' })
  .map(([value, label]) => ({ value, label }));
const isPl = market.key === 'pl';

const form = useForm({
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  password_confirmation: '',
  companyName: '',
  companyType: companyTypes[0]?.value ?? 'eenmanszaak',
  kvkNumber: '',
  vatNumber: '',
  acceptTerms: false,
  newsletter: true,
  'cf-turnstile-response': '',
});

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
    { label: t('Te kort'), color: '#DC2626', pct: 15 },
    { label: t('Zwak'),    color: '#DC2626', pct: 30 },
    { label: t('Matig'),   color: '#F59E0B', pct: 55 },
    { label: t('Goed'),    color: '#10B981', pct: 80 },
    { label: t('Sterk'),   color: '#059669', pct: 100 },
  ];
  return { score, ...levels[score] };
});

// Poolse markt: bedrijfsnaam en REGON ophalen uit GUS / biała lista op basis van het NIP.
const nipLoading = ref(false);
const nipError = ref('');
const nipResult = ref('');

const lookupNip = async () => {
  const nip = String(form.vatNumber || '').replace(/\D/g, '');
  nipError.value = '';
  nipResult.value = '';
  if (nip.length !== 10) {
    nipError.value = t('Vul eerst een geldig NIP-nummer in (10 cijfers).');
    return;
  }
  nipLoading.value = true;
  try {
    const { data } = await axios.get(route('nip.public', nip));
    if (!data.result) {
      nipError.value = data.error || t('Geen bedrijf gevonden bij dit NIP-nummer.');
      return;
    }
    if (data.result.name) form.companyName = data.result.name;
    if (data.result.regon) form.kvkNumber = String(data.result.regon);
    nipResult.value = [data.result.name, data.result.city, data.result.vat_status].filter(Boolean).join(' · ');
  } catch (e) {
    nipError.value = e?.response?.data?.error || t('Ophalen mislukt. Probeer het later opnieuw of vul de gegevens zelf in.');
  } finally {
    nipLoading.value = false;
  }
};

const submit = () => form.post(route('register'));
</script>

<template>
  <Head :title="$t('Registreren')" />
  <AuthLayout>
    <template #hero>
      <!-- Lopra heeft een eigen, korte hero met visual; EasyInvoice houdt de uitgebreide SEO-tekst. -->
      <LopraAuthHero v-if="brand.key === 'lopra' || brand.key === 'lopra_pl'" mode="register" />
      <div v-else class="auth-copy">
        <h1 class="auth-h1">{{ $t('Maak gratis een :brand-account aan', { brand: brand.name }) }}</h1>
        <p>{{ $t('Begin vandaag met je administratie: je maakt in één minuut een account aan en verstuurt binnen vijf minuten je eerste professionele factuur. De eerste 14 dagen zijn gratis, zonder creditcard en zonder verplichtingen — daarna vanaf € 12,10 per maand (incl. 21% btw), maandelijks opzegbaar.') }}</p>
        <ul class="hero-bullets">
          <li>{{ $t('Onbeperkt facturen en offertes, in je eigen huisstijl') }}</li>
          <li>{{ $t('Btw automatisch per regel (21/9/0%) en de aangifte per kwartaal klaar') }}</li>
          <li>{{ $t('Herinneringen, aanmaningen en incasso zonder omkijken') }}</li>
          <li>{{ $t('iDEAL-betaallink en klantenportaal met digitaal ondertekenen') }}</li>
          <li>{{ $t('Urenregistratie, inkoopfacturen en een live resultaatoverzicht') }}</li>
          <li>{{ $t('Boekhouder gratis mee laten kijken') }}</li>
        </ul>
        <h2>{{ $t('Hoe werkt het?') }}</h2>
        <p>{{ $t('Vul hiernaast je naam, e-mailadres en een wachtwoord in en bevestig je e-mailadres met de code die je ontvangt. Daarna vul je je bedrijfsgegevens in — met je KvK-nummer halen we die grotendeels automatisch op — en kun je meteen een klant toevoegen en factureren. Alles wat je in de proefperiode aanmaakt, blijft gewoon staan als je doorgaat.') }}</p>
        <h2>{{ $t('Wat kost het?') }}</h2>
        <p>{{ $t('Het volledige facturatiepakket kost € 12,10 per maand (incl. 21% btw). Wil je ook de AI-functies, zoals bonnetjes scannen en een offerte maken uit een paar zinnen tekst? Dan kies je Slim voor € 21,18 per maand. Geen instapkosten, geen jaarcontract: je zegt op wanneer je wilt.') }}</p>
        <h2>{{ $t('Liever eerst kijken?') }}</h2>
        <p>{{ $t('Bekijk de demo met voorbeeldgegevens, maak zonder account een gratis factuur, of lees in het helpcentrum hoe alles werkt.') }}</p>
        <ul class="auth-links">
          <li><a :href="route('demo')">{{ $t('Demo bekijken') }}</a></li>
          <li><a :href="route('gratis-factuur')">{{ $t('Gratis factuur maken') }}</a></li>
          <li><a :href="route('helpcentrum')">{{ $t('Helpcentrum') }}</a></li>
          <li><a :href="route('faq')">{{ $t('Veelgestelde vragen') }}</a></li>
          <li><a :href="route('over')">{{ $t('Over :brand', { brand: brand.name }) }}</a></li>
          <li><a :href="route('login')">{{ $t('Al een account? Inloggen') }}</a></li>
        </ul>
      </div>
    </template>

    <div class="register-card">
      <div class="login-form-title">{{ $t('Maak een account') }}</div>
      <div class="login-form-sub">
        {{ $t('Heb je al een account?') }} <a :href="route('login')">{{ $t('Inloggen') }}</a>
      </div>

      <form @submit.prevent="submit">
        <div class="register-section">
          <div class="register-section-title"><span class="num">1</span> {{ $t('Jouw gegevens') }}</div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Voornaam') }} *</label>
              <input v-model="form.firstName" type="text" required />
              <div v-if="form.errors.firstName" class="field-error">{{ form.errors.firstName }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Achternaam') }} *</label>
              <input v-model="form.lastName" type="text" required />
              <div v-if="form.errors.lastName" class="field-error">{{ form.errors.lastName }}</div>
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('E-mailadres') }} *</label>
            <input v-model="form.email" type="email" required />
            <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Wachtwoord') }} *</label>
              <input v-model="form.password" type="password" required />
              <div v-if="form.password" class="password-strength">
                <div class="bar"><div class="fill" :style="{ width: pwStrength.pct + '%', background: pwStrength.color }"></div></div>
                <span class="label" :style="{ color: pwStrength.color }">{{ pwStrength.label }}</span>
              </div>
              <div v-if="form.errors.password" class="field-error">{{ form.errors.password }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Bevestigen') }} *</label>
              <input v-model="form.password_confirmation" type="password" required />
            </div>
          </div>
        </div>

        <div class="register-section">
          <div class="register-section-title"><span class="num">2</span> {{ $t('Je bedrijf') }}</div>
          <div class="form-group">
            <label>{{ $t('Bedrijfsnaam') }} *</label>
            <input v-model="form.companyName" type="text" required />
            <div v-if="form.errors.companyName" class="field-error">{{ form.errors.companyName }}</div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Bedrijfsvorm') }}</label>
              <select v-model="form.companyType">
                <option v-for="ct in companyTypes" :key="ct.value" :value="ct.value">{{ ct.label }}</option>
              </select>
            </div>
            <div class="form-group">
              <!-- KvK-nummer (nl) of REGON (pl): label, lengte en verplichting per markt -->
              <label>{{ registry.label }}<template v-if="registry.required"> *</template><span v-else class="muted"> {{ $t('(optioneel)') }}</span></label>
              <input v-model="form.kvkNumber" type="text" inputmode="numeric" :maxlength="registry.digits[1]" :placeholder="registry.placeholder" :required="registry.required" />
              <div v-if="form.errors.kvkNumber" class="field-error">{{ form.errors.kvkNumber }}</div>
            </div>
          </div>
          <div class="form-group">
            <!-- Btw-nummer (nl) of NIP (pl); in Polen met knop om bedrijfsgegevens uit GUS / biała lista te halen -->
            <label>{{ taxId.label }}<template v-if="taxId.required"> *</template><span v-else class="muted"> {{ $t('(optioneel)') }}</span></label>
            <div style="display:flex;gap:8px;align-items:flex-start;">
              <input v-model="form.vatNumber" type="text" :placeholder="taxId.placeholder" :maxlength="taxId.maxlength" :required="taxId.required" style="flex:1;min-width:0;" />
              <button v-if="isPl" type="button" class="btn btn-secondary" :disabled="nipLoading" @click="lookupNip">{{ nipLoading ? $t('Bezig…') : $t('Gegevens ophalen') }}</button>
            </div>
            <div v-if="nipResult" class="muted" style="font-size:12px;margin-top:4px;">{{ nipResult }}</div>
            <div v-if="nipError" class="field-error">{{ nipError }}</div>
            <div v-if="form.errors.vatNumber" class="field-error">{{ form.errors.vatNumber }}</div>
          </div>
        </div>

        <label class="checkbox-row">
          <input type="checkbox" v-model="form.acceptTerms" />
          <span>{{ $t('Ik ga akkoord met de algemene voorwaarden en het privacybeleid.') }} *</span>
        </label>
        <div v-if="form.errors.acceptTerms" class="field-error">{{ form.errors.acceptTerms }}</div>

        <label class="checkbox-row">
          <input type="checkbox" v-model="form.newsletter" />
          <span>{{ $t('Stuur me tips, productupdates en nieuws.') }}</span>
        </label>

        <Turnstile :sitekey="turnstileSitekey"
                   @verified="t => form['cf-turnstile-response'] = t"
                   @expired="() => form['cf-turnstile-response'] = ''" />
        <div v-if="form.errors['cf-turnstile-response']" class="field-error">{{ form.errors['cf-turnstile-response'] }}</div>

        <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
          {{ form.processing ? $t('Bezig…') : $t('Account aanmaken') }}
        </button>
      </form>
    </div>
  </AuthLayout>
</template>
