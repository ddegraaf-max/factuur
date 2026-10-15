<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FachoraShell from '@/Components/FachoraShell.vue';
import Turnstile from '@/Components/Turnstile.vue';
import { icon } from '@/fachora-icons.js';
import { t } from '@/i18n';
import { todayLocal } from '@/format.js';

/**
 * De aanvraag van een klant (/zlecenie/nowe): zes stappen op één pagina, één
 * formulier, één keer versturen. Daarna de "kijk in je mailbox"-stand, tot de
 * klant de link in de bevestigingsmail opent. Geen account, geen telefoonnummers
 * van bedrijven: alles loopt via Fachora.
 */
const props = defineProps({
  categories: { type: Array, default: () => [] },
  languages: { type: Array, default: () => [] },
  preset: { type: String, default: null },
  presetCity: { type: String, default: '' },
  wait: { type: Object, default: null },
  maxPhotos: { type: Number, default: 5 },
});

const page = usePage();
const limitError = computed(() => (page.props.errors || {}).limiet ?? null);
const uiLocale = computed(() => page.props.locale || 'pl');

const form = useForm({
  category: props.preset || '',
  postal_code: '',
  city: props.presetCity || '',
  title: '',
  description: '',
  photos: [],
  urgency: 'week',
  wanted_date: '',
  time_window: '',
  language: uiLocale.value === 'en' ? 'en' : 'pl',
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  consent: false,
  website: '',
  'cf-turnstile-response': '',
});

/* ---------- stappen ---------- */
const STEPS = [
  { n: 1, title: 'Wat heb je nodig?', fields: ['category'] },
  { n: 2, title: 'Waar?', fields: ['postal_code', 'city'] },
  { n: 3, title: 'Wat is er aan de hand?', fields: ['title', 'description', 'photos'] },
  { n: 4, title: 'Wanneer?', fields: ['urgency', 'wanted_date', 'time_window'] },
  { n: 5, title: 'In welke taal?', fields: ['language'] },
  { n: 6, title: 'Contact', fields: ['customer_name', 'customer_email', 'customer_phone', 'consent', 'cf-turnstile-response'] },
];
const step = ref(props.preset ? 2 : 1);
const reached = ref(step.value);
const local = ref({});

const urgencies = [
  { key: 'now', label: 'Zo snel mogelijk', hint: 'Noodgeval: vandaag of morgen' },
  { key: 'week', label: 'Deze week|termijn', hint: 'Binnen een paar dagen' },
  { key: 'month', label: 'Binnen een maand', hint: 'Geen haast, wel graag ingepland' },
  { key: 'date', label: 'Op een datum', hint: 'Kies zelf een dag' },
];

const categoryName = (key) => props.categories.find((c) => c.key === key)?.name || key;
const languageOf = (code) => props.languages.find((l) => l.code === code);

const summary = (n) => {
  switch (n) {
    case 1: return categoryName(form.category);
    case 2: return [form.postal_code, form.city].filter(Boolean).join(' ');
    case 3: return (form.title || form.description).slice(0, 70) + ((form.title || form.description).length > 70 ? '…' : '') + (form.photos.length ? ' · ' + t(':n foto\'s', { n: form.photos.length }) : '');
    case 4: { const u = urgencies.find((x) => x.key === form.urgency); return (form.urgency === 'date' && form.wanted_date ? form.wanted_date : t(u?.label || '')) + (form.time_window ? ' · ' + form.time_window : ''); }
    case 5: { const l = languageOf(form.language); return l ? l.flag + ' ' + l.name : form.language; }
    default: return '';
  }
};

const validate = (n) => {
  const e = {};
  if (n === 1 && !form.category) e.category = t('Kies een categorie.');
  if (n === 2) {
    if (!form.city.trim()) e.city = t('Vul de plaats in.');
    if (form.postal_code && !/^\d{2}-\d{3}$/.test(form.postal_code.trim())) e.postal_code = t('Een Poolse postcode ziet eruit als 00-000.');
  }
  if (n === 3 && form.description.trim().length < 10) e.description = t('Beschrijf de klus in minstens een paar woorden.');
  if (n === 4 && form.urgency === 'date' && !form.wanted_date) e.wanted_date = t('Kies een datum.');
  if (n === 5 && !form.language) e.language = t('Kies een taal.');
  if (n === 6) {
    if (!form.customer_name.trim()) e.customer_name = t('Vul je naam in.');
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(form.customer_email.trim())) e.customer_email = t('Vul een geldig e-mailadres in.');
    if (!form.consent) e.consent = t('Accepteer het regulamin om verder te gaan.');
  }
  local.value = e;
  return Object.keys(e).length === 0;
};

const next = () => {
  if (!validate(step.value)) return;
  step.value = Math.min(6, step.value + 1);
  reached.value = Math.max(reached.value, step.value);
  scrollTo(step.value);
};
const go = (n) => {
  if (n > reached.value) return;
  local.value = {};
  step.value = n;
  scrollTo(n);
};
const scrollTo = (n) => {
  if (typeof document === 'undefined') return;
  requestAnimationFrame(() => document.getElementById('krok-' + n)?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
};
const errorFor = (field) => local.value[field] || form.errors[field] || null;

const pick = (key) => { form.category = key; local.value = {}; };

/* ---------- foto's ---------- */
const previews = ref([]);
watch(() => form.photos, (files) => {
  previews.value.forEach((p) => URL.revokeObjectURL(p.url));
  previews.value = files.map((f) => ({ name: f.name, url: URL.createObjectURL(f) }));
});
const addPhotos = (event) => {
  const files = Array.from(event.target.files || []).filter((f) => f.type.startsWith('image/'));
  form.photos = [...form.photos, ...files].slice(0, props.maxPhotos);
  event.target.value = '';
};
const removePhoto = (i) => { form.photos = form.photos.filter((_, idx) => idx !== i); };

/* ---------- versturen ---------- */
const submit = () => {
  if (!validate(6)) return;
  form.transform((d) => ({ ...d, postal_code: d.postal_code.trim(), wanted_date: d.urgency === 'date' ? d.wanted_date : '' }))
    .post(route('fachora.intake.store'), {
      forceFormData: true,
      preserveScroll: true,
      onError: (errors) => {
        const bad = STEPS.find((s) => s.fields.some((f) => errors[f]));
        if (bad) { step.value = bad.n; reached.value = Math.max(reached.value, bad.n); scrollTo(bad.n); }
        form['cf-turnstile-response'] = '';
      },
    });
};

/* ---------- wachten op de bevestiging ---------- */
const resendForm = useForm({ token: props.wait?.token || '' });
const resend = () => resendForm.post(route('fachora.intake.resend'), { preserveScroll: true });

const today = todayLocal();
</script>

<template>
  <FachoraShell :title="wait ? $t('Controleer je mailbox') : $t('Nieuwe aanvraag')" narrow>
    <!-- ===== Na het versturen: bevestig je e-mailadres ===== -->
    <template v-if="wait">
      <div class="fa-card in-wait">
        <div class="in-wait-icon"><span class="ic" v-html="icon('check', 28)"></span></div>
        <h1 class="fa-h1">{{ $t('Controleer je mailbox') }}</h1>
        <p class="fa-lead">{{ $t('We hebben een e-mail gestuurd naar :email met een link om je aanvraag te bevestigen. Pas na die klik bieden we de klus aan bij bedrijven.', { email: wait.email }) }}</p>
        <p><span class="fa-eyebrow">{{ $t('Kenmerk') }}</span><br><span class="fa-ref">{{ wait.reference }}</span></p>

        <div v-if="wait.mailFailed" class="fa-warn">{{ $t('De e-mail kon niet worden verstuurd. Controleer het adres en probeer het hieronder opnieuw.') }}</div>
        <div v-else-if="wait.resent" class="fa-flash">{{ $t('De e-mail is opnieuw verstuurd.') }}</div>
        <div v-if="wait.resendLimit" class="fa-warn">{{ $t('Je hebt de e-mail al een paar keer opnieuw laten sturen. Wacht even en kijk ook in de map Spam.') }}</div>

        <p class="fa-small fa-muted">{{ $t('Niets ontvangen? Kijk in de map Spam of Ongewenst. De link is 7 dagen geldig.') }}</p>
        <form @submit.prevent="resend" class="fa-actions">
          <button type="submit" class="fa-btn fa-btn--secondary" :disabled="resendForm.processing || wait.resendLimit">{{ $t('E-mail opnieuw sturen') }}</button>
          <a :href="route('fachora.intake')" class="fa-link fa-link--muted">{{ $t('Nieuwe aanvraag beginnen') }}</a>
        </form>
      </div>
    </template>

    <!-- ===== Het formulier ===== -->
    <template v-else>
      <div class="in-head">
        <h1 class="fa-h1">{{ $t('Vind een gecontroleerde vakman') }}</h1>
        <p class="fa-lead">{{ $t('Beschrijf de klus in een paar stappen. Fachora Verified-bedrijven uit de buurt sturen je een prijs; jij kiest rustig.') }}</p>
        <ul class="in-trust">
          <li><span class="ic" v-html="icon('check', 16)"></span>{{ $t('Gratis voor klanten') }}</li>
          <li><span class="ic" v-html="icon('shield', 16)"></span>{{ $t('Alleen gecontroleerde bedrijven') }}</li>
          <li><span class="ic" v-html="icon('card', 16)"></span>{{ $t('Prijs vooraf afgesproken') }}</li>
          <li><span class="ic" v-html="icon('lang', 16)"></span>{{ $t('Geen telefoonnummers nodig') }}</li>
        </ul>
      </div>

      <div v-if="limitError" class="fa-error">{{ limitError }}</div>

      <form @submit.prevent="submit" novalidate>
        <!-- Stap 1: categorie -->
        <section :id="'krok-1'" class="fa-card in-step" :class="{ 'is-open': step === 1, 'is-done': step !== 1 && reached >= 1 }">
          <header class="in-step-head" @click="step !== 1 && go(1)">
            <span class="in-step-n">1</span>
            <div class="in-step-title"><h2 class="fa-h2">{{ $t('Wat heb je nodig?') }}</h2><div v-if="step !== 1" class="in-step-sum">{{ summary(1) }}</div></div>
            <button v-if="step !== 1" type="button" class="fa-link fa-small">{{ $t('Wijzigen') }}</button>
          </header>
          <div v-if="step === 1" class="in-step-body">
            <div class="fa-tiles">
              <button v-for="c in categories" :key="c.key" type="button" class="fa-tile" :class="{ 'is-on': form.category === c.key }" @click="pick(c.key)">
                <span class="ic" v-html="icon(c.icon, 26)"></span>
                <strong>{{ c.name }}</strong>
                <small>{{ c.examples }}</small>
              </button>
            </div>
            <div v-if="errorFor('category')" class="fa-err">{{ errorFor('category') }}</div>
            <div class="fa-actions"><button type="button" class="fa-btn fa-btn--navy" @click="next">{{ $t('Volgende') }}</button></div>
          </div>
        </section>

        <!-- Stap 2: plaats -->
        <section :id="'krok-2'" class="fa-card in-step" :class="{ 'is-open': step === 2, 'is-done': step !== 2 && reached >= 2, 'is-locked': reached < 2 }">
          <header class="in-step-head" @click="step !== 2 && go(2)">
            <span class="in-step-n">2</span>
            <div class="in-step-title"><h2 class="fa-h2">{{ $t('Waar?') }}</h2><div v-if="step !== 2 && reached >= 2" class="in-step-sum">{{ summary(2) }}</div></div>
            <button v-if="step !== 2 && reached >= 2" type="button" class="fa-link fa-small">{{ $t('Wijzigen') }}</button>
          </header>
          <div v-if="step === 2" class="in-step-body">
            <div class="fa-row">
              <div class="fa-field">
                <label for="in-postal">{{ $t('Postcode') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
                <input id="in-postal" type="text" v-model="form.postal_code" inputmode="numeric" placeholder="00-000" maxlength="6" :aria-invalid="!!errorFor('postal_code')">
                <div v-if="errorFor('postal_code')" class="fa-err">{{ errorFor('postal_code') }}</div>
              </div>
              <div class="fa-field">
                <label for="in-city">{{ $t('Plaats') }} *</label>
                <input id="in-city" type="text" v-model="form.city" autocomplete="address-level2" :placeholder="$t('bijv. Warszawa')" :aria-invalid="!!errorFor('city')">
                <div v-if="errorFor('city')" class="fa-err">{{ errorFor('city') }}</div>
              </div>
            </div>
            <p class="fa-small fa-muted">{{ $t('Je straat en huisnummer vragen we pas als je een bedrijf kiest. Tot dan ziet niemand je adres.') }}</p>
            <div class="fa-actions"><button type="button" class="fa-btn fa-btn--navy" @click="next">{{ $t('Volgende') }}</button></div>
          </div>
        </section>

        <!-- Stap 3: omschrijving en foto's -->
        <section :id="'krok-3'" class="fa-card in-step" :class="{ 'is-open': step === 3, 'is-done': step !== 3 && reached >= 3, 'is-locked': reached < 3 }">
          <header class="in-step-head" @click="step !== 3 && go(3)">
            <span class="in-step-n">3</span>
            <div class="in-step-title"><h2 class="fa-h2">{{ $t('Wat is er aan de hand?') }}</h2><div v-if="step !== 3 && reached >= 3" class="in-step-sum">{{ summary(3) }}</div></div>
            <button v-if="step !== 3 && reached >= 3" type="button" class="fa-link fa-small">{{ $t('Wijzigen') }}</button>
          </header>
          <div v-if="step === 3" class="in-step-body">
            <div class="fa-field">
              <label for="in-desc">{{ $t('Omschrijving van de klus') }} *</label>
              <textarea id="in-desc" v-model="form.description" rows="5" maxlength="3000" :placeholder="$t('Wat is er kapot, waar zit het, sinds wanneer? Schrijf gerust in je eigen taal: wij vertalen het voor het bedrijf.')" :aria-invalid="!!errorFor('description')"></textarea>
              <div v-if="errorFor('description')" class="fa-err">{{ errorFor('description') }}</div>
            </div>
            <div class="fa-field">
              <label for="in-title">{{ $t('Korte titel') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
              <input id="in-title" type="text" v-model="form.title" maxlength="160" :placeholder="$t('bijv. Lekkende kraan in de keuken')">
              <div v-if="errorFor('title')" class="fa-err">{{ errorFor('title') }}</div>
            </div>
            <div class="fa-field">
              <label>{{ $t("Foto's") }} <span class="fa-hint">{{ $t('optioneel, maximaal :n', { n: maxPhotos }) }}</span></label>
              <div v-if="previews.length" class="fa-gallery in-gallery">
                <div v-for="(p, i) in previews" :key="p.url" class="ph">
                  <img :src="p.url" :alt="p.name">
                  <button type="button" class="rm" :aria-label="$t('Verwijder')" @click="removePhoto(i)">×</button>
                </div>
              </div>
              <label v-if="form.photos.length < maxPhotos" class="in-upload">
                <input type="file" accept="image/*" multiple @change="addPhotos">
                <span class="in-plus">+</span>{{ previews.length ? $t('Nog een foto toevoegen') : $t("Foto's toevoegen") }}
              </label>
              <div v-if="errorFor('photos')" class="fa-err">{{ errorFor('photos') }}</div>
              <div v-for="(err, key) in form.errors" :key="key">
                <div v-if="String(key).startsWith('photos.')" class="fa-err">{{ err }}</div>
              </div>
              <p class="fa-small fa-muted">{{ $t('Een foto zegt meer dan een alinea: bedrijven geven dan een nauwkeurigere prijs.') }}</p>
            </div>
            <div class="fa-actions"><button type="button" class="fa-btn fa-btn--navy" @click="next">{{ $t('Volgende') }}</button></div>
          </div>
        </section>

        <!-- Stap 4: wanneer -->
        <section :id="'krok-4'" class="fa-card in-step" :class="{ 'is-open': step === 4, 'is-done': step !== 4 && reached >= 4, 'is-locked': reached < 4 }">
          <header class="in-step-head" @click="step !== 4 && go(4)">
            <span class="in-step-n">4</span>
            <div class="in-step-title"><h2 class="fa-h2">{{ $t('Wanneer?') }}</h2><div v-if="step !== 4 && reached >= 4" class="in-step-sum">{{ summary(4) }}</div></div>
            <button v-if="step !== 4 && reached >= 4" type="button" class="fa-link fa-small">{{ $t('Wijzigen') }}</button>
          </header>
          <div v-if="step === 4" class="in-step-body">
            <div class="fa-options">
              <label v-for="u in urgencies" :key="u.key" class="fa-option" :class="{ 'is-on': form.urgency === u.key }">
                <input type="radio" name="urgency" :value="u.key" v-model="form.urgency">
                <span><strong>{{ $t(u.label) }}</strong><br><span class="fa-hint">{{ $t(u.hint) }}</span></span>
              </label>
            </div>
            <div v-if="errorFor('urgency')" class="fa-err">{{ errorFor('urgency') }}</div>
            <div class="fa-row in-when">
              <div v-if="form.urgency === 'date'" class="fa-field">
                <label for="in-date">{{ $t('Gewenste datum') }} *</label>
                <input id="in-date" type="date" v-model="form.wanted_date" :min="today" :aria-invalid="!!errorFor('wanted_date')">
                <div v-if="errorFor('wanted_date')" class="fa-err">{{ errorFor('wanted_date') }}</div>
              </div>
              <div class="fa-field">
                <label for="in-window">{{ $t('Voorkeurstijd') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
                <input id="in-window" type="text" v-model="form.time_window" maxlength="40" placeholder="9:00–12:00">
                <div v-if="errorFor('time_window')" class="fa-err">{{ errorFor('time_window') }}</div>
              </div>
            </div>
            <div class="fa-actions"><button type="button" class="fa-btn fa-btn--navy" @click="next">{{ $t('Volgende') }}</button></div>
          </div>
        </section>

        <!-- Stap 5: taal -->
        <section :id="'krok-5'" class="fa-card in-step" :class="{ 'is-open': step === 5, 'is-done': step !== 5 && reached >= 5, 'is-locked': reached < 5 }">
          <header class="in-step-head" @click="step !== 5 && go(5)">
            <span class="in-step-n">5</span>
            <div class="in-step-title"><h2 class="fa-h2">{{ $t('In welke taal?') }}</h2><div v-if="step !== 5 && reached >= 5" class="in-step-sum">{{ summary(5) }}</div></div>
            <button v-if="step !== 5 && reached >= 5" type="button" class="fa-link fa-small">{{ $t('Wijzigen') }}</button>
          </header>
          <div v-if="step === 5" class="in-step-body">
            <p class="fa-small fa-muted">{{ $t('We bieden de klus alleen aan bij bedrijven die deze taal spreken.') }}</p>
            <div class="fa-options">
              <label v-for="l in languages" :key="l.code" class="fa-option" :class="{ 'is-on': form.language === l.code }">
                <input type="radio" name="language" :value="l.code" v-model="form.language">
                <span class="flag">{{ l.flag }}</span><span>{{ l.name }}</span>
              </label>
            </div>
            <div v-if="errorFor('language')" class="fa-err">{{ errorFor('language') }}</div>
            <div class="fa-actions"><button type="button" class="fa-btn fa-btn--navy" @click="next">{{ $t('Volgende') }}</button></div>
          </div>
        </section>

        <!-- Stap 6: contact -->
        <section :id="'krok-6'" class="fa-card in-step" :class="{ 'is-open': step === 6, 'is-locked': reached < 6 }">
          <header class="in-step-head">
            <span class="in-step-n">6</span>
            <div class="in-step-title"><h2 class="fa-h2">{{ $t('Contact') }}</h2></div>
          </header>
          <div v-if="step === 6" class="in-step-body">
            <div class="fa-field">
              <label for="in-name">{{ $t('Naam en achternaam') }} *</label>
              <input id="in-name" type="text" v-model="form.customer_name" autocomplete="name" maxlength="120" :aria-invalid="!!errorFor('customer_name')">
              <div v-if="errorFor('customer_name')" class="fa-err">{{ errorFor('customer_name') }}</div>
            </div>
            <div class="fa-field">
              <label for="in-email">{{ $t('E-mailadres') }} *</label>
              <input id="in-email" type="email" v-model="form.customer_email" autocomplete="email" inputmode="email" maxlength="180" :aria-invalid="!!errorFor('customer_email')">
              <div v-if="errorFor('customer_email')" class="fa-err">{{ errorFor('customer_email') }}</div>
              <div class="fa-hint">{{ $t('Hierop krijg je de bevestigingslink en de prijzen van de bedrijven.') }}</div>
            </div>
            <div class="fa-field">
              <label for="in-phone">{{ $t('Telefoonnummer') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
              <input id="in-phone" type="tel" v-model="form.customer_phone" autocomplete="tel" inputmode="tel" maxlength="40" placeholder="+48 …">
              <div v-if="errorFor('customer_phone')" class="fa-err">{{ errorFor('customer_phone') }}</div>
              <div class="fa-hint">{{ $t('Alleen het bedrijf dat je kiest krijgt je nummer. Niemand anders.') }}</div>
            </div>

            <label class="fa-check in-consent">
              <input type="checkbox" v-model="form.consent">
              <span>{{ $t('Ik ga akkoord met het') }} <a :href="route('pl.regulamin')" target="_blank" rel="noopener">{{ $t('regulamin') }}</a> {{ $t('en het verwerken van mijn gegevens om deze aanvraag af te handelen.') }}</span>
            </label>
            <div v-if="errorFor('consent')" class="fa-err">{{ errorFor('consent') }}</div>

            <input name="website" v-model="form.website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
            <Turnstile :sitekey="$page.props.turnstile_sitekey" @verified="v => form['cf-turnstile-response'] = v" @expired="() => form['cf-turnstile-response'] = ''" />
            <div v-if="form.errors['cf-turnstile-response']" class="fa-err">{{ form.errors['cf-turnstile-response'] }}</div>

            <div class="fa-note in-promise">
              <strong>{{ $t('Wat er nu gebeurt') }}</strong>
              <ul class="fa-ticks">
                <li>{{ $t('Je bevestigt je e-mailadres via de link in de mail. Tot die klik sturen we niets naar bedrijven.') }}</li>
                <li>{{ $t('We bieden de klus aan bij maximaal vijf Fachora Verified-bedrijven in de buurt die jouw taal spreken.') }}</li>
                <li>{{ $t('Je ziet hun prijzen op je eigen pagina en kiest zelf. De prijs staat vooraf vast.') }}</li>
                <li>{{ $t('Gratis voor jou als klant. Je adres en nummer gaan alleen naar het bedrijf dat je kiest.') }}</li>
              </ul>
            </div>

            <div class="fa-actions">
              <button type="submit" class="fa-btn" :disabled="form.processing">{{ form.processing ? $t('Bezig met versturen…') : $t('Aanvraag versturen') }}</button>
            </div>
          </div>
        </section>
      </form>
    </template>
  </FachoraShell>
</template>

<style scoped>
.in-head { margin-bottom: 18px; }
.in-trust { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; font-size: 13.5px; color: #334155; }
.in-trust li { display: flex; align-items: center; gap: 6px; }
.in-trust .ic { color: #F4781F; }
.in-step { padding: 0; overflow: hidden; }
.in-step-head { display: flex; align-items: center; gap: 12px; padding: 16px 20px; }
.in-step.is-done .in-step-head { cursor: pointer; }
.in-step.is-locked { opacity: 0.55; }
.in-step-n { flex: 0 0 32px; width: 32px; height: 32px; border-radius: 50%; background: #EEF2F7; color: #0B2545; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; }
.in-step.is-open .in-step-n { background: #0B2545; color: #fff; }
.in-step.is-done .in-step-n { background: #ECFDF5; color: #065F46; }
.in-step-title { flex: 1; min-width: 0; }
.in-step-title .fa-h2 { margin-bottom: 0; font-size: 17px; }
.in-step-sum { font-size: 13.5px; color: #64748B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.in-step-body { padding: 4px 20px 20px; border-top: 1px solid #EEF2F7; padding-top: 16px; }
@media (min-width: 640px) { .in-step-head { padding: 18px 28px; } .in-step-body { padding: 18px 28px 24px; } }
.in-upload { display: inline-flex; align-items: center; gap: 8px; min-height: 48px; padding: 10px 16px; border: 1px dashed #8FC4F2; border-radius: 10px; background: #EAF2FB; color: #0B2545; font-weight: 600; font-size: 14.5px; cursor: pointer; margin-top: 8px; width: 100%; justify-content: center; }
.in-upload input { display: none; }
.in-plus { font-size: 20px; font-weight: 800; line-height: 1; }
.in-gallery { margin-bottom: 4px; }
.in-when { margin-top: 14px; }
.in-consent { margin: 6px 0 4px; }
.in-promise { margin: 18px 0 4px; }
.in-promise strong { display: block; margin-bottom: 8px; }
.in-wait { text-align: center; }
.in-wait-icon { width: 56px; height: 56px; border-radius: 50%; background: #ECFDF5; color: #065F46; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; }
.in-wait .fa-actions { align-items: center; justify-content: center; }
</style>
