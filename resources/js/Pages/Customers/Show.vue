<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { eur } from '@/format.js';
import { t } from '@/i18n';

const props = defineProps({
  customer: Object,
  year: Number,
  stats: Object,
  invoices: Array,
  invoices_total: Number,
  quotes: Array,
  quotes_total: Number,
  hours_url: { type: String, default: null },
});

// Markt (nl/pl): korte labels voor KvK/REGON en btw-nummer/NIP.
const market = computed(() => usePage().props.market || {});

const amount = (n) => (n < 0 ? '− ' : '') + eur(Math.abs(n || 0));

// Offertestatus in dezelfde pil-kleuren als op de offertepagina.
const quotePill = (q) => {
  if (q.status === 'sent' && q.is_expired) return 'pill-partial';
  return { draft: 'pill-draft', sent: 'pill-sent', accepted: 'pill-paid', rejected: 'pill-overdue', expired: 'pill-partial' }[q.status] ?? 'pill-draft';
};
const quoteLabel = (q) => (q.status === 'sent' && q.is_expired ? t('Verlopen') : q.status_label);

const hours = (minutes) => {
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return m ? t(':hu :min', { h, min: String(m).padStart(2, '0') }) : t(':hu', { h });
};

const languageLabel = computed(() => ({ nl: t('Nederlands'), en: t('Engels') }[props.customer.language] || t('Nederlands')));

const payBehaviour = computed(() => {
  const s = props.stats;
  if (s.avg_days_to_pay === null || s.paid_count === 0) return null;
  const pct = Math.round((s.on_time_count / s.paid_count) * 100);
  return { days: s.avg_days_to_pay, pct, good: pct >= 80 };
});

const acceptance = computed(() => {
  const s = props.stats;
  return s.quotes_decided_count > 0 ? Math.round((s.quotes_accepted_count / s.quotes_decided_count) * 100) : null;
});

const openInvoice = (inv) => router.visit(route('invoices.show', inv.id));
const openQuote = (q) => router.visit(route('quotes.show', q.id));
</script>

<template>
  <Head :title="customer.name" />
  <AppLayout>
    <template #breadcrumb><Link :href="route('customers.index')">{{ $t('Klanten') }}</Link> / <span class="breadcrumb-current">{{ customer.name }}</span></template>
    <template #topbar-actions>
      <Link :href="route('customers.edit', customer.id)" class="btn btn-secondary btn-sm">{{ $t('Bewerken') }}</Link>
      <Link :href="route('quotes.create', { customer_id: customer.id })" class="btn btn-secondary btn-sm">{{ $t('Nieuwe offerte') }}</Link>
      <Link :href="route('invoices.create', { customer_id: customer.id })" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $t('Nieuwe factuur') }}
      </Link>
    </template>

    <!-- Kop: wie is dit -->
    <div class="cust-head">
      <div class="cust-avatar">{{ customer.initials }}</div>
      <div class="cust-title">
        <h1 class="page-title" style="margin:0;">{{ customer.name }}</h1>
        <div class="cust-meta">
          <span class="pill" :class="customer.type === 'consumer' ? 'pill-sent' : 'pill-draft'">{{ customer.type === 'consumer' ? $t('Particulier') : $t('Zakelijk') }}</span>
          <span v-if="customer.contact_name">{{ customer.contact_name }}</span>
          <span v-if="customer.city">{{ customer.city }}</span>
          <span v-if="customer.created_at_label" class="muted">{{ $t('klant sinds :date', { date: customer.created_at_label }) }}</span>
        </div>
      </div>
    </div>

    <!-- Kerncijfers -->
    <div class="kpi-grid">
      <div class="kpi" :class="{ alert: stats.overdue_count > 0 }">
        <div class="lbl">{{ $t('Openstaand') }}</div>
        <div class="val" :class="{ brand: stats.open_total > 0 }">{{ eur(stats.open_total) }}</div>
        <div class="meta">
          <template v-if="stats.open_count === 0">{{ $t('Niets open') }}</template>
          <template v-else>{{ stats.open_count === 1 ? $t(':n factuur', { n: stats.open_count }) : $t(':n facturen', { n: stats.open_count }) }}<span v-if="stats.overdue_count" class="warn"> · {{ $t(':n achterstallig', { n: stats.overdue_count }) }}</span></template>
        </div>
      </div>
      <div class="kpi">
        <div class="lbl">{{ $t('Omzet :year', { year }) }} <span class="muted">{{ $t('excl. btw') }}</span></div>
        <div class="val">{{ amount(stats.revenue_year) }}</div>
        <div class="meta">{{ $t(':amount in totaal', { amount: amount(stats.revenue_total) }) }}<template v-if="stats.first_invoice_label"> · {{ $t('sinds :date', { date: stats.first_invoice_label }) }}</template></div>
      </div>
      <div class="kpi">
        <div class="lbl">{{ $t('Betaalgedrag') }}</div>
        <template v-if="payBehaviour">
          <div class="val" :class="payBehaviour.good ? 'good' : 'warn'">{{ $t(':n dagen', { n: payBehaviour.days }) }}</div>
          <div class="meta">{{ $t('gemiddeld na factuurdatum · :pct% op tijd (:ontime van :paid)', { pct: payBehaviour.pct, ontime: stats.on_time_count, paid: stats.paid_count }) }}</div>
        </template>
        <template v-else>
          <div class="val muted">—</div>
          <div class="meta">{{ $t('Nog geen betaalde factuur') }}</div>
        </template>
      </div>
      <div class="kpi">
        <div class="lbl">{{ $t('Offertes') }}</div>
        <div class="val">{{ acceptance === null ? (stats.quotes_open_count ? stats.quotes_open_count : '—') : acceptance + '%' }}</div>
        <div class="meta">
          <template v-if="acceptance !== null">{{ $t('geaccepteerd (:accepted van :decided)', { accepted: stats.quotes_accepted_count, decided: stats.quotes_decided_count }) }}</template>
          <template v-else-if="stats.quotes_open_count">{{ stats.quotes_open_count === 1 ? $t('offerte wacht op reactie') : $t('offertes wachten op reactie') }}</template>
          <template v-else>{{ $t('Nog geen offertes') }}</template>
          <span v-if="acceptance !== null && stats.quotes_open_count" class="warn"> · {{ $t(':n open (:amount)', { n: stats.quotes_open_count, amount: eur(stats.quotes_open_total) }) }}</span>
        </div>
      </div>
    </div>

    <!-- Nog te factureren uren -->
    <component :is="hours_url ? Link : 'div'" v-if="stats.unbilled_minutes > 0" :href="hours_url || undefined" class="hours-banner">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <div><strong>{{ $t(':hours nog niet gefactureerd', { hours: hours(stats.unbilled_minutes) }) }}</strong><span v-if="stats.unbilled_value > 0"> · {{ $t('ongeveer :amount excl. btw', { amount: eur(stats.unbilled_value) }) }}</span></div>
      <span v-if="hours_url" class="hours-cta">{{ $t('Naar uren') }} →</span>
    </component>

    <div class="cust-grid">
      <div class="cust-main">
        <!-- Facturen -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Facturen') }}</div>
              <div class="card-subtitle">{{ invoices_total === 1 ? $t(':n factuur', { n: invoices_total }) : $t(':n facturen', { n: invoices_total }) }}<template v-if="stats.credit_count"> · {{ $t(":n creditnota's", { n: stats.credit_count }) }}</template></div>
            </div>
            <Link :href="route('invoices.index', { q: customer.name })" class="card-link">{{ $t('Alle') }} →</Link>
          </div>
          <div v-if="invoices.length" class="card-body-flush">
            <table class="data-table">
              <thead>
                <tr><th>{{ $t('Nummer') }}</th><th>{{ $t('Datum') }}</th><th>{{ $t('Vervaldatum') }}</th><th>{{ $t('Status') }}</th><th class="right">{{ $t('Bedrag') }}</th><th class="right">{{ $t('Open') }}</th></tr>
              </thead>
              <tbody>
                <tr v-for="inv in invoices" :key="inv.id" @click="openInvoice(inv)">
                  <td class="cell-primary num">{{ inv.number }}<span v-if="inv.is_credit" class="tag">{{ $t('credit') }}</span></td>
                  <td :data-label="$t('Datum')">{{ inv.invoice_date_label }}</td>
                  <td :data-label="$t('Vervaldatum')">{{ inv.due_date_label || '—' }}</td>
                  <td :data-label="$t('Status')"><StatusPill :status="inv.status" :days-overdue="inv.days_overdue" /></td>
                  <td class="right num" :data-label="$t('Bedrag')">{{ eur(inv.total) }}</td>
                  <td class="right num" :data-label="$t('Open')" :class="{ 'is-open': inv.remaining > 0.009 && ['sent','partial','overdue','incasso'].includes(inv.status) }">
                    {{ inv.remaining > 0.009 && ['sent','partial','overdue','incasso'].includes(inv.status) ? eur(inv.remaining) : '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="invoices_total > invoices.length" class="more-note">{{ $t('De laatste :shown van :total', { shown: invoices.length, total: invoices_total }) }} — <Link :href="route('invoices.index', { q: customer.name })">{{ $t('bekijk alles') }}</Link></div>
          </div>
          <div v-else class="card-empty">
            {{ $t('Nog geen facturen voor deze klant.') }} <Link :href="route('invoices.create', { customer_id: customer.id })" class="lnk">{{ $t('Maak de eerste factuur') }} →</Link>
          </div>
        </div>

        <!-- Offertes -->
        <div class="card" style="margin-top:20px;">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Offertes') }}</div>
              <div class="card-subtitle">{{ quotes_total === 1 ? $t(':n offerte', { n: quotes_total }) : $t(':n offertes', { n: quotes_total }) }}<template v-if="stats.quotes_open_count"> · {{ $t(':n wacht op reactie', { n: stats.quotes_open_count }) }}</template></div>
            </div>
            <Link :href="route('quotes.index', { q: customer.name })" class="card-link">{{ $t('Alle') }} →</Link>
          </div>
          <div v-if="quotes.length" class="card-body-flush">
            <table class="data-table">
              <thead>
                <tr><th>{{ $t('Nummer') }}</th><th>{{ $t('Datum') }}</th><th>{{ $t('Geldig tot') }}</th><th>{{ $t('Status') }}</th><th class="right">{{ $t('Bedrag') }}</th></tr>
              </thead>
              <tbody>
                <tr v-for="q in quotes" :key="q.id" @click="openQuote(q)">
                  <td class="cell-primary num">{{ q.number }}</td>
                  <td :data-label="$t('Datum')">{{ q.quote_date_label }}</td>
                  <td :data-label="$t('Geldig tot')">{{ q.valid_until_label || '—' }}<span v-if="q.days_left" class="muted-sm"> · {{ $t('nog :n d.', { n: q.days_left }) }}</span></td>
                  <td :data-label="$t('Status')">
                    <span class="pill" :class="quotePill(q)">{{ quoteLabel(q) }}</span>
                    <span v-if="q.converted" class="pill pill-sent" style="margin-left:4px;">{{ $t('Gefactureerd') }}</span>
                  </td>
                  <td class="right num" :data-label="$t('Bedrag')">{{ eur(q.total) }}</td>
                </tr>
              </tbody>
            </table>
            <div v-if="quotes_total > quotes.length" class="more-note">{{ $t('De laatste :shown van :total', { shown: quotes.length, total: quotes_total }) }} — <Link :href="route('quotes.index', { q: customer.name })">{{ $t('bekijk alles') }}</Link></div>
          </div>
          <div v-else class="card-empty">
            {{ $t('Nog geen offertes voor deze klant.') }} <Link :href="route('quotes.create', { customer_id: customer.id })" class="lnk">{{ $t('Maak een offerte') }} →</Link>
          </div>
        </div>
      </div>

      <!-- Zijkolom: gegevens en notities -->
      <div class="cust-side">
        <div class="card">
          <div class="card-header">
            <div class="card-title">{{ $t('Gegevens') }}</div>
            <Link :href="route('customers.edit', customer.id)" class="card-link">{{ $t('Bewerken') }}</Link>
          </div>
          <div class="card-body">
            <dl class="dl">
              <template v-if="customer.email"><dt>{{ $t('E-mail') }}</dt><dd><a :href="`mailto:${customer.email}`" class="lnk">{{ customer.email }}</a></dd></template>
              <template v-if="customer.phone"><dt>{{ $t('Telefoon') }}</dt><dd><a :href="`tel:${customer.phone}`" class="lnk">{{ customer.phone }}</a></dd></template>
              <template v-if="customer.address"><dt>{{ $t('Adres') }}</dt><dd>{{ customer.address }}<template v-if="customer.country && customer.country !== 'NL'"> ({{ customer.country }})</template></dd></template>
              <template v-if="customer.kvk_number"><dt>{{ market.registry?.short || 'KVK' }}</dt><dd class="mono">{{ customer.kvk_number }}</dd></template>
              <template v-if="customer.vat_number"><dt>{{ market.tax_id?.label || $t('Btw-nummer') }}</dt><dd class="mono">{{ customer.vat_number }}</dd></template>
              <template v-if="customer.peppol_id"><dt>Peppol</dt><dd class="mono">{{ customer.peppol_id }}<span v-if="customer.peppol_available" class="tag ok">{{ $t('bereikbaar') }}</span></dd></template>
              <!--
                Staat hier een omgeving, dan krijgt die bij elke voldane
                factuur door tot wanneer er is betaald. Zichtbaar maken is het
                halve werk: staat het er niet, dan is dat te zien in plaats van
                dat je je afvraagt waarom een vereniging op slot ging.
              -->
              <template v-if="customer.vvemaat_slug">
                <dt>VvEMaat</dt>
                <dd class="mono">
                  <a :href="`https://${customer.vvemaat_slug}.vvemaat.nl`" target="_blank" rel="noopener noreferrer">{{ customer.vvemaat_slug }}.vvemaat.nl</a>
                </dd>
              </template>
              <dt>{{ $t('Taal') }}</dt><dd>{{ languageLabel }}</dd>
              <dt>{{ $t('Betaaltermijn') }}</dt><dd>{{ customer.payment_terms ? $t(':n dagen', { n: customer.payment_terms }) : $t('Standaard van je bedrijf') }}</dd>
              <template v-if="customer.hourly_rate"><dt>{{ $t('Uurtarief') }}</dt><dd>{{ eur(customer.hourly_rate) }}</dd></template>
            </dl>
            <div v-if="!customer.email" class="side-warn">{{ $t('Geen e-mailadres — facturen en offertes kunnen niet gemaild worden.') }} <Link :href="route('customers.edit', customer.id)" class="lnk">{{ $t('Aanvullen') }}</Link></div>
          </div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-header"><div class="card-title">{{ $t('Notities') }}</div></div>
          <div class="card-body">
            <p v-if="customer.notes" class="notes">{{ customer.notes }}</p>
            <p v-else class="notes muted">{{ $t('Nog geen notities. Handig voor afspraken, contactpersonen of bijzonderheden —') }} <Link :href="route('customers.edit', customer.id)" class="lnk">{{ $t('toevoegen') }}</Link>.</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.cust-head { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; }
.cust-avatar { width: 56px; height: 56px; border-radius: 14px; background: var(--brand); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 20px; flex: none; }
.cust-title { min-width: 0; }
.cust-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 6px; font-size: 13px; color: var(--text-2); }
.muted { color: var(--text-4); font-weight: 400; }
.muted-sm { color: var(--text-4); font-size: 12px; }

.kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.kpi.alert { border-color: var(--brand-border); background: linear-gradient(180deg, var(--brand-tint) 0%, var(--surface) 60%); }
.kpi .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.kpi .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.kpi .val.brand { color: var(--brand); }
.kpi .val.good { color: var(--success); }
.kpi .val.warn { color: var(--warning); }
.kpi .meta { font-size: 11.5px; color: var(--text-3); margin-top: 4px; line-height: 1.5; }
.kpi .meta .warn { color: var(--warning); font-weight: 600; }

.hours-banner { display: flex; align-items: center; gap: 12px; background: var(--info-bg); border: 1px solid var(--info-border); color: var(--info); border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; font-size: 13.5px; text-decoration: none; }
.hours-banner > svg { width: 20px; height: 20px; flex: none; }
.hours-banner > div { flex: 1; }
.hours-cta { font-weight: 600; white-space: nowrap; }

.cust-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start; }
.tag { display: inline-block; margin-left: 6px; font-size: 10px; font-weight: 700; color: var(--text-4); background: var(--surface-3); border-radius: 4px; padding: 1px 5px; vertical-align: middle; }
.tag.ok { color: var(--success); background: var(--success-bg); }
.is-open { color: var(--brand); font-weight: 600; }
.more-note { padding: 10px 16px; font-size: 12.5px; color: var(--text-4); border-top: 1px solid var(--border); }
.more-note a, .lnk { color: var(--brand); font-weight: 500; }

.dl { display: grid; grid-template-columns: 110px minmax(0, 1fr); gap: 8px 12px; margin: 0; font-size: 13px; }
.dl dt { color: var(--text-3); }
.dl dd { margin: 0; color: var(--text); overflow-wrap: anywhere; }
.dl .mono { font-family: var(--font-mono); }
.side-warn { margin-top: 14px; font-size: 12.5px; color: var(--warning); background: var(--warning-bg); border: 1px solid var(--warning-border); border-radius: 8px; padding: 10px 12px; line-height: 1.5; }
.notes { margin: 0; font-size: 13.5px; line-height: 1.7; white-space: pre-wrap; color: var(--text-2); }

@media (max-width: 1100px) { .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cust-grid { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 640px) { .kpi-grid { grid-template-columns: minmax(0, 1fr); } }
</style>
