<script setup>
/**
 * Fachora Verified: de checklist van het bedrijf. Per stap de status en
 * de actie die erbij hoort (sms-code, voorwaarden, Stripe-onboarding,
 * polis uploaden); register en btw lopen automatisch. Onderaan de
 * aanvraag van het keurmerk, de tien regels van de Fachora Standard en de fee.
 */
import { computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FachoraBadge from '@/Components/FachoraBadge.vue';
import { icon } from '@/fachora-icons.js';
import { num, todayLocal } from '@/format.js';
import { t } from '@/i18n';

const props = defineProps({
  provider: Object,
  sms_available: Boolean,
  protect_enabled: Boolean,
  terms_version: String,
  fee_percent: Number,
  intro_fee_percent: Number,
  intro_days: Number,
  insurance_files: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const pageErrors = computed(() => page.props.errors || {});

const checkPill = { pending: 'pill-draft', passed: 'pill-paid', manual: 'pill-paid', failed: 'pill-overdue' };
const checkLabel = { pending: 'Nog open', passed: 'Geslaagd', manual: 'Handmatig gecontroleerd', failed: 'Niet geslaagd' };
const checkIcon = { registry: 'invoice', vat: 'card', email: 'lang', phone: 'clock', terms: 'check', bank: 'card', insurance: 'shield' };
const ok = (c) => ['passed', 'manual'].includes(c.status);

const checks = computed(() => props.provider.checks || []);
const requiredChecks = computed(() => checks.value.filter((c) => c.required));
const doneRequired = computed(() => requiredChecks.value.filter(ok).length);
const canRequest = computed(() => ['claimed', 'pending'].includes(props.provider.status));

/* ---------- acties per stap ---------- */
const sendCode = () => router.post(route('fachora.verification.phone'), {}, { preserveScroll: true });
const codeForm = useForm({ code: '' });
const verifyCode = () => codeForm.post(route('fachora.verification.phone.verify'), { preserveScroll: true, onSuccess: () => codeForm.reset() });

const termsForm = useForm({ terms: false, standard: false });
const acceptTerms = () => termsForm.post(route('fachora.verification.terms'), { preserveScroll: true });

const insuranceForm = useForm({ file: null, until: '' });
const pickInsurance = (event) => { insuranceForm.file = event.target.files?.[0] || null; };
const uploadInsurance = () => insuranceForm.post(route('fachora.verification.insurance'), { forceFormData: true, preserveScroll: true, onSuccess: () => insuranceForm.reset() });

const requestVerification = () => {
  if (!confirm(t('Het keurmerk aanvragen? Wij draaien de automatische controles en nemen contact op voor de rest.'))) return;
  router.post(route('fachora.verification.request'), {}, { preserveScroll: true });
};

// De tien regels van de Fachora Standard (concept §5.5), in volgorde.
const standard = [
  'Legaal geregistreerd en actief bedrijf',
  'Identiteit van de vertegenwoordiger gecontroleerd',
  'Duidelijke prijzen vooraf',
  'Geen meerwerk zonder akkoord van de klant',
  'Afspraak bevestigen, afzeggen alleen met reden',
  'Factuur of bewijs van dienstverlening, altijd',
  'Garantie op het werk waar van toepassing (standaard 12 maanden)',
  'Klachten binnen 48 uur beantwoorden',
  'Betaling volgens de Fachora-regels (Protect)',
  'Minimale beoordeling 4,2 en reactie binnen 24 uur',
];
</script>

<template>
  <Head :title="$t('Verificatie')" />
  <AppLayout>
    <template #breadcrumb>Fachora / <span class="breadcrumb-current">{{ $t('Verificatie') }}</span></template>
    <template #topbar-actions>
      <button v-if="canRequest" class="btn btn-primary btn-sm" @click="requestVerification">{{ provider.status === 'pending' ? $t('Opnieuw indienen') : $t('Fachora Verified aanvragen') }}</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Fachora Verified') }}</h1>
        <p class="page-subtitle">{{ $t('Het keurmerk dat klanten vertrouwen. Pas met het keurmerk bieden wij u opdrachten aan; de administratie blijft gratis zolang u Verified bent.') }}</p>
      </div>
    </div>

    <div v-if="flash.error" class="fx-alert fx-alert--err">{{ flash.error }}</div>

    <!-- Status -->
    <div v-if="provider.status === 'verified'" class="fx-banner fx-banner--ok">
      <FachoraBadge />
      <div>
        <b>{{ $t('U bent Fachora Verified.') }}</b>
        <template v-if="provider.verified_since"> {{ $t('Sinds :date.', { date: provider.verified_since }) }}</template>
        {{ $t('Wij controleren wekelijks het register en de verzekering; houd uw polis geldig.') }}
      </div>
    </div>
    <div v-else-if="provider.status === 'pending'" class="fx-banner fx-banner--info">
      <div v-html="icon('clock', 22)"></div>
      <div>
        <b>{{ $t('Aanvraag ingediend.') }}</b><template v-if="provider.verification_requested_at"> {{ $t('Op :date.', { date: provider.verification_requested_at }) }}</template>
        {{ $t('Wij beoordelen de stappen hieronder en bellen u als er iets mist. Stappen die u zelf kunt afronden, kunt u intussen gewoon doen.') }}
      </div>
    </div>
    <div v-else-if="provider.status === 'suspended' || provider.status === 'blocked'" class="fx-banner fx-banner--warn">
      <div v-html="icon('shield', 22)"></div>
      <div>
        <b>{{ provider.status === 'suspended' ? $t('Uw profiel is tijdelijk geschorst.') : $t('Uw profiel is geblokkeerd.') }}</b>
        <template v-if="provider.status_reason"> {{ provider.status_reason }}</template>
        <template v-if="provider.suspended_until"> {{ $t('Tot :date.', { date: provider.suspended_until }) }}</template>
      </div>
    </div>
    <div v-else class="fx-banner fx-banner--info">
      <div v-html="icon('shield', 22)"></div>
      <div>
        <b>{{ $t(':done van :total verplichte stappen afgerond.', { done: doneRequired, total: requiredChecks.length }) }}</b>
        <template v-if="provider.ready"> {{ $t('Alles staat op groen: dien de aanvraag in, dan keuren wij hem goed.') }}</template>
        <template v-else> {{ $t('U kunt de aanvraag nu al indienen; het keurmerk krijgt u zodra alle verplichte stappen groen zijn.') }}</template>
      </div>
    </div>

    <div class="fx-grid">
      <div class="fx-main">
        <!-- Checklist -->
        <div v-for="c in checks" :key="c.kind" class="card fx-check" :class="{ 'is-ok': ok(c) }">
          <div class="fx-check-head">
            <span class="fx-check-icon" v-html="icon(checkIcon[c.kind] || 'check', 20)"></span>
            <div class="fx-check-title">
              <div class="card-title">{{ c.label }} <span v-if="!c.required" class="fx-opt">{{ $t('optioneel') }}</span></div>
              <div v-if="c.note" class="sub">{{ c.note }}</div>
              <div v-if="c.checked_at" class="sub">{{ $t('gecontroleerd :date', { date: c.checked_at }) }}</div>
            </div>
            <span :class="['pill', checkPill[c.status] || 'pill-draft']">{{ $t(checkLabel[c.status] || c.status) }}</span>
          </div>

          <!-- Per stap de actie -->
          <div v-if="c.kind === 'registry' || c.kind === 'vat'" class="fx-check-body sub">
            {{ c.kind === 'registry' ? $t('Wij controleren automatisch in de btw-lijst (biała lista) of uw NIP actief is. Lukt dat niet, dan kijken wij zelf in CEIDG of KRS.') : $t('Btw-status en de gemelde bankrekeningen uit de biała lista. Een vrijgesteld bedrijf kan ook Verified worden.') }}
          </div>

          <div v-else-if="c.kind === 'email'" class="fx-check-body sub">
            {{ ok(c) ? $t('Bevestigd bij het claimen van uw profiel.') : $t('Bevestig uw e-mailadres via de link in de e-mail die u van ons kreeg bij het claimen.') }}
            <template v-if="provider.email"> ({{ provider.email }})</template>
          </div>

          <div v-else-if="c.kind === 'phone'" class="fx-check-body">
            <template v-if="ok(c)"><span class="sub">{{ provider.phone }}</span></template>
            <template v-else-if="!provider.phone">
              <span class="sub">{{ $t('Vul eerst uw mobiele nummer in op het profiel.') }}</span> <Link :href="route('fachora.profile')" class="fx-link">{{ $t('Naar het profiel') }} →</Link>
            </template>
            <template v-else-if="sms_available">
              <div class="fx-inline">
                <span class="sub">{{ provider.phone }}</span>
                <button type="button" class="btn btn-secondary btn-sm" @click="sendCode">{{ provider.phone_code_sent ? $t('Code opnieuw sturen') : $t('Code per sms sturen') }}</button>
              </div>
              <form v-if="provider.phone_code_sent" class="fx-inline" style="margin-top:10px;" @submit.prevent="verifyCode">
                <input type="text" v-model="codeForm.code" inputmode="numeric" maxlength="10" :placeholder="$t('Code uit de sms')" style="width:160px;">
                <button type="submit" class="btn btn-primary btn-sm" :disabled="codeForm.processing || !codeForm.code.trim()">{{ $t('Bevestigen|code') }}</button>
                <div v-if="codeForm.errors.code || pageErrors.code" class="field-error">{{ codeForm.errors.code || pageErrors.code }}</div>
              </form>
            </template>
            <template v-else>
              <span class="sub">{{ provider.phone }} · {{ $t('Wij bellen u om het nummer te bevestigen.') }}</span>
            </template>
          </div>

          <div v-else-if="c.kind === 'terms'" class="fx-check-body">
            <template v-if="provider.terms_accepted"><span class="sub">{{ $t('Geaccepteerd (versie :version).', { version: terms_version }) }}</span></template>
            <form v-else @submit.prevent="acceptTerms">
              <label class="checkbox-row"><input type="checkbox" v-model="termsForm.terms"> <span>{{ $t('Ik accepteer het regulamin van Fachora (versie :version)', { version: terms_version }) }} <a :href="route('pl.regulamin')" target="_blank" rel="noopener">{{ $t('lezen') }} ↗</a></span></label>
              <label class="checkbox-row"><input type="checkbox" v-model="termsForm.standard"> <span>{{ $t('Ik werk volgens de Fachora Standard (de tien regels hiernaast)') }} <a :href="route('fachora.standard')" target="_blank" rel="noopener">{{ $t('lezen') }} ↗</a></span></label>
              <div v-if="termsForm.errors.terms || termsForm.errors.standard" class="field-error">{{ termsForm.errors.terms || termsForm.errors.standard }}</div>
              <button type="submit" class="btn btn-primary btn-sm" :disabled="termsForm.processing || !termsForm.terms || !termsForm.standard">{{ $t('Accepteren') }}</button>
            </form>
          </div>

          <div v-else-if="c.kind === 'bank'" class="fx-check-body">
            <template v-if="!protect_enabled">
              <span class="sub">{{ $t('Fachora Protect is in deze omgeving nog niet beschikbaar; deze stap is daarom nu niet verplicht.') }}</span>
            </template>
            <template v-else-if="provider.stripe_ready">
              <span class="sub fx-ok">{{ $t('Stripe is ingericht: identiteit gecontroleerd, uitbetalingen staan aan.') }}</span>
            </template>
            <template v-else>
              <p class="sub" style="margin:0 0 10px;line-height:1.6;">{{ $t('Stripe controleert de identiteit van de vertegenwoordiger en de bankrekening op naam van het bedrijf (Fachora Standard 02). Fachora slaat geen identiteitsbewijzen op. Via dezelfde rekening krijgt u de uitbetalingen van Fachora Protect.') }}</p>
              <ul v-if="provider.stripe_due && provider.stripe_due.length" class="fx-due">
                <li v-for="d in provider.stripe_due" :key="d">{{ d }}</li>
              </ul>
              <!-- Gewone link: Stripe-onboarding is een volledige paginawissel, geen Inertia-bezoek. -->
              <a :href="route('fachora.verification.stripe')" class="btn btn-primary btn-sm">{{ provider.stripe_started ? $t('Stripe-onboarding afronden') : $t('Stripe-onboarding starten') }}</a>
            </template>
          </div>

          <div v-else-if="c.kind === 'insurance'" class="fx-check-body">
            <p class="sub" style="margin:0 0 10px;line-height:1.6;">{{ $t('Een aansprakelijkheidsverzekering (OC) is niet verplicht, maar klanten zien het en wij tonen het op uw profiel. Upload de polis; wij lezen verzekeraar, dekking en einddatum en keuren hem goed.') }}</p>
            <div v-if="provider.insurance_until" class="sub" style="margin-bottom:8px;">{{ $t('Geldig tot :date', { date: provider.insurance_until }) }}<template v-if="provider.insurance_note"> · {{ provider.insurance_note }}</template></div>
            <ul v-if="insurance_files.length" class="fx-files">
              <li v-for="f in insurance_files" :key="f.id"><a :href="f.url" target="_blank" rel="noopener">{{ f.filename }}</a></li>
            </ul>
            <form class="fx-inline" @submit.prevent="uploadInsurance">
              <input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="fx-file-input" @change="pickInsurance">
              <label class="sub" style="display:flex;align-items:center;gap:6px;">{{ $t('Geldig tot') }} <input type="date" v-model="insuranceForm.until" :min="todayLocal()" style="width:160px;"></label>
              <button type="submit" class="btn btn-secondary btn-sm" :disabled="insuranceForm.processing || !insuranceForm.file">{{ $t('Polis uploaden') }}</button>
            </form>
            <div v-if="insuranceForm.errors.file || insuranceForm.errors.until" class="field-error">{{ insuranceForm.errors.file || insuranceForm.errors.until }}</div>
          </div>
        </div>

        <!-- Aanvraag -->
        <div class="card fx-request">
          <div class="card-body">
            <div class="card-title" style="margin-bottom:6px;">{{ provider.status === 'verified' ? $t('Keurmerk toegekend') : $t('Het keurmerk aanvragen') }}</div>
            <p class="sub" style="margin:0 0 12px;line-height:1.6;">
              <template v-if="provider.status === 'verified'">{{ $t('Het keurmerk is nooit automatisch: een medewerker van Fachora heeft uw gegevens beoordeeld en goedgekeurd.') }}</template>
              <template v-else-if="provider.ready">{{ $t('Alle verplichte stappen zijn groen. Na uw aanvraag beoordeelt een medewerker van Fachora het dossier; het keurmerk wordt nooit automatisch toegekend.') }}</template>
              <template v-else>{{ $t('Nog niet alle verplichte stappen zijn groen. U kunt de aanvraag al indienen: wij draaien de automatische controles opnieuw en helpen u met de rest.') }}</template>
            </p>
            <button v-if="canRequest" type="button" class="btn btn-primary" @click="requestVerification">{{ provider.status === 'pending' ? $t('Opnieuw indienen') : $t('Fachora Verified aanvragen') }}</button>
          </div>
        </div>
      </div>

      <!-- Rechterkolom: de Standard en de fee -->
      <div class="fx-side">
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Fachora Standard') }}</div>
              <div class="card-subtitle">{{ $t('De tien regels waar elk Verified-bedrijf zich aan houdt.') }}</div>
            </div>
          </div>
          <div class="card-body">
            <ol class="fx-standard"><li v-for="(rule, i) in standard" :key="i">{{ $t(rule) }}</li></ol>
            <p class="sub" style="margin:10px 0 0;line-height:1.6;">{{ $t('Twee gegronde klachten in 90 dagen betekent schorsing; fraude, een no-show na reservering of buiten Protect om werken betekent blokkering.') }}</p>
            <a :href="route('fachora.standard')" target="_blank" rel="noopener" class="fx-link">{{ $t('Volledige tekst') }} ↗</a>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Wat het kost') }}</div></div>
          <div class="card-body">
            <div class="fx-fee"><span class="fx-fee-val">{{ num(fee_percent, 0) }}%</span> <span class="sub">{{ $t('platformfee over een afgeronde opdracht') }}</span></div>
            <div class="fx-fee"><span class="fx-fee-val">{{ num(intro_fee_percent, 0) }}%</span> <span class="sub">{{ $t('de eerste :days dagen na verificatie', { days: intro_days }) }}</span></div>
            <p class="sub" style="margin:10px 0 0;line-height:1.6;">
              {{ $t('Geen abonnement, geen kosten per aanbieding. Alleen bij een opdracht die de klant heeft goedgekeurd; bij Fachora Protect wordt de fee bij de uitbetaling ingehouden.') }}
              <template v-if="provider.status === 'verified'"> {{ $t('Uw huidige tarief: :fee%.', { fee: num(provider.fee_percent, 0) }) }}</template>
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; line-height: 1.5; }
.fx-alert { padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 14px; }
.fx-alert--err { background: var(--brand-tint-2); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.fx-banner { display: flex; gap: 12px; align-items: flex-start; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13.5px; line-height: 1.6; border: 1px solid; }
.fx-banner--info { background: var(--info-bg); border-color: var(--info-border); color: var(--info); }
.fx-banner--ok { background: var(--success-bg); border-color: var(--success-border); color: var(--success); align-items: center; }
.fx-banner--warn { background: var(--warning-bg); border-color: var(--warning-border); color: var(--warning); }
.fx-grid { display: grid; grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); gap: 16px; align-items: start; }
.fx-main, .fx-side { display: flex; flex-direction: column; gap: 12px; min-width: 0; }
.fx-main .card, .fx-side .card { margin: 0; }
@media (max-width: 900px) { .fx-grid { grid-template-columns: 1fr; } }
.fx-check { padding: 14px 18px; border-left: 4px solid var(--border-strong); }
.fx-check.is-ok { border-left-color: var(--success); }
.fx-check-head { display: flex; gap: 12px; align-items: flex-start; }
.fx-check-icon { color: var(--brand); flex: none; margin-top: 2px; }
.fx-check-title { flex: 1; min-width: 0; }
.fx-check-body { margin: 10px 0 0 32px; font-size: 13.5px; }
@media (max-width: 600px) { .fx-check-body { margin-left: 0; } }
.fx-opt { font-size: 11px; font-weight: 500; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.05em; margin-left: 6px; }
.fx-inline { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.fx-inline input[type=text], .fx-inline input[type=date] { height: 32px; padding: 0 10px; border: 1px solid var(--border); border-radius: 6px; font: inherit; font-size: 13px; }
.fx-file-input { width: auto !important; height: auto !important; padding: 0 !important; border: 0 !important; }
.fx-link { color: var(--brand); font-weight: 600; }
.fx-ok { color: var(--success); font-weight: 600; }
.fx-due { margin: 0 0 10px; padding-left: 18px; font-size: 12.5px; color: var(--warning); font-family: var(--font-mono); }
.fx-files { margin: 0 0 10px; padding-left: 18px; font-size: 13px; }
.fx-request { border-color: var(--brand-border); background: var(--brand-tint); }
.fx-standard { margin: 0; padding-left: 22px; font-size: 13px; line-height: 1.7; color: var(--text-2); }
.fx-fee { display: flex; align-items: baseline; gap: 8px; margin-bottom: 4px; }
.fx-fee-val { font-family: var(--font-display); font-size: 22px; font-weight: 700; color: var(--accent, #F4781F); }
</style>
