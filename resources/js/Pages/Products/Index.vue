<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { ref, watch } from 'vue';

const props = defineProps({
  products: Object,
  filters: Object,
});

const search = ref(props.filters.q || '');
let t = null;
watch(search, (v) => {
  clearTimeout(t);
  t = setTimeout(() => {
    router.get(route('products.index'), { q: v }, { preserveState: true, replace: true });
  }, 250);
});
</script>

<template>
  <Head title="Producten" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Verkoop / <span class="breadcrumb-current">Producten</span></div>
    </template>
    <template #topbar-actions>
      <Link :href="route('products.create')" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nieuw product
      </Link>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Producten &amp; diensten</h1>
        <p class="page-subtitle">{{ products.total }} items · sjablonen voor factuurregels</p>
      </div>
    </div>

    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" type="text" placeholder="Zoek producten...">
      </div>
    </div>

    <div class="card" v-if="products.data.length > 0">
      <table class="data-table">
        <thead>
          <tr>
            <th>Naam</th>
            <th>SKU</th>
            <th>Eenheid</th>
            <th class="right">Prijs</th>
            <th>BTW</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in products.data" :key="p.id" @click="router.visit(route('products.edit', p.id))">
            <td>
              <div style="font-weight:500;">{{ p.name }}</div>
              <div v-if="p.description" style="font-size:12px;color:var(--text-3);margin-top:2px;max-width:480px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ p.description }}</div>
            </td>
            <td class="mono" style="color:var(--text-3);font-size:12px;">{{ p.sku || '—' }}</td>
            <td>{{ p.unit }}</td>
            <td class="num right">{{ eur(p.price) }}</td>
            <td class="num">{{ Number(p.vat_rate) }}%</td>
            <td>
              <span v-if="p.is_active" class="pill pill-paid" style="font-size:10px;">Actief</span>
              <span v-else class="pill pill-draft" style="font-size:10px;">Inactief</span>
            </td>
            <td><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--text-4)"><polyline points="9 18 15 12 9 6"/></svg></td>
          </tr>
        </tbody>
      </table>

      <div class="pagination" v-if="products.last_page > 1">
        <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
          :class="['page-link', { active: link.active, disabled: !link.url }]" preserve-state />
      </div>
    </div>
    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">Nog geen producten</div>
      <div style="margin-bottom:20px;">Voeg producten of diensten toe om sneller facturen te maken.</div>
      <Link :href="route('products.create')" class="btn btn-primary btn-sm" style="display:inline-flex;">+ Nieuw product</Link>
    </div>
  </AppLayout>
</template>
