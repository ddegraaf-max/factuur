<script setup>
/**
 * Cloudflare Turnstile widget voor Inertia/Vue.
 * Laadt het script zelf (ook na client-side navigatie) en rendert expliciet.
 * Als er geen sitekey is geconfigureerd, rendert het niets en blokkeert het niets.
 *
 * Gebruik:
 *   <Turnstile :sitekey="sitekey" @verified="t => form['cf-turnstile-response'] = t"
 *              @expired="() => form['cf-turnstile-response'] = ''" />
 */
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  sitekey: { type: String, default: '' },
});
const emit = defineEmits(['verified', 'expired']);

const el = ref(null);
let widgetId = null;
const SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

function loadScript() {
  return new Promise((resolve, reject) => {
    if (window.turnstile) return resolve();
    let s = document.querySelector('script[data-turnstile]');
    if (s) {
      s.addEventListener('load', () => resolve());
      s.addEventListener('error', reject);
      return;
    }
    s = document.createElement('script');
    s.src = SRC;
    s.async = true;
    s.defer = true;
    s.setAttribute('data-turnstile', '');
    s.onload = () => resolve();
    s.onerror = reject;
    document.head.appendChild(s);
  });
}

async function renderWidget() {
  if (!props.sitekey) return; // niet geconfigureerd → niets tonen
  try {
    await loadScript();
  } catch (e) {
    return;
  }
  if (!window.turnstile || !el.value) return;
  widgetId = window.turnstile.render(el.value, {
    sitekey: props.sitekey,
    callback: (token) => emit('verified', token),
    'expired-callback': () => emit('expired'),
    'error-callback': () => emit('expired'),
  });
}

onMounted(renderWidget);

onBeforeUnmount(() => {
  try {
    if (widgetId !== null && window.turnstile) {
      window.turnstile.remove(widgetId);
    }
  } catch (e) {
    /* stil */
  }
});
</script>

<template>
  <div v-if="sitekey" ref="el" class="cf-turnstile-holder" style="margin: 8px 0;"></div>
</template>
