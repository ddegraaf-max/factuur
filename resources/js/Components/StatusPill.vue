<script setup>
const props = defineProps({
  status: String,
  daysOverdue: { type: Number, default: 0 },
});

const labels = {
  draft: 'Concept',
  sent: 'Verstuurd',
  partial: 'Deels betaald',
  paid: 'Betaald',
  overdue: 'Achterstallig',
  incasso: 'Bij incasso',
  cancelled: 'Geannuleerd',
};
</script>

<template>
  <span :class="['pill', `pill-${status}`]">
    <template v-if="status === 'overdue' && daysOverdue > 0">{{ daysOverdue }} d. over tijd</template>
    <template v-else>{{ labels[status] || status }}</template>
  </span>
</template>
