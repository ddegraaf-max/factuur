<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/**
 * De pagina van de online aanmaning (openbaar, via de geheime link): een brief
 * die leeft. Het bedrag is dat van vandaag, de klant reageert met één klik.
 * Geen inlog, geen app-layout. De teksten komen van de server, in de taal van
 * de factuur: zo zeggen de mail, de brief en deze pagina hetzelfde.
 *
 * Met de sleutel van de schuldeiser is dit zijn overzicht (creditor); op de
 * website dient dezelfde pagina als voorbeeld (demo).
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
  creditor: { type: Object, default: null },
  demo: { type: Object, default: null },
});

const page = usePage();
const brand = computed(() => page.props.brand || {});
const flash = computed(() => page.props.flash?.flash || null);
const pageError = computed(() => (page.props.errors || {}).demand ?? null);
const color = computed(() => props.company?.color || brand.value.color || '#DC2626');
const fill = (text, values) => Object.entries(values).reduce((s, [key, value]) => s.replace(':' + key, value), text || '');

const active = computed(() => props.demand?.status === 'sent');
const answered = computed(() => !!props.demand?.response);
// Wat de klant ziet als stand: zijn eigen reactie, anders geopend of verstuurd.
const state = computed(() => {
  const d = props.demand;
  if (d.status !== 'sent') return { cls: d.status === 'paid' ? 'ok' : 'off', label: props.t.status[d.status] };
  if (d.response) return { cls: d.response === 'dispute' ? 'warn' : 'info', label: props.t.status[d.response] };
  return { cls: 'plain', label: props.t.status[d.opened ? 'opened' : 'sent'] };
});

const form = useForm({ response: '', date: '', note: '', website: '' });
const choose = (response) => {
  form.clearErrors();
  form.response = response;
  form.date = '';
};
const submit = () => {
  if (props.demo) return;
  form.post(route('demand.respond', props.token), { preserveScroll: true, onSuccess: () => form.reset() });
};
const options = computed(() => [
  { key: 'paid', label: props.t.opt_paid, hint: props.t.opt_paid_hint },
  { key: 'promise', label: props.t.opt_promise, hint: props.t.opt_promise_hint },
  { key: 'dispute', label: props.t.opt_dispute, hint: props.t.opt_dispute_hint },
]);

/* ---------- Het overzicht van de schuldeiser ---------- */
const copied = ref(false);
const copyLink = async () => {
  try { await navigator.clipboard.writeText(props.creditor.debtor_url); copied.value = true; setTimeout(() => { copied.value = false; }, 2000); } catch (e) { /* stil */ }
};
const act = (url, question) => {
  if (confirm(question)) router.post(url, { k: props.creditor.key }, { preserveScroll: true });
};
</script>

<template>
  <Head :title="valid ? t.letter.title + ' · ' + invoice.number : t.invalid_title">
    <meta name="robots" content="noindex, nofollow">
  </Head>
  <div class="dp-shell">
    <div v-if="demo" class="dp-demo">
      <span class="dp-badge">{{ t.demo.badge }}</span>
      <span>{{ t.demo.bar }}</span>
      <a :href="demo.url" class="dp-btn small" :style="{ background: color }">{{ t.demo.cta }}</a>
    </div>

    <div v-if="!valid" class="dp-doc">
      <h1>{{ t.invalid_title }}</h1>
      <p class="dp-p">{{ t.invalid_text }}</p>
    </div>

    <div v-else class="dp-doc">
      <div class="dp-head" :style="{ borderColor: color }">
        <div class="dp-company" :style="{ color }">{{ company.name }}</div>
        <div class="dp-meta">
          <div>{{ t.issued }}: {{ demand.issued_label }}</div>
          <div>{{ t.ref }}: {{ demand.reference }}</div>
        </div>
      </div>

      <div v-if="flash" class="dp-flash">{{ flash }}</div>
      <div v-if="pageError" class="dp-error">{{ pageError }}</div>

      <!-- Alleen voor de schuldeiser: wanneer de klant keek en wat hij antwoordde -->
      <div v-if="creditor" class="dp-cred">
        <div class="dp-label">{{ t.cred.title }}</div>
        <p class="dp-hint">{{ t.cred.note }}</p>
        <div class="dp-field-row">
          <span class="k">{{ t.cred.share }}</span>
          <span class="v mono">{{ creditor.debtor_url }} <button type="button" class="dp-link" @click="copyLink">{{ copied ? '✓' : t.cred.copy }}</button></span>
        </div>
        <div class="dp-field-row">
          <span class="k">{{ t.cred.opened }}</span>
          <span class="v">
            <template v-if="creditor.first_open_label">{{ creditor.first_open_label }}<template v-if="creditor.last_open_label"> · {{ fill(t.cred.times, { n: creditor.opens, date: creditor.last_open_label }) }}</template></template>
            <template v-else>{{ t.cred.not_yet }}</template>
          </span>
        </div>
        <div class="dp-field-row">
          <span class="k">{{ t.cred.response }}</span>
          <span class="v">
            <template v-if="demand.response"><strong>{{ demand.response_label }}</strong> · {{ creditor.responded_at_label }}<div v-if="demand.response_note" class="dp-quote">“{{ demand.response_note }}”</div></template>
            <template v-else>{{ t.cred.none }}</template>
          </span>
        </div>
        <div v-if="creditor.facts" class="dp-field-row"><span class="k">{{ t.cred.facts }}</span><span class="v">{{ creditor.facts }}</span></div>

        <div v-if="creditor.actions && active" class="dp-cred-next">
          {{ creditor.can_transfer ? fill(t.cred.transfer_now, { partner: creditor.partner }) : fill(t.cred.transfer_wait, { date: demand.deadline_label }) }}
        </div>
        <div class="dp-cred-actions">
          <button v-if="creditor.can_transfer" type="button" class="dp-btn small" :style="{ background: color }" @click="act(creditor.actions.transfer, fill(t.cred.transfer_confirm, { partner: creditor.partner }))">{{ t.cred.transfer }}</button>
          <button v-if="creditor.can_close" type="button" class="dp-btn small ghost" @click="act(creditor.actions.paid, t.cred.paid_confirm)">{{ t.cred.paid }}</button>
          <a v-if="demand.pdf_url" :href="demand.pdf_url" target="_blank" rel="noopener" class="dp-btn small ghost">{{ t.cred.letter }}</a>
          <a v-if="creditor.invoice_url" :href="creditor.invoice_url" class="dp-btn small ghost">{{ t.cred.invoice }}</a>
          <button v-if="creditor.can_close" type="button" class="dp-link danger" @click="act(creditor.actions.withdraw, t.cred.withdraw_confirm)">{{ t.cred.withdraw }}</button>
        </div>
        <p class="dp-small">{{ t.cred.evidence }}</p>
      </div>

      <div class="dp-status">
        <span class="dp-tag" :class="state.cls">{{ state.label }}</span>
        <span v-if="active" class="dp-live" :title="t.per_day ? fill(t.per_day, { amount: claim.per_day_label }) : ''"> · {{ t.live }}</span>
      </div>

      <div v-if="demand.status === 'paid'" class="dp-final good">{{ t.state_paid }}</div>
      <div v-else-if="demand.status === 'transferred'" class="dp-final">{{ t.state_transferred }}</div>
      <div v-else-if="demand.status === 'withdrawn'" class="dp-final">{{ t.state_withdrawn }}</div>

      <div class="dp-parties">
        <div>
          <div class="dp-label">{{ t.creditor }}</div>
          <div class="dp-party">{{ company.holder || company.name }}<div v-if="company.kvk" class="sub">KvK {{ company.kvk }}</div></div>
        </div>
        <div>
          <div class="dp-label">{{ t.debtor }}</div>
          <div class="dp-party">{{ invoice.customer_name }}<div v-if="invoice.customer_kvk" class="sub">KvK {{ invoice.customer_kvk }}</div></div>
        </div>
      </div>

      <h1>{{ t.letter.title }}</h1>
      <p class="dp-p">{{ t.letter.intro }}</p>

      <template v-if="active">
        <!-- Het bedrag van vandaag -->
        <table class="dp-table">
          <tr><td>{{ t.letter.l_principal }}</td><td>{{ claim.principal_label }}</td></tr>
          <tr v-if="claim.with_interest"><td>{{ t.letter.l_interest }}</td><td>{{ claim.interest_label }}</td></tr>
          <tr v-if="claim.costs_due"><td>{{ t.letter.l_costs }}</td><td>{{ claim.costs_label }}</td></tr>
          <tr class="total"><td>{{ t.today }}</td><td>{{ claim.total_label }}</td></tr>
        </table>

        <p v-if="demand.file" class="dp-p">
          <strong>{{ t.attachment }}:</strong>{{ ' ' }}
          <a v-if="demand.file.url" :href="demand.file.url" target="_blank" rel="noopener">{{ demand.file.name }}</a><template v-else>{{ demand.file.name }}</template>
          <span class="sub"> ({{ demand.file.size_kb }} KB)</span>
        </p>

        <p class="dp-p">
          <template v-if="demand.expired">{{ t.deadline_passed }}</template>
          <template v-else>{{ t.letter.term }}</template>
          <template v-if="claim.with_interest && claim.per_day > 0">{{ ' ' + fill(t.per_day, { amount: claim.per_day_label }) }}</template>
        </p>
        <p v-if="!demand.expired" class="dp-p dp-warn"><strong>{{ t.deadline_open }}</strong> {{ fill(t.after, { amount: claim.total_after_label }) }} {{ t.letter.consequence_short }}</p>

        <!-- Betalen -->
        <div class="dp-pay">
          <div class="dp-bank">
            <div class="dp-label">{{ t.pay_title }}</div>
            <div v-if="company.iban" class="row"><span class="k">{{ t.pay_iban }}</span><span class="mono">{{ company.iban }}</span></div>
            <div class="row"><span class="k">{{ t.pay_name }}</span><span>{{ company.holder || company.name }}</span></div>
            <div class="row"><span class="k">{{ t.pay_amount }}</span><span class="mono">{{ claim.total_label }}</span></div>
            <div class="row"><span class="k">{{ t.pay_reference }}</span><span class="mono">{{ invoice.number }}</span></div>
            <a v-if="invoice.portal_url" :href="invoice.portal_url" class="dp-btn small" :style="{ background: color }">{{ t.pay_online }}</a>
          </div>
          <div v-if="qr" class="dp-qr">
            <img :src="qr" alt="">
            <div>{{ t.pay_qr }}</div>
          </div>
        </div>

        <!-- Reageren: één keer -->
        <div id="reactie" class="dp-respond">
          <div class="dp-label">{{ t.respond_title }}</div>
          <template v-if="answered">
            <p class="dp-hint">{{ t.respond_done }}</p>
            <div class="dp-answer">{{ demand.response_label }}</div>
            <div v-if="demand.response_note" class="dp-quote">“{{ demand.response_note }}”</div>
          </template>
          <p v-else-if="creditor" class="dp-hint">{{ t.cred.form_hidden }}</p>
          <form v-else @submit.prevent="submit">
            <p class="dp-hint">{{ t.respond_intro }}</p>
            <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="dp-trap" aria-hidden="true">
            <div class="dp-choices">
              <label v-for="o in options" :key="o.key" class="dp-choice" :class="{ on: form.response === o.key }" :style="form.response === o.key ? { borderColor: color } : {}">
                <input type="radio" name="response" :value="o.key" :checked="form.response === o.key" :disabled="!!demo" @change="choose(o.key)">
                <span><strong>{{ o.label }}</strong><small>{{ o.hint }}</small></span>
              </label>
            </div>
            <div v-if="form.errors.response" class="dp-err">{{ form.errors.response }}</div>

            <div class="dp-fields">
              <div v-if="form.response !== 'dispute'" class="dp-field">
                <label>{{ t.date }}</label>
                <input
                  type="date" v-model="form.date" :disabled="!!demo"
                  :min="form.response === 'promise' ? demand.today : undefined"
                  :max="form.response === 'promise' ? demand.promise_max : demand.today"
                  :required="form.response === 'promise'"
                >
                <div v-if="form.errors.date" class="dp-err">{{ form.errors.date }}</div>
              </div>
              <div class="dp-field wide">
                <label>{{ t.note }}</label>
                <textarea v-model="form.note" rows="3" maxlength="2000" :placeholder="t.note_placeholder" :required="form.response === 'dispute'" :disabled="!!demo"></textarea>
                <div v-if="form.errors.note" class="dp-err">{{ form.errors.note }}</div>
              </div>
            </div>
            <button type="submit" class="dp-btn" :style="{ background: color }" :disabled="form.processing || !!demo || !form.response">{{ t.send }}</button>
            <p class="dp-small">{{ demo ? t.demo.disabled : t.send_note }}</p>
          </form>
        </div>
      </template>

      <div class="dp-foot">
        <p>{{ t.footer }}</p>
        <p>{{ t.contact }}<template v-if="company.phone"> · {{ company.phone }}</template><template v-if="company.email && !demand.standalone"> · <a :href="'mailto:' + company.email">{{ company.email }}</a></template></p>
        <p class="dp-small">{{ t.letter.legal }}</p>
        <div class="dp-foot-actions">
          <a v-if="demand.pdf_url" :href="demand.pdf_url" target="_blank" rel="noopener" class="dp-btn small ghost">{{ t.print }}</a>
          <a :href="demand.make_url" class="dp-link">{{ t.make_own }}</a>
        </div>
      </div>
    </div>

    <!-- Voorbeeld: de mails die bij de aanmaning horen -->
    <div v-if="demo" class="dp-doc dp-mails">
      <h2>{{ t.demo.mails_title }}</h2>
      <p class="dp-hint">{{ t.demo.mails_text }}</p>
      <div v-for="(mail, i) in demo.mails" :key="i" class="dp-mail">
        <div class="dp-label">{{ mail.title }}</div>
        <div class="dp-field-row"><span class="k">{{ t.demo.to }}</span><span class="v">{{ mail.to }}</span></div>
        <div v-if="mail.reply_to" class="dp-field-row"><span class="k">{{ t.demo.reply_to }}</span><span class="v">{{ mail.reply_to }}</span></div>
        <div class="dp-field-row"><span class="k">{{ t.demo.subject }}</span><span class="v">{{ mail.subject }}</span></div>
        <div v-if="mail.attachments" class="dp-field-row"><span class="k">{{ t.demo.attachments }}</span><span class="v">{{ mail.attachments }}</span></div>
        <iframe :srcdoc="mail.html" sandbox="" :title="mail.title"></iframe>
      </div>
      <a :href="demo.url" class="dp-btn" :style="{ background: color }">{{ t.demo.cta }}</a>
    </div>
  </div>
</template>

<style scoped>
.dp-shell { min-height: 100vh; background: #F5F4F0; padding: 28px 16px 40px; font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1C1917; }
.dp-doc { max-width: 760px; margin: 0 auto; background: #fff; border: 1px solid #E7E5E4; border-radius: 14px; padding: 34px 40px 36px; box-shadow: 0 1px 3px rgba(28,25,23,0.06); }
.dp-doc + .dp-doc { margin-top: 22px; }
.dp-demo { max-width: 760px; margin: 0 auto 16px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 12px 16px; border-radius: 12px; background: #FEF9E7; border: 1px solid #F3E2A9; font-size: 14px; color: #44403C; }
.dp-demo .dp-btn { margin: 0 0 0 auto; }
.dp-badge { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; padding: 3px 9px; border-radius: 100px; background: #1C1917; color: #fff; }
.dp-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding-bottom: 18px; margin-bottom: 18px; border-bottom: 3px solid; }
.dp-company { font-family: 'Bricolage Grotesque', 'DM Sans', sans-serif; font-size: 21px; font-weight: 700; letter-spacing: -0.01em; }
.dp-meta { text-align: right; font-size: 12.5px; color: #78716C; line-height: 1.6; font-variant-numeric: tabular-nums; }
.dp-label { font-size: 11px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase; color: #78716C; margin-bottom: 6px; }
.dp-status { font-size: 13px; color: #78716C; margin-bottom: 16px; }
.dp-tag { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; padding: 4px 10px; border-radius: 6px; background: #F5F5F4; color: #44403C; border: 1px solid #E7E5E4; }
.dp-tag.info { background: #EFF6FF; color: #1D4ED8; border-color: #BFDBFE; }
.dp-tag.warn { background: #FEF3C7; color: #92400E; border-color: #FCD34D; }
.dp-tag.ok { background: #ECFDF5; color: #065F46; border-color: #A7F3D0; }
.dp-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 22px; }
.dp-party { font-weight: 600; font-size: 15.5px; line-height: 1.5; }
.sub { font-weight: 400; color: #78716C; font-size: 13px; }
h1 { font-family: 'Bricolage Grotesque', 'DM Sans', sans-serif; font-size: 27px; margin: 0 0 12px; letter-spacing: -0.02em; }
h2 { font-family: 'Bricolage Grotesque', 'DM Sans', sans-serif; font-size: 22px; margin: 0 0 8px; letter-spacing: -0.015em; }
.dp-p { font-size: 15px; line-height: 1.65; color: #292524; margin: 0 0 12px; }
.dp-p a { color: inherit; }
.dp-warn { padding: 12px 14px; border-radius: 10px; background: #FFFBEB; border: 1px solid #FCD34D; color: #44403C; }
.dp-table { width: 100%; border-collapse: collapse; margin: 16px 0 18px; font-size: 15px; font-variant-numeric: tabular-nums; }
.dp-table td { padding: 11px 12px; border-bottom: 1px solid #E7E5E4; }
.dp-table td:last-child { text-align: right; white-space: nowrap; }
.dp-table tr.total td { border-bottom: 0; border-top: 2px solid #1C1917; font-weight: 700; font-size: 17px; background: #FAFAF9; }
.dp-flash { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 14px; }
.dp-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 14px; }
.dp-final { background: #F5F5F4; border-radius: 8px; padding: 12px 14px; font-size: 14.5px; line-height: 1.6; margin-bottom: 18px; }
.dp-final.good { background: #ECFDF5; color: #065F46; }
.dp-pay { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; margin: 20px 0 6px; padding: 16px 18px; border: 1px solid #E7E5E4; border-radius: 12px; }
.dp-bank { flex: 1; min-width: 250px; font-size: 14px; }
.dp-bank .row { display: flex; gap: 12px; padding: 4px 0; }
.dp-bank .k { color: #78716C; width: 130px; flex: 0 0 130px; }
.mono { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 13px; }
.dp-qr { text-align: center; font-size: 12px; color: #78716C; width: 140px; }
.dp-qr img { width: 128px; height: 128px; display: block; margin: 0 auto 4px; }
.dp-respond { margin-top: 22px; padding: 20px 22px; border-radius: 12px; background: #FAF6EC; border: 1px solid #EFE6CF; }
.dp-cred { margin-bottom: 20px; padding: 18px 20px; border-radius: 12px; background: #F0F9FF; border: 1px solid #BAE6FD; }
.dp-cred-next { margin-top: 12px; font-size: 14px; line-height: 1.6; color: #292524; }
.dp-cred-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 12px; }
.dp-field-row { display: grid; grid-template-columns: 190px 1fr; gap: 12px; padding: 8px 0; border-top: 1px solid rgba(28,25,23,0.08); font-size: 14px; }
.dp-field-row .k { color: #78716C; }
.dp-field-row .v { overflow-wrap: anywhere; }
.dp-hint { font-size: 13.5px; color: #57534E; line-height: 1.6; margin: 0 0 12px; }
.dp-choices { display: grid; gap: 8px; margin-bottom: 12px; }
.dp-choice { display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border: 2px solid #E7E5E4; border-radius: 10px; background: #fff; cursor: pointer; }
.dp-choice input { width: 18px; height: 18px; margin-top: 2px; flex: none; }
.dp-choice strong { display: block; font-size: 14.5px; }
.dp-choice small { display: block; font-size: 12.5px; color: #78716C; margin-top: 2px; line-height: 1.5; }
.dp-fields { display: grid; grid-template-columns: 200px 1fr; gap: 14px; }
.dp-field.wide:first-child { grid-column: 1 / -1; }
.dp-field label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #78716C; margin-bottom: 5px; }
.dp-field input, .dp-field textarea { width: 100%; box-sizing: border-box; border: 1px solid #D6D3D1; border-radius: 8px; padding: 9px 12px; font-size: 14px; font-family: inherit; background: #fff; }
.dp-trap { position: absolute; left: -9999px; }
.dp-err { color: #B91C1C; font-size: 12.5px; margin-top: 4px; }
.dp-small { font-size: 12px; color: #A8A29E; line-height: 1.6; margin: 10px 0 0; }
.dp-btn { display: inline-block; border: 0; color: #fff; font-weight: 600; font-size: 15px; padding: 12px 22px; border-radius: 100px; cursor: pointer; text-decoration: none; margin-top: 14px; font-family: inherit; }
.dp-btn.small { font-size: 13.5px; padding: 8px 16px; margin-top: 0; }
.dp-btn.ghost { background: #fff; color: #1C1917; border: 1px solid #D6D3D1; }
.dp-btn:disabled { opacity: 0.55; cursor: default; }
.dp-link { background: none; border: 0; color: #57534E; text-decoration: underline; cursor: pointer; font-size: 13px; padding: 0; font-family: inherit; }
.dp-link.danger { color: #B91C1C; margin-left: auto; }
.dp-answer { font-weight: 600; font-size: 15px; }
.dp-quote { font-size: 14px; color: #44403C; margin-top: 4px; white-space: pre-wrap; }
.dp-foot { margin-top: 26px; padding-top: 18px; border-top: 1px solid #E7E5E4; font-size: 13px; color: #78716C; line-height: 1.6; }
.dp-foot p { margin: 0 0 6px; }
.dp-foot a { color: inherit; }
.dp-foot-actions { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; margin-top: 12px; }
.dp-mail { margin: 22px 0 26px; }
.dp-mail iframe { width: 100%; height: 520px; border: 1px solid #E7E5E4; border-radius: 10px; margin-top: 10px; background: #FAFAF9; }
@media (max-width: 640px) {
  .dp-doc { padding: 22px 18px 26px; }
  .dp-head { flex-direction: column; }
  .dp-meta { text-align: left; }
  .dp-parties, .dp-fields { grid-template-columns: 1fr; }
  .dp-field-row { grid-template-columns: 1fr; gap: 2px; }
  .dp-demo .dp-btn { margin-left: 0; }
}
</style>
