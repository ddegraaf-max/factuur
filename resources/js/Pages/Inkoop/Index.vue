<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  purchases: Object,
  filters: Object,
  counts: Object,
  stats: Object,
  by_supplier: Array,
});

const search = ref(props.filters.q || '');
const status = ref(props.filters.status || 'all');

let searchTimeout = null;
watch(search, (v) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(route('purchases.index'), { q: v, status: status.value }, { preserveState: true, replace: true });
  }, 250);
});

const setStatus = (s) => {
  status.value = s;
  router.get(route('purchases.index'), { q: search.value, status: s }, { preserveState: true, replace: true });
};

const maxSupplier = computed(() => Math.max(1, ...props.by_supplier.map(s => s.open_total)));
</script>

<template>
  <Head :title="$t('Inkoopfacturen')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">{{ $t('Inkoop') }} / <span class="breadcrumb-current">{{ $t('Inkoopfacturen') }}</span></div>
    </template>
    <template #topbar-actions>
      <Link :href="route('purchases.create')" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Inkoopfactuur inboeken') }}
      </Link>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Inkoopfacturen') }}</h1>
        <p class="page-subtitle">{{ $t('Crediteuren · boek binnengekomen facturen in, handmatig of met een foto erbij') }}</p>
      </div>
      <a :href="route('purchases.csv')" class="btn btn-secondary btn-sm" :title="$t('Download alle inkoopfacturen van dit jaar als CSV voor je boekhouder')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        {{ $t('CSV-export') }}
      </a>
    </div>

    <div class="kpi-grid">
      <div class="kpi">
        <div class="lbl">{{ $t('Openstaand bij leveranciers') }}</div>
        <div class="val">{{ eur(stats.open_total) }}</div>
        <div class="meta">{{ $t(':n open facturen', { n: stats.open_count }) }}</div>
      </div>
      <div class="kpi" :class="{ alert: stats.overdue_count > 0 }">
        <div class="lbl">{{ $t('Waarvan over de vervaldatum') }}</div>
        <div class="val">{{ eur(stats.overdue_total) }}</div>
        <div class="meta">{{ $t(':n facturen te laat', { n: stats.overdue_count }) }}</div>
      </div>
      <div class="kpi">
        <div class="lbl">{{ $t('Voorbelasting :period', { period: stats.quarter_label }) }}</div>
        <div class="val">{{ eur(stats.quarter_vat) }}</div>
        <div class="meta">{{ $t('rubriek 5b — telt mee in je BTW-aangifte') }}</div>
      </div>
      <div class="kpi">
        <div class="lbl">{{ $t('Kosten :year excl. BTW', { year: stats.year_label }) }}</div>
        <div class="val">{{ eur(stats.year_base) }}</div>
        <div class="meta">{{ $t('totaal ingeboekt dit jaar') }}</div>
      </div>
    </div>

    <!-- Crediteuren: openstaand per leverancier -->
    <div v-if="by_supplier.length > 0" class="card" style="margin-bottom:16px;">
      <div class="card-header"><div class="card-title">{{ $t('Openstaand per leverancier') }}</div></div>
      <div class="card-body">
        <div v-for="s in by_supplier" :key="s.supplier_name" class="sup-row">
          <div class="sup-name">{{ s.supplier_name }} <span class="muted">· {{ s.count }} {{ s.count === 1 ? $t('factuur') : $t('facturen') }}</span></div>
          <div class="sup-track"><div class="sup-fill" :style="{ width: Math.max(s.open_total / maxSupplier * 100, 2) + '%' }"></div></div>
          <div class="sup-amount">{{ eur(s.open_total) }}</div>
        </div>
      </div>
    </div>

    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" type="text" :placeholder="$t('Zoek op leverancier, factuurnummer, categorie...')">
      </div>
      <button :class="['filter-chip', { active: status === 'all' }]" @click="setStatus('all')">{{ $t('Alle') }} <span class="count">{{ counts.all }}</span></button>
      <button :class="['filter-chip', { active: status === 'open' }]" @click="setStatus('open')">{{ $t('Open') }} <span class="count">{{ counts.open }}</span></button>
      <button :class="['filter-chip', { active: status === 'overdue' }]" @click="setStatus('overdue')">{{ $t('Vervallen') }} <span class="count">{{ counts.overdue }}</span></button>
      <button :class="['filter-chip', { active: status === 'paid' }]" @click="setStatus('paid')">{{ $t('Betaald') }} <span class="count">{{ counts.paid }}</span></button>
    </div>

    <div class="card" v-if="purchases.data.length > 0">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Leverancier') }}</th>
            <th>{{ $t('Categorie') }}</th>
            <th>{{ $t('Datum') }}</th>
            <th>{{ $t('Vervaldatum') }}</th>
            <th>{{ $t('Status') }}</th>
            <th class="right">{{ $t('BTW') }}</th>
            <th class="right">{{ $t('Bedrag') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in purchases.data" :key="p.id" @click="router.visit(route('purchases.show', p.id))">
            <td class="cell-primary">
              {{ p.supplier_name }}
              <span v-if="p.supplier_reference" class="muted"> · {{ p.supplier_reference }}</span>
              <svg v-if="p.attachments_count > 0" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--text-4)" stroke-width="2" style="margin-left:5px;vertical-align:-1px;"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            </td>
            <td :data-label="$t('Categorie')">{{ p.category || '—' }}</td>
            <td :data-label="$t('Datum')">{{ p.invoice_date_label }}</td>
            <td :data-label="$t('Vervaldatum')">{{ p.due_date_label || '—' }}</td>
            <td :data-label="$t('Status')">
              <span v-if="p.status === 'paid'" class="pill pill-paid">{{ $t('Betaald') }}</span>
              <span v-else-if="p.is_overdue" class="pill pill-overdue">{{ $t(':n d. over tijd', { n: p.days_overdue }) }}</span>
              <span v-else class="pill pill-sent">{{ $t('Open') }}</span>
            </td>
            <td class="num right" :data-label="$t('BTW')">{{ eur(p.vat_total) }}</td>
            <td class="num right" :data-label="$t('Bedrag')">{{ eur(p.total) }}</td>
            <td><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--text-4)"><polyline points="9 18 15 12 9 6"/></svg></td>
          </tr>
        </tbody>
      </table>

      <div class="pagination" v-if="purchases.last_page > 1">
        <Link v-for="link in purchases.links" :key="link.label"
          :href="link.url || '#'"
          v-html="link.label"
          :class="['page-link', { active: link.active, disabled: !link.url }]"
          preserve-state
        />
      </div>
    </div>
    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">
        {{ counts.all === 0 ? $t('Nog geen inkoopfacturen') : $t('Geen resultaten') }}
      </div>
      <div style="margin-bottom:20px;">
        {{ counts.all === 0
          ? $t('Boek je eerste inkoopfactuur in — de BTW telt direct mee als voorbelasting in je aangifte.')
          : $t('Pas je zoekopdracht of filter aan.') }}
      </div>
      <Link v-if="counts.all === 0" :href="route('purchases.create')" class="btn btn-primary btn-sm" style="display:inline-flex;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Eerste inkoopfactuur inboeken') }}
      </Link>
    </div>
  </AppLayout>
</template>

<style scoped>
.kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.kpi.alert { background: var(--brand-tint); border-color: var(--brand-border); }
.kpi.alert .val { color: var(--brand-darker); }
.kpi .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.kpi .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; }
.kpi .meta { font-size: 11px; color: var(--text-3); margin-top: 4px; }

.sup-row { display: grid; grid-template-columns: 220px 1fr 120px; gap: 14px; align-items: center; padding: 8px 0; }
.sup-name { font-size: 13px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sup-track { height: 20px; background: var(--surface-2); border-radius: 5px; }
.sup-fill { height: 100%; background: linear-gradient(90deg, var(--warning), #D97706); border-radius: 5px; }
.sup-amount { text-align: right; font-family: var(--font-mono); font-weight: 600; font-size: 13.5px; }

@media (max-width: 760px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .kpi { padding: 14px; }
  .kpi .val { font-size: 18px; }
  .sup-row { grid-template-columns: minmax(0, 1fr) auto; gap: 4px 10px; }
  .sup-name { grid-column: 1 / -1; }
}
@media (max-width: 400px) {
  .kpi-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
