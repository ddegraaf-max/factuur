import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * "Installeer als app" (PWA, 1.106.2).
 *
 * Chrome, Edge en Android geven een beforeinstallprompt; app.js houdt die vast in
 * window.__installPrompt en install() toont hem. Safari op iPhone en iPad kent geen
 * prompt: daar toont de aanroeper de stappen (Delen → Zet op beginscherm). In een al
 * geïnstalleerde app (display-mode: standalone) is de knop overbodig.
 */
export function useInstallApp() {
  const canPrompt = ref(!!window.__installPrompt);
  const installed = ref(false);
  const isStandalone = computed(() => installed.value
    || (!!window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
    || window.navigator.standalone === true);
  const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

  const onReady = () => { canPrompt.value = !!window.__installPrompt; };
  const onDone = () => { canPrompt.value = false; installed.value = true; };
  onMounted(() => {
    window.addEventListener('installprompt-ready', onReady);
    window.addEventListener('installprompt-done', onDone);
  });
  onBeforeUnmount(() => {
    window.removeEventListener('installprompt-ready', onReady);
    window.removeEventListener('installprompt-done', onDone);
  });

  /** Toont de installatiebalk van de browser; false als die er niet is (dan zelf uitleg tonen). */
  const install = async () => {
    const prompt = window.__installPrompt;
    if (!prompt) return false;
    prompt.prompt();
    const choice = await prompt.userChoice.catch(() => ({ outcome: 'dismissed' }));
    window.__installPrompt = null;
    canPrompt.value = false;
    if (choice && choice.outcome === 'accepted') installed.value = true;
    return true;
  };

  return { canPrompt, isStandalone, isIos, installed, install };
}
