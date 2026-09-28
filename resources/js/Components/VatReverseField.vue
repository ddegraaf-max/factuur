<script setup>
// Btw verlegd: de keuze op factuur en offerte. Staat het btw-nummer nog niet bij
// de klant, dan vul je het hier in; bij het opslaan wordt het bij de klant bewaard.
import { computed } from 'vue';

const props = defineProps({
  modelValue: Boolean, // btw verlegd aan of uit
  vatNumber: { type: String, default: '' },
  customer: { type: Object, default: null },
  error: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue', 'update:vatNumber']);

const missing = computed(() => props.modelValue && !props.customer?.vat_number);
</script>

<template>
  <div class="vr">
    <label class="vr-check">
      <input type="checkbox" :checked="modelValue" @change="emit('update:modelValue', $event.target.checked)">
      <span>
        <b>{{ $t('Btw verlegd') }}</b>
        <span class="vr-hint">{{ $t('De klant draagt de btw af, bijvoorbeeld bij onderaanneming in de bouw. Op het document staat geen btw, wel "btw verlegd" en het btw-nummer van de klant.') }}</span>
      </span>
    </label>
    <div v-if="missing" class="vr-number">
      <label for="vr-number">{{ $t('Btw-nummer van de klant') }} *</label>
      <input id="vr-number" type="text" :value="vatNumber" placeholder="NL123456789B01" maxlength="20" @input="emit('update:vatNumber', $event.target.value)">
      <div class="vr-hint">{{ $t('Dit nummer wordt bij de klant bewaard.') }}</div>
    </div>
    <div v-if="error" class="field-error">{{ error }}</div>
  </div>
</template>

<style scoped>
.vr { border-top: 1px solid var(--border); margin-top: 4px; padding-top: 14px; }
.vr-check { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13.5px; color: var(--text); line-height: 1.5; }
/* De algemene regel voor invoervelden maakt een vinkje 100% breed; hier dus een vaste maat. */
.vr-check input { width: 17px; height: 17px; padding: 0; margin: 2px 0 0; flex: none; accent-color: var(--brand); cursor: pointer; }
.vr-hint { display: block; font-size: 12px; color: var(--text-3); font-weight: 400; margin-top: 2px; }
.vr-number { margin: 12px 0 0 27px; max-width: 280px; }
.vr-number label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text-2); margin-bottom: 5px; }
.vr-number input { width: 100%; font-family: var(--font-mono); }
</style>
