<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { eur } from '@/format.js';

const props = defineProps({
  invoice: Object,
  company: Object,
});

const email = computed(() => usePage().props.portal_email || null);

const brand = computed(() => props.company.brand_color || '#E8231F');

const isOpen = computed(() =>
  !props.invoice.is_credit && ['sent', 'partial', 'overdue', 'incasso'].includes(props.invoice.status)
);
</script>

<template>
  <Head :title="`Factuur ${invoice.number} · Facturenportaal`" />
  <PortalLayout :email="email">
    <Link :href="route('portal.index')" class="portal-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Alle facturen
    </Link>

    <div class="portal-card portal-invoice">
      <!-- Kop met huisstijl van de afzender -->
      <div class="pi-head" :style="{ '--pi-brand': brand }">
        <div class="pi-head-left">
          <img v-if="company.logo_data" :src="company.logo_data" :alt="company.name" class="pi-logo">
          <div v-else class="pi-company-name" :style="{ color: brand }">{{ company.name }}</div>
          <div class="pi-doc-label">
            {{ invoice.is_credit ? 'Creditnota' : 'Factuur' }} <strong>{{ invoice.number }}</strong>
          </div>
        </div>
        <div class="pi-head-right">
          <StatusPill :status="invoice.status" :days-overdue="invoice.days_overdue" />
          <div class="pi-total">{{ eur(invoice.total) }}</div>
          <div v-if="invoice.paid_total > 0 && invoice.remaining > 0" class="pi-remaining">
            {{ eur(invoice.paid_total) }} betaald · nog {{ eur(invoice.remaining) }} open
          </div>
          <div v-else-if="invoice.status === 'paid'" class="pi-paid-note">Volledig betaald — dank!</div>
        </div>
      </div>

      <!-- Meta -->
      <div class="pi-meta">
        <div>
          <div class="pi-meta-label">Factuurdatum</div>
          <div class="pi-meta-value">{{ invoice.invoice_date_label }}</div>
        </div>
        <div v-if="invoice.due_date_label">
          <div class="pi-meta-label">Vervaldatum</div>
          <div class="pi-meta-value">{{ invoice.due_date_label }}</div>
        </div>
        <div v-if="invoice.reference">
          <div class="pi-meta-label">Referentie</div>
          <div class="pi-meta-value mono">{{ invoice.reference }}</div>
        </div>
        <div>
          <div class="pi-meta-label">Voor</div>
          <div class="pi-meta-value">{{ invoice.customer_name }}</div>
        </div>
      </div>

      <!-- Regels -->
      <table class="pi-lines">
        <thead>
          <tr>
            <th>Omschrijving</th>
            <th class="right">Aantal</th>
            <th class="right">Prijs</th>
            <th class="right">Bedrag</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="line in invoice.lines" :key="line.id">
            <td>
              <div class="pi-line-desc">{{ line.description }}</div>
              <div v-if="line.details" class="pi-line-details">{{ line.details }}</div>
            </td>
            <td class="right mono">{{ Number(line.quantity) }}</td>
            <td class="right mono">{{ eur(line.unit_price) }}</td>
            <td class="right mono">{{ eur(line.line_subtotal) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Totalen -->
      <div class="pi-totals">
        <div class="pi-total-row">
          <span>Subtotaal</span>
          <span class="mono">{{ eur(invoice.subtotal) }}</span>
        </div>
        <div v-for="(amount, rate) in invoice.vat_breakdown" :key="rate" class="pi-total-row">
          <span>BTW {{ Number(rate) }}%</span>
          <span class="mono">{{ eur(amount) }}</span>
        </div>
        <div class="pi-total-row grand">
          <span>Totaal</span>
          <span class="mono">{{ eur(invoice.total) }}</span>
        </div>
      </div>

      <div v-if="invoice.notes" class="pi-notes">{{ invoice.notes }}</div>

      <!-- Betaalinformatie -->
      <div v-if="isOpen && company.iban" class="pi-pay" :style="{ borderColor: brand }">
        <div class="pi-pay-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          Zo betaal je deze factuur
        </div>
        <div class="pi-pay-grid">
          <div>
            <div class="pi-meta-label">Te betalen</div>
            <div class="pi-pay-amount">{{ eur(invoice.remaining) }}</div>
          </div>
          <div>
            <div class="pi-meta-label">IBAN</div>
            <div class="pi-meta-value mono">{{ company.iban }}</div>
          </div>
          <div>
            <div class="pi-meta-label">Ten name van</div>
            <div class="pi-meta-value">{{ company.name }}</div>
          </div>
          <div>
            <div class="pi-meta-label">Onder vermelding van</div>
            <div class="pi-meta-value mono">{{ invoice.number }}</div>
          </div>
        </div>
        <div v-if="invoice.due_date_label" class="pi-pay-due">
          Graag betalen vóór <strong>{{ invoice.due_date_label }}</strong>.
        </div>
      </div>

      <!-- Bijlagen van de afzender -->
      <div v-if="invoice.attachments && invoice.attachments.length" class="pi-payments">
        <div class="pi-sect-title">Bijlagen bij deze factuur</div>
        <div v-for="att in invoice.attachments" :key="att.id" class="pi-att-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <div class="pi-att-info">
            <div class="pi-att-name">{{ att.filename }}</div>
            <div class="pi-att-meta">{{ att.size_formatted }}</div>
          </div>
          <a :href="route('portal.invoice.attachment', [invoice.token, att.id])" class="btn btn-secondary" style="height:34px;padding:0 14px;font-size:13px;">
            Download
          </a>
        </div>
      </div>

      <!-- Betalingen -->
      <div v-if="invoice.payments && invoice.payments.length" class="pi-payments">
        <div class="pi-sect-title">Ontvangen betalingen</div>
        <div v-for="p in invoice.payments" :key="p.id" class="pi-payment-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <span>{{ p.paid_on_label }} · {{ p.label }}</span>
          <span class="mono" style="margin-left:auto;">{{ eur(p.amount) }}</span>
        </div>
      </div>

      <!-- Acties -->
      <div class="pi-actions">
        <a :href="route('portal.invoice.pdf', invoice.token)" class="btn btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download PDF
        </a>
        <a v-if="company.email" :href="`mailto:${company.email}?subject=Vraag over factuur ${invoice.number}`" class="btn btn-secondary">
          Vraag stellen over deze factuur
        </a>
      </div>

      <div v-if="invoice.footer" class="pi-footer">{{ invoice.footer }}</div>
    </div>

    <!-- Afzendergegevens -->
    <div class="pi-sender">
      <div class="pi-sender-name">{{ company.name }}</div>
      <div class="pi-sender-line">
        <template v-if="company.address_line">{{ company.address_line }}, </template>
        {{ company.postal_code }} {{ company.city }}
      </div>
      <div class="pi-sender-line">
        <span v-if="company.kvk_number">KVK {{ company.kvk_number }}</span>
        <span v-if="company.kvk_number && company.vat_number"> · </span>
        <span v-if="company.vat_number">BTW {{ company.vat_number }}</span>
        <span v-if="company.phone"> · {{ company.phone }}</span>
      </div>
    </div>
  </PortalLayout>
</template>

<style scoped>
.portal-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-3);
  margin-bottom: 14px;
}
.portal-back svg { width: 14px; height: 14px; }
.portal-back:hover { color: var(--text); }

.portal-invoice { padding: 0; overflow: hidden; }

.pi-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  padding: 28px 32px;
  border-bottom: 3px solid var(--pi-brand, var(--brand));
  background: linear-gradient(180deg, var(--surface-2) 0%, var(--surface) 100%);
}
.pi-logo { max-height: 48px; max-width: 200px; object-fit: contain; display: block; margin-bottom: 12px; }
.pi-company-name { font-family: var(--font-display); font-weight: 700; font-size: 20px; margin-bottom: 10px; }
.pi-doc-label { font-size: 14px; color: var(--text-2); }
.pi-doc-label strong { font-family: var(--font-mono); }
.pi-head-right { text-align: right; flex: none; }
.pi-total { font-family: var(--font-display); font-weight: 700; font-size: 28px; letter-spacing: -0.02em; margin-top: 8px; }
.pi-remaining { font-size: 12.5px; color: var(--warning); margin-top: 4px; }
.pi-paid-note { font-size: 12.5px; color: var(--success); margin-top: 4px; font-weight: 500; }

.pi-meta {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
  padding: 20px 32px;
  border-bottom: 1px solid var(--border);
}
.pi-meta-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); font-weight: 600; margin-bottom: 4px; }
.pi-meta-value { font-size: 14px; font-weight: 500; }
.mono { font-family: var(--font-mono); }

.pi-lines { width: 100%; border-collapse: collapse; }
.pi-lines th {
  text-align: left;
  padding: 12px 16px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-3);
  border-bottom: 1px solid var(--border);
}
.pi-lines th:first-child, .pi-lines td:first-child { padding-left: 32px; }
.pi-lines th:last-child, .pi-lines td:last-child { padding-right: 32px; }
.pi-lines td { padding: 13px 16px; border-bottom: 1px solid var(--border); vertical-align: top; font-size: 14px; }
.pi-lines .right { text-align: right; }
.pi-line-desc { font-weight: 500; }
.pi-line-details { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }

.pi-totals { padding: 18px 32px 6px; margin-left: auto; max-width: 340px; }
.pi-total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; color: var(--text-2); }
.pi-total-row.grand { border-top: 2px solid var(--text); margin-top: 8px; padding-top: 12px; font-weight: 700; font-size: 17px; color: var(--text); }

.pi-notes { padding: 10px 32px 0; font-size: 13px; color: var(--text-3); line-height: 1.6; white-space: pre-wrap; }

.pi-pay {
  margin: 22px 32px 0;
  border: 1.5px solid;
  border-radius: var(--r);
  padding: 18px 20px;
  background: var(--surface-2);
}
.pi-pay-title { display: flex; align-items: center; gap: 9px; font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 14px; }
.pi-pay-title svg { width: 18px; height: 18px; color: var(--text-3); }
.pi-pay-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; }
.pi-pay-amount { font-family: var(--font-display); font-weight: 700; font-size: 20px; letter-spacing: -0.01em; }
.pi-pay-due { margin-top: 14px; font-size: 13px; color: var(--text-2); }

.pi-payments { padding: 22px 32px 0; }
.pi-att-row {
  display: flex; align-items: center; gap: 12px;
  border: 1px solid var(--border); border-radius: 10px;
  padding: 10px 14px; margin-bottom: 8px;
}
.pi-att-row > svg { width: 20px; height: 20px; color: var(--text-3); flex: none; }
.pi-att-info { flex: 1; min-width: 0; }
.pi-att-name { font-weight: 600; font-size: 13.5px; word-break: break-word; }
.pi-att-meta { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.pi-sect-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 10px; }
.pi-payment-row {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 0; border-bottom: 1px solid var(--border);
  font-size: 13.5px;
}
.pi-payment-row:last-child { border-bottom: none; }
.pi-payment-row svg { width: 15px; height: 15px; color: var(--success); }

.pi-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  padding: 24px 32px;
}
.pi-footer {
  padding: 0 32px 24px;
  font-size: 12.5px;
  color: var(--text-4);
  line-height: 1.6;
}

.pi-sender {
  margin-top: 18px;
  text-align: center;
  font-size: 12.5px;
  color: var(--text-4);
  line-height: 1.7;
}
.pi-sender-name { font-weight: 600; color: var(--text-3); }

@media (max-width: 640px) {
  .pi-head { flex-direction: column; padding: 20px 18px; }
  .pi-head-right { text-align: left; }
  .pi-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 16px 18px; }
  .pi-lines th:first-child, .pi-lines td:first-child { padding-left: 18px; }
  .pi-lines th:last-child, .pi-lines td:last-child { padding-right: 18px; }
  .pi-lines th:nth-child(2), .pi-lines td:nth-child(2),
  .pi-lines th:nth-child(3), .pi-lines td:nth-child(3) { display: none; }
  .pi-totals { padding: 14px 18px 4px; max-width: none; }
  .pi-pay { margin: 18px 18px 0; }
  .pi-payments { padding: 18px 18px 0; }
  .pi-actions { padding: 20px 18px; }
  .pi-actions .btn { flex: 1; }
  .pi-footer { padding: 0 18px 20px; }
  .pi-notes { padding: 10px 18px 0; }
}
</style>
