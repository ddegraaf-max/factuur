<script setup>
/**
 * Eigenaar: de bedrijvendatabase van Fachora. Filteren op status, zoeken,
 * importeren (plaklijst of bestand), brieven per post voor unclaimed
 * bedrijven, en uitnodigen per e-mail — dat laatste alleen voor rijen met
 * vastgelegde toestemming (ConsentPolicy). Bellen mag altijd.
 */
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { icon } from '@/fachora-icons.js';
import { num } from '@/format.js';
import { t } from '@/i18n';

const props = defineProps({
  providers: Object,
  counts: Object,
  filters: Object,
  categories: { type: Array, default: () => [] },
  letterCandidates: { type: Number, default: 0 },
  ceidg: { type: Boolean, default: false },
  jobCounts: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const statusPill = { unclaimed: 'pill-draft', claimed: 'pill-sent', pending: 'pill-partial', verified: 'pill-paid', suspended: 'pill-overdue', blocked: 'pill-cancelled' };
const statusLabel = { unclaimed: 'Niet geclaimd', claimed: 'Geclaimd', pending: 'In verificatie', verified: 'Verified', suspended: 'Geschorst', blocked: 'Geblokkeerd' };
const statuses = ['all', 'pending', 'claimed', 'verified', 'unclaimed', 'suspended', 'blocked'];
const categoryName = (key) => (props.categories.find((c) => c.key === key) || {}).name || key;

/* ---------- filteren ---------- */
const q = ref(props.filters.q || '');
const category = ref(props.filters.categorie || '');
const go = (status = props.filters.status) => router.get(route('fachora.admin.providers'), { status, q: q.value || undefined, categorie: category.value || undefined }, { preserveState: true, replace: true });
let timer = null;
watch(q, () => { clearTimeout(timer); timer = setTimeout(() => go(), 350); });
watch(category, () => go());

/* ---------- selectie en uitnodigen ---------- */
const selected = ref([]);
const rows = computed(() => props.providers.data || []);
const allSelected = computed(() => rows.value.length > 0 && rows.value.every((p) => selected.value.includes(p.id)));
const toggleAll = () => { selected.value = allSelected.value ? [] : rows.value.map((p) => p.id); };
const toggle = (id) => { const i = selected.value.indexOf(id); if (i >= 0) selected.value.splice(i, 1); else selected.value.push(id); };
const invitable = computed(() => rows.value.filter((p) => selected.value.includes(p.id) && p.consent && p.status === 'unclaimed'));
const invite = () => {
  if (!invitable.value.length) return;
  if (!confirm(t(':n uitnodigingen per e-mail versturen? Alleen bedrijven met vastgelegde toestemming (opt-in) en status "niet geclaimd" worden gemaild; de rest wordt overgeslagen.', { n: invitable.value.length }))) return;
  router.post(route('fachora.admin.invite'), { ids: invitable.value.map((p) => p.id) }, { preserveScroll: true, onSuccess: () => { selected.value = []; } });
};

/* ---------- import ---------- */
const importModal = ref(false);
const importForm = useForm({ text: '', file: null, source: '', consent_source: '' });
const pickImport = (event) => { importForm.file = event.target.files?.[0] || null; };
const runImport = () => importForm.post(route('fachora.admin.import'), { forceFormData: true, preserveScroll: true, onSuccess: () => { importModal.value = false; importForm.reset(); } });

/* ---------- brieven ---------- */
const lettersModal = ref(false);
const letters = ref({ categorie: '', miasto: '', aantal: 50 });
const lettersUrl = computed(() => route('fachora.admin.letters', {
  categorie: letters.value.categorie || undefined,
  miasto: letters.value.miasto || undefined,
  aantal: Math.max(1, Math.min(100, Number(letters.value.aantal) || 50)),
}));
</script>

<template>
  <Head :title="$t('Fachora: bedrijven')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Eigenaar') }} / <span class="breadcrumb-current">{{ $t('Fachora: bedrijven') }}</span></template>
    <template #topbar-actions>
      <button class="btn btn-secondary btn-sm" @click="lettersModal = true">{{ $t('Brieven (pdf)') }}</button>
      <button class="btn btn-primary btn-sm" @click="importModal = true">{{ $t('Importeren') }}</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Bedrijven op Fachora') }}</h1>
        <p class="page-subtitle">{{ $t('Van register tot Verified. In de database staan is niet Verified: alleen een geclaimd en goedgekeurd bedrijf krijgt opdrachten.') }}</p>
      </div>
      <div class="page-actions">
        <button class="btn btn-secondary btn-sm" @click="importModal = true">{{ $t('Importeren') }}</button>
        <button class="btn btn-secondary btn-sm" @click="lettersModal = true">{{ $t('Brieven (pdf)') }}</button>
        <Link :href="route('fachora.admin.jobs')" class="btn btn-secondary btn-sm">{{ $t('Opdrachten') }} <span class="fx-count">{{ jobCounts.open || 0 }} {{ $t('open|opdrachten') }}<template v-if="jobCounts.disputed"> · {{ jobCounts.disputed }} {{ $t('geschil') }}</template></span></Link>
      </div>
    </div>

    <div v-if="flash.error" class="fx-alert fx-alert--err">{{ flash.error }}</div>
    <div v-if="$page.props.errors?.text" class="fx-alert fx-alert--err">{{ $page.props.errors.text }}</div>

    <!-- Spelregel die altijd zichtbaar moet zijn -->
    <div class="fx-rule">
      <span v-html="icon('shield', 18)"></span>
      <span>{{ $t('Fachora mailt of sms\'t een bedrijf nooit ongevraagd (PKE). Een groene stip betekent: mag gemaild worden (geclaimd of opt-in). Grijs: alleen per post, via het openbare profiel, of bellen.') }}</span>
    </div>

    <div v-if="!ceidg" class="fx-hint">
      <b>{{ $t('CEIDG-import staat uit.') }}</b> {{ $t('Zet CEIDG_TOKEN (dane.biznes.gov.pl) in de omgeving en draai bijvoorbeeld:') }}
      <code>php artisan fachora:import-ceidg 43.22.Z --wojewodztwo=łódzkie</code>
    </div>

    <!-- Filters -->
    <div class="filters" style="margin-bottom:10px;">
      <button v-for="s in statuses" :key="s" :class="['filter-chip', { active: filters.status === s }]" @click="go(s)">
        {{ s === 'all' ? $t('Alle') : $t(statusLabel[s]) }} <span class="count">{{ counts[s] ?? 0 }}</span>
      </button>
    </div>
    <div class="filter-bar" style="margin-bottom:14px;">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="q" type="text" :placeholder="$t('Zoek op naam, plaats, NIP of e-mail…')">
      </div>
      <select v-model="category" class="fx-select">
        <option value="">{{ $t('Alle categorieën') }}</option>
        <option v-for="c in categories" :key="c.key" :value="c.key">{{ c.name }}</option>
      </select>
      <button class="btn btn-primary btn-sm" :disabled="!invitable.length" :title="$t('Alleen geselecteerde rijen met opt-in en status niet geclaimd')" @click="invite">
        {{ $t('Uitnodiging sturen (alleen opt-in)') }}<template v-if="selected.length"> · {{ invitable.length }}/{{ selected.length }}</template>
      </button>
    </div>

    <div class="card">
      <div v-if="rows.length" class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width:32px;"><input type="checkbox" :checked="allSelected" @change="toggleAll"></th>
              <th>{{ $t('Bedrijf') }}</th>
              <th>{{ $t('Plaats') }}</th>
              <th>{{ $t('Categorieën') }}</th>
              <th>{{ $t('Status') }}</th>
              <th>{{ $t('Contact') }}</th>
              <th class="right">{{ $t('Score') }}</th>
              <th>{{ $t('Bron') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in rows" :key="p.id">
              <td class="fx-check-cell"><input type="checkbox" :checked="selected.includes(p.id)" @change="toggle(p.id)"></td>
              <td class="cell-primary">
                <Link :href="p.url">{{ p.name }}</Link>
                <div class="sub"><template v-if="p.nip">NIP {{ p.nip }}</template><template v-if="p.updated_at"> · {{ p.updated_at }}</template></div>
              </td>
              <td :data-label="$t('Plaats')">{{ p.city || '—' }} <span v-if="p.geocoded" class="fx-geo" :title="$t('Gegeocodeerd: telt mee in de afstand tot een opdracht')" v-html="icon('pin', 13)"></span><span v-else class="fx-geo off" :title="$t('Geen coördinaten')" v-html="icon('pin', 13)"></span></td>
              <td :data-label="$t('Categorieën')"><span v-for="c in p.categories" :key="c" class="tagx">{{ categoryName(c) }}</span><span v-if="!p.categories.length" class="sub">—</span></td>
              <td :data-label="$t('Status')"><span :class="['pill', statusPill[p.status]]">{{ $t(statusLabel[p.status] || p.status) }}</span></td>
              <td :data-label="$t('Contact')">
                <span class="fx-dot" :class="p.consent ? 'on' : 'off'" :title="p.consent_reason"></span>
                <span class="sub">{{ p.email || $t('geen e-mail') }}<template v-if="p.phone"> · <a :href="'tel:' + p.phone" @click.stop>{{ p.phone }}</a></template></span>
              </td>
              <td :data-label="$t('Score')" class="right num">{{ p.rating ? num(p.rating, 1) : '—' }}<span class="sub"> · {{ p.jobs_done || 0 }}</span></td>
              <td :data-label="$t('Bron')" class="sub">{{ p.source || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="card-body sub">{{ $t('Geen bedrijven in deze selectie.') }}</div>
    </div>

    <div v-if="providers.last_page > 1" class="pagination">
      <span class="sub">{{ $t(':from–:to van :total', { from: providers.from, to: providers.to, total: providers.total }) }}</span>
      <Link v-for="link in providers.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['page-link', { active: link.active, disabled: !link.url }]" preserve-state />
    </div>

    <!-- Modal: importeren -->
    <div v-if="importModal" class="modal-overlay" @click.self="importModal = false">
      <div class="modal" style="max-width:680px;">
        <div class="modal-header"><div class="modal-title">{{ $t('Bedrijven importeren') }}</div></div>
        <div class="modal-body">
          <p class="sub" style="margin:0 0 10px;line-height:1.6;">
            {{ $t('Eén bedrijf per regel, velden gescheiden door een puntkomma:') }}
            <code class="fx-code">nazwa; nip; e-mail; telefon; miasto; kod pocztowy; kategorie (rozdzielone przecinkami); zgoda (tak/nie)</code><br>
            {{ $t('Bestaande NIP\'s worden overgeslagen. Alleen rijen met zgoda=tak krijgen een vastgelegde toestemming en mogen worden gemaild; vul dan ook de bron van de toestemming in.') }}
          </p>
          <div class="form-group">
            <textarea v-model="importForm.text" rows="7" placeholder="Hydro-Max Jan Kowalski; 7251234567; biuro@hydromax.pl; 600100200; Łódź; 90-001; hydraulik, ogrzewanie; nie"></textarea>
            <div v-if="importForm.errors.text" class="field-error">{{ importForm.errors.text }}</div>
          </div>
          <div class="form-group">
            <label>{{ $t('Of een bestand (.txt of .csv)') }}</label>
            <input type="file" accept=".txt,.csv,text/plain,text/csv" class="fx-file-input" @change="pickImport">
            <div v-if="importForm.errors.file" class="field-error">{{ importForm.errors.file }}</div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Bron') }} <span class="label-hint">{{ $t('(bijv. ceidg, lijst-xyz)') }}</span></label>
              <input type="text" v-model="importForm.source" maxlength="60" placeholder="import">
            </div>
            <div class="form-group">
              <label>{{ $t('Bron van de toestemming') }} <span class="label-hint">{{ $t('(alleen bij zgoda=tak)') }}</span></label>
              <input type="text" v-model="importForm.consent_source" maxlength="80" :placeholder="$t('bijv. opt-in-lijst leverancier, 10-2026')">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="importModal = false">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="importForm.processing || (!importForm.text.trim() && !importForm.file)" @click="runImport">{{ $t('Importeren') }}</button>
        </div>
      </div>
    </div>

    <!-- Modal: brieven -->
    <div v-if="lettersModal" class="modal-overlay" @click.self="lettersModal = false">
      <div class="modal" style="max-width:560px;">
        <div class="modal-header"><div class="modal-title">{{ $t('Brieven per post (pdf)') }}</div></div>
        <div class="modal-body">
          <p class="sub" style="margin:0 0 12px;line-height:1.6;">{{ $t('Post naar niet-geclaimde bedrijven met een adres is toegestaan (geen elektronische communicatie). Elke brief bevat een claimcode. Kandidaten met postadres: :n.', { n: letterCandidates }) }}</p>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Categorie') }}</label>
              <select v-model="letters.categorie"><option value="">{{ $t('Alle categorieën') }}</option><option v-for="c in categories" :key="c.key" :value="c.key">{{ c.name }}</option></select>
            </div>
            <div class="form-group">
              <label>{{ $t('Plaats') }}</label>
              <input type="text" v-model="letters.miasto" :placeholder="$t('leeg = overal')">
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('Aantal brieven') }} <span class="label-hint">{{ $t('(max. 100)') }}</span></label>
            <input type="number" min="1" max="100" v-model.number="letters.aantal" style="width:120px;">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="lettersModal = false">{{ $t('Sluiten') }}</button>
          <a :href="lettersUrl" target="_blank" rel="noopener" class="btn btn-primary btn-sm">{{ $t('Pdf maken') }}</a>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.filters { display: flex; flex-wrap: wrap; gap: 6px; }
.num { font-family: var(--font-mono); }
.fx-alert { padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 14px; }
.fx-alert--err { background: var(--brand-tint-2); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.fx-rule { display: flex; gap: 10px; align-items: flex-start; padding: 10px 14px; border-radius: 8px; background: var(--brand-tint); border: 1px solid var(--brand-border); color: var(--brand); font-size: 13px; line-height: 1.55; margin-bottom: 12px; }
.fx-hint { padding: 10px 14px; border-radius: 8px; background: var(--warning-bg); border: 1px solid var(--warning-border); color: var(--warning); font-size: 13px; line-height: 1.6; margin-bottom: 12px; }
.fx-hint code, .fx-code { font-family: var(--font-mono); font-size: 12px; background: rgba(0,0,0,0.05); padding: 1px 6px; border-radius: 4px; }
.fx-count { font-size: 11px; opacity: 0.8; margin-left: 4px; }
.fx-select { height: 36px; padding: 0 10px; border: 1px solid var(--border); border-radius: 8px; font: inherit; font-size: 13px; background: var(--surface); }
.fx-check-cell { width: 32px; }
.fx-check-cell input, thead input { width: 16px; height: 16px; }
.tagx { display: inline-block; font-size: 11px; padding: 2px 7px; border-radius: 999px; background: var(--surface-2); border: 1px solid var(--border); margin: 1px 3px 1px 0; color: var(--text-2); }
.fx-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
.fx-dot.on { background: var(--success); }
.fx-dot.off { background: var(--border-strong); }
.fx-geo { color: var(--success); vertical-align: middle; display: inline-flex; }
.fx-geo.off { color: var(--text-4); }
.fx-file-input { width: auto !important; height: auto !important; padding: 0 !important; border: 0 !important; }
.pagination { display: flex; gap: 4px; align-items: center; flex-wrap: wrap; margin-top: 14px; }
.pagination .sub { margin-right: 8px; }
.page-link { padding: 5px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12.5px; color: var(--text-2); background: var(--surface); }
.page-link.active { background: var(--text); color: #fff; border-color: var(--text); }
.page-link.disabled { opacity: 0.4; pointer-events: none; }
@media (max-width: 760px) { .data-table td.fx-check-cell { display: block; } }
</style>
