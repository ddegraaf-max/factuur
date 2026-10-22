<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import FachoraShell from '@/Components/FachoraShell.vue';
import FachoraBadge from '@/Components/FachoraBadge.vue';
import { icon } from '@/fachora-icons.js';
import { money } from '@/format.js';
import { t } from '@/i18n';

/**
 * De opdrachtpagina van de klant (/zlecenie/{token}): aanbiedingen vergelijken
 * en kiezen, reserveren via Fachora Protect, de afspraak volgen, meerwerk
 * beoordelen, het werk goedkeuren of een geschil openen, beoordelen. Eén
 * rustige pagina die per status alleen toont wat nu van belang is.
 */
const props = defineProps({
  token: { type: String, required: true },
  job: { type: Object, required: true },
  offers: { type: Array, default: () => [] },
  provider: { type: Object, default: null },
  files: { type: Array, default: () => [] },
  protect_enabled: { type: Boolean, default: false },
  auto_accept_days: { type: Number, default: 5 },
  // 1.86: de klant kiest zelf tot vijf bedrijven ({ candidates, max, fee, selected }) zolang de status 'selecting' is.
  choice: { type: Object, default: null },
});

const job = computed(() => props.job);
const status = computed(() => props.job.status);
const photos = computed(() => props.files.filter((f) => f.kind === 'photo'));
const donePhotos = computed(() => props.files.filter((f) => f.kind === 'done'));
const disputePhotos = computed(() => props.files.filter((f) => f.kind === 'dispute'));

/* ---------- stand van zaken ---------- */
const STEPS = ['offers', 'agreed', 'scheduled', 'in_progress', 'done', 'accepted'];
// 'Klaar|zlecenie' en 'Afgewezen|meerwerk': dezelfde Nederlandse woorden hebben elders een andere Poolse vorm (zie i18n.js).
const STEP_LABELS = { offers: 'Aanbiedingen', agreed: 'Bedrijf gekozen', scheduled: 'Ingepland', in_progress: 'In uitvoering', done: 'Klaar|zlecenie', accepted: 'Afgerond' };
const STEP_INDEX = { selecting: 0, open: 0, quoted: 0, agreed: 1, scheduled: 2, in_progress: 3, done: 4, disputed: 4, accepted: 5 };
const stepIndex = computed(() => STEP_INDEX[status.value] ?? -1);
const statusChip = computed(() => ({
  accepted: 'fa-chip--ok', done: 'fa-chip--ok', disputed: 'fa-chip--warn', cancelled: 'fa-chip--muted', expired: 'fa-chip--muted', no_match: 'fa-chip--warn',
}[status.value] || 'fa-chip--blue'));

const canChoose = computed(() => ['open', 'quoted'].includes(status.value));
const withProvider = computed(() => ['agreed', 'scheduled', 'in_progress', 'done', 'accepted', 'disputed'].includes(status.value) && props.provider);
const RANK = { responded: 0, chosen: 0, sent: 1, rejected: 2, declined: 3, expired: 3 };
const sortedOffers = computed(() => [...props.offers].sort((a, b) => (RANK[a.status] ?? 9) - (RANK[b.status] ?? 9) || (a.price ?? Infinity) - (b.price ?? Infinity)));
const hasPrice = (o) => o.price !== null && o.price !== undefined && ['responded', 'chosen', 'rejected'].includes(o.status);
const pricesIn = computed(() => props.offers.filter(hasPrice).length);
const waiting = computed(() => props.offers.filter((o) => o.status === 'sent').length);

const PRICE_TYPES = { fixed: 'Vaste prijs', hourly: 'Uurtarief', estimate: 'Richtprijs' };
const priceType = (type) => t(PRICE_TYPES[type] || type || '');
const stars = (r) => [1, 2, 3, 4, 5].map((i) => i <= Math.round(Number(r) || 0));
const rating = (r) => (Number(r) || 0).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
const features = (p) => [
  p.invoice ? t('Factuur') : null,
  p.warranty_months ? t(':n maanden garantie', { n: p.warranty_months }) : null,
  p.protect ? 'Fachora Protect' : null,
  p.insured ? t('Verzekerd') : null,
].filter(Boolean);
const cancelledBy = computed(() => ({ customer: t('door jou'), provider: t('door het bedrijf') }[job.value.cancelled_by] || t('door Fachora')));

/* ---------- kiezen ---------- */
const choosing = ref(null);
const chooseForm = useForm({ address_line: props.job.address_line || '', customer_phone: props.job.customer_phone || '', note: '' });
const openChoose = (offer) => { chooseForm.clearErrors(); choosing.value = choosing.value === offer.id ? null : offer.id; };
const choose = () => chooseForm.post(route('fachora.job.choose', [props.token, choosing.value]), { preserveScroll: true });

/* ---------- zelf bedrijven kiezen (1.86) ---------- */
const picked = ref([...(props.choice?.selected || [])]);
const pickMax = computed(() => props.choice?.max || 5);
const togglePick = (id) => {
  const i = picked.value.indexOf(id);
  if (i >= 0) picked.value.splice(i, 1);
  else if (picked.value.length < pickMax.value) picked.value.push(id);
};
const selectExpress = ref(false);
const selecting = ref(false);
const page = usePage();
const selectError = computed(() => (page.props.errors || {}).providers || (page.props.errors || {}).express || null);
const sendSelection = () => {
  selecting.value = true;
  router.post(route('fachora.job.select', props.token), { providers: picked.value, express: selectExpress.value ? 1 : 0 }, { preserveScroll: true, onFinish: () => { selecting.value = false; } });
};

/* ---------- import op aanvraag (1.87): pagina ververst zolang we zoeken ---------- */
let searchTimer = null;
onMounted(() => {
  searchTimer = setInterval(() => {
    if (props.choice?.searching) router.reload({ preserveScroll: true });
  }, 20000);
});
onBeforeUnmount(() => clearInterval(searchTimer));

/* ---------- sms-code (1.87) ---------- */
const smsForm = useForm({ phone: props.choice?.phone || props.job.customer_phone || '' });
const codeForm = useForm({ code: '' });
const sendSms = () => smsForm.post(route('fachora.job.sms.send', props.token), { preserveScroll: true });
const verifySms = () => codeForm.post(route('fachora.job.sms.verify', props.token), { preserveScroll: true, onSuccess: () => codeForm.reset() });

/* ---------- Fachora Protect ---------- */
const paying = ref(false);
// Consumentenrecht: de klant vraagt uitdrukkelijk om uitvoering binnen de bedenktermijn.
const expressOk = ref(false);
const pay = () => {
  paying.value = true;
  router.post(route('fachora.job.pay', props.token), { express: expressOk.value ? 1 : 0 }, { preserveScroll: true, onFinish: () => { paying.value = false; } });
};

/* ---------- meerwerk ---------- */
const EXTRA_LABELS = { proposed: 'Wacht op je akkoord', approved: 'Goedgekeurd', declined: 'Afgewezen|meerwerk' };
const extraLabel = (s) => t(EXTRA_LABELS[s] || s);
const decideExtra = (extra, decision) => router.post(route('fachora.job.extra', [props.token, extra.id]), { decision }, { preserveScroll: true });
const approvedExtras = computed(() => (job.value.extras || []).filter((e) => e.status === 'approved'));

/* ---------- goedkeuren of geschil ---------- */
const accepting = ref(false);
const accept = () => {
  if (!confirm(t('Het werk goedkeuren? Daarna krijgt het bedrijf zijn geld en kun je geen geschil meer openen.'))) return;
  accepting.value = true;
  router.post(route('fachora.job.accept', props.token), {}, { preserveScroll: true, onFinish: () => { accepting.value = false; } });
};
const showDispute = ref(false);
const disputeForm = useForm({ reason: '', photos: [] });
const disputePick = (event) => { disputeForm.photos = Array.from(event.target.files || []).filter((f) => f.type.startsWith('image/')).slice(0, 5); };
const dispute = () => disputeForm.post(route('fachora.job.dispute', props.token), { forceFormData: true, preserveScroll: true, onSuccess: () => { showDispute.value = false; } });
const RESOLUTIONS = { payout: 'Het gereserveerde bedrag is uitbetaald aan het bedrijf.', refund: 'Het gereserveerde bedrag is aan jou terugbetaald.', split: 'Het gereserveerde bedrag is verdeeld tussen jou en het bedrijf.' };

/* ---------- beoordelen ---------- */
const reviewForm = useForm({ rating: 0, text: '' });
const hover = ref(0);
const review = () => reviewForm.post(route('fachora.job.review', props.token), { preserveScroll: true });

/* ---------- annuleren ---------- */
const showCancel = ref(false);
const cancelForm = useForm({ reason: '' });
const cancel = () => cancelForm.post(route('fachora.job.cancel', props.token), { preserveScroll: true, onSuccess: () => { showCancel.value = false; } });
</script>

<template>
  <FachoraShell :title="$t('Opdracht :ref', { ref: job.reference })" noindex>
    <!-- ===== Kop: kenmerk, status, stappen ===== -->
    <div class="fa-card jb-head">
      <div class="jb-head-top">
        <span class="fa-eyebrow">{{ $t('Opdracht') }} <span class="fa-ref">{{ job.reference }}</span></span>
        <span class="fa-chip" :class="statusChip">{{ job.status_label }}</span>
      </div>
      <h1 class="fa-h1">{{ job.title || job.category_name }}</h1>
      <div class="fa-small fa-muted">{{ job.category_name }} · {{ job.city }} · {{ job.urgency_label }}<template v-if="job.time_window"> · {{ job.time_window }}</template></div>

      <ol v-if="stepIndex >= 0" class="jb-steps" :aria-label="$t('Status')">
        <li v-for="(s, i) in STEPS" :key="s" :class="{ 'is-done': i < stepIndex, 'is-now': i === stepIndex }">
          <span class="dot"><template v-if="i < stepIndex">✓</template></span><span class="lbl">{{ $t(STEP_LABELS[s]) }}</span>
        </li>
      </ol>

      <!-- Wat er nu speelt -->
      <div v-if="status === 'selecting'" class="fa-note jb-banner">
        {{ $t('Kies hieronder tot :n bedrijven uit jouw buurt. Alleen zij krijgen je aanvraag; je gegevens zien ze pas als jij een bedrijf kiest.', { n: pickMax }) }}
      </div>
      <div v-else-if="status === 'open'" class="fa-note jb-banner">
        {{ $t('Je aanvraag is aangeboden bij bedrijven in de buurt. De meeste reageren binnen een dag.') }}
        <template v-if="job.offers_deadline"> {{ $t('Reageren kan tot :date.', { date: job.offers_deadline }) }}</template>
        {{ $t('Je krijgt een e-mail bij elke prijs die binnenkomt.') }}
      </div>
      <div v-else-if="status === 'quoted'" class="fa-note jb-banner">
        {{ $t('Je hebt :n prijzen ontvangen. Vergelijk ze rustig en kies het bedrijf dat je het beste ligt.', { n: pricesIn }) }}
        <template v-if="waiting"> {{ $t('Nog :n bedrijven hebben niet gereageerd.', { n: waiting }) }}</template>
      </div>
      <div v-else-if="status === 'no_match'" class="fa-warn jb-banner">
        {{ $t('We hebben nog geen Fachora Verified-bedrijf gevonden dat deze klus in jouw buurt en taal kan doen. We blijven zoeken en mailen je zodra er een reactie is.') }}
      </div>
      <div v-else-if="status === 'agreed'" class="fa-note jb-banner">
        {{ $t('Je hebt :provider gekozen.', { provider: provider?.name || '' }) }}
        <template v-if="job.protect && job.paid"> {{ $t('Het bedrag is gereserveerd; het bedrijf bevestigt nu de afspraak.') }}</template>
        <template v-else-if="job.protect"> {{ $t('Reserveer hieronder het afgesproken bedrag; daarna bevestigt het bedrijf de afspraak.') }}</template>
      </div>
      <div v-else-if="status === 'scheduled'" class="fa-note jb-banner">{{ $t('De afspraak staat. Het bedrijf neemt contact met je op als er nog iets te regelen is.') }}</div>
      <div v-else-if="status === 'in_progress'" class="fa-note jb-banner">{{ $t('Het bedrijf is aan het werk. Meerwerk kan alleen met jouw akkoord op deze pagina.') }}</div>
      <div v-else-if="status === 'done'" class="fa-note jb-banner">{{ $t('Het bedrijf heeft het werk klaar gemeld. Kijk hieronder of alles in orde is.') }}</div>
      <div v-else-if="status === 'accepted'" class="fa-flash jb-banner">{{ $t('Afgerond. Bedankt dat je Fachora hebt gebruikt.') }}</div>
      <div v-else-if="status === 'disputed'" class="fa-warn jb-banner">{{ $t('Er loopt een geschil. Het gereserveerde bedrag blijft staan tot het is opgelost.') }}</div>
      <div v-else-if="status === 'cancelled'" class="jb-banner fa-note">
        {{ $t('Deze opdracht is geannuleerd :by.', { by: cancelledBy }) }}
        <template v-if="job.cancel_reason"> {{ $t('Reden: :reason', { reason: job.cancel_reason }) }}</template>
        <template v-if="job.refunded"> {{ $t('Het gereserveerde bedrag is teruggestort.') }}</template>
      </div>
      <div v-else-if="status === 'expired'" class="jb-banner fa-note">{{ offers.length ? $t('Deze aanvraag is verlopen zonder reactie van bedrijven.') : $t('Deze aanvraag is verlopen: er zijn geen bedrijven gekozen.') }} <a :href="route('fachora.intake')">{{ $t('Nieuwe aanvraag beginnen') }}</a></div>
    </div>

    <!-- ===== Zelf bedrijven kiezen (1.86) ===== -->
    <section v-if="status === 'selecting' && choice" class="jb-section">
      <div v-if="choice.searching" class="fa-card fa-card--tint jb-searching">
        <h2 class="fa-h2">{{ $t('We zoeken vakmensen bij jou in de buurt') }}</h2>
        <p>{{ $t('We kijken in het Poolse bedrijvenregister welke bedrijven rond :city dit werk doen. Dat duurt meestal een paar minuten; je krijgt een e-mail zodra je kunt kiezen. Deze pagina ververst vanzelf.', { city: job.city }) }}</p>
      </div>
      <h2 v-else class="fa-h2">{{ $t('Kies de bedrijven') }} <span class="fa-muted jb-count">{{ picked.length }}/{{ pickMax }}</span></h2>
      <article v-for="p in choice.candidates" :key="p.id" class="fa-card jb-offer jb-pick" :class="{ 'is-open': picked.includes(p.id) }" @click="togglePick(p.id)">
        <div class="jb-prov">
          <label class="jb-pick-box" @click.stop>
            <input type="checkbox" :checked="picked.includes(p.id)" :disabled="!picked.includes(p.id) && picked.length >= pickMax" @change="togglePick(p.id)">
          </label>
          <div class="jb-prov-main">
            <div class="jb-prov-name">
              <strong>{{ p.name }}</strong>
              <FachoraBadge v-if="p.verified" :verified="true" small />
            </div>
            <div class="fa-small fa-muted">
              {{ p.city }}<template v-if="p.distance_km != null"> · {{ $t(':km km', { km: p.distance_km }) }}</template>
              <template v-if="p.rating_count"> · ★ {{ rating(p.rating) }} ({{ p.rating_count }})</template>
              <template v-if="!p.verified"> · {{ $t('uit het bedrijvenregister') }}</template>
            </div>
            <div class="fa-chips jb-prov-chips">
              <span v-for="l in p.languages || []" :key="l.code" class="fa-chip fa-chip--muted">{{ l.flag }} {{ l.name }}</span>
            </div>
          </div>
          <a v-if="p.url" :href="p.url" target="_blank" rel="noopener" class="fa-link fa-small jb-prov-link" @click.stop>{{ $t('Bekijk profiel') }}</a>
        </div>
      </article>

      <div v-if="!choice.searching" class="fa-card">
        <p class="fa-small">{{ $t('Je aanvraag gaat per e-mail naar de bedrijven die je kiest. Zij geven een prijs; jij kiest daarna rustig met wie je verdergaat.') }} <template v-if="!(choice.fee > 0)"><strong>{{ $t('Gratis voor jou.') }}</strong></template></p>
        <template v-if="choice.fee > 0">
          <p class="fa-small"><strong>{{ $t('Kosten: :amount per aanvraag', { amount: money(choice.fee) }) }}</strong> · {{ $t('betalen met BLIK, Przelewy24 of kaart') }}</p>
          <label class="fa-check">
            <input type="checkbox" v-model="selectExpress">
            <span>{{ $t('Ik vraag om mijn aanvraag meteen te versturen. Daarna vervalt het herroepingsrecht voor deze bijdrage.') }}</span>
          </label>
        </template>
        <div v-if="choice.sms_required" class="jb-sms">
          <p class="fa-small"><strong>{{ $t('Bevestig je telefoonnummer') }}</strong> · {{ $t('Zo weten de bedrijven dat je aanvraag echt is. Je nummer zien ze pas als je een bedrijf kiest.') }}</p>
          <div class="fa-row">
            <div class="fa-field">
              <input type="tel" v-model="smsForm.phone" :placeholder="$t('bijv. +48 600 000 000')" autocomplete="tel">
              <div v-if="smsForm.errors.phone" class="fa-err">{{ smsForm.errors.phone }}</div>
            </div>
            <button type="button" class="fa-btn fa-btn--secondary" :disabled="smsForm.processing || !smsForm.phone" @click="sendSms">{{ choice.code_sent ? $t('Nieuwe code') : $t('Stuur code') }}</button>
          </div>
          <div v-if="choice.code_sent" class="fa-row">
            <div class="fa-field">
              <input type="text" inputmode="numeric" maxlength="6" v-model="codeForm.code" :placeholder="$t('Code uit de sms')" autocomplete="one-time-code">
              <div v-if="codeForm.errors.code" class="fa-err">{{ codeForm.errors.code }}</div>
            </div>
            <button type="button" class="fa-btn" :disabled="codeForm.processing || codeForm.code.length < 6" @click="verifySms">{{ $t('Bevestigen') }}</button>
          </div>
        </div>
        <div v-if="selectError" class="fa-err">{{ selectError }}</div>
        <div class="fa-actions">
          <button type="button" class="fa-btn" :disabled="selecting || !picked.length || choice.sms_required || (choice.fee > 0 && !selectExpress)" @click="sendSelection">
            {{ choice.fee > 0 ? $t('Betalen en versturen (:amount)', { amount: money(choice.fee) }) : $t('Aanvraag versturen') }}
          </button>
        </div>
      </div>
    </section>

    <!-- ===== Aanbiedingen ===== -->
    <section v-if="canChoose" class="jb-section">
      <h2 class="fa-h2">{{ $t('Aanbiedingen') }} <span class="fa-muted jb-count">{{ pricesIn }}/{{ offers.length }}</span></h2>
      <p v-if="!offers.length" class="fa-card fa-muted">{{ $t('Nog geen reacties. We laten het je per e-mail weten.') }}</p>

      <article v-for="o in sortedOffers" :key="o.id" class="fa-card jb-offer" :class="{ 'is-off': ['declined', 'expired', 'rejected'].includes(o.status), 'is-open': choosing === o.id }">
        <div class="jb-prov">
          <div class="jb-prov-main">
            <div class="jb-prov-name">
              <strong>{{ o.provider?.name }}</strong>
              <FachoraBadge :verified="!!o.provider?.verified" small />
            </div>
            <div class="fa-stars" v-if="o.provider?.rating_count">
              <span class="st" v-for="(on, i) in stars(o.provider.rating)" :key="i" :class="{ on }">★</span>
              <span>{{ rating(o.provider.rating) }} ({{ o.provider.rating_count }})</span>
              <span v-if="o.provider.jobs_done" class="fa-muted">· {{ $t(':n opdrachten via Fachora', { n: o.provider.jobs_done }) }}</span>
            </div>
            <div v-else class="fa-small fa-muted">{{ $t('Nog geen beoordelingen') }}<template v-if="o.provider?.jobs_done"> · {{ $t(':n opdrachten via Fachora', { n: o.provider.jobs_done }) }}</template></div>
            <div class="fa-chips jb-prov-chips">
              <span v-for="l in o.provider?.languages || []" :key="l.code" class="fa-chip fa-chip--muted">{{ l.flag }} {{ l.name }}</span>
              <span v-for="f in features(o.provider || {})" :key="f" class="fa-chip fa-chip--blue">{{ f }}</span>
            </div>
          </div>
          <a v-if="o.provider?.url" :href="o.provider.url" target="_blank" rel="noopener" class="fa-link fa-small jb-prov-link">{{ $t('Bekijk profiel') }}</a>
        </div>

        <template v-if="hasPrice(o)">
          <hr class="fa-sep">
          <div class="jb-price">
            <div>
              <div class="fa-amount">{{ money(o.price) }}</div>
              <div class="fa-small fa-muted">{{ priceType(o.price_type) }} · {{ $t('bruto, incl. btw') }}<template v-if="o.price_type === 'hourly'">
                <template v-if="o.callout"> · {{ $t('voorrijkosten :amount', { amount: money(o.callout) }) }}</template>
                <template v-if="o.hourly"> · {{ money(o.hourly) }}/h</template>
              </template></div>
            </div>
            <div class="jb-avail">
              <span class="ic" v-html="icon('clock', 18)"></span>
              <span v-if="o.available_label">{{ o.available_label }}<template v-if="o.available_note"> · {{ o.available_note }}</template></span>
              <span v-else>{{ o.available_note || $t('Moment in overleg') }}</span>
            </div>
          </div>
          <p v-if="o.remarks" class="fa-pre fa-small jb-remarks">{{ o.remarks }}</p>
          <div v-if="o.provider?.protect && protect_enabled && o.price_type === 'fixed'" class="fa-small jb-protect-hint"><span class="ic" v-html="icon('shield', 16)"></span>{{ $t('Met Fachora Protect: je reserveert het bedrag en betaalt pas na je akkoord.') }}</div>

          <div v-if="choosing !== o.id" class="fa-actions">
            <button type="button" class="fa-btn" @click="openChoose(o)">{{ $t('Dit bedrijf kiezen') }}</button>
          </div>
          <form v-else @submit.prevent="choose" class="jb-choose">
            <h3 class="fa-h3">{{ $t('Waar moet :provider zijn?', { provider: o.provider?.name || '' }) }}</h3>
            <p class="fa-small fa-muted">{{ $t('Je adres en telefoonnummer gaan alleen naar dit bedrijf. De andere bedrijven horen dat de keuze is gevallen.') }}</p>
            <div class="fa-field">
              <label>{{ $t('Adres (straat en huisnummer)') }} *</label>
              <input type="text" v-model="chooseForm.address_line" maxlength="200" autocomplete="street-address" :placeholder="$t('bijv. ul. Kwiatowa 5/12')" required>
              <div v-if="chooseForm.errors.address_line" class="fa-err">{{ chooseForm.errors.address_line }}</div>
            </div>
            <div class="fa-field">
              <label>{{ $t('Telefoonnummer') }} <span class="fa-hint">{{ $t('optioneel, handig voor de afspraak') }}</span></label>
              <input type="tel" v-model="chooseForm.customer_phone" maxlength="40" autocomplete="tel" placeholder="+48 …">
              <div v-if="chooseForm.errors.customer_phone" class="fa-err">{{ chooseForm.errors.customer_phone }}</div>
            </div>
            <div class="fa-field">
              <label>{{ $t('Bericht aan het bedrijf') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
              <textarea v-model="chooseForm.note" rows="3" maxlength="1000" :placeholder="$t('bijv. bel aan bij nummer 12, derde verdieping')"></textarea>
              <div v-if="chooseForm.errors.note" class="fa-err">{{ chooseForm.errors.note }}</div>
            </div>
            <div class="fa-actions">
              <button type="submit" class="fa-btn" :disabled="chooseForm.processing">{{ $t('Bevestig: :provider voor :amount', { provider: o.provider?.name || '', amount: money(o.price) }) }}</button>
              <button type="button" class="fa-link fa-link--muted" @click="choosing = null">{{ $t('Annuleren') }}</button>
            </div>
          </form>
        </template>
        <template v-else-if="o.status === 'sent'">
          <hr class="fa-sep">
          <div class="fa-small fa-muted jb-wait"><span class="ic" v-html="icon('clock', 16)"></span>{{ $t('Wacht op een prijs') }}<template v-if="o.sent_at"> · {{ $t('aangeboden :date', { date: o.sent_at }) }}</template></div>
        </template>
        <template v-else-if="o.status === 'declined'">
          <hr class="fa-sep">
          <div class="fa-small fa-muted">{{ $t('Dit bedrijf kan de klus niet doen.') }}<template v-if="o.decline_reason"> {{ o.decline_reason }}</template></div>
        </template>
      </article>
    </section>

    <!-- ===== Fachora Protect: reserveren ===== -->
    <section v-if="job.can_pay" class="fa-card fa-card--tint jb-protect">
      <div class="jb-protect-head"><span class="ic" v-html="icon('shield', 26)"></span><h2 class="fa-h2">{{ $t('Reserveer het bedrag via Fachora Protect') }}</h2></div>
      <div class="fa-amount">{{ money(job.total) }}</div>
      <ul class="fa-ticks jb-protect-list">
        <li>{{ $t('Je betaalt nu met BLIK, Przelewy24 of kaart, maar het geld blijft bij Fachora staan.') }}</li>
        <li>{{ $t('Het bedrijf krijgt het pas als jij op deze pagina bevestigt dat het werk goed is gedaan.') }}</li>
        <li>{{ $t('Gaat er iets mis, dan meld je dat hier en blijft het bedrag staan tot het is opgelost.') }}</li>
        <li>{{ $t('Meerwerk alleen met jouw akkoord; de prijs is de prijs.') }}</li>
      </ul>
      <label class="jb-express"><input type="checkbox" v-model="expressOk"> <span>{{ $t('Ik vraag uitdrukkelijk om uitvoering van de dienst binnen de bedenktermijn van 14 dagen en weet dat ik na volledige uitvoering niet meer kan herroepen.') }}</span></label>
      <div v-if="$page.props.errors?.express" class="fa-error">{{ $page.props.errors.express }}</div>
      <div class="fa-actions">
        <button type="button" class="fa-btn" :disabled="paying || !expressOk" @click="pay">{{ paying ? $t('Even geduld…') : $t('Reserveer :amount', { amount: money(job.total) }) }}</button>
      </div>
      <p class="fa-small fa-muted jb-protect-foot">{{ $t('Fachora wordt pas betaald als de klus klaar is; voor jou is de dienst gratis.') }}</p>
    </section>
    <section v-else-if="status === 'agreed' && job.protect && !job.paid" class="fa-card fa-card--warn">
      <strong>{{ $t('Reserveren is op dit moment niet mogelijk.') }}</strong> {{ $t('Probeer het later nog eens of mail ons; we helpen je verder.') }}
    </section>

    <!-- ===== Afspraak, meerwerk ===== -->
    <section v-if="withProvider && ['agreed', 'scheduled', 'in_progress', 'done', 'disputed', 'accepted'].includes(status)" class="fa-card">
      <h2 class="fa-h2">{{ $t('De afspraak') }}</h2>
      <dl class="fa-kv">
        <dt>{{ $t('Bedrijf') }}</dt><dd><strong>{{ provider.name }}</strong> <FachoraBadge :verified="!!provider.verified" small /> <a v-if="provider.url" :href="provider.url" target="_blank" rel="noopener" class="fa-link fa-small">{{ $t('Bekijk profiel') }}</a></dd>
        <dt>{{ $t('Moment') }}</dt><dd><template v-if="job.scheduled_label">{{ job.scheduled_label }}<template v-if="job.scheduled_window"> · {{ job.scheduled_window }}</template></template><template v-else>{{ job.scheduled_window || $t('Het bedrijf neemt contact met je op om een moment af te spreken.') }}</template></dd>
        <dt>{{ $t('Afgesproken prijs') }}</dt><dd><strong>{{ money(job.agreed_amount) }}</strong> <span class="fa-muted">· {{ priceType(job.agreed_type) }} · {{ $t('bruto, incl. btw') }}</span></dd>
        <template v-if="approvedExtras.length"><dt>{{ $t('Met meerwerk') }}</dt><dd><strong>{{ money(job.total) }}</strong></dd></template>
        <template v-if="job.agreed_scope"><dt>{{ $t('Wat erin zit') }}</dt><dd class="fa-pre">{{ job.agreed_scope }}</dd></template>
        <dt>{{ $t('Garantie') }}</dt><dd>{{ job.warranty_months ? $t(':n maanden', { n: job.warranty_months }) : $t('volgens afspraak met het bedrijf') }}</dd>
        <dt>{{ $t('Betaling') }}</dt>
        <dd v-if="job.protect && job.paid"><span class="fa-chip fa-chip--ok">✓ {{ $t('Gereserveerd via Fachora Protect') }}</span> <span v-if="job.paid_at" class="fa-small fa-muted">{{ job.paid_at }}</span></dd>
        <dd v-else-if="job.protect">{{ $t('Via Fachora Protect, nog te reserveren') }}</dd>
        <dd v-else>{{ $t('Rechtstreeks aan het bedrijf, na het werk') }}<template v-if="provider.invoice"> · {{ $t('met factuur') }}</template></dd>
        <template v-if="job.address_line"><dt>{{ $t('Adres') }}</dt><dd>{{ job.address_line }}, {{ job.city }}</dd></template>
        <template v-if="job.started_at"><dt>{{ $t('Gestart') }}</dt><dd>{{ job.started_at }}</dd></template>
      </dl>
      <p class="fa-small fa-muted jb-contact-note">{{ $t('Fachora toont geen telefoonnummers van bedrijven: het bedrijf belt of mailt jou. Zo weet je altijd dat het echt om deze opdracht gaat.') }}</p>

      <template v-if="job.extras?.length">
        <hr class="fa-sep">
        <h3 class="fa-h3">{{ $t('Meerwerk') }}</h3>
        <div v-for="e in job.extras" :key="e.id" class="jb-extra" :class="'is-' + e.status">
          <div class="jb-extra-main">
            <div>{{ e.description }}</div>
            <div class="fa-small"><strong>{{ money(e.amount) }}</strong> · <span :class="{ 'fa-muted': e.status !== 'proposed' }">{{ extraLabel(e.status) }}</span></div>
          </div>
          <div v-if="e.status === 'proposed' && ['scheduled', 'in_progress'].includes(status)" class="jb-extra-actions">
            <button type="button" class="fa-btn fa-btn--sm" @click="decideExtra(e, 'approve')">{{ $t('Akkoord') }}</button>
            <button type="button" class="fa-btn fa-btn--sm fa-btn--secondary" @click="decideExtra(e, 'decline')">{{ $t('Afwijzen') }}</button>
          </div>
        </div>
        <p class="fa-small fa-muted">{{ $t('Alleen goedgekeurd meerwerk komt bovenop de afgesproken prijs.') }}</p>
      </template>

      <div v-if="status === 'in_progress' && !showDispute" class="jb-inline-link">
        <button type="button" class="fa-link fa-link--muted" @click="showDispute = true">{{ $t('Is er een probleem met het werk?') }}</button>
      </div>
    </section>

    <!-- ===== Klaar gemeld: goedkeuren of geschil ===== -->
    <section v-if="status === 'done'" class="fa-card jb-done">
      <h2 class="fa-h2">{{ $t('Is alles in orde?') }}</h2>
      <p class="fa-small fa-muted" v-if="job.done_at">{{ $t('Klaar gemeld op :date', { date: job.done_at }) }}</p>
      <p v-if="job.done_note" class="fa-pre jb-done-note">{{ job.done_note }}</p>
      <div v-if="donePhotos.length" class="fa-gallery jb-gallery">
        <a v-for="f in donePhotos" :key="f.id" :href="f.url" target="_blank" rel="noopener"><img :src="f.url" :alt="f.filename" loading="lazy"></a>
      </div>
      <dl class="fa-kv jb-done-sum">
        <dt>{{ $t('Totaal') }}</dt><dd><strong>{{ money(job.total) }}</strong><template v-if="approvedExtras.length"> <span class="fa-muted fa-small">({{ $t('incl. meerwerk') }})</span></template></dd>
        <dt>{{ $t('Betaling') }}</dt><dd>{{ job.protect && job.paid ? $t('Gereserveerd; wordt na je akkoord uitbetaald aan het bedrijf') : $t('Rechtstreeks aan het bedrijf, na het werk') }}</dd>
      </dl>
      <div class="fa-actions">
        <button type="button" class="fa-btn" :disabled="accepting" @click="accept"><span class="ic" v-html="icon('check', 20)"></span>{{ $t('Ja, het werk is goed gedaan') }}</button>
        <button v-if="!showDispute" type="button" class="fa-btn fa-btn--secondary" @click="showDispute = true">{{ $t('Nee, er is iets mis') }}</button>
      </div>
      <p v-if="job.auto_accept_on" class="fa-small fa-muted jb-auto">{{ $t('Reageer je niet, dan geldt het werk op :date als goedgekeurd (:days dagen na de melding).', { date: job.auto_accept_on, days: auto_accept_days }) }}</p>
    </section>

    <form v-if="showDispute && ['done', 'in_progress', 'scheduled'].includes(status)" @submit.prevent="dispute" class="fa-card fa-card--warn jb-dispute">
      <h2 class="fa-h2">{{ $t('Wat is er mis?') }}</h2>
      <p class="fa-small">{{ $t('Beschrijf zo concreet mogelijk wat niet klopt. Het bedrijf krijgt 48 uur om te reageren; daarna beslist Fachora. Het gereserveerde bedrag blijft intussen staan.') }}</p>
      <div class="fa-field">
        <label>{{ $t('Omschrijving') }} *</label>
        <textarea v-model="disputeForm.reason" rows="5" maxlength="3000" required minlength="10"></textarea>
        <div v-if="disputeForm.errors.reason" class="fa-err">{{ disputeForm.errors.reason }}</div>
      </div>
      <div class="fa-field">
        <label>{{ $t("Foto's") }} <span class="fa-hint">{{ $t('optioneel, maximaal :n', { n: 5 }) }}</span></label>
        <input type="file" accept="image/*" multiple @change="disputePick">
        <div v-if="disputeForm.photos.length" class="fa-hint">{{ $t(':n foto\'s', { n: disputeForm.photos.length }) }}</div>
        <div v-if="disputeForm.errors.photos" class="fa-err">{{ disputeForm.errors.photos }}</div>
      </div>
      <div class="fa-actions">
        <button type="submit" class="fa-btn fa-btn--navy" :disabled="disputeForm.processing">{{ $t('Geschil openen') }}</button>
        <button type="button" class="fa-link fa-link--muted" @click="showDispute = false">{{ $t('Annuleren') }}</button>
      </div>
    </form>

    <!-- ===== Geschil ===== -->
    <section v-if="job.dispute_reason" class="fa-card" :class="{ 'fa-card--warn': status === 'disputed' }">
      <h2 class="fa-h2">{{ $t('Geschil') }}</h2>
      <p class="fa-small fa-muted" v-if="job.dispute_opened_at">{{ $t('Geopend op :date', { date: job.dispute_opened_at }) }}</p>
      <div class="fa-eyebrow">{{ $t('Jouw melding') }}</div>
      <p class="fa-pre">{{ job.dispute_reason }}</p>
      <div v-if="disputePhotos.length" class="fa-gallery jb-gallery">
        <a v-for="f in disputePhotos" :key="f.id" :href="f.url" target="_blank" rel="noopener"><img :src="f.url" :alt="f.filename" loading="lazy"></a>
      </div>
      <div class="fa-eyebrow">{{ $t('Reactie van het bedrijf') }}</div>
      <p v-if="job.dispute_reply" class="fa-pre">{{ job.dispute_reply }}</p>
      <p v-else class="fa-muted">{{ $t('Nog geen reactie. Het bedrijf heeft 48 uur; blijft een reactie uit, dan beslist Fachora in jouw voordeel.') }}</p>
      <template v-if="job.dispute_resolution">
        <div class="fa-eyebrow">{{ $t('Uitkomst') }}</div>
        <p><strong>{{ $t(RESOLUTIONS[job.dispute_resolution] || job.dispute_resolution) }}</strong></p>
        <p v-if="job.dispute_note" class="fa-pre">{{ job.dispute_note }}</p>
      </template>
      <p v-else-if="status === 'disputed'" class="fa-small fa-muted">{{ $t('Fachora bekijkt de melding en de reactie en laat je per e-mail weten wat de uitkomst is.') }}</p>
    </section>

    <!-- ===== Afgerond: overzicht en beoordeling ===== -->
    <section v-if="status === 'accepted'" class="fa-card">
      <h2 class="fa-h2">{{ $t('Overzicht') }}</h2>
      <dl class="fa-kv">
        <dt>{{ $t('Afgerond op') }}</dt><dd>{{ job.accepted_at }}<template v-if="job.accepted_by === 'auto'"> <span class="fa-muted fa-small">({{ $t('automatisch, zonder reactie') }})</span></template></dd>
        <dt>{{ $t('Totaal') }}</dt><dd><strong>{{ money(job.total) }}</strong><template v-if="approvedExtras.length"> <span class="fa-muted fa-small">({{ $t('incl. meerwerk') }})</span></template></dd>
        <dt>{{ $t('Betaling') }}</dt>
        <dd v-if="job.protect && job.paid">{{ $t('Betaald via Fachora Protect.') }} <template v-if="job.payout_at">{{ $t('Uitbetaald aan het bedrijf op :date.', { date: job.payout_at }) }}</template><template v-else>{{ $t('Het bedrijf ontvangt het bedrag binnen enkele dagen.') }}</template></dd>
        <dd v-else>{{ $t('Je betaalt het bedrijf rechtstreeks.') }}<template v-if="provider?.invoice"> {{ $t('Het bedrijf heeft toegezegd een factuur te geven.') }}</template></dd>
        <dt>{{ $t('Garantie') }}</dt><dd>{{ job.warranty_months ? $t(':n maanden vanaf vandaag. Klacht? Mail ons met het kenmerk en we heropenen het dossier.', { n: job.warranty_months }) : $t('volgens afspraak met het bedrijf') }}</dd>
      </dl>

      <hr class="fa-sep">
      <template v-if="job.review">
        <h3 class="fa-h3">{{ $t('Jouw beoordeling') }}</h3>
        <div class="fa-stars"><span class="st" v-for="(on, i) in stars(job.review.rating)" :key="i" :class="{ on }">★</span></div>
        <p v-if="job.review.text" class="fa-pre">{{ job.review.text }}</p>
        <div v-if="job.review.reply" class="jb-reply"><div class="fa-eyebrow">{{ $t('Reactie van het bedrijf') }}</div><p class="fa-pre">{{ job.review.reply }}</p></div>
      </template>
      <form v-else @submit.prevent="review" class="jb-review">
        <h3 class="fa-h3">{{ $t('Hoe was :provider?', { provider: provider?.name || '' }) }}</h3>
        <p class="fa-small fa-muted">{{ $t('Alleen klanten met een afgeronde opdracht kunnen beoordelen. Je beoordeling komt op het profiel van het bedrijf.') }}</p>
        <div class="jb-rate" @mouseleave="hover = 0">
          <button v-for="i in 5" :key="i" type="button" class="jb-rate-btn" :class="{ on: i <= (hover || reviewForm.rating) }" :aria-label="$t(':n van 5 sterren', { n: i })" @mouseenter="hover = i" @click="reviewForm.rating = i">★</button>
        </div>
        <div v-if="reviewForm.errors.rating" class="fa-err">{{ reviewForm.errors.rating }}</div>
        <div class="fa-field">
          <label>{{ $t('Toelichting|opinia') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
          <textarea v-model="reviewForm.text" rows="4" maxlength="2000" :placeholder="$t('Wat ging goed, wat kon beter?')"></textarea>
          <div v-if="reviewForm.errors.text" class="fa-err">{{ reviewForm.errors.text }}</div>
        </div>
        <div class="fa-actions"><button type="submit" class="fa-btn fa-btn--navy" :disabled="reviewForm.processing || !reviewForm.rating">{{ $t('Beoordeling plaatsen') }}</button></div>
      </form>
    </section>

    <!-- ===== De aanvraag ===== -->
    <section class="fa-card">
      <h2 class="fa-h2">{{ $t('Je aanvraag') }}</h2>
      <p class="fa-pre">{{ job.description }}</p>
      <div v-if="photos.length" class="fa-gallery jb-gallery">
        <a v-for="f in photos" :key="f.id" :href="f.url" target="_blank" rel="noopener"><img :src="f.url" :alt="f.filename" loading="lazy"></a>
      </div>
      <dl class="fa-kv jb-req">
        <dt>{{ $t('Categorie') }}</dt><dd>{{ job.category_name }}</dd>
        <dt>{{ $t('Plaats') }}</dt><dd>{{ [job.postal_code, job.city].filter(Boolean).join(' ') }}</dd>
        <dt>{{ $t('Wanneer') }}</dt><dd>{{ job.urgency_label }}<template v-if="job.time_window"> · {{ job.time_window }}</template></dd>
        <dt>{{ $t('Taal') }}</dt><dd>{{ job.language_label }}<template v-if="job.language_fallback"> <span class="fa-small fa-muted">({{ $t('ook aangeboden aan bedrijven die alleen Pools spreken') }})</span></template></dd>
        <template v-if="job.estimate_low && job.estimate_high"><dt>{{ $t('Richtprijs') }}</dt><dd>{{ $t('Indicatie: :low – :high', { low: money(job.estimate_low), high: money(job.estimate_high) }) }} <span class="fa-small fa-muted">({{ $t('automatische schatting op basis van je omschrijving, geen offerte') }})</span></dd></template>
        <dt>{{ $t('Aangevraagd') }}</dt><dd>{{ job.created_at }}</dd>
      </dl>
    </section>

    <!-- ===== Annuleren ===== -->
    <section v-if="job.can_cancel" class="jb-cancel">
      <button v-if="!showCancel" type="button" class="fa-link fa-link--muted" @click="showCancel = true">{{ $t('Opdracht annuleren') }}</button>
      <form v-else @submit.prevent="cancel" class="fa-card">
        <h3 class="fa-h3">{{ $t('Opdracht annuleren?') }}</h3>
        <p class="fa-small fa-muted">{{ $t('Het bedrijf krijgt bericht. Is er al een afspraak, annuleer dan zo vroeg mogelijk; een gereserveerd bedrag wordt teruggestort.') }}</p>
        <div class="fa-field">
          <label>{{ $t('Reden') }} <span class="fa-hint">{{ $t('optioneel') }}</span></label>
          <textarea v-model="cancelForm.reason" rows="2" maxlength="1000"></textarea>
        </div>
        <div class="fa-actions">
          <button type="submit" class="fa-btn fa-btn--danger" :disabled="cancelForm.processing">{{ $t('Ja, annuleren') }}</button>
          <button type="button" class="fa-link fa-link--muted" @click="showCancel = false">{{ $t('Toch niet') }}</button>
        </div>
      </form>
    </section>

    <!-- ===== Verloop ===== -->
    <section v-if="job.events?.length" class="fa-card jb-events">
      <h2 class="fa-h2">{{ $t('Verloop') }}</h2>
      <ol class="fa-timeline">
        <li v-for="(e, i) in [...job.events].reverse()" :key="i">{{ e.description }}<time>{{ e.at }}</time></li>
      </ol>
    </section>
  </FachoraShell>
</template>

<style scoped>
.jb-head-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 4px; }
.jb-steps { list-style: none; margin: 18px 0 0; padding: 0; display: grid; grid-template-columns: repeat(6, 1fr); gap: 4px; }
.jb-steps li { display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; position: relative; }
.jb-steps li::before { content: ''; position: absolute; top: 11px; left: -50%; right: 50%; height: 2px; background: #DCE3EC; z-index: 0; }
.jb-steps li:first-child::before { display: none; }
.jb-steps li.is-done::before, .jb-steps li.is-now::before { background: #0B2545; }
.jb-steps .dot { position: relative; z-index: 1; width: 24px; height: 24px; border-radius: 50%; background: #fff; border: 2px solid #DCE3EC; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: #fff; }
.jb-steps li.is-done .dot { background: #0B2545; border-color: #0B2545; }
.jb-steps li.is-now .dot { border-color: #F4781F; box-shadow: 0 0 0 4px rgba(244, 120, 31, 0.2); }
.jb-steps .lbl { font-size: 10.5px; line-height: 1.2; color: #94A3B8; }
.jb-steps li.is-done .lbl, .jb-steps li.is-now .lbl { color: #0B2545; font-weight: 600; }
@media (min-width: 560px) { .jb-steps .lbl { font-size: 12px; } }
.jb-banner { margin: 18px 0 0; }
.jb-section { margin-bottom: 16px; }
.jb-section .fa-h2 { margin-bottom: 12px; }
.jb-count { font-size: 14px; font-weight: 500; }
.jb-offer.is-off { opacity: 0.6; }
.jb-pick { cursor: pointer; }
.jb-pick-box { display: flex; align-items: flex-start; padding-top: 2px; }
.jb-pick-box input { width: 20px; height: 20px; accent-color: #0B2545; }
.jb-offer.is-open { border-color: #0B2545; box-shadow: 0 0 0 1px #0B2545; }
.jb-prov { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
.jb-prov-main { min-width: 0; flex: 1; }
.jb-prov-name { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 17px; margin-bottom: 4px; }
.jb-prov-chips { margin-top: 8px; }
.jb-prov-link { white-space: nowrap; }
.jb-price { display: flex; flex-direction: column; gap: 10px; }
@media (min-width: 560px) { .jb-price { flex-direction: row; justify-content: space-between; align-items: flex-end; } }
.jb-avail { display: flex; align-items: center; gap: 6px; font-size: 14px; color: #334155; }
.jb-avail .ic { color: #0B2545; }
.jb-remarks { margin-top: 10px; color: #334155; }
.jb-protect-hint { display: flex; align-items: center; gap: 6px; margin-top: 10px; color: #0B2545; }
.jb-wait { display: flex; align-items: center; gap: 6px; }
.jb-choose { margin-top: 16px; padding-top: 16px; border-top: 1px dashed #C5CFDC; }
.jb-protect-head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.jb-protect-head .ic { color: #F4781F; }
.jb-protect-head .fa-h2 { margin: 0; }
.jb-protect-list { margin: 12px 0 4px; }
.jb-protect-foot { margin-top: 10px; }
.jb-contact-note { margin-top: 12px; }
.jb-extra { display: flex; justify-content: space-between; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid #EEF2F7; flex-wrap: wrap; }
.jb-extra.is-declined { opacity: 0.6; text-decoration: line-through; }
.jb-extra-main { flex: 1; min-width: 180px; }
.jb-extra-actions { display: flex; gap: 8px; }
.jb-inline-link { margin-top: 12px; }
.jb-gallery { margin: 12px 0; }
.jb-done-note { background: #F6F8FB; border-radius: 10px; padding: 12px 14px; }
.jb-done-sum { margin: 12px 0; }
.jb-auto { margin-top: 12px; }
.jb-dispute { margin-top: -4px; }
.jb-reply { margin-top: 10px; padding-left: 12px; border-left: 3px solid #8FC4F2; }
.jb-rate { display: flex; gap: 4px; margin: 6px 0 10px; }
.jb-rate-btn { background: none; border: 0; font-size: 36px; line-height: 1; color: #CBD5E1; cursor: pointer; padding: 2px; min-width: 44px; min-height: 44px; }
.jb-rate-btn.on { color: #F4781F; }
.jb-req { margin-top: 12px; }
.jb-cancel { margin: 4px 0 16px; text-align: center; }
.jb-cancel form { text-align: left; }
.jb-express { display: flex; gap: 10px; align-items: flex-start; font-size: 13.5px; line-height: 1.5; margin: 6px 0 12px; }
.jb-express input { width: 18px; height: 18px; margin-top: 2px; flex-shrink: 0; }
.jb-sms { margin: 12px 0; display: grid; gap: 8px; }
.jb-sms .fa-row { align-items: flex-start; }
</style>
