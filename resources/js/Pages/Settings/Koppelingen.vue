<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  mcp: Object,     // { active, url }
  has_ai: Boolean, // AI-toegang (Slim, proef of vrijgesteld)
  peppol: Object,  // { configured, status, verification_url, participant_id, registered_at_label, verified_at_label, blockers }
  mail_domain: Object, // { configured, status, domain, from_address, records, checked_at_label, default_from, suggested_domain, suggested_local_part }
});

/* ---------- Eigen afzenderadres (Resend Domains) ---------- */
const domainForm = useForm({ domain: props.mail_domain?.suggested_domain || '', local_part: props.mail_domain?.suggested_local_part || 'facturen' });
const domainRefresh = useForm({});
const domainDisconnect = useForm({});
const connectDomain = () => domainForm.post(route('settings.integrations.maildomain.connect'), { preserveScroll: true });
const refreshDomain = () => domainRefresh.post(route('settings.integrations.maildomain.refresh'), { preserveScroll: true });
const disconnectDomain = () => {
  if (confirm(`Eigen afzenderadres loskoppelen? Mail gaat dan weer uit via ${brand.value.domain}.`)) domainDisconnect.delete(route('settings.integrations.maildomain.disconnect'), { preserveScroll: true });
};
const domainStatusLabel = computed(() => ({ none: 'Uit', pending: 'DNS instellen', verified: 'Actief', failed: 'DNS niet gevonden' }[props.mail_domain?.status] || 'Uit'));
const copyValue = (v) => navigator.clipboard?.writeText(v);

/* ---------- Peppol (Recommand) ---------- */
const peppolActivate = useForm({});
const peppolRefresh = useForm({});
const peppolDisable = useForm({});
const activatePeppol = () => peppolActivate.post(route('settings.integrations.peppol.activate'), { preserveScroll: true });
const refreshPeppol = () => peppolRefresh.post(route('settings.integrations.peppol.refresh'), { preserveScroll: true });
const disablePeppol = () => {
  if (confirm('Peppol uitschakelen? Je administratie wordt afgemeld op het netwerk; klanten kunnen je dan geen e-facturen meer sturen en jij kunt niet meer via Peppol afleveren.')) {
    peppolDisable.delete(route('settings.integrations.peppol.disable'), { preserveScroll: true });
  }
};
const peppolStatusLabel = computed(() => ({
  none: 'Uit', pending: 'Identiteitscontrole', verified: 'Actief', rejected: 'Afgewezen', error: 'Fout',
}[props.peppol?.status] || 'Uit'));

const page = usePage();
const flash = computed(() => page.props.flash || {});
const brand = computed(() => page.props.brand);

const rotateForm = useForm({});
const disableForm = useForm({});

const activate = () => rotateForm.post(route('settings.integrations.claude.rotate'), { preserveScroll: true });
const rotate = () => {
  if (confirm('Nieuwe koppel-URL aanmaken? De huidige URL werkt dan direct niet meer en moet je in Claude bijwerken.')) {
    rotateForm.post(route('settings.integrations.claude.rotate'), { preserveScroll: true });
  }
};
const disable = () => {
  if (confirm('Claude-koppeling uitschakelen? Claude kan dan niets meer in deze administratie aanmaken.')) {
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
    prompt('Kopieer de koppel-URL:', props.mcp.url);
  }
};
</script>

<template>
  <Head title="Koppelingen" />
  <AppLayout>
    <template #breadcrumb>
      <span class="breadcrumb">Instellingen</span>
      <span class="breadcrumb">/</span>
      <span class="breadcrumb-current">Koppelingen</span>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Koppelingen</h1>
        <p class="page-subtitle">Verbind {{ brand.name }} met de tools waarmee je werkt.</p>
      </div>
    </div>

    <div v-if="flash.flash" class="kop-alert ok">{{ flash.flash }}</div>
    <div v-if="flash.error" class="kop-alert err">{{ flash.error }}</div>

    <!-- Peppol: e-facturen verzenden en ontvangen -->
    <div v-if="peppol" class="card kop-card">
      <div class="card-body">
        <div class="kop-head">
          <div>
            <div class="kop-title">
              Peppol e-facturatie
              <span class="kop-pill" :class="peppol.status === 'verified' ? 'on' : (peppol.status === 'pending' ? 'wait' : 'off')">{{ peppolStatusLabel }}</span>
            </div>
            <p class="kop-desc">
              Lever facturen rechtstreeks af in het boekhoudpakket van je klant en ontvang inkoopfacturen van leveranciers
              automatisch in je Postvak IN — via het Peppol-netwerk, zonder mailbox ertussen. Je administratie wordt een eigen
              Peppol-deelnemer<template v-if="peppol.participant_id"> (<code style="font-size:12px;">{{ peppol.participant_id }}</code>)</template>.
            </p>
          </div>
        </div>

        <div v-if="!peppol.configured" class="kop-locked">
          Peppol wordt binnenkort geactiveerd. Zodra de koppeling met het netwerk klaar is, kun je hier je administratie aanmelden.
        </div>

        <template v-else-if="peppol.status === 'none'">
          <div v-if="peppol.blockers.length" class="kop-locked">
            Vul eerst {{ peppol.blockers.join(', ') }} in bij
            <Link :href="route('settings.company')" style="color:var(--brand);font-weight:600;">Bedrijfsgegevens</Link>.
          </div>
          <template v-else>
            <button class="btn btn-primary" :disabled="peppolActivate.processing" @click="activatePeppol">
              {{ peppolActivate.processing ? 'Bezig…' : 'Peppol activeren' }}
            </button>
            <p class="kop-hint">
              Je administratie wordt geregistreerd op het netwerk. Daarna rondt een tekenbevoegd persoon eenmalig een
              online identiteitscontrole af (een paar minuten) — verplicht voor iedereen op Peppol.
            </p>
          </template>
        </template>

        <template v-else>
          <div v-if="peppol.status === 'pending'" class="kop-steps">
            <div class="kop-steps-title">Nog één stap: de identiteitscontrole</div>
            <p class="kop-hint" style="margin:0 0 10px;">
              Geregistreerd op {{ peppol.registered_at_label }}. Verzenden en ontvangen kan zodra een tekenbevoegd persoon de
              identiteitscontrole heeft afgerond. Niet zelf tekenbevoegd? Stuur de link door.
            </p>
            <div class="kop-actions" style="margin-top:0;">
              <a v-if="peppol.verification_url" :href="peppol.verification_url" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Identiteitscontrole afronden ↗</a>
              <button class="btn btn-secondary btn-sm" :disabled="peppolRefresh.processing" @click="refreshPeppol">Status vernieuwen</button>
            </div>
          </div>
          <div v-else-if="peppol.status === 'verified'" class="kop-steps">
            <div class="kop-steps-title">Actief sinds {{ peppol.verified_at_label }}</div>
            <ol>
              <li>Op verstuurde facturen van klanten die op Peppol zitten staat de knop <b>"⚡ Via Peppol afleveren"</b>.</li>
              <li>E-facturen van leveranciers komen automatisch binnen in <Link :href="route('purchases.inbox.index')" style="color:var(--brand);font-weight:600;">Postvak IN</Link>, met de gegevens al ingevuld.</li>
              <li>Geef leveranciers je Peppol-ID door: <code style="font-size:12px;">{{ peppol.participant_id }}</code>.</li>
            </ol>
          </div>
          <div v-else class="kop-locked">
            De identiteitscontrole is {{ peppol.status === 'rejected' ? 'afgewezen' : 'niet gelukt' }}. Neem contact met ons op via
            <a :href="'mailto:' + brand.email" style="color:var(--brand);font-weight:600;">{{ brand.email }}</a>.
          </div>
          <div class="kop-actions">
            <button v-if="peppol.status !== 'pending'" class="btn btn-secondary btn-sm" :disabled="peppolRefresh.processing" @click="refreshPeppol">Status vernieuwen</button>
            <button class="btn btn-danger btn-sm" :disabled="peppolDisable.processing" @click="disablePeppol">Peppol uitschakelen</button>
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
              Eigen afzenderadres
              <span class="kop-pill" :class="mail_domain.status === 'verified' ? 'on' : (mail_domain.status === 'pending' ? 'wait' : 'off')">{{ domainStatusLabel }}</span>
            </div>
            <p class="kop-desc">
              Facturen, offertes en herinneringen gaan nu uit naam van <b>{{ mail_domain.default_from }}</b> met jouw bedrijfsnaam als afzender
              (antwoorden komen al bij jou aan). Wil je dat ze echt vanaf <b>jouw</b> domein komen — bijvoorbeeld
              <code style="font-size:12px;">facturen@{{ mail_domain.suggested_domain || 'jouwbedrijf.nl' }}</code> — koppel dan je domein. Je zet daarvoor
              eenmalig een paar DNS-records bij je domeinbeheerder (TransIP, Vimexx, Cloudflare, Hostnet …).
            </p>
          </div>
        </div>

        <div v-if="!mail_domain.configured" class="kop-locked">Deze koppeling is nog niet beschikbaar op dit platform.</div>

        <template v-else-if="mail_domain.status === 'none'">
          <div class="dom-form">
            <div class="form-group"><label>Afzender</label>
              <div class="dom-from"><input type="text" v-model="domainForm.local_part" placeholder="facturen" style="max-width:160px;"><span>@</span><input type="text" v-model="domainForm.domain" placeholder="jouwbedrijf.nl" style="max-width:260px;"></div>
              <div v-if="domainForm.errors.domain || domainForm.errors.local_part" class="field-error">{{ domainForm.errors.domain || domainForm.errors.local_part }}</div>
            </div>
            <button class="btn btn-primary" :disabled="domainForm.processing || !domainForm.domain" @click="connectDomain">{{ domainForm.processing ? 'Bezig…' : 'Domein koppelen' }}</button>
          </div>
          <p class="kop-hint">Werkt alleen met een eigen domeinnaam (geen Gmail/Outlook). Na het koppelen zie je hier precies welke DNS-records je moet toevoegen.</p>
        </template>

        <template v-else>
          <div class="kop-steps">
            <div class="kop-steps-title">
              <template v-if="mail_domain.status === 'verified'">Actief — mail gaat uit als {{ mail_domain.from_address }}</template>
              <template v-else>Zet deze DNS-records bij de beheerder van {{ mail_domain.domain }}</template>
            </div>
            <table class="dom-records">
              <thead><tr><th>Type</th><th>Naam (host)</th><th>Waarde</th><th></th></tr></thead>
              <tbody>
                <tr v-for="r in mail_domain.records" :key="r.name + r.type">
                  <td><b>{{ r.type }}</b><div class="dom-rec-kind">{{ r.record }}</div></td>
                  <td><code>{{ r.name }}</code></td>
                  <td><code class="dom-value">{{ r.value }}</code><div v-if="r.priority" class="dom-rec-kind">prioriteit {{ r.priority }}</div></td>
                  <td class="right"><span class="kop-pill" :class="r.status === 'verified' ? 'on' : 'wait'" style="margin-left:0;">{{ r.status === 'verified' ? 'ok' : 'wacht' }}</span> <button type="button" class="dom-copy" @click="copyValue(r.value)" title="Kopieer waarde">kopieer</button></td>
                </tr>
              </tbody>
            </table>
            <p class="kop-hint" style="margin-top:10px;">
              <template v-if="mail_domain.status === 'verified'">Geverifieerd; laatst gecontroleerd {{ mail_domain.checked_at_label }}. Tip: voeg ook een DMARC-record toe (<code>_dmarc</code> TXT <code>v=DMARC1; p=none;</code>) voor de beste aflevering.</template>
              <template v-else>DNS-wijzigingen zijn meestal binnen een kwartier zichtbaar, soms pas na een uur. Klik daarna op "Controleer DNS". Tot die tijd gaat je mail gewoon via {{ mail_domain.default_from }}.</template>
            </p>
          </div>
          <div class="kop-actions">
            <button class="btn btn-secondary btn-sm" :disabled="domainRefresh.processing" @click="refreshDomain">{{ domainRefresh.processing ? 'Bezig…' : 'Controleer DNS' }}</button>
            <button class="btn btn-danger btn-sm" :disabled="domainDisconnect.processing" @click="disconnectDomain">Loskoppelen</button>
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
              <span v-if="mcp.active" class="kop-pill on">Actief</span>
              <span v-else class="kop-pill off">Uit</span>
            </div>
            <p class="kop-desc">
              Schrijf je offertes in een gesprek met Claude en zeg simpelweg <i>"zet deze offerte in {{ brand.name }}"</i> —
              Claude maakt het concept direct in je administratie aan. Claude kan klanten opzoeken, concept-offertes en
              concept-facturen aanmaken en je openstaande facturen opvragen. <b>Versturen doe je altijd zelf</b> in {{ brand.name }}.
            </p>
          </div>
        </div>

        <!-- Slim-vereiste -->
        <div v-if="!has_ai" class="kop-locked">
          De Claude-koppeling hoort bij de AI-functies van het <b>Slim</b>-abonnement.
          <Link :href="route('billing.show')" style="color:var(--brand);font-weight:600;">Bekijk de abonnementen</Link>
        </div>

        <template v-else>
          <!-- Nog niet actief -->
          <div v-if="!mcp.active">
            <button class="btn btn-primary" :disabled="rotateForm.processing" @click="activate">
              {{ rotateForm.processing ? 'Bezig…' : 'Koppeling activeren' }}
            </button>
            <p class="kop-hint">Je krijgt een geheime koppel-URL die je eenmalig in Claude toevoegt.</p>
          </div>

          <!-- Actief: URL + beheer -->
          <template v-else>
            <div class="kop-url-label">Jouw geheime koppel-URL</div>
            <div class="kop-url-row">
              <code class="kop-url">{{ mcp.url }}</code>
              <button type="button" class="btn btn-secondary btn-sm" @click="copyUrl">
                {{ copied ? 'Gekopieerd ✓' : 'Kopiëren' }}
              </button>
            </div>
            <p class="kop-hint">
              Behandel deze URL als een wachtwoord: iedereen die hem kent kan concepten in je administratie aanmaken.
              Uitgelekt? Maak met één klik een nieuwe aan.
            </p>

            <div class="kop-steps">
              <div class="kop-steps-title">Zo koppel je Claude (eenmalig)</div>
              <ol>
                <li>Open <b>claude.ai</b> → Instellingen → <b>Connectors</b> (of in de Claude-desktopapp).</li>
                <li>Kies <b>"Add custom connector"</b> en plak de koppel-URL hierboven. Geen verdere authenticatie nodig.</li>
                <li>Klaar! Vraag Claude bijvoorbeeld: <i>"Zoek klant Jansen op in {{ brand.name }}"</i> of
                  <i>"Zet deze offerte als concept in {{ brand.name }}"</i>.</li>
              </ol>
              <p class="kop-hint" style="margin-top:6px;">
                Werkt ook in Claude Code: <code style="font-size:11.5px;">claude mcp add {{ brand.key }} --transport http {{ mcp.url }}</code>
              </p>
            </div>

            <div class="kop-actions">
              <button class="btn btn-secondary btn-sm" :disabled="rotateForm.processing" @click="rotate">Nieuwe koppel-URL</button>
              <button class="btn btn-danger btn-sm" :disabled="disableForm.processing" @click="disable">Koppeling uitschakelen</button>
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
