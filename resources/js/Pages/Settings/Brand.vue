<script setup>
import { ref, computed, reactive } from 'vue';
import { router, useForm, Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { t } from '@/i18n';
import { eur, fmtDateLong } from '@/format';

// Het platform-merk (EasyInvoice/Lopra) — niet te verwarren met de huisstijl van de gebruiker hieronder.
const brand = usePage().props.brand;
// Markt (nl/pl): valuta, btw-tarief en KvK/REGON- en btw/NIP-labels in het voorbeeld.
const market = usePage().props.market;

const props = defineProps({
  company: Object,
  ai_enabled: Boolean, // huisstijl herkennen met AI (Slim + API-key)
  ai_locked: Boolean,  // functie bestaat, maar zit in het Slim-abonnement
});

const defaultFooter = t('Bedankt voor uw vertrouwen! Gelieve het factuurbedrag binnen de betaaltermijn te voldoen onder vermelding van het factuurnummer. Heeft u vragen over deze factuur? Neem gerust contact met ons op.');

const form = useForm({
  brand_color: props.company.brand_color || '#E8231F',
  accent_color: props.company.accent_color || '#1C1917',
  invoice_template: props.company.invoice_template || 'modern',
  invoice_font: props.company.invoice_font || 'sans',
  invoice_footer: props.company.invoice_footer || defaultFooter,
  logo_scale: props.company.logo_scale || 100,
  logo: null,
  logo_data_url: null,
  stationery: null,
  stationery_margin_top: props.company.stationery_margin_top || 45,
  stationery_margin_bottom: props.company.stationery_margin_bottom || 25,
});

const logoUploading = ref(false);
const previewLogo = computed(() => props.company.logo_data || (props.company.logo_path ? `/storage/${props.company.logo_path}` : null));

const logoStyleModern = computed(() => ({
  maxHeight: (36 * form.logo_scale / 100) + 'px',
  maxWidth: (180 * form.logo_scale / 100) + 'px',
}));
const logoStyleClassic = computed(() => ({
  maxHeight: (36 * form.logo_scale / 100) + 'px',
  maxWidth: (120 * form.logo_scale / 100) + 'px',
}));

const colorPresets = ['#E8231F', '#0F172A', '#1E40AF', '#15803D', '#7C3AED', '#DB2777', '#EA580C', '#0891B2'];
const templates = [
  { value: 'modern', name: t('Modern'), desc: t('Kleurband, sterk') },
  { value: 'classic', name: t('Klassiek'), desc: t('Formeel, gelijnd') },
  { value: 'minimal', name: t('Minimaal'), desc: t('Veel ruimte, rustig') },
  { value: 'stationery', name: t('Briefpapier'), desc: t('Je eigen ontwerp') },
];

const previewStationery = computed(() => props.company.stationery_data || null);
const stationeryHint = ref(false);
const selectTemplate = (value) => {
  if (value === 'stationery' && !previewStationery.value) {
    stationeryHint.value = true;
    return;
  }
  stationeryHint.value = false;
  form.invoice_template = value;
};

/* ---------- Briefpapier uploaden ---------- */
const stationeryUploading = ref(false);
const onStationeryChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  form.stationery = file;
  stationeryUploading.value = true;
  form.post(route('settings.brand.update'), {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => { stationeryUploading.value = false; form.stationery = null; },
  });
};
const removeStationery = () => {
  if (!confirm(t('Briefpapier verwijderen? Het sjabloon valt dan terug op Modern.'))) return;
  router.delete(route('settings.brand.stationery.remove'), { preserveScroll: true });
};

/* ---------- Huisstijl herkennen met AI ---------- */
const aiBusy = ref(false);
const aiError = ref('');
const aiNotice = ref('');
const onAiFile = async (e) => {
  const file = e.target.files[0];
  e.target.value = '';
  if (!file || aiBusy.value) return;
  aiBusy.value = true;
  aiError.value = ''; aiNotice.value = '';
  try {
    const fd = new FormData();
    fd.append('file', file);
    const { data } = await axios.post(route('settings.brand.scan'), fd);
    const r = data.result;
    form.brand_color = r.brand_color;
    if (r.accent_color) form.accent_color = r.accent_color;
    form.invoice_font = r.font;
    if (form.invoice_template !== 'stationery') form.invoice_template = r.template;
    aiNotice.value = (r.motivation ? r.motivation + ' — ' : '')
      + t('Bekijk het voorbeeld rechts en klik op Opslaan om te bevestigen.');
  } catch (err) {
    aiError.value = err.response?.data?.message
      || err.response?.data?.errors?.file?.[0]
      || t('Herkennen is niet gelukt. Probeer het opnieuw of stel de kleuren handmatig in.');
  } finally {
    aiBusy.value = false;
  }
};

/* ---------- Huisstijl ontwerpen met AI ---------- */
// De uitstraling gaat als tekst naar de AI; de labels in de keuzelijst worden vertaald, de waarde blijft de brontekst.
const tones = ['fris en modern', 'warm en ambachtelijk', 'strak en zakelijk', 'speels en creatief', 'luxe en rustig'];
const design = reactive({ sector: '', audience: '', tone: 'fris en modern', colors: '' });
const designBusy = ref(false);
const designError = ref('');
const directions = ref([]);
const chosen = ref(null);
const useLogo = ref(true);

const proposeDesign = async () => {
  if (designBusy.value) return;
  designBusy.value = true;
  designError.value = '';
  chosen.value = null;
  try {
    const { data } = await axios.post(route('settings.brand.design'), design);
    directions.value = data.directions || [];
  } catch (err) {
    designError.value = err.response?.data?.message
      || err.response?.data?.errors?.sector?.[0]
      || t('Ontwerpen is niet gelukt. Probeer het opnieuw.');
  } finally {
    designBusy.value = false;
  }
};

const escXml = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
// Logo-voorstel als SVG: monogram (letters in een gekleurd vlak) of woordmerk (naam in accentkleur met kleurstreep).
const svgFor = (d) => {
  const font = d.font === 'serif' ? "Georgia, 'Times New Roman', serif" : 'Inter, Arial, Helvetica, sans-serif';
  if (d.logo_style === 'monogram') {
    const t = escXml(d.logo_text.slice(0, 3).toUpperCase());
    const size = t.length > 2 ? 84 : (t.length === 2 ? 104 : 124);
    return `<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240"><rect width="240" height="240" rx="48" fill="${d.brand_color}"/><text x="120" y="126" text-anchor="middle" dominant-baseline="middle" font-family="${font}" font-weight="800" font-size="${size}" fill="#ffffff">${t}</text></svg>`;
  }
  const t = escXml(d.logo_text);
  const w = Math.max(320, Math.round(d.logo_text.length * 27) + 70);
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="96" viewBox="0 0 ${w} 96"><rect x="0" y="22" width="12" height="52" rx="4" fill="${d.brand_color}"/><text x="30" y="52" dominant-baseline="middle" font-family="${font}" font-weight="800" font-size="44" letter-spacing="-1" fill="${d.accent_color}">${t}</text></svg>`;
};
const logoUrl = (d) => 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgFor(d))));

const applyDirection = (d) => {
  form.brand_color = d.brand_color;
  form.accent_color = d.accent_color;
  form.invoice_font = d.font;
  if (form.invoice_template !== 'stationery') form.invoice_template = d.template;
  form.logo_data_url = useLogo.value ? logoUrl(d) : null;
  chosen.value = d.name;
  aiError.value = '';
  aiNotice.value = t(':name gekozen — :motivation Bekijk het voorbeeld rechts en klik op Opslaan om te bevestigen.', { name: d.name, motivation: d.motivation });
};

// Geld in de schrijfwijze van de markt (€ 1.234,50 / 1 234,50 zł).
const nf = (n) => eur(n);
const companyAddr = computed(() => [props.company.postal_code, props.company.city].filter(Boolean).join(' '));
const footerText = computed(() => form.invoice_footer || defaultFooter);
const _today = new Date();
const _fmt = (d) => fmtDateLong(d);
const _terms = props.company.default_payment_terms || 30;
const _vat = Number(market.default_vat ?? 21);
const _subtotal = 1430;
const pv = {
  number: market.key === 'pl' ? 'FV/2026/0007' : '2026-0007',
  date: _fmt(_today),
  due: _fmt(new Date(_today.getTime() + _terms * 86400000)),
  terms: _terms,
  customer: { name: t('Voorbeeldklant B.V.'), addr: t('Keizersgracht 123'), city: t('1015 CJ Amsterdam') },
  lines: [
    { desc: t('Webdesign basispakket'), qty: 1, price: 1250, vat: _vat },
    { desc: t('Hosting jaarpakket'), qty: 1, price: 180, vat: _vat },
  ],
  vatRate: _vat,
  subtotal: _subtotal, vat: Math.round(_subtotal * _vat) / 100, total: _subtotal + Math.round(_subtotal * _vat) / 100,
};

const onLogoChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  form.logo = file;
  logoUploading.value = true;
  form.post(route('settings.brand.update'), {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => { logoUploading.value = false; form.logo = null; },
  });
};

const submit = () => {
  form.post(route('settings.brand.update'), { forceFormData: true, preserveScroll: true });
};

const removeLogo = () => {
  if (!confirm(t('Logo verwijderen?'))) return;
  router.delete(route('settings.brand.logo.remove'), { preserveScroll: true });
};
</script>

<template>
  <Head :title="$t('Huisstijl')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Instellingen') }} / <span class="breadcrumb-current">{{ $t('Huisstijl') }}</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" @click="submit" :disabled="form.processing">{{ $t('Opslaan') }}</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Huisstijl') }}</h1>
        <p class="page-subtitle">{{ $t('Pas logo, kleuren en lay-out aan — alles wat je hier wijzigt verschijnt op je facturen') }}</p>
      </div>
    </div>

    <div class="huisstijl-layout">
      <div class="huisstijl-settings">
        <!-- Huisstijl herkennen met AI -->
        <div v-if="ai_enabled" class="card" style="border-color:var(--brand-border);">
          <div class="card-header">
            <div>
              <div class="card-title">✨ {{ $t('Huisstijl herkennen met AI') }}</div>
              <div class="card-subtitle">{{ $t('Upload je huisstijlgids, briefpapier of een oude factuur — de kleuren, het lettertype en het best passende sjabloon worden voor je ingevuld') }}</div>
            </div>
          </div>
          <div class="card-body">
            <label class="btn btn-primary btn-sm" style="cursor:pointer;">
              {{ aiBusy ? $t('Document wordt gelezen…') : $t('Kies een bestand (PDF of afbeelding)') }}
              <input type="file" accept=".pdf,.png,.jpg,.jpeg,.webp" style="display:none;" :disabled="aiBusy" @change="onAiFile" />
            </label>
            <div v-if="aiNotice" class="ai-msg ai-ok">{{ aiNotice }}</div>
            <div v-if="aiError" class="field-error" style="margin-top:8px;">{{ aiError }}</div>
          </div>
        </div>
        <div v-else-if="ai_locked" class="card">
          <div class="card-body" style="font-size:13px;color:var(--text-2);line-height:1.6;">
            ✨ <b>{{ $t('Huisstijl herkennen met AI') }}</b> — {{ $t('upload je huisstijlgids of briefpapier en de kleuren en stijl worden automatisch ingesteld. Onderdeel van het') }} <b>Slim</b>{{ $t('-abonnement.') }}
            <Link :href="route('billing.show')" style="color:var(--brand);font-weight:600;">{{ $t('Bekijk de abonnementen') }}</Link>
          </div>
        </div>

        <!-- Huisstijl ontwerpen met AI -->
        <div v-if="ai_enabled" class="card" style="border-color:var(--brand-border);">
          <div class="card-header">
            <div>
              <div class="card-title">✨ {{ $t('Huisstijl ontwerpen met AI') }}</div>
              <div class="card-subtitle">{{ $t('Nog geen huisstijl? Vertel in een paar woorden wat je doet en kies uit drie voorstellen: kleuren, lettertype, sjabloon, slogan en een logo.') }}</div>
            </div>
          </div>
          <div class="card-body">
            <div class="form-group"><label>{{ $t('Wat doet je bedrijf?') }}</label><input v-model="design.sector" :placeholder="$t('Bijv. loodgieter voor particulieren in Utrecht')" /></div>
            <div class="form-row">
              <div class="form-group"><label>{{ $t('Voor wie?') }}</label><input v-model="design.audience" :placeholder="$t('Bijv. huiseigenaren en VvE\'s')" /></div>
              <div class="form-group"><label>{{ $t('Uitstraling') }}</label>
                <select v-model="design.tone"><option v-for="tone in tones" :key="tone" :value="tone">{{ $t(tone) }}</option></select>
              </div>
            </div>
            <div class="form-group"><label>{{ $t('Kleurwens (optioneel)') }}</label><input v-model="design.colors" :placeholder="$t('Bijv. graag blauw, geen rood')" /></div>
            <button class="btn btn-primary btn-sm" :disabled="designBusy || design.sector.trim().length < 3" @click="proposeDesign">
              {{ designBusy ? $t('Ontwerpen… (± 20 seconden)') : (directions.length ? $t('Nieuwe voorstellen') : $t('Ontwerp mijn huisstijl')) }}
            </button>
            <div v-if="designError" class="field-error" style="margin-top:8px;">{{ designError }}</div>
            <div v-if="directions.length" class="dir-grid">
              <div v-for="d in directions" :key="d.name" class="dir" :class="{ chosen: chosen === d.name }">
                <div class="dir-swatches"><span :style="{ background: d.brand_color }"></span><span :style="{ background: d.accent_color }"></span></div>
                <img :src="logoUrl(d)" class="dir-logo" alt="" />
                <div class="dir-name">{{ d.name }}</div>
                <div class="dir-meta">{{ d.font === 'serif' ? $t('Schreefletter') : $t('Schreefloos') }} · {{ $t('sjabloon') }} {{ d.template }}</div>
                <div class="dir-tag">"{{ d.tagline }}"</div>
                <div class="dir-why">{{ d.motivation }}</div>
                <button class="btn btn-secondary btn-sm" @click="applyDirection(d)">{{ chosen === d.name ? $t('Gekozen ✓') : $t('Gebruik deze') }}</button>
              </div>
            </div>
            <label v-if="directions.length" class="toggle-row" style="margin-top:12px;">
              <input type="checkbox" v-model="useLogo">
              <div><div class="toggle-title">{{ $t('Ook het logo-voorstel gebruiken') }}</div><div class="toggle-sub">{{ $t('Vervangt je huidige logo zodra je op Opslaan klikt. Later altijd te wijzigen.') }}</div></div>
            </label>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Logo') }}</div></div>
          <div class="card-body">
            <div v-if="previewLogo">
              <img :src="previewLogo" class="logo-preview" alt="Logo" />
              <div style="display:flex;gap:8px;margin-top:10px;">
                <label class="btn btn-ghost btn-sm">
                  {{ logoUploading ? $t('Bezig…') : $t('Vervangen') }}
                  <input type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" @change="onLogoChange" :disabled="logoUploading" style="display:none;" />
                </label>
                <button class="btn btn-danger btn-sm" @click="removeLogo" :disabled="logoUploading">{{ $t('Verwijderen') }}</button>
              </div>

              <div class="logo-scale-row">
                <div class="logo-scale-label">
                  <span>{{ $t('Grootte op factuur') }}</span>
                  <span class="logo-scale-value">{{ form.logo_scale }}%</span>
                </div>
                <input
                  type="range"
                  min="50"
                  max="200"
                  step="5"
                  v-model.number="form.logo_scale"
                  class="logo-scale-slider"
                />
                <div class="logo-scale-ticks">
                  <span>50%</span>
                  <span>100%</span>
                  <span>200%</span>
                </div>
              </div>
            </div>
            <label v-else class="logo-upload-zone">
              <input type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" @change="onLogoChange" :disabled="logoUploading" style="display:none;" />
              <div class="upload-hint">{{ $t('PNG, JPG, SVG of WebP — max 2 MB') }}</div>
              <div class="upload-cta"><b>{{ logoUploading ? $t('Uploaden…') : $t('Klik om te uploaden') }}</b></div>
            </label>
            <div v-if="form.errors.logo" class="field-error" style="margin-top:8px;">{{ form.errors.logo }}</div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Huiskleur') }}</div></div>
          <div class="card-body">
            <div class="form-group">
              <label>{{ $t('Primaire kleur') }}</label>
              <div style="display:flex;gap:10px;">
                <input type="color" v-model="form.brand_color" />
                <input type="text" v-model="form.brand_color" class="mono" maxlength="7" />
              </div>
              <div class="color-presets">
                <div v-for="c in colorPresets" :key="c"
                  class="color-preset"
                  :class="{ active: form.brand_color.toLowerCase() === c.toLowerCase() }"
                  :style="{ background: c }"
                  @click="form.brand_color = c"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Factuur-lay-out') }}</div>
              <div class="card-subtitle">{{ $t('Kies een sjabloon — het voorbeeld rechts past zich direct aan') }}</div>
            </div>
          </div>
          <div class="card-body">
            <div class="template-cards">
              <div v-for="t in templates" :key="t.value"
                class="template-card"
                :class="{ active: form.invoice_template === t.value, disabled: t.value === 'stationery' && !previewStationery }"
                @click="selectTemplate(t.value)">
                <div class="template-thumb" :class="`thumb-${t.value}`" :style="{ '--brand': form.brand_color }">
                  <!-- Modern -->
                  <template v-if="t.value === 'modern'">
                    <div class="tt-band"></div>
                    <div class="tt-line tt-line-1"></div>
                    <div class="tt-line tt-line-2"></div>
                    <div class="tt-line tt-line-3"></div>
                    <div class="tt-line tt-line-4"></div>
                    <div class="tt-total"></div>
                  </template>
                  <!-- Classic -->
                  <template v-else-if="t.value === 'classic'">
                    <div class="tt-title-c"></div>
                    <div class="tt-grid">
                      <span></span><span></span><span></span><span></span>
                      <span></span><span></span><span></span><span></span>
                    </div>
                    <div class="tt-total-c"></div>
                  </template>
                  <!-- Minimal -->
                  <template v-else-if="t.value === 'minimal'">
                    <div class="tt-title-m"></div>
                    <div class="tt-line tt-line-1"></div>
                    <div class="tt-line tt-line-2"></div>
                    <div class="tt-total-m"></div>
                  </template>
                  <!-- Briefpapier -->
                  <template v-else>
                    <img v-if="previewStationery" :src="previewStationery" class="tt-stationery-img" alt="" />
                    <div v-else class="tt-stationery-empty">{{ $t('Upload') }}<br>{{ $t('hieronder') }}</div>
                  </template>
                </div>
                <div class="template-name">{{ t.name }}</div>
                <div class="template-desc">{{ t.desc }}</div>
              </div>
            </div>
            <div v-if="stationeryHint" class="field-error" style="margin-top:8px;">{{ $t('Upload eerst je briefpapier (hieronder) om dit sjabloon te kunnen kiezen.') }}</div>
          </div>
        </div>

        <!-- Eigen briefpapier -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Eigen briefpapier') }}</div>
              <div class="card-subtitle">{{ $t('Je volledige eigen ontwerp (bijv. door AI gemaakt) als ondergrond — :brand zet er alleen de factuurinhoud op', { brand: brand.name }) }}</div>
            </div>
          </div>
          <div class="card-body">
            <div v-if="previewStationery">
              <img :src="previewStationery" class="stationery-preview" :alt="$t('Briefpapier')" />
              <div style="display:flex;gap:8px;margin-top:10px;">
                <label class="btn btn-ghost btn-sm">
                  {{ stationeryUploading ? $t('Bezig…') : $t('Vervangen') }}
                  <input type="file" accept="image/png,image/jpeg,image/webp" @change="onStationeryChange" :disabled="stationeryUploading" style="display:none;" />
                </label>
                <button class="btn btn-danger btn-sm" @click="removeStationery" :disabled="stationeryUploading">{{ $t('Verwijderen') }}</button>
              </div>
              <div class="form-row" style="margin-top:14px;">
                <div class="form-group">
                  <label>{{ $t('Bovenmarge (mm)') }}<span class="label-hint">{{ $t('waar de inhoud begint') }}</span></label>
                  <input type="number" v-model.number="form.stationery_margin_top" min="10" max="150" />
                </div>
                <div class="form-group">
                  <label>{{ $t('Ondermarge (mm)') }}</label>
                  <input type="number" v-model.number="form.stationery_margin_bottom" min="5" max="100" />
                </div>
              </div>
              <p class="stationery-hint">{{ $t('Tip: staat je adres of logo bovenaan het papier? Zet de bovenmarge dan zo dat de factuurinhoud eronder begint. Het voorbeeld rechts beweegt live mee.') }}</p>
            </div>
            <label v-else class="logo-upload-zone">
              <input type="file" accept="image/png,image/jpeg,image/webp" @change="onStationeryChange" :disabled="stationeryUploading" style="display:none;" />
              <div class="upload-hint">{{ $t('PNG of JPG op A4-verhouding — max 4 MB. Heb je een PDF? Exporteer die eerst als afbeelding.') }}</div>
              <div class="upload-cta"><b>{{ stationeryUploading ? $t('Uploaden…') : $t('Klik om je briefpapier te uploaden') }}</b></div>
            </label>
            <div v-if="form.errors.stationery" class="field-error" style="margin-top:8px;">{{ form.errors.stationery }}</div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Lettertype') }}</div></div>
          <div class="card-body">
            <div class="font-options">
              <label class="font-option" :class="{ active: form.invoice_font === 'sans' }">
                <input type="radio" v-model="form.invoice_font" value="sans" hidden />
                <div class="font-sample" style="font-family:'DM Sans',sans-serif;">Aa</div>
                <div class="font-name">{{ $t('Sans-serif') }}</div>
              </label>
              <label class="font-option" :class="{ active: form.invoice_font === 'serif' }">
                <input type="radio" v-model="form.invoice_font" value="serif" hidden />
                <div class="font-sample" style="font-family:Georgia,serif;">Aa</div>
                <div class="font-name">{{ $t('Serif') }}</div>
              </label>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Voetnoot op factuur') }}</div></div>
          <div class="card-body">
            <textarea v-model="form.invoice_footer" rows="3" :placeholder="$t('Bedankt voor uw vertrouwen…')"></textarea>
          </div>
        </div>
      </div>

      <div class="preview-pane">
        <div class="preview-label">{{ $t('Live voorbeeld') }} — {{ templates.find(t => t.value === form.invoice_template)?.name }}</div>
        <div
          class="preview-frame"
          :class="`pv-${form.invoice_template} pv-font-${form.invoice_font}`"
          :style="{ '--brand': form.brand_color }"
        >
          <!-- ============ MODERN ============ -->
          <template v-if="form.invoice_template === 'modern'">
            <div class="pv-modern-header">
              <div>
                <img v-if="previewLogo" :src="previewLogo" class="pv-logo" :style="logoStyleModern" alt="" />
                <div v-else class="pv-logo-mark">{{ (company.name || 'E')[0] }}</div>
                <div class="pv-company-name">{{ company.name }}</div>
                <div class="pv-company-addr">
                  <template v-if="company.address_line">{{ company.address_line }}<br></template>
                  <template v-if="companyAddr">{{ companyAddr }}</template>
                </div>
              </div>
              <div style="text-align:right;">
                <div class="pv-doctype">{{ $t('FACTUUR') }}</div>
                <div class="pv-num">{{ pv.number }}</div>
              </div>
            </div>
            <div class="pv-body">
              <div class="pv-parties">
                <div class="pv-party">
                  <div class="pv-party-label">{{ $t('Afzender') }}</div>
                  <div class="pv-party-name">{{ company.name }}</div>
                  <div v-if="company.kvk_number">{{ market.registry.short }} {{ company.kvk_number }}</div>
                  <div v-if="company.vat_number">{{ market.tax_id.short }} {{ company.vat_number }}</div>
                </div>
                <div class="pv-party">
                  <div class="pv-party-label">{{ $t('Factuur aan') }}</div>
                  <div class="pv-party-name">{{ pv.customer.name }}</div>
                  <div>{{ pv.customer.addr }}</div>
                  <div>{{ pv.customer.city }}</div>
                </div>
              </div>
              <div class="pv-meta">
                <div><span>{{ $t('Factuurdatum') }}</span><strong>{{ pv.date }}</strong></div>
                <div><span>{{ $t('Vervaldatum') }}</span><strong>{{ pv.due }}</strong></div>
              </div>
              <table class="pv-lines">
                <thead><tr><th>{{ $t('Omschrijving') }}</th><th class="r">{{ $t('Aantal') }}</th><th class="r">{{ $t('Stuksprijs') }}</th><th class="c">{{ $t('Btw') }}</th><th class="r">{{ $t('Bedrag') }}</th></tr></thead>
                <tbody>
                  <tr v-for="(l, i) in pv.lines" :key="i">
                    <td>{{ l.desc }}</td><td class="r">{{ l.qty }}</td><td class="r">{{ nf(l.price) }}</td><td class="c">{{ l.vat }}%</td><td class="r">{{ nf(l.qty * l.price) }}</td>
                  </tr>
                </tbody>
              </table>
              <div class="pv-totals">
                <div><span>{{ $t('Subtotaal') }}</span><span>{{ nf(pv.subtotal) }}</span></div>
                <div><span>{{ $t('Btw') }} {{ pv.vatRate }}%</span><span>{{ nf(pv.vat) }}</span></div>
                <div class="pv-grand"><span>{{ $t('Te betalen') }}</span><span>{{ nf(pv.total) }}</span></div>
              </div>
              <div v-if="company.iban" class="pv-pay-note">
                {{ $t('Gelieve het bedrag binnen :days dagen te voldoen op :iban t.n.v. :name o.v.v. factuurnummer :number.', { days: pv.terms, iban: company.iban, name: company.name, number: pv.number }) }}
              </div>
              <div class="pv-footer">{{ footerText }}</div>
            </div>
          </template>

          <!-- ============ CLASSIC ============ -->
          <template v-else-if="form.invoice_template === 'classic'">
            <div class="pv-classic-header">
              <div style="text-align:center;">
                <img v-if="previewLogo" :src="previewLogo" class="pv-logo-c" :style="logoStyleClassic" alt="" />
                <div class="pv-classic-title">{{ $t('FACTUUR') }}</div>
                <div class="pv-classic-sub">{{ company.name }} · {{ pv.number }}</div>
              </div>
            </div>
            <div class="pv-body">
              <div class="pv-parties">
                <div class="pv-party">
                  <div class="pv-party-label">{{ $t('Afzender') }}</div>
                  <div class="pv-party-name">{{ company.name }}</div>
                  <div v-if="company.address_line">{{ company.address_line }}</div>
                  <div v-if="companyAddr">{{ companyAddr }}</div>
                  <div v-if="company.kvk_number">{{ market.registry.short }} {{ company.kvk_number }}</div>
                  <div v-if="company.vat_number">{{ market.tax_id.short }} {{ company.vat_number }}</div>
                </div>
                <div class="pv-party" style="text-align:right;">
                  <div class="pv-party-label">{{ $t('Factuur aan') }}</div>
                  <div class="pv-party-name">{{ pv.customer.name }}</div>
                  <div>{{ pv.customer.addr }}</div>
                  <div>{{ pv.customer.city }}</div>
                </div>
              </div>
              <div class="pv-classic-meta">
                <div><strong>{{ $t('Factuurdatum') }}:</strong> {{ pv.date }}</div>
                <div><strong>{{ $t('Vervaldatum') }}:</strong> {{ pv.due }}</div>
              </div>
              <table class="pv-lines pv-lines-classic">
                <thead><tr><th>{{ $t('Omschrijving') }}</th><th class="r">{{ $t('Aantal') }}</th><th class="r">{{ $t('Prijs') }}</th><th class="c">{{ $t('Btw') }}</th><th class="r">{{ $t('Totaal') }}</th></tr></thead>
                <tbody>
                  <tr v-for="(l, i) in pv.lines" :key="i">
                    <td>{{ l.desc }}</td><td class="r">{{ l.qty }}</td><td class="r">{{ nf(l.price) }}</td><td class="c">{{ l.vat }}%</td><td class="r">{{ nf(l.qty * l.price) }}</td>
                  </tr>
                </tbody>
              </table>
              <div class="pv-totals pv-totals-classic">
                <div><span>{{ $t('Subtotaal') }}</span><span>{{ nf(pv.subtotal) }}</span></div>
                <div><span>{{ $t('Btw') }} {{ pv.vatRate }}%</span><span>{{ nf(pv.vat) }}</span></div>
                <div class="pv-grand"><span>{{ $t('Te betalen') }}</span><span>{{ nf(pv.total) }}</span></div>
              </div>
              <div v-if="company.iban" class="pv-pay-note">
                {{ $t('Gelieve het bedrag binnen :days dagen te voldoen op :iban o.v.v. factuurnummer :number.', { days: pv.terms, iban: company.iban, number: pv.number }) }}
              </div>
              <div class="pv-footer pv-footer-classic">{{ footerText }}</div>
            </div>
          </template>

          <!-- ============ BRIEFPAPIER ============ -->
          <template v-else-if="form.invoice_template === 'stationery'">
            <div class="pv-stationery">
              <img v-if="previewStationery" :src="previewStationery" class="pv-stationery-bg" alt="" />
              <div
                class="pv-stationery-content"
                :style="{ top: (form.stationery_margin_top / 297 * 100) + '%', bottom: (form.stationery_margin_bottom / 297 * 100) + '%' }"
              >
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                  <div>
                    <div style="font-weight:700;font-size:13px;">{{ $t('FACTUUR') }}</div>
                    <div style="font-size:9px;color:#78716c;">{{ pv.number }}</div>
                  </div>
                  <div style="text-align:right;font-size:9px;color:#44403c;">
                    <div>{{ $t('Factuurdatum') }}: <b>{{ pv.date }}</b></div>
                    <div>{{ $t('Vervaldatum') }}: <b>{{ pv.due }}</b></div>
                  </div>
                </div>
                <div style="margin-top:10px;font-size:9px;">
                  <div style="font-size:8px;text-transform:uppercase;letter-spacing:0.07em;color:#78716c;">{{ $t('Factuur aan') }}</div>
                  <div style="font-weight:700;font-size:10px;">{{ pv.customer.name }}</div>
                  <div>{{ pv.customer.addr }} · {{ pv.customer.city }}</div>
                </div>
                <table class="pv-lines" style="margin-top:10px;">
                  <thead><tr><th>{{ $t('Omschrijving') }}</th><th class="r">{{ $t('Aantal') }}</th><th class="r">{{ $t('Prijs') }}</th><th class="r">{{ $t('Bedrag') }}</th></tr></thead>
                  <tbody>
                    <tr v-for="(l, i) in pv.lines" :key="i">
                      <td>{{ l.desc }}</td><td class="r">{{ l.qty }}</td><td class="r">{{ nf(l.price) }}</td><td class="r">{{ nf(l.qty * l.price) }}</td>
                    </tr>
                  </tbody>
                </table>
                <div class="pv-totals">
                  <div><span>{{ $t('Subtotaal') }}</span><span>{{ nf(pv.subtotal) }}</span></div>
                  <div><span>{{ $t('Btw') }} {{ pv.vatRate }}%</span><span>{{ nf(pv.vat) }}</span></div>
                  <div class="pv-grand" style="border-top:2px solid #1c1917;"><span>{{ $t('Te betalen') }}</span><span>{{ nf(pv.total) }}</span></div>
                </div>
              </div>
            </div>
          </template>

          <!-- ============ MINIMAL ============ -->
          <template v-else>
            <div class="pv-minimal-header">
              <img v-if="previewLogo" :src="previewLogo" class="pv-logo" :style="logoStyleModern" alt="" />
              <div class="pv-minimal-title">{{ $t('Factuur') }}</div>
              <div class="pv-minimal-num">{{ pv.number }} · {{ pv.date }}</div>
            </div>
            <div class="pv-body">
              <div class="pv-parties">
                <div class="pv-party">
                  <div class="pv-party-label">{{ $t('Afzender') }}</div>
                  <div class="pv-party-name">{{ company.name }}</div>
                  <div v-if="companyAddr">{{ companyAddr }}</div>
                  <div v-if="company.vat_number">{{ market.tax_id.short }} {{ company.vat_number }}</div>
                </div>
                <div class="pv-party">
                  <div class="pv-party-label">{{ $t('Factuur aan') }}</div>
                  <div class="pv-party-name">{{ pv.customer.name }}</div>
                  <div>{{ pv.customer.city }}</div>
                </div>
              </div>
              <div class="pv-meta">
                <div><span>{{ $t('Vervaldatum') }}</span><strong>{{ pv.due }}</strong></div>
              </div>
              <table class="pv-lines pv-lines-minimal">
                <tbody>
                  <tr v-for="(l, i) in pv.lines" :key="i">
                    <td>{{ l.desc }}</td><td class="r">{{ nf(l.qty * l.price) }}</td>
                  </tr>
                </tbody>
              </table>
              <div class="pv-totals pv-totals-minimal">
                <div><span>{{ $t('Subtotaal') }}</span><span>{{ nf(pv.subtotal) }}</span></div>
                <div><span>{{ $t('Btw') }} {{ pv.vatRate }}%</span><span>{{ nf(pv.vat) }}</span></div>
                <div class="pv-grand"><span>{{ $t('Te betalen') }}</span><span>{{ nf(pv.total) }}</span></div>
              </div>
              <div v-if="company.iban" class="pv-pay-note">
                {{ $t('Betaling binnen :days dagen op :iban o.v.v. :number.', { days: pv.terms, iban: company.iban, number: pv.number }) }}
              </div>
              <div class="pv-footer">{{ footerText }}</div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.huisstijl-layout { display: grid; grid-template-columns: minmax(0, 460px) minmax(0, 1fr); gap: 24px; align-items: flex-start; }
.huisstijl-settings { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
@media (max-width: 1000px) {
  /* Instellingen boven, live voorbeeld eronder — naast elkaar past niet meer. */
  .huisstijl-layout { grid-template-columns: minmax(0, 1fr); }
  .preview-pane { position: static; }
  .preview-frame { max-width: 420px; }
}
.logo-preview { max-width: 200px; max-height: 80px; display: block; }
.logo-upload-zone { display: block; padding: 28px; border: 2px dashed var(--border); border-radius: 10px; text-align: center; cursor: pointer; background: var(--surface-2); }
.logo-upload-zone:hover { border-color: var(--brand); background: var(--brand-tint); }

.logo-scale-row {
  margin-top: 18px;
  padding-top: 14px;
  border-top: 1px solid var(--border);
}
.logo-scale-label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  font-weight: 500;
  color: var(--text-2);
  margin-bottom: 8px;
}
.logo-scale-value {
  font-family: var(--font-mono);
  color: var(--brand);
  font-weight: 600;
}
.logo-scale-slider {
  width: 100%;
  height: 4px;
  -webkit-appearance: none;
  appearance: none;
  background: var(--surface-3);
  border-radius: 100px;
  outline: none;
  cursor: pointer;
}
.logo-scale-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 18px;
  height: 18px;
  background: var(--brand);
  border: 2px solid white;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
  cursor: pointer;
}
.logo-scale-slider::-moz-range-thumb {
  width: 18px;
  height: 18px;
  background: var(--brand);
  border: 2px solid white;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
  cursor: pointer;
}
.logo-scale-ticks {
  display: flex;
  justify-content: space-between;
  margin-top: 6px;
  font-size: 10px;
  color: var(--text-4);
  font-family: var(--font-mono);
}
.upload-hint { font-size: 12px; color: var(--text-3); margin-bottom: 8px; }
.upload-cta { font-size: 14px; color: var(--text); }
.color-presets { display: flex; gap: 6px; margin-top: 8px; }
.color-preset { width: 22px; height: 22px; border-radius: 50%; cursor: pointer; border: 2px solid white; box-shadow: 0 0 0 1px var(--border); }
.color-preset.active { box-shadow: 0 0 0 2px var(--text); }

/* Template selector cards */
.template-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.template-card { padding: 10px; border: 2px solid var(--border); border-radius: 10px; cursor: pointer; text-align: center; transition: all 0.15s; }
.template-card:hover { border-color: var(--text-4); }
.template-card.active { border-color: var(--brand); background: var(--brand-tint); }
.template-name { font-weight: 600; font-size: 12px; margin-top: 8px; }
.template-desc { font-size: 10px; color: var(--text-3); }

/* Thumbnails */
.template-thumb {
  aspect-ratio: 3/4;
  background: white;
  border-radius: 4px;
  position: relative;
  overflow: hidden;
  padding: 8px;
  box-shadow: inset 0 0 0 1px var(--border);
}
.tt-line { height: 3px; background: #E7E5E4; border-radius: 2px; margin-top: 5px; }
.tt-line-1 { width: 80%; }
.tt-line-2 { width: 65%; }
.tt-line-3 { width: 70%; }
.tt-line-4 { width: 55%; }

/* Modern thumb */
.thumb-modern .tt-band { position: absolute; top: 0; left: 0; right: 0; height: 14px; background: var(--brand); }
.thumb-modern .tt-line-1 { margin-top: 22px; }
.thumb-modern .tt-total { position: absolute; bottom: 8px; right: 8px; width: 35%; height: 6px; background: var(--brand); border-radius: 2px; }

/* Classic thumb */
.thumb-classic { padding-top: 12px; }
.thumb-classic .tt-title-c { width: 50%; height: 4px; background: var(--text); margin: 0 auto 10px; }
.thumb-classic .tt-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px; }
.thumb-classic .tt-grid span { height: 3px; background: #E7E5E4; }
.thumb-classic .tt-total-c { margin-top: 12px; height: 5px; background: var(--text); width: 100%; }

/* Minimal thumb */
.thumb-minimal { padding-top: 14px; }
.thumb-minimal .tt-title-m { width: 40%; height: 4px; background: var(--text); margin-bottom: 14px; }
.thumb-minimal .tt-total-m { margin-top: 14px; height: 4px; background: var(--text); width: 30%; margin-left: auto; }

/* Briefpapier */
.template-card.disabled { opacity: 0.55; }
.tt-stationery-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.tt-stationery-empty { display: flex; align-items: center; justify-content: center; height: 100%; font-size: 9px; color: var(--text-3); text-align: center; line-height: 1.4; }
.stationery-preview { max-width: 100%; max-height: 220px; display: block; border: 1px solid var(--border); border-radius: 8px; }
.stationery-hint { font-size: 12px; color: var(--text-3); margin-top: 10px; line-height: 1.6; }
.ai-msg { margin-top: 10px; font-size: 12.5px; border-radius: 8px; padding: 8px 10px; line-height: 1.5; }
.ai-ok { background: var(--success-bg); color: var(--success); }

.pv-stationery { position: relative; width: 100%; height: 100%; }
.pv-stationery-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.pv-stationery-content { position: absolute; left: 8.6%; right: 8.6%; overflow: hidden; font-size: 10px; }

/* Fonts */
.font-options { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.font-option { padding: 12px; border: 2px solid var(--border); border-radius: 10px; cursor: pointer; text-align: center; }
.font-option.active { border-color: var(--brand); background: var(--brand-tint); }
.font-sample { font-size: 20px; font-weight: 600; }
.font-name { font-size: 11px; color: var(--text-3); }

/* Preview pane */
.preview-pane { position: sticky; top: 96px; }
.preview-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-3); margin-bottom: 10px; font-weight: 600; }
.preview-frame {
  background: white;
  border: 1px solid var(--border);
  border-radius: 10px;
  box-shadow: var(--shadow-lg);
  aspect-ratio: 210/297;
  overflow: hidden;
  font-family: 'DM Sans', sans-serif;
  font-size: 11px;
  color: #1C1917;
}
.pv-font-serif { font-family: Georgia, 'Times New Roman', serif; }

.pv-body { padding: 18px 24px; }
.pv-logo { max-width: 100px; max-height: 36px; display: block; margin-bottom: 8px; }
.pv-logo-mark {
  width: 28px; height: 28px;
  background: var(--brand);
  color: white;
  border-radius: 5px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  margin-bottom: 6px;
}

.pv-lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
.pv-lines th { text-align: left; padding: 6px 4px; font-size: 9px; text-transform: uppercase; color: var(--text-3); border-bottom: 1px solid var(--border); }
.pv-lines td { padding: 5px 4px; }
.pv-lines .r { text-align: right; font-variant-numeric: tabular-nums; }

.pv-totals { margin-top: 14px; margin-left: auto; width: 55%; font-size: 11px; }
.pv-totals > div { display: flex; justify-content: space-between; padding: 3px 0; color: var(--text-2); }
.pv-totals .pv-grand { margin-top: 6px; padding-top: 8px; font-weight: 700; font-size: 12px; color: var(--text); }
.pv-footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid var(--border); font-size: 9px; color: var(--text-3); }

/* ============ MODERN variant ============ */
.pv-modern .pv-modern-header {
  padding: 22px 24px 14px;
  border-bottom: 4px solid var(--brand);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.pv-modern .pv-doctype { font-weight: 800; font-size: 22px; color: var(--brand); letter-spacing: -0.5px; }
.pv-modern .pv-num { font-family: var(--font-mono); font-size: 10px; color: var(--text-3); margin-top: 4px; }
.pv-modern .pv-company-name { font-weight: 700; font-size: 13px; }
.pv-modern .pv-company-addr { font-size: 10px; color: var(--text-3); }
.pv-modern .pv-grand { border-top: 2px solid var(--brand); color: var(--brand); }

/* ============ CLASSIC variant ============ */
.pv-classic .pv-classic-header {
  padding: 22px 24px 16px;
  border-bottom: 2px double var(--brand);
}
.pv-classic .pv-classic-title {
  font-weight: 700;
  font-size: 20px;
  letter-spacing: 4px;
  margin-top: 6px;
  color: var(--brand);
}
.pv-classic .pv-classic-sub {
  font-size: 10px;
  color: var(--text-3);
  font-style: italic;
  margin-top: 4px;
}
.pv-classic .pv-logo-c {
  max-width: 70px;
  max-height: 36px;
  display: block;
  margin: 0 auto;
}
.pv-classic .pv-classic-meta {
  display: flex;
  justify-content: space-between;
  padding: 10px 24px;
  border-bottom: 1px solid var(--border);
  font-size: 10px;
}
.pv-classic .pv-lines-classic th,
.pv-classic .pv-lines-classic td {
  border: 1px solid #d1d1d1;
  padding: 5px 6px;
}
.pv-classic .pv-lines-classic th { background: var(--brand); color: #fff; font-weight: 700; border-color: var(--brand); }
.pv-classic .pv-totals-classic > div { border-bottom: 1px solid #ddd; }
.pv-classic .pv-grand { border-bottom: 3px double var(--brand) !important; border-top: 2px solid var(--brand); }
.pv-classic .pv-footer-classic { text-align: center; font-style: italic; }

/* ============ MINIMAL variant ============ */
.pv-minimal .pv-minimal-header {
  padding: 32px 24px 18px;
  border-bottom: none;
}
.pv-minimal .pv-minimal-title {
  font-weight: 300;
  font-size: 28px;
  letter-spacing: -1px;
  color: var(--brand);
}
.pv-minimal .pv-minimal-num {
  font-size: 10px;
  color: var(--text-3);
  margin-top: 4px;
}
.pv-minimal .pv-body { padding-top: 6px; }
.pv-minimal .pv-minimal-from { margin-bottom: 18px; font-size: 11px; }
.pv-minimal .pv-lines-minimal td {
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}
.pv-minimal .pv-lines-minimal td:first-child { color: var(--text-2); }
.pv-minimal .pv-grand { border-top: 2px solid var(--brand); padding-top: 10px; }

/* ---- Gedeelde velden (afzender/afnemer, meta, betaalregel) ---- */
.pv-lines .c { text-align: center; }
.pv-parties { display: flex; gap: 20px; margin-bottom: 12px; }
.pv-parties .pv-party { flex: 1; font-size: 10px; color: var(--text-3); line-height: 1.5; }
.pv-party-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.07em; color: #b7b3ae; margin-bottom: 3px; }
.pv-party-name { font-weight: 700; color: var(--text); font-size: 11px; }
.pv-meta { display: flex; gap: 24px; margin-bottom: 10px; font-size: 10px; }
.pv-meta > div { display: flex; flex-direction: column; }
.pv-meta span { color: var(--text-3); font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.05em; }
.pv-meta strong { font-weight: 600; color: var(--text); }
.pv-pay-note { margin-top: 12px; padding: 8px 10px; background: #faf7f2; border-left: 2px solid var(--brand); border-radius: 4px; font-size: 9px; color: var(--text-2); line-height: 1.5; }

/* Modern: meta met dunne lijnen */
.pv-modern .pv-meta { border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 8px 0; }
/* Classic: meta binnen body i.p.v. volle breedte */
.pv-classic .pv-parties { border-bottom: 1px solid var(--border); padding-bottom: 10px; }
.pv-classic .pv-classic-meta { padding: 10px 0; margin-bottom: 6px; border-bottom: 1px solid var(--border); }
.pv-classic .pv-pay-note { background: transparent; border-left: none; border-top: 1px solid var(--border); border-radius: 0; padding: 8px 0 0; }
/* Minimal: rustiger */
.pv-minimal .pv-parties { gap: 28px; margin-bottom: 16px; }
.pv-minimal .pv-pay-note { background: transparent; border-left: none; padding: 10px 0 0; color: var(--text-3); }
.pv-minimal .pv-meta { margin-top: 2px; }
.dir-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-top: 14px; }
.dir { border: 1px solid var(--border); border-radius: 12px; padding: 12px; display: flex; flex-direction: column; gap: 6px; background: var(--surface); }
.dir.chosen { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-tint); }
.dir-swatches { display: flex; gap: 6px; }
.dir-swatches span { width: 28px; height: 28px; border-radius: 8px; display: block; border: 1px solid rgba(0,0,0,.06); }
.dir-logo { height: 40px; width: 100%; object-fit: contain; object-position: left; }
.dir-name { font-weight: 700; }
.dir-meta, .dir-why { font-size: 12px; color: var(--text-2); line-height: 1.45; }
.dir-tag { font-size: 13px; font-style: italic; }
</style>
