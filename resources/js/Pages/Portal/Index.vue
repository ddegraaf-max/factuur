<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { eur } from '@/format.js';

const props = defineProps({
  email: String,
  invoices: Array,
  quotes: { type: Array, default: () => [] },
  stats: Object,
});

// Offertes: pil-kleuren als bij facturen; open offertes bovenaan (daar wordt op gewacht).
const quotePill = (q) => ({ sent: 'pill-sent', accepted: 'pill-paid', rejected: 'pill-overdue', expired: 'pill-partial' }[q.status] ?? 'pill-draft');
const quotesFirst = computed(() => props.quotes.some((q) => q.awaiting));
const openQuote = (q) => router.get(route('portal.quote', q.token));

// Facturen van meerdere bedrijven? Dan groeperen we per afzender.
const groups = computed(() => {
  const byCompany = new Map();
  for (const inv of props.invoices) {
    const key = inv.company_name || 'Onbekende afzender';
    if (!byCompany.has(key)) byCompany.set(key, []);
    byCompany.get(key).push(inv);
  }
  return [...byCompany.entries()].map(([company, invoices]) => ({ company, invoices }));
});

const multiCompany = computed(() => {
  const names = new Set([...props.invoices, ...props.quotes].map((d) => d.company_name || 'Onbekende afzender'));
  return names.size > 1;
});

const open = (inv) => router.get(route('portal.invoice', inv.token));
</script>

<template>
  <Head title="Jouw facturen en offertes · Klantenportaal" />
  <PortalLayout :email="email">
    <div class="portal-page-head">
      <h1 class="portal-card-title" style="margin-bottom:4px;">Jouw facturen en offertes</h1>
      <p class="portal-card-sub" style="margin-bottom:0;">Alles wat naar {{ email }} is verstuurd, op één plek.</p>
    </div>

    <div class="portal-stats">
      <div class="portal-stat">
        <div class="portal-stat-label">Openstaand bedrag</div>
        <div class="portal-stat-value">{{ eur(stats.open_amount) }}</div>
      </div>
      <div class="portal-stat">
        <div class="portal-stat-label">Open facturen</div>
        <div class="portal-stat-value">{{ stats.open_count }}</div>
      </div>
      <div class="portal-stat" :class="{ 'portal-stat-alert': stats.overdue_count > 0 }">
        <div class="portal-stat-label">Waarvan over de vervaldatum</div>
        <div class="portal-stat-value">{{ stats.overdue_count }}</div>
      </div>
      <div v-if="quotes.length" class="portal-stat" :class="{ 'portal-stat-attention': stats.quotes_open_count > 0 }">
        <div class="portal-stat-label">Offertes die op u wachten</div>
        <div class="portal-stat-value">{{ stats.quotes_open_count }}</div>
      </div>
    </div>

    <div v-if="invoices.length === 0 && quotes.length === 0" class="portal-card portal-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <div class="portal-empty-title">Geen facturen of offertes gevonden</div>
      <p>Er is (nog) niets verstuurd naar dit e-mailadres.</p>
    </div>

    <div class="portal-sections">
    <!-- Offertes: bovenaan als er een op reactie wacht, anders onder de facturen -->
    <div v-if="quotes.length" class="portal-group" :style="{ order: quotesFirst ? 0 : 2 }">
      <div class="portal-group-title">Offertes</div>
      <div class="portal-list">
        <button v-for="q in quotes" :key="q.token" type="button" class="portal-row" :class="{ 'portal-row-attention': q.awaiting }" @click="openQuote(q)">
          <div class="portal-row-main">
            <div class="portal-row-number">
              Offerte {{ q.number }}
              <span v-if="q.company_name" class="portal-row-company">· {{ q.company_name }}</span>
            </div>
            <div class="portal-row-meta">
              {{ q.quote_date_label }}
              <template v-if="q.awaiting && q.valid_until_label">· geldig tot {{ q.valid_until_label }}<template v-if="q.days_left > 0"> (nog {{ q.days_left }} {{ q.days_left === 1 ? 'dag' : 'dagen' }})</template></template>
              <template v-else-if="q.status === 'accepted' && q.accepted_at_label">· geaccepteerd op {{ q.accepted_at_label }}</template>
            </div>
          </div>
          <div class="portal-row-side">
            <div class="portal-row-amount">
              <div class="portal-row-total">{{ eur(q.total) }}</div>
              <div v-if="q.awaiting" class="portal-row-cta">Bekijk en onderteken →</div>
            </div>
            <span class="pill" :class="quotePill(q)">{{ q.status_label }}</span>
            <svg class="portal-row-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </div>
        </button>
      </div>
    </div>

    <div v-for="group in groups" :key="group.company" class="portal-group" style="order:1;">
      <div v-if="!multiCompany && quotes.length" class="portal-group-title">Facturen</div>
      <div v-if="multiCompany" class="portal-group-title">Facturen · {{ group.company }}</div>
      <div class="portal-list">
        <button
          v-for="inv in group.invoices"
          :key="inv.token"
          type="button"
          class="portal-row"
          @click="open(inv)"
        >
          <div class="portal-row-main">
            <div class="portal-row-number">
              {{ inv.is_credit ? 'Creditnota' : 'Factuur' }} {{ inv.number }}
              <span v-if="!multiCompany && inv.company_name" class="portal-row-company">· {{ inv.company_name }}</span>
            </div>
            <div class="portal-row-meta">
              {{ inv.invoice_date_label }}
              <template v-if="inv.due_date_label && ['sent','partial','overdue','incasso'].includes(inv.status)">
                · te betalen vóór {{ inv.due_date_label }}
              </template>
            </div>
          </div>
          <div class="portal-row-side">
            <div class="portal-row-amount">
              <div class="portal-row-total">{{ eur(inv.total) }}</div>
              <div v-if="inv.paid_total > 0 && inv.remaining > 0" class="portal-row-remaining">nog {{ eur(inv.remaining) }} open</div>
            </div>
            <StatusPill :status="inv.status" :days-overdue="inv.days_overdue" />
            <svg class="portal-row-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </div>
        </button>
      </div>
    </div>
    </div>
  </PortalLayout>
</template>

<style scoped>
.portal-page-head { margin-bottom: 20px; }

.portal-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 24px;
}
.portal-stat {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 16px 18px;
}
.portal-stat-label { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; margin-bottom: 6px; }
.portal-stat-value { font-family: var(--font-display); font-weight: 700; font-size: 22px; letter-spacing: -0.02em; }
.portal-stat-alert .portal-stat-value { color: var(--brand-dark); }
.portal-stat-attention { border-color: var(--warning-border); background: var(--warning-bg); }
.portal-stat-attention .portal-stat-value { color: var(--warning); }
.portal-row-attention { border-color: var(--warning-border); }
.portal-row-cta { font-size: 11.5px; color: var(--warning); font-weight: 600; margin-top: 2px; white-space: nowrap; }

.portal-sections { display: flex; flex-direction: column; }
.portal-group { margin-bottom: 24px; }
.portal-group-title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 15px;
  margin-bottom: 10px;
}
.portal-list { display: flex; flex-direction: column; gap: 10px; }
.portal-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  width: 100%;
  text-align: left;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 16px 18px;
  transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
  cursor: pointer;
}
.portal-row:hover {
  border-color: var(--border-strong);
  box-shadow: var(--shadow);
  transform: translateY(-1px);
}
.portal-row-main { min-width: 0; }
.portal-row-number { font-weight: 600; font-size: 14.5px; }
.portal-row-company { color: var(--text-3); font-weight: 400; font-size: 13px; }
.portal-row-meta { font-size: 12.5px; color: var(--text-3); margin-top: 3px; }
.portal-row-side { display: flex; align-items: center; gap: 14px; flex: none; }
.portal-row-amount { text-align: right; }
.portal-row-total { font-family: var(--font-mono); font-weight: 600; font-size: 14.5px; }
.portal-row-remaining { font-size: 11.5px; color: var(--warning); margin-top: 2px; }
.portal-row-chevron { width: 16px; height: 16px; color: var(--text-4); }

.portal-empty {
  text-align: center;
  padding: 48px 24px;
  color: var(--text-3);
}
.portal-empty svg { width: 36px; height: 36px; color: var(--text-4); margin-bottom: 12px; }
.portal-empty-title { font-family: var(--font-display); font-weight: 600; font-size: 17px; color: var(--text); margin-bottom: 6px; }
.portal-empty p { font-size: 13.5px; }

@media (max-width: 640px) {
  .portal-stats { grid-template-columns: minmax(0, 1fr); }
  .portal-row { flex-direction: column; align-items: stretch; gap: 10px; }
  .portal-row-side { justify-content: space-between; }
  .portal-row-chevron { display: none; }
}
</style>
