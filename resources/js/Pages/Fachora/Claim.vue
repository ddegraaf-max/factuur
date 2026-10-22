<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FachoraShell from '@/Components/FachoraShell.vue';
import FachoraBadge from '@/Components/FachoraBadge.vue';
import Turnstile from '@/Components/Turnstile.vue';
import { icon } from '@/fachora-icons.js';

/**
 * Profiel claimen en aanmelden voor bedrijven, in vier standen:
 *   start    code van de brief invullen, of het bedrijf uit ?kod= / ?firma= overnemen
 *   apply    nieuw bedrijf dat nog niet in de database staat
 *   confirm  de link uit de mail: wachtwoord kiezen en het regulamin accepteren
 *   optout   afmelden voor e-mail
 * Na start en apply stuurt de server terug met ?wyslano=1: "kijk in uw mailbox".
 */
const props = defineProps({
  mode: { type: String, required: true },
  code: { type: String, default: null },
  codeInvalid: { type: Boolean, default: false },
  provider: { type: Object, default: null },
  alreadyClaimed: { type: Boolean, default: false },
  nipNotFound: { type: Boolean, default: false },
  categories: { type: Array, default: () => [] },
  legalForms: { type: Object, default: () => ({}) },
  valid: { type: Boolean, default: false },
  token: { type: String, default: null },
  email: { type: String, default: null },
  existingUser: { type: Boolean, default: false },
  // 1.87: afmelden in twee stappen (de link verandert niets; de knop wel).
  done: { type: String, default: null },
  canRemove: { type: Boolean, default: false },
});
const optoutForm = useForm({ action: '' });
const optoutAction = (action) => { optoutForm.action = action; optoutForm.post(route('fachora.optout.confirm', props.token)); };

const page = usePage();
const sent = computed(() => /[?&]wyslano=1(&|$)/.test(page.url || (typeof window !== 'undefined' ? window.location.search : '')));
const categoryName = (key) => props.categories.find((c) => c.key === key)?.name || key;
const providerCategories = computed(() => (props.provider?.categories || []).map(categoryName));

const titles = { start: 'Profiel overnemen', apply: 'Bedrijf aanmelden', confirm: 'Account aanmaken', optout: 'Afmelden voor e-mail' };

/* ---------- start: profiel overnemen ---------- */
const codeInput = ref(props.code || '');
// Zoeken op NIP (?nip=): dezelfde pagina, de controller zet dan `provider` of `nipNotFound`.
const nipFromUrl = () => { try { return new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('nip') || ''; } catch (e) { return ''; } };
const nipInput = ref(nipFromUrl());
// Al overgenomen (of al Verified, dus zeker van een eigenaar): niet nog eens claimen, maar inloggen.
const claimed = computed(() => props.alreadyClaimed || !!props.provider?.verified);
const claimForm = useForm({
  provider_id: props.provider?.id || null,
  code: props.code || '',
  name: '',
  email: '',
  phone: '',
  website: '',
  'cf-turnstile-response': '',
});
const startClaim = () => claimForm.post(route('fachora.claim.start'), { preserveScroll: true, onError: () => { claimForm['cf-turnstile-response'] = ''; } });

/* ---------- apply: nieuw bedrijf ---------- */
const applyForm = useForm({
  nip: '',
  company_name: '',
  legal_form: '',
  address: '',
  postal_code: '',
  city: '',
  categories: [],
  name: '',
  email: '',
  phone: '',
  site: '',
  website: '',
  'cf-turnstile-response': '',
});
const toggleCategory = (key) => {
  applyForm.categories = applyForm.categories.includes(key) ? applyForm.categories.filter((k) => k !== key) : [...applyForm.categories, key];
};
const apply = () => applyForm.post(route('fachora.apply.store'), { preserveScroll: true, onError: () => { applyForm['cf-turnstile-response'] = ''; } });

/* ---------- confirm: wachtwoord en regulamin ---------- */
const confirmForm = useForm({ password: '', terms: false });
const confirmClaim = () => confirmForm.post(route('fachora.claim.confirm', props.token));
</script>

<template>
  <FachoraShell :title="$t(titles[mode] || 'Fachora')" narrow :noindex="mode !== 'start' && mode !== 'apply'">
    <!-- ===== Mail verstuurd ===== -->
    <div v-if="sent && (mode === 'start' || mode === 'apply')" class="fa-card cl-sent">
      <div class="cl-sent-icon"><span class="ic" v-html="icon('check', 28)"></span></div>
      <h1 class="fa-h1">{{ $t('Controleer uw mailbox') }}</h1>
      <p class="fa-lead">{{ $t('We hebben een e-mail gestuurd met een link om uw account aan te maken. De link is 7 dagen geldig.') }}</p>
      <p class="fa-small fa-muted">{{ $t('Niets ontvangen? Kijk in de map Spam of Ongewenst, of mail ons.') }}</p>
    </div>

    <!-- ===== START ===== -->
    <template v-else-if="mode === 'start'">
      <div v-if="provider" class="fa-card">
        <span class="fa-eyebrow">{{ $t('Profiel op Fachora') }}</span>
        <div class="cl-prov">
          <h1 class="fa-h1">{{ provider.name }}</h1>
          <FachoraBadge :verified="!!provider.verified" />
        </div>
        <div class="fa-small fa-muted">{{ provider.city }}<template v-if="providerCategories.length"> · {{ providerCategories.join(', ') }}</template></div>
        <a v-if="provider.url" :href="provider.url" target="_blank" rel="noopener" class="fa-link fa-small">{{ $t('Bekijk profiel') }}</a>

        <template v-if="claimed">
          <hr class="fa-sep">
          <div class="fa-note">{{ $t('Dit profiel is al overgenomen door de eigenaar. Bent u dat zelf? Log dan in.') }}</div>
          <div class="fa-actions">
            <a :href="route('login')" class="fa-btn fa-btn--navy">{{ $t('Inloggen') }}</a>
            <a :href="'mailto:' + ($page.props.brand?.email || '')" class="fa-link fa-link--muted">{{ $t('Dit klopt niet, help') }}</a>
          </div>
        </template>

        <form v-else @submit.prevent="startClaim" class="cl-form">
          <hr class="fa-sep">
          <h2 class="fa-h2">{{ $t('Profiel overnemen') }}</h2>
          <p class="fa-small fa-muted">{{ $t('Vul uw gegevens in; u krijgt een e-mail met een link om het account aan te maken. Daarna vult u het profiel aan en vraagt u het keurmerk Fachora Verified aan.') }}</p>
          <div v-if="claimForm.errors.provider_id" class="fa-error">{{ claimForm.errors.provider_id }}</div>
          <div v-if="code" class="fa-field">
            <label>{{ $t('Code van de brief') }}</label>
            <input type="text" v-model="claimForm.code" maxlength="12" class="cl-code" autocapitalize="characters">
            <div v-if="claimForm.errors.code" class="fa-err">{{ claimForm.errors.code }}</div>
          </div>
          <div class="fa-field">
            <label>{{ $t('Naam en achternaam') }} *</label>
            <input type="text" v-model="claimForm.name" maxlength="120" autocomplete="name" required>
            <div v-if="claimForm.errors.name" class="fa-err">{{ claimForm.errors.name }}</div>
          </div>
          <div class="fa-field">
            <label>{{ $t('E-mailadres') }} *</label>
            <input type="email" v-model="claimForm.email" maxlength="180" autocomplete="email" inputmode="email" required>
            <div v-if="claimForm.errors.email" class="fa-err">{{ claimForm.errors.email }}</div>
            <div class="fa-hint">{{ $t('Dit wordt uw inlognaam. Gebruik bij voorkeur het zakelijke adres van het bedrijf.') }}</div>
          </div>
          <div class="fa-field">
            <label>{{ $t('Telefoonnummer') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
            <input type="tel" v-model="claimForm.phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="+48 …">
            <div v-if="claimForm.errors.phone" class="fa-err">{{ claimForm.errors.phone }}</div>
          </div>
          <input name="website" v-model="claimForm.website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
          <Turnstile :sitekey="$page.props.turnstile_sitekey" @verified="v => claimForm['cf-turnstile-response'] = v" @expired="() => claimForm['cf-turnstile-response'] = ''" />
          <div v-if="claimForm.errors['cf-turnstile-response']" class="fa-err">{{ claimForm.errors['cf-turnstile-response'] }}</div>
          <div class="fa-actions">
            <button type="submit" class="fa-btn" :disabled="claimForm.processing">{{ $t('Stuur mij de link') }}</button>
          </div>
          <p class="fa-small fa-muted cl-consent">{{ $t('Fachora stuurt alleen e-mail aan bedrijven die daar zelf om vragen. Met deze aanvraag vraagt u ons om de bevestigingslink en daarna om berichten over uw profiel en opdrachten; afmelden kan altijd.') }}</p>
        </form>
      </div>

      <div v-else class="fa-card">
        <h1 class="fa-h1">{{ $t('Profiel overnemen') }}</h1>
        <p class="fa-lead">{{ $t('Heeft u een brief van Fachora gekregen? Vul de code uit de brief in; dan ziet u het profiel van uw bedrijf en kunt u het overnemen.') }}</p>
        <div v-if="codeInvalid" class="fa-error">{{ $t('Deze code kennen we niet. Controleer de letters en cijfers (geen 0 of O, geen 1 of I) of mail ons.') }}</div>
        <form method="get" :action="route('fachora.claim')" class="cl-codeform">
          <div class="fa-field">
            <label for="cl-kod">{{ $t('Code van de brief') }}</label>
            <input id="cl-kod" type="text" name="kod" v-model="codeInput" maxlength="12" class="cl-code" autocapitalize="characters" autocomplete="off" placeholder="ABCD2345" required>
          </div>
          <div class="fa-actions"><button type="submit" class="fa-btn fa-btn--navy">{{ $t('Profiel opzoeken') }}</button></div>
        </form>
        <hr class="fa-sep">
        <h2 class="fa-h3">{{ $t('Profiel zoeken op NIP') }}</h2>
        <p class="fa-small fa-muted">{{ $t('Geen brief bij de hand? Zoek uw bedrijf op het NIP-nummer.') }}</p>
        <div v-if="nipNotFound" class="fa-warn">{{ $t('We hebben geen bedrijf met dit NIP-nummer gevonden.') }} <a :href="route('fachora.apply')">{{ $t('Mijn bedrijf staat niet in de database') }}</a></div>
        <form method="get" :action="route('fachora.claim')" class="cl-nipform">
          <div class="fa-field">
            <label for="cl-nip">NIP</label>
            <input id="cl-nip" type="text" name="nip" v-model="nipInput" maxlength="13" inputmode="numeric" autocomplete="off" placeholder="1234567890" required>
          </div>
          <div class="fa-actions"><button type="submit" class="fa-btn fa-btn--secondary">{{ $t('Zoeken op NIP') }}</button></div>
        </form>
        <hr class="fa-sep">
        <h2 class="fa-h3">{{ $t('Geen brief, of staat uw bedrijf er niet in?') }}</h2>
        <p class="fa-small fa-muted">{{ $t('Meld uw bedrijf aan. Na een korte controle van de registers en uw gegevens kan het Fachora Verified worden.') }}</p>
        <a :href="route('fachora.apply')" class="fa-btn fa-btn--secondary">{{ $t('Mijn bedrijf staat niet in de database') }}</a>
      </div>

      <div class="fa-card cl-why">
        <h2 class="fa-h3">{{ $t('Waarom Fachora?') }}</h2>
        <ul class="fa-ticks">
          <li>{{ $t('Opdrachten van klanten uit de buurt, in de talen die u spreekt. Geen abonnement: u betaalt alleen voor een aanvraag waarop u reageert, en de eerste is gratis.') }}</li>
          <li>{{ $t('Fachora Protect: de klant reserveert het bedrag vooraf, u wordt uitbetaald na zijn akkoord.') }}</li>
          <li>{{ $t('Het keurmerk Fachora Verified op uw profiel en in elke aanbieding.') }}</li>
          <li>{{ $t('Geen ongevraagde telefoontjes: de klant kiest op zijn pagina, u krijgt zijn gegevens pas daarna.') }}</li>
        </ul>
        <a :href="route('fachora.for-companies')" class="fa-link fa-small">{{ $t('Meer voor bedrijven') }}</a>
      </div>
    </template>

    <!-- ===== APPLY ===== -->
    <template v-else-if="mode === 'apply'">
      <form @submit.prevent="apply" class="fa-card">
        <h1 class="fa-h1">{{ $t('Bedrijf aanmelden') }}</h1>
        <p class="fa-lead">{{ $t('Staat uw bedrijf nog niet op Fachora? Meld het hier aan. Alleen bedrijven met een geldig NIP-nummer.') }}</p>

        <h2 class="fa-h3">{{ $t('Het bedrijf') }}</h2>
        <div class="fa-row">
          <div class="fa-field">
            <label>NIP *</label>
            <input type="text" v-model="applyForm.nip" maxlength="13" inputmode="numeric" placeholder="1234567890" required>
            <div v-if="applyForm.errors.nip" class="fa-err">{{ applyForm.errors.nip }}</div>
          </div>
          <div class="fa-field">
            <label>{{ $t('Bedrijfsvorm') }}</label>
            <select v-model="applyForm.legal_form">
              <option value="">{{ $t('Kies…') }}</option>
              <option v-for="(label, key) in legalForms" :key="key" :value="key">{{ label }}</option>
            </select>
            <div v-if="applyForm.errors.legal_form" class="fa-err">{{ applyForm.errors.legal_form }}</div>
          </div>
        </div>
        <div class="fa-field">
          <label>{{ $t('Bedrijfsnaam') }} *</label>
          <input type="text" v-model="applyForm.company_name" maxlength="160" autocomplete="organization" required>
          <div v-if="applyForm.errors.company_name" class="fa-err">{{ applyForm.errors.company_name }}</div>
        </div>
        <div class="fa-field">
          <label>{{ $t('Adres') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
          <input type="text" v-model="applyForm.address" maxlength="200" autocomplete="street-address">
          <div v-if="applyForm.errors.address" class="fa-err">{{ applyForm.errors.address }}</div>
        </div>
        <div class="fa-row">
          <div class="fa-field">
            <label>{{ $t('Postcode') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
            <input type="text" v-model="applyForm.postal_code" maxlength="6" inputmode="numeric" placeholder="00-000" autocomplete="postal-code">
            <div v-if="applyForm.errors.postal_code" class="fa-err">{{ applyForm.errors.postal_code }}</div>
          </div>
          <div class="fa-field">
            <label>{{ $t('Plaats') }} *</label>
            <input type="text" v-model="applyForm.city" maxlength="120" autocomplete="address-level2" required>
            <div v-if="applyForm.errors.city" class="fa-err">{{ applyForm.errors.city }}</div>
          </div>
        </div>
        <div class="fa-field">
          <label>{{ $t('Website') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
          <input type="text" v-model="applyForm.site" maxlength="180" inputmode="url" placeholder="https://">
          <div v-if="applyForm.errors.site" class="fa-err">{{ applyForm.errors.site }}</div>
        </div>

        <div class="fa-field">
          <label>{{ $t('Wat doet u?') }} * <span class="fa-hint">{{ $t('meerdere mogelijk') }}</span></label>
          <div class="fa-tiles">
            <button v-for="c in categories" :key="c.key" type="button" class="fa-tile cl-tile" :class="{ 'is-on': applyForm.categories.includes(c.key) }" @click="toggleCategory(c.key)">
              <span class="ic" v-html="icon(c.icon, 24)"></span>
              <strong>{{ c.name }}</strong>
            </button>
          </div>
          <div v-if="applyForm.errors.categories" class="fa-err">{{ applyForm.errors.categories }}</div>
        </div>

        <h2 class="fa-h3">{{ $t('Contactpersoon') }}</h2>
        <div class="fa-field">
          <label>{{ $t('Naam en achternaam') }} *</label>
          <input type="text" v-model="applyForm.name" maxlength="120" autocomplete="name" required>
          <div v-if="applyForm.errors.name" class="fa-err">{{ applyForm.errors.name }}</div>
        </div>
        <div class="fa-row">
          <div class="fa-field">
            <label>{{ $t('E-mailadres') }} *</label>
            <input type="email" v-model="applyForm.email" maxlength="180" autocomplete="email" inputmode="email" required>
            <div v-if="applyForm.errors.email" class="fa-err">{{ applyForm.errors.email }}</div>
          </div>
          <div class="fa-field">
            <label>{{ $t('Telefoonnummer') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
            <input type="tel" v-model="applyForm.phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="+48 …">
            <div v-if="applyForm.errors.phone" class="fa-err">{{ applyForm.errors.phone }}</div>
          </div>
        </div>

        <input name="website" v-model="applyForm.website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <Turnstile :sitekey="$page.props.turnstile_sitekey" @verified="v => applyForm['cf-turnstile-response'] = v" @expired="() => applyForm['cf-turnstile-response'] = ''" />
        <div v-if="applyForm.errors['cf-turnstile-response']" class="fa-err">{{ applyForm.errors['cf-turnstile-response'] }}</div>

        <div class="fa-note">
          <strong>{{ $t('Hoe het verder gaat') }}</strong>
          <ul class="fa-ticks cl-steps">
            <li>{{ $t('U bevestigt uw e-mailadres via de link in de mail en kiest een wachtwoord.') }}</li>
            <li>{{ $t('U vult het profiel aan: talen, werkgebied, tarieven, garantie.') }}</li>
            <li>{{ $t('Fachora controleert de registers, uw telefoonnummer en uw bankrekening (via Stripe). Daarna bent u Fachora Verified en ontvangt u opdrachten.') }}</li>
          </ul>
        </div>

        <div class="fa-actions">
          <button type="submit" class="fa-btn" :disabled="applyForm.processing">{{ $t('Bedrijf aanmelden') }}</button>
          <a :href="route('fachora.claim')" class="fa-link fa-link--muted">{{ $t('Ik heb een code van de brief') }}</a>
        </div>
      </form>
    </template>

    <!-- ===== CONFIRM ===== -->
    <template v-else-if="mode === 'confirm'">
      <div v-if="!valid" class="fa-card">
        <h1 class="fa-h1">{{ $t('Deze link is niet (meer) geldig') }}</h1>
        <p class="fa-lead">{{ $t('De link is verlopen of al gebruikt. Vraag een nieuwe aan; dat duurt een minuut.') }}</p>
        <div class="fa-actions">
          <a :href="route('fachora.claim')" class="fa-btn fa-btn--navy">{{ $t('Opnieuw beginnen') }}</a>
          <a :href="route('login')" class="fa-link fa-link--muted">{{ $t('Inloggen') }}</a>
        </div>
      </div>
      <form v-else @submit.prevent="confirmClaim" class="fa-card">
        <span class="fa-eyebrow">{{ $t('Profiel op Fachora') }}</span>
        <div class="cl-prov">
          <h1 class="fa-h1">{{ provider?.name }}</h1>
          <FachoraBadge :verified="!!provider?.verified" />
        </div>
        <div class="fa-small fa-muted">{{ provider?.city }}<template v-if="providerCategories.length"> · {{ providerCategories.join(', ') }}</template></div>
        <hr class="fa-sep">
        <p class="fa-lead">{{ $t('Nog één stap: kies een wachtwoord en accepteer het regulamin. Daarna staat uw panel klaar.') }}</p>
        <div class="fa-field">
          <label>{{ $t('E-mailadres') }}</label>
          <input type="email" :value="email" disabled>
        </div>
        <div v-if="existingUser" class="fa-note">{{ $t('Er bestaat al een account met dit e-mailadres. U logt straks in met uw bestaande wachtwoord; het Fachora-profiel wordt eraan gekoppeld.') }}</div>
        <div v-else class="fa-field">
          <label>{{ $t('Kies een wachtwoord') }} *</label>
          <input type="password" v-model="confirmForm.password" minlength="8" maxlength="200" autocomplete="new-password" required>
          <div v-if="confirmForm.errors.password" class="fa-err">{{ confirmForm.errors.password }}</div>
          <div class="fa-hint">{{ $t('Minstens 8 tekens.') }}</div>
        </div>
        <label class="fa-check">
          <input type="checkbox" v-model="confirmForm.terms">
          <span>{{ $t('Ik accepteer het') }} <a :href="route('pl.regulamin')" target="_blank" rel="noopener">{{ $t('regulamin') }}</a> {{ $t('en de') }} <a :href="route('fachora.standard')" target="_blank" rel="noopener">{{ $t('Fachora Standard') }}</a>{{ $t(', de regels voor bedrijven op het platform.') }}</span>
        </label>
        <div v-if="confirmForm.errors.terms" class="fa-err">{{ confirmForm.errors.terms }}</div>
        <div class="fa-actions">
          <button type="submit" class="fa-btn" :disabled="confirmForm.processing">{{ existingUser ? $t('Profiel koppelen en inloggen') : $t('Account aanmaken') }}</button>
        </div>
      </form>
    </template>

    <!-- ===== OPTOUT ===== -->
    <template v-else-if="mode === 'optout'">
      <div class="fa-card cl-sent">
        <template v-if="valid && done === 'remove'">
          <div class="cl-sent-icon"><span class="ic" v-html="icon('check', 28)"></span></div>
          <h1 class="fa-h1">{{ $t('Profiel verwijderd') }}</h1>
          <p class="fa-lead">{{ $t('We hebben het profiel en de gegevens van uw bedrijf verwijderd. Alleen het NIP bewaren we, zodat we uw bedrijf niet opnieuw opnemen. U krijgt geen e-mail meer van ons.') }}</p>
        </template>
        <template v-else-if="valid && done === 'email'">
          <div class="cl-sent-icon"><span class="ic" v-html="icon('check', 28)"></span></div>
          <h1 class="fa-h1">{{ $t('Afgemeld') }}</h1>
          <p class="fa-lead">{{ $t('We sturen geen e-mail meer aan :name. Verandert u van gedachten, mail ons dan; we zetten het graag weer aan.', { name: provider?.name || '' }) }}</p>
          <button v-if="canRemove" type="button" class="fa-link" :disabled="optoutForm.processing" @click="optoutAction('remove')">{{ $t('Ook mijn profiel verwijderen') }}</button>
        </template>
        <template v-else-if="valid">
          <h1 class="fa-h1">{{ $t('Wat wilt u?') }}</h1>
          <p class="fa-lead">{{ provider?.name }}</p>
          <div class="fa-actions">
            <button type="button" class="fa-btn" :disabled="optoutForm.processing" @click="optoutAction('email')">{{ $t('Geen e-mail meer van Fachora') }}</button>
            <button v-if="canRemove" type="button" class="fa-btn fa-btn--secondary" :disabled="optoutForm.processing" @click="optoutAction('remove')">{{ $t('Mijn profiel verwijderen') }}</button>
          </div>
        </template>
        <template v-else>
          <h1 class="fa-h1">{{ $t('Deze link is niet (meer) geldig') }}</h1>
          <p class="fa-lead">{{ $t('Wilt u geen e-mail meer van Fachora? Mail ons dan even; we regelen het direct.') }}</p>
        </template>
        <a :href="'mailto:' + ($page.props.brand?.email || '')" class="fa-link">{{ $page.props.brand?.email }}</a>
      </div>
    </template>
  </FachoraShell>
</template>

<style scoped>
.cl-sent { text-align: center; }
.cl-sent-icon { width: 56px; height: 56px; border-radius: 50%; background: #ECFDF5; color: #065F46; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; }
.cl-prov { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 4px; }
.cl-prov .fa-h1 { margin-bottom: 0; }
.cl-form { margin-top: 4px; }
.cl-code { font-family: 'JetBrains Mono', ui-monospace, Menlo, monospace; letter-spacing: 0.15em; text-transform: uppercase; }
.cl-codeform { margin-top: 12px; }
.cl-nipform { margin-top: 8px; }
.cl-consent { margin-top: 12px; }
.cl-why .fa-ticks { margin: 10px 0 12px; }
.cl-tile { min-height: 72px; }
.cl-steps { margin-top: 8px; }
</style>
