<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { t } from '@/i18n';

const props = defineProps({
  profiles: Array,  // handelsnamen incl. logo_data en invoices_count
  company: Object,  // { name, brand_color, invoice_template } — de standaard
});

// Markt (nl/pl): KvK/REGON- en btw/NIP-benaming.
const market = usePage().props.market;

const TEMPLATES = { modern: t('Modern'), classic: t('Klassiek'), minimal: t('Minimaal') };

/* ---------- Formulier (toevoegen of bewerken) ---------- */
const editingId = ref(null);
const showForm = ref(false);
const logoPreview = ref(null);

const form = useForm({
  name: '',
  logo: null,
  remove_logo: false,
  logo_scale: 100,
  brand_color: '',
  invoice_template: '',
  invoice_footer: '',
});

const startAdd = () => {
  editingId.value = null;
  form.reset();
  form.clearErrors();
  logoPreview.value = null;
  showForm.value = true;
};

const startEdit = (p) => {
  editingId.value = p.id;
  form.clearErrors();
  form.name = p.name;
  form.logo = null;
  form.remove_logo = false;
  form.logo_scale = p.logo_scale ?? 100;
  form.brand_color = p.brand_color ?? '';
  form.invoice_template = p.invoice_template ?? '';
  form.invoice_footer = p.invoice_footer ?? '';
  logoPreview.value = p.logo_data || null;
  showForm.value = true;
};

const pickLogo = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;
  form.logo = file;
  form.remove_logo = false;
  logoPreview.value = URL.createObjectURL(file);
  event.target.value = '';
};

const clearLogo = () => {
  form.logo = null;
  form.remove_logo = true;
  logoPreview.value = null;
};

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      brand_color: data.brand_color || null,
      invoice_template: data.invoice_template || null,
      invoice_footer: data.invoice_footer || null,
    }))
    .post(
      editingId.value ? route('settings.brands.update', editingId.value) : route('settings.brands.store'),
      {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { showForm.value = false; form.reset(); logoPreview.value = null; },
      }
    );
};

const removeProfile = (p) => {
  const warning = p.invoices_count > 0
    ? t('Er zijn :n facturen onder deze handelsnaam gemaakt; die vallen terug op je standaard huisstijl.', { n: p.invoices_count }) + '\n\n'
    : '';
  if (confirm(warning + t('Handelsnaam ":name" verwijderen?', { name: p.name }))) {
    router.delete(route('settings.brands.destroy', p.id), { preserveScroll: true });
  }
};

const effectiveColor = (p) => p.brand_color || props.company.brand_color;
const effectiveTemplate = (p) => TEMPLATES[p.invoice_template || props.company.invoice_template] || TEMPLATES.modern;
</script>

<template>
  <Head :title="$t('Handelsnamen')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">{{ $t('Instellingen') }} / <span class="breadcrumb-current">{{ $t('Handelsnamen') }}</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Handelsnamen') }}</h1>
        <p class="page-subtitle">
          {{ $t('Factureer onder meerdere handelsnamen, elk met een eigen logo, kleur en sjabloon — binnen één administratie. :registry, :taxid, IBAN en de factuurnummering blijven gewoon van :name.', { registry: market.registry.short, taxid: market.tax_id.label, name: company.name }) }}
        </p>
      </div>
      <button v-if="!showForm" type="button" class="btn btn-primary" @click="startAdd">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Handelsnaam toevoegen') }}
      </button>
    </div>

    <!-- Formulier -->
    <div v-if="showForm" class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div class="hn-form-title">{{ editingId ? $t('Handelsnaam bewerken') : $t('Nieuwe handelsnaam') }}</div>
        <form @submit.prevent="submit">
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Handelsnaam') }} *<span class="label-hint">{{ $t('(zo staat de afzender op de factuur)') }}</span></label>
              <input type="text" v-model="form.name" maxlength="190" :placeholder="$t('Bijv. Vries Webdesign')">
              <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Factuurkleur') }}<span class="label-hint">{{ $t('(leeg = standaard huisstijl)') }}</span></label>
              <div style="display:flex;gap:10px;align-items:center;">
                <input type="color" :value="form.brand_color || company.brand_color" @input="form.brand_color = $event.target.value" style="width:48px;height:42px;padding:2px;cursor:pointer;">
                <input type="text" v-model="form.brand_color" maxlength="7" class="mono" style="width:120px;" :placeholder="$t('Standaard')">
              </div>
              <div v-if="form.errors.brand_color" class="field-error">{{ form.errors.brand_color }}</div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Eigen logo') }}<span class="label-hint">{{ $t('(PNG/JPG/SVG/WEBP, max 2 MB)') }}</span></label>
              <div class="hn-logo-row">
                <div class="hn-logo-preview">
                  <img v-if="logoPreview" :src="logoPreview" alt="">
                  <span v-else class="hn-logo-mark" :style="{ background: form.brand_color || company.brand_color }">
                    {{ (form.name || '?').substring(0, 1).toUpperCase() }}
                  </span>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                  <label class="btn btn-secondary btn-sm" style="cursor:pointer;">
                    <input type="file" accept=".png,.jpg,.jpeg,.svg,.webp" style="display:none;" @change="pickLogo">
                    {{ logoPreview ? $t('Ander logo kiezen') : $t('Logo uploaden') }}
                  </label>
                  <button v-if="logoPreview" type="button" class="btn btn-secondary btn-sm" @click="clearLogo">{{ $t('Logo weghalen') }}</button>
                </div>
              </div>
              <div class="hn-hint">{{ $t('Zonder eigen logo toont de factuur een letter-embleem met de gekozen kleur — niet het logo van :name.', { name: company.name }) }}</div>
              <div v-if="form.errors.logo" class="field-error">{{ form.errors.logo }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Factuursjabloon') }}<span class="label-hint">{{ $t('(leeg = standaard huisstijl)') }}</span></label>
              <select v-model="form.invoice_template">
                <option value="">{{ $t('Standaard') }} ({{ TEMPLATES[company.invoice_template] || TEMPLATES.modern }})</option>
                <option value="modern">{{ TEMPLATES.modern }}</option>
                <option value="classic">{{ TEMPLATES.classic }}</option>
                <option value="minimal">{{ TEMPLATES.minimal }}</option>
              </select>
              <label style="margin-top:14px;">{{ $t('Logo-grootte') }}<span class="label-hint">{{ form.logo_scale }}%</span></label>
              <input type="range" v-model.number="form.logo_scale" min="50" max="200" step="5">
            </div>
          </div>

          <div class="form-group">
            <label>{{ $t('Voetnoot op de factuur') }}<span class="label-hint">{{ $t('(leeg = standaard voetnoot)') }}</span></label>
            <textarea v-model="form.invoice_footer" rows="2" maxlength="1000" :placeholder="$t('Bijv. betalingsvoorwaarden voor deze handelsnaam')"></textarea>
          </div>

          <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" class="btn btn-secondary" @click="showForm = false">{{ $t('Annuleren') }}</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? $t('Bezig…') : (editingId ? $t('Wijzigingen opslaan') : $t('Handelsnaam toevoegen')) }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Lijst -->
    <div v-if="profiles.length" class="hn-grid">
      <div v-for="p in profiles" :key="p.id" class="card hn-card">
        <div class="hn-swatch" :style="{ background: effectiveColor(p) }"></div>
        <div class="card-body" style="display:flex;gap:14px;align-items:center;">
          <div class="hn-logo-preview">
            <img v-if="p.logo_data" :src="p.logo_data" alt="">
            <span v-else class="hn-logo-mark" :style="{ background: effectiveColor(p) }">{{ p.name.substring(0, 1).toUpperCase() }}</span>
          </div>
          <div style="flex:1;min-width:0;">
            <div class="hn-name">{{ p.name }}</div>
            <div class="hn-meta">
              {{ $t('Sjabloon:') }} {{ effectiveTemplate(p) }}
              · {{ p.invoices_count === 1 ? $t('1 factuur') : $t(':n facturen', { n: p.invoices_count }) }}
            </div>
          </div>
          <div style="display:flex;gap:4px;">
            <button type="button" class="icon-btn" :title="$t('Bewerken')" @click="startEdit(p)">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
            </button>
            <button type="button" class="icon-btn" :title="$t('Verwijderen')" @click="removeProfile(p)">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-else-if="!showForm" class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">{{ $t('Nog geen handelsnamen') }}</div>
      <div style="margin-bottom:20px;">
        {{ $t('Handig als je onder meerdere namen werkt: elke handelsnaam krijgt een eigen logo, kleur en sjabloon op de factuur, terwijl je administratie en nummering gewoon één geheel blijven.') }}
      </div>
      <button type="button" class="btn btn-primary btn-sm" style="display:inline-flex;" @click="startAdd">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Eerste handelsnaam toevoegen') }}
      </button>
    </div>
  </AppLayout>
</template>

<style scoped>
.hn-form-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 14px; }
.label-hint { color: var(--text-4); font-weight: 400; font-size: 11.5px; margin-left: 6px; }
.hn-hint { font-size: 11.5px; color: var(--text-4); margin-top: 6px; line-height: 1.5; }

.hn-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.hn-card { overflow: hidden; }
.hn-swatch { height: 6px; }
.hn-name { font-weight: 700; font-size: 15px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.hn-meta { font-size: 12px; color: var(--text-3); margin-top: 3px; }

.hn-logo-row { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.hn-logo-preview {
  width: 64px; height: 64px; border: 1px solid var(--border); border-radius: 10px;
  display: inline-flex; align-items: center; justify-content: center; flex: none;
  background: var(--surface-2); overflow: hidden;
}
.hn-logo-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
.hn-logo-mark {
  width: 40px; height: 40px; border-radius: 9px; color: #fff; font-weight: 700; font-size: 19px;
  display: inline-flex; align-items: center; justify-content: center;
}

.icon-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.icon-btn:hover { background: var(--surface-2); color: var(--brand-dark); }

@media (max-width: 760px) {
  .hn-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
