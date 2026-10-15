<script setup>
/**
 * Het Fachora-profiel van het bedrijf: wat klanten zien op de openbare
 * profielpagina en waarop wij opdrachten toewijzen (categorieën, talen,
 * werkgebied, tarieven, garantie). Eén formulier, met de foto als upload.
 */
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FachoraBadge from '@/Components/FachoraBadge.vue';
import { icon } from '@/fachora-icons.js';

const props = defineProps({
  provider: Object,
  categories: { type: Array, default: () => [] },
  languages: { type: Array, default: () => [] },
  levels: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

// Talen: Pools staat vast op 'ojczysty'; de andere talen kiest het bedrijf met een niveau (of niets).
const languageDefaults = () => {
  const out = {};
  for (const l of props.languages) out[l.code] = l.code === 'pl' ? 'native' : (props.provider.languages_raw?.[l.code] || '');
  return out;
};

const form = useForm({
  // De presenter geeft 'name' (display_name); 'display_name' als vangnet voor als dat ooit verandert.
  display_name: props.provider.display_name ?? props.provider.name ?? '',
  description: props.provider.description || '',
  phone: props.provider.phone || '',
  website: props.provider.website || '',
  address_line: props.provider.address_line || '',
  postal_code: props.provider.postal_code || '',
  city: props.provider.city || '',
  categories: [...(props.provider.categories || [])],
  languages: languageDefaults(),
  radius_km: props.provider.radius_km ?? 25,
  rates: {
    callout: props.provider.rates?.callout ?? '',
    first_hour: props.provider.rates?.first_hour ?? '',
    extra_hour: props.provider.rates?.extra_hour ?? '',
  },
  warranty_months: props.provider.warranty_months ?? 12,
  invoice_promise: props.provider.invoice !== false,
  // 'protect' in de props is de gereedheid (Stripe), niet de keuze zelf; de keuze staat standaard aan.
  protect_opt_in: props.provider.protect_opt_in ?? true,
  photo: null,
});

const toggleCategory = (key) => {
  const i = form.categories.indexOf(key);
  if (i >= 0) form.categories.splice(i, 1); else form.categories.push(key);
};

const preview = ref(props.provider.photo || null);
const pickPhoto = (event) => {
  const file = event.target.files?.[0] || null;
  form.photo = file;
  preview.value = file ? URL.createObjectURL(file) : (props.provider.photo || null);
};

const save = () => form
  .transform((d) => ({
    ...d,
    languages: Object.fromEntries(Object.entries(d.languages).filter(([, level]) => level)),
    rates: Object.fromEntries(Object.entries(d.rates).map(([k, v]) => [k, v === '' || v === null ? '' : Number(String(v).replace(',', '.')) || 0])),
  }))
  .post(route('fachora.profile.update'), { forceFormData: true, preserveScroll: true, onSuccess: () => { form.photo = null; } });

const radiusLabel = computed(() => form.radius_km + ' km');
</script>

<template>
  <Head :title="$t('Fachora-profiel')" />
  <AppLayout>
    <template #breadcrumb>Fachora / <span class="breadcrumb-current">{{ $t('Fachora-profiel') }}</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="save">{{ $t('Opslaan') }}</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Fachora-profiel') }}</h1>
        <p class="page-subtitle">{{ $t('Wat klanten van u zien, en waarop wij opdrachten toewijzen: categorieën, talen, werkgebied en tarieven.') }}</p>
      </div>
      <div class="page-actions">
        <a :href="provider.url" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">{{ $t('Openbaar profiel') }} ↗</a>
        <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="save">{{ $t('Opslaan') }}</button>
      </div>
    </div>

    <div v-if="flash.error" class="fx-alert fx-alert--err">{{ flash.error }}</div>
    <div v-if="Object.keys(form.errors).length" class="fx-alert fx-alert--err">{{ $t('Niet alle velden zijn goed ingevuld; kijk de rode meldingen na.') }}</div>

    <div class="fx-grid">
      <form class="fx-main" @submit.prevent="save">
        <!-- Bedrijf -->
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Bedrijf') }}</div></div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group">
                <label>{{ $t('Naam op Fachora') }} *</label>
                <input type="text" v-model="form.display_name" maxlength="160">
                <div v-if="form.errors.display_name" class="field-error">{{ form.errors.display_name }}</div>
              </div>
              <div class="form-group">
                <label>{{ $t('Officiële naam en NIP') }} <span class="label-hint">{{ $t('(uit het register)') }}</span></label>
                <input type="text" :value="[provider.legal_name, provider.nip ? 'NIP ' + provider.nip : ''].filter(Boolean).join(' · ')" disabled>
              </div>
            </div>
            <div class="form-group">
              <label>{{ $t('Over het bedrijf') }} <span class="label-hint">{{ $t('(staat op uw profielpagina; vertel wat u doet en wat de klant kan verwachten)') }}</span></label>
              <textarea v-model="form.description" rows="5" maxlength="2000"></textarea>
              <div v-if="form.errors.description" class="field-error">{{ form.errors.description }}</div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>{{ $t('Telefoon') }} <span class="label-hint">{{ $t('(mobiel, voor sms-bevestiging)') }}</span></label>
                <input type="text" v-model="form.phone" maxlength="40" placeholder="+48 600 000 000">
                <div v-if="form.errors.phone" class="field-error">{{ form.errors.phone }}</div>
              </div>
              <div class="form-group">
                <label>{{ $t('Website') }}</label>
                <input type="text" v-model="form.website" maxlength="180" placeholder="https://">
                <div v-if="form.errors.website" class="field-error">{{ form.errors.website }}</div>
              </div>
            </div>
            <div class="form-group">
              <label>{{ $t('Vestigingsadres') }} <span class="label-hint">{{ $t('(klanten zien alleen de plaats)') }}</span></label>
              <input type="text" v-model="form.address_line" maxlength="200">
              <div v-if="form.errors.address_line" class="field-error">{{ form.errors.address_line }}</div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>{{ $t('Postcode') }}</label>
                <input type="text" v-model="form.postal_code" maxlength="6" placeholder="00-000">
                <div v-if="form.errors.postal_code" class="field-error">{{ form.errors.postal_code }}</div>
              </div>
              <div class="form-group">
                <label>{{ $t('Plaats') }} *</label>
                <input type="text" v-model="form.city" maxlength="120">
                <div v-if="form.errors.city" class="field-error">{{ form.errors.city }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Categorieën -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Categorieën') }} *</div>
              <div class="card-subtitle">{{ $t('U krijgt alleen opdrachten uit de categorieën die u aanvinkt.') }}</div>
            </div>
          </div>
          <div class="card-body">
            <div class="fx-tiles">
              <button v-for="c in categories" :key="c.key" type="button" class="fx-cat" :class="{ on: form.categories.includes(c.key) }" @click="toggleCategory(c.key)">
                <span class="fx-cat-icon" v-html="icon(c.icon, 22)"></span>
                <span class="fx-cat-name">{{ c.name }}</span>
                <span v-if="c.examples" class="fx-cat-ex">{{ c.examples }}</span>
              </button>
            </div>
            <div v-if="form.errors.categories" class="field-error">{{ form.errors.categories }}</div>
          </div>
        </div>

        <!-- Talen en werkgebied -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Talen en werkgebied') }}</div>
              <div class="card-subtitle">{{ $t('Een klant die geen Pools spreekt ziet alleen bedrijven die zijn taal aangeven. Wees eerlijk over het niveau.') }}</div>
            </div>
          </div>
          <div class="card-body">
            <div class="fx-langs">
              <div v-for="l in languages" :key="l.code" class="fx-lang">
                <span class="fx-lang-name">{{ l.flag }} {{ l.name }}</span>
                <select v-if="l.code !== 'pl'" v-model="form.languages[l.code]">
                  <option value="">{{ $t('— spreek ik niet') }}</option>
                  <option v-for="lv in levels" :key="lv.code" :value="lv.code">{{ lv.name }}</option>
                </select>
                <span v-else class="sub">{{ (levels.find((lv) => lv.code === 'native') || {}).name || 'native' }}</span>
              </div>
            </div>
            <div v-if="form.errors.languages" class="field-error">{{ form.errors.languages }}</div>

            <div class="form-group" style="margin-top:18px;">
              <label>{{ $t('Werkgebied rond uw vestiging') }}: <b>{{ radiusLabel }}</b></label>
              <div class="fx-radius">
                <input type="range" min="5" max="200" step="5" v-model.number="form.radius_km">
                <input type="number" min="5" max="200" v-model.number="form.radius_km" style="width:90px;">
              </div>
              <div class="sub">{{ $t('Opdrachten komen alleen uit deze straal. Kleiner is vaak beter: minder reistijd, snellere reactie.') }}</div>
              <div v-if="form.errors.radius_km" class="field-error">{{ form.errors.radius_km }}</div>
            </div>
          </div>
        </div>

        <!-- Tarieven en garantie -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Tarieven en garantie') }}</div>
              <div class="card-subtitle">{{ $t('Fachora Standard 03: duidelijke prijzen vooraf. Richttarieven staan op uw profiel; de echte prijs geeft u per opdracht.') }}</div>
            </div>
          </div>
          <div class="card-body">
            <div class="form-row-3">
              <div class="form-group">
                <label>{{ $t('Voorrijkosten (zł)') }}</label>
                <input type="text" inputmode="decimal" v-model="form.rates.callout" placeholder="0">
                <div v-if="form.errors['rates.callout']" class="field-error">{{ form.errors['rates.callout'] }}</div>
              </div>
              <div class="form-group">
                <label>{{ $t('Eerste uur (zł)') }}</label>
                <input type="text" inputmode="decimal" v-model="form.rates.first_hour" placeholder="0">
                <div v-if="form.errors['rates.first_hour']" class="field-error">{{ form.errors['rates.first_hour'] }}</div>
              </div>
              <div class="form-group">
                <label>{{ $t('Elk volgend uur (zł)') }}</label>
                <input type="text" inputmode="decimal" v-model="form.rates.extra_hour" placeholder="0">
                <div v-if="form.errors['rates.extra_hour']" class="field-error">{{ form.errors['rates.extra_hour'] }}</div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>{{ $t('Garantie op het werk (maanden)') }} *</label>
                <input type="number" min="0" max="60" v-model.number="form.warranty_months">
                <div class="sub">{{ $t('Fachora Standard 07: standaard 12 maanden waar van toepassing.') }}</div>
                <div v-if="form.errors.warranty_months" class="field-error">{{ form.errors.warranty_months }}</div>
              </div>
            </div>
            <label class="checkbox-row">
              <input type="checkbox" v-model="form.invoice_promise">
              <span><b>{{ $t('Ik geef altijd een factuur') }}</b><br><span class="sub">{{ $t('Fachora Standard 06. Klanten filteren hierop; de factuur maakt u in deze administratie.') }}</span></span>
            </label>
            <label class="checkbox-row">
              <input type="checkbox" v-model="form.protect_opt_in">
              <span><b>{{ $t('Ik doe mee met Fachora Protect') }}</b><br><span class="sub">{{ $t('De klant reserveert het bedrag vooraf via Fachora; na goedkeuring wordt het, minus de fee, naar uw rekening overgemaakt. Vereist de Stripe-stap in de verificatie.') }}</span>
                <span v-if="form.protect_opt_in && !provider.protect" class="sub fx-warn"><br>{{ $t('Nog niet actief: rond de Stripe-stap af bij Verificatie.') }}</span></span>
            </label>
          </div>
        </div>

        <!-- Foto -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Foto of logo') }}</div>
              <div class="card-subtitle">{{ $t('Een foto van uzelf of uw bus werkt beter dan een logo: klanten kiezen een mens.') }}</div>
            </div>
          </div>
          <div class="card-body fx-photo-row">
            <div class="fx-avatar"><img v-if="preview" :src="preview" alt=""><span v-else v-html="icon('tools', 28)"></span></div>
            <div>
              <input type="file" accept="image/*" class="fx-file-input" @change="pickPhoto">
              <div class="sub">{{ $t('JPG of PNG, maximaal 1 MB.') }}</div>
              <div v-if="form.errors.photo" class="field-error">{{ form.errors.photo }}</div>
            </div>
          </div>
        </div>

        <div class="fx-save">
          <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ form.processing ? $t('Bezig…') : $t('Profiel opslaan') }}</button>
          <span v-if="form.recentlySuccessful" class="sub fx-ok">{{ $t('Opgeslagen') }}</span>
        </div>
      </form>

      <!-- Rechterkolom: wat de klant ziet -->
      <div class="fx-side">
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Zo ziet de klant u') }}</div></div>
          <div class="card-body">
            <div class="fx-preview">
              <div class="fx-avatar fx-avatar--sm"><img v-if="preview" :src="preview" alt=""><span v-else v-html="icon('tools', 20)"></span></div>
              <div>
                <div class="fx-preview-name">{{ form.display_name || '—' }}</div>
                <div class="sub">{{ form.city || '—' }} · {{ radiusLabel }}</div>
                <FachoraBadge :verified="provider.verified" small style="margin-top:6px;" />
              </div>
            </div>
            <ul class="fx-list">
              <li>{{ $t('Naam, plaats, foto en uw beschrijving') }}</li>
              <li>{{ $t('Het keurmerk Fachora Verified (na verificatie)') }}</li>
              <li>{{ $t('Categorieën, talen en werkgebied') }}</li>
              <li>{{ $t('Richttarieven, garantie en of u een factuur geeft') }}</li>
              <li>{{ $t('Beoordelingen van klanten met uw reactie') }}</li>
              <li>{{ $t('Niet: uw adres, e-mail en telefoonnummer. Contact loopt via de opdracht.') }}</li>
            </ul>
            <a :href="provider.url" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="margin-top:12px;">{{ $t('Openbaar profiel bekijken') }} ↗</a>
          </div>
        </div>
        <div v-if="provider.status !== 'verified'" class="card">
          <div class="card-body">
            <div class="card-title" style="margin-bottom:6px;">{{ $t('Nog niet geverifieerd') }}</div>
            <p class="sub" style="margin:0 0 10px;line-height:1.6;">{{ $t('Uw profiel is vindbaar, maar zonder keurmerk bieden wij u geen opdrachten aan. De verificatie kost een paar minuten.') }}</p>
            <Link :href="route('fachora.verification')" class="btn btn-primary btn-sm">{{ $t('Naar de verificatie') }}</Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.fx-alert { padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 14px; }
.fx-alert--err { background: var(--brand-tint-2); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.fx-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 16px; align-items: start; }
.fx-main, .fx-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.fx-main .card, .fx-side .card { margin: 0; }
@media (max-width: 900px) { .fx-grid { grid-template-columns: 1fr; } }
.fx-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 8px; }
.fx-cat { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); cursor: pointer; text-align: left; font: inherit; color: var(--text-2); }
.fx-cat:hover { border-color: var(--border-strong); }
.fx-cat.on { border-color: var(--brand); background: var(--brand-tint); color: var(--brand); }
.fx-cat-icon { color: inherit; }
.fx-cat-name { font-weight: 600; font-size: 13.5px; }
.fx-cat-ex { font-size: 11px; color: var(--text-3); line-height: 1.4; }
.fx-langs { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px 16px; }
.fx-lang { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 6px 0; border-bottom: 1px solid var(--border); font-size: 13.5px; }
.fx-lang select { height: 32px; padding: 0 8px; border: 1px solid var(--border); border-radius: 6px; font: inherit; font-size: 13px; max-width: 160px; }
.fx-radius { display: flex; align-items: center; gap: 12px; }
.fx-radius input[type=range] { flex: 1; height: auto; padding: 0; border: 0; }
.fx-warn { color: var(--warning); }
.fx-ok { color: var(--success); font-weight: 600; }
.fx-photo-row { display: flex; align-items: center; gap: 16px; }
.fx-avatar { width: 84px; height: 84px; border-radius: 12px; background: var(--surface-2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; overflow: hidden; color: var(--text-3); flex: none; }
.fx-avatar img { width: 100%; height: 100%; object-fit: cover; }
.fx-avatar--sm { width: 56px; height: 56px; }
.fx-file-input { width: auto !important; height: auto !important; padding: 0 !important; border: 0 !important; }
.fx-save { display: flex; align-items: center; gap: 12px; }
.fx-preview { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; }
.fx-preview-name { font-weight: 600; font-size: 15px; }
.fx-list { margin: 0; padding-left: 18px; font-size: 13px; line-height: 1.7; color: var(--text-2); }
</style>
