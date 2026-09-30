<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';

const props = defineProps({
  status: String,
  q: String,
  counts: Object,
  projects: Array,
});

const search = ref(props.q || '');
let timer = null;
watch(search, (value) => {
  clearTimeout(timer);
  timer = setTimeout(() => router.get(route('projects.index'), { status: props.status, q: value || undefined }, { preserveState: true, replace: true }), 300);
});
const setStatus = (s) => router.get(route('projects.index'), { status: s, q: search.value || undefined }, { preserveState: true, replace: true });
const pct = (n) => (n === null || n === undefined ? '—' : n.toLocaleString('nl-NL', { maximumFractionDigits: 1 }) + '%');
</script>

<template>
  <Head :title="$t('Projecten')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">{{ $t('Verkoop') }} / <span class="breadcrumb-current">{{ $t('Projecten') }}</span></div>
    </template>
    <template #topbar-actions>
      <Link :href="route('projects.create')" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Nieuw project') }}
      </Link>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Projecten') }}</h1>
        <p class="page-subtitle">{{ $t('Per klus de offertes, facturen, inkoop, uren en uitvragen bij elkaar, met de calculatie en het resultaat.') }}</p>
      </div>
      <div class="page-actions">
        <Link :href="route('projects.create')" class="btn btn-secondary btn-sm">{{ $t('Nieuw project') }}</Link>
      </div>
    </div>

    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" type="text" :placeholder="$t('Zoek op naam, nummer of plaats…')">
      </div>
      <button :class="['filter-chip', { active: status === 'open' }]" @click="setStatus('open')">{{ $t('Open') }} <span class="chip-count">{{ counts.open }}</span></button>
      <button :class="['filter-chip', { active: status === 'closed' }]" @click="setStatus('closed')">{{ $t('Gesloten') }} <span class="chip-count">{{ counts.closed }}</span></button>
      <button :class="['filter-chip', { active: status === 'all' }]" @click="setStatus('all')">{{ $t('Alle') }}</button>
    </div>

    <div v-if="projects.length" class="card">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Project') }}</th>
            <th>{{ $t('Klant') }}</th>
            <th class="right">{{ $t('Afgesproken') }}</th>
            <th class="right">{{ $t('Gefactureerd') }}</th>
            <th class="right">{{ $t('Kosten') }}</th>
            <th class="right">{{ $t('Resultaat') }}</th>
            <th class="right">{{ $t('Marge') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in projects" :key="p.id" class="row-link" @click="router.visit(route('projects.show', p.id))">
            <td class="cell-primary">
              <Link :href="route('projects.show', p.id)" @click.stop>{{ p.name }}</Link>
              <div class="sub">{{ p.number }}<template v-if="p.location"> · {{ p.location }}</template><span v-if="p.status === 'closed'" class="pill pill-draft" style="margin-left:8px;">{{ $t('Gesloten') }}</span></div>
            </td>
            <td>{{ p.customer_name || '—' }}</td>
            <td class="right num">{{ p.agreed ? eur(p.agreed) : '—' }}</td>
            <td class="right num">{{ eur(p.invoiced) }}</td>
            <td class="right num">{{ eur(p.costs) }}</td>
            <td class="right num" :class="{ good: p.result > 0, bad: p.result < 0 }">{{ eur(p.result) }}</td>
            <td class="right num">{{ pct(p.margin) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="card">
      <div class="card-empty">
        <p>{{ status === 'open' ? $t('Nog geen open projecten.') : $t('Geen projecten gevonden.') }}</p>
        <p class="sub">{{ $t('Een project bundelt de offerte, de facturen, de inkoop, de uren en de uitvragen van één klus. Maak er een aan en koppel de documenten.') }}</p>
        <Link :href="route('projects.create')" class="btn btn-primary btn-sm" style="margin-top:12px;">{{ $t('Nieuw project') }}</Link>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.row-link { cursor: pointer; }
.row-link:hover td { background: var(--surface-2); }
.good { color: var(--success); font-weight: 600; }
.bad { color: var(--brand); font-weight: 600; }
.chip-count { margin-left: 6px; opacity: 0.7; }
.card-empty { padding: 40px 24px; text-align: center; }
.card-empty .sub { max-width: 520px; margin: 8px auto 0; line-height: 1.6; }
</style>
