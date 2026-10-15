<script setup>
/**
 * Eigenaar: één bedrijf. Toestemming (mag het gemaild worden, en waarom),
 * de feiten uit het register, de verificatiestappen met handmatig afvinken,
 * de beslissing (goedkeuren, schorsen, blokkeren, terugzetten), aantekeningen,
 * bestanden, opdrachten en aanbiedingen.
 */
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FachoraBadge from '@/Components/FachoraBadge.vue';
import { money, num, todayLocal } from '@/format.js';
import { t } from '@/i18n';

const props = defineProps({
  provider: Object,
  files: { type: Array, default: () => [] },
  jobs: { type: Array, default: () => [] },
  offers: { type: Array, default: () => [] },
  checkKinds: { type: Array, default: () => [] },
  protect_enabled: { type: Boolean, default: false },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const statusPill = { unclaimed: 'pill-draft', claimed: 'pill-sent', pending: 'pill-partial', verified: 'pill-paid', suspended: 'pill-overdue', blocked: 'pill-cancelled' };
const statusLabel = { unclaimed: 'Niet geclaimd', claimed: 'Geclaimd', pending: 'In verificatie', verified: 'Verified', suspended: 'Geschorst', blocked: 'Geblokkeerd' };
const checkPill = { pending: 'pill-draft', passed: 'pill-paid', manual: 'pill-paid', failed: 'pill-overdue' };
const checkLabel = { pending: 'Nog open', passed: 'Geslaagd', manual: 'Handmatig gecontroleerd', failed: 'Niet geslaagd' };
const jobPill = {
  pending: 'pill-draft', open: 'pill-sent', quoted: 'pill-partial', no_match: 'pill-overdue', agreed: 'pill-sent', scheduled: 'pill-sent',
  in_progress: 'pill-partial', done: 'pill-partial', accepted: 'pill-paid', disputed: 'pill-overdue', cancelled: 'pill-cancelled', expired: 'pill-cancelled',
};
const offerPill = { sent: 'pill-sent', responded: 'pill-partial', declined: 'pill-cancelled', chosen: 'pill-paid', rejected: 'pill-draft', expired: 'pill-cancelled' };

const p = computed(() => props.provider);
const ok = (c) => ['passed', 'manual'].includes(c.status);
const missing = computed(() => (p.value.checks || []).filter((c) => c.required && !ok(c)));
const canApprove = computed(() => ['claimed', 'pending', 'suspended'].includes(p.value.status));
const address = computed(() => [p.value.address_line, [p.value.postal_code, p.value.city].filter(Boolean).join(' ')].filter(Boolean).join(', '));

/* ---------- controles ---------- */
const passCheck = (c) => {
  const note = prompt(t('Handmatig aftekenen: ":label". Aantekening (bijv. "gebeld op 15-10, nummer klopt"):', { label: c.label }), c.note || '');
  if (note === null) return;
  router.post(route('fachora.admin.providers.check', p.value.id), { kind: c.kind, note }, { preserveScroll: true });
};
const recheck = () => router.post(route('fachora.admin.providers.recheck', p.value.id), {}, { preserveScroll: true });

/* ---------- beslissing ---------- */
const approve = () => {
  if (!confirm(t(':name goedkeuren als Fachora Verified? Het bedrijf krijgt bericht, wordt vindbaar en ontvangt opdrachten.', { name: p.value.name }))) return;
  router.post(route('fachora.admin.providers.approve', p.value.id), {}, { preserveScroll: true });
};
const panel = ref(null); // 'suspend' | 'block'
const suspendForm = useForm({ reason: '', until: '' });
const suspend = () => suspendForm.post(route('fachora.admin.providers.suspend', p.value.id), { preserveScroll: true, onSuccess: () => { panel.value = null; suspendForm.reset(); } });
const blockForm = useForm({ reason: '' });
const block = () => {
  if (!confirm(t(':name definitief blokkeren? Het bedrijf verdwijnt van het platform en de NIP kan zich niet opnieuw aanmelden.', { name: p.value.name }))) return;
  blockForm.post(route('fachora.admin.providers.block', p.value.id), { preserveScroll: true, onSuccess: () => { panel.value = null; blockForm.reset(); } });
};
const restore = () => {
  if (!confirm(t('Terugzetten naar "geclaimd" (of "in verificatie") en opnieuw beoordelen?'))) return;
  router.post(route('fachora.admin.providers.restore', p.value.id), {}, { preserveScroll: true });
};

/* ---------- aantekeningen ---------- */
const notesForm = useForm({ notes: props.provider.notes || '' });
const saveNotes = () => notesForm.post(route('fachora.admin.providers.note', p.value.id), { preserveScroll: true });
</script>

<template>
  <Head :title="provider.name" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        {{ $t('Eigenaar') }} / <Link :href="route('fachora.admin.providers')" style="color:var(--text-3);">{{ $t('Fachora: bedrijven') }}</Link> /
        <span class="breadcrumb-current">{{ provider.name }}</span>
      </div>
    </template>
    <template #topbar-actions>
      <button v-if="canApprove" class="btn btn-primary btn-sm" :disabled="!provider.ready" @click="approve">{{ $t('Goedkeuren') }}</button>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('fachora.admin.providers')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">‹ {{ $t('Terug') }}</Link>
        <h1 class="page-title">{{ provider.name }} <span :class="['pill', statusPill[provider.status]]" style="vertical-align:middle;margin-left:8px;">{{ $t(statusLabel[provider.status] || provider.status) }}</span></h1>
        <p class="page-subtitle">
          <template v-if="provider.city">{{ provider.city }} · </template><FachoraBadge :verified="provider.verified" small />
          <template v-if="provider.verified_at"> · {{ $t('Verified sinds :date', { date: provider.verified_at }) }}</template>
          <template v-if="provider.rating_count"> · {{ num(provider.rating, 1) }} ★ ({{ provider.rating_count }})</template>
          · {{ $t(':n opdrachten afgerond', { n: provider.jobs_done || 0 }) }}
        </p>
      </div>
      <div class="page-actions">
        <a :href="provider.url" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">{{ $t('Openbaar profiel') }} ↗</a>
        <button class="btn btn-secondary btn-sm" @click="recheck">{{ $t('Opnieuw controleren') }}</button>
        <button v-if="canApprove" class="btn btn-primary btn-sm" :disabled="!provider.ready" @click="approve">{{ $t('Goedkeuren') }}</button>
        <button v-if="provider.status !== 'suspended' && provider.status !== 'blocked'" class="btn btn-ghost btn-sm" @click="panel = panel === 'suspend' ? null : 'suspend'">{{ $t('Schorsen') }}</button>
        <button v-if="provider.status !== 'blocked'" class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="panel = panel === 'block' ? null : 'block'">{{ $t('Blokkeren') }}</button>
        <button v-if="provider.status === 'suspended' || provider.status === 'blocked'" class="btn btn-secondary btn-sm" @click="restore">{{ $t('Terugzetten') }}</button>
      </div>
    </div>

    <div v-if="flash.error" class="fx-alert fx-alert--err">{{ flash.error }}</div>

    <!-- Schorsen / blokkeren -->
    <form v-if="panel === 'suspend'" class="card fx-panel" @submit.prevent="suspend">
      <div class="card-body">
        <div class="card-title" style="margin-bottom:8px;">{{ $t('Schorsen') }}</div>
        <div class="form-row">
          <div class="form-group"><label>{{ $t('Reden (gaat naar het bedrijf)') }} *</label><input type="text" v-model="suspendForm.reason" maxlength="500"><div v-if="suspendForm.errors.reason" class="field-error">{{ suspendForm.errors.reason }}</div></div>
          <div class="form-group"><label>{{ $t('Tot en met') }} <span class="label-hint">{{ $t('(leeg = tot herbeoordeling)') }}</span></label><input type="date" v-model="suspendForm.until" :min="todayLocal()"><div v-if="suspendForm.errors.until" class="field-error">{{ suspendForm.errors.until }}</div></div>
        </div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn btn-primary btn-sm" :disabled="suspendForm.processing || !suspendForm.reason.trim()">{{ $t('Schorsen') }}</button><button type="button" class="btn btn-secondary btn-sm" @click="panel = null">{{ $t('Annuleren') }}</button></div>
      </div>
    </form>
    <form v-if="panel === 'block'" class="card fx-panel fx-panel--danger" @submit.prevent="block">
      <div class="card-body">
        <div class="card-title" style="margin-bottom:8px;">{{ $t('Blokkeren') }}</div>
        <div class="form-group"><label>{{ $t('Reden (gaat naar het bedrijf)') }} *</label><input type="text" v-model="blockForm.reason" maxlength="500"><div v-if="blockForm.errors.reason" class="field-error">{{ blockForm.errors.reason }}</div></div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn btn-danger btn-sm" :disabled="blockForm.processing || !blockForm.reason.trim()">{{ $t('Definitief blokkeren') }}</button><button type="button" class="btn btn-secondary btn-sm" @click="panel = null">{{ $t('Annuleren') }}</button></div>
      </div>
    </form>

    <div class="fx-grid">
      <div class="fx-main">
        <!-- Toestemming -->
        <div class="fx-consent" :class="provider.consent ? 'on' : 'off'">
          <span class="fx-dot"></span>
          <div>
            <b>{{ provider.consent ? $t('Mag per e-mail benaderd worden') : $t('Geen e-mail en geen sms') }}</b> — {{ provider.consent_reason }}
            <template v-if="provider.consent_source"> · {{ $t('bron') }}: {{ provider.consent_source }}</template>
            <div class="sub">{{ provider.consent ? $t('Uitnodigingen en aanbiedingen mogen; afmelden wint altijd.') : $t('Wel toegestaan: bellen, post met claimcode, het openbare profiel.') }}</div>
          </div>
        </div>

        <!-- Status en reden -->
        <div v-if="provider.status_reason" class="fx-alert fx-alert--warn"><b>{{ $t(statusLabel[provider.status] || provider.status) }}:</b> {{ provider.status_reason }}<template v-if="provider.suspended_until"> · {{ $t('tot :date', { date: provider.suspended_until }) }}</template><template v-if="provider.blocked_at"> · {{ provider.blocked_at }}</template></div>

        <!-- Feiten -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Gegevens') }}</div></div>
          <div class="card-body">
            <dl class="fx-facts">
              <dt>NIP / REGON</dt><dd class="num">{{ provider.nip || '—' }}<template v-if="provider.regon"> / {{ provider.regon }}</template></dd>
              <dt>{{ $t('Officiële naam') }}</dt><dd>{{ provider.legal_name || '—' }}<span v-if="provider.legal_form" class="sub"> · {{ provider.legal_form }}</span></dd>
              <dt>{{ $t('Adres') }}</dt><dd>{{ address || '—' }} <span class="sub">{{ provider.lat !== null && provider.lat !== undefined ? '· ' + $t('gegeocodeerd') : '· ' + $t('geen coördinaten') }}</span></dd>
              <dt>{{ $t('E-mail') }}</dt><dd>{{ provider.email || '—' }}</dd>
              <dt>{{ $t('Telefoon') }}</dt><dd><a v-if="provider.phone" :href="'tel:' + provider.phone">{{ provider.phone }}</a><span v-else>—</span><span v-if="provider.phone_verified" class="sub fx-ok"> · {{ $t('bevestigd') }}</span></dd>
              <dt>{{ $t('Website') }}</dt><dd><a v-if="provider.website" :href="provider.website.startsWith('http') ? provider.website : 'https://' + provider.website" target="_blank" rel="noopener">{{ provider.website }}</a><span v-else>—</span></dd>
              <dt>{{ $t('Bron') }}</dt><dd>{{ provider.source || '—' }}</dd>
              <dt>{{ $t('Register / btw') }}</dt><dd>{{ provider.registry_status || '—' }} / {{ provider.vat_status || '—' }}</dd>
              <dt>{{ $t('Categorieën') }}</dt><dd><span v-for="c in provider.categories" :key="c" class="tagx">{{ c }}</span><span v-if="!provider.categories.length">—</span></dd>
              <dt>{{ $t('Talen') }}</dt><dd><span v-for="l in provider.languages" :key="l.code" class="tagx">{{ l.flag }} {{ l.name }} ({{ l.level }})</span></dd>
              <dt>{{ $t('Werkgebied') }}</dt><dd>{{ provider.radius_km ? provider.radius_km + ' km' : '—' }}</dd>
              <dt>{{ $t('Tarieven') }}</dt><dd v-if="provider.rates">{{ $t('voorrij') }} {{ money(provider.rates.callout || 0) }} · {{ $t('1e uur') }} {{ money(provider.rates.first_hour || 0) }} · {{ $t('volgend uur') }} {{ money(provider.rates.extra_hour || 0) }}</dd><dd v-else>—</dd>
              <dt>{{ $t('Garantie / factuur / Protect') }}</dt><dd>{{ provider.warranty_months ?? 0 }} {{ $t('mnd') }} · {{ provider.invoice ? $t('factuur: ja') : $t('factuur: nee') }} · {{ provider.protect ? $t('Protect klaar') : $t('Protect niet klaar') }}<span v-if="provider.stripe_started && !provider.stripe_ready" class="sub"> ({{ $t('Stripe gestart') }}<template v-if="provider.stripe_due && provider.stripe_due.length">, {{ $t('wacht op') }}: {{ provider.stripe_due.join(', ') }}</template>)</span></dd>
              <dt>{{ $t('Claimcode') }}</dt><dd class="num">{{ provider.claim_code || '—' }}</dd>
              <dt>{{ $t('Claim aangevraagd') }}</dt><dd>{{ provider.claim_requested_at || '—' }}<template v-if="provider.claim_email"> · {{ provider.claim_email }}</template><template v-if="provider.claimed_at"> · {{ $t('geclaimd op :date', { date: provider.claimed_at }) }}</template></dd>
              <dt>{{ $t('Gebruiker') }}</dt><dd><template v-if="provider.user">{{ provider.user.name }} · {{ provider.user.email }}<span v-if="provider.company_id" class="sub"> · {{ $t('administratie') }} #{{ provider.company_id }}</span></template><span v-else>—</span></dd>
              <dt>{{ $t('Verzekering') }}</dt><dd>{{ provider.insurance_until ? $t('geldig tot :date', { date: provider.insurance_until }) : '—' }}<span v-if="provider.insurance_note" class="sub"> · {{ provider.insurance_note }}</span></dd>
            </dl>
            <p v-if="provider.description" class="fx-desc">{{ provider.description }}</p>
          </div>
        </div>

        <!-- Controles -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Verificatiestappen') }}</div>
              <div class="card-subtitle">
                <template v-if="provider.ready">{{ $t('Alle verplichte stappen zijn groen; goedkeuren kan.') }}</template>
                <template v-else>{{ $t('Nog open (verplicht): :list', { list: missing.map((c) => c.label).join(', ') || '—' }) }}</template>
              </div>
            </div>
            <button class="btn btn-secondary btn-sm" @click="recheck">{{ $t('Opnieuw controleren') }}</button>
          </div>
          <div class="table-scroll">
            <table class="data-table">
              <thead><tr><th>{{ $t('Stap') }}</th><th>{{ $t('Status') }}</th><th>{{ $t('Aantekening') }}</th><th>{{ $t('Gecontroleerd') }}</th><th></th></tr></thead>
              <tbody>
                <tr v-for="c in provider.checks" :key="c.kind">
                  <td class="cell-primary">{{ c.label }} <span v-if="!c.required" class="sub">({{ $t('optioneel') }})</span></td>
                  <td :data-label="$t('Status')"><span :class="['pill', checkPill[c.status] || 'pill-draft']">{{ $t(checkLabel[c.status] || c.status) }}</span></td>
                  <td :data-label="$t('Aantekening')" class="sub">{{ c.note || '—' }}</td>
                  <td :data-label="$t('Gecontroleerd')" class="sub">{{ c.checked_at || '—' }}</td>
                  <td class="right actions"><button v-if="c.status !== 'manual'" class="btn btn-ghost btn-sm" @click="passCheck(c)">{{ $t('Handmatig aftekenen') }}</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Opdrachten -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Opdrachten') }}</div></div>
          <table v-if="jobs.length" class="data-table">
            <tbody>
              <tr v-for="j in jobs" :key="j.id">
                <td class="cell-primary"><Link :href="j.url">{{ j.reference }}</Link> · {{ j.title }}</td>
                <td :data-label="$t('Bedrag')" class="right num">{{ j.amount !== null ? money(j.amount) : '—' }}</td>
                <td :data-label="$t('Status')"><span :class="['pill', jobPill[j.status]]">{{ j.status_label }}</span></td>
              </tr>
            </tbody>
          </table>
          <div v-else class="card-body sub">{{ $t('Nog geen opdrachten.') }}</div>
        </div>

        <!-- Aanbiedingen -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Aanbiedingen') }}</div></div>
          <table v-if="offers.length" class="data-table">
            <tbody>
              <tr v-for="o in offers" :key="o.id">
                <td class="cell-primary"><Link v-if="o.url" :href="o.url">{{ o.reference }}</Link><span v-else>{{ o.reference || '—' }}</span><span v-if="o.sent_at" class="sub"> · {{ o.sent_at }}</span></td>
                <td :data-label="$t('Prijs')" class="right num">{{ o.price !== null ? money(o.price) : '—' }}</td>
                <td :data-label="$t('Status')"><span :class="['pill', offerPill[o.status]]">{{ o.status_label }}</span></td>
              </tr>
            </tbody>
          </table>
          <div v-else class="card-body sub">{{ $t('Nog geen aanbiedingen.') }}</div>
        </div>
      </div>

      <div class="fx-side">
        <!-- Aantekeningen -->
        <form class="card" @submit.prevent="saveNotes">
          <div class="card-header"><div class="card-title">{{ $t('Aantekeningen') }} <span class="sub">({{ $t('intern') }})</span></div></div>
          <div class="card-body">
            <textarea v-model="notesForm.notes" rows="6" maxlength="5000" class="fx-notes"></textarea>
            <div v-if="notesForm.errors.notes" class="field-error">{{ notesForm.errors.notes }}</div>
            <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
              <button type="submit" class="btn btn-secondary btn-sm" :disabled="notesForm.processing">{{ $t('Opslaan') }}</button>
              <span v-if="notesForm.recentlySuccessful" class="sub fx-ok">{{ $t('Opgeslagen') }}</span>
            </div>
          </div>
        </form>

        <!-- Bestanden -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Bestanden') }}</div></div>
          <div class="card-body">
            <ul v-if="files.length" class="fx-files">
              <li v-for="f in files" :key="f.id"><span class="tagx">{{ f.kind }}</span> <a :href="f.url" target="_blank" rel="noopener">{{ f.filename }}</a></li>
            </ul>
            <div v-else class="sub">{{ $t('Geen bestanden.') }}</div>
          </div>
        </div>

        <div v-if="!protect_enabled" class="fx-alert fx-alert--warn" style="margin:0;">{{ $t('Fachora Protect staat op deze omgeving uit: de Stripe-stap is niet verplicht.') }}</div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.num { font-family: var(--font-mono); }
.fx-alert { padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 14px; line-height: 1.5; }
.fx-alert--err { background: var(--brand-tint-2); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.fx-alert--warn { background: var(--warning-bg); color: var(--warning); border: 1px solid var(--warning-border); }
.fx-panel { margin-bottom: 16px; border-color: var(--warning-border); }
.fx-panel--danger { border-color: var(--brand-border); }
.fx-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 16px; align-items: start; }
.fx-main, .fx-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.fx-main .card, .fx-side .card { margin: 0; }
@media (max-width: 900px) { .fx-grid { grid-template-columns: 1fr; } }
.fx-consent { display: flex; gap: 12px; align-items: flex-start; padding: 12px 16px; border-radius: 10px; font-size: 13.5px; line-height: 1.55; border: 1px solid; }
.fx-consent.on { background: var(--success-bg); border-color: var(--success-border); color: var(--success); }
.fx-consent.off { background: var(--surface-2); border-color: var(--border-strong); color: var(--text-2); }
.fx-consent .fx-dot { width: 12px; height: 12px; border-radius: 50%; flex: none; margin-top: 4px; background: currentColor; }
.fx-facts { display: grid; grid-template-columns: 180px minmax(0, 1fr); gap: 6px 16px; margin: 0; font-size: 13.5px; }
.fx-facts dt { color: var(--text-3); font-weight: 500; }
.fx-facts dd { margin: 0; word-break: break-word; }
@media (max-width: 600px) { .fx-facts { grid-template-columns: 1fr; gap: 2px 0; } .fx-facts dd { margin-bottom: 8px; } }
.fx-desc { margin: 12px 0 0; font-size: 13px; color: var(--text-2); line-height: 1.6; white-space: pre-line; border-top: 1px solid var(--border); padding-top: 10px; }
.fx-ok { color: var(--success); font-weight: 600; }
.tagx { display: inline-block; font-size: 11px; padding: 2px 7px; border-radius: 999px; background: var(--surface-2); border: 1px solid var(--border); margin: 1px 3px 1px 0; color: var(--text-2); }
.actions { white-space: normal; min-width: 120px; }
.fx-notes { width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font: inherit; font-size: 13px; }
.fx-files { margin: 0; padding-left: 0; list-style: none; font-size: 13px; display: flex; flex-direction: column; gap: 6px; }
</style>
