<script setup>
import { computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  reminders: Object,
  default_payment_terms: Number,
});

const form = useForm({ ...props.reminders });

const timeline = computed(() => {
  const r = form;
  const basePT = props.default_payment_terms || 7;
  const events = [];
  events.push({ type: 'send', day: 0, name: 'Factuur versturen' });
  events.push({ type: 'sub',  day: basePT, name: 'Einde betaaltermijn' });
  let day = basePT + 1 + (Number(r.reminder_delay) || 0);
  for (let i = 1; i <= r.num_reminders; i++) {
    events.push({ type: 'reminder', day, name: i === 1 ? 'Eerste herinnering' : i === 2 ? 'Tweede herinnering' : `${i}e herinnering` });
    day += Number(r.payment_term_reminder) || 0;
    events.push({ type: 'sub', day, name: 'Einde betaaltermijn' });
    day += 1;
  }
  day += Number(r.warning_delay) || 0;
  for (let i = 1; i <= 2; i++) {
    events.push({ type: 'warning', day, name: i === 1 ? 'Eerste aanmaning' : 'Tweede aanmaning' });
    day += Number(r.payment_term_warning) || 0;
    events.push({ type: 'sub', day, name: 'Einde betaaltermijn' });
    day += 1;
  }
  return events;
});

const submit = () => form.patch(route('settings.reminders.update'), { preserveScroll: true });
</script>

<template>
  <Head title="Herinneringen" />
  <AppLayout>
    <template #breadcrumb>Instellingen / <span class="breadcrumb-current">Herinneringen</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" @click="submit" :disabled="form.processing">Opslaan</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Herinneringen en aanmaningen</h1>
        <p class="page-subtitle">Stel het herinneringsschema in voor te late betalers</p>
      </div>
    </div>

    <div class="single-col">
      <div class="card">
        <div class="card-header"><div class="card-title">Herinneringen en aanmaningen</div></div>
        <div class="card-body">
          <div class="setting-line">
            <div>Betaaltermijn herinnering</div>
            <div><input type="number" v-model.number="form.payment_term_reminder" min="0" max="60" /> <span>dagen</span></div>
          </div>
          <div class="setting-line">
            <div>Betaaltermijn aanmaning</div>
            <div><input type="number" v-model.number="form.payment_term_warning" min="0" max="60" /> <span>dagen</span></div>
          </div>
          <div class="setting-line">
            <div>Aantal herinneringen</div>
            <div><input type="number" v-model.number="form.num_reminders" min="0" max="5" /></div>
          </div>
          <div class="setting-line">
            <div>Tweede herinnering e-mail</div>
            <div>
              <select v-model="form.second_reminder_email">
                <option value="first">Als eerste herinnering</option>
                <option value="custom">Eigen tekst</option>
              </select>
            </div>
          </div>
          <div class="setting-line">
            <div>Negatief openstaand bedrag</div>
            <div>
              <label><input type="checkbox" v-model="form.negative_outstanding" /> Stuur wel herinneringen indien het openstaand bedrag negatief is.</label>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:14px;">
        <div class="card-header"><div class="card-title">Later herinneren en/of aanmanen</div></div>
        <div class="card-body">
          <div class="setting-line">
            <div>Herinneringen later versturen</div>
            <div><input type="number" v-model.number="form.reminder_delay" min="0" max="30" /> <span>dagen</span></div>
          </div>
          <div class="setting-line">
            <div>Aanmaningen later versturen</div>
            <div><input type="number" v-model.number="form.warning_delay" min="0" max="30" /> <span>dagen</span></div>
          </div>
        </div>
      </div>

      <div class="tl-preview-card">
        <div class="tl-title">Hoe werken betalingsherinneringen op basis van jouw instellingen?</div>
        <div class="reminder-timeline">
          <template v-for="(e, i) in timeline" :key="i">
            <div v-if="e.type === 'sub'" class="tl-row sub">
              <span class="tl-day">Dag {{ e.day }}</span>
              <span class="tl-name">{{ e.name }}</span>
            </div>
            <div v-else class="tl-row main" :class="e.type">
              <span class="tl-dot"></span>
              <span class="tl-day">Dag {{ e.day }}</span>
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
</style>
