<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ types: Object, fields: Object, preview: Object, result: Object });

const upload = useForm({ type: 'customers', file: null });
const submitUpload = () => upload.post(route('import.preview'), { forceFormData: true, preserveScroll: true });

const mapping = ref({});
watch(() => props.preview, (p) => { mapping.value = { ...(p?.mapping || {}) }; }, { immediate: true });
const commit = useForm({ token: '', mapping: {} });
const submitCommit = () => {
  commit.token = props.preview.token;
  commit.mapping = mapping.value;
  commit.post(route('import.commit'), { preserveScroll: true });
};
const targetFields = computed(() => props.preview ? props.fields[props.preview.type] : []);
const mappedLabel = (i) => { const f = targetFields.value.find(f => String(mapping.value[f.key]) === String(i)); return f ? f.label : null; };
const missingRequired = computed(() => targetFields.value.filter(f => f.required && (mapping.value[f.key] === undefined || mapping.value[f.key] === '' || mapping.value[f.key] === null)));

const help = {
  customers: 'WeFact: Debiteuren → Exporteren (CSV). Moneybird: Contacten → Exporteren. e-Boekhouden: Relaties → Exporteren. Excel: Opslaan als → CSV (puntkomma).',
  products: 'WeFact: Producten → Exporteren. Moneybird: Producten → Exporteren. Of een eigen lijst met kolommen Naam, Prijs, Btw.',
  invoices: 'Exporteer alleen de openstaande facturen: nummer, klant, datum, vervaldatum en totaalbedrag incl. btw. Ze komen als "verstuurd" binnen, zodat herinneringen en debiteurenoverzicht meteen kloppen.',
};
</script>

<template>
  <Head title="Overstappen" />
  <AppLayout>
    <template #breadcrumb>Instellingen / <span class="breadcrumb-current">Overstappen</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Overstappen naar EasyInvoice</h1>
        <p class="page-subtitle">Neem in tien minuten je klanten, producten en openstaande facturen over uit WeFact, Moneybird, e-Boekhouden, Excel of elk ander pakket. Upload een CSV-export; EasyInvoice herkent de kolommen en slaat dubbelen over.</p>
      </div>
    </div>

    <div v-if="result" class="card" style="margin-bottom:16px;">
      <div class="card-body imp-result">
        <div class="imp-result-title">✓ {{ result.label }}: {{ result.created }} toegevoegd<span v-if="result.skipped">, {{ result.skipped }} overgeslagen (bestond al of onbruikbaar)</span></div>
        <ul v-if="result.errors && result.errors.length" class="imp-errors"><li v-for="e in result.errors" :key="e">{{ e }}</li></ul>
        <div class="imp-links">
          <Link v-if="result.type === 'customers'" :href="route('customers.index')" class="btn btn-secondary btn-sm">Bekijk klanten</Link>
          <Link v-if="result.type === 'products'" :href="route('products.index')" class="btn btn-secondary btn-sm">Bekijk producten</Link>
          <Link v-if="result.type === 'invoices'" :href="route('invoices.index')" class="btn btn-secondary btn-sm">Bekijk facturen</Link>
        </div>
      </div>
    </div>

    <div class="imp-grid">
      <div class="card">
        <div class="card-header"><div class="card-title">1. Bestand uploaden</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Wat wil je importeren?</label>
            <div class="imp-types">
              <label v-for="(label, key) in types" :key="key" class="imp-type" :class="{ active: upload.type === key }"><input type="radio" v-model="upload.type" :value="key">{{ label }}</label>
            </div>
            <div class="muted-sm" style="margin-top:6px;">{{ help[upload.type] }}</div>
          </div>
          <div class="form-group">
            <label>CSV-bestand</label>
            <input type="file" accept=".csv,.txt,text/csv" @change="upload.file = $event.target.files[0] || null">
            <div v-if="upload.errors.file" class="field-error">{{ upload.errors.file }}</div>
          </div>
          <button class="btn btn-primary" :disabled="!upload.file || upload.processing" @click="submitUpload">{{ upload.processing ? 'Bezig met inlezen…' : 'Inlezen en controleren' }}</button>
        </div>
      </div>

      <div class="card imp-help">
        <div class="card-header"><div class="card-title">Zo doe je het</div></div>
        <div class="card-body">
          <ol>
            <li>Exporteer in je oude pakket als <b>CSV</b> (Excel-bestanden eerst opslaan als CSV).</li>
            <li>Upload het hier — EasyInvoice herkent kolommen als naam, e-mail, adres, KvK, btw-nummer, prijs.</li>
            <li>Controleer de koppeling in het voorbeeld en klik op importeren. Bestaande klanten (zelfde e-mail of naam) worden overgeslagen, dus je kunt het gerust twee keer draaien.</li>
            <li>Volgorde: eerst klanten, dan producten, dan openstaande facturen.</li>
          </ol>
          <div class="muted-sm">Historie van betaalde facturen hoef je niet over te nemen; die blijft in je oude pakket (of in je <Link :href="route('export.index')" style="color:var(--brand);">XAF-auditfile</Link>). Loop je vast? Mail je export naar hallo@easyinvoice.nl — wij zetten hem voor je over.</div>
        </div>
      </div>
    </div>

    <div v-if="preview" class="card" style="margin-top:16px;">
      <div class="card-header">
        <div>
          <div class="card-title">2. Kolommen koppelen — {{ preview.filename }}</div>
          <div class="card-subtitle">{{ preview.total }} regels gevonden · {{ types[preview.type] }}. Kies per kolom uit je bestand het EasyInvoice-veld (of "overslaan").</div>
        </div>
      </div>
      <div class="card-body-flush imp-scroll">
        <table class="data-table imp-table">
          <thead>
            <tr><th v-for="(h, i) in preview.headers" :key="i" class="imp-th">
              <div class="imp-source">{{ h || '(leeg)' }}</div>
              <select :value="targetFields.find(f => String(mapping[f.key]) === String(i))?.key || ''" @change="e => { for (const f of targetFields) if (String(mapping[f.key]) === String(i)) delete mapping[f.key]; if (e.target.value) mapping[e.target.value] = i; }">
                <option value="">— overslaan —</option>
                <option v-for="f in targetFields" :key="f.key" :value="f.key">{{ f.label }}{{ f.required ? ' *' : '' }}</option>
              </select>
            </th></tr>
          </thead>
          <tbody>
            <tr v-for="(row, r) in preview.sample" :key="r"><td v-for="(h, i) in preview.headers" :key="i" :class="{ 'imp-mapped': mappedLabel(i) }">{{ row[i] }}</td></tr>
          </tbody>
        </table>
      </div>
      <div class="imp-footer">
        <div class="muted-sm"><template v-if="missingRequired.length">Nog te koppelen: <b>{{ missingRequired.map(f => f.label).join(', ') }}</b></template><template v-else>Alle verplichte velden zijn gekoppeld.</template></div>
        <button class="btn btn-primary" :disabled="missingRequired.length || commit.processing" @click="submitCommit">{{ commit.processing ? 'Bezig met importeren…' : `Importeer ${preview.total} ${types[preview.type].toLowerCase()}` }}</button>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.imp-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 16px; align-items: start; }
.imp-types { display: flex; gap: 8px; flex-wrap: wrap; }
.imp-type { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--r-sm); font-size: 13px; cursor: pointer; background: var(--surface); }
.imp-type.active { border-color: var(--brand); background: var(--brand-tint); font-weight: 600; }
.imp-type input { accent-color: var(--brand); }
.imp-help ol { margin: 0 0 10px; padding-left: 18px; font-size: 13px; line-height: 1.65; color: var(--text-2); }
.imp-help li { margin-bottom: 6px; }
.muted-sm { font-size: 12.5px; color: var(--text-4); line-height: 1.6; }
.imp-scroll { overflow-x: auto; }
.imp-table th { vertical-align: top; min-width: 150px; }
.imp-source { font-size: 12px; color: var(--text-3); margin-bottom: 4px; font-weight: 500; }
.imp-th select { width: 100%; font-size: 12.5px; padding: 5px 6px; }
.imp-mapped { background: var(--brand-tint); }
.imp-footer { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 18px; border-top: 1px solid var(--border); flex-wrap: wrap; }
.imp-result { font-size: 13.5px; }
.imp-result-title { font-weight: 600; color: var(--success); font-size: 15px; margin-bottom: 6px; }
.imp-errors { margin: 6px 0 10px; padding-left: 18px; color: var(--text-3); font-size: 12.5px; }
.imp-links { display: flex; gap: 8px; }
@media (max-width: 900px) { .imp-grid { grid-template-columns: minmax(0, 1fr); } }
</style>
