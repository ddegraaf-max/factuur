<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import axios from 'axios';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
  purchase: Object,     // null bij nieuw
  suppliers: Array,     // eerder gebruikte leveranciersnamen (autocomplete)
  categories: Array,
  scan_enabled: Boolean, // bonnetjes scannen met AI (alleen met ANTHROPIC_API_KEY)
  inbox_item: Object,   // aangeleverd bestand uit het Postvak IN (of null)
});

const isEdit = computed(() => !!props.purchase);

/* ---------- Formulier ---------- */
const today = new Date().toISOString().slice(0, 10);

// Bedragregels: in 'excl'-modus vul je het bedrag exclusief in (BTW aanpasbaar),
// in 'incl'-modus het totaalbedrag van de bon en rekenen wij terug.
const amountMode = ref('excl');

const initialRows = () => {
  if (props.purchase?.vat_lines?.length) {
    return props.purchase.vat_lines.map(l => ({
      amount: Number(l.base),
      rate: Number(l.rate),
      vat: Number(l.vat),
    }));
  }
  return [{ amount: null, rate: 21, vat: 0 }];
};

const form = useForm({
  supplier_name: props.purchase?.supplier_name || '',
  supplier_reference: props.purchase?.supplier_reference || '',
  category: props.purchase?.category || '',
  invoice_date: props.purchase?.invoice_date || today,
  due_date: props.purchase?.due_date || '',
  rows: initialRows(),
  is_paid: props.purchase ? props.purchase.status === 'paid' : false,
  paid_at: props.purchase?.paid_at || today,
  payment_method: props.purchase?.payment_method || 'bank_transfer',
  notes: props.purchase?.notes || '',
});

/* ---------- BTW-regels ---------- */
const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

// Grondslag en BTW van een regel, afhankelijk van de invoermodus.
const lineCalc = (row) => {
  const amount = Number(row.amount) || 0;
  const rate = Number(row.rate) || 0;
  if (amountMode.value === 'incl') {
    const base = round2(amount / (1 + rate / 100));
    return { base, vat: round2(amount - base) };
  }
  return { base: round2(amount), vat: round2(Number(row.vat) || 0) };
};

const recalcVat = (row) => {
  // In excl-modus vullen we de BTW automatisch; daarna mag je hem overschrijven
  // (bonnen ronden weleens anders af).
  if (amountMode.value === 'excl') {
    row.vat = round2((Number(row.amount) || 0) * (Number(row.rate) || 0) / 100);
  }
};

const switchMode = (mode) => {
  if (amountMode.value === mode) return;
  // Reken bestaande regels om zodat het totaal hetzelfde blijft.
  form.rows = form.rows.map(row => {
    const { base, vat } = lineCalc(row);
    return mode === 'incl'
      ? { amount: round2(base + vat), rate: row.rate, vat }
      : { amount: base, rate: row.rate, vat };
  });
  amountMode.value = mode;
};

const addRow = () => form.rows.push({ amount: null, rate: 21, vat: 0 });
const removeRow = (idx) => form.rows.splice(idx, 1);

const totals = computed(() => {
  let base = 0, vat = 0;
  for (const row of form.rows) {
    const c = lineCalc(row);
    base += c.base;
    vat += c.vat;
  }
  return { base: round2(base), vat: round2(vat), total: round2(base + vat) };
});

/* ---------- Bestanden (foto / PDF) ---------- */
const fileInput = ref(null);
const cameraInput = ref(null);
const files = ref([]); // { file, name, size, previewUrl|null }

const addFiles = (event) => {
  for (const file of Array.from(event.target.files || [])) {
    files.value.push({
      file,
      name: file.name,
      size: file.size,
      previewUrl: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
    });
  }
  event.target.value = '';
};

const removeFile = (idx) => {
  const [removed] = files.value.splice(idx, 1);
  if (removed?.previewUrl) URL.revokeObjectURL(removed.previewUrl);
};

onBeforeUnmount(() => {
  files.value.forEach(f => f.previewUrl && URL.revokeObjectURL(f.previewUrl));
});

const formatSize = (b) => b < 1024 * 1024 ? (b / 1024).toFixed(0) + ' KB' : (b / 1024 / 1024).toFixed(1) + ' MB';

const removeExistingAttachment = (att) => {
  if (confirm(`Bijlage "${att.filename}" verwijderen?`)) {
    router.delete(route('attachments.destroy', att.id), { preserveScroll: true, preserveState: false });
  }
};

/* ---------- Scan & herken (AI) ---------- */
const scanning = ref(false);
const scanError = ref('');
const scanNotice = ref('');
const scanWarning = ref('');

// De eerste foto of PDF in de lijst wordt gescand (meestal is er maar één);
// zonder eigen upload wordt het bestand uit het Postvak IN gescand.
const scannable = computed(() =>
  files.value.find(f => f.file.type === 'application/pdf' || f.file.type.startsWith('image/'))
);
const canScan = computed(() => props.scan_enabled && (scannable.value || props.inbox_item));

// Grote foto's eerst verkleinen: sneller uploaden en ruim binnen de AI-limieten.
const prepareForScan = (file) => new Promise((resolve) => {
  if (file.type === 'application/pdf' || file.size < 1.5 * 1024 * 1024) return resolve(file);
  const img = new Image();
  const url = URL.createObjectURL(file);
  img.onload = () => {
    URL.revokeObjectURL(url);
    const scale = Math.min(1, 2048 / Math.max(img.width, img.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(img.width * scale);
    canvas.height = Math.round(img.height * scale);
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
    canvas.toBlob(
      (blob) => resolve(blob ? new File([blob], 'bon.jpg', { type: 'image/jpeg' }) : file),
      'image/jpeg',
      0.85
    );
  };
  img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
  img.src = url;
});

const applyScan = (r) => {
  if (r.supplier_name) form.supplier_name = r.supplier_name;
  if (r.supplier_reference) form.supplier_reference = r.supplier_reference;
  if (r.invoice_date) form.invoice_date = r.invoice_date;
  if (r.due_date) form.due_date = r.due_date;
  if (r.category) form.category = r.category;
  if (r.notes && !form.notes) form.notes = r.notes;
  amountMode.value = 'excl';
  form.rows = r.vat_lines.map(l => ({ amount: Number(l.base), rate: Number(l.rate), vat: Number(l.vat) }));
};

const scanReceipt = async () => {
  const entry = scannable.value;
  if ((!entry && !props.inbox_item) || scanning.value) return;
  scanning.value = true;
  scanError.value = ''; scanNotice.value = ''; scanWarning.value = '';
  try {
    let data;
    if (entry) {
      const payload = await prepareForScan(entry.file);
      const fd = new FormData();
      fd.append('file', payload, payload.name || entry.name);
      ({ data } = await axios.post(route('purchases.scan'), fd));
    } else {
      // Bestand uit het Postvak IN — staat al op de server.
      ({ data } = await axios.post(route('purchases.scan'), { inbox_id: props.inbox_item.id }));
    }
    applyScan(data.result);
    scanNotice.value = 'Gegevens van de bon overgenomen — controleer ze even voor je opslaat.';
    scanWarning.value = data.result.warning || '';
  } catch (e) {
    scanError.value = e.response?.data?.message
      || 'Scannen is niet gelukt. Probeer het opnieuw of vul de gegevens handmatig in.';
  } finally {
    scanning.value = false;
  }
};

/* ---------- Opslaan ---------- */
const submit = () => {
  form
    .transform((data) => ({
      supplier_name: data.supplier_name,
      supplier_reference: data.supplier_reference || null,
      category: data.category || null,
      invoice_date: data.invoice_date,
      due_date: data.due_date || null,
      vat_lines: data.rows.map(row => {
        const c = lineCalc(row);
        return { base: c.base, rate: Number(row.rate) || 0, vat: c.vat };
      }),
      is_paid: data.is_paid,
      paid_at: data.is_paid ? data.paid_at : null,
      payment_method: data.is_paid ? data.payment_method : null,
      notes: data.notes || null,
      files: files.value.map(f => f.file),
      inbox_id: props.inbox_item?.id ?? null,
      ...(isEdit.value ? { _method: 'patch' } : {}),
    }))
    .post(isEdit.value ? route('purchases.update', props.purchase.id) : route('purchases.store'), {
      forceFormData: true,
      preserveScroll: true,
    });
};

const lineError = computed(() => {
  if (form.errors.vat_lines) return form.errors.vat_lines;
  const key = Object.keys(form.errors).find(k => k.startsWith('vat_lines.'));
  return key ? form.errors[key] : null;
});

const fileError = computed(() => {
  const errs = form.errors;
  const key = Object.keys(errs).find(k => k === 'files' || k.startsWith('files.'));
  return key ? errs[key] : null;
});
</script>

<template>
  <Head :title="isEdit ? 'Inkoopfactuur bewerken' : 'Inkoopfactuur inboeken'" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        Inkoop / <Link :href="route('purchases.index')" style="color:var(--text-3);">Inkoopfacturen</Link> /
        <span class="breadcrumb-current">{{ isEdit ? 'Bewerken' : 'Inboeken' }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isEdit ? 'Inkoopfactuur bewerken' : 'Inkoopfactuur inboeken' }}</h1>
        <p class="page-subtitle">Boek de factuur van je leverancier in — met een foto of PDF erbij blijft je administratie compleet.</p>
      </div>
    </div>

    <form @submit.prevent="submit">
      <div class="pf-grid">
        <!-- Linkerkolom: gegevens -->
        <div class="card pf-main">
          <div class="card-body">
            <div class="pf-section-title">Leverancier</div>
            <div class="form-row">
              <div class="form-group">
                <label>Leverancier *</label>
                <input type="text" v-model="form.supplier_name" list="supplier-list" placeholder="Bijv. Coolblue B.V." maxlength="180">
                <datalist id="supplier-list">
                  <option v-for="s in suppliers" :key="s" :value="s" />
                </datalist>
                <div v-if="form.errors.supplier_name" class="field-error">{{ form.errors.supplier_name }}</div>
              </div>
              <div class="form-group">
                <label>Factuurnummer leverancier<span class="label-hint">(van de bon)</span></label>
                <input type="text" v-model="form.supplier_reference" maxlength="100">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Categorie</label>
                <select v-model="form.category">
                  <option value="">— Kies een categorie —</option>
                  <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>
              </div>
              <div class="form-group"></div>
            </div>

            <div class="pf-section-title">Datums</div>
            <div class="form-row">
              <div class="form-group">
                <label>Factuurdatum *</label>
                <input type="date" v-model="form.invoice_date">
                <div v-if="form.errors.invoice_date" class="field-error">{{ form.errors.invoice_date }}</div>
              </div>
              <div class="form-group">
                <label>Vervaldatum<span class="label-hint">(optioneel)</span></label>
                <input type="date" v-model="form.due_date">
              </div>
            </div>

            <div class="pf-section-title" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
              <span>Bedragen</span>
              <div class="pf-mode">
                <button type="button" :class="{ active: amountMode === 'excl' }" @click="switchMode('excl')">Excl. BTW invoeren</button>
                <button type="button" :class="{ active: amountMode === 'incl' }" @click="switchMode('incl')">Incl. BTW invoeren</button>
              </div>
            </div>

            <div class="pf-rows">
              <div class="pf-row pf-row-head">
                <span>{{ amountMode === 'incl' ? 'Bedrag incl. BTW' : 'Bedrag excl. BTW' }}</span>
                <span>Tarief</span>
                <span>BTW</span>
                <span></span>
              </div>
              <div v-for="(row, idx) in form.rows" :key="idx" class="pf-row">
                <input type="number" step="0.01" v-model="row.amount" placeholder="0,00" @input="recalcVat(row)">
                <select v-model="row.rate" @change="recalcVat(row)">
                  <option :value="21">21%</option>
                  <option :value="9">9%</option>
                  <option :value="0">0% / vrijgesteld</option>
                </select>
                <input v-if="amountMode === 'excl'" type="number" step="0.01" v-model="row.vat" title="BTW-bedrag — pas aan als de bon anders afrondt">
                <input v-else type="text" :value="eur(lineCalc(row).vat)" disabled>
                <button type="button" class="icon-btn" :disabled="form.rows.length === 1" title="Regel verwijderen" @click="removeRow(idx)">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" style="margin-top:8px;" @click="addRow">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Tarief toevoegen
            </button>
            <div v-if="lineError" class="field-error" style="margin-top:6px;">{{ lineError }}</div>

            <div class="pf-totals">
              <div><span>Subtotaal excl. BTW</span><span class="num">{{ eur(totals.base) }}</span></div>
              <div><span>BTW (voorbelasting)</span><span class="num">{{ eur(totals.vat) }}</span></div>
              <div class="grand"><span>Totaal incl. BTW</span><span class="num">{{ eur(totals.total) }}</span></div>
            </div>

            <div class="pf-section-title">Betaling</div>
            <label class="checkbox-row" style="margin-top:0;">
              <input type="checkbox" v-model="form.is_paid">
              <span>Deze factuur is al betaald</span>
            </label>
            <div v-if="form.is_paid" class="form-row">
              <div class="form-group">
                <label>Betaald op *</label>
                <input type="date" v-model="form.paid_at">
                <div v-if="form.errors.paid_at" class="field-error">{{ form.errors.paid_at }}</div>
              </div>
              <div class="form-group">
                <label>Betaalwijze</label>
                <select v-model="form.payment_method">
                  <option value="bank_transfer">Bankoverschrijving</option>
                  <option value="ideal">iDEAL</option>
                  <option value="direct_debit">Automatische incasso</option>
                  <option value="card">Pinpas / creditcard</option>
                  <option value="cash">Contant</option>
                  <option value="other">Anders</option>
                </select>
              </div>
            </div>

            <div class="form-group" style="margin-top:14px;">
              <label>Notities<span class="label-hint">(optioneel)</span></label>
              <textarea v-model="form.notes" maxlength="2000" placeholder="Bijv. waar de aankoop voor was"></textarea>
            </div>
          </div>
        </div>

        <!-- Rechterkolom: foto / upload -->
        <div class="pf-side">
          <div class="card">
            <div class="card-body">
              <div class="pf-section-title" style="margin-top:0;">Bon of factuur</div>
              <p class="pf-hint">
                Voeg een foto of PDF van de factuur toe, dan kun je overtypen terwijl je 'm ernaast ziet —
                en is je administratie meteen compleet voor de boekhouder.
              </p>

              <input ref="fileInput" type="file" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" style="display:none" @change="addFiles">
              <input ref="cameraInput" type="file" accept="image/*" capture="environment" style="display:none" @change="addFiles">

              <div class="pf-upload-buttons">
                <button type="button" class="btn btn-secondary btn-sm" @click="fileInput?.click()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  Bestand kiezen
                </button>
                <button type="button" class="btn btn-secondary btn-sm" @click="cameraInput?.click()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                  Foto maken
                </button>
              </div>
              <div v-if="fileError" class="field-error" style="margin-top:8px;">{{ fileError }}</div>

              <!-- Uit het Postvak IN: het aangeleverde bestand -->
              <div v-if="inbox_item" class="pf-file" style="border-color:var(--brand-border);background:var(--brand-tint);">
                <img v-if="inbox_item.is_image" :src="inbox_item.url" class="pf-thumb" alt="">
                <span v-else class="pf-thumb pf-thumb-pdf">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </span>
                <div class="pf-file-info">
                  <div class="pf-file-name">{{ inbox_item.filename }}</div>
                  <div class="pf-file-meta">
                    uit je Postvak IN<template v-if="inbox_item.from_email"> · van {{ inbox_item.from_email }}</template>
                    — wordt bij het inboeken als bijlage gekoppeld
                  </div>
                </div>
              </div>

              <!-- Scan & herken: AI leest de bon en vult het formulier in -->
              <div v-if="canScan" class="pf-scan">
                <button type="button" class="btn btn-primary btn-sm" :disabled="scanning" @click="scanReceipt">
                  <svg v-if="!scanning" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 7.2L22 12l-7.6 2.8L12 22l-2.4-7.2L2 12l7.6-2.8z"/></svg>
                  <svg v-else class="pf-spin" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-6.2-8.56"/></svg>
                  {{ scanning ? 'Bon wordt gelezen…' : 'Scan & herken' }}
                </button>
                <span class="pf-scan-hint">De gegevens en bedragen worden automatisch ingevuld — jij controleert ze.</span>
              </div>
              <div v-if="scanNotice" class="pf-scan-msg pf-scan-ok">{{ scanNotice }}</div>
              <div v-if="scanWarning" class="pf-scan-msg pf-scan-warn">{{ scanWarning }}</div>
              <div v-if="scanError" class="field-error" style="margin-top:8px;">{{ scanError }}</div>

              <!-- Nieuwe uploads -->
              <div v-for="(f, idx) in files" :key="idx" class="pf-file">
                <img v-if="f.previewUrl" :src="f.previewUrl" class="pf-thumb" alt="">
                <span v-else class="pf-thumb pf-thumb-pdf">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </span>
                <div class="pf-file-info">
                  <div class="pf-file-name">{{ f.name }}</div>
                  <div class="pf-file-meta">{{ formatSize(f.size) }} · nog niet opgeslagen</div>
                </div>
                <button type="button" class="icon-btn" title="Verwijderen" @click="removeFile(idx)">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>

              <!-- Grote preview van de eerste foto: overtypen met de bon ernaast -->
              <div v-if="files.find(f => f.previewUrl)" class="pf-preview">
                <img :src="files.find(f => f.previewUrl).previewUrl" alt="Voorbeeld van de bon">
              </div>
              <div v-else-if="inbox_item?.is_image" class="pf-preview">
                <img :src="inbox_item.url" alt="Voorbeeld van de aangeleverde bon">
              </div>
              <div v-else-if="inbox_item" class="pf-preview" style="padding:14px;text-align:center;">
                <a :href="inbox_item.url" target="_blank" class="btn btn-secondary btn-sm">Open de aangeleverde PDF in een nieuw tabblad</a>
              </div>

              <!-- Bestaande bijlagen (bewerken) -->
              <template v-if="isEdit && purchase.attachments?.length">
                <div class="pf-section-title" style="font-size:13px;">Opgeslagen bijlagen</div>
                <div v-for="att in purchase.attachments" :key="att.id" class="pf-file">
                  <img v-if="att.kind === 'image'" :src="route('attachments.show', att.id)" class="pf-thumb" alt="">
                  <span v-else class="pf-thumb pf-thumb-pdf">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  </span>
                  <div class="pf-file-info">
                    <a :href="route('attachments.show', att.id)" target="_blank" class="pf-file-name" style="color:var(--text);">{{ att.filename }}</a>
                    <div class="pf-file-meta">{{ att.size_formatted }}</div>
                  </div>
                  <button type="button" class="icon-btn" title="Verwijderen" @click="removeExistingAttachment(att)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>

      <div class="pf-actions">
        <Link :href="isEdit ? route('purchases.show', purchase.id) : route('purchases.index')" class="btn btn-secondary">Annuleren</Link>
        <button type="submit" class="btn btn-primary" :disabled="form.processing">
          {{ form.processing ? 'Bezig…' : (isEdit ? 'Wijzigingen opslaan' : 'Inboeken') }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<style scoped>
.pf-grid { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 16px; align-items: start; }
.pf-main .card-body, .pf-side .card-body { padding: 20px 22px; }

.pf-section-title {
  font-family: var(--font-display); font-weight: 600; font-size: 15px;
  margin: 20px 0 12px;
}
.pf-main .pf-section-title:first-child { margin-top: 0; }
.label-hint { color: var(--text-4); font-weight: 400; font-size: 11.5px; margin-left: 6px; }

.pf-mode { display: flex; gap: 3px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 3px; }
.pf-mode button { font-size: 12px; font-weight: 500; padding: 5px 11px; border-radius: 6px; color: var(--text-3); }
.pf-mode button.active { background: var(--surface); color: var(--text); box-shadow: var(--shadow-sm); }

.pf-rows { display: flex; flex-direction: column; gap: 8px; }
.pf-row { display: grid; grid-template-columns: minmax(0, 1fr) 150px 130px 32px; gap: 8px; align-items: center; }
.pf-row-head { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; margin-bottom: -2px; }
.pf-row input, .pf-row select { min-width: 0; }

.pf-totals { margin-top: 16px; border-top: 1px solid var(--border); padding-top: 12px; max-width: 340px; margin-left: auto; }
.pf-totals > div { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13.5px; color: var(--text-2); }
.pf-totals .num { font-family: var(--font-mono); font-weight: 500; }
.pf-totals .grand { font-weight: 700; font-size: 15px; color: var(--text); border-top: 2px solid var(--text); margin-top: 6px; padding-top: 10px; }

.pf-hint { font-size: 12.5px; color: var(--text-3); line-height: 1.6; margin-bottom: 14px; }
.pf-upload-buttons { display: flex; gap: 8px; flex-wrap: wrap; }

.pf-scan { margin-top: 12px; padding: 10px 12px; border: 1px dashed var(--border); border-radius: 10px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.pf-scan-hint { font-size: 11.5px; color: var(--text-3); line-height: 1.5; flex: 1; min-width: 140px; }
.pf-scan-msg { margin-top: 8px; font-size: 12.5px; border-radius: 8px; padding: 8px 10px; line-height: 1.5; }
.pf-scan-ok { background: var(--success-bg); color: var(--success); }
.pf-scan-warn { background: rgba(217, 119, 6, 0.1); color: #b45309; }
.pf-spin { animation: pf-rotate 0.9s linear infinite; }
@keyframes pf-rotate { to { transform: rotate(360deg); } }

.pf-file { display: flex; align-items: center; gap: 10px; border: 1px solid var(--border); border-radius: 10px; padding: 8px 10px; margin-top: 10px; }
.pf-thumb { width: 42px; height: 42px; border-radius: 7px; object-fit: cover; flex: none; background: var(--surface-2); }
.pf-thumb-pdf { display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.pf-thumb-pdf svg { width: 20px; height: 20px; }
.pf-file-info { flex: 1; min-width: 0; }
.pf-file-name { font-weight: 600; font-size: 12.5px; word-break: break-word; }
.pf-file-meta { font-size: 11.5px; color: var(--text-3); margin-top: 1px; }

.pf-preview { margin-top: 12px; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.pf-preview img { display: block; width: 100%; max-height: 480px; object-fit: contain; background: var(--surface-2); }

.pf-actions {
  display: flex; justify-content: flex-end; gap: 10px;
  margin-top: 16px;
}

.icon-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); flex: none; }
.icon-btn:hover:not(:disabled) { background: var(--surface-2); color: var(--brand-dark); }
.icon-btn:disabled { opacity: 0.35; cursor: not-allowed; }

@media (max-width: 960px) {
  .pf-grid { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 560px) {
  .pf-row { grid-template-columns: minmax(0, 1fr) 90px 90px 28px; }
}
</style>
