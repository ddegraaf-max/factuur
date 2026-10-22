<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, ref } from 'vue';
import FachoraShell from '@/Components/FachoraShell.vue';
import FachoraBadge from '@/Components/FachoraBadge.vue';
import { icon } from '@/fachora-icons.js';
import { money } from '@/format.js';

/**
 * De aanbieding aan een bedrijf: de klus van de klant (vertaald naar het
 * Pools), de foto's en het formulier voor prijs en beschikbaarheid. Via de
 * geheime link uit de mail (/oferta/{token}) in de openbare schil, of in het
 * panel van het bedrijf (inPanel) binnen de app-layout.
 */
const props = defineProps({
  valid: { type: Boolean, default: false },
  token: { type: String, default: null },
  inPanel: { type: Boolean, default: false },
  offer: { type: Object, default: () => ({}) },
  job: { type: Object, default: () => ({}) },
  provider: { type: Object, default: null },
  files: { type: Array, default: () => [] },
  can_respond: { type: Boolean, default: false },
  protect: { type: Boolean, default: false },
  fee_percent: { type: Number, default: 10 },
  panel_url: { type: String, default: null },
  // 1.86: een bedrijf dat zijn profiel nog niet claimde, gaat eerst akkoord met de voorwaarden.
  needs_terms: { type: Boolean, default: false },
});

// De app-layout alleen laden als hij nodig is: de tokenpagina blijft licht.
const AppLayout = defineAsyncComponent(() => import('@/Layouts/AppLayout.vue'));
const layout = computed(() => (props.inPanel ? AppLayout : FachoraShell));

const page = usePage();
const pageError = computed(() => (page.props.errors || {}).offer ?? null);
const flash = computed(() => (props.inPanel ? page.props.flash?.flash : null));

const title = computed(() => (props.valid ? (props.job.title_pl || props.job.title || props.job.category_name || '') : ''));
const headTitle = computed(() => (props.valid ? title.value : 'Fachora'));

const toLocalInput = (value) => {
  if (!value) return '';
  const d = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  const p = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
};
const minNow = toLocalInput(new Date());

const answered = computed(() => props.offer?.status === 'responded');
const form = useForm({
  price: answered.value && props.offer.price != null ? String(props.offer.price) : '',
  price_type: props.offer?.price_type || 'fixed',
  callout: props.offer?.callout != null ? String(props.offer.callout) : '',
  hourly: props.offer?.hourly != null ? String(props.offer.hourly) : '',
  available_at: toLocalInput(props.offer?.available_at),
  available_note: props.offer?.available_note || '',
  remarks: props.offer?.remarks || '',
  accept_terms: false,
});
const declineForm = useForm({ reason: '' });
const showDecline = ref(false);
const showOriginal = ref(false);

const respondUrl = () => (props.token ? route('fachora.offer.respond', props.token) : route('fachora.offers.respond', props.offer.id));
const declineUrl = () => (props.token ? route('fachora.offer.decline', props.token) : route('fachora.offers.decline', props.offer.id));
const num = (v) => String(v ?? '').replace(/\s/g, '').replace(',', '.');

const submit = () => form
  .transform((d) => ({ ...d, price: num(d.price), callout: d.price_type === 'hourly' ? num(d.callout) : '', hourly: d.price_type === 'hourly' ? num(d.hourly) : '' }))
  .post(respondUrl(), { preserveScroll: true });
const decline = () => declineForm.post(declineUrl(), { preserveScroll: true, onSuccess: () => { showDecline.value = false; } });

const photos = computed(() => props.files.filter((f) => f.kind === 'photo'));
const hasCustomer = computed(() => !!props.job?.customer_name);
const showOriginalToggle = computed(() => props.job?.description_pl && props.job.description && props.job.description !== props.job.description_pl);
const final = computed(() => ['chosen', 'rejected', 'expired'].includes(props.offer?.status) || !props.can_respond);
const claimUrl = computed(() => (props.provider?.slug ? route('fachora.claim', { firma: props.provider.slug }) : route('fachora.claim')));
</script>

<template>
  <Head v-if="inPanel" :title="headTitle" />
  <component :is="layout" v-bind="inPanel ? {} : { title: headTitle, noindex: true }">
    <template v-if="inPanel" #breadcrumb>Fachora / <span class="breadcrumb-current">{{ $t('Aanbieding') }}</span></template>

    <div class="of-wrap" :class="{ 'of-wrap--panel': inPanel }">
      <!-- ===== Ongeldige link ===== -->
      <div v-if="!valid" class="fa-card">
        <h1 class="fa-h1">{{ $t('Deze link is niet (meer) geldig') }}</h1>
        <p class="fa-lead">{{ $t('De aanbieding is verlopen of de link is onvolledig. Open de link uit de laatste e-mail van Fachora, of log in op uw panel.') }}</p>
        <a :href="route('login')" class="fa-btn fa-btn--secondary">{{ $t('Inloggen') }}</a>
      </div>

      <template v-else>
        <!-- ===== De klus ===== -->
        <article class="fa-card">
          <div class="of-top">
            <span class="fa-eyebrow">{{ $t('Opdracht') }} <span class="fa-ref">{{ job.reference }}</span></span>
            <span v-if="job.protect" class="fa-chip fa-chip--navy"><span class="ic" v-html="icon('shield', 13)"></span>Fachora Protect</span>
          </div>
          <h1 class="fa-h1">{{ title }}</h1>
          <div class="fa-chips of-chips">
            <span class="fa-chip fa-chip--blue">{{ job.category_name }}</span>
            <span class="fa-chip"><span class="ic" v-html="icon('pin', 13)"></span>{{ [job.postal_code, job.city].filter(Boolean).join(' ') }}</span>
            <span class="fa-chip"><span class="ic" v-html="icon('clock', 13)"></span>{{ job.urgency_label }}<template v-if="job.time_window"> · {{ job.time_window }}</template></span>
            <span class="fa-chip"><span class="ic" v-html="icon('lang', 13)"></span>{{ job.language_label }}</span>
          </div>

          <p class="fa-pre of-desc">{{ job.description_pl || job.description }}</p>
          <div v-if="showOriginalToggle" class="of-orig">
            <button type="button" class="fa-link fa-link--muted fa-small" @click="showOriginal = !showOriginal">{{ showOriginal ? $t('Verberg de oorspronkelijke tekst') : $t('Toon de oorspronkelijke tekst van de klant') }}</button>
            <p v-if="showOriginal" class="fa-pre fa-small of-orig-text">{{ job.description }}</p>
          </div>

          <div v-if="photos.length" class="fa-gallery of-gallery">
            <a v-for="f in photos" :key="f.id" :href="f.url" target="_blank" rel="noopener"><img :src="f.url" :alt="f.filename" loading="lazy"></a>
          </div>

          <div v-if="job.questions_pl?.length" class="of-questions">
            <div class="fa-eyebrow">{{ $t('Vragen die het waard zijn om te stellen') }}</div>
            <ul>
              <li v-for="(q, i) in job.questions_pl" :key="i">{{ q }}</li>
            </ul>
          </div>

          <dl class="fa-kv of-facts">
            <template v-if="job.estimate_low && job.estimate_high">
              <dt>{{ $t('Richtprijs') }}</dt>
              <dd>{{ $t('Indicatie: :low – :high', { low: money(job.estimate_low), high: money(job.estimate_high) }) }} <span class="fa-small fa-muted">({{ $t('automatische schatting; u bepaalt de prijs') }})</span></dd>
            </template>
            <template v-if="job.wanted_date">
              <dt>{{ $t('Gewenste datum') }}</dt><dd>{{ job.urgency_label }}<template v-if="job.time_window"> · {{ job.time_window }}</template></dd>
            </template>
            <template v-if="job.offers_deadline && can_respond">
              <dt>{{ $t('Reageren vóór') }}</dt><dd><strong>{{ job.offers_deadline }}</strong></dd>
            </template>
          </dl>

          <div v-if="hasCustomer" class="fa-note of-customer">
            <div class="fa-eyebrow">{{ $t('Gegevens van de klant') }}</div>
            <dl class="fa-kv">
              <dt>{{ $t('Naam en achternaam') }}</dt><dd>{{ job.customer_name }}</dd>
              <template v-if="job.customer_phone"><dt>{{ $t('Telefoonnummer') }}</dt><dd><a :href="'tel:' + job.customer_phone">{{ job.customer_phone }}</a></dd></template>
              <template v-if="job.address_line"><dt>{{ $t('Adres') }}</dt><dd>{{ job.address_line }}, {{ [job.postal_code, job.city].filter(Boolean).join(' ') }}</dd></template>
            </dl>
          </div>
          <p v-else class="fa-small fa-muted of-privacy">{{ $t('Adres en telefoonnummer van de klant ziet u pas als de klant uw bedrijf kiest.') }}</p>
        </article>

        <div v-if="flash" class="fa-flash">{{ flash }}</div>
        <div v-if="pageError" class="fa-error">{{ pageError }}</div>

        <!-- ===== Eindstanden ===== -->
        <div v-if="offer.status === 'chosen'" class="fa-card fa-card--tint of-final">
          <h2 class="fa-h2">{{ $t('De klant heeft uw bedrijf gekozen') }}</h2>
          <p>{{ $t('Gefeliciteerd. De gegevens van de klant staan hierboven; neem contact op om het moment te bevestigen.') }}</p>
          <p v-if="offer.price != null"><strong>{{ money(offer.price) }}</strong> <span class="fa-muted">· {{ $t('bruto, incl. btw') }}</span><template v-if="offer.available_label"> · {{ offer.available_label }}</template></p>
          <p v-if="job.protect" class="fa-small">{{ $t('De klant reserveert het bedrag via Fachora Protect; de uitbetaling volgt na zijn akkoord op het werk.') }}</p>
          <div class="fa-actions">
            <a v-if="panel_url" :href="panel_url" class="fa-btn fa-btn--navy">{{ $t('Naar het panel') }}</a>
            <a v-else :href="claimUrl" class="fa-btn fa-btn--navy">{{ $t('Profiel overnemen en de opdracht in het panel volgen') }}</a>
          </div>
        </div>
        <div v-else-if="offer.status === 'rejected'" class="fa-card of-final">
          <h2 class="fa-h2">{{ $t('De klant heeft een ander bedrijf gekozen') }}</h2>
          <p class="fa-muted">{{ $t('Bedankt voor uw prijsopgave. De volgende klus in uw buurt bieden we u graag weer aan.') }}</p>
        </div>
        <div v-else-if="offer.status === 'expired'" class="fa-card of-final">
          <h2 class="fa-h2">{{ $t('De termijn is verstreken') }}</h2>
          <p class="fa-muted">{{ $t('Deze aanbieding is niet meer open.') }}</p>
        </div>
        <div v-else-if="!can_respond" class="fa-card of-final">
          <h2 class="fa-h2">{{ $t('Deze opdracht is niet meer open') }}</h2>
          <p class="fa-muted">{{ offer.status === 'declined' ? $t('U heeft deze opdracht afgezegd.') : $t('De klant heeft de aanvraag gesloten of al een keuze gemaakt.') }}</p>
        </div>

        <!-- ===== Reageren ===== -->
        <template v-if="!final">
          <div v-if="offer.status === 'declined' && !showDecline" class="fa-note">{{ $t('U heeft deze opdracht afgezegd. Toch beschikbaar? Dan kunt u hieronder alsnog een prijs doorgeven.') }}</div>
          <div v-else-if="answered" class="fa-note">{{ $t('Uw prijs is doorgegeven op :date. Zolang de klant niet heeft gekozen kunt u hem hieronder aanpassen.', { date: offer.responded_at || '' }) }}</div>

          <form @submit.prevent="submit" class="fa-card">
            <h2 class="fa-h2">{{ answered ? $t('Prijs aanpassen') : $t('Prijs doorgeven') }}</h2>
            <p class="fa-small fa-muted">{{ $t('De klant ziet uw prijs, uw beschikbaarheid, uw beoordelingen en uw talen. Geen telefoonnummer: de klant kiest op deze pagina.') }}</p>

            <div class="fa-field">
              <label>{{ $t('Soort prijs') }}</label>
              <div class="fa-options of-types">
                <label class="fa-option" :class="{ 'is-on': form.price_type === 'fixed' }"><input type="radio" value="fixed" v-model="form.price_type"><span><strong>{{ $t('Vaste prijs') }}</strong><br><span class="fa-hint">{{ $t('Alles inbegrepen; kan via Fachora Protect') }}</span></span></label>
                <label class="fa-option" :class="{ 'is-on': form.price_type === 'hourly' }"><input type="radio" value="hourly" v-model="form.price_type"><span><strong>{{ $t('Uurtarief') }}</strong><br><span class="fa-hint">{{ $t('Voorrijkosten plus een tarief per uur') }}</span></span></label>
                <label class="fa-option" :class="{ 'is-on': form.price_type === 'estimate' }"><input type="radio" value="estimate" v-model="form.price_type"><span><strong>{{ $t('Richtprijs') }}</strong><br><span class="fa-hint">{{ $t('Definitief na een kijkje ter plaatse') }}</span></span></label>
              </div>
              <div v-if="form.errors.price_type" class="fa-err">{{ form.errors.price_type }}</div>
            </div>

            <div class="fa-row">
              <div class="fa-field">
                <label>{{ form.price_type === 'hourly' ? $t('Verwacht totaal (bruto)') : $t('Prijs (bruto, incl. btw)') }} *</label>
                <div class="fa-money"><input type="text" inputmode="decimal" v-model="form.price" placeholder="0,00" required><span>zł</span></div>
                <div v-if="form.errors.price" class="fa-err">{{ form.errors.price }}</div>
              </div>
              <div class="fa-field">
                <label>{{ $t('Beschikbaar vanaf') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
                <input type="datetime-local" v-model="form.available_at" :min="minNow">
                <div v-if="form.errors.available_at" class="fa-err">{{ form.errors.available_at }}</div>
              </div>
            </div>

            <div v-if="form.price_type === 'hourly'" class="fa-row">
              <div class="fa-field">
                <label>{{ $t('Voorrijkosten') }}</label>
                <div class="fa-money"><input type="text" inputmode="decimal" v-model="form.callout" placeholder="0,00"><span>zł</span></div>
                <div v-if="form.errors.callout" class="fa-err">{{ form.errors.callout }}</div>
              </div>
              <div class="fa-field">
                <label>{{ $t('Tarief per uur') }}</label>
                <div class="fa-money"><input type="text" inputmode="decimal" v-model="form.hourly" placeholder="0,00"><span>zł/h</span></div>
                <div v-if="form.errors.hourly" class="fa-err">{{ form.errors.hourly }}</div>
              </div>
            </div>

            <div class="fa-field">
              <label>{{ $t('Opmerking bij het moment') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
              <input type="text" v-model="form.available_note" maxlength="120" :placeholder="$t('bijv. tussen 9:00 en 12:00, of: ook in het weekend')">
              <div v-if="form.errors.available_note" class="fa-err">{{ form.errors.available_note }}</div>
            </div>
            <div class="fa-field">
              <label>{{ $t('Wat zit erin, wat niet') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
              <textarea v-model="form.remarks" rows="4" maxlength="2000" :placeholder="$t('bijv. inclusief materiaal en afvoer; exclusief onderdelen boven 200 zł')"></textarea>
              <div v-if="form.errors.remarks" class="fa-err">{{ form.errors.remarks }}</div>
            </div>

            <div class="fa-note of-terms">
              <div>{{ $t('Provisie Fachora: :fee% alleen over een uitgevoerde opdracht. Geen abonnement, geen kosten per aanbieding.', { fee: fee_percent }) }}</div>
              <div v-if="protect">{{ $t('De klant reserveert het bedrag via Fachora Protect; de uitbetaling volgt na zijn akkoord op het werk.') }}</div>
            </div>

            <label v-if="needs_terms" class="fa-check of-accept">
              <input type="checkbox" v-model="form.accept_terms">
              <span>{{ $t('Ik ga akkoord met de voorwaarden van Fachora, inclusief de provisie van :fee% over een opdracht die de klant mij gunt.', { fee: fee_percent }) }} <a :href="route('pl.regulamin')" target="_blank" rel="noopener">{{ $t('Voorwaarden') }}</a></span>
            </label>
            <div v-if="form.errors.accept_terms" class="fa-err">{{ form.errors.accept_terms }}</div>

            <div class="fa-actions">
              <button type="submit" class="fa-btn" :disabled="form.processing">{{ answered ? $t('Prijs bijwerken') : $t('Prijs doorgeven') }}</button>
              <button v-if="offer.status !== 'declined'" type="button" class="fa-link fa-link--muted" @click="showDecline = !showDecline">{{ $t('Ik kan deze opdracht niet doen') }}</button>
            </div>
          </form>

          <form v-if="showDecline" @submit.prevent="decline" class="fa-card">
            <h3 class="fa-h3">{{ $t('Afzeggen') }}</h3>
            <p class="fa-small fa-muted">{{ $t('De klant ziet dat u niet kunt; een reden is niet verplicht maar wel netjes.') }}</p>
            <div class="fa-field">
              <label>{{ $t('Reden') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
              <textarea v-model="declineForm.reason" rows="2" maxlength="500" :placeholder="$t('bijv. geen capaciteit deze week')"></textarea>
              <div v-if="declineForm.errors.reason" class="fa-err">{{ declineForm.errors.reason }}</div>
            </div>
            <div class="fa-actions">
              <button type="submit" class="fa-btn fa-btn--secondary" :disabled="declineForm.processing">{{ $t('Afzeggen') }}</button>
              <button type="button" class="fa-link fa-link--muted" @click="showDecline = false">{{ $t('Annuleren') }}</button>
            </div>
          </form>
        </template>

        <!-- ===== Het ontvangende bedrijf ===== -->
        <div v-if="provider" class="of-provider fa-small fa-muted">
          {{ $t('Aangeboden aan') }} <strong>{{ provider.name }}</strong> <FachoraBadge :verified="!!provider.verified" small />
          <template v-if="provider.url"> · <a :href="provider.url" target="_blank" rel="noopener">{{ $t('Bekijk profiel') }}</a></template>
          <template v-if="!panel_url"> · <a :href="claimUrl">{{ $t('Profiel overnemen') }}</a></template>
        </div>
      </template>
    </div>
  </component>
</template>

<style scoped>
.of-wrap--panel { max-width: 760px; }
.of-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 4px; }
.of-chips { margin: 10px 0 16px; }
.of-desc { font-size: 16px; line-height: 1.6; }
.of-orig { margin-top: 8px; }
.of-orig-text { margin-top: 8px; padding: 10px 12px; background: #F6F8FB; border-radius: 10px; color: #334155; }
.of-gallery { margin: 14px 0; }
.of-questions { margin: 16px 0; padding: 12px 14px; background: #F6F8FB; border-radius: 10px; }
.of-questions ul { margin: 4px 0 0; padding: 0; list-style: none; display: grid; gap: 6px; font-size: 14.5px; }
.of-questions li { display: flex; gap: 8px; }
.of-questions li::before { content: '☐'; color: #64748B; }
.of-facts { margin-top: 12px; }
.of-customer { margin-top: 16px; }
.of-privacy { margin-top: 14px; }
.of-final { margin-bottom: 16px; }
.of-types { grid-template-columns: 1fr; }
.of-terms { margin: 4px 0 8px; display: grid; gap: 6px; font-size: 14px; }
.of-provider { text-align: center; margin: 4px 0 16px; }
.of-accept { display: flex; gap: 8px; align-items: flex-start; margin: 4px 0 10px; font-size: 14px; line-height: 1.45; }
.of-accept input { margin-top: 3px; }
</style>
