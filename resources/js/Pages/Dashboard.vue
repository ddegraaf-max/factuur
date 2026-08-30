<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import ResultChart from '@/Components/ResultChart.vue';
import { eur } from '@/format.js';
import { t } from '@/i18n';

const props = defineProps({
  kpis: Object,
  recent_invoices: Array,
  result_chart: Object,
  vat_due: { type: Object, default: null },
  quotes: { type: Object, default: null },
});

// Offertestatus in dezelfde pil-kleuren als op de offertepagina.
const quotePill = (q) => {
  if (q.status === 'sent' && q.is_expired) return 'pill-partial';
  return { draft: 'pill-draft', sent: 'pill-sent', accepted: 'pill-paid', rejected: 'pill-overdue', expired: 'pill-partial' }[q.status] ?? 'pill-draft';
};
const quoteLabel = (q) => {
  if (q.status === 'sent' && q.is_expired) return t('Verlopen');
  if (q.status === 'sent' && q.days_left !== null && q.days_left <= 7) return q.days_left === 1 ? t('Nog 1 dag') : t('Nog :n dagen', { n: q.days_left });
  if (q.to_invoice) return t('Te factureren');
  return q.status_label;
};

const greeting = () => {
  const h = new Date().getHours();
  if (h < 6) return t('Goedenacht');
  if (h < 12) return t('Goedemorgen');
  if (h < 18) return t('Goedemiddag');
  return t('Goedenavond');
};
</script>

<template>
  <Head :title="$t('Dashboard')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">{{ $t('Overzicht') }} / <span class="breadcrumb-current">{{ $t('Dashboard') }}</span></div>
    </template>
    <template #topbar-actions>
      <Link :href="route('invoices.create')" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Nieuwe factuur') }}
      </Link>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ greeting() }}, {{ $page.props.auth.user.name.split(' ')[0] }}</h1>
        <p class="page-subtitle" v-if="kpis.overdue_count > 0">
          {{ kpis.overdue_count === 1 ? $t('Je hebt 1 factuur die aandacht nodig heeft.') : $t('Je hebt :n facturen die aandacht nodig hebben.', { n: kpis.overdue_count }) }}
        </p>
        <p class="page-subtitle" v-else>{{ $t('Alles is up-to-date.') }}</p>
      </div>
    </div>

    <!-- Btw-aangifte die open staat -->
    <Link v-if="vat_due" :href="route('vat.index', { year: vat_due.year })" class="vat-banner">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <div class="vat-banner-text">
        <strong>{{ $t('Btw-aangifte :period staat open', { period: vat_due.label + ' ' + vat_due.year }) }}</strong>
        <span>
          <template v-if="vat_due.balance_rounded > 0">{{ $t('Per saldo :amount te betalen', { amount: eur(vat_due.balance_rounded) }) }}</template>
          <template v-else-if="vat_due.balance_rounded < 0">{{ $t('Je krijgt :amount terug', { amount: eur(-vat_due.balance_rounded) }) }}</template>
          <template v-else>{{ $t('Nihilaangifte') }}</template>
          · {{ $t('vóór :date', { date: vat_due.deadline_label }) }}{{ vat_due.days_left !== null ? ' (' + (vat_due.days_left === 1 ? $t('nog 1 dag') : $t('nog :n dagen', { n: vat_due.days_left })) + ')' : '' }}
        </span>
      </div>
      <span class="vat-banner-cta">{{ $t('Aangifte voorbereiden →') }}</span>
    </Link>

    <!-- KPI CARDS -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          {{ $t('Openstaand') }}
        </div>
        <div class="kpi-value">{{ eur(kpis.outstanding) }}</div>
        <div class="kpi-meta">{{ kpis.outstanding_count === 1 ? $t('1 factuur') : $t(':n facturen', { n: kpis.outstanding_count }) }}</div>
      </div>

      <div :class="['kpi-card', { alert: kpis.overdue > 0 }]">
        <div class="kpi-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :color="kpis.overdue > 0 ? 'var(--brand)' : 'currentColor'"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          {{ $t('Achterstallig') }}
        </div>
        <div class="kpi-value">{{ eur(kpis.overdue) }}</div>
        <div class="kpi-meta">
          <template v-if="kpis.overdue_count > 0">
            <span class="change-down">{{ kpis.overdue_count === 1 ? $t('1 factuur') : $t(':n facturen', { n: kpis.overdue_count }) }}</span>
          </template>
          <template v-else>{{ $t('Geen') }}</template>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
          {{ $t('Omzet deze maand') }}
        </div>
        <div class="kpi-value">{{ eur(kpis.month_revenue) }}</div>
        <div class="kpi-meta" v-if="kpis.month_change !== 0">
          <span :class="kpis.month_change >= 0 ? 'change-up' : 'change-down'">
            {{ kpis.month_change >= 0 ? '↑' : '↓' }} {{ Math.abs(kpis.month_change) }}%
          </span>
          {{ $t('vs vorige maand') }}
        </div>
        <div class="kpi-meta" v-else>—</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {{ $t('Btw') }} {{ kpis.vat_period_label || ('Q' + kpis.quarter_number) }}
        </div>
        <div class="kpi-value">{{ eur(kpis.vat_to_pay) }}</div>
        <div class="kpi-meta">{{ kpis.vat_to_pay < 0 ? $t('Terug te ontvangen') : $t('Per saldo') }} · {{ $t('aangifte vóór :date', { date: kpis.quarter_deadline }) }}</div>
      </div>
    </div>

    <div class="row-2">
      <!-- Recent invoices -->
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">{{ $t('Recente facturen') }}</div>
            <div class="card-subtitle">{{ $t('Laatste 7 facturen') }}</div>
          </div>
          <Link :href="route('invoices.index')" class="card-link">{{ $t('Alle →') }}</Link>
        </div>
        <div class="card-body-flush" v-if="recent_invoices.length > 0">
          <table class="data-table">
            <thead>
              <tr>
                <th>{{ $t('Nummer') }}</th>
                <th>{{ $t('Klant') }}</th>
                <th>{{ $t('Datum') }}</th>
                <th>{{ $t('Status') }}</th>
                <th class="right">{{ $t('Bedrag') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in recent_invoices" :key="inv.id" @click="router.visit(route('invoices.show', inv.id))">
                <td class="num cell-primary">{{ inv.number || '—' }}</td>
                <td :data-label="$t('Klant')">{{ inv.customer_name }}</td>
                <td :data-label="$t('Datum')">{{ inv.invoice_date }}</td>
                <td :data-label="$t('Status')"><StatusPill :status="inv.status" /></td>
                <td class="num right" :data-label="$t('Bedrag')">{{ eur(inv.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="card-empty" v-else>
          {{ $t('Nog geen facturen.') }} <Link :href="route('invoices.create')" style="color:var(--brand);font-weight:500;">{{ $t('Maak je eerste factuur →') }}</Link>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ $t('Snel aan de slag') }}</div>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
          <Link :href="route('invoices.create')" class="quick-action">
            <div class="qa-icon" style="background:var(--brand-tint-2);color:var(--brand);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div>
              <div class="qa-title">{{ $t('Nieuwe factuur') }}</div>
              <div class="qa-sub">{{ $t('Stuur direct of bewaar als concept') }}</div>
            </div>
          </Link>
          <Link :href="route('quotes.create')" class="quick-action">
            <div class="qa-icon" style="background:var(--warning-bg);color:var(--warning);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
            </div>
            <div>
              <div class="qa-title">{{ $t('Nieuwe offerte') }}</div>
              <div class="qa-sub">{{ $t('Laat online ondertekenen, zet om naar factuur') }}</div>
            </div>
          </Link>
          <Link :href="route('customers.create')" class="quick-action">
            <div class="qa-icon" style="background:var(--info-bg);color:var(--info);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            </div>
            <div>
              <div class="qa-title">{{ $t('Klant toevoegen') }}</div>
              <div class="qa-sub">{{ $t('Beheer je relaties') }}</div>
            </div>
          </Link>
          <Link :href="route('products.create')" class="quick-action">
            <div class="qa-icon" style="background:var(--success-bg);color:var(--success);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div>
              <div class="qa-title">{{ $t('Product toevoegen') }}</div>
              <div class="qa-sub">{{ $t('Sjabloon voor factuurregels') }}</div>
            </div>
          </Link>
        </div>
      </div>
    </div>

    <!-- Offertes: recente offertes + wat er open staat, te factureren is en hoe vaak klanten ja zeggen -->
    <div v-if="quotes" class="row-2" style="margin-top:20px;">
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">{{ $t('Offertes') }}</div>
            <div class="card-subtitle">{{ $t('Laatste 6 offertes') }}</div>
          </div>
          <Link :href="route('quotes.index')" class="card-link">{{ $t('Alle →') }}</Link>
        </div>
        <div class="card-body-flush" v-if="quotes.recent.length > 0">
          <table class="data-table">
            <thead>
              <tr><th>{{ $t('Nummer') }}</th><th>{{ $t('Klant') }}</th><th>{{ $t('Datum') }}</th><th>{{ $t('Status') }}</th><th class="right">{{ $t('Bedrag') }}</th></tr>
            </thead>
            <tbody>
              <tr v-for="q in quotes.recent" :key="q.id" @click="router.visit(route('quotes.show', q.id))">
                <td class="num cell-primary">{{ q.number }}</td>
                <td :data-label="$t('Klant')">{{ q.customer_name }}</td>
                <td :data-label="$t('Datum')">{{ q.quote_date }}</td>
                <td :data-label="$t('Status')"><span class="pill" :class="quotePill(q)">{{ quoteLabel(q) }}</span></td>
                <td class="num right" :data-label="$t('Bedrag')">{{ eur(q.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="card-empty" v-else>
          {{ $t('Nog geen offertes.') }} <Link :href="route('quotes.create')" style="color:var(--brand);font-weight:500;">{{ $t('Maak je eerste offerte →') }}</Link>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title">{{ $t('Offertes in cijfers') }}</div></div>
        <div class="card-body q-stats">
          <Link :href="route('quotes.index', { status: 'sent' })" class="q-stat">
            <div class="q-stat-val">{{ eur(quotes.open_total) }}</div>
            <div class="q-stat-lbl">{{ $t(':n open · wacht op reactie van de klant', { n: quotes.open_count }) }}</div>
            <div v-if="quotes.expired_count || quotes.expiring_count" class="q-stat-meta warn">
              <template v-if="quotes.expired_count">{{ $t(':n verlopen', { n: quotes.expired_count }) }}</template>
              <template v-if="quotes.expired_count && quotes.expiring_count"> · </template>
              <template v-if="quotes.expiring_count">{{ quotes.expiring_count === 1 ? $t('1 verloopt binnen 7 dagen') : $t(':n verlopen binnen 7 dagen', { n: quotes.expiring_count }) }}</template>
            </div>
          </Link>
          <Link :href="route('quotes.index', { status: 'accepted' })" class="q-stat" :class="{ hot: quotes.to_invoice_count > 0 }">
            <div class="q-stat-val">{{ eur(quotes.to_invoice_total) }}</div>
            <div class="q-stat-lbl">{{ $t(':n geaccepteerd · nog te factureren', { n: quotes.to_invoice_count }) }}</div>
            <div v-if="quotes.to_invoice_count > 0" class="q-stat-meta">{{ $t('Zet om naar een factuur via de offertepagina') }}</div>
          </Link>
          <div class="q-stat">
            <div class="q-stat-val">{{ quotes.acceptance_rate === null ? '—' : quotes.acceptance_rate + '%' }}</div>
            <div class="q-stat-lbl">{{ $t('acceptatiegraad :year', { year: new Date().getFullYear() }) }}</div>
            <div class="q-stat-meta">
              <template v-if="quotes.decided_year_count">{{ $t(':accepted van :decided beslist · :won gewonnen', { accepted: quotes.accepted_year_count, decided: quotes.decided_year_count, won: eur(quotes.accepted_year_total) }) }}</template>
              <template v-else>{{ $t('Nog geen offerte geaccepteerd of afgewezen dit jaar') }}</template>
              <template v-if="quotes.draft_count"> · {{ $t(':n concept', { n: quotes.draft_count }) }}</template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resultaat per maand: omzet, inkoop & winst vs vorig jaar -->
    <ResultChart :chart="result_chart" />
  </AppLayout>
</template>

<style>
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
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
.vat-banner {
  display: flex; align-items: center; gap: 14px;
  background: var(--warning-bg); border: 1px solid var(--warning-border); color: var(--warning);
  border-radius: 12px; padding: 14px 18px; margin-bottom: 18px; text-decoration: none;
  transition: box-shadow .15s;
}
.vat-banner:hover { box-shadow: 0 0 0 3px var(--warning-bg); }
.vat-banner > svg { width: 22px; height: 22px; flex: none; }
.vat-banner-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; font-size: 13.5px; line-height: 1.5; }
.vat-banner-text strong { font-weight: 700; }
.vat-banner-cta { font-size: 13px; font-weight: 600; white-space: nowrap; }
@media (max-width: 640px) { .vat-banner { flex-wrap: wrap; } .vat-banner-cta { width: 100%; padding-left: 36px; } }
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

.row-2 { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 20px; }
@media (max-width: 1100px) {
  .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .row-2 { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 760px) {
  .kpi-grid { gap: 10px; margin-bottom: 20px; }
  .kpi-card { padding: 14px; }
  .kpi-label { font-size: 12px; margin-bottom: 6px; }
  .kpi-value { font-size: 22px; }
}

.q-stats { display: flex; flex-direction: column; gap: 8px; }
.q-stat { display: block; padding: 12px 14px; border: 1px solid var(--border); border-radius: var(--r-sm); text-decoration: none; color: inherit; transition: background .15s, border-color .15s; }
a.q-stat:hover { background: var(--surface-2); border-color: var(--border-strong); }
.q-stat.hot { border-color: var(--success-border); background: var(--success-bg); }
.q-stat-val { font-family: var(--font-display); font-weight: 600; font-size: 20px; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.q-stat-lbl { font-size: 12.5px; color: var(--text-2); margin-top: 2px; }
.q-stat-meta { font-size: 11.5px; color: var(--text-4); margin-top: 3px; line-height: 1.5; }
.q-stat-meta.warn { color: var(--warning); font-weight: 600; }

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

</style>
