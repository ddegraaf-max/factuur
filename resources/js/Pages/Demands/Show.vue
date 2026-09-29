<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/**
 * De pagina van de online aanmaning (openbaar, via de geheime link). Geen
 * inlog, geen app-layout. De teksten komen van de server, in de taal van de
 * factuur: zo zeggen de mail, de brief en deze pagina hetzelfde.
 */
const props = defineProps({
  valid: Boolean,
  token: String,
  t: { type: Object, default: () => ({}) },
  company: Object,
  invoice: Object,
  demand: Object,
  claim: Object,
  qr: String,
});

const page = usePage();
const brand = computed(() => page.props.brand || {});
const flash = computed(() => page.props.flash?.flash || null);
const pageError = computed(() => (page.props.errors || {}).demand ?? null);
const color = computed(() => props.company?.color || brand.value.color || '#DC2626');
const fill = (text, values) => Object.entries(values).reduce((s, [key, value]) => s.replace(':' + key, value), text || '');

const active = computed(() => props.demand?.status === 'sent');
const form = useForm({ response: '', date: '', note: '' });
const answering = ref(!props.demand?.response);
const choose = (response) => {
  form.clearErrors();
  form.response = response;
  form.date = '';
};
const submit = () => form.post(route('demand.respond', props.token), {
  preserveScroll: true,
  onSuccess: () => { answering.value = false; form.reset(); },
});
const options = computed(() => [
  { key: 'paid', label: props.t.opt_paid, hint: props.t.opt_paid_hint },
  { key: 'promise', label: props.t.opt_promise, hint: props.t.opt_promise_hint },
  { key: 'dispute', label: props.t.opt_dispute, hint: props.t.opt_dispute_hint },
]);
</script>

<template>
  <Head :title="valid ? t.letter.title + ' · ' + invoice.number : t.invalid_title">
    <meta name="robots" content="noindex, nofollow">
  </Head>
  <div class="dp-shell">
    <div class="dp-card">
      <div class="dp-head" :style="{ background: color }">
        <div class="dp-company">{{ company?.name || brand.name }}</div>
        <div v-if="valid" class="dp-kind">{{ t.letter.title }}</div>
      </div>

      <div v-if="!valid" class="dp-body">
        <h1>{{ t.invalid_title }}</h1>
        <p>{{ t.invalid_text }}</p>
      </div>

      <div v-else class="dp-body">
        <h1>{{ t.letter.subtitle }}</h1>
        <p class="dp-intro">{{ t.letter.intro }}</p>

        <div v-if="flash" class="dp-flash">{{ flash }}</div>
        <div v-if="pageError" class="dp-error">{{ pageError }}</div>

        <div v-if="demand.status === 'paid'" class="dp-final good">{{ t.state_paid }}</div>
        <div v-else-if="demand.status === 'transferred'" class="dp-final">{{ t.state_transferred }}</div>
        <div v-else-if="demand.status === 'withdrawn'" class="dp-final">{{ t.state_withdrawn }}</div>

        <template v-if="active">
          <!-- Het bedrag van vandaag -->
          <div class="dp-amount">
            <div class="dp-row"><span>{{ t.letter.l_principal }}</span><span class="n">{{ claim.principal_label }}</span></div>
            <div v-if="claim.with_interest" class="dp-row"><span>{{ t.letter.l_interest }}</span><span class="n">{{ claim.interest_label }}</span></div>
            <div v-if="claim.costs_due" class="dp-row"><span>{{ t.letter.l_costs }}</span><span class="n">{{ claim.costs_label }}</span></div>
            <div class="dp-row total"><span>{{ t.today }}</span><span class="n">{{ claim.total_label }}</span></div>
          </div>
          <p v-if="claim.with_interest && claim.per_day > 0" class="dp-note">{{ fill(t.per_day, { amount: claim.per_day_label }) }}</p>
          <div class="dp-deadline" :class="{ passed: demand.expired }">
            <template v-if="demand.expired">{{ t.deadline_passed }}</template>
            <template v-else>
              <strong>{{ t.deadline_open }}</strong>
              {{ fill(t.after, { amount: claim.total_after_label }) }}
            </template>
          </div>

          <!-- Betalen -->
          <h2>{{ t.pay_title }}</h2>
          <div class="dp-pay">
            <div class="dp-bank">
              <div v-if="company.iban"><span class="k">{{ t.pay_iban }}</span><span class="mono">{{ company.iban }}</span></div>
              <div><span class="k">{{ t.pay_name }}</span><span>{{ company.holder || company.name }}</span></div>
              <div><span class="k">{{ t.pay_amount }}</span><span class="mono">{{ claim.total_label }}</span></div>
              <div><span class="k">{{ t.pay_reference }}</span><span class="mono">{{ invoice.number }}</span></div>
              <a v-if="invoice.portal_url" :href="invoice.portal_url" class="dp-btn" :style="{ background: color }">{{ t.pay_online }}</a>
            </div>
            <div v-if="qr" class="dp-qr">
              <img :src="qr" alt="">
              <div>{{ t.pay_qr }}</div>
            </div>
          </div>

          <!-- Reageren -->
          <h2>{{ t.respond_title }}</h2>
          <div v-if="demand.response && !answering" class="dp-answered">
            <div class="dp-hint">{{ t.answered }}</div>
            <div class="dp-answer">{{ demand.response_label }}</div>
            <div v-if="demand.response_note" class="dp-answer-note">“{{ demand.response_note }}”</div>
            <button type="button" class="dp-link" @click="answering = true">{{ t.answer_again }}</button>
          </div>
          <form v-else @submit.prevent="submit">
            <p class="dp-hint">{{ t.respond_intro }}</p>
            <div class="dp-options">
              <button v-for="o in options" :key="o.key" type="button" class="dp-option" :class="{ on: form.response === o.key }" :style="form.response === o.key ? { borderColor: color } : {}" @click="choose(o.key)">
                {{ o.label }}
              </button>
            </div>
            <div v-if="form.errors.response" class="dp-err">{{ form.errors.response }}</div>

            <template v-if="form.response">
              <p class="dp-hint">{{ options.find(o => o.key === form.response).hint }}</p>
              <div v-if="form.response !== 'dispute'" class="dp-field">
                <label>{{ t.date }}</label>
                <input
                  type="date" v-model="form.date"
                  :min="form.response === 'promise' ? demand.today : undefined"
                  :max="form.response === 'promise' ? demand.promise_max : demand.today"
                  :required="form.response === 'promise'"
                >
                <div v-if="form.errors.date" class="dp-err">{{ form.errors.date }}</div>
              </div>
              <div class="dp-field">
                <label>{{ t.note }}</label>
                <textarea v-model="form.note" rows="3" maxlength="2000" :placeholder="t.note_placeholder" :required="form.response === 'dispute'"></textarea>
                <div v-if="form.errors.note" class="dp-err">{{ form.errors.note }}</div>
              </div>
              <button type="submit" class="dp-btn" :style="{ background: color }" :disabled="form.processing">{{ t.send }}</button>
              <p class="dp-small">{{ t.send_note }}</p>
            </template>
          </form>
        </template>

        <div class="dp-docs">
          <a :href="demand.pdf_url" target="_blank" rel="noopener">📄 {{ t.letter_pdf }}</a>
        </div>

        <div class="dp-contact">
          {{ t.contact }}<template v-if="company.phone"> · {{ company.phone }}</template><template v-if="company.email"> · <a :href="'mailto:' + company.email">{{ company.email }}</a></template>
        </div>
        <p class="dp-small">{{ t.letter.legal }}</p>
      </div>
    </div>
    <div v-if="valid" class="dp-footer">{{ t.footer }}</div>
  </div>
</template>

<style scoped>
.dp-shell { min-height: 100vh; background: #F5F5F4; padding: 32px 16px; font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1C1917; }
.dp-card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 1px 3px rgba(28,25,23,0.08); overflow: hidden; }
.dp-head { padding: 22px 32px; color: #fff; display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
.dp-company { font-size: 18px; font-weight: 700; }
.dp-kind { font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.85; white-space: nowrap; }
.dp-body { padding: 28px 32px 30px; }
h1 { font-size: 22px; margin: 0 0 8px; letter-spacing: -0.015em; }
h2 { font-size: 15px; margin: 26px 0 10px; letter-spacing: -0.01em; }
.dp-intro { color: #44403C; line-height: 1.6; margin: 0 0 16px; font-size: 14.5px; }
.dp-flash { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 14px; }
.dp-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 14px; }
.dp-final { background: #F5F5F4; border-radius: 8px; padding: 12px 14px; font-size: 14.5px; line-height: 1.6; margin-bottom: 14px; }
.dp-final.good { background: #ECFDF5; color: #065F46; }
.dp-amount { background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 10px; padding: 6px 16px; font-size: 14px; }
.dp-row { display: flex; justify-content: space-between; gap: 16px; padding: 9px 0; border-bottom: 1px solid #E7E5E4; color: #44403C; }
.dp-row .n { white-space: nowrap; color: #1C1917; font-variant-numeric: tabular-nums; }
.dp-row.total { border-bottom: 0; font-weight: 700; font-size: 17px; color: #1C1917; padding: 12px 0; }
.dp-note { font-size: 13px; color: #78716C; margin: 8px 0 0; }
.dp-deadline { margin-top: 12px; padding: 12px 14px; border-radius: 10px; background: #FFFBEB; border: 1px solid #FCD34D; font-size: 14px; line-height: 1.6; color: #44403C; }
.dp-deadline.passed { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }
.dp-pay { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; }
.dp-bank { flex: 1; min-width: 260px; font-size: 14px; }
.dp-bank > div { display: flex; gap: 12px; padding: 4px 0; }
.dp-bank .k { color: #78716C; width: 130px; flex: 0 0 130px; }
.mono { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 13.5px; }
.dp-qr { text-align: center; font-size: 12px; color: #78716C; width: 150px; }
.dp-qr img { width: 140px; height: 140px; display: block; margin: 0 auto 4px; }
.dp-btn { display: inline-block; border: 0; color: #fff; font-weight: 600; font-size: 15px; padding: 12px 22px; border-radius: 8px; cursor: pointer; text-decoration: none; margin-top: 12px; }
.dp-btn:disabled { opacity: 0.6; cursor: default; }
.dp-hint { font-size: 13.5px; color: #57534E; line-height: 1.6; margin: 0 0 10px; }
.dp-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px; }
@media (max-width: 560px) { .dp-options { grid-template-columns: 1fr; } }
.dp-option { border: 2px solid #E7E5E4; background: #fff; border-radius: 10px; padding: 12px 10px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; color: #1C1917; }
.dp-option.on { background: #FAFAF9; }
.dp-field { margin-bottom: 12px; }
.dp-field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
.dp-field input, .dp-field textarea { width: 100%; box-sizing: border-box; border: 1px solid #D6D3D1; border-radius: 8px; padding: 9px 12px; font-size: 14px; font-family: inherit; }
.dp-err { color: #B91C1C; font-size: 12px; margin-top: 4px; }
.dp-small { font-size: 12px; color: #A8A29E; line-height: 1.6; margin: 10px 0 0; }
.dp-answered { background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 10px; padding: 14px 16px; }
.dp-answer { font-weight: 600; font-size: 14.5px; }
.dp-answer-note { font-size: 14px; color: #44403C; margin-top: 4px; white-space: pre-wrap; }
.dp-link { background: none; border: 0; color: #78716C; text-decoration: underline; cursor: pointer; font-size: 13px; padding: 0; margin-top: 8px; font-family: inherit; }
.dp-docs { margin-top: 22px; font-size: 13.5px; }
.dp-docs a { color: inherit; }
.dp-contact { margin-top: 16px; padding-top: 16px; border-top: 1px solid #E7E5E4; font-size: 13px; color: #78716C; }
.dp-contact a { color: inherit; }
.dp-footer { text-align: center; font-size: 12px; color: #A8A29E; margin: 18px auto 0; max-width: 640px; line-height: 1.5; }
</style>
