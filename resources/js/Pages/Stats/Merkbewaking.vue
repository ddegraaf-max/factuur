<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  incidents: Array,
  sources: Object,
  dossiers: Array,
  owner_emails: Array,
});

const today = new Date().toISOString().slice(0, 10);
const form = useForm({
  occurred_on: today,
  source: 'telefoon',
  name: '',
  email: '',
  summary: '',
  evidence: '',
  attachment: null,
});
const submit = () => form.post(route('brand.incidents.store'), {
  forceFormData: true,
  preserveScroll: true,
  onSuccess: () => { form.reset(); form.occurred_on = today; form.source = 'telefoon'; },
});
const remove = (i) => {
  if (confirm('Dit incident verwijderen?')) router.delete(route('brand.incidents.destroy', i.id), { preserveScroll: true });
};

const dossierForm = useForm({ month: '' });
const generate = () => dossierForm.post(route('brand.dossier.generate'), { preserveScroll: true });

const prevMonth = computed(() => {
  const d = new Date(); d.setMonth(d.getMonth() - 1);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
});
const bySource = computed(() => {
  const m = {};
  for (const i of props.incidents) m[i.source_label] = (m[i.source_label] || 0) + 1;
  return m;
});
const kb = (b) => (b >= 1024 * 1024 ? (b / 1024 / 1024).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' kB');
const showOld = ref(false);
</script>

<template>
  <Head title="Merkbewaking" />
  <AppLayout>
    <template #breadcrumb>Eigenaar / <span class="breadcrumb-current">Merkbewaking</span></template>
    <template #topbar-actions>
      <a :href="route('brand.export')" class="btn btn-secondary btn-sm">Verwarringslog als CSV</a>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Merkbewaking</h1>
        <p class="page-subtitle">Bewijs van eigen gebruik (elke maand automatisch) en bewijs van verwarring (elk incident met datum, wie, wat en waaruit het blijkt).</p>
      </div>
    </div>

    <div class="mb-grid">
      <!-- Verwarringslog -->
      <div>
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Verwarringslog</div>
              <div class="card-subtitle">{{ incidents.length }} {{ incidents.length === 1 ? 'incident' : 'incidenten' }}<template v-for="(n, s) in bySource" :key="s"> · {{ n }}× {{ s }}</template></div>
            </div>
          </div>
          <div class="card-body">
            <form class="mb-form" @submit.prevent="submit">
              <div class="mb-row">
                <div class="form-group"><label>Datum</label><input type="date" v-model="form.occurred_on" required></div>
                <div class="form-group"><label>Bron</label>
                  <select v-model="form.source"><option v-for="(label, key) in sources" :key="key" :value="key">{{ label }}</option></select>
                </div>
              </div>
              <div class="mb-row">
                <div class="form-group"><label>Van wie <span class="hint">(naam)</span></label><input type="text" v-model="form.name" maxlength="160"></div>
                <div class="form-group"><label>E-mail <span class="hint">(optioneel)</span></label><input type="email" v-model="form.email" maxlength="180"></div>
              </div>
              <div class="form-group"><label>Wat werd er precies gezegd of gevraagd?</label><textarea v-model="form.summary" rows="3" required maxlength="4000" placeholder="Bijv. 'Belde over de uitbetaling van augustus; dacht dat wij het uitbetaalplatform waren.'"></textarea><div v-if="form.errors.summary" class="field-error">{{ form.errors.summary }}</div></div>
              <div class="form-group"><label>Waaruit blijkt de verwarring?</label><textarea v-model="form.evidence" rows="2" maxlength="4000" placeholder="Bijv. 'Noemde het andere bedrijf bij naam; had daar een account, niet bij ons.'"></textarea></div>
              <div class="form-group"><label>Screenshot of PDF <span class="hint">(optioneel, max 8 MB)</span></label><input type="file" accept="image/png,image/jpeg,image/webp,application/pdf" @change="form.attachment = $event.target.files[0] || null"><div v-if="form.errors.attachment" class="field-error">{{ form.errors.attachment }}</div></div>
              <button class="btn btn-primary btn-sm" type="submit" :disabled="form.processing">Incident vastleggen</button>
            </form>
          </div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-body-flush" v-if="incidents.length">
            <table class="data-table">
              <thead><tr><th>Datum</th><th>Bron</th><th>Van wie</th><th>Wat / waaruit blijkt het</th><th></th></tr></thead>
              <tbody>
                <tr v-for="i in incidents" :key="i.id">
                  <td class="num" style="white-space:nowrap;">{{ i.occurred_on_label }}</td>
                  <td><span class="pill pill-draft">{{ i.source_label }}</span></td>
                  <td>{{ i.name || '—' }}<div v-if="i.email" class="muted-sm">{{ i.email }}</div></td>
                  <td class="mb-text"><div>{{ i.summary }}</div><div v-if="i.evidence" class="muted-sm">{{ i.evidence }}</div><a v-if="i.has_attachment" :href="route('brand.incidents.attachment', i.id)" target="_blank" class="lnk">📎 {{ i.attachment_name }}</a></td>
                  <td class="right"><button type="button" class="link-danger" @click="remove(i)">verwijderen</button></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="card-empty">Nog geen incidenten. Alles wat via het contactformulier-vinkje of de pagina "Zocht u een ander EasyInvoice?" binnenkomt, verschijnt hier automatisch.</div>
        </div>
      </div>

      <!-- Bewijs van eigen gebruik + werkwijze -->
      <div class="mb-side">
        <div class="card">
          <div class="card-header"><div class="card-title">Merkgebruik-dossiers</div></div>
          <div class="card-body">
            <p class="mb-help">Elke 1e van de maand om 07:30 stelt EasyInvoice automatisch het dossier van de vorige maand op en mailt het naar <b>{{ owner_emails.join(', ') || 'de eigenaar' }}</b>: gebruikscijfers, factuurexport, homepage met logo, een verstuurde klantfactuur (mail + PDF) en een manifest met SHA-256-hashes. <b>Bewaar die mails</b> — de bestanden op de server zijn tijdelijk.</p>
            <form class="mb-gen" @submit.prevent="generate">
              <input type="month" v-model="dossierForm.month" :placeholder="prevMonth">
              <button class="btn btn-secondary btn-sm" type="submit" :disabled="dossierForm.processing">{{ dossierForm.processing ? 'Bezig…' : 'Nu opstellen & mailen' }}</button>
            </form>
            <div v-if="dossiers.length" class="mb-dossiers">
              <div v-for="d in dossiers" :key="d.month" class="mb-dossier">
                <div class="mb-dossier-head"><b>{{ d.month }}</b><span class="muted-sm">{{ d.generated_at_label }}{{ d.mailed_to ? ' · gemaild' : '' }}</span></div>
                <div class="muted-sm">{{ d.stats.facturen_verstuurd }} facturen · {{ d.stats.offertes_verstuurd }} offertes · {{ d.stats.administraties_actief }} actieve administraties<template v-if="d.stats.bezoekers_website !== null"> · {{ d.stats.bezoekers_website }} bezoekers</template></div>
                <div class="mb-files">
                  <template v-for="f in d.files" :key="f.file">
                    <a v-if="f.available" :href="route('brand.dossier.file', { month: d.month, file: f.file })" class="lnk">{{ f.file }} <span class="muted-sm">({{ kb(f.bytes) }})</span></a>
                    <span v-else class="muted-sm">{{ f.file }} (in de mail)</span>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-header"><div class="card-title">Werkwijze</div></div>
          <div class="card-body mb-help">
            <ol class="mb-steps">
              <li><b>Automatisch:</b> contactformulier-vinkje "ik zocht een ander EasyInvoice" en de pagina <a :href="route('confusion')" target="_blank" class="lnk">/zocht-u-een-ander-easyinvoice</a> (in de footer) leggen incidenten vast, met datum en IP.</li>
              <li><b>Telefoon:</b> begint iemand over uitbetalingen of affiliates? Direct hierboven vastleggen: datum, naam, wat er werd gevraagd, waaruit de verwarring blijkt.</li>
              <li><b>E-mail:</b> stuur verdachte berichten door naar een apart adres (bijv. verwarring@easyinvoice.nl) én leg ze hier vast met een screenshot — de doorstuurdatum is de tijdstempel.</li>
              <li><b>Google Alerts</b> op "easyinvoice" en "easy-invoice" — handmatig in te stellen op google.com/alerts.</li>
              <li><b>Voor de gemachtigde:</b> knop "Verwarringslog als CSV" rechtsboven; de dossiers zitten in de maandmails.</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.mb-grid { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 20px; align-items: start; }
.mb-form .form-group { margin-bottom: 12px; }
.mb-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
.hint { font-weight: 400; color: var(--text-4); }
.mb-text { max-width: 520px; white-space: normal; font-size: 13px; line-height: 1.5; }
.muted-sm { font-size: 12px; color: var(--text-4); }
.lnk { color: var(--brand); font-weight: 500; }
.link-danger { background: none; border: none; padding: 0; font-size: 12px; color: var(--brand); cursor: pointer; text-decoration: underline; }
.mb-help { font-size: 13px; color: var(--text-2); line-height: 1.65; }
.mb-gen { display: flex; gap: 8px; align-items: center; margin: 12px 0; }
.mb-gen input { flex: 1; }
.mb-dossiers { display: flex; flex-direction: column; gap: 10px; margin-top: 6px; }
.mb-dossier { border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; font-size: 13px; }
.mb-dossier-head { display: flex; justify-content: space-between; gap: 8px; }
.mb-files { display: flex; flex-wrap: wrap; gap: 4px 12px; margin-top: 6px; font-size: 12.5px; }
.mb-steps { margin: 0; padding-left: 18px; }
.mb-steps li { margin-bottom: 8px; }
@media (max-width: 1100px) { .mb-grid { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 640px) { .mb-row { grid-template-columns: minmax(0, 1fr); } }
</style>
