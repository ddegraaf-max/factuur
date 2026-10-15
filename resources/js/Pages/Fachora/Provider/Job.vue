<script setup>
/**
 * Eén opdracht zoals het bedrijf hem ziet: klant en afspraak (pas na de
 * keuze), de klus zelf, foto's, en de acties die bij de status horen:
 * starten, meerwerk voorstellen, klaar melden, op een geschil reageren.
 */
import { computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { icon } from '@/fachora-icons.js';
import { money } from '@/format.js';
import { t } from '@/i18n';

const props = defineProps({
  provider: Object,
  job: Object,
  offer: { type: Object, default: null },
  files: { type: Array, default: () => [] },
  invoice_url: { type: String, default: null },
  dispute_hours: { type: Number, default: 48 },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const jobPill = {
  pending: 'pill-draft', open: 'pill-sent', quoted: 'pill-partial', no_match: 'pill-overdue', agreed: 'pill-sent', scheduled: 'pill-sent',
  in_progress: 'pill-partial', done: 'pill-partial', accepted: 'pill-paid', disputed: 'pill-overdue', cancelled: 'pill-cancelled', expired: 'pill-cancelled',
};
const typeLabel = { fixed: 'Vaste prijs', hourly: 'Uurtarief', estimate: 'Richtprijs' };
const extraPill = { proposed: 'pill-partial', approved: 'pill-paid', rejected: 'pill-cancelled' };
const extraLabel = { proposed: 'Wacht op de klant', approved: 'Goedgekeurd', rejected: 'Afgewezen|meerwerk' };
const fileKindLabel = { photo: "Foto's van de klant", done: "Foto's bij de oplevering", dispute: "Foto's bij het geschil" };
const stars = (rating) => '★'.repeat(Math.round(Number(rating) || 0)) + '☆'.repeat(5 - Math.round(Number(rating) || 0));

const title = computed(() => props.job.title_pl || props.job.title);
const status = computed(() => props.job.status);
const canWork = computed(() => ['scheduled', 'in_progress'].includes(status.value));
const canCancel = computed(() => ['agreed', 'scheduled', 'in_progress'].includes(status.value));
const hasExtras = computed(() => props.job.total !== null && props.job.agreed_amount !== null && Number(props.job.total) !== Number(props.job.agreed_amount));
const fileGroups = computed(() => {
  const groups = {};
  for (const f of props.files) (groups[f.kind] ||= []).push(f);
  return Object.entries(groups).map(([kind, items]) => ({ kind, label: fileKindLabel[kind] || kind, items }));
});

/* ---------- acties ---------- */
const start = () => {
  if (confirm(t('Melden dat u met de klus begint? De klant krijgt bericht.'))) {
    router.post(route('fachora.jobs.start', props.job.id), {}, { preserveScroll: true });
  }
};
const cancelForm = useForm({ reason: '' });
const cancelJob = () => {
  if (!confirm(t('Deze opdracht afzeggen? Afzeggen zonder goede reden telt mee in uw beoordeling en kan tot schorsing leiden.'))) return;
  cancelForm.post(route('fachora.jobs.cancel', props.job.id), { preserveScroll: true });
};
const extraForm = useForm({ description: '', amount: '' });
const proposeExtra = () => extraForm
  .transform((d) => ({ ...d, amount: Number(String(d.amount).replace(',', '.')) || 0 }))
  .post(route('fachora.jobs.extra', props.job.id), { preserveScroll: true, onSuccess: () => extraForm.reset() });
const doneForm = useForm({ note: '', photos: [] });
const pickPhotos = (event) => { doneForm.photos = Array.from(event.target.files || []).slice(0, 8); };
const markDone = () => {
  if (!confirm(t('Klaar melden? De klant krijgt bericht om het werk goed te keuren. Zonder reactie wordt de opdracht na een paar dagen automatisch goedgekeurd.'))) return;
  doneForm.post(route('fachora.jobs.done', props.job.id), { forceFormData: true, preserveScroll: true, onSuccess: () => doneForm.reset() });
};
const disputeForm = useForm({ text: '' });
const replyDispute = () => disputeForm.post(route('fachora.jobs.dispute', props.job.id), { preserveScroll: true });
</script>

<template>
  <Head :title="job.reference + ' · ' + title" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        Fachora / <Link :href="route('fachora.dashboard')" style="color:var(--text-3);">{{ $t('Opdrachten') }}</Link> /
        <span class="breadcrumb-current">{{ job.reference }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('fachora.dashboard')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">‹ {{ $t('Terug') }}</Link>
        <h1 class="page-title">{{ title }} <span :class="['pill', jobPill[status]]" style="vertical-align:middle;margin-left:8px;">{{ job.status_label }}</span></h1>
        <p class="page-subtitle">
          <span class="num">{{ job.reference }}</span> · {{ job.category_name }}<template v-if="job.city"> · {{ job.postal_code }} {{ job.city }}</template>
          · {{ job.language_label }}<template v-if="job.created_at"> · {{ $t('aangevraagd :date', { date: job.created_at }) }}</template>
        </p>
      </div>
      <div class="page-actions">
        <button v-if="status === 'scheduled'" class="btn btn-primary btn-sm" @click="start">{{ $t('Ik begin met de klus') }}</button>
        <a v-if="job.customer_phone" :href="'tel:' + job.customer_phone" class="btn btn-secondary btn-sm">{{ $t('Bel de klant') }}</a>
      </div>
    </div>

    <div v-if="flash.error" class="fx-alert fx-alert--err">{{ flash.error }}</div>

    <div class="fx-grid">
      <!-- Linkerkolom -->
      <div class="fx-main">
        <!-- Klant en afspraak -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Klant en afspraak') }}</div></div>
          <div class="card-body">
            <dl v-if="job.customer_name" class="fx-facts">
              <dt>{{ $t('Klant') }}</dt><dd>{{ job.customer_name }}</dd>
              <dt>{{ $t('Telefoon') }}</dt><dd><a v-if="job.customer_phone" :href="'tel:' + job.customer_phone">{{ job.customer_phone }}</a><span v-else>—</span></dd>
              <dt>{{ $t('Adres') }}</dt><dd>{{ job.address_line || '—' }}<template v-if="job.postal_code || job.city">, {{ job.postal_code }} {{ job.city }}</template></dd>
              <dt>{{ $t('Afspraak') }}</dt><dd>{{ job.scheduled_label || $t('Nog in te plannen met de klant') }}<span v-if="job.scheduled_window" class="sub"> · {{ job.scheduled_window }}</span></dd>
              <dt>{{ $t('Afgesproken bedrag') }}</dt>
              <dd>
                <template v-if="job.agreed_amount !== null"><b>{{ money(job.agreed_amount) }}</b> <span class="sub">{{ $t(typeLabel[job.agreed_type] || 'Vaste prijs') }} · {{ $t('incl. btw') }}</span></template>
                <template v-else>—</template>
                <div v-if="hasExtras" class="sub">{{ $t('Met goedgekeurd meerwerk: :total', { total: money(job.total) }) }}</div>
                <div v-if="job.agreed_scope" class="sub">{{ job.agreed_scope }}</div>
              </dd>
              <dt>{{ $t('Garantie') }}</dt><dd>{{ job.warranty_months ? $t(':n maanden', { n: job.warranty_months }) : '—' }}</dd>
              <dt>{{ $t('Betaling') }}</dt>
              <dd>
                <template v-if="job.protect">
                  <span v-if="job.paid" class="fx-ok"><span v-html="icon('shield', 14)"></span> {{ $t('Bedrag gereserveerd via Fachora Protect') }}</span>
                  <span v-else class="fx-warn">{{ $t('Fachora Protect: de klant moet het bedrag nog reserveren') }}</span>
                  <div class="sub">{{ $t('Na goedkeuring door de klant betaalt Fachora uit, minus de platformfee.') }}</div>
                </template>
                <template v-else>{{ $t('De klant betaalt rechtstreeks aan u na het werk.') }} <span class="sub">{{ $t('Zonder Fachora Protect, dus zonder Fachora-garantie.') }}</span></template>
              </dd>
            </dl>
            <p v-else class="sub">{{ $t('De klantgegevens zijn alleen zichtbaar zolang de opdracht aan u is toegewezen.') }}</p>
          </div>
        </div>

        <!-- De klus -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('De klus') }}</div></div>
          <div class="card-body">
            <p v-if="job.description_pl" class="fx-text">{{ job.description_pl }}</p>
            <template v-if="job.description && job.description !== job.description_pl">
              <div class="lbl">{{ $t('Oorspronkelijke tekst van de klant') }} ({{ job.language_label }})</div>
              <p class="fx-text sub">{{ job.description }}</p>
            </template>
            <dl class="fx-facts" style="margin-top:12px;">
              <dt>{{ $t('Wanneer') }}</dt><dd>{{ job.urgency_label }}<template v-if="job.time_window"> · {{ job.time_window }}</template></dd>
              <dt v-if="job.estimate_low !== null || job.estimate_high !== null">{{ $t('Prijsindicatie voor de klant') }}</dt>
              <dd v-if="job.estimate_low !== null || job.estimate_high !== null">{{ money(job.estimate_low || 0) }} – {{ money(job.estimate_high || 0) }}</dd>
            </dl>
            <template v-if="job.questions_pl && job.questions_pl.length">
              <div class="lbl" style="margin-top:12px;">{{ $t('Vragen om te stellen vóór de prijs') }}</div>
              <ul class="fx-list"><li v-for="(q, i) in job.questions_pl" :key="i">{{ q }}</li></ul>
            </template>
          </div>
        </div>

        <!-- Foto's -->
        <div v-if="fileGroups.length" class="card">
          <div class="card-header"><div class="card-title">{{ $t("Foto's en bestanden") }}</div></div>
          <div class="card-body">
            <div v-for="g in fileGroups" :key="g.kind" style="margin-bottom:10px;">
              <div class="lbl">{{ $t(g.label) }}</div>
              <div class="fx-photos">
                <a v-for="f in g.items" :key="f.id" :href="f.url" target="_blank" rel="noopener" class="fx-photo" :title="f.filename">
                  <img v-if="f.image" :src="f.url" :alt="f.filename" loading="lazy">
                  <span v-else class="fx-file">{{ f.filename }}</span>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Meerwerk -->
        <div v-if="job.extras.length || canWork" class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Meerwerk') }}</div>
              <div class="card-subtitle">{{ $t('Fachora Standard 04: geen meerwerk zonder akkoord van de klant. Stel het hier voor; de klant keurt het goed op zijn pagina.') }}</div>
            </div>
          </div>
          <table v-if="job.extras.length" class="data-table">
            <tbody>
              <tr v-for="e in job.extras" :key="e.id">
                <td class="cell-primary">{{ e.description }}</td>
                <td :data-label="$t('Bedrag')" class="right num">{{ money(e.amount) }}</td>
                <td :data-label="$t('Status')"><span :class="['pill', extraPill[e.status] || 'pill-draft']">{{ $t(extraLabel[e.status] || e.status) }}</span></td>
              </tr>
            </tbody>
          </table>
          <form v-if="canWork" class="card-body fx-inline-form" @submit.prevent="proposeExtra">
            <div class="form-group" style="flex:2;min-width:200px;margin:0;">
              <label>{{ $t('Omschrijving') }}</label>
              <input type="text" v-model="extraForm.description" maxlength="200" :placeholder="$t('bijv. vervangen van de sifon')">
              <div v-if="extraForm.errors.description" class="field-error">{{ extraForm.errors.description }}</div>
            </div>
            <div class="form-group" style="flex:1;min-width:120px;margin:0;">
              <label>{{ $t('Bedrag (zł, incl. btw)') }}</label>
              <input type="text" inputmode="decimal" v-model="extraForm.amount" placeholder="0,00">
              <div v-if="extraForm.errors.amount" class="field-error">{{ extraForm.errors.amount }}</div>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" :disabled="extraForm.processing || !extraForm.description.trim() || !extraForm.amount">{{ $t('Voorstellen aan de klant') }}</button>
          </form>
        </div>

        <!-- Klaar melden -->
        <div v-if="canWork" class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Klaar melden') }}</div>
              <div class="card-subtitle">{{ $t("Voeg foto's van het resultaat toe: dat voorkomt discussie en versnelt de goedkeuring en de uitbetaling.") }}</div>
            </div>
          </div>
          <form class="card-body" @submit.prevent="markDone">
            <div class="form-group">
              <label>{{ $t('Korte toelichting voor de klant') }}</label>
              <textarea v-model="doneForm.note" rows="3" maxlength="2000"></textarea>
              <div v-if="doneForm.errors.note" class="field-error">{{ doneForm.errors.note }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t("Foto's van het resultaat") }} <span class="label-hint">{{ $t('(max. 8)') }}</span></label>
              <input type="file" accept="image/*" multiple class="fx-file-input" @change="pickPhotos">
              <div v-if="doneForm.photos.length" class="sub">{{ $t(':n foto\'s gekozen', { n: doneForm.photos.length }) }}</div>
              <div v-if="doneForm.errors.photos" class="field-error">{{ doneForm.errors.photos }}</div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="doneForm.processing">{{ $t('Werk klaar melden') }}</button>
          </form>
        </div>

        <!-- Wachten op de klant -->
        <div v-if="status === 'done'" class="fx-banner fx-banner--info">
          <div v-html="icon('clock', 22)"></div>
          <div>
            <b>{{ $t('Gemeld als klaar; de klant beoordeelt het werk.') }}</b>
            <template v-if="job.auto_accept_on"> {{ $t('Zonder reactie wordt de opdracht op :date automatisch goedgekeurd.', { date: job.auto_accept_on }) }}</template>
            <div v-if="job.done_note" class="sub">{{ $t('Uw toelichting') }}: {{ job.done_note }}</div>
          </div>
        </div>

        <!-- Geschil -->
        <div v-if="status === 'disputed' || job.dispute_reason" class="card fx-card-warn">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Geschil') }}</div>
              <div class="card-subtitle" v-if="job.dispute_opened_at">{{ $t('Geopend door de klant op :date', { date: job.dispute_opened_at }) }}</div>
            </div>
          </div>
          <div class="card-body">
            <div class="lbl">{{ $t('Bezwaar van de klant') }}</div>
            <p class="fx-text">{{ job.dispute_reason }}</p>
            <template v-if="job.dispute_reply">
              <div class="lbl">{{ $t('Uw reactie') }}</div>
              <p class="fx-text">{{ job.dispute_reply }}</p>
            </template>
            <form v-else-if="status === 'disputed'" @submit.prevent="replyDispute">
              <p class="fx-note">{{ $t('Fachora Standard 08: reageer binnen :hours uur. Blijft een reactie uit, dan beslist Fachora in het voordeel van de klant. Het gereserveerde bedrag blijft staan tot de beslissing.', { hours: dispute_hours }) }}</p>
              <div class="form-group">
                <label>{{ $t('Uw reactie') }}</label>
                <textarea v-model="disputeForm.text" rows="4" maxlength="3000" :placeholder="$t('Wat is er gebeurd, en wat stelt u voor (herstel, deels terugbetalen)?')"></textarea>
                <div v-if="disputeForm.errors.text" class="field-error">{{ disputeForm.errors.text }}</div>
              </div>
              <button type="submit" class="btn btn-primary" :disabled="disputeForm.processing || disputeForm.text.trim().length < 5">{{ $t('Reactie versturen') }}</button>
            </form>
            <template v-if="job.dispute_resolution">
              <div class="lbl" style="margin-top:12px;">{{ $t('Beslissing van Fachora') }}</div>
              <p class="fx-text">{{ $t({ payout: 'Uitbetaling aan het bedrijf', refund: 'Terugbetaling aan de klant', split: 'Gedeeltelijke uitbetaling' }[job.dispute_resolution] || job.dispute_resolution) }}<template v-if="job.dispute_note"> — {{ job.dispute_note }}</template></p>
            </template>
          </div>
        </div>

        <!-- Afgerond -->
        <div v-if="status === 'accepted'" class="card">
          <div class="card-header"><div class="card-title">{{ $t('Afgerond') }}</div></div>
          <div class="card-body">
            <dl class="fx-facts">
              <dt>{{ $t('Goedgekeurd') }}</dt><dd>{{ job.accepted_at }}<span v-if="job.accepted_by" class="sub"> · {{ $t({ customer: 'door de klant', auto: 'automatisch', owner: 'door Fachora' }[job.accepted_by] || job.accepted_by) }}</span></dd>
              <dt>{{ $t('Totaal') }}</dt><dd><b>{{ money(job.total) }}</b></dd>
              <dt>{{ $t('Uitbetaling') }}</dt>
              <dd>
                <template v-if="job.protect && job.paid">
                  {{ $t('Fachora betaalt uit na aftrek van de platformfee.') }}
                  <template v-if="job.payout_at"> {{ $t('Uitbetaald op :date.', { date: job.payout_at }) }}</template>
                  <template v-else> {{ $t('De overboeking naar uw Stripe-rekening volgt binnen enkele werkdagen.') }}</template>
                </template>
                <template v-else-if="job.refunded">{{ $t('Het bedrag is aan de klant terugbetaald.') }}</template>
                <template v-else>{{ $t('De klant betaalt rechtstreeks aan u; de platformfee wordt apart in rekening gebracht.') }}</template>
              </dd>
              <dt>{{ $t('Factuur') }}</dt>
              <dd>
                <Link v-if="invoice_url" :href="invoice_url">{{ $t('Factuur in de administratie') }} →</Link>
                <span v-else class="sub">{{ $t('Nog geen factuur gekoppeld.') }}</span>
              </dd>
            </dl>
            <template v-if="job.review">
              <div class="lbl" style="margin-top:12px;">{{ $t('Beoordeling') }}</div>
              <div><span class="stars">{{ stars(job.review.rating) }}</span> {{ job.review.text }}</div>
              <div v-if="job.review.reply" class="sub">{{ $t('Uw reactie') }}: {{ job.review.reply }}</div>
              <Link v-else :href="route('fachora.dashboard')" class="sub">{{ $t('Reageren kan op het overzicht') }} →</Link>
            </template>
          </div>
        </div>

        <!-- Geannuleerd -->
        <div v-if="status === 'cancelled'" class="fx-banner fx-banner--warn">
          <div v-html="icon('clock', 22)"></div>
          <div>
            <b>{{ $t('Geannuleerd|opdracht') }}</b><template v-if="job.cancelled_by"> · {{ $t({ customer: 'door de klant', provider: 'door u', owner: 'door Fachora', system: 'automatisch' }[job.cancelled_by] || job.cancelled_by) }}</template>
            <div v-if="job.cancel_reason" class="sub">{{ job.cancel_reason }}</div>
          </div>
        </div>

        <!-- Afzeggen -->
        <div v-if="canCancel" class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Afzeggen|opdracht') }}</div>
              <div class="card-subtitle">{{ $t('Fachora Standard 05: afzeggen alleen met reden. Een no-show of afzeggen zonder goede reden telt mee in uw beoordeling.') }}</div>
            </div>
          </div>
          <form class="card-body fx-inline-form" @submit.prevent="cancelJob">
            <div class="form-group" style="flex:1;min-width:240px;margin:0;">
              <input type="text" v-model="cancelForm.reason" maxlength="1000" :placeholder="$t('Reden voor de klant')">
              <div v-if="cancelForm.errors.reason" class="field-error">{{ cancelForm.errors.reason }}</div>
            </div>
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" :disabled="cancelForm.processing || cancelForm.reason.trim().length < 5">{{ $t('Opdracht afzeggen') }}</button>
          </form>
        </div>
      </div>

      <!-- Rechterkolom -->
      <div class="fx-side">
        <div v-if="offer" class="card">
          <div class="card-header"><div class="card-title">{{ $t('Uw prijsopgave') }}</div></div>
          <div class="card-body">
            <dl class="fx-facts fx-facts--narrow">
              <dt>{{ $t('Prijs') }}</dt><dd><b>{{ offer.price !== null ? money(offer.price) : '—' }}</b> <span class="sub">{{ $t(typeLabel[offer.price_type] || 'Vaste prijs') }}</span></dd>
              <dt v-if="offer.callout !== null">{{ $t('Voorrijkosten') }}</dt><dd v-if="offer.callout !== null">{{ money(offer.callout) }}</dd>
              <dt v-if="offer.hourly !== null">{{ $t('Uurtarief') }}</dt><dd v-if="offer.hourly !== null">{{ money(offer.hourly) }}</dd>
              <dt>{{ $t('Beschikbaar') }}</dt><dd>{{ offer.available_label || '—' }}<div v-if="offer.available_note" class="sub">{{ offer.available_note }}</div></dd>
              <dt v-if="offer.remarks">{{ $t('Opmerkingen') }}</dt><dd v-if="offer.remarks">{{ offer.remarks }}</dd>
              <dt v-if="offer.responded_at">{{ $t('Doorgegeven') }}</dt><dd v-if="offer.responded_at">{{ offer.responded_at }}</dd>
            </dl>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Verloop') }}</div></div>
          <div class="card-body">
            <ol v-if="job.events.length" class="fx-timeline">
              <li v-for="(e, i) in job.events" :key="i">
                <div class="fx-tl-at">{{ e.at }}</div>
                <div class="fx-tl-text">{{ e.description }}<span v-if="e.actor" class="sub"> · {{ $t({ customer: 'klant', provider: 'bedrijf', owner: 'Fachora', system: 'systeem' }[e.actor] || e.actor) }}</span></div>
              </li>
            </ol>
            <div v-else class="sub">{{ $t('Nog geen gebeurtenissen.') }}</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; margin: 0 0 4px; }
.num { font-family: var(--font-mono); }
.fx-alert { padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 14px; }
.fx-alert--err { background: var(--brand-tint-2); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.fx-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 16px; align-items: start; }
.fx-main, .fx-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.fx-main .card, .fx-side .card { margin: 0; }
@media (max-width: 900px) { .fx-grid { grid-template-columns: 1fr; } }
.fx-facts { display: grid; grid-template-columns: 160px minmax(0, 1fr); gap: 8px 16px; margin: 0; font-size: 13.5px; }
.fx-facts--narrow { grid-template-columns: 110px minmax(0, 1fr); }
.fx-facts dt { color: var(--text-3); font-weight: 500; }
.fx-facts dd { margin: 0; color: var(--text); word-break: break-word; }
@media (max-width: 600px) { .fx-facts { grid-template-columns: 1fr; gap: 2px 0; } .fx-facts dd { margin-bottom: 8px; } }
.fx-text { margin: 0 0 10px; line-height: 1.6; white-space: pre-line; font-size: 13.5px; }
.fx-list { margin: 0; padding-left: 18px; line-height: 1.6; font-size: 13.5px; }
.fx-note { font-size: 13px; color: var(--warning); background: var(--warning-bg); border: 1px solid var(--warning-border); padding: 8px 12px; border-radius: 8px; margin: 0 0 12px; line-height: 1.55; }
.fx-ok { color: var(--success); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
.fx-warn { color: var(--warning); font-weight: 600; }
.fx-banner { display: flex; gap: 12px; align-items: flex-start; padding: 12px 16px; border-radius: 10px; font-size: 13.5px; line-height: 1.6; border: 1px solid; }
.fx-banner--info { background: var(--info-bg); border-color: var(--info-border); color: var(--info); }
.fx-banner--warn { background: var(--warning-bg); border-color: var(--warning-border); color: var(--warning); }
.fx-card-warn { border-color: var(--warning-border); }
.fx-photos { display: flex; flex-wrap: wrap; gap: 8px; }
.fx-photo { display: block; width: 110px; height: 110px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); background: var(--surface-2); }
.fx-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.fx-file { display: flex; align-items: center; justify-content: center; height: 100%; padding: 8px; font-size: 11px; text-align: center; word-break: break-all; color: var(--text-2); }
.fx-inline-form { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
.fx-file-input { width: auto !important; height: auto !important; padding: 0 !important; border: 0 !important; }
.stars { color: var(--accent, #F4781F); letter-spacing: 1px; }
.fx-timeline { list-style: none; margin: 0; padding: 0 0 0 14px; border-left: 2px solid var(--border); display: flex; flex-direction: column; gap: 10px; }
.fx-timeline li { position: relative; font-size: 13px; }
.fx-timeline li::before { content: ''; position: absolute; left: -19px; top: 5px; width: 8px; height: 8px; border-radius: 50%; background: var(--brand); }
.fx-tl-at { font-size: 11px; color: var(--text-3); font-family: var(--font-mono); }
.fx-tl-text { line-height: 1.5; }
</style>
