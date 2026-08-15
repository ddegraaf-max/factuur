<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';
import { computed } from 'vue';

const props = defineProps({
  quote: Object,
  company: Object,
});

const page = usePage();
const pageError = computed(() => (page.props.errors || {}).quote ?? null);

const isOpen = computed(() => ['sent', 'expired'].includes(props.quote.status));
const canEdit = computed(() => ['draft', 'sent'].includes(props.quote.status));

const pillClass = computed(() => ({
  draft: 'pill-draft',
  sent: 'pill-sent',
  accepted: 'pill-paid',
  rejected: 'pill-overdue',
  expired: 'pill-partial',
}[props.quote.status] ?? 'pill-draft'));

const send = () => {
  const label = props.quote.status === 'sent' ? 'Offerte opnieuw versturen?' : 'Offerte versturen naar de klant?';
  if (confirm(label)) {
    router.post(route('quotes.send', props.quote.id), {}, { preserveScroll: true });
  }
};

const accept = () => router.post(route('quotes.accept', props.quote.id), {}, { preserveScroll: true });
const reject = () => {
  if (confirm('Offerte markeren als afgewezen?')) {
    router.post(route('quotes.reject', props.quote.id), {}, { preserveScroll: true });
  }
};

const convert = () => {
  if (confirm('Van deze offerte een concept-factuur maken? De offerte blijft bewaard.')) {
    router.post(route('quotes.convert', props.quote.id));
  }
};

const destroy = () => {
  if (confirm('Concept verwijderen?')) {
    router.delete(route('quotes.destroy', props.quote.id));
  }
};
</script>

<template>
  <Head :title="`Offerte ${quote.number || 'concept'}`" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        Verkoop / <Link :href="route('quotes.index')" style="color:var(--text-3);">Offertes</Link> /
        <span class="breadcrumb-current">{{ quote.number || 'Concept' }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('quotes.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          Terug
        </Link>
        <h1 class="page-title">Offerte {{ quote.number || '— concept —' }}</h1>
        <p class="page-subtitle">
          <template v-if="quote.status === 'draft'">Concept · nog niet verstuurd</template>
          <template v-else-if="quote.status === 'accepted'">Geaccepteerd op {{ quote.accepted_at_label }}</template>
          <template v-else-if="quote.status === 'rejected'">Afgewezen op {{ quote.rejected_at_label }}</template>
          <template v-else-if="quote.sent_at_label">Verstuurd op {{ quote.sent_at_label }}</template>
        </p>
      </div>
      <div class="page-actions">
        <a :href="route('quotes.pdf', quote.id)" target="_blank" class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          PDF
        </a>
        <Link v-if="canEdit" :href="route('quotes.edit', quote.id)" class="btn btn-secondary btn-sm">Bewerken</Link>
        <button v-if="quote.status === 'draft'" class="btn btn-danger btn-sm" @click="destroy">Verwijder</button>
        <button v-if="canEdit" class="btn btn-primary btn-sm" @click="send">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          {{ quote.status === 'sent' ? 'Opnieuw versturen' : 'Versturen' }}
        </button>
      </div>
    </div>

    <div v-if="pageError" class="q-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ pageError }}
    </div>

    <!-- Beslisbalk: wat is er met dit voorstel gebeurd? -->
    <div v-if="isOpen" class="decide">
      <div class="decide-text">
        <strong>Wat heeft de klant besloten?</strong>
        <span v-if="quote.status === 'expired'">Deze offerte is verlopen op {{ quote.valid_until_label }}, maar je kunt hem alsnog afronden.</span>
        <span v-else-if="quote.days_left > 0">Nog {{ quote.days_left }} {{ quote.days_left === 1 ? 'dag' : 'dagen' }} geldig.</span>
      </div>
      <div class="decide-actions">
        <button class="btn btn-secondary btn-sm" @click="reject">Afgewezen</button>
        <button class="btn btn-primary btn-sm" @click="accept">Geaccepteerd</button>
      </div>
    </div>

    <div v-if="quote.status === 'accepted' && !quote.invoice" class="decide accepted">
      <div class="decide-text">
        <strong>De klant is akkoord.</strong>
        <span>Zet de offerte om in een factuur — de regels worden overgenomen als concept.</span>
      </div>
      <div class="decide-actions">
        <button class="btn btn-primary btn-sm" @click="convert">Omzetten naar factuur</button>
      </div>
    </div>

    <div v-if="quote.invoice" class="decide accepted">
      <div class="decide-text">
        <strong>Gefactureerd.</strong>
        <span>Deze offerte is omgezet in factuur {{ quote.invoice.number || '(concept)' }}.</span>
      </div>
      <div class="decide-actions">
        <Link :href="route('invoices.show', quote.invoice.id)" class="btn btn-secondary btn-sm">Bekijk factuur</Link>
      </div>
    </div>

    <div class="inv-detail">
      <div class="inv-detail-header">
        <div class="inv-detail-top">
          <div>
            <div class="inv-number">{{ quote.number || '— concept —' }}</div>
            <div style="margin-top:8px;">
              <span class="pill" :class="pillClass">{{ quote.status_label }}</span>
            </div>
          </div>
          <div style="text-align:right">
            <div class="inv-meta-label" style="margin-bottom:6px;">Totaal</div>
            <div style="font-family:var(--font-display);font-weight:700;font-size:28px;letter-spacing:-0.02em;">{{ eur(quote.total) }}</div>
          </div>
        </div>
        <div class="inv-detail-meta">
          <div>
            <div class="inv-meta-label">Offertedatum</div>
            <div class="inv-meta-value">{{ quote.quote_date_label }}</div>
          </div>
          <div>
            <div class="inv-meta-label">Geldig tot</div>
            <div class="inv-meta-value">{{ quote.valid_until_label }}</div>
          </div>
          <div v-if="quote.reference">
            <div class="inv-meta-label">Referentie</div>
            <div class="inv-meta-value mono">{{ quote.reference }}</div>
          </div>
        </div>
      </div>

      <div class="inv-body">
        <div class="inv-parties">
          <div>
            <div class="inv-party-label">Van</div>
            <div class="inv-party-name">{{ company.name }}</div>
            <div v-if="company.address_line" class="inv-party-line">{{ company.address_line }}</div>
            <div v-if="company.postal_code || company.city" class="inv-party-line">{{ company.postal_code }} {{ company.city }}</div>
          </div>
          <div>
            <div class="inv-party-label">Voor</div>
            <div class="inv-party-name">{{ quote.customer_name }}</div>
            <div v-if="quote.customer_address_line" class="inv-party-line">{{ quote.customer_address_line }}</div>
            <div v-if="quote.customer_postal_code || quote.customer_city" class="inv-party-line">
              {{ quote.customer_postal_code }} {{ quote.customer_city }}
            </div>
            <div v-if="quote.customer_email" class="inv-party-line">{{ quote.customer_email }}</div>
          </div>
        </div>

        <div v-if="quote.intro" style="margin-bottom:24px;font-size:14px;color:var(--text-2);line-height:1.65;white-space:pre-line;">{{ quote.intro }}</div>

        <table class="inv-lines stacked-table">
          <thead>
            <tr>
              <th style="width:55%">Omschrijving</th>
              <th style="text-align:right">Aantal</th>
              <th style="text-align:right">Prijs</th>
              <th style="text-align:center">BTW</th>
              <th style="text-align:right">Totaal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="line in quote.lines" :key="line.id">
              <td class="cell-primary">
                <div style="font-weight:500;margin-bottom:2px;">{{ line.description }}</div>
                <div v-if="line.details" style="font-size:12px;color:var(--text-3);">{{ line.details }}</div>
              </td>
              <td class="mono" style="text-align:right" data-label="Aantal">{{ Number(line.quantity) }}</td>
              <td class="mono" style="text-align:right" data-label="Prijs">{{ eur(line.unit_price) }}</td>
              <td style="text-align:center" data-label="BTW">{{ Number(line.vat_rate) }}%</td>
              <td class="mono" style="text-align:right" data-label="Totaal">{{ eur(line.line_subtotal) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="inv-totals">
          <div class="inv-total-row">
            <span class="label">Subtotaal</span>
            <span class="value mono">{{ eur(quote.subtotal) }}</span>
          </div>
          <div v-for="(amount, rate) in quote.vat_breakdown" :key="rate" class="inv-total-row">
            <span class="label">BTW {{ Number(rate) }}%</span>
            <span class="value mono">{{ eur(amount) }}</span>
          </div>
          <div class="inv-total-row grand">
            <span class="label">Totaal</span>
            <span class="value mono">{{ eur(quote.total) }}</span>
          </div>
        </div>

        <div v-if="quote.notes" style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);font-size:13px;color:var(--text-3);">
          <div style="margin-bottom:8px;color:var(--text-2);font-weight:500;">Opmerking</div>
          {{ quote.notes }}
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Documentweergave — zelfde vormgeving als de factuurpagina. Lokaal
   gedefinieerd zodat de offerte er ook goed uitziet wanneer je er direct
   op binnenkomt zonder eerst een factuur te openen. */
.inv-detail { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); overflow: hidden; }
.inv-detail-header { background: linear-gradient(180deg, var(--brand-tint) 0%, var(--surface) 100%); padding: 28px 32px; border-bottom: 1px solid var(--border); }
.inv-detail-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
.inv-number { font-family: var(--font-display); font-weight: 600; font-size: 22px; letter-spacing: -0.01em; }
.inv-detail-meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
.inv-meta-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; margin-bottom: 4px; }
.inv-meta-value { font-size: 14px; color: var(--text); font-weight: 500; }
.inv-meta-value.mono { font-family: var(--font-mono); }
.inv-body { padding: 28px 32px; }
.inv-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; padding-bottom: 28px; margin-bottom: 28px; border-bottom: 1px solid var(--border); }
.inv-party-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; margin-bottom: 8px; }
.inv-party-name { font-weight: 600; font-size: 15px; margin-bottom: 4px; }
.inv-party-line { color: var(--text-2); font-size: 13px; line-height: 1.6; }
.inv-lines th { text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); border-bottom: 1px solid var(--border); }
.inv-lines td { padding: 14px 12px; border-bottom: 1px solid var(--border); vertical-align: top; }
.inv-totals { margin-top: 24px; margin-left: auto; width: 320px; }
.inv-total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
.inv-total-row .label { color: var(--text-2); }
.inv-total-row .value { font-weight: 500; }
.inv-total-row.grand { border-top: 2px solid var(--text); padding-top: 14px; margin-top: 8px; font-weight: 700; font-size: 18px; }

@media (max-width: 760px) {
  .inv-detail-header, .inv-body { padding: 20px 16px; }
  .inv-detail-top { flex-direction: column; gap: 12px; }
  .inv-detail-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
  .inv-parties { grid-template-columns: minmax(0, 1fr); gap: 22px; }
  .inv-party-line { overflow-wrap: anywhere; }
  .inv-totals { width: 100%; margin-left: 0; }
  .inv-lines td:not([data-label]):not(.cell-primary) { display: none; }
}

.q-alert { display: flex; align-items: center; gap: 10px; background: var(--brand-tint); border: 1px solid var(--brand-border); color: var(--brand-darker); border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13.5px; }
.q-alert svg { width: 18px; height: 18px; flex: none; }

.decide {
  display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
  background: var(--surface); border: 1px solid var(--border-strong);
  border-radius: 12px; padding: 16px 20px; margin-bottom: 16px;
}
.decide.accepted { background: var(--success-bg); border-color: var(--success-border); }
.decide-text { flex: 1; min-width: 220px; font-size: 13.5px; color: var(--text-2); line-height: 1.6; }
.decide-text strong { display: block; color: var(--text); font-size: 14px; margin-bottom: 2px; }
.decide-actions { display: flex; gap: 8px; }

@media (max-width: 640px) {
  .decide-actions { width: 100%; }
  .decide-actions .btn { flex: 1; }
}
</style>
