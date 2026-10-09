<script setup>
import { computed } from 'vue';
import { t } from '@/i18n';

const props = defineProps({
  status: String,
  daysOverdue: { type: Number, default: 0 },
  // Op pauze (geen herinneringen, aanmaningen of incasso): gaat vóór de gewone
  // status, die dan in de tooltip staat.
  paused: { type: Boolean, default: false },
  // Bij "deels betaald": wat er al binnen is en het factuurbedrag. Geef je ze
  // mee, dan staat er een taartje bij: groen is binnen, rood staat nog open.
  paid: { type: Number, default: null },
  total: { type: Number, default: null },
});

const labels = {
  draft: 'Concept',
  sent: 'Verstuurd',
  partial: 'Deels betaald',
  paid: 'Betaald',
  overdue: 'Achterstallig',
  incasso: 'Bij incasso',
  settled: 'Verrekend',
  cancelled: 'Geannuleerd',
};

const label = computed(() =>
  props.status === 'overdue' && props.daysOverdue > 0
    ? t(':n d. over tijd', { n: props.daysOverdue })
    : t(labels[props.status] || props.status)
);

// Het betaalde deel als breuk 0–1, alleen bij deels betaald met bekende bedragen.
const fraction = computed(() => {
  if (props.status !== 'partial' || props.paid === null || !props.total || props.total <= 0) return null;
  return Math.min(1, Math.max(0, props.paid / props.total));
});
const percent = computed(() => (fraction.value === null ? null : Math.round(fraction.value * 100)));

// Een taartpunt met een dikke cirkelrand: straal 3,5 en lijndikte 7 vullen de
// hele schijf; de dasharray tekent precies het betaalde deel, vanaf twaalf uur.
const R = 3.5;
const C = 2 * Math.PI * R;
const dash = computed(() => (fraction.value === null ? '' : `${(fraction.value * C).toFixed(3)} ${C.toFixed(3)}`));
</script>

<template>
  <span v-if="paused" class="pill pill-paused" :title="`${label} · ${$t('Op pauze: geen herinneringen, aanmaningen of incasso')}`">{{ $t('Op pauze') }}</span>
  <span v-else :class="['pill', `pill-${status}`, { 'has-pie': fraction !== null }]" :title="percent !== null ? $t(':p% betaald, de rest staat nog open', { p: percent }) : undefined">
    <svg v-if="fraction !== null" class="pill-pie" viewBox="0 0 14 14" width="12" height="12" aria-hidden="true">
      <circle cx="7" cy="7" :r="R" fill="none" stroke="#DC2626" :stroke-width="R * 2" />
      <circle cx="7" cy="7" :r="R" fill="none" stroke="#15803D" :stroke-width="R * 2" :stroke-dasharray="dash" transform="rotate(-90 7 7)" />
    </svg>
    {{ label }}
  </span>
</template>

<style scoped>
.pill-pie { display: inline-block; vertical-align: -1px; flex: none; }
/* Het taartje vervangt het standaardstipje van het label; anders staan er twee bolletjes. */
.pill.has-pie::before { display: none; }
</style>
