<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
  defaults: Object,
  accountant_email: String,
});

// Markt (nl/pl): de btw-tarieven in de uitleg komen van de server.
const market = usePage().props.market || {};
const vatRatesLabel = (market.vat_rates || [21, 9, 0]).map(r => r + '%').join(' / ');

const from = ref(props.defaults.from);
const to = ref(props.defaults.to);
const status = ref('all');
const includeCredit = ref(true);

const pad = (n) => String(n).padStart(2, '0');
const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

const setPreset = (preset) => {
  const now = new Date();
  const y = now.getFullYear();
  const q = Math.floor(now.getMonth() / 3); // 0..3
  switch (preset) {
    case 'this_month':
      from.value = fmt(new Date(y, now.getMonth(), 1));
      to.value = fmt(new Date(y, now.getMonth() + 1, 0));
      break;
    case 'last_month':
      from.value = fmt(new Date(y, now.getMonth() - 1, 1));
      to.value = fmt(new Date(y, now.getMonth(), 0));
      break;
    case 'this_quarter':
      from.value = fmt(new Date(y, q * 3, 1));
      to.value = fmt(new Date(y, q * 3 + 3, 0));
      break;
    case 'last_quarter':
      from.value = fmt(new Date(y, (q - 1) * 3, 1));
      to.value = fmt(new Date(y, q * 3, 0));
      break;
    case 'this_year':
      from.value = `${y}-01-01`;
      to.value = fmt(now);
      break;
    case 'last_year':
      from.value = `${y - 1}-01-01`;
      to.value = `${y - 1}-12-31`;
      break;
  }
};

const downloadUrl = computed(() => route('export.download', {
  from: from.value,
  to: to.value,
  status: status.value,
  include_credit: includeCredit.value ? 1 : 0,
}));

const canDownload = computed(() => from.value && to.value && from.value <= to.value);

const thisYear = new Date().getFullYear();
const xafYear = ref(thisYear);
const xafYears = [thisYear, thisYear - 1, thisYear - 2, thisYear - 3];
const xafUrl = computed(() => route('export.xaf', { year: xafYear.value }));
</script>

<template>
  <Head :title="$t('Export naar boekhouder')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Rapporten') }} / <span class="breadcrumb-current">{{ $t('Export boekhouder') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Export naar boekhouder') }}</h1>
        <p class="page-subtitle">{{ $t('Download al je facturen als CSV — met grondslag en BTW per tarief, klaar voor je accountant') }}</p>
      </div>
    </div>

    <div class="export-grid">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ $t('Periode & filters') }}</div>
        </div>
        <div class="card-body">
          <div class="preset-row">
            <button class="filter-chip" @click="setPreset('this_month')">{{ $t('Deze maand') }}</button>
            <button class="filter-chip" @click="setPreset('last_month')">{{ $t('Vorige maand') }}</button>
            <button class="filter-chip" @click="setPreset('this_quarter')">{{ $t('Dit kwartaal') }}</button>
            <button class="filter-chip" @click="setPreset('last_quarter')">{{ $t('Vorig kwartaal') }}</button>
            <button class="filter-chip" @click="setPreset('this_year')">{{ $t('Dit jaar') }}</button>
            <button class="filter-chip" @click="setPreset('last_year')">{{ $t('Vorig jaar') }}</button>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Van') }}</label>
              <input type="date" v-model="from">
            </div>
            <div class="form-group">
              <label>{{ $t('Tot en met') }}</label>
              <input type="date" v-model="to">
            </div>
          </div>

          <div class="form-group">
            <label>{{ $t('Facturen') }}</label>
            <select v-model="status">
              <option value="all">{{ $t('Alle definitieve facturen') }}</option>
              <option value="open">{{ $t('Alleen openstaand') }}</option>
              <option value="paid">{{ $t('Alleen betaald') }}</option>
            </select>
          </div>

          <label class="check-row">
            <input type="checkbox" v-model="includeCredit">
            <span>{{ $t("Creditnota's meenemen") }}</span>
          </label>

          <a
            :href="canDownload ? downloadUrl : undefined"
            class="btn btn-primary btn-block"
            :class="{ disabled: !canDownload }"
            style="margin-top:18px;"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            {{ $t('Download CSV-export') }}
          </a>
        </div>
      </div>

      <div>
        <div class="card" style="margin-bottom:16px;">
          <div class="card-header"><div class="card-title">{{ $t('Auditfile (XAF 3.2)') }} <span class="pill pill-sent" style="margin-left:6px;">{{ $t('Nieuw') }}</span></div></div>
          <div class="card-body" style="font-size:13px;line-height:1.7;color:var(--text-2);">
            <p style="margin:0 0 12px;">{{ $t('Het standaardbestand dat elke Nederlandse accountant en de Belastingdienst direct kunnen inlezen (Twinfield, Exact, e-Boekhouden, AFAS, Snelstart …). Bevat per boekjaar het verkoopboek, inkoopboek en bankboek met btw per tarief, klanten en leveranciers — sluitend in debet en credit.') }}</p>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
              <select v-model="xafYear" style="max-width:140px;"><option v-for="y in xafYears" :key="y" :value="y">{{ $t('Boekjaar :year', { year: y }) }}</option></select>
              <a :href="xafUrl" class="btn btn-primary btn-sm">{{ $t('Download auditfile') }}</a>
            </div>
          </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-body" style="font-size:13px;line-height:1.7;color:var(--text-2);">
            <div style="font-family:var(--font-display);font-weight:600;font-size:15px;color:var(--text);margin-bottom:8px;">{{ $t('Wat zit er in de export?') }}</div>
            <ul style="padding-left:18px;margin:0;">
              <li>{{ $t('Alle definitieve facturen in de gekozen periode (concepten blijven buiten beschouwing)') }}</li>
              <li>{{ $t('Per factuur: nummer, datum, klant, status en referentie') }}</li>
              <li>{{ $t('Grondslag en BTW uitgesplitst per tarief (:rates)', { rates: vatRatesLabel }) }}</li>
              <li>{{ $t('Betaald, openstaand en betaaldatum') }}</li>
              <li>{{ $t('Controletotalen onderaan het bestand') }}</li>
            </ul>
            <div style="margin-top:12px;color:var(--text-3);">{{ $t('Het bestand opent direct goed in Excel (puntkomma-gescheiden, Nederlandse notatie).') }}</div>
          </div>
        </div>

        <div class="card">
          <div class="card-body" style="font-size:13px;line-height:1.7;color:var(--text-2);">
            <div style="font-family:var(--font-display);font-weight:600;font-size:15px;color:var(--text);margin-bottom:8px;">{{ $t('Tip: automatische kopie') }}</div>
            <template v-if="accountant_email">
              {{ $t('Je boekhouder') }} (<b>{{ accountant_email }}</b>) {{ $t('ontvangt al automatisch een kopie (BCC) van elke verstuurde factuur.') }}
            </template>
            <template v-else>
              {{ $t('Stel bij') }} <Link :href="route('settings.company')" style="color:var(--brand);font-weight:600;">{{ $t('Bedrijfsgegevens') }}</Link> {{ $t('het e-mailadres van je boekhouder in — die ontvangt dan automatisch een kopie (BCC) van elke verstuurde factuur.') }}
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.export-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 20px; align-items: start; }
.preset-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
.check-row { display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--text-2); cursor: pointer; }
.check-row input { width: 16px; height: 16px; accent-color: var(--brand); }
.btn.disabled { opacity: 0.5; pointer-events: none; }
@media (max-width: 900px) {
  .export-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
