<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/**
 * De planningspagina voor de onderaannemer (openbaar, via de tokenlink).
 * Bevestigen dat hij op de geplande dag komt, een probleem melden, of
 * antwoorden op de vraag of hij eerder kan beginnen.
 */
const props = defineProps({
  valid: Boolean,
  token: String,
  company: Object,
  item: Object,
});

const page = usePage();
const brand = computed(() => page.props.brand || {});
const flash = computed(() => page.props.flash?.flash || null);
const pageError = computed(() => (page.props.errors || {}).plan ?? null);
const color = computed(() => props.company?.color || brand.value.color || '#DC2626');

const form = useForm({ action: 'confirm', start: '', message: '' });
const mode = ref(null); // null | 'counter' | 'problem'

const send = (action) => {
  form.action = action;
  form.post(route('plan.respond', props.token), { preserveScroll: true, onSuccess: () => { mode.value = null; form.message = ''; } });
};
const pending = computed(() => props.item?.request?.pending);
const closed = computed(() => !props.item?.project_open || props.item?.status === 'done');
</script>

<template>
  <Head :title="valid ? $t('Planning: :title', { title: item.title }) : $t('Planning')" />
  <div class="tr-shell">
    <div class="tr-card">
      <div class="tr-head" :style="{ background: color }">
        <div class="tr-company">{{ company?.name || brand.name }}</div>
        <div class="tr-kind">{{ $t('Planning') }}</div>
      </div>

      <div v-if="!valid" class="tr-body">
        <h1>{{ $t('Deze link is niet (meer) geldig') }}</h1>
        <p>{{ $t('Neem contact op met de opdrachtgever als u iets wilt doorgeven.') }}</p>
      </div>

      <div v-else class="tr-body">
        <h1>{{ item.title }}</h1>
        <p class="tr-intro" v-if="item.name">{{ $t('Beste :name,', { name: item.name }) }}</p>

        <div class="tr-box">
          <div><span class="k">{{ $t('Project') }}</span><span>{{ item.project }}</span></div>
          <div v-if="item.location"><span class="k">{{ $t('Locatie') }}</span><span>{{ item.location }}</span></div>
          <div v-if="item.starts_label"><span class="k">{{ $t('Geplande start') }}</span><span><strong>{{ item.starts_label }}</strong></span></div>
          <div v-if="item.ends_label"><span class="k">{{ $t('Tot en met') }}</span><span>{{ item.ends_label }}</span></div>
          <div v-if="item.notes" class="tr-desc">{{ item.notes }}</div>
        </div>

        <div v-if="flash" class="tr-flash">{{ flash }}</div>
        <div v-if="pageError" class="tr-error">{{ pageError }}</div>

        <template v-if="closed">
          <div class="tr-final">{{ item.status === 'done' ? $t('Dit onderdeel is afgerond. Bedankt voor uw werk.') : $t('Dit project is afgerond; reageren is niet meer nodig.') }}</div>
        </template>

        <!-- Vraag: eerder beginnen? -->
        <template v-else-if="pending">
          <div class="tr-ask">
            <div class="tr-ask-title">{{ $t('Kunt u eerder beginnen?') }}</div>
            <p>{{ $t('Het werk loopt voor op schema. :company vraagt of u kunt beginnen op :proposed in plaats van :planned.', { company: company.name, proposed: item.request.start_label, planned: item.starts_label }) }}</p>
            <p v-if="item.request.message" class="tr-quote">{{ item.request.message }}</p>
            <div class="tr-choices">
              <button type="button" class="tr-btn" :style="{ background: color }" :disabled="form.processing" @click="send('accepted')">{{ $t('Ja, wij beginnen op :date', { date: item.request.start_label }) }}</button>
              <button type="button" class="tr-btn secondary" :disabled="form.processing" @click="mode = mode === 'counter' ? null : 'counter'">{{ $t('Ja, maar op een andere dag') }}</button>
              <button type="button" class="tr-btn outline" :disabled="form.processing" @click="send('declined')">{{ $t('Nee, wij houden :date', { date: item.starts_label }) }}</button>
            </div>
            <form v-if="mode === 'counter'" @submit.prevent="send('counter')" class="tr-sub">
              <label>{{ $t('Wij kunnen beginnen op') }}</label>
              <input type="date" v-model="form.start" :min="item.request.start" :max="item.starts_on" required>
              <label>{{ $t('Toelichting (optioneel)') }}</label>
              <textarea v-model="form.message" rows="2" maxlength="2000"></textarea>
              <button type="submit" class="tr-btn" :style="{ background: color }" :disabled="form.processing">{{ $t('Dag doorgeven') }}</button>
            </form>
          </div>
        </template>

        <!-- Bevestigen of een probleem melden -->
        <template v-else>
          <div v-if="item.request && item.request.answer" class="tr-final good">
            <template v-if="item.request.answer === 'accepted'">{{ $t('U heeft toegezegd eerder te beginnen: :date. Bedankt!', { date: item.starts_label }) }}</template>
            <template v-else-if="item.request.answer === 'counter'">{{ $t('U heeft :date doorgegeven als startdag. Bedankt!', { date: item.starts_label }) }}</template>
            <template v-else>{{ $t('U heeft aangegeven de geplande start aan te houden: :date.', { date: item.starts_label }) }}</template>
          </div>
          <div v-else-if="item.confirmed_at" class="tr-final good">{{ $t('U heeft op :date bevestigd dat u op de geplande dag begint. Bedankt!', { date: item.confirmed_at }) }}</div>
          <div v-else-if="item.problem" class="tr-final">{{ $t('U heeft een probleem gemeld: ":problem". :company neemt contact met u op.', { problem: item.problem, company: company.name }) }}</div>

          <div v-if="mode !== 'problem'" class="tr-choices" style="margin-top: 14px;">
            <button v-if="!item.confirmed_at" type="button" class="tr-btn" :style="{ background: color }" :disabled="form.processing" @click="send('confirm')">{{ $t('Ja, wij zijn er op :date', { date: item.starts_label }) }}</button>
            <button type="button" class="tr-link" @click="mode = 'problem'">{{ item.problem ? $t('Nog iets doorgeven') : $t('Er zit iets in de weg') }}</button>
          </div>
          <form v-else @submit.prevent="send('problem')" class="tr-sub">
            <label>{{ $t('Wat zit er in de weg?') }}</label>
            <textarea v-model="form.message" rows="3" maxlength="2000" :placeholder="$t('Bijv. materiaal komt later, of wij kunnen pas een dag later')" required></textarea>
            <div class="tr-actions">
              <button type="submit" class="tr-btn secondary" :disabled="form.processing">{{ $t('Bericht sturen') }}</button>
              <button type="button" class="tr-link" @click="mode = null">{{ $t('Annuleren') }}</button>
            </div>
          </form>
        </template>

        <div class="tr-contact">
          {{ $t('Vragen? Neem contact op met :company', { company: company.name }) }}<template v-if="company.phone"> · {{ company.phone }}</template><template v-if="company.email"> · <a :href="'mailto:' + company.email">{{ company.email }}</a></template>
        </div>
      </div>
    </div>
    <div class="tr-footer">{{ $t('Verzonden via :brand namens :company.', { brand: brand.name, company: company?.name || '' }) }}</div>
  </div>
</template>

<style scoped>
.tr-shell { min-height: 100vh; background: #F5F5F4; padding: 32px 16px; font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1C1917; }
.tr-card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 1px 3px rgba(28,25,23,0.08); overflow: hidden; }
.tr-head { padding: 22px 32px; color: #fff; display: flex; justify-content: space-between; align-items: baseline; }
.tr-company { font-size: 18px; font-weight: 700; }
.tr-kind { font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.85; }
.tr-body { padding: 28px 32px 30px; }
.tr-body h1 { font-size: 22px; margin: 0 0 8px; letter-spacing: -0.015em; }
.tr-intro { color: #44403C; line-height: 1.6; margin: 0 0 16px; font-size: 14.5px; }
.tr-box { background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 10px; padding: 14px 16px; font-size: 14px; margin-bottom: 18px; }
.tr-box > div { display: flex; gap: 12px; padding: 3px 0; }
.tr-box .k { color: #78716C; width: 140px; flex: 0 0 140px; }
.tr-desc { display: block !important; white-space: pre-wrap; margin-top: 10px; padding-top: 10px; border-top: 1px solid #E7E5E4; line-height: 1.6; color: #44403C; }
.tr-flash { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 14px; }
.tr-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 14px; }
.tr-final { background: #F5F5F4; border-radius: 8px; padding: 12px 14px; font-size: 14.5px; line-height: 1.6; margin-bottom: 14px; }
.tr-final.good { background: #ECFDF5; color: #065F46; }
.tr-ask { border: 1px solid #E7E5E4; border-radius: 10px; padding: 16px 18px; }
.tr-ask-title { font-weight: 700; font-size: 16px; margin-bottom: 6px; }
.tr-ask p { font-size: 14.5px; line-height: 1.6; color: #44403C; margin: 0 0 12px; }
.tr-quote { border-left: 3px solid #D6D3D1; padding-left: 10px; color: #57534E; }
.tr-choices { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; }
.tr-btn { border: 0; color: #fff; font-weight: 600; font-size: 15px; padding: 12px 22px; border-radius: 8px; cursor: pointer; }
.tr-btn.secondary { background: #78716C; font-size: 14px; padding: 10px 16px; }
.tr-btn.outline { background: #fff; color: #44403C; border: 1px solid #D6D3D1; font-size: 14px; padding: 10px 16px; }
.tr-btn:disabled { opacity: 0.6; cursor: default; }
.tr-link { background: none; border: 0; color: #78716C; text-decoration: underline; cursor: pointer; font-size: 13.5px; padding: 6px 0; }
.tr-sub { margin-top: 14px; padding-top: 14px; border-top: 1px solid #E7E5E4; }
.tr-sub label { display: block; font-size: 13px; font-weight: 600; margin: 8px 0 5px; }
.tr-sub input, .tr-sub textarea { width: 100%; box-sizing: border-box; border: 1px solid #D6D3D1; border-radius: 8px; padding: 9px 12px; font-size: 14px; font-family: inherit; margin-bottom: 6px; }
.tr-sub input[type="date"] { max-width: 220px; }
.tr-actions { display: flex; align-items: center; gap: 16px; margin-top: 6px; flex-wrap: wrap; }
.tr-contact { margin-top: 22px; padding-top: 16px; border-top: 1px solid #E7E5E4; font-size: 13px; color: #78716C; }
.tr-contact a { color: inherit; }
.tr-footer { text-align: center; font-size: 12px; color: #A8A29E; margin-top: 18px; }
@media (max-width: 480px) { .tr-body, .tr-head { padding-left: 18px; padding-right: 18px; } .tr-box .k { width: 105px; flex-basis: 105px; } }
</style>
