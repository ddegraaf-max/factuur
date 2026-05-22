<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { eur } from '@/format.js';
import { ref, watch } from 'vue';

const props = defineProps({
  invoices: Object,
  filters: Object,
  counts: Object,
});

const search = ref(props.filters.q || '');
const status = ref(props.filters.status || 'all');

let searchTimeout = null;
watch(search, (v) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(route('invoices.index'), { q: v, status: status.value }, { preserveState: true, replace: true });
  }, 250);
});

const setStatus = (s) => {
  status.value = s;
  router.get(route('invoices.index'), { q: search.value, status: s }, { preserveState: true, replace: true });
};
</script>

<template>
  <Head title="Facturen" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Verkoop / <span class="breadcrumb-current">Facturen</span></div>
    </template>
    <template #topbar-actions>
      <Link :href="route('invoices.create')" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nieuwe factuur
      </Link>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Facturen</h1>
        <p class="page-subtitle">{{ counts.all }} facturen totaal</p>
      </div>
    </div>

    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" type="text" placeholder="Zoek op klant, nummer, referentie...">
      </div>
      <button :class="['filter-chip', { active: status === 'all' }]" @click="setStatus('all')">Alle <span class="count">{{ counts.all }}</span></button>
      <button :class="['filter-chip', { active: status === 'draft' }]" @click="setStatus('draft')">Concept <span class="count">{{ counts.draft }}</span></button>
      <button :class="['filter-chip', { active: status === 'sent' }]" @click="setStatus('sent')">Verstuurd <span class="count">{{ counts.sent }}</span></button>
      <button :class="['filter-chip', { active: status === 'overdue' }]" @click="setStatus('overdue')">Achterstallig <span class="count">{{ counts.overdue }}</span></button>
      <button :class="['filter-chip', { active: status === 'partial' }]" @click="setStatus('partial')">Deels betaald <span class="count">{{ counts.partial }}</span></button>
      <button :class="['filter-chip', { active: status === 'incasso' }]" @click="setStatus('incasso')">Bij incasso <span class="count">{{ counts.incasso }}</span></button>
      <button :class="['filter-chip', { active: status === 'paid' }]" @click="setStatus('paid')">Betaald <span class="count">{{ counts.paid }}</span></button>
      <button :class="['filter-chip', { active: status === 'creditnota' }]" @click="setStatus('creditnota')">Creditnota's <span class="count">{{ counts.creditnota }}</span></button>
    </div>

    <div class="card" v-if="invoices.data.length > 0">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nummer</th>
            <th>Klant</th>
            <th>Datum</th>
            <th>Vervaldatum</th>
            <th>Status</th>
            <th class="right">Bedrag</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inv in invoices.data" :key="inv.id" @click="router.visit(route('invoices.show', inv.id))">
            <td class="num">{{ inv.number || '— concept —' }}</td>
            <td>{{ inv.customer_name }}</td>
            <td>{{ inv.invoice_date_label }}</td>
            <td>{{ inv.due_date_label || '—' }}</td>
            <td><StatusPill :status="inv.status" :days-overdue="inv.days_overdue" /></td>
            <td class="num right">{{ eur(inv.total) }}</td>
            <td><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--text-4)"><polyline points="9 18 15 12 9 6"/></svg></td>
          </tr>
        </tbody>
      </table>

      <div class="pagination" v-if="invoices.last_page > 1">
        <Link v-for="link in invoices.links" :key="link.label"
          :href="link.url || '#'"
          v-html="link.label"
          :class="['page-link', { active: link.active, disabled: !link.url }]"
          preserve-state
        />
      </div>
    </div>
    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">Nog geen facturen</div>
      <div style="margin-bottom:20px;">Begin met je eerste factuur.</div>
      <Link :href="route('invoices.create')" class="btn btn-primary btn-sm" style="display:inline-flex;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Maak je eerste factuur
      </Link>
    </div>
  </AppLayout>
</template>

<style>
.pagination {
  display: flex;
  gap: 4px;
  justify-content: center;
  padding: 16px;
  border-top: 1px solid var(--border);
}
.page-link {
  padding: 6px 10px;
  border-radius: var(--r-sm);
  font-size: 13px;
  color: var(--text-2);
  border: 1px solid var(--border);
}
.page-link:hover { background: var(--surface-2); }
.page-link.active { background: var(--brand); color: white; border-color: var(--brand); }
.page-link.disabled { opacity: 0.4; pointer-events: none; }
</style>
