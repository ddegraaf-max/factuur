<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  subscription: Object,
  price: Object,
  stripeReady: Boolean,
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
  if (status.value === 'active') {
    return { pill: 'Actief abonnement', cls: 'ok', note: 'Je abonnement is actief.' };
  }
  if (status.value === 'trialing') {
    return { pill: 'Proefperiode', cls: 'trial', note: 'Je zit in de gratis proefperiode.' };
  }
  return { pill: 'Verlopen', cls: 'expired', note: 'Je toegang is verlopen. Sluit een abonnement af om verder te gaan.' };
});

const checkout = useForm({});
const portal = useForm({});

const startCheckout = () => checkout.post(route('billing.checkout'));
const openPortal = () => portal.post(route('billing.portal'));
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

          <div class="days-wrap" v-if="status !== 'expired'">
            <div class="days-num">{{ daysLeft }}</div>
            <div class="days-label">{{ daysLeft === 1 ? 'dag resterend' : 'dagen resterend' }}</div>
          </div>
          <div class="days-wrap expired" v-else>
            <div class="days-num">0</div>
            <div class="days-label">dagen resterend</div>
          </div>

          <p class="status-note">{{ statusMeta.note }}</p>
          <p v-if="endsAtLabel" class="status-sub">
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

      <!-- PLAN CARD -->
      <div class="card plan-card">
        <div class="card-body">
          <div class="plan-name">EasyInvoice Compleet</div>
          <div class="plan-price">
            <span class="plan-amount">{{ price.currency }}{{ price.amount }}</span>
            <span class="plan-period">/ {{ price.period }}</span>
          </div>
          <div class="plan-vat">{{ price.vat_note }}</div>

          <ul class="plan-feats">
            <li>Onbeperkt facturen, klanten en producten</li>
            <li>BTW automatisch · herinneringen · incasso</li>
            <li>Maandelijks opzegbaar</li>
          </ul>

          <template v-if="status === 'active'">
            <button class="btn btn-secondary btn-block" :disabled="portal.processing" @click="openPortal">
              {{ portal.processing ? 'Bezig…' : 'Abonnement beheren' }}
            </button>
            <p class="plan-hint">Wijzig je betaalmethode of zeg op via het beveiligde Stripe-portaal.</p>
          </template>
          <template v-else>
            <button class="btn btn-primary btn-block" :disabled="checkout.processing || !stripeReady" @click="startCheckout">
              {{ checkout.processing ? 'Bezig…' : (status === 'expired' ? 'Abonnement afsluiten' : 'Nu abonnement afsluiten') }}
            </button>
            <p v-if="!stripeReady" class="plan-hint err-text">Betalen is momenteel niet beschikbaar. Probeer het later opnieuw.</p>
            <p v-else class="plan-hint">Veilig betalen via Stripe. Je kunt elke maand opzeggen.</p>
          </template>
        </div>
      </div>
    </div>

    <p class="bill-foot">
      Vragen over je abonnement? Mail <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a>.
    </p>
  </AppLayout>
</template>

<style scoped>
.bill-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 880px; }
@media (max-width: 760px) { .bill-grid { grid-template-columns: 1fr; } }

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

.bill-alert { padding: 12px 16px; border-radius: var(--r); margin-bottom: 18px; font-size: 14px; font-weight: 500; max-width: 880px; }
.bill-alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid var(--success-border); }
.bill-alert.err { background: var(--brand-tint); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.bill-foot { margin-top: 22px; font-size: 13px; color: var(--text-3); }
.bill-foot a { color: var(--brand); font-weight: 500; }
</style>
