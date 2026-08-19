<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
  items: Object,           // paginator
  filters: Object,         // { status }
  counts: Object,          // { pending, done }
  inbound_address: String, // bon-xxxx@inboekdomein (of null)
  configured: Boolean,     // is het inbound-maildomein ingericht?
});

const setStatus = (status) => router.get(route('purchases.inbox.index'), { status }, { preserveState: true });

/* ---------- Inboek-adres kopiëren ---------- */
const copied = ref(false);
const copyAddress = async () => {
  try {
    await navigator.clipboard.writeText(props.inbound_address);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2500);
  } catch (e) {
    prompt('Kopieer het adres handmatig:', props.inbound_address);
  }
};

const rotateAddress = () => {
  if (confirm('Nieuw inboek-adres aanmaken?\n\nHet huidige adres werkt daarna niet meer — handig als het adres bij spammers bekend is geraakt.')) {
    router.post(route('purchases.inbox.rotate'), {}, { preserveScroll: true });
  }
};

/* ---------- Items ---------- */
const dismiss = (item) => {
  router.post(route('purchases.inbox.dismiss', item.id), {}, { preserveScroll: true });
};

const remove = (item) => {
  if (confirm(`"${item.filename}" definitief verwijderen uit het postvak?`)) {
    router.delete(route('purchases.inbox.destroy', item.id), { preserveScroll: true });
  }
};
</script>

<template>
  <Head title="Postvak IN" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Inkoop / <span class="breadcrumb-current">Postvak IN</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Postvak IN</h1>
        <p class="page-subtitle">Stuur bonnen en inkoopfacturen (of laat leveranciers dat doen) naar je eigen inboek-adres — ze verschijnen hier, klaar om in te boeken.</p>
      </div>
    </div>

    <!-- Inboek-adres -->
    <div v-if="configured && inbound_address" class="pv-address">
      <div class="pv-address-info">
        <div class="pv-address-label">Jouw inboek-adres</div>
        <div class="pv-address-value mono">{{ inbound_address }}</div>
        <div class="pv-address-hint">
          Stuur (of forward) e-mails met een PDF of foto als bijlage naar dit adres.
          Alleen bijlagen tellen; de mailtekst wordt niet bewaard.
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" class="btn btn-primary btn-sm" @click="copyAddress">
          {{ copied ? 'Gekopieerd ✓' : 'Kopieer adres' }}
        </button>
        <button type="button" class="btn btn-secondary btn-sm" title="Het oude adres vervalt" @click="rotateAddress">Nieuw adres</button>
      </div>
    </div>
    <div v-else class="pv-address pv-address-off">
      <div class="pv-address-info">
        <div class="pv-address-label">Nog niet geactiveerd</div>
        <div class="pv-address-hint" style="margin-top:4px;">
          Het aanleveren per e-mail vereist een eenmalige serverinstelling (inbound-maildomein).
          Zodra die is ingericht verschijnt hier je persoonlijke inboek-adres — zie de beheerdocumentatie.
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <button :class="['filter-chip', { active: filters.status === 'pending' }]" @click="setStatus('pending')">
        Te verwerken <span class="count">{{ counts.pending }}</span>
      </button>
      <button :class="['filter-chip', { active: filters.status === 'done' }]" @click="setStatus('done')">
        Afgehandeld <span class="count">{{ counts.done }}</span>
      </button>
    </div>

    <!-- Items -->
    <div v-if="items.data.length" class="pv-grid">
      <div v-for="item in items.data" :key="item.id" class="card pv-item">
        <a :href="route('purchases.inbox.file', item.id)" target="_blank" class="pv-thumb-wrap" title="Bekijk het bestand">
          <img v-if="item.is_image" :src="route('purchases.inbox.file', item.id)" class="pv-thumb" alt="" loading="lazy">
          <span v-else class="pv-thumb pv-thumb-pdf">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </span>
        </a>
        <div class="pv-body">
          <div class="pv-name">{{ item.filename }}</div>
          <div class="pv-meta">
            {{ item.received_label }} · {{ item.size_label }}
            <template v-if="item.from_email"><br>van {{ item.from_email }}</template>
            <template v-if="item.subject"><br>„{{ item.subject }}"</template>
          </div>
          <div class="pv-actions">
            <template v-if="item.status === 'pending'">
              <Link :href="route('purchases.create', { inbox: item.id })" class="btn btn-primary btn-sm">Inboeken</Link>
              <button type="button" class="btn btn-secondary btn-sm" @click="dismiss(item)">Afwijzen</button>
            </template>
            <template v-else>
              <Link v-if="item.purchase_invoice_id" :href="route('purchases.show', item.purchase_invoice_id)" class="pill pill-paid" style="text-decoration:none;">
                Ingeboekt{{ item.purchase_supplier ? ` — ${item.purchase_supplier}` : '' }}
              </Link>
              <span v-else class="pill pill-muted">Afgewezen</span>
              <button type="button" class="icon-btn" title="Verwijderen" @click="remove(item)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">
        {{ filters.status === 'pending' ? 'Niets te verwerken' : 'Nog niets afgehandeld' }}
      </div>
      <div>
        {{ filters.status === 'pending'
          ? 'Stuur een bon of factuur (PDF of foto) naar je inboek-adres en hij verschijnt hier vanzelf.'
          : 'Ingeboekte en afgewezen items komen hier te staan.' }}
      </div>
    </div>

    <div class="pagination" v-if="items.last_page > 1" style="margin-top:16px;">
      <Link v-for="link in items.links" :key="link.label"
        :href="link.url || '#'"
        v-html="link.label"
        :class="['page-link', { active: link.active, disabled: !link.url }]"
        preserve-state
      />
    </div>
  </AppLayout>
</template>

<style scoped>
.pv-address {
  display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
  background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
  padding: 16px 20px; margin-bottom: 16px;
}
.pv-address-off { border-style: dashed; }
.pv-address-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; color: var(--text-4); }
.pv-address-value { font-size: 16px; font-weight: 600; margin-top: 4px; word-break: break-all; }
.pv-address-hint { font-size: 12px; color: var(--text-3); margin-top: 6px; line-height: 1.55; max-width: 560px; }

.pv-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
.pv-item { display: flex; gap: 14px; padding: 14px; }
.pv-thumb-wrap { flex: none; }
.pv-thumb { width: 84px; height: 84px; border-radius: 9px; object-fit: cover; background: var(--surface-2); display: block; }
.pv-thumb-pdf { display: flex; align-items: center; justify-content: center; color: var(--text-3); }
.pv-thumb-pdf svg { width: 30px; height: 30px; }
.pv-body { flex: 1; min-width: 0; }
.pv-name { font-weight: 600; font-size: 13.5px; word-break: break-word; }
.pv-meta { font-size: 11.5px; color: var(--text-3); margin-top: 3px; line-height: 1.5; }
.pv-actions { display: flex; align-items: center; gap: 8px; margin-top: 10px; flex-wrap: wrap; }

.pill-muted { background: var(--surface-2); color: var(--text-3); }
.icon-btn { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.icon-btn:hover { background: var(--surface-2); color: var(--brand-dark); }
</style>
