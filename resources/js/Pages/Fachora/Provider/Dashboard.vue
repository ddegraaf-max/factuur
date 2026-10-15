<script setup>
/**
 * Fachora-panel van een aangesloten bedrijf: nieuwe aanbiedingen om te
 * prijzen, de opdrachten in uitvoering, afgeronde opdrachten en de
 * beoordelingen. Zonder profiel (provider null) legt de pagina uit hoe je
 * er een claimt of aanmeldt.
 */
import { computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FachoraBadge from '@/Components/FachoraBadge.vue';
import { icon } from '@/fachora-icons.js';
import { money, num } from '@/format.js';
import { t } from '@/i18n';

const props = defineProps({
  provider: { type: Object, default: null },
  offers: { type: Array, default: () => [] },
  active: { type: Array, default: () => [] },
  closed: { type: Array, default: () => [] },
  reviews: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const jobPill = {
  pending: 'pill-draft', open: 'pill-sent', quoted: 'pill-partial', no_match: 'pill-overdue', agreed: 'pill-sent', scheduled: 'pill-sent',
  in_progress: 'pill-partial', done: 'pill-partial', accepted: 'pill-paid', disputed: 'pill-overdue', cancelled: 'pill-cancelled', expired: 'pill-cancelled',
};
const offerPill = { sent: 'pill-sent', responded: 'pill-partial', declined: 'pill-cancelled', chosen: 'pill-paid', rejected: 'pill-draft', expired: 'pill-cancelled' };

const title = (job) => job.title_pl || job.title;
const stars = (rating) => '★'.repeat(Math.round(Number(rating) || 0)) + '☆'.repeat(5 - Math.round(Number(rating) || 0));

/* ---------- beoordelingen beantwoorden ---------- */
// Eén formulier per beoordeling, aangemaakt in setup (niet tijdens het renderen).
const replyForms = Object.fromEntries(props.reviews.map((r) => [r.id, useForm({ text: '' })]));
const replyFor = (review) => replyForms[review.id];
const sendReply = (review) => replyFor(review).post(route('fachora.reviews.reply', review.id), { preserveScroll: true, onSuccess: () => replyFor(review).reset() });

const openJob = (job) => router.visit(route('fachora.jobs.show', job.id));
</script>

<template>
  <Head :title="$t('Opdrachten')" />
  <AppLayout>
    <template #breadcrumb>Fachora / <span class="breadcrumb-current">{{ $t('Opdrachten') }}</span></template>
    <template #topbar-actions>
      <Link v-if="provider" :href="route('fachora.profile')" class="btn btn-secondary btn-sm">{{ $t('Fachora-profiel') }}</Link>
      <Link v-if="provider && provider.status !== 'verified'" :href="route('fachora.verification')" class="btn btn-primary btn-sm">{{ $t('Verificatie') }}</Link>
    </template>

    <!-- Geen profiel: uitleg en de twee ingangen -->
    <template v-if="!provider">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('Fachora voor bedrijven') }}</h1>
          <p class="page-subtitle">{{ $t('Deze administratie is nog niet gekoppeld aan een Fachora-profiel.') }}</p>
        </div>
      </div>
      <div class="card">
        <div class="card-body fx-intro">
          <div class="fx-intro-icon" v-html="icon('shield', 40)"></div>
          <div>
            <h2 class="fx-h2">{{ $t('Ontvang opdrachten van klanten in uw buurt') }}</h2>
            <p>{{ $t('Fachora stuurt klussen van klanten naar gecontroleerde bedrijven. Staat uw bedrijf al in onze database (uit het openbare register)? Claim dan uw profiel met de code uit onze brief of per e-mail. Staat het er nog niet in? Meld uw bedrijf aan.') }}</p>
            <div class="fx-actions">
              <a :href="route('fachora.claim')" class="btn btn-primary">{{ $t('Profiel claimen') }}</a>
              <a :href="route('fachora.apply')" class="btn btn-secondary">{{ $t('Bedrijf aanmelden') }}</a>
            </div>
            <p class="sub">{{ $t('Na het claimen vult u uw profiel aan, doorloopt u de verificatie en ontvangt u het keurmerk Fachora Verified. Pas daarna bieden wij u opdrachten aan.') }}</p>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('Opdrachten') }}</h1>
          <p class="page-subtitle">
            {{ provider.name }}<template v-if="provider.city"> · {{ provider.city }}</template>
            · <FachoraBadge :verified="provider.verified" small />
          </p>
        </div>
        <div class="page-actions">
          <Link :href="route('fachora.profile')" class="btn btn-secondary btn-sm">{{ $t('Fachora-profiel') }}</Link>
          <a :href="provider.url" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">{{ $t('Openbaar profiel') }} ↗</a>
        </div>
      </div>

      <div v-if="flash.error" class="fx-alert fx-alert--err">{{ flash.error }}</div>

      <!-- Statusbalk per status -->
      <div v-if="provider.status === 'claimed'" class="fx-banner fx-banner--info">
        <div v-html="icon('shield', 22)"></div>
        <div>
          <b>{{ $t('Uw profiel is geclaimd, maar nog niet geverifieerd.') }}</b>
          {{ $t('Pas met het keurmerk Fachora Verified bieden wij u opdrachten aan. De verificatie duurt meestal een paar dagen.') }}
          <Link :href="route('fachora.verification')" class="fx-link">{{ $t('Start de verificatie') }} →</Link>
        </div>
      </div>
      <div v-else-if="provider.status === 'pending'" class="fx-banner fx-banner--info">
        <div v-html="icon('clock', 22)"></div>
        <div>
          <b>{{ $t('Verificatie in behandeling.') }}</b>
          {{ $t('Wij controleren uw gegevens en nemen contact op als er nog iets nodig is.') }}
          <Link :href="route('fachora.verification')" class="fx-link">{{ $t('Bekijk de stappen') }} →</Link>
        </div>
      </div>
      <div v-else-if="provider.status === 'verified'" class="fx-banner fx-banner--ok">
        <FachoraBadge />
        <div>
          <template v-if="provider.verified_since">{{ $t('Geverifieerd sinds :date.', { date: provider.verified_since }) }}</template>
          {{ $t('Platformfee: :fee% van het afgesproken bedrag, alleen bij een afgeronde opdracht.', { fee: num(provider.fee_percent, 0) }) }}
        </div>
      </div>
      <div v-else-if="provider.status === 'suspended'" class="fx-banner fx-banner--warn">
        <div v-html="icon('clock', 22)"></div>
        <div>
          <b>{{ $t('Uw profiel is tijdelijk geschorst.') }}</b>
          <template v-if="provider.status_reason"> {{ provider.status_reason }}</template>
          <template v-if="provider.suspended_until"> {{ $t('Tot :date.', { date: provider.suspended_until }) }}</template>
          {{ $t('U ontvangt in deze periode geen nieuwe opdrachten.') }}
        </div>
      </div>
      <div v-else-if="provider.status === 'blocked'" class="fx-banner fx-banner--err">
        <div v-html="icon('shield', 22)"></div>
        <div>
          <b>{{ $t('Uw profiel is geblokkeerd.') }}</b>
          <template v-if="provider.status_reason"> {{ provider.status_reason }}</template>
        </div>
      </div>

      <!-- Cijfers -->
      <div class="fx-tiles">
        <div class="fx-tile">
          <div class="lbl">{{ $t('Beoordeling') }}</div>
          <div class="val"><template v-if="provider.rating_count">{{ num(provider.rating, 1) }} <span class="stars">{{ stars(provider.rating) }}</span></template><template v-else>—</template></div>
          <div class="sub">{{ $t(':n beoordelingen', { n: provider.rating_count || 0 }) }}</div>
        </div>
        <div class="fx-tile">
          <div class="lbl">{{ $t('Afgeronde opdrachten') }}</div>
          <div class="val">{{ provider.jobs_done || 0 }}</div>
        </div>
        <div class="fx-tile">
          <div class="lbl">{{ $t('Open aanbiedingen') }}</div>
          <div class="val">{{ offers.length }}</div>
        </div>
        <div v-if="provider.response_minutes !== null && provider.response_minutes !== undefined" class="fx-tile">
          <div class="lbl">{{ $t('Gemiddelde reactietijd') }}</div>
          <div class="val">{{ provider.response_minutes < 120 ? $t(':n min', { n: provider.response_minutes }) : $t(':n uur', { n: Math.round(provider.response_minutes / 60) }) }}</div>
        </div>
      </div>

      <!-- Nieuwe opdrachten om te prijzen -->
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
          <div>
            <div class="card-title">{{ $t('Nieuwe opdrachten om te prijzen') }}</div>
            <div class="card-subtitle">{{ $t('Reageer snel: de klant ziet de eerste prijzen het eerst en kiest zelf.') }}</div>
          </div>
        </div>
        <div v-if="offers.length" class="table-scroll">
          <table class="data-table">
            <thead>
              <tr><th>{{ $t('Opdracht') }}</th><th>{{ $t('Wanneer') }}</th><th>{{ $t('Taal') }}</th><th>{{ $t('Reageren vóór') }}</th><th>{{ $t('Status') }}</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="o in offers" :key="o.id">
                <td class="cell-primary">
                  {{ title(o.job) }}
                  <div class="sub">{{ o.job.category_name }}<template v-if="o.job.city"> · {{ o.job.city }}</template> · {{ $t('ontvangen :date', { date: o.sent_at }) }}</div>
                </td>
                <td :data-label="$t('Wanneer')">{{ o.job.urgency_label }}<div v-if="o.job.time_window" class="sub">{{ o.job.time_window }}</div></td>
                <td :data-label="$t('Taal')">{{ o.job.language_label }}</td>
                <td :data-label="$t('Reageren vóór')">{{ o.job.offers_deadline || '—' }}</td>
                <td :data-label="$t('Status')"><span :class="['pill', offerPill[o.status]]">{{ o.status_label }}</span><div v-if="o.price !== null" class="sub">{{ money(o.price) }}</div></td>
                <td class="right actions">
                  <Link :href="o.url" class="btn btn-sm" :class="o.status === 'sent' ? 'btn-primary' : 'btn-secondary'">{{ o.status === 'sent' ? $t('Prijs doorgeven') : $t('Prijs aanpassen') }}</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="card-body sub">
          <template v-if="provider.status === 'verified'">{{ $t('Op dit moment geen nieuwe aanbiedingen. U krijgt een e-mail zodra er een opdracht in uw categorie en regio binnenkomt.') }}</template>
          <template v-else>{{ $t('Aanbiedingen ontvangt u zodra uw profiel Fachora Verified is.') }}</template>
        </div>
      </div>

      <!-- In uitvoering -->
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><div class="card-title">{{ $t('In uitvoering') }}</div></div>
        <div v-if="active.length" class="table-scroll">
          <table class="data-table">
            <thead>
              <tr><th>{{ $t('Kenmerk') }}</th><th>{{ $t('Opdracht') }}</th><th>{{ $t('Afspraak') }}</th><th class="right">{{ $t('Bedrag') }}</th><th>{{ $t('Status') }}</th></tr>
            </thead>
            <tbody>
              <tr v-for="j in active" :key="j.id" class="row-link" @click="openJob(j)">
                <td :data-label="$t('Kenmerk')" class="num">{{ j.reference }}</td>
                <td class="cell-primary"><Link :href="route('fachora.jobs.show', j.id)" @click.stop>{{ title(j) }}</Link><div class="sub">{{ j.city }}<template v-if="j.customer_name"> · {{ j.customer_name }}</template></div></td>
                <td :data-label="$t('Afspraak')">{{ j.scheduled_label || '—' }}<div v-if="j.scheduled_window" class="sub">{{ j.scheduled_window }}</div></td>
                <td :data-label="$t('Bedrag')" class="right num">{{ j.agreed_amount !== null ? money(j.agreed_amount) : '—' }}</td>
                <td :data-label="$t('Status')"><span :class="['pill', jobPill[j.status]]">{{ j.status_label }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="card-body sub">{{ $t('Geen opdrachten in uitvoering.') }}</div>
      </div>

      <!-- Afgerond -->
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><div class="card-title">{{ $t('Afgerond en geannuleerd') }}</div></div>
        <div v-if="closed.length" class="table-scroll">
          <table class="data-table">
            <thead>
              <tr><th>{{ $t('Kenmerk') }}</th><th>{{ $t('Opdracht') }}</th><th>{{ $t('Afgerond op') }}</th><th class="right">{{ $t('Bedrag') }}</th><th>{{ $t('Status') }}</th></tr>
            </thead>
            <tbody>
              <tr v-for="j in closed" :key="j.id" class="row-link" @click="openJob(j)">
                <td :data-label="$t('Kenmerk')" class="num">{{ j.reference }}</td>
                <td class="cell-primary"><Link :href="route('fachora.jobs.show', j.id)" @click.stop>{{ title(j) }}</Link><div class="sub">{{ j.city }}</div></td>
                <td :data-label="$t('Afgerond op')">{{ j.accepted_at || '—' }}</td>
                <td :data-label="$t('Bedrag')" class="right num">{{ j.total ? money(j.total) : '—' }}</td>
                <td :data-label="$t('Status')"><span :class="['pill', jobPill[j.status]]">{{ j.status_label }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="card-body sub">{{ $t('Nog geen afgeronde opdrachten.') }}</div>
      </div>

      <!-- Beoordelingen -->
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">{{ $t('Beoordelingen') }}</div>
            <div class="card-subtitle">{{ $t('Alleen klanten met een afgeronde opdracht kunnen beoordelen. U kunt één keer reageren; de reactie staat openbaar bij de beoordeling.') }}</div>
          </div>
        </div>
        <div v-if="reviews.length" class="card-body fx-reviews">
          <div v-for="r in reviews" :key="r.id" class="fx-review">
            <div class="fx-review-head"><span class="stars">{{ stars(r.rating) }}</span> <span class="sub">{{ r.at }}</span></div>
            <p class="fx-review-text">{{ r.text || $t('(zonder tekst)') }}</p>
            <div v-if="r.reply" class="fx-review-reply"><b>{{ $t('Uw reactie') }}:</b> {{ r.reply }}</div>
            <form v-else class="fx-review-form" @submit.prevent="sendReply(r)">
              <textarea v-model="replyFor(r).text" rows="2" maxlength="2000" :placeholder="$t('Reageer kort en zakelijk…')"></textarea>
              <div v-if="replyFor(r).errors.text" class="field-error">{{ replyFor(r).errors.text }}</div>
              <button type="submit" class="btn btn-secondary btn-sm" :disabled="replyFor(r).processing || !replyFor(r).text.trim()">{{ $t('Reactie plaatsen') }}</button>
            </form>
          </div>
        </div>
        <div v-else class="card-body sub">{{ $t('Nog geen beoordelingen.') }}</div>
      </div>
    </template>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.row-link { cursor: pointer; }
.fx-h2 { font-family: var(--font-display); font-size: 20px; font-weight: 600; margin: 0 0 8px; color: var(--text); }
.fx-intro { display: flex; gap: 20px; align-items: flex-start; line-height: 1.6; }
.fx-intro-icon { color: var(--accent, #F4781F); flex: none; }
.fx-intro p { margin: 0 0 14px; max-width: 640px; }
.fx-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
.fx-alert { padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 14px; }
.fx-alert--err { background: var(--brand-tint-2); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.fx-banner { display: flex; gap: 12px; align-items: flex-start; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13.5px; line-height: 1.6; border: 1px solid; }
.fx-banner--info { background: var(--info-bg); border-color: var(--info-border); color: var(--info); }
.fx-banner--ok { background: var(--success-bg); border-color: var(--success-border); color: var(--success); align-items: center; }
.fx-banner--warn { background: var(--warning-bg); border-color: var(--warning-border); color: var(--warning); }
.fx-banner--err { background: var(--brand-tint-2); border-color: var(--brand-border); color: var(--brand-darker); }
.fx-link { font-weight: 600; color: inherit; text-decoration: underline; margin-left: 4px; }
.fx-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 16px; }
.fx-tile { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 14px 16px; }
.fx-tile .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; margin-bottom: 4px; }
.fx-tile .val { font-family: var(--font-display); font-size: 22px; font-weight: 600; color: var(--text); }
.stars { color: var(--accent, #F4781F); letter-spacing: 1px; font-size: 0.85em; }
.actions { white-space: normal; min-width: 140px; }
.fx-reviews { display: flex; flex-direction: column; gap: 14px; }
.fx-review { border-bottom: 1px solid var(--border); padding-bottom: 14px; }
.fx-review:last-child { border-bottom: 0; padding-bottom: 0; }
.fx-review-head { display: flex; gap: 10px; align-items: center; margin-bottom: 4px; }
.fx-review-text { margin: 0 0 8px; line-height: 1.55; font-size: 13.5px; }
.fx-review-reply { font-size: 13px; color: var(--text-2); background: var(--surface-2); padding: 8px 12px; border-radius: 8px; }
.fx-review-form { display: flex; flex-direction: column; gap: 8px; max-width: 560px; }
.fx-review-form textarea { width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font: inherit; font-size: 13px; }
.fx-review-form .btn { align-self: flex-start; }
</style>
