<script setup>
import { computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { t } from '@/i18n';

const props = defineProps({
  reminders: Object,
  default_payment_terms: Number,
});

const form = useForm({ ...props.reminders });

const timeline = computed(() => {
  const r = form;
  const basePT = props.default_payment_terms || 7;
  const events = [];
  events.push({ type: 'send', day: 0, name: t('Factuur versturen') });
  events.push({ type: 'sub',  day: basePT, name: t('Einde betaaltermijn') });
  let day = basePT + 1 + (Number(r.reminder_delay) || 0);
  for (let i = 1; i <= r.num_reminders; i++) {
    events.push({ type: 'reminder', day, name: i === 1 ? t('Eerste herinnering') : i === 2 ? t('Tweede herinnering') : t(':ne herinnering', { n: i }) });
    day += Number(r.payment_term_reminder) || 0;
    events.push({ type: 'sub', day, name: t('Einde betaaltermijn') });
    day += 1;
  }
  day += Number(r.warning_delay) || 0;
  for (let i = 1; i <= 2; i++) {
    events.push({ type: 'warning', day, name: i === 1 ? t('Eerste aanmaning') : t('Tweede aanmaning') });
    day += Number(r.payment_term_warning) || 0;
    events.push({ type: 'sub', day, name: t('Einde betaaltermijn') });
    day += 1;
  }
  return events;
});

const submit = () => form.patch(route('settings.reminders.update'), { preserveScroll: true });
</script>

<template>
  <Head :title="$t('Herinneringen')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Instellingen') }} / <span class="breadcrumb-current">{{ $t('Herinneringen') }}</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" @click="submit" :disabled="form.processing">{{ $t('Opslaan') }}</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Herinneringen en aanmaningen') }}</h1>
        <p class="page-subtitle">{{ $t('Stel het herinneringsschema in voor te late betalers') }}</p>
      </div>
    </div>

    <div class="single-col">
      <div class="card">
        <div class="card-header"><div class="card-title">{{ $t('Herinneringen en aanmaningen') }}</div></div>
        <div class="card-body">
          <div class="setting-line">
            <div>{{ $t('Betaaltermijn herinnering') }}</div>
            <div><input type="number" v-model.number="form.payment_term_reminder" min="0" max="60" /> <span>{{ $t('dagen') }}</span></div>
          </div>
          <div class="setting-line">
            <div>{{ $t('Betaaltermijn aanmaning') }}</div>
            <div><input type="number" v-model.number="form.payment_term_warning" min="0" max="60" /> <span>{{ $t('dagen') }}</span></div>
          </div>
          <div class="setting-line">
            <div>{{ $t('Aantal herinneringen') }}</div>
            <div><input type="number" v-model.number="form.num_reminders" min="0" max="5" /></div>
          </div>
          <div class="setting-line">
            <div>{{ $t('Tweede herinnering e-mail') }}</div>
            <div>
              <select v-model="form.second_reminder_email">
                <option value="first">{{ $t('Als eerste herinnering') }}</option>
                <option value="custom">{{ $t('Eigen tekst') }}</option>
              </select>
            </div>
          </div>
          <div class="setting-line">
            <div>{{ $t('Negatief openstaand bedrag') }}</div>
            <div>
              <label><input type="checkbox" v-model="form.negative_outstanding" /> {{ $t('Stuur wel herinneringen indien het openstaand bedrag negatief is.') }}</label>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:14px;">
        <div class="card-header"><div class="card-title">{{ $t('Later herinneren en/of aanmanen') }}</div></div>
        <div class="card-body">
          <div class="setting-line">
            <div>{{ $t('Herinneringen later versturen') }}</div>
            <div><input type="number" v-model.number="form.reminder_delay" min="0" max="30" /> <span>{{ $t('dagen') }}</span></div>
          </div>
          <div class="setting-line">
            <div>{{ $t('Aanmaningen later versturen') }}</div>
            <div><input type="number" v-model.number="form.warning_delay" min="0" max="30" /> <span>{{ $t('dagen') }}</span></div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:14px;">
        <div class="card-header"><div class="card-title">{{ $t('Teksten') }}</div></div>
        <div class="card-body" style="padding:18px 20px;">
          <p class="txt-help">
            {{ $t('Deze teksten worden verstuurd. Gebruik variabelen — ze worden automatisch ingevuld:') }}
            <code>{klant}</code> <code>{factuurnummer}</code> <code>{factuurdatum}</code>
            <code>{vervaldatum}</code> <code>{bedrag}</code> <code>{openstaand}</code>
            <code>{termijn}</code> <code>{iban}</code> <code>{bedrijf}</code>
          </p>

          <div class="txt-block">
            <div class="txt-label">{{ $t('Herinnering — onderwerp') }}</div>
            <input type="text" v-model="form.reminder_subject" />
            <div class="txt-label">{{ $t('Herinnering — bericht') }}</div>
            <textarea v-model="form.reminder_body" rows="8"></textarea>
          </div>

          <div class="txt-block">
            <div class="txt-label">{{ $t('Aanmaning — onderwerp') }}</div>
            <input type="text" v-model="form.warning_subject" />
            <div class="txt-label">{{ $t('Aanmaning — bericht') }}</div>
            <textarea v-model="form.warning_body" rows="8"></textarea>
          </div>
        </div>
      </div>

      <div class="tl-preview-card">
        <div class="tl-title">{{ $t('Hoe werken betalingsherinneringen op basis van jouw instellingen?') }}</div>
        <div class="reminder-timeline">
          <template v-for="(e, i) in timeline" :key="i">
            <div v-if="e.type === 'sub'" class="tl-row sub">
              <span class="tl-day">{{ $t('Dag :n', { n: e.day }) }}</span>
              <span class="tl-name">{{ e.name }}</span>
            </div>
            <div v-else class="tl-row main" :class="e.type">
              <span class="tl-dot"></span>
              <span class="tl-day">{{ $t('Dag :n', { n: e.day }) }}</span>
              <span class="tl-name">{{ e.name }}</span>
            </div>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.setting-line { display: grid; grid-template-columns: 280px 1fr; gap: 24px; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); }
.setting-line:last-child { border-bottom: none; }
.setting-line > div { display: flex; align-items: center; gap: 10px; }
.setting-line input[type="number"] { width: 110px; text-align: center; }
.setting-line select { min-width: 220px; }
.setting-line span { color: var(--text-3); font-size: 13px; }
.setting-line label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-2); cursor: pointer; }
.setting-line:last-child { border-bottom: none; }
.setting-line input[type="number"] { width: 80px; }
.tl-preview-card { background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 22px; margin-top: 14px; }
.tl-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 14px; }
.reminder-timeline { position: relative; padding-left: 28px; }
.reminder-timeline::before { content: ''; position: absolute; left: 13px; top: 18px; bottom: 18px; width: 2px; background: var(--border); }
.tl-row { display: flex; align-items: center; gap: 12px; padding: 5px 0; font-size: 13px; }
.tl-row.main { margin-left: -28px; padding-left: 0; }
.tl-row.sub { padding-left: 0; color: var(--text-3); }
.tl-dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; margin-left: 6px; }
.tl-row.send .tl-dot { background: var(--success); }
.tl-row.reminder .tl-dot { background: var(--warning); }
.tl-row.warning .tl-dot { background: var(--brand); }
.tl-day { font-weight: 600; min-width: 50px; }
.tl-row.sub .tl-day { font-weight: 500; }

.txt-help { font-size: 12.5px; color: var(--text-3); line-height: 1.8; margin: 0 0 18px; }
.txt-help code { background: var(--surface-2); border: 1px solid var(--border); border-radius: 4px; padding: 1px 6px; font-size: 11.5px; color: var(--text-2); }
.txt-block { margin-bottom: 20px; }
.txt-block:last-child { margin-bottom: 0; }
.txt-label { font-size: 12.5px; font-weight: 600; color: var(--text-2); margin: 12px 0 6px; }
.txt-block input, .txt-block textarea { width: 100%; }

@media (max-width: 760px) {
  /* Label boven de instelling i.p.v. een vaste kolom van 280px ernaast. */
  .setting-line { grid-template-columns: minmax(0, 1fr); gap: 8px; align-items: flex-start; }
  .setting-line > div { flex-wrap: wrap; }
  .setting-line select { max-width: 100%; }
  .tl-row { flex-wrap: wrap; gap: 6px 10px; }
}
</style>
