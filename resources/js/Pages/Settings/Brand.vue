<script setup>
import { ref, reactive, computed } from 'vue';
import { router, useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  company: Object,
});

const form = useForm({
  brand_color: props.company.brand_color || '#E8231F',
  accent_color: props.company.accent_color || '#1C1917',
  invoice_template: props.company.invoice_template || 'modern',
  invoice_font: props.company.invoice_font || 'sans',
  invoice_footer: props.company.invoice_footer || '',
  logo: null,
});

const colorPresets = ['#E8231F', '#0F172A', '#1E40AF', '#15803D', '#7C3AED', '#DB2777', '#EA580C', '#0891B2'];
const templates = [
  { value: 'modern', name: 'Modern', desc: 'Kleurband, sterk' },
  { value: 'classic', name: 'Klassiek', desc: 'Formeel, gelijnd' },
  { value: 'minimal', name: 'Minimaal', desc: 'Veel ruimte, rustig' },
];

const onLogoChange = (e) => {
  form.logo = e.target.files[0];
};

const submit = () => {
  form.post(route('settings.brand.update'), { forceFormData: true, preserveScroll: true });
};

const removeLogo = () => {
  router.delete(route('settings.brand.logo.remove'), { preserveScroll: true });
};
</script>

<template>
  <Head title="Huisstijl" />
  <AppLayout>
    <template #breadcrumb>Instellingen / <span class="breadcrumb-current">Huisstijl</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" @click="submit" :disabled="form.processing">Opslaan</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Huisstijl</h1>
        <p class="page-subtitle">Pas logo, kleuren en lay-out aan — alles wat je hier wijzigt verschijnt op je facturen</p>
      </div>
    </div>

    <div class="huisstijl-layout">
      <div class="huisstijl-settings">
        <div class="card">
          <div class="card-header"><div class="card-title">Logo</div></div>
          <div class="card-body">
            <div v-if="company.logo_path">
              <img :src="`/storage/${company.logo_path}`" class="logo-preview" />
              <div style="display:flex;gap:8px;margin-top:10px;">
                <label class="btn btn-ghost btn-sm">
                  Vervangen
                  <input type="file" accept="image/*" @change="onLogoChange" style="display:none;" />
                </label>
                <button class="btn btn-danger btn-sm" @click="removeLogo">Verwijderen</button>
              </div>
            </div>
            <label v-else class="logo-upload-zone">
              <input type="file" accept="image/*" @change="onLogoChange" style="display:none;" />
              <div class="upload-hint">PNG, JPG of SVG — max 2 MB</div>
              <div class="upload-cta"><b>Klik om te uploaden</b></div>
            </label>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">Huiskleur</div></div>
          <div class="card-body">
            <div class="form-group">
              <label>Primaire kleur</label>
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
              <div class="card-title">Factuur-lay-out</div>
              <div class="card-subtitle">Kies een sjabloon</div>
            </div>
          </div>
          <div class="card-body">
            <div class="template-cards">
              <div v-for="t in templates" :key="t.value"
                class="template-card"
                :class="{ active: form.invoice_template === t.value }"
                @click="form.invoice_template = t.value">
                <div class="template-thumb"></div>
                <div class="template-name">{{ t.name }}</div>
                <div class="template-desc">{{ t.desc }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">Lettertype</div></div>
          <div class="card-body">
            <div class="font-options">
              <label class="font-option" :class="{ active: form.invoice_font === 'sans' }">
                <input type="radio" v-model="form.invoice_font" value="sans" hidden />
                <div class="font-sample" style="font-family:'DM Sans',sans-serif;">Aa</div>
                <div class="font-name">Sans-serif</div>
              </label>
              <label class="font-option" :class="{ active: form.invoice_font === 'serif' }">
                <input type="radio" v-model="form.invoice_font" value="serif" hidden />
                <div class="font-sample" style="font-family:Georgia,serif;">Aa</div>
                <div class="font-name">Serif</div>
              </label>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">Voetnoot op factuur</div></div>
          <div class="card-body">
            <textarea v-model="form.invoice_footer" rows="3" placeholder="Bedankt voor uw vertrouwen…"></textarea>
          </div>
        </div>
      </div>

      <div class="preview-pane">
        <div class="preview-label">Live voorbeeld</div>
        <div class="preview-frame" :style="{ '--brand': form.brand_color }">
          <div class="pv-header">
            <div class="pv-doctype">FACTUUR</div>
            <div class="pv-num">2026-0007</div>
          </div>
          <div class="pv-body">
            <div style="font-weight:600;margin-bottom:6px;">{{ company.name }}</div>
            <div style="font-size:12px;color:#666;margin-bottom:16px;">{{ company.address_line || 'Voorbeeldstraat 1, Amsterdam' }}</div>
            <table class="pv-lines">
              <thead><tr><th>Omschrijving</th><th class="r">Bedrag</th></tr></thead>
              <tbody>
                <tr><td>Webdesign basispakket</td><td class="r">€ 1.250,00</td></tr>
                <tr><td>Hosting jaarpakket</td><td class="r">€ 180,00</td></tr>
              </tbody>
              <tfoot><tr><td>Totaal</td><td class="r" style="color:var(--brand);font-weight:700;">€ 1.730,30</td></tr></tfoot>
            </table>
            <div v-if="form.invoice_footer" class="pv-footer">{{ form.invoice_footer }}</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.huisstijl-layout { display: grid; grid-template-columns: 460px 1fr; gap: 24px; align-items: flex-start; }
.huisstijl-settings { display: flex; flex-direction: column; gap: 14px; }
.logo-preview { max-width: 200px; max-height: 80px; }
.logo-upload-zone { display: block; padding: 28px; border: 2px dashed var(--border); border-radius: 10px; text-align: center; cursor: pointer; background: var(--surface-2); }
.color-presets { display: flex; gap: 6px; margin-top: 8px; }
.color-preset { width: 22px; height: 22px; border-radius: 50%; cursor: pointer; border: 2px solid white; box-shadow: 0 0 0 1px var(--border); }
.color-preset.active { box-shadow: 0 0 0 2px var(--text); }
.template-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.template-card { padding: 12px; border: 2px solid var(--border); border-radius: 10px; cursor: pointer; text-align: center; }
.template-card.active { border-color: var(--brand); background: var(--brand-tint); }
.template-thumb { aspect-ratio: 3/4; background: var(--surface-2); border-radius: 4px; margin-bottom: 8px; }
.template-name { font-weight: 600; font-size: 12px; }
.template-desc { font-size: 10px; color: var(--text-3); }
.font-options { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.font-option { padding: 12px; border: 2px solid var(--border); border-radius: 10px; cursor: pointer; text-align: center; }
.font-option.active { border-color: var(--brand); background: var(--brand-tint); }
.font-sample { font-size: 20px; font-weight: 600; }
.font-name { font-size: 11px; color: var(--text-3); }
.preview-pane { position: sticky; top: 96px; }
.preview-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-3); margin-bottom: 10px; }
.preview-frame { background: white; border: 1px solid var(--border); border-radius: 10px; box-shadow: var(--shadow-lg); aspect-ratio: 210/297; }
.pv-header { padding: 24px 28px; border-bottom: 4px solid var(--brand); display: flex; justify-content: space-between; }
.pv-doctype { font-family: var(--font-display); font-weight: 700; font-size: 22px; color: var(--brand); }
.pv-num { font-family: var(--font-mono); font-size: 11px; color: var(--text-3); align-self: flex-start; margin-top: 8px; }
.pv-body { padding: 22px 28px; font-size: 12px; }
.pv-lines { width: 100%; margin-top: 12px; }
.pv-lines th { text-align: left; padding: 6px 0; border-bottom: 1px solid var(--border); font-size: 10px; text-transform: uppercase; color: var(--text-3); }
.pv-lines td { padding: 6px 0; }
.pv-lines .r { text-align: right; font-family: var(--font-mono); }
.pv-lines tfoot td { border-top: 2px solid var(--brand); padding-top: 8px; font-weight: 600; font-size: 14px; }
.pv-footer { margin-top: 22px; padding-top: 12px; border-top: 1px solid var(--border); font-size: 10px; color: var(--text-3); }
</style>
