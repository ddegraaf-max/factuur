<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { eur } from '@/format.js';

const props = defineProps({
  email: String,
  invoices: Array,
  stats: Object,
});

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

const multiCompany = computed(() => groups.value.length > 1);

const open = (inv) => router.get(route('portal.invoice', inv.token));
</script>

<template>
  <Head title="Jouw facturen · Facturenportaal" />
  <PortalLayout :email="email">
    <div class="portal-page-head">
      <h1 class="portal-card-title" style="margin-bottom:4px;">Jouw facturen</h1>
      <p class="portal-card-sub" style="margin-bottom:0;">Alle facturen die naar {{ email }} zijn verstuurd.</p>
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
    </div>

    <div v-if="invoices.length === 0" class="portal-card portal-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <div class="portal-empty-title">Geen facturen gevonden</div>
      <p>Er zijn (nog) geen facturen verstuurd naar dit e-mailadres.</p>
    </div>

    <div v-for="group in groups" :key="group.company" class="portal-group">
      <div v-if="multiCompany" class="portal-group-title">{{ group.company }}</div>
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
  </PortalLayout>
</template>

<style scoped>
.portal-page-head { margin-bottom: 20px; }

.portal-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
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
