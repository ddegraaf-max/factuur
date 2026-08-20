<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  mcp: Object,     // { active, url }
  has_ai: Boolean, // AI-toegang (Slim, proef of vrijgesteld)
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

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
        <p class="page-subtitle">Verbind EasyInvoice met de tools waarmee je werkt.</p>
      </div>
    </div>

    <div v-if="flash.flash" class="kop-alert ok">{{ flash.flash }}</div>
    <div v-if="flash.error" class="kop-alert err">{{ flash.error }}</div>

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
              Schrijf je offertes in een gesprek met Claude en zeg simpelweg <i>"zet deze offerte in EasyInvoice"</i> —
              Claude maakt het concept direct in je administratie aan. Claude kan klanten opzoeken, concept-offertes en
              concept-facturen aanmaken en je openstaande facturen opvragen. <b>Versturen doe je altijd zelf</b> in EasyInvoice.
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
                <li>Klaar! Vraag Claude bijvoorbeeld: <i>"Zoek klant Jansen op in EasyInvoice"</i> of
                  <i>"Zet deze offerte als concept in EasyInvoice"</i>.</li>
              </ol>
              <p class="kop-hint" style="margin-top:6px;">
                Werkt ook in Claude Code: <code style="font-size:11.5px;">claude mcp add easyinvoice --transport http {{ mcp.url }}</code>
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
.kop-head { display: flex; justify-content: space-between; gap: 16px; }
.kop-title { font-family: var(--font-display); font-weight: 700; font-size: 18px; display: flex; align-items: center; gap: 10px; }
.kop-pill { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-radius: 100px; padding: 3px 10px; border: 1px solid transparent; }
.kop-pill.on { color: var(--success); background: var(--success-bg); border-color: var(--success-border); }
.kop-pill.off { color: var(--text-3); background: var(--surface-2); border-color: var(--border); }
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
