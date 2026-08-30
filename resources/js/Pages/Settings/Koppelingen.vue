<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { t } from '@/i18n';

const props = defineProps({
  mcp: Object,     // { active, url }
  has_ai: Boolean, // AI-toegang (Slim, proef of vrijgesteld)
  peppol: Object,  // { configured, status, verification_url, participant_id, registered_at_label, verified_at_label, blockers }
  mail_domain: Object, // { configured, status, domain, from_address, records, checked_at_label, default_from, suggested_domain, suggested_local_part }
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const brand = computed(() => page.props.brand);
// Markt (nl/pl): e-facturatie via Peppol (NL) of KSeF (PL).
const market = page.props.market;

/* ---------- Eigen afzenderadres (Resend Domains) ---------- */
const domainForm = useForm({ domain: props.mail_domain?.suggested_domain || '', local_part: props.mail_domain?.suggested_local_part || 'facturen' });
const domainRefresh = useForm({});
const domainDisconnect = useForm({});
const connectDomain = () => domainForm.post(route('settings.integrations.maildomain.connect'), { preserveScroll: true });
const refreshDomain = () => domainRefresh.post(route('settings.integrations.maildomain.refresh'), { preserveScroll: true });
const disconnectDomain = () => {
  if (confirm(t('Eigen afzenderadres loskoppelen? Mail gaat dan weer uit via :domain.', { domain: brand.value.domain }))) domainDisconnect.delete(route('settings.integrations.maildomain.disconnect'), { preserveScroll: true });
};
const domainStatusLabel = computed(() => ({ none: t('Uit'), pending: t('DNS instellen'), verified: t('Actief'), failed: t('DNS niet gevonden') }[props.mail_domain?.status] || t('Uit')));
const copyValue = (v) => navigator.clipboard?.writeText(v);

/* ---------- Peppol (Recommand) ---------- */
const peppolActivate = useForm({});
const peppolRefresh = useForm({});
const peppolDisable = useForm({});
const activatePeppol = () => peppolActivate.post(route('settings.integrations.peppol.activate'), { preserveScroll: true });
const refreshPeppol = () => peppolRefresh.post(route('settings.integrations.peppol.refresh'), { preserveScroll: true });
const disablePeppol = () => {
  if (confirm(t('Peppol uitschakelen? Je administratie wordt afgemeld op het netwerk; klanten kunnen je dan geen e-facturen meer sturen en jij kunt niet meer via Peppol afleveren.'))) {
    peppolDisable.delete(route('settings.integrations.peppol.disable'), { preserveScroll: true });
  }
};
const peppolStatusLabel = computed(() => ({
  none: t('Uit'), pending: t('Identiteitscontrole'), verified: t('Actief'), rejected: t('Afgewezen'), error: t('Fout'),
}[props.peppol?.status] || t('Uit')));

const rotateForm = useForm({});
const disableForm = useForm({});

const activate = () => rotateForm.post(route('settings.integrations.claude.rotate'), { preserveScroll: true });
const rotate = () => {
  if (confirm(t('Nieuwe koppel-URL aanmaken? De huidige URL werkt dan direct niet meer en moet je in Claude bijwerken.'))) {
    rotateForm.post(route('settings.integrations.claude.rotate'), { preserveScroll: true });
  }
};
const disable = () => {
  if (confirm(t('Claude-koppeling uitschakelen? Claude kan dan niets meer in deze administratie aanmaken.'))) {
    disableForm.delete(route('settings.integrations.claude.disable'), { preserveScroll: true });
  }
};

/* URL kopiëren (met nette fallback) */
const copied = ref(false);
const copyUrl = async () => {
  try {
    await navigator.clipboard.writeText(props.mcp.url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
  } catch (e) {
    prompt(t('Kopieer de koppel-URL:'), props.mcp.url);
  }
};
</script>

<template>
  <Head :title="$t('Koppelingen')" />
  <AppLayout>
    <template #breadcrumb>
      <span class="breadcrumb">{{ $t('Instellingen') }}</span>
      <span class="breadcrumb">/</span>
      <span class="breadcrumb-current">{{ $t('Koppelingen') }}</span>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Koppelingen') }}</h1>
        <p class="page-subtitle">{{ $t('Verbind :brand met de tools waarmee je werkt.', { brand: brand.name }) }}</p>
      </div>
    </div>

    <div v-if="flash.flash" class="kop-alert ok">{{ flash.flash }}</div>
    <div v-if="flash.error" class="kop-alert err">{{ flash.error }}</div>

    <!-- KSeF (Polen): e-facturen als FA-XML per factuur; directe verzending volgt -->
    <div v-if="market.e_invoicing === 'ksef'" class="card kop-card">
      <div class="card-body">
        <div class="kop-head">
          <div>
            <div class="kop-title">KSeF</div>
            <p class="kop-desc">{{ $t('KSeF-XML per factuur download je op de factuurpagina; directe verzending volgt.') }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Peppol: e-facturen verzenden en ontvangen -->
    <div v-if="peppol && market.e_invoicing === 'peppol'" class="card kop-card">
      <div class="card-body">
        <div class="kop-head">
          <div>
            <div class="kop-title">
              {{ $t('Peppol e-facturatie') }}
              <span class="kop-pill" :class="peppol.status === 'verified' ? 'on' : (peppol.status === 'pending' ? 'wait' : 'off')">{{ peppolStatusLabel }}</span>
            </div>
            <p class="kop-desc">
              {{ $t('Lever facturen rechtstreeks af in het boekhoudpakket van je klant en ontvang inkoopfacturen van leveranciers automatisch in je Postvak IN — via het Peppol-netwerk, zonder mailbox ertussen. Je administratie wordt een eigen Peppol-deelnemer') }}<template v-if="peppol.participant_id"> (<code style="font-size:12px;">{{ peppol.participant_id }}</code>)</template>.
            </p>
          </div>
        </div>

        <div v-if="!peppol.configured" class="kop-locked">
          {{ $t('Peppol wordt binnenkort geactiveerd. Zodra de koppeling met het netwerk klaar is, kun je hier je administratie aanmelden.') }}
        </div>

        <template v-else-if="peppol.status === 'none'">
          <div v-if="peppol.blockers.length" class="kop-locked">
            {{ $t('Vul eerst :fields in bij', { fields: peppol.blockers.join(', ') }) }}
            <Link :href="route('settings.company')" style="color:var(--brand);font-weight:600;">{{ $t('Bedrijfsgegevens') }}</Link>.
          </div>
          <template v-else>
            <button class="btn btn-primary" :disabled="peppolActivate.processing" @click="activatePeppol">
              {{ peppolActivate.processing ? $t('Bezig…') : $t('Peppol activeren') }}
            </button>
            <p class="kop-hint">
              {{ $t('Je administratie wordt geregistreerd op het netwerk. Daarna rondt een tekenbevoegd persoon eenmalig een online identiteitscontrole af (een paar minuten) — verplicht voor iedereen op Peppol.') }}
            </p>
          </template>
        </template>

        <template v-else>
          <div v-if="peppol.status === 'pending'" class="kop-steps">
            <div class="kop-steps-title">{{ $t('Nog één stap: de identiteitscontrole') }}</div>
            <p class="kop-hint" style="margin:0 0 10px;">
              {{ $t('Geregistreerd op :date. Verzenden en ontvangen kan zodra een tekenbevoegd persoon de identiteitscontrole heeft afgerond. Niet zelf tekenbevoegd? Stuur de link door.', { date: peppol.registered_at_label }) }}
            </p>
            <div class="kop-actions" style="margin-top:0;">
              <a v-if="peppol.verification_url" :href="peppol.verification_url" target="_blank" rel="noopener" class="btn btn-primary btn-sm">{{ $t('Identiteitscontrole afronden') }} ↗</a>
              <button class="btn btn-secondary btn-sm" :disabled="peppolRefresh.processing" @click="refreshPeppol">{{ $t('Status vernieuwen') }}</button>
            </div>
          </div>
          <div v-else-if="peppol.status === 'verified'" class="kop-steps">
            <div class="kop-steps-title">{{ $t('Actief sinds :date', { date: peppol.verified_at_label }) }}</div>
            <ol>
              <li>{{ $t('Op verstuurde facturen van klanten die op Peppol zitten staat de knop') }} <b>"⚡ {{ $t('Via Peppol afleveren') }}"</b>.</li>
              <li>{{ $t('E-facturen van leveranciers komen automatisch binnen in') }} <Link :href="route('purchases.inbox.index')" style="color:var(--brand);font-weight:600;">{{ $t('Postvak IN') }}</Link>, {{ $t('met de gegevens al ingevuld.') }}</li>
              <li>{{ $t('Geef leveranciers je Peppol-ID door:') }} <code style="font-size:12px;">{{ peppol.participant_id }}</code>.</li>
            </ol>
          </div>
          <div v-else class="kop-locked">
            {{ peppol.status === 'rejected' ? $t('De identiteitscontrole is afgewezen.') : $t('De identiteitscontrole is niet gelukt.') }} {{ $t('Neem contact met ons op via') }}
            <a :href="'mailto:' + brand.email" style="color:var(--brand);font-weight:600;">{{ brand.email }}</a>.
          </div>
          <div class="kop-actions">
            <button v-if="peppol.status !== 'pending'" class="btn btn-secondary btn-sm" :disabled="peppolRefresh.processing" @click="refreshPeppol">{{ $t('Status vernieuwen') }}</button>
            <button class="btn btn-danger btn-sm" :disabled="peppolDisable.processing" @click="disablePeppol">{{ $t('Peppol uitschakelen') }}</button>
          </div>
        </template>
      </div>
    </div>

    <!-- Eigen afzenderadres: mail vanaf het eigen domein -->
    <div v-if="mail_domain" class="card kop-card">
      <div class="card-body">
        <div class="kop-head">
          <div>
            <div class="kop-title">
              {{ $t('Eigen afzenderadres') }}
              <span class="kop-pill" :class="mail_domain.status === 'verified' ? 'on' : (mail_domain.status === 'pending' ? 'wait' : 'off')">{{ domainStatusLabel }}</span>
            </div>
            <p class="kop-desc">
              {{ $t('Facturen, offertes en herinneringen gaan nu uit naam van') }} <b>{{ mail_domain.default_from }}</b> {{ $t('met jouw bedrijfsnaam als afzender (antwoorden komen al bij jou aan). Wil je dat ze echt vanaf') }}
              <b>{{ $t('jouw') }}</b> {{ $t('domein komen — bijvoorbeeld') }}
              <code style="font-size:12px;">{{ $t('facturen') }}@{{ mail_domain.suggested_domain || $t('jouwbedrijf.nl') }}</code> — {{ $t('koppel dan je domein. Je zet daarvoor eenmalig een paar DNS-records bij je domeinbeheerder (TransIP, Vimexx, Cloudflare, Hostnet …).') }}
            </p>
          </div>
        </div>

        <div v-if="!mail_domain.configured" class="kop-locked">{{ $t('Deze koppeling is nog niet beschikbaar op dit platform.') }}</div>

        <template v-else-if="mail_domain.status === 'none'">
          <div class="dom-form">
            <div class="form-group"><label>{{ $t('Afzender') }}</label>
              <div class="dom-from"><input type="text" v-model="domainForm.local_part" :placeholder="$t('facturen')" style="max-width:160px;"><span>@</span><input type="text" v-model="domainForm.domain" :placeholder="$t('jouwbedrijf.nl')" style="max-width:260px;"></div>
              <div v-if="domainForm.errors.domain || domainForm.errors.local_part" class="field-error">{{ domainForm.errors.domain || domainForm.errors.local_part }}</div>
            </div>
            <button class="btn btn-primary" :disabled="domainForm.processing || !domainForm.domain" @click="connectDomain">{{ domainForm.processing ? $t('Bezig…') : $t('Domein koppelen') }}</button>
          </div>
          <p class="kop-hint">{{ $t('Werkt alleen met een eigen domeinnaam (geen Gmail/Outlook). Na het koppelen zie je hier precies welke DNS-records je moet toevoegen.') }}</p>
        </template>

        <template v-else>
          <div class="kop-steps">
            <div class="kop-steps-title">
              <template v-if="mail_domain.status === 'verified'">{{ $t('Actief — mail gaat uit als :from', { from: mail_domain.from_address }) }}</template>
              <template v-else>{{ $t('Zet deze DNS-records bij de beheerder van :domain', { domain: mail_domain.domain }) }}</template>
            </div>
            <table class="dom-records">
              <thead><tr><th>{{ $t('Type') }}</th><th>{{ $t('Naam (host)') }}</th><th>{{ $t('Waarde') }}</th><th></th></tr></thead>
              <tbody>
                <tr v-for="r in mail_domain.records" :key="r.name + r.type">
                  <td><b>{{ r.type }}</b><div class="dom-rec-kind">{{ r.record }}</div></td>
                  <td><code>{{ r.name }}</code></td>
                  <td><code class="dom-value">{{ r.value }}</code><div v-if="r.priority" class="dom-rec-kind">{{ $t('prioriteit') }} {{ r.priority }}</div></td>
                  <td class="right"><span class="kop-pill" :class="r.status === 'verified' ? 'on' : 'wait'" style="margin-left:0;">{{ r.status === 'verified' ? $t('ok') : $t('wacht') }}</span> <button type="button" class="dom-copy" @click="copyValue(r.value)" :title="$t('Kopieer waarde')">{{ $t('kopieer') }}</button></td>
                </tr>
              </tbody>
            </table>
            <p class="kop-hint" style="margin-top:10px;">
              <template v-if="mail_domain.status === 'verified'">{{ $t('Geverifieerd; laatst gecontroleerd :date.', { date: mail_domain.checked_at_label }) }} {{ $t('Tip: voeg ook een DMARC-record toe') }} (<code>_dmarc</code> TXT <code>v=DMARC1; p=none;</code>) {{ $t('voor de beste aflevering.') }}</template>
              <template v-else>{{ $t('DNS-wijzigingen zijn meestal binnen een kwartier zichtbaar, soms pas na een uur. Klik daarna op "Controleer DNS". Tot die tijd gaat je mail gewoon via :from.', { from: mail_domain.default_from }) }}</template>
            </p>
          </div>
          <div class="kop-actions">
            <button class="btn btn-secondary btn-sm" :disabled="domainRefresh.processing" @click="refreshDomain">{{ domainRefresh.processing ? $t('Bezig…') : $t('Controleer DNS') }}</button>
            <button class="btn btn-danger btn-sm" :disabled="domainDisconnect.processing" @click="disconnectDomain">{{ $t('Loskoppelen') }}</button>
          </div>
        </template>
      </div>
    </div>

    <div class="card kop-card">
      <div class="card-body">
        <div class="kop-head">
          <div>
            <div class="kop-title">
              Claude
              <span v-if="mcp.active" class="kop-pill on">{{ $t('Actief') }}</span>
              <span v-else class="kop-pill off">{{ $t('Uit') }}</span>
            </div>
            <p class="kop-desc">
              {{ $t('Schrijf je offertes in een gesprek met Claude en zeg simpelweg') }} <i>"{{ $t('zet deze offerte in :brand', { brand: brand.name }) }}"</i> —
              {{ $t('Claude maakt het concept direct in je administratie aan. Claude kan klanten opzoeken, concept-offertes en concept-facturen aanmaken en je openstaande facturen opvragen.') }} <b>{{ $t('Versturen doe je altijd zelf') }}</b> {{ $t('in') }} {{ brand.name }}.
            </p>
          </div>
        </div>

        <!-- Slim-vereiste -->
        <div v-if="!has_ai" class="kop-locked">
          {{ $t('De Claude-koppeling hoort bij de AI-functies van het') }} <b>Slim</b>{{ $t('-abonnement.') }}
          <Link :href="route('billing.show')" style="color:var(--brand);font-weight:600;">{{ $t('Bekijk de abonnementen') }}</Link>
        </div>

        <template v-else>
          <!-- Nog niet actief -->
          <div v-if="!mcp.active">
            <button class="btn btn-primary" :disabled="rotateForm.processing" @click="activate">
              {{ rotateForm.processing ? $t('Bezig…') : $t('Koppeling activeren') }}
            </button>
            <p class="kop-hint">{{ $t('Je krijgt een geheime koppel-URL die je eenmalig in Claude toevoegt.') }}</p>
          </div>

          <!-- Actief: URL + beheer -->
          <template v-else>
            <div class="kop-url-label">{{ $t('Jouw geheime koppel-URL') }}</div>
            <div class="kop-url-row">
              <code class="kop-url">{{ mcp.url }}</code>
              <button type="button" class="btn btn-secondary btn-sm" @click="copyUrl">
                {{ copied ? $t('Gekopieerd ✓') : $t('Kopiëren') }}
              </button>
            </div>
            <p class="kop-hint">
              {{ $t('Behandel deze URL als een wachtwoord: iedereen die hem kent kan concepten in je administratie aanmaken. Uitgelekt? Maak met één klik een nieuwe aan.') }}
            </p>

            <div class="kop-steps">
              <div class="kop-steps-title">{{ $t('Zo koppel je Claude (eenmalig)') }}</div>
              <ol>
                <li>{{ $t('Open claude.ai → Instellingen → Connectors (of in de Claude-desktopapp).') }}</li>
                <li>{{ $t('Kies') }} <b>"Add custom connector"</b> {{ $t('en plak de koppel-URL hierboven. Geen verdere authenticatie nodig.') }}</li>
                <li>{{ $t('Klaar! Vraag Claude bijvoorbeeld:') }} <i>"{{ $t('Zoek klant Jansen op in :brand', { brand: brand.name }) }}"</i> {{ $t('of') }}
                  <i>"{{ $t('Zet deze offerte als concept in :brand', { brand: brand.name }) }}"</i>.</li>
              </ol>
              <p class="kop-hint" style="margin-top:6px;">
                {{ $t('Werkt ook in Claude Code:') }} <code style="font-size:11.5px;">claude mcp add {{ brand.key }} --transport http {{ mcp.url }}</code>
              </p>
            </div>

            <div class="kop-actions">
              <button class="btn btn-secondary btn-sm" :disabled="rotateForm.processing" @click="rotate">{{ $t('Nieuwe koppel-URL') }}</button>
              <button class="btn btn-danger btn-sm" :disabled="disableForm.processing" @click="disable">{{ $t('Koppeling uitschakelen') }}</button>
            </div>
          </template>
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.kop-card { max-width: 860px; }
.dom-form { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-top: 6px; }
.dom-form .form-group { margin: 0; }
.dom-from { display: flex; align-items: center; gap: 6px; }
.dom-from span { color: var(--text-3); font-weight: 600; }
.dom-records { width: 100%; border-collapse: collapse; font-size: 12.5px; margin-top: 6px; }
.dom-records th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-3); padding: 4px 8px 6px 0; border-bottom: 1px solid var(--border); }
.dom-records td { padding: 8px 8px 8px 0; border-bottom: 1px solid var(--border); vertical-align: top; }
.dom-records code { font-size: 11.5px; word-break: break-all; }
.dom-value { display: inline-block; max-width: 360px; }
.dom-rec-kind { font-size: 11px; color: var(--text-4); }
.dom-copy { background: none; border: none; font-size: 11.5px; color: var(--brand); text-decoration: underline; cursor: pointer; padding: 0; }
.kop-head { display: flex; justify-content: space-between; gap: 16px; }
.kop-title { font-family: var(--font-display); font-weight: 700; font-size: 18px; display: flex; align-items: center; gap: 10px; }
.kop-pill { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-radius: 100px; padding: 3px 10px; border: 1px solid transparent; }
.kop-pill.on { color: var(--success); background: var(--success-bg); border-color: var(--success-border); }
.kop-pill.off { color: var(--text-3); background: var(--surface-2); border-color: var(--border); }
.kop-pill.wait { color: var(--warning); background: var(--warning-bg); border-color: var(--warning-border); }
.kop-desc { font-size: 13.5px; color: var(--text-2); line-height: 1.65; margin-top: 8px; max-width: 640px; }

.kop-locked {
  margin-top: 14px; padding: 12px 15px; border-radius: 10px;
  background: var(--brand-tint); border: 1px solid var(--brand-border);
  color: var(--text-2); font-size: 13px; line-height: 1.6;
}

.kop-url-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; margin: 16px 0 6px; }
.kop-url-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.kop-url {
  flex: 1; min-width: 240px; font-family: var(--font-mono); font-size: 12px;
  background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px;
  padding: 9px 12px; word-break: break-all; color: var(--text-2);
}
.kop-hint { font-size: 12px; color: var(--text-3); margin-top: 8px; line-height: 1.6; }

.kop-steps { margin-top: 18px; border: 1px dashed var(--border-strong); border-radius: 10px; padding: 14px 16px; }
.kop-steps-title { font-weight: 600; font-size: 13.5px; margin-bottom: 8px; }
.kop-steps ol { margin: 0; padding-left: 20px; font-size: 13px; color: var(--text-2); line-height: 1.8; }

.kop-actions { display: flex; gap: 8px; margin-top: 18px; flex-wrap: wrap; }

.kop-alert { padding: 12px 16px; border-radius: var(--r); margin-bottom: 18px; font-size: 14px; font-weight: 500; max-width: 860px; }
.kop-alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid var(--success-border); }
.kop-alert.err { background: var(--brand-tint); color: var(--brand-darker); border: 1px solid var(--brand-border); }
</style>
