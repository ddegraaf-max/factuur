<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { eur } from '@/format.js';
import { computed } from 'vue';

const props = defineProps({
  kpis: Object,
  recent_invoices: Array,
  monthly_revenue: Array,
});

const maxRevenue = computed(() => {
  const max = Math.max(...props.monthly_revenue.map(m => m.value), 1);
  return max;
});

const barHeight = (value) => {
  const pct = (value / maxRevenue.value) * 100;
  return Math.max(pct, 2) + '%';
};

const greeting = () => {
  const h = new Date().getHours();
  if (h < 6) return 'Goedenacht';
  if (h < 12) return 'Goedemorgen';
  if (h < 18) return 'Goedemiddag';
  return 'Goedenavond';
};
</script>

<template>
  <Head title="Dashboard" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Overzicht / <span class="breadcrumb-current">Dashboard</span></div>
    </template>
    <template #topbar-actions>
      <Link :href="route('invoices.create')" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nieuwe factuur
      </Link>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ greeting() }}, {{ $page.props.auth.user.name.split(' ')[0] }}</h1>
        <p class="page-subtitle" v-if="kpis.overdue_count > 0">
          Je hebt {{ kpis.overdue_count }} {{ kpis.overdue_count === 1 ? 'factuur' : 'facturen' }} die aandacht nodig {{ kpis.overdue_count === 1 ? 'heeft' : 'hebben' }}.
        </p>
        <p class="page-subtitle" v-else>Alles is up-to-date.</p>
      </div>
    </div>

    <!-- KPI CARDS -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Openstaand
        </div>
        <div class="kpi-value">{{ eur(kpis.outstanding) }}</div>
        <div class="kpi-meta">{{ kpis.outstanding_count }} {{ kpis.outstanding_count === 1 ? 'factuur' : 'facturen' }}</div>
      </div>

      <div :class="['kpi-card', { alert: kpis.overdue > 0 }]">
        <div class="kpi-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :color="kpis.overdue > 0 ? 'var(--brand)' : 'currentColor'"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Achterstallig
        </div>
        <div class="kpi-value">{{ eur(kpis.overdue) }}</div>
        <div class="kpi-meta">
          <template v-if="kpis.overdue_count > 0">
            <span class="change-down">{{ kpis.overdue_count }} {{ kpis.overdue_count === 1 ? 'factuur' : 'facturen' }}</span>
          </template>
          <template v-else>Geen</template>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
          Omzet deze maand
        </div>
        <div class="kpi-value">{{ eur(kpis.month_revenue) }}</div>
        <div class="kpi-meta" v-if="kpis.month_change !== 0">
          <span :class="kpis.month_change >= 0 ? 'change-up' : 'change-down'">
            {{ kpis.month_change >= 0 ? '↑' : '↓' }} {{ Math.abs(kpis.month_change) }}%
          </span>
          vs vorige maand
        </div>
        <div class="kpi-meta" v-else>—</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          BTW Q{{ kpis.quarter_number }}
        </div>
        <div class="kpi-value">{{ eur(kpis.vat_to_pay) }}</div>
        <div class="kpi-meta">Deadline {{ kpis.quarter_deadline }}</div>
      </div>
    </div>

    <div class="row-2">
      <!-- Recent invoices -->
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">Recente facturen</div>
            <div class="card-subtitle">Laatste 7 facturen</div>
          </div>
          <Link :href="route('invoices.index')" class="card-link">Alle →</Link>
        </div>
        <div class="card-body-flush" v-if="recent_invoices.length > 0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nummer</th>
                <th>Klant</th>
                <th>Datum</th>
                <th>Status</th>
                <th class="right">Bedrag</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in recent_invoices" :key="inv.id" @click="router.visit(route('invoices.show', inv.id))">
                <td class="num">{{ inv.number || '—' }}</td>
                <td>{{ inv.customer_name }}</td>
                <td>{{ inv.invoice_date }}</td>
                <td><StatusPill :status="inv.status" /></td>
                <td class="num right">{{ eur(inv.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="card-empty" v-else>
          Nog geen facturen. <Link :href="route('invoices.create')" style="color:var(--brand);font-weight:500;">Maak je eerste factuur →</Link>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Snel aan de slag</div>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
          <Link :href="route('invoices.create')" class="quick-action">
            <div class="qa-icon" style="background:var(--brand-tint-2);color:var(--brand);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div>
              <div class="qa-title">Nieuwe factuur</div>
              <div class="qa-sub">Stuur direct of bewaar als concept</div>
            </div>
          </Link>
          <Link :href="route('customers.create')" class="quick-action">
            <div class="qa-icon" style="background:var(--info-bg);color:var(--info);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            </div>
            <div>
              <div class="qa-title">Klant toevoegen</div>
              <div class="qa-sub">Beheer je relaties</div>
            </div>
          </Link>
          <Link :href="route('products.create')" class="quick-action">
            <div class="qa-icon" style="background:var(--success-bg);color:var(--success);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div>
              <div class="qa-title">Product toevoegen</div>
              <div class="qa-sub">Sjabloon voor factuurregels</div>
            </div>
          </Link>
        </div>
      </div>
    </div>

    <!-- Revenue chart -->
    <div class="card" style="margin-top:20px;">
      <div class="card-header">
        <div>
          <div class="card-title">Omzet per maand</div>
          <div class="card-subtitle">Exclusief BTW · laatste 12 maanden</div>
        </div>
      </div>
      <div class="chart-wrap">
        <div class="bars">
          <div v-for="(m, i) in monthly_revenue" :key="i" class="bar-col">
            <div class="bar-value">{{ m.value > 0 ? eur(m.value) : '' }}</div>
            <div class="bar" :style="{ height: barHeight(m.value) }"></div>
            <div class="bar-label">{{ m.month }}</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}
.kpi-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: 20px;
  transition: all 0.2s;
}
.kpi-card:hover { border-color: var(--border-strong); box-shadow: var(--shadow-sm); }
.kpi-card.alert {
  border-color: var(--brand-border);
  background: linear-gradient(180deg, var(--brand-tint) 0%, var(--surface) 60%);
}
.kpi-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; color: var(--text-3);
  margin-bottom: 10px; font-weight: 500;
}
.kpi-label svg { width: 14px; height: 14px; }
.kpi-value {
  font-family: var(--font-display);
  font-weight: 600; font-size: 28px;
  letter-spacing: -0.02em; line-height: 1.1;
  margin-bottom: 6px;
  font-variant-numeric: tabular-nums;
}
.kpi-meta { font-size: 12px; color: var(--text-3); }
.kpi-meta .change-up { color: var(--success); font-weight: 600; }
.kpi-meta .change-down { color: var(--brand); font-weight: 600; }

.row-2 { display: grid; grid-template-columns: 1fr 380px; gap: 20px; }
@media (max-width: 1100px) {
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .row-2 { grid-template-columns: 1fr; }
}

.quick-action {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: background 0.15s;
}
.quick-action:hover { background: var(--surface-2); }
.qa-icon {
  width: 36px; height: 36px;
  border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.qa-title { font-weight: 500; font-size: 14px; }
.qa-sub { font-size: 12px; color: var(--text-3); margin-top: 1px; }

.chart-wrap { padding: 24px 24px 20px; }
.bars {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  height: 200px;
  border-bottom: 1px solid var(--border);
  padding-bottom: 4px;
  position: relative;
}
.bar-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
  justify-content: flex-end;
  position: relative;
}
.bar {
  width: 60%;
  background: var(--brand);
  border-radius: 3px 3px 0 0;
  transition: opacity 0.2s;
  min-height: 2px;
}
.bar-col:hover .bar { opacity: 0.8; }
.bar-value {
  position: absolute;
  bottom: 100%;
  font-size: 10px;
  font-family: var(--font-mono);
  color: var(--text-3);
  white-space: nowrap;
  opacity: 0;
  transition: opacity 0.15s;
  margin-bottom: 4px;
}
.bar-col:hover .bar-value { opacity: 1; }
.bar-label {
  position: absolute;
  top: calc(100% + 6px);
  font-size: 11px;
  color: var(--text-3);
  white-space: nowrap;
}
</style>
