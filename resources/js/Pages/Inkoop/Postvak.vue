<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { t } from '@/i18n';
import { eur } from '@/format';
import { ref } from 'vue';

const props = defineProps({
  items: Object,           // paginator
  filters: Object,         // { status }
  counts: Object,          // { pending, done }
  inbound_address: String, // bon-xxxx@inboekdomein (of null)
  configured: Boolean,     // is het inbound-maildomein ingericht?
  scan_enabled: Boolean,   // automatische herkenning actief (ANTHROPIC_API_KEY)
});

/* ---------- Direct inboeken vanuit het voorstel ---------- */
const booking = ref(null);
const book = (item) => {
  booking.value = item.id;
  router.post(route('purchases.inbox.book', item.id), {}, {
    preserveScroll: true,
    onFinish: () => { booking.value = null; },
  });
};

const setStatus = (status) => router.get(route('purchases.inbox.index'), { status }, { preserveState: true });

/* ---------- Inboek-adres kopiëren ---------- */
const copied = ref(false);
const copyAddress = async () => {
  try {
    await navigator.clipboard.writeText(props.inbound_address);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2500);
  } catch (e) {
    prompt(t('Kopieer het adres handmatig:'), props.inbound_address);
  }
};

const rotateAddress = () => {
  if (confirm(t('Nieuw inboek-adres aanmaken?\n\nHet huidige adres werkt daarna niet meer — handig als het adres bij spammers bekend is geraakt.'))) {
    router.post(route('purchases.inbox.rotate'), {}, { preserveScroll: true });
  }
};

/* ---------- Items ---------- */
const dismiss = (item) => {
  router.post(route('purchases.inbox.dismiss', item.id), {}, { preserveScroll: true });
};

const remove = (item) => {
  if (confirm(t('":filename" definitief verwijderen uit het postvak?', { filename: item.filename }))) {
    router.delete(route('purchases.inbox.destroy', item.id), { preserveScroll: true });
  }
};
</script>

<template>
  <Head :title="$t('Postvak IN')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">{{ $t('Inkoop') }} / <span class="breadcrumb-current">{{ $t('Postvak IN') }}</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Postvak IN') }}</h1>
        <p class="page-subtitle">{{ $t('Stuur bonnen en inkoopfacturen (of laat leveranciers dat doen) naar je eigen inboek-adres — ze verschijnen hier, klaar om in te boeken.') }}</p>
      </div>
    </div>

    <!-- Inboek-adres -->
    <div v-if="configured && inbound_address" class="pv-address">
      <div class="pv-address-info">
        <div class="pv-address-label">{{ $t('Jouw inboek-adres') }}</div>
        <div class="pv-address-value mono">{{ inbound_address }}</div>
        <div class="pv-address-hint">
          {{ $t('Stuur (of forward) e-mails met een PDF of foto als bijlage naar dit adres. Alleen bijlagen tellen; de mailtekst wordt niet bewaard.') }}
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" class="btn btn-primary btn-sm" @click="copyAddress">
          {{ copied ? $t('Gekopieerd ✓') : $t('Kopieer adres') }}
        </button>
        <button type="button" class="btn btn-secondary btn-sm" :title="$t('Het oude adres vervalt')" @click="rotateAddress">{{ $t('Nieuw adres') }}</button>
      </div>
    </div>
    <div v-else class="pv-address pv-address-off">
      <div class="pv-address-info">
        <div class="pv-address-label">{{ $t('Nog niet geactiveerd') }}</div>
        <div class="pv-address-hint" style="margin-top:4px;">
          {{ $t('Het aanleveren per e-mail vereist een eenmalige serverinstelling (inbound-maildomein). Zodra die is ingericht verschijnt hier je persoonlijke inboek-adres — zie de beheerdocumentatie.') }}
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <button :class="['filter-chip', { active: filters.status === 'pending' }]" @click="setStatus('pending')">
        {{ $t('Te verwerken') }} <span class="count">{{ counts.pending }}</span>
      </button>
      <button :class="['filter-chip', { active: filters.status === 'done' }]" @click="setStatus('done')">
        {{ $t('Afgehandeld') }} <span class="count">{{ counts.done }}</span>
      </button>
    </div>

    <!-- Items -->
    <div v-if="items.data.length" class="pv-grid">
      <div v-for="item in items.data" :key="item.id" class="card pv-item">
        <a :href="route('purchases.inbox.file', item.id)" target="_blank" class="pv-thumb-wrap" :title="$t('Bekijk het bestand')">
          <img v-if="item.is_image" :src="route('purchases.inbox.file', item.id)" class="pv-thumb" alt="" loading="lazy">
          <span v-else class="pv-thumb pv-thumb-pdf">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </span>
        </a>
        <div class="pv-body">
          <div class="pv-name">{{ item.filename }}</div>
          <div class="pv-meta">
            {{ item.received_label }} · {{ item.size_label }}
            <template v-if="item.from_email"><br>{{ $t('van :email', { email: item.from_email }) }}</template>
            <template v-if="item.subject"><br>„{{ item.subject }}"</template>
          </div>

          <!-- Boekingsvoorstel uit de automatische herkenning -->
          <div v-if="item.status === 'pending' && item.proposal" class="pv-proposal">
            <div class="pv-proposal-title">{{ $t('Boekingsvoorstel') }}</div>
            <div class="pv-proposal-line">
              <strong>{{ item.proposal.supplier_name || $t('Leverancier onbekend') }}</strong>
              · {{ eur(item.proposal.total_incl) }} {{ $t('incl. btw') }}
            </div>
            <div class="pv-proposal-sub">
              <template v-if="item.proposal.invoice_date">{{ item.proposal.invoice_date }}</template>
              <template v-if="item.proposal.category"> · {{ item.proposal.category }}</template>
            </div>
            <div v-if="item.proposal.warning" class="pv-proposal-warn">⚠ {{ item.proposal.warning }}</div>
          </div>
          <div v-else-if="item.status === 'pending' && item.scan_error" class="pv-proposal pv-proposal-err">
            {{ $t('Niet automatisch herkend: :error', { error: item.scan_error }) }}
          </div>
          <div v-else-if="item.status === 'pending' && scan_enabled && !item.scanned" class="pv-proposal pv-proposal-wait">
            {{ $t('Wordt automatisch herkend — het voorstel staat hier binnen een paar minuten.') }}
          </div>

          <div class="pv-actions">
            <template v-if="item.status === 'pending'">
              <template v-if="item.proposal">
                <button type="button" class="btn btn-primary btn-sm" :disabled="booking === item.id"
                        :title="item.proposal.warning ? $t('Let op: de bedragen sloten niet helemaal — controleer eerst') : ''"
                        @click="book(item)">
                  {{ booking === item.id ? $t('Bezig…') : $t('Direct inboeken') }}
                </button>
                <Link :href="route('purchases.create', { inbox: item.id })" class="btn btn-secondary btn-sm">{{ $t('Controleer eerst') }}</Link>
              </template>
              <Link v-else :href="route('purchases.create', { inbox: item.id })" class="btn btn-primary btn-sm">{{ $t('Inboeken') }}</Link>
              <button type="button" class="btn btn-secondary btn-sm" @click="dismiss(item)">{{ $t('Afwijzen') }}</button>
            </template>
            <template v-else>
              <Link v-if="item.purchase_invoice_id" :href="route('purchases.show', item.purchase_invoice_id)" class="pill pill-paid" style="text-decoration:none;">
                {{ $t('Ingeboekt') }}{{ item.purchase_supplier ? ` — ${item.purchase_supplier}` : '' }}
              </Link>
              <span v-else class="pill pill-muted">{{ $t('Afgewezen') }}</span>
              <button type="button" class="icon-btn" :title="$t('Verwijderen')" @click="remove(item)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="card card-empty">
      <div style="font-family:var(--font-display);font-weight:600;font-size:18px;color:var(--text);margin-bottom:6px;">
        {{ filters.status === 'pending' ? $t('Niets te verwerken') : $t('Nog niets afgehandeld') }}
      </div>
      <div>
        {{ filters.status === 'pending'
          ? $t('Stuur een bon of factuur (PDF of foto) naar je inboek-adres en hij verschijnt hier vanzelf.')
          : $t('Ingeboekte en afgewezen items komen hier te staan.') }}
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

.pv-proposal {
  margin-top: 10px; padding: 8px 10px; border-radius: 8px; font-size: 12px; line-height: 1.55;
  background: var(--success-bg, #F0FDF4); border: 1px solid var(--success-border, #BBF7D0);
}
.pv-proposal-title { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--success, #16A34A); margin-bottom: 2px; }
.pv-proposal-line { font-size: 12.5px; }
.pv-proposal-sub { color: var(--text-3); margin-top: 1px; }
.pv-proposal-warn { color: #92400E; margin-top: 4px; }
.pv-proposal-err { background: var(--surface-2); border-color: var(--border); color: var(--text-3); }
.pv-proposal-wait { background: var(--surface-2); border-color: var(--border); color: var(--text-3); font-style: italic; }

.pill-muted { background: var(--surface-2); color: var(--text-3); }
.icon-btn { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); }
.icon-btn:hover { background: var(--surface-2); color: var(--brand-dark); }
</style>
