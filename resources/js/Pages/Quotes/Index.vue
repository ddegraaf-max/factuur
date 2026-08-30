<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { t } from '@/i18n';
import { ref, watch } from 'vue';

const props = defineProps({
  quotes: Object,
  filters: Object,
  counts: Object,
});

const search = ref(props.filters.q ?? '');

let timer = null;
watch(search, (value) => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    router.get(route('quotes.index'), { status: props.filters.status, q: value || undefined }, {
      preserveState: true,
      replace: true,
    });
  }, 350);
});

const setStatus = (status) => {
  router.get(route('quotes.index'), { status, q: search.value || undefined }, {
    preserveState: true,
    replace: true,
  });
};

const chips = [
  { key: 'all', label: t('Alle') },
  { key: 'draft', label: t('Concept') },
  { key: 'sent', label: t('Verstuurd') },
  { key: 'accepted', label: t('Geaccepteerd') },
  { key: 'rejected', label: t('Afgewezen') },
  { key: 'expired', label: t('Verlopen') },
];

const pillClass = (status) => ({
  draft: 'pill-draft',
  sent: 'pill-sent',
  accepted: 'pill-paid',
  rejected: 'pill-overdue',
  expired: 'pill-partial',
}[status] ?? 'pill-draft');
</script>

<template>
  <Head :title="$t('Offertes')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Verkoop') }} / <span class="breadcrumb-current">{{ $t('Offertes') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Offertes') }}</h1>
        <p class="page-subtitle">{{ $t('Stuur een voorstel en zet het met één klik om in een factuur') }}</p>
      </div>
      <div class="page-actions">
        <Link :href="route('quotes.create')" class="btn btn-primary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          {{ $t('Nieuwe offerte') }}
        </Link>
      </div>
    </div>

    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" :placeholder="$t('Zoek op nummer, klant of referentie…')">
      </div>
      <button
        v-for="c in chips"
        :key="c.key"
        :class="['filter-chip', { active: filters.status === c.key }]"
        @click="setStatus(c.key)"
      >
        {{ c.label }}<span class="count">{{ counts[c.key] ?? 0 }}</span>
      </button>
    </div>

    <div v-if="quotes.data.length === 0" class="card card-empty">
      <div style="font-family:var(--font-display);font-size:18px;font-weight:600;margin-bottom:6px;color:var(--text);">{{ $t('Nog geen offertes') }}</div>
      <div style="margin-bottom:18px;">{{ $t('Maak een voorstel voor je klant. Gaat hij akkoord, dan wordt het met één klik een factuur.') }}</div>
      <Link :href="route('quotes.create')" class="btn btn-primary btn-sm" style="display:inline-flex;">{{ $t('Eerste offerte maken') }}</Link>
    </div>

    <div v-else class="card">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Nummer') }}</th>
            <th>{{ $t('Klant') }}</th>
            <th>{{ $t('Datum') }}</th>
            <th>{{ $t('Geldig tot') }}</th>
            <th>{{ $t('Status') }}</th>
            <th class="right">{{ $t('Bedrag') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="q in quotes.data" :key="q.id" @click="router.get(route('quotes.show', q.id))">
            <td class="cell-primary num">{{ q.number || $t('Concept') }}</td>
            <td :data-label="$t('Klant')">{{ q.customer_name }}</td>
            <td :data-label="$t('Datum')">{{ q.quote_date_label }}</td>
            <td :data-label="$t('Geldig tot')">
              {{ q.valid_until_label }}
              <span v-if="q.status === 'sent' && q.days_left > 0" style="color:var(--text-4);font-size:12px;">· {{ $t('nog :n d.', { n: q.days_left }) }}</span>
            </td>
            <td :data-label="$t('Status')">
              <span class="pill" :class="pillClass(q.status)">{{ q.status_label }}</span>
              <span v-if="q.converted" class="pill pill-sent" style="margin-left:4px;">{{ $t('Gefactureerd') }}</span>
            </td>
            <td class="right num" :data-label="$t('Bedrag')">{{ eur(q.total) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="quotes.links && quotes.last_page > 1" class="pager">
      <Link
        v-for="link in quotes.links"
        :key="link.label"
        :href="link.url || '#'"
        :class="['pager-link', { active: link.active, disabled: !link.url }]"
        v-html="link.label"
      />
    </div>
  </AppLayout>
</template>

<style scoped>
.pager { display: flex; gap: 4px; justify-content: center; margin-top: 20px; flex-wrap: wrap; }
.pager-link {
  min-width: 34px; height: 34px;
  display: inline-flex; align-items: center; justify-content: center;
  padding: 0 10px;
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  background: var(--surface);
  font-size: 13px;
}
.pager-link.active { background: var(--text); color: #fff; border-color: var(--text); }
.pager-link.disabled { opacity: 0.4; pointer-events: none; }
</style>
