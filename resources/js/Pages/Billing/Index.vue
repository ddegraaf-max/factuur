<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  subscription: Object,
  plans: Array,
  stripeReady: Boolean,
  ai_usage: { type: Object, default: null },   // verbruik van deze administratie
  platform_ai: { type: Object, default: null }, // alleen voor het vrijgestelde beheerdersaccount
});

const usagePct = computed(() => {
  if (!props.ai_usage?.limit) return 0;
  return Math.min(100, Math.round((props.ai_usage.total / props.ai_usage.limit) * 100));
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const sub = computed(() => props.subscription || {});
const status = computed(() => sub.value.status);
const daysLeft = computed(() => sub.value.days_left ?? 0);

const endsAtLabel = computed(() => {
  if (!sub.value.ends_at) return null;
  try {
    return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' })
      .format(new Date(sub.value.ends_at));
  } catch (e) {
    return null;
  }
});

const statusMeta = computed(() => {
  if (status.value === 'exempt') {
    return { pill: 'Vrijgesteld', cls: 'ok', note: 'Dit account is vrijgesteld — je hebt altijd volledige toegang tot alles, inclusief de AI-functies.' };
  }
  if (status.value === 'active') {
    return { pill: 'Actief abonnement', cls: 'ok', note: 'Je abonnement is actief.' };
  }
  if (status.value === 'trialing') {
    return { pill: 'Proefperiode', cls: 'trial', note: 'Je zit in de gratis proefperiode — je probeert nu alles, inclusief de AI-functies uit Slim.' };
  }
  return { pill: 'Verlopen', cls: 'expired', note: 'Je toegang is verlopen. Sluit een abonnement af om verder te gaan.' };
});

const checkout = useForm({ plan: 'basis' });
const portal = useForm({});

const startCheckout = (plan) => {
  checkout.plan = plan;
  checkout.post(route('billing.checkout'));
};
const openPortal = () => portal.post(route('billing.portal'));

// Het plan waar dit bedrijf nu op zit (alleen relevant bij een actief abonnement).
const currentPlan = computed(() => (status.value === 'active' ? (sub.value.plan || 'basis') : null));
</script>

<template>
  <Head title="Abonnement" />
  <AppLayout>
    <template #breadcrumb>
      <span class="breadcrumb">Instellingen</span>
      <span class="breadcrumb">/</span>
      <span class="breadcrumb-current">Abonnement</span>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Abonnement</h1>
        <p class="page-subtitle">Beheer je EasyInvoice-abonnement en bekijk hoeveel dagen je nog hebt.</p>
      </div>
    </div>

    <div v-if="flash.flash" class="bill-alert ok">{{ flash.flash }}</div>
    <div v-if="flash.error || $page.props.errors?.error" class="bill-alert err">
      {{ flash.error || $page.props.errors?.error }}
    </div>

    <div class="bill-grid">
      <!-- STATUS CARD -->
      <div class="card">
        <div class="card-body">
          <span class="status-pill" :class="statusMeta.cls">{{ statusMeta.pill }}</span>

          <template v-if="status === 'exempt'">
            <div class="days-wrap">
              <div class="days-num">∞</div>
              <div class="days-label">altijd toegang</div>
            </div>
          </template>
          <template v-else>
            <div class="days-wrap" v-if="status !== 'expired'">
              <div class="days-num">{{ daysLeft }}</div>
              <div class="days-label">{{ daysLeft === 1 ? 'dag resterend' : 'dagen resterend' }}</div>
            </div>
            <div class="days-wrap expired" v-else>
              <div class="days-num">0</div>
              <div class="days-label">dagen resterend</div>
            </div>
          </template>

          <p class="status-note">{{ statusMeta.note }}</p>
          <p v-if="status === 'active'" class="status-sub">
            Huidig abonnement: <strong>{{ currentPlan === 'slim' ? 'Slim' : 'Basis' }}</strong>
          </p>
          <p v-if="endsAtLabel && status !== 'exempt'" class="status-sub">
            <template v-if="status === 'active'">Volgende verlenging op <strong>{{ endsAtLabel }}</strong></template>
            <template v-else-if="status === 'trialing'">Proefperiode loopt tot <strong>{{ endsAtLabel }}</strong></template>
            <template v-else>Verlopen op <strong>{{ endsAtLabel }}</strong></template>
          </p>

          <!-- progress bar during trial -->
          <div v-if="status === 'trialing'" class="trial-bar">
            <div class="trial-bar-fill" :style="{ width: Math.min(100, Math.max(4, (daysLeft / 14) * 100)) + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- PLAN CARDS -->
      <div v-for="plan in plans" :key="plan.key" class="card plan-card" :class="{ featured: plan.key === 'slim' }">
        <div class="card-body">
          <div class="plan-head">
            <div class="plan-name">{{ plan.name }}</div>
            <span v-if="currentPlan === plan.key" class="plan-current">Huidig</span>
            <span v-else-if="plan.key === 'slim'" class="plan-ai-badge">Met AI</span>
          </div>
          <div class="plan-price">
            <span class="plan-amount">€ {{ plan.amount }}</span>
            <span class="plan-period">/ maand</span>
          </div>
          <div class="plan-vat">{{ plan.vat_note }}</div>
          <p class="plan-tagline">{{ plan.tagline }}</p>

          <ul class="plan-feats">
            <li v-for="feat in plan.features" :key="feat">{{ feat }}</li>
          </ul>

          <template v-if="status === 'exempt'">
            <p class="plan-hint">Vrijgesteld account — afsluiten is niet nodig.</p>
          </template>
          <template v-else-if="status === 'active'">
            <button v-if="currentPlan === plan.key" class="btn btn-secondary btn-block" :disabled="portal.processing" @click="openPortal">
              {{ portal.processing ? 'Bezig…' : 'Abonnement beheren' }}
            </button>
            <p v-if="currentPlan === plan.key" class="plan-hint">Wijzig je betaalmethode of zeg op via het beveiligde Stripe-portaal.</p>
            <p v-else class="plan-hint">Overstappen? Mail <a href="mailto:hallo@easyinvoice.nl" style="color:var(--brand);font-weight:500;">hallo@easyinvoice.nl</a> — wij regelen het zonder dubbele kosten.</p>
          </template>
          <template v-else>
            <button
              :class="['btn', plan.key === 'slim' ? 'btn-primary' : 'btn-secondary', 'btn-block']"
              :disabled="checkout.processing || !plan.available"
              @click="startCheckout(plan.key)"
            >
              {{ checkout.processing && checkout.plan === plan.key ? 'Bezig…' : `Kies ${plan.name}` }}
            </button>
            <p v-if="!plan.available" class="plan-hint err-text">Dit abonnement is momenteel niet af te sluiten. Probeer het later opnieuw.</p>
            <p v-else class="plan-hint">Veilig betalen via Stripe. Maandelijks opzegbaar.</p>
          </template>
        </div>
      </div>
    </div>

    <!-- AI-gebruik deze maand -->
    <div v-if="ai_usage && (ai_usage.has_ai || ai_usage.total > 0)" class="card au-card">
      <div class="card-body">
        <div class="au-head">
          <div>
            <div class="au-title">AI-gebruik · {{ ai_usage.month_label }}</div>
            <div class="au-sub">Scan &amp; herken en Offerte uit tekst — de teller staat elke maand weer op nul.</div>
          </div>
          <div class="au-total">
            <b>{{ ai_usage.total }}</b>
            <span v-if="ai_usage.limit"> van {{ ai_usage.limit }}</span>
            <span v-else> · onbeperkt</span>
          </div>
        </div>
        <div v-if="ai_usage.limit" class="au-bar">
          <div class="au-bar-fill" :class="{ warn: usagePct >= 80 }" :style="{ width: Math.max(2, usagePct) + '%' }"></div>
        </div>
        <div class="au-split">
          {{ ai_usage.receipt_scans }} {{ ai_usage.receipt_scans === 1 ? 'bon of factuur gescand' : 'bonnen en facturen gescand' }}
          · {{ ai_usage.quote_parses }} {{ ai_usage.quote_parses === 1 ? 'offerte uit tekst' : 'offertes uit tekst' }}
        </div>
      </div>
    </div>

    <!-- Platformoverzicht: alleen zichtbaar voor het vrijgestelde beheerdersaccount -->
    <div v-if="platform_ai" class="card au-card">
      <div class="card-body">
        <div class="au-title">AI-gebruik hele platform <span class="au-admin-badge">beheer</span></div>
        <div class="au-sub" style="margin-bottom:12px;">Alle administraties samen — om de AI-kosten en de fair-use-grens te bewaken.</div>

        <table class="au-table">
          <thead>
            <tr><th>Maand</th><th class="right">Bonscans</th><th class="right">Offertes</th><th class="right">Totaal</th></tr>
          </thead>
          <tbody>
            <tr v-for="m in platform_ai.months" :key="m.label">
              <td>{{ m.label }}</td>
              <td class="right num">{{ m.receipt_scans }}</td>
              <td class="right num">{{ m.quote_parses }}</td>
              <td class="right num"><b>{{ m.total }}</b></td>
            </tr>
          </tbody>
        </table>

        <template v-if="platform_ai.top.length">
          <div class="au-sub" style="margin:14px 0 6px;">Meeste AI-acties deze maand</div>
          <div v-for="t in platform_ai.top" :key="t.name" class="au-top-row">
            <span>{{ t.name }}</span><span class="num">{{ t.total }}</span>
          </div>
        </template>
        <div v-else class="au-sub" style="margin-top:10px;">Nog geen AI-gebruik deze maand.</div>
      </div>
    </div>

    <p class="bill-foot">
      Vragen over je abonnement? Mail <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a>.
    </p>
  </AppLayout>
</template>

<style scoped>
.bill-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; max-width: 1180px; align-items: start; }
@media (max-width: 1080px) { .bill-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 760px) { .bill-grid { grid-template-columns: 1fr; } }

.plan-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.plan-current {
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
  color: var(--success); background: var(--success-bg); border: 1px solid var(--success-border);
  border-radius: 100px; padding: 3px 10px;
}
.plan-ai-badge {
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
  color: var(--brand-darker); background: var(--brand-tint); border: 1px solid var(--brand-border);
  border-radius: 100px; padding: 3px 10px;
}
.plan-card.featured { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
.plan-tagline { font-size: 13px; color: var(--text-3); margin-top: 10px; line-height: 1.5; }

.status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 600; border: 1px solid transparent; }
.status-pill::before { content: ''; width: 7px; height: 7px; border-radius: 100px; background: currentColor; }
.status-pill.ok { color: var(--success); background: var(--success-bg); border-color: var(--success-border); }
.status-pill.trial { color: var(--info); background: var(--info-bg); border-color: var(--info-border); }
.status-pill.expired { color: var(--brand-darker); background: var(--brand-tint-2); border-color: var(--brand-border); }

.days-wrap { margin: 22px 0 8px; }
.days-num { font-family: var(--font-display); font-weight: 700; font-size: 56px; line-height: 1; letter-spacing: -0.03em; color: var(--text); }
.days-wrap.expired .days-num { color: var(--brand); }
.days-label { color: var(--text-3); font-size: 14px; margin-top: 4px; }
.status-note { color: var(--text-2); font-size: 14px; margin-top: 10px; }
.status-sub { color: var(--text-3); font-size: 13px; margin-top: 6px; }

.trial-bar { margin-top: 16px; height: 8px; background: var(--surface-3); border-radius: 100px; overflow: hidden; }
.trial-bar-fill { height: 100%; background: var(--brand); border-radius: 100px; transition: width .3s; }

.plan-card { border-color: var(--brand-border); }
.plan-name { font-family: var(--font-display); font-weight: 700; font-size: 18px; }
.plan-price { display: flex; align-items: baseline; gap: 6px; margin-top: 8px; }
.plan-amount { font-family: var(--font-display); font-weight: 700; font-size: 40px; letter-spacing: -0.02em; }
.plan-period { color: var(--text-3); }
.plan-vat { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.plan-feats { list-style: none; padding: 0; margin: 18px 0; display: grid; gap: 8px; }
.plan-feats li { font-size: 13.5px; color: var(--text-2); padding-left: 24px; position: relative; }
.plan-feats li::before { content: '✓'; position: absolute; left: 0; color: var(--success); font-weight: 700; }
.btn-block { width: 100%; }
.plan-hint { font-size: 12px; color: var(--text-3); margin-top: 10px; text-align: center; }
.plan-hint.err-text { color: var(--brand); }

/* AI-gebruik */
.au-card { max-width: 1180px; margin-top: 20px; }
.au-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap; }
.au-title { font-family: var(--font-display); font-weight: 700; font-size: 16px; }
.au-sub { font-size: 12.5px; color: var(--text-3); margin-top: 3px; }
.au-total { font-size: 15px; color: var(--text-2); white-space: nowrap; }
.au-total b { font-family: var(--font-display); font-size: 22px; color: var(--text); }
.au-bar { margin-top: 12px; height: 8px; background: var(--surface-3); border-radius: 100px; overflow: hidden; }
.au-bar-fill { height: 100%; background: var(--success); border-radius: 100px; transition: width .3s; }
.au-bar-fill.warn { background: var(--warning); }
.au-split { font-size: 13px; color: var(--text-2); margin-top: 10px; }
.au-admin-badge {
  font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
  color: var(--info); background: var(--info-bg); border: 1px solid var(--info-border);
  border-radius: 100px; padding: 2px 9px; vertical-align: middle; margin-left: 8px;
}
.au-table { width: 100%; border-collapse: collapse; font-size: 13.5px; margin-top: 6px; }
.au-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); padding: 6px 8px; border-bottom: 1px solid var(--border); }
.au-table td { padding: 8px; border-bottom: 1px solid var(--border); }
.au-table .right { text-align: right; }
.num { font-family: var(--font-mono); }
.au-top-row { display: flex; justify-content: space-between; gap: 12px; padding: 6px 8px; font-size: 13.5px; color: var(--text-2); border-bottom: 1px solid var(--border); }
.au-top-row:last-child { border-bottom: none; }

.bill-alert { padding: 12px 16px; border-radius: var(--r); margin-bottom: 18px; font-size: 14px; font-weight: 500; max-width: 880px; }
.bill-alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid var(--success-border); }
.bill-alert.err { background: var(--brand-tint); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.bill-foot { margin-top: 22px; font-size: 13px; color: var(--text-3); }
.bill-foot a { color: var(--brand); font-weight: 500; }
</style>
