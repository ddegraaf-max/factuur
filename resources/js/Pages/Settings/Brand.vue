<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  company: Object,
});

const defaultFooter = 'Bedankt voor uw vertrouwen! Gelieve het factuurbedrag binnen de betaaltermijn te voldoen onder vermelding van het factuurnummer. Heeft u vragen over deze factuur? Neem gerust contact met ons op.';

const form = useForm({
  brand_color: props.company.brand_color || '#E8231F',
  accent_color: props.company.accent_color || '#1C1917',
  invoice_template: props.company.invoice_template || 'modern',
  invoice_font: props.company.invoice_font || 'sans',
  invoice_footer: props.company.invoice_footer || defaultFooter,
  logo_scale: props.company.logo_scale || 100,
  logo: null,
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
  { value: 'modern', name: 'Modern', desc: 'Kleurband, sterk' },
  { value: 'classic', name: 'Klassiek', desc: 'Formeel, gelijnd' },
  { value: 'minimal', name: 'Minimaal', desc: 'Veel ruimte, rustig' },
];

const nf = (n) => '€ ' + Number(n).toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const companyAddr = computed(() => [props.company.postal_code, props.company.city].filter(Boolean).join(' '));
const footerText = computed(() => form.invoice_footer || defaultFooter);
const _today = new Date();
const _fmt = (d) => d.toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' });
const _terms = props.company.default_payment_terms || 30;
const pv = {
  number: '2026-0007',
  date: _fmt(_today),
  due: _fmt(new Date(_today.getTime() + _terms * 86400000)),
  terms: _terms,
  customer: { name: 'Voorbeeldklant B.V.', addr: 'Keizersgracht 123', city: '1015 CJ Amsterdam' },
  lines: [
    { desc: 'Webdesign basispakket', qty: 1, price: 1250, vat: 21 },
    { desc: 'Hosting jaarpakket', qty: 1, price: 180, vat: 21 },
  ],
  subtotal: 1430, vat: 300.30, total: 1730.30,
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
  if (!confirm('Logo verwijderen?')) return;
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
            <div v-if="previewLogo">
              <img :src="previewLogo" class="logo-preview" alt="Logo" />
              <div style="display:flex;gap:8px;margin-top:10px;">
                <label class="btn btn-ghost btn-sm">
                  {{ logoUploading ? 'Bezig…' : 'Vervangen' }}
                  <input type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" @change="onLogoChange" :disabled="logoUploading" style="display:none;" />
                </label>
                <button class="btn btn-danger btn-sm" @click="removeLogo" :disabled="logoUploading">Verwijderen</button>
              </div>

              <div class="logo-scale-row">
                <div class="logo-scale-label">
                  <span>Grootte op factuur</span>
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
              <div class="upload-hint">PNG, JPG, SVG of WebP — max 2 MB</div>
              <div class="upload-cta"><b>{{ logoUploading ? 'Uploaden…' : 'Klik om te uploaden' }}</b></div>
            </label>
            <div v-if="form.errors.logo" class="field-error" style="margin-top:8px;">{{ form.errors.logo }}</div>
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
              <div class="card-subtitle">Kies een sjabloon — het voorbeeld rechts past zich direct aan</div>
            </div>
          </div>
          <div class="card-body">
            <div class="template-cards">
              <div v-for="t in templates" :key="t.value"
                class="template-card"
                :class="{ active: form.invoice_template === t.value }"
                @click="form.invoice_template = t.value">
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
                  <template v-else>
                    <div class="tt-title-m"></div>
                    <div class="tt-line tt-line-1"></div>
                    <div class="tt-line tt-line-2"></div>
                    <div class="tt-total-m"></div>
                  </template>
                </div>
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
        <div class="preview-label">Live voorbeeld — {{ templates.find(t => t.value === form.invoice_template)?.name }}</div>
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
                <div class="pv-doctype">FACTUUR</div>
                <div class="pv-num">{{ pv.number }}</div>
              </div>
            </div>
            <div class="pv-body">
              <div class="pv-parties">
                <div class="pv-party">
                  <div class="pv-party-label">Van</div>
                  <div class="pv-party-name">{{ company.name }}</div>
                  <div v-if="company.kvk_number">KVK {{ company.kvk_number }}</div>
                  <div v-if="company.vat_number">BTW {{ company.vat_number }}</div>
                </div>
                <div class="pv-party">
                  <div class="pv-party-label">Factuur aan</div>
                  <div class="pv-party-name">{{ pv.customer.name }}</div>
                  <div>{{ pv.customer.addr }}</div>
                  <div>{{ pv.customer.city }}</div>
                </div>
              </div>
              <div class="pv-meta">
                <div><span>Factuurdatum</span><strong>{{ pv.date }}</strong></div>
                <div><span>Vervaldatum</span><strong>{{ pv.due }}</strong></div>
              </div>
              <table class="pv-lines">
                <thead><tr><th>Omschrijving</th><th class="r">Aantal</th><th class="r">Stuksprijs</th><th class="c">BTW</th><th class="r">Bedrag</th></tr></thead>
                <tbody>
                  <tr v-for="(l, i) in pv.lines" :key="i">
                    <td>{{ l.desc }}</td><td class="r">{{ l.qty }}</td><td class="r">{{ nf(l.price) }}</td><td class="c">{{ l.vat }}%</td><td class="r">{{ nf(l.qty * l.price) }}</td>
                  </tr>
                </tbody>
              </table>
              <div class="pv-totals">
                <div><span>Subtotaal</span><span>{{ nf(pv.subtotal) }}</span></div>
                <div><span>BTW 21%</span><span>{{ nf(pv.vat) }}</span></div>
                <div class="pv-grand"><span>Te betalen</span><span>{{ nf(pv.total) }}</span></div>
              </div>
              <div v-if="company.iban" class="pv-pay-note">
                Gelieve het bedrag binnen {{ pv.terms }} dagen te voldoen op {{ company.iban }} t.n.v. {{ company.name }} o.v.v. factuurnummer {{ pv.number }}.
              </div>
              <div class="pv-footer">{{ footerText }}</div>
            </div>
          </template>

          <!-- ============ CLASSIC ============ -->
          <template v-else-if="form.invoice_template === 'classic'">
            <div class="pv-classic-header">
              <div style="text-align:center;">
                <img v-if="previewLogo" :src="previewLogo" class="pv-logo-c" :style="logoStyleClassic" alt="" />
                <div class="pv-classic-title">FACTUUR</div>
                <div class="pv-classic-sub">{{ company.name }} · {{ pv.number }}</div>
              </div>
            </div>
            <div class="pv-body">
              <div class="pv-parties">
                <div class="pv-party">
                  <div class="pv-party-label">Afzender</div>
                  <div class="pv-party-name">{{ company.name }}</div>
                  <div v-if="company.address_line">{{ company.address_line }}</div>
                  <div v-if="companyAddr">{{ companyAddr }}</div>
                  <div v-if="company.kvk_number">KVK {{ company.kvk_number }}</div>
                  <div v-if="company.vat_number">BTW {{ company.vat_number }}</div>
                </div>
                <div class="pv-party" style="text-align:right;">
                  <div class="pv-party-label">Aan</div>
                  <div class="pv-party-name">{{ pv.customer.name }}</div>
                  <div>{{ pv.customer.addr }}</div>
                  <div>{{ pv.customer.city }}</div>
                </div>
              </div>
              <div class="pv-classic-meta">
                <div><strong>Factuurdatum:</strong> {{ pv.date }}</div>
                <div><strong>Vervaldatum:</strong> {{ pv.due }}</div>
              </div>
              <table class="pv-lines pv-lines-classic">
                <thead><tr><th>Omschrijving</th><th class="r">Aantal</th><th class="r">Prijs</th><th class="c">BTW</th><th class="r">Totaal</th></tr></thead>
                <tbody>
                  <tr v-for="(l, i) in pv.lines" :key="i">
                    <td>{{ l.desc }}</td><td class="r">{{ l.qty }}</td><td class="r">{{ nf(l.price) }}</td><td class="c">{{ l.vat }}%</td><td class="r">{{ nf(l.qty * l.price) }}</td>
                  </tr>
                </tbody>
              </table>
              <div class="pv-totals pv-totals-classic">
                <div><span>Subtotaal</span><span>{{ nf(pv.subtotal) }}</span></div>
                <div><span>BTW 21%</span><span>{{ nf(pv.vat) }}</span></div>
                <div class="pv-grand"><span>Te betalen</span><span>{{ nf(pv.total) }}</span></div>
              </div>
              <div v-if="company.iban" class="pv-pay-note">
                Gelieve het bedrag binnen {{ pv.terms }} dagen te voldoen op {{ company.iban }} o.v.v. factuurnummer {{ pv.number }}.
              </div>
              <div class="pv-footer pv-footer-classic">{{ footerText }}</div>
            </div>
          </template>

          <!-- ============ MINIMAL ============ -->
          <template v-else>
            <div class="pv-minimal-header">
              <img v-if="previewLogo" :src="previewLogo" class="pv-logo" :style="logoStyleModern" alt="" />
              <div class="pv-minimal-title">Factuur</div>
              <div class="pv-minimal-num">{{ pv.number }} · {{ pv.date }}</div>
            </div>
            <div class="pv-body">
              <div class="pv-parties">
                <div class="pv-party">
                  <div class="pv-party-label">Van</div>
                  <div class="pv-party-name">{{ company.name }}</div>
                  <div v-if="companyAddr">{{ companyAddr }}</div>
                  <div v-if="company.vat_number">BTW {{ company.vat_number }}</div>
                </div>
                <div class="pv-party">
                  <div class="pv-party-label">Aan</div>
                  <div class="pv-party-name">{{ pv.customer.name }}</div>
                  <div>{{ pv.customer.city }}</div>
                </div>
              </div>
              <div class="pv-meta">
                <div><span>Vervaldatum</span><strong>{{ pv.due }}</strong></div>
              </div>
              <table class="pv-lines pv-lines-minimal">
                <tbody>
                  <tr v-for="(l, i) in pv.lines" :key="i">
                    <td>{{ l.desc }}</td><td class="r">{{ nf(l.qty * l.price) }}</td>
                  </tr>
                </tbody>
              </table>
              <div class="pv-totals pv-totals-minimal">
                <div><span>Subtotaal</span><span>{{ nf(pv.subtotal) }}</span></div>
                <div><span>BTW 21%</span><span>{{ nf(pv.vat) }}</span></div>
                <div class="pv-grand"><span>Te betalen</span><span>{{ nf(pv.total) }}</span></div>
              </div>
              <div v-if="company.iban" class="pv-pay-note">
                Betaling binnen {{ pv.terms }} dagen op {{ company.iban }} o.v.v. {{ pv.number }}.
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
.huisstijl-layout { display: grid; grid-template-columns: 460px 1fr; gap: 24px; align-items: flex-start; }
.huisstijl-settings { display: flex; flex-direction: column; gap: 14px; }
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
</style>
