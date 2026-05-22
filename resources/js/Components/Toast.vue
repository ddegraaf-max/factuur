<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  message: { type: String, default: null },
  type: { type: String, default: 'success' },
});

const visible = ref(false);
const text = ref('');

watch(() => props.message, (msg) => {
  if (msg) {
    text.value = msg;
    visible.value = true;
    setTimeout(() => visible.value = false, 3500);
  }
}, { immediate: true });
</script>

<template>
  <transition name="toast">
    <div v-if="visible" :class="['toast', type]">
      <svg v-if="type === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
      <span>{{ text }}</span>
    </div>
  </transition>
</template>

<style>
.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: var(--text);
  color: white;
  padding: 12px 16px;
  border-radius: var(--r);
  box-shadow: var(--shadow-lg);
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 500;
  z-index: 200;
  min-width: 260px;
}
.toast svg { width: 18px; height: 18px; flex-shrink: 0; }
.toast.success svg { color: #4ADE80; }

.toast-enter-active, .toast-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}
.toast-enter-from, .toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
