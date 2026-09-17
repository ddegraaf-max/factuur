<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TenderRoundModal from '@/Components/TenderRoundModal.vue';
import { eur } from '@/format.js';
import { ref } from 'vue';

const props = defineProps({
  status: String,
  counts: Object,
  rounds: Array,
  packages: Array,
});

const showModal = ref(false);
const setStatus = (s) => router.get(route('tenders.index'), { status: s }, { preserveState: true, replace: true });

const pillClass = { open: 'pill-sent', awarded: 'pill-paid', closed: 'pill-cancelled' };
const pillLabel = { open: 'Open', awarded: 'Gegund', closed: 'Gesloten' };
</script>

<template>
  <Head :title="$t('Uitvragen')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Inkoop') }} / <span class="breadcrumb-current">{{ $t('Uitvragen') }}</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" @click="showModal = true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Nieuwe uitvraag') }}
      </button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Uitvragen bij onderaannemers') }}</h1>
        <p class="page-subtitle">{{ $t('Per onderdeel prijzen en beschikbaarheid opvragen, vergelijken en gunnen') }}</p>
      </div>
      <div class="page-actions">
        <Link :href="route('tenders.pool')" class="btn btn-secondary btn-sm">{{ $t('Pool & werkpakketten') }}</Link>
      </div>
    </div>

    <div class="filters" style="margin-bottom:14px;">
      <button v-for="s in ['open', 'awarded', 'closed', 'all']" :key="s" :class="['filter-chip', { active: status === s }]" @click="setStatus(s)">
        {{ $t({ open: 'Open', awarded: 'Gegund', closed: 'Gesloten', all: 'Alle' }[s]) }} <span class="count">{{ counts[s] }}</span>
      </button>
    </div>

    <div v-if="rounds.length" class="card">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Uitvraag') }}</th>
            <th>{{ $t('Project') }}</th>
            <th>{{ $t('Reageren vóór') }}</th>
            <th class="right">{{ $t('Reacties') }}</th>
            <th class="right">{{ $t('Laagste prijs') }}</th>
            <th class="right">{{ $t('Calculatie') }}</th>
            <th>{{ $t('Status') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rounds" :key="r.id" class="row-link" @click="router.visit(route('tenders.show', r.id))">
            <td class="cell-primary">
              {{ r.title }}
              <div class="sub">{{ r.package }}<template v-if="r.location"> · {{ r.location }}</template><template v-if="r.start_week"> · {{ $t('start week :week', { week: r.start_week }) }}</template></div>
            </td>
            <td>
              <template v-if="r.quote_number">{{ r.quote_number }}<div class="sub">{{ r.customer_name }}</div></template>
              <span v-else class="sub">—</span>
            </td>
            <td :class="{ warn: r.deadline_passed && r.status === 'open' }">{{ r.deadline_label }}</td>
            <td class="right num">{{ r.responded }} / {{ r.requested }}<span v-if="r.declined" class="sub"> · {{ $t(':n afgezegd', { n: r.declined }) }}</span></td>
            <td class="right num">{{ r.lowest !== null ? eur(r.lowest) : '—' }}</td>
            <td class="right num">{{ r.budget !== null ? eur(r.budget) : '—' }}</td>
            <td>
              <span :class="['pill', pillClass[r.status]]">{{ $t(pillLabel[r.status]) }}</span>
              <div v-if="r.awarded_to" class="sub">{{ r.awarded_to }}</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">{{ $t('Nog geen uitvragen') }}</div>
      <div style="margin-bottom:20px;max-width:520px;margin-left:auto;margin-right:auto;line-height:1.6;">
        {{ $t('Zet per onderdeel van een project (fundament, houtskelet, metselwerk …) een prijsaanvraag uit bij een handvol bedrijven. Zij geven prijs en beschikbaarheid door via een link; jij vergelijkt en gunt met één klik. Begin met de pool en de werkpakketten.') }}
      </div>
      <Link :href="route('tenders.pool')" class="btn btn-secondary btn-sm" style="display:inline-flex;margin-right:8px;">{{ $t('Pool & werkpakketten') }}</Link>
      <button class="btn btn-primary btn-sm" style="display:inline-flex;" @click="showModal = true">{{ $t('Nieuwe uitvraag') }}</button>
    </div>

    <TenderRoundModal :show="showModal" :packages="packages" @close="showModal = false" />
  </AppLayout>
</template>

<style scoped>
.row-link { cursor: pointer; }
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.warn { color: var(--warning); font-weight: 600; }
</style>
