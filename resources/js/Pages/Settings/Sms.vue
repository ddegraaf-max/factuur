<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';

const props = defineProps({
  balance: Number,
  free: Boolean,
  monthly_limit: { type: Number, default: null },
  sender: String,
  bundles: { type: Array, default: () => [] },
  vat_rate: Number,
  can_buy: Boolean,
  purchases: { type: Array, default: () => [] },
  messages: { type: Array, default: () => [] },
  webhook: { type: Object, default: null },
  used_this_month: { type: Number, default: 0 },
});

const page = usePage();
const error = computed(() => (page.props.errors || {}).sms ?? (page.props.errors || {}).credits ?? null);

// Eén bundel tegelijk: de knop van de gekozen bundel toont dat hij bezig is.
const buying = ref(null);
const buy = (bundle) => {
  buying.value = bundle.credits;
  router.post(route('settings.sms.buy'), { credits: bundle.credits }, {
    preserveScroll: true,
    onError: () => { buying.value = null; },
  });
};
// Een halve cent alleen tonen als die er is: € 0,10 en € 0,095.
const perSms = (bundle) => eur(bundle.per_sms, { decimals: Math.round(bundle.per_sms * 1000) % 10 === 0 ? 2 : 3 });
</script>

<template>
  <Head :title="$t('Sms')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Instellingen') }} / <span class="breadcrumb-current">{{ $t('Sms') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Sms') }}</h1>
        <p class="page-subtitle">{{ $t('Stuur een prijsaanvraag of een aanmaning ook per sms, met een korte link. Je betaalt per sms, uit je tegoed.') }}</p>
      </div>
    </div>

    <div v-if="error" class="field-error" style="margin-bottom:12px;">{{ error }}</div>

    <div class="sms-top">
      <div class="card sms-counter">
        <div class="lbl">{{ free ? $t('Deze maand nog te versturen') : $t('Tegoed') }}</div>
        <div class="val" :class="{ low: !free && balance <= 10 }">{{ balance }}</div>
        <div class="sub">
          <template v-if="free">{{ $t('van :n per maand, zonder tegoed', { n: monthly_limit }) }}</template>
          <template v-else-if="balance === 0">{{ $t('Koop een bundel om te kunnen sms\'en.') }}</template>
          <template v-else>{{ $t('sms\'en. Elke verstuurde sms gaat eraf.') }}</template>
        </div>
      </div>
      <div class="card sms-facts">
        <div class="fact"><span>{{ $t('Afzender') }}</span><b>{{ sender }}</b></div>
        <div class="fact"><span>{{ $t('Verstuurd deze maand') }}</span><b>{{ used_this_month }}</b></div>
        <p class="sub">{{ $t('Eén sms is 160 tekens. Een langer bericht telt als twee of drie sms\'en; dat zie je voor het versturen. Een sms die niet aankomt bij de aanbieder kost geen tegoed.') }}</p>
      </div>
    </div>

    <div v-if="!free" class="card" style="margin-top:16px;">
      <div class="card-header"><div class="card-title">{{ $t('Bundel kopen') }}</div></div>
      <div class="card-body">
        <div class="sms-bundles">
          <div v-for="b in bundles" :key="b.credits" class="sms-bundle">
            <div class="n">{{ b.credits }}</div>
            <div class="sub">{{ $t(':price per sms', { price: perSms(b) }) }}</div>
            <div class="price">{{ eur(b.price_excl) }}</div>
            <div class="sub">{{ $t('excl. btw · :incl incl. :rate% btw', { incl: eur(b.price_incl), rate: vat_rate }) }}</div>
            <button class="btn btn-primary btn-sm" :disabled="!can_buy || buying !== null" @click="buy(b)">
              {{ buying === b.credits ? $t('Naar de betaalpagina…') : $t('Kopen') }}
            </button>
          </div>
        </div>
        <p class="sub" style="margin:14px 0 0;line-height:1.6;">{{ $t('Betalen gaat via Stripe, met iDEAL of kaart. Je tegoed staat er direct na de betaling en verloopt niet.') }}</p>
      </div>
    </div>

    <div v-if="purchases.length" class="card" style="margin-top:16px;">
      <div class="card-header"><div class="card-title">{{ $t('Gekochte bundels') }}</div></div>
      <table class="data-table">
        <thead><tr><th>{{ $t('Datum') }}</th><th class="right">{{ $t('Sms\'en') }}</th><th class="right">{{ $t('Excl. btw') }}</th><th class="right">{{ $t('Incl. btw') }}</th></tr></thead>
        <tbody>
          <tr v-for="p in purchases" :key="p.id">
            <td>{{ p.paid_at_label }}</td>
            <td class="right num">{{ p.credits }}</td>
            <td class="right num">{{ eur(p.price_excl) }}</td>
            <td class="right num">{{ eur(p.price_incl) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card" style="margin-top:16px;">
      <div class="card-header"><div class="card-title">{{ $t('Verstuurde sms\'en') }}</div></div>
      <table v-if="messages.length" class="data-table">
        <thead><tr><th>{{ $t('Datum') }}</th><th>{{ $t('Naar') }}</th><th>{{ $t('Bericht') }}</th><th class="right">{{ $t('Sms\'en') }}</th><th>{{ $t('Status') }}</th></tr></thead>
        <tbody>
          <tr v-for="m in messages" :key="m.id">
            <td class="nowrap">{{ m.sent_at_label }}</td>
            <td class="nowrap">{{ m.to }}</td>
            <td class="body">{{ m.body }}</td>
            <td class="right num">{{ m.status === 'sent' ? m.segments : 0 }}</td>
            <td>
              <span :class="['pill', m.status !== 'sent' ? 'pill-cancelled' : m.delivery === 'delivered' ? 'pill-paid' : m.delivery === 'failed' ? 'pill-overdue' : 'pill-sent']">{{ m.status !== 'sent' ? $t('Mislukt') : (m.delivery_label || $t('Verstuurd')) }}</span>
              <div v-if="m.delivery === 'failed' && m.delivery_detail" class="sub">{{ m.delivery_detail }}</div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="card-body sub">{{ $t('Nog geen sms verstuurd. Dat doe je bij een uitvraag of bij een aanmaning.') }}</div>
      <div v-if="webhook" class="card-body sub" style="border-top:1px solid var(--border);line-height:1.6;">
        <b>{{ $t('Afleverstatus') }}</b> — {{ $t('stel in Smstools een webhook in van het type delivery_report op dit adres:') }} <code>{{ webhook.url }}</code>.
        {{ webhook.secret_set ? $t('De secret van de webhook staat in de omgeving (SMSTOOLS_WEBHOOK_SECRET); meldingen worden op handtekening gecontroleerd.') : $t('Zet de secret van de webhook in Railway als SMSTOOLS_WEBHOOK_SECRET; tot die tijd worden alleen meldingen over een bekend messageid verwerkt.') }}
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sms-top { display: grid; grid-template-columns: minmax(220px, 300px) 1fr; gap: 16px; }
.sms-counter { padding: 20px 22px; }
.sms-counter .lbl { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; }
.sms-counter .val { font-family: var(--font-display); font-weight: 700; font-size: 52px; line-height: 1.1; margin-top: 6px; color: var(--text); font-variant-numeric: tabular-nums; }
.sms-counter .val.low { color: var(--brand); }
.sms-facts { padding: 18px 22px; }
.fact { display: flex; justify-content: space-between; gap: 12px; padding: 7px 0; border-bottom: 1px solid var(--border); font-size: 13.5px; }
.fact span { color: var(--text-3); }
.sub { font-size: 12.5px; color: var(--text-3); line-height: 1.55; }
.sms-facts .sub { margin: 12px 0 0; }
.sms-bundles { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
.sms-bundle { border: 1px solid var(--border); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 4px; }
.sms-bundle .n { font-family: var(--font-display); font-weight: 700; font-size: 26px; }
.sms-bundle .price { font-weight: 700; font-size: 18px; margin-top: 8px; }
.sms-bundle .btn { margin-top: 12px; align-self: flex-start; }
.body { font-size: 13px; max-width: 520px; white-space: pre-wrap; }
.nowrap { white-space: nowrap; }
@media (max-width: 760px) {
  .sms-top { grid-template-columns: 1fr; }
}
</style>
