<script setup>
import { computed } from 'vue';
import { t } from '@/i18n';

const props = defineProps({
  status: String,
  daysOverdue: { type: Number, default: 0 },
  // Op pauze (geen herinneringen, aanmaningen of incasso): gaat vóór de gewone
  // status, die dan in de tooltip staat.
  paused: { type: Boolean, default: false },
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
</script>

<template>
  <span v-if="paused" class="pill pill-paused" :title="`${label} · ${$t('Op pauze: geen herinneringen, aanmaningen of incasso')}`">{{ $t('Op pauze') }}</span>
  <span v-else :class="['pill', `pill-${status}`]">{{ label }}</span>
</template>
