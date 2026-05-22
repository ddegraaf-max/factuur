<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { ref, watch } from 'vue';

const props = defineProps({
  customers: Object,
  filters: Object,
});

const search = ref(props.filters.q || '');
const type = ref(props.filters.type || 'all');

let t = null;
watch(search, (v) => {
  clearTimeout(t);
  t = setTimeout(() => {
    router.get(route('customers.index'), { q: v, type: type.value }, { preserveState: true, replace: true });
  }, 250);
});

const setType = (v) => {
  type.value = v;
  router.get(route('customers.index'), { q: search.value, type: v }, { preserveState: true, replace: true });
};
</script>

<template>
  <Head title="Klanten" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Verkoop / <span class="breadcrumb-current">Klanten</span></div>
    </template>
    <template #topbar-actions>
      <Link :href="route('customers.create')" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nieuwe klant
      </Link>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Klanten</h1>
        <p class="page-subtitle">{{ customers.total }} klanten</p>
      </div>
    </div>

    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" type="text" placeholder="Zoek op naam, e-mail, plaats, KVK...">
      </div>
      <button :class="['filter-chip', { active: type === 'all' }]" @click="setType('all')">Alle</button>
      <button :class="['filter-chip', { active: type === 'business' }]" @click="setType('business')">Zakelijk</button>
      <button :class="['filter-chip', { active: type === 'consumer' }]" @click="setType('consumer')">Particulier</button>
    </div>

    <div v-if="customers.data.length > 0" class="customer-grid">
      <Link v-for="c in customers.data" :key="c.id" :href="route('customers.edit', c.id)" class="customer-card">
        <div class="customer-avatar">{{ c.initials }}</div>
        <div class="customer-info">
          <div class="customer-name">{{ c.name }}</div>
          <div class="customer-meta">
            <span v-if="c.city">{{ c.city }}</span>
            <span v-if="c.kvk_number" class="mono">KVK {{ c.kvk_number }}</span>
            <span v-if="c.type === 'consumer'" style="color:var(--info)">Particulier</span>
          </div>
          <div class="customer-stats">
            <div class="cs-item">
              <div class="cs-label">Facturen</div>
              <div class="cs-value mono">{{ c.invoices_count }}</div>
            </div>
            <div class="cs-item" v-if="c.outstanding > 0">
              <div class="cs-label">Openstaand</div>
              <div class="cs-value mono" style="color:var(--brand)">{{ eur(c.outstanding) }}</div>
            </div>
          </div>
        </div>
      </Link>
    </div>
    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">Geen klanten gevonden</div>
      <div style="margin-bottom:20px;">Voeg je eerste klant toe om facturen te maken.</div>
      <Link :href="route('customers.create')" class="btn btn-primary btn-sm" style="display:inline-flex;">+ Nieuwe klant</Link>
    </div>

    <div class="pagination" v-if="customers.last_page > 1">
      <Link v-for="link in customers.links" :key="link.label"
        :href="link.url || '#'"
        v-html="link.label"
        :class="['page-link', { active: link.active, disabled: !link.url }]"
        preserve-state
      />
    </div>
  </AppLayout>
</template>

<style>
.customer-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 14px;
}
.customer-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: 18px;
  display: flex;
  gap: 14px;
  align-items: flex-start;
  transition: all 0.15s;
  cursor: pointer;
}
.customer-card:hover {
  border-color: var(--brand-border);
  box-shadow: var(--shadow);
  transform: translateY(-1px);
}
.customer-avatar {
  width: 44px; height: 44px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
  color: white;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
  flex-shrink: 0;
}
.customer-info { flex: 1; min-width: 0; }
.customer-name {
  font-weight: 600;
  font-size: 15px;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.customer-meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  font-size: 12px;
  color: var(--text-3);
  margin-bottom: 12px;
}
.customer-meta span { white-space: nowrap; }
.customer-stats { display: flex; gap: 16px; padding-top: 10px; border-top: 1px solid var(--border); }
.cs-item { font-size: 11px; }
.cs-label { color: var(--text-4); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 2px; }
.cs-value { font-size: 13px; font-weight: 600; }
</style>
