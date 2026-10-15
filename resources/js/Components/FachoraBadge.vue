<script setup>
/**
 * Het keurmerk "Fachora Verified": nachtblauwe pil met een oranje vinkje.
 * Alleen tonen bij status 'verified'; voor andere statussen geeft `status`
 * een neutrale, grijze pil met het label.
 */
defineProps({
  verified: { type: Boolean, default: true },
  label: { type: String, default: null },
  small: { type: Boolean, default: false },
});
</script>

<template>
  <span v-if="verified" class="fv-badge" :class="{ 'fv-badge--sm': small }"><span class="fv-check">✓</span>{{ label || 'Fachora Verified' }}</span>
  <span v-else class="fv-badge fv-badge--muted" :class="{ 'fv-badge--sm': small }">{{ label || $t('Nog niet geverifieerd') }}</span>
</template>

<style scoped>
.fv-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px 4px 6px; border-radius: 100px; background: #0B2545; color: #fff; font-size: 11.5px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; white-space: nowrap; }
.fv-badge--sm { font-size: 10px; padding: 3px 8px 3px 5px; }
.fv-badge--muted { background: #E2E8F0; color: #475569; }
.fv-check { width: 18px; height: 18px; border-radius: 50%; background: #F4781F; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; }
.fv-badge--sm .fv-check { width: 15px; height: 15px; font-size: 9px; }
</style>
